<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class CandidateTransfers
{
    /**
     * @param Ballot[] $ballots
     */
    public function __construct(
        public string $candidate,
        public string $details,
        public array $ballots,
    ) {
    }

    public function getValue(): int
    {
        return count($this->ballots);
    }
}
