<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use DOMDocument, DOMNode, DOMNodeList, Exception;

class WikiParser
{
    public static function getHtml(string $fileOrUrl): string
    {
        $result = @\file_get_contents($fileOrUrl);

        if ($result === false) {
            throw new Exception('Failed to fetch HTML');
        }

        return $result;
    }

    public static function getElectionResults(string $fileOrUrl, int $seats, int $firstVoteIndex, ?int $numPolls, bool $countInvalid, bool $showInvalid): string
    {
        $output = "Reading from {$fileOrUrl}..." . PHP_EOL . PHP_EOL;
        $html = WikiParser::getHtml($fileOrUrl);
        $preferenceVotes = WikiParser::getVotesFromHtml($html, $firstVoteIndex, $numPolls);
        $election = new StvElection($preferenceVotes, $seats, $countInvalid);

        $output .= $election->getSummary(false, $showInvalid);
        $rounds = $election->runElection();

        foreach ($rounds as $round) {
            $output .= $round->getSummary() . PHP_EOL;
            $elected = $round->elected;

            foreach ($elected as $candidate) {
                $output .= "{$candidate->name} elected with {$candidate->surplus} surplus votes" . PHP_EOL;

                if (count($candidate->transfers) !== 0) {
                    $output .= "Distributing surplus votes" . PHP_EOL . PHP_EOL;
                }

                foreach ($candidate->transfers as $transfer) {
                    $output .= "{$transfer->candidate}: +{$transfer->count}  {$transfer->details}" . PHP_EOL;
                }
            }

            foreach ($round->eliminated as $cc) {
                $output .= "Eliminating {$cc->candidate}" . PHP_EOL;
            }
        }

        return $output;
    }

    /**
     * @return PreferenceVotes[]
     */
    public static function getVotesFromHtml(string $html, int $firstVoteIndex, ?int $numPolls): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        libxml_use_internal_errors(false);

        $parser = new self();
        return $parser->getPreferenceVotes($doc, $firstVoteIndex, $numPolls);
    }

    /**
     * @return PreferenceVotes[]
     */
    public function getPreferenceVotes(DOMDocument $doc, int $firstVoteIndex, ?int $numPolls = null): array
    {
        $forms = $doc->getElementsByTagName('form');
        $index = -1;
        $rankedVotes = [];

        for ($i = 0; $i < $forms->count(); $i++) {
            $form = $forms->item($i);
            $nameAttr = $form->attributes->getNamedItem('name');

            if (!$nameAttr || $nameAttr->nodeValue !== 'doodle__form') {
                continue;
            }

            $index++;

            if ($index < $firstVoteIndex) {
                continue;
            }

            $table = $this->getFirstTable($form->childNodes);

            if ($table === null) {
                continue;
            }

            $rankedVotes[] = $this->getVoteInfo($table);

            if ($numPolls !== null && $index === $firstVoteIndex + $numPolls - 1) {
                break;
            }
        }

        return $rankedVotes;
    }

    public function getFirstTable(DOMNodeList $nodes): ?DOMNode
    {
        for ($i = 0; $i < $nodes->count(); $i++) {
            $node = $nodes->item($i);

            if ($node->nodeName === 'table') {
                return $node;
            }
        }

        return null;
    }

    public function getRows(DOMNode $table): DOMNodeList
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

    public function getVoteInfo(DOMNode $table): PreferenceVotes
    {
        $rows = $this->getRows($table);
        $name = null;
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
                    $candidates = $this->getCandidates($row);
                } else {
                    throw new Exception('Unexpected class name for row');
                }
            } else {
                $vote = $this->getVote($row);

                if ($vote !== null) {
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

        return new PreferenceVotes($name, $candidates, $votes);
    }

    /**
     * @return string[]
     */
    public function getCandidates(DOMNode $row): array
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

    public function getVote(DOMNode $row): ?Vote
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
                return null; // poll closed row
            }

            if ($child->nodeName !== 'td') {
                continue;
            }

            // first td is username
            if ($username === null) {
                $username = trim($child->textContent);
                continue;
            }

            if ($this->containsImg($child)) {
                return new Vote($username, $candidateIndex);
            }

            $candidateIndex++;
        }

        throw new Exception("Failed to find vote for user {$username}");
    }

    public function containsImg(DOMNode $node): bool
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
