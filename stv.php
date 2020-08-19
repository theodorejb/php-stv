<?php

declare(strict_types=1);

use theodorejb\PhpStv\WikiParser;

require 'vendor/autoload.php';

$cacheFilename = 'stv_cache.html';
$rfcUrl = 'https://wiki.php.net/rfc/shorter_attribute_syntax_change';

if (!file_exists($cacheFilename)) {
    echo "Reading from {$rfcUrl}..." . PHP_EOL;
    $html = WikiParser::getHtml($rfcUrl);
    echo 'Updating cache...' . PHP_EOL;
    $result = @file_put_contents($cacheFilename, $html);

    if ($result === false) {
        throw new Exception('Failed to update cache');
    }
}

echo WikiParser::getElectionResults($cacheFilename, 1, 1, null, false, true);
