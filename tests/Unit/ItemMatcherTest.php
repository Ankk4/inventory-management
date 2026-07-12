<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Item;
use App\Services\Inventory\ItemMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_exact_normalized_match(): void
    {
        $category = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);

        Item::create([
            'name' => 'Organic Milk 1L',
            'normalized_name' => Item::normalizeName('Organic Milk 1L'),
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        $match = (new ItemMatcher)->bestMatch('organic milk 1l');

        $this->assertNotNull($match);
        $this->assertSame('Organic Milk 1L', $match->name);
    }
}
