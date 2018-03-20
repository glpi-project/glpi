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

$AJAX_INCLUDE = 1;

include ("../inc/includes.php");

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_GET['node'])) {
   $nodes = [];

   // Get ancestors of current entity
   $ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);

   // Root node
   if ($_GET['node'] == -1) {
      $pos = 0;

      foreach ($_SESSION['glpiactiveprofile']['entities'] as $entity) {
         $ID                           = $entity['id'];
         $is_recursive                 = $entity['is_recursive'];

         $path = [
            // append r for root nodes, id are uniques in jstree.
            // so, in case of presence of this id in subtree of other nodes,
            // it will be removed from root nodes
            'id'   => $ID.'r',
            'text' => Dropdown::getDropdownName("glpi_entities", $ID)
         ];

         if ($is_recursive) {
            $path['children'] = true;
            $result2 = $DB->request([
               'FROM'   => 'glpi_entities',
               'COUNT'  => 'cpt',
               'WHERE'  => ['entities_id' => $ID]
            ]);
            $result2 = $result2->next();
            if ($result2['cpt'] > 0) {
               //apend a i tag (one of shortest tags) to have the is_recursive link
               $path['text'].= '<i/>';
               if (isset($ancestors[$ID])) {
                  $path['state']['opened'] = 'true';
               }
            }
         }
         $nodes[] = $path;
      }
   } else { // standard node
      $node_id = $_GET['node'];
      $query   = "SELECT ent.`id`, ent.`name`, ent.`sons_cache`, count(sub_entities.id) as nb_subs
                  FROM `glpi_entities` as ent
                  LEFT JOIN `glpi_entities` as sub_entities
                     ON sub_entities.entities_id = ent.id
                  WHERE ent.`entities_id` = '$node_id'
                  GROUP BY ent.`id`, ent.`name`, ent.`sons_cache`
                  ORDER BY `name`";

      if ($result = $DB->query($query)) {
         while ($row = $DB->fetch_assoc($result)) {
            $path = [
               'id'   => $row['id'],
               'text' => $row['name']
            ];

            if ($row['nb_subs'] > 0) {
               //apend a i tag (one of shortest tags) to have the is_recursive link
               $path['text'].= '<i/>';
               $path['children'] = true;

               if (isset($ancestors[$row['id']])) {
                  $path['state']['opened'] = 'true';
               }
            }
            $nodes[] = $path;
         }
      }
   }
   echo json_encode($nodes);
}
