<?php

namespace App\Services\Receipt;

use App\Contracts\ReceiptParserInterface;
use App\DTO\ReceiptDraft;
use App\DTO\ReceiptParseRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OllamaReceiptParser implements ReceiptParserInterface
{
    public function __construct(
        private readonly ReceiptPayloadNormalizer $normalizer,
    ) {}

    public function parse(ReceiptParseRequest $request): ReceiptDraft
    {
        if ($request->image === null) {
            throw new InvalidArgumentException('Receipt image is required for Ollama parsing.');
        }

        $baseUrl = rtrim(config('inventory.ollama.base_url'), '/');
        $model = config('inventory.ollama.model');
        $prompt = config('inventory.receipt_prompt');
        $imageData = base64_encode(file_get_contents($request->image->getRealPath()));

        $response = Http::timeout(config('inventory.ollama.timeout'))
            ->post("{$baseUrl}/api/chat", [
                'model' => $model,
                'stream' => false,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                        'images' => [$imageData],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Ollama request failed: '.$response->body());
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        $data = $this->normalizer->normalize($content);

        return ReceiptDraft::fromArray($data, 'ollama');
    }
}
