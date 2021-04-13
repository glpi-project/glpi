<?php

include ('../inc/includes.php');

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}

require_once(GLPI_ROOT . '/inc/projecttasklink.class.php');

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class LinkDAO {

    public function getLinksForItemIDs($ids) {
        $links = [];
        $tasklink = new ProjectTaskLink();
        
        $ids = implode(',', $ids);
        $iterator = $tasklink->getFromDBForItemIDs($ids);
        while ($data = $iterator->next()) {
            array_push($links, $this->populateFromDB($data));
        }

        return $links;
    }

    function populateFromDB($data) {
        $link = new Link();
        $link->id = $data["id"];
        $link->source = $data["source_uuid"];
        $link->target = $data["target_uuid"];
        $link->type = $data["type"];
        $link->lag = $data["lag"];
        $link->lead = $data["lead"];
        return $link;
    }
}