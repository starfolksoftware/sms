<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDealRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'value' => 'required|numeric|min:0', // Legacy field, still required in DB
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'contact_id' => 'required|exists:contacts,id',
            'product_id' => 'nullable|exists:products,id',
            'owner_id' => 'nullable|exists:users,id',
            'expected_close_date' => 'nullable|date|after:today',
            'probability' => 'nullable|integer|min:0|max:100',
            'source' => 'nullable|string|max:100',
            'source_meta' => 'nullable|array',
            'notes' => 'nullable|string|max:10000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Deal title is required.',
            'value.required' => 'Deal value is required.',
            'value.numeric' => 'Deal value must be a valid number.',
            'value.min' => 'Deal value cannot be negative.',
            'amount.numeric' => 'Deal amount must be a valid number.',
            'amount.min' => 'Deal amount cannot be negative.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-character ISO code (e.g., USD).',
            'contact_id.required' => 'A contact must be selected for this deal.',
            'contact_id.exists' => 'The selected contact does not exist.',
            'product_id.exists' => 'The selected product does not exist.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'expected_close_date.after' => 'Expected close date must be in the future.',
            'probability.integer' => 'Probability must be an integer.',
            'probability.min' => 'Probability cannot be less than 0%.',
            'probability.max' => 'Probability cannot be greater than 100%.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim($this->title ?? ''),
            'description' => trim($this->description ?? ''),
            'notes' => trim($this->notes ?? ''),
            'source' => trim($this->source ?? ''),
            'currency' => strtoupper(trim($this->currency ?? '')),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $deal = $this->route('deal');

            // Prevent updating closed deals unless they're being restored
            if ($deal && $deal->isClosed()) {
                $validator->errors()->add('status', 'Cannot update a deal that is already won or lost. Restore the deal first if needed.');
            }
        });
    }
}
