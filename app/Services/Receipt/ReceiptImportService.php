<?php

namespace App\Services\Receipt;

use App\DTO\ReceiptDraft;
use App\Models\Item;
use App\Models\Receipt;
use App\Models\ReceiptLine;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceiptImportService
{
    public function storeDraftInSession(ReceiptDraft $draft, ?string $imagePath = null): array
    {
        return [
            'draft' => $draft->toArray(),
            'image_path' => $imagePath,
        ];
    }

    public function confirm(User $user, array $payload): Receipt
    {
        return DB::transaction(function () use ($user, $payload) {
            $receipt = Receipt::create([
                'user_id' => $user->id,
                'store_name' => $payload['store_name'] ?? null,
                'purchased_at' => $payload['purchase_date'] ?? null,
                'total' => $payload['total'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'image_path' => $payload['image_path'] ?? null,
                'parser_source' => $payload['parser_source'] ?? 'manual_paste',
                'raw_payload' => $payload['raw_payload'] ?? $payload,
            ]);

            foreach ($payload['lines'] as $lineData) {
                if (! empty($lineData['skipped'])) {
                    ReceiptLine::create([
                        'receipt_id' => $receipt->id,
                        'raw_name' => $lineData['raw_name'],
                        'quantity' => $lineData['quantity'] ?? 1,
                        'unit' => $lineData['unit'] ?? null,
                        'unit_price' => $lineData['unit_price'] ?? null,
                        'line_total' => $lineData['line_total'] ?? null,
                        'skipped' => true,
                    ]);

                    continue;
                }

                $item = $this->resolveItem($lineData);

                ReceiptLine::create([
                    'receipt_id' => $receipt->id,
                    'matched_item_id' => $item->id,
                    'raw_name' => $lineData['raw_name'],
                    'quantity' => $lineData['quantity'] ?? 1,
                    'unit' => $lineData['unit'] ?? $item->unit,
                    'unit_price' => $lineData['unit_price'] ?? null,
                    'line_total' => $lineData['line_total'] ?? null,
                    'skipped' => false,
                ]);

                $delta = (float) ($lineData['quantity'] ?? 1);
                $item->increment('quantity', $delta);

                StockMovement::create([
                    'item_id' => $item->id,
                    'receipt_id' => $receipt->id,
                    'user_id' => $user->id,
                    'delta' => $delta,
                    'reason' => StockMovement::REASON_RECEIPT_IMPORT,
                    'notes' => 'Imported from receipt #'.$receipt->id,
                ]);
            }

            return $receipt->load(['lines.matchedItem', 'stockMovements.item']);
        });
    }

    public function storeImage($file): string
    {
        return $file->store('receipts', 'local');
    }

    public function imageUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return route('receipts.image', ['path' => $path]);
    }

    private function resolveItem(array $lineData): Item
    {
        if (! empty($lineData['matched_item_id'])) {
            return Item::findOrFail($lineData['matched_item_id']);
        }

        if (! empty($lineData['create_item'])) {
            $name = trim($lineData['new_item_name'] ?? $lineData['raw_name']);

            return Item::create([
                'name' => $name,
                'normalized_name' => Item::normalizeName($name),
                'quantity' => 0,
                'unit' => $lineData['unit'] ?? 'pcs',
                'category_id' => $lineData['category_id'] ?? null,
            ]);
        }

        throw new \InvalidArgumentException('Each line must match an item or create a new one.');
    }
}
