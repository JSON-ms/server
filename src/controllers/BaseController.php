<?php

namespace JSONms\Controllers;

use \PDO;
use \PDOStatement;
use \PDOException;
use \stdClass;

abstract class BaseController {

    private ?\PDO $pdo = null;
    private $user = null;

    public function getLastInsertedId(): string | false {
        return $this->pdo->lastInsertId();
    }

    public function connectToDatabase() {
        try {
            $host = $_ENV['DATABASE_HOST'];
            $dbName = $_ENV['DATABASE_DBNAME'];
            $charset = 'utf8mb4';
            $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, $_ENV['DATABASE_USERNAME'], $_ENV['DATABASE_PASSWORD'], $options);
        } catch (PDOException $e) {
            throwError(500, "Connection failed: " . $e->getMessage());
        }
    }

    public function getSqlFromQueryName(string $name) {
        return file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/src/queries/' . $name . '.sql');
    }

    public function queryRaw(string $sql, array $params = []) {
        if (!$this->pdo) {
            $this->connectToDatabase();
        }
        try {

            // Count how many times each param key are in the query
            // and add an incrementation next to the key so we can
            // change the same parameter multiple times using the same
            // key. (PDO limitation)
            $updatedSql = $sql;
            $prefixedKeys = array_map(function($key) {
                return ':' . $key;
            }, array_keys($params));
            $params = array_combine($prefixedKeys, array_values($params));
            $updatedParams = [];
            $count = -1;
            foreach ($params as $key => $value) {
                $updatedSql = preg_replace_callback('/' . preg_quote($key, '/') . '/i', function ($matches) use (&$count, &$key, &$value, &$updatedParams) {
                    $count++;
                    $updatedParams[$key . $count] = $value;
                    return $matches[0] . $count;
                }, $updatedSql);
            }

            // Place the parameters and execute the query
            $stmt = $this->pdo->prepare($updatedSql);
            foreach ($updatedParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } catch (\PDOException $e) {
            throwError(500, $e->getMessage());
        }
        return $stmt;
    }

    public function query(string $query, array $params = []): PDOStatement {
        $sql = $this->getSqlFromQueryName($query);
        return $this->queryRaw($sql, $params);
    }

    public function getCurrentUserId() {
        if ($this->user == null && isset($_SESSION['user_id'])) {
            $stmt = $this->query('get-user-by-id', [
                'id' => $_SESSION['user_id']
            ]);
            $this->user = $stmt->fetch(PDO::FETCH_OBJ);
            if ($this->user) {
                return $this->user->id;
            }
            return null;
        }
        return $this->user->id;
    }

    protected function getHash($length = 10): string {
        $bytes = random_bytes($length);
        $result = bin2hex($bytes);
        return substr($result, 0, $length);
    }

    public function encrypt($data, $encryptionKey) {
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $encryptionKey, 0, $iv);
        return base64_encode($encryptedData . '::' . $iv);
    }

    public function decrypt($encryptedData, $encryptionKey) {
        $arr = explode('::', base64_decode($encryptedData), 2);
        if (count($arr) != 2) {
            return false;
        }
        list($encryptedData, $iv) = $arr;
        return openssl_decrypt($encryptedData, 'AES-256-CBC', $encryptionKey, 0, $iv);
    }
}
