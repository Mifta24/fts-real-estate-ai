<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="__('site.area.title')">
    <section class="container-x page-head">
        <span class="eyebrow">{{ __('site.area.eyebrow', ['city' => $agency->city]) }}</span>
        <h1 class="display">{{ __('site.area.title') }}</h1>
        @if ($brief)
            <x-brief class="mt-6 max-w-3xl" :text="$brief" key="area" :locale="$locale" />
        @endif
    </section>

    <section class="container-x mt-10">
        @if ($places->isEmpty())
            <p class="empty-state">{{ __('site.area.empty') }}</p>
        @else
            <div class="place-grid">
                @foreach ($places as $place)
                    @include('site.place-card', ['place' => $place])
                @endforeach
            </div>
        @endif
    </section>
</x-showroom>
