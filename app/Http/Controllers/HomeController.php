<?php

namespace App\Http\Controllers;

use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, InventoryService $inventories): RedirectResponse
    {
        $user = $request->user();
        $preferredId = $request->session()->get('active_inventory_id');

        $inventory = $inventories->resolveActive($user, $preferredId);

        if ($inventory === null) {
            $inventory = $inventories->createDefault($user);
        }

        $request->session()->put('active_inventory_id', $inventory->id);

        return redirect()->route('inventories.show', $inventory);
    }
}
