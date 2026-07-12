<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_paste_import_shows_review_page(): void
    {
        $user = User::factory()->create();

        $payload = json_encode([
            'store_name' => 'Test Mart',
            'purchase_date' => '2026-06-05',
            'currency' => 'EUR',
            'total' => 10.50,
            'lines' => [
                [
                    'raw_name' => 'Milk 1L',
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 1.25,
                    'line_total' => 2.50,
                ],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('import.paste'), [
            'pasted_text' => $payload,
        ]);

        $response->assertOk();
        $response->assertSee('Review Receipt Import');
        $response->assertSee('Milk 1L');
        $response->assertSee('Test Mart');
    }

    public function test_confirm_import_updates_inventory_and_creates_receipt(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Dairy', 'slug' => 'dairy']);

        $existing = Item::create([
            'name' => 'Milk 1L',
            'normalized_name' => 'milk 1l',
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->post(route('import.confirm'), [
            'store_name' => 'Test Mart',
            'purchase_date' => '2026-06-05',
            'currency' => 'EUR',
            'total' => 5.00,
            'parser_source' => 'manual_paste',
            'lines' => [
                [
                    'raw_name' => 'Milk 1L',
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 1.25,
                    'line_total' => 2.50,
                    'skipped' => false,
                    'matched_item_id' => $existing->id,
                    'create_item' => false,
                ],
                [
                    'raw_name' => 'New Bread',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'skipped' => false,
                    'create_item' => true,
                    'new_item_name' => 'New Bread',
                    'category_id' => $category->id,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('receipts', 1);

        $existing->refresh();
        $this->assertEquals(3, (float) $existing->quantity);

        $bread = Item::where('name', 'New Bread')->first();
        $this->assertNotNull($bread);
        $this->assertEquals(1, (float) $bread->quantity);

        $receipt = Receipt::first();
        $this->assertEquals('manual_paste', $receipt->parser_source);
        $this->assertEquals(2, $receipt->lines()->count());
        $this->assertEquals(2, $receipt->stockMovements()->count());
    }

    public function test_inventory_pages_require_authentication(): void
    {
        $this->get(route('items.index'))->assertRedirect(route('login'));
        $this->get(route('import.create'))->assertRedirect(route('login'));
        $this->get(route('receipts.index'))->assertRedirect(route('login'));
    }
}
