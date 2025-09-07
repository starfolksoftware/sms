<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $contacts = Contact::with('creator')->get();

        return response()->json([
            'contacts' => $contacts,
            'message' => 'Contacts retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Contact::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:contacts,email',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $contact = Contact::create($validated);
        $contact->load('creator');

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

        $contact->load('creator', 'deals');

        return response()->json([
            'contact' => $contact,
            'message' => 'Contact retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:contacts,email,' . $contact->id,
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $contact->update($validated);
        $contact->load('creator');

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
