<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
* @since version 0.84
*/

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

try {
   $ma = new MassiveAction($_POST, $_GET, 'initial');
} catch (Exception $e) {

   echo "<div class='center'><img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='".
                              __s('Warning')."'><br><br>";
   echo "<span class='b'>".$e->getMessage()."</span><br>";
   echo "</div>";
   exit();

}

echo "<div width='90%' class='center'><br>";
Html::openMassiveActionsForm();
$params = array('action' => '__VALUE__');
$input  = $ma->getInput();
foreach ($input as $key => $val) {
   $params[$key] = $val;
}

$actions = $params['actions'];

if (count($actions)) {
   if (isset($params['hidden']) && is_array($params['hidden'])) {
      foreach ($params['hidden'] as $key => $val) {
         echo Html::hidden($key, array('value' => $val));
      }
   }
   _e('Action');
   echo "&nbsp;";

   $actions = array('-1' => Dropdown::EMPTY_VALUE) + $actions;
   $rand    = Dropdown::showFromArray('massiveaction', $actions);

   echo "<br><br>";

   Ajax::updateItemOnSelectEvent("dropdown_massiveaction$rand", "show_massiveaction$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",
                                 $params);

   echo "<span id='show_massiveaction$rand'>&nbsp;</span>\n";
}

// Force 'checkbox-zero-on-empty', because some massive actions can use checkboxes
$CFG_GLPI['checkbox-zero-on-empty'] = true;
Html::closeForm();
echo "</div>";
?>
