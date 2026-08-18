<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/products', function () {
    return ['products' => Product::query()->orderBy('id')->get()];
});

Route::get('/products/{product}', function (Product $product) {
    return $product;
});
