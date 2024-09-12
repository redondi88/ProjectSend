<?php
$allowed_levels = array(9);
require_once 'bootstrap.php';

/**
 * @var PDO $dbh
 */

log_in_required($allowed_levels);

/**
 * Get the list of currently installed plugins from the database, including their versions and paths.
 * @return array
 */
function getCurrentPlugins()
{
    global $dbh;
    $cq = "SELECT name, version, path FROM " . TABLE_PLUGINS;
    $sql = $dbh->prepare($cq);
    $sql->execute([]);
    $sql->setFetchMode(PDO::FETCH_ASSOC);

    $response = [];
    while ($data = $sql->fetch()) {
        $response[$data['name']] = [
            'version' => $data['version'],
            'path' => $data['path'],
        ];
    }

    return $response;
}

/**
 * Simulate the installation of the plugin or update it if needed.
 * 
 * @param array $params The plugin parameters like name, version, and path.
 */
function doInstall($params = [])
{
    global $dbh;

    // For demonstration, print the plugin being installed
    echo "Installing/Updating plugin: " . $params['name'] . " (Version: " . $params['version'] . ", Path: " . $params['path'] . ")\n";

    // Insert or update plugin details into the database
    $cq = "INSERT INTO " . TABLE_PLUGINS . " (name, path, description, version, enabled) 
           VALUES (:name, :path, :description, :version, 0)  /* Assuming enabled is a BOOLEAN or TINYINT column */
           ON DUPLICATE KEY UPDATE version = :version_update, path = :path_update, description = :description_update";

    $sql = $dbh->prepare($cq);

    // Execute the query and ensure the parameter placeholders are correctly matched
    $sql->execute([
        ':name' => $params['name'],
        ':path' => $params['path'],
        ':description' => $params['description'],
        ':version' => $params['version'],
        // Bind the parameters again for the update clause with different names
        ':version_update' => $params['version'],
        ':path_update' => $params['path'],
        ':description_update' => $params['description']
    ]);
}

/**
 * Search for plugin folders, find the install.php files, and install or update plugins that are not already installed or have a newer version.
 *
 * @param string $base_dir The base directory to search for plugins.
 * @param string $file_to_execute The PHP file that contains the installation logic.
 */
function execute_php_in_folders($base_dir, $file_to_execute = 'install.php')
{
    // Ensure the base directory exists
    if (!is_dir($base_dir)) {
        throw new Exception("The base directory does not exist.");
    }

    // Recursively search through folders
    $directory_iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    // Get the list of currently installed plugins with their versions and paths
    $currentPlugins = getCurrentPlugins();

    // Loop through each folder and file
    foreach ($directory_iterator as $file) {
        // Check if the current file is the target PHP file
        if ($file->isFile() && basename($file) === $file_to_execute) {
            // Execute the PHP file and get the plugin details
            try {
                // Include the install.php file (each install.php should return plugin details)
                $pluginDetails = include $file;

                // Ensure the pluginDetails array has 'name', 'version', and 'path'
                if (!isset($pluginDetails['name']) || !isset($pluginDetails['version'])) {
                    throw new Exception("Invalid plugin details in {$file}. 'name' and 'version' are required.");
                }

                // Add the plugin path to the details
                $pluginDetails['path'] = dirname($file);  // Store the plugin's directory path

                // Check if the plugin is already installed
                if (isset($currentPlugins[$pluginDetails['name']])) {
                    // Compare the version and path
                    $installed_version = $currentPlugins[$pluginDetails['name']]['version'];
                    $installed_path = $currentPlugins[$pluginDetails['name']]['path'];

                    if (version_compare($pluginDetails['version'], $installed_version, '>')) {
                        // If the new version is higher, update the plugin
                        echo "Updating plugin {$pluginDetails['name']} from version {$installed_version} to {$pluginDetails['version']}...\n";
                        doInstall($pluginDetails);
                    } elseif ($pluginDetails['path'] !== $installed_path) {
                        // If the path has changed, update the path
                        echo "Updating path of plugin {$pluginDetails['name']} from {$installed_path} to {$pluginDetails['path']}...\n";
                        doInstall($pluginDetails);
                    } else {
                        // If the plugin is already installed and up-to-date
                        echo "Plugin {$pluginDetails['name']} is already installed and up-to-date. Skipping...\n";
                    }
                } else {
                    // Install the plugin if it is not installed
                    echo "Installing plugin {$pluginDetails['name']}...\n";
                    doInstall($pluginDetails);
                }
            } catch (Exception $e) {
                // Handle exceptions that may arise during execution
                echo "Error executing {$file}: " . $e->getMessage() . "\n";
            }
        }
    }
}

try {
    $base_dir = './plugins/';
    execute_php_in_folders($base_dir);
    // header("Location: plugins.php"); 
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
