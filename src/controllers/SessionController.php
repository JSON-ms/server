<?php

use JSONms\Controllers\RestfulController;
use GuzzleHttp\Client as GuzzleClient;

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

    private function getEndpoints($userId) {
        $stmt = $this->query('get-all-endpoints', [
            'userId' => $userId,
        ]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        return [];
    }

    private function getLoginUrl() {

        $httpClient = new GuzzleClient([
            'timeout' => 3.0,
            'connect_timeout' => 3.0,
        ]);

        try {
            // Google Client Configuration
            $client = new Google_Client();
            $client->setHttpClient($httpClient);
            $client->setClientId($_ENV['GOOGLE_OAUTH_CLIENT_ID']);
            $client->setClientSecret($_ENV['GOOGLE_OAUTH_CLIENT_SECRET']);
            $client->setRedirectUri($_ENV['GOOGLE_OAUTH_CALLBACK_URL']);
            $client->addScope('email');
            $client->addScope('profile');

            return $client->createAuthUrl();
        } catch(\Exception $e) {
            throwError(500, $e->getMessage());
        }
    }

    public function indexAction() {

        $loggedIn = isset($_SESSION['access_token']) && $_SESSION['access_token'];
        $user = null;
        $loginUrl = null;
        $structures = [];
        $endpoints = [];

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
               $loginUrl = $this->getLoginUrl();
            }
        }
        else {
            $loginUrl = $this->getLoginUrl();
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
            $endpoints = $this->getEndpoints($user->id);
        }

        $this->responseJson([
            'loggedIn' => $loggedIn,
            'user' => $user,
            'googleOAuthSignInUrl' => $loginUrl,
            'structures' => $structures,
            'endpoints' => $endpoints,
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
            'endpoints' => [],
        ]);
    }
}
