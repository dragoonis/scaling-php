<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::query()->count() > 0) {
            return;
        }

        Product::factory()
            ->count(10000)
            ->make()
            ->chunk(500)
            ->each(fn ($chunk) => DB::table('products')->insert(
                $chunk->map(fn (Product $p) => $p->getAttributes() + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            ));
    }
}
