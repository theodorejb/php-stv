<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class CandidateCount
{
    public string $candidate;
    public int $count;

    public function __construct(string $candidate, int $count)
    {
        $this->candidate = $candidate;
        $this->count = $count;
    }
}
