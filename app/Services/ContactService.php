<?php

namespace App\Services;

use App\Events\ContactCreated;
use App\Events\ContactDeleted;
use App\Events\ContactRestored;
use App\Events\ContactUpdated;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactService
{
    /**
     * Get paginated, filtered, and searchable contacts
     */
    public function getContacts(array $filters = [], array $options = []): LengthAwarePaginator
    {
        $query = Contact::query();

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply search
        if (! empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        // Apply sorting
        $this->applySorting($query, $options);

        // Apply eager loading
        $query->with(['creator', 'owner'])->withCount('deals');

        // Return paginated results
        return $query->paginate(
            $options['per_page'] ?? 15,
            ['*'],
            'page',
            $options['page'] ?? 1
        );
    }

    /**
     * Create a new contact with duplicate detection
     */
    public function createContact(array $data): array
    {
        // Check for duplicates and provide warnings
        $duplicateInfo = $this->checkForDuplicates($data);

        // If email duplicate exists and it's not soft deleted, throw validation error
        if ($duplicateInfo['email_duplicate'] && ! $duplicateInfo['email_duplicate']->trashed()) {
            $validator = validator($data, []);
            $validator->errors()->add('email',
                'A contact with this email already exists. '.
                'Existing contact: '.$duplicateInfo['email_duplicate']->name.
                ' (ID: '.$duplicateInfo['email_duplicate']->id.')'
            );
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $contact = Contact::create($data);

        event(new ContactCreated($contact));

        return [
            'contact' => $contact->load(['creator', 'owner']),
            'warnings' => $duplicateInfo['phone_warning'] ? [
                'phone' => 'A contact with similar phone number already exists: '.
                          $duplicateInfo['phone_duplicate']->name.
                          ' (ID: '.$duplicateInfo['phone_duplicate']->id.')',
            ] : [],
        ];
    }

    /**
     * Update contact with duplicate detection
     */
    public function updateContact(Contact $contact, array $data): array
    {
        // Check for duplicates excluding current contact
        $duplicateInfo = $this->checkForDuplicates($data, $contact->id);

        // If email duplicate exists and it's not soft deleted, throw validation error
        if ($duplicateInfo['email_duplicate']) {
            $validator = validator($data, []);
            $validator->errors()->add('email',
                'A contact with this email already exists. '.
                'Existing contact: '.$duplicateInfo['email_duplicate']->name.
                ' (ID: '.$duplicateInfo['email_duplicate']->id.')'
            );
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $contact->update($data);

        event(new ContactUpdated($contact));

        return [
            'contact' => $contact->load(['creator', 'owner']),
            'warnings' => $duplicateInfo['phone_warning'] ? [
                'phone' => 'A contact with similar phone number already exists: '.
                          $duplicateInfo['phone_duplicate']->name.
                          ' (ID: '.$duplicateInfo['phone_duplicate']->id.')',
            ] : [],
        ];
    }

    /**
     * Delete contact (soft delete)
     */
    public function deleteContact(Contact $contact): void
    {
        $contact->delete();
        event(new ContactDeleted($contact));
    }

    /**
     * Restore soft-deleted contact with duplicate validation
     */
    public function restoreContact(Contact $contact): Contact
    {
        // Validate uniqueness constraints before restoring
        if ($contact->email) {
            $emailExists = Contact::where('email', $contact->email)
                ->whereNull('deleted_at')
                ->exists();

            if ($emailExists) {
                $validator = validator([], []);
                $validator->errors()->add('email', 'Cannot restore: A contact with this email already exists.');
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }

        $contact->restore();
        event(new ContactRestored($contact));

        return $contact->load(['creator', 'owner']);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
    }

    /**
     * Apply search to query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('company', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%")
                ->orWhere('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Apply sorting to query
     */
    private function applySorting(Builder $query, array $options): void
    {
        $sortBy = $options['sort_by'] ?? 'created_at';
        $sortDirection = $options['sort_direction'] ?? 'desc';

        $allowedSortFields = ['created_at', 'name', 'email', 'company', 'updated_at'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Check for duplicate contacts
     */
    private function checkForDuplicates(array $data, ?int $excludeId = null): array
    {
        $result = [
            'email_duplicate' => null,
            'phone_duplicate' => null,
            'phone_warning' => false,
        ];

        // Check email duplicate
        if (! empty($data['email'])) {
            $emailQuery = Contact::withTrashed()->where('email', strtolower(trim($data['email'])));
            if ($excludeId) {
                $emailQuery->where('id', '!=', $excludeId);
            }
            $result['email_duplicate'] = $emailQuery->first();
        }

        // Check phone duplicate (warning only)
        if (! empty($data['phone'])) {
            $phoneQuery = Contact::where('phone', $data['phone']);
            if ($excludeId) {
                $phoneQuery->where('id', '!=', $excludeId);
            }
            $phoneDuplicate = $phoneQuery->first();
            if ($phoneDuplicate) {
                $result['phone_duplicate'] = $phoneDuplicate;
                $result['phone_warning'] = true;
            }
        }

        return $result;
    }
}
