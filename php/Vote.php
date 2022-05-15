<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Vote
{
    public function __construct(
        public string $username,
        public int $candidateIndex,
    ) {
    }
}
