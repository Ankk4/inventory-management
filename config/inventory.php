<?php

return [
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        // 8b-instruct, not :8b — the short tag is the thinking variant.
        'model' => env('OLLAMA_MODEL', 'qwen3-vl:8b-instruct'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 180),
        'num_ctx' => (int) env('OLLAMA_NUM_CTX', 8192),
        // Square-equivalent pixel budget. Tall receipts keep width; 12MP photos shrink.
        'max_image_edge' => (int) env('OLLAMA_MAX_IMAGE_EDGE', 2048),
    ],

    'gemini' => [
        'enabled' => (bool) env('GEMINI_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'daily_limit' => (int) env('GEMINI_DAILY_LIMIT', 50),
    ],

    'receipt_prompt' => <<<'PROMPT'
Analyze this receipt, shopping list, or handwritten list image. Return ONLY valid JSON matching this schema:
{"store_name":"string|null","purchase_date":"YYYY-MM-DD|null","currency":"string|null","total":number|null,"lines":[{"raw_name":"string","quantity":number,"unit":"string|null","unit_price":number|null,"line_total":number|null}]}
Extract every product or item as a line. If quantity is missing, use 1. Store name, date, currency, prices, and total may be null for handwritten lists. Do not round prices. If a field is unclear, use null. No markdown fences or extra text.
PROMPT,
];
