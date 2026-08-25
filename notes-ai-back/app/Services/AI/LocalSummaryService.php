<?php

namespace App\Services\AI;

// Offline fallback used when OPENAI_API_KEY is not set - a simple
// extractive summarizer (keeps the highest-scoring sentences, in order).
class LocalSummaryService implements SummaryService
{
    private const MAX_SENTENCES = 3;

    public function summarize(string $title, string $content): string
    {
        $sentences = $this->splitIntoSentences($content);

        if (count($sentences) <= self::MAX_SENTENCES) {
            return trim(implode(' ', $sentences));
        }

        $wordFrequency = $this->wordFrequency($content);

        $scored = [];
        foreach ($sentences as $position => $sentence) {
            $scored[] = [
                'position' => $position,
                'sentence' => $sentence,
                'score' => $this->scoreSentence($sentence, $wordFrequency),
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, self::MAX_SENTENCES);

        usort($top, fn ($a, $b) => $a['position'] <=> $b['position']);

        return trim(implode(' ', array_column($top, 'sentence')));
    }

    /**
     * @return string[]
     */
    private function splitIntoSentences(string $content): array
    {
        $content = trim(preg_replace('/\s+/', ' ', $content));
        $parts = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        return $parts ?: [$content];
    }

    /**
     * @return array<string, int>
     */
    private function wordFrequency(string $content): array
    {
        preg_match_all('/[a-z0-9]+/', strtolower($content), $matches);
        $frequency = [];

        foreach ($matches[0] as $word) {
            if (strlen($word) < 3) {
                continue;
            }
            $frequency[$word] = ($frequency[$word] ?? 0) + 1;
        }

        return $frequency;
    }

    /**
     * @param  array<string, int>  $wordFrequency
     */
    private function scoreSentence(string $sentence, array $wordFrequency): float
    {
        preg_match_all('/[a-z0-9]+/', strtolower($sentence), $matches);
        $words = $matches[0];

        if (empty($words)) {
            return 0.0;
        }

        $score = 0;
        foreach ($words as $word) {
            $score += $wordFrequency[$word] ?? 0;
        }

        return $score / count($words);
    }
}
