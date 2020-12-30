<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use Exception;

class StvElection
{
    public int $seats;
    public int $quota;
    public bool $isClosed;

    /** @var string[] */
    public array $candidates;

    /**
     * @var array<mixed, string[]>
     */
    public array $allBallots;

    /** @var Ballot[] */
    public array $validBallots;

    /** @var Ballot[] */
    public array $invalidBallots;

    /**
     * @param PreferenceVotes[] $preferenceVotes
     */
    public function __construct(array $preferenceVotes, int $seats, bool $keepInvalidBallots = false)
    {
        $this->seats = $seats;
        $this->setBallots($preferenceVotes, $keepInvalidBallots);

        $votesCast = count($this->validBallots);
        // Droop quota formula
        $this->quota = (int) floor($votesCast / ($this->seats + 1)) + 1;
    }

    public function getResults(bool $showInvalid, bool $showCounted): string
    {
        $output = $this->getSummary($showCounted, $showInvalid);
        $rounds = $this->runElection();
        $lastIndex = count($rounds) - 1;

        foreach ($rounds as $index => $round) {
            $output .= $round->getSummary() . PHP_EOL;
            $elected = $round->elected;

            foreach ($elected as $candidate) {
                $output .= "{$candidate->name} elected with {$candidate->surplus} surplus votes" . PHP_EOL;

                if ($index !== $lastIndex) {
                    if (count($candidate->transfers) !== 0) {
                        $output .= "Distributing surplus votes" . PHP_EOL . PHP_EOL;
                    }

                    foreach ($candidate->transfers as $transfer) {
                        $output .= "{$transfer->candidate}: +{$transfer->count}  {$transfer->details}" . PHP_EOL;
                    }
                }
            }

            foreach ($round->eliminated as $cc) {
                $output .= "Eliminating {$cc->candidate}" . PHP_EOL;
            }
        }

        return $output;
    }

    public function getSummary(bool $listVotes, bool $showInvalid): string
    {
        $summary = 'Candidates (in order of ballot):' . PHP_EOL;
        $summary .= implode("  -  ", $this->candidates) . PHP_EOL;

        $invalidBallotCount = count($this->invalidBallots);
        $index = $invalidBallotCount * -1;

        if ($invalidBallotCount > 0 && $showInvalid) {
            $summary .= PHP_EOL . "{$invalidBallotCount} invalid ballots:" . PHP_EOL;

            foreach ($this->invalidBallots as $ballot) {
                $index++;
                $summary .= "{$ballot->name}:   ";
                $summary .= implode("  -  ", $ballot->rankedChoices) . PHP_EOL;
            }
        }

        if ($listVotes) {
            $summary .= PHP_EOL . 'Votes:' . PHP_EOL;
            $index = 0;

            foreach ($this->validBallots as $ballot) {
                $index++;
                $summary .= "Vote #{$index}:   ";
                $summary .= implode("  -  ", $ballot->rankedChoices) . PHP_EOL;
            }
        }

        $votes = count($this->validBallots);
        $summary .= PHP_EOL . "Votes: {$votes}" . PHP_EOL;
        $summary .= 'Candidates: ' . count($this->candidates) . PHP_EOL;
        $summary .= 'Seats: ' . $this->seats . PHP_EOL;
        $summary .= "Quota: floor({$votes} / ({$this->seats} + 1)) + 1 = {$this->quota}" . PHP_EOL;

        return $summary;
    }

    /**
     * @return ElectionRound[]
     */
    public function runElection(): array
    {
        $roundNum = 0;
        $ballots = $this->validBallots;
        $candidates = $this->candidates;
        $pastRounds = [];
        $allEliminated = [];
        $allElected = [];

        while (count($allElected) < $this->seats && count($candidates) !== 0) {
            $roundNum++;
            $baseTally = [];

            foreach ($candidates as $candidate) {
                if (!isset($allElected[$candidate])) {
                    $baseTally[$candidate] = 0;
                }
            }

            if (count($baseTally) === 0) {
                break;
            }

            $round = new ElectionRound($roundNum, $ballots, $baseTally, $allEliminated, $this);
            $pastRounds[] = $round;
            $elected = $round->elected;

            foreach ($elected as $e) {
                $allElected[$e->name] = true;
            }

            foreach ($round->eliminated as $cc) {
                $allEliminated[$cc->candidate] = true;
            }

            $newCandidates = [];

            foreach ($candidates as $candidate) {
                if (!isset($allEliminated[$candidate])) {
                    $newCandidates[] = $candidate;
                }
            }

            $candidates = $newCandidates;
            $ballots = $round->getNewBallots($elected, $allElected, $allEliminated);
        }

        return $pastRounds;
    }

    /**
     * @param PreferenceVotes[] $preferenceVotes
     */
    private function setBallots(array $preferenceVotes, bool $keepInvalidBallots): void
    {
        if (count($preferenceVotes) === 0) {
            throw new Exception('Failed to find any votes');
        }

        $this->isClosed = true;
        $this->candidates = [];
        $this->allBallots = [];

        foreach ($preferenceVotes as $idx => $rankedVote) {
            if ($this->candidates === []) {
                $this->candidates = $rankedVote->candidates;
            } elseif ($rankedVote->candidates !== $this->candidates) {
                throw new Exception("Candidate list doesn't match for {$rankedVote->name} vote");
            }

            if (!$rankedVote->pollClosed) {
                $this->isClosed = false;
            }

            foreach ($rankedVote->votes as $vote) {
                $this->allBallots[$vote->username][] = $this->candidates[$vote->candidateIndex];
            }
        }

        $this->setValidBallots($keepInvalidBallots);
    }

    private function setValidBallots(bool $keepInvalidBallots): void
    {
        $this->validBallots = [];
        $this->invalidBallots = [];

        foreach ($this->allBallots as $username => $ballot) {
            $unique = [];
            $isValid = true;

            foreach ($ballot as $preference) {
                if (isset($unique[$preference])) {
                    $isValid = false;
                    break;
                }

                $unique[$preference] = true;
            }

            if ($isValid || $keepInvalidBallots) {
                // if invalid, only include preferences prior to duplicate
                $this->validBallots[] = new Ballot((string) $username, array_keys($unique));
            }

            if (!$isValid) {
                $this->invalidBallots[] = new Ballot((string) $username, $ballot);
            }
        }
    }
}
