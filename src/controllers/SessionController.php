<?php

use JSONms\Controllers\RestfulController;

class SessionController extends RestfulController {

    private function getDemoStructure() {
        $stmt = $this->query('get-demo-structure');
        if ($stmt->rowCount() > 0) {
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            foreach ($rows as $row) {
                $row->permission_admin = [];
                $row->permission_structure = [];
                $row->type = 'structure,admin';
                return $row;
            }
        }
        return null;
    }

    private function getWebhooks($userId) {
        $stmt = $this->query('get-all-webhooks', [
            'userId' => $userId,
        ]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        return [];
    }

    public function indexAction() {

        $loggedIn = isset($_SESSION['access_token']) && $_SESSION['access_token'];
        $user = null;
        $loginUrl = null;
        $structures = [];
        $webhooks = [];

        // Fetch demo structure
        $demo = $this->getDemoStructure();
        if ($demo) {
            $structures[] = $demo;
        }

        if ($loggedIn) {

            // Check if user already exists
            $stmt = $this->query('get-user-by-id', [
                'id' => $this->getCurrentUserId(),
            ]);

            if ($stmt->rowCount() > 0) {

                // User exists, fetch data
                $user = $stmt->fetch(PDO::FETCH_OBJ);
            } else {
                // Google Client Configuration
                $client = new Google_Client();
                $client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']);
                $client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']);
                $client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']);
                $client->addScope('email');
                $client->addScope('profile');
                $loginUrl = $client->createAuthUrl();
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
                    'structures' => $structures,
                    'webhooks' => $webhooks,
                ]);
            }

            // Check if user already exists
            $stmt = $this->query('get-user-by-google-id', [
                'id' => $this->getCurrentUserId(),
            ]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_OBJ);
            }
        }

        if ($loggedIn && isset($user)) {

            // Fetch all structures
            $stmt = $this->query('get-all-structures', [
                'userId' => $this->getCurrentUserId(),
            ]);
            $structures = [];
            if ($stmt->rowCount() > 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
                foreach ($rows as $row) {
                    $row->permission_admin = array_filter(explode(',', $row->permission_admin ?? ''));
                    $row->permission_structure = array_filter(explode(',', $row->permission_structure ?? ''));
                    $structures[] = $row;
                }
            }
        }

        // Fetch demo structure
        $loggedIn = $loggedIn && isset($user);
        if ($loggedIn) {
            $webhooks = $this->getWebhooks($user->id);
        }

        $this->responseJson([
            'loggedIn' => $loggedIn,
            'user' => $user,
            'googleOAuthSignInUrl' => $loginUrl,
            'structures' => $structures,
            'webhooks' => $webhooks,
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

        // Fetch demo structure
        $structures = [];
        $demo = $this->getDemoStructure();
        if ($demo) {
            $structures[] = $demo;
        }

        // Return the JSON response
        $this->responseJson([
            'loggedIn' => false,
            'user' => null,
            'googleOAuthSignInUrl' => $loginUrl,
            'structures' => $structures,
            'webhooks' => [],
        ]);
    }
}
