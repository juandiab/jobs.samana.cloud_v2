<?php
session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';

$config = include('../google-login/config.php');

$client = new Google_Client();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_uri']);
$client->addScope($config['google']['scopes']);

$loginUrl = $client->createAuthUrl();
header('Location: ' . filter_var($loginUrl, FILTER_SANITIZE_URL));
exit();
?>
