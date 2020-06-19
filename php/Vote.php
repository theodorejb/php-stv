<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Vote
{
    public string $username;
    public int $candidateIndex;

    public function __construct(string $username, int $candidateIndex)
    {
        $this->username = $username;
        $this->candidateIndex = $candidateIndex;
    }
}
