<?php

namespace App\Events;

use App\Models\Contact;

class ContactDeleted
{
    public function __construct(public Contact $contact) {}
}
