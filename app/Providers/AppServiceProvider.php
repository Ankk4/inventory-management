<?php

namespace App\Providers;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('inventory', function (string $value) {
            $user = auth()->user();

            abort_unless($user instanceof User, 404);

            return Inventory::query()
                ->where('user_id', $user->id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('item', function (string $value) {
            $user = auth()->user();

            abort_unless($user instanceof User, 404);

            return Item::query()
                ->whereKey($value)
                ->whereHas('inventory', fn ($query) => $query->where('user_id', $user->id))
                ->firstOrFail();
        });
    }
}
