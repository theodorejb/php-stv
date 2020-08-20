<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

use PHPUnit\Framework\TestCase;

class WikiParserTest extends TestCase
{
    public function testRmElection(): void
    {
        $html = WikiParser::getHtml('test/php/rm_election.html');
        $preferenceVotes = WikiParser::getVotesFromHtml($html, 0, 4);
        $election = new StvElection($preferenceVotes, 2, false);

        $this->assertTrue($election->isClosed);
        $this->assertCount(4, $election->candidates);
        $this->assertCount(43, $election->validBallots);
        $this->assertSame(15, $election->quota);

        $rounds = $election->runElection();
        $this->assertCount(4, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->eliminated);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('Sara Golemon', $firstElected->name);
        $this->assertSame(18, $firstElected->surplus);

        $this->assertSame([
            'Ben Ramsey' => 7,
            'Gabriel Caruso' => 2,
            'Joe Ferguson' => 1,
            'Sara Golemon' => 33,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('Gabriel Caruso', 11, 'floor((18 / 29) * 18)'),
            new CandidateCount('Ben Ramsey', 6, 'floor((11 / 29) * 18)'),
        ], $firstElected->transfers);

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            new CandidateCount('Joe Ferguson', 1),
        ], $secondRound->eliminated);

        $this->assertSame([
            'Ben Ramsey' => 13,
            'Gabriel Caruso' => 13,
            'Joe Ferguson' => 1,
        ], $secondRound->tally);

        // round 3
        $thirdRound = $rounds[2];
        $this->assertEmpty($thirdRound->elected);

        $this->assertEquals([
            new CandidateCount('Ben Ramsey', 13),
        ], $thirdRound->eliminated);

        $this->assertSame([
            'Ben Ramsey' => 13,
            'Gabriel Caruso' => 14,
        ], $thirdRound->tally);
    }
}
