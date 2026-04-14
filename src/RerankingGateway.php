<?php

namespace AgaveApi\LaravelAiBedrock;

use Aws\BedrockAgentRuntime\BedrockAgentRuntimeClient;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Providers\RerankingProvider;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\RankedDocument;
use Laravel\Ai\Responses\RerankingResponse;

class RerankingGateway implements \Laravel\Ai\Contracts\Gateway\RerankingGateway
{
    public function rerank(
        RerankingProvider $provider,
        string $model,
        array $documents,
        string $query,
        ?int $limit = null,
    ): RerankingResponse {
        $client = new BedrockAgentRuntimeClient($provider->additionalConfiguration());

        $payload = [
            'queries' => [
                [
                    'type' => 'TEXT',
                    'textQuery' => [
                        'text' => $query,
                    ],
                ],
            ],
            'rerankingConfiguration' => [
                'type' => 'BEDROCK_RERANKING_MODEL',
                'bedrockRerankingConfiguration' => [
                    'modelConfiguration' => [
                        'modelArn' => "arn:aws:bedrock:{$client->getRegion()}::foundation-model/$model",
                    ],
                    'numberOfResults' => $limit,
                ]
            ],
            'sources' => array_map(static fn (string $document) => [
                'type' => 'INLINE',
                'inlineDocumentSource' => [
                    'type' => 'TEXT',
                    'textDocument' => [
                        'text' => $document,
                    ],
                ],
            ], $documents),
        ];

        $response = $client->rerank($payload);
        $results = (new Collection($response['results']))->map(fn (array $result) => new RankedDocument(
            index: $result['index'],
            document: $documents[$result['index']],
            score: $result['relevanceScore'],
        ))->all();

        return new RerankingResponse(
            $results,
            new Meta($provider->name(), $model),
        );
    }

    protected function client(RerankingProvider $provider): BedrockAgentRuntimeClient
    {
        $credentials = $provider->providerCredentials();
        $config = $provider->additionalConfiguration();

        $clientConfig = [
            'region' => $config['region'] ?? 'us-east-1',
            'version' => 'latest',
        ];

        if (empty($config['use_default_credential_provider'])) {
            $clientConfig['credentials'] = array_filter([
                'key' => $credentials['key'],
                'secret' => $config['api_secret'],
                'token' => $config['session_token'] ?? null,
            ]);
        }

        return new BedrockAgentRuntimeClient($clientConfig);
    }
}
