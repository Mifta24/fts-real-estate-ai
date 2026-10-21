<x-admin-layout :title="__('admin.knowledge.title')">
    <x-slot name="actions">
        @can('manage-agency-content')
            <a href="{{ route('admin.knowledge-items.create') }}" class="rounded-lg bg-navy px-3 py-2 text-sm font-medium text-white hover:bg-ink">
                {{ __('admin.knowledge.add') }}
            </a>
        @endcan
    </x-slot>

    <p class="mb-4 text-sm text-muted">{{ __('admin.knowledge.intro') }}</p>

    <div class="overflow-hidden rounded-2xl border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-stone-50 text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('admin.knowledge.category') }}</th>
                    <th class="px-4 py-3">{{ __('admin.knowledge.entry_title') }}</th>
                    <th class="px-4 py-3">{{ __('admin.knowledge.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ __('admin.knowledge.categories.'.$item->category) }}</td>
                        <td class="px-4 py-3 font-medium text-stone-900">{{ $item->title }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $item->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-muted' }}">
                                {{ $item->is_active ? __('admin.knowledge.active') : __('admin.knowledge.hidden') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('manage-agency-content')
                                <a href="{{ route('admin.knowledge-items.edit', $item) }}" class="text-stone-600 hover:underline">{{ __('admin.knowledge.edit') }}</a>
                                <form method="POST" action="{{ route('admin.knowledge-items.destroy', $item) }}" class="inline" onsubmit="return confirm({{ Js::from(__('admin.knowledge.confirm_delete')) }})">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-3 text-red-600 hover:underline">{{ __('admin.knowledge.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-stone-400">{{ __('admin.knowledge.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
