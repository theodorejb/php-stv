<?php

declare(strict_types=1);

namespace theodorejb\PhpStv;

/**
 * @api
 */
class CandidateCount
{
    public function __construct(
        public string $candidate,
        public float $count,
        public string $details = '',
    ) {
    }
}
