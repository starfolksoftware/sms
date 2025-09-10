<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStage', $this->route('deal'));
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::in(['new', 'qualified', 'proposal', 'negotiation', 'closed'])],
        ];
    }

    public function messages(): array
    {
        return [
            'stage.required' => 'A deal stage is required.',
            'stage.in' => 'Invalid deal stage selected.',
        ];
    }
}