<?php
/*
 * @version $Id$
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

/** @file
* @brief
*/

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));


define('DO_NOT_CHECK_HTTP_REFERER', 1);
include ('../inc/includes.php');

if (!is_writable(GLPI_LOCK_DIR)) {
   //TRANS: %s is a directory
   echo "\t".sprintf(__('ERROR: %s is not writable')."\n", GLPI_LOCK_DIR);
   echo "\t".__('run script as apache user')."\n";
   exit (1);
}

if (!isCommandLine()) {
   //The advantage of using background-image is that cron is called in a separate
   //request and thus does not slow down output of the main page as it would if called
   //from there.
   $image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000".
                       "018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
   header("Content-Type: image/gif");
   header("Content-Length: ".strlen($image));
   header("Cache-Control: no-cache,no-store");
   header("Pragma: no-cache");
   header("Connection: close");
   echo $image;
   flush();

   CronTask::launch(CronTask::MODE_INTERNAL);

} else if (isset($_SERVER['argc']) && ($_SERVER['argc'] > 1)) {
   // Parse command line options

   $mode = CronTask::MODE_EXTERNAL; // when taskname given, will allow --force
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      if ($_SERVER['argv'][$i] == '--force') {
         $mode = -CronTask::MODE_EXTERNAL;
      } else if (is_numeric($_SERVER['argv'][$i])) {
         // Number of tasks
         CronTask::launch(CronTask::MODE_EXTERNAL, intval($_SERVER['argv'][$i]));
         // Only check first parameter when numeric is passed
         break;
      } else {
         // Task name
         CronTask::launch($mode, $CFG_GLPI['cron_limit'], $_SERVER['argv'][$i]);
      }
   }

} else {
   // Default from configuration
   CronTask::launch(CronTask::MODE_EXTERNAL, $CFG_GLPI['cron_limit']);
}
?>
