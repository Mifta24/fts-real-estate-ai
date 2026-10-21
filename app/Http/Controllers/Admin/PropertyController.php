<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentAgency;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyController extends Controller
{
    use ResolvesCurrentAgency;

    private const MAX_IMAGE_KILOBYTES = 5120;

    public function index(Request $request): View
    {
        $agency = $this->currentAgency($request);

        $properties = $agency->properties()->withCount('images')->orderByDesc('is_featured')->orderBy('sort_order')->get();

        return view('admin.properties.index', compact('agency', 'properties'));
    }

    public function create(Request $request): View
    {
        $agency = $this->currentAgency($request);

        return view('admin.properties.form', [
            'agency' => $agency,
            'property' => new Property([
                'listing_type' => Property::LISTING_SALE,
                'property_type' => 'house',
                'floors' => 1,
                'units_available' => 1,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $agency = $this->currentAgency($request);

        $property = $agency->properties()->create($this->validated($request, $agency));
        $this->syncImages($property, $request);

        return redirect()->route('admin.properties.index')->with('status', __('admin.listings.created', ['name' => $property->name]));
    }

    public function edit(Request $request, Property $property): View
    {
        $agency = $this->currentAgency($request);
        abort_if($property->agency_id !== $agency->id, 404);

        $property->load('images');

        return view('admin.properties.form', compact('agency', 'property'));
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $agency = $this->currentAgency($request);
        abort_if($property->agency_id !== $agency->id, 404);

        $property->update($this->validated($request, $agency, $property));
        $this->syncImages($property, $request);

        return redirect()->route('admin.properties.index')->with('status', __('admin.listings.updated', ['name' => $property->name]));
    }

    public function destroy(Request $request, Property $property): RedirectResponse
    {
        $agency = $this->currentAgency($request);
        abort_if($property->agency_id !== $agency->id, 404);

        $property->delete();

        return redirect()->route('admin.properties.index')->with('status', __('admin.listings.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Agency $agency, ?Property $property = null): array
    {
        $data = $request->validate([
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
        ]);

        $amenities = collect(explode(',', (string) ($data['amenities'] ?? '')))
            ->map(fn ($amenity) => Str::of($amenity)->trim()->slug('_')->toString())
            ->filter()
            ->values()
            ->all();

        return [
            'name' => $data['name'],
            'slug' => $property?->slug ?? $this->uniqueSlug($agency, $data['name']),
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
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function uniqueSlug(Agency $agency, string $name): string
    {
        $base = Str::slug($name) ?: 'listing';
        $slug = $base;
        $suffix = 1;

        while ($agency->properties()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function syncImages(Property $property, Request $request): void
    {
        $deleteIds = collect($request->input('delete_images', []))->map(fn ($id) => (int) $id);
        if ($deleteIds->isNotEmpty()) {
            $property->images()->whereIn('id', $deleteIds)->get()->each(function (PropertyImage $image) {
                if ($image->image_path) {
                    PropertyImage::disk()->delete($image->image_path);
                }

                $image->delete();
            });
        }

        $sort = $property->images()->max('sort_order') + 1;
        foreach ($request->all()['new_images'] ?? [] as $index => $row) {
            $file = $request->file("new_images.{$index}.file");
            $url = $row['url'] ?? null;
            $hasValidUrl = filled($url) && filter_var($url, FILTER_VALIDATE_URL) && Str::startsWith($url, ['http://', 'https://']);

            if (! $file && ! $hasValidUrl) {
                continue;
            }

            $property->images()->create([
                'image_path' => $file ? $file->store("properties/{$property->id}", config('filesystems.property_images_disk')) : null,
                'image_url' => $file ? null : $url,
                'alt_text' => $row['alt'] ?? $property->name,
                'tags' => collect(explode(',', $row['tags'] ?? ''))->map(fn ($tag) => trim($tag))->filter()->values()->all(),
                'sort_order' => $sort++,
            ]);
        }
    }
}
