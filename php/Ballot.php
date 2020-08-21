<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class Ballot
{
    /** null if a redistributed vote */
    public ?string $name;

    /** @var string[] */
    public array $rankedChoices;
    public int $selectedChoice;
    public ?int $lastChoice;

    /**
     * @param string[] $rankedChoices
     */
    public function __construct(?string $name, array $rankedChoices, int $selectedChoice = 0, ?int $lastChoice = null)
    {
        if (!isset($rankedChoices[0])) {
            throw new \Exception("Ballot for '{$name}' has no ranked choices");
        }

        $this->name = $name;
        $this->rankedChoices = $rankedChoices;
        $this->selectedChoice = $selectedChoice;
        $this->lastChoice = $lastChoice;
    }

    public function getCandidate(): string
    {
        return $this->rankedChoices[$this->selectedChoice];
    }

    public function withFirstOpenPreference(array $elected, array $eliminated): ?self
    {
        if (!isset($eliminated[$this->getCandidate()])) {
            // return new instance so ballot won't appear as transferred
            return new self($this->name, $this->rankedChoices, $this->selectedChoice);
        }

        return $this->getNextPreference(array_merge($elected, $eliminated));
    }

    public function getNextPreference(array $eliminated): ?self
    {
        $index = $this->selectedChoice + 1;

        while (isset($this->rankedChoices[$index])) {
            $candidate = $this->rankedChoices[$index];

            if (!isset($eliminated[$candidate])) {
                return new self($this->name, $this->rankedChoices, $index, $this->selectedChoice);
            }

            $index++;
        }

        return null;
    }
}
