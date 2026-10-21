<x-admin-layout :title="__('admin.nav.listings')">
    <x-slot name="actions">
        @can('manage-agency-content')
            <a href="{{ route('admin.properties.create') }}" class="btn btn-dark btn-sm">{{ __('admin.listings.add') }}</a>
        @endcan
    </x-slot>

    <div class="overflow-x-auto rounded-2xl border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-paper text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('admin.listings.listing') }}</th>
                    <th class="px-4 py-3">{{ __('admin.listings.type') }}</th>
                    <th class="px-4 py-3">{{ __('admin.listings.price') }}</th>
                    <th class="px-4 py-3">{{ __('admin.listings.units') }}</th>
                    <th class="px-4 py-3">{{ __('admin.listings.photos') }}</th>
                    <th class="px-4 py-3">{{ __('admin.listings.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($properties as $property)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $property->name }} @if ($property->is_featured)<span class="badge badge-alert ml-1">{{ __('admin.listings.featured') }}</span>@endif</p>
                            <p class="text-xs text-muted">{{ $property->district }} · {{ $property->bedrooms }} {{ __('admin.listings.bedrooms_short') }} · {{ $property->building_size_sqm ?? '–' }}/{{ $property->land_size_sqm ?? '–' }} m²</p>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ __('site.common.'.$property->listing_type) }} · {{ __('site.common.property_types.'.$property->property_type) }}</td>
                        <td class="px-4 py-3">{{ $agency->formatMoney($property->price) }}</td>
                        <td class="px-4 py-3">{{ $property->units_available }}</td>
                        <td class="px-4 py-3">{{ $property->images_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $property->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">
                                {{ $property->is_active ? __('admin.listings.published') : __('admin.listings.hidden') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @can('manage-agency-content')
                                <a href="{{ route('admin.properties.edit', $property) }}" class="text-ink hover:underline">{{ __('admin.listings.edit') }}</a>
                                <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" class="inline" onsubmit="return confirm({{ Js::from(__('admin.listings.confirm_delete')) }})">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-3 text-red-600 hover:underline">{{ __('admin.listings.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted">{{ __('admin.listings.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
