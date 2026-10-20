@php
    $cover = $agencies->first()?->cover_path;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.opening.title') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site antialiased">
    <main class="opening site-main">
        <section class="opening-copy">
            <div class="flex items-center justify-between gap-4">
                <span class="brand"><span class="brand-mark" aria-hidden="true">F</span><span class="brand-name">{{ __('site.opening.title') }}</span></span>
                <nav aria-label="{{ __('site.nav.language') }}" class="lang-switch">
                    @foreach ($supportedLocales as $code)
                        <a href="?lang={{ $code }}" lang="{{ $code }}" aria-label="{{ ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語'][$code] }}" @if($code === $locale) aria-current="true" @endif>{{ $code }}</a>
                    @endforeach
                </nav>
            </div>

            <span class="eyebrow mt-14">{{ __('site.opening.eyebrow') }}</span>
            <h1 class="display">{{ __('site.opening.headline') }}<br><em>{{ __('site.opening.headline_em') }}</em></h1>
            <p class="hero-intro">{{ __('site.opening.tagline') }}</p>
            <ul class="opening-features">
                @foreach (__('site.opening.features') as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>

            <div class="opening-agencies">
                @forelse ($agencies as $agency)
                    <a href="{{ route('agency.home', ['agencySlug' => $agency->slug, 'lang' => $locale]) }}" class="opening-agency" data-page-link>
                        @if ($agency->cover_path)
                            <img src="{{ $agency->cover_path }}" alt="">
                        @else
                            <span class="brand-mark" aria-hidden="true">{{ mb_substr($agency->name, 0, 1) }}</span>
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold">{{ str_replace(':name', $agency->name, __('site.opening.enter')) }}</span>
                            <span class="block text-sm text-muted">{{ $agency->city }} · {{ __('site.opening.listings', ['count' => $agency->properties_count]) }}</span>
                        </span>
                        <x-icon name="arrow" />
                    </a>
                @empty
                    <p class="empty-state">{{ __('site.opening.empty') }}</p>
                @endforelse
            </div>
        </section>

        <div class="opening-visual">
            <img src="{{ $cover ?: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1600&q=80' }}" alt="">
            <p class="opening-quote">{{ __('site.advisor.name') }} · {{ __('site.advisor.role') }}</p>
        </div>
    </main>
</body>
</html>
