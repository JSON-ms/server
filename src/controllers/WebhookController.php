<?php

use JSONms\Controllers\RestfulController;

class WebhookController extends RestfulController {

    public function saveAction($webhooks) {
        foreach($webhooks as $webhook) {
            if (isset($webhook->uuid)) {
                $this->query('update-webhook-by-uuid', [
                    'uuid' => $webhook->uuid,
                    'url' => $webhook->url,
                    'userId' => $this->getCurrentUserId(),
                ]);
            } else {
                $cypherKey = $this->getHash(24);
                $serverSecret = $this->encrypt($this->getHash(24), $cypherKey);
                $encryptedCypherKey = $this->encrypt($cypherKey, $_ENV['JSONMS_CYPHER_KEY']);
                $this->query('insert-webhook', [
                    'url' => $webhook->url,
                    'secret' => $serverSecret,
                    'cypher' => $encryptedCypherKey,
                    'created_by' => $this->getCurrentUserId(),
                ]);
            }
        }
        $stmt = $this->query('get-all-webhooks', [
            'userId' => $this->getCurrentUserId(),
        ]);

        if ($stmt->rowCount() > 0) {
            $this->responseJson($stmt->fetchAll(PDO::FETCH_OBJ));
        }
    }

    public function deleteAction($id) {
        $stmt = $this->query('delete-webhook-by-uuid', [
            'uuid' => $id,
            'userId' => $this->getCurrentUserId(),
        ]);
        if ($stmt->rowCount() > 0) {
            $this->responseJson(true);
        }
    }

    public function secretKeyAction($uuid) {
        $webhook = $this->getWebhook($uuid);
        $decryptedCypherKey = $this->decrypt($webhook->cypher, $_ENV['JSONMS_CYPHER_KEY']);
        $decryptedServerKey = $this->decrypt($webhook->secret, $decryptedCypherKey);
        $this->responseJson($decryptedServerKey);
    }

    public function cypherKeyAction($uuid) {
        $webhook = $this->getWebhook($uuid);
        $decryptedCypherKey = $this->decrypt($webhook->cypher, $_ENV['JSONMS_CYPHER_KEY']);
        $this->responseJson($decryptedCypherKey);
    }

    private function getWebhook($uuid, $showError = true): false | stdClass {
        $stmt = $this->query('get-webhook-by-uuid', [
            'uuid' => $uuid,
            'userId' => $this->getCurrentUserId(),
        ]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_OBJ);
        }
        if ($showError) {
            throwError(403, 'You don\'t have permission to access this webhook');
        }
        return false;
    }
}
