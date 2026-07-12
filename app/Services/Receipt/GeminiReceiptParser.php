<?php

namespace App\Services\Receipt;

use App\Contracts\ReceiptParserInterface;
use App\DTO\ReceiptDraft;
use App\DTO\ReceiptParseRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class GeminiReceiptParser implements ReceiptParserInterface
{
    public function __construct(
        private readonly ReceiptPayloadNormalizer $normalizer,
    ) {}

    public function parse(ReceiptParseRequest $request): ReceiptDraft
    {
        if (! config('inventory.gemini.enabled')) {
            throw new RuntimeException('Gemini parsing is disabled. Enable GEMINI_ENABLED in .env or use manual paste.');
        }

        $apiKey = config('inventory.gemini.api_key');

        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        if ($request->image === null) {
            throw new InvalidArgumentException('Receipt image is required for Gemini parsing.');
        }

        $this->guardDailyLimit();

        $model = config('inventory.gemini.model');
        $prompt = config('inventory.receipt_prompt');
        $mimeType = $request->image->getMimeType() ?: 'image/jpeg';
        $imageData = base64_encode(file_get_contents($request->image->getRealPath()));

        $response = Http::timeout(120)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                    ],
                ],
            );

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request failed: '.$response->body());
        }

        $this->incrementDailyCounter();

        $content = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $data = $this->normalizer->normalize($content);

        return ReceiptDraft::fromArray($data, 'gemini');
    }

    public function dailyUsage(): int
    {
        return (int) Cache::get($this->cacheKey(), 0);
    }

    public function dailyLimit(): int
    {
        return config('inventory.gemini.daily_limit');
    }

    private function guardDailyLimit(): void
    {
        if ($this->dailyUsage() >= $this->dailyLimit()) {
            throw new RuntimeException('Gemini daily free-tier limit reached. Use manual paste instead.');
        }
    }

    private function incrementDailyCounter(): void
    {
        $key = $this->cacheKey();
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->endOfDay());
    }

    private function cacheKey(): string
    {
        return 'gemini_receipt_requests_'.now()->toDateString();
    }
}
