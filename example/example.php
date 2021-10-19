<?php

use MincDev\OtpAuth\OtpAuthenticator;

require "../vendor/autoload.php";

$authCode = new OtpAuthenticator();

$qrCode = $authCode->getQR("John Doe", "Some Site", $authCode->newSecret());

echo "
<!DOCTYPE html>
<html>
    <head>
        <title>Usage example of PHP-OtpAuth library</title>
        <meta charset=\"utf-8\">
        <style>
            body {font-family:Arial, Helvetica, sans-serif;margin:30px;}
            table {border: 1px solid black;}
            th {border: 1px solid black;padding:4px;background-barcode:cornsilk;}
            td {border: 1px solid black;padding:4px;}
            h3 {color:darkblue;}
            h4 {color:darkgreen;}
            h4 span  {color:firebrick;}
        </style>
    </head>
    <body>
        <h1>Usage example of PHP-OtpAuth library</h1>
        <p>This is an usage example of <a href=\"\" title=\"PHP library to generate a google authenticator barcode\">PHP-OtpAuth</a> library.</p>
        <h2>QR Code Output</h2>
        <p><img alt=\"Embedded Image\" src=\"data:image/png;base64,".$qrCode."\" /></p>
    </body>
</html>
";