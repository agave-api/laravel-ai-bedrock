<?php

namespace AgaveApi\LaravelAiBedrock;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Gateway\Prism\PrismException;
use Laravel\Ai\Gateway\Prism\PrismGateway;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\EmbeddingsResponse;
use Prism\Prism\Exceptions\PrismException as PrismVendorException;
use Prism\Prism\Facades\Prism;

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

    /**
     * This is ripped from the base class, because it doesn't provide a good way to override how to pass dimensions in
     * as provider options
     *
     * @inheritDoc
     */
    public function generateEmbeddings(
        EmbeddingProvider $provider,
        string $model,
        array $inputs,
        int $dimensions,
        int $timeout = 30,
    ): EmbeddingsResponse {
        $request = tap(
            Prism::embeddings(),
            fn ($prism) => $this->configure($prism, $provider, $model)
        )->withClientOptions([
            'timeout' => $timeout,
        ]);

        $request->withProviderOptions(match ($provider->driver()) {
            'gemini' => ['outputDimensionality' => $dimensions],
            'ollama' => ['dimensions' => $dimensions],
            'openai' => ['dimensions' => $dimensions],
            'openrouter' => ['dimensions' => $dimensions],
            'voyageai' => ['outputDimension' => $dimensions],
            'bedrock' => [
                Str::startsWith($model, 'cohere.') ? 'output_dimension' : 'dimensions' => $dimensions,
            ],
            default => [],
        });

        (new Collection($inputs))->each($request->fromInput(...));

        try {
            $response = $request->asEmbeddings();
        } catch (PrismVendorException $e) {
            throw PrismException::toAiException($e, $provider, $model);
        }

        return new EmbeddingsResponse(
            (new Collection($response->embeddings))->map->embedding->all(),
            $response->usage->tokens,
            new Meta($provider->name(), $model),
        );
    }
}
