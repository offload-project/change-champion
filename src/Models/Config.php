<?php

declare(strict_types=1);

namespace ChangeChampion\Models;

class Config
{
    public function __construct(
        public readonly string $baseBranch = 'main',
        public readonly bool $changelog = true,
        public readonly ?string $repository = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            baseBranch: $data['baseBranch'] ?? 'main',
            changelog: $data['changelog'] ?? true,
            repository: $data['repository'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'baseBranch' => $this->baseBranch,
            'changelog' => $this->changelog,
            'repository' => $this->repository,
        ];
    }
}
