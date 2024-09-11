<?php
/**
 * Class that handles all the actions and functions that can be applied to
 * clients groups.
 */

namespace ProjectSend\Classes;

use \PDO;
use \ProjectSend\Classes\Validation;
use \ProjectSend\Classes\ActionsLog;

class CustomAsset
{
    private $dbh;
    private $logger;

    public $id;
    public $title;
    public $content;
    public $language;
    public $language_formatted;
    public $location;
    public $position;
    public $enabled;
    public $created_date;

    private $validation_passed = false;  // Ensure this is initialized
    private $validation_errors = [];     // Ensure this is initialized

    // Permissions
    private $allowed_actions_roles;

    public function __construct($asset_id = null)
    {
        global $dbh;  // Using the global database handler

        if (empty($dbh)) {
            throw new \Exception("Database handler is not initialized.");
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;

        $this->allowed_actions_roles = [9];

        if (!empty($asset_id)) {
            $this->get($asset_id);
        }
    }

    /**
     * Set the ID
     */
    public function setId($id)
    {
        $this->id = (int) $id;  // Typecasting for safety
    }
  
    /**
     * Return the ID
     * @return int|false
     */
    public function getId()
    {
        return !empty($this->id) ? $this->id : false;
    }

    /**
     * Set the properties when editing
     */
    public function set($arguments = [])
    {
        $this->title = isset($arguments['title']) ? encode_html($arguments['title']) : null;
        $this->content = isset($arguments['content']) ? $arguments['content'] : null;
        $this->language = isset($arguments['language']) ? $arguments['language'] : null;
        $this->location = isset($arguments['location']) ? $arguments['location'] : null;
        $this->position = isset($arguments['position']) ? $arguments['position'] : null;
        $this->enabled = isset($arguments['enabled']) ? (int) $arguments['enabled'] : 0;
    }

    /**
     * Get existing user data from the database
     * @return bool
     */
    public function get($id)
    {
        $this->id = (int) $id;

        $stmt = $this->dbh->prepare("SELECT * FROM " . TABLE_CUSTOM_ASSETS . " WHERE id=:id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() == 0) {
            return false;
        }

        $row = $stmt->fetch();  // Fetch the row once, no need to use while loop for a single row
        if ($row) {
            $this->title = html_output($row['title']);
            $this->content = htmlentities_allowed_code_editor($row['content']);
            $this->language = html_output($row['language']);
            $this->location = html_output($row['location']);
            $this->position = html_output($row['position']);
            $this->enabled = (int) $row['enabled'];
            $this->created_date = html_output($row['timestamp']);
            $this->language_formatted = format_asset_language_name($this->language);
            return true;
        }

        return false;
    }

    /**
     * Return the current properties
     */
    public function getProperties()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => htmlentities_allowed_code_editor($this->content),
            'language' => $this->language,
            'language_formatted' => format_asset_language_name($this->language),
            'location' => $this->location,
            'position' => $this->position,
            'enabled' => $this->enabled,
            'created_date' => $this->created_date,
        ];
    }

    /**
     * Is asset enabled?
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled === 1;
    }

    /**
     * Validate the information from the form.
     */
    public function validate()
    {
        global $json_strings;

        $validation = new Validation;
        $validation->validate_items([
            'title' => [
                'value' => $this->title,
                'required' => ['error' => $json_strings['validation']['no_title']],
            ],
            'language' => [
                'value' => $this->language,
                'in_enum' => [
                    'error' => __('Language is not valid', 'cftp_admin'),
                    'valid_values' => array_keys(get_asset_languages()),
                ],
            ],
            'location' => [
                'value' => $this->location,
                'in_enum' => [
                    'error' => __('Location is not valid', 'cftp_admin'),
                    'valid_values' => array_keys(get_asset_locations()),
                ],
            ],
            'position' => [
                'value' => $this->position,
                'in_enum' => [
                    'error' => __('Position is not valid', 'cftp_admin'),
                    'valid_values' => array_keys(get_asset_positions()),
                ],
            ],
        ]);

        if ($validation->passed()) {
            $this->validation_passed = true;
            return true;
        } else {
            $this->validation_passed = false;
            $this->validation_errors = $validation->list_errors();
        }

        return false;
    }

    public function validationPassed()
    {
        return $this->validation_passed;
    }

    /**
     * Return the validation errors to the front end
     */
    public function getValidationErrors()
    {
        return !empty($this->validation_errors) ? $this->validation_errors : false;
    }

    /**
     * Create a new asset.
     */
    public function create()
    {
        if (!$this->validate()) {
            return ['query' => 0];
        }

        $sql = $this->dbh->prepare("INSERT INTO " . TABLE_CUSTOM_ASSETS . " (title, content, language, location, position, enabled)"
                                    ." VALUES (:title, :content, :language, :location, :position, :enabled)");
        $sql->bindParam(':title', $this->title);
        $sql->bindParam(':content', $this->content);
        $sql->bindParam(':language', $this->language);
        $sql->bindParam(':location', $this->location);
        $sql->bindParam(':position', $this->position);
        $sql->bindParam(':enabled', $this->enabled, PDO::PARAM_INT);

        if ($sql->execute()) {
            $this->id = $this->dbh->lastInsertId();
            $this->logAction(50);  // Record action 50 for creation

            return ['query' => 1, 'id' => $this->id];
        }

        return ['query' => 0];
    }

    /**
     * Edit an existing asset.
     */
    public function edit()
    {
        if (empty($this->id)) {
            return false;
        }

        if (!$this->validate()) {
            return ['query' => 0];
        }

        $sql = $this->dbh->prepare("UPDATE " . TABLE_CUSTOM_ASSETS . " SET title = :title, content = :content, location = :location, position = :position, enabled = :enabled WHERE id = :id");
        $sql->bindParam(':title', $this->title);
        $sql->bindParam(':content', $this->content);
        $sql->bindParam(':location', $this->location);
        $sql->bindParam(':position', $this->position);
        $sql->bindParam(':enabled', $this->enabled, PDO::PARAM_INT);
        $sql->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($sql->execute()) {
            $this->logAction(51);  // Record action 51 for editing
            return ['query' => 1];
        }

        return ['query' => 0];
    }

    /**
     * Enable the asset
     */
    public function enable()
    {
        return $this->setEnabledStatus(1);
    }

    /**
     * Disable the asset
     */
    public function disable()
    {
        return $this->setEnabledStatus(0);
    }

    private function setEnabledStatus($change_to)
    {
        if (!$this->get($this->id)) {
            return false;
        }

        $log_action_number = ($change_to === 1) ? 53 : 54;  // Log enable/disable action

        if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
            $sql = $this->dbh->prepare('UPDATE ' . TABLE_CUSTOM_ASSETS . ' SET enabled=:enabled_state WHERE id=:id');
            $sql->bindParam(':enabled_state', $change_to, PDO::PARAM_INT);
            $sql->bindParam(':id', $this->id, PDO::PARAM_INT);
            $sql->execute();

            $this->logAction($log_action_number);
            return true;
        }

        return false;
    }

    /**
     * Delete an existing asset.
     */
    public function delete()
    {
        if (empty($this->id)) {
            return false;
        }

        if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
            $sql = $this->dbh->prepare('DELETE FROM ' . TABLE_CUSTOM_ASSETS . ' WHERE id=:id');
            $sql->bindParam(':id', $this->id, PDO::PARAM_INT);
            $sql->execute();

            $this->logAction(52);  // Record action 52 for deletion
            return true;
        }

        return false;
    }

    /**
     * Record the action log.
     */
    private function logAction($number)
    {
        $this->logger->addEntry([
            'action' => $number,
            'owner_id' => CURRENT_USER_ID,
            'details' => json_encode([
                'title' => $this->title,
                'language' => $this->language
            ]),
        ]);
    }
}
