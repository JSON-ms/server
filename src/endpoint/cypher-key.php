<?php

$conn = connectToDb();

// Get json content
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $conn->prepare(getSql('get-accessible-interface-by-uuid'));
    $stmt->bind_param("sii", $_GET['uuid'], $_SESSION['user']['id'], $_SESSION['user']['id']);
    $stmt->execute();
    if ($stmt->affected_rows == 0) {
        throwError(401, 'You do not have permission to perform this action.');
    }
    $result = $stmt->get_result();
    $interface = (object) $result->fetch_assoc();
    $decryptedCypherKey = decrypt($interface->cypher_key, $_ENV['JSONMS_CYPHER_KEY']);
    http_response_code(200);
    echo json_encode($decryptedCypherKey);
}

// Close MySQL connection
$conn->close();
exit;
