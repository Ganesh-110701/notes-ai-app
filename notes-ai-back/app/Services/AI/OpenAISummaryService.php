<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

// Real summaries via OpenAI's Chat Completions API. Requires OPENAI_API_KEY.
class OpenAISummaryService implements SummaryService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    public function summarize(string $title, string $content): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(20)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'temperature' => 0.3,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You summarize personal notes in 2-3 short, plain sentences. '
                            .'Be concise and only use information present in the note.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Title: {$title}\n\nContent:\n{$content}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI summary request failed: '.$response->body());
        }

        return trim($response->json('choices.0.message.content'));
    }
}
