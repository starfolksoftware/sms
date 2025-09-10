<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactService;
use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

    // Web routes (Inertia)
    
    /**
     * Display contacts list page (Inertia)
     */
    public function webIndex(Request $request): Response
    {
        $this->authorize('viewAny', Contact::class);

        $filters = $request->only(['search', 'status', 'source', 'owner_id', 'created_from', 'created_to']);
        $options = $request->only(['sort_by', 'sort_direction', 'per_page', 'page']);
        
        $contacts = $this->contactService->getContacts($filters, $options);
        
        // Get filter options
        $owners = User::select('id', 'name')->orderBy('name')->get();
        $statusOptions = collect(ContactStatus::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->displayName(),
            'badge_class' => $status->badgeClass()
        ]);
        $sourceOptions = collect(ContactSource::cases())->map(fn($source) => [
            'value' => $source->value, 
            'label' => $source->displayName(),
            'icon_class' => $source->iconClass()
        ]);

        return Inertia::render('crm/contacts/Index', [
            'contacts' => $contacts,
            'filters' => $filters,
            'owners' => $owners,
            'statusOptions' => $statusOptions,
            'sourceOptions' => $sourceOptions,
        ]);
    }

    /**
     * Show contact create form (Inertia)
     */
    public function webCreate(): Response
    {
        $this->authorize('create', Contact::class);

        $owners = User::select('id', 'name')->orderBy('name')->get();
        $statusOptions = collect(ContactStatus::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->displayName()
        ]);
        $sourceOptions = collect(ContactSource::cases())->map(fn($source) => [
            'value' => $source->value,
            'label' => $source->displayName()
        ]);

        return Inertia::render('crm/contacts/Create', [
            'owners' => $owners,
            'statusOptions' => $statusOptions,
            'sourceOptions' => $sourceOptions,
        ]);
    }

    /**
     * Show contact details (Inertia)
     */
    public function webShow(Contact $contact): Response
    {
        $this->authorize('view', $contact);

        $contact->load(['creator', 'owner'])->loadCount(['deals', 'tasks']);

        return Inertia::render('crm/contacts/Show', [
            'contact' => $contact,
            'can' => [
                'update' => auth()->user()->can('update', $contact),
                'delete' => auth()->user()->can('delete', $contact),
                'restore' => auth()->user()->can('restore', $contact),
            ],
        ]);
    }

    /**
     * Show contact edit form (Inertia)
     */
    public function webEdit(Contact $contact): Response
    {
        $this->authorize('update', $contact);

        $owners = User::select('id', 'name')->orderBy('name')->get();
        $statusOptions = collect(ContactStatus::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->displayName()
        ]);
        $sourceOptions = collect(ContactSource::cases())->map(fn($source) => [
            'value' => $source->value,
            'label' => $source->displayName()
        ]);

        return Inertia::render('crm/contacts/Edit', [
            'contact' => $contact,
            'owners' => $owners,
            'statusOptions' => $statusOptions,
            'sourceOptions' => $sourceOptions,
        ]);
    }

    /**
     * Store contact via web form (Inertia)
     */
    public function webStore(StoreContactRequest $request)
    {
        $this->authorize('create', Contact::class);

        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        if (! isset($validated['owner_id'])) {
            $validated['owner_id'] = auth()->id();
        }

        try {
            $result = $this->contactService->createContact($validated);

            $message = 'Contact created successfully';
            if (!empty($result['warnings'])) {
                $message .= ' (with warnings)';
            }

            return redirect()->route('crm.contacts.show', $result['contact'])
                ->with('success', $message)
                ->with('warnings', $result['warnings']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Update contact via web form (Inertia)
     */
    public function webUpdate(UpdateContactRequest $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validated();

        try {
            $result = $this->contactService->updateContact($contact, $validated);

            $message = 'Contact updated successfully';
            if (!empty($result['warnings'])) {
                $message .= ' (with warnings)';
            }

            return redirect()->route('crm.contacts.show', $result['contact'])
                ->with('success', $message)
                ->with('warnings', $result['warnings']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Delete contact via web (Inertia)
     */
    public function webDestroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        $this->contactService->deleteContact($contact);

        return redirect()->route('crm.contacts.index')
            ->with('success', 'Contact deleted successfully');
    }

    /**
     * Restore contact via web (Inertia)
     */
    public function webRestore(int $id)
    {
        $contact = Contact::withTrashed()->findOrFail($id);
        $this->authorize('restore', $contact);

        if (! $contact->trashed()) {
            return redirect()->route('crm.contacts.show', $contact)
                ->with('error', 'Contact is not deleted.');
        }

        try {
            $restoredContact = $this->contactService->restoreContact($contact);

            return redirect()->route('crm.contacts.show', $restoredContact)
                ->with('success', 'Contact restored successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
