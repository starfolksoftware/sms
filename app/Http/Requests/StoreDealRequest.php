<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'stage' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['open', 'won', 'lost'])],
            'expected_close_date' => ['nullable', 'date', 'after_or_equal:today'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'lost_reason' => ['nullable', 'string', 'max:1000'],
            'won_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'deal_stage_id' => ['nullable', 'exists:deal_stages,id'],
            'source' => ['required', Rule::in(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'])],
            'source_meta' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Deal title is required.',
            'title.max' => 'Deal title cannot exceed 255 characters.',
            'contact_id.required' => 'Contact is required.',
            'contact_id.exists' => 'Selected contact does not exist.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'amount.max' => 'Amount cannot exceed 999,999,999.99.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'status.required' => 'Deal status is required.',
            'status.in' => 'Invalid deal status.',
            'expected_close_date.after_or_equal' => 'Expected close date cannot be in the past.',
            'probability.min' => 'Probability must be between 0 and 100.',
            'probability.max' => 'Probability must be between 0 and 100.',
            'lost_reason.max' => 'Lost reason cannot exceed 1,000 characters.',
            'won_amount.min' => 'Won amount must be greater than or equal to 0.',
            'won_amount.max' => 'Won amount cannot exceed 999,999,999.99.',
            'source.required' => 'Deal source is required.',
            'source.in' => 'Invalid deal source.',
            'notes.max' => 'Notes cannot exceed 10,000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Trim string fields
        foreach (['title', 'stage', 'lost_reason', 'notes'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([
                    $field => trim($this->input($field)) ?: null,
                ]);
            }
        }

        // Normalize currency to uppercase
        if ($this->has('currency') && $this->input('currency')) {
            $this->merge([
                'currency' => strtoupper(trim($this->input('currency'))),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate lost reason if status is lost
            if ($this->input('status') === 'lost' && !$this->input('lost_reason')) {
                $validator->errors()->add('lost_reason', 'Lost reason is required when deal status is lost.');
            }

            // Validate won amount makes sense
            if ($this->input('status') === 'won' && $this->input('won_amount') && $this->input('amount')) {
                if ($this->input('won_amount') > ($this->input('amount') * 2)) {
                    $validator->errors()->add('won_amount', 'Won amount seems unusually high compared to original amount.');
                }
            }
        });
    }
}
