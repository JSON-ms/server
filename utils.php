<?php

$conn = null;

function dd(...$values) {
    die(var_dump(...$values));
}

function getSql($name) {
    return file_get_contents(__DIR__ . '/src/queries/' . $name . '.sql');
}

function throwError($code, $body) {
    http_response_code($code || 500);
    echo json_encode(['body' => $body]);
    exit;
}

function connectToDb() {
    $conn = new mysqli(
        $_ENV['DATABASE_HOST'],
        $_ENV['DATABASE_USERNAME'],
        $_ENV['DATABASE_PASSWORD'],
        $_ENV['DATABASE_DBNAME'],
    );

    if ($conn->connect_error) {
        throwError(500, "Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline", 3, 'errors.log');
    http_response_code(500);
    echo json_encode(['body' => "[$errno]: $errstr in $errfile on line $errline"]);
    exit();
}
set_error_handler("customErrorHandler");

function customExceptionHandler($exception) {
    error_log("Exception: " . $exception->getMessage(), 3, 'errors.log');
    http_response_code(500);
    echo json_encode(['body' => $exception->getMessage()]);
    exit();
}
set_exception_handler("customExceptionHandler");

function encrypt($data, $encryptionKey) {
    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $encryptionKey, 0, $iv);
    return base64_encode($encryptedData . '::' . $iv);
}

function decrypt($encryptedData, $encryptionKey) {
    $arr = explode('::', base64_decode($encryptedData), 2);
    if (count($arr) != 2) {
        return false;
    }
    list($encryptedData, $iv) = $arr;
    return openssl_decrypt($encryptedData, 'AES-256-CBC', $encryptionKey, 0, $iv);
}

function generateHash($input_string, $length = 64) {
    $full_hash = hash('sha256', $input_string);
    if ($length > strlen($full_hash)) {
        $length = strlen($full_hash);
    }
    return substr($full_hash, 0, $length);
}
