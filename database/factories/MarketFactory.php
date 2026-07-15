<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Market>
 */
class MarketFactory extends Factory
{
    protected $model = Market::class;

    public function definition(): array
    {
        $province = fake()->randomElement(['BUJUMBURA', 'GITEGA', 'BUHUMUZA', 'BURUNGA', 'BUTANYERERA']);

        return [
            'name' => fake()->company().' Market',
            'city' => $province,
            'province' => $province,
            'commune' => fake()->word(),
            'zone' => fake()->word(),
            'colline' => fake()->word(),
            'location' => fake()->address(),
            'description' => fake()->paragraph(),
            'total_places' => fake()->numberBetween(20, 200),
            'occupied_places' => 0,
            'is_active' => true,
        ];
    }
}
