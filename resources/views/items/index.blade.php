<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Inventory') }}</h2>
            <a href="{{ route('items.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">Add item</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="mb-6 flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search items..." class="border-gray-300 rounded-md shadow-sm flex-1">
                    <button class="bg-gray-800 text-white px-4 py-2 rounded-md">Search</button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-4 py-2">
                                    <a href="{{ route('items.show', $item) }}" class="text-indigo-600 hover:underline">{{ $item->name }}</a>
                                </td>
                                <td class="px-4 py-2 text-gray-600">{{ $item->category?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ number_format($item->quantity, 2) }}</td>
                                <td class="px-4 py-2">{{ $item->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No items yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
