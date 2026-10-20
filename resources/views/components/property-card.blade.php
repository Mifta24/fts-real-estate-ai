@props(['property', 'agency', 'locale', 'order' => 0])
@php
    $photo = $property->images->first();
    $name = $property->translatedName($locale);
@endphp
<article {{ $attributes->merge(['class' => 'property-card']) }}
    data-property-card
    data-listing-type="{{ $property->listing_type }}"
    data-property-type="{{ $property->property_type }}"
    data-price="{{ (int) $property->price }}"
    data-size="{{ (int) ($property->building_size_sqm ?: $property->land_size_sqm) }}"
    data-order="{{ $order }}">
    <div class="property-card-photo">
        @if ($photo)
            <img src="{{ $photo->image_source }}" alt="{{ $photo->alt_text ?: $name }}" loading="lazy">
        @endif
        <div class="property-card-badges">
            <span class="badge {{ $property->isForSale() ? 'badge-sale' : 'badge-rent' }}">{{ __('site.common.'.$property->listing_type) }}</span>
            <span class="badge badge-soft">{{ __('site.common.property_types.'.$property->property_type) }}</span>
            @if (! $property->isAvailable())
                <span class="badge badge-alert">{{ __('site.common.sold_out') }}</span>
            @elseif ($property->units_available > 1 && $property->units_available <= 3)
                <span class="badge badge-alert">{{ __('site.common.units_left', ['count' => $property->units_available]) }}</span>
            @endif
        </div>
    </div>
    <div class="property-card-body">
        <p class="property-card-price">{{ $agency->formatCompactMoney($property->price, $locale) }} @if ($property->isForRent())<small>{{ __('site.common.per_year') }}</small>@endif</p>
        <h3 class="property-card-name"><a href="{{ route('agency.listing', ['agencySlug' => $agency->slug, 'propertySlug' => $property->slug, 'lang' => $locale]) }}" data-page-link>{{ $name }}</a></h3>
        <p class="property-card-place"><x-icon name="pin" :size="14" /> {{ $property->district ?: $agency->city }}</p>
        <div class="spec-row">
            @if ($property->bedrooms)<span><x-icon name="bed" :size="16" /> {{ $property->bedrooms }} {{ __('site.common.short.bedrooms') }}</span>@endif
            @if ($property->bathrooms)<span><x-icon name="bath" :size="16" /> {{ $property->bathrooms }} {{ __('site.common.short.bathrooms') }}</span>@endif
            @if ($property->building_size_sqm)<span><x-icon name="building" :size="16" /> {{ $property->building_size_sqm }} m²</span>@endif
            @if ($property->land_size_sqm)<span><x-icon name="land" :size="16" /> {{ $property->land_size_sqm }} m²</span>@endif
        </div>
    </div>
</article>
