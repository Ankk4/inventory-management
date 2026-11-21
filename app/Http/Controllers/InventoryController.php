<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustItemStockRequest;
use App\Http\Requests\AskInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryAskService;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InventoryController extends Controller
{
    public function show(Request $request, Inventory $inventory): View
    {
        $this->authorize('view', $inventory);

        $request->session()->put('active_inventory_id', $inventory->id);

        $search = $request->string('search')->trim()->toString();
        $existingOnly = $request->boolean('existing', true);

        $itemsQuery = $inventory->items()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('normalized_name', 'like', '%'.Item::normalizeName($search).'%');
                });
            });

        $matchingItemCount = (clone $itemsQuery)->count();

        $items = $itemsQuery
            ->when($existingOnly, fn ($query) => $query->existing())
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('inventories.show', [
            'inventory' => $inventory,
            'items' => $items,
            'search' => $search,
            'existingOnly' => $existingOnly,
            'matchingItemCount' => $matchingItemCount,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInventoryRequest $request, InventoryService $inventories): RedirectResponse
    {
        $inventory = $inventories->create(
            $request->user(),
            $request->string('name')->toString(),
        );

        $request->session()->put('active_inventory_id', $inventory->id);

        return redirect()
            ->route('inventories.show', $inventory)
            ->with('status', 'Inventory created.');
    }

    public function ask(Request $request, Inventory $inventory, InventoryAskService $askService): View
    {
        $this->authorize('view', $inventory);

        $request->session()->put('active_inventory_id', $inventory->id);

        return view('inventories.ask', $this->askViewData($request, $inventory, $askService));
    }

    public function askStore(
        AskInventoryRequest $request,
        Inventory $inventory,
        InventoryAskService $askService,
    ): View|RedirectResponse {
        $question = $request->string('question')->toString();
        $inventoryContext = $askService->serializeInventory($inventory);
        $itemCount = $askService->inStockItemCount($inventory);

        try {
            $answer = $askService->ask($inventory, $question, $inventoryContext);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['question' => $e->getMessage()]);
        }

        return view('inventories.ask', $this->askViewData(
            $request,
            $inventory,
            $askService,
            $question,
            $answer,
            $inventoryContext,
            $itemCount,
        ));
    }

    private function askViewData(
        Request $request,
        Inventory $inventory,
        InventoryAskService $askService,
        ?string $question = null,
        ?string $answer = null,
        ?string $inventoryContext = null,
        ?int $itemCount = null,
    ): array {
        return [
            'inventory' => $inventory,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
            'provider' => $askService->currentProvider(),
            'inventoryContext' => $inventoryContext ?? $askService->serializeInventory($inventory),
            'itemCount' => $itemCount ?? $askService->inStockItemCount($inventory),
            'question' => $question,
            'answer' => $answer,
        ];
    }

    public function createItem(Request $request, Inventory $inventory): View
    {
        $this->authorize('view', $inventory);

        return view('items.create', [
            'inventory' => $inventory,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function storeItem(StoreItemRequest $request, Inventory $inventory): RedirectResponse
    {
        Item::create([
            'inventory_id' => $inventory->id,
            'name' => $request->string('name')->toString(),
            'normalized_name' => Item::normalizeName($request->string('name')->toString()),
            'category_id' => $request->integer('category_id') ?: null,
            'quantity' => $request->input('quantity', 0),
            'unit' => $request->string('unit')->toString() ?: 'pcs',
            'barcode' => $request->string('barcode')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('inventories.show', $inventory)
            ->with('status', 'Item created.');
    }

    public function showItem(Request $request, Inventory $inventory, Item $item): View
    {
        $this->authorize('view', $inventory);
        $this->ensureItemBelongsToInventory($inventory, $item);

        $item->load(['category', 'stockMovements' => fn ($q) => $q->latest()->limit(20)]);

        return view('items.show', [
            'inventory' => $inventory,
            'item' => $item,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
        ]);
    }

    public function editItem(Request $request, Inventory $inventory, Item $item): View
    {
        $this->authorize('view', $inventory);
        $this->ensureItemBelongsToInventory($inventory, $item);

        return view('items.edit', [
            'inventory' => $inventory,
            'item' => $item,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function updateItem(
        UpdateItemRequest $request,
        Inventory $inventory,
        Item $item,
    ): RedirectResponse {
        $this->ensureItemBelongsToInventory($inventory, $item);

        $name = $request->string('name')->toString();

        $item->update([
            'name' => $name,
            'normalized_name' => Item::normalizeName($name),
            'category_id' => $request->integer('category_id') ?: null,
            'unit' => $request->string('unit')->toString() ?: $item->unit,
            'barcode' => $request->string('barcode')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('inventories.items.show', [$inventory, $item])
            ->with('status', 'Item updated.');
    }

    public function adjustItem(
        AdjustItemStockRequest $request,
        Inventory $inventory,
        Item $item,
    ): RedirectResponse {
        $this->ensureItemBelongsToInventory($inventory, $item);

        $delta = (float) $request->input('delta');

        $item->increment('quantity', $delta);

        StockMovement::create([
            'item_id' => $item->id,
            'user_id' => $request->user()->id,
            'delta' => $delta,
            'reason' => $request->string('reason')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('inventories.items.show', [$inventory, $item])
            ->with('status', 'Stock adjusted.');
    }

    private function ensureItemBelongsToInventory(Inventory $inventory, Item $item): void
    {
        if ($item->inventory_id !== $inventory->id) {
            abort(404);
        }
    }
}
