<?php

$headers = getallheaders();
if (!isset($headers['X-Jms-Api-Key'])) {
    throwError(401, 'API Secret Key not provided');
} else if (decrypt($headers['X-Jms-Api-Key'], $_ENV['API_CYPHER_KEY']) !== $_ENV['API_SECRET_KEY']) {
    throwError(401, 'Invalid API Secret Key');
}

$hasFile = isset($_FILES['file']);
$privatePath = __DIR__ . '/../../private/';
$dataPath = $privatePath . 'data/';
$interfacePath = $privatePath . 'interfaces/';
$uploadDir = $privatePath . 'files/';
$serverSettings = [
    "uploadMaxSize" => ini_get('upload_max_filesize'),
    "postMaxSize" => ini_get('post_max_size'),
    'publicUrl' => $_ENV['PUBLIC_FILE_PATH'],
];

// Create directory if not existing
if (!is_dir($interfacePath)) {
    mkdir($interfacePath, 0755, true);
}
if (!is_dir($dataPath)) {
    mkdir($dataPath, 0755, true);
}
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get json content
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    $dataFilePath = $dataPath . $_GET['hash'] . '.json';
    $interfaceFilePath = $interfacePath . $_GET['hash'] . '.json';
    $data = [];
    $interface = [];
    if (file_exists($dataFilePath)) {
        $data = json_decode(file_get_contents($dataFilePath));
    }
    if (file_exists($interfaceFilePath)) {
        $interface = json_decode(file_get_contents($interfaceFilePath));
    }
    echo json_encode([
        'data' => $data,
        'interface' => $interface,
        'settings' => $serverSettings,
    ]);
    exit;
}

// File upload
else if ($hasFile && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($headers['X-Jms-Interface-Hash'])) {
        throwError(400, 'Interface hash not provided.');
    }

    // Something wrong with the upload..
    if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                throwError(400, "Error: The uploaded file exceeds the maximum file size limit.");
                break;
            case UPLOAD_ERR_FORM_SIZE:
                throwError(400, "Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
                break;
            case UPLOAD_ERR_PARTIAL:
                throwError(400, "Error: The uploaded file was only partially uploaded.");
                break;
            case UPLOAD_ERR_NO_FILE:
                throwError(400, "Error: No file was uploaded.");
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                throwError(400, "Error: Missing a temporary folder.");
                break;
            case UPLOAD_ERR_CANT_WRITE:
                throwError(400, "Error: Failed to write file to disk.");
                break;
            case UPLOAD_ERR_EXTENSION:
                throwError(400, "Error: A PHP extension stopped the file upload.");
                break;
            default:
                throwError(400, "Error: Unknown upload error.");
                break;
        }
    }
    // All good.. proceeding with the upload..
    else {

        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Specify the directory where you want to save the uploaded file
        $destPath = $uploadDir . $headers['X-Jms-Interface-Hash'] . '-' . generateHash($fileName, 16) . '.' . $extension;

        // Move the file to the desired directory
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $internalPath = str_replace($uploadDir, '', $destPath);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'publicPath' => $_ENV['PUBLIC_FILE_PATH'] . $internalPath,
                'internalPath' => $internalPath,
            ]);
            exit;
        } else {
            throwError(400, 'There was an error moving the uploaded file.');
        }
    }
}

// Create/Update json
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = (object) json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throwError(400, 'Invalid JSON');
    }

    file_put_contents(
        $dataPath . $data->hash . '.json',
        json_encode($data->data)
    );
    file_put_contents(
        $interfacePath . $data->hash . '.json',
        json_encode($data->interface)
    );
    http_response_code(200);
    echo json_encode($data);
    exit;
}


