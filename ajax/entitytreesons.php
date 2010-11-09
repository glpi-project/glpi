<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE = 1;

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkLoginUser();

if (isset($_REQUEST['node'])) {

   if ($_SESSION['glpiactiveprofile']['interface']=='helpdesk') {
      $target = "helpdesk.public.php";
   } else {
      $target = "central.php";
   }

   $nodes = array();

   // Root node
   if ($_REQUEST['node']== -1) {
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
               $path['leaf']  = false;
               $path['cls']   = 'folder';
               $path['text'] .= "&nbsp;<a title=\"".$LANG['buttons'][40]."\" href='".
                                 $CFG_GLPI["root_doc"]."/front/".$target."?active_entity=".$ID.
                                 "&amp;is_recursive=1'><img alt=\"".$LANG['buttons'][40]."\" src='".
                                 $CFG_GLPI["root_doc"]."/pics/entity_all.png'></a>";
            }
         }
         $nodes[] = $path;
      }

   } else { // standard node
      $query = "SELECT *
                FROM `glpi_entities`
                WHERE `entities_id` = '".$_REQUEST['node']."'
                ORDER BY `name`";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            $pos = 0;

            while ($row = $DB->fetch_array($result)) {
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
                  $path['leaf']  = false;
                  $path['cls']   = 'folder';
                  $path['text'] .= "&nbsp;<a title=\"".$LANG['buttons'][40]."\" href='".
                                    $CFG_GLPI["root_doc"]."/front/".$target."?active_entity=".
                                    $row['id']."&amp;is_recursive=1'><img alt=\"".
                                    $LANG['buttons'][40]."\" src='".$CFG_GLPI["root_doc"].
                                    "/pics/entity_all.png'></a>";
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