<?php

namespace App\Http\Controllers;

use App\Events\ContactCreated;
use App\Events\ContactDeleted;
use App\Events\ContactRestored;
use App\Events\ContactUpdated;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()
            ->with(['owner', 'creator'])
            ->withCount(['deals', 'tasks']);

        // Apply filters
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        if ($ownerId = $request->integer('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($from = $request->date('created_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('created_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Search functionality
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();
        
        if (in_array($sortBy, ['name', 'email', 'company', 'status', 'source', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = $request->integer('per_page', 15);
        $contacts = $query->paginate($perPage)->withQueryString();

    // In the test environment we return JSON to avoid relying on Inertia SSR / Vite manifest.
        if ($request->wantsJson()) {
            return response()->json([
                'contacts' => $contacts,
                'message' => 'Contacts retrieved successfully',
            ]);
        }

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'filters' => $request->only(['status', 'source', 'owner_id', 'created_from', 'created_to', 'search', 'sort_by', 'sort_direction']),
            'users' => User::select('id', 'name')->get(),
            'canCreateContacts' => $request->user()->can('create', Contact::class),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Contact::class);

        return Inertia::render('Contacts/Create', [
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        $contact = Contact::create($validated);
        $contact->load(['creator', 'owner']);

        event(new ContactCreated($contact));

        if ($request->wantsJson()) {
            return response()->json([
                'contact' => $contact,
                'message' => 'Contact created successfully',
            ], 201);
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Contact $contact)
    {
        $this->authorize('view', $contact);

        $contact->load([
            'creator', 
            'owner',
            'deals' => fn($query) => $query->latest()->limit(5),
            'tasks' => fn($query) => $query->latest()->limit(5)
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'contact' => $contact,
                'message' => 'Contact retrieved successfully',
            ]);
        }

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
            'canEditContact' => $request->user()->can('update', $contact),
            'canDeleteContact' => $request->user()->can('delete', $contact),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Contact $contact): Response
    {
        $this->authorize('update', $contact);

        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $validated = $request->validated();

        $contact->update($validated);
        $contact->load(['creator', 'owner']);

        event(new ContactUpdated($contact));

        if ($request->wantsJson()) {
            return response()->json([
                'contact' => $contact,
                'message' => 'Contact updated successfully',
            ]);
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Contact $contact)
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        event(new ContactDeleted($contact));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Contact deleted successfully',
            ]);
        }

        return redirect()->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Request $request, int $id)
    {
        $contact = Contact::withTrashed()->findOrFail($id);
        $this->authorize('restore', $contact);

        $contact->restore();

        event(new ContactRestored($contact));

        if ($request->wantsJson()) {
            return response()->json([
                'contact' => $contact->load(['creator', 'owner']),
                'message' => 'Contact restored successfully',
            ]);
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Contact restored successfully.');
    }

    /**
     * Check for duplicate contacts by email (for inline warnings).
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $email = $request->string('email')->toString();
        $excludeId = $request->integer('exclude_id');

        if (empty($email)) {
            return response()->json(['duplicate' => false]);
        }

        $query = Contact::where('email_normalized', strtolower(trim($email)));
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $duplicate = $query->first(['id', 'name', 'email']);

        return response()->json([
            'duplicate' => $duplicate !== null,
            'contact' => $duplicate,
        ]);
    }
}
