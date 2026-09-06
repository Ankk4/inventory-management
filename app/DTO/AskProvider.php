<?php

namespace App\DTO;

final readonly class AskProvider
{
    public function __construct(
        public string $vendor,
        public string $model,
        public bool $isLocal,
    ) {}

    public function locationLabel(): string
    {
        return $this->isLocal ? 'Local' : 'Cloud';
    }

    public function privacyNote(): string
    {
        return $this->isLocal ? 'Stays on this machine' : 'Sent to Google';
    }

    public function answerLabel(): string
    {
        return "Answer from {$this->vendor}";
    }
}
