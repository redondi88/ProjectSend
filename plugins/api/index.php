<?php
require_once '../../bootstrap.php';
$response = ['status' => true , 'message' => 'Service Operational'];
header('Content-Type: application/json');
echo json_encode($response);

