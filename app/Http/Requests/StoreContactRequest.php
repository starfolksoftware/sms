<?php

namespace App\Http\Requests;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:contacts,email,NULL,id,deleted_at,NULL',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::enum(ContactStatus::class)],
            'source' => ['nullable', Rule::enum(ContactSource::class)],
            'source_meta' => 'nullable|array',
            'notes' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A contact with this email already exists.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'status.enum' => 'The selected status is invalid.',
            'source.enum' => 'The selected source is invalid.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase
        if ($this->has('email') && $this->email) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        // Set default values
        $this->merge([
            'status' => $this->status ?? ContactStatus::Lead->value,
            'source' => $this->source ?? ContactSource::Manual->value,
        ]);
    }
}
