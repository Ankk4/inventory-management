@php
    $isInventory = request()->routeIs('inventories.show', 'inventories.items.*');
    $isScan = request()->routeIs('inventories.scan', 'inventories.scan.*');
    $isAsk = request()->routeIs('inventories.ask', 'inventories.ask.*');
@endphp

<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200 bg-white/95 backdrop-blur pb-[env(safe-area-inset-bottom)]">
    <div class="mx-auto grid max-w-xl grid-cols-3">
        <a
            href="{{ route('inventories.show', $inventory) }}"
            class="flex min-h-16 flex-col items-center justify-center gap-1 {{ $isInventory ? 'text-teal-800' : 'text-stone-400' }}"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <span class="text-[11px] font-medium {{ $isInventory ? 'font-semibold' : '' }}">Inventory</span>
        </a>

        <a
            href="{{ route('inventories.scan', $inventory) }}"
            class="flex min-h-16 flex-col items-center justify-center gap-1 {{ $isScan ? 'text-teal-800' : 'text-stone-400' }}"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
            </svg>
            <span class="text-[11px] font-medium {{ $isScan ? 'font-semibold' : '' }}">Scan</span>
        </a>

        <a
            href="{{ route('inventories.ask', $inventory) }}"
            class="flex min-h-16 flex-col items-center justify-center gap-1 {{ $isAsk ? 'text-teal-800' : 'text-stone-400' }}"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.25 21a5.99 5.99 0 01-.75-.046A6.002 6.002 0 008.25 16.5c0-.323-.023-.641-.067-.952C5.735 14.67 4.5 13.148 4.5 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            <span class="text-[11px] font-medium {{ $isAsk ? 'font-semibold' : '' }}">Ask</span>
        </a>
    </div>
</nav>
