@php
    $name = $property->translatedName($locale);
    $primaryImage = $property->images->first();
    $listingUrl = fn ($other) => route('agency.listing', ['agencySlug' => $agency->slug, 'propertySlug' => $other->slug, 'lang' => $locale]);
    $specs = array_filter([
        ['bed', 'bedrooms', $property->bedrooms ?: null],
        ['bath', 'bathrooms', $property->bathrooms ?: null],
        ['building', 'building', $property->building_size_sqm ? $property->building_size_sqm.' m²' : null],
        ['land', 'land', $property->land_size_sqm ? $property->land_size_sqm.' m²' : null],
        ['car', 'carports', $property->carports ?: null],
        ['floors', 'floors', $property->property_type !== 'land' ? $property->floors : null],
        ['certificate', 'certificate', $property->certificate ? __('site.common.certificates.'.$property->certificate) : null],
        ['sofa', 'furnishing', $property->furnishing ? __('site.common.furnishing.'.$property->furnishing) : null],
        ['calendar', 'year_built', $property->year_built],
        ['ruler', 'price_per_sqm', $property->isForSale() && $property->pricePerSqm() ? $agency->formatCompactMoney($property->pricePerSqm(), $locale) : null],
    ], fn ($spec) => filled($spec[2]));
@endphp
<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="$name" :backdrop="$primaryImage?->image_source">
    <section class="container-x page-head">
        <nav class="breadcrumb" aria-label="{{ __('site.listing.back') }}">
            <a href="{{ route('agency.listings', ['agencySlug' => $agency->slug, 'lang' => $locale]) }}" data-page-link><x-icon name="arrow-left" :size="14" class="inline" /> {{ __('site.listing.back') }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ __('site.listing.counter', ['current' => $propertyIndex + 1, 'total' => $properties->count()]) }}</span>
        </nav>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="badge {{ $property->isForSale() ? 'badge-sale' : 'badge-rent' }}">{{ __('site.common.'.$property->listing_type) }}</span>
            <span class="badge border border-line">{{ __('site.common.property_types.'.$property->property_type) }}</span>
            @if (! $property->isAvailable())
                <span class="badge badge-alert">{{ __('site.common.sold_out') }}</span>
            @elseif ($property->units_available > 1)
                <span class="badge border border-line">{{ __('site.common.units_left', ['count' => $property->units_available]) }}</span>
            @endif
        </div>
        <h1 class="display mt-3! text-[clamp(36px,5vw,64px)]!">{{ $name }}</h1>
        <p class="mt-2 flex items-center gap-1.5 text-muted"><x-icon name="pin" :size="16" /> {{ $property->district ?: $agency->city }}, {{ $agency->city }}</p>
    </section>

    <section class="container-x mt-6 listing-layout">
        <div class="min-w-0">
            <div data-gallery>
                <div class="gallery-main">
                    @if ($primaryImage)
                        <img src="{{ $primaryImage->image_source }}" alt="{{ $primaryImage->alt_text ?: $name }}" data-gallery-main>
                    @else
                        <div class="grid h-full place-items-center text-sm text-muted">{{ __('site.listing.no_photo') }}</div>
                    @endif
                </div>
                @if ($property->images->count() > 1)
                    <ul class="gallery-thumbs" aria-label="{{ __('site.listing.gallery') }}">
                        @foreach ($property->images as $imageIndex => $image)
                            <li>
                                <button type="button" class="gallery-thumb w-full" data-gallery-thumb data-src="{{ $image->image_source }}" data-alt="{{ $image->alt_text }}" aria-label="{{ __('site.listing.photo') }} {{ $imageIndex + 1 }}" @if($imageIndex === 0) aria-current="true" @endif>
                                    <img src="{{ $image->image_source }}" alt="" loading="lazy">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <x-brief class="mt-8" :text="$brief" :key="'listing-'.$property->slug" :locale="$locale" />

            <h2 class="subheading mt-10">{{ __('site.listing.specs') }}</h2>
            <dl class="spec-grid mt-4">
                @foreach ($specs as [$icon, $key, $value])
                    <div><dt><x-icon :name="$icon" :size="15" /> {{ __('site.common.specs.'.$key) }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
            </dl>

            @if ($property->translatedDescription($locale))
                <h2 class="subheading mt-10">{{ __('site.listing.about') }}</h2>
                <p class="prose-block mt-4">{{ $property->translatedDescription($locale) }}</p>
            @endif

            @if (! empty($property->amenities))
                <h2 class="subheading mt-10">{{ __('site.listing.amenities') }}</h2>
                <ul class="amenity-list mt-4">
                    @foreach ($property->amenities as $amenity)
                        <li><x-icon name="check" :size="14" /> {{ Lang::has('site.common.amenities.'.$amenity) ? __('site.common.amenities.'.$amenity) : Str::headline($amenity) }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($properties->count() > 1)
                <nav class="pager mt-10" aria-label="{{ __('site.listing.counter', ['current' => $propertyIndex + 1, 'total' => $properties->count()]) }}">
                    <a href="{{ $listingUrl($previousProperty) }}" class="btn btn-ghost btn-sm" data-page-link rel="prev"><x-icon name="arrow-left" :size="14" /> {{ __('site.listing.prev') }}</a>
                    <a href="{{ $listingUrl($nextProperty) }}" class="btn btn-ghost btn-sm" data-page-link rel="next">{{ __('site.listing.next') }} <x-icon name="arrow" :size="14" /></a>
                </nav>
            @endif
        </div>

        <aside class="price-panel" aria-label="{{ __('site.listing.price') }}">
            <p class="eyebrow">{{ __('site.listing.price') }}</p>
            <p class="price-panel-price">{{ $agency->formatMoney($property->price) }} @if ($property->isForRent())<small>{{ __('site.common.per_year') }}</small>@endif</p>
            <p class="mt-2 text-xs leading-relaxed text-muted">{{ $property->isForRent() ? __('site.listing.rent_note') : __('site.listing.note') }}</p>

            <div class="mt-5 grid gap-2">
                @if ($property->isAvailable())
                    <a href="{{ route('agency.viewing', ['agencySlug' => $agency->slug, 'lang' => $locale, 'property' => $property->slug]) }}" class="btn btn-accent" data-page-link data-tour-line="{{ __('site.narration.tour.viewing') }}"><x-icon name="calendar" :size="16" /> {{ __('site.listing.schedule') }}</a>
                @else
                    <p class="rounded-xl bg-paper p-3 text-sm">{{ __('site.listing.sold_out_note') }}</p>
                @endif
                <button type="button" class="btn btn-ghost" data-hero-quick-message="{{ __('site.listing.ask_q', ['name' => $name]) }}"><x-icon name="spark" :size="16" class="text-accent" /> {{ __('site.listing.ask') }}</button>
            </div>

            @if ($mortgageDefaults)
                <div class="panel-divider"></div>
                <div class="mortgage" data-mortgage
                    data-url="{{ route('mortgage.simulate', $agency->slug) }}"
                    data-property="{{ $property->slug }}"
                    data-locale="{{ $locale }}"
                    data-currency="{{ $agency->currency }}"
                    data-error="{{ __('site.listing.mortgage.error') }}">
                    <p class="font-bold">{{ __('site.listing.mortgage.title') }}</p>
                    <p class="mt-1 text-xs text-muted">{{ __('site.listing.mortgage.intro', ['rate' => rtrim(rtrim(number_format((float) $agency->mortgage_rate, 2, '.', ''), '0'), '.')]) }}</p>

                    <label class="mt-4">
                        <span class="mortgage-row"><span>{{ __('site.listing.mortgage.down_payment') }}</span><strong><span data-mortgage-dp-label>{{ (int) $mortgageDefaults['down_payment_percent'] }}</span>%</strong></span>
                        <input type="range" name="down_payment_percent" min="{{ \App\Services\Viewing\MortgageCalculator::MIN_DOWN_PAYMENT_PERCENT }}" max="50" step="5" value="{{ (int) $mortgageDefaults['down_payment_percent'] }}">
                    </label>
                    <label class="mt-4">
                        <span class="mortgage-row"><span>{{ __('site.listing.mortgage.tenor') }}</span></span>
                        <select name="tenor_years">
                            @foreach ([5, 10, 15, 20, 25] as $years)
                                <option value="{{ $years }}" @selected($years === $mortgageDefaults['tenor_years'])>{{ $years }} {{ __('site.listing.mortgage.years') }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="mortgage-result" aria-live="polite">
                        <p class="text-xs text-muted">{{ __('site.listing.mortgage.monthly') }}</p>
                        <p class="mortgage-monthly" data-mortgage-out="monthly_installment">{{ $agency->formatMoney($mortgageDefaults['monthly_installment']) }}</p>
                        <dl>
                            <div><dt>{{ __('site.listing.mortgage.dp_amount') }}</dt><dd data-mortgage-out="down_payment">{{ $agency->formatMoney($mortgageDefaults['down_payment']) }}</dd></div>
                            <div><dt>{{ __('site.listing.mortgage.loan') }}</dt><dd data-mortgage-out="loan_amount">{{ $agency->formatMoney($mortgageDefaults['loan_amount']) }}</dd></div>
                            <div><dt>{{ __('site.listing.mortgage.income') }}</dt><dd data-mortgage-out="minimum_monthly_income">{{ $agency->formatMoney($mortgageDefaults['minimum_monthly_income']) }}</dd></div>
                        </dl>
                    </div>
                    <p class="mt-3 text-[11px] leading-relaxed text-muted" data-mortgage-note>{{ __('site.listing.mortgage.disclaimer') }}</p>
                    <button type="button" class="link-arrow mt-3 text-sm" data-hero-quick-message="{{ __('site.listing.mortgage.ask_q', ['name' => $name]) }}">{{ __('site.listing.mortgage.ask') }} <x-icon name="arrow" :size="14" /></button>
                </div>
            @endif
        </aside>
    </section>

    @if ($similar->isNotEmpty())
        <section class="container-x section">
            <div class="section-head"><h2 class="display">{{ __('site.listing.similar') }}</h2></div>
            <div class="property-grid">
                @foreach ($similar as $other)
                    <x-property-card :property="$other" :agency="$agency" :locale="$locale" :order="$loop->index" />
                @endforeach
            </div>
        </section>
    @endif
</x-showroom>
