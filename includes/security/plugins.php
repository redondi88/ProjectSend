<?php
// error_reporting(E_ALL);
// Get the request URI
$path = $_SERVER['REQUEST_URI'];

function isPlugin($path)
{
    // Check if '/plugins/' is in the path
    if (strpos($path, '/plugins/') !== false) {
        return true;
    }

    return false;
}
function isPluginEnabled($path)
{
    global $dbh;
    $sql = "SELECT * FROM " . TABLE_PLUGINS . " WHERE enabled = TRUE";
    $statement = $dbh->prepare($sql);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        if (strpos($path, $row["path"]) !== true) {
            return true;
        }
    }
    return false;
}
if (isPlugin($path)) {
    // find plugin and check if enabled 
    if (!isPluginEnabled($path)) {
        exit_with_error_code(404);
    }
}
