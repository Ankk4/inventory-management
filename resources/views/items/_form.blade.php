@php($editing = isset($item))

<div>
    <label class="block text-sm font-medium text-stone-700">Name</label>
    <input type="text" name="name" value="{{ old('name', $editing ? $item->name : '') }}" required class="mt-1 block w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-stone-700">Category</label>
    <select name="category_id" class="mt-1 block w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
        <option value="">— None —</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $editing ? $item->category_id : '') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>

@if (! $editing)
<div>
    <label class="block text-sm font-medium text-stone-700">Initial quantity</label>
    <input type="number" step="0.001" name="quantity" value="{{ old('quantity', 0) }}" class="mt-1 block w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
</div>
@endif

<div>
    <label class="block text-sm font-medium text-stone-700">Unit</label>
    <input type="text" name="unit" value="{{ old('unit', $editing ? $item->unit : 'pcs') }}" class="mt-1 block w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
</div>

<div>
    <label class="block text-sm font-medium text-stone-700">Barcode</label>
    <input type="text" name="barcode" value="{{ old('barcode', $editing ? $item->barcode : '') }}" class="mt-1 block w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">
</div>

<div>
    <label class="block text-sm font-medium text-stone-700">Notes</label>
    <textarea name="notes" rows="3" class="mt-1 block w-full rounded-xl border-stone-300 focus:border-teal-700 focus:ring-teal-700">{{ old('notes', $editing ? $item->notes : '') }}</textarea>
</div>
