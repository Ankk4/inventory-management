<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Item') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('items.update', $item) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('items._form', ['item' => $item])
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-md">Update</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
