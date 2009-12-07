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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// FUNCTIONS links

/**
 * Print the HTML array for device on link
 *
 * Print the HTML array for device on link for link $instID
 *
 *@param $instID array : Link identifier.
 *
 *@return Nothing (display)
 *
 **/
function showLinkDevice($instID) {
   global $DB,$CFG_GLPI, $LANG;

   $link = new Link();
   if ($instID > 0) {
      $link->check($instID,'r');
   } else {
      // Create item
      $link->check(-1,'w');
      $link->getEmpty();
   }

   $canedit=$link->can($instID,'w');
   $canrecu=$link->can($instID,'recursive');

   if (!haveRight("link","r")) {
      return false;
   }
   //$canedit= haveRight("link","w");
   $ci = new CommonItem();
   $query = "SELECT *
             FROM `glpi_links_itemtypes`
             WHERE `links_id`='$instID'
             ORDER BY `itemtype`";
   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = 0;

   echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/link.form.php\">";
   echo "<div class='center'><table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='2'>".$LANG['links'][4]."&nbsp;:</th></tr>";
   echo "<tr><th>".$LANG['common'][17]."</th>";
   echo "<th>&nbsp;</th></tr>";

   while ($i < $number) {
      $ID=$DB->result($result, $i, "id");
      $ci->setType($DB->result($result, $i, "itemtype"));
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".$ci->getType()."</td>";
      echo "<td class='center'>";
      echo "<a href='".$_SERVER['PHP_SELF']."?deletedevice=deletedevice&amp;id=$ID&amp;lID=$instID'>
            <strong>".$LANG['buttons'][6]."</strong></a></td></tr>";
      $i++;
   }
   if ($canedit) {
      echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
      echo "<div class='software-instal'><input type='hidden' name='lID' value='$instID'>";
      Device::dropdownTypes("itemtype",0,$CFG_GLPI["link_types"]);
      echo "&nbsp;&nbsp;<input type='submit' name='adddevice' value=\"".
                         $LANG['buttons'][8]."\" class='submit'>";
      echo "</div></td></tr>";
   }
   echo "</table></div></form>";
}

/**
 * Delete an item type for a link
 *
 * @param $ID integer : glpi_links_itemtypes ID
 */
function deleteLinkDevice($ID) {
   global $DB;

   $query="DELETE
           FROM `glpi_links_itemtypes`
           WHERE `id`= '$ID';";
   $result = $DB->query($query);
}

/**
 * Add an item type to a link
 *
 * @param $itemtype integer : item type
 * @param $lID integer : link ID
 */
function addLinkDevice($itemtype,$lID) {
   global $DB;

   if ($itemtype>0&&$lID>0) {
      $query="INSERT INTO
              `glpi_links_itemtypes` (`itemtype,links_id`)
              VALUES ('$itemtype','$lID');";
      $result = $DB->query($query);
   }
}

/**
 * Show Links for an item
 *
 * @param $itemtype integer : item type
 * @param $ID integer : item ID
 */
