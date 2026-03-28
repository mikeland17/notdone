<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $existingUser = User::where('email', $input['email'] ?? '')->first();

        // Social-only user adding a password — let them use both login methods
        if ($existingUser && is_null($existingUser->password)) {
            Validator::make($input, [
                'name' => $this->nameRules(),
                'password' => $this->passwordRules(),
            ])->validate();

            $existingUser->update([
                'name' => $input['name'],
                'password' => $input['password'],
            ]);

            return $existingUser;
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
