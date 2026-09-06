<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-start justify-between gap-3">
            <div>
                <a href="{{ route('inventories.show', $inventory) }}" class="text-sm text-teal-800 hover:underline">← Stock</a>
                <h1 class="mt-2 text-xl font-semibold tracking-tight">{{ $item->name }}</h1>
            </div>
            <a href="{{ route('inventories.items.edit', [$inventory, $item]) }}" class="text-sm font-medium text-teal-800 hover:underline">Edit</a>
        </div>

        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-stone-200 bg-white p-4">
            <div>
                <p class="text-xs text-stone-500">Category</p>
                <p class="mt-1 font-medium">{{ $item->category?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-stone-500">Quantity</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($item->quantity, 2) }} <span class="text-base font-normal text-stone-500">{{ $item->unit }}</span></p>
            </div>
            <div>
                <p class="text-xs text-stone-500">Barcode</p>
                <p class="mt-1 font-medium">{{ $item->barcode ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-stone-500">Notes</p>
                <p class="mt-1 font-medium">{{ $item->notes ?? '—' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">Adjust stock</h2>
            <form method="POST" action="{{ route('inventories.items.adjust', [$inventory, $item]) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-sm text-stone-700">Delta (+/-)</label>
                    <input type="number" step="0.001" name="delta" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
                </div>
                <div>
                    <label class="block text-sm text-stone-700">Reason</label>
                    <select name="reason" class="mt-1 w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
                        <option value="manual_adjustment">Manual adjustment</option>
                        <option value="consumed">Consumed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-stone-700">Notes</label>
                    <input type="text" name="notes" class="mt-1 w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
                </div>
                <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white">
                    Apply
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">Recent movements</h2>
            <div class="mt-3 space-y-2">
                @forelse ($item->stockMovements as $movement)
                    <div class="flex items-center justify-between gap-3 border-b border-stone-100 py-2 text-sm last:border-0">
                        <span class="text-stone-600">{{ str_replace('_', ' ', $movement->reason) }}</span>
                        <span class="font-medium tabular-nums {{ $movement->delta >= 0 ? 'text-teal-800' : 'text-red-700' }}">
                            {{ $movement->delta >= 0 ? '+' : '' }}{{ number_format($movement->delta, 2) }}
                        </span>
                        <span class="text-stone-400">{{ $movement->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">No movements yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
