<?php

namespace Clinically\LaravelAiBedrock\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Orchestra\Testbench\TestCase;
use Prism\Prism\Facades\Prism;
use Clinically\LaravelAiBedrock\BedrockPrismGateway;
use Clinically\LaravelAiBedrock\BedrockProvider;

class BedrockPrismGatewayTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \Prism\Prism\PrismServiceProvider::class,
            \Prism\Bedrock\BedrockServiceProvider::class,
        ];
    }

    private function makeGateway(): BedrockPrismGateway
    {
        return new BedrockPrismGateway($this->app->make(Dispatcher::class));
    }

    private function makeProvider(?BedrockPrismGateway $gateway = null, array $config = []): BedrockProvider
    {
        $gateway ??= $this->makeGateway();

        $config = array_merge([
            'driver' => 'bedrock',
            'name' => 'bedrock',
            'access_key' => 'test-key',
            'secret_key' => 'test-secret',
            'region' => 'us-east-1',
        ], $config);

        return new BedrockProvider(
            $gateway,
            $config,
            $this->app->make(Dispatcher::class),
        );
    }

    private function makeConfiguredRequest()
    {
        return Prism::text()->using('bedrock', 'anthropic.claude-sonnet-4-5-20250929-v1:0', [
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
            'region' => 'us-east-1',
        ]);
    }

    public function test_configure_uses_bedrock_string_for_bedrock_driver(): void
    {
        $gateway = $this->makeGateway();
        $provider = $this->makeProvider($gateway);

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'configure');

        $prism = Prism::text();
        $result = $reflection->invoke($gateway, $prism, $provider, 'anthropic.claude-sonnet-4-5-20250929-v1:0');

        $this->assertNotNull($result);
    }

    public function test_configure_delegates_to_parent_for_other_drivers(): void
    {
        $gateway = $this->makeGateway();

        $provider = new BedrockProvider(
            $gateway,
            ['driver' => 'anthropic', 'name' => 'anthropic', 'key' => 'test-key'],
            $this->app->make(Dispatcher::class),
        );

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'configure');

        $prism = Prism::text();
        $result = $reflection->invoke($gateway, $prism, $provider, 'claude-sonnet-4-5-20250929');

        $this->assertNotNull($result);
    }

    public function test_with_provider_options_enables_tool_calling_when_schema_provided(): void
    {
        $gateway = $this->makeGateway();
        $provider = $this->makeProvider($gateway);
        $request = $this->makeConfiguredRequest();
        $schema = ['type' => 'object', 'properties' => []];

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'withProviderOptions');
        $result = $reflection->invoke($gateway, $request, $provider, $schema, null);

        $resolved = $result->toRequest();

        $this->assertTrue($resolved->providerOptions('use_tool_calling'));
    }

    public function test_with_provider_options_omits_tool_calling_without_schema(): void
    {
        $gateway = $this->makeGateway();
        $provider = $this->makeProvider($gateway);
        $request = $this->makeConfiguredRequest();

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'withProviderOptions');
        $result = $reflection->invoke($gateway, $request, $provider, null, null);

        $resolved = $result->toRequest();

        $this->assertEmpty($resolved->providerOptions());
    }

    public function test_with_provider_options_uses_default_max_tokens(): void
    {
        $gateway = $this->makeGateway();
        $provider = $this->makeProvider($gateway);
        $request = $this->makeConfiguredRequest();

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'withProviderOptions');
        $result = $reflection->invoke($gateway, $request, $provider, null, null);

        $resolved = $result->toRequest();

        $this->assertEquals(16_384, $resolved->maxTokens());
    }

    public function test_with_provider_options_respects_options_max_tokens(): void
    {
        $gateway = $this->makeGateway();
        $provider = $this->makeProvider($gateway);
        $request = $this->makeConfiguredRequest();
        $options = new TextGenerationOptions(maxTokens: 4_096);

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'withProviderOptions');
        $result = $reflection->invoke($gateway, $request, $provider, null, $options);

        $resolved = $result->toRequest();

        $this->assertEquals(4_096, $resolved->maxTokens());
    }

    public function test_with_provider_options_respects_configured_max_tokens(): void
    {
        $gateway = $this->makeGateway();
        $provider = $this->makeProvider($gateway, ['max_tokens' => 32_000]);
        $request = $this->makeConfiguredRequest();

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'withProviderOptions');
        $result = $reflection->invoke($gateway, $request, $provider, null, null);

        $resolved = $result->toRequest();

        $this->assertEquals(32_000, $resolved->maxTokens());
    }

    public function test_with_provider_options_delegates_to_parent_for_other_providers(): void
    {
        $gateway = $this->makeGateway();

        $provider = new BedrockProvider(
            $gateway,
            ['driver' => 'anthropic', 'name' => 'anthropic', 'key' => 'test-key'],
            $this->app->make(Dispatcher::class),
        );

        $request = Prism::text()->using('anthropic', 'claude-sonnet-4-5-20250929', [
            'api_key' => 'test-key',
        ]);

        $options = new TextGenerationOptions(maxTokens: 2_000);

        $reflection = new \ReflectionMethod(BedrockPrismGateway::class, 'withProviderOptions');
        $result = $reflection->invoke($gateway, $request, $provider, null, $options);

        $resolved = $result->toRequest();

        $this->assertEquals(2_000, $resolved->maxTokens());
        $this->assertEmpty($resolved->providerOptions());
    }
}
