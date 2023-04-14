<?php

use theodorejb\PhpStv\{Page, WikiParser};

require '../../vendor/autoload.php';

$title = 'PHP STV results';

try {
    $countInvalid = (bool) ($_GET['countInvalid'] ?? true);
    $showInvalid = (bool) ($_GET['showInvalid'] ?? false);
    $showCounted = (bool) ($_GET['showCounted'] ?? false);
    /** @var string $rfc */
    $rfc = $_GET['rfc'] ?? '';
    /** @var string $electionUrl */
    $electionUrl = $_GET['election'] ?? '';

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

    $html = '<p class="text-break"><small>Reading from <a href="' . $url . '">' . $url . '</a>...</small></p>';

    $election = WikiParser::getStvElection(WikiParser::getHtml($url), $countInvalid);
    $html .= $election->getResultsHtml($showInvalid, $showCounted);

    if ($rfc === 'shorter_attribute_syntax_change') {
        $html .= "<p>
                <a href='https://theodorejb.me/2020/08/21/why-atat-is-the-best-attribute-syntax-for-php/'>
                Why @@ is the best attribute syntax for PHP</a></p>";
    }
} catch (Exception $e) {
    $message = htmlspecialchars($e->getMessage());
    $html = <<<_html
        <div class="alert alert-danger" role="alert">
            {$message}
        </div>
    _html;
}

echo Page::render($title, $html);
