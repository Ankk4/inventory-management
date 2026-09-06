<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div class="space-y-4">
        <h1 class="text-xl font-semibold tracking-tight">Imports</h1>

        <div class="space-y-3">
            @forelse ($receipts as $receipt)
                <a
                    href="{{ route('receipts.show', $receipt) }}"
                    class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-4"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $receipt->store_name ?? 'Unknown store' }}</p>
                        <p class="text-sm text-stone-500">{{ $receipt->purchased_at?->format('Y-m-d') ?? $receipt->created_at->format('Y-m-d') }} · {{ $receipt->lines_count }} lines</p>
                    </div>
                    <span class="text-xs text-stone-400">{{ $receipt->parser_source }}</span>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-10 text-center text-sm text-stone-500">
                    No imports yet.
                </div>
            @endforelse
        </div>

        @if ($receipts->hasPages())
            <div class="mt-4">{{ $receipts->links() }}</div>
        @endif
    </div>
</x-app-layout>
