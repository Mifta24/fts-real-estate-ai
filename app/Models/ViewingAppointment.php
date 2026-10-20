<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request to visit a listing with an agent (survei lokasi). No payment is
 * taken; the agency confirms the slot.
 */
#[Fillable([
    'reference',
    'agency_id',
    'property_id',
    'conversation_id',
    'visitor_name',
    'visitor_email',
    'visitor_phone',
    'contact_type',
    'viewing_date',
    'viewing_time',
    'party_size',
    'payment_plan',
    'status',
    'notes',
])]
class ViewingAppointment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PLANS = ['cash', 'mortgage', 'undecided'];

    protected function casts(): array
    {
        return [
            'viewing_date' => 'date',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * A short, unambiguous, non-sequential code visitors can quote to agents.
     */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $reference = 'SV-'.collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
