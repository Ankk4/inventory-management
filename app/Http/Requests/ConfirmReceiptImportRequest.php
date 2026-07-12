<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmReceiptImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:3'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'parser_source' => ['required', 'string', 'max:50'],
            'image_path' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.raw_name' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit' => ['nullable', 'string', 'max:50'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'lines.*.skipped' => ['sometimes', 'boolean'],
            'lines.*.matched_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.create_item' => ['sometimes', 'boolean'],
            'lines.*.new_item_name' => ['nullable', 'string', 'max:255'],
            'lines.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
