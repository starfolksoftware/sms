<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Contact::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'name' => ['nullable', 'string', 'max:240'],
            'email' => [
                'nullable', 
                'email', 
                'max:255',
                Rule::unique('contacts', 'email_normalized')
                    ->where(fn($query) => $query) // MySQL: cannot exclude soft-deleted at DB; app-level dup logic in controller
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:180'],
            'job_title' => ['nullable', 'string', 'max:180'],
            'status' => ['required', Rule::in(['lead', 'qualified', 'customer', 'archived'])],
            'source' => ['required', Rule::in(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'])],
            'source_meta' => ['nullable', 'array'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email ? mb_strtolower(trim($this->email)) : null,
            'status' => $this->status ?? 'lead',
            'source' => $this->source ?? 'manual',
        ]);
    }
}
