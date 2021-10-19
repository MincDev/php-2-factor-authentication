<?php

namespace MincDev\OtpAuth;

use DateTimeInterface;
use Com\Tecnick\Barcode\Barcode;
use MincDev\OtpAuth\OtpAuthInterface;

/**
 * This is the primary class for the OtpAuthenticator
 * 
 * @author Christopher Smit <christopher@mincdevelopment.co.za>
 * @package \MincDev\OtpAuth
 * @license MIT
 * @see https://github.com/google/google-authenticator/wiki/Key-Uri-Format
 */
class OtpAuthenticator implements OtpAuthInterface 
{
    /**
     * The lenght of the OTP code
     * @var int
     */
    private $codeLength;

    /**
     * The length of the secret
     * @var int
     */
    private $secretLength;

    /**
     * Mod for the pin code
     * @var int
     */
    private $pinModulo;

    /**
     * @var \DateTimeInterface
     */
    private $instanceTime;

    /**
     * Validity of the code
     * @var int
     */
    private $validFor;

    /**
     * Length of a period to calculate periods since Unix epoch.
     * @var int
     */
    private $periodSize = 30;

    public function __construct(int $codeLength = 6, int $secretLength = 10, ?\DateTimeInterface $instanceTime = null, int $validFor = 30)
    {
        $this->codeLength = $codeLength;
        $this->secretLength = $secretLength;
        $this->validFor = $validFor;
        $this->periodSize = $validFor < $this->periodSize ? $validFor : $this->periodSize;
        $this->pinModulo = 10 ** $codeLength;
        $this->instanceTime = $instanceTime ?? new \DateTimeImmutable();
    }

    public function validate(string $secret, string $code): bool
    {
        $periods = floor($this->validFor / $this->periodSize);

        $result = 0;
        for ($i = 0; $i < $periods; ++$i) {
            $dateTime = new \DateTimeImmutable('@'.($this->instanceTime->getTimestamp() - ($i * $this->periodSize)));
            $result = hash_equals($this->getCode($secret, $dateTime), $code) ? $dateTime->getTimestamp() : $result;
        }

        return $result > 0;
    }

    public function getCode(string $secret, ?DateTimeInterface $time = null): string
    {
        if (null === $time) {
            $time = $this->instanceTime;
        }
    
        $timeForCode = floor($time->getTimestamp() / $this->periodSize);
       
    
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);
        $secret = $base32->decode($secret);
    
        $timeForCode = str_pad(pack('N', $timeForCode), 8, \chr(0), \STR_PAD_LEFT);
    
        $hash = hash_hmac('sha1', $timeForCode, $secret, true);
        $offset = \ord(substr($hash, -1));
        $offset &= 0xF;
    
        $truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
    
        return str_pad((string) ($truncatedHash % $this->pinModulo), $this->codeLength, '0', \STR_PAD_LEFT);
    }

    public function newSecret(): string
    {
        return (new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true))
            ->encode(random_bytes($this->secretLength));
    }

    private function hashToInt(string $bytes, int $start): int
    {
        return unpack('N', substr(substr($bytes, $start), 0, 4))[1];
    }

    public function getQR(string $accountName, string $issuer, string $secret): string
    {
        if ('' === $accountName || false !== strpos($accountName, ':')) {
            throw OtpAuthException::InvalidAccountName($accountName);
        }

        if ('' === $secret) {
            throw OtpAuthException::InvalidSecret();
        }

        $label = $accountName;
        $otpauthString = 'otpauth://totp/%s?secret=%s';

        if (null !== $issuer) {
            if ('' === $issuer || false !== strpos($issuer, ':')) {
                throw OtpAuthException::InvalidIssuer($issuer);
            }

            $label = $issuer.':'.$label;
            $otpauthString .= '&issuer=%s';
        }

        $otpauthString = sprintf($otpauthString, $label, $secret, $issuer);

        $barcode = new Barcode();
        $qrCode = $barcode->getBarcodeObj(
            'QRCODE,H',
            $otpauthString,
            200,
            200,
            'black',
            [0,0,0,0]
        )->setBackgroundColor('white');

        return base64_encode($qrCode->getPngData());
    }
}