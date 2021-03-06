<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP STV results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">STV results</span>
    </div>
</nav>
<div class="container">
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

    echo '<p style="font-size: 0.875rem" class="text-break mt-2">Reading from <a href="' . $url . '">' . $url . '</a>...</p>';

    $html = WikiParser::getHtml($url);
    $election = WikiParser::getStvElection($html, $countInvalid);
    echo $election->getResultsHtml($showInvalid, $showCounted);

    if ($rfc === 'shorter_attribute_syntax_change') {
        echo "<p>
                <a href='https://theodorejb.me/2020/08/21/why-atat-is-the-best-attribute-syntax-for-php/'>
                Why @@ is the best attribute syntax for PHP</a></p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
</div>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-text">
          Created with ❤️ by Theodore Brown
        </span>
        <a class="navbar-text" href="https://github.com/theodorejb/php-stv">Source</a>
    </div>
</nav>
</body>
</html>
