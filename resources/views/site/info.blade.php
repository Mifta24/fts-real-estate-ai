@php
    $mapUrl = $agency->latitude && $agency->longitude
        ? 'https://www.google.com/maps?q='.$agency->latitude.','.$agency->longitude
        : 'https://www.google.com/maps?q='.rawurlencode(trim($agency->address.' '.$agency->city));
    $tabs = collect(__('site.info.tabs'))->filter(fn ($label, $category) => ($guideItems[$category] ?? collect())->isNotEmpty());
@endphp
<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="__('site.info.title')">
    <section class="container-x page-head">
        <span class="eyebrow">{{ __('site.info.eyebrow', ['name' => $agency->name]) }}</span>
        <h1 class="display">{{ __('site.info.title') }}</h1>
        <x-brief class="mt-6 max-w-3xl" :text="$brief" key="info" :locale="$locale" />
    </section>

    <section class="container-x mt-10 info-layout">
        <aside class="info-card">
            <p class="font-bold">{{ __('site.info.about') }}</p>
            @if ($agency->translatedDescription($locale))
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ $agency->translatedDescription($locale) }}</p>
            @endif
            @foreach ($aboutItems as $item)
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ $item->translatedBody($locale) }}</p>
            @endforeach
            <div class="panel-divider"></div>
            <dl>
                <div><dt>{{ __('site.info.address') }}</dt><dd>{{ $agency->address }}, {{ $agency->city }}, {{ $agency->country }}</dd></div>
                <div><dt>{{ __('site.info.hours') }}</dt><dd>{{ __('site.common.every_day') }} · {{ substr((string) $agency->office_opens_at, 0, 5) }}–{{ substr((string) $agency->office_closes_at, 0, 5) }}</dd></div>
                <div><dt>{{ __('site.info.mortgage_rate') }}</dt><dd>{{ rtrim(rtrim(number_format((float) $agency->mortgage_rate, 2, '.', ''), '0'), '.') }}% · fixed</dd></div>
                @if ($agency->phone)<div><dt>{{ __('site.info.contact') }}</dt><dd>{{ $agency->phone }}@if ($agency->email)<br>{{ $agency->email }}@endif</dd></div>@endif
            </dl>
            <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm mt-6"><x-icon name="pin" :size="14" /> {{ __('site.info.map') }}</a>
        </aside>

        <div class="min-w-0">
            @if ($tabs->isNotEmpty())
                <div class="info-tabs">
                    @foreach ($tabs as $category => $label)
                        <input type="radio" name="info-tab" id="info-tab-{{ $category }}" class="info-tab-radio" @checked($loop->first)>
                    @endforeach

                    <div class="info-tablist">
                        @foreach ($tabs as $category => $label)
                            <label for="info-tab-{{ $category }}">{{ $label }}</label>
                        @endforeach
                    </div>

                    <div class="info-tabpanels">
                        @foreach ($tabs as $category => $label)
                            <div class="info-tabpanel" data-panel="{{ $category }}">
                                @foreach ($guideItems[$category] as $item)
                                    <details class="info-row" @if($loop->first) open @endif>
                                        <summary><span>{{ $item->translatedTitle($locale) }}</span><span aria-hidden="true" class="info-row-chevron">+</span></summary>
                                        <p>{{ $item->translatedBody($locale) }}</p>
                                    </details>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <button type="button" data-hero-quick-message="{{ __('site.info.ask_q') }}" class="btn btn-dark mt-8"><x-icon name="spark" :size="16" /> {{ __('site.info.ask') }}</button>
        </div>
    </section>
</x-showroom>
