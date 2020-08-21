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
                // 2 second preferences for oranges
                new Vote('5', 0),
                new Vote('6', 0),
                // 8 second preferences for strawberries
                new Vote('7', 3),
                new Vote('8', 3),
                new Vote('9', 3),
                new Vote('10', 3),
                new Vote('11', 3),
                new Vote('12', 3),
                new Vote('13', 3),
                new Vote('14', 3),
                // 4 second preferences for hamburgers
                new Vote('15', 4),
                new Vote('16', 4),
                new Vote('17', 4),
                new Vote('18', 4),
            ]),
        ];

        $election = new StvElection($preferenceVotes, 3);
        $this->assertSame(6, $election->quota);
        $this->assertEmpty($election->invalidBallots);
        $rounds = $election->runElection();
        $this->assertCount(5, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('Chocolate', $firstElected->name);
        $this->assertSame(6, $firstElected->surplus);

        $this->assertSame([
            'Oranges' => 4,
            'Pears' => 2,
            'Chocolate' => 12,
            'Strawberries' => 1,
            'Hamburgers' => 1,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('Strawberries', 4, 'floor((8 / 12) * 6)'),
            new CandidateCount('Hamburgers', 2, 'floor((4 / 12) * 6)'),
        ], $firstElected->transfers);

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->getTransfers());
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            new CandidateCount('Pears', 2),
        ], $secondRound->eliminated);

        $this->assertSame([
            'Oranges' => 4,
            'Pears' => 2,
            'Strawberries' => 5,
            'Hamburgers' => 3,
        ], $secondRound->tally);

        // round 3
        $thirdRound = $rounds[2];
        $this->assertEmpty($thirdRound->eliminated);

        $this->assertSame([
            'Oranges' => 2,
        ], $thirdRound->getTransfers());

        $secondElected = $thirdRound->elected[0];
        $this->assertSame('Oranges', $secondElected->name);
        $this->assertSame(0, $secondElected->surplus);

        $this->assertSame([
            'Oranges' => 6,
            'Strawberries' => 5,
            'Hamburgers' => 3,
        ], $thirdRound->tally);

        // round 4
        $fourthRound = $rounds[3];
        $this->assertEmpty($fourthRound->getTransfers());
        $this->assertEmpty($fourthRound->elected);

        $this->assertEquals([
            new CandidateCount('Hamburgers', 3),
        ], $fourthRound->eliminated);

        $this->assertSame([
            'Strawberries' => 5,
            'Hamburgers' => 3,
        ], $fourthRound->tally);

        // round 5
        $fifthRound = $rounds[4];
        $this->assertEmpty($fifthRound->getTransfers());
        $this->assertEmpty($fifthRound->eliminated);

        $thirdElected = $fifthRound->elected[0];
        $this->assertSame('Strawberries', $thirdElected->name);
        $this->assertSame(-1, $thirdElected->surplus);

        $this->assertSame([
            'Strawberries' => 5,
        ], $fifthRound->tally);
    }
}
