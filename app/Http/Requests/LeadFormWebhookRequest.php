<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadFormWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'submission_id' => ['nullable', 'string', 'max:255'],
            'consent' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Must have at least one of: email, phone, or a non-empty name
            $email = $this->input('email');
            $phone = $this->input('phone');
            $name = trim($this->input('name', ''));
            $firstName = trim($this->input('first_name', ''));
            $lastName = trim($this->input('last_name', ''));
            
            $hasName = !empty($name) || (!empty($firstName) || !empty($lastName));
            
            if (empty($email) && empty($phone) && !$hasName) {
                $validator->errors()->add('contact_info', 'At least one of email, phone, or name is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email address is too long.',
            'phone.max' => 'Phone number is too long.',
            'first_name.max' => 'First name is too long.',
            'last_name.max' => 'Last name is too long.',
            'name.max' => 'Name is too long.',
            'company.max' => 'Company name is too long.',
            'job_title.max' => 'Job title is too long.',
            'message.max' => 'Message is too long.',
            'notes.max' => 'Notes are too long.',
        ];
    }
}
