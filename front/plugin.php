<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", "w");

// Obsolete function provided to detect compatibility issue
function handleObsoleteCall($func) {
   global $LANG;

   $name = NOT_AVAILABLE;
   foreach (debug_backtrace() as $row) {
      if (isset($row['function'])
          && ($row['function']==$func)
          && isset($row['file'])
          && preg_match(':(/|\\\\)plugins(/|\\\\)(.*)(/|\\\\):', $row['file'], $reg)) {
         $name = $reg[3];
         break;
      }
   }
   echo "</table>";
   Html::displayErrorAndDie($LANG['plugins'][10]."&nbsp;: $name<br><br>".$LANG['plugins'][1]);
}

function registerPluginType($name) {
   handleObsoleteCall('registerPluginType');
}
function getLoginUserID() {
   handleObsoleteCall('getLoginUserID');
}
function haveRight() {
   handleObsoleteCall('haveRight');
}

$plugin = new Plugin();

Html::header($LANG['common'][12],$_SERVER['PHP_SELF'],"config","plugins");

if (isset($_GET['action']) && isset($_GET['id'])) {
   if (method_exists($plugin,$_GET['action'])) {
      $plugin->$_GET['action']($_GET['id']);
   } else {
      echo "Action ".$_GET['action']." undefined";
   }
   Html::back();
}

$plugin->listPlugins();

Html::footer();
?>