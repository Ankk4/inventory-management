<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptPasteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inventory'));
    }

    public function rules(): array
    {
        return [
            'pasted_text' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
