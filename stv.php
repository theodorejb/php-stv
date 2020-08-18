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
    $html = WikiParser::getHtml($rfcUrl);
    echo 'Updating cache...' . PHP_EOL;
    $result = @file_put_contents($cacheFilename, $html);

    if ($result === false) {
        throw new Exception('Failed to update cache');
    }
}

echo "Reading from {$cacheFilename}..." . PHP_EOL;
$html = WikiParser::getHtml($cacheFilename);
$preferenceVotes = WikiParser::getVotesFromHtml($html, $firstVoteIndex, $numPreferences);
$election = new StvElection($preferenceVotes, $seats, false);

echo PHP_EOL . $election->getSummary();
$rounds = $election->runElection();

foreach ($rounds as $round) {
    echo $round->getSummary() . PHP_EOL;
    $elected = $round->elected;

    foreach ($elected as $candidate) {
        echo "{$candidate->name} elected with {$candidate->surplus} surplus votes" . PHP_EOL;

        if (count($candidate->transfers) !== 0) {
            echo "Distributing surplus votes" . PHP_EOL . PHP_EOL;
        }

        foreach ($candidate->transfers as $transfer) {
            echo "{$transfer->candidate}: +{$transfer->count}  {$transfer->details}" . PHP_EOL;
        }
    }

    foreach ($round->eliminated as $cc) {
        echo "Eliminating {$cc->candidate}" . PHP_EOL;
    }
}
