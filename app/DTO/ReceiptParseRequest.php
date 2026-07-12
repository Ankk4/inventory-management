<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

readonly class ReceiptParseRequest
{
    public function __construct(
        public ?string $pastedText = null,
        public ?UploadedFile $image = null,
    ) {}
}
