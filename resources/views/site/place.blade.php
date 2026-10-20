@php
    $title = $place->translatedTitle($locale);
    $placeUrl = fn ($item) => route('agency.place', ['agencySlug' => $agency->slug, 'placeId' => $item->id, 'lang' => $locale]);
@endphp
<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="$title">
    <section class="container-x page-head">
        <nav class="breadcrumb">
            <a href="{{ route('agency.area', ['agencySlug' => $agency->slug, 'lang' => $locale]) }}" data-page-link><x-icon name="arrow-left" :size="14" class="inline" /> {{ __('site.area.back') }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ __('site.area.counter', ['current' => $placeIndex + 1, 'total' => $places->count()]) }}</span>
        </nav>
        <div class="mt-5 flex items-center gap-3">
            <span class="place-icon"><x-icon :name="$place->iconName()" /></span>
            @if ($place->distance)<span class="place-card-distance mt-0!">{{ $place->distance }}</span>@endif
        </div>
        <h1 class="display mt-4!">{{ $title }}</h1>
    </section>

    <section class="container-x mt-8 grid gap-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:items-start">
        @if ($place->image_url)
            <div class="gallery-main"><img src="{{ $place->image_url }}" alt="{{ $title }}"></div>
        @endif
        <div>
            <x-brief :text="$place->translatedBody($locale)" :key="'place-'.$place->id" :locale="$locale" />
            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" class="btn btn-dark" data-hero-quick-message="{{ __('site.area.ask_q', ['name' => $title]) }}"><x-icon name="spark" :size="16" /> {{ __('site.area.ask') }}</button>
            </div>
            @if ($places->count() > 1)
                <nav class="pager mt-8">
                    <a href="{{ $placeUrl($previousPlace) }}" class="btn btn-ghost btn-sm" data-page-link rel="prev"><x-icon name="arrow-left" :size="14" /> {{ __('site.area.prev') }}</a>
                    <a href="{{ $placeUrl($nextPlace) }}" class="btn btn-ghost btn-sm" data-page-link rel="next">{{ __('site.area.next') }} <x-icon name="arrow" :size="14" /></a>
                </nav>
            @endif
        </div>
    </section>
</x-showroom>
