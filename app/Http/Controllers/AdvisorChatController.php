<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\Advisor\AdvisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdvisorChatController extends Controller
{
    public function __construct(private readonly AdvisorService $advisor) {}

    public function start(Request $request, string $agencySlug): JsonResponse
    {
        $agency = $this->publishedAgency($agencySlug);

        $locale = in_array($request->input('locale'), AgencyPageController::SUPPORTED_LOCALES, true)
            ? $request->input('locale')
            : $agency->default_locale;

        $conversation = $this->advisor->startConversation($agency, $locale);

        return response()->json([
            'visitor_token' => $conversation->visitor_token,
            'locale' => $conversation->locale,
        ]);
    }

    public function message(Request $request, string $agencySlug): JsonResponse
    {
        $agency = $this->publishedAgency($agencySlug);

        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'message' => ['required', 'string', 'max:2000'],
            'scene' => ['nullable', Rule::in(Conversation::SCENES)],
            'selected_property' => ['nullable', 'string', 'max:120'],
            'selected_place' => ['nullable', 'integer', 'min:1'],
            'viewing' => ['nullable', 'array'],
            'viewing.property_slug' => ['nullable', 'string', 'max:120'],
            'viewing.date' => ['nullable', 'date_format:Y-m-d'],
            'viewing.time' => ['nullable', 'date_format:H:i'],
            'viewing.party_size' => ['nullable', 'integer', 'min:1', 'max:10'],
            'viewing.payment_plan' => ['nullable', Rule::in(['cash', 'mortgage', 'undecided'])],
        ]);

        $conversation = $this->findConversation($agency, $data['visitor_token']);

        $this->advisor->rememberContext($agency, $conversation, [
            'scene' => $data['scene'] ?? null,
            'selected_place' => $data['selected_place'] ?? null,
            'selected_property' => $data['selected_property'] ?? ($data['viewing']['property_slug'] ?? null),
            ...array_key_exists('viewing', $data) ? ['viewing' => array_filter($data['viewing'] ?? [], fn ($value) => $value !== null && $value !== '')] : [],
        ]);

        try {
            $assistantMessage = $this->advisor->reply($agency, $conversation, $data['message']);
        } catch (\Throwable $e) {
            Log::error('Advisor reply failed', [
                'agency_id' => $agency->id,
                'conversation_id' => $conversation->id,
                'exception' => $e,
            ]);

            return response()->json([
                'error' => 'advisor_unavailable',
                'message' => __('site.advisor.error', [], $conversation->locale),
            ], 503);
        }

        return response()->json([
            'message' => $this->formatMessage($assistantMessage),
            'status' => $conversation->fresh()->status,
        ]);
    }

    public function history(Request $request, string $agencySlug): JsonResponse
    {
        $agency = $this->publishedAgency($agencySlug);

        $data = $request->validate(['visitor_token' => ['required', 'uuid']]);

        $conversation = $this->findConversation($agency, $data['visitor_token']);

        $messages = $conversation->messages()
            ->whereIn('role', [ConversationMessage::ROLE_VISITOR, ConversationMessage::ROLE_ASSISTANT, ConversationMessage::ROLE_AGENT, ConversationMessage::ROLE_SYSTEM])
            ->orderBy('created_at')
            ->get()
            ->map(fn (ConversationMessage $m) => $this->formatMessage($m))
            ->values();

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
        ]);
    }

    private function publishedAgency(string $agencySlug): Agency
    {
        $agency = Agency::where('slug', $agencySlug)->first();

        abort_if(! $agency || ! $agency->isPublished(), 404);

        return $agency;
    }

    private function findConversation(Agency $agency, string $visitorToken): Conversation
    {
        $conversation = Conversation::where('agency_id', $agency->id)
            ->where('visitor_token', $visitorToken)
            ->first();

        if (! $conversation) {
            throw ValidationException::withMessages([
                'visitor_token' => 'This conversation no longer exists. Please start a new one.',
            ]);
        }

        return $conversation;
    }

    private function formatMessage(ConversationMessage $message): array
    {
        return [
            'role' => $message->role,
            'content' => $message->content,
            'ui_payload' => $message->ui_payload,
            'suggested_actions' => $this->suggestedActions($message->ui_payload),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Showroom actions that follow from what the advisor just showed, so the
     * visitor can open the matching page instead of typing again.
     *
     * @param  list<array<string, mixed>>|null  $uiPayload
     * @return list<array{action: string, property?: string}>
     */
    private function suggestedActions(?array $uiPayload): array
    {
        $actions = [];

        foreach ($uiPayload ?? [] as $payload) {
            $type = $payload['type'] ?? null;
            $property = $payload['property']['property_slug'] ?? $payload['simulation']['property_slug'] ?? $payload['slots']['property_slug'] ?? null;

            if ($type === 'property_detail' && $property) {
                $actions[] = ['action' => 'view_property', 'property' => $property];
                $actions[] = ['action' => 'schedule', 'property' => $property];
            } elseif ($type === 'mortgage' && $property) {
                $actions[] = ['action' => 'schedule', 'property' => $property];
            } elseif ($type === 'handover') {
                $actions[] = ['action' => 'agent'];
            }
        }

        return collect($actions)->unique(fn (array $action) => $action['action'].($action['property'] ?? ''))->values()->all();
    }
}
