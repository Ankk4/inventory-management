<?php

return [
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2-vision:11b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    ],

    'gemini' => [
        'enabled' => (bool) env('GEMINI_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'daily_limit' => (int) env('GEMINI_DAILY_LIMIT', 50),
    ],

    'receipt_prompt' => <<<'PROMPT'
Analyze this receipt image. Return ONLY valid JSON matching this schema:
{"store_name":"string|null","purchase_date":"YYYY-MM-DD|null","currency":"string|null","total":number|null,"lines":[{"raw_name":"string","quantity":number,"unit":"string|null","unit_price":number|null,"line_total":number|null}]}
Do not round prices. If a field is unclear, use null. No markdown fences or extra text.
PROMPT,
];
