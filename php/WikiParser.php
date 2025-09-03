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
    public static function getStvElection(string $html, bool $countInvalid = true, ?int $seats = null): StvElection
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
        $doc->loadHTML($html, LIBXML_BIGLINES);
        libxml_use_internal_errors(false);

        $forms = $doc->getElementsByTagName('form');
        $rankedVotes = [];

        for ($i = 0; $i < $forms->count(); $i++) {
            /** @var DOMNode $form */
            $form = $forms->item($i);
            $nameAttr = $form->attributes?->getNamedItem('name');

            if ($nameAttr?->nodeValue !== 'doodle__form') {
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
            /** @var DOMNode $node */
            $node = $nodes->item($i);

            if ($node->nodeName === 'table') {
                return $node;
            }
        }

        return null;
    }

    private static function getNthChildByName(DOMNode $node, string $name, int $n = 0): DOMNode
    {
        $children = $node->childNodes;
        $index = 0;

        for ($i = 0; $i < $children->count(); $i++) {
            /** @var DOMNode $child */
            $child = $children->item($i);
            if ($child->nodeName === $name) {
                if ($index === $n) {
                    return $child;
                }
                $index++;
            }
        }

        throw new Exception("Failed to find child $n $name");
    }

    private static function getVoteInfo(DOMNode $table): Poll
    {
        $thead = self::getNthChildByName($table, 'thead');
        $titleRow = self::getNthChildByName($thead, 'tr');
        $name = trim($titleRow->textContent);
        $candidatesRow = self::getNthChildByName($thead, 'tr', 1);
        $candidates = self::getCandidates($candidatesRow);

        $rows = self::getNthChildByName($table, 'tbody')->childNodes;
        $pollClosed = false;
        $votes = [];

        for ($i = 0; $i < $rows->count(); $i++) {
            /** @var DOMNode $row */
            $row = $rows->item($i);

            if ($row->nodeName !== 'tr') {
                continue;
            }

            $vote = self::getVote($row);

            if ($vote === false) {
                $pollClosed = true;
            } elseif ($vote !== null) {
                $votes[] = $vote;
            }
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
            /** @var DOMNode $child */
            $child = $row->childNodes->item($i);

            if ($child->nodeName === 'th') {
                if ($child->hasAttributes()) {
                    /** @var \DOMNamedNodeMap $attributes */
                    $attributes = $child->attributes;
                    $classAttr = $attributes->getNamedItem('class');

                    if ($classAttr?->nodeValue === 'fields_caption') {
                        continue;
                    }
                }

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
            /** @var DOMNode $child */
            $child = $row->childNodes->item($i);

            if ($child->nodeName === 'td' &&
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
            /** @var DOMNode $child */
            $child = $node->childNodes->item($i);

            if ($child->nodeName === 'img') {
                return true;
            }
        }

        return false;
    }
}
