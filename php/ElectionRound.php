<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class ElectionRound
{
    public int $round;

    /** @var Ballot[] */
    public array $ballots;
    private array $eliminatedKeys;
    public array $tally;
    private StvElection $election;

    /** @var ElectedCandidate[] */
    public array $elected;

    /**
     * @var CandidateCount[]
     */
    public array $eliminated;

    /**
     * @param Ballot[] $ballots
     * @param string[] $candidates
     */
    public function __construct(int $round, array $ballots, array $candidates, array $electedKeys, array $eliminatedKeys, StvElection $election)
    {
        $this->round = $round;
        $this->ballots = $ballots;
        $this->eliminatedKeys = $eliminatedKeys;
        $this->election = $election;
        $this->tally = [];

        foreach ($candidates as $candidate) {
            if (!isset($electedKeys[$candidate])) {
                $this->tally[$candidate] = 0;
            }
        }

        foreach ($this->ballots as $ballot) {
            $candidate = $ballot->getCandidate();

            if (isset($this->tally[$candidate])) {
                $this->tally[$candidate]++;
            }
        }

        $this->elected = $this->getElected();
        $this->eliminated = (count($this->elected) === 0) ? $this->getCandidatesWithFewestVotes() : [];
    }

    public function getSummary(): string
    {
        $summary = PHP_EOL . "Round #{$this->round}" . PHP_EOL;
        $summary .= '--------' . PHP_EOL . PHP_EOL;
        $summary .= 'Tally:' . PHP_EOL;

        foreach ($this->tally as $candidate => $count) {
            $summary .= "Candidate {$candidate}: {$count}" . PHP_EOL;
        }

        return $summary;
    }

    /**
     * Return all candidates that meet the quota
     * @return ElectedCandidate[]
     */
    private function getElected(): array
    {
        $elected = [];

        foreach ($this->tally as $candidate => $count) {
            $surplus = $count - $this->election->quota;

            if ($surplus < 0 && count($this->tally) > 1) {
                continue;
            }

            $electedCandidate = new ElectedCandidate($candidate, $surplus);

            // tally next preferences of each voter for this candidate
            $nextTally = $this->getNextPreferenceTally($candidate);

            foreach ($nextTally as $nextCandidate => $nextCount) {
                $toTransfer = (int) floor(($nextCount / $count) * $surplus);

                if ($toTransfer !== 0) {
                    $electedCandidate->transfers[] = new CandidateCount($nextCandidate, $toTransfer);
                }
            }

            $elected[] = $electedCandidate;
        }

        return $elected;
    }

    /**
     * @param ElectedCandidate[] $elected
     * @return Ballot[]
     */
    public function getNewBallots(array $elected, array $eliminated): array
    {
        $ballots = [];

        foreach ($this->ballots as $ballot) {
            $newBallot = $ballot->withFirstOpenPreference($eliminated);

            if ($newBallot !== null) {
                $ballots[] = $newBallot;
            }
        }

        // add transfer ballots from any elected candidates
        foreach ($elected as $candidate) {
            foreach ($candidate->transfers as $transfer) {
                for ($i = 0; $i < $transfer->count; $i++) {
                    $ballots[] = new Ballot(null, [$transfer->candidate]);
                }
            }
        }

        return $ballots;
    }

    /**
     * @return CandidateCount[]
     */
    public function getCandidatesWithFewestVotes(): array
    {
        $tallyCopy = $this->tally;
        asort($tallyCopy);

        $fewestVotes = $tallyCopy[array_key_first($tallyCopy)];
        $candidates = [];

        foreach ($tallyCopy as $candidate => $count) {
            if ($count === $fewestVotes) {
                $candidates[] = new CandidateCount($candidate, $count);
            } else {
                break;
            }
        }

        return $candidates;
    }

    private function getNextPreferenceTally(string $candidate): array
    {
        $tally = [];

        foreach ($this->ballots as $ballot) {
            if ($ballot->getCandidate() === $candidate) {
                $nextPreference = $ballot->getNextPreference($this->eliminatedKeys);

                if ($nextPreference !== null) {
                    $nextCandidate = $nextPreference->getCandidate();

                    if (!isset($tally[$nextCandidate])) {
                        $tally[$nextCandidate] = 1;
                    } else {
                        $tally[$nextCandidate]++;
                    }
                }
            }
        }

        return $tally;
    }
}
