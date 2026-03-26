<?php

namespace AgaveApi\LaravelAiBedrock\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Orchestra\Testbench\TestCase;
use AgaveApi\LaravelAiBedrock\BedrockPrismGateway;
use AgaveApi\LaravelAiBedrock\BedrockProvider;

class BedrockProviderTest extends TestCase
{
    private function makeProvider(array $config = []): BedrockProvider
    {
        $config = array_merge([
            'driver' => 'bedrock',
            'name' => 'bedrock',
            'access_key' => 'test-key',
            'secret_key' => 'test-secret',
            'session_token' => 'test-token',
            'region' => 'us-west-2',
            'models' => [
                'text' => [
                    'default' => 'anthropic.claude-sonnet-4-5-20250929-v1:0',
                    'cheapest' => 'anthropic.claude-haiku-4-5-20251001-v1:0',
                    'smartest' => 'anthropic.claude-opus-4-6-v1:0',
                ],
                'embeddings' => [
                    'default' => 'amazon.titan-embed-text-v2:0',
                    'dimensions' => 1024,
                ],
            ],
        ], $config);

        return new BedrockProvider(
            new BedrockPrismGateway($this->app['events']),
            $config,
            $this->app->make(Dispatcher::class),
        );
    }

    public function test_provider_credentials_returns_access_key(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals(['key' => 'test-key'], $provider->providerCredentials());
    }

    public function test_provider_credentials_returns_null_when_no_access_key(): void
    {
        $provider = $this->makeProvider(['access_key' => null]);

        $this->assertEquals(['key' => null], $provider->providerCredentials());
    }

    public function test_additional_configuration_includes_aws_credentials(): void
    {
        $provider = $this->makeProvider();
        $config = $provider->additionalConfiguration();

        $this->assertArrayNotHasKey('api_key', $config);
        $this->assertEquals('test-secret', $config['api_secret']);
        $this->assertEquals('test-token', $config['session_token']);
        $this->assertEquals('us-west-2', $config['region']);
        $this->assertArrayNotHasKey('use_default_credential_provider', $config);
    }

    public function test_uses_default_credential_provider_when_no_keys(): void
    {
        $provider = $this->makeProvider([
            'access_key' => null,
            'secret_key' => null,
        ]);

        $config = $provider->additionalConfiguration();

        $this->assertTrue($config['use_default_credential_provider']);
        $this->assertArrayNotHasKey('api_key', $config);
        $this->assertArrayNotHasKey('api_secret', $config);
    }

    public function test_default_text_model_from_config(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals('anthropic.claude-sonnet-4-5-20250929-v1:0', $provider->defaultTextModel());
    }

    public function test_default_text_model_fallback(): void
    {
        $provider = $this->makeProvider(['models' => []]);

        $this->assertEquals('anthropic.claude-sonnet-4-5-20250929-v1:0', $provider->defaultTextModel());
    }

    public function test_cheapest_text_model_from_config(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals('anthropic.claude-haiku-4-5-20251001-v1:0', $provider->cheapestTextModel());
    }

    public function test_smartest_text_model_from_config(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals('anthropic.claude-opus-4-6-v1:0', $provider->smartestTextModel());
    }

    public function test_default_embeddings_model_from_config(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals('amazon.titan-embed-text-v2:0', $provider->defaultEmbeddingsModel());
    }

    public function test_default_embeddings_dimensions_from_config(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals(1024, $provider->defaultEmbeddingsDimensions());
    }

    public function test_default_embeddings_dimensions_fallback(): void
    {
        $provider = $this->makeProvider(['models' => []]);

        $this->assertEquals(1024, $provider->defaultEmbeddingsDimensions());
    }

    public function test_driver_returns_bedrock(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals('bedrock', $provider->driver());
    }

    public function test_name_returns_bedrock(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals('bedrock', $provider->name());
    }

    public function test_custom_model_config(): void
    {
        $provider = $this->makeProvider([
            'models' => [
                'text' => [
                    'default' => 'custom.model-v1',
                    'cheapest' => 'custom.cheap-v1',
                    'smartest' => 'custom.smart-v1',
                ],
                'embeddings' => [
                    'default' => 'custom.embed-v1',
                    'dimensions' => 512,
                ],
            ],
        ]);

        $this->assertEquals('custom.model-v1', $provider->defaultTextModel());
        $this->assertEquals('custom.cheap-v1', $provider->cheapestTextModel());
        $this->assertEquals('custom.smart-v1', $provider->smartestTextModel());
        $this->assertEquals('custom.embed-v1', $provider->defaultEmbeddingsModel());
        $this->assertEquals(512, $provider->defaultEmbeddingsDimensions());
    }

    public function test_default_region_fallback(): void
    {
        $provider = $this->makeProvider(['region' => null]);
        $config = $provider->additionalConfiguration();

        $this->assertEquals('us-east-1', $config['region']);
    }

    public function test_default_max_tokens(): void
    {
        $provider = $this->makeProvider();

        $this->assertEquals(16_384, $provider->defaultMaxTokens());
    }

    public function test_default_max_tokens_fallback(): void
    {
        $provider = $this->makeProvider(['max_tokens' => null]);

        $this->assertEquals(16_384, $provider->defaultMaxTokens());
    }

    public function test_custom_max_tokens(): void
    {
        $provider = $this->makeProvider(['max_tokens' => 32_000]);

        $this->assertEquals(32_000, $provider->defaultMaxTokens());
    }
}
