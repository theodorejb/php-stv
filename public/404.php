<?php

use theodorejb\PhpStv\Page;

require '../vendor/autoload.php';

$html = <<<_html
    <h1>404</h1>
    <p class="lead">Sorry, this page doesn't exist.</p>
_html;

echo Page::render('404', $html);
