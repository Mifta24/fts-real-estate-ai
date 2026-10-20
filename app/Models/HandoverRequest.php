<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'reason',
    'summary',
    'status',
    'assigned_to',
    'resolved_at',
])]
class HandoverRequest extends Model
{
    public const REASON_PRICE_NEGOTIATION = 'price_negotiation';

    public const REASON_MORTGAGE_HELP = 'mortgage_help';

    public const REASON_LEGAL_QUESTION = 'legal_question';

    public const REASON_SPECIAL_REQUEST = 'special_request';

    public const REASON_COMPLAINT = 'complaint';

    public const REASON_PAYMENT_ISSUE = 'payment_issue';

    public const REASON_LOW_CONFIDENCE = 'low_confidence';

    public const REASONS = [
        self::REASON_PRICE_NEGOTIATION,
        self::REASON_MORTGAGE_HELP,
        self::REASON_LEGAL_QUESTION,
        self::REASON_SPECIAL_REQUEST,
        self::REASON_COMPLAINT,
        self::REASON_PAYMENT_ISSUE,
        self::REASON_LOW_CONFIDENCE,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
