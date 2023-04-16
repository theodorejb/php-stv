<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class ElectedCandidate
{
    /**
     * @param CandidateTransfers[] $transfers
     */
    public function __construct(
        public string $name,
        public float $surplus,
        public int $transferable = 0,
        public array $transfers = [],
    ) {
    }
}
