<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import Receipt') }}</h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'paste' }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex gap-4 border-b mb-6">
                    <button type="button" @click="tab = 'paste'" :class="tab === 'paste' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium">Paste JSON</button>
                    <button type="button" @click="tab = 'ollama'" :class="tab === 'ollama' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium">Ollama</button>
                    <button type="button" @click="tab = 'gemini'" :class="tab === 'gemini' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 font-medium">Gemini API</button>
                </div>

                <div x-show="tab === 'paste'">
                    <p class="text-sm text-gray-600 mb-4">Copy this prompt into Gemini (web), attach your receipt image, then paste the JSON response below.</p>
                    <textarea readonly rows="6" class="w-full text-xs bg-gray-50 border rounded-md p-3 mb-4">{{ $geminiPrompt }}</textarea>

                    <form method="POST" action="{{ route('import.paste') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pasted JSON</label>
                            <textarea name="pasted_text" rows="12" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm font-mono text-sm">{{ old('pasted_text') }}</textarea>
                            @error('pasted_text')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Receipt image (optional, for archive)</label>
                            <input type="file" name="image" accept="image/*" class="mt-1">
                        </div>
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-md">Review import</button>
                    </form>
                </div>

                <div x-show="tab === 'ollama'" x-cloak>
                    <p class="text-sm text-gray-600 mb-4">Requires Ollama running locally with model <code class="bg-gray-100 px-1 rounded">{{ $ollamaModel }}</code>.</p>
                    <form method="POST" action="{{ route('import.ollama') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Receipt image</label>
                            <input type="file" name="image" accept="image/*" required class="mt-1">
                            @error('image')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                        </div>
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-md">Parse with Ollama</button>
                    </form>
                </div>

                <div x-show="tab === 'gemini'" x-cloak>
                    @if ($geminiEnabled)
                        <p class="text-sm text-gray-600 mb-4">Free-tier usage today: {{ $geminiUsage }} / {{ $geminiLimit }}</p>
                        <form method="POST" action="{{ route('import.gemini') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Receipt image</label>
                                <input type="file" name="image" accept="image/*" required class="mt-1">
                                @error('image')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                            </div>
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-md">Parse with Gemini</button>
                        </form>
                    @else
                        <p class="text-gray-600">Set <code class="bg-gray-100 px-1 rounded">GEMINI_ENABLED=true</code> and <code class="bg-gray-100 px-1 rounded">GEMINI_API_KEY</code> in your <code class="bg-gray-100 px-1 rounded">.env</code> to enable.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
