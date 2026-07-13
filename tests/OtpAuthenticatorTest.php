<?php

namespace MincDev\OtpAuth\Tests;

use DateTimeImmutable;
use Exception;
use MincDev\OtpAuth\FixedBitNotation;
use MincDev\OtpAuth\OtpAuthenticator;
use MincDev\OtpAuth\OtpAuthException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

class OtpAuthenticatorTest extends TestCase
{
    #[Test]
    #[DataProvider('rfc6238Vectors')]
    public function rfc6238TestVectors(int $timestamp, string $expected): void {
        $totp = new OtpAuthenticator(8);

        $base32 = new FixedBitNotation(
            5,
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
            true,
            true
        );

        $secret = $base32->encode('12345678901234567890');

        $this->assertSame(
            $expected,
            $totp->getCode(
                $secret,
                new DateTimeImmutable("@$timestamp")
            )
        );
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function newSecretReturnsString(): void
    {
        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $isBase32 = function($secret) {
            // RFC 4648 Base32 pattern (A-Z and 2-7) with optional strict padding
            $pattern = '#^(?:[A-Z2-7]{8})*(?:[A-Z2-7]{2}={6}|[A-Z2-7]{4}={4}|[A-Z2-7]{5}={3}|[A-Z2-7]{7}=)?$#i';
            return preg_match($pattern, $secret) === 1;
        };

        $this->assertNotEmpty($secret);
        $this->assertIsString($secret);
        $this->assertTrue($isBase32($secret));
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function generatedSecretsAreUnique(): void
    {
        $totp = new OtpAuthenticator();

        $secrets = [];

        for ($i = 0; $i < 100; $i++) {
            $secrets[] = $totp->newSecret();
        }

        $this->assertCount(100, $secrets);
        $this->assertCount(100, array_unique($secrets));
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function getCodeReturnsSixDigits(): void
    {
        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();
        $code = $totp->getCode($secret);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function sameSecretAndTimeGenerateSameCode(): void
    {
        $totp = new OtpAuthenticator();

        $secret = $totp->newSecret();
        $time = new DateTimeImmutable('@1234');

        $firstCode = $totp->getCode($secret, $time);
        $secondCode = $totp->getCode($secret, $time);

        $this->assertSame($firstCode, $secondCode);
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function differentTimeGeneratesDifferentCode(): void
    {
        $totp = new OtpAuthenticator();

        $secret = $totp->newSecret();
        $firstTime = new DateTimeImmutable('@1234');
        $secondTime = new DateTimeImmutable('@4321');

        $firstCode = $totp->getCode($secret, $firstTime);
        $secondCode = $totp->getCode($secret, $secondTime);

        $this->assertNotSame($firstCode, $secondCode);
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function getCodeReturnsEightDigitsWhenConfigured(): void
    {
        $totp = new OtpAuthenticator(8);

        $secret = $totp->newSecret();
        $code = $totp->getCode($secret);

        $this->assertMatchesRegularExpression('/^\d{8}$/', $code);
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    #[Test]
    public function validateReturnsTrueForCorrectCode(): void
    {
        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();
        $code = $totp->getCode($secret);

        $validationResult = $totp->validate($secret, $code);

        $this->assertTrue($validationResult);
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    #[Test]
    public function validateReturnsTrueForCorrectCodeWithDeterministicTimestamp(): void
    {
        $time = new DateTimeImmutable('@1234');

        $totp = new OtpAuthenticator(instanceTime: $time);
        $secret = $totp->newSecret();

        $code = $totp->getCode($secret, $time);
        $validationResult = $totp->validate($secret, $code);

        $this->assertTrue($validationResult);
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    #[Test]
    public function validateReturnsFalseForIncorrectCode(): void
    {
        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();
        $randomCode = '123456';

        $validationResult = $totp->validate($secret, $randomCode);

        $this->assertFalse($validationResult);
    }

    /**
     * @throws RandomException
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRWithEmptyAccountNameThrowsException(): void
    {
        $this->expectException(OtpAuthException::class);
        $this->expectExceptionMessage("The account name must not be empty or contain a colon (:). Given: \"\".");
        $this->expectExceptionCode(1001);

        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $totp->getQR("", null, $secret);
    }

    /**
     * @throws RandomException
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRWithInvalidAccountNameThrowsException(): void
    {
        $invalidAccountName = "invalid:accountName";

        $this->expectException(OtpAuthException::class);
        $this->expectExceptionMessage("The account name must not be empty or contain a colon (:). Given: \"$invalidAccountName\".");
        $this->expectExceptionCode(1001);

        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $totp->getQR($invalidAccountName, null, $secret);
    }

    /**
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws RandomException
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRWithEmptyIssuerThrowsException(): void
    {
        $this->expectException(OtpAuthException::class);
        $this->expectExceptionMessage("The issuer must not be empty or contain a colon (:). Given: \"\".");
        $this->expectExceptionCode(1002);

        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $totp->getQR("john.doe@example.com", "", $secret);
    }

    /**
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws RandomException
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRWithInvalidIssuerThrowsException(): void
    {
        $invalidIssuer = "invalid:issuer";

        $this->expectException(OtpAuthException::class);
        $this->expectExceptionMessage("The issuer must not be empty or contain a colon (:). Given: \"$invalidIssuer\".");
        $this->expectExceptionCode(1002);

        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $totp->getQR("john.doe@example.com", $invalidIssuer, $secret);
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function getUriReturnsTheFullValidOtpAuthUri(): void
    {
        $accountName = "john.doe@example.com";
        $issuer = "MyAwesomeApp";

        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $uri = $totp->getUri($accountName, $issuer, $secret);

        $this->assertSame("otpauth://totp/$issuer:$accountName?secret=$secret&issuer=$issuer", $uri);
    }

    /**
     * @throws RandomException
     */
    #[Test]
    public function getUriWithoutIssuerReturnsTheFullValidOtpAuthUri(): void
    {
        $accountName = "john.doe@example.com";

        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $uri = $totp->getUri($accountName, null, $secret);

        $this->assertSame("otpauth://totp/$accountName?secret=$secret", $uri);
    }

    /**
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRWithEmptySecretThrowsException(): void
    {
        $this->expectException(OtpAuthException::class);
        $this->expectExceptionMessage("The secret must not be empty.");
        $this->expectExceptionCode(1003);

        $totp = new OtpAuthenticator();

        $totp->getQR("john.doe@example.com", null, "");
    }

    /**
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws RandomException
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRReturnsDataUri(): void
    {
        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $qr = $totp->getQR("john.doe@example.com", null, $secret);

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $qr
        );
    }

    /**
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws RandomException
     * @throws \Com\Tecnick\Color\Exception
     */
    #[Test]
    public function getQRContainsPNG(): void
    {
        $totp = new OtpAuthenticator();
        $secret = $totp->newSecret();

        $qr = $totp->getQR("john.doe@example.com", null, $secret);
        $png = base64_decode(str_replace('data:image/png;base64,', '', $qr));

        $this->assertStringStartsWith(
            "\x89PNG",
            $png
        );
    }

    public static function rfc6238Vectors(): iterable
    {
        yield '59 seconds' => [59, '94287082'];
        yield '1111111109' => [1111111109, '07081804'];
        yield '1111111111' => [1111111111, '14050471'];
        yield '1234567890' => [1234567890, '89005924'];
        yield '2000000000' => [2000000000, '69279037'];
        yield '20000000000' => [20000000000, '65353130'];
    }
}