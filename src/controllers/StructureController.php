<?php

use JSONms\Controllers\RestfulController;

class StructureController extends RestfulController {

    public function indexAction() {
        $stmt = $this->query('get-all-structures', [
            'userId' => $this->getCurrentUserId(),
        ]);
        $users = $stmt->fetchAll();
        $this->responseJson($users);
    }

    public function getAction($id) {
        $structure = $this->getAccessibleStructure($id);
        $this->responseJson($structure);
    }

    public function createAction($data) {
        $hash = $this->getHash();

        $this->query('insert-structure', [
            'hash' => $hash,
            'label' => $data->label,
            'logo' => $data->logo,
            'content' => $data->content,
            'webhook' => $data->webhook,
            'created_by' => $this->getCurrentUserId(),
        ]);

        // Get inserted structure
        $structure = $this->query('get-structure-by-hash', [
            'hash' => $hash,
        ])->fetch(PDO::FETCH_OBJ);
        $this->preparePermissions($structure);

        // Update structure
//        $this->updateStructure($hash, $structure);

        $this->responseJson($structure);
    }

    public function updateAction($id, $data) {
        $currentStructure = $this->getAccessibleStructure($id);
        if ($currentStructure) {

            // Copy current structure to history table
            $this->copyToHistory($currentStructure);

            // Update structure
            $this->query('update-structure-by-uuid', [
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

            // Update structure
//            $this->updateStructure($data->hash, $structure);

            $this->responseJson($data);
        }
    }

    public function deleteAction($id) {
        if ($this->hasAccess($id)) {
            $stmt = $this->query('delete-structure', [
                'uuid' => $id,
                'userId' => $this->getCurrentUserId(),
            ]);
            if ($stmt->rowCount() > 0) {
                $this->responseJson(true);
            }
        }
    }

    private function hasAccess($uuid, $showError = true): bool {
        return (bool) $this->getAccessibleStructure($uuid, $showError);
    }

    private function getAccessibleStructure($uuid, $showError = true): false | stdClass {
        $stmt = $this->query('get-accessible-structure-by-uuid', [
            'uuid' => $uuid,
            'userId' => $this->getCurrentUserId(),
        ]);
        if ($stmt->rowCount() > 0) {
            $structure = $stmt->fetch(PDO::FETCH_OBJ);
            $this->preparePermissions($structure);
            return $structure;
        }
        if ($showError) {
            throwError(403, 'You don\'t have permission to view this structure');
        }
        return false;
    }

    private function preparePermissions(&$structure) {
        $structure->permission_admin = array_filter(explode(',', $structure->permission_admin ?? ''));
        $structure->permission_structure = array_filter(explode(',', $structure->permission_structure ?? ''));
        return $structure;
    }

    private function copyToHistory(stdClass $structure) {
        $this->query('insert-history', [
            'uuid' => $structure->uuid,
            'content' => $structure->content,
            'userId' => $this->getCurrentUserId(),
        ]);
    }

    private function updatePermissions($structure) {

        // Delete current permissions
        $this->query('delete-user-permissions', [
            'uuid' => $structure->uuid,
        ]);

        // Update with newest permissions
        foreach (['structure', 'admin'] as $type) {
            foreach ($structure->{'permission_' . $type} as $email) {
                $this->query('insert-permissions', [
                    'uuid' => $structure->uuid,
                    'type' => $type,
                    'email' => $email,
                ]);
            }
        }
    }
}
