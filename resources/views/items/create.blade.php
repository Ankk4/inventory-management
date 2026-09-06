<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div class="space-y-4">
        <div>
            <a href="{{ route('inventories.show', $inventory) }}" class="text-sm text-teal-800 hover:underline">← Back</a>
            <h1 class="mt-2 text-xl font-semibold tracking-tight">Add item</h1>
        </div>

        <form method="POST" action="{{ route('inventories.items.store', $inventory) }}" class="space-y-4 rounded-2xl border border-stone-200 bg-white p-4">
            @csrf
            @include('items._form')
            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white">
                Save
            </button>
        </form>
    </div>
</x-app-layout>
