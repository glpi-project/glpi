<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['node'])) {

   if ($_SESSION['glpiactiveprofile']['interface']=='helpdesk') {
      $target = "helpdesk.public.php";
   } else {
      $target = "central.php";
   }

   $nodes = array();

   // Get ancestors of current entity
   $ancestors = getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']);

   // Root node
   if ($_POST['node'] == -1) {
      $pos = 0;

      foreach ($_SESSION['glpiactiveprofile']['entities'] as $entity) {
         $ID                = $entity['id'];
         $is_recursive      = $entity['is_recursive'];
         $path['text']      = Dropdown::getDropdownName("glpi_entities", $ID);
         $path['id']        = $ID;
         $path['position']  = $pos;
         $pos++;
         $path['draggable'] = false;
         $path['href']      = $CFG_GLPI["root_doc"]."/front/$target?active_entity=".$ID;
         // Check if node is a leaf or a folder.
         $path['leaf']      = true;
         $path['cls']       = 'file';

         if ($is_recursive) {
            $query2 = "SELECT COUNT(`id`)
                       FROM `glpi_entities`
                       WHERE `entities_id` = '$ID'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2,0,0) >0) {
               $path['leaf']     = false;
               $path['cls']      = 'folder';
               $path['text']    .= "&nbsp;<a title=\"".__s('Show all')."\" href='".
                                           $CFG_GLPI["root_doc"]."/front/".$target."?active_entity=".
                                           $ID."&amp;is_recursive=1'>".
                                   "<img alt=\"".__s('Show all')."\" src='".
                                     $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";
               $path['expanded'] = isset($ancestors[$ID]);
            }
         }
         $nodes[] = $path;
      }

   } else { // standard node
      $query = "SELECT *
                FROM `glpi_entities`
                WHERE `entities_id` = '".$_POST['node']."'
                ORDER BY `name`";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            $pos = 0;

            while ($row = $DB->fetch_assoc($result)) {
               $path['text']      = $row['name'];
               $path['id']        = $row['id'];
               $path['position']  = $pos;
               $pos++;
               $path['draggable'] = false;
               $path['href']      = $CFG_GLPI["root_doc"]."/front/$target?active_entity=".$row['id'];

               // Check if node is a leaf or a folder.
               $query2 = "SELECT COUNT(`id`)
                          FROM `glpi_entities`
                          WHERE `entities_id` = '".$row['id']."'";
               $result2 = $DB->query($query2);

               if ($DB->result($result2,0,0) >0) {
                  $path['leaf']     = false;
                  $path['cls']      = 'folder';
                  $path['text']    .= "&nbsp;<a title=\"".__s('Show all')."\" href='".
                                              $CFG_GLPI["root_doc"]."/front/".$target.
                                              "?active_entity=".$row['id']."&amp;is_recursive=1'>".
                                       "<img alt=\"".__s('Show all')."\" src='".
                                         $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";
                  $path['expanded'] = isset($ancestors[$row['id']]);
               } else {
                  $path['leaf'] = true;
                  $path['cls']  = 'file';
               }
               $nodes[] = $path;
            }
         }
      }
   }
   print json_encode($nodes);
}
?>