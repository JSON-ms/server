<?php

use JSONms\Controllers\RestfulController;

class SessionController extends RestfulController {

    public function indexAction() {

        $loggedIn = isset($_SESSION['access_token']) && $_SESSION['access_token'];
        $user = null;
        $interfaces = [];
        $loginUrl = null;

        if ($loggedIn) {

            // Check if user already exists
            $stmt = $this->query('get-user-by-id', [
                'id' => $this->getCurrentUser()->id,
            ]);

            if ($stmt->rowCount() > 0) {

                // User exists, fetch data
                $user = $stmt->fetch(PDO::FETCH_OBJ);

                // Fetch all interfaces
                $stmt = $this->query('get-all-interfaces', [
                    'userId' => $this->getCurrentUser()->id,
                ]);

                if ($stmt->rowCount() > 0) {
                    $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                    foreach ($rows as $row) {
                        $row->permission_admin = array_filter(explode(',', $row->permission_admin ?? ''));
                        $row->permission_interface = array_filter(explode(',', $row->permission_interface ?? ''));
                        $interfaces[] = $row;
                    }
                }
            }
        }
        else {

            try {
                // Google Client Configuration
                $client = new Google_Client();
                $client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']);
                $client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']);
                $client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']);
                $client->addScope('email');
                $client->addScope('profile');
                $loginUrl = $client->createAuthUrl();
            } catch(\Exception $e) {
                throwError(500, $e->getMessage());
            }

            try {
                $oauth2 = new Google_Service_Oauth2($client);
                $oauth2->userinfo->get();
                $loggedIn = true;
            } catch(\Exception $e) {
                $this->responseJson([
                    'error' => $e->getMessage(),
                    'loggedIn' => false,
                    'user' => $user,
                    'googleOAuthSignInUrl' => $loginUrl,
                    'interfaces' => $interfaces,
                ]);
            }

            // Check if user already exists
            $stmt = $this->query('get-user-by-google-id', [
                'id' => $this->getCurrentUser()->id,
            ]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_OBJ);
            }
        }

        $this->responseJson([
            'loggedIn' => $loggedIn && isset($user),
            'user' => $user,
            'googleOAuthSignInUrl' => $loginUrl,
            'interfaces' => $interfaces,
        ]);
    }

    public function logoutAction() {

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
        $this->responseJson([
            'loggedIn' => false,
            'user' => null,
            'googleOAuthSignInUrl' => $loginUrl
        ]);
    }
}
