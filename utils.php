<?php

function dd(...$values) {
    die(var_dump(...$values));
}

function throwError($code, $body) {
    http_response_code($code || 500);
    echo json_encode(['body' => $body]);
    exit;
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

function shutdownFunction() {
    $error = error_get_last();
    if ($error) {
        error_log("Exception: " . $error['message'], 3, 'errors.log');
        http_response_code(500);
        echo json_encode(['body' => $error['message']]);
        exit();
    }
}
register_shutdown_function('shutdownFunction');
