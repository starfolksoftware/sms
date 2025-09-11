<?php

namespace App\Events;

use App\Models\Contact;

class ContactCreated
{
    public function __construct(public Contact $contact) {}
}
