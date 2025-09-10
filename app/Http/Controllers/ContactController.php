<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $contacts = Contact::with(['creator', 'owner'])
            ->withCount('deals')
            ->get();

        return response()->json([
            'contacts' => $contacts,
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

        $contact = Contact::create($validated);
        $contact->load(['creator', 'owner']);

        return response()->json([
            'contact' => $contact,
            'message' => 'Contact created successfully',
        ], 201);
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

        $contact->update($validated);
        $contact->load(['creator', 'owner']);

        return response()->json([
            'contact' => $contact,
            'message' => 'Contact updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return response()->json([
            'message' => 'Contact deleted successfully',
        ]);
    }
}
