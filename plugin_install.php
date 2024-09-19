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
    $cq = "SELECT name, version, path, id FROM " . TABLE_PLUGINS;
    $sql = $dbh->prepare($cq);
    $sql->execute([]);
    $sql->setFetchMode(PDO::FETCH_ASSOC);

    $response = [];
    while ($data = $sql->fetch()) {
        $response[$data['name']] = [
            'version' => $data['version'],
            'path' => $data['path'],
            'id' => $data['id']
        ];
    }

    return $response;
}

/**
 * Install or update the plugin if needed.
 * 
 * @param array $params The plugin parameters like name, version, and path.
 */
function doInstall($params = [])
{
    $plugin = new \ProjectSend\Classes\Plugins();
    
    $arguments = [
        'name' => $params['name'],
        'path' => $params['path'],
        'version' => $params['version'],
        'description' => $params['description'],
        'settings' => json_encode($params['settings']),
        'enabled' => 0, // Default to disabled; can be changed later
    ];

    // Check if the plugin already exists
    $existingPlugin = getCurrentPlugins();
    $currentPlugin = $existingPlugin[$params['name']];
    if (isset($currentPlugin)) {
        // Plugin exists, update it
        $plugin->getPlugin($currentPlugin['id']);
        $plugin->set($arguments);
        $plugin->edit();

    } else {
        // Plugin does not exist, create a new one
        $plugin->set($arguments);
        $plugin->create();
    }
}

/**
 * Search for plugin folders, find the install.php files, and install or update plugins that are not already installed or have a newer version.
 *
 * @param string $base_dir The base directory to search for plugins.
 * @param string $file_to_execute The PHP file that contains the installation logic.
 */
function execute_php_in_folders($base_dir, $file_to_execute = 'install.php')
{
    global $msg;
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
                        $msg .= "Updating plugin {$pluginDetails['name']} from version {$installed_version} to {$pluginDetails['version']}...<br>";
                        doInstall($pluginDetails);
                    } elseif ($pluginDetails['path'] !== $installed_path) {
                        // If the path has changed, update the path
                        $msg .= "Updating path of plugin {$pluginDetails['name']} from {$installed_path} to {$pluginDetails['path']}...<br>";
                        doInstall($pluginDetails);
                    } else {
                        // If the plugin is already installed and up-to-date
                        $msg .= "Plugin {$pluginDetails['name']} is already installed and up-to-date. Skipping...<br>";
                    }
                } else {
                    // Install the plugin if it is not installed
                    $msg .= "Installing plugin {$pluginDetails['name']}...<br>";
                    doInstall($pluginDetails);
                }
            } catch (Exception $e) {
                // Handle exceptions that may arise during execution
                $msg .= "Error executing {$file}: " . $e->getMessage() . "<br>";
            }
        }
    }
}

try {
    $base_dir = './plugins/';
    execute_php_in_folders($base_dir);
    if (!$msg) {
        $msg = 'No changes made';
    }
    $flash->success(__($msg));
    ps_redirect(BASE_URI . 'plugins.php?');
} catch (Exception $e) {;
    $flash->error(__($msg . $e->getMessage()));
    ps_redirect(BASE_URI . 'plugins.php?');
}
