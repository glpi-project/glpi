<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

$AJAX_INCLUDE = 1;

include("../inc/includes.php");

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

/** @global array $CFG_GLPI */

$base_path = $CFG_GLPI['root_doc'] . "/front/central.php";
if (Session::getCurrentInterface() == 'helpdesk') {
    $base_path = $CFG_GLPI["root_doc"] . "/front/helpdesk.public.php";
}

$ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);

$ckey    = 'entity_selector';
$subckey = sha1($_SESSION['glpiactiveentities_string']);
$all_entitiestree = $GLPI_CACHE->get($ckey, []);
if (array_key_exists($subckey, $all_entitiestree)) {
    echo json_encode($all_entitiestree[$subckey]);
    exit;
}

$entitiestree = [];
foreach ($_SESSION['glpiactiveprofile']['entities'] as $default_entity) {
    $default_entity_id = $default_entity['id'];

    $entitytree  = $default_entity['is_recursive'] ? getTreeForItem('glpi_entities', $default_entity_id) : [$default_entity['id'] => $default_entity];
    $adapt_tree = static function (&$entities) use (&$adapt_tree, $base_path, $ancestors) {
        foreach ($entities as $entities_id => &$entity) {
            $entity['key']   = $entities_id;

            $title = "<a href='$base_path?active_entity={$entities_id}'>{$entity['name']}</a>";
            $entity['title'] = $title;
            unset($entity['name']);

            if (isset($ancestors[$entities_id])) {
                $entity['expanded'] = 'true';
            }

            if ($entities_id == $_SESSION['glpiactive_entity_name']) {
                $entity['selected'] = 'true';
            }

            if (isset($entity['tree']) && count($entity['tree']) > 0) {
                $entity['folder'] = true;

                $entity['title'] .= "<a href='$base_path?active_entity={$entities_id}&is_recursive=1'>
               <i class='fas fa-angle-double-down ms-1' data-bs-toggle='tooltip' data-bs-placement='right' title='" . __('+ sub-entities') . "'></i>
            </a>";

                $children = $adapt_tree($entity['tree']);
                $entity['children'] = array_values($children);
            }

            unset($entity['tree']);
        }

        return $entities;
    };
    $adapt_tree($entitytree);

    $entitiestree = array_merge($entitiestree, $entitytree);
}

$all_entitiestree[$subckey] = $entitiestree;
$GLPI_CACHE->set($ckey, $all_entitiestree);

echo json_encode($entitiestree);
exit;
