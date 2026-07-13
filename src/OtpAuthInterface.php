<?php

namespace MincDev\OtpAuth;

use DateTimeInterface;
use Exception;
use Random\RandomException;

interface OtpAuthInterface
{
    /**
     * Validate a TOTP code against the supplied secret.
     *
     * @throws Exception
     */
    public function validate(string $secret, string $code): bool;

    /**
     * Generate the TOTP code for the supplied secret.
     *
     * This method is primarily intended for testing,
     * interoperability and advanced use cases.
     *
     * Most applications should use validate() instead.
     */
    public function getCode(string $secret, ?DateTimeInterface $time = null): string;

    /**
     * Generate a QR code that can be scanned by compatible
     * authenticator applications to register the secret.
     *
     * Returns a PNG image as a Base64 data URI.
     *
     * @throws \Com\Tecnick\Barcode\Exception
     * @throws \Com\Tecnick\Color\Exception
     */
    public function getQR(string $accountName, ?string $issuer, string $secret): string;

    /**
     * Generate a new Base32-encoded secret suitable for TOTP.
     *
     * @throws RandomException
     */
    public function newSecret(): string;

    /**
     * Returns the standard otpauth:// URI for the TOTP configuration.
     *
     * This URI can be used with any QR code generation library or encoded
     * as a QR code for provisioning compatible authenticator applications.
     */
    public function getUri(string $accountName, ?string $issuer, string $secret): string;
}