<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

if (!isset($_REQUEST['itemtype'])
    && !is_subclass_of($_REQUEST['itemtype'], 'CommonDBTM')) {
   throw new \RuntimeException('Required argument missing or incorrect!');
}

$item = new $_REQUEST['itemtype'];
$item->getFromDB((int) $_REQUEST['items_id']);

$locations_id = 0;
if (isset($item->fields['locations_id'])) {
   $locations_id = $item->fields['locations_id'];
}

$entities_id = $_SESSION['glpiactive_entity'];
if (isset($item->fields['entities_id'])) {
   $entities_id = $item->fields['entities_id'];
}

$is_recursive = $_SESSION['glpiactive_entity_recursive'];
if (isset($_REQUEST['is_recursive'])) {
   $is_recursive = (bool) $_REQUEST['is_recursive'];
}

echo Location::dropdown([
   'value'        => $locations_id,
   'entity'       => $entities_id,
   'entity_sons'  => $is_recursive,
]);
