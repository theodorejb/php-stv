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

    private bool $countInvalid;

    /**
     * @param Poll[] $polls
     */
    public function __construct(array $polls, int $seats, bool $countInvalid = false)
    {
        $this->seats = $seats;
        $this->countInvalid = $countInvalid;
        $this->setBallots($polls);

        $votesCast = count($this->validBallots);
        // Droop quota formula
        $this->quota = (int) floor($votesCast / ($this->seats + 1)) + 1;
    }

    public function getResultsHtml(bool $showInvalid, bool $showCounted): string
    {
        $output = $this->getSummaryHtml($showCounted, $showInvalid);
        $rounds = $this->runElection();
        $lastIndex = count($rounds) - 1;

        foreach ($rounds as $index => $round) {
            $output .= "\n<div class=\"container p-3 mb-4 shadow-sm\">\n";
            $output .= $round->getSummaryHtml() . "\n";

            foreach ($round->elected as $candidate) {
                $encodedName = Utils::encodeHtml($candidate->name);
                $output .= <<<elected

                <p class="alert alert-success" role="alert">
                  <b>{$encodedName}</b> elected with {$candidate->surplus} surplus votes 🎉
                </p>

                elected;

                if ($index !== $lastIndex) {
                    if (count($candidate->transfers) !== 0) {
                        $output .= "\n<p>➕ Distributing surplus votes...</p>\n";
                        $output .= "<ul>\n";

                        foreach ($candidate->transfers as $transfer) {
                            $output .= "  <li>" . Utils::encodeHtml($transfer->candidate) . ": <b>+{$transfer->count}</b>";
                            $output .= "  " . Utils::encodeHtml($transfer->details) . "</li>\n";
                        }

                        $output .= "</ul>\n";
                    }
                }
            }

            if (count($round->eliminated) !== 0) {
                $output .= "<h4>⛔ Eliminated (" . $round->eliminated[0]->count . " votes)</h4>\n";
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
            <td>floor({$votes} / ({$this->seats} + 1)) + 1 = <b>{$this->quota}</b></td>
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
     * @param Poll[] $polls
     */
    private function setBallots(array $polls): void
    {
        if (count($polls) === 0) {
            throw new Exception('Failed to find any votes');
        }

        $this->isClosed = true;
        $this->candidates = [];
        $this->allBallots = [];

        foreach ($polls as $poll) {
            if ($this->candidates === []) {
                $this->candidates = $poll->candidates;
            } elseif ($poll->candidates !== $this->candidates) {
                throw new Exception("Candidate list doesn't match for {$poll->name} vote");
            }

            if (!$poll->isClosed) {
                $this->isClosed = false;
            }

            foreach ($poll->votes as $vote) {
                $this->allBallots[$vote->username][] = $this->candidates[$vote->candidateIndex];
            }
        }

        $this->setValidBallots();
    }

    private function setValidBallots(): void
    {
        $this->validBallots = [];
        $this->invalidBallots = [];

        foreach ($this->allBallots as $username => $ballot) {
            $unique = [];
            $isValid = true;

            foreach ($ballot as $preference) {
                if (isset($unique[$preference])) {
                    $isValid = false;
                    continue;
                }

                $unique[$preference] = true;
            }

            if ($isValid || $this->countInvalid) {
                // if invalid, only count highest preference for each candidate
                $this->validBallots[] = new Ballot((string) $username, array_keys($unique));
            }

            if (!$isValid) {
                $this->invalidBallots[] = new Ballot((string) $username, $ballot);
            }
        }
    }
}
