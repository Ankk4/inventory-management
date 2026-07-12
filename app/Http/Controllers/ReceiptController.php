<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    public function index(): View
    {
        $receipts = Receipt::query()
            ->withCount('lines')
            ->latest()
            ->paginate(15);

        return view('receipts.index', compact('receipts'));
    }

    public function show(Receipt $receipt): View
    {
        $receipt->load(['lines.matchedItem', 'stockMovements.item', 'user']);

        return view('receipts.show', compact('receipt'));
    }

    public function image(string $path): StreamedResponse|Response
    {
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }
}
