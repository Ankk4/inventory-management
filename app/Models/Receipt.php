<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'user_id',
        'inventory_id',
        'store_name',
        'purchased_at',
        'total',
        'currency',
        'image_path',
        'parser_source',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'total' => 'decimal:2',
            'raw_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptLine::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
