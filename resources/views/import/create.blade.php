<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div
        x-data="{
            preview: null,
            loading: false,
            advanced: {{ ($errors->has('pasted_text') || old('pasted_text')) ? 'true' : 'false' }},
            onFile(event) {
                const file = event.target.files?.[0];
                if (!file) {
                    this.preview = null;
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => { this.preview = e.target.result; };
                reader.readAsDataURL(file);
            },
            submitScan() {
                this.loading = true;
            }
        }"
        class="space-y-4"
    >
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Scan</h1>
            <p class="mt-1 text-sm text-stone-500">Photograph a receipt or list to add items to {{ $inventory->name }}.</p>
        </div>

        <div
            x-show="loading"
            x-cloak
            class="rounded-2xl border border-stone-200 bg-white px-5 py-8 text-center"
        >
            <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-stone-300 border-t-stone-800"></div>
            <p class="font-medium text-stone-800">Reading photo…</p>
            <p class="mt-1 text-sm text-stone-500">This can take up to a few minutes on first run.</p>
        </div>

        <form
            x-show="!loading"
            method="POST"
            action="{{ route('inventories.scan.ollama', $inventory) }}"
            enctype="multipart/form-data"
            @submit="submitScan"
            class="space-y-4"
        >
            @csrf

            <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-10 text-center hover:border-stone-400">
                <template x-if="!preview">
                    <div>
                        <svg class="mx-auto h-10 w-10 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                        </svg>
                        <p class="mt-3 font-medium text-stone-800">Take or choose a photo</p>
                        <p class="mt-1 text-sm text-stone-500">Uses Ollama ({{ $ollamaModel }})</p>
                    </div>
                </template>
                <template x-if="preview">
                    <img :src="preview" alt="Preview" class="max-h-72 rounded-xl object-contain">
                </template>
                <input
                    type="file"
                    name="image"
                    accept="image/*"
                    capture="environment"
                    required
                    class="sr-only"
                    @change="onFile"
                >
            </label>
            @error('image')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white"
            >
                Process photo
            </button>
        </form>

        <div x-show="!loading" class="rounded-2xl border border-stone-200 bg-white">
            <button
                type="button"
                @click="advanced = !advanced"
                class="flex min-h-12 w-full items-center justify-between px-4 text-sm font-medium text-stone-700"
            >
                <span>Advanced</span>
                <svg class="h-4 w-4 text-stone-400" :class="advanced && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="advanced" x-cloak class="space-y-6 border-t border-stone-100 px-4 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-stone-800">Paste JSON</h2>
                    <p class="mt-1 text-xs text-stone-500">Copy the prompt into a vision model, then paste the JSON response.</p>
                    <textarea readonly rows="4" class="mt-3 w-full rounded-xl border-stone-200 bg-stone-50 p-3 text-xs text-stone-600">{{ $geminiPrompt }}</textarea>

                    <form method="POST" action="{{ route('inventories.scan.paste', $inventory) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        <textarea
                            name="pasted_text"
                            rows="8"
                            required
                            class="w-full rounded-xl border-stone-300 font-mono text-sm focus:border-teal-700 focus:ring-teal-700"
                        >{{ old('pasted_text') }}</textarea>
                        @error('pasted_text')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <input type="file" name="image" accept="image/*" class="block w-full text-sm text-stone-500">
                        <button type="submit" class="flex min-h-11 w-full items-center justify-center rounded-xl border border-stone-300 text-sm font-medium text-stone-700">
                            Review paste
                        </button>
                    </form>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-stone-800">Gemini API</h2>
                    @if ($geminiEnabled)
                        <p class="mt-1 text-xs text-stone-500">Usage today: {{ $geminiUsage }} / {{ $geminiLimit }}</p>
                        <form method="POST" action="{{ route('inventories.scan.gemini', $inventory) }}" enctype="multipart/form-data" class="mt-3 space-y-3" @submit="submitScan">
                            @csrf
                            <input type="file" name="image" accept="image/*" capture="environment" required class="block w-full text-sm text-stone-500">
                            <button type="submit" class="flex min-h-11 w-full items-center justify-center rounded-xl border border-stone-300 text-sm font-medium text-stone-700">
                                Parse with Gemini
                            </button>
                        </form>
                    @else
                        <p class="mt-1 text-xs text-stone-500">Set <code class="rounded bg-stone-100 px-1">GEMINI_ENABLED=true</code> and <code class="rounded bg-stone-100 px-1">GEMINI_API_KEY</code> to enable.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
