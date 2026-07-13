<?php

declare(strict_types=1);

session_start();

use MincDev\OtpAuth\OtpAuthenticator;

require __DIR__ . '/../vendor/autoload.php';

$auth = new OtpAuthenticator();

$error = null;
$isValid = null;

try {
    $_SESSION['secret'] ??= $auth->newSecret();
    $secret = $_SESSION['secret'];
    $qrCode = $auth->getQR('John Doe', 'Some Site', $secret);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = trim($_POST['code'] ?? '');
        $isValid = $auth->validate($secret, $code);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PHP-OtpAuth Usage Example</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 30px;
        }

        input {
            padding: 8px;
            font-size: 16px;
            width: 150px;
        }

        button {
            padding: 8px 16px;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        code {
            background: #f5f5f5;
            padding: 2px 6px;
        }
    </style>
</head>
<body>

<h1>PHP-OtpAuth Usage Example</h1>

<p>
    Scan the QR code below using a compatible authenticator application,
    such as Google Authenticator, Microsoft Authenticator or Authy.
</p>

<?php if ($error !== null): ?>

    <p class="error"><?= htmlspecialchars($error) ?></p>

<?php else: ?>

    <h2>Your Secret</h2>

    <p>
        <code><?= htmlspecialchars($secret) ?></code>
    </p>

    <h2>QR Code</h2>

    <p>
        <img src="<?= $qrCode ?>" alt="Authenticator QR Code">
    </p>

    <h2>Validate a Code</h2>

    <form method="post">
        <input
            type="text"
            name="code"
            maxlength="6"
            placeholder="123456"
            autocomplete="one-time-code"
            required
        >

        <button type="submit">
            Validate
        </button>
    </form>

    <?php if ($isValid !== null): ?>

        <p class="<?= $isValid ? 'success' : 'error' ?>">
            <?= $isValid ? '✔ Code is valid.' : '✘ Invalid code.' ?>
        </p>

    <?php endif; ?>

<?php endif; ?>

</body>
</html>