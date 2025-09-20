<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TimelineFilters;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\ContactTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactTimelineController extends Controller
{
    public function __construct(private ContactTimelineService $timelineService) {}

    /**
     * Get timeline events for a contact
     */
    public function index(Contact $contact, Request $request): JsonResponse
    {
        // Authorize access to contact
        $this->authorize('view', $contact);

        // Validate query parameters
        $validated = $request->validate([
            'type' => ['sometimes', 'array'],
            'type.*' => [Rule::in(['tasks', 'deals', 'system', 'emails'])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'cursor' => ['sometimes', 'string'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        // Create filters object
        $filters = TimelineFilters::fromArray([
            'types' => $validated['type'] ?? ['tasks', 'deals', 'system', 'emails'],
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'cursor' => $validated['cursor'] ?? null,
            'limit' => $validated['limit'] ?? 15,
        ]);

        // Fetch timeline data
        $timeline = $this->timelineService->fetch($contact, $filters);

        return response()->json([
            'data' => $timeline->toArray(),
            'meta' => [
                'contact_id' => $contact->id,
                'contact_name' => $contact->name,
                'filters_applied' => $filters->toArray(),
            ],
        ]);
    }
}
