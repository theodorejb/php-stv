<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use DOMDocument, DOMNode, DOMNodeList, Exception;

class WikiParser
{
    /**
     * @return non-empty-string
     */
    public static function getHtml(string $fileOrUrl): string
    {
        $result = @\file_get_contents($fileOrUrl);

        if ($result === false || $result === '') {
            throw new Exception('Failed to fetch HTML');
        }

        return $result;
    }

    /**
     * @param non-empty-string $html
     */
    public static function getStvElection(string $html, bool $countInvalid, ?int $seats = null): StvElection
    {
        $stvLineNum = self::getStvLineNum($html);

        if ($stvLineNum === null) {
            // todo: also support plurality elections
            throw new Exception('Failed to detect STV election');
        }

        if ($seats === null) {
            $seats = 1;
            $checkForRm = true;
        } else {
            $checkForRm = false;
        }

        $polls = self::getPollsFromHtml($html);
        $stvPolls = [];

        foreach ($polls as $poll) {
            if ($poll->lineNumber < $stvLineNum) {
                continue;
            }

            $stvPolls[] = $poll;

            if ($checkForRm && str_contains($poll->name, " RM ")) {
                $seats = 2;
                $checkForRm = false;
            }

            if (count($stvPolls) === count($poll->candidates)) {
                break;
            }
        }

        return StvElection::fromPolls($stvPolls, $seats, $countInvalid);
    }

    public static function getStvLineNum(string $html): ?int
    {
        $lineNum = 1;
        $separator = "\r\n";
        $line = strtok($html, $separator);

        while ($line !== false) {
            if (
                str_contains($line, ">STV<") ||
                // for https://wiki.php.net/todo/php81, https://wiki.php.net/todo/php82, https://wiki.php.net/todo/php83
                str_contains($line, 'Single Transferable Vote')
            ) {
                return $lineNum;
            }

            $lineNum++;
            $line = strtok($separator);
        }

        return null;
    }

    /**
     * @param non-empty-string $html
     * @return Poll[]
     */
    public static function getPollsFromHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        libxml_use_internal_errors(false);

        $forms = $doc->getElementsByTagName('form');
        $rankedVotes = [];

        for ($i = 0; $i < $forms->count(); $i++) {
            $form = $forms->item($i);
            $nameAttr = $form->attributes->getNamedItem('name');

            if (!$nameAttr || $nameAttr->nodeValue !== 'doodle__form') {
                continue;
            }

            $table = self::getFirstTable($form->childNodes);

            if ($table === null) {
                continue;
            }

            $rankedVotes[] = self::getVoteInfo($table);
        }

        return $rankedVotes;
    }

    private static function getFirstTable(DOMNodeList $nodes): ?DOMNode
    {
        for ($i = 0; $i < $nodes->count(); $i++) {
            $node = $nodes->item($i);

            if ($node->nodeName === 'table') {
                return $node;
            }
        }

        return null;
    }

    private static function getRows(DOMNode $table): DOMNodeList
    {
        $children = $table->childNodes;
        $tbody = null;

        for ($i = 0; $i < $children->count(); $i++) {
            $child = $children->item($i);
            if ($child->nodeName === 'tbody') {
                $tbody = $child;
                break;
            }
        }

        if ($tbody === null) {
            throw new Exception('No tbody in table');
        }

        return $tbody->childNodes;
    }

    private static function getVoteInfo(DOMNode $table): Poll
    {
        $rows = self::getRows($table);
        $name = null;
        $pollClosed = false;
        $candidates = null;
        $votes = [];

        for ($i = 0; $i < $rows->count(); $i++) {
            $row = $rows->item($i);

            if ($row->nodeName !== 'tr') {
                continue;
            }

            if ($row->hasAttributes()) {
                /** @var \DOMNamedNodeMap $attributes */
                $attributes = $row->attributes;
                $classAttr = $attributes->getNamedItem('class');

                if (!$classAttr) {
                    throw new Exception('Missing class attribute for row');
                }

                if ($classAttr->nodeValue === 'row0') {
                    $name = trim($row->textContent);
                } elseif ($classAttr->nodeValue === 'row1') {
                    $candidates = self::getCandidates($row);
                } else {
                    throw new Exception('Unexpected class name for row');
                }
            } else {
                $vote = self::getVote($row);

                if ($vote === false) {
                    $pollClosed = true;
                } elseif ($vote !== null) {
                    $votes[] = $vote;
                }
            }
        }

        if ($name === null) {
            throw new Exception('Failed to find vote name');
        }

        if ($candidates === null) {
            throw new Exception('Failed to find candidates');
        }

        return new Poll($name, $candidates, $votes, $pollClosed, $table->getLineNo());
    }

    /**
     * @return string[]
     */
    private static function getCandidates(DOMNode $row): array
    {
        $candidates = [];

        for ($i = 0; $i < $row->childNodes->count(); $i++) {
            $child = $row->childNodes->item($i);

            if ($child->nodeName === 'td') {
                $candidates[] = $child->textContent;
            }
        }

        return $candidates;
    }

    /**
     * @return Vote|null|false
     */
    private static function getVote(DOMNode $row)
    {
        $username = null;
        $candidateIndex = 0;

        for ($i = 0; $i < $row->childNodes->count(); $i++) {
            $child = $row->childNodes->item($i);

            if ($child->nodeName === 'th' &&
                (trim($child->textContent) === 'Count:' || trim($child->textContent) === 'Final result:')
            ) {
                return null; // tally row
            }

            if ($child->nodeName === 'td' && trim($child->textContent) === 'This poll has been closed.') {
                return false; // poll closed row
            }

            if ($child->nodeName !== 'td') {
                continue;
            }

            // first td is username
            if ($username === null) {
                $username = trim($child->textContent);
                continue;
            }

            if (self::containsImg($child)) {
                return new Vote($username, $candidateIndex);
            }

            $candidateIndex++;
        }

        throw new Exception("Failed to find vote for user {$username}");
    }

    private static function containsImg(DOMNode $node): bool
    {
        for ($i = 0; $i < $node->childNodes->count(); $i++) {
            $child = $node->childNodes->item($i);

            if ($child->nodeName === 'img') {
                return true;
            }
        }

        return false;
    }
}
