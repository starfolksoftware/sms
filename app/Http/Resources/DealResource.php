<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'value' => $this->value, // Legacy field support
            'stage' => $this->stage,
            'status' => $this->status,
            'probability' => $this->probability,
            'expected_close_date' => $this->expected_close_date?->format('Y-m-d'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
            'won_amount' => $this->won_amount,
            'lost_reason' => $this->lost_reason,
            'source' => $this->source,
            'source_meta' => $this->source_meta,
            'notes' => $this->notes,
            'effective_amount' => $this->getEffectiveAmount(),
            'is_closed' => $this->isClosed(),
            'is_won' => $this->isWon(),
            'is_lost' => $this->isLost(),

            // Relationships
            'contact' => $this->whenLoaded('contact', function () {
                return [
                    'id' => $this->contact->id,
                    'name' => $this->contact->name,
                    'email' => $this->contact->email,
                ];
            }),
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                ];
            }),
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),

            // Activity counts if loaded (disabled for now)
            // 'activities_count' => $this->when(isset($this->activities_count), $this->activities_count),

            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
