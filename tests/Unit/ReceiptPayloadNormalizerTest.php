<?php

namespace Tests\Unit;

use App\Services\Receipt\ReceiptPayloadNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReceiptPayloadNormalizerTest extends TestCase
{
    public function test_normalizes_plain_json(): void
    {
        $normalizer = new ReceiptPayloadNormalizer;

        $result = $normalizer->normalize('{"store_name":"Shop","lines":[{"raw_name":"Eggs","quantity":1}]}');

        $this->assertSame('Shop', $result['store_name']);
        $this->assertCount(1, $result['lines']);
    }

    public function test_extracts_json_from_markdown_fence(): void
    {
        $normalizer = new ReceiptPayloadNormalizer;

        $result = $normalizer->normalize(<<<'TEXT'
Here is the receipt:
```json
{"store_name":"Shop","lines":[{"raw_name":"Eggs","quantity":1}]}
```
TEXT);

        $this->assertSame('Shop', $result['store_name']);
    }

    public function test_rejects_missing_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ReceiptPayloadNormalizer)->normalize('{"store_name":"Shop"}');
    }
}
