<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WinDealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_deals');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'won_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'won_amount.numeric' => 'Won amount must be a valid number.',
            'won_amount.min' => 'Won amount cannot be negative.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $deal = $this->route('deal');

            // Prevent winning an already closed deal
            if ($deal && $deal->isClosed()) {
                $validator->errors()->add('status', 'Cannot mark as won - deal is already closed.');
            }
        });
    }
}
