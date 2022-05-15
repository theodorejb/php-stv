<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Poll
{
    /**
     * @param string[] $candidates
     * @param Vote[] $votes
     */
    public function __construct(
        public string $name,
        public array $candidates,
        public array $votes,
        public bool $isClosed = true,
        public int $lineNumber = 0,
    ) {
    }
}
