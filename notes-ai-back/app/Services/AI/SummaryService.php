<?php

namespace App\Services\AI;

interface SummaryService
{
    public function summarize(string $title, string $content): string;
}
