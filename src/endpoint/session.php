<?php

$loggedIn = isset($_SESSION['access_token']) && $_SESSION['access_token'];
$user = null;
$interfaces = [];
$conn = connectToDb();
$loginUrl = null;

if ($loggedIn) {

    // Check if user already exists
    $stmt = $conn->prepare(getSql('get-user-by-id'));
    $stmt->bind_param("s", $_SESSION['user']['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, fetch data
        $user = $result->fetch_assoc();

        // Fetch all interfaces
        $stmt = $conn->prepare(getSql('get-all-interfaces'));
        $stmt->bind_param("ss", $user['id'], $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['permission_admin'] = array_filter(explode(',', $row['permission_admin'] ?? ''));
                $row['permission_interface'] = array_filter(explode(',', $row['permission_interface'] ?? ''));
                $interfaces[] = $row;
            }
        }
    }
}
else {

    // Google Client Configuration
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']);
    $client->addScope('email');
    $client->addScope('profile');

    $loginUrl = $client->createAuthUrl();

    try {
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        $loggedIn = true;
    } catch(\Exception $e) {
        die(json_encode([
            'loggedIn' => false,
            'user' => $user,
            'googleOAuthSignInUrl' => $loginUrl,
            'interfaces' => $interfaces,
        ]));
    }

    // Check if user already exists
    $stmt = $conn->prepare(getSql('get-user-by-google-id'));
    $stmt->bind_param("s", $userInfo->id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
}

// Close MySQL connection
$conn->close();

echo json_encode([
    'loggedIn' => $loggedIn && isset($user),
    'user' => $user,
    'googleOAuthSignInUrl' => $loginUrl,
    'interfaces' => $interfaces,
]);
