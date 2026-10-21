<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\HandoverRequest;
use App\Models\Property;
use App\Models\ViewingAppointment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrunePersonalDataTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        config(['retention.conversation_days' => 90, 'retention.viewing_days' => 365]);

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->property = $this->agency->properties()->create(['name' => 'House', 'slug' => 'house', 'price' => 1]);
    }

    private function conversation(int $daysSinceLastMessage): Conversation
    {
        $conversation = Conversation::create([
            'agency_id' => $this->agency->id, 'visitor_token' => (string) Str::uuid(), 'locale' => 'en',
            'visitor_name' => 'Budi', 'last_message_at' => now()->subDays($daysSinceLastMessage),
        ]);
        $conversation->messages()->create(['role' => ConversationMessage::ROLE_VISITOR, 'content' => 'My number is 0812']);

        return $conversation;
    }

    private function viewing(int $daysSinceViewing, ?Conversation $conversation = null): ViewingAppointment
    {
        return ViewingAppointment::create([
            'reference' => 'SV-'.Str::upper(Str::random(6)), 'agency_id' => $this->agency->id, 'property_id' => $this->property->id,
            'conversation_id' => $conversation?->id, 'visitor_name' => 'Budi', 'visitor_email' => 'budi@example.test', 'visitor_phone' => '0812',
            'notes' => 'Call after 5pm', 'viewing_date' => now()->subDays($daysSinceViewing)->toDateString(), 'viewing_time' => '10:00', 'status' => 'completed',
        ]);
    }

    public function test_old_conversations_are_deleted_with_their_messages_and_recent_ones_are_kept(): void
    {
        $old = $this->conversation(91);
        $recent = $this->conversation(89);

        $this->artisan('privacy:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertSame(0, ConversationMessage::where('conversation_id', $old->id)->count());
        $this->assertModelExists($recent);
        $this->assertSame(1, $recent->messages()->count());
    }

    public function test_a_conversation_with_an_open_handover_is_kept_until_it_is_resolved(): void
    {
        $waiting = $this->conversation(200);
        $handover = HandoverRequest::create(['conversation_id' => $waiting->id, 'reason' => HandoverRequest::REASON_COMPLAINT, 'summary' => 'Angry']);
        $done = $this->conversation(200);
        HandoverRequest::create(['conversation_id' => $done->id, 'reason' => HandoverRequest::REASON_COMPLAINT, 'summary' => 'Fixed', 'status' => HandoverRequest::STATUS_RESOLVED]);

        $this->artisan('privacy:prune')->assertSuccessful();

        $this->assertModelExists($waiting);
        $this->assertModelMissing($done);

        $handover->update(['status' => HandoverRequest::STATUS_RESOLVED]);
        $this->artisan('privacy:prune')->assertSuccessful();
        $this->assertModelMissing($waiting);
    }

    public function test_old_viewings_lose_contact_details_but_keep_the_booking(): void
    {
        $conversation = $this->conversation(10);
        $old = $this->viewing(366, $conversation);
        $recent = $this->viewing(10, $conversation);

        $this->artisan('privacy:prune')->assertSuccessful();

        $old->refresh();
        $this->assertSame('—', $old->visitor_name);
        $this->assertNull($old->visitor_email);
        $this->assertNull($old->visitor_phone);
        $this->assertNull($old->notes);
        $this->assertNull($old->conversation_id);
        $this->assertSame('completed', $old->status);
        $this->assertSame($this->property->id, $old->property_id);

        $this->assertSame('Budi', $recent->fresh()->visitor_name);
        $this->assertSame('budi@example.test', $recent->fresh()->visitor_email);
    }

    public function test_deleting_a_conversation_keeps_its_viewing_request(): void
    {
        $conversation = $this->conversation(200);
        $viewing = $this->viewing(5, $conversation);

        $this->artisan('privacy:prune')->assertSuccessful();

        $this->assertModelMissing($conversation);
        $this->assertSame('Budi', $viewing->fresh()->visitor_name);
        $this->assertNull($viewing->fresh()->conversation_id);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $old = $this->conversation(200);
        $viewing = $this->viewing(400);

        $this->artisan('privacy:prune --dry-run')->expectsOutputToContain('Would delete 1 conversation(s)')->assertSuccessful();

        $this->assertModelExists($old);
        $this->assertSame('Budi', $viewing->fresh()->visitor_name);
    }

    public function test_a_retention_of_zero_days_keeps_that_data_forever(): void
    {
        config(['retention.conversation_days' => 0, 'retention.viewing_days' => 0]);
        $old = $this->conversation(2000);
        $viewing = $this->viewing(2000);

        $this->artisan('privacy:prune')->assertSuccessful();

        $this->assertModelExists($old);
        $this->assertSame('Budi', $viewing->fresh()->visitor_name);
    }

    public function test_the_prune_runs_daily(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'privacy:prune'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
    }
}
