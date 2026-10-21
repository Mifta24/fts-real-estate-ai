@php
    $bookable = $properties->filter->isAvailable()->values();
    $wizard = __('site.viewing');
@endphp
<x-showroom :agency="$agency" :locale="$locale" :supported-locales="$supportedLocales" :page="$page" :menu-items="$menuItems" :contact-links="$contactLinks" :title="__('site.viewing.title')">
    <section class="container-x page-head">
        <span class="eyebrow">{{ __('site.viewing.eyebrow') }}</span>
        <h1 class="display">{{ __('site.viewing.title') }}</h1>
        <p class="mt-4 max-w-2xl leading-relaxed text-muted">{{ __('site.viewing.intro') }}</p>
    </section>

    <section class="container-x mt-8 wizard-shell">
        <div class="wizard-card" data-wizard
            data-slots-url="{{ route('viewing.slots', $agency->slug) }}"
            data-submit-url="{{ route('viewing.store', $agency->slug) }}"
            data-agency-slug="{{ $agency->slug }}"
            data-locale="{{ $locale }}"
            data-earliest="{{ $earliestDate }}"
            data-latest="{{ $latestDate }}"
            data-preselect-property="{{ $preselectedProperty }}"
        >
            <div data-wizard-flow>
                <div class="wizard-progress">
                    <p class="wizard-step-label" data-wizard-step-label aria-live="polite"></p>
                    <ol>
                        @foreach (__('site.viewing.steps') as $stepLabel)
                            <li data-progress-step>{{ $stepLabel }}</li>
                        @endforeach
                    </ol>
                </div>

                <form data-wizard-form novalidate autocomplete="on">
                    <div data-step="1" class="wizard-step">
                        <fieldset>
                            <legend class="wizard-legend">{{ __('site.viewing.choose_property') }}</legend>
                            <div class="wizard-properties">
                                @foreach ($bookable as $property)
                                    <label class="wizard-property" data-property-option data-name="{{ $property->translatedName($locale) }}">
                                        <input type="radio" name="property_slug" value="{{ $property->slug }}">
                                        @if ($property->images->first())
                                            <img src="{{ $property->images->first()->image_source }}" alt="" loading="lazy">
                                        @else
                                            <span class="wizard-property-ph"></span>
                                        @endif
                                        <span class="min-w-0">
                                            <strong>{{ $property->translatedName($locale) }}</strong>
                                            <span>{{ __('site.common.property_types.'.$property->property_type) }} · {{ $property->district }}</span>
                                            <span class="font-semibold text-ink">{{ $agency->formatCompactMoney($property->price, $locale) }}@if ($property->isForRent()) {{ __('site.common.per_year') }}@endif</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div data-step="2" class="wizard-step" hidden>
                        <label class="wizard-field max-w-xs">
                            <span>{{ __('site.viewing.date') }}</span>
                            <input type="date" name="date" min="{{ $earliestDate }}" max="{{ $latestDate }}" required>
                        </label>
                        <fieldset>
                            <legend class="wizard-legend">{{ __('site.viewing.time') }}</legend>
                            <div class="slot-grid" data-slot-grid>
                                @foreach (\App\Services\Viewing\ViewingService::TIME_SLOTS as $slot)
                                    <label class="slot"><input type="radio" name="time" value="{{ $slot }}" disabled><span>{{ $slot }}</span></label>
                                @endforeach
                            </div>
                            <p class="wizard-hint mt-3" data-slot-hint aria-live="polite">{{ __('site.viewing.pick_date_first') }}</p>
                        </fieldset>
                    </div>

                    <div data-step="3" class="wizard-step" hidden>
                        <label class="wizard-field">
                            <span>{{ __('site.viewing.name') }}</span>
                            <input type="text" name="visitor_name" maxlength="100" autocomplete="name" required>
                        </label>
                        <fieldset>
                            <legend class="wizard-legend">{{ __('site.viewing.contact_method') }}</legend>
                            <div class="pill-group">
                                @foreach (['whatsapp', 'phone', 'email'] as $method)
                                    <label><input type="radio" name="contact_type" value="{{ $method }}" @checked($method === 'whatsapp')><span>{{ __('site.viewing.'.$method) }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <label class="wizard-field">
                            <span>{{ __('site.viewing.contact_value') }}</span>
                            <input type="text" name="contact_value" maxlength="120" autocomplete="tel" required>
                        </label>
                        <div class="grid gap-5 sm:grid-cols-[160px_minmax(0,1fr)]">
                            <label class="wizard-field">
                                <span>{{ __('site.viewing.party_size') }}</span>
                                <input type="number" name="party_size" min="1" max="{{ \App\Services\Viewing\ViewingService::MAX_PARTY_SIZE }}" value="2" inputmode="numeric" required>
                            </label>
                            <fieldset>
                                <legend class="wizard-legend">{{ __('site.viewing.payment_plan') }}</legend>
                                <div class="pill-group">
                                    @foreach (__('site.viewing.plans') as $plan => $planLabel)
                                        <label><input type="radio" name="payment_plan" value="{{ $plan }}" @checked($plan === 'undecided')><span>{{ $planLabel }}</span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                        <label class="wizard-field">
                            <span>{{ __('site.viewing.notes') }}</span>
                            <textarea name="notes" rows="2" maxlength="500" placeholder="{{ __('site.viewing.notes_placeholder') }}"></textarea>
                        </label>
                    </div>

                    <div data-step="4" class="wizard-step" hidden>
                        <dl class="wizard-summary" data-summary></dl>
                        <p class="wizard-hint">{{ __('site.viewing.disclaimer') }}</p>
                        <p class="wizard-hint">{{ __('site.viewing.privacy', ['days' => config('retention.viewing_days')]) }}</p>
                    </div>

                    <div class="wizard-error" data-wizard-error role="alert" hidden></div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-ghost" data-wizard-back hidden><x-icon name="arrow-left" :size="16" /> {{ __('site.viewing.back') }}</button>
                        <button type="button" class="btn btn-dark" data-wizard-next>{{ __('site.viewing.next') }} <x-icon name="arrow" :size="16" /></button>
                        <button type="submit" class="btn btn-accent" data-wizard-submit hidden>{{ __('site.viewing.submit') }}</button>
                    </div>
                </form>
            </div>

            <div class="wizard-done" data-wizard-done hidden>
                <span class="wizard-done-icon"><x-icon name="check" :size="30" /></span>
                <p class="mt-5 text-lg font-bold">{{ __('site.viewing.done_title') }}</p>
                <p class="mt-4 text-sm text-muted">{{ __('site.viewing.reference') }}</p>
                <p class="wizard-reference" data-done-reference></p>
                <p class="wizard-status">{{ __('site.viewing.awaiting') }}</p>
                <p class="mx-auto mt-5 max-w-md text-sm leading-relaxed text-muted">{{ __('site.viewing.done_hint') }}</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a class="btn btn-accent" data-done-whatsapp target="_blank" rel="noopener" hidden><x-icon name="whatsapp" :size="16" /> {{ __('site.viewing.send_whatsapp') }}</a>
                    <a class="btn btn-ghost" data-done-phone hidden>{{ __('site.viewing.call_office') }}</a>
                    <a class="btn btn-ghost" data-done-email hidden>{{ __('site.viewing.email_office') }}</a>
                </div>
                <button type="button" class="link-arrow mt-6" data-wizard-reset>{{ __('site.viewing.new_request') }} <x-icon name="arrow" :size="14" /></button>
            </div>

            <script type="application/json" data-wizard-labels>@json($wizard)</script>
        </div>

        <aside class="grid gap-4">
            <div class="info-card">
                <p class="font-bold">{{ __('site.home.how') }}</p>
                <ol class="mt-4 grid gap-4">
                    @foreach (__('site.home.steps') as $step)
                        <li class="flex gap-3"><span class="step-index shrink-0">{{ $loop->iteration }}</span><span><span class="block text-sm font-semibold">{{ $step['title'] }}</span><span class="mt-1 block text-xs leading-relaxed text-muted">{{ $step['body'] }}</span></span></li>
                    @endforeach
                </ol>
            </div>
            <button type="button" class="btn btn-ghost" data-hero-quick-message="{{ __('site.advisor.quick_q.viewing') }}"><x-icon name="spark" :size="16" class="text-accent" /> {{ __('site.advisor.open') }}</button>
        </aside>
    </section>
</x-showroom>
