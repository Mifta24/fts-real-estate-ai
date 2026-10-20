<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="__('site.agents.title')">
    <section class="container-x page-head">
        <span class="eyebrow">{{ __('site.agents.eyebrow', ['name' => $agency->name]) }}</span>
        <h1 class="display">{{ __('site.agents.title') }}</h1>
        <p class="mt-4 max-w-2xl leading-relaxed text-muted">{{ __('site.agents.intro') }}</p>
        <x-brief class="mt-6 max-w-3xl" :text="__('site.narration.agents')" key="agents" :locale="$locale" />
    </section>

    <section class="container-x mt-10">
        <div class="contact-grid">
            <button type="button" class="contact-card contact-card-featured text-left" data-hero-quick-message="{{ __('site.agents.handover_q') }}">
                <span class="place-icon"><x-icon name="spark" /></span>
                <span class="font-bold">{{ __('site.agents.handover') }}</span>
                <span class="text-sm opacity-75">{{ __('site.advisor.name') }} · {{ __('site.advisor.role') }}</span>
            </button>
            @if ($contactLinks['whatsapp_url'])
                <a href="{{ $contactLinks['whatsapp_url'] }}" target="_blank" rel="noopener" class="contact-card">
                    <span class="place-icon"><x-icon name="whatsapp" /></span>
                    <span class="font-bold">{{ __('site.agents.whatsapp') }}</span>
                    <span class="text-sm text-muted">+{{ $agency->whatsapp }}</span>
                </a>
            @endif
            @if ($contactLinks['phone_url'])
                <a href="{{ $contactLinks['phone_url'] }}" class="contact-card">
                    <span class="place-icon"><x-icon name="phone" /></span>
                    <span class="font-bold">{{ __('site.agents.call') }}</span>
                    <span class="text-sm text-muted">{{ $agency->phone }}</span>
                </a>
            @endif
            @if ($contactLinks['email_url'])
                <a href="{{ $contactLinks['email_url'] }}" class="contact-card">
                    <span class="place-icon"><x-icon name="mail" /></span>
                    <span class="font-bold">{{ __('site.agents.email') }}</span>
                    <span class="text-sm text-muted">{{ $agency->email }}</span>
                </a>
            @endif
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            <div class="info-card">
                <p class="font-bold">{{ __('site.agents.topics') }}</p>
                <ul class="check-list mt-4">
                    @foreach (__('site.agents.topic_list') as $topic)
                        <li><x-icon name="check" :size="16" /> {{ $topic }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="info-card">
                <p class="font-bold">{{ __('site.agents.office') }}</p>
                <p class="mt-3 leading-relaxed text-muted">{{ $agency->address }}<br>{{ $agency->city }}, {{ $agency->country }}</p>
                <p class="mt-3 flex items-center gap-2 text-sm"><x-icon name="clock" :size="16" /> {{ __('site.common.every_day') }} · {{ substr((string) $agency->office_opens_at, 0, 5) }}–{{ substr((string) $agency->office_closes_at, 0, 5) }}</p>
            </div>
        </div>
    </section>
</x-showroom>
