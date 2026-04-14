<?php

namespace App\Gps\Providers;

use App\Contracts\Gps\GpsProviderContract;
use App\Models\GpsProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractGpsProvider implements GpsProviderContract
{
    protected GpsProvider $model;
    protected PendingRequest $client;

    public function __construct(GpsProvider $model)
    {
        $this->model  = $model;
        $this->client = Http::baseUrl($model->base_url)
            ->timeout(10)
            ->withHeaders($this->buildHeaders());
    }

    /**
     * Build auth headers from the provider's config.
     * Concrete providers may override for non-header auth (e.g. query params).
     */
    protected function buildHeaders(): array
    {
        $config = $this->model->auth_config;

        return match ($this->model->auth_type) {
            'api_key' => [
                $config['header'] ?? 'X-API-Key' => $config['value'],
            ],
            'bearer'  => [
                'Authorization' => 'Bearer ' . $config['token'],
            ],
            'basic'   => [
                'Authorization' => 'Basic ' . base64_encode(
                    $config['username'] . ':' . $config['password']
                ),
            ],
            default   => [],
        };
    }

    /**
     * Resolve a dot-notated field path from the provider response
     * using the field_map stored in the model config.
     * e.g. field_map: {"lat": "data.latitude"} → $payload['data']['latitude']
     */
    protected function resolveField(array $payload, string $mapKey): mixed
    {
        $path = $this->model->field_map[$mapKey] ?? null;

        if (! $path) {
            return null;
        }

        return data_get($payload, str_replace('.', '.', $path));
    }

    /**
     * Centralized error handler — logs and re-throws.
     */
    protected function handleError(\Throwable $e, string $context = ''): void
    {
        Log::error("[GpsProvider:{$this->model->name}] {$context}", [
            'error'       => $e->getMessage(),
            'provider_id' => $this->model->id,
        ]);

        throw $e;
    }
}
