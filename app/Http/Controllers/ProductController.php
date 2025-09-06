<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Product::class);
        $products = Product::with('creator')->get();
        return response()->json(['products' => $products]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('create', Product::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'stock_quantity' => 'integer|min:0',
            'active' => 'boolean',
        ]);
        
        $validated['created_by'] = auth()->id();
        $product = Product::create($validated);
        return response()->json(['product' => $product], 201);
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);
        $product->load('creator');
        return response()->json(['product' => $product]);
    }

    public function update(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'stock_quantity' => 'integer|min:0',
            'active' => 'boolean',
        ]);
        
        $product->update($validated);
        return response()->json(['product' => $product]);
    }

    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
