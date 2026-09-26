<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::query()->count() > 0) {
            return;
        }

        Customer::factory()
            ->count(2000)
            ->make()
            ->chunk(500)
            ->each(fn ($chunk) => DB::table('customers')->insert(
                $chunk->map(fn (Customer $c) => $c->getAttributes() + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            ));
    }
}
