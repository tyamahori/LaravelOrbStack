<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Intentionally empty: Seeder::__invoke() requires run() to exist,
        // and this is the default target of `artisan db:seed`.
    }
}
