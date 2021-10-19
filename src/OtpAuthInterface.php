<?php

namespace MincDev\OtpAuth;

use DateTimeInterface;

/**
 * Interface for the OtpAuth class
 *
 * @author Christopher Smit <christopher@mincdevelopment.co.za>
 * @package \MincDev\OtpAuth
 * @license MIT
 */

interface OtpAuthInterface
{
    /**
     * @param string $secret
     * @param string $code
     */
    public function validate(string $secret, string $code): bool;

    /**
     * @param string $secret
     * @param \DateTimeInterface|null $time
     */
    public function getCode(string $secret, ?DateTimeInterface $time = null): string;

    /**
     * @param string $accountName
     * @param string $issuer
     * @param string $secret
     */
    public function getQR(string $accountName, string $issuer, string $secret): string;

    public function newSecret(): string;
}