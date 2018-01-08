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

include ('../inc/includes.php');
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_REQUEST['id'])) {
   throw new \RuntimeException('Required argument missing!');
}

$id = $_REQUEST['id'];
$current = isset($_REQUEST['current']) ? $_REQUEST['current'] : null;
$rand = isset($_REQUEST['rand']) ? $_REQUEST['rand'] : mt_rand();

$room = new DCRoom();
if ($room->getFromDB($id)) {
   $used = $room->getFilled($current);
   $positions = $room->getAllPositions();

   Dropdown::showFromArray(
      'position',
      $positions, [
         'value'                 => $current,
         'rand'                  => $rand,
         'display_emptychoice'   => true,
         'used'                  => $used
      ]
   );
} else {
   echo __('No room found or selected');
}
