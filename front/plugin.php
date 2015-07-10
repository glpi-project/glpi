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

include ('../inc/includes.php');

Session::checkRight("config", UPDATE);

/**
 * Obsolete function provided to detect compatibility issue
 *
 * @since version 0.84
**/
function handleObsoleteCall($func) {

   $name = NOT_AVAILABLE;
   foreach (debug_backtrace() as $row) {
      if (isset($row['function'])
          && ($row['function'] == $func)
          && isset($row['file'])
          && preg_match(':(/|\\\\)plugins(/|\\\\)(.*)(/|\\\\):', $row['file'], $reg)) {
         $name = $reg[3];
         break;
      }
   }
   echo "</table>";
   Html::displayErrorAndDie(sprintf(__('The plugin %s is incompatible with this version of GLPI'),
                                    $name).
                            "<br><br>".__('Delete or update it otherwise GLPI will not work correctly.'));
}


/**
 * Obsolete function keep only for compatibility old versions
 *
 * @param $name
**/
function registerPluginType($name) {
   handleObsoleteCall('registerPluginType');
}


/**
 * Obsolete function keep only for compatibility old versions
**/
function getLoginUserID() {
   handleObsoleteCall('getLoginUserID');
}


/**
 * Obsolete function keep only for compatibility old versions
**/
function haveRight() {
   handleObsoleteCall('haveRight');
}


$plugin = new Plugin();

Html::header(__('Setup'), $_SERVER['PHP_SELF'], "config", "plugin");

$plugin->listPlugins();

Html::footer();
?>