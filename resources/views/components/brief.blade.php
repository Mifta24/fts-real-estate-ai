@props(['text', 'key', 'locale'])
{{-- The AI advisor "speaking" a short intro for the page. $text is built from stored agency data, never generated. --}}
<div {{ $attributes->merge(['class' => 'brief']) }} data-narrator data-key="{{ $key }}" data-lang="{{ ['id' => 'id-ID', 'en' => 'en-US', 'ja' => 'ja-JP'][$locale] }}" data-stop-label="{{ __('site.narration.stop') }}" data-listen-label="{{ __('site.narration.listen') }}">
    <span class="brief-avatar" aria-hidden="true">{{ mb_substr(__('site.advisor.name'), 0, 1) }}</span>
    <div class="brief-body">
        <p class="brief-name">
            {{ __('site.narration.brief') }}
            <span class="brief-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
        </p>
        <p class="sr-only">{{ $text }}</p>
        <p class="brief-text" data-narrator-text data-text="{{ $text }}" aria-hidden="true">{{ $text }}</p>
        <div class="brief-actions">
            <button type="button" data-narrator-listen hidden>{{ __('site.narration.listen') }}</button>
            <button type="button" data-narrator-skip>{{ __('site.narration.skip') }}</button>
        </div>
    </div>
</div>
