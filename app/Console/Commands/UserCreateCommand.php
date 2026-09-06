<?php

namespace App\Console\Commands;

use App\Services\User\UserService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class UserCreateCommand extends Command
{
    protected $signature = 'user:create
                            {name? : Display name}
                            {email? : Login email}
                            {--password= : Password (prompted if omitted)}';

    protected $description = 'Create a user account';

    public function handle(UserService $users): int
    {
        $name = $this->argument('name') ?? $this->ask('Name');
        $email = $this->argument('email') ?? $this->ask('Email');
        $password = $this->resolvePassword();

        if (! is_string($name) || $name === '') {
            $this->error('Name is required.');

            return self::FAILURE;
        }

        if (! is_string($email) || $email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        if ($password === null) {
            return self::FAILURE;
        }

        try {
            $user = $users->create($name, $email, $password);
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $this->info("Created user {$user->email} (id {$user->id}).");

        return self::SUCCESS;
    }

    private function resolvePassword(): ?string
    {
        $option = $this->option('password');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        $password = $this->secret('Password');

        if (! is_string($password) || $password === '') {
            $this->error('Password is required.');

            return null;
        }

        if ($password !== $this->secret('Confirm password')) {
            $this->error('Passwords do not match.');

            return null;
        }

        return $password;
    }
}
