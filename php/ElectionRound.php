<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

class ElectionRound
{
    /** @var array<string, int> */
    public array $tally;

    /** @var ElectedCandidate[] */
    public array $elected;

    /**
     * @var CandidateCount[]
     */
    public array $eliminated;

    /**
     * @param Ballot[] $ballots
     * @param array<string, int> $baseTally
     * @param array<string, true> $allElected
     * @param array<string, true> $allEliminated
     */
    public function __construct(
        public int $round,
        public array $ballots,
        array $baseTally,
        private array $allElected,
        private array $allEliminated,
        private StvElection $election,
    ) {
        $this->tally = $baseTally;

        foreach ($this->ballots as $ballot) {
            $candidate = $ballot->getCandidate();

            if (isset($this->tally[$candidate])) {
                $this->tally[$candidate]++;
            }
        }

        $this->elected = $this->getElected();
        $this->eliminated = (count($this->elected) === 0) ? $this->getCandidatesWithFewestVotes() : [];
    }

    public function getSummaryHtml(): string
    {
        $summary = "<h2 class=\"mb-3\">Round #{$this->round}</h2>\n";
        $transfers = $this->getTransfers();

        if (count($transfers) !== 0) {
            $summary .= "<h4>🔀 Transfers</h4>\n";
            $summary .= "<ul>\n";

            foreach ($transfers as $candidate => $transfer) {
                $summary .= "  <li>" . Utils::encodeHtml($candidate) . ": <b>+{$transfer}</b></li>\n";
            }

            $summary .= "</ul>\n";
        }

        $summary .= <<<tallyTable

        <table class="table">
          <thead>
            <tr>
              <th scope="col">Candidate</th>
              <th scope="col">Tally</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">

        tallyTable;

        foreach ($this->tally as $candidate => $count) {
            $encoded = Utils::encodeHtml($candidate);
            $summary .= <<<candidateRow
                <tr>
                  <td>{$encoded}</td>
                  <td>{$count}</td>
                </tr>

            candidateRow;
        }

        $summary .= "  </tbody>\n</table>\n";
        return $summary;
    }

    /**
     * @return array<string, int>
     */
    public function getTransfers(): array
    {
        $transfers = [];

        foreach ($this->ballots as $ballot) {
            if ($ballot->lastChoice !== null) {
                $candidate = $ballot->getCandidate();

                if (!isset($transfers[$candidate])) {
                    $transfers[$candidate] = 1;
                } else {
                    $transfers[$candidate]++;
                }
            }
        }

        return $transfers;
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

            // tally next preferences of each voter for this candidate
            $nextTally = $this->getNextPreferenceTally($candidate);
            $transferable = array_sum($nextTally);
            $transfers = [];

            foreach ($nextTally as $nextCandidate => $nextCount) {
                $toTransfer = (int) floor(($nextCount / $transferable) * $surplus);
                $details = "floor(({$nextCount} / {$transferable}) * {$surplus})";

                if ($toTransfer !== 0) {
                    $transfers[] = new CandidateCount($nextCandidate, $toTransfer, $details);
                }
            }

            $elected[] = new ElectedCandidate($candidate, $surplus, $transfers);
        }

        return $elected;
    }

    /**
     * @param ElectedCandidate[] $elected
     * @return Ballot[]
     */
    public function getNewBallots(array $elected, array $allElected, array $eliminated): array
    {
        $ballots = [];

        foreach ($this->ballots as $ballot) {
            $newBallot = $ballot->withFirstOpenPreference($allElected, $eliminated);

            if ($newBallot !== null) {
                $ballots[] = $newBallot;
            }
        }

        // add transfer ballots from any elected candidates
        foreach ($elected as $candidate) {
            foreach ($candidate->transfers as $transfer) {
                for ($i = 0; $i < $transfer->count; $i++) {
                    $ballots[] = new Ballot('', [$transfer->candidate]);
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
        $firstKey = array_key_first($tallyCopy);
        assert($firstKey !== null);

        $fewestVotes = $tallyCopy[$firstKey];
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

    /**
     * @return array<string, int>
     */
    private function getNextPreferenceTally(string $candidate): array
    {
        $tally = [];
        $excluded = array_merge($this->allElected, $this->allEliminated);

        foreach ($this->ballots as $ballot) {
            if ($ballot->getCandidate() === $candidate) {
                $nextPreference = $ballot->getNextPreference($excluded);

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
