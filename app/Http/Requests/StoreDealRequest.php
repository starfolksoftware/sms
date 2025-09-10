<?php

namespace App\Http\Requests;

use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Deal::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:240'],
            'description' => ['nullable', 'string'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stage' => ['required', Rule::in(['new', 'qualified', 'proposal', 'negotiation', 'closed'])],
            'status' => ['in:open,won,lost'], // typically 'open' on create
            'expected_close_date' => ['nullable', 'date'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string'],
            'source' => ['required', Rule::in(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'])],
            'source_meta' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The deal title is required.',
            'title.max' => 'The deal title must not exceed 240 characters.',
            'contact_id.required' => 'A contact must be selected for this deal.',
            'contact_id.exists' => 'The selected contact is invalid.',
            'product_id.exists' => 'The selected product is invalid.',
            'owner_id.exists' => 'The selected owner is invalid.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be greater than or equal to 0.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-letter code.',
            'stage.required' => 'A deal stage is required.',
            'stage.in' => 'Invalid deal stage selected.',
            'status.in' => 'Invalid deal status.',
            'expected_close_date.date' => 'Expected close date must be a valid date.',
            'probability.integer' => 'Probability must be a whole number.',
            'probability.between' => 'Probability must be between 0 and 100.',
            'source.required' => 'Deal source is required.',
            'source.in' => 'Invalid deal source selected.',
            'source_meta.array' => 'Source meta must be valid data.',
        ];
    }
}