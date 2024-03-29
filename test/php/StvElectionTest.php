<?php

declare(strict_types=1);

namespace theodorejb\PhpStv\Tests;

use PHPUnit\Framework\TestCase;
use theodorejb\PhpStv\{Ballot, CandidateCount, CandidateTransfers, StvElection};

class StvElectionTest extends TestCase
{
    public function testWikipediaExample(): void
    {
        $candidates = ['Oranges', 'Pears', 'Chocolate', 'Strawberries', 'Hamburgers'];

        $ballots = self::getBallots(5, ['Oranges', 'Pears']);
        array_push($ballots, ...self::getBallots(3, ['Pears', 'Oranges']));
        array_push($ballots, ...self::getBallots(8, ['Chocolate', 'Strawberries']));
        array_push($ballots, ...self::getBallots(4, ['Chocolate', 'Hamburgers']));
        array_push($ballots, ...self::getBallots(1, ['Strawberries', 'Pears']));
        array_push($ballots, ...self::getBallots(2, ['Hamburgers', 'Pears']));

        $election = new StvElection($ballots, $candidates, 3, true, false);
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
        $this->assertEquals(6, $firstElected->surplus);

        $this->assertEquals([
            'Oranges' => 5,
            'Pears' => 3,
            'Chocolate' => 12,
            'Strawberries' => 1,
            'Hamburgers' => 2,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('Strawberries', 4, 'floor(8 * (6 / 12))'),
            new CandidateCount('Hamburgers', 2, 'floor(4 * (6 / 12))'),
        ], self::getTransferCandidateCounts($firstElected->transfers));

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->getTransfers());
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            new CandidateCount('Pears', 3),
        ], $secondRound->eliminated);

        $this->assertEquals([
            'Oranges' => 5,
            'Pears' => 3,
            'Strawberries' => 5,
            'Hamburgers' => 4,
        ], $secondRound->tally);

        // round 3
        $thirdRound = $rounds[2];
        $this->assertEmpty($thirdRound->eliminated);

        $this->assertEquals([
            'Oranges' => 3,
        ], $thirdRound->getTransfers());

        $secondElected = $thirdRound->elected[0];
        $this->assertSame('Oranges', $secondElected->name);
        $this->assertEquals(2, $secondElected->surplus);
        $this->assertEmpty($secondElected->transfers);

        $this->assertEquals([
            'Oranges' => 8,
            'Strawberries' => 5,
            'Hamburgers' => 4,
        ], $thirdRound->tally);

        // round 4
        $fourthRound = $rounds[3];
        $this->assertEmpty($fourthRound->getTransfers());
        $this->assertEmpty($fourthRound->elected);

        $this->assertEquals([
            new CandidateCount('Hamburgers', 4),
        ], $fourthRound->eliminated);

        $this->assertEquals([
            'Strawberries' => 5,
            'Hamburgers' => 4,
        ], $fourthRound->tally);

        // round 5
        $fifthRound = $rounds[4];
        $this->assertEmpty($fifthRound->getTransfers());
        $this->assertEmpty($fifthRound->eliminated);

        $thirdElected = $fifthRound->elected[0];
        $this->assertSame('Strawberries', $thirdElected->name);
        $this->assertEquals(-1, $thirdElected->surplus);
        $this->assertEmpty($thirdElected->transfers);

        $this->assertEquals([
            'Strawberries' => 5,
        ], $fifthRound->tally);
    }

    public function testTidemanExample(): void
    {
        // from https://pubs.aeaweb.org/doi/pdfplus/10.1257/jep.9.1.27
        $candidates = ['R', 'S', 'T', 'U', 'V'];
        $ballots = self::getBallots(25, ['R', 'S', 'V', 'U', 'T']);
        array_push($ballots, ...self::getBallots(15, $candidates));
        array_push($ballots, ...self::getBallots(9, ['S', 'R', 'T', 'U', 'V']));
        array_push($ballots, ...self::getBallots(9, ['S', 'U', 'T', 'R', 'V']));
        array_push($ballots, ...self::getBallots(6, ['T', 'U', 'R', 'S', 'V']));
        array_push($ballots, ...self::getBallots(7, ['U', 'T', 'S', 'R', 'V']));
        array_push($ballots, ...self::getBallots(9, ['V', 'T', 'U', 'R', 'S']));

        $election = new StvElection($ballots, $candidates, 3, true, false);
        $this->assertSame(21, $election->quota);
        $this->assertEmpty($election->invalidBallots);
        $rounds = $election->runElection();
        $this->assertCount(5, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());

        $firstElected = $firstRound->elected[0];
        $this->assertSame('R', $firstElected->name);
        $this->assertEquals(19, $firstElected->surplus);

        $this->assertEquals([
            new CandidateCount('S', 19, 'floor(40 * (19 / 40))'),
        ], self::getTransferCandidateCounts($firstElected->transfers));

        $this->assertEmpty($firstRound->eliminated);

        $this->assertEquals([
            'R' => 40,
            'S' => 18,
            'T' => 6,
            'U' => 7,
            'V' => 9,
        ], $firstRound->tally);

        // round 2
        $secondRound = $rounds[1];

        $this->assertEmpty($secondRound->getTransfers());
        $this->assertCount(1, $secondRound->elected);

        $secondElected = $secondRound->elected[0];
        $this->assertSame('S', $secondElected->name);
        $this->assertEquals(16, $secondElected->surplus);

        $this->assertEquals([
            new CandidateCount('T', 3, 'floor(9 * (16 / 37))'),
            new CandidateCount('U', 3, 'floor(9 * (16 / 37))'),
            new CandidateCount('V', 8, 'floor(19 * (16 / 37))'),
        ], self::getTransferCandidateCounts($secondElected->transfers));

        $this->assertEmpty($secondRound->eliminated);

        $this->assertEquals([
            'S' => 37,
            'T' => 6,
            'U' => 7,
            'V' => 9,
        ], $secondRound->tally);

        // round 3
        $thirdRound = $rounds[2];

        $this->assertEquals([
            new CandidateCount('T', 9),
        ], $thirdRound->eliminated);

        $this->assertEmpty($thirdRound->getTransfers());
        $this->assertEmpty($thirdRound->elected);

        $this->assertEquals([
            'T' => 9,
            'U' => 10,
            'V' => 17,
        ], $thirdRound->tally);

        // round 4
        $fourthRound = $rounds[3];

        $this->assertEquals([
            'U' => 9,
        ], $fourthRound->getTransfers());

        $this->assertCount(0, $fourthRound->elected);

        $this->assertEquals([
            new CandidateCount('V', 17),
        ], $fourthRound->eliminated);

        $this->assertEquals([
            'U' => 19,
            'V' => 17,
        ], $fourthRound->tally);

        // round 5
        $fifthRound = $rounds[4];

        $this->assertEquals([
            'U' => 17,
        ], $fifthRound->getTransfers());

        $this->assertCount(1, $fifthRound->elected);
        $thirdElected = $fifthRound->elected[0];
        $this->assertSame('U', $thirdElected->name);
        $this->assertEquals(15, $thirdElected->surplus);
        $this->assertEmpty($thirdElected->transfers);

        $this->assertEmpty($fifthRound->eliminated);

        $this->assertEquals([
            'U' => 36,
        ], $fifthRound->tally);
    }

    public function testMultipleElectedInRound(): void
    {
        $candidates = ['Amy', 'Bob', 'Chad', 'Deb', 'Eva'];
        $ballots = self::getBallots(10, ['Amy', 'Bob', 'Eva', 'Deb', 'Chad']);
        array_push($ballots, ...self::getBallots(9, $candidates));
        array_push($ballots, ...self::getBallots(8, ['Bob', 'Amy', 'Deb', 'Chad', 'Eva']));
        array_push($ballots, ...self::getBallots(11, ['Bob', 'Chad', 'Eva', 'Deb', 'Amy']));
        array_push($ballots, ...self::getBallots(5, ['Chad', 'Deb', 'Amy', 'Bob', 'Eva']));
        array_push($ballots, ...self::getBallots(3, ['Chad', 'Bob', 'Deb', 'Eva', 'Amy']));
        array_push($ballots, ...self::getBallots(2, ['Deb', 'Eva', 'Chad', 'Amy', 'Bob']));

        $election = new StvElection($ballots, $candidates, 3, true, false);
        $this->assertSame(13, $election->quota);
        $this->assertEmpty($election->invalidBallots);
        $rounds = $election->runElection();
        $this->assertCount(2, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertCount(2, $firstRound->elected);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('Amy', $firstElected->name);
        $this->assertEquals(6, $firstElected->surplus);

        $this->assertEquals([
            new CandidateCount('Eva', 3, 'floor(10 * (6 / 19))'),
            new CandidateCount('Chad', 2, 'floor(9 * (6 / 19))'),
        ], self::getTransferCandidateCounts($firstElected->transfers));

        $secondElected = $firstRound->elected[1];
        $this->assertSame('Bob', $secondElected->name);
        $this->assertEquals(6, $secondElected->surplus);

        $this->assertEquals([
            new CandidateCount('Deb', 2, 'floor(8 * (6 / 19))'),
            new CandidateCount('Chad', 3, 'floor(11 * (6 / 19))'),
        ], self::getTransferCandidateCounts($secondElected->transfers));

        $this->assertEmpty($firstRound->eliminated);

        $this->assertEquals([
            'Amy' => 19,
            'Bob' => 19,
            'Chad' => 8,
            'Deb' => 2,
            'Eva' => 0,
        ], $firstRound->tally);

        // round 2
        $secondRound = $rounds[1];

        $this->assertEmpty($secondRound->getTransfers());
        $this->assertCount(1, $secondRound->elected);

        $thirdElected = $secondRound->elected[0];
        $this->assertSame('Chad', $thirdElected->name);
        $this->assertEquals(0, $thirdElected->surplus);

        $this->assertEmpty($secondRound->eliminated);

        $this->assertEquals([
            'Chad' => 13,
            'Deb' => 4,
            'Eva' => 3,
        ], $secondRound->tally);
    }

    /**
     * @param CandidateTransfers[] $transfers
     * @return CandidateCount[]
     */
    public static function getTransferCandidateCounts(array $transfers): array
    {
        $counts = [];

        foreach ($transfers as $transfer) {
            $counts[] = new CandidateCount($transfer->candidate, $transfer->getValue(), $transfer->details);
        }

        return $counts;
    }

    /**
     * @param string[] $candidates
     * @return Ballot[]
     */
    private static function getBallots(int $num, array $candidates): array
    {
        $ballots = [];

        for ($i = 0; $i < $num; $i++) {
            $ballots[] = new Ballot('', $candidates);
        }

        return $ballots;
    }
}
