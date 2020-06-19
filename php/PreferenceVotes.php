<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class PreferenceVotes
{
    public string $name;
    /** @var string[] */
    public array $candidates;
    /** @var Vote[] */
    public array $votes;

    public function __construct(string $name, array $candidates, array $votes)
    {
        $this->name = $name;
        $this->candidates = $candidates;
        $this->votes = $votes;
    }
}
