<?php

// Google Client Configuration
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']); // Replace with your Client ID
$client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']); // Replace with your Client Secret
$client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']); // Replace with your redirect URI

// Authenticate the user
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    // Database connection
    $conn = connectToDb();

    // Check if user already exists
    $stmt = $conn->prepare(getSql('get-user-by-google-id'));
    $stmt->bind_param("s", $userInfo->id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, fetch data
        $user = $result->fetch_assoc();
    } else {
        // User does not exist, insert new user
        $stmt = $conn->prepare(getSql('insert-user'));
        $stmt->bind_param("ssss", $userInfo->id, $userInfo->name, $userInfo->email, $userInfo->picture);
        $stmt->execute();
        $userId = $stmt->insert_id; // Get the new user's ID
        $user = [
            'id' => $userId,
            'googleId' => $userInfo->id,
            'name' => $userInfo->name,
            'email' => $userInfo->email,
            'avatar' => $userInfo->picture,
        ];
    }

    $stmt->close();
    $conn->close();

    // Store user information in the session
    $_SESSION['user'] = $user;
    $_SESSION['access_token'] = $token['access_token'];

    // Redirect to a protected page or dashboard
    $decodedState = json_decode(urldecode($_GET['state']), true);
    setcookie("PHPSESSID", session_id(), time() + 3600 * 24 * 7, "/", $_ENV['INTERFACE_EDITOR_DOMAIN']);
    header('Location: ' . $_ENV['INTERFACE_EDITOR_URL'] . $decodedState['path']);
    exit;
} else {
    throwError(400, "Error during authentication.");
}
