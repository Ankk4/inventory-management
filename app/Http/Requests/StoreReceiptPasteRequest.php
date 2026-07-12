<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptPasteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pasted_text' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
