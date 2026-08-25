<?php

namespace App\Services\AI;

// Offline fallback used when OPENAI_API_KEY is not set - a hashing-trick
// bag-of-words vector, not true semantic embeddings.
class LocalEmbeddingService extends ConcreteEmbeddingService
{
    private const DIMENSIONS = 256;

    public function embed(string $text): array
    {
        $vector = array_fill(0, self::DIMENSIONS, 0.0);

        $words = $this->tokenize($text);

        if (empty($words)) {
            return $vector;
        }

        foreach ($words as $word) {
            $bucket = crc32($word) % self::DIMENSIONS;
            $vector[$bucket] += 1.0;
        }

        $total = count($words);
        foreach ($vector as $i => $value) {
            $vector[$i] = $value / $total;
        }

        return $vector;
    }

    /**
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        $text = strtolower($text);
        preg_match_all('/[a-z0-9]+/', $text, $matches);

        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'to', 'of', 'and', 'or', 'in', 'on', 'for', 'with', 'this', 'that', 'it', 'as', 'be', 'at'];

        return array_values(array_diff($matches[0], $stopWords));
    }
}
