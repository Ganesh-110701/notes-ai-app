<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

// Real embeddings via OpenAI's Embeddings API. Requires OPENAI_API_KEY.
class OpenAIEmbeddingService extends ConcreteEmbeddingService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => mb_substr($text, 0, 8000),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI embeddings request failed: '.$response->body());
        }

        return $response->json('data.0.embedding');
    }
}
