<?php
/*
 * @license $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either license 2 of the License, or
 (at your option) any later license.

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

class Computer_SoftwareLicense extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Computer';
   public $items_id_1 = 'computers_id';
   public $itemtype_2 = 'SoftwareLicense';
   public $items_id_2 = 'softwarelicenses_id';


   /**
    * Get number of installed licenses of a license
    *
    * @param $softwarelicenses_id license ID
    * @param $entity to search for computer in (default = all active entities)
    *
    * @return number of installations
   **/
   static function countForLicense($softwarelicenses_id, $entity='') {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwarelicenses`.`id`)
                FROM `glpi_computers_softwarelicenses`
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_computers_softwarelicenses`.`softwarelicenses_id`='$softwarelicenses_id'
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0' " .
                      getEntitiesRestrictRequest('AND', 'glpi_computers','',$entity);

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }


   /**
    * Get number of installed licenses of a software
    *
    * @param $softwares_id software ID
    *
    * @return number of installations
   **/
   static function countForSoftware($softwares_id) {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwarelicenses`.`id`)
                FROM `glpi_softwarelicenses`
                INNER JOIN `glpi_computers_softwarelicenses`
                      ON (`glpi_softwarelicenses`.`id`
                          = `glpi_computers_softwarelicenses`.`softwarelicenses_id`)
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_softwarelicenses`.`softwares_id` = '$softwares_id'
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0' " .
                      getEntitiesRestrictRequest('AND', 'glpi_computers');

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }


   /**
    * Show number of installation per entity
    *
    * @param $license SoftwareLicense object
    *
    * @return nothing
   **/
   static function showForLicenseByEntity(SoftwareLicense $license) {
      global $DB, $CFG_GLPI, $LANG;

      $softwarelicense_id = $license->getField('id');

      if (!haveRight("software", "r") || !$softwarelicense_id) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th>&nbsp;".$LANG['entity'][0]."&nbsp;</th>";
      echo "<th>&nbsp;".$LANG['software'][9]."&nbsp;-&nbsp;".$LANG['tracking'][29]."</th>";
      echo "</tr>\n";

      $tot = 0;
      if (in_array(0,$_SESSION["glpiactiveentities"])) {
         $nb = self::countForLicense($softwarelicense_id,0);
         if ($nb>0) {
            echo "<tr class='tab_bg_2'><td>" . $LANG['entity'][2] . "</td>";
            echo "<td class='right'>" . $nb . "</td></tr>\n";
            $tot += $nb;
         }
      }
      $sql = "SELECT `id`, `completename`
              FROM `glpi_entities` " .
              getEntitiesRestrictRequest('WHERE', 'glpi_entities') ."
              ORDER BY `completename`";

      foreach ($DB->request($sql) as $ID => $data) {
         $nb = self::countForLicense($softwarelicense_id,$ID);
         if ($nb>0) {
            echo "<tr class='tab_bg_2'><td>" . $data["completename"] . "</td>";
            echo "<td class='right'>".$nb."</td></tr>\n";
            $tot += $nb;
         }
      }

      if ($tot>0) {
         echo "<tr class='tab_bg_1'><td class='right b'>".$LANG['common'][33]."</td>";
         echo "<td class='right b'>".$tot."</td></tr>\n";
      } else {
         echo "<tr class='tab_bg_1'><td colspan='2 b'>" . $LANG['search'][15] . "</td></tr>\n";
      }
      echo "</table></div>";
   }


   /**
    * Show computers linked to a License
    *
    * @param $license SoftwareLicense object
    *
    * @return nothing
   **/
   static function showForLicense (SoftwareLicense $license) {
      global $DB, $CFG_GLPI, $LANG;

      $searchID = $license->getField('id');

      if (!haveRight("software", "r") || !$searchID) {
         return false;
      }

      $canedit         = haveRight("software", "w");
      $canshowcomputer = haveRight("computer", "r");

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }

      if (isset($_REQUEST["order"]) && $_REQUEST["order"]=="DESC") {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      if (isset($_REQUEST["sort"]) && !empty($_REQUEST["sort"])) {
         // manage several param like location,compname : order first
         $tmp  = explode(",",$_REQUEST["sort"]);
         $sort = "`".implode("` $order,`",$tmp)."`";
      } else {
         $sort = "`entity` $order, `compname`";
      }

      //SoftwareLicense ID
      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_computers_softwarelicenses`
                       INNER JOIN `glpi_computers`
                           ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                       WHERE `glpi_computers_softwarelicenses`.`softwarelicenses_id` = '$searchID'" .
                             getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                             AND `glpi_computers`.`is_deleted` = '0'
                             AND `glpi_computers`.`is_template` = '0'";

      $number = 0;
      if ($result =$DB->query($query_number)) {
         $number  = $DB->result($result,0,0);
      }

      echo "<div class='center'>";

      if ($canedit) {
         echo "<form method='post' action='".
                $CFG_GLPI["root_doc"]."/front/computer_softwarelicense.form.php'>";
         echo "<input type='hidden' name='softwarelicenses_id' value='$searchID'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td>";
         Dropdown::show('Computer', array('entity'      => $license->fields['entities_id'],
                                          'entity_sons' => $license->fields['is_recursive']));

         echo "</td>";
         echo "<td><input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit'>";
         echo "</td></tr>";

         echo "</table></form>";
      }

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['search'][15]."</th></tr>";
         echo "</table></div>\n";
         return;
      }

      // Display the pager
      printAjaxPager($LANG['software'][9], $start, $number);

      $query = "SELECT `glpi_computers_softwarelicenses`.*,
                       `glpi_computers`.`name` AS compname,
                       `glpi_computers`.`id` AS cID,
                       `glpi_computers`.`serial`,
                       `glpi_computers`.`otherserial`,
                       `glpi_users`.`name` AS username,
                       `glpi_softwarelicenses`.`name` AS license,
                       `glpi_softwarelicenses`.`id` AS vID,
                       `glpi_softwarelicenses`.`name` AS vername,
                       `glpi_entities`.`completename` AS entity,
                       `glpi_locations`.`completename` AS location,
                       `glpi_states`.`name` AS state,
                       `glpi_groups`.`name` AS groupe,
                       `glpi_softwarelicenses`.`name` AS lname,
                       `glpi_softwarelicenses`.`id` AS lID
                FROM `glpi_computers_softwarelicenses`
                INNER JOIN `glpi_softwarelicenses`
                     ON (`glpi_computers_softwarelicenses`.`softwarelicenses_id`
                          = `glpi_softwarelicenses`.`id`)
                INNER JOIN `glpi_computers`
                     ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                LEFT JOIN `glpi_locations`
                     ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                WHERE (`glpi_softwarelicenses`.`id` = '$searchID') " .
                       getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                       AND `glpi_computers`.`is_deleted` = '0'
                       AND `glpi_computers`.`is_template` = '0'
                ORDER BY $sort $order
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      $rand=mt_rand();

      if ($result=$DB->query($query)) {
         if ($data=$DB->fetch_assoc($result)) {

            $soft = new Software;
            $soft->getFromDB($license->fields['softwares_id']);
            $showEntity = ($license->isRecursive());
            $title      =$LANG['help'][31] ." = ". $soft->fields["name"]." - " . $data["vername"];

            initNavigateListItems('Computer',$title);
            $sort_img = "<img src='" . $CFG_GLPI["root_doc"] . "/pics/" .
                        ($order == "DESC" ? "puce-down.png" : "puce-up.png") . "' alt='' title=''>";
            if ($canedit) {
               echo "<form name='softinstall".$rand."' id='softinstall".$rand."' method='post'
                      action='".$CFG_GLPI["root_doc"]."/front/computer_softwarelicense.form.php'>";
               echo "<table class='tab_cadre_fixehov'><tr>";
               echo "<th>&nbsp;</th>";
            } else {
               echo "<table class='tab_cadre_fixehov'><tr>";
            }

            echo "<th>".($sort=="`compname`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][16]."</a></th>";

            if ($showEntity) {
               echo "<th>".(strstr($sort,"entity")?$sort_img:"").
                    "<a href='javascript:reloadTab(\"sort=entity,compname&amp;order=".
                      ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['entity'][0]."</a></th>";
            }

            echo "<th>".($sort=="`serial`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=serial&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][19]."</a></th>";
            echo "<th>".($sort=="`otherserial`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=otherserial&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][20]."</a></th>";
            echo "<th>".(strstr($sort,"`location`")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=location,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][15]."</a></th>";
            echo "<th>".(strstr($sort,"state")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=state,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['state'][0]."</a></th>";
            echo "<th>".(strstr($sort,"groupe")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=groupe,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][35]."</a></th>";
            echo "<th>".(strstr($sort,"username")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=username,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][34]."</a></th>";
            echo "</tr>\n";

            do {
               addToNavigateListItems('Computer',$data["cID"]);

               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
               }

               $compname = $data['compname'];
               if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
                  $compname .= " (".$data['cID'].")";
               }

               if ($canshowcomputer) {
                  echo "<td><a href='computer.form.php?id=".$data['cID']."'>$compname</a></td>";
               } else {
                  echo "<td>".$compname."</td>";
               }

               if ($showEntity) {
                  echo "<td>".(empty($data['entity']) ? $LANG['entity'][2] : $data['entity'])."</td>";
               }
               echo "<td>".$data['serial']."</td>";
               echo "<td>".$data['otherserial']."</td>";
               echo "<td>".$data['location']."</td>";
               echo "<td>".$data['state']."</td>";
               echo "<td>".$data['groupe']."</td>";
               echo "<td>".$data['username']."</td>";
               echo "</tr>\n";

            } while ($data=$DB->fetch_assoc($result));

            echo "</table>\n";

            if ($canedit) {
               openArrowMassive("softinstall".$rand."",true);
               Dropdown::show('SoftwareLicense',
                              array('condition' => "`glpi_softwarelicenses`.`softwares_id`
                                                      = '".$license->fields['softwares_id']."'",
                           '        used'       => array($searchID)));

               echo "&nbsp;<input type='submit' name='move' value=\"".
                     $LANG['buttons'][20]."\" class='submit'>&nbsp;";
               closeArrowMassive('delete', $LANG['buttons'][6]);

               echo "</form>";
            }

         } else { // Not found
            echo $LANG['search'][15];
         }
      } // Query
      echo "</div>\n";

   }


   /**
    * Update license associated on a computer
    *
    * @param $licID ID of the install software lienk
    * @param $softwarelicenses_id ID of the new license
    *
    * @return nothing
   **/
   function upgrade($licID, $softwarelicenses_id) {
      global $DB;

      if ($this->getFromDB($licID)) {
         $computers_id = $this->fields['computers_id'];
         $this->delete(array('id' => $licID));
         $this->add(array('computers_id'        => $computers_id,
                          'softwarelicenses_id' => $softwarelicenses_id));
      }
   }


   /**
    * Get licenses list corresponding to an installation
    *
    * @param $computers_id ID of the computer
    * @param $softwareversions_id ID of the version
    *
    * @return nothing
   **/
   static function GetLicenseForInstallation($computers_id, $softwareversions_id) {
      global $DB;

      $lic = array();
      $sql = "SELECT `glpi_softwarelicenses`.*,
                     `glpi_softwarelicensetypes`.`name` AS type
              FROM `glpi_softwarelicenses`
              INNER JOIN `glpi_computers_softwarelicenses`
                  ON (`glpi_softwarelicenses`.`id`
                           = `glpi_computers_softwarelicenses`.`softwarelicenses_id`
                      AND `glpi_computers_softwarelicenses`.`computers_id` = '$computers_id')
              LEFT JOIN `glpi_softwarelicensetypes`
                  ON (`glpi_softwarelicenses`.`softwarelicensetypes_id`
                           =`glpi_softwarelicensetypes`.`id`)
              WHERE `glpi_softwarelicenses`.`softwareversions_id_use` = '$softwareversions_id'
                    OR `glpi_softwarelicenses`.`softwareversions_id_buy` = '$softwareversions_id'";

      foreach ($DB->request($sql) as $ID => $data) {
         $lic[$data['id']] = $data;
      }
      return $lic;
   }

}

?>