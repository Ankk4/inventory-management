<x-app-layout :inventory="$inventory" :inventories="$inventories">
    @if (session('status'))
        <div class="mb-4 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Stock</h1>
            <p class="text-sm text-stone-500">{{ $items->total() }} {{ $items->total() === 1 ? 'item' : 'items' }}</p>
        </div>
        <a
            href="{{ route('inventories.items.create', $inventory) }}"
            class="inline-flex min-h-11 items-center rounded-xl border border-stone-300 bg-white px-3 text-sm font-medium text-stone-700"
        >
            Add item
        </a>
    </div>

    <form method="GET" action="{{ route('inventories.show', $inventory) }}" class="mb-4">
        <input
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="Search items…"
            class="w-full rounded-2xl border-stone-300 bg-white text-sm focus:border-teal-700 focus:ring-teal-700"
        >
    </form>

    <div class="space-y-3">
        @forelse ($items as $item)
            <a
                href="{{ route('inventories.items.show', [$inventory, $item]) }}"
                class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-4"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium text-stone-800">{{ $item->name }}</p>
                    <p class="text-sm text-stone-500">{{ $item->category?->name ?? 'Uncategorized' }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="font-semibold tabular-nums text-stone-800">{{ number_format($item->quantity, 2) }}</p>
                    <p class="text-xs text-stone-500">{{ $item->unit }}</p>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
                <p class="font-medium text-stone-800">Nothing here yet</p>
                <p class="mt-2 text-sm text-stone-500">Scan a receipt or list to fill this pantry.</p>
                <a
                    href="{{ route('inventories.scan', $inventory) }}"
                    class="mt-6 inline-flex min-h-12 items-center justify-center rounded-xl bg-stone-900 px-5 text-sm font-medium text-white"
                >
                    Scan a photo
                </a>
            </div>
        @endforelse
    </div>

    @if ($items->hasPages())
        <div class="mt-6">{{ $items->links() }}</div>
    @endif
</x-app-layout>
