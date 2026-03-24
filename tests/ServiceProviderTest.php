<?php

namespace Clinically\LaravelAiBedrock\Tests;

use Laravel\Ai\Ai;
use LogicException;
use Orchestra\Testbench\TestCase;
use Clinically\LaravelAiBedrock\BedrockProvider;
use Clinically\LaravelAiBedrock\BedrockServiceProvider;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \Laravel\Ai\AiServiceProvider::class,
            \Prism\Prism\PrismServiceProvider::class,
            \Prism\Bedrock\BedrockServiceProvider::class,
            BedrockServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ai.providers.bedrock', [
            'driver' => 'bedrock',
            'access_key' => 'test-access-key',
            'secret_key' => 'test-secret-key',
            'region' => 'us-east-1',
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
        ]);
    }

    public function test_bedrock_driver_is_registered(): void
    {
        $provider = Ai::textProvider('bedrock');

        $this->assertInstanceOf(BedrockProvider::class, $provider);
    }

    public function test_text_provider_returns_bedrock_provider(): void
    {
        $provider = Ai::textProvider('bedrock');

        $this->assertInstanceOf(BedrockProvider::class, $provider);
        $this->assertEquals('bedrock', $provider->driver());
    }

    public function test_embedding_provider_returns_bedrock_provider(): void
    {
        $provider = Ai::embeddingProvider('bedrock');

        $this->assertInstanceOf(BedrockProvider::class, $provider);
    }

    public function test_audio_provider_throws_logic_exception(): void
    {
        $this->expectException(LogicException::class);

        Ai::audioProvider('bedrock');
    }

    public function test_image_provider_throws_logic_exception(): void
    {
        $this->expectException(LogicException::class);

        Ai::imageProvider('bedrock');
    }

    public function test_transcription_provider_throws_logic_exception(): void
    {
        $this->expectException(LogicException::class);

        Ai::transcriptionProvider('bedrock');
    }

    public function test_config_is_merged(): void
    {
        $config = config('ai.providers.bedrock');

        $this->assertEquals('bedrock', $config['driver']);
        $this->assertEquals('test-access-key', $config['access_key']);
    }

    public function test_provider_has_correct_default_models(): void
    {
        $provider = Ai::textProvider('bedrock');

        $this->assertEquals('anthropic.claude-sonnet-4-5-20250929-v1:0', $provider->defaultTextModel());
        $this->assertEquals('anthropic.claude-haiku-4-5-20251001-v1:0', $provider->cheapestTextModel());
        $this->assertEquals('anthropic.claude-opus-4-6-v1:0', $provider->smartestTextModel());
    }
}
