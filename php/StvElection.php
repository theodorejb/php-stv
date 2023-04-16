<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use Exception;

class StvElection
{
    public int $quota;
    public string $quotaFormula;

    /** @var Ballot[] */
    public array $validBallots = [];

    /** @var Ballot[] */
    public array $invalidBallots = [];

    /**
     * @param Ballot[] $ballots
     * @param string[] $candidates
     */
    public function __construct(
        array $ballots,
        public array $candidates,
        public int $seats,
        public bool $isClosed,
        private bool $countInvalid,
    ) {
        foreach ($ballots as $ballot) {
            $unique = [];
            $isValid = true;

            foreach ($ballot->rankedChoices as $preference) {
                if (isset($unique[$preference])) {
                    $isValid = false;
                    continue;
                }

                $unique[$preference] = true;
            }

            if ($isValid || $countInvalid) {
                // if invalid, only count the highest preference for each candidate
                $this->validBallots[] = new Ballot($ballot->name, array_keys($unique));
            }

            if (!$isValid) {
                $this->invalidBallots[] = $ballot;
            }
        }

        $votesCast = count($this->validBallots);
        // Droop quota formula
        $this->quota = (int) floor($votesCast / ($this->seats + 1)) + 1;
        $this->quotaFormula = "floor({$votesCast} / ({$this->seats} + 1)) + 1";
    }

    public function getResultsHtml(bool $showInvalid, bool $showCounted): string
    {
        $output = $this->getSummaryHtml($showCounted, $showInvalid);
        $rounds = $this->runElection();

        foreach ($rounds as $round) {
            $output .= "\n<div class=\"container p-3 mb-4 shadow-sm\">\n";
            $output .= $round->getSummaryHtml() . "\n";

            foreach ($round->elected as $candidate) {
                $encodedName = Utils::encodeHtml($candidate->name);
                $output .= <<<elected

                <p class="alert alert-success" role="alert">
                  <b>{$encodedName}</b> elected with {$candidate->surplus} surplus votes 🎉
                </p>

                elected;

                if (count($candidate->transfers) !== 0) {
                    $output .= "\n<p>➕ Distributing surplus with {$candidate->transferable} transferable ballots:</p>\n";
                    $output .= "<ul>\n";

                    foreach ($candidate->transfers as $transfer) {
                        $transferValue = round($transfer->getValue(), 3);
                        $output .= "  <li>" . Utils::encodeHtml($transfer->candidate) . ": <b>+{$transferValue}</b>";
                        $output .= "  (" . Utils::encodeHtml($transfer->details) . ")</li>\n";
                    }

                    $output .= "</ul>\n";
                }
            }

            if (count($round->eliminated) !== 0) {
                $displayCount = round($round->eliminated[0]->count, 3);
                $output .= "<h4>⛔ Eliminated ({$displayCount} votes)</h4>\n";
                $output .= "<ul>\n";

                foreach ($round->eliminated as $cc) {
                    $output .= "  <li>" . Utils::encodeHtml($cc->candidate) . "</li>\n";
                }

                $output .= "</ul>\n";
            }

            $output .= "</div>\n";
        }

        return $output;
    }

    public function getSummaryHtml(bool $listVotes, bool $showInvalid): string
    {
        $summary = "<h2>" . count($this->candidates) . " Candidates (in order of ballot)</h2>\n";
        $summary .= "<ul>\n";

        foreach ($this->candidates as $candidate) {
            $summary .= "  <li>" . Utils::encodeHtml($candidate) . "</li>\n";
        }

        $summary .= "</ul>\n";

        if (count($this->invalidBallots) > 0 && $showInvalid) {
            $summary .= Utils::getBallotsHtml($this->invalidBallots, 'Invalid Ballots ❌');

            if ($this->countInvalid) {
                $summary .= <<<countInfo

                <div class="alert alert-secondary" role="alert">
                  ⚠️ Only the highest preference expressed for each candidate is counted.
                </div>

                countInfo;
            }
        }

        $votes = count($this->validBallots);
        $summary .= <<<infoTable

        <h2>🗒️ Info</h2>
        <table class="table">
          <tr>
            <th scope="row">Votes</th>
            <td>{$votes}</td>
          </tr>
          <tr>
            <th scope="row">Seats</th>
            <td>{$this->seats}</td>
          </tr>
          <tr>
            <th scope="row">Quota</th>
            <td>{$this->quotaFormula} = <b>{$this->quota}</b></td>
          </tr>
        </table>

        infoTable;

        if (!$this->isClosed) {
            $summary .= <<<inProgress

            <div class="alert alert-warning" role="alert">
              ⚠️ Note: voting is in progress and these results are not final!
            </div>

            inProgress;
        }

        if ($listVotes) {
            $summary .= Utils::getBallotsHtml($this->validBallots, 'Votes', true);
        }

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

        while (count($candidates) !== 0) {
            $roundNum++;
            $baseTally = [];

            foreach ($candidates as $candidate) {
                if (!isset($allElected[$candidate])) {
                    $baseTally[$candidate] = 0;
                }
            }

            if (count($baseTally) === 0) {
                break; // there was a tie
            }

            $round = new ElectionRound($roundNum, $ballots, $baseTally, $this);
            $pastRounds[] = $round;

            foreach ($round->elected as $e) {
                $allElected[$e->name] = true;
            }

            foreach ($round->eliminated as $cc) {
                $allEliminated[$cc->candidate] = true;
            }

            if (count($allElected) === $this->seats) {
                break; // don't set transfers if all seats filled
            }

            $round->setElectedTransfers(array_merge($allElected, $allEliminated));
            $newCandidates = [];

            foreach ($candidates as $candidate) {
                if (!isset($allEliminated[$candidate])) {
                    $newCandidates[] = $candidate;
                }
            }

            $candidates = $newCandidates;
            $ballots = $round->getNewBallots($round->elected, $allElected, $allEliminated);
        }

        return $pastRounds;
    }

    /**
     * @param Poll[] $polls
     */
    public static function fromPolls(array $polls, int $seats, bool $countInvalid): self
    {
        if (count($polls) === 0) {
            throw new Exception('Failed to find any votes');
        }

        $isClosed = true;
        $candidates = [];
        /** @var array<string|int, list<string>> $allBallots */
        $allBallots = [];

        foreach ($polls as $poll) {
            if ($candidates === []) {
                $candidates = $poll->candidates;
            } elseif ($poll->candidates !== $candidates) {
                throw new Exception("Candidate list doesn't match for {$poll->name} vote");
            }

            if (!$poll->isClosed) {
                $isClosed = false;
            }

            foreach ($poll->votes as $vote) {
                $allBallots[$vote->username][] = $candidates[$vote->candidateIndex];
            }
        }

        $ballots = [];

        foreach ($allBallots as $username => $rankedChoices) {
            $ballots[] = new Ballot((string) $username, $rankedChoices);
        }

        return new self($ballots, $candidates, $seats, $isClosed, $countInvalid);
    }
}
