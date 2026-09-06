<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div
        class="space-y-4"
        x-data="{
            lines: {{ Js::from($lines->map(fn ($l) => [
                'raw_name' => $l['raw_name'],
                'quantity' => $l['quantity'],
                'unit' => $l['unit'],
                'unit_price' => $l['unit_price'],
                'line_total' => $l['line_total'],
                'skipped' => false,
                'matched_item_id' => $l['best_match_id'],
                'create_item' => $l['best_match_id'] ? false : true,
                'new_item_name' => $l['raw_name'],
                'category_id' => null,
            ])) }}
        }"
    >
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Review import</h1>
            <p class="mt-1 text-sm text-stone-500">Confirm lines before adding them to {{ $inventory->name }}.</p>
        </div>

        <form method="POST" action="{{ route('inventories.scan.confirm', $inventory) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="parser_source" value="{{ $draft->parserSource }}">
            <input type="hidden" name="image_path" value="{{ $imagePath }}">

            <div class="rounded-2xl border border-stone-200 bg-white p-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-stone-500">Store</label>
                    <input type="text" name="store_name" value="{{ $draft->storeName }}" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-stone-500">Date</label>
                        <input type="date" name="purchase_date" value="{{ $draft->purchaseDate }}" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-500">Currency</label>
                        <input type="text" name="currency" value="{{ $draft->currency }}" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-500">Total</label>
                    <input type="number" step="0.01" name="total" value="{{ $draft->total }}" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                </div>
                @if ($imagePath)
                    <p class="text-xs text-stone-500">Photo saved with this import.</p>
                @endif
            </div>

            <template x-for="(line, index) in lines" :key="index">
                <div class="rounded-2xl border border-stone-200 bg-white p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-medium text-stone-800" x-text="line.raw_name"></h3>
                        <label class="flex items-center gap-2 text-sm text-stone-500">
                            <input type="checkbox" x-model="line.skipped" class="rounded border-stone-300 text-teal-800 focus:ring-teal-700">
                            Skip
                        </label>
                    </div>

                    <div class="space-y-3" x-show="!line.skipped">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-stone-500">Quantity</label>
                                <input type="number" step="0.001" x-model="line.quantity" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-500">Unit</label>
                                <input type="text" x-model="line.unit" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-500">Match existing item</label>
                            <select
                                x-model="line.matched_item_id"
                                @change="line.create_item = !line.matched_item_id"
                                class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700"
                            >
                                <option value="">— Create new —</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ number_format($item->quantity, 2) }} {{ $item->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="line.create_item">
                            <label class="block text-xs font-medium text-stone-500">New item name</label>
                            <input type="text" x-model="line.new_item_name" class="mt-1 w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700">
                        </div>
                    </div>

                    <input type="hidden" :name="'lines[' + index + '][raw_name]'" :value="line.raw_name">
                    <input type="hidden" :name="'lines[' + index + '][quantity]'" :value="line.quantity">
                    <input type="hidden" :name="'lines[' + index + '][unit]'" :value="line.unit">
                    <input type="hidden" :name="'lines[' + index + '][unit_price]'" :value="line.unit_price">
                    <input type="hidden" :name="'lines[' + index + '][line_total]'" :value="line.line_total">
                    <input type="hidden" :name="'lines[' + index + '][skipped]'" :value="line.skipped ? 1 : 0">
                    <input type="hidden" :name="'lines[' + index + '][matched_item_id]'" :value="line.skipped || line.create_item ? '' : line.matched_item_id">
                    <input type="hidden" :name="'lines[' + index + '][create_item]'" :value="(!line.skipped && line.create_item) ? 1 : 0">
                    <input type="hidden" :name="'lines[' + index + '][new_item_name]'" :value="line.new_item_name">
                    <input type="hidden" :name="'lines[' + index + '][category_id]'" :value="line.category_id">
                </div>
            </template>

            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white">
                Confirm and update stock
            </button>
        </form>
    </div>
</x-app-layout>
