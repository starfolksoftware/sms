<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WinDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('win', $this->route('deal'));
    }

    public function rules(): array
    {
        return [
            'won_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'won_amount.numeric' => 'The won amount must be a valid number.',
            'won_amount.min' => 'The won amount must be greater than or equal to 0.',
        ];
    }
}