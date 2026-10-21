<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * A real estate agency (or developer marketing office) with its own public
 * showroom, listings and AI Property Advisor.
 */
#[Fillable([
    'name',
    'slug',
    'custom_domain',
    'tagline',
    'description',
    'translations',
    'address',
    'city',
    'country',
    'latitude',
    'longitude',
    'phone',
    'whatsapp',
    'email',
    'timezone',
    'currency',
    'default_locale',
    'office_opens_at',
    'office_closes_at',
    'mortgage_rate',
    'logo_path',
    'cover_path',
    'public_status',
])]
class Agency extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'mortgage_rate' => 'decimal:2',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agency_users')
            ->using(AgencyUser::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function knowledgeItems(): HasMany
    {
        return $this->hasMany(AgencyKnowledgeItem::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(ViewingAppointment::class);
    }

    public function translatedDescription(string $locale): ?string
    {
        $value = $this->translations[$locale]['description'] ?? null;

        return filled($value) ? $value : $this->description;
    }

    public function translatedTagline(string $locale): ?string
    {
        $value = $this->translations[$locale]['tagline'] ?? null;

        return filled($value) ? $value : $this->tagline;
    }

    public function isPublished(): bool
    {
        return $this->public_status === 'published';
    }

    public function publicUrl(): string
    {
        return $this->custom_domain
            ? 'https://'.$this->custom_domain
            : url('/'.$this->slug);
    }

    public function formatMoney(float|int|string|null $amount): string
    {
        return $this->currency.' '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * A short, readable price for cards and narration, e.g. "Rp 1,85 M",
     * "IDR 1.85B" or "18.5億ルピア". Non-IDR currencies use the full amount.
     */
    public function formatCompactMoney(float|int|string|null $amount, string $locale): string
    {
        $amount = (float) $amount;

        if ($this->currency !== 'IDR' || $amount < 1_000_000) {
            return $this->formatMoney($amount);
        }

        $trim = function (float $value, int $decimals, string $point): string {
            $formatted = number_format($value, $decimals, $point, $point === ',' ? '.' : ',');

            return $decimals > 0 ? rtrim(rtrim($formatted, '0'), $point) : $formatted;
        };

        return match ($locale) {
            'en' => 'IDR '.($amount >= 1_000_000_000 ? $trim($amount / 1_000_000_000, 2, '.').'B' : $trim($amount / 1_000_000, 1, '.').'M'),
            'ja' => $amount >= 100_000_000 ? $trim($amount / 100_000_000, 1, '.').'億ルピア' : $trim($amount / 10_000, 0, '.').'万ルピア',
            default => 'Rp '.($amount >= 1_000_000_000 ? $trim($amount / 1_000_000_000, 2, ',').' M' : $trim($amount / 1_000_000, 1, ',').' jt'),
        };
    }

    protected static function booted(): void
    {
        static::saving(function (Agency $agency) {
            if (in_array($agency->slug, static::reservedSlugs(), true)) {
                throw new InvalidArgumentException("The slug \"{$agency->slug}\" is reserved for application routes.");
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'agency';
        $slug = $base;
        $suffix = 1;

        while (static::withTrashed()->where('slug', $slug)->exists() || in_array($slug, static::reservedSlugs(), true)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public static function reservedSlugs(): array
    {
        return [
            'admin', 'login', 'logout', 'register', 'pricing', 'dashboard',
            'api', 'terms', 'privacy', 'forgot-password', 'reset-password',
            'verify-email', 'confirm-password', 'storage', 'build', 'account', 'up',
        ];
    }
}
