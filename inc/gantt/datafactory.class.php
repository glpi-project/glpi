<?php

include ('../inc/includes.php');

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}
 
require_once(GLPI_ROOT . '/inc/gantt/item.class.php');
require_once(GLPI_ROOT . '/inc/gantt/linkdao.class.php');

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class DataFactory {
    
    function getItemsForProject(&$itemArray, $id) {
        global $DB;
        $project = new Project();
        if ($id == -1) {
            $iterator = $DB->request([
                'FROM'   => 'glpi_projects',
                'WHERE'  => [
                   'projects_id'           => 0,
                   'show_on_global_gantt'  => 1,
                   'is_template'           => 0,
                   'is_deleted'            => 0
                ] + getEntitiesRestrictCriteria('glpi_projects', '', '', true)
             ]);
             while ($data = $iterator->next()) {
                $this->getItemsForProject($itemArray, $data['id']);
             }
        }
        else if ($project->getFromDB($id)) {
            array_push($itemArray, $this->populateGanttItem($project->fields, "root-project"));
            $this->getProjectTasks($itemArray, $id);
            $this->getSubprojects($itemArray, $id);     // subproject tasks included
        }
    }

    function getProjectTaskLinks($itemArray) {
        $links = [];
        if (isset($itemArray)) {
            
            $ids = [];
            foreach($itemArray as $item) {
                if ($item->type != 'project')
                    $ids[] = $item->linktask_id;
            }
            
            if (count($ids) > 0) {
            $linkDao = new LinkDAO();
            $links = $linkDao->getLinksForItemIDs($ids);
        }
        }
        return $links;
    }

    function getSubprojects(&$itemArray, $projectId) {
        global $DB;
        foreach($DB->request('glpi_projects', ['projects_id' => $projectId, 'is_deleted' => 0]) as $record) {
            array_push($itemArray, $this->populateGanttItem($record, "project"));
            $this->getSubprojects($itemArray, $record['id']);
            $this->getProjectTasks($itemArray, $record['id']);
        }
    }

    function getProjectTasks(&$itemArray, $projectId) {
        $taskRecords[] = ProjectTask::getAllForProject($projectId);
        foreach($taskRecords[0] as $record) {
            array_push($itemArray, $this->populateGanttItem($record, "task"));
        }
    }

    function populateGanttItem($record, $type) {
        if (isset($record['is_milestone']) && $record['is_milestone'] > 0) 
            $type = 'milestone';

        $parentTaskUid = "";
        if (($type == 'task' || $type == 'milestone') && $record["projecttasks_id"] > 0) {
            $parentTask = new ProjectTask();
            $parentTask->getFromDB($record["projecttasks_id"]);
            $parentTaskUid = $parentTask->fields["uuid"];
        }

        $item = new Item();
        $item->id = ($type == "project" || $type == "root-project") ? $record['id'] : $record['uuid'];
        $item->type = ($type == "root-project") ? "project" : $type;
        $item->parent = ($type == "root-project") ? 0 : (($type == "project") ? $record['projects_id'] : ($record["projecttasks_id"] > 0 ? $parentTaskUid : $record['projects_id']));
        $item->linksource_id = ($item->type != "project") ? $record["projecttasks_id"] : 0;
        $item->linktask_id = ($item->type != "project") ? $record["id"] : 0;    // parent task id to search for by child->linksource_id
        $item->start_date = $record['plan_start_date'];
        $item->end_date = $record['plan_end_date'];
        $item->text = $record['name'];
        $item->note = isset($record['code']) ? $record['code'] : "";
        $item->progress = $record['percent_done'] / 100;

        return $item;
    }
}
