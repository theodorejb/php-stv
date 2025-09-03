<?php

use theodorejb\PhpStv\Page;

require '../vendor/autoload.php';

$html = <<<_html
    <p>Last updated 2025-09-02</p>
    
    <div class="container p-3 pb-1 mb-4 shadow-sm">
        <h2>RFCs</h2>
        <ul>
            <li><a href="/php?rfc=shorter_attribute_syntax_change">Shorter Attribute Syntax Change</a> (2020-08-04)</li>
            <li><a href="/php?rfc=shorter_attribute_syntax">Shorter Attribute Syntax</a> (2020-06-03)</li>
        </ul>
    </div>
    
    <div class="container p-3 pb-1 mb-4 shadow-sm">
        <h2>Release manager elections</h2>
        <ul>
            <li><a href="/php?election=todo/php85">PHP 8.5</a> (2025-04-02)</li>
            <li><a href="/php?election=todo/php84">PHP 8.4</a> (2024-04-02)</li>
            <li><a href="/php?election=todo/php83">PHP 8.3</a> (2023-04-01)</li>
            <li><a href="/php?election=todo/php82">PHP 8.2</a> (2022-05-11)</li>
            <li><a href="/php?election=todo/php81">PHP 8.1</a> (2021-04-12)</li>
            <li><a href="/php?election=todo/php80">PHP 8.0</a> (2020-04-07)</li>
        </ul>
    </div>
    
    <div class="container p-3 pb-1 mb-4 shadow-sm">
        <h2>Straw polls</h2>
        <ul>
            <li><a href="/php?rfc=deque_straw_poll">Naming pattern to use for Deque</a> (2022-01-05)</li>
            <li><a href="/php?rfc=any_all_on_iterable_straw_poll_namespace">
                Using namespaces for *any() and *all() on iterables</a> (2021-01-05)</li>
        </ul>
    </div>
_html;

echo Page::render('STV elections', $html);
