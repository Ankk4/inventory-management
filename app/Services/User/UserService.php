<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @throws ValidationException
     */
    public function create(string $name, string $email, string $password, bool $verified = true): User
    {
        $data = Validator::make(
            [
                'name' => trim($name),
                'email' => Str::lower(trim($email)),
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::defaults()],
            ],
        )->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if ($verified) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
