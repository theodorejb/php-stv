<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Ballot
{
    /**
     * @param string $name An empty string if a redistributed vote
     * @param string[] $rankedChoices
     */
    public function __construct(
        public string $name,
        public array $rankedChoices,
        public int $selectedChoice = 0,
        public ?int $lastChoice = null,
        public float $value = 1.0,
    ) {
        if (count($rankedChoices) === 0) {
            throw new \Exception("Ballot for '{$name}' has no ranked choices");
        }
    }

    public function getCandidate(): string
    {
        return $this->rankedChoices[$this->selectedChoice];
    }

    public function withFirstOpenPreference(array $elected, array $eliminated): ?self
    {
        if (!isset($eliminated[$this->getCandidate()])) {
            // return new instance so ballot won't appear as transferred
            return new self($this->name, $this->rankedChoices, $this->selectedChoice, null, $this->value);
        }

        return $this->getNextPreference(array_merge($elected, $eliminated));
    }

    public function getNextPreference(array $eliminated, bool $keepLastChoice = true): ?self
    {
        $index = $this->selectedChoice + 1;
        $lastChoice = $keepLastChoice ? $this->selectedChoice : null;

        while (isset($this->rankedChoices[$index])) {
            $candidate = $this->rankedChoices[$index];

            if (!isset($eliminated[$candidate])) {
                return new self($this->name, $this->rankedChoices, $index, $lastChoice, $this->value);
            }

            $index++;
        }

        return null;
    }
}
