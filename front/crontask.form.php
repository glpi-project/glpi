<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file: Form to edit Cron Task
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");

$crontask = new CronTask();

if (isset($_POST["update"])) {
   checkRight('config','w');
   $crontask->update($_POST);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET['resetdate']) && isset($_GET["id"])) {
   checkRight('config','w');
   if ($crontask->getFromDB($_GET["id"])) {
       $crontask->resetDate();
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET['resetstate']) && isset($_GET["id"])) {
   checkRight('config','w');
   if ($crontask->getFromDB($_GET["id"])) {
       $crontask->resetState();
   }
   glpi_header($_SERVER['HTTP_REFERER']);

}else {
   if (!isset($_GET["id"]) || empty($_GET["id"])) {
      exit();
   }
   commonHeader($LANG['crontask'][0],$_SERVER['PHP_SELF'],"config","crontask");
   $crontask->showForm($_GET["id"]);
   commonFooter();
}

?>
