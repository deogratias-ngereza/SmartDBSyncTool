<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Entity;
use App\Models\User;
use App\Utilities\HelperUtil;
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
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $entityId = HelperUtil::generateRandomAccNo(20);
        //create new entity
        $entity = Entity::create([
            'id' => $entityId,
            'name' => $input['name'],
            'full_name' => $input['name'],
            'first_name' => $input['name'],
            'email1' => $input['email'],
        ]);

        return User::create([
            'entity_id' => $entityId,
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
        
    }
}
