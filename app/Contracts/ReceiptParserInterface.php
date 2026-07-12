<?php

namespace App\Contracts;

use App\DTO\ReceiptDraft;
use App\DTO\ReceiptParseRequest;

interface ReceiptParserInterface
{
    public function parse(ReceiptParseRequest $request): ReceiptDraft;
}
