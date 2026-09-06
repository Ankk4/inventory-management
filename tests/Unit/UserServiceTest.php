<?php

namespace Tests\Unit;

use App\Models\Inventory;
use App\Services\Inventory\InventoryService;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_stores_user_and_default_inventory(): void
    {
        $user = $this->users()->create('Ada Lovelace', 'ada@example.com', 'password');

        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertSame('ada@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(password_verify('password', $user->password));
        $this->assertDatabaseHas('inventories', [
            'user_id' => $user->id,
            'name' => InventoryService::DEFAULT_NAME,
        ]);
    }

    public function test_create_normalizes_email_case(): void
    {
        $user = $this->users()->create('Ada', 'Ada@Example.COM', 'password');

        $this->assertSame('ada@example.com', $user->email);
    }

    public function test_create_rejects_duplicate_email(): void
    {
        $this->users()->create('Ada', 'ada@example.com', 'password');

        $this->expectException(ValidationException::class);

        $this->users()->create('Ada Two', 'ADA@example.com', 'password');
    }

    public function test_delete_removes_user_and_inventories(): void
    {
        $user = $this->users()->create('Ada', 'ada@example.com', 'password');
        $inventoryId = Inventory::query()->where('user_id', $user->id)->value('id');

        $this->users()->delete($user);

        $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
        $this->assertDatabaseMissing('inventories', ['id' => $inventoryId]);
    }

    private function users(): UserService
    {
        return $this->app->make(UserService::class);
    }
}
