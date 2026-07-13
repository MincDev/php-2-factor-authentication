# PHP-OtpAuth

[![Tests](https://github.com/mincdev/php-otpauth/actions/workflows/run-tests.yml/badge.svg)](https://github.com/mincdev/php-otpauth/actions/workflows/run-tests.yml)

A lightweight PHP library for generating and validating **Time-based One-Time Passwords (TOTP)** compatible with Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden, and other RFC 6238 compatible authenticator applications.

## Features

- 🔒 Generate cryptographically secure TOTP secrets
- 📱 Generate QR codes for easy authenticator app provisioning
- ✅ Validate one-time passwords
- 🧪 Verified against the official **RFC 6238** test vectors
- 🖥️ QR codes are generated locally on your server — no secrets are sent to third-party services
- 🚀 Lightweight with minimal dependencies

## Installation

Install the package using Composer:

```bash
composer require mincdev/php-otpauth
```

## Generating a Secret and QR Code

```php
use MincDev\OtpAuth\OtpAuthenticator;

$otp = new OtpAuthenticator();

// Store the secret securely for the user.
$secret = $otp->newSecret();

$qrCode = $otp->getQR(
    'john.doe@example.com',
    'My Awesome App',
    $secret
);
```

The returned value is a Base64 data URI that can be used directly in an image tag:

```html
<img src="<?= $qrCode ?>" alt="Authenticator QR Code">
```

## Validating a Code

```php
use MincDev\OtpAuth\OtpAuthenticator;

$otp = new OtpAuthenticator();

$isValid = $otp->validate($secret, $userEnteredCode);
```

## Security

This library generates QR codes **entirely on your server**. The user's secret is never transmitted to an external QR code generation service, ensuring that sensitive authentication data remains under your control.

QR code generation is powered by `tc-lib-barcode`.

## Compatibility

This library implements **RFC 6238 (TOTP)** and works with applications such as:

- Google Authenticator
- Microsoft Authenticator
- Authy
- 1Password
- Bitwarden
- and other compatible authenticator apps.

## Testing

The TOTP implementation is verified against the official **RFC 6238** test vectors to ensure standards-compliant code generation.

Run the test suite with:

```bash
composer test
```

## License

Released under the MIT License.