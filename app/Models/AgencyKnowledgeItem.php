<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * The approved knowledge base the AI Property Advisor retrieves from. This is
 * the ONLY place agency, neighbourhood, financing and legal facts may come
 * from — the model is never allowed to answer them from its own memory.
 */
#[Fillable([
    'agency_id',
    'category',
    'title',
    'body',
    'translations',
    'tags',
    'image_url',
    'distance',
    'is_active',
    'sort_order',
])]
class AgencyKnowledgeItem extends Model
{
    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_NEIGHBOURHOOD = 'neighbourhood';

    public const CATEGORY_BUYING = 'buying';

    public const CATEGORY_FINANCING = 'financing';

    public const CATEGORY_LEGAL = 'legal';

    public const CATEGORY_FAQ = 'faq';

    public const CATEGORIES = [
        self::CATEGORY_GENERAL,
        self::CATEGORY_NEIGHBOURHOOD,
        self::CATEGORY_BUYING,
        self::CATEGORY_FINANCING,
        self::CATEGORY_LEGAL,
        self::CATEGORY_FAQ,
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * A short icon name for this entry, taken from its tags or title so the
     * neighbourhood cards can show a matching symbol without another column.
     */
    public function iconName(): string
    {
        $haystack = Str::lower($this->title.' '.implode(' ', $this->tags ?? []));

        foreach ([
            'school' => ['school', 'sekolah', 'university', 'universitas', 'kampus'],
            'health' => ['hospital', 'rumah sakit', 'clinic', 'klinik'],
            'mall' => ['mall', 'shopping', 'belanja', 'pasar', 'market'],
            'road' => ['toll', 'tol', 'highway', 'jalan'],
            'train' => ['train', 'krl', 'station', 'stasiun', 'mrt', 'lrt'],
            'park' => ['park', 'taman', 'green', 'hijau', 'golf'],
            'airport' => ['airport', 'bandara'],
        ] as $icon => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($haystack, $needle)) {
                    return $icon;
                }
            }
        }

        return 'pin';
    }

    public function translatedTitle(string $locale): string
    {
        $value = $this->translations[$locale]['title'] ?? null;

        return filled($value) ? $value : $this->title;
    }

    public function translatedBody(string $locale): string
    {
        $value = $this->translations[$locale]['body'] ?? null;

        return filled($value) ? $value : $this->body;
    }
}
