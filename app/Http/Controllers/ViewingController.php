<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\ViewingAppointment;
use App\Services\Viewing\ContactLinks;
use App\Services\Viewing\ViewingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ViewingController extends Controller
{
    private const PHONE_PATTERN = '/^\+?[0-9\s\-().]{6,20}$/';

    public function __construct(
        private readonly ViewingService $viewings,
        private readonly ContactLinks $contactLinks,
    ) {}

    /**
     * The free viewing times for one listing on one day.
     */
    public function slots(Request $request, string $agencySlug): JsonResponse
    {
        $agency = $this->publishedAgency($agencySlug);
        $this->useLocale($request, $agency);

        $data = $request->validate($this->scheduleRules(withTime: false), $this->validationMessages());
        [$property, $date] = $this->resolveSchedule($agency, $data);

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $this->viewings->availableSlots($agency, $property, $date),
            'all_slots' => ViewingService::TIME_SLOTS,
        ]);
    }

    public function store(Request $request, string $agencySlug): JsonResponse
    {
        $agency = $this->publishedAgency($agencySlug);
        $locale = $this->useLocale($request, $agency);

        $data = $request->validate([
            ...$this->scheduleRules(withTime: true),
            'visitor_name' => ['required', 'string', 'max:100'],
            'contact_type' => ['required', Rule::in(['whatsapp', 'phone', 'email'])],
            'contact_value' => ['required', 'string', 'max:120'],
            'party_size' => ['required', 'integer', 'min:1', 'max:'.ViewingService::MAX_PARTY_SIZE],
            'payment_plan' => ['required', Rule::in(ViewingAppointment::PAYMENT_PLANS)],
            'notes' => ['nullable', 'string', 'max:500'],
            'visitor_token' => ['nullable', 'uuid'],
        ], $this->validationMessages());

        $isEmail = $data['contact_type'] === 'email';

        if ($isEmail && ! filter_var($data['contact_value'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['contact_value' => __('site.viewing.errors.contact_email')]);
        }

        if (! $isEmail && ! preg_match(self::PHONE_PATTERN, $data['contact_value'])) {
            throw ValidationException::withMessages(['contact_value' => __('site.viewing.errors.contact_phone')]);
        }

        [$property, $date] = $this->resolveSchedule($agency, $data);

        $conversation = isset($data['visitor_token'])
            ? Conversation::where('agency_id', $agency->id)->where('visitor_token', $data['visitor_token'])->first()
            : null;

        $viewing = $this->viewings->createRequest($agency, $property, [
            'date' => $date,
            'time' => $data['time'],
            'visitor_name' => $data['visitor_name'],
            'visitor_email' => $isEmail ? $data['contact_value'] : null,
            'visitor_phone' => $isEmail ? null : $data['contact_value'],
            'contact_type' => $data['contact_type'],
            'party_size' => (int) $data['party_size'],
            'payment_plan' => $data['payment_plan'],
            'notes' => $data['notes'] ?? null,
        ], $conversation);

        if (! $viewing) {
            $message = __('site.viewing.errors.slot_taken');

            return response()->json([
                'message' => $message,
                'errors' => ['time' => [$message]],
                'slots' => $this->viewings->availableSlots($agency, $property, $date),
            ], 422);
        }

        return response()->json([
            'reference' => $viewing->reference,
            'status' => $viewing->status,
            'contact' => $this->contactLinks->forViewing($agency, $viewing, $locale),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleRules(bool $withTime): array
    {
        return [
            'property_slug' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d'],
            ...$withTime ? ['time' => ['required', Rule::in(ViewingService::TIME_SLOTS)]] : [],
            'locale' => ['nullable', Rule::in(AgencyPageController::SUPPORTED_LOCALES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        $invalid = __('site.viewing.errors.invalid');

        return [
            'required' => $invalid,
            'date_format' => $invalid,
            'integer' => $invalid,
            'min' => $invalid,
            'max' => $invalid,
            'in' => $invalid,
            'uuid' => $invalid,
            'time.required' => __('site.viewing.errors.slot_required'),
            'time.in' => __('site.viewing.errors.slot_required'),
            'property_slug.required' => __('site.viewing.errors.property_unknown'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Property, 1: CarbonImmutable}
     */
    private function resolveSchedule(Agency $agency, array $data): array
    {
        $property = $agency->properties()->where('is_active', true)->where('slug', $data['property_slug'])->first();

        if (! $property) {
            throw ValidationException::withMessages(['property_slug' => __('site.viewing.errors.property_unknown')]);
        }

        if (! $property->isAvailable()) {
            throw ValidationException::withMessages(['property_slug' => __('site.viewing.errors.property_unavailable')]);
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'], $agency->timezone)->startOfDay();

        if (! $this->viewings->isWithinWindow($agency, $date)) {
            throw ValidationException::withMessages(['date' => __('site.viewing.errors.date_range', [
                'from' => $this->viewings->earliestDate($agency)->toDateString(),
                'to' => $this->viewings->latestDate($agency)->toDateString(),
            ])]);
        }

        return [$property, $date];
    }

    private function useLocale(Request $request, Agency $agency): string
    {
        $locale = in_array($request->input('locale'), AgencyPageController::SUPPORTED_LOCALES, true)
            ? $request->input('locale')
            : $agency->default_locale;

        app()->setLocale($locale);

        return $locale;
    }

    private function publishedAgency(string $agencySlug): Agency
    {
        $agency = Agency::where('slug', $agencySlug)->first();

        abort_if(! $agency || ! $agency->isPublished(), 404);

        return $agency;
    }
}
