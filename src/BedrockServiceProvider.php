<?php

namespace AgaveApi\LaravelAiBedrock;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;

class BedrockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-bedrock.php', 'ai.providers.bedrock');
        // We need to leverage resolving in register rather than Ai::extend in boot because the AiManager is scoped and as a result is cleared regularly
        $this->app->resolving(AiManager::class, function (AiManager $manager) {
            $manager->extend('bedrock', function ($app, array $config) {
                $dispatcher = $app->make(Dispatcher::class);

                return (new BedrockProvider(
                    new BedrockPrismGateway($dispatcher),
                    $config,
                    $dispatcher,
                ))
                    ->useRerankingGateway(
                        new RerankingGateway(),
                    );
            });
        });
    }
}
