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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/** Link Class
**/
class Link extends CommonDBTM {

   static $rightname = 'link';



   static function getTypeName($nb=0) {
      return _n('External link', 'External links',$nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (self::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $restrict = "`glpi_links_itemtypes`.`links_id` = `glpi_links`.`id`
                         AND `glpi_links_itemtypes`.`itemtype` = '".$item->getType()."'".
                          getEntitiesRestrictRequest(" AND ", "glpi_links", '', '', false);
            $nb = countElementsInTable(array('glpi_links_itemtypes','glpi_links'), $restrict);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForItem($item);
      return true;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Link_ItemType', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;

      $query2 = "DELETE FROM `glpi_links_itemtypes`
                 WHERE `links_id` = '".$this->fields['id']."'";
      $DB->query($query2);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getEmpty()
   **/
   function getEmpty() {

      parent::getEmpty();
      //Keep the same behavior as in previous versions
      $this->fields['open_window'] = 1;
   }


   /**
   * Print the link form
   *
   * @param $ID      integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *
   * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td height='23'>".__('Valid tags')."</td>";
      echo "<td colspan='3'>[LOGIN], [ID], [NAME], [LOCATION], [LOCATIONID], [IP], [MAC], [NETWORK],
                            [DOMAIN], [SERIAL], [OTHERSERIAL], [USER], [GROUP], [REALNAME],
                            [FIRSTNAME]</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Name')."</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Link or filename')."</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this, "link", array('size' => 84));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Open in a new window')."</td><td>";
      Dropdown::showYesNo('open_window', $this->fields['open_window']);
      echo "</td><td colspan='2'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('File content')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea name='data' rows='10' cols='96'>".$this->fields["data"]."</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   function getSearchOptions() {

      $tab                      = array();
      $tab['common']            = __('Characteristics');

      $tab[1]['table']          = $this->getTable();
      $tab[1]['field']          = 'name';
      $tab[1]['name']           = __('Name');
      $tab[1]['datatype']       = 'itemlink';
      $tab[1]['massiveaction']  = false;

      $tab[2]['table']          = $this->getTable();
      $tab[2]['field']          = 'id';
      $tab[2]['name']           = __('ID');
      $tab[2]['massiveaction']  = false;
      $tab[2]['datatype']       = 'number';

      $tab[3]['table']          = $this->getTable();
      $tab[3]['field']          = 'link';
      $tab[3]['name']           = __('Link or filename');
      $tab[3]['datatype']       = 'string';

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = __('Entity');
      $tab[80]['massiveaction'] = false;
      $tab[80]['datatype']      = 'dropdown';

      return $tab;
   }


   /**
    * Generate link
    *
    * @param $link    string   original string content
    * @param $item             CommonDBTM object: item used to make replacements
    *
    * @return array of link contents (may have several when item have several IP / MAC cases)
   **/
   static function generateLinkContents($link, CommonDBTM $item) {
      global $DB;

      if (strstr($link,"[ID]")) {
         $link = str_replace("[ID]", $item->fields['id'],$link);
      }
      if (strstr($link,"[LOGIN]")
          && isset($_SESSION["glpiname"])) {
         $link = str_replace("[LOGIN]", $_SESSION["glpiname"], $link);
      }

      if (strstr($link,"[NAME]")) {
         $link = str_replace("[NAME]", $item->getName(), $link);
      }
      if (strstr($link,"[SERIAL]")
          && $item->isField('serial')) {
            $link = str_replace("[SERIAL]", $item->getField('serial'), $link);
      }
      if (strstr($link,"[OTHERSERIAL]")
          && $item->isField('otherserial')) {
            $link=str_replace("[OTHERSERIAL]",$item->getField('otherserial'),$link);
      }
      if (strstr($link,"[LOCATIONID]")
          && $item->isField('locations_id')) {
            $link = str_replace("[LOCATIONID]", $item->getField('locations_id'), $link);
      }
      if (strstr($link,"[LOCATION]")
          && $item->isField('locations_id')) {
            $link = str_replace("[LOCATION]",
                                Dropdown::getDropdownName("glpi_locations",
                                                          $item->getField('locations_id')), $link);
      }
      if (strstr($link,"[NETWORK]")
          && $item->isField('networks_id')) {
            $link = str_replace("[NETWORK]",
                                Dropdown::getDropdownName("glpi_networks",
                                                          $item->getField('networks_id')), $link);
      }
      if (strstr($link,"[DOMAIN]")
          && $item->isField('domains_id')) {
            $link = str_replace("[DOMAIN]",
                                Dropdown::getDropdownName("glpi_domains",
                                                          $item->getField('domains_id')), $link);
      }
      if (strstr($link,"[USER]")
          && $item->isField('users_id')) {
            $link = str_replace("[USER]",
                                Dropdown::getDropdownName("glpi_users",
                                                          $item->getField('users_id')), $link);
      }
      if (strstr($link,"[GROUP]")
          && $item->isField('groups_id')) {
            $link = str_replace("[GROUP]",
                                Dropdown::getDropdownName("glpi_groups",
                                                          $item->getField('groups_id')), $link);
      }
      if (strstr($link,"[REALNAME]")
            && $item->isField('realname')) {
         $link = str_replace("[REALNAME]",$item->getField('realname'),$link);
      }
      if (strstr($link,"[FIRSTNAME]")
            && $item->isField('firstname')) {
         $link = str_replace("[FIRSTNAME]",$item->getField('firstname'),$link);
      }


      $replace_IP  = strstr($link,"[IP]");
      $replace_MAC = strstr($link,"[MAC]");

      if (!$replace_IP && !$replace_MAC) {
         return array($link);
      }
      // Return several links id several IP / MAC

      $ipmac = array();
      if (get_class($item) == 'NetworkEquipment') {
         if ($replace_IP) {
            $query2 = "SELECT `glpi_ipaddresses`.`id`,
                              `glpi_ipaddresses`.`name` AS ip
                       FROM `glpi_networknames`, `glpi_ipaddresses`
                       WHERE `glpi_networknames`.`items_id` = '" . $item->getID() . "'
                             AND `glpi_networknames`.`itemtype` = 'NetworkEquipment'
                             AND `glpi_ipaddresses`.`itemtype` = 'NetworkName'
                             AND `glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`";
            foreach ($DB->request($query2) as $data2) {
               $ipmac['ip'.$data2['id']]['ip']  = $data2["ip"];
               $ipmac['ip'.$data2['id']]['mac'] = $item->getField('mac');
            }
         }
         if ($replace_MAC) {
            // If there is no entry, then, we must at least define the mac of the item ...
            if (count($ipmac) == 0) {
               $ipmac['mac0']['ip']    = '';
               $ipmac['mac0']['mac']   = $item->getField('mac');
            }
         }
      }

      if ($replace_IP) {
         $query2 = "SELECT `glpi_ipaddresses`.`id`,
                           `glpi_networkports`.`mac`,
                           `glpi_ipaddresses`.`name` AS ip
                    FROM `glpi_networkports`, `glpi_networknames`, `glpi_ipaddresses`
                    WHERE `glpi_networkports`.`items_id` = '" . $item->getID() . "'
                          AND `glpi_networkports`.`itemtype` = '" . $item->getType() . "'
                          AND `glpi_networknames`.`itemtype` = 'NetworkPort'
                          AND `glpi_networknames`.`items_id` = `glpi_networkports`.`id`
                          AND `glpi_ipaddresses`.`itemtype` = 'NetworkName'
                          AND `glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`";

         foreach ($DB->request($query2) as $data2) {
            $ipmac['ip'.$data2['id']]['ip']  = $data2["ip"];
            $ipmac['ip'.$data2['id']]['mac'] = $data2["mac"];
         }
      }

      if ($replace_MAC) {
         $left  = '';
         $where = '';
         if ($replace_IP) {
            $left  = " LEFT JOIN `glpi_networknames`
                             ON (`glpi_networknames`.`items_id` = `glpi_networkports`.`id`
                                 AND `glpi_networknames`.`itemtype` = 'NetworkPort')";
            $where = " AND `glpi_networknames`.`id` IS NULL";
         }

         $query2 = "SELECT `glpi_networkports`.`id`,
                           `glpi_networkports`.`mac`
                    FROM `glpi_networkports`
                    $left
                    WHERE `glpi_networkports`.`items_id` = '" . $item->getID() . "'
                          AND `glpi_networkports`.`itemtype` = '" . $item->getType() . "'
                    $where
                    GROUP BY `glpi_networkports`.`mac`";

         foreach ($DB->request($query2) as $data2) {
            $ipmac['mac'.$data2['id']]['ip']  = '';
            $ipmac['mac'.$data2['id']]['mac'] = $data2["mac"];
         }
      }

      $links = array();
      if (count($ipmac) > 0) {
         foreach ($ipmac as $key => $val) {
            $tmplink = $link;
            $disp    = 1;
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
               $links[$key] = $tmplink;
            }
         }
      }

      if (count($links)) {
         return $links;
      }
      return array($link);
   }


   /**
    * Show Links for an item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default '')
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      if ($item->isNewID($item->getID())) {
         return false;
      }

      $restrict = $item->getEntityID();
      if ($item->getType() == 'User') {
         $restrict = Profile_User::getEntitiesForUser($item->getID());
      }

      $query = "SELECT `glpi_links`.`id`,
                       `glpi_links`.`link` AS link,
                       `glpi_links`.`name` AS name ,
                       `glpi_links`.`data` AS data,
                       `glpi_links`.`open_window` AS open_window
                FROM `glpi_links`
                INNER JOIN `glpi_links_itemtypes`
                     ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
                WHERE `glpi_links_itemtypes`.`itemtype`='".$item->getType()."' " .
                      getEntitiesRestrictRequest(" AND", "glpi_links", "entities_id",
                                                 $restrict, true)."
                ORDER BY name";

      $result = $DB->query($query);

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";

      if ($DB->numrows($result) > 0) {
         echo "<tr><th>".self::getTypeName(Session::getPluralNumber())."</th></tr>";
         while ($data = $DB->fetch_assoc($result)) {
            $links = self::getAllLinksFor($item, $data);

            foreach ($links as $link) {
               echo "<tr class='tab_bg_2'>";
               echo "<td class='center'>$link</td></tr>";
            }
         }
         echo "</table></div>";

      } else {
         echo "<tr class='tab_bg_2'><th>".self::getTypeName(Session::getPluralNumber())."</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center b'>".__('No link defined')."</td></tr>";
         echo "</table></div>";
      }
   }


   /**
    * Show Links for an item
    *
    * @since version 0.85
    *
    * @param $item                        CommonDBTM object
    * @param $params    array of params : must contain id / name / link / data
   **/
   static function getAllLinksFor($item, $params=array()) {
      global $CFG_GLPI;

      $computedlinks = array();
      if (!isset($params['name'])
          || !isset($params['link'])
          || !isset($params['data'])
          || !isset($params['id'])) {
         return $computedlinks;
      }

      if (!isset($params['open_window'])) {
         $params['open_window'] = true;
      }

      if (empty($params['name'])) {
         $params['name'] = $params['link'];
      }
      $names = self::generateLinkContents($params['name'], $item);
      $file  = trim($params['data']);

      if (empty($file)) {
         // Generate links
         $links = self::generateLinkContents($params['link'], $item);
         $i     = 1;
         foreach ($links as $key => $val) {
            $name    = (isset($names[$key]) ? $names[$key] : reset($names));
            $url     = $val;
            $newlink = "<a href='$url'";
            if ($params['open_window']) {
               $newlink .= " target='_blank'";
            }
            $newlink          .= ">";
            $linkname          = sprintf(__('%1$s #%2$s'), $name, $i);
            $newlink          .= sprintf(__('%1$s: %2$s'), $linkname, $val);
            $newlink          .= "</a>";
            $computedlinks[]   = $newlink;
            $i++;
         }
      } else {
         // Generate files
         $files = self::generateLinkContents($params['link'], $item);
         $links = self::generateLinkContents($params['data'], $item);
         $i     = 1;
         foreach ($links as $key => $val) {
            $name = (isset($names[$key]) ? $names[$key] : reset($names));
            if (isset($files[$key])) {
               // a different name for each file, ex name = foo-[IP].txt
               $file = $files[$key];
            } else {
               // same name for all files, ex name = foo.txt
               $file = reset($files);
            }
            $url             = $CFG_GLPI["root_doc"]."/front/link.send.php?lID=".$params['id'].
                                 "&amp;itemtype=".$item->getType().
                                 "&amp;id=".$item->getID()."&amp;rank=$key";
            $newlink         = "<a href='$url' target='_blank'>";
            $linkname        = sprintf(__('%1$s #%2$s'), $name, $i);
            $newlink        .= sprintf(__('%1$s: %2$s'), $linkname, $val);
            $newlink        .= "</a>";
            $computedlinks[] = $newlink;
            $i++;
         }
      }

      return $computedlinks;
   }


   /**
    * @since version 0.85
   **/
   static function getSearchOptionsToAdd() {

      $tab                           = array();

      $tab[145]['table']             = 'glpi_links';
      $tab[145]['field']             = '_virtual';
      $tab[145]['name']              = _n('External link', 'External links', Session::getPluralNumber());
      $tab[145]['datatype']          = 'specific';
      $tab[145]['additionalfields']  = array('id','link', 'name', 'data', 'open_window');
      $tab[145]['nosearch']          = true;
      $tab[145]['forcegroupby']      = true;
      $tab[145]['nosort']            = true;
      $tab[145]['joinparams']        = array('beforejoin'
                                              => array('table'      => 'glpi_links_itemtypes',
                                                       'joinparams' => array('jointype'
                                                                              => 'itemtypeonly')));
      if (!Session::isCron()
          && !isCommandLine()) {
         $tab[145]['joinparams']['condition'] = getEntitiesRestrictRequest('AND', 'NEWTABLE');
      }

      return $tab;
   }
}
?>