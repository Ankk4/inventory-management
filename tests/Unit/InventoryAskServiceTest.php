<?php

namespace Tests\Unit;

use App\DTO\AskProvider;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use App\Services\Inventory\InventoryAskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class InventoryAskServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_provider_defaults_to_local_ollama(): void
    {
        config([
            'inventory.gemini.enabled' => false,
            'inventory.gemini.api_key' => null,
            'inventory.ollama.model' => 'qwen3-vl:8b-instruct',
        ]);

        $provider = (new InventoryAskService)->currentProvider();

        $this->assertInstanceOf(AskProvider::class, $provider);
        $this->assertTrue($provider->isLocal);
        $this->assertSame('Ollama', $provider->vendor);
        $this->assertSame('qwen3-vl:8b-instruct', $provider->model);
        $this->assertSame('Local', $provider->locationLabel());
        $this->assertSame('Stays on this machine', $provider->privacyNote());
    }

    public function test_current_provider_uses_gemini_when_enabled_with_key(): void
    {
        config([
            'inventory.gemini.enabled' => true,
            'inventory.gemini.api_key' => 'test-key',
            'inventory.gemini.model' => 'gemini-2.0-flash',
        ]);

        $provider = (new InventoryAskService)->currentProvider();

        $this->assertFalse($provider->isLocal);
        $this->assertSame('Gemini', $provider->vendor);
        $this->assertSame('gemini-2.0-flash', $provider->model);
        $this->assertSame('Cloud', $provider->locationLabel());
        $this->assertSame('Sent to Google', $provider->privacyNote());
    }

    public function test_serialize_inventory_includes_only_in_stock_items_from_current_inventory(): void
    {
        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);
        $cabin = Inventory::create(['user_id' => $user->id, 'name' => 'Cabin']);
        $category = Category::create(['name' => 'Dairy', 'slug' => 'dairy']);

        Item::create([
            'inventory_id' => $pantry->id,
            'category_id' => $category->id,
            'name' => 'Milk',
            'normalized_name' => 'milk',
            'quantity' => 2,
            'unit' => 'L',
        ]);

        Item::create([
            'inventory_id' => $pantry->id,
            'name' => 'Empty Box',
            'normalized_name' => 'empty box',
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

        $service = new InventoryAskService;
        $context = $service->serializeInventory($pantry);

        $this->assertStringContainsString('Dairy:', $context);
        $this->assertStringContainsString('Milk', $context);
        $this->assertStringNotContainsString('Empty Box', $context);
        $this->assertStringNotContainsString('Secret Sauce', $context);
        $this->assertSame(1, $service->inStockItemCount($pantry));
    }

    public function test_serialize_inventory_returns_empty_message_when_nothing_in_stock(): void
    {
        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        Item::create([
            'inventory_id' => $pantry->id,
            'name' => 'Gone',
            'normalized_name' => 'gone',
            'quantity' => 0,
            'unit' => 'pcs',
        ]);

        $service = new InventoryAskService;

        $this->assertSame('(This inventory is empty.)', $service->serializeInventory($pantry));
        $this->assertSame(0, $service->inStockItemCount($pantry));
    }

    public function test_ask_maps_ollama_failures_to_friendly_message(): void
    {
        config([
            'inventory.gemini.enabled' => false,
            'inventory.ollama.base_url' => 'http://127.0.0.1:11434',
            'inventory.ollama.model' => 'qwen3-vl:8b-instruct',
            'inventory.ollama.timeout' => 5,
            'inventory.ollama.num_ctx' => 8192,
        ]);

        Log::shouldReceive('error')->once();

        Http::fake([
            '*/api/chat' => Http::response(['error' => 'connection refused'], 500),
        ]);

        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Couldn't reach the local model. Is Ollama running?");

        (new InventoryAskService)->ask($pantry, 'What do I have?');
    }

    public function test_ask_maps_gemini_failures_to_friendly_message(): void
    {
        config([
            'inventory.gemini.enabled' => true,
            'inventory.gemini.api_key' => 'test-key',
            'inventory.gemini.model' => 'gemini-2.0-flash',
        ]);

        Log::shouldReceive('error')->once();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
        ]);

        $user = User::factory()->create();
        $pantry = Inventory::create(['user_id' => $user->id, 'name' => 'Pantry']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Couldn't reach Gemini.");

        (new InventoryAskService)->ask($pantry, 'What do I have?');
    }
}
