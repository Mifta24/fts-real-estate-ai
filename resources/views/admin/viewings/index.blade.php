<x-admin-layout :title="__('admin.nav.viewings')">
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach (['' => __('admin.viewings.all'), 'pending' => __('admin.viewing_status.pending'), 'confirmed' => __('admin.viewing_status.confirmed'), 'completed' => __('admin.viewing_status.completed'), 'cancelled' => __('admin.viewing_status.cancelled')] as $value => $label)
            <a href="{{ route('admin.viewings.index', $value ? ['status' => $value] : []) }}"
                class="rounded-full px-3 py-1 {{ $status === $value || (! $status && $value === '') ? 'bg-navy text-white' : 'border border-line bg-white text-muted' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-paper text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('admin.viewings.reference') }}</th>
                    <th class="px-4 py-3">{{ __('admin.viewings.visitor') }}</th>
                    <th class="px-4 py-3">{{ __('admin.viewings.listing') }}</th>
                    <th class="px-4 py-3">{{ __('admin.viewings.schedule') }}</th>
                    <th class="px-4 py-3">{{ __('admin.viewings.plan') }}</th>
                    <th class="px-4 py-3">{{ __('admin.viewings.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($viewings as $viewing)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $viewing->reference }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $viewing->visitor_name }}</p>
                            <p class="text-xs text-muted">{{ $viewing->contact_type ? __('admin.contact_type.'.$viewing->contact_type).': ' : '' }}{{ $viewing->visitor_phone ?? $viewing->visitor_email }}</p>
                            @if ($viewing->notes)<p class="mt-1 max-w-xs text-xs italic text-muted">{{ $viewing->notes }}</p>@endif
                        </td>
                        <td class="px-4 py-3">{{ $viewing->property->name }}<p class="text-xs text-muted">{{ __('admin.viewings.visitors_count', ['count' => $viewing->party_size]) }}</p></td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $viewing->viewing_date->translatedFormat('j M Y') }} · {{ $viewing->viewing_time }}</td>
                        <td class="px-4 py-3 text-xs">{{ __('admin.payment_plan.'.$viewing->payment_plan) }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs',
                                'bg-amber-100 text-amber-700' => $viewing->status === 'pending',
                                'bg-emerald-100 text-emerald-700' => $viewing->status === 'confirmed',
                                'bg-sky-100 text-sky-700' => $viewing->status === 'completed',
                                'bg-stone-100 text-stone-500' => $viewing->status === 'cancelled',
                            ])>{{ __('admin.viewing_status.'.$viewing->status) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @foreach (['pending' => ['confirmed' => __('admin.viewings.confirm'), 'cancelled' => __('admin.viewings.cancel')], 'confirmed' => ['completed' => __('admin.viewings.mark_visited'), 'cancelled' => __('admin.viewings.cancel')]][$viewing->status] ?? [] as $next => $label)
                                <form method="POST" action="{{ route('admin.viewings.status', $viewing) }}" class="inline" @if($next === 'cancelled') onsubmit="return confirm({{ Js::from(__('admin.viewings.confirm_cancel')) }})" @endif>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <button type="submit" class="ml-3 {{ $next === 'cancelled' ? 'text-red-600' : 'text-emerald-700' }} hover:underline">{{ $label }}</button>
                                </form>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted">{{ __('admin.viewings.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $viewings->links() }}</div>
</x-admin-layout>
