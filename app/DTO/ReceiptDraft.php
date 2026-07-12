<?php

namespace App\DTO;

readonly class ReceiptDraft
{
    /**
     * @param  ReceiptDraftLine[]  $lines
     */
    public function __construct(
        public ?string $storeName,
        public ?string $purchaseDate,
        public ?string $currency,
        public ?float $total,
        public array $lines,
        public string $parserSource,
        public array $rawPayload = [],
    ) {}

    public static function fromArray(array $data, string $parserSource): self
    {
        $lines = array_map(
            fn (array $line) => ReceiptDraftLine::fromArray($line),
            $data['lines'] ?? [],
        );

        return new self(
            storeName: $data['store_name'] ?? null,
            purchaseDate: $data['purchase_date'] ?? null,
            currency: $data['currency'] ?? null,
            total: isset($data['total']) ? (float) $data['total'] : null,
            lines: $lines,
            parserSource: $parserSource,
            rawPayload: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'store_name' => $this->storeName,
            'purchase_date' => $this->purchaseDate,
            'currency' => $this->currency,
            'total' => $this->total,
            'lines' => array_map(fn (ReceiptDraftLine $line) => $line->toArray(), $this->lines),
            'parser_source' => $this->parserSource,
        ];
    }
}
