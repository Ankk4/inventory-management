<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-11 px-4 py-2 bg-stone-900 border border-transparent rounded-xl font-medium text-sm text-white hover:bg-stone-800 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
