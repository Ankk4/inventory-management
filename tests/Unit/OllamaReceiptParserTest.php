<?php

namespace Tests\Unit;

use App\DTO\ReceiptParseRequest;
use App\Services\Receipt\OllamaReceiptParser;
use App\Services\Receipt\ReceiptPayloadNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaReceiptParserTest extends TestCase
{
    public function test_sends_json_format_and_parses_receipt_lines(): void
    {
        config([
            'inventory.ollama.base_url' => 'http://127.0.0.1:11434',
            'inventory.ollama.model' => 'qwen3-vl:8b-instruct',
            'inventory.ollama.timeout' => 180,
            'inventory.ollama.num_ctx' => 4096,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'store_name' => 'Test Mart',
                        'purchase_date' => '2026-08-22',
                        'currency' => 'EUR',
                        'total' => 2.5,
                        'lines' => [
                            [
                                'raw_name' => 'Milk 1L',
                                'quantity' => 2,
                                'unit' => 'pcs',
                                'unit_price' => 1.25,
                                'line_total' => 2.5,
                            ],
                        ],
                    ]),
                ],
            ]),
        ]);

        $imagePath = tempnam(sys_get_temp_dir(), 'receipt');
        // Minimal 1x1 JPEG so the test does not require the GD extension.
        file_put_contents($imagePath, base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGcP//Z'
        ));
        $image = new UploadedFile($imagePath, 'receipt.jpg', 'image/jpeg', null, true);
        $parser = new OllamaReceiptParser(new ReceiptPayloadNormalizer);
        $draft = $parser->parse(new ReceiptParseRequest(image: $image));

        $this->assertSame('ollama', $draft->parserSource);
        $this->assertSame('Test Mart', $draft->storeName);
        $this->assertCount(1, $draft->lines);
        $this->assertSame('Milk 1L', $draft->lines[0]->rawName);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'http://127.0.0.1:11434/api/chat'
                && $payload['model'] === 'qwen3-vl:8b-instruct'
                && $payload['format'] === 'json'
                && $payload['think'] === false
                && ($payload['options']['temperature'] ?? null) === 0
                && ($payload['options']['num_ctx'] ?? null) === 4096
                && isset($payload['messages'][0]['images'][0]);
        });
    }
}
