<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP STV results</title>
    <meta name="viewport" content="width=device-width">
</head>
<body>
<?php

use theodorejb\PhpStv\WikiParser;

require '../../vendor/autoload.php';

// todo: handle plurality elections and detect stv vs. plurality votes
//  if page contains text " STV " before vote doodle, treat following doodles as stv election
//  (otherwise as separate plurality polls)
// if certain header detected, stop counting stv election (for rm example)
// and detect following polls as stv or plurality as before
// default to one seat unless find text about release manager, then 2

try {
    $rfc = $_GET['rfc'] ?? null;

    if (!$rfc) {
        throw new Exception('Missing required rfc parameter');
    }

    $rfcUrl = 'https://wiki.php.net/rfc/' . $rfc;
    $seats = (int) ($_GET['seats'] ?? 1);
    $numPreferences = $_GET['numPolls'] ?? null;

    if ($numPreferences !== null) {
        $numPreferences = (int) $numPreferences;
    }

    $firstVoteIndex = (int) ($_GET['firstVoteIndex'] ?? 1);
    $countInvalid = (bool) ($_GET['countInvalid'] ?? false);
    $showInvalid = (bool) ($_GET['showInvalid'] ?? false);

    $results = WikiParser::getElectionResults($rfcUrl, $seats, $firstVoteIndex, $numPreferences, $countInvalid, $showInvalid);
    echo p($results);

    if ($rfc === 'shorter_attribute_syntax_change') {
        echo '<p style="margin: 2em 0; font-weight: bold">
                Note: these results are not official/final!
            </p>';
    }
} catch (Exception $e) {
    echo p("Error: {$e->getMessage()}");
}

function p(string $html): string
{
    return '<p><pre>' . htmlspecialchars($html) . '</pre></p>';
}
?>
<p>
    Created with ❤️ by Theodore Brown
</p>
</body>
</html>
