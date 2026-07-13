<?php

namespace MincDev\OtpAuth\Tests;

use MincDev\OtpAuth\FixedBitNotation;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

class FixedBitNotationTest extends TestCase
{
    /**
     * @throws RandomException
     */
    public function testFixedBitNotationWorks(): void
    {
        $base32 = new FixedBitNotation(5, "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567", true, true);
        $raw = random_bytes(128);

        $encoded = $base32->encode($raw);

        $decoded = $base32->decode($encoded);

        $this->assertSame($raw, $decoded);
    }
}