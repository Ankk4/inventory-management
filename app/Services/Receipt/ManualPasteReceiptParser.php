<?php

namespace App\Services\Receipt;

use App\Contracts\ReceiptParserInterface;
use App\DTO\ReceiptDraft;
use App\DTO\ReceiptParseRequest;
use InvalidArgumentException;

class ManualPasteReceiptParser implements ReceiptParserInterface
{
    public function __construct(
        private readonly ReceiptPayloadNormalizer $normalizer,
    ) {}

    public function parse(ReceiptParseRequest $request): ReceiptDraft
    {
        if ($request->pastedText === null || trim($request->pastedText) === '') {
            throw new InvalidArgumentException('Pasted receipt JSON is required.');
        }

        $data = $this->normalizer->normalize($request->pastedText);

        return ReceiptDraft::fromArray($data, 'manual_paste');
    }
}
