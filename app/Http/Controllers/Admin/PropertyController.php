<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentAgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavePropertyRequest;
use App\Models\Agency;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PropertyController extends Controller
{
    use ResolvesCurrentAgency;

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

    public function store(SavePropertyRequest $request): RedirectResponse
    {
        $agency = $this->currentAgency($request);

        $property = $agency->properties()->create([...$request->propertyAttributes(), 'slug' => $this->uniqueSlug($agency, $request->validated('name'))]);
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

    public function update(SavePropertyRequest $request, Property $property): RedirectResponse
    {
        $agency = $this->currentAgency($request);
        abort_if($property->agency_id !== $agency->id, 404);

        $property->update($request->propertyAttributes());
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

    private function syncImages(Property $property, SavePropertyRequest $request): void
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
