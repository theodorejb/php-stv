<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class ElectedCandidate
{
    public string $name;
    public int $surplus;
    /** @var CandidateCount[] */
    public array $transfers;

    public function __construct(string $name, int $surplus)
    {
        $this->name = $name;
        $this->surplus = $surplus;
        $this->transfers = [];
    }
}
