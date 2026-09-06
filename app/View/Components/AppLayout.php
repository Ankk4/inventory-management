<?php

namespace App\View\Components;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public function __construct(
        public ?Inventory $inventory = null,
        public ?Collection $inventories = null,
    ) {
        $this->inventories = $inventories ?? collect();
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
