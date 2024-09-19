<?php

function upgrade_2024091001()
{
    if ( !table_exists( TABLE_PLUGINS ) ) {
        global $dbh;
        $query = "
        CREATE TABLE IF NOT EXISTS `".TABLE_PLUGINS."` (
            `id` int(10) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) COLLATE utf8_general_ci NOT NULL,
            `path` text COLLATE utf8_general_ci NULL,
            `description` varchar(250) COLLATE utf8_general_ci  NULL,
            `version` varchar(50) COLLATE utf8_general_ci NOT NULL DEFAULT 1,
            `enabled` BOOLEAN NOT NULL DEFAULT FALSE,
            `settings` JSON NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";
        $statement = $dbh->prepare($query);
        $statement->execute();
    }
}
