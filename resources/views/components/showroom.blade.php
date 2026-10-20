@props(['agency', 'locale', 'supportedLocales', 'page', 'menuItems', 'contactLinks', 'title' => null, 'backdrop' => null])
{{-- The fixed full-screen stage every showroom page shares: photo backdrop, header, numbered menu, a content panel that scrolls on its own, and the AI advisor drawer. --}}
@php
    $scene = ['home' => 'home', 'listings' => 'listings', 'listing' => 'listing_detail', 'area' => 'area', 'place' => 'area_detail', 'info' => 'info', 'viewing' => 'viewing', 'agents' => 'agents'][$page] ?? 'home';
    $activeKey = ['listing' => 'listings', 'place' => 'area'][$page] ?? $page;
    $viewingUrl = route('agency.viewing', ['agencySlug' => $agency->slug, 'lang' => $locale]);
    $backdrop = $backdrop ?: ($agency->cover_path ?: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1920&q=80');
    $menu = [...$menuItems, ['key' => 'viewing', 'label' => __('site.nav.schedule'), 'href' => $viewingUrl, 'tour' => __('site.narration.tour.viewing')]];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $agency->translatedTagline($locale) }}">
    <title>{{ $title ? $title.' · '.$agency->name : $agency->name.' · AI Property Advisor' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site stage-site antialiased" data-showroom data-scene="{{ $scene }}" data-page="{{ $page }}">
    <div class="stage" data-page-kind="{{ $page === 'home' ? 'home' : 'panel' }}">
        <img src="{{ $backdrop }}" alt="" class="stage-bg" fetchpriority="high">
        <div class="stage-shade"></div>

        <header class="stage-header">
            <a href="{{ route('agency.home', ['agencySlug' => $agency->slug, 'lang' => $locale]) }}" class="brand" data-page-link data-tour-line="{{ __('site.narration.tour.home') }}">
                <span class="brand-mark" aria-hidden="true">{{ mb_substr($agency->name, 0, 1) }}</span>
                <span class="min-w-0"><span class="brand-name">{{ $agency->name }}</span><span class="brand-city">{{ $agency->city }}, {{ $agency->country }}</span></span>
            </a>
            <div class="header-tools">
                <nav aria-label="{{ __('site.nav.language') }}" class="lang-switch">
                    @foreach ($supportedLocales as $code)
                        <a href="?lang={{ $code }}" lang="{{ $code }}" aria-label="{{ ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語'][$code] }}" @if($code === $locale) aria-current="true" @endif>{{ $code }}</a>
                    @endforeach
                </nav>
                <x-sound-toggle :on="__('site.common.sound_on')" :off="__('site.common.sound_off')" />
            </div>
        </header>

        <aside class="stage-menu" aria-label="{{ __('site.nav.menu') }}">
            <p class="eyebrow">{{ __('site.nav.menu') }}</p>
            <nav class="stage-menu-list">
                @foreach ($menu as $item)
                    <a href="{{ $item['href'] }}" data-page-link data-tour-line="{{ $item['tour'] }}" @if($item['key'] === $activeKey) aria-current="page" @endif>
                        <span class="stage-menu-num">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span>{{ $item['label'] }}</span>
                        <x-icon name="arrow" :size="15" class="stage-menu-arrow" />
                    </a>
                @endforeach
            </nav>
            <div class="stage-menu-foot">
                <p>{{ __('site.common.office_hours') }}<br><strong>{{ substr((string) $agency->office_opens_at, 0, 5) }}–{{ substr((string) $agency->office_closes_at, 0, 5) }}</strong></p>
                <p class="mt-3">{{ __('site.common.powered_by') }}</p>
            </div>
        </aside>

        <nav class="mobile-nav" aria-label="{{ __('site.nav.menu') }}">
            @foreach ($menu as $item)
                <a href="{{ $item['href'] }}" data-page-link @if($item['key'] === $activeKey) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <main class="stage-main" id="main">
            <div class="site-main">
                {{ $slot }}
            </div>
        </main>
    </div>

    <p class="page-tour" data-page-tour aria-live="polite"></p>
    @include('site.advisor')
    <noscript><p class="site-noscript">{{ __('site.common.noscript') }} {{ $agency->phone }}</p></noscript>
</body>
</html>
