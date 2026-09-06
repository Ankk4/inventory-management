<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_create_command_creates_an_account(): void
    {
        $this->artisan('user:create', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            '--password' => 'password',
        ])->expectsOutputToContain('Created user ada@example.com')
            ->assertSuccessful();

        $user = User::query()->where('email', 'ada@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(password_verify('password', $user->password));
        $this->assertDatabaseHas('inventories', [
            'user_id' => $user->id,
            'name' => InventoryService::DEFAULT_NAME,
        ]);
    }

    public function test_user_create_command_prompts_when_arguments_are_omitted(): void
    {
        $this->artisan('user:create')
            ->expectsQuestion('Name', 'Ada Lovelace')
            ->expectsQuestion('Email', 'ada@example.com')
            ->expectsQuestion('Password', 'password')
            ->expectsQuestion('Confirm password', 'password')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
    }

    public function test_created_user_can_log_in(): void
    {
        $this->artisan('user:create', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            '--password' => 'password',
        ])->assertSuccessful();

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_user_create_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('user:create', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            '--password' => 'password',
        ])->assertFailed();
    }

    public function test_user_list_command_shows_users(): void
    {
        User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $this->artisan('user:list')
            ->expectsOutputToContain('Ada Lovelace  ada@example.com')
            ->assertSuccessful();
    }

    public function test_user_delete_command_removes_the_account(): void
    {
        User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $this->artisan('user:delete', [
            'email' => 'ada@example.com',
            '--force' => true,
        ])->expectsOutputToContain('Deleted user ada@example.com')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
    }

    public function test_user_delete_command_can_be_cancelled(): void
    {
        User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $this->artisan('user:delete', ['email' => 'ada@example.com'])
            ->expectsConfirmation('Delete Ada Lovelace (ada@example.com) and all of their inventories?', 'no')
            ->expectsOutputToContain('Cancelled.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_user_delete_command_fails_when_email_is_unknown(): void
    {
        $this->artisan('user:delete', [
            'email' => 'missing@example.com',
            '--force' => true,
        ])->assertFailed();
    }
}
