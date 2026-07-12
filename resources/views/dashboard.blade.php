<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Items tracked</p>
                    <p class="text-3xl font-semibold">{{ $itemCount }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Total quantity</p>
                    <p class="text-3xl font-semibold">{{ number_format($totalQuantity, 2) }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <a href="{{ route('import.create') }}" class="text-indigo-600 hover:underline font-medium">Import a receipt</a>
                    <p class="text-sm text-gray-500 mt-2">Paste JSON, Ollama, or Gemini</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg mb-4">Low stock</h3>
                        @forelse ($lowStockItems as $item)
                            <div class="flex justify-between py-2 border-b">
                                <a href="{{ route('items.show', $item) }}" class="text-indigo-600 hover:underline">{{ $item->name }}</a>
                                <span class="text-gray-600">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500">No low-stock items.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg mb-4">Recent receipts</h3>
                        @forelse ($recentReceipts as $receipt)
                            <div class="flex justify-between py-2 border-b">
                                <a href="{{ route('receipts.show', $receipt) }}" class="text-indigo-600 hover:underline">
                                    {{ $receipt->store_name ?? 'Unknown store' }}
                                </a>
                                <span class="text-gray-600">{{ $receipt->purchased_at?->format('Y-m-d') ?? $receipt->created_at->format('Y-m-d') }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500">No receipts yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
