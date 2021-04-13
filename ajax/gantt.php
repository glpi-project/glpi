<?php

include ('../inc/includes.php');

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}
 
require_once(GLPI_ROOT . '/inc/gantt/datafactory.class.php');
require_once(GLPI_ROOT . '/inc/gantt/item.class.php');
require_once(GLPI_ROOT . '/inc/gantt/taskdao.class.php');
require_once(GLPI_ROOT . '/inc/gantt/projectdao.class.php');
require_once(GLPI_ROOT . '/inc/gantt/link.class.php');

$id = 0;

if (isset($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
}

if (isset($_REQUEST['getData'])) {
    $itemArray = array();
    $factory = new DataFactory();
    $factory->getItemsForProject($itemArray, $id);
    $links = $factory->getProjectTaskLinks($itemArray);

    usort($itemArray, function($a, $b) { return strlen($a->id) <=> strlen($b->id); });

    $result = (object)[
        'data' => $itemArray,
        'links' => $links
    ];
    echo json_encode($result);
}
else if (isset($_REQUEST["updateTask"])) {
    $result;
    try {
        $updated = false;
        $item = new Item();
        $task = $_POST["task"];
        $item->populateFrom($task);
        $taskDAO = new TaskDAO();
        $updated = $taskDAO->updateTask($item);
        $result = (object)[
            'ok' => $updated
        ];
    }
    catch(Exception $ex) {
        $result = (object)[ 
            'ok' => false,
            'error' => $ex->getMessage() 
        ];
    }
    echo json_encode($result);
}
else if (isset($_REQUEST["deleteTask"])) {
    $result;
    try {
        $failed = array();
        $taskId = $_POST["taskId"];
        $taskDAO = new TaskDAO();
        $taskDAO->deleteTask($failed, $taskId);
        if (count($failed) > 0) 
            throw new Exception("Some tasks may have not been deleted");
        $result = (object)[ 
            'ok' => true
        ];
    }
    catch(Exception $ex) {
        $result = (object)[ 
            'ok' => false,
            'error' => $ex->getMessage() 
        ];
    }
    echo json_encode($result);
}
else if (isset($_REQUEST["updateProject"])) {
    $result;
    try {
        $updated = false;
        $item = new Item();
        $project = $_POST["project"];
        $item->populateFrom($project);
        $projectDAO = new ProjectDAO();
        $updated = $projectDAO->updateProject($item);
        $result = (object)[
            'ok' => $updated
        ];
    } 
    catch(Exception $ex) {
        $result = (object)[ 
            'ok' => false,
            'error' => $ex->getMessage() 
        ];
    }
    echo json_encode($result);
}
else if (isset($_REQUEST["putInTrashbin"])) {
    $result;
    try {
        $projectId = $_POST["projectId"];
        $projectDAO = new ProjectDAO();
        $projectDAO->putInTrashbin($projectId);
        $result = (object)[ 
            'ok' => true
        ];
    }
    catch(Exception $ex) {
        $result = (object)[ 
            'ok' => false,
            'error' => $ex->getMessage() 
        ];
    }
    echo json_encode($result);
}
else if (isset($_REQUEST["addTaskLink"])) {
    $result;
    try {
        $taskLink = new ProjectTaskLink();

        if ($taskLink->checkIfExist($_POST["taskLink"]))
        throw new Exception("Link already exist!");

        $id = $taskLink->add($_POST["taskLink"]);
        $result = (object)[ 
            'ok' => true,
            'id' => $id
        ];
    }
    catch (Exception $ex) {
        $result = (object)[
            'ok' => false,
            'error' => $ex->getMessage()
        ];
    }
    echo json_encode($result);
}
else if (isset($_REQUEST["updateTaskLink"])) {
    $result;
    try {
        $taskLink = new ProjectTaskLink();
        $taskLink->update($_POST["taskLink"]);
        $result = (object)[
            'ok' => true
        ];
    }
    catch (Exception $ex) {
        $result = (object)[
            'ok' => false,
            'error' => $ex->getMessage()
        ];
    }
    echo json_encode($result);
}
else if (isset($_REQUEST["deleteTaskLink"])) {
    $result;
    try {
        $taskLink = new ProjectTaskLink();
        $taskLink->delete($_POST);
        $result = (object)[
            'ok' => true
        ];
    }
    catch (Exception $ex) {
        $result = (object)[
            'ok' => false,
            'error' => $ex->getMessage()
        ];
    }
    echo json_encode($result);
}