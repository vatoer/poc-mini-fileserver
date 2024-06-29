<?php
// Include the common functions file
require 'vendor/autoload.php';
require_once 'common.php';
$hashedPassword = password_hash(PLAIN_PASSWORD, PASSWORD_DEFAULT);
//echo password_verify(PLAIN_PASSWORD, $hashedPassword);
echo $hashedPassword.PHP_EOL;