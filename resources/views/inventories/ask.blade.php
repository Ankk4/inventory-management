<x-app-layout :inventory="$inventory" :inventories="$inventories">
    <div
        x-data="{
            loading: false,
            question: @js(old('question', $question)),
            setQuestion(text) {
                this.question = text;
                this.$nextTick(() => this.$refs.question?.focus());
            }
        }"
        class="space-y-4"
    >
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Ask</h1>
            <p class="mt-1 text-sm text-stone-500">Ask a question about {{ $inventory->name }}.</p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white px-4 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Talking to</p>
            <p class="mt-1 text-sm font-medium text-stone-800">
                {{ $provider->locationLabel() }}
                <span class="text-stone-400">·</span>
                {{ $provider->vendor }}
                <span class="text-stone-400">·</span>
                <span class="font-normal text-stone-600">{{ $provider->model }}</span>
            </p>
            <p class="mt-0.5 text-xs text-stone-500">{{ $provider->privacyNote() }}</p>
        </div>

        @if ($itemCount === 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <p class="font-medium">This inventory is empty</p>
                <p class="mt-1 text-amber-900/80">Answers will say there is nothing in stock until you add items.</p>
            </div>
        @else
            <details class="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                <summary class="cursor-pointer text-sm font-medium text-stone-800">
                    {{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'items' }} in {{ $inventory->name }}
                    <span class="font-normal text-stone-500">— added to the prompt</span>
                </summary>
                <pre class="mt-3 max-h-64 overflow-y-auto whitespace-pre-wrap rounded-xl bg-stone-50 px-3 py-3 text-xs leading-relaxed text-stone-700">{{ $inventoryContext }}</pre>
            </details>
        @endif

        <div
            x-show="loading"
            x-cloak
            class="rounded-2xl border border-stone-200 bg-white px-5 py-8 text-center"
        >
            <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-stone-300 border-t-stone-800"></div>
            <p class="font-medium text-stone-800">Thinking…</p>
            <p class="mt-1 text-sm text-stone-500">
                @if ($provider->isLocal)
                    This can take a minute on a local model.
                @else
                    Waiting for Gemini…
                @endif
            </p>
        </div>

        <form
            x-show="!loading"
            method="POST"
            action="{{ route('inventories.ask.store', $inventory) }}"
            class="space-y-3"
            @submit="loading = true"
        >
            @csrf
            <textarea
                x-ref="question"
                x-model="question"
                name="question"
                rows="4"
                required
                maxlength="2000"
                placeholder="What can I cook with what I have?"
                class="w-full rounded-2xl border-stone-300 bg-white text-sm focus:border-teal-700 focus:ring-teal-700"
            ></textarea>
            @error('question')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="setQuestion('What can I cook with what I have?')"
                    class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 hover:border-stone-400"
                >
                    What can I cook?
                </button>
                <button
                    type="button"
                    @click="setQuestion('What\'s running low?')"
                    class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 hover:border-stone-400"
                >
                    What’s running low?
                </button>
            </div>

            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-900 text-sm font-medium text-white">
                Ask
            </button>
        </form>

        @if ($question && $answer)
            <div class="space-y-3" x-show="!loading">
                <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-400">You asked</p>
                    <p class="mt-2 text-sm text-stone-700">{{ $question }}</p>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-400">{{ $provider->answerLabel() }}</p>
                    <p class="mt-0.5 text-xs text-stone-500">{{ $provider->model }}</p>
                    <div class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-stone-800">{{ $answer }}</div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
