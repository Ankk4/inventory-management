<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    public function index(Request $request, InventoryService $inventories): View
    {
        $inventory = $inventories->resolveActive(
            $request->user(),
            $request->session()->get('active_inventory_id'),
        );

        $receipts = Receipt::query()
            ->where('user_id', $request->user()->id)
            ->withCount('lines')
            ->latest()
            ->paginate(15);

        return view('receipts.index', [
            'receipts' => $receipts,
            'inventory' => $inventory,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Receipt $receipt, InventoryService $inventories): View
    {
        abort_unless($receipt->user_id === $request->user()->id, 404);

        $receipt->load(['lines.matchedItem', 'stockMovements.item', 'user', 'inventory']);

        $inventory = $receipt->inventory
            ?? $inventories->resolveActive(
                $request->user(),
                $request->session()->get('active_inventory_id'),
            );

        return view('receipts.show', [
            'receipt' => $receipt,
            'inventory' => $inventory,
            'inventories' => $request->user()->inventories()->orderBy('name')->get(),
        ]);
    }

    public function image(string $path): StreamedResponse|Response
    {
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }
}
