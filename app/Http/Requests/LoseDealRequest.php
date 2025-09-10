<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoseDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('lose', $this->route('deal'));
    }

    public function rules(): array
    {
        return [
            'lost_reason' => ['required', 'string', 'min:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'lost_reason.required' => 'A reason for losing the deal is required.',
            'lost_reason.string' => 'The lost reason must be text.',
            'lost_reason.min' => 'The lost reason must be at least 3 characters.',
        ];
    }
}