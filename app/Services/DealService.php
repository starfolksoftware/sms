<?php

namespace App\Services;

use App\Events\DealCreated;
use App\Events\DealDeleted;
use App\Events\DealLost;
use App\Events\DealRestored;
use App\Events\DealStageChanged;
use App\Events\DealUpdated;
use App\Events\DealWon;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DealService
{
    /**
     * Create a new deal.
     */
    public function create(array $data): Deal
    {
        return DB::transaction(function () use ($data) {
            $deal = Deal::create($data);

            event(new DealCreated($deal));

            return $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name']);
        });
    }

    /**
     * Update an existing deal.
     */
    public function update(Deal $deal, array $data): Deal
    {
        return DB::transaction(function () use ($deal, $data) {
            $originalAttributes = $deal->getOriginal();

            $deal->update($data);

            event(new DealUpdated($deal, $originalAttributes));

            return $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name']);
        });
    }

    /**
     * Change the stage of a deal.
     */
    public function changeStage(Deal $deal, string $toStage, ?int $probability = null): Deal
    {
        return DB::transaction(function () use ($deal, $toStage, $probability) {
            $fromStage = $deal->stage;

            $updateData = ['stage' => $toStage];
            if ($probability !== null) {
                $updateData['probability'] = $probability;
            }

            $deal->update($updateData);

            event(new DealStageChanged($deal, $fromStage, $toStage));

            return $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name']);
        });
    }

    /**
     * Mark a deal as won.
     */
    public function win(Deal $deal, ?float $wonAmount = null): Deal
    {
        return DB::transaction(function () use ($deal, $wonAmount) {
            // Default won_amount to the deal amount if not specified
            $finalWonAmount = $wonAmount ?? $deal->amount ?? $deal->value;

            $deal->update([
                'status' => 'won',
                'won_amount' => $finalWonAmount,
                'closed_at' => now(),
            ]);

            event(new DealWon($deal, $finalWonAmount));

            return $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name']);
        });
    }

    /**
     * Mark a deal as lost.
     */
    public function lose(Deal $deal, string $lostReason): Deal
    {
        return DB::transaction(function () use ($deal, $lostReason) {
            $deal->update([
                'status' => 'lost',
                'lost_reason' => $lostReason,
                'closed_at' => now(),
            ]);

            event(new DealLost($deal, $lostReason));

            return $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name']);
        });
    }

    /**
     * Soft delete a deal.
     */
    public function delete(Deal $deal): void
    {
        DB::transaction(function () use ($deal) {
            $deal->delete();

            event(new DealDeleted($deal));
        });
    }

    /**
     * Restore a soft deleted deal.
     */
    public function restore(Deal $deal): Deal
    {
        return DB::transaction(function () use ($deal) {
            $deal->restore();

            // Reset status to open when restoring
            $deal->update([
                'status' => 'open',
                'closed_at' => null,
                'lost_reason' => null,
                'won_amount' => null,
            ]);

            event(new DealRestored($deal));

            return $deal->load(['contact:id,name,email', 'owner:id,name', 'product:id,name']);
        });
    }

    /**
     * Get a paginated list of deals with filtering and searching.
     */
    public function getList(array $filters = [], array $options = []): LengthAwarePaginator
    {
        $query = Deal::query();

        // Eager load relationships
        $query->with([
            'contact:id,name,email',
            'owner:id,name',
            'product:id,name',
        ]);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply search
        if (! empty($filters['q'])) {
            $this->applySearch($query, $filters['q']);
        }

        // Apply sorting
        $this->applySorting($query, $options['sort'] ?? '-created_at');

        // Include counts if requested
        if (! empty($options['include_counts'])) {
            // Activity log integration can be added here if needed
            // For now, we'll skip the withCount to avoid relation issues
        }

        // Paginate
        return $query->paginate(
            $options['per_page'] ?? 15,
            ['*'],
            'page',
            $options['page'] ?? 1
        );
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (! empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Date range filters
        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['expected_close_from'])) {
            $query->whereDate('expected_close_date', '>=', $filters['expected_close_from']);
        }

        if (! empty($filters['expected_close_to'])) {
            $query->whereDate('expected_close_date', '<=', $filters['expected_close_to']);
        }
    }

    /**
     * Apply search to the query.
     */
    private function applySearch(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
                ->orWhereHas('contact', function ($contactQuery) use ($searchTerm) {
                    $contactQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
        });
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(Builder $query, string $sortBy): void
    {
        $direction = 'asc';

        if (str_starts_with($sortBy, '-')) {
            $direction = 'desc';
            $sortBy = substr($sortBy, 1);
        }

        switch ($sortBy) {
            case 'created_at':
            case 'amount':
            case 'expected_close_date':
            case 'title':
                $query->orderBy($sortBy, $direction);
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }
}
