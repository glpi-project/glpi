<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Unglobalize an item : duplicate item and connections
 *
 * @param $itemtype item type
 * @param $ID item ID
 */
function unglobalizeDevice($itemtype,$ID) {
   global $DB;

   $ci=new CommonItem();
   // Update item to unit management :
   $ci->getFromDB($itemtype,$ID);
   if ($ci->getField('is_global')) {
      $input=array("id"=>$ID,"is_global"=>"0");
      $ci->obj->update($input);

      // Get connect_wire for this connection
      $query = "SELECT `glpi_computers_items`.`id` AS connectID
                FROM `glpi_computers_items`
                WHERE `glpi_computers_items`.`items_id` = '$ID'
                      AND `glpi_computers_items`.`itemtype` = '$itemtype'";
      $result=$DB->query($query);
      if (($nb=$DB->numrows($result))>1) {
         for ($i=1;$i<$nb;$i++) {
            // Get ID of the computer
            if ($data=$DB->fetch_array($result)) {
               // Add new Item
               unset($ci->obj->fields['id']);
               if ($newID=$ci->obj->add(array("id"=>$ID))) {
                  // Update Connection
                  $query2="UPDATE `glpi_computers_items`
                           SET `items_id`='$newID'
                           WHERE `id`='".$data["connectID"]."'";
                  $DB->query($query2);
               }
            }
         }
      }
   }
}

?>