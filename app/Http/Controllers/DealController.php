<?php

namespace App\Http\Controllers;

use App\Events\DealCreated;
use App\Events\DealDeleted;
use App\Events\DealLost;
use App\Events\DealRestored;
use App\Events\DealStageChanged;
use App\Events\DealUpdated;
use App\Events\DealWon;
use App\Http\Requests\ChangeStageRequest;
use App\Http\Requests\LoseDealRequest;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Http\Requests\WinDealRequest;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $this->authorize('viewAny', Deal::class);

        $query = Deal::query()
            ->with(['contact:id,name,email', 'owner:id,name', 'product:id,name']);

        // Filters
        foreach (['status', 'stage', 'owner_id', 'contact_id', 'product_id'] as $key) {
            if ($value = $request->get($key)) {
                $query->where($key, $value);
            }
        }

        if ($from = $request->date('created_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->date('created_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($expectedFrom = $request->date('expected_close_from')) {
            $query->whereDate('expected_close_date', '>=', $expectedFrom);
        }
        if ($expectedTo = $request->date('expected_close_to')) {
            $query->whereDate('expected_close_date', '<=', $expectedTo);
        }

        // Search
        if ($term = trim((string) $request->get('q'))) {
            $query->where(function ($subQuery) use ($term) {
                $subQuery->where('title', 'like', "%$term%")
                    ->orWhereHas('contact', fn ($contact) => 
                        $contact->where('name', 'like', "%$term%")
                                ->orWhere('email', 'like', "%$term%"));
            });
        }

        // Sort
        $sort = in_array($request->get('sort'), ['created_at', 'amount', 'expected_close_date', 'title']) 
            ? $request->get('sort') 
            : 'created_at';
        $direction = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        $deals = $query->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['deals' => $deals]);
        }

        return Inertia::render('Deals/Index', [
            'deals' => $deals,
            'filters' => $request->only([
                'q', 'status', 'stage', 'owner_id', 'contact_id', 'product_id',
                'created_from', 'created_to', 'expected_close_from', 'expected_close_to', 
                'sort', 'dir'
            ]),
            'enums' => [
                'stages' => ['new', 'qualified', 'proposal', 'negotiation', 'closed'],
                'statuses' => ['open', 'won', 'lost'],
                'sources' => ['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Deal::class);

        return Inertia::render('Deals/Create', [
            'prefill' => ['contact_id' => $request->integer('contact_id')],
            'enums' => [
                'stages' => ['new', 'qualified', 'proposal', 'negotiation', 'closed'],
                'statuses' => ['open', 'won', 'lost'],
                'sources' => ['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'],
                'currencyDefault' => 'USD',
            ],
        ]);
    }

    public function store(StoreDealRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Deal::class);

        $deal = Deal::create(array_merge(
            $request->validated(),
            [
                'owner_id' => $request->owner_id ?: optional($request->user())->id,
                'status' => 'open',
                'created_by' => $request->user()->id,
            ]
        ));

        event(new DealCreated($deal));

        if ($request->wantsJson()) {
            return response()->json(['deal' => $deal], 201);
        }

        return to_route('deals.show', $deal)->with('success', 'Deal created.');
    }

    public function show(Deal $deal): JsonResponse|Response
    {
        $this->authorize('view', $deal);
        
        $deal->load(['contact', 'owner', 'product']);

        if (request()->wantsJson()) {
            return response()->json(['deal' => $deal]);
        }

        return Inertia::render('Deals/Show', compact('deal'));
    }

    public function edit(Deal $deal): Response
    {
        $this->authorize('update', $deal);

        return Inertia::render('Deals/Edit', [
            'deal' => $deal->load(['contact', 'owner', 'product']),
            'enums' => [
                'stages' => ['new', 'qualified', 'proposal', 'negotiation', 'closed'],
                'sources' => ['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'],
            ],
        ]);
    }

    public function update(UpdateDealRequest $request, Deal $deal): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $deal);
        
        $deal->update($request->validated());
        event(new DealUpdated($deal));

        if ($request->wantsJson()) {
            return response()->json(['deal' => $deal]);
        }

        return to_route('deals.show', $deal)->with('success', 'Deal updated.');
    }

    public function destroy(Deal $deal): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $deal);
        
        $deal->delete();
        event(new DealDeleted($deal));

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Deal archived successfully']);
        }

        return to_route('deals.index')->with('success', 'Deal archived.');
    }

    public function restore(int $id): RedirectResponse|JsonResponse
    {
        $deal = Deal::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $deal);
        
        $deal->restore();
        event(new DealRestored($deal));

        if (request()->wantsJson()) {
            return response()->json(['deal' => $deal]);
        }

        return to_route('deals.show', $deal)->with('success', 'Deal restored.');
    }

    public function changeStage(ChangeStageRequest $request, Deal $deal): RedirectResponse|JsonResponse
    {
        $this->authorize('changeStage', $deal);
        
        $fromStage = $deal->stage;
        
        if (in_array($deal->status, ['won', 'lost'])) {
            $error = ['stage' => 'Cannot change stage of a closed deal.'];
            
            if ($request->wantsJson()) {
                return response()->json(['errors' => $error], 422);
            }
            
            return back()->withErrors($error);
        }

        $deal->update(['stage' => $request->validated('stage')]);
        event(new DealStageChanged($deal, $fromStage, $deal->stage));

        if ($request->wantsJson()) {
            return response()->json(['deal' => $deal]);
        }

        return back()->with('success', 'Stage updated.');
    }

    public function win(WinDealRequest $request, Deal $deal): RedirectResponse|JsonResponse
    {
        $this->authorize('win', $deal);
        
        if ($deal->status !== 'open') {
            $error = ['status' => 'Deal already closed.'];
            
            if ($request->wantsJson()) {
                return response()->json(['errors' => $error], 422);
            }
            
            return back()->withErrors($error);
        }

        $wonAmount = $request->validated('won_amount') ?? $deal->amount;
        
        $deal->update([
            'status' => 'won',
            'won_amount' => $wonAmount,
            'closed_at' => now(),
            'stage' => 'closed',
        ]);

        event(new DealWon($deal, $wonAmount));

        if ($request->wantsJson()) {
            return response()->json(['deal' => $deal]);
        }

        return to_route('deals.show', $deal)->with('success', 'Deal marked as won.');
    }

    public function lose(LoseDealRequest $request, Deal $deal): RedirectResponse|JsonResponse
    {
        $this->authorize('lose', $deal);
        
        if ($deal->status !== 'open') {
            $error = ['status' => 'Deal already closed.'];
            
            if ($request->wantsJson()) {
                return response()->json(['errors' => $error], 422);
            }
            
            return back()->withErrors($error);
        }

        $lostReason = $request->validated('lost_reason');
        
        $deal->update([
            'status' => 'lost',
            'lost_reason' => $lostReason,
            'closed_at' => now(),
            'stage' => 'closed',
        ]);

        event(new DealLost($deal, $lostReason));

        if ($request->wantsJson()) {
            return response()->json(['deal' => $deal]);
        }

        return to_route('deals.show', $deal)->with('success', 'Deal marked as lost.');
    }
}
