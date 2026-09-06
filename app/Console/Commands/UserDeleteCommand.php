<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UserDeleteCommand extends Command
{
    protected $signature = 'user:delete
                            {email? : Login email}
                            {--force : Delete without confirmation}';

    protected $description = 'Delete a user account and their inventories';

    public function handle(UserService $users): int
    {
        $email = $this->argument('email') ?? $this->ask('Email');

        if (! is_string($email) || $email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$user->name} ({$user->email}) and all of their inventories?")) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        $users->delete($user);

        $this->info("Deleted user {$email}.");

        return self::SUCCESS;
    }
}
