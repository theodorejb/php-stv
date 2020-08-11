<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use Exception;

class StvElection
{
    public int $seats;
    public array $candidates;
    public array $allBallots;
    /** @var Ballot[] */
    public array $validBallots;
    /** @var Ballot[] */
    public array $invalidBallots;
    public int $quota;

    /**
     * @param PreferenceVotes[] $preferenceVotes
     */
    public function __construct(array $preferenceVotes, int $seats)
    {
        $this->seats = $seats;
        $this->setBallots($preferenceVotes);

        $votesCast = count($this->validBallots);
        // Droop quota formula
        $this->quota = (int) floor($votesCast / ($this->seats + 1)) + 1;
    }

    public function getSummary(): string
    {
        $votes = count($this->validBallots);

        $summary = "Votes: {$votes}" . PHP_EOL;
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
            $round = new ElectionRound($roundNum, $ballots, $candidates, $allElected, $allEliminated, $this);
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
                if (!isset($allEliminated[$candidate]) && $candidate) {
                    $newCandidates[] = $candidate;
                }
            }

            $candidates = $newCandidates;
            $ballots = $round->getNewBallots($elected, $allEliminated);
        }

        return $pastRounds;
    }

    /**
     * @param PreferenceVotes[] $preferenceVotes
     */
    private function setBallots(array $preferenceVotes): void
    {
        if (count($preferenceVotes) === 0) {
            throw new Exception('Failed to find any votes');
        }

        $this->candidates = [];
        $this->allBallots = [];

        foreach ($preferenceVotes as $idx => $rankedVote) {
            if ($this->candidates === []) {
                $this->candidates = $rankedVote->candidates;
            } elseif ($rankedVote->candidates !== $this->candidates) {
                throw new Exception("Candidate list doesn't match for {$rankedVote->name} vote");
            }

            foreach ($rankedVote->votes as $vote) {
                /*if ($idx !== 0 && isset($this->allBallots[$vote->username]) && !isset($this->allBallots[$vote->username][$idx - 1])) {
                    throw new Exception("Gap in vote for user {$vote->username}");
                }*/

                $this->allBallots[$vote->username][] = $this->candidates[$vote->candidateIndex];
            }
        }

        $this->setValidBallots($this->allBallots);
    }

    private function setValidBallots(array $ballots)
    {
        $this->validBallots = [];
        $this->invalidBallots = [];

        foreach ($ballots as $username => $ballot) {
            $unique = [];
            $isValid = true;

            foreach ($ballot as $preference) {
                if (isset($unique[$preference])) {
                    $isValid = false;
                    break;
                }

                $unique[$preference] = true;
            }

            if ($isValid) {
                $this->validBallots[] = new Ballot((string) $username, $ballot);
            } else {
                $this->invalidBallots[] = new Ballot((string) $username, $ballot);
            }
        }
    }
}
