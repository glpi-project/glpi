<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

Html::header_nocache();

if (
    !isset($_REQUEST['itemtype'])
    && !is_subclass_of($_REQUEST['itemtype'], 'CommonDBTM')
) {
    throw new RuntimeException('Required argument missing or incorrect!');
}

$item = getItemForItemtype($_REQUEST['itemtype']);
$item->getFromDB((int) $_REQUEST['items_id']);

$locations_id = $item->fields['locations_id'] ?? 0;

$entities_id = $item->fields['entities_id'] ?? $_SESSION['glpiactive_entity'];

$is_recursive = $_SESSION['glpiactive_entity_recursive'];
if (isset($_REQUEST['is_recursive'])) {
    $is_recursive = (bool) $_REQUEST['is_recursive'];
}

Location::dropdown([
    'value' => $locations_id,
    'entity' => $entities_id,
    'entity_sons' => $is_recursive,
]);
