@php
    $isEdit = $property->exists;
    $input = 'mt-1 w-full rounded-xl border border-line bg-white px-3 py-2 text-sm';
    $label = 'block text-sm font-medium';
@endphp

<x-admin-layout :title="$isEdit ? 'Edit listing' : 'Add listing'">
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.properties.update', $property) : route('admin.properties.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="rounded-2xl border border-line bg-white p-5">
            <h2 class="subheading">Basics</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Name (Indonesian, default)</label>
                    <input type="text" name="name" value="{{ old('name', $property->name) }}" required class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Description (Indonesian, default)</label>
                    <textarea name="description" rows="3" class="{{ $input }}">{{ old('description', $property->description) }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Name (English)</label>
                    <input type="text" name="translations[en][name]" value="{{ old('translations.en.name', $property->translations['en']['name'] ?? '') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Name (Japanese)</label>
                    <input type="text" name="translations[ja][name]" value="{{ old('translations.ja.name', $property->translations['ja']['name'] ?? '') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Description (English)</label>
                    <textarea name="translations[en][description]" rows="3" class="{{ $input }}">{{ old('translations.en.description', $property->translations['en']['description'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Description (Japanese)</label>
                    <textarea name="translations[ja][description]" rows="3" class="{{ $input }}">{{ old('translations.ja.description', $property->translations['ja']['description'] ?? '') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-white p-5">
            <h2 class="subheading">Listing & price</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <label class="{{ $label }}">Listing type</label>
                    <select name="listing_type" class="{{ $input }}">
                        @foreach (['sale' => 'For sale', 'rent' => 'For rent (per year)'] as $value => $text)
                            <option value="{{ $value }}" @selected(old('listing_type', $property->listing_type) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Property type</label>
                    <select name="property_type" class="{{ $input }}">
                        @foreach (\App\Models\Property::PROPERTY_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('property_type', $property->property_type) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Price ({{ $agency->currency }})</label>
                    <input type="number" step="1000000" min="0" name="price" value="{{ old('price', $property->price) }}" required class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Units available</label>
                    <input type="number" min="0" name="units_available" value="{{ old('units_available', $property->units_available) }}" required class="{{ $input }}">
                </div>
                <div class="col-span-2">
                    <label class="{{ $label }}">District / area</label>
                    <input type="text" name="district" value="{{ old('district', $property->district) }}" placeholder="BSD City, Serpong" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Sort order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $property->sort_order) }}" class="{{ $input }}">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-6 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $property->is_featured)) class="rounded border-line"> Featured on the home page</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $property->is_active)) class="rounded border-line"> Published (visible on the website and to the AI)</label>
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-white p-5">
            <h2 class="subheading">Specifications</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach (['building_size_sqm' => 'Building size (m²)', 'land_size_sqm' => 'Land size (m²)', 'bedrooms' => 'Bedrooms', 'bathrooms' => 'Bathrooms', 'carports' => 'Carports', 'floors' => 'Floors', 'year_built' => 'Year built'] as $field => $text)
                    <div>
                        <label class="{{ $label }}">{{ $text }}</label>
                        <input type="number" min="0" name="{{ $field }}" value="{{ old($field, $property->{$field}) }}" @if(in_array($field, ['bedrooms', 'bathrooms'])) required @endif class="{{ $input }}">
                    </div>
                @endforeach
                <div>
                    <label class="{{ $label }}">Title / certificate</label>
                    <select name="certificate" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach (['shm' => 'SHM', 'hgb' => 'HGB', 'strata' => 'Strata', 'girik' => 'Girik'] as $value => $text)
                            <option value="{{ $value }}" @selected(old('certificate', $property->certificate) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Furnishing</label>
                    <select name="furnishing" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach (['unfurnished' => 'Unfurnished', 'semi' => 'Semi furnished', 'full' => 'Fully furnished'] as $value => $text)
                            <option value="{{ $value }}" @selected(old('furnishing', $property->furnishing) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="{{ $label }}">Amenities (comma-separated)</label>
                <input type="text" name="amenities" value="{{ old('amenities', implode(', ', $property->amenities ?? [])) }}" placeholder="one_gate, security, garden, smart_home" class="{{ $input }}">
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-white p-5">
            <h2 class="subheading">Photos</h2>

            @if ($isEdit && $property->images->isNotEmpty())
                <div class="mt-4 space-y-2">
                    @foreach ($property->images as $image)
                        <div class="flex items-center gap-3 rounded-xl border border-line p-2">
                            <img src="{{ $image->image_source }}" alt="{{ $image->alt_text }}" class="h-14 w-20 rounded-lg object-cover">
                            <div class="min-w-0 flex-1 text-xs text-muted">
                                <p class="truncate">{{ $image->image_url }}</p>
                                <p>Tags: {{ implode(', ', $image->tags ?? []) }}</p>
                            </div>
                            <label class="flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded border-line">
                                Delete
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mt-4 text-xs text-muted">Add photos by URL (e.g. an Unsplash or CDN link). Tag them so the AI Property Advisor can pick the right one — e.g. "exterior", "kitchen", "bedroom", "pool".</p>
            @for ($i = 0; $i < 3; $i++)
                <div class="mt-2 grid grid-cols-6 gap-2">
                    <input type="url" name="new_images[{{ $i }}][url]" placeholder="https://…" class="col-span-3 rounded-xl border border-line px-3 py-2 text-sm">
                    <input type="text" name="new_images[{{ $i }}][tags]" placeholder="exterior, facade" class="col-span-2 rounded-xl border border-line px-3 py-2 text-sm">
                    <input type="text" name="new_images[{{ $i }}][alt]" placeholder="Alt text" class="rounded-xl border border-line px-3 py-2 text-sm">
                </div>
            @endfor
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.properties.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-dark">Save listing</button>
        </div>
    </form>
</x-admin-layout>
