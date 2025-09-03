<?php

declare(strict_types=1);

namespace theodorejb\PhpStv\Tests;

use PHPUnit\Framework\TestCase;
use theodorejb\PhpStv\{Ballot, CandidateCount, WikiParser};

class WikiParserTest extends TestCase
{
    public function testRmElection80(): void
    {
        $html = WikiParser::getHtml('test/cases/rm_election_80.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(4, $election->candidates);
        $this->assertCount(43, $election->validBallots);
        $this->assertSame(15, $election->quota);
        $this->assertEmpty($election->invalidBallots);

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
        ], StvElectionTest::getTransferCandidateCounts($firstElected->transfers));

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

        // round 4
        $fourthRound = $rounds[3];

        $this->assertCount(1, $fourthRound->elected);
        $elected = $fourthRound->elected[0];
        $this->assertSame('Gabriel Caruso', $elected->name);
        $this->assertEquals(8, $elected->surplus);
        $this->assertEmpty($elected->transfers);

        $this->assertEmpty($fourthRound->eliminated);

        $this->assertEquals([
            'Gabriel Caruso' => 23,
        ], $fourthRound->tally);
    }

    public function testRmElection81(): void
    {
        $html = WikiParser::getHtml('test/cases/rm_election_81.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(8, $election->candidates);
        $this->assertCount(35, $election->validBallots);
        $this->assertSame(12, $election->quota);
        $this->assertEmpty($election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(1, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);
        $this->assertCount(2, $firstRound->elected);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('P Allaert', $firstElected->name);
        $this->assertEquals(1, $firstElected->surplus);
        $this->assertEmpty($firstElected->transfers);

        $secondElected = $firstRound->elected[1];
        $this->assertSame('B Ramsey', $secondElected->name);
        $this->assertEquals(3, $secondElected->surplus);
        $this->assertEmpty($secondElected->transfers);

        $this->assertEquals([
            'A Cristo' => 1,
            'G Engebreth' => 0,
            'H Smits' => 0,
            'E vJohnson' => 2,
            'P Allaert' => 13,
            'S Panteleev' => 2,
            'B Ramsey' => 15,
            'S E Gmati' => 2,
        ], $firstRound->tally);
    }

    public function testRmElection82(): void
    {
        $html = WikiParser::getHtml('test/cases/rm_election_82.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(7, $election->candidates);
        $this->assertCount(28, $election->validBallots);
        $this->assertSame(10, $election->quota);
        $this->assertEquals([
            new Ballot('felipe', array_fill(0, 7, 'P Charron')),
        ], $election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(1, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);
        $this->assertCount(2, $firstRound->elected);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('S Panteleev', $firstElected->name);
        $this->assertEquals(0, $firstElected->surplus);
        $this->assertEmpty($firstElected->transfers);

        $secondElected = $firstRound->elected[1];
        $this->assertSame('P Charron', $secondElected->name);
        $this->assertEquals(0, $secondElected->surplus);
        $this->assertEmpty($secondElected->transfers);

        $this->assertEquals([
            'S Panteleev' => 10,
            'E Sims' => 0,
            'A Junker' => 0,
            'C Buckley' => 2,
            'E Mann' => 4,
            'P Charron' => 10,
            'S E Gmati' => 2,
        ], $firstRound->tally);
    }

    public function testRmElection83WithTie(): void
    {
        $html = WikiParser::getHtml('test/cases/rm_election_83.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(3, $election->candidates);
        $this->assertCount(24, $election->validBallots);
        $this->assertSame(9, $election->quota);
        $this->assertEmpty($election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(2, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);

        $firstElected = $firstRound->elected[0];
        $this->assertSame('J Zelenka', $firstElected->name);
        $this->assertEquals(11, $firstElected->surplus);

        $this->assertEquals([
            'E Mann' => 4,
            'C Buckley' => 0,
            'J Zelenka' => 20,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('C Buckley', 7, 'floor(13 * (11 / 19))'),
            new CandidateCount('E Mann', 3, 'floor(6 * (11 / 19))'),
        ], StvElectionTest::getTransferCandidateCounts($firstElected->transfers));

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->getTransfers());
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            new CandidateCount('E Mann', 7),
            new CandidateCount('C Buckley', 7),
        ], $secondRound->eliminated);

        $this->assertEquals([
            'E Mann' => 7,
            'C Buckley' => 7,
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
        $this->assertEquals([
            new Ballot('mgocobachi', array_fill(0, 3, '#[]')),
        ], $election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(1, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);
        $this->assertCount(1, $firstRound->elected);

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

    public function testShorterAttributeSyntaxChange(): void
    {
        $html = WikiParser::getHtml('test/cases/shorter_attribute_syntax_change.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(6, $election->candidates);
        $this->assertCount(65, $election->validBallots);
        $this->assertSame(33, $election->quota);
        $this->assertEquals([
            new Ballot('duodraco', ['#[Attr]', '<<Attr>>', '@[Attr]', '@{Attr}', '@:Attr', '@[Attr]']),
        ], $election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(3, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->elected);

        $this->assertEquals([
            '@@Attr' => 9,
            '#[Attr]' => 32,
            '@[Attr]' => 13,
            '<<Attr>>' => 6,
            '@:Attr' => 1,
            '@{Attr}' => 4,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('@:Attr', 1),
        ], $firstRound->eliminated);

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->elected);

        $this->assertEquals([
            '@@Attr' => 1,
        ], $secondRound->getTransfers());

        $this->assertEquals([
            '@@Attr' => 10,
            '#[Attr]' => 32,
            '@[Attr]' => 13,
            '<<Attr>>' => 6,
            '@{Attr}' => 4,
        ], $secondRound->tally);

        $this->assertEquals([
            new CandidateCount('@{Attr}', 4),
        ], $secondRound->eliminated);

        // round 3
        $thirdRound = $rounds[2];
        $this->assertEmpty($thirdRound->eliminated);
        $this->assertCount(1, $thirdRound->elected);

        $elected = $thirdRound->elected[0];
        $this->assertSame('#[Attr]', $elected->name);
        $this->assertEquals(1, $elected->surplus);
        $this->assertEmpty($elected->transfers);

        $this->assertEquals([
            '@[Attr]' => 1,
            '#[Attr]' => 2,
            '<<Attr>>' => 1,
        ], $thirdRound->getTransfers());

        $this->assertEquals([
            '@@Attr' => 10,
            '#[Attr]' => 34,
            '@[Attr]' => 14,
            '<<Attr>>' => 7,
        ], $thirdRound->tally);
    }

    public function testDequeNamingPatternStrawPoll(): void
    {
        $html = WikiParser::getHtml('test/cases/deque_straw_poll.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(3, $election->candidates);
        $this->assertCount(17, $election->validBallots);
        $this->assertSame(9, $election->quota);
        $this->assertEmpty($election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(1, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->eliminated);
        $this->assertCount(1, $firstRound->elected);

        $firstElected = $firstRound->elected[0];
        $this->assertSame("''Collections\Deque''", $firstElected->name);
        $this->assertEquals(6, $firstElected->surplus);
        $this->assertEmpty($firstElected->transfers);

        $this->assertEquals([
            "''Deque''" => 2,
            "''Collections\Deque''" => 15,
            "''SplDeque''" => 0,
        ], $firstRound->tally);
    }

    public function testIterableNamespacingStrawPoll(): void
    {
        $html = WikiParser::getHtml('test/cases/iterable_namespace_straw_poll.html');
        $election = WikiParser::getStvElection($html);

        $this->assertTrue($election->isClosed);
        $this->assertCount(11, $election->candidates);
        $this->assertCount(24, $election->validBallots);
        $this->assertSame(13, $election->quota);
        $this->assertEmpty($election->invalidBallots);

        $rounds = $election->runElection();
        $this->assertCount(5, $rounds);

        // round 1
        $firstRound = $rounds[0];
        $this->assertEmpty($firstRound->getTransfers());
        $this->assertEmpty($firstRound->elected);

        $this->assertEquals([
            'iterable_any() and iterable_all()' => 9,
            'iter\\' => 0,
            'iterable\\' => 1,
            'PHP\\' => 0,
            'PHP\Spl\\' => 0,
            'PHP\iter\\' => 1,
            'PHP\iterable\\' => 9,
            'Ext\Spl\\' => 0,
            'Spl\\' => 3,
            'Spl\iter\\' => 0,
            'Spl\iterable\\' => 1,
        ], $firstRound->tally);

        $this->assertEquals([
            new CandidateCount('iter\\', 0),
            new CandidateCount('PHP\\', 0),
            new CandidateCount('PHP\Spl\\', 0),
            new CandidateCount('Ext\Spl\\', 0),
            new CandidateCount('Spl\iter\\', 0),
        ], $firstRound->eliminated);

        // round 2
        $secondRound = $rounds[1];
        $this->assertEmpty($secondRound->elected);
        $this->assertEmpty($secondRound->getTransfers());

        $this->assertEquals([
            'iterable_any() and iterable_all()' => 9,
            'iterable\\' => 1,
            'PHP\iter\\' => 1,
            'PHP\iterable\\' => 9,
            'Spl\\' => 3,
            'Spl\iterable\\' => 1,
        ], $secondRound->tally);

        $this->assertEquals([
            new CandidateCount('iterable\\', 1),
            new CandidateCount('PHP\iter\\', 1),
            new CandidateCount('Spl\iterable\\', 1),
        ], $secondRound->eliminated);

        // round 3
        $thirdRound = $rounds[2];
        $this->assertEmpty($thirdRound->elected);

        $this->assertEquals([
            'PHP\iterable\\' => 2,
            'iterable_any() and iterable_all()' => 1,
        ], $thirdRound->getTransfers());

        $this->assertEquals([
            'iterable_any() and iterable_all()' => 10,
            'PHP\iterable\\' => 11,
            'Spl\\' => 3,
        ], $thirdRound->tally);

        $this->assertEquals([
            new CandidateCount('Spl\\', 3),
        ], $thirdRound->eliminated);

        // round 4
        $fourthRound = $rounds[3];
        $this->assertEmpty($fourthRound->elected);

        $this->assertEquals([
            'PHP\iterable\\' => 1,
            'iterable_any() and iterable_all()' => 1,
        ], $fourthRound->getTransfers());

        $this->assertEquals([
            'iterable_any() and iterable_all()' => 11,
            'PHP\iterable\\' => 12,
        ], $fourthRound->tally);

        $this->assertEquals([
            new CandidateCount('iterable_any() and iterable_all()', 11),
        ], $fourthRound->eliminated);

        // round 5
        $fifthRound = $rounds[4];

        $this->assertEquals([
            'PHP\iterable\\' => 3,
        ], $fifthRound->getTransfers());

        $this->assertEquals([
            'PHP\iterable\\' => 15,
        ], $fifthRound->tally);

        $this->assertEmpty($fifthRound->eliminated);
        $this->assertCount(1, $fifthRound->elected);
        $elected = $fifthRound->elected[0];

        $this->assertSame('PHP\iterable\\', $elected->name);
        $this->assertEquals(2, $elected->surplus);
        $this->assertEmpty($elected->transfers);
    }
}
