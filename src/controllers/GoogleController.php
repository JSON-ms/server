<?php

use JSONms\Controllers\RestfulController;

class GoogleController extends RestfulController {

    public function callbackAction() {

        $code = $_GET['code'];
        $state = $_GET['state'];

        // Google Client Configuration
        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']); // Replace with your Client ID
        $client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']); // Replace with your Client Secret
        $client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']); // Replace with your redirect URI

        // Authenticate the user
        if (!empty($code)) {
            $token = $client->fetchAccessTokenWithAuthCode($code);
            $client->setAccessToken($token['access_token']);
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            // Check if user already exists
            $stmt = $this->query('get-user-by-google-id', [
                'id' => $userInfo->id,
            ]);

            if ($stmt->rowCount() > 0) {
                // User exists, fetch data
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // User does not exist, insert new user
                $this->query('insert-user', [
                    'id' => $userInfo->id,
                    'name' => $userInfo->name,
                    'email' => $userInfo->email,
                    'picture' => $userInfo->picture
                ]);

                $userId = $this->getLastInsertedId(); // Get the new user's ID
                $user = [
                    'id' => $userId,
                    'googleId' => $userInfo->id,
                    'name' => $userInfo->name,
                    'email' => $userInfo->email,
                    'avatar' => $userInfo->picture,
                ];
            }

            // Store user information in the session
            $_SESSION['user'] = $user;
            $_SESSION['access_token'] = $token['access_token'];

            // Redirect to a protected page or dashboard
            $decodedState = json_decode(urldecode($state), true);
            header('Location: ' . $_ENV['INTERFACE_EDITOR_URL'] . $decodedState['path']);
            exit;
        } else {
            throwError(400, "Error during authentication.");
        }
    }
}
