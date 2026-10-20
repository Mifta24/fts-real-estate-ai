<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'agency_id',
    'visitor_token',
    'visitor_name',
    'visitor_email',
    'locale',
    'status',
    'handover_summary',
    'current_scene',
    'selected_property_id',
    'selected_place_id',
    'viewing_state',
    'last_message_at',
])]
class Conversation extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_HANDED_OVER = 'handed_over';

    public const STATUS_CLOSED = 'closed';

    /**
     * The pages of the showroom the visitor can be on while talking to the advisor.
     */
    public const SCENES = ['home', 'listings', 'listing_detail', 'area', 'area_detail', 'info', 'viewing', 'agents'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'viewing_state' => 'array',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function selectedProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'selected_property_id');
    }

    public function selectedPlace(): BelongsTo
    {
        return $this->belongsTo(AgencyKnowledgeItem::class, 'selected_place_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(ViewingAppointment::class);
    }

    public function handoverRequest(): HasOne
    {
        return $this->hasOne(HandoverRequest::class)->latestOfMany();
    }

    public function isHandedOver(): bool
    {
        return $this->status === self::STATUS_HANDED_OVER;
    }
}
