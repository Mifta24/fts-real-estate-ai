<x-admin-layout title="Dashboard">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ([
            ['value' => $stats['active_listings'], 'label' => 'Active listings', 'alert' => false],
            ['value' => $stats['knowledge_items'], 'label' => 'Knowledge items', 'alert' => false],
            ['value' => $stats['pending_viewings'], 'label' => 'Pending viewings', 'alert' => $stats['pending_viewings'] > 0],
            ['value' => $stats['open_handovers'], 'label' => 'Open handovers', 'alert' => $stats['open_handovers'] > 0],
        ] as $stat)
            <div class="rounded-2xl border p-4 {{ $stat['alert'] ? 'border-amber-300 bg-amber-50' : 'border-line bg-white' }}">
                <p class="display text-4xl">{{ $stat['value'] }}</p>
                <p class="text-xs text-muted">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-line bg-white">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <p class="font-semibold">Upcoming viewings</p>
                <a href="{{ route('admin.viewings.index') }}" class="text-xs text-muted hover:underline">View all</a>
            </div>
            <div class="divide-y divide-line">
                @forelse ($upcomingViewings as $viewing)
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium">{{ $viewing->visitor_name }} — {{ $viewing->property->name }}</p>
                        <p class="mt-0.5 text-xs text-muted">{{ $viewing->viewing_date->toFormattedDateString() }} · {{ $viewing->viewing_time }} · {{ $viewing->party_size }} pax · {{ ucfirst($viewing->status) }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-muted">No upcoming viewings.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-line bg-white">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <p class="font-semibold">Open handovers</p>
                <a href="{{ route('admin.handovers.index') }}" class="text-xs text-muted hover:underline">View all</a>
            </div>
            <div class="divide-y divide-line">
                @forelse ($openHandovers as $handover)
                    <a href="{{ route('admin.handovers.show', $handover) }}" class="block px-4 py-3 hover:bg-paper">
                        <p class="text-sm font-medium">{{ ucwords(str_replace('_', ' ', $handover->reason)) }}</p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ $handover->summary }}</p>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-muted">No open handovers.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
