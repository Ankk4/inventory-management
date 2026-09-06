<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use App\Services\Inventory\ItemMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_exact_normalized_match_within_inventory(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);
        $other = Inventory::create(['user_id' => $user->id, 'name' => 'Cabin']);
        $category = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);

        Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Organic Milk 1L',
            'normalized_name' => Item::normalizeName('Organic Milk 1L'),
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $other->id,
            'name' => 'Organic Milk 1L',
            'normalized_name' => Item::normalizeName('Organic Milk 1L'),
            'quantity' => 99,
            'unit' => 'pcs',
        ]);

        $match = (new ItemMatcher)->bestMatch('organic milk 1l', $inventory);

        $this->assertNotNull($match);
        $this->assertSame('Organic Milk 1L', $match->name);
        $this->assertSame($inventory->id, $match->inventory_id);
    }
}
