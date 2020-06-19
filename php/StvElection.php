<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use Exception;

class StvElection
{
    public int $seats;
    public array $candidates;
    public array $allBallots;
    public array $validBallots;
    public array $invalidBallots;

    /**
     * @param PreferenceVotes[] $preferenceVotes
     */
    public function __construct(array $preferenceVotes, int $seats)
    {
        $this->seats = $seats;
        $this->setBallots($preferenceVotes);
    }

    public function getQuota(): int
    {
        $votesCast = count($this->validBallots);
        return (int) floor($votesCast / ($this->seats + 1)) + 1;
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
                if ($idx !== 0 && isset($this->allBallots[$vote->username]) && !isset($this->allBallots[$vote->username][$idx - 1])) {
                    throw new Exception("Gap in vote for user {$vote->username}");
                }

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
                $this->validBallots[$username] = $ballot;
            } else {
                $this->invalidBallots[$username] = $ballot;
            }
        }
    }
}
