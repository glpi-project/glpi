<?php

include ('../inc/includes.php');

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}

require_once(GLPI_ROOT . '/inc/projecttask.class.php');

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * DAO class for handling project task records
 */
class TaskDAO {

    function updateTask($task) {
        global $DB;
        $t = new ProjectTask();
        $t->getFromDB($task->id);

        $t->update(
            [
                'id' => $task->id,
                'plan_start_date'  => $task->start_date,
                'plan_end_date'   => $task->end_date,
                'percent_done' => ($task->progress * 100),
                'name' => (isset($task->text) ? $task->text : $t->fields['name'])
            ]
        );
        return true;
    }

    function deleteTask(&$failed, $taskId) {
        global $DB;
        if ($taskId > 0) {
            foreach ($DB->request('glpi_projecttasks', ['projecttasks_id' => $taskId]) as $record) {
                if (isset($record['id']))
                    if (!$this->deleteTask($failed, $record['id']))
                        $failed[] = $record;
            }
            try {
                $DB->delete(ProjectTask::getTable(), [
                    'id' => $taskId
                ]);
            } 
            catch (Exception $ex) {
                return false;
            }
        }
        return true;
    }
}