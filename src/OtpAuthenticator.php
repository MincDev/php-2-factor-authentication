<?php

declare(strict_types=1);

namespace MincDev\OtpAuth;

use DateTimeImmutable;
use DateTimeInterface;
use Com\Tecnick\Barcode\Barcode;

/**
 * Generates and validates Time-based One-Time Passwords (TOTP)
 * and creates QR codes for provisioning authenticator applications.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6238 TOTP: Time-Based One-Time Password Algorithm
 */
class OtpAuthenticator implements OtpAuthInterface 
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const QR_SIZE = 200;

    /**
     * The length of the OTP code
     * @var int
     */
    private readonly int $codeLength;

    /**
     * The length of the secret
     * @var int
     */
    private readonly int $secretLength;

    /**
     * Mod for the pin code
     * @var int
     */
    private readonly int $pinModulo;

    /**
     * @var DateTimeInterface
     */
    private readonly DateTimeInterface $instanceTime;

    /**
     * Validity of the code
     * @var int
     */
    private readonly int $validFor;

    /**
     * Length of a period to calculate periods since Unix epoch.
     * @var int
     */
    private int $periodSize = 30;

    private readonly FixedBitNotation $base32;

    public function __construct(
        int $codeLength = 6,
        int $secretLength = 10,
        ?DateTimeInterface $instanceTime = null,
        int $validFor = 30
    ) {
        $this->codeLength = $codeLength;
        $this->secretLength = $secretLength;
        $this->validFor = $validFor;
        $this->periodSize = min($validFor, $this->periodSize);
        $this->pinModulo = 10 ** $codeLength;
        $this->instanceTime = $instanceTime ?? new DateTimeImmutable();
        $this->base32 = new FixedBitNotation(5, self::BASE32_ALPHABET, true, true);
    }

    public function validate(string $secret, string $code): bool
    {
        $periods = (int) floor($this->validFor / $this->periodSize);

        $result = 0;
        for ($i = 0; $i < $periods; ++$i) {
            $dateTime = new DateTimeImmutable('@'.($this->instanceTime->getTimestamp() - ($i * $this->periodSize)));
            $result = hash_equals($this->getCode($secret, $dateTime), $code) ? $dateTime->getTimestamp() : $result;
        }

        return $result > 0;
    }

    public function getCode(string $secret, ?DateTimeInterface $time = null): string
    {
        if (null === $time) {
            $time = $this->instanceTime;
        }
    
        $timeForCode = (int) floor($time->getTimestamp() / $this->periodSize);

        $secret = $this->base32->decode($secret);
    
        $timeForCode = str_pad(pack('N', $timeForCode), 8, chr(0), STR_PAD_LEFT);
    
        $hash = hash_hmac('sha1', $timeForCode, $secret, true);
        $offset = ord(substr($hash, -1));
        $offset &= 0xF;
    
        $truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
    
        return str_pad((string) ($truncatedHash % $this->pinModulo), $this->codeLength, '0', STR_PAD_LEFT);
    }

    public function newSecret(): string
    {
        return $this->base32->encode(random_bytes($this->secretLength));
    }

    private function hashToInt(string $bytes, int $start): int
    {
        return unpack('N', substr($bytes, $start, 4))[1];
    }

    public function getQR(string $accountName, ?string $issuer, string $secret): string
    {
        $otpauthString = $this->getUri($accountName, $issuer, $secret);
        $barcode = new Barcode();

        $qrCode = $barcode->getBarcodeObj(
            'QRCODE,H',
            $otpauthString,
            self::QR_SIZE,
            self::QR_SIZE
        )->setBackgroundColor('white');

        return 'data:image/png;base64,' . base64_encode($qrCode->getPngData());
    }

    public function getUri(string $accountName, ?string $issuer, string $secret): string
    {
        if ('' === $accountName || str_contains($accountName, ':')) {
            throw OtpAuthException::invalidAccountName($accountName);
        }

        if ('' === $secret) {
            throw OtpAuthException::invalidSecret();
        }

        $label = $accountName;
        $otpauthString = 'otpauth://totp/%s?secret=%s';

        if (null !== $issuer) {
            if ('' === $issuer || str_contains($issuer, ':')) {
                throw OtpAuthException::invalidIssuer($issuer);
            }

            $label = $issuer.':'.$label;
            $otpauthString .= '&issuer=%s';
        }

        return sprintf($otpauthString, $label, $secret, $issuer);
    }
}