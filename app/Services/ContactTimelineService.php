<?php

namespace App\Services;

use App\DTOs\TimelineEventDTO;
use App\DTOs\TimelineFilters;
use App\DTOs\TimelinePageDTO;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class ContactTimelineService
{
    public function fetch(Contact $contact, TimelineFilters $filters): TimelinePageDTO
    {
        try {
            $events = collect();
            $warnings = [];

            // Validate user permissions
            $user = Auth::user();
            if (! $user || ! $user->can('view', $contact)) {
                return TimelinePageDTO::empty();
            }

            // Fetch events from each source with error handling
            if ($filters->hasType('tasks')) {
                try {
                    $taskEvents = $this->getTaskEvents($contact, $filters, $user);
                    $events = $events->merge($taskEvents);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch task events for timeline', [
                        'contact_id' => $contact->id,
                        'error' => $e->getMessage(),
                    ]);
                    $warnings[] = 'Some task events could not be loaded';
                }
            }

            if ($filters->hasType('deals')) {
                try {
                    $dealEvents = $this->getDealEvents($contact, $filters, $user);
                    $events = $events->merge($dealEvents);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch deal events for timeline', [
                        'contact_id' => $contact->id,
                        'error' => $e->getMessage(),
                    ]);
                    $warnings[] = 'Some deal events could not be loaded';
                }
            }

            if ($filters->hasType('system')) {
                try {
                    $systemEvents = $this->getSystemEvents($contact, $filters, $user);
                    $events = $events->merge($systemEvents);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch system events for timeline', [
                        'contact_id' => $contact->id,
                        'error' => $e->getMessage(),
                    ]);
                    $warnings[] = 'Some system events could not be loaded';
                }
            }

            if ($filters->hasType('emails')) {
                try {
                    $emailEvents = $this->getEmailEvents($contact, $filters, $user);
                    $events = $events->merge($emailEvents);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch email events for timeline', [
                        'contact_id' => $contact->id,
                        'error' => $e->getMessage(),
                    ]);
                    $warnings[] = 'Some email events could not be loaded';
                }
            }

            // Sort by timestamp descending (newest first)
            $events = $events->sortByDesc('timestamp')->values();

            // Apply cursor pagination
            $paginatedResult = $this->applyCursorPagination($events, $filters);

            return new TimelinePageDTO(
                events: $paginatedResult['events'],
                nextCursor: $paginatedResult['nextCursor'],
                prevCursor: $paginatedResult['prevCursor'],
                hasMore: $paginatedResult['hasMore'],
                partial: ! empty($warnings),
                warning: ! empty($warnings) ? implode(', ', $warnings) : null,
                total: $events->count()
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch timeline for contact', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return TimelinePageDTO::partial(
                collect(),
                'Timeline could not be loaded due to a system error'
            );
        }
    }

    private function getTaskEvents(Contact $contact, TimelineFilters $filters, User $user): Collection
    {
        $query = Task::select(['id', 'title', 'status', 'assignee_id', 'creator_id', 'contact_id', 'created_at', 'updated_at'])
            ->where('contact_id', $contact->id)
            ->with(['assignee:id,name', 'creator:id,name']);

        // Apply date filters with proper indexing
        if ($filters->from) {
            $query->where('created_at', '>=', $filters->from);
        }
        if ($filters->to) {
            $query->where('created_at', '<=', $filters->to);
        }

        // Apply authorization filters
        $query->whereHas('contact', function ($q) use ($user) {
            $q->where(function ($q) use ($user) {
                // Users can see tasks for contacts they can view
                if ($user->can('viewAny', Task::class)) {
                    return;
                }
                // Add additional permission logic if needed
            });
        });

        return $query->get()->filter(function (Task $task) use ($user) {
            return $user->can('view', $task);
        })->map(function (Task $task) {
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

    private function getDealEvents(Contact $contact, TimelineFilters $filters, User $user): Collection
    {
        $events = collect();

        $query = Deal::select(['id', 'title', 'amount', 'currency', 'status', 'owner_id', 'product_id', 'contact_id', 'created_at', 'closed_at', 'won_amount', 'lost_reason'])
            ->where('contact_id', $contact->id)
            ->with(['owner:id,name', 'product:id,name']);

        // Apply date filters
        if ($filters->from) {
            $query->where(function ($q) use ($filters) {
                $q->where('created_at', '>=', $filters->from)
                    ->orWhere('closed_at', '>=', $filters->from);
            });
        }
        if ($filters->to) {
            $query->where(function ($q) use ($filters) {
                $q->where('created_at', '<=', $filters->to)
                    ->orWhere('closed_at', '<=', $filters->to);
            });
        }

        $deals = $query->get()->filter(function (Deal $deal) use ($user) {
            return $user->can('view', $deal);
        });

        foreach ($deals as $deal) {
            // Deal created event
            if (! $filters->from || $deal->created_at >= $filters->from) {
                if (! $filters->to || $deal->created_at <= $filters->to) {
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
                }
            }

            // Deal won/lost events
            if ($deal->status === 'won' && $deal->closed_at) {
                if (! $filters->from || $deal->closed_at >= $filters->from) {
                    if (! $filters->to || $deal->closed_at <= $filters->to) {
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
                    }
                }
            } elseif ($deal->status === 'lost' && $deal->closed_at) {
                if (! $filters->from || $deal->closed_at >= $filters->from) {
                    if (! $filters->to || $deal->closed_at <= $filters->to) {
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
            }
        }

        return $events;
    }

    private function getSystemEvents(Contact $contact, TimelineFilters $filters, User $user): Collection
    {
        $query = Activity::select(['id', 'event', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'created_at'])
            ->where('subject_type', Contact::class)
            ->where('subject_id', $contact->id)
            ->with('causer:id,name');

        // Apply date filters
        if ($filters->from) {
            $query->where('created_at', '>=', $filters->from);
        }
        if ($filters->to) {
            $query->where('created_at', '<=', $filters->to);
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

    private function getEmailEvents(Contact $contact, TimelineFilters $filters, User $user): Collection
    {
        // Check if user has permission to view marketing data
        if (! $user->hasPermissionTo('view_marketing')) {
            return collect();
        }

        // Placeholder for future email tracking implementation
        // When email tracking tables are created, this method will fetch:
        // - Email sent events
        // - Email opened events
        // - Email clicked events
        // - Email unsubscribed events
        //
        // Example implementation:
        // $query = EmailEvent::where('contact_id', $contact->id)
        //     ->select(['id', 'type', 'campaign_id', 'occurred_at', 'metadata'])
        //     ->with('campaign:id,name');
        //
        // if ($filters->from) {
        //     $query->where('occurred_at', '>=', $filters->from);
        // }
        // if ($filters->to) {
        //     $query->where('occurred_at', '<=', $filters->to);
        // }

        return collect();
    }

    private function applyCursorPagination(Collection $events, TimelineFilters $filters): array
    {
        $limit = $filters->limit;
        $cursor = $filters->cursor;

        if ($cursor) {
            // Find the position based on cursor
            $cursorTimestamp = Carbon::parse(base64_decode($cursor));
            $position = $events->search(function (TimelineEventDTO $event) use ($cursorTimestamp) {
                return $event->timestamp->equalTo($cursorTimestamp);
            });

            if ($position !== false) {
                $events = $events->slice($position + 1);
            }
        }

        $items = $events->take($limit);
        $hasMore = $events->count() > $limit;

        $nextCursor = null;
        $prevCursor = null;

        if ($hasMore && $items->isNotEmpty()) {
            $nextCursor = base64_encode($items->last()->timestamp->toISOString());
        }

        if ($cursor && $items->isNotEmpty()) {
            $prevCursor = base64_encode($items->first()->timestamp->toISOString());
        }

        return [
            'events' => $items,
            'nextCursor' => $nextCursor,
            'prevCursor' => $prevCursor,
            'hasMore' => $hasMore,
        ];
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

    // Legacy method for backward compatibility
    public function getTimeline(
        Contact $contact,
        array $filters = [],
        int $perPage = 15
    ) {
        $timelineFilters = TimelineFilters::fromArray(array_merge($filters, ['limit' => $perPage]));
        $result = $this->fetch($contact, $timelineFilters);

        // Convert to legacy pagination format for existing widget
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $result->events->values(),
            $result->total,
            $perPage,
            request()->get('page', 1),
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }
}
