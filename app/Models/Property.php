<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One listing: a house, apartment, shophouse or plot that is for sale or for
 * rent. Its price and stock are the only source the AI advisor may quote.
 */
#[Fillable([
    'agency_id',
    'name',
    'slug',
    'description',
    'translations',
    'listing_type',
    'property_type',
    'district',
    'price',
    'building_size_sqm',
    'land_size_sqm',
    'bedrooms',
    'bathrooms',
    'carports',
    'floors',
    'certificate',
    'furnishing',
    'year_built',
    'amenities',
    'units_available',
    'is_featured',
    'is_active',
    'sort_order',
])]
class Property extends Model
{
    use HasFactory, SoftDeletes;

    public const LISTING_SALE = 'sale';

    public const LISTING_RENT = 'rent';

    public const LISTING_TYPES = [self::LISTING_SALE, self::LISTING_RENT];

    public const PROPERTY_TYPES = ['house', 'townhouse', 'apartment', 'shophouse', 'land'];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'amenities' => 'array',
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(ViewingAppointment::class);
    }

    public function isForSale(): bool
    {
        return $this->listing_type === self::LISTING_SALE;
    }

    public function isForRent(): bool
    {
        return $this->listing_type === self::LISTING_RENT;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->units_available > 0;
    }

    /**
     * Price per square metre of building (or land, for a plot), for comparing listings.
     */
    public function pricePerSqm(): ?float
    {
        $size = $this->building_size_sqm ?: $this->land_size_sqm;

        return $size ? round((float) $this->price / $size) : null;
    }

    public function translatedName(string $locale): string
    {
        $value = $this->translations[$locale]['name'] ?? null;

        return filled($value) ? $value : $this->name;
    }

    public function translatedDescription(string $locale): ?string
    {
        $value = $this->translations[$locale]['description'] ?? null;

        return filled($value) ? $value : $this->description;
    }
}
