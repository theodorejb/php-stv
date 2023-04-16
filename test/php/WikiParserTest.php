<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use PHPUnit\Framework\TestCase;

class WikiParserTest extends TestCase
{
    public function testRmElection(): void
    {
        $html = WikiParser::getHtml('test/cases/rm_election.html');
        $election = WikiParser::getStvElection($html, false);

        $this->assertTrue($election->isClosed);
        $this->assertCount(4, $election->candidates);
        $this->assertCount(43, $election->validBallots);
        $this->assertSame(15, $election->quota);

        $rounds = $election->runElection();
        $this->assertCount(4, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('Sara Golemon', $firstElected->name);
        $this->assertEquals(18, $firstElected->surplus);

        $this->assertEquals([
            'Ben Ramsey' => 7,
            'Gabriel Caruso' => 2,
            'Joe Ferguson' => 1,
            'Sara Golemon' => 33,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('Gabriel Caruso', 11, 'floor(18 * (18 / 29))'),
            new CandidateCount('Ben Ramsey', 6, 'floor(11 * (18 / 29))'),
        ], $firstElected->transfers);

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->getTransfers());
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            new CandidateCount('Joe Ferguson', 1),
        ], $secondRound->eliminated);

        $this->assertEquals([
            'Ben Ramsey' => 13,
            'Gabriel Caruso' => 13,
            'Joe Ferguson' => 1,
        ], $secondRound->tally);

        // round 3
        $thirdRound = $rounds[2];
        $this->assertEmpty($thirdRound->elected);

        $this->assertEquals([
            'Gabriel Caruso' => 1,
        ], $thirdRound->getTransfers());

        $this->assertEquals([
            new CandidateCount('Ben Ramsey', 13),
        ], $thirdRound->eliminated);

        $this->assertEquals([
            'Ben Ramsey' => 13,
            'Gabriel Caruso' => 14,
        ], $thirdRound->tally);
    }

    public function testRmElection83WithTie(): void
    {
        $html = WikiParser::getHtml('test/cases/rm_election_83_before_finish.html');
        $election = WikiParser::getStvElection($html, false);

        $this->assertFalse($election->isClosed);
        $this->assertCount(3, $election->candidates);
        $this->assertCount(20, $election->validBallots);
        $this->assertSame(7, $election->quota);

        $rounds = $election->runElection();
        $this->assertCount(2, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('J Zelenka', $firstElected->name);
        $this->assertEquals(10, $firstElected->surplus);

        $this->assertEquals([
            'E Mann' => 3,
            'C Buckley' => 0,
            'J Zelenka' => 17,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('C Buckley', 6, 'floor(11 * (10 / 16))'),
            new CandidateCount('E Mann', 3, 'floor(5 * (10 / 16))'),
        ], $firstElected->transfers);

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->getTransfers());
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            new CandidateCount('E Mann', 6),
            new CandidateCount('C Buckley', 6),
        ], $secondRound->eliminated);

        $this->assertEquals([
            'E Mann' => 6,
            'C Buckley' => 6,
        ], $secondRound->tally);
    }

    public function testShorterAttributeSyntax(): void
    {
        $html = WikiParser::getHtml('test/cases/shorter_attribute_syntax.html');
        $election = WikiParser::getStvElection($html, false);

        $this->assertTrue($election->isClosed);
        $this->assertCount(3, $election->candidates);
        $this->assertCount(61, $election->validBallots);
        $this->assertSame(31, $election->quota);

        $rounds = $election->runElection();
        $this->assertCount(1, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('@@', $firstElected->name);
        $this->assertEquals(3, $firstElected->surplus);
        $this->assertEmpty($firstElected->transfers);

        $this->assertEquals([
            '@@' => 34,
            '#[]' => 21,
            '<<>>' => 6,
        ], $firstRound->tally);
    }
}
