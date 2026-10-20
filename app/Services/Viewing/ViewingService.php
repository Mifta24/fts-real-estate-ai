<?php

namespace App\Services\Viewing;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\ViewingAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The single place where viewing (site visit) slots are computed and
 * requested. Both the AI advisor tools and the visitor-facing scheduling
 * wizard go through here, so they can never disagree.
 */
class ViewingService
{
    /**
     * Start times an agent can walk a visitor through a listing.
     */
    public const TIME_SLOTS = ['09:00', '10:30', '13:00', '14:30', '16:00'];

    public const MAX_DAYS_AHEAD = 60;

    public const MAX_PARTY_SIZE = 6;

    /**
     * Viewings need a day's notice so an agent and the keys can be arranged.
     */
    public function earliestDate(Agency $agency): CarbonImmutable
    {
        return CarbonImmutable::now($agency->timezone)->startOfDay()->addDay();
    }

    public function latestDate(Agency $agency): CarbonImmutable
    {
        return CarbonImmutable::now($agency->timezone)->startOfDay()->addDays(self::MAX_DAYS_AHEAD);
    }

    public function isWithinWindow(Agency $agency, CarbonImmutable $date): bool
    {
        return $date->betweenIncluded($this->earliestDate($agency), $this->latestDate($agency));
    }

    /**
     * The start times still free for this listing on that day. Empty when the
     * listing cannot be viewed (sold out, hidden) or the day is out of range.
     *
     * @return list<string>
     */
    public function availableSlots(Agency $agency, Property $property, CarbonImmutable $date, bool $lockForUpdate = false): array
    {
        if (! $property->isAvailable() || ! $this->isWithinWindow($agency, $date)) {
            return [];
        }

        $query = ViewingAppointment::where('property_id', $property->id)
            ->whereDate('viewing_date', $date->toDateString())
            ->whereIn('status', [ViewingAppointment::STATUS_PENDING, ViewingAppointment::STATUS_CONFIRMED]);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $taken = $query->pluck('viewing_time')->all();

        return array_values(array_diff(self::TIME_SLOTS, $taken));
    }

    /**
     * Creates a pending viewing request and holds the slot for it. Returns
     * null when that slot was taken in the meantime.
     *
     * @param  array{date: CarbonImmutable, time: string, visitor_name: string, visitor_email?: ?string, visitor_phone?: ?string, contact_type?: ?string, party_size?: int, payment_plan?: string, notes?: ?string}  $data
     */
    public function createRequest(Agency $agency, Property $property, array $data, ?Conversation $conversation = null): ?ViewingAppointment
    {
        return DB::transaction(function () use ($agency, $property, $data, $conversation) {
            if (! in_array($data['time'], $this->availableSlots($agency, $property, $data['date'], lockForUpdate: true), true)) {
                return null;
            }

            return ViewingAppointment::create([
                'reference' => ViewingAppointment::generateReference(),
                'agency_id' => $agency->id,
                'property_id' => $property->id,
                'conversation_id' => $conversation?->id,
                'visitor_name' => $data['visitor_name'],
                'visitor_email' => $data['visitor_email'] ?? null,
                'visitor_phone' => $data['visitor_phone'] ?? null,
                'contact_type' => $data['contact_type'] ?? null,
                'viewing_date' => $data['date']->toDateString(),
                'viewing_time' => $data['time'],
                'party_size' => min(max((int) ($data['party_size'] ?? 1), 1), self::MAX_PARTY_SIZE),
                'payment_plan' => in_array($data['payment_plan'] ?? null, ViewingAppointment::PAYMENT_PLANS, true) ? $data['payment_plan'] : 'undecided',
                'status' => ViewingAppointment::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
