<?php

// Start a new session or resume the existing session
session_start();

// Set CORS headers to allow requests from a specific origin defined in the environment variable
header("Access-Control-Allow-Origin: http://localhost:3000");
// Allow specific HTTP methods for cross-origin requests
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
// Allow specific headers in the request
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Jms-Api-Key");
// Indicate that credentials (like cookies) can be included in cross-origin requests
header("Access-Control-Allow-Credentials: true");

// Set the content type of the response to application/json
header('Content-Type: application/json');

// Handle preflight requests (OPTIONS method) by responding with a 200 OK status
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(); // Exit to prevent further processing
}

// Check for JSON errors in the request
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['body' => 'Invalid JSON']);
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200); // Set response status to 200 OK
    // Read the content of a JSON file based on the 'hash' parameter from the query string
    $content = file_get_contents(__DIR__ . '/../../private/' . $_GET['hash'] . '.json');
    // If no content is found, return an empty JSON object
    if (!$content) {
        echo '{}';
    } else {
        echo $content; // Return the content of the JSON file
    }
    exit; // Exit to prevent further processing
}
// Handle POST requests
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data from the request body
    $json = file_get_contents('php://input');
    // Decode the JSON data into a PHP object
    $data = (object) json_decode($json, true);
    // Save the data to a JSON file named after the 'hash' property in the data
    file_put_contents(
        __DIR__ . '/../../private/' . $data->hash . '.json',
        json_encode($data->data) // Encode the 'data' property back to JSON format
    );
    http_response_code(200); // Set response status to 200 OK
    echo json_encode($data); // Return the saved data as a JSON response
    exit; // Exit to prevent further processing
}
