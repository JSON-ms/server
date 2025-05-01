<?php

use JSONms\Controllers\RestfulController;

class EndpointController extends RestfulController {

    public function saveAction($endpoints) {
        foreach($endpoints as $endpoint) {
            if (isset($endpoint->uuid)) {
                $this->query('update-endpoint-by-uuid', [
                    'uuid' => $endpoint->uuid,
                    'url' => $endpoint->url,
                    'userId' => $this->getCurrentUserId(),
                ]);
            } else {
                $cypherKey = $this->getHash(24);
                $serverSecret = $this->encrypt($this->getHash(24), $cypherKey);
                $encryptedCypherKey = $this->encrypt($cypherKey, $_ENV['JSONMS_CYPHER_KEY']);
                $this->query('insert-endpoint', [
                    'url' => $endpoint->url,
                    'secret' => $serverSecret,
                    'cypher' => $encryptedCypherKey,
                    'created_by' => $this->getCurrentUserId(),
                ]);
            }
        }
        $stmt = $this->query('get-all-endpoints', [
            'userId' => $this->getCurrentUserId(),
        ]);

        if ($stmt->rowCount() > 0) {
            $this->responseJson($stmt->fetchAll(PDO::FETCH_OBJ));
        }
    }

    public function deleteAction($id) {
        $stmt = $this->query('delete-endpoint-by-uuid', [
            'uuid' => $id,
            'userId' => $this->getCurrentUserId(),
        ]);
        if ($stmt->rowCount() > 0) {
            $this->responseJson(true);
        }
    }

    public function secretKeyAction($uuid) {
        $endpoint = $this->getEndpoint($uuid);
        $decryptedCypherKey = $this->decrypt($endpoint->cypher, $_ENV['JSONMS_CYPHER_KEY']);
        $decryptedServerKey = $this->decrypt($endpoint->secret, $decryptedCypherKey);
        $this->responseJson($decryptedServerKey);
    }

    public function cypherKeyAction($uuid) {
        $endpoint = $this->getEndpoint($uuid);
        $decryptedCypherKey = $this->decrypt($endpoint->cypher, $_ENV['JSONMS_CYPHER_KEY']);
        $this->responseJson($decryptedCypherKey);
    }

    private function getEndpoint($uuid, $showError = true): false | stdClass {
        $stmt = $this->query('get-endpoint-by-uuid', [
            'uuid' => $uuid,
            'userId' => $this->getCurrentUserId(),
        ]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_OBJ);
        }
        if ($showError) {
            throwError(403, 'You don\'t have permission to access this endpoint');
        }
        return false;
    }
}
