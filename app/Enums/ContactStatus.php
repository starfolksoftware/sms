<?php

namespace App\Enums;

enum ContactStatus: string
{
    case Lead = 'lead';
    case Qualified = 'qualified';
    case Customer = 'customer';
    case Archived = 'archived';
}