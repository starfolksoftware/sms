<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Deal::class);
        $deals = Deal::with('creator', 'contact')->get();
        return response()->json(['deals' => $deals]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Deal::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'status' => 'in:open,won,lost',
            'expected_close_date' => 'nullable|date',
            'contact_id' => 'required|exists:contacts,id',
        ]);
        
        $validated['created_by'] = auth()->id();
        $deal = Deal::create($validated);
        return response()->json(['deal' => $deal], 201);
    }

    public function show(Deal $deal): JsonResponse
    {
        $this->authorize('view', $deal);
        $deal->load('creator', 'contact');
        return response()->json(['deal' => $deal]);
    }

    public function update(Request $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'status' => 'in:open,won,lost',
            'expected_close_date' => 'nullable|date',
            'contact_id' => 'required|exists:contacts,id',
        ]);
        
        $deal->update($validated);
        return response()->json(['deal' => $deal]);
    }

    public function destroy(Deal $deal): JsonResponse
    {
        $this->authorize('delete', $deal);
        $deal->delete();
        return response()->json(['message' => 'Deal deleted successfully']);
    }
}
