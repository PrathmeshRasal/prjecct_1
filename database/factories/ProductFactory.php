<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $word = $this->faker->unique()->word;
        $number = $this->faker->unique()->randomNumber(3); // Change the argument to specify the number of digits

        return [
            'product_name' => $word,
            'product_unique' => $number,
            'is_active' => "1",
            // Add other fields as needed
        ];
    }
}

