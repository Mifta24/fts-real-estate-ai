<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\ViewingAppointment;
use App\Services\Viewing\ViewingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewingBookingTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->agency->properties()->create(['name' => 'House', 'slug' => 'house', 'price' => 1_500_000_000, 'is_active' => true, 'units_available' => 1, 'listing_type' => 'sale']);
        $this->date = (new ViewingService)->earliestDate($this->agency)->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return [
            'property_slug' => 'house', 'date' => $this->date, 'time' => '10:30', 'visitor_name' => 'Budi',
            'contact_type' => 'whatsapp', 'contact_value' => '+62 812-3456-7890', 'party_size' => 2, 'payment_plan' => 'mortgage',
            ...$override,
        ];
    }

    public function test_a_visitor_can_book_a_free_slot(): void
    {
        $this->postJson('/demo/viewing', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['reference', 'status', 'contact']);

        $this->assertDatabaseHas('viewing_appointments', ['visitor_name' => 'Budi', 'visitor_phone' => '+62 812-3456-7890', 'status' => ViewingAppointment::STATUS_PENDING]);
    }

    public function test_booking_a_taken_slot_returns_the_remaining_slots(): void
    {
        $this->postJson('/demo/viewing', $this->payload())->assertCreated();

        $this->postJson('/demo/viewing', $this->payload(['visitor_name' => 'Siti']))
            ->assertStatus(422)
            ->assertJsonPath('slots', ['09:00', '13:00', '14:30', '16:00']);

        $this->assertSame(1, ViewingAppointment::count());
    }

    public function test_contact_details_must_match_the_chosen_contact_type(): void
    {
        $this->postJson('/demo/viewing', $this->payload(['contact_type' => 'email', 'contact_value' => 'not-an-email']))->assertJsonValidationErrors('contact_value');
        $this->postJson('/demo/viewing', $this->payload(['contact_type' => 'phone', 'contact_value' => 'call me']))->assertJsonValidationErrors('contact_value');

        $this->postJson('/demo/viewing', $this->payload(['contact_type' => 'email', 'contact_value' => 'budi@example.test']))->assertCreated();
        $this->assertDatabaseHas('viewing_appointments', ['visitor_email' => 'budi@example.test', 'visitor_phone' => null]);
    }

    public function test_invalid_bookings_are_rejected(): void
    {
        $this->postJson('/demo/viewing', $this->payload(['time' => '03:00']))->assertJsonValidationErrors('time');
        $this->postJson('/demo/viewing', $this->payload(['party_size' => 50]))->assertJsonValidationErrors('party_size');
        $this->postJson('/demo/viewing', $this->payload(['visitor_name' => '']))->assertJsonValidationErrors('visitor_name');
        $this->postJson('/demo/viewing', $this->payload(['property_slug' => 'missing']))->assertJsonValidationErrors('property_slug');
        $this->postJson('/demo/viewing', $this->payload(['date' => now()->toDateString()]))->assertStatus(422);

        $this->assertSame(0, ViewingAppointment::count());
    }

    public function test_the_slots_endpoint_lists_free_times(): void
    {
        $this->postJson('/demo/viewing', $this->payload())->assertCreated();

        $this->postJson('/demo/viewing/slots', ['property_slug' => 'house', 'date' => $this->date])
            ->assertOk()
            ->assertJsonPath('slots', ['09:00', '13:00', '14:30', '16:00'])
            ->assertJsonPath('all_slots', ViewingService::TIME_SLOTS);
    }

    public function test_unpublished_agencies_cannot_take_bookings(): void
    {
        $this->agency->update(['public_status' => 'draft']);

        $this->postJson('/demo/viewing', $this->payload())->assertNotFound();
    }

    public function test_the_mortgage_widget_uses_the_listing_price_not_the_browser(): void
    {
        $response = $this->postJson('/demo/mortgage', ['property_slug' => 'house', 'down_payment_percent' => 20, 'tenor_years' => 15, 'price' => 1])
            ->assertOk()
            ->assertJsonPath('price', 1_500_000_000)
            ->assertJsonPath('currency', 'IDR');

        $this->assertSame(1_200_000_000, $response->json('loan_amount'));

        $this->postJson('/demo/mortgage', ['property_slug' => 'house', 'down_payment_percent' => 5, 'tenor_years' => 15])->assertJsonValidationErrors('down_payment_percent');
        $this->postJson('/demo/mortgage', ['property_slug' => 'house', 'down_payment_percent' => 20, 'tenor_years' => 40])->assertJsonValidationErrors('tenor_years');
    }
}
