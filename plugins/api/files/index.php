<?php
// Include required dependencies
require_once '../../../bootstrap.php';
require_once '../apiBootstrap.php';
$token= getBearerToken();

print_r(verify_jwt($token, getCsrfToken()));

// Set response headers
header('Content-Type: application/json');

// Default response structure
$response = [
    'message' => 'Service Operational',
    'data' => []
];

// Pagination setup with default fallback
$per_page = get_option('pagination_results_per_page') ?: 10;  // Fallback to 10 if option not set
$pagination_page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;  // Ensure page is an integer
$pagination_start = ($pagination_page - 1) * $per_page;

// Argument setup for fetching files
$args = [
    'group' => null,
    'pagination' => [
        'page' => $pagination_page,
        'start' => $pagination_start,
        'per_page' => $per_page
    ]
];

// Filter by group if provided
if (!empty($_GET['group'])) {
    $args['group_id'] = intval($_GET['group']);  // Ensure group is an integer
}

// Fetch public files based on arguments
$files_data = get_public_files($args);
// Check if files are availablea
if (isset($files_data['files_ids']) && count($files_data['files_ids']) > 0) {
    // Initialize an array to hold file information
    $files = [];

    // Iterate over file IDs and build file data objects
    foreach ($files_data['files_ids'] as $file_id) {
        $file = new \ProjectSend\Classes\Files($file_id);
        
        // Build file information object
        $files[] = [
            'title' => $file->title,
            'description' => $file->description,
            'type' => $file->mime_type,
            'size' => $file->size_formatted,
            'public_url' => $file->public_url,
        ];
    }

    // Add file information to the response
    $response['data'] = $files;
} else {
    // Handle case where no files are found
    $response['message'] = 'No files found';
}
// Output the response in JSON format
echo json_encode($response);

