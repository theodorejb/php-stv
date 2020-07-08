<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use PHPUnit\Framework\TestCase;

class StvElectionTest extends TestCase
{
    public function testWikipediaExample()
    {
        $candidates = ['Oranges', 'Pears', 'Chocolate', 'Strawberries', 'Hamburgers'];

        $preferenceVotes = [
            new PreferenceVotes('1st preference', $candidates, [
                // 4 ballots with oranges as 1st pref and no 2nd
                new Vote('1', 0),
                new Vote('2', 0),
                new Vote('3', 0),
                new Vote('4', 0),
                // 2 ballots with pears as first and oranges as 2nd
                new Vote('5', 1),
                new Vote('6', 1),
                // 8 ballots with chocolate as 1st and strawberries as 2nd
                new Vote('7', 2),
                new Vote('8', 2),
                new Vote('9', 2),
                new Vote('10', 2),
                new Vote('11', 2),
                new Vote('12', 2),
                new Vote('13', 2),
                new Vote('14', 2),
                // 4 ballots with chocolate as first and hamburgers as 2nd
                new Vote('15', 2),
                new Vote('16', 2),
                new Vote('17', 2),
                new Vote('18', 2),
                // 1 ballot with strawberries as 1st and no 2nd
                new Vote('19', 3),
                // 1 ballot with hamburgers as 1st and no 2nd
                new Vote('20', 4),
            ]),
            new PreferenceVotes('2nd preference', $candidates, [
                new Vote('5', 0),
                new Vote('6', 0),
                new Vote('7', 3),
                new Vote('8', 3),
                new Vote('9', 3),
                new Vote('10', 3),
                new Vote('11', 3),
                new Vote('12', 3),
                new Vote('13', 3),
                new Vote('14', 3),
                new Vote('15', 4),
                new Vote('16', 4),
                new Vote('17', 4),
                new Vote('18', 4),
            ]),
        ];

        $election = new StvElection($preferenceVotes, 3);
        $this->assertSame(6, $election->getQuota());
        $this->assertEmpty($election->invalidBallots);

        // todo: get object with elected candidates, rounds
        // each round has tally for each candidate and any transfers/elections
    }
}
