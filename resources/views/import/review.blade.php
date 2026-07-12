<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Review Receipt Import') }}</h2>
    </x-slot>

    <div class="py-12" x-data="{
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
    }">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('import.confirm') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                <input type="hidden" name="parser_source" value="{{ $draft->parserSource }}">
                <input type="hidden" name="image_path" value="{{ $imagePath }}">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700">Store</label>
                        <input type="text" name="store_name" value="{{ $draft->storeName }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Date</label>
                        <input type="date" name="purchase_date" value="{{ $draft->purchaseDate }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Currency</label>
                        <input type="text" name="currency" value="{{ $draft->currency }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Total</label>
                        <input type="number" step="0.01" name="total" value="{{ $draft->total }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>

                @if ($imagePath)
                    <p class="text-sm text-gray-600">Receipt image saved for archive.</p>
                @endif

                <template x-for="(line, index) in lines" :key="index">
                    <div class="border rounded-lg p-4 space-y-3">
                        <div class="flex justify-between items-start">
                            <h4 class="font-medium" x-text="line.raw_name"></h4>
                            <label class="text-sm flex items-center gap-2">
                                <input type="checkbox" x-model="line.skipped"> Skip line
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3" x-show="!line.skipped">
                            <div>
                                <label class="block text-xs text-gray-500">Quantity</label>
                                <input type="number" step="0.001" x-model="line.quantity" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Unit</label>
                                <input type="text" x-model="line.unit" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Match existing item</label>
                                <select x-model="line.matched_item_id" @change="line.create_item = !line.matched_item_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">— Create new —</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }} ({{ number_format($item->quantity, 2) }} {{ $item->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="line.create_item">
                                <label class="block text-xs text-gray-500">New item name</label>
                                <input type="text" x-model="line.new_item_name" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
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

                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md font-medium">Confirm and update inventory</button>
            </form>
        </div>
    </div>
</x-app-layout>
