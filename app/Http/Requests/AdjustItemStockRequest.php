<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustItemStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    public function rules(): array
    {
        return [
            'delta' => ['required', 'numeric'],
            'reason' => ['required', 'in:manual_adjustment,consumed'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
