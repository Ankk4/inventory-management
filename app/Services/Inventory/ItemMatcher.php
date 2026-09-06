<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\Item;
use Illuminate\Support\Collection;

class ItemMatcher
{
    public function suggest(string $rawName, Inventory $inventory, int $limit = 5): Collection
    {
        $normalized = Item::normalizeName($rawName);

        if ($normalized === '') {
            return collect();
        }

        $exact = Item::query()
            ->where('inventory_id', $inventory->id)
            ->where('normalized_name', $normalized)
            ->limit($limit)
            ->get();

        if ($exact->isNotEmpty()) {
            return $exact;
        }

        return Item::query()
            ->where('inventory_id', $inventory->id)
            ->where(function ($query) use ($normalized, $rawName) {
                $query->where('normalized_name', 'like', '%'.$normalized.'%')
                    ->orWhere('name', 'like', '%'.$rawName.'%');
            })
            ->limit($limit)
            ->get();
    }

    public function bestMatch(string $rawName, Inventory $inventory): ?Item
    {
        return $this->suggest($rawName, $inventory, 1)->first();
    }
}
