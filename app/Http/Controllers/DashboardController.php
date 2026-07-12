<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Receipt;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $recentReceipts = Receipt::query()
            ->withCount('lines')
            ->latest()
            ->limit(5)
            ->get();

        $lowStockItems = Item::query()
            ->with('category')
            ->where('quantity', '<=', 2)
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        $itemCount = Item::count();
        $totalQuantity = Item::sum('quantity');

        return view('dashboard', compact(
            'recentReceipts',
            'lowStockItems',
            'itemCount',
            'totalQuantity',
        ));
    }
}
