<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DealController extends Controller
{
    public function win(Request $request, Deal $deal): JsonResponse
    {
        Gate::authorize('win', $deal);

        $validated = $request->validate([
            'won_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($deal->status !== 'open') {
            throw ValidationException::withMessages([
                'status' => ['Deal must be open to mark as won.'],
            ]);
        }

        $deal->markAsWon($validated['won_amount'] ?? null);

        return response()->json([
            'message' => 'Deal marked as won successfully.',
            'deal' => $deal->fresh(),
        ]);
    }

    public function lose(Request $request, Deal $deal): JsonResponse
    {
        Gate::authorize('lose', $deal);

        $validated = $request->validate([
            'lost_reason' => ['required', 'string', 'min:5'],
        ]);

        if ($deal->status !== 'open') {
            throw ValidationException::withMessages([
                'status' => ['Deal must be open to mark as lost.'],
            ]);
        }

        $deal->markAsLost($validated['lost_reason']);

        return response()->json([
            'message' => 'Deal marked as lost successfully.',
            'deal' => $deal->fresh(),
        ]);
    }
}
