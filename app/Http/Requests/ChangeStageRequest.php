<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStageRequest extends FormRequest
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
            'stage' => 'required|string|max:50',
            'probability' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'stage.required' => 'Stage is required for stage transition.',
            'stage.string' => 'Stage must be a valid string.',
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
            'stage' => trim($this->stage ?? ''),
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

            // Prevent changing stage on closed deals
            if ($deal && $deal->isClosed()) {
                $validator->errors()->add('stage', 'Cannot change stage on a deal that is already won or lost.');
            }
        });
    }
}
