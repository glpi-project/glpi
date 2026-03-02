<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use function Safe\json_encode;

global $CFG_GLPI, $GLPI_CACHE;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

//echo json_encode(Entity::getEntitySelectorTree((int) ($_GET['rand'] ?? 0)));

$ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);
$entitiestree = [];
foreach ($_SESSION['glpiactiveprofile']['entities'] as $default_entity) {
    $default_entity_id = $default_entity['id'];
    $entitytree = $default_entity['is_recursive'] ? Entity::getEntityTree($default_entity_id) : [$default_entity['id'] => $default_entity];

    $adapt_tree = static function (&$entities) use (&$adapt_tree) {
        foreach ($entities as $entities_id => &$entity) {
            $entity['key'] = $entities_id;
            $entity['label'] = $entity['name'];
            $entity['tree'] ??= [];
            $entity['children'] = array_values($adapt_tree($entity['tree']));
            unset($entity['name'], $entity['tree']);
        }
        unset($entity);
        return $entities;
    };
    $adapt_tree($entitytree);

    $entitiestree = array_merge($entitiestree, $entitytree);
}
$select_tree = static function (&$entities) use (&$select_tree, $ancestors) {
    foreach ($entities as &$entity) {
        if (isset($ancestors[$entity['key']])) {
            $entity['expanded'] = true;
        }
        if ((int) $entity['key'] === (int) $_SESSION['glpiactive_entity']) {
            $entity['selected'] = true;
        }
        if (isset($entity['children'])) {
            $select_tree($entity['children']);
        }
    }
};
$select_tree($entitiestree);

echo json_encode($entitiestree);
return;
