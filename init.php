<?php
$password = 'your_plain_text_password_here';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo $hashedPassword;