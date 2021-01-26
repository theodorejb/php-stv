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

try {
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

    echo "<p>Reading from <a href=\"{$url}\">{$url}</a>...</p>";

    $html = WikiParser::getHtml($url);
    $election = WikiParser::getStvElection($html, $countInvalid);
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
