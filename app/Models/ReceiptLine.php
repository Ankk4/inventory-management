<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptLine extends Model
{
    protected $fillable = [
        'receipt_id',
        'matched_item_id',
        'raw_name',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'skipped',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'skipped' => 'boolean',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function matchedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'matched_item_id');
    }
}
