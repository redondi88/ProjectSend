<?php
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
function generateJwtToken($user_id, $email, $secret_key, $expiration_time_in_seconds = 3600) {

    // Current timestamp
    $issued_at = time();
    $expiration_time = $issued_at + $expiration_time_in_seconds;  // Token expiration time

    // JWT Payload
    $payload = [
        // 'iss' => 'getBaseUri',         // Issuer
        'iat' => $issued_at,               // Issued at time
        'exp' => $expiration_time,         // Expiration time
        'data' => [
            'user_id' => $user_id,
            'email' => $email
        ]
    ];

    // Encode the payload to generate the JWT token
    $jwt = JWT::encode($payload, $secret_key, 'HS256');  // 'HS256' is the hashing algorithm used

    return $jwt;
}

function verify_jwt($jwt, $secret_key) {
    try {
        // Decode the JWT token
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        // Handle errors (e.g., expired token, invalid signature)
        return null;
    }
}
