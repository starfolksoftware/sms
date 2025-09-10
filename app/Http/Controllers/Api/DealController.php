<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStageRequest;
use App\Http\Requests\LoseDealRequest;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Http\Requests\WinDealRequest;
use App\Http\Resources\DealCollection;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function __construct(
        private DealService $dealService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): DealCollection
    {
        $this->authorize('viewAny', Deal::class);

        $filters = $request->only([
            'status', 'stage', 'owner_id', 'contact_id', 'product_id',
            'source', 'created_from', 'created_to',
            'expected_close_from', 'expected_close_to', 'q',
        ]);

        $options = [
            'sort' => $request->get('sort', '-created_at'),
            'per_page' => min($request->get('per_page', 15), 100),
            'page' => $request->get('page', 1),
            'include_counts' => $request->boolean('include_counts'),
        ];

        $deals = $this->dealService->getList($filters, $options);

        return new DealCollection($deals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDealRequest $request): JsonResponse
    {
        $deal = $this->dealService->create($request->validated());

        return response()->json([
            'message' => 'Deal created successfully.',
            'data' => new DealResource($deal),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal): JsonResponse
    {
        $this->authorize('view', $deal);

        $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name', 'creator:id,name']);

        return response()->json([
            'data' => new DealResource($deal),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDealRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $updatedDeal = $this->dealService->update($deal, $request->validated());

        return response()->json([
            'message' => 'Deal updated successfully.',
            'data' => new DealResource($updatedDeal),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal): JsonResponse
    {
        $this->authorize('delete', $deal);

        $this->dealService->delete($deal);

        return response()->json(null, 204);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(Deal $deal): JsonResponse
    {
        $this->authorize('restore', $deal);

        $restoredDeal = $this->dealService->restore($deal);

        return response()->json([
            'message' => 'Deal restored successfully.',
            'data' => new DealResource($restoredDeal),
        ]);
    }

    /**
     * Change the stage of the specified resource.
     */
    public function changeStage(ChangeStageRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('changeStage', $deal);

        $updatedDeal = $this->dealService->changeStage(
            $deal,
            $request->validated('stage'),
            $request->validated('probability')
        );

        return response()->json([
            'message' => 'Deal stage changed successfully.',
            'data' => new DealResource($updatedDeal),
        ]);
    }

    /**
     * Mark the specified resource as won.
     */
    public function win(WinDealRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('win', $deal);

        $wonDeal = $this->dealService->win($deal, $request->validated('won_amount'));

        return response()->json([
            'message' => 'Deal marked as won.',
            'data' => new DealResource($wonDeal),
        ]);
    }

    /**
     * Mark the specified resource as lost.
     */
    public function lose(LoseDealRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('lose', $deal);

        $lostDeal = $this->dealService->lose($deal, $request->validated('lost_reason'));

        return response()->json([
            'message' => 'Deal marked as lost.',
            'data' => new DealResource($lostDeal),
        ]);
    }
}
