<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

use Auth;

class PhoneIsUniqueForThisUser implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $userWithThisPhoneExists = User::where('user_unique','<>',Auth::user()->user_unique)
            ->where('mobile', $value)
            ->exists();
        

        if($userWithThisPhoneExists)
        {

            $fail('Phone already exists, please try again!');

        }
    }
}
