<?php

namespace JSONms\Controllers;

use \PDO;
use \PDOStatement;
use \PDOException;
use \stdClass;

abstract class RestfulController extends BaseController {

    public function responseJson($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    public function indexAction() {

    }

    public function getAction(string $id) {

    }

    public function createAction(stdClass $data) {

    }

    public function updateAction(string $id, stdClass $data) {

    }

    public function deleteAction(string $id) {

    }
}
