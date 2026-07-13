<?php

declare(strict_types=1);

namespace MincDev\OtpAuth;

use RuntimeException;

/**
 * Exception thrown when invalid TOTP configuration is provided.
 */

final class OtpAuthException extends RuntimeException
{
    public const INVALID_ACCOUNT = 1001;
    public const INVALID_ISSUER = 1002;
    public const INVALID_SECRET = 1003;

    public static function invalidAccountName(string $accountName): self
    {
        return new self(
            "The account name must not be empty or contain a colon (:). Given: \"$accountName\".",
            self::INVALID_ACCOUNT
        );
    }

    public static function invalidIssuer(string $issuer): self
    {
        return new self(
            "The issuer must not be empty or contain a colon (:). Given: \"$issuer\".",
            self::INVALID_ISSUER
        );
    }

    public static function invalidSecret(): self
    {
        return new self(
            'The secret must not be empty.',
            self::INVALID_SECRET
        );
    }
}