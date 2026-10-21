<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\Advisor\AdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AdvisorServiceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Conversation $conversation;

    private AdvisorService $advisor;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.local_llm' => ['base_url' => 'http://llm.test', 'api_key' => 'secret', 'model' => 'test-model']]);

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'city' => 'Jakarta', 'country' => 'Indonesia']);
        $this->agency->properties()->create(['name' => 'Garden House', 'slug' => 'garden-house', 'price' => 2_000_000_000, 'is_active' => true, 'units_available' => 1, 'listing_type' => 'sale']);

        $this->advisor = app(AdvisorService::class);
        $this->conversation = $this->advisor->startConversation($this->agency, 'en');
    }

    /**
     * @return array<string, mixed>
     */
    private function completion(?string $content, array $toolCalls = [], string $finish = 'stop'): array
    {
        return ['choices' => [['finish_reason' => $finish, 'message' => array_filter(['role' => 'assistant', 'content' => $content, 'tool_calls' => $toolCalls ?: null])]]];
    }

    /**
     * @return array<string, mixed>
     */
    private function toolCall(string $name, array $arguments): array
    {
        return ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)]];
    }

    public function test_a_plain_answer_is_stored_and_sent_with_the_configured_model(): void
    {
        Http::fake(['llm.test/*' => Http::response($this->completion('Hello! How can I help?'))]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Hi there');

        $this->assertSame('Hello! How can I help?', $reply->content);
        $this->assertSame(ConversationMessage::ROLE_ASSISTANT, $reply->role);
        $this->assertSame(2, $this->conversation->messages()->count());

        Http::assertSent(fn (Request $request) => $request->url() === 'http://llm.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer secret')
            && $request['model'] === 'test-model'
            && $request['reasoning_effort'] === 'none'
            && $request['messages'][0]['role'] === 'system');
    }

    public function test_tool_calls_are_executed_and_their_cards_are_attached_to_the_reply(): void
    {
        Http::fake(['llm.test/*' => Http::sequence()
            ->push($this->completion(null, [$this->toolCall('get_property_detail', ['property_slug' => 'garden-house'])]))
            ->push($this->completion('A bright family home.')),
        ]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Tell me about Garden House');

        $this->assertSame('A bright family home.', $reply->content);
        $this->assertSame('get_property_detail', $reply->tool_calls[0]['name']);
        $this->assertSame('property_detail', $reply->ui_payload[0]['type']);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => collect($request['messages'])->contains(fn ($m) => $m['role'] === 'tool' && $m['tool_call_id'] === 'call_1' && str_contains($m['content'], 'Garden House')));
    }

    public function test_an_unknown_tool_does_not_break_the_turn(): void
    {
        Http::fake(['llm.test/*' => Http::sequence()
            ->push($this->completion(null, [$this->toolCall('drop_database', [])]))
            ->push($this->completion('Sorry, I cannot do that.')),
        ]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Do something odd');

        $this->assertSame('Sorry, I cannot do that.', $reply->content);
        Http::assertSent(fn (Request $request) => collect($request['messages'])->contains(fn ($m) => $m['role'] === 'tool' && str_contains($m['content'], 'Unknown tool')));
    }

    public function test_a_price_stated_without_a_tool_call_is_challenged_once(): void
    {
        Http::fake(['llm.test/*' => Http::sequence()
            ->push($this->completion('Garden House costs Rp 2 miliar.'))
            ->push($this->completion('Let me check the details for you first.')),
        ]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'How much is Garden House?');

        $this->assertSame('Let me check the details for you first.', $reply->content);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => collect($request['messages'])->contains(fn ($m) => $m['role'] === 'user' && str_contains($m['content'], 'System notice')));
    }

    public function test_a_price_taken_from_a_tool_result_is_delivered_as_written(): void
    {
        Http::fake(['llm.test/*' => Http::sequence()
            ->push($this->completion(null, [$this->toolCall('get_property_detail', ['property_slug' => 'garden-house'])]))
            ->push($this->completion('Garden House is priced at Rp 2 miliar and fits a family.')),
        ]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Tell me about Garden House');

        $this->assertSame('Garden House is priced at Rp 2 miliar and fits a family.', $reply->content);
        Http::assertSentCount(2);
    }

    public function test_a_price_that_contradicts_the_tool_result_is_challenged_and_then_replaced(): void
    {
        Http::fake(['llm.test/*' => Http::sequence()
            ->push($this->completion(null, [$this->toolCall('get_property_detail', ['property_slug' => 'garden-house'])]))
            ->push($this->completion('Garden House is only Rp 1,2 miliar!'))
            ->push($this->completion('It is a bargain at Rp 1,2 miliar.')),
        ]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Tell me about Garden House');

        $this->assertSame(__('site.advisor.unverified_figures', [], 'en'), $reply->content);
        $this->assertSame('property_detail', $reply->ui_payload[0]['type']);
        Http::assertSentCount(3);
    }

    public function test_a_price_repeated_from_an_earlier_answer_needs_no_new_tool_call(): void
    {
        $this->conversation->messages()->create(['role' => ConversationMessage::ROLE_VISITOR, 'content' => 'How much is it?']);
        $this->conversation->messages()->create(['role' => ConversationMessage::ROLE_ASSISTANT, 'content' => 'Garden House is Rp 2 miliar.']);
        Http::fake(['llm.test/*' => Http::response($this->completion('As said, the price is Rp 2 miliar.'))]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Remind me the price');

        $this->assertSame('As said, the price is Rp 2 miliar.', $reply->content);
        Http::assertSentCount(1);
    }

    public function test_the_tool_loop_stops_after_the_iteration_limit_and_fails_instead_of_replying_with_nothing(): void
    {
        Http::fake(['llm.test/*' => Http::response($this->completion(null, [$this->toolCall('search_knowledge', ['query' => 'parking'])]))]);

        try {
            $this->advisor->reply($this->agency, $this->conversation, 'Is there parking?');
            $this->fail('Expected an empty reply to be reported as a failure.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('empty reply after 6 tool rounds', $e->getMessage());
        }

        Http::assertSentCount(7);
        $this->assertSame(0, $this->conversation->messages()->count());
    }

    public function test_an_empty_reply_with_cards_is_still_delivered(): void
    {
        Http::fake(['llm.test/*' => Http::sequence()
            ->push($this->completion(null, [$this->toolCall('get_property_detail', ['property_slug' => 'garden-house'])]))
            ->push($this->completion('')),
        ]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Show me Garden House');

        $this->assertNull($reply->content);
        $this->assertSame('property_detail', $reply->ui_payload[0]['type']);
    }

    public function test_an_offensive_visitor_message_never_reaches_the_model(): void
    {
        Http::fake();

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'dasar anjing lu');

        $this->assertSame(ConversationMessage::ROLE_ASSISTANT, $reply->role);
        $this->assertNotEmpty($reply->content);
        Http::assertNothingSent();
    }

    public function test_an_offensive_model_reply_is_replaced_with_the_refusal(): void
    {
        Http::fake(['llm.test/*' => Http::response($this->completion('dasar anjing lu'))]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Hello');

        $this->assertStringNotContainsStringIgnoringCase('anjing', (string) $reply->content);
        $this->assertNotEmpty($reply->content);
    }

    public function test_a_handed_over_conversation_is_not_answered_by_the_ai(): void
    {
        Http::fake();
        $this->conversation->update(['status' => Conversation::STATUS_HANDED_OVER]);

        $reply = $this->advisor->reply($this->agency, $this->conversation, 'Anyone there?');

        $this->assertSame(ConversationMessage::ROLE_SYSTEM, $reply->role);
        $this->assertSame(__('site.advisor.handed_over', [], 'en'), $reply->content);
        Http::assertNothingSent();
    }

    public function test_a_failed_model_call_removes_the_visitor_message_so_a_retry_does_not_duplicate_it(): void
    {
        Http::fake(['llm.test/*' => Http::response('boom', 500)]);

        try {
            $this->advisor->reply($this->agency, $this->conversation, 'Hi');
            $this->fail('Expected the failed model call to throw.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('HTTP 500', $e->getMessage());
        }

        $this->assertSame(0, $this->conversation->messages()->count());
    }

    public function test_a_model_that_runs_out_of_tokens_is_reported_as_a_failure(): void
    {
        Http::fake(['llm.test/*' => Http::response($this->completion('Let me think…', finish: 'length'))]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ran out of tokens');

        $this->advisor->reply($this->agency, $this->conversation, 'Hi');
    }

    public function test_context_only_keeps_values_that_belong_to_the_agency(): void
    {
        $other = Agency::create(['name' => 'Other', 'slug' => 'other', 'public_status' => 'published']);
        $foreign = $other->properties()->create(['name' => 'Foreign', 'slug' => 'foreign', 'price' => 1, 'is_active' => true]);

        $this->advisor->rememberContext($this->agency, $this->conversation, ['scene' => 'nonsense', 'selected_property' => 'foreign']);
        $this->assertNull($this->conversation->fresh()->selected_property_id);

        $this->advisor->rememberContext($this->agency, $this->conversation, ['scene' => 'listing_detail', 'selected_property' => 'garden-house']);
        $this->assertSame('listing_detail', $this->conversation->fresh()->current_scene);
        $this->assertNotNull($this->conversation->fresh()->selected_property_id);
        $this->assertNotSame($foreign->id, $this->conversation->fresh()->selected_property_id);
    }

    public function test_the_chat_endpoint_returns_503_when_the_model_is_down(): void
    {
        Http::fake(['llm.test/*' => Http::response('boom', 500)]);

        $this->postJson('/demo/advisor/message', ['visitor_token' => $this->conversation->visitor_token, 'message' => 'Hi'])
            ->assertStatus(503)
            ->assertJsonPath('error', 'advisor_unavailable')
            ->assertJsonPath('message', __('site.advisor.error', [], 'en'));
    }

    public function test_the_failure_message_follows_the_conversation_language(): void
    {
        Http::fake(['llm.test/*' => Http::response('boom', 500)]);
        $japanese = $this->advisor->startConversation($this->agency, 'ja');

        $this->postJson('/demo/advisor/message', ['visitor_token' => $japanese->visitor_token, 'message' => 'こんにちは'])
            ->assertStatus(503)
            ->assertJsonPath('message', __('site.advisor.error', [], 'ja'));
    }

    public function test_a_failed_reply_is_logged_with_its_exception_and_conversation(): void
    {
        Http::fake(['llm.test/*' => Http::response('boom', 500)]);
        Log::spy();

        $this->postJson('/demo/advisor/message', ['visitor_token' => $this->conversation->visitor_token, 'message' => 'Hi'])->assertStatus(503);

        Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => $message === 'Advisor reply failed'
            && $context['conversation_id'] === $this->conversation->id
            && $context['exception'] instanceof RuntimeException)->once();
    }

    public function test_the_chat_endpoint_only_serves_conversations_of_that_agency(): void
    {
        Http::fake();
        Agency::create(['name' => 'Other', 'slug' => 'other', 'public_status' => 'published']);

        $this->postJson('/other/advisor/message', ['visitor_token' => $this->conversation->visitor_token, 'message' => 'Hi'])->assertJsonValidationErrors('visitor_token');
        $this->postJson('/demo/advisor/message', ['visitor_token' => (string) Str::uuid(), 'message' => 'Hi'])->assertJsonValidationErrors('visitor_token');
        Http::assertNothingSent();
    }
}
