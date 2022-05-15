<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class ElectedCandidate
{
    /**
     * @param CandidateCount[] $transfers
     */
    public function __construct(
        public string $name,
        public int $surplus,
        public array $transfers,
    ) {
    }
}
