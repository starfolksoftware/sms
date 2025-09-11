<?php

namespace App\Events;

use App\Models\Contact;

class ContactRestored
{
    public function __construct(public Contact $contact) {}
}
