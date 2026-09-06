<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div class="space-y-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Ask</h1>
            <p class="mt-1 text-sm text-stone-500">Ask a question about {{ $inventory->name }}. Your stock list is added as context automatically.</p>
        </div>

        <form method="POST" action="{{ route('inventories.ask.store', $inventory) }}" class="space-y-3">
            @csrf
            <textarea
                name="question"
                rows="4"
                required
                maxlength="2000"
                placeholder="What can I cook with what I have?"
                class="w-full rounded-2xl border-stone-300 bg-white text-sm focus:border-teal-700 focus:ring-teal-700"
            >{{ old('question', $question) }}</textarea>
            @error('question')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white">
                Ask
            </button>
        </form>

        @if ($question && $answer)
            <div class="space-y-3">
                <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-400">You asked</p>
                    <p class="mt-2 text-sm text-stone-700">{{ $question }}</p>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Answer</p>
                    <div class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-stone-800">{{ $answer }}</div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
