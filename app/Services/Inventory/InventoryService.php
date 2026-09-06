<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\User;

class InventoryService
{
    public const DEFAULT_NAME = 'Pantry';

    public function createDefault(User $user): Inventory
    {
        return Inventory::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => self::DEFAULT_NAME,
            ],
        );
    }

    public function create(User $user, string $name): Inventory
    {
        return Inventory::create([
            'user_id' => $user->id,
            'name' => trim($name),
        ]);
    }

    public function firstFor(User $user): ?Inventory
    {
        return $user->inventories()->orderBy('name')->first();
    }

    public function resolveActive(User $user, ?int $preferredId = null): ?Inventory
    {
        if ($preferredId !== null) {
            $preferred = $user->inventories()->whereKey($preferredId)->first();

            if ($preferred !== null) {
                return $preferred;
            }
        }

        return $this->firstFor($user);
    }
}
