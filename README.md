# php-otpauth

 A library for generating a 2 factor authentication QR code to use with Google Authenticator, Authy, etc.
 
### Secure QRCode Creation

This library has secure QRCode creation because of the fact that the QRCode is generated locally on your server. This means that the user's secret is not passed to any third party or remote server in order to generate a code. This was inspired by the stack overflow answer by **kravietz** as seen [here](https://stackoverflow.com/a/56737468/3948544)

### Installation (Composer)

```
composer require mincdev/php-otpauth
```

### Dependencies

This library requires the **tc-lib-barcode** library found at https://github.com/tecnickcom/tc-lib-barcode. 

**Note:** The tc-lib-barcode library is maintained and owned by a separate entity.

#### Generating a QR Code

You can generate a QR code which can be scanned by Google Authenticator, Authy, etc. by using the below.

```php
$otpAuth = new OtpAuthenticator();

$userName = "MrDoe";
$appName = "My Awesome App";

// Store this secret somewhere safe, as you'll need it to validate the pin later
$userSecret = $otpAuth->newSecret();

$qrBase64 = $otpAuth->getQR($userName, $appName, $userSecret);
```

#### Validating a PIN

Once your user logs in, you can validate their pin by making use of the following:

```php
$otpAuth = new OtpAuthenticator();
$isValid = $otpAuth->validate($userSecret, $pinCode);
```
