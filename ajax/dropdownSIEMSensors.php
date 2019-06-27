<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (strpos($_SERVER['PHP_SELF'], "dropdownSIEMSensors.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRight('event', UPDATE);

global $PLUGIN_HOOKS;

if ($_POST['logger'] > 0) {
   if (!isset($_POST['value'])) {
      $_POST['value'] = 0;
   }

   $values = [];
   $logger = Plugin::getPlugins()[$_POST['logger']];
   if ($logger && isset($PLUGIN_HOOKS['siem_sensors'][$logger])) {
      $sensors = $PLUGIN_HOOKS['siem_sensors'][$logger];
   }
   foreach ($sensors as $sensor_id => $params) {
      $values[$sensor_id] = $params['name'];
   }
   Dropdown::showFromArray($_POST['myname'], $values, ['display_emptychoice' => true]);
}
