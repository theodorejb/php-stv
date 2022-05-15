<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class CandidateCount
{
    public function __construct(
        public string $candidate,
        public int $count,
        public string $details = '',
    ) {
    }
}
