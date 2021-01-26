<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Poll
{
    public string $name;
    public bool $isClosed;
    public int $lineNumber;

    /** @var string[] */
    public array $candidates;

    /** @var Vote[] */
    public array $votes;

    /**
     * @param string[] $candidates
     * @param Vote[] $votes
     */
    public function __construct(string $name, array $candidates, array $votes, bool $isClosed = true, int $lineNumber = 0)
    {
        $this->name = $name;
        $this->candidates = $candidates;
        $this->votes = $votes;
        $this->isClosed = $isClosed;
        $this->lineNumber = $lineNumber;
    }
}
