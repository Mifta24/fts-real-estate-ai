<x-admin-layout :title="__('admin.handovers.detail_title')">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        <div class="rounded-2xl border border-line bg-white">
            <div class="border-b border-line px-4 py-3">
                <p class="font-medium text-stone-900">{{ __('admin.handovers.conversation') }}</p>
                <p class="text-xs text-muted">{{ $handover->conversation->visitor_name ?? __('admin.handovers.visitor') }} · {{ strtoupper($handover->conversation->locale) }}</p>
            </div>
            <div class="max-h-[520px] space-y-3 overflow-y-auto px-4 py-4">
                @foreach ($handover->conversation->messages as $message)
                    <div class="flex {{ $message->role === 'visitor' ? 'justify-end' : 'justify-start' }}">
                        <div @class([
                            'max-w-[80%] rounded-2xl px-3 py-2 text-sm',
                            'bg-navy text-white' => $message->role === 'visitor',
                            'bg-stone-100 text-stone-900' => $message->role === 'assistant',
                            'bg-sky-50 text-sky-900 border border-sky-200' => $message->role === 'agent',
                            'bg-amber-50 text-amber-800 border border-amber-200 text-xs' => $message->role === 'system',
                        ])>
                            @if ($message->role === 'agent')
                                <p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-500">{{ __('admin.handovers.agent') }}</p>
                            @endif
                            {{ $message->content }}
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($handover->status === 'open')
                <form method="POST" action="{{ route('admin.handovers.reply', $handover) }}" class="flex items-center gap-2 border-t border-line p-3">
                    @csrf
                    <input type="text" name="message" required placeholder="{{ __('admin.handovers.reply_placeholder') }}" class="flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white hover:bg-ink">{{ __('admin.handovers.send') }}</button>
                </form>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">{{ __('admin.handover_reason.'.$handover->reason) }}</p>
                <p class="mt-2 text-sm text-amber-900">{{ $handover->summary }}</p>
            </div>

            @if ($handover->status === 'open')
                <form method="POST" action="{{ route('admin.handovers.resolve', $handover) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                        {{ __('admin.handovers.resolve') }}
                    </button>
                </form>
            @else
                <p class="rounded-lg border border-line bg-white px-4 py-2 text-center text-sm text-muted">{{ __('admin.handovers.resolved_at', ['time' => $handover->resolved_at?->diffForHumans()]) }}</p>
            @endif
        </div>
    </div>
</x-admin-layout>
