<?php
// Set the content type to JSON for the response
require_once '../../../../bootstrap.php';
require_once '../../apiBootstrap.php';
header('Content-Type: application/json');

// Ensure we're dealing with a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

// Get the raw POST data (JSON payload)
$inputJSON = file_get_contents('php://input');

// Check if php://input received data
if ($inputJSON === false || empty($inputJSON)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No input received'
    ]);
    exit;
}

// Decode the JSON payload into an associative array
$input = json_decode($inputJSON, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON format: ' . json_last_error_msg()
    ]);
    exit;
}

// Check if email and password are provided
if (isset($input['email']) && isset($input['password'])) {
    $email = $input['email'];
    $password = $input['password'];
    $login = json_decode($auth->authenticate($email, $password));

    if ($login->status == 'success') {
        $user = new \ProjectSend\Classes\Users($login->user_id);
        $jwt_token= generateJwtToken($user->id, $user->email, getCsrfToken(), $expiration_time_in_seconds = 3600);
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $jwt_token,
            'expires_in' => $expiration_time_in_seconds // Token expiration time in seconds
        ]);

    } else {
        // Respond with an error if credentials are invalid
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
    }
} else {
    // Respond with an error if email or password is missing
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required'
    ]);
}
