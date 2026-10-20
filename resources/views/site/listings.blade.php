@php
    $types = $properties->pluck('property_type')->unique()->values();
@endphp
<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="__('site.listings.title')">
    <section class="container-x page-head">
        <span class="eyebrow">{{ __('site.listings.eyebrow') }}</span>
        <h1 class="display">{{ __('site.listings.title') }}</h1>
        <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            @if ($brief)
                <x-brief :text="$brief" key="listings" :locale="$locale" />
            @endif
            <button type="button" class="btn btn-ghost" data-hero-quick-message="{{ __('site.listings.ask_q') }}"><x-icon name="spark" :size="16" class="text-accent" /> {{ __('site.listings.ask') }}</button>
        </div>
    </section>

    <section class="container-x" data-listings data-initial-type="{{ $initialListingType }}" data-count-template="{{ __('site.listings.count') }}">
        @if ($properties->isEmpty())
            <p class="empty-state mt-8">{{ __('site.listings.none') }}</p>
        @else
            <div class="filter-bar" role="group" aria-label="{{ __('site.listings.filter_label') }}">
                <div class="segmented">
                    <button type="button" data-filter-listing="" aria-pressed="true">{{ __('site.common.all') }}</button>
                    <button type="button" data-filter-listing="sale" aria-pressed="false">{{ __('site.common.sale') }}</button>
                    <button type="button" data-filter-listing="rent" aria-pressed="false">{{ __('site.common.rent') }}</button>
                </div>
                <div class="filter-types" aria-label="{{ __('site.listings.type_label') }}">
                    @foreach ($types as $type)
                        <button type="button" data-filter-type="{{ $type }}" aria-pressed="false">{{ __('site.common.property_types.'.$type) }}</button>
                    @endforeach
                </div>
                <label class="filter-sort">
                    <span>{{ __('site.listings.sort_label') }}</span>
                    <select data-filter-sort>
                        @foreach (__('site.listings.sort') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <p class="filter-count mb-4" data-filter-count aria-live="polite">{{ __('site.listings.count', ['count' => $properties->count()]) }}</p>
            <div class="property-grid" data-listing-grid>
                @foreach ($properties as $property)
                    <x-property-card :property="$property" :agency="$agency" :locale="$locale" :order="$loop->index" />
                @endforeach
            </div>
            <p class="empty-state" data-filter-empty hidden>{{ __('site.listings.empty') }}</p>
        @endif
    </section>
</x-showroom>
