<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('inventory'));
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
        ];
    }
}
