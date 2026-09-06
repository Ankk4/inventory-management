<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function view(User $user, Inventory $inventory): bool
    {
        return $inventory->user_id === $user->id;
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $inventory->user_id === $user->id;
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $inventory->user_id === $user->id;
    }
}
