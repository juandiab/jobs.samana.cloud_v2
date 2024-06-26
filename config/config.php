<?php
require_once  '/vendor/autoload.php';// Path to the vendor directory

$config = [
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID'),
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => 'https://' . $_ENV['APP_HOSTNAME'] . '/google-login/callback.php',
    ],
    'database' => [
        'host' => getenv('MYSQL_HOST'),
        'db' => getenv('MYSQL_DATABASE'),
        'user' => getenv('MYSQL_USER'),
        'pass' => getenv('MYSQL_PASSWORD'),
    ],
];
return $config;
?>
