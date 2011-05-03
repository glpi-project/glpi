<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file: Search engine from cron tasks
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");

if (isset($_GET['execute'])) {
   if (is_numeric($_GET['execute'])) {
      // Execute button from list.
      $name = CronTask::launch(CronTask::MODE_INTERNAL,intval($_GET['execute']));
   } else {
      // Execute button from Task form (force)
      $name = CronTask::launch(-CronTask::MODE_INTERNAL,1,$_GET['execute']);
   }
   if ($name) {
      addMessageAfterRedirect($LANG['crontask'][40]." : ".$name);
   }
   glpi_header($_SERVER['HTTP_REFERER']);
}
commonHeader($LANG['crontask'][0],$_SERVER['PHP_SELF'],"config","crontask");

$crontask = new CronTask();
if ($crontask->getNeedToRun(CronTask::MODE_INTERNAL)) {
   displayTitle(GLPI_ROOT.'/pics/warning.png', $LANG['crontask'][41],
                $LANG['crontask'][41]."&nbsp;: ".$crontask->fields['name'],
                array($_SERVER['PHP_SELF']."?execute=1" => $LANG['buttons'][57]));
} else {
   displayTitle(GLPI_ROOT.'/pics/ok.png',$LANG['crontask'][43],$LANG['crontask'][43]);
}

Search::show('CronTask');

commonFooter();

?>
