<?php

namespace App\Services;

use App\DTOs\TimelineEventDTO;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ContactTimelineService
{
    public function getTimeline(
        Contact $contact,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $events = collect();

        // Get events based on type filters
        $types = $filters['types'] ?? ['tasks', 'deals', 'system'];
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : null;
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : null;

        if (in_array('tasks', $types)) {
            $events = $events->merge($this->getTaskEvents($contact, $dateFrom, $dateTo));
        }

        if (in_array('deals', $types)) {
            $events = $events->merge($this->getDealEvents($contact, $dateFrom, $dateTo));
        }

        if (in_array('system', $types)) {
            $events = $events->merge($this->getSystemEvents($contact, $dateFrom, $dateTo));
        }

        if (in_array('emails', $types)) {
            $events = $events->merge($this->getEmailEvents($contact, $dateFrom, $dateTo));
        }

        // Sort by timestamp descending (newest first)
        $events = $events->sortByDesc('timestamp')->values();

        // Manual pagination
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $items = $events->slice($offset, $perPage);

        return new LengthAwarePaginator(
            $items->values(),
            $events->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    private function getTaskEvents(Contact $contact, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): Collection
    {
        $query = Task::where('contact_id', $contact->id)
            ->with(['assignee', 'creator']);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->get()->map(function (Task $task) {
            return new TimelineEventDTO(
                id: "task_{$task->id}_created",
                type: 'task',
                subtype: 'created',
                timestamp: $task->created_at,
                actor: $task->creator ? [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name,
                ] : null,
                title: "Task created: {$task->title}",
                summary: 'Status: '.ucwords(str_replace('_', ' ', $task->status)).
                        ($task->assignee ? " • Assigned to: {$task->assignee->name}" : ''),
                link: [
                    'label' => 'View Task',
                    'url' => "/admin/tasks/{$task->id}",
                ],
                metadata: [
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'assignee_id' => $task->assignee_id,
                ]
            );
        });
    }

    private function getDealEvents(Contact $contact, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): Collection
    {
        $events = collect();

        // Get deal creation events
        $query = Deal::where('contact_id', $contact->id)
            ->with(['owner', 'product']);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $deals = $query->get();

        foreach ($deals as $deal) {
            // Deal created event
            $events->push(new TimelineEventDTO(
                id: "deal_{$deal->id}_created",
                type: 'deal',
                subtype: 'created',
                timestamp: $deal->created_at,
                actor: $deal->owner ? [
                    'id' => $deal->owner->id,
                    'name' => $deal->owner->name,
                ] : null,
                title: "Deal created: {$deal->title}",
                summary: 'Amount: '.number_format($deal->amount, 2).
                        " {$deal->currency}".
                        ($deal->product ? " • Product: {$deal->product->name}" : ''),
                link: [
                    'label' => 'View Deal',
                    'url' => "/admin/deals/{$deal->id}",
                ],
                metadata: [
                    'deal_id' => $deal->id,
                    'amount' => $deal->amount,
                    'currency' => $deal->currency,
                ]
            ));

            // Deal won/lost events
            if ($deal->status === 'won' && $deal->closed_at) {
                $events->push(new TimelineEventDTO(
                    id: "deal_{$deal->id}_won",
                    type: 'deal',
                    subtype: 'won',
                    timestamp: $deal->closed_at,
                    actor: $deal->owner ? [
                        'id' => $deal->owner->id,
                        'name' => $deal->owner->name,
                    ] : null,
                    title: "Deal won: {$deal->title}",
                    summary: 'Won amount: '.number_format($deal->won_amount ?? $deal->amount, 2)." {$deal->currency}",
                    link: [
                        'label' => 'View Deal',
                        'url' => "/admin/deals/{$deal->id}",
                    ],
                    metadata: [
                        'deal_id' => $deal->id,
                        'won_amount' => $deal->won_amount,
                        'original_amount' => $deal->amount,
                    ]
                ));
            } elseif ($deal->status === 'lost' && $deal->closed_at) {
                $events->push(new TimelineEventDTO(
                    id: "deal_{$deal->id}_lost",
                    type: 'deal',
                    subtype: 'lost',
                    timestamp: $deal->closed_at,
                    actor: $deal->owner ? [
                        'id' => $deal->owner->id,
                        'name' => $deal->owner->name,
                    ] : null,
                    title: "Deal lost: {$deal->title}",
                    summary: 'Lost reason: '.($deal->lost_reason ?? 'Not specified'),
                    link: [
                        'label' => 'View Deal',
                        'url' => "/admin/deals/{$deal->id}",
                    ],
                    metadata: [
                        'deal_id' => $deal->id,
                        'lost_reason' => $deal->lost_reason,
                    ]
                ));
            }
        }

        return $events;
    }

    private function getSystemEvents(Contact $contact, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): Collection
    {
        $query = Activity::where('subject_type', Contact::class)
            ->where('subject_id', $contact->id)
            ->with('causer');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->get()->map(function (Activity $activity) {
            return new TimelineEventDTO(
                id: "activity_{$activity->id}",
                type: 'system',
                subtype: $activity->event ?? 'updated',
                timestamp: $activity->created_at,
                actor: $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                title: $activity->description ?? "Contact {$activity->event}",
                summary: $this->formatActivitySummary($activity),
                link: [
                    'label' => 'View Contact',
                    'url' => "/admin/contacts/{$activity->subject_id}",
                ],
                metadata: [
                    'activity_id' => $activity->id,
                    'event' => $activity->event,
                    'properties' => $activity->properties,
                ]
            );
        });
    }

    private function getEmailEvents(Contact $contact, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): Collection
    {
        // Placeholder for future email tracking implementation
        // When email tracking tables are created, this method will fetch:
        // - Email sent events
        // - Email opened events
        // - Email clicked events
        // - Email unsubscribed events

        return collect();
    }

    private function formatActivitySummary(Activity $activity): ?string
    {
        if (! $activity->properties || $activity->properties->isEmpty()) {
            return null;
        }

        $changes = [];

        if ($activity->properties->has('attributes') && $activity->properties->has('old')) {
            $attributes = $activity->properties->get('attributes', []);
            $old = $activity->properties->get('old', []);

            foreach ($attributes as $key => $value) {
                if (isset($old[$key]) && $old[$key] !== $value) {
                    $changes[] = ucfirst($key).": {$old[$key]} → {$value}";
                }
            }
        }

        return $changes ? implode(' • ', $changes) : null;
    }
}
