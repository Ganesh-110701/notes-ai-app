<?php

namespace App\Providers;

use App\Services\AI\EmbeddingService;
use App\Services\AI\LocalEmbeddingService;
use App\Services\AI\LocalSummaryService;
use App\Services\AI\OpenAIEmbeddingService;
use App\Services\AI\OpenAISummaryService;
use App\Services\AI\SummaryService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind to OpenAI when a key is configured, otherwise the local fallback.
        $apiKey = config('services.openai.key');

        $this->app->bind(EmbeddingService::class, function () use ($apiKey) {
            return $apiKey
                ? new OpenAIEmbeddingService($apiKey, config('services.openai.embedding_model'))
                : new LocalEmbeddingService;
        });

        $this->app->bind(SummaryService::class, function () use ($apiKey) {
            return $apiKey
                ? new OpenAISummaryService($apiKey, config('services.openai.chat_model'))
                : new LocalSummaryService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Avoids a MySQL "key too long" error on unique/string migrations.
        Schema::defaultStringLength(191);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // AI-backed endpoints (search, summary) get a tighter cap.
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
