<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KecamatanSeeder::class,
            ServiceTypeSeeder::class,
            UserSeeder::class,
            TenantSeeder::class,
        ]);
    }
}
