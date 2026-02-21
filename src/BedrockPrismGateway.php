<?php

namespace WojtJanowski\LaravelAiBedrock;

use Laravel\Ai\Gateway\Prism\PrismGateway;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Providers\Provider;

class BedrockPrismGateway extends PrismGateway
{
    protected function configure($prism, Provider $provider, string $model): mixed
    {
        if ($provider->driver() === 'bedrock') {
            return $prism->using(
                'bedrock',
                $model,
                array_filter([
                    ...$provider->additionalConfiguration(),
                    'api_key' => $provider->providerCredentials()['key'],
                ]),
            );
        }

        return parent::configure($prism, $provider, $model);
    }

    protected function withProviderOptions($request, Provider $provider, ?array $schema, ?TextGenerationOptions $options)
    {
        if ($provider instanceof BedrockProvider) {
            return $request
                ->withProviderOptions(array_filter([
                    'use_tool_calling' => $schema ? true : null,
                ]))
                ->withMaxTokens($options?->maxTokens ?? $provider->defaultMaxTokens());
        }

        return parent::withProviderOptions($request, $provider, $schema, $options);
    }
}
