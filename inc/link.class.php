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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES link
class Link extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][33];
   }


   function canCreate() {
      return haveRight('link', 'w');
   }


   function canView() {
      return haveRight('link', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];
      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;

      $query2 = "DELETE FROM `glpi_links_itemtypes`
                 WHERE `links_id` = '".$this->fields['id']."'";
      $DB->query($query2);
   }


   /**
   * Print the link form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *
   *@return Nothing (display)
   *
   **/
   function showForm ($ID, $options=array()) {
      global $LANG;

      if (!haveRight("link","r")) {
         return false;
      }
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td height='23'>".$LANG['links'][6]."&nbsp;:</td>";
      echo "<td colspan='3'>[LOGIN], [ID], [NAME], [LOCATION], [LOCATIONID], [IP], [MAC], [NETWORK],
                            [DOMAIN], [SERIAL], [OTHERSERIAL], [USER], [GROUP]</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      autocompletionTextField($this, "name", array('size' => 84));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['links'][1]."&nbsp;:</td>";
      echo "<td colspan='2'>";
      autocompletionTextField($this, "link", array('size' => 84));
      echo "</td><td width='1'></td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['links'][9]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='data' rows='10' cols='96'>".$this->fields["data"]."</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'link';
      $tab[3]['name']      = $LANG['links'][1];
      $tab[3]['datatype']  = 'string';

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      return $tab;
   }


   /**
   * Generate link
   *
   * @param $link string : original string content
   * @param $item CommonDBTM : item used to make replacements
   * @param $name string : name used for multi link generation
   * @param $noip boolean : true to not evaluate IP/MAC
   *
   * @return array of link contents (may have several when item have several IP / MAC cases)
   */
   static function generateLinkContents($link, CommonDBTM $item, $name='', $noip=false) {
      global $DB;

      if (strstr($link,"[ID]")) {
         $link = str_replace("[ID]", $item->fields['id'],$link);
      }
      if (strstr($link,"[LOGIN]") && isset($_SESSION["glpiname"])) {
         if (isset($_SESSION["glpiname"])) {
            $link = str_replace("[LOGIN]", $_SESSION["glpiname"],$link);
         }
      }

      if (strstr($link,"[NAME]")) {
         $link = str_replace("[NAME]", $item->getName(), $link);
      }
      if (strstr($link,"[SERIAL]")) {
         if ($item->isField('serial')) {
            $link = str_replace("[SERIAL]", $item->getField('serial'), $link);
         }
      }
      if (strstr($link,"[OTHERSERIAL]")) {
         if ($item->isField('otherserial')) {
            $link=str_replace("[OTHERSERIAL]",$item->getField('otherserial'),$link);
         }
      }
      if (strstr($link,"[LOCATIONID]")) {
         if ($item->isField('locations_id')) {
            $link = str_replace("[LOCATIONID]", $item->getField('locations_id'), $link);
         }
      }
      if (strstr($link,"[LOCATION]")) {
         if ($item->isField('locations_id')) {
            $link = str_replace("[LOCATION]",
                                Dropdown::getDropdownName("glpi_locations",
                                                          $item->getField('locations_id')), $link);
         }
      }
      if (strstr($link,"[NETWORK]")) {
         if ($item->isField('networks_id')) {
            $link = str_replace("[NETWORK]",
                                Dropdown::getDropdownName("glpi_networks",
                                                          $item->getField('networks_id')), $link);
         }
      }
      if (strstr($link,"[DOMAIN]")) {
         if ($item->isField('domains_id')) {
            $link = str_replace("[DOMAIN]",
                                Dropdown::getDropdownName("glpi_domains",
                                                          $item->getField('domains_id')), $link);
         }
      }
      if (strstr($link,"[USER]")) {
         if ($item->isField('users_id')) {
            $link = str_replace("[USER]",
                                Dropdown::getDropdownName("glpi_users",
                                                          $item->getField('users_id')), $link);
         }
      }
      if (strstr($link,"[GROUP]")) {
         if ($item->isField('groups_id')) {
            $link = str_replace("[GROUP]",
                                Dropdown::getDropdownName("glpi_groups",
                                                          $item->getField('groups_id')), $link);
         }
      }
      $ipmac = array();
      $i = 0;

      if ($noip || (!strstr($link,"[IP]") && !strstr($link,"[MAC]"))) {
         return array($link);

      } else { // Return sevral links id several IP / MAC
         $links = array();
         $query2 = "SELECT `ip`, `mac`, `logical_number`
                    FROM `glpi_networkports`
                    WHERE `items_id` = '".$item->fields['id']."'
                          AND `itemtype` = '".get_class($item)."'
                    ORDER BY `logical_number`";
         $result2 = $DB->query($query2);

         if ($DB->numrows($result2)>0) {
            while ($data2=$DB->fetch_array($result2)) {
               $ipmac[$i]['ip']     = $data2["ip"];
               $ipmac[$i]['mac']    = $data2["mac"];
               $ipmac[$i]['number'] = $data2["logical_number"];
               $i++;
            }
         }

         // Add IP/MAC internal switch
         if (get_class($item)=='NetworkEquipment') {
            $tmplink = $link;
            $tmplink = str_replace("[IP]", $item->getField('ip'), $tmplink);
            $tmplink = str_replace("[MAC]", $item->getField('mac'), $tmplink);

            $links["$name - $tmplink"] = $tmplink;
         }
         if (count($ipmac)>0) {
            foreach ($ipmac as $key => $val) {
               $tmplink = $link;
               $disp = 1;
               if (strstr($link,"[IP]")) {
                  if (empty($val['ip'])) {
                     $disp = 0;
                  } else {
                     $tmplink = str_replace("[IP]", $val['ip'], $tmplink);
                  }
               }
               if (strstr($link,"[MAC]")) {
                  if (empty($val['mac'])) {
                     $disp = 0;
                  } else {
                     $tmplink = str_replace("[MAC]", $val['mac'], $tmplink);
                  }
               }

               if ($disp) {
                  $links["$name #" .$val['number']." - $tmplink"] = $tmplink;
               }
            }
         }

         if (count($links)) {
            return $links;
         }
         return array($link);
      }
   }


   /**
    * Show Links for an item
    *
    * @param $itemtype integer : item type
    * @param $ID integer : item ID
    */
   static function showForItem($itemtype, $ID) {
      global $DB, $LANG, $CFG_GLPI;

      if (!class_exists($itemtype)) {
         return false;
      }

      $item = new $itemtype;
      if (!$item->getFromDB($ID)) {
         return false;
      }

      if (!haveRight("link","r")) {
         return false;
      }

      $query = "SELECT `glpi_links`.`id`,
                       `glpi_links`.`link` AS link,
                       `glpi_links`.`name` AS name ,
                       `glpi_links`.`data` AS data
                FROM `glpi_links`
                INNER JOIN `glpi_links_itemtypes`
                     ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
                WHERE `glpi_links_itemtypes`.`itemtype`='$itemtype' " .
                      getEntitiesRestrictRequest(" AND", "glpi_links", "entities_id",
                                                 $item->getEntityID(), true)."
                ORDER BY name";

      $result = $DB->query($query);

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";

      if ($DB->numrows($result)>0) {
         echo "<tr><th>".$LANG['title'][33]."</th></tr>";
         while ($data=$DB->fetch_assoc($result)) {
            $name = $data["name"];
            if (empty($name)) {
               $name = $data["link"];
            }
            $file = trim($data["data"]);

            $tosend = false;
            if (empty($file)) {
               $link = $data["link"];
            } else {
               $link = $data['name'];
               $tosend = true;
            }

            $contents=Link::generateLinkContents($link, $item, $name);
            if (count($contents)) {
               foreach ($contents as $title => $link) {
                  $current_name = $name;
                  if (!empty($title)) {
                     $current_name = $title;
                  }
                  $clean_name=Link::generateLinkContents($current_name, $item, $name, true);

                  $url = $link;
                  if ($tosend) {
                     $url = $CFG_GLPI["root_doc"]."/front/link.send.php?lID=".$data['id'].
                            "&amp;itemtype=$itemtype&amp;id=$ID";
                  }
                  echo "<tr class='tab_bg_2'>";
                  echo "<td class='center'><a href='$url' target='_blank'>".$clean_name[0]."</a>";
                  echo "</td></tr>";
               }
            }
         }
         echo "</table></div>";

      } else {
         echo "<tr class='tab_bg_2'><th>".$LANG['title'][33]."</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center b'>".$LANG['links'][7]."</td></tr>";
         echo "</table></div>";
      }
   }
}

?>