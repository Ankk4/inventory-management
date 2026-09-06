<?php

namespace App\Http\Controllers;

use App\DTO\ReceiptParseRequest;
use App\Http\Requests\ConfirmReceiptImportRequest;
use App\Http\Requests\ParseReceiptImageRequest;
use App\Http\Requests\StoreReceiptPasteRequest;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Services\Inventory\ItemMatcher;
use App\Services\Receipt\GeminiReceiptParser;
use App\Services\Receipt\ManualPasteReceiptParser;
use App\Services\Receipt\OllamaReceiptParser;
use App\Services\Receipt\ReceiptImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptImportController extends Controller
{
    public function create(Request $request, Inventory $inventory, GeminiReceiptParser $geminiParser): View
    {
        $this->authorize('view', $inventory);

        $request->session()->put('active_inventory_id', $inventory->id);

        return view('import.create', [
            'inventory' => $inventory,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
            'geminiPrompt' => config('inventory.receipt_prompt'),
            'geminiEnabled' => config('inventory.gemini.enabled') && config('inventory.gemini.api_key'),
            'geminiUsage' => $geminiParser->dailyUsage(),
            'geminiLimit' => $geminiParser->dailyLimit(),
            'ollamaModel' => config('inventory.ollama.model'),
        ]);
    }

    public function parsePaste(
        StoreReceiptPasteRequest $request,
        Inventory $inventory,
        ManualPasteReceiptParser $parser,
        ReceiptImportService $importService,
        ItemMatcher $matcher,
    ): View|RedirectResponse {
        $this->authorize('view', $inventory);

        try {
            $imagePath = $request->file('image')
                ? $importService->storeImage($request->file('image'))
                : null;

            $draft = $parser->parse(new ReceiptParseRequest(
                pastedText: $request->string('pasted_text')->toString(),
            ));

            return $this->reviewView($request, $inventory, $draft, $imagePath, $matcher);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['pasted_text' => $e->getMessage()]);
        }
    }

    public function parseOllama(
        ParseReceiptImageRequest $request,
        Inventory $inventory,
        OllamaReceiptParser $parser,
        ReceiptImportService $importService,
        ItemMatcher $matcher,
    ): View|RedirectResponse {
        $this->authorize('view', $inventory);

        try {
            $imagePath = $importService->storeImage($request->file('image'));

            $draft = $parser->parse(new ReceiptParseRequest(
                image: $request->file('image'),
            ));

            return $this->reviewView($request, $inventory, $draft, $imagePath, $matcher);
        } catch (\Throwable $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }
    }

    public function parseGemini(
        ParseReceiptImageRequest $request,
        Inventory $inventory,
        GeminiReceiptParser $parser,
        ReceiptImportService $importService,
        ItemMatcher $matcher,
    ): View|RedirectResponse {
        $this->authorize('view', $inventory);

        try {
            $imagePath = $importService->storeImage($request->file('image'));

            $draft = $parser->parse(new ReceiptParseRequest(
                image: $request->file('image'),
            ));

            return $this->reviewView($request, $inventory, $draft, $imagePath, $matcher);
        } catch (\Throwable $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }
    }

    public function confirm(
        ConfirmReceiptImportRequest $request,
        Inventory $inventory,
        ReceiptImportService $importService,
    ): RedirectResponse {
        $this->authorize('view', $inventory);

        $payload = $request->validated();
        $payload['raw_payload'] = $payload;

        try {
            $importService->confirm($request->user(), $inventory, $payload);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()
            ->route('inventories.show', $inventory)
            ->with('status', 'Imported into '.$inventory->name.'.');
    }

    private function reviewView(
        Request $request,
        Inventory $inventory,
        $draft,
        ?string $imagePath,
        ItemMatcher $matcher,
    ): View {
        $lines = collect($draft->lines)->map(function ($line, $index) use ($matcher, $inventory) {
            $suggestions = $matcher->suggest($line->rawName, $inventory);

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
            'inventory' => $inventory,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
            'draft' => $draft,
            'imagePath' => $imagePath,
            'lines' => $lines,
            'items' => Item::query()
                ->where('inventory_id', $inventory->id)
                ->orderBy('name')
                ->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
