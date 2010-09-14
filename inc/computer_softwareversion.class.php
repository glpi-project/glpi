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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class Computer_SoftwareVersion extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Computer';
   public $items_id_1 = 'computers_id';
   public $itemtype_2 = 'SoftwareVersion';
   public $items_id_2 = 'softwareversions_id';

   /**
    * Get number of installed licenses of a version
    *
    * @param $softwareversions_id version ID
    * @param $entity to search for computer in (default = all active entities)
    *
    * @return number of installations
    */
   static function countForVersion($softwareversions_id, $entity='') {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwareversions`.`id`)
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_computers_softwareversions`.`softwareversions_id`='$softwareversions_id'
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
    * Get number of installed versions of a software
    *
    * @param $softwares_id software ID
    * @return number of installations
    */
   static function countForSoftware($softwares_id) {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwareversions`.`id`)
                FROM `glpi_softwareversions`
                INNER JOIN `glpi_computers_softwareversions`
                      ON (`glpi_softwareversions`.`id`
                          = `glpi_computers_softwareversions`.`softwareversions_id`)
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_softwareversions`.`softwares_id` = '$softwares_id'
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
    * Show installation of a Software
    *
    * @param $software object
    * @return nothing
    */
   static function showForSoftware (Software $software) {
      self::showInstallations($software->getField('id'), 'softwares_id');
   }


   /**
    * Show installation of a Version
    *
    * @param $version SoftwareVersion object
    * @return nothing
    */
   static function showForVersion (SoftwareVersion $version) {
      self::showInstallations($version->getField('id'), 'id');
   }


   /**
    * Show installations of a software
    *
    * @param $searchID valeur to the ID to search
    * @param $crit to search : softwares_id (software) or id (version)
    * @return nothing
    */
   private static function showInstallations($searchID, $crit) {
      global $DB, $CFG_GLPI, $LANG;

      if (!haveRight("software", "r") || !$searchID) {
         return false;
      }

      $canedit = haveRight("software", "w");
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
         // manage several param like location,compname :  order first
         $tmp  = explode(",",$_REQUEST["sort"]);
         $sort = "`".implode("` $order,`",$tmp)."`";
      } else {
         if ($crit=="softwares_id") {
            $sort = "`entity` $order, `version`, `compname`";
         } else {
            $sort = "`entity` $order, `compname`";
         }
      }


      // Total Number of events
      if ($crit=="softwares_id") {
         // Software ID
         $number = countElementsInTable("glpi_computers_softwareversions, glpi_computers,
                                         glpi_softwareversions",
                 "glpi_computers_softwareversions.computers_id = glpi_computers.id
                  AND glpi_computers_softwareversions.softwareversions_id = glpi_softwareversions.id
                  AND glpi_softwareversions.softwares_id=$searchID" .
                  getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                  AND glpi_computers.is_deleted=0
                  AND glpi_computers.is_template=0");
      } else {
         //SoftwareVersion ID
         $number = countElementsInTable("glpi_computers_softwareversions, glpi_computers",
                              "glpi_computers_softwareversions.computers_id = glpi_computers.id
                               AND glpi_computers_softwareversions.softwareversions_id = $searchID" .
                               getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                               AND glpi_computers.is_deleted=0
                               AND glpi_computers.is_template=0");
      }

      echo "<div class='center'>";
      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['search'][15]."</th></tr>";
         echo "</table></div>\n";
         return;
      }

      // Display the pager
      printAjaxPager($LANG['software'][19],$start,$number);

      $query = "SELECT DISTINCT `glpi_computers_softwareversions`.*,
                       `glpi_computers`.`name` AS compname,
                       `glpi_computers`.`id` AS cID,
                       `glpi_computers`.`serial`,
                       `glpi_computers`.`otherserial`,
                       `glpi_users`.`name` AS username,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_softwareversions`.`id` AS vID,
                       `glpi_softwareversions`.`softwares_id` AS sID,
                       `glpi_softwareversions`.`name` AS vername,
                       `glpi_entities`.`completename` AS entity,
                       `glpi_locations`.`completename` AS location,
                       `glpi_states`.`name` AS state,
                       `glpi_groups`.`name` AS groupe
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                         = `glpi_softwareversions`.`id`)
                INNER JOIN `glpi_computers`
                     ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                LEFT JOIN `glpi_locations` ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                WHERE (`glpi_softwareversions`.`$crit` = '$searchID') " .
                       getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                       AND `glpi_computers`.`is_deleted` = '0'
                       AND `glpi_computers`.`is_template` = '0'
                ORDER BY $sort $order
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      $rand = mt_rand();

      if ($result=$DB->query($query)) {
         if ($data=$DB->fetch_assoc($result)) {
            $softwares_id = $data['sID'];

            $soft = new Software;
            $showEntity = ($soft->getFromDB($softwares_id) && $soft->isRecursive());
            $title=$LANG['help'][31] ." = ". $soft->fields["name"];
            if ($crit=="id") {
               $title .= " - " . $data["vername"];
            }
            initNavigateListItems('Computer',$title);
            $sort_img="<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
                        ($order == "DESC" ? "puce-down.png" : "puce-up.png") . "\" alt='' title=''>";
            if ($canedit) {
               echo "<form name='softinstall".$rand."' id='softinstall".$rand."' method='post' action=\"".
                      $CFG_GLPI["root_doc"]."/front/computer_softwareversion.form.php\">";
               echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
               echo "<table class='tab_cadre_fixehov'><tr>";
               echo "<th>&nbsp;</th>";
            } else {
               echo "<table class='tab_cadre_fixehov'><tr>";
            }

            if ($crit=="softwares_id") {
               echo "<th>".($sort=="`vername`"?$sort_img:"").
                     "<a href='javascript:reloadTab(\"sort=vername&amp;order=".
                       ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['software'][5].
                     "</a></th>";
            }
            echo "<th>".($sort=="`compname`"?$sort_img:"").
                  "<a href='javascript:reloadTab(\"sort=compname&amp;order=".
                    ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][16]."</a></th>";
            if ($showEntity) {
               echo "<th>".(strstr($sort,"entity")?$sort_img:"").
                     "<a href='javascript:reloadTab(\"sort=entity,compname&amp;order=".
                       ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['entity'][0].
                     "</a></th>";
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
            echo "<th>".($sort=="`lname`"?$sort_img:"").
                  "<a href='javascript:reloadTab(\"sort=lname&amp;order=".
                    ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['software'][11].
                  "</a></th>";
            echo "</tr>\n";

            do {
               addToNavigateListItems('Computer',$data["cID"]);

               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
               }
               if ($crit=="softwares_id") {
                  echo "<td><a href='softwareversion.form.php?id=".$data['vID']."'>".$data['version'];
                  echo "</a></td>";
               }
               $compname=$data['compname'];
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

               $lics=Computer_SoftwareLicense::GetLicenseForInstallation($data['cID'],$data['vID']);
               echo "<td>";
               if (count($lics)) {
                  foreach ($lics as $data) {
                     echo "<a href='softwarelicense.form.php?id=".$data['id']."'>".$data['name'];
                     echo "</a><br>";
                  }
               }
               echo "</td>";

               echo "</tr>\n";

            } while ($data=$DB->fetch_assoc($result));

            echo "</table>\n";

            if ($canedit) {
               openArrowMassive("softinstall".$rand."",true);
               SoftwareVersion::dropdown(array( 'name'         => 'versionID',
                                                'softwares_id' => $softwares_id));
               echo "&nbsp;<input type='submit' name='moveinstalls' value='".
                     $LANG['buttons'][20]."' class='submit'>&nbsp;";
               closeArrowMassive('deleteinstalls', $LANG['buttons'][6]);

               echo "</form>";
            }
         } else { // Not found
            echo $LANG['search'][15];
         }
      } // Query
      echo "</div>\n";
   }


   /**
    * Show number of installation per entity
    *
    * @param $version SoftwareVersion object
    *
    * @return nothing
    */
   static function showForVersionByEntity(SoftwareVersion $version) {
      global $DB, $CFG_GLPI, $LANG;

      $softwareversions_id = $version->getField('id');

      if (!haveRight("software", "r") || !$softwareversions_id) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th>&nbsp;".$LANG['entity'][0]."&nbsp;</th>";
      echo "<th>&nbsp;".$LANG['software'][19]."&nbsp;</th>";
      echo "</tr>\n";

      $tot = 0;
      if (in_array(0,$_SESSION["glpiactiveentities"])) {
         $nb = self::countForVersion($softwareversions_id,0);
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
         $nb = self::countForVersion($softwareversions_id,$ID);
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
    * Show software installed on a computer
    *
    * @param $comp Computer object
    * @param $withtemplate template case of the view process
    * @return nothing
    */
   static function showForComputer(Computer $comp, $withtemplate = '') {
      global $DB, $CFG_GLPI, $LANG;

      if (!haveRight("software", "r")) {
         return false;
      }

      $computers_id = $comp->getField('id');
      $rand = mt_rand();
      $canedit = haveRight("software", "w");
      $entities_id = $comp->fields["entities_id"];

      $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_computers_softwareversions`.`id`,
                       `glpi_states`.`name` AS state,
                       `glpi_softwareversions`.`id` AS verid,
                       `glpi_softwareversions`.`softwares_id`,
                       `glpi_softwareversions`.`name` AS version
                FROM `glpi_computers_softwareversions`
                LEFT JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                          = `glpi_softwareversions`.`id`)
                LEFT JOIN `glpi_states` ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                WHERE `glpi_computers_softwareversions`.`computers_id` = '$computers_id'
                ORDER BY `softwarecategories_id`, `softname`, `version`";
      $result = $DB->query($query);
      $i = 0;

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      if ((empty ($withtemplate) || $withtemplate != 2) && $canedit) {
         echo "<tr class='tab_bg_1'><td class='center' colspan='5'>";
         echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] .
               "/front/computer_softwareversion.form.php\">";
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
         Software::dropdownSoftwareToInstall("softwareversions_id", $entities_id);
         echo "<input type='submit' name='install' value='" .$LANG['buttons'][4]."' class='submit'>";
         echo "</form>";
         echo "</td></tr>\n";
      }
      echo "<tr><th colspan='5'>";
      if ($DB->numrows($result)==1) {
         echo $LANG['software'][16];
      } else {
         echo $LANG['software'][17];
      }
      echo "</th></tr>";

      $cat = -1;

      initNavigateListItems('Software', $LANG['help'][25]." = ".
                  (empty($comp->fields["name"]) ? "(".$comp->fields["id"].")":$comp->fields["name"]));
      initNavigateListItems('SoftwareLicense', $LANG['help'][25]." = ".
                  (empty($comp->fields["name"]) ? "(".$comp->fields["id"].")":$comp->fields["name"]));

      $installed = array();
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_array($result)) {
            if ($data["softwarecategories_id"]!=$cat) {
               self::displayCategoryFooter($cat, $rand, $canedit);
               $cat = self::displayCategoryHeader($computers_id, $data, $rand, $canedit);
            }

            $licids = self::displaySoftsByCategory($data, $computers_id, $withtemplate, $canedit);
            addToNavigateListItems('Software', $data["softwares_id"]);
            foreach ($licids as $licid) {
               addToNavigateListItems('SoftwareLicense', $licid);
               $installed[] = $licid;
            }
         }
         self::displayCategoryFooter($cat, $rand, $canedit);
      }

      // Affected licenses NOT installed
      $query = "SELECT `glpi_softwarelicenses`.*,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_states`.`name` AS state
               FROM `glpi_softwarelicenses`
               LEFT JOIN `glpi_computers_softwarelicenses`
                      ON (`glpi_computers_softwarelicenses`.softwarelicenses_id
                          = `glpi_softwarelicenses`.`id`)
               INNER JOIN `glpi_softwares`
                      ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id`)
               LEFT JOIN `glpi_softwareversions`
                      ON (`glpi_softwarelicenses`.`softwareversions_id_use` = `glpi_softwareversions`.`id`
                           OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                               AND `glpi_softwarelicenses`.`softwareversions_id_buy` = `glpi_softwareversions`.`id`)
                           )
               LEFT JOIN `glpi_states` ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
               WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$computers_id' ";
      if (count($installed)) {
         $query .= " AND `glpi_softwarelicenses`.`id` NOT IN (".implode(',',$installed).")";
      }
      $req = $DB->request($query);
      if ($req->numrows()) {
         $cat = true;
         foreach ($req as $data) {
            if ($cat) {
               self::displayCategoryHeader($computers_id, $data, $rand, $canedit);
               $cat = false;
            }
            self::displaySoftsByLicense($data, $computers_id, $withtemplate, $canedit);
            addToNavigateListItems('SoftwareLicense', $data["id"]);
         }
         self::displayCategoryFooter(NULL, $rand, $canedit);
      }

      echo "</table></div>\n";

   }


   /**
    * Display category footer for Computer_SoftwareVersion::showForComputer function
    *
    * @param $cat current category ID
    * @param $rand random for unicity
    * @param $canedit boolean
    *
    * @return new category ID
    */
   private static function displayCategoryFooter($cat, $rand, $canedit) {
      global $LANG;

      // Close old one
      if ($cat != -1) {
         echo "</table>";

         if ($canedit) {
            openArrowMassive("lic_form$cat$rand", true);

            if (isset($cat)) {
               closeArrowMassive('massuninstall', $LANG['buttons'][5]);
            } else {
               closeArrowMassive('massinstall', $LANG['buttons'][4]);
            }

         }
         echo "</form>";
         echo "</div></td></tr>";
      }
   }


   /**
    * Display category header for Computer_SoftwareVersion::showForComputer function
    *
    * @param $computers_ID ID of the computer
    * @param $data data used to display
    * @param $rand random for unicity
    * @param $canedit boolean
    *
    * @return new category ID
    */
   private static function displayCategoryHeader($computers_ID, $data, $rand, $canedit) {
      global $LANG, $CFG_GLPI;

      $display = "none";

      if (isset($data["softwarecategories_id"])) {
         $cat = $data["softwarecategories_id"];
         if ($cat) {
            // Categorized
            $catname = Dropdown::getDropdownName('glpi_softwarecategories', $cat);
            $display = $_SESSION["glpiis_categorized_soft_expanded"];
         } else {
            // Not categorized
            $catname = $LANG['softwarecategories'][2];
            $display = $_SESSION["glpiis_not_categorized_soft_expanded"];
         }
      } else {
         // Not installed
         $cat = '';
         $catname = $LANG['software'][3];
         $display = true;
      }

      echo "<tr class='tab_bg_2'><td class='center' colspan='5'>";
      echo "<a href=\"javascript:showHideDiv('softcat$cat$rand','imgcat$cat','" . GLPI_ROOT .
            "/pics/folder.png','" . GLPI_ROOT . "/pics/folder-open.png');\">";
      echo "<img alt='' name='imgcat$cat' src=\"" . GLPI_ROOT . "/pics/folder" .
            (!$display ? '' : "-open") . ".png\">&nbsp;<strong>" . $catname . "</strong>";
      echo "</a></td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='5'>";
      echo "<div class='center' id='softcat$cat$rand' " . (!$display ? "style=\"display:none;\"" : '') . ">";
      echo "<form id='lic_form$cat$rand' name='lic_form$cat$rand' method='post' action='";
      echo $CFG_GLPI["root_doc"]."/front/computer_softwareversion.form.php'>";
      echo "<input type='hidden' name='computers_id' value='$computers_ID'>";

      echo "<table class='tab_cadre_fixe'><tr>";
      if ($canedit) {
         echo "<th>&nbsp;</th>";
      }
      echo "<th>" . $LANG['common'][16] . "</th><th>" . $LANG['state'][0] . "</th>";
      echo "<th>" .$LANG['rulesengine'][78]."</th><th>" . $LANG['install'][92] . "</th></tr>\n";

      return $cat;
   }


   /**
    * Display a installed software for a category
    *
    * @param $data data used to display
    * @param $computers_id ID of the computer
    * @param $withtemplate template case of the view process
    * @param $canedit boolean user can edit software ?

    * @return array of found license id
    */
   private static function displaySoftsByCategory($data, $computers_id, $withtemplate, $canedit) {
      global $DB, $LANG, $CFG_GLPI;

      $ID = $data["id"];
      $verid = $data["verid"];
      $multiple = false;

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td><input type='checkbox' name='softversion_".$data['id']."'></td>";
      }
      echo "<td class='center b'>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/software.form.php?id=".$data['softwares_id'] ."\">";
      echo $data["softname"] . ($_SESSION["glpiis_ids_visible"] ? " (" . $data['softwares_id'] . ")" : "");
      echo "</a></td>";
      echo "<td>" . $data["state"] . "</td>";

      echo "<td>" . $data["version"];
      if ((empty ($withtemplate) || $withtemplate != 2)
          && $canedit) {

         echo " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/computer_softwareversion.form.php".
              "?uninstall=uninstall&amp;id=$ID&amp;computers_id=$computers_id\">";
         echo "<strong>" . $LANG['buttons'][5] . "</strong></a>";
      }
      echo "</td><td>";

      $query = "SELECT `glpi_softwarelicenses`.*,
                       `glpi_softwarelicensetypes`.`name` AS type
                FROM `glpi_computers_softwarelicenses`
                INNER JOIN `glpi_softwarelicenses`
                     ON (`glpi_computers_softwarelicenses`.`softwarelicenses_id`
                         = `glpi_softwarelicenses`.`id`)
                LEFT JOIN `glpi_softwarelicensetypes`
                     ON (`glpi_softwarelicenses`.`softwarelicensetypes_id`
                         =`glpi_softwarelicensetypes`.`id`)
                WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$computers_id'
                      AND (`glpi_softwarelicenses`.`softwareversions_id_use` = '$verid'
                           OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                               AND `glpi_softwarelicenses`.`softwareversions_id_buy` = '$verid'))";

      $licids = array();
      foreach ($DB->request($query) as $licdata) {
         $licids[] = $licdata['id'];
         echo "<strong>". $licdata['name'] . "</strong>&nbsp; ";
         if ($licdata['type']) {
            echo "(".$licdata['type'].")&nbsp; ";
         }
         $link_item = getItemTypeFormURL('SoftwareLicense');
         $link = $link_item."?id=".$licdata['id'];
         showToolTip ($LANG['common'][16]."&nbsp;: ".$licdata['name']."<br>". $LANG['common'][19].
                        "&nbsp;: ".$licdata['serial']."<br>".$licdata['comment'],
                      array('link' => $link));
         echo "<br>";
      }
      if (!count($licids)) {
         echo "&nbsp;";
      }

      echo "</td></tr>\n";

      return $licids;
   }


   /**
    * Display a software for a License (not installed)
    *
    * @param $data data used to display
    * @param $computers_id ID of the computer
    * @param $withtemplate template case of the view process
    * @param $canedit boolean user can edit software ?

    * @return nothing
    */
   private static function displaySoftsByLicense($data, $computers_id, $withtemplate, $canedit) {
      global $LANG, $CFG_GLPI;

      if ($data["softwareversions_id_use"]>0) {
         $ID = $data["softwareversions_id_use"];
      } else {
         $ID = $data["softwareversions_id_buy"];
      }
      $multiple = false;
      $link_item = getItemTypeFormURL('SoftwareLicense');
      $link = $link_item."?id=".$data['id'];

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td>";
         if ((empty ($withtemplate) || $withtemplate != 2)
             && $ID>0) {

            echo "<input type='checkbox' name='softversion_$ID'>";
         }
         echo "</td>";
      }
      echo "<td class='center b'>";
      echo "<a href=\"".$CFG_GLPI["root_doc"] ."/front/software.form.php?id=".$data['softwares_id']."\">";
      echo $data["softname"] . ($_SESSION["glpiis_ids_visible"] ? " (" . $data['softwares_id'] . ")" : "");
      echo "</a></td>";
      echo "<td>" . $data["state"] . "</td>";

      echo "<td>" . $data["version"];
      if ((empty ($withtemplate) || $withtemplate != 2)
          && $canedit && $ID>0) {

         echo " - <a href=\"" . $CFG_GLPI["root_doc"] ."/front/computer_softwareversion.form.php".
               "?install=install&amp;softwareversions_id=$ID&amp;computers_id=$computers_id\">";
         echo "<strong>" . $LANG['buttons'][4] . "</strong></a>";
      }
      echo "</td></td><strong>" . $data["name"] . "</strong>&nbsp; ";
      if ($data["softwarelicensetypes_id"]) {
         echo " (". Dropdown::getDropdownName("glpi_softwarelicensetypes",
                                              $data["softwarelicensetypes_id"]).")&nbsp; ";
      }
      showToolTip ($LANG['common'][16]."&nbsp;: ".$data['name']."<br>". $LANG['common'][19].
                     "&nbsp;: ".$data['serial']."<br>".$data['comment'],
                   array('link' => $link));
      echo "</td></tr>\n";
   }


   function post_addItem() {
      global $DB;

      $vers = new SoftwareVersion();
      if (!$vers->getFromDB($this->fields['softwareversions_id'])) {
         return false;
      }

      // Update affected licenses
//       $lic = new SoftwareLicense();
//       $query = "SELECT `id`
//                 FROM `glpi_softwarelicenses`
//                 WHERE `softwares_id` = '".$vers->fields['softwares_id']."'
//                       AND `computers_id` = '".$this->fields['computers_id']."'
//                       AND `softwareversions_id_use` = '0'";
//       foreach ($DB->request($query) as $data) {
//          $data['softwareversions_id_use'] = $this->fields['softwareversions_id'];
//          $lic->update($data);
//       }

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }

      $soft = new Software();
      if ($soft->getFromDB($vers->fields['softwares_id'])) {
         $changes[0] = '0';
         $changes[1] = "";
         $changes[2] = addslashes($soft->fields["name"] . " " . $vers->fields["name"]);
         // Log on Computer history
         Log::history($this->fields['computers_id'], 'Computer', $changes, 0,
                      HISTORY_INSTALL_SOFTWARE);
      }
      $comp = new Computer();
      if ($comp->getFromDB($this->fields['computers_id'])) {
         $changes[0] = '0';
         $changes[1] = "";
         $changes[2] = addslashes($comp->fields["name"]);
         // Log on SoftwareVersion history
         Log::history($this->fields['softwareversions_id'], 'SoftwareVersion', $changes, 0,
                      HISTORY_INSTALL_SOFTWARE);
      }
   }


   function post_deleteFromDB() {

      $vers = new SoftwareVersion();
      if (!$vers->getFromDB($this->fields['softwareversions_id'])) {
         return false;
      }

      /// Could not be possible : because several computers may be linked to a version
      // Update affected licenses
//       $lic = new SoftwareLicense();
//       $query = "SELECT `id`
//                 FROM `glpi_softwarelicenses`
//                 WHERE `softwares_id` = '".$vers->fields['softwares_id']."'
//                   AND `computers_id` = '".$this->fields['computers_id']."'
//                   AND `softwareversions_id_use` = '".$this->fields['softwareversions_id']."'";
//       foreach ($DB->request($query) as $data) {
//          $data['softwareversions_id_use'] = 0;
//          $lic->update($data);
//       }

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }

      $soft = new Software();
      if ($soft->getFromDB($vers->fields['softwares_id'])) {
         $changes[0] = '0';
         $changes[1] = addslashes($soft->fields["name"] . " " . $vers->fields["name"]);
         $changes[2] = "";
         // Log on Computer history
         Log::history($this->fields['computers_id'], 'Computer', $changes, 0,
                      HISTORY_UNINSTALL_SOFTWARE);
      }
      $comp = new Computer();
      if ($comp->getFromDB($this->fields['computers_id'])) {
         $changes[0] = '0';
         $changes[1] = addslashes($comp->fields["name"]);
         $changes[2] = "";
         // Log on SoftwareVersion history
         Log::history($this->fields['softwareversions_id'], 'SoftwareVersion', $changes, 0,
                      HISTORY_UNINSTALL_SOFTWARE);
      }
   }


   /**
    * Update version installed on a computer
    *
    * @param $instID ID of the install software lienk
    * @param $softwareversions_id ID of the new version
    * @param $dohistory Do history ?
    *
    * @return nothing
    */
   function upgrade($instID, $softwareversions_id, $dohistory=1) {

      if ($this->getFromDB($instID)) {
         $computers_id = $this->fields['computers_id'];
         $this->delete(array('id' => $instID));
         $this->add(array('computers_id'        => $computers_id,
                          'softwareversions_id' => $softwareversions_id));
      }
   }


   /**
    * Duplicate all software from a computer template to his clone
    */
   function cloneComputer ($oldid, $newid) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `computers_id` = '$oldid'";

      foreach ($DB->request($query) as $data) {
         unset($data['id']);
         $data['computers_id'] = $newid;
         $data['_no_history'] = true;

         $this->add($data);
      }
   }
}

?>