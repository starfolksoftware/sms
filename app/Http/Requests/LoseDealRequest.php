<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoseDealRequest extends FormRequest
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
            'lost_reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lost_reason.required' => 'Lost reason is required when marking a deal as lost.',
            'lost_reason.string' => 'Lost reason must be a valid text description.',
            'lost_reason.max' => 'Lost reason cannot be longer than 500 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'lost_reason' => trim($this->lost_reason ?? ''),
            'notes' => trim($this->notes ?? ''),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $deal = $this->route('deal');

            // Prevent losing an already closed deal
            if ($deal && $deal->isClosed()) {
                $validator->errors()->add('status', 'Cannot mark as lost - deal is already closed.');
            }
        });
    }
}
