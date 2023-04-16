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
     */
    public function __construct(
        public int $round,
        public array $ballots,
        array $baseTally,
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

            $elected[] = new ElectedCandidate($candidate, $surplus);
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
                array_push($ballots, ...$transfer->ballots);
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
     * @param array<string, true> $excluded
     */
    public function setElectedTransfers(array $excluded): void
    {
        foreach ($this->elected as $e) {
            // tally next preferences of each voter for this candidate
            $nextPreferences = $this->getNextPreferences($e->name, $excluded);
            $nextTally = array_map(fn($np) => count($np), $nextPreferences);
            $e->transferable = array_sum($nextTally);
            $transferValue = ($e->transferable !== 0) ? $e->surplus / $e->transferable : 0;
            $e->transfers = [];

            foreach ($nextPreferences as $nextCandidate => $ballots) {
                $nextCount = count($ballots);
                $toTransfer = (int) floor($nextCount * $transferValue);
                $details = "floor({$nextCount} * ({$e->surplus} / {$e->transferable}))";

                if ($toTransfer !== 0) {
                    $transferBallots = array_slice($ballots, 0, $toTransfer);
                    $e->transfers[] = new CandidateTransfers($nextCandidate, $details, $transferBallots);
                }
            }
        }
    }

    /**
     * @param array<string, true> $excluded
     * @return array<string, Ballot[]>
     */
    private function getNextPreferences(string $candidate, array $excluded): array
    {
        $preferences = [];

        foreach ($this->ballots as $ballot) {
            if ($ballot->getCandidate() === $candidate) {
                $nextPreference = $ballot->getNextPreference($excluded, false);

                if ($nextPreference !== null) {
                    $nextCandidate = $nextPreference->getCandidate();
                    $preferences[$nextCandidate][] = $nextPreference;
                }
            }
        }

        return $preferences;
    }
}
