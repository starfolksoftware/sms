<?php

namespace App\Observers;

use App\Events\{ContactCreated, ContactUpdated, ContactDeleted, ContactRestored};
use App\Models\Contact;

class ContactObserver
{
    public function created(Contact $contact): void { event(new ContactCreated($contact)); }
    public function updated(Contact $contact): void { event(new ContactUpdated($contact)); }
    public function deleted(Contact $contact): void { event(new ContactDeleted($contact)); }
    public function restored(Contact $contact): void { event(new ContactRestored($contact)); }

    public function restoring(Contact $contact): void
    {
        if ($contact->email) {
            $exists = Contact::query()
                ->whereNull('deleted_at')
                ->where('id', '!=', $contact->id)
                ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($contact->email))])
                ->exists();
            if ($exists) {
                abort(409, 'Cannot restore contact; another active contact has the same email.');
            }
        }
    }
}
