<?php

namespace App\Services\AI;

abstract class ConcreteEmbeddingService implements EmbeddingService
{
    public function cosineSimilarity(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] ** 2;
            $magnitudeB += $b[$i] ** 2;
        }

        if ($magnitudeA == 0.0 || $magnitudeB == 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }
}
