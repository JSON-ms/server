<?php
session_destroy();

// Google Client Configuration
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']);
$client->addScope('email');
$client->addScope('profile');

// Generate the login URL
$loginUrl = $client->createAuthUrl();

// Return the JSON response
echo json_encode([
    'loggedIn' => false,
    'user' => null,
    'googleOAuthSignInUrl' => $loginUrl
]);

