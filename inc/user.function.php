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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}




/**  Show items of a user
* @param $ID user ID
*/
function showDeviceUser($ID) {
   global $DB,$CFG_GLPI, $LANG;

   $group_where = "";
   $groups = array();
   $query = "SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
             FROM `glpi_groups_users`
             LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
             WHERE `glpi_groups_users`.`users_id` = '$ID'";
   $result=$DB->query($query);

   if ($DB->numrows($result) >0) {
      $first = true;
      while ($data=$DB->fetch_array($result)) {
         if ($first) {
            $first = false;
         } else {
            $group_where .= " OR ";
         }
         $group_where .= " `groups_id` = '".$data["groups_id"]."' ";
         $groups[$data["groups_id"]] = $data["name"];
      }
   }

   echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]."</th>".
         "<th>".$LANG['entity'][0]."</th>".
         "<th>".$LANG['common'][16]."</th>".
         "<th>".$LANG['common'][19]."</th>".
         "<th>".$LANG['common'][20]."</th><th>&nbsp;</th></tr>";

   foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
      if (!class_exists($itemtype)) {
         continue;
      }
      $item = new $itemtype();
      if ($item->canView()) {
         $itemtable=getTableForItemType($itemtype);
         $query = "SELECT *
                   FROM `$itemtable`
                   WHERE `users_id` = '$ID'";

         if ($item->maybeTemplate()) {
            $query .= " AND `is_template` = '0' ";
         }
         if ($item->maybeDeleted()) {
            $query .= " AND `is_deleted` = '0' ";
         }
         $result = $DB->query($query);

         $type_name= $item->getTypeName();

         if ($DB->numrows($result) >0) {
            while ($data = $DB->fetch_array($result)) {
               $cansee = $item->can($data["id"],"r");
               $link = $data["name"];
               if ($cansee) {
                  $link_item=getItemTypeFormURL($itemtype);
                  $link = "<a href='".$link_item."?id=".
                           $data["id"]."'>".$link.
                           (($_SESSION["glpiis_ids_visible"]||empty($link))?" (".$data["id"].")":"")
                           ."</a>";
               }
               $linktype = "";
               if ($data["users_id"] == $ID) {
                  $linktype = $LANG['common'][34];
               }
               echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>".
                     "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data["entities_id"]).
                     "</td><td class='center'>$link</td>".
                     "<td class='center'>";
               if (isset($data["serial"]) && !empty($data["serial"])) {
                  echo $data["serial"];
               } else {
                  echo '&nbsp;';
               }
               echo "</td><td class='center'>";
               if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                  echo $data["otherserial"];
               } else {
                  echo '&nbsp;';
               }
               echo "<td class='center'>$linktype</td></tr>";
            }
         }
      }
   }
   echo "</table></div><br>";

   if (!empty($group_where)) {
      echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]."</th>".
            "<th>".$LANG['entity'][0]."</th>".
            "<th>".$LANG['common'][16]."</th>".
            "<th>".$LANG['common'][19]."</th>".
            "<th>".$LANG['common'][20]."</th><th>&nbsp;</th></tr>";

      foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         if ($item->canView()) {

            $itemtable=getTableForItemType($itemtype);
            $query = "SELECT *
                     FROM `$itemtable`
                     WHERE $group_where";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
               $query .= " AND `is_deleted` = '0' ";
            }
            $result = $DB->query($query);

            $type_name= $item->getTypeName();


            if ($DB->numrows($result) >0) {
               while ($data = $DB->fetch_array($result)) {
                  $cansee = $item->can($data["id"],"r");
                  $link = $data["name"];
                  if ($cansee) {
                     $link_item=getItemTypeFormURL($itemtype);
                     $link = "<a href='".$link_item."?id=".
                              $data["id"]."'>".$link.
                              (($_SESSION["glpiis_ids_visible"] || empty($link))?" (".$data["id"].")":"").
                              "</a>";
                  }
                  $linktype = "";
                  if (isset($groups[$data["groups_id"]])) {
                     $linktype = $LANG['common'][35]." ".$groups[$data["groups_id"]];
                  }
                  echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>".
                        "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data["entities_id"]).
                        "</td><td class='center'>$link</td>".
                        "<td class='center'>";
                  if (isset($data["serial"]) && !empty($data["serial"])) {
                     echo $data["serial"];
                  } else {
                     echo '&nbsp;';
                  }
                  echo "</td><td class='center'>";
                  if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                     echo $data["otherserial"];
                  } else {
                     echo '&nbsp;';
                  }
                  echo "</td><td class='center'>$linktype</td></tr>";
               }
            }
         }
      }
      echo "</table></div><br>";
   }
}











/**
 * Get language in GLPI associated with the value coming from LDAP
 * Value can be, for example : English, en_EN or en
 * @param $lang : the value coming from LDAP
 * @return the locale's php page in GLPI or '' is no language associated with the value
 */
function getUserLanguage($lang) {
   global $CFG_GLPI;

   /// TODO Add fields in config array to be more efficient / use stricmp instead of == for strings

   foreach ($CFG_GLPI["languages"] as $ID => $language) {
      if ($lang == $ID
          || $lang == $language[0]
          || $lang == $language[2]
          || $lang == $language[3]) {
         return $ID;
      }
   }
   return "";
}



?>