<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\HandoverRequest;
use App\Models\User;
use App\Models\ViewingAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Agency $otherAgency;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->otherAgency = Agency::create(['name' => 'Other', 'slug' => 'other', 'public_status' => 'published']);

        $this->staff = User::factory()->create();
        $this->agency->users()->attach($this->staff->id, ['role' => 'owner', 'status' => 'active']);
    }

    private function foreignViewing(): ViewingAppointment
    {
        $property = $this->otherAgency->properties()->create(['name' => 'Foreign House', 'slug' => 'foreign-house', 'price' => 1]);

        return ViewingAppointment::create([
            'reference' => 'SV-FOREIGN', 'agency_id' => $this->otherAgency->id, 'property_id' => $property->id, 'visitor_name' => 'Foreign Visitor',
            'viewing_date' => now()->addDays(3)->toDateString(), 'viewing_time' => '10:00',
        ]);
    }

    private function foreignHandover(): HandoverRequest
    {
        $conversation = Conversation::create(['agency_id' => $this->otherAgency->id, 'visitor_token' => (string) Str::uuid(), 'locale' => 'en']);

        return HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => HandoverRequest::REASON_COMPLAINT, 'summary' => 'Foreign complaint']);
    }

    public function test_visitors_are_sent_to_the_login_page_for_every_admin_screen(): void
    {
        foreach (['admin.dashboard', 'admin.properties.index', 'admin.knowledge-items.index', 'admin.viewings.index', 'admin.handovers.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_staff_only_see_viewings_of_their_own_agency(): void
    {
        $this->foreignViewing();

        $this->actingAs($this->staff)->get(route('admin.viewings.index'))->assertOk()->assertDontSee('SV-FOREIGN')->assertDontSee('Foreign Visitor');
    }

    public function test_staff_cannot_change_another_agencys_viewing(): void
    {
        $viewing = $this->foreignViewing();

        $this->actingAs($this->staff)
            ->patch(route('admin.viewings.status', $viewing), ['status' => ViewingAppointment::STATUS_CONFIRMED])
            ->assertNotFound();

        $this->assertSame(ViewingAppointment::STATUS_PENDING, $viewing->fresh()->status);
    }

    public function test_staff_cannot_read_reply_to_or_resolve_another_agencys_handover(): void
    {
        $handover = $this->foreignHandover();

        $this->actingAs($this->staff)->get(route('admin.handovers.show', $handover))->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.handovers.reply', $handover), ['message' => 'Hello'])->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.handovers.resolve', $handover))->assertNotFound();

        $this->assertSame(0, ConversationMessage::count());
        $this->assertSame(HandoverRequest::STATUS_OPEN, $handover->fresh()->status);
    }

    public function test_agent_reply_reaches_the_visitor_and_resolving_returns_the_conversation_to_the_ai(): void
    {
        $conversation = Conversation::create(['agency_id' => $this->agency->id, 'visitor_token' => (string) Str::uuid(), 'locale' => 'en', 'status' => Conversation::STATUS_HANDED_OVER]);
        $handover = HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => HandoverRequest::REASON_PRICE_NEGOTIATION, 'summary' => 'Wants to negotiate the price']);

        $this->actingAs($this->staff)->post(route('admin.handovers.reply', $handover), ['message' => 'We are on it.'])->assertRedirect();
        $this->assertSame('We are on it.', $conversation->messages()->where('role', ConversationMessage::ROLE_AGENT)->value('content'));

        $this->actingAs($this->staff)->post(route('admin.handovers.resolve', $handover))->assertRedirect(route('admin.handovers.index'));
        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
        $this->assertSame(HandoverRequest::STATUS_RESOLVED, $handover->fresh()->status);
    }

    public function test_admin_login_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), ['email' => $this->staff->email, 'password' => 'wrong'])->assertSessionHasErrors();
        }

        $this->post(route('admin.login.store'), ['email' => $this->staff->email, 'password' => 'wrong'])->assertTooManyRequests();
    }

    public function test_staff_can_give_a_neighbourhood_entry_a_photo_and_only_a_real_url_is_accepted(): void
    {
        $item = $this->agency->knowledgeItems()->create(['category' => 'neighbourhood', 'title' => 'City park', 'body' => 'Open until 8pm.', 'is_active' => true]);
        $form = ['category' => 'neighbourhood', 'title' => 'City park', 'body' => 'Open until 8pm.', 'is_active' => 1];

        $this->actingAs($this->staff)
            ->put(route('admin.knowledge-items.update', $item), [...$form, 'image_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('image_url');

        $this->actingAs($this->staff)
            ->put(route('admin.knowledge-items.update', $item), [...$form, 'image_url' => 'ftp://example.test/park.jpg'])
            ->assertSessionHasErrors('image_url');

        $this->actingAs($this->staff)
            ->put(route('admin.knowledge-items.update', $item), [...$form, 'image_url' => 'https://example.test/park.jpg'])
            ->assertSessionHasNoErrors();

        $this->assertSame('https://example.test/park.jpg', $item->fresh()->image_url);
    }

    private function agentStaff(): User
    {
        $agent = User::factory()->create();
        $this->agency->users()->attach($agent->id, ['role' => 'agent', 'status' => 'active']);

        return $agent;
    }

    public function test_agents_can_list_but_not_manage_listings_and_knowledge_items(): void
    {
        $agent = $this->agentStaff();
        $property = $this->agency->properties()->create(['name' => 'Own House', 'slug' => 'own-house', 'price' => 1]);
        $item = $this->agency->knowledgeItems()->create(['category' => 'faq', 'title' => 'FAQ', 'body' => 'Answer', 'is_active' => true]);

        $this->actingAs($agent)->get(route('admin.properties.index'))->assertOk()->assertDontSee(route('admin.properties.create'));
        $this->actingAs($agent)->get(route('admin.knowledge-items.index'))->assertOk()->assertDontSee(route('admin.knowledge-items.create'));

        $this->actingAs($agent)->get(route('admin.properties.create'))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.properties.edit', $property))->assertForbidden();
        $this->actingAs($agent)->delete(route('admin.properties.destroy', $property))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.knowledge-items.create'))->assertForbidden();
        $this->actingAs($agent)->delete(route('admin.knowledge-items.destroy', $item))->assertForbidden();

        $this->assertModelExists($property);
        $this->assertModelExists($item);
    }

    public function test_owners_can_manage_listings(): void
    {
        $property = $this->agency->properties()->create(['name' => 'Own House', 'slug' => 'own-house', 'price' => 1]);

        $this->actingAs($this->staff)->get(route('admin.properties.create'))->assertOk();
        $this->actingAs($this->staff)->delete(route('admin.properties.destroy', $property))->assertRedirect(route('admin.properties.index'));
        $this->assertSoftDeleted($property);
    }

    public function test_agents_can_still_handle_viewings(): void
    {
        $viewing = ViewingAppointment::create([
            'reference' => 'SV-OWN', 'agency_id' => $this->agency->id,
            'property_id' => $this->agency->properties()->create(['name' => 'Own House', 'slug' => 'own-house', 'price' => 1])->id,
            'visitor_name' => 'Visitor', 'viewing_date' => now()->addDays(3)->toDateString(), 'viewing_time' => '10:00',
        ]);

        $this->actingAs($this->agentStaff())
            ->patch(route('admin.viewings.status', $viewing), ['status' => ViewingAppointment::STATUS_CONFIRMED])
            ->assertRedirect();

        $this->assertSame(ViewingAppointment::STATUS_CONFIRMED, $viewing->fresh()->status);
    }
}
