<?php

// Get the request URI
$path = $_SERVER['REQUEST_URI'];

/**
 * Check if the path contains '/plugins/'.
 * 
 * @param string $path
 * @return bool
 */
function isPlugin($path)
{
    return strpos($path, '/plugins/') !== false;
}

/**
 * Check if the plugin corresponding to the given path is enabled.
 * 
 * @param string $path
 * @return bool
 */
function isPluginEnabled($path)
{
    global $dbh;

    // Prepare and execute the SQL query to check for enabled plugins with the specified path
    $sql = "SELECT path FROM " . TABLE_PLUGINS . " WHERE enabled = TRUE";
    $statement = $dbh->prepare($sql);
    $statement->execute();
    
    // Fetch all enabled plugin paths
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Check if the requested path matches any enabled plugin's path
    foreach ($results as $row) {
        if (strpos($path, $row['path']) === false) {
            return true;
        }
    }
    // die();
    return false;
}

// Check if the current request is for a plugin
if (isPlugin($path)) {
    // If the plugin is not enabled, return a 404 error
    if (!isPluginEnabled($path)) {
        exit_with_error_code(404);
    }
}
