<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InventoryAskService
{
    public function serializeInventory(Inventory $inventory): string
    {
        $items = $inventory->items()
            ->with('category')
            ->orderBy('name')
            ->get();

        if ($items->isEmpty()) {
            return '(This inventory is empty.)';
        }

        $grouped = $items->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized');

        $lines = [];

        foreach ($grouped as $category => $categoryItems) {
            $lines[] = $category.':';

            foreach ($categoryItems as $item) {
                $lines[] = sprintf(
                    '- %s — %s %s',
                    $item->name,
                    rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.'),
                    $item->unit,
                );
            }
        }

        return implode("\n", $lines);
    }

    public function ask(Inventory $inventory, string $question): string
    {
        $inventoryContext = $this->serializeInventory($inventory);
        $prompt = $this->buildPrompt($question, $inventoryContext);

        if (config('inventory.gemini.enabled') && config('inventory.gemini.api_key')) {
            return $this->askGemini($prompt);
        }

        return $this->askOllama($prompt);
    }

    public function buildPrompt(string $question, string $inventoryContext): string
    {
        return <<<PROMPT
You are a helpful pantry assistant. Answer the user's question using only the inventory context below. If the inventory does not contain enough information, say so clearly. Be concise.

Inventory:
{$inventoryContext}

Question:
{$question}
PROMPT;
    }

    private function askOllama(string $prompt): string
    {
        $baseUrl = rtrim(config('inventory.ollama.base_url'), '/');
        $model = config('inventory.ollama.model');

        $response = Http::timeout(config('inventory.ollama.timeout'))
            ->post("{$baseUrl}/api/chat", [
                'model' => $model,
                'stream' => false,
                'think' => false,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'options' => [
                    'temperature' => 0.2,
                    'num_ctx' => config('inventory.ollama.num_ctx'),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Ollama request failed: '.$response->body());
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        return trim($content);
    }

    private function askGemini(string $prompt): string
    {
        $apiKey = config('inventory.gemini.api_key');
        $model = config('inventory.gemini.model');

        $response = Http::timeout(120)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                    ],
                ],
            );

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request failed: '.$response->body());
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return trim($content);
    }
}
