<header
    class="sticky top-0 z-30 border-b border-stone-200 bg-stone-50/95 backdrop-blur"
    x-data="{ open: false, creating: false }"
>
    <div class="mx-auto flex h-14 max-w-xl items-center justify-between gap-3 px-4">
        <button
            type="button"
            @click="open = !open"
            class="inline-flex min-h-11 max-w-[70%] items-center gap-1.5 rounded-xl px-2 py-1 text-left hover:bg-stone-200/60"
        >
            <span class="truncate text-base font-semibold tracking-tight">{{ $inventory->name }}</span>
            <svg class="h-4 w-4 shrink-0 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <a href="{{ route('profile.edit') }}" class="text-sm text-stone-500 hover:text-stone-800">Profile</a>
    </div>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false; creating = false"
        class="absolute inset-x-0 top-14 z-40 mx-auto max-w-xl border-b border-stone-200 bg-white px-4 py-3 shadow-sm"
    >
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-stone-400">Inventories</p>
        <div class="space-y-1">
            @foreach ($inventories as $option)
                <a
                    href="{{ route('inventories.show', $option) }}"
                    class="flex min-h-11 items-center rounded-xl px-3 text-sm {{ $option->id === $inventory->id ? 'bg-stone-100 font-semibold text-teal-800' : 'text-stone-700 hover:bg-stone-50' }}"
                >
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <button
            type="button"
            @click="creating = !creating"
            class="mt-3 flex min-h-11 w-full items-center rounded-xl px-3 text-sm font-medium text-teal-800 hover:bg-teal-50"
        >
            New inventory
        </button>

        <form
            x-show="creating"
            x-cloak
            method="POST"
            action="{{ route('inventories.store') }}"
            class="mt-2 space-y-2 border-t border-stone-100 pt-3"
        >
            @csrf
            <input
                type="text"
                name="name"
                required
                maxlength="255"
                placeholder="e.g. Cabin"
                class="w-full rounded-xl border-stone-300 text-sm focus:border-teal-700 focus:ring-teal-700"
            >
            <button type="submit" class="flex min-h-11 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white">
                Create
            </button>
        </form>
    </div>
</header>
