<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserListCommand extends Command
{
    protected $signature = 'user:list';

    protected $description = 'List user accounts';

    public function handle(): int
    {
        $users = User::query()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'created_at']);

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $this->line(sprintf(
                '[%d] %s  %s  created %s',
                $user->id,
                $user->name,
                $user->email,
                $user->created_at?->toDateTimeString() ?? '',
            ));
        }

        return self::SUCCESS;
    }
}
