<?php

namespace App\Http\Requests;

use App\Models\Property;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SavePropertyRequest extends FormRequest
{
    public const MAX_IMAGE_KILOBYTES = 5120;

    public function authorize(): bool
    {
        return $this->user()->can('manage-agency-content');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'translations.en.name' => ['nullable', 'string'],
            'translations.en.description' => ['nullable', 'string'],
            'translations.ja.name' => ['nullable', 'string'],
            'translations.ja.description' => ['nullable', 'string'],
            'listing_type' => ['required', Rule::in(Property::LISTING_TYPES)],
            'property_type' => ['required', Rule::in(Property::PROPERTY_TYPES)],
            'district' => ['nullable', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'building_size_sqm' => ['nullable', 'integer', 'min:1'],
            'land_size_sqm' => ['nullable', 'integer', 'min:1'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:50'],
            'carports' => ['nullable', 'integer', 'min:0', 'max:20'],
            'floors' => ['nullable', 'integer', 'min:1', 'max:100'],
            'certificate' => ['nullable', Rule::in(['shm', 'hgb', 'strata', 'girik'])],
            'furnishing' => ['nullable', Rule::in(['unfurnished', 'semi', 'full'])],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'amenities' => ['nullable', 'string'],
            'units_available' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'new_images.*.file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KILOBYTES],
        ];
    }

    /**
     * The validated input shaped as property columns (the slug is assigned by the controller).
     *
     * @return array<string, mixed>
     */
    public function propertyAttributes(): array
    {
        $data = $this->validated();

        $amenities = collect(explode(',', (string) ($data['amenities'] ?? '')))
            ->map(fn ($amenity) => Str::of($amenity)->trim()->slug('_')->toString())
            ->filter()
            ->values()
            ->all();

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'translations' => [
                'en' => ['name' => $data['translations']['en']['name'] ?? null, 'description' => $data['translations']['en']['description'] ?? null],
                'ja' => ['name' => $data['translations']['ja']['name'] ?? null, 'description' => $data['translations']['ja']['description'] ?? null],
            ],
            'listing_type' => $data['listing_type'],
            'property_type' => $data['property_type'],
            'district' => $data['district'] ?? null,
            'price' => $data['price'],
            'building_size_sqm' => $data['building_size_sqm'] ?? null,
            'land_size_sqm' => $data['land_size_sqm'] ?? null,
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'carports' => $data['carports'] ?? 0,
            'floors' => $data['floors'] ?? 1,
            'certificate' => $data['certificate'] ?? null,
            'furnishing' => $data['furnishing'] ?? null,
            'year_built' => $data['year_built'] ?? null,
            'amenities' => $amenities,
            'units_available' => $data['units_available'],
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
