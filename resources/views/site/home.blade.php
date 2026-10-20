@php
    $available = $properties->filter->isAvailable();
    $cheapest = $available->where('listing_type', 'sale')->sortBy('price')->first() ?? $available->sortBy('price')->first();
@endphp
<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :backdrop="$featured->first()?->images->first()?->image_source">
    <section class="home-stage">
        <div class="home-copy">
            <span class="eyebrow">{{ __('site.home.eyebrow', ['city' => $agency->city]) }}</span>
            <h1 class="display">{{ __('site.home.headline') }}<br><em>{{ __('site.home.headline_em') }}</em></h1>
            <p class="hero-intro">{{ __('site.home.intro') }}</p>

            <label class="prompt-label" for="hero-prompt"><x-icon name="spark" :size="16" /> {{ __('site.home.prompt_label') }}</label>
            <form class="prompt-box" data-advisor-prompt>
                <textarea id="hero-prompt" rows="2" maxlength="500" placeholder="{{ __('site.home.prompt_placeholder') }}" required></textarea>
                <button type="submit" class="btn btn-accent">{{ __('site.home.prompt_submit') }} <x-icon name="arrow" :size="16" /></button>
            </form>
            <div class="chip-row" aria-label="{{ __('site.home.try') }}">
                @foreach (__('site.home.prompts') as $prompt)
                    <button type="button" class="chip" data-hero-quick-message="{{ $prompt }}">{{ $prompt }}</button>
                @endforeach
            </div>

            <div class="home-stats">
                <div><span class="stat-value">{{ $available->count() }}</span><span class="stat-label">{{ __('site.home.stat_listings') }}</span></div>
                <div><span class="stat-value">{{ $cheapest ? $agency->formatCompactMoney($cheapest->price, $locale) : '—' }}</span><span class="stat-label">{{ __('site.home.stat_from') }}</span></div>
                <div><span class="stat-value">{{ __('site.home.stat_response_value') }}</span><span class="stat-label">{{ __('site.home.stat_response') }}</span></div>
            </div>
        </div>

        @if ($featured->isNotEmpty())
            <aside class="home-featured" aria-label="{{ __('site.home.featured') }}">
                <p class="eyebrow">{{ __('site.home.featured') }}</p>
                @foreach ($featured as $property)
                    <a href="{{ route('agency.listing', ['agencySlug' => $agency->slug, 'propertySlug' => $property->slug, 'lang' => $locale]) }}" class="mini-card" data-page-link>
                        @if ($property->images->first())<img src="{{ $property->images->first()->image_source }}" alt="" loading="lazy">@endif
                        <span class="min-w-0">
                            <span class="mini-card-type">{{ __('site.common.property_types.'.$property->property_type) }} · {{ $property->district }}</span>
                            <span class="mini-card-name">{{ $property->translatedName($locale) }}</span>
                            <span class="mini-card-price">{{ $agency->formatCompactMoney($property->price, $locale) }}</span>
                        </span>
                    </a>
                @endforeach
                <a href="{{ route('agency.listings', ['agencySlug' => $agency->slug, 'lang' => $locale]) }}" class="link-arrow mini-more" data-page-link data-tour-line="{{ __('site.narration.tour.listings') }}">{{ __('site.home.see_all') }} <x-icon name="arrow" :size="16" /></a>
            </aside>
        @endif
    </section>
</x-showroom>
