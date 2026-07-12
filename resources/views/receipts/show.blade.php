<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $receipt->store_name ?? 'Receipt' }} — {{ $receipt->purchased_at?->format('Y-m-d') ?? $receipt->created_at->format('Y-m-d') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div><span class="text-gray-500 text-sm">Total</span><p>{{ $receipt->total ? number_format($receipt->total, 2).' '.$receipt->currency : '—' }}</p></div>
                <div><span class="text-gray-500 text-sm">Parser</span><p>{{ $receipt->parser_source }}</p></div>
                <div><span class="text-gray-500 text-sm">Imported by</span><p>{{ $receipt->user->name }}</p></div>
                <div><span class="text-gray-500 text-sm">Imported at</span><p>{{ $receipt->created_at->format('Y-m-d H:i') }}</p></div>
            </div>

            @if ($receipt->image_path)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Receipt image</h3>
                    <img src="{{ route('receipts.image', ['path' => $receipt->image_path]) }}" alt="Receipt" class="max-h-96 rounded border">
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Lines</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-gray-500">Item</th>
                            <th class="px-3 py-2 text-left text-gray-500">Matched inventory</th>
                            <th class="px-3 py-2 text-left text-gray-500">Qty</th>
                            <th class="px-3 py-2 text-left text-gray-500">Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($receipt->lines as $line)
                            <tr class="{{ $line->skipped ? 'opacity-50' : '' }}">
                                <td class="px-3 py-2">{{ $line->raw_name }}</td>
                                <td class="px-3 py-2">
                                    @if ($line->skipped)
                                        <span class="text-gray-500">Skipped</span>
                                    @elseif ($line->matchedItem)
                                        <a href="{{ route('items.show', $line->matchedItem) }}" class="text-indigo-600 hover:underline">{{ $line->matchedItem->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ number_format($line->quantity, 2) }} {{ $line->unit }}</td>
                                <td class="px-3 py-2">{{ $line->unit_price ? number_format($line->unit_price, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Stock movements</h3>
                @forelse ($receipt->stockMovements as $movement)
                    <div class="flex justify-between py-2 border-b text-sm">
                        <a href="{{ route('items.show', $movement->item) }}" class="text-indigo-600 hover:underline">{{ $movement->item->name }}</a>
                        <span class="text-green-700">+{{ number_format($movement->delta, 2) }}</span>
                    </div>
                @empty
                    <p class="text-gray-500">No stock changes recorded.</p>
                @endforelse
            </div>

            <details class="bg-white shadow-sm sm:rounded-lg p-6">
                <summary class="font-semibold cursor-pointer">Raw parsed JSON</summary>
                <pre class="mt-4 text-xs bg-gray-50 p-4 rounded overflow-auto">{{ json_encode($receipt->raw_payload, JSON_PRETTY_PRINT) }}</pre>
            </details>
        </div>
    </div>
</x-app-layout>
