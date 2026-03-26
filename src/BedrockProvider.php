<?php

namespace AgaveApi\LaravelAiBedrock;

use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\Concerns;
use Laravel\Ai\Providers\Provider;

class BedrockProvider extends Provider implements EmbeddingProvider, TextProvider
{
    use Concerns\GeneratesEmbeddings;
    use Concerns\GeneratesText;
    use Concerns\HasEmbeddingGateway;
    use Concerns\HasTextGateway;
    use Concerns\StreamsText;

    public function providerCredentials(): array
    {
        return [
            'key' => $this->config['access_key'] ?? null,
        ];
    }

    public function additionalConfiguration(): array
    {
        return array_filter([
            'api_secret' => $this->config['secret_key'] ?? null,
            'session_token' => $this->config['session_token'] ?? null,
            'region' => $this->config['region'] ?? 'us-east-1',
            'use_default_credential_provider' => $this->useDefaultCredentialProvider(),
        ]);
    }

    public function defaultTextModel(): string
    {
        return $this->config['models']['text']['default'] ?? 'anthropic.claude-sonnet-4-5-20250929-v1:0';
    }

    public function cheapestTextModel(): string
    {
        return $this->config['models']['text']['cheapest'] ?? 'anthropic.claude-haiku-4-5-20251001-v1:0';
    }

    public function smartestTextModel(): string
    {
        return $this->config['models']['text']['smartest'] ?? 'anthropic.claude-opus-4-6-v1:0';
    }

    public function defaultEmbeddingsModel(): string
    {
        return $this->config['models']['embeddings']['default'] ?? 'amazon.titan-embed-text-v2:0';
    }

    public function defaultEmbeddingsDimensions(): int
    {
        return $this->config['models']['embeddings']['dimensions'] ?? 1024;
    }

    public function defaultMaxTokens(): int
    {
        return $this->config['max_tokens'] ?? 16_384;
    }

    protected function useDefaultCredentialProvider(): bool
    {
        return empty($this->config['access_key']) && empty($this->config['secret_key']);
    }
}
