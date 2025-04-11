<?php

use JSONms\Controllers\RestfulController;

class InterfaceController extends RestfulController {

    public function indexAction() {
        $stmt = $this->query('get-all-interfaces', [
            'userId' => $this->getCurrentUserId(),
        ]);
        $users = $stmt->fetchAll();
        $this->responseJson($users);
    }

    public function getAction($id) {
        $interface = $this->getAccessibleInterface($id);
        $this->responseJson($interface);
    }

    public function createAction($data) {
        $hash = $this->getHash();

        $this->query('insert-interface', [
            'hash' => $hash,
            'label' => $data->label,
            'logo' => $data->logo,
            'content' => $data->content,
            'webhook' => $data->webhook,
            'created_by' => $this->getCurrentUserId(),
        ]);

        // Get inserted interface
        $interface = $this->query('get-interface-by-hash', [
            'hash' => $hash,
        ])->fetch(PDO::FETCH_OBJ);
        $this->preparePermissions($interface);

        $this->responseJson($interface);
    }

    public function updateAction($id, $data) {
        $currentInterface = $this->getAccessibleInterface($id);
        if ($currentInterface) {

            // Copy current interface to history table
            $this->copyToHistory($currentInterface);

            // Update interface
            $this->query('update-interface-by-uuid', [
                'uuid' => $data->uuid,
                'label' => $data->label,
                'logo' => $data->logo,
                'content' => $data->content,
                'webhook' => $data->webhook,
                'userId' => $this->getCurrentUserId(),
            ]);

            // Clear all existing permissions (will be added later on)
            if ($data->created_by === $this->getCurrentUserId()) {
                $this->updatePermissions($data);
            }

            $this->responseJson($data);
        }
    }

    public function deleteAction($id) {
        if ($this->hasAccess($id)) {
            $stmt = $this->query('delete-interface', [
                'uuid' => $id,
                'userId' => $this->getCurrentUserId(),
            ]);
            if ($stmt->rowCount() > 0) {
                $this->responseJson(true);
            }
        }
    }

    private function hasAccess($uuid, $showError = true): bool {
        return (bool) $this->getAccessibleInterface($uuid, $showError);
    }

    private function getAccessibleInterface($uuid, $showError = true): false | stdClass {
        $stmt = $this->query('get-accessible-interface-by-uuid', [
            'uuid' => $uuid,
            'userId' => $this->getCurrentUserId(),
        ]);
        if ($stmt->rowCount() > 0) {
            $interface = $stmt->fetch(PDO::FETCH_OBJ);
            $this->preparePermissions($interface);
            return $interface;
        }
        if ($showError) {
            throwError(403, 'You don\'t have permission to view this interface');
        }
        return false;
    }

    private function preparePermissions(&$interface) {
        $interface->permission_admin = array_filter(explode(',', $interface->permission_admin ?? ''));
        $interface->permission_interface = array_filter(explode(',', $interface->permission_interface ?? ''));
        return $interface;
    }

    private function copyToHistory(stdClass $interface) {
        $this->query('insert-history', [
            'uuid' => $interface->uuid,
            'content' => $interface->content,
            'userId' => $this->getCurrentUserId(),
        ]);
    }

    private function updatePermissions($interface) {

        // Delete current permissions
        $this->query('delete-user-permissions', [
            'uuid' => $interface->uuid,
        ]);

        // Update with newest permissions
        foreach (['interface', 'admin'] as $type) {
            foreach ($interface->{'permission_' . $type} as $email) {
                $this->query('insert-permissions', [
                    'uuid' => $interface->uuid,
                    'type' => $type,
                    'email' => $email,
                ]);
            }
        }
    }
}
