@php
    $quickTopics = collect(['listings', 'mortgage', 'area', 'viewing', 'agent'])->map(fn ($key) => ['label' => __('site.advisor.quick.'.$key), 'message' => __('site.advisor.quick_q.'.$key)]);
    $labels = [
        'intro' => __('site.advisor.intro'),
        'suggestions' => __('site.advisor.suggestions'),
        'thinking' => __('site.advisor.thinking'),
        'send' => __('site.advisor.send'),
        'handedOver' => __('site.advisor.handed_over'),
        'statusSent' => __('site.advisor.status_sent'),
        'statusWaiting' => __('site.advisor.status_waiting'),
        'statusReplied' => __('site.advisor.status_replied'),
        'error' => __('site.advisor.error'),
        'slow' => __('site.advisor.slow'),
        'retry' => __('site.advisor.retry'),
        'connectionError' => __('site.advisor.connection_error'),
        'agent' => __('site.advisor.agent'),
        'talkAgent' => __('site.advisor.talk_agent'),
        'viewDetails' => __('site.advisor.view_details'),
        'detailsQuestion' => __('site.advisor.details_q'),
        'schedule' => __('site.advisor.schedule'),
        'scheduleQuestion' => __('site.advisor.schedule_q'),
        'mortgageTitle' => __('site.advisor.mortgage_title'),
        'monthly' => __('site.advisor.monthly'),
        'downPayment' => __('site.advisor.down_payment'),
        'loan' => __('site.advisor.loan'),
        'tenor' => __('site.advisor.tenor'),
        'years' => __('site.advisor.years'),
        'rate' => __('site.advisor.rate'),
        'income' => __('site.advisor.income'),
        'slotsTitle' => __('site.advisor.slots_title'),
        'noSlots' => __('site.advisor.no_slots'),
        'viewingReceived' => __('site.advisor.viewing_received'),
        'reference' => __('site.advisor.reference'),
        'perYear' => __('site.common.per_year'),
        'sale' => __('site.common.sale'),
        'rent' => __('site.common.rent'),
        'bedroomsShort' => __('site.common.short.bedrooms'),
        'bathroomsShort' => __('site.common.short.bathrooms'),
        'propertyTypes' => __('site.common.property_types'),
    ];
@endphp
<div class="advisor" id="advisor-app" data-advisor
    data-agency-slug="{{ $agency->slug }}"
    data-locale="{{ $locale }}"
    data-currency="{{ $agency->currency }}"
    data-start-url="{{ route('advisor.start', $agency->slug) }}"
    data-message-url="{{ route('advisor.message', $agency->slug) }}"
    data-history-url="{{ route('advisor.history', $agency->slug) }}"
    data-storage-key="advisor_token_{{ $agency->slug }}"
    data-property-url="{{ route('agency.listing', ['agencySlug' => $agency->slug, 'propertySlug' => '__SLUG__', 'lang' => $locale]) }}"
    data-viewing-url="{{ route('agency.viewing', ['agencySlug' => $agency->slug, 'lang' => $locale, 'property' => '__SLUG__']) }}"
    data-agents-url="{{ route('agency.agents', ['agencySlug' => $agency->slug, 'lang' => $locale]) }}"
>
    <button type="button" class="advisor-launcher" data-advisor-launcher aria-controls="advisor-panel" aria-expanded="false">
        <span class="advisor-avatar" aria-hidden="true">{{ mb_substr(__('site.advisor.name'), 0, 1) }}</span>
        <span class="advisor-launcher-dot" aria-hidden="true"></span>
        <span>{{ __('site.advisor.open') }}</span>
    </button>

    <section id="advisor-panel" class="advisor-panel" aria-label="{{ __('site.advisor.name') }} · {{ __('site.advisor.role') }}" aria-hidden="true">
        <header class="advisor-head">
            <span class="advisor-avatar" aria-hidden="true">{{ mb_substr(__('site.advisor.name'), 0, 1) }}</span>
            <div class="min-w-0">
                <p class="advisor-head-name">{{ __('site.advisor.name') }} <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span></p>
                <p class="advisor-head-role truncate">{{ __('site.advisor.role') }} · {{ __('site.advisor.subtitle') }}</p>
                <p class="advisor-status" data-advisor-status aria-live="polite" hidden></p>
            </div>
            <button type="button" class="advisor-close" data-advisor-close aria-label="{{ __('site.advisor.close') }}"><span aria-hidden="true">×</span></button>
        </header>

        <div class="advisor-messages" data-advisor-messages role="log" aria-live="polite"></div>

        <div class="advisor-thinking" data-advisor-thinking role="status" hidden>
            <span class="thinking-dots" aria-hidden="true"><i></i><i></i><i></i></span>
            <span>{{ __('site.advisor.thinking') }}</span>
        </div>
        <div class="advisor-banner" data-advisor-banner hidden></div>

        <form class="advisor-composer" data-advisor-form>
            <input type="text" data-advisor-input aria-label="{{ __('site.advisor.placeholder') }}" maxlength="2000" placeholder="{{ __('site.advisor.placeholder') }}" autocomplete="off">
            <button type="submit" data-advisor-submit>{{ __('site.advisor.send') }}</button>
        </form>

        <div class="advisor-draft" data-advisor-draft role="dialog" aria-modal="false" aria-labelledby="advisor-draft-title" hidden>
            <p id="advisor-draft-title" class="font-semibold">{{ __('site.advisor.draft_title') }}</p>
            <p class="mt-1 text-sm text-muted">{{ __('site.advisor.draft_body') }}</p>
            <div class="mt-3 flex justify-end gap-2">
                <button type="button" class="btn btn-ghost btn-sm" data-advisor-draft-keep>{{ __('site.advisor.draft_keep') }}</button>
                <button type="button" class="btn btn-dark btn-sm" data-advisor-draft-discard>{{ __('site.advisor.draft_discard') }}</button>
            </div>
        </div>
    </section>

    <script type="application/json" data-advisor-labels>@json($labels)</script>
    <script type="application/json" data-advisor-topics>@json($quickTopics)</script>
</div>
