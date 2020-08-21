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
    $seats = 1;
    $firstVoteIndex = 1;
    $numPolls = null;
    $countInvalid = (bool) ($_GET['countInvalid'] ?? false);
    $showInvalid = (bool) ($_GET['showInvalid'] ?? false);
    $showCounted = (bool) ($_GET['showCounted'] ?? false);
    $rfc = $_GET['rfc'] ?? null;
    $electionUrl = $_GET['election'] ?? null;

    if ($rfc) {
        if (!preg_match('/^\w+$/', $rfc)) {
            throw new Exception('Invalid rfc name');
        }

        $url = 'https://wiki.php.net/rfc/' . $rfc;
    } elseif ($electionUrl) {
        if (!preg_match('/^(\w+(\/\w+)*)$/', $electionUrl)) {
            throw new Exception('Invalid election url');
        }

        $url = 'https://wiki.php.net/' . $electionUrl;
    } else {
        throw new Exception('Missing required rfc parameter');
    }

    if ($electionUrl !== null && strpos($electionUrl, 'todo/') === 0) {
        // defaults for RM election
        $seats = 2;
        $firstVoteIndex = 0;
        $numPolls = 4;
    }

    if (isset($_GET['seats'])) {
        $seats = (int) $_GET['seats'];
    }

    if (isset($_GET['numPolls'])) {
        $numPolls = (int) $_GET['numPolls'];
    }

    if (isset($_GET['firstVoteIndex'])) {
        $firstVoteIndex = (int) $_GET['firstVoteIndex'];
    }

    echo "<p>Reading from <a href=\"{$url}\">{$url}</a>...</p>";

    $html = WikiParser::getHtml($url);
    $election = WikiParser::getElection($html, $seats, $firstVoteIndex, $numPolls, $countInvalid);
    $results = $election->getResults($showInvalid, $showCounted);
    echo p($results);

    if (!$election->isClosed) {
        echo '<p style="margin: 2em 0; font-weight: bold">
                Note: voting is in progress and these results are not final!
            </p>';
    }

    if ($rfc === 'shorter_attribute_syntax_change') {
        echo "<p>
                <a href='https://theodorejb.me/2020/08/21/why-atat-is-the-best-attribute-syntax-for-php/'>
                Why @@ is the best attribute syntax for PHP</a></p>";
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
