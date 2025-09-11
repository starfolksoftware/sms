<?php

namespace App\Events;

use App\Models\Contact;

class ContactUpdated
{
    public function __construct(public Contact $contact) {}
}
