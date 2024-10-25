<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PositiveNumbersInCsvRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate the CSV format with regex
        $pattern = '/^\d+(?:,\d+)*$/';

        if (!preg_match($pattern, $value)) {

            $fail('The :attribute field is invalid, please try again!');

        }

        // Convert the CSV string to an array of values
        $values = str_getcsv($value);

        // Check if all values are positive numbers
        foreach ($values as $num) {
            if ($num <= 0) {

                $fail('The :attribute field must contain positive numbers only, please try again!');

            }
        }

    }
}
