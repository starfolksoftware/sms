<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $filters = $request->only(['search', 'status', 'source', 'owner_id', 'created_from', 'created_to']);
        $options = $request->only(['sort_by', 'sort_direction', 'per_page', 'page']);

        $contacts = $this->contactService->getContacts($filters, $options);

        return response()->json([
            'contacts' => $contacts->items(),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
                'from' => $contacts->firstItem(),
                'to' => $contacts->lastItem(),
            ],
            'links' => [
                'first' => $contacts->url(1),
                'last' => $contacts->url($contacts->lastPage()),
                'prev' => $contacts->previousPageUrl(),
                'next' => $contacts->nextPageUrl(),
            ],
            'message' => 'Contacts retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $this->authorize('create', Contact::class);

        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        // Set default owner_id if not provided
        if (! isset($validated['owner_id'])) {
            $validated['owner_id'] = auth()->id();
        }

        try {
            $result = $this->contactService->createContact($validated);

            return response()->json([
                'contact' => $result['contact'],
                'warnings' => $result['warnings'],
                'message' => 'Contact created successfully',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        $contact->load(['creator', 'owner', 'deals', 'tasks']);

        return response()->json([
            'contact' => $contact,
            'message' => 'Contact retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validated();

        try {
            $result = $this->contactService->updateContact($contact, $validated);

            return response()->json([
                'contact' => $result['contact'],
                'warnings' => $result['warnings'],
                'message' => 'Contact updated successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);

        $this->contactService->deleteContact($contact);

        return response()->json([
            'message' => 'Contact deleted successfully',
        ]);
    }

    /**
     * Restore the specified soft-deleted resource.
     */
    public function restore(int $id): JsonResponse
    {
        $contact = Contact::withTrashed()->findOrFail($id);

        $this->authorize('restore', $contact);

        if (! $contact->trashed()) {
            return response()->json([
                'message' => 'Contact is not deleted.',
            ], 400);
        }

        try {
            $restoredContact = $this->contactService->restoreContact($contact);

            return response()->json([
                'contact' => $restoredContact,
                'message' => 'Contact restored successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Cannot restore contact.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
