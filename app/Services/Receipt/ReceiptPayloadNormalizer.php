<?php

namespace App\Services\Receipt;

use InvalidArgumentException;

class ReceiptPayloadNormalizer
{
    public function normalize(string $input): array
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Receipt data cannot be empty.');
        }

        $json = $this->extractJson($trimmed);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('Receipt data must be valid JSON.');
        }

        if (! isset($data['lines']) || ! is_array($data['lines'])) {
            throw new InvalidArgumentException('Receipt JSON must include a lines array.');
        }

        return $data;
    }

    private function extractJson(string $input): string
    {
        if (str_starts_with($input, '{')) {
            return $input;
        }

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(\{.*\})/s', $input, $matches)) {
            return $matches[1];
        }

        throw new InvalidArgumentException('Could not find JSON in pasted receipt data.');
    }
}
