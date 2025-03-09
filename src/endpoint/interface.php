<?php

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$postedData = (object) $data['interface'];
$postedData->permission_admin = $postedData->permission_admin ?? [];
$postedData->permission_interface = $postedData->permission_interface ?? [];
$interface = $postedData;

if (json_last_error() !== JSON_ERROR_NONE) {
    throwError(400, 'Invalid JSON');
}

$hash = null;
$conn = connectToDb();

function generateShortHash($length = 10) {
    $bytes = random_bytes($length);
    $result = bin2hex($bytes);
    return substr($result, 0, $length);
}

// Existing interface..
if (isset($postedData->uuid)) {

    $hash = $postedData->hash;

    // Does the user has access to this interface?
    $stmt = $conn->prepare(getSql('get-accessible-interface-by-uuid'));
    $stmt->bind_param("sii", $postedData->uuid, $_SESSION['user']['id'], $_SESSION['user']['id']);
    $stmt->execute();
    if ($stmt->affected_rows == 0) {
        throwError(401, 'You do not have permission to perform this action.');
    }

    // Delete interface request
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $stmt->close();

        $stmt = $conn->prepare(getSql('delete-interface'));
        $stmt->bind_param("si", $postedData->uuid, $_SESSION['user']['id']);
        $stmt->execute();
        $stmt->close();

        http_response_code(200);
        echo json_encode(true);
        exit;
    }

    // Update existing interface
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Insert old interface into history
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $currentInterface = (object) $result->fetch_assoc();
            $stmt = $conn->prepare(getSql('insert-history'));
            $stmt->bind_param("ssi", $postedData->uuid, $currentInterface->content, $_SESSION['user']['id']);
            $stmt->execute();
            $stmt->close();
        }

        // Update interface
        $sql = getSql('update-interface-by-uuid');
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $postedData->label, $postedData->logo, $postedData->content, $postedData->server_url, $postedData->uuid);
        $stmt->execute();
        $stmt->close();

        // Clear all existing permissions (will be added later on)
        if ($postedData->created_by === $_SESSION['user']['id']) {
            $stmt = $conn->prepare(getSql('delete-user-permissions'));
            $stmt->bind_param("s", $postedData->uuid);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// New interface..
else {
    $hash = generateShortHash(10);
    $cypherKey = generateShortHash(24);
    $serverSecret = encrypt(generateShortHash(24), $cypherKey);
    $encryptedCypherKey = encrypt($cypherKey, $_ENV['JSONMS_CYPHER_KEY']);
    $stmt = $conn->prepare(getSql('insert-interface'));
    $stmt->bind_param("sssssssi", $hash, $postedData->label, $postedData->logo, $postedData->content, $postedData->server_url, $serverSecret, $encryptedCypherKey, $_SESSION['user']['id']);
    $stmt->execute();
    $stmt->close();
}

// Fetch updated interface
$stmt = $conn->prepare(getSql('get-interface-by-hash'));
$stmt->bind_param("s", $hash);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $interface = (object) $result->fetch_assoc();
}
$stmt->close();

// Insert permissions if any..
if ($interface->created_by === $_SESSION['user']['id']) {
    foreach (['interface', 'admin'] as $type) {
        foreach ($postedData->{'permission_' . $type} as $email) {
            $stmt = $conn->prepare(getSql('insert-permissions'));
            $stmt->bind_param("sss", $interface->uuid, $type, $email);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Close MySQL connection
$conn->close();

// Return new/updated interface
http_response_code(200);
echo json_encode([
    'interface' => $interface,
]);
exit;

