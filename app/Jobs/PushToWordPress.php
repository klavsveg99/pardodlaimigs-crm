<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CrmProperty;
use App\Services\Wp\WpSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushToWordPress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public CrmProperty $property,
    ) {}

    public function handle(WpSource $wp): void
    {
        $config = config('wp-bridge.wordpress');
        $baseUrl = $config['site_url'].'/wp-json/crm/v1';
        $apiKey = config('wp-bridge.api_key', config('WP_CRM_API_KEY'));

        $payload = $this->property->toWpPayload();

        $response = Http::withHeaders([
            'X-CRM-API-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(15)->post("{$baseUrl}/properties/sync", $payload);

        if ($response->failed()) {
            Log::error('WordPress push failed', [
                'property_id' => $this->property->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("WordPress push failed for property #{$this->property->id}");
        }

        Log::info('WordPress push succeeded', [
            'property_id' => $this->property->id,
            'title' => $this->property->title,
        ]);
    }
}
