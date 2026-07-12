<?php

namespace App\Http\Controllers;

use App\DTO\ReceiptParseRequest;
use App\Http\Requests\ConfirmReceiptImportRequest;
use App\Http\Requests\ParseReceiptImageRequest;
use App\Http\Requests\StoreReceiptPasteRequest;
use App\Models\Category;
use App\Models\Item;
use App\Services\Inventory\ItemMatcher;
use App\Services\Receipt\GeminiReceiptParser;
use App\Services\Receipt\ManualPasteReceiptParser;
use App\Services\Receipt\OllamaReceiptParser;
use App\Services\Receipt\ReceiptImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReceiptImportController extends Controller
{
    public function create(
        GeminiReceiptParser $geminiParser,
    ): View {
        return view('import.create', [
            'geminiPrompt' => config('inventory.receipt_prompt'),
            'geminiEnabled' => config('inventory.gemini.enabled') && config('inventory.gemini.api_key'),
            'geminiUsage' => $geminiParser->dailyUsage(),
            'geminiLimit' => $geminiParser->dailyLimit(),
            'ollamaModel' => config('inventory.ollama.model'),
        ]);
    }

    public function parsePaste(
        StoreReceiptPasteRequest $request,
        ManualPasteReceiptParser $parser,
        ReceiptImportService $importService,
        ItemMatcher $matcher,
    ): View|RedirectResponse {
        try {
            $imagePath = $request->file('image')
                ? $importService->storeImage($request->file('image'))
                : null;

            $draft = $parser->parse(new ReceiptParseRequest(
                pastedText: $request->string('pasted_text')->toString(),
            ));

            return $this->reviewView($draft, $imagePath, $matcher);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['pasted_text' => $e->getMessage()]);
        }
    }

    public function parseOllama(
        ParseReceiptImageRequest $request,
        OllamaReceiptParser $parser,
        ReceiptImportService $importService,
        ItemMatcher $matcher,
    ): View|RedirectResponse {
        try {
            $imagePath = $importService->storeImage($request->file('image'));

            $draft = $parser->parse(new ReceiptParseRequest(
                image: $request->file('image'),
            ));

            return $this->reviewView($draft, $imagePath, $matcher);
        } catch (\Throwable $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }
    }

    public function parseGemini(
        ParseReceiptImageRequest $request,
        GeminiReceiptParser $parser,
        ReceiptImportService $importService,
        ItemMatcher $matcher,
    ): View|RedirectResponse {
        try {
            $imagePath = $importService->storeImage($request->file('image'));

            $draft = $parser->parse(new ReceiptParseRequest(
                image: $request->file('image'),
            ));

            return $this->reviewView($draft, $imagePath, $matcher);
        } catch (\Throwable $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }
    }

    public function confirm(
        ConfirmReceiptImportRequest $request,
        ReceiptImportService $importService,
    ): RedirectResponse {
        $payload = $request->validated();
        $payload['raw_payload'] = $payload;

        $receipt = $importService->confirm($request->user(), $payload);

        return redirect()
            ->route('receipts.show', $receipt)
            ->with('status', 'Receipt imported and inventory updated.');
    }

    private function reviewView($draft, ?string $imagePath, ItemMatcher $matcher): View
    {
        $lines = collect($draft->lines)->map(function ($line, $index) use ($matcher) {
            $suggestions = $matcher->suggest($line->rawName);

            return [
                'index' => $index,
                'raw_name' => $line->rawName,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'unit_price' => $line->unitPrice,
                'line_total' => $line->lineTotal,
                'suggestions' => $suggestions,
                'best_match_id' => $suggestions->first()?->id,
            ];
        });

        return view('import.review', [
            'draft' => $draft,
            'imagePath' => $imagePath,
            'lines' => $lines,
            'items' => Item::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
