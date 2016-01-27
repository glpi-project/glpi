<?php
/*
 * @version $Id: HEADER 22656 2014-02-12 16:15:25Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include ('../inc/includes.php');

if (isset($_SERVER['argv'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it    = explode("=",$_SERVER['argv'][$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}
if (isset($_GET['cycle'])) {
   $cycle = $_GET['cycle'];
} else {
   $cycle = 25;
}

if (isset($_GET['only_tasks'])) {
   $only_tasks = explode(',', $_GET['only_tasks']);
} else {
   $only_tasks = array();
}

$crontask = new Crontask();
$query    = "SELECT `id`, `name`
             FROM `glpi_crontasks`
             WHERE `state` = '".Crontask::STATE_RUNNING."'
                   AND unix_timestamp(`lastrun`) + $cycle * `frequency` < unix_timestamp(now())";

//Number of unlocked tasks by the script
$unlocked_tasks = 0;

echo "Date : ".Html::convDateTime($_SESSION['glpi_currenttime'])."\n";
echo "Start unlock script\n";

foreach ($DB->request($query) as $task) {
   if (!empty($only_tasks) && !in_array($task['name'], $only_tasks)) {
      echo $task['name']." is still running but not in the whitelist\n";
      continue;
   }

   $tmp['state'] = Crontask::STATE_WAITING;
   $tmp['id']    = $task['id'];
   if ($crontask->update($tmp)) {
      $unlocked_tasks++;
      $message = "Task '".$task['name']."' unlocked";
      echo $message."\n";
      Event::log($task['id'], 'Crontask', 5, 'Configuration', $message);
   }
}
echo "Number of unlocked tasks : ".$unlocked_tasks."\n";