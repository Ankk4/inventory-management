<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_default_pantry(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('inventories', [
            'user_id' => $user->id,
            'name' => InventoryService::DEFAULT_NAME,
        ]);
    }

    public function test_home_redirects_to_active_inventory(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create([
            'user_id' => $user->id,
            'name' => 'Pantry',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('inventories.show', $inventory));
    }

    public function test_item_list_is_scoped_to_inventory(): void
    {
        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);
        $cabin = Inventory::create(['user_id' => $user->id, 'name' => 'Cabin']);

        Item::create([
            'inventory_id' => $pantry->id,
            'name' => 'Milk',
            'normalized_name' => 'milk',
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $cabin->id,
            'name' => 'Firewood',
            'normalized_name' => 'firewood',
            'quantity' => 5,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->get(route('inventories.show', $pantry));

        $response->assertOk();
        $response->assertSee('Milk');
        $response->assertDontSee('Firewood');
    }

    public function test_user_cannot_view_another_users_inventory(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $owner->id, 'name' => 'Pantry']);

        $this->actingAs($other)
            ->get(route('inventories.show', $inventory))
            ->assertNotFound();
    }

    public function test_paste_import_shows_review_page_for_inventory(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

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

        $response = $this->actingAs($user)->post(route('inventories.scan.paste', $inventory), [
            'pasted_text' => $payload,
        ]);

        $response->assertOk();
        $response->assertSee('Review import');
        $response->assertSee('Milk 1L');
        $response->assertSee('Test Mart');
    }

    public function test_confirm_import_updates_only_selected_inventory(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Dairy', 'slug' => 'dairy']);
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);
        $other = Inventory::create(['user_id' => $user->id, 'name' => 'Cabin']);

        $existing = Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Milk 1L',
            'normalized_name' => 'milk 1l',
            'category_id' => $category->id,
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $other->id,
            'name' => 'Milk 1L',
            'normalized_name' => 'milk 1l',
            'quantity' => 9,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->post(route('inventories.scan.confirm', $inventory), [
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

        $response->assertRedirect(route('inventories.show', $inventory));

        $existing->refresh();
        $this->assertEquals(3, (float) $existing->quantity);

        $bread = Item::where('inventory_id', $inventory->id)->where('name', 'New Bread')->first();
        $this->assertNotNull($bread);
        $this->assertEquals(1, (float) $bread->quantity);

        $this->assertEquals(9, (float) Item::where('inventory_id', $other->id)->where('name', 'Milk 1L')->value('quantity'));

        $receipt = Receipt::first();
        $this->assertEquals($inventory->id, $receipt->inventory_id);
        $this->assertEquals(2, $receipt->lines()->count());
    }

    public function test_cannot_confirm_match_from_another_inventory(): void
    {
        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);
        $cabin = Inventory::create(['user_id' => $user->id, 'name' => 'Cabin']);

        $foreignItem = Item::create([
            'inventory_id' => $cabin->id,
            'name' => 'Milk 1L',
            'normalized_name' => 'milk 1l',
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->from(route('inventories.scan', $pantry))
            ->post(route('inventories.scan.confirm', $pantry), [
                'store_name' => 'Test Mart',
                'parser_source' => 'manual_paste',
                'lines' => [
                    [
                        'raw_name' => 'Milk 1L',
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'skipped' => false,
                        'matched_item_id' => $foreignItem->id,
                        'create_item' => false,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('receipts', 0);
        $this->assertEquals(1, (float) $foreignItem->fresh()->quantity);
    }

    public function test_ask_appends_inventory_context_to_prompt(): void
    {
        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);
        $cabin = Inventory::create(['user_id' => $user->id, 'name' => 'Cabin']);

        Item::create([
            'inventory_id' => $pantry->id,
            'name' => 'Eggs',
            'normalized_name' => 'eggs',
            'quantity' => 6,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $cabin->id,
            'name' => 'Secret Sauce',
            'normalized_name' => 'secret sauce',
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => [
                    'content' => 'You have eggs.',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('inventories.ask.store', $pantry), [
            'question' => 'What do I have?',
        ]);

        $response->assertOk();
        $response->assertSee('What do I have?');
        $response->assertSee('You have eggs.');

        Http::assertSent(function ($request) {
            $content = $request['messages'][0]['content'] ?? '';

            return str_contains($content, 'Eggs')
                && str_contains($content, 'What do I have?')
                && ! str_contains($content, 'Secret Sauce');
        });
    }

    public function test_inventory_pages_require_authentication(): void
    {
        $this->get(route('items.index'))->assertRedirect(route('login'));
        $this->get(route('import.create'))->assertRedirect(route('login'));
        $this->get(route('receipts.index'))->assertRedirect(route('login'));
    }
}
