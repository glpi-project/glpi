<?php

include ('../inc/includes.php');

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}

require_once(GLPI_ROOT . '/inc/project.class.php');

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * DAO class for handling project records
 */
class ProjectDAO {

    function updateProject($project) {
        global $DB;
        $p = new Project();
        $p->getFromDB($project->id);
        $p->update(
            [
                'id' => $project->id,
                'percent_done' => ($project->progress * 100),
                'name' => $project->text                
            ]
        );
        return true;
    }

    function putInTrashbin($projectId) {
        global $DB;
        if ($projectId > 0) {
            $p = new Project();
            $p->getFromDB($projectId);
            $p->update(
                [
                    'id' => $projectId,
                    'is_deleted' => 1
                ]
            );    
        }
        return true;
    }
}