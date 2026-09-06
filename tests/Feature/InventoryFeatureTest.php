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

    public function test_stock_page_hides_zero_quantity_items_by_default(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Milk',
            'normalized_name' => 'milk',
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Flour',
            'normalized_name' => 'flour',
            'quantity' => 0,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->get(route('inventories.show', $inventory));

        $response->assertOk();
        $response->assertSee('Milk');
        $response->assertDontSee('Flour');
        $response->assertSee('Existing items only');
        $response->assertSee('1 item in stock');
    }

    public function test_stock_page_shows_zero_quantity_items_when_existing_filter_is_off(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Milk',
            'normalized_name' => 'milk',
            'quantity' => 1,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Flour',
            'normalized_name' => 'flour',
            'quantity' => 0,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->get(route('inventories.show', [
            'inventory' => $inventory,
            'existing' => 0,
        ]));

        $response->assertOk();
        $response->assertSee('Milk');
        $response->assertSee('Flour');
        $response->assertSee('2 items');
        $response->assertDontSee('in stock');
    }

    public function test_stock_page_explains_empty_state_when_all_items_are_out_of_stock(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        Item::create([
            'inventory_id' => $inventory->id,
            'name' => 'Flour',
            'normalized_name' => 'flour',
            'quantity' => 0,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->get(route('inventories.show', $inventory));

        $response->assertOk();
        $response->assertDontSee('Flour');
        $response->assertSee('Nothing in stock');
        $response->assertDontSee('Scan a photo');
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

    public function test_scan_photo_input_does_not_force_the_camera(): void
    {
        $user = User::factory()->create();
        $inventory = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        $response = $this->actingAs($user)->get(route('inventories.scan', $inventory));

        $response->assertOk();
        $response->assertSee('Take or choose a photo');
        $this->assertStringNotContainsString('capture=', $response->getContent());
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

    public function test_ask_page_shows_provider_and_stock_context(): void
    {
        config([
            'inventory.gemini.enabled' => false,
            'inventory.ollama.model' => 'qwen3-vl:8b-instruct',
        ]);

        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        Item::create([
            'inventory_id' => $pantry->id,
            'name' => 'Eggs',
            'normalized_name' => 'eggs',
            'quantity' => 6,
            'unit' => 'pcs',
        ]);

        Item::create([
            'inventory_id' => $pantry->id,
            'name' => 'Empty Jar',
            'normalized_name' => 'empty jar',
            'quantity' => 0,
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($user)->get(route('inventories.ask', $pantry));

        $response->assertOk();
        $response->assertSee('Local');
        $response->assertSee('Ollama');
        $response->assertSee('qwen3-vl:8b-instruct');
        $response->assertSee('Stays on this machine');
        $response->assertSee('1 item in Pantry');
        $response->assertSee('Eggs');
        $response->assertDontSee('Empty Jar');
    }

    public function test_ask_page_warns_when_inventory_is_empty(): void
    {
        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        $response = $this->actingAs($user)->get(route('inventories.ask', $pantry));

        $response->assertOk();
        $response->assertSee('This inventory is empty');
        $response->assertSee('nothing in stock');
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
            'inventory_id' => $pantry->id,
            'name' => 'Empty Jar',
            'normalized_name' => 'empty jar',
            'quantity' => 0,
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
        $response->assertSee('Answer from Ollama');
        $response->assertSee('qwen3-vl:8b-instruct');

        Http::assertSent(function ($request) {
            $content = $request['messages'][0]['content'] ?? '';

            return str_contains($content, 'Eggs')
                && str_contains($content, 'What do I have?')
                && ! str_contains($content, 'Secret Sauce')
                && ! str_contains($content, 'Empty Jar');
        });
    }

    public function test_ask_shows_friendly_error_without_raw_provider_body(): void
    {
        config([
            'inventory.gemini.enabled' => false,
            'inventory.ollama.base_url' => 'http://127.0.0.1:11434',
            'inventory.ollama.model' => 'qwen3-vl:8b-instruct',
            'inventory.ollama.timeout' => 5,
            'inventory.ollama.num_ctx' => 8192,
        ]);

        Http::fake([
            '*/api/chat' => Http::response(['error' => 'secret upstream detail'], 500),
        ]);

        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        $response = $this->actingAs($user)
            ->from(route('inventories.ask', $pantry))
            ->post(route('inventories.ask.store', $pantry), [
                'question' => 'What do I have?',
            ]);

        $response->assertRedirect(route('inventories.ask', $pantry));
        $response->assertSessionHasErrors('question');
        $this->assertSame(
            "Couldn't reach the local model. Is Ollama running?",
            session('errors')->first('question'),
        );
        $this->assertStringNotContainsString('secret upstream detail', session('errors')->first('question'));
    }

    public function test_inventory_pages_require_authentication(): void
    {
        $this->get(route('items.index'))->assertRedirect(route('login'));
        $this->get(route('import.create'))->assertRedirect(route('login'));
        $this->get(route('receipts.index'))->assertRedirect(route('login'));
    }
}
