<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                {{ session('status') }}
            </div>
        @endif

        <div>
            @if ($inventory)
                <a href="{{ route('inventories.show', $inventory) }}" class="text-sm text-teal-800 hover:underline">← Stock</a>
            @endif
            <h1 class="mt-2 text-xl font-semibold tracking-tight">
                {{ $receipt->store_name ?? 'Import' }}
            </h1>
            <p class="text-sm text-stone-500">
                {{ $receipt->purchased_at?->format('Y-m-d') ?? $receipt->created_at->format('Y-m-d') }}
                @if ($receipt->inventory)
                    · {{ $receipt->inventory->name }}
                @endif
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-sm">
            <div>
                <p class="text-xs text-stone-500">Total</p>
                <p class="mt-1 font-medium">{{ $receipt->total ? number_format($receipt->total, 2).' '.$receipt->currency : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-stone-500">Parser</p>
                <p class="mt-1 font-medium">{{ $receipt->parser_source }}</p>
            </div>
        </div>

        @if ($receipt->image_path)
            <div class="rounded-2xl border border-stone-200 bg-white p-4">
                <h2 class="font-semibold">Photo</h2>
                <img src="{{ route('receipts.image', ['path' => $receipt->image_path]) }}" alt="Receipt" class="mt-3 max-h-96 rounded-xl border border-stone-200">
            </div>
        @endif

        <div class="rounded-2xl border border-stone-200 bg-white p-4">
            <h2 class="font-semibold">Lines</h2>
            <div class="mt-3 space-y-3">
                @foreach ($receipt->lines as $line)
                    <div class="border-b border-stone-100 pb-3 last:border-0 last:pb-0 {{ $line->skipped ? 'opacity-50' : '' }}">
                        <p class="font-medium">{{ $line->raw_name }}</p>
                        <p class="text-sm text-stone-500">
                            {{ number_format($line->quantity, 2) }} {{ $line->unit }}
                            @if ($line->skipped)
                                · Skipped
                            @elseif ($line->matchedItem && $inventory)
                                · <a href="{{ route('inventories.items.show', [$inventory, $line->matchedItem]) }}" class="text-teal-800 hover:underline">{{ $line->matchedItem->name }}</a>
                            @elseif ($line->matchedItem)
                                · {{ $line->matchedItem->name }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
