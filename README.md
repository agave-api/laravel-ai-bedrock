# Laravel AI Bedrock Provider

AWS Bedrock provider for the [Laravel AI SDK](https://github.com/laravel/ai).

This package adds first-class AWS Bedrock support to the Laravel AI SDK, allowing you to use Bedrock-hosted models (Claude, Titan, etc.) with Laravel's agent system, embeddings, and streaming APIs.

## Requirements

- PHP 8.4+
- Laravel 12+
- `laravel/ai` ^0.2
- `prism-php/bedrock` ^1.8

## Installation

```bash
composer require wojt-janowski/laravel-ai-bedrock
```

The service provider is auto-discovered via Laravel's package discovery.

## Configuration

### Environment Variables

Add these to your `.env`:

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_BEDROCK_REGION=us-east-1
```

Optional:

```env
AWS_SESSION_TOKEN=your-session-token
AWS_BEDROCK_MAX_TOKENS=16384
AWS_BEDROCK_TEXT_MODEL=anthropic.claude-sonnet-4-5-20250929-v1:0
AWS_BEDROCK_CHEAPEST_MODEL=anthropic.claude-haiku-4-5-20251001-v1:0
AWS_BEDROCK_SMARTEST_MODEL=anthropic.claude-opus-4-6-v1:0
AWS_BEDROCK_EMBEDDINGS_MODEL=amazon.titan-embed-text-v2:0
AWS_BEDROCK_EMBEDDINGS_DIMENSIONS=1024
```

### Provider Registration

Add the Bedrock provider to your `config/ai.php`:

```php
'providers' => [
    'bedrock' => [
        'driver' => 'bedrock',
        'access_key' => env('AWS_ACCESS_KEY_ID'),
        'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
        'session_token' => env('AWS_SESSION_TOKEN'),
        'region' => env('AWS_BEDROCK_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'max_tokens' => env('AWS_BEDROCK_MAX_TOKENS', 16_384),
        'models' => [
            'text' => [
                'default' => env('AWS_BEDROCK_TEXT_MODEL', 'anthropic.claude-sonnet-4-5-20250929-v1:0'),
                'cheapest' => env('AWS_BEDROCK_CHEAPEST_MODEL', 'anthropic.claude-haiku-4-5-20251001-v1:0'),
                'smartest' => env('AWS_BEDROCK_SMARTEST_MODEL', 'anthropic.claude-opus-4-6-v1:0'),
            ],
            'embeddings' => [
                'default' => env('AWS_BEDROCK_EMBEDDINGS_MODEL', 'amazon.titan-embed-text-v2:0'),
                'dimensions' => env('AWS_BEDROCK_EMBEDDINGS_DIMENSIONS', 1024),
            ],
        ],
    ],
],
```

If no provider config is defined in `config/ai.php`, the package merges sensible defaults automatically.

## Usage

### With Agents (Attribute-based)

```php
use Laravel\Ai\Attributes\Agent;

#[Agent(
    model: 'anthropic.claude-sonnet-4-5-20250929-v1:0',
    provider: 'bedrock',
)]
class MyAgent extends \Laravel\Ai\Agent
{
    // ...
}
```

### With Agents (Inline)

```php
use Laravel\Ai\Ai;

$provider = Ai::textProvider('bedrock');
```

### Embeddings

```php
use Laravel\Ai\Ai;

$provider = Ai::embeddingProvider('bedrock');

$response = $provider->embeddings(['Hello world']);
```

## AWS Credential Provider Chain

When no explicit `access_key` and `secret_key` are configured, the package automatically uses the AWS default credential provider chain. This supports:

- Environment variables (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`)
- Shared credentials file (`~/.aws/credentials`)
- IAM instance profiles (EC2)
- ECS task roles
- Web identity tokens (EKS)

This is the recommended approach for production deployments on AWS infrastructure.

## How It Works

The Laravel AI SDK's `PrismGateway` uses a hard-coded `match` statement for provider routing that doesn't include Bedrock. This package:

1. Extends `PrismGateway` with `BedrockPrismGateway` to handle the `'bedrock'` driver
2. Registers the Bedrock driver via `Ai::extend()` using this custom gateway
3. Maps AWS credentials to the format expected by `prism-php/bedrock`

## Credits

This package was inspired by [PR #134](https://github.com/laravel/ai/pull/134) on `laravel/ai` by **Mohit Kumar** ([@mohitky2018](https://github.com/mohitky2018)), which implemented Bedrock support directly in the SDK. This package extracts that concept into a standalone Composer package.

## License

MIT License. See [LICENSE](LICENSE) for details.
