<?php

namespace App\Services\AI;

interface EmbeddingService
{
    /**
     * @return float[]
     */
    public function embed(string $text): array;

    /**
     * @param  float[]  $a
     * @param  float[]  $b
     */
    public function cosineSimilarity(array $a, array $b): float;
}