function showLinkOnDevice($itemtype,$ID) {
   global $DB,$LANG,$CFG_GLPI;

   $commonitem = new CommonItem;
   $commonitem->getFromDB($itemtype,$ID);

   if (!haveRight("link","r")) {
      return false;
   }

   $query="SELECT `glpi_links`.`id`, `glpi_links`.`link` AS link, `glpi_links`.`name` AS name ,
                  `glpi_links`.`data` AS data
           FROM `glpi_links`
           INNER JOIN `glpi_links_itemtypes` ON `glpi_links`.`id`=`glpi_links_itemtypes`.`links_id`
           WHERE `glpi_links_itemtypes`.`itemtype`='$itemtype' " .
                 getEntitiesRestrictRequest(" AND","glpi_links","entities_id",
                                            $commonitem->obj->fields["entities_id"],true)."
           ORDER BY name";

   $result=$DB->query($query);

   $ci=new CommonItem;
   if ($DB->numrows($result)>0) {
      echo "<div class='center'><table class='tab_cadre'><tr><th>".$LANG['title'][33]."</th></tr>";
      while ($data=$DB->fetch_assoc($result)) {
         $name=$data["name"];
         if (empty($name)) {
            $name=$data["link"];
         }
         $link=$data["link"];
         $file=trim($data["data"]);
         if (empty($file)) {
            $ci->getFromDB($itemtype,$ID);
            if (strstr($link,"[NAME]")) {
               $link=str_replace("[NAME]",$ci->getName(),$link);
            }
            if (strstr($link,"[ID]")) {
               $link=str_replace("[ID]",$ID,$link);
            }
            if (strstr($link,"[LOGIN]")) {
               if (isset($_SESSION["glpiname"])) {
                  $link=str_replace("[LOGIN]",$_SESSION["glpiname"],$link);
               }
            }
            if (strstr($link,"[SERIAL]")) {
               if ($tmp=$ci->getField('serial')) {
                  $link=str_replace("[SERIAL]",$tmp,$link);
               }
            }
            if (strstr($link,"[OTHERSERIAL]")) {
               if ($tmp=$ci->getField('otherserial')) {
                  $link=str_replace("[OTHERSERIAL]",$tmp,$link);
               }
            }
            if (strstr($link,"[LOCATIONID]")) {
               if ($tmp=$ci->getField('locations_id')) {
                  $link=str_replace("[LOCATIONID]",$tmp,$link);
               }
            }
            if (strstr($link,"[LOCATION]")) {
               if ($tmp=$ci->getField('locations_id')) {
                  $link=str_replace("[LOCATION]",getDropdownName("glpi_locations",$tmp),$link);
               }
            }
            if (strstr($link,"[NETWORK]")) {
               if ($tmp=$ci->getField('networks_id')) {
                  $link=str_replace("[NETWORK]",getDropdownName("glpi_networks",$tmp),$link);
               }
            }
            if (strstr($link,"[DOMAIN]")) {
               if ($tmp=$ci->getField('domains_id')) {
                  $link=str_replace("[DOMAIN]",getDropdownName("glpi_domains",$tmp),$link);
               }
            }
            if (strstr($link,"[USER]")) {
               if ($tmp=$ci->getField('users_id')) {
                  $link=str_replace("[USER]",getDropdownName("glpi_users",$tmp),$link);
               }
            }
            if (strstr($link,"[GROUP]")) {
               if ($tmp=$ci->getField('groups_id')) {
                  $link=str_replace("[GROUP]",getDropdownName("glpi_groups",$tmp),$link);
               }
            }
            $ipmac=array();
            $i=0;
            if (strstr($link,"[IP]") || strstr($link,"[MAC]")) {
               $query2 = "SELECT `ip`, `mac`, `logical_number`
                          FROM `glpi_networkports`
                          WHERE `items_id` = '$ID'
                                AND `itemtype` = '$itemtype'
                          ORDER BY `logical_number`";
               $result2=$DB->query($query2);
               if ($DB->numrows($result2)>0) {
                  while ($data2=$DB->fetch_array($result2)) {
                     $ipmac[$i]['ip']=$data2["ip"];
                     $ipmac[$i]['mac']=$data2["mac"];
                     $ipmac[$i]['number']=$data2["logical_number"];
                     $i++;
                  }
               }
            }
            if (strstr($link,"[IP]") || strstr($link,"[MAC]")) {
               // Add IP/MAC internal switch
               if ($itemtype==NETWORKING_TYPE) {
                  $tmplink=$link;
                  $tmplink=str_replace("[IP]",$ci->getField('ip'),$tmplink);
                  $tmplink=str_replace("[MAC]",$ci->getField('mac'),$tmplink);
                  echo "<tr class='tab_bg_2'>";
                  echo "<td><a target='_blank' href='$tmplink'>$name - $tmplink</a></td></tr>";
               }
               if (count($ipmac)>0) {
                  foreach ($ipmac as $key => $val) {
                     $tmplink=$link;
                     $disp=1;
                     if (strstr($link,"[IP]")) {
                        if (empty($val['ip'])) {
                           $disp=0;
                        } else {
                           $tmplink=str_replace("[IP]",$val['ip'],$tmplink);
                        }
                     }
                     if (strstr($link,"[MAC]")) {
                        if (empty($val['mac'])) {
                           $disp=0;
                        } else {
                           $tmplink=str_replace("[MAC]",$val['mac'],$tmplink);
                        }
                     }
                     if ($disp) {
                        echo "<tr class='tab_bg_2'>";
                        echo "<td><a target='_blank' href='$tmplink'>$name #" .
                                    $val['number'] . " - $tmplink</a></td></tr>";
                     }
                  }
               }
            } else {
               echo "<tr class='tab_bg_2'><td><a target='_blank' href='$link'>$name</a></td></tr>";
            }
         } else {// File Generated Link
            $link=$data['name'];
            $ci->getFromDB($itemtype,$ID);

            // Manage Filename
            if (strstr($link,"[NAME]")) {
               $link=str_replace("[NAME]",$ci->getName(),$link);
            }
            if (strstr($link,"[LOGIN]")) {
               if (isset($_SESSION["glpiname"])) {
                  $link=str_replace("[LOGIN]",$_SESSION["glpiname"],$link);
               }
            }
            if (strstr($link,"[ID]")) {
               $link=str_replace("[ID]",$_GET["id"],$link);
            }
            echo "<tr class='tab_bg_2'>";
            echo "<td><a href='".$CFG_GLPI["root_doc"]."/front/link.send.php?lID=".
                        $data['id']."&amp;itemtype=$itemtype&amp;id=$ID' target='_blank'>".
                        $name."</a></td></tr>";
         }
      }
      echo "</table></div>";
   } else {
      echo "<div class='center'><strong>".$LANG['links'][7]."</strong></div>";
   }
}

?>
