<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'name' => ['nullable', 'string', 'max:240'],
            'email' => [
                'nullable',
                'email:rfc',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    
                    $normalizedEmail = mb_strtolower(trim($value));
                    $exists = \App\Models\Contact::query()
                        ->whereNull('deleted_at')
                        ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                        ->exists();
                        
                    if ($exists) {
                        $fail('A contact with this email already exists.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:180'],
            'job_title' => ['nullable', 'string', 'max:180'],
            'status' => ['required', Rule::in(['lead', 'qualified', 'customer', 'archived'])],
            'source' => ['required', Rule::in(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'])],
            'source_meta' => ['nullable', 'array'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A contact with this email already exists.',
            'status.required' => 'Contact status is required.',
            'status.in' => 'Invalid contact status.',
            'source.required' => 'Contact source is required.',
            'source.in' => 'Invalid contact source.',
            'notes.max' => 'Notes cannot exceed 10,000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && $this->input('email')) {
            $this->merge([
                'email' => mb_strtolower(trim($this->input('email'))),
            ]);
        }
        
        if ($this->has('phone') && $this->input('phone')) {
            $this->merge([
                'phone' => trim($this->input('phone')),
            ]);
        }

        // Trim string fields
        foreach (['first_name', 'last_name', 'name', 'company', 'job_title', 'notes'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([
                    $field => trim($this->input($field)) ?: null,
                ]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('email') && $this->input('email')) {
                // Add machine-readable error code for API consumers
                if ($validator->errors()->has('email')) {
                    $validator->errors()->add('email', json_encode(['code' => 'duplicate_email']));
                }
            }
        });
    }
}
