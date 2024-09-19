<?php

namespace ProjectSend\Classes;

use \PDO;

class Plugins
{
    private $dbh;

    public $id;
    public $settings;
    public $enabled;
    public $name;
    public $path;
    public $description;
    public $version;

    public $edit_response;

    private $sql_query;
    private $logger;
    private $invalidJson;

    function __construct()
    {
        global $dbh;
        $this->dbh = $dbh;
        $this->invalidJson = false;
        $this->logger = new \ProjectSend\Classes\ActionsLog;
    }

    /**
     * Function to sanitize and validate JSON input.
     */
    private function sanitizeJSON($json)
    {
        // Check if it's a valid JSON string
        $decoded = json_decode($json, true);

        // If the JSON is invalid, return an empty JSON object
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->invalidJson = true;
        }

        // Optionally, you could further sanitize or clean up the data here
        // Re-encode the JSON to ensure it's clean and properly formatted
        return json_encode($decoded, JSON_PRETTY_PRINT);
    }

    public function create()
    {
        $state = array(
            'query' => 0,
        );
    
        // Sanitize the settings JSON before inserting into the database
        $this->settings = $this->sanitizeJSON($this->settings);
    
        $this->sql_query = $this->dbh->prepare(
            "INSERT INTO " . TABLE_PLUGINS . " (name, path, description, version, enabled, settings)
             VALUES (:name, :path, :description, :version, :enabled, :settings)
             ON DUPLICATE KEY UPDATE 
                version = :version_update, 
                path = :path_update, 
                description = :description_update,
                enabled = :enabled_update, 
                settings = :settings_update"
        );
    
        // Bind parameters for INSERT
        $this->sql_query->bindParam(':name', $this->name);
        $this->sql_query->bindParam(':path', $this->path);
        $this->sql_query->bindParam(':description', $this->description);
        $this->sql_query->bindParam(':version', $this->version);
        $this->sql_query->bindParam(':enabled', $this->enabled, PDO::PARAM_INT);
        $this->sql_query->bindParam(':settings', $this->settings);
    
        // Bind parameters for UPDATE (duplicate key case)
        $this->sql_query->bindParam(':version_update', $this->version);
        $this->sql_query->bindParam(':path_update', $this->path);
        $this->sql_query->bindParam(':description_update', $this->description);
        $this->sql_query->bindParam(':enabled_update', $this->enabled, PDO::PARAM_INT);
        $this->sql_query->bindParam(':settings_update', $this->settings);
    
        // Execute the query
        $this->sql_query->execute();
    
        // Get the last inserted ID
        $this->id = $this->dbh->lastInsertId();
        $state['id'] = $this->id;
    
        // Check if the query was successful
        if ($this->sql_query) {
            $state['query'] = 1;
    
            // Log the action
            $record = $this->logAction(56);
        }
    
        return $state;
    }    

    public function getPlugin($id = null)
    {
        if (empty($id)) {
            // ID is required to get details
            return null;
        }

        if (empty($this->dbh)) {
            return null;
        }

        try {
            $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_PLUGINS . " WHERE id = :id");
            $statement->bindParam(':id', $id);
            $statement->execute();
            $results = $statement->fetch();

            if ((!empty($results))) {
                $this->name = $results["name"];
                $this->id = $id;
                $this->version=$results["version"];
                return $results;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    public function set($arguments = [])
    { {
            // Sanitize the settings input when setting the values
            $this->name = (!empty($arguments['name'])) ? $arguments['name'] : $this->name;
            $this->path =  (!empty($arguments['path'])) ? $arguments['path'] : $this->path;
            $this->version = (!empty($arguments['version'])) ? $arguments['version'] : $this->version;
            $this->description = (!empty($arguments['description'])) ? $arguments['description'] : $this->description;
            $this->settings = $this->sanitizeJSON($arguments['settings']);
            $this->enabled = (!empty($arguments['enabled'])) ? (int)$arguments['enabled'] : 0;
        }
    }

    public function edit()
{
    // Check if the plugin ID is set
    if (empty($this->id)) {
        $this->edit_response = ['error' => 'Plugin ID is missing'];
        return $this->edit_response;
    }

    // Validate JSON payload before processing
    if ($this->invalidJson) {
        $this->edit_response = ['error' => 'Invalid JSON payload'];
        return $this->edit_response;
    }

    $state = [
        'query' => 0,
        'message' => 'Update failed'
    ];

    // Sanitize the settings JSON before updating the database
    $this->settings = $this->sanitizeJSON($this->settings);

    // Prepare the SQL query with version update
    $this->sql_query = $this->dbh->prepare(
        "UPDATE " . TABLE_PLUGINS . " 
        SET settings = :settings, enabled = :enabled, version = :version 
        WHERE id = :id"
    );

    // Bind the parameters
    $this->sql_query->bindParam(':settings', $this->settings);
    $this->sql_query->bindParam(':enabled', $this->enabled, PDO::PARAM_INT);
    $this->sql_query->bindParam(':version', $this->version); // Bind the version parameter
    $this->sql_query->bindParam(':id', $this->id, PDO::PARAM_INT);

    // Execute the SQL query and check if it was successful
    if ($this->sql_query->execute()) {
        // Update was successful
        $state['query'] = 1;
        $state['message'] = 'Update successful';
        $this->logAction(55); // Log the update action
    } else {
        // Update failed
        $state['error'] = 'Failed to execute update query';
    }

    // Store the result in the response
    $this->edit_response = $state;

    return $state;
}


    private function logAction($number)
    {
        $this->logger->addEntry([
            'action' => $number,
            'owner_id' => CURRENT_USER_ID,
            'details' => json_encode([
                'name' => $this->name,
                'version' => $this->version
            ]),
        ]);
    }
}
