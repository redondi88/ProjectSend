<?php
namespace ProjectSend\Classes;

class Plugins {
    private $dbh;

    function __construct()
    {
        global $dbh;
        $this->dbh = $dbh;
    }
    public function getPlugin($option = null)
    {
        if (empty($option)) {
            return null;
        }

        if (empty($this->dbh)) {
            return null;
        }

        try {
            $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_PLUGINS . " WHERE id = :option");
            $statement->bindParam(':option', $option);
            $statement->execute();
            $results = $statement->fetch();

            $value = $results['value'];

            if ((!empty($value))) {
                return $value;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
