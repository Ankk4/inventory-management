<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseReceiptImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inventory'));
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:10240'],
        ];
    }
}
