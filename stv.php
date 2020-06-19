<?php

declare(strict_types=1);

use theodorejb\PhpStv\{StvElection, WikiParser};

require 'vendor/autoload.php';

$cacheFilename = 'stv_cache.html';
$rfcUrl = 'https://wiki.php.net/rfc/shorter_attribute_syntax';
$seats = 1;
$firstVoteIndex = 1; // ignore primary vote

if (!file_exists($cacheFilename)) {
    echo "Reading from {$rfcUrl}..." . PHP_EOL;
    $html = getHtml($rfcUrl);
    echo 'Updating cache...' . PHP_EOL;
    $result = @file_put_contents($cacheFilename, $html);

    if ($result === false) {
        throw new Exception('Failed to update cache');
    }
}

echo "Reading from {$cacheFilename}..." . PHP_EOL;
$html = getHtml($cacheFilename);

libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML($html);
libxml_use_internal_errors(false);

$parser = new WikiParser();
$preferenceVotes = $parser->getPreferenceVotes($doc, $firstVoteIndex);
$election = new StvElection($preferenceVotes, $seats);

echo PHP_EOL;
echo 'Candidates (in order of ballot):' . PHP_EOL;
echo implode("  -   ", $election->candidates) . PHP_EOL . PHP_EOL;

echo 'Invalid votes:' . PHP_EOL;
$index = 0;
foreach ($election->invalidBallots as $ballot) {
    $index++;
    echo "Vote #{$index}:   ";
    echo implode("  -   ", $ballot) . PHP_EOL;
}

echo PHP_EOL;
echo 'Votes:' . PHP_EOL;
$index = 0;
foreach ($election->validBallots as $ballot) {
    $index++;
    echo "Vote #{$index}:   ";
    echo implode("  -   ", $ballot) . PHP_EOL;
}

echo PHP_EOL;
echo 'Votes: ' . count($election->validBallots) . PHP_EOL;
echo 'Candidates: ' . count($election->candidates) . PHP_EOL;
echo 'Seats: ' . $seats . PHP_EOL;
echo 'Quota: ' . $election->getQuota() . PHP_EOL;

$round = 0;
$seatsFilled = 0;
$tally = [];

foreach ($election->candidates as $candidate) {
    $tally[$candidate] = 0;
}

foreach ($election->validBallots as $validBallot) {
    $tally[$validBallot[0]]++;
}

while ($seatsFilled < $seats) {
    $round++;
    echo PHP_EOL . "Round #{$round}" . PHP_EOL;
    echo '--------' . PHP_EOL . PHP_EOL;
    echo 'Tally:' . PHP_EOL;

    foreach ($tally as $candidate => $count) {
        echo "Candidate {$candidate}: {$count}" . PHP_EOL;
    }

    echo PHP_EOL;
    $fewestVotes = null;

    foreach ($tally as $candidate => $count) {
        if ($fewestVotes === null || $fewestVotes > $count) {
            $fewestVotes = $count;
        }

        if ($count >= $election->getQuota()) {
            echo "Candidate {$candidate} reached quota and is elected!" . PHP_EOL;
            $seatsFilled++;

            // todo: distribute extra votes
            /*
             * In this case, 8 of the 12 voters for Chocolate had the second preference of Strawberries,
             * so (8/12)•6 = 4 of Chocolate's votes would transfer to Strawberries; meanwhile 4 of the 12
             * voters for Chocolate had Hamburgers as their second preference, so (4/12)•6 = 2 of Chocolate's
             * votes will transfer to Hamburgers. Thus, Strawberries has 1 first-preference votes and 4 new votes,
             * for an updated total of 1+4 = 5 votes; likewise, Hamburgers now has 1 + 2 = 3 votes; no other
             * tallies change. Even with the transfer of this surplus no candidate has reached the quota.
             * Therefore, Pear, which now has the fewest votes (after the update), is eliminated.
             */
        }
    }

    if ($seatsFilled < $seats) {
        foreach ($tally as $candidate => $count) {
            if ($count === $fewestVotes) {
                echo "Eliminating {$candidate} with {$count} votes" . PHP_EOL . PHP_EOL;
                echo "Distributing votes to next preference:" . PHP_EOL;

                $tally = [];

                foreach ($election->candidates as $candidate) {
                    $tally[$candidate] = 0;
                }

                $nextTally = $tally;

                foreach ($election->validBallots as $validBallot) {
                    if ($validBallot[0] === $candidate) {
                        // todo: avoid hardcoding index
                        if (isset($validBallot[1])) {
                            $nextTally[$validBallot[1]]++;
                        }
                    } else {
                        $tally[$validBallot[0]]++;
                    }
                }

                // remove candidates that tie with fewest votes and redistribute
                $total = array_sum($nextTally);

                foreach ($nextTally as $nextCandidate => $nextCount) {
                    $toRedistribute = floor($nextCount / $total * $count);

                    if ($toRedistribute !== 0) {
                        echo "Candidate {$nextCandidate}: +{$toRedistribute}" . PHP_EOL;
                        $tally[$nextCandidate] += $toRedistribute;
                    }
                }
            }
        }
    }
}

function getHtml(string $fileOrUrl): string
{
    $result = @file_get_contents($fileOrUrl);

    if ($result === false) {
        throw new Exception('Failed to fetch HTML');
    }

    return $result;
}
