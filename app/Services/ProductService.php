<?php

namespace App\Services;

class ProductService
{
    public function getProducts()
    {
        $path = storage_path('app/products.json');
        if (! file_exists($path)) {
            return [];
        }

        try {
            return json_decode(file_get_contents($path), true);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function saveProducts($products)
    {
        try {
            $path = storage_path('app/products.json');
            file_put_contents($path, json_encode($products));
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
