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
        private readonly ReceiptImageEncoder $imageEncoder,
    ) {}

    public function parse(ReceiptParseRequest $request): ReceiptDraft
    {
        if ($request->image === null) {
            throw new InvalidArgumentException('Receipt image is required for Ollama parsing.');
        }

        $baseUrl = rtrim(config('inventory.ollama.base_url'), '/');
        $model = config('inventory.ollama.model');
        $prompt = config('inventory.receipt_prompt');
        $imageData = $this->imageEncoder->toBase64($request->image->getRealPath());

        $response = Http::timeout(config('inventory.ollama.timeout'))
            ->post("{$baseUrl}/api/chat", [
                'model' => $model,
                'stream' => false,
                'format' => 'json',
                'think' => false,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                        'images' => [$imageData],
                    ],
                ],
                'options' => [
                    'temperature' => 0,
                    'num_ctx' => config('inventory.ollama.num_ctx'),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->formatOllamaError($response->body()));
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        $data = $this->normalizer->normalize($content);

        return ReceiptDraft::fromArray($data, 'ollama');
    }

    private function formatOllamaError(string $body): string
    {
        $payload = json_decode($body, true);

        if (is_array($payload) && is_string($payload['error'] ?? null)) {
            $nested = json_decode($payload['error'], true);
            $payload = is_array($nested) ? $nested : $payload;
        }

        $error = is_array($payload) ? ($payload['error'] ?? $payload) : null;

        if (is_array($error) && ($error['type'] ?? null) === 'exceed_context_size_error') {
            $used = $error['n_prompt_tokens'] ?? '?';
            $limit = $error['n_ctx'] ?? '?';

            return "Ollama request failed: the image used {$used} tokens but the context window is {$limit}. Increase OLLAMA_NUM_CTX or lower OLLAMA_MAX_IMAGE_EDGE.";
        }

        if (is_array($error) && is_string($error['message'] ?? null)) {
            return 'Ollama request failed: '.$error['message'];
        }

        return 'Ollama request failed: '.$body;
    }
}
