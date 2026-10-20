<x-admin-layout title="Listings">
    <x-slot name="actions">
        <a href="{{ route('admin.properties.create') }}" class="btn btn-dark btn-sm">Add listing</a>
    </x-slot>

    <div class="overflow-x-auto rounded-2xl border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-paper text-xs uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Listing</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Units</th>
                    <th class="px-4 py-3">Photos</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($properties as $property)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $property->name }} @if ($property->is_featured)<span class="badge badge-alert ml-1">Featured</span>@endif</p>
                            <p class="text-xs text-muted">{{ $property->district }} · {{ $property->bedrooms }} bd · {{ $property->building_size_sqm ?? '–' }}/{{ $property->land_size_sqm ?? '–' }} m²</p>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ ucfirst($property->listing_type) }} · {{ ucfirst($property->property_type) }}</td>
                        <td class="px-4 py-3">{{ $agency->formatMoney($property->price) }}</td>
                        <td class="px-4 py-3">{{ $property->units_available }}</td>
                        <td class="px-4 py-3">{{ $property->images_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $property->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">
                                {{ $property->is_active ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.properties.edit', $property) }}" class="text-ink hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" class="inline" onsubmit="return confirm('Delete this listing?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted">No listings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
