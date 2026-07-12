<?php

namespace App\DTO;

readonly class ReceiptDraftLine
{
    public function __construct(
        public string $rawName,
        public float $quantity,
        public ?string $unit,
        public ?float $unitPrice,
        public ?float $lineTotal,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            rawName: (string) ($data['raw_name'] ?? ''),
            quantity: (float) ($data['quantity'] ?? 1),
            unit: isset($data['unit']) ? (string) $data['unit'] : null,
            unitPrice: isset($data['unit_price']) ? (float) $data['unit_price'] : null,
            lineTotal: isset($data['line_total']) ? (float) $data['line_total'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'raw_name' => $this->rawName,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
        ];
    }
}
