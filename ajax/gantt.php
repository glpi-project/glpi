<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$id = 0;

if (isset($_REQUEST['id'])) {
   $id = $_REQUEST['id'];
}

if (isset($_REQUEST['getData'])) {
   $itemArray = [];
   $factory = new Glpi\Gantt\DataFactory();
   $factory->getItemsForProject($itemArray, $id);
   $links = $factory->getProjectTaskLinks($itemArray);

   usort($itemArray, function($a, $b) {
      return strlen($a->id) <=> strlen($b->id);
   });

   $result = [
      'data' => $itemArray,
      'links' => $links
   ];
   echo json_encode($result);

} else if (isset($_REQUEST["addTask"])) {
   $result;
   try {
      $item = new Glpi\Gantt\Item();
      $task = $_POST["task"];
      $item->populateFrom($task);
      $taskDAO = new Glpi\Gantt\TaskDAO();
      $newTask = $taskDAO->addTask($item);
      $factory = new Glpi\Gantt\DataFactory();
      $ganttItem = $factory->populateGanttItem($newTask->fields, "task");

      $result = [
         'ok' => true,
         'item' => $ganttItem
      ];

   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);

} else if (isset($_REQUEST["updateTask"])) {
   $result;
   try {
      $updated = false;
      $item = new Glpi\Gantt\Item();
      $task = $_POST["task"];
      $item->populateFrom($task);
      $taskDAO = new Glpi\Gantt\TaskDAO();
      $updated = $taskDAO->updateTask($item);
      $result = [
         'ok' => $updated
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);

} else if (isset($_REQUEST["changeItemParent"])) {
   $result;
   try {
      $p_item = $_POST["item"];
      $p_target = $_POST["target"];

      if ($p_item["type"] == "project" && $p_target["type"] != "project") {
         throw new \Exception(__("Target item must be of project type"));
      }

      $item = new Glpi\Gantt\Item();
      $item->populateFrom($p_item);
      $target = new Glpi\Gantt\Item();
      $target->populateFrom($p_target);

      $item->parent = $target->id;
      $dao;
      if ($p_item["type"] == "project") {
         $dao = new \Glpi\Gantt\ProjectDAO();
      } else {
         $dao = new \Glpi\Gantt\TaskDAO();
      }
      $dao->updateParent($item);

      $result = [
         'ok' => true
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);
} else if (isset($_REQUEST["makeRootProject"])) {
   $result;
   try {
      $p_item = $_POST["item"];

      // double check for safety..
      if ($p_item["type"] != "project") {
         throw new \Exception(__("Item must be of project type"));
      }

      $item = new Glpi\Gantt\Item();
      $item->populateFrom($p_item);
      $dao = new \Glpi\Gantt\ProjectDAO();
      $dao->updateParent($item);

      $result = [
         'ok' => true
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);
} else if (isset($_REQUEST["addProject"])) {
   $result;
   try {
      $item = new Glpi\Gantt\Item();
      $project = $_POST["project"];
      $item->populateFrom($project);
      $dao = new Glpi\Gantt\ProjectDAO();
      $newProj = $dao->addProject($item);
      $factory = new Glpi\Gantt\DataFactory();
      $ganttItem = $factory->populateGanttItem($newProj->fields, "project");

      $result = [
         'ok' => true,
         'item' => $ganttItem
      ];

   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);

} else if (isset($_REQUEST["updateProject"])) {
   $result;
   try {
      $updated = false;
      $item = new Glpi\Gantt\Item();
      $project = $_POST["project"];
      $item->populateFrom($project);
      $projectDAO = new Glpi\Gantt\ProjectDAO();
      $updated = $projectDAO->updateProject($item);
      $result = [
         'ok' => $updated
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);

} else if (isset($_REQUEST["addTaskLink"])) {
   $result;
   try {
      $taskLink = new \ProjectTaskLink();

      if ($taskLink->checkIfExist($_POST["taskLink"])) {
         throw new \Exception(__("Link already exist!"));
      }

      $id = $taskLink->add($_POST["taskLink"]);
      $result = [
         'ok' => true,
         'id' => $id
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);

} else if (isset($_REQUEST["updateTaskLink"])) {
   $result;
   try {
      $taskLink = new \ProjectTaskLink();
      $taskLink->update($_POST["taskLink"]);
      $result = [
         'ok' => true
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);

} else if (isset($_REQUEST["deleteTaskLink"])) {
   $result;
   try {
      $taskLink = new \ProjectTaskLink();
      $taskLink->delete($_POST);
      $result = [
         'ok' => true
      ];
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);
} else if (isset($_REQUEST["openEditForm"])) {
   $result = [];
   $result["ok"] = true;
   try {
      if ($_POST["item"]["type"] == "project") {
         $result["url"] = $CFG_GLPI["root_doc"]."/front/project.form.php?id=".$_POST["item"]["id"]."&forcetab=Project";
      } else {
         $result["url"] = $CFG_GLPI["root_doc"]."/front/projecttask.form.php?id=".$_POST["item"]["linktask_id"]."&forcetab=ProjectTask";
      }
   } catch (\Exception $ex) {
      $result = [
         'ok' => false,
         'error' => $ex->getMessage()
      ];
   }
   echo json_encode($result);
}
