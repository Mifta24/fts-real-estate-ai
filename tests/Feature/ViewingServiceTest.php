<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Property;
use App\Models\ViewingAppointment;
use App\Services\Viewing\ViewingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Property $property;

    private ViewingService $viewings;

    private CarbonImmutable $tomorrow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->property = $this->agency->properties()->create(['name' => 'House', 'slug' => 'house', 'price' => 1_000_000_000, 'is_active' => true, 'units_available' => 1]);
        $this->viewings = new ViewingService;
        $this->tomorrow = $this->viewings->earliestDate($this->agency);
    }

    private function request(string $time, ?CarbonImmutable $date = null, array $extra = []): ?ViewingAppointment
    {
        return $this->viewings->createRequest($this->agency, $this->property, [
            'date' => $date ?? $this->tomorrow,
            'time' => $time,
            'visitor_name' => 'Visitor',
            ...$extra,
        ]);
    }

    public function test_every_slot_is_free_on_an_empty_day(): void
    {
        $this->assertSame(ViewingService::TIME_SLOTS, $this->viewings->availableSlots($this->agency, $this->property, $this->tomorrow));
    }

    public function test_a_requested_slot_is_held_and_cannot_be_booked_twice(): void
    {
        $this->assertNotNull($this->request('10:30'));
        $this->assertNull($this->request('10:30'));

        $this->assertNotContains('10:30', $this->viewings->availableSlots($this->agency, $this->property, $this->tomorrow));
    }

    public function test_a_cancelled_viewing_frees_its_slot(): void
    {
        $this->request('13:00')->update(['status' => ViewingAppointment::STATUS_CANCELLED]);

        $this->assertContains('13:00', $this->viewings->availableSlots($this->agency, $this->property, $this->tomorrow));
        $this->assertNotNull($this->request('13:00'));
    }

    public function test_slots_are_per_listing_and_per_day(): void
    {
        $other = $this->agency->properties()->create(['name' => 'Other', 'slug' => 'other', 'price' => 1, 'is_active' => true, 'units_available' => 1]);
        $this->request('09:00');

        $this->assertContains('09:00', $this->viewings->availableSlots($this->agency, $other, $this->tomorrow));
        $this->assertContains('09:00', $this->viewings->availableSlots($this->agency, $this->property, $this->tomorrow->addDay()));
    }

    public function test_days_outside_the_booking_window_have_no_slots(): void
    {
        $today = CarbonImmutable::now($this->agency->timezone)->startOfDay();

        $this->assertSame([], $this->viewings->availableSlots($this->agency, $this->property, $today));
        $this->assertSame([], $this->viewings->availableSlots($this->agency, $this->property, $today->addDays(ViewingService::MAX_DAYS_AHEAD + 1)));
        $this->assertNotSame([], $this->viewings->availableSlots($this->agency, $this->property, $today->addDays(ViewingService::MAX_DAYS_AHEAD)));
        $this->assertNull($this->request('09:00', $today));
    }

    public function test_hidden_or_sold_out_listings_cannot_be_viewed(): void
    {
        $this->property->update(['units_available' => 0]);
        $this->assertSame([], $this->viewings->availableSlots($this->agency, $this->property->fresh(), $this->tomorrow));

        $this->property->update(['units_available' => 1, 'is_active' => false]);
        $this->assertNull($this->request('09:00'));
    }

    public function test_party_size_and_payment_plan_are_sanitised(): void
    {
        $viewing = $this->request('16:00', extra: ['party_size' => 99, 'payment_plan' => 'barter']);

        $this->assertSame(ViewingService::MAX_PARTY_SIZE, $viewing->party_size);
        $this->assertSame('undecided', $viewing->payment_plan);
        $this->assertSame(ViewingAppointment::STATUS_PENDING, $viewing->status);
    }

    public function test_an_unknown_time_cannot_be_booked(): void
    {
        $this->assertNull($this->request('03:00'));
    }
}
