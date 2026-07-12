<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustItemStockRequest;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $items = Item::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('normalized_name', 'like', '%'.Item::normalizeName($search).'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', compact('items', 'search'));
    }

    public function create(): View
    {
        return view('items.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        Item::create([
            'name' => $request->string('name')->toString(),
            'normalized_name' => Item::normalizeName($request->string('name')->toString()),
            'category_id' => $request->integer('category_id') ?: null,
            'quantity' => $request->input('quantity', 0),
            'unit' => $request->string('unit')->toString() ?: 'pcs',
            'barcode' => $request->string('barcode')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()->route('items.index')->with('status', 'Item created.');
    }

    public function show(Item $item): View
    {
        $item->load(['category', 'stockMovements' => fn ($q) => $q->latest()->limit(20)]);

        return view('items.show', compact('item'));
    }

    public function edit(Item $item): View
    {
        return view('items.edit', [
            'item' => $item,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $name = $request->string('name')->toString();

        $item->update([
            'name' => $name,
            'normalized_name' => Item::normalizeName($name),
            'category_id' => $request->integer('category_id') ?: null,
            'unit' => $request->string('unit')->toString() ?: $item->unit,
            'barcode' => $request->string('barcode')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()->route('items.show', $item)->with('status', 'Item updated.');
    }

    public function adjust(AdjustItemStockRequest $request, Item $item): RedirectResponse
    {
        $delta = (float) $request->input('delta');

        $item->increment('quantity', $delta);

        StockMovement::create([
            'item_id' => $item->id,
            'user_id' => $request->user()->id,
            'delta' => $delta,
            'reason' => $request->string('reason')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()->route('items.show', $item)->with('status', 'Stock adjusted.');
    }
}
