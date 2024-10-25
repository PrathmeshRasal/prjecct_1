<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

use Auth;

class EmailIsUniqueForThisUser implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userWithThisEmailExists = User::where('user_unique','<>',Auth::user()->user_unique)
        ->where('email', $value)
        ->exists();
    

        if($userWithThisEmailExists)
        {

            $fail('Email already exists, please try again!');

        }
    }
}
