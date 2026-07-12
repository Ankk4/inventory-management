<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const REASON_RECEIPT_IMPORT = 'receipt_import';

    public const REASON_MANUAL_ADJUSTMENT = 'manual_adjustment';

    public const REASON_CONSUMED = 'consumed';

    protected $fillable = [
        'item_id',
        'receipt_id',
        'user_id',
        'delta',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'decimal:3',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
