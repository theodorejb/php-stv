<?php

declare(strict_types=1);

use theodorejb\PhpStv\{StvElection, WikiParser};

require 'vendor/autoload.php';

$cacheFilename = 'stv_cache.html';
$rfcUrl = 'https://wiki.php.net/rfc/shorter_attribute_syntax_change';
$seats = 1;
$numPreferences = null;
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
$preferenceVotes = $parser->getPreferenceVotes($doc, $firstVoteIndex, $numPreferences);
$election = new StvElection($preferenceVotes, $seats, true);

echo PHP_EOL;
echo 'Candidates (in order of ballot):' . PHP_EOL;
echo implode("  -   ", $election->candidates) . PHP_EOL . PHP_EOL;

$invalidBallotCount = count($election->invalidBallots);
$index = $invalidBallotCount * -1;

if ($invalidBallotCount > 0) {
    echo $invalidBallotCount . ' invalid ballots:' . PHP_EOL;
    foreach ($election->invalidBallots as $ballot) {
        $index++;
        echo "{$ballot->name}:   ";
        echo implode("  -   ", $ballot->rankedChoices) . PHP_EOL;
    }
}

if (false) {
    echo PHP_EOL . 'Votes:' . PHP_EOL;
    $index = 0;
    foreach ($election->validBallots as $ballot) {
        $index++;
        echo "Vote #{$index}:   ";
        echo implode("  -   ", $ballot->rankedChoices) . PHP_EOL;
    }
}

echo PHP_EOL . $election->getSummary();
$rounds = $election->runElection();

foreach ($rounds as $round) {
    echo $round->getSummary() . PHP_EOL;
    $elected = $round->elected;

    foreach ($elected as $candidate) {
        echo "Candidate {$candidate->name} reached quota and is elected with {$candidate->surplus} surplus votes" . PHP_EOL;

        if ($candidate->surplus !== 0) {
            echo "Distributing surplus votes" . PHP_EOL . PHP_EOL;
        }

        foreach ($candidate->transfers as $transfer) {
            echo "Candidate {$transfer->candidate}: +{$transfer->count}" . PHP_EOL;
        }
    }

    foreach ($round->eliminated as $cc) {
        echo "Eliminating {$cc->candidate} with {$cc->count} votes" . PHP_EOL . PHP_EOL;
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
