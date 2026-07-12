<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $item->name }}</h2>
            <a href="{{ route('items.edit', $item) }}" class="text-indigo-600 hover:underline">Edit</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-2 gap-4">
                <div><span class="text-gray-500">Category</span><p>{{ $item->category?->name ?? '—' }}</p></div>
                <div><span class="text-gray-500">Quantity</span><p class="text-2xl font-semibold">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</p></div>
                <div><span class="text-gray-500">Barcode</span><p>{{ $item->barcode ?? '—' }}</p></div>
                <div><span class="text-gray-500">Notes</span><p>{{ $item->notes ?? '—' }}</p></div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Adjust stock</h3>
                <form method="POST" action="{{ route('items.adjust', $item) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-700">Delta (+/-)</label>
                        <input type="number" step="0.001" name="delta" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Reason</label>
                        <select name="reason" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="manual_adjustment">Manual adjustment</option>
                            <option value="consumed">Consumed</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-700">Notes</label>
                        <input type="text" name="notes" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="md:col-span-4">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-md">Apply</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Recent movements</h3>
                @forelse ($item->stockMovements as $movement)
                    <div class="flex justify-between py-2 border-b text-sm">
                        <span>{{ $movement->reason }}</span>
                        <span class="{{ $movement->delta >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ $movement->delta >= 0 ? '+' : '' }}{{ number_format($movement->delta, 2) }}
                        </span>
                        <span class="text-gray-500">{{ $movement->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No movements yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
