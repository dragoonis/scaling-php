<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->count() > 0) {
            return;
        }

        $customerIds = Customer::query()->pluck('id')->all();

        Order::factory()
            ->count(5000)
            ->make()
            ->chunk(500)
            ->each(fn ($chunk) => DB::table('orders')->insert(
                $chunk->map(fn (Order $o) => $o->getAttributes() + [
                    'customer_id' => $customerIds[array_rand($customerIds)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            ));
    }
}
