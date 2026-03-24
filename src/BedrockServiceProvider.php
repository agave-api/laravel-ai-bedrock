<?php

namespace Clinically\LaravelAiBedrock;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Ai;

class BedrockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-bedrock.php', 'ai.providers.bedrock');
    }

    public function boot(): void
    {
        Ai::extend('bedrock', function ($app, array $config) {
            $dispatcher = $app->make(Dispatcher::class);

            return new BedrockProvider(
                new BedrockPrismGateway($dispatcher),
                $config,
                $dispatcher,
            );
        });
    }
}
