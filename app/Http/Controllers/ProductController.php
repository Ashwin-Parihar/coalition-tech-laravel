<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductService;
use Carbon\Carbon;
class ProductController extends Controller
{
    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'quantity_in_stock' => 'required|integer|min:0',
            'price_per_item' => 'required|numeric|min:1',
        ]);

        $productService = new ProductService();
        $products = $productService->getProducts();

        // Checking if the product name is already used by another product
        $existingProductNames = array_column($products, 'product_name');
        if (in_array($request->input('product_name'), $existingProductNames)) {
            return response()->json(['message' => 'Product already exists'], 400);
        }

        $products[] = [
            'id' => time().rand(1000, 9999).count($products),
            'product_name' => $request->input('product_name'),
            'quantity_in_stock' => $request->input('quantity_in_stock'),
            'price_per_item' => round($request->input('price_per_item'), 2),
            'submitted_at' => now()->format('Y-m-d H:i:s'),
        ];

        $productService->saveProducts($products);

        return response()->json(['message' => 'Product added successfully'], 200);
    }

    public function getProducts()
    {
        $productService = new ProductService();
        $products = $productService->getProducts();
        
        $totalValue = 0;
        try {
            $products = array_map(function($product) {
                $product['total_value'] = round($product['quantity_in_stock'] * $product['price_per_item'], 2);
                $product['datetime_submitted'] = Carbon::parse($product['submitted_at'])->format('d M Y, H:i');
                return $product;
            }, $products);
    
            // Sorting products by their time of submission in ascending order
            usort($products, function($a, $b) {
                return strtotime($a['submitted_at']) - strtotime($b['submitted_at']);
            });
            
            $totalValue = round(array_sum(array_column($products, 'total_value')), 2);
        } catch (\Exception $e) {
            $totalValue = 0;
            $products = [];
        }

        return response()->json(['products' => $products, 'total_value' => $totalValue], 200);
    }

    public function updateProduct(Request $request, $id)
    {
        $validFields = ['product_name', 'quantity_in_stock', 'price_per_item'];
        $fieldWiseValidation = [
            'product_name' => 'required|string|max:255',
            'quantity_in_stock' => 'required|integer|min:0',
            'price_per_item' => 'required|numeric|min:1',
        ];

        $request->validate([
            'field' => 'required|in:'.implode(',', $validFields)
        ]);

        $request->validate([
            'value' => $fieldWiseValidation[$request->input('field')]
        ]);

        $productService = new ProductService();
        $products = $productService->getProducts();

        // Creating a map of prodcuts for faster searching
        $productMap = array_column($products, null, 'id');

        // Checking if the product exists
        if (!isset($productMap[$id])) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Checking if the product name is already used by another product
        if ($request->input('field') === 'product_name' && in_array($request->input('value'), array_column($products, 'product_name'))) {
            return response()->json(['message' => 'Product name already exists'], 400);
        }

        $productMap[$id][$request->input('field')] = $request->input('value');

        // Converting map back to array
        $products = array_values($productMap);

        $productService->saveProducts($products);

        return response()->json(['message' => 'Product updated successfully'], 200);
    }
}
