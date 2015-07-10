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

$AJAX_INCLUDE = 1;

include ("../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_GET['node'])) {

   if ($_SESSION['glpiactiveprofile']['interface']=='helpdesk') {
      $target = "helpdesk.public.php";
   } else {
      $target = "central.php";
   }

   $nodes = array();

   // Get ancestors of current entity
   $ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);

   // Root node
   if ($_GET['node'] == -1) {
      $pos = 0;

      foreach ($_SESSION['glpiactiveprofile']['entities'] as $entity) {
         $path                         = array();
         $ID                           = $entity['id'];
         $is_recursive                 = $entity['is_recursive'];

         $path['data']['title']        = Dropdown::getDropdownName("glpi_entities", $ID);
         $path['attr']['id']           = 'ent'.$ID;
         $path['data']['attr']['href'] = $CFG_GLPI["root_doc"]."/front/$target?active_entity=".$ID;

         if ($is_recursive) {
            $query2 = "SELECT count(*)
                       FROM `glpi_entities`
                       WHERE `entities_id` = '$ID'";
            $result2 = $DB->query($query2);
            if ($DB->result($result2,0,0) > 0) {
               $path['data']['title'] .= "&nbsp;<a title=\"".__s('Show all')."\" href='".
                                                 $CFG_GLPI["root_doc"]."/front/".$target.
                                                 "?active_entity=".$ID."&amp;is_recursive=1'>".
                                         "<img alt=\"".__s('Show all')."\" src='".
                                           $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";
               if (isset($ancestors[$ID])) {
                  $path['state'] = 'open';
               } else {
                  $path['state'] = 'closed';
               }
            }
         }
         $nodes[] = $path;
      }
   } else { // standard node
      $node_id = str_replace('ent','', $_GET['node']);
      $query   = "SELECT *
                  FROM `glpi_entities`
                  WHERE `entities_id` = '$node_id'
                  ORDER BY `name`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($row = $DB->fetch_assoc($result)) {
               $path = array();
               $path['data']['title']        = $row['name'];
               $path['attr']['id']           = 'ent'.$row['id'];
               $path['data']['attr']['href'] = $CFG_GLPI["root_doc"]."/front/$target?active_entity=".
                                                $row['id'];

               $query2 = "SELECT count(*)
                          FROM `glpi_entities`
                          WHERE `entities_id` = '".$row['id']."'";
               $result2 = $DB->query($query2);
               if ($DB->result($result2,0,0) > 0) {
                  $path['data']['title'] .= "&nbsp;<a title=\"".__s('Show all')."\" href='".
                                                    $CFG_GLPI["root_doc"]."/front/".$target.
                                                    "?active_entity=".$row['id']."&amp;is_recursive=1'>".
                                            "<img alt=\"".__s('Show all')."\" src='".
                                              $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";

                  if (isset($ancestors[$row['id']])) {
                     $path['state'] = 'open';
                  } else {
                     $path['state'] = 'closed';
                  }
               }
               $nodes[] = $path;
            }
         }
      }

   }
   echo json_encode($nodes);
}
?>