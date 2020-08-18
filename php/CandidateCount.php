<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class CandidateCount
{
    public string $candidate;
    public int $count;
    public string $details;

    public function __construct(string $candidate, int $count, string $details = '')
    {
        $this->candidate = $candidate;
        $this->count = $count;
        $this->details = $details;
    }
}
