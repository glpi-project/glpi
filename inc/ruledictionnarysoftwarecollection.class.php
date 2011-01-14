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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class RuleDictionnarySoftwareCollection extends RuleCachedCollection {
   // From RuleCollection

   public $stop_on_first_match = true;
   public $right               = 'rule_dictionnary_software';
   public $menu_type           = 'dictionnary';
   public $menu_option         = 'software';


   /**
    * Constructor
   **/
   function __construct() {

      //Init cache system values
      $this->initCache("glpi_rulecachesoftwares", array("name"         => "old_value",
                                                        "manufacturer" => "manufacturer"),
                       array("name"                => "new_value",
                             "version"             => "version",
                             "manufacturer"        => "new_manufacturer",
                             "is_helpdesk_visible" => "is_helpdesk_visible",
                             "_ignore_ocs_import"  => "ignore_ocs_import"));
   }


   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][35];
   }


   function cleanTestOutputCriterias($output) {

      //If output array contains keys begining with _ : drop it
      foreach ($output as $criteria => $value) {
         if ($criteria[0] == '_' && $criteria != '_ignore_ocs_import') {
            unset ($output[$criteria]);
         }
      }
      return $output;
   }


   function warningBeforeReplayRulesOnExistingDB($target) {
      global $LANG, $CFG_GLPI;

      echo "<form name='testrule_form' id='softdictionnary_confirmation' method='post' action=\"" .
             $target . "\">\n";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='b'>" . $LANG['rulesengine'][92] . "</th</tr>\n";
      echo "<tr><td class='tab_bg_2 center'>";
      echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\"></td>";
      echo "<td class='tab_bg_2 center'>" . $LANG['rulesengine'][93] . "</td></tr>\n";
      echo "<tr><th colspan='2' class='b'>" . $LANG['rulesengine'][95] . "</th</tr>\n";
      echo "<tr><td class='tab_bg_2 center'>" . $LANG['rulesengine'][96] . "</td>";
      echo "<td class='tab_bg_2 center'>";
      Dropdown::show('Manufacturer', array('name'=>'manufacturer'));
      echo "</td></tr>\n";

      echo "<tr><td class='tab_bg_2 center' colspan='2'>";
      echo "<input type='submit' name='replay_rule' value=\"".$LANG['buttons'][2]."\" class='submit'>";
      echo "<input type='hidden' name='replay_confirm' value='replay_confirm'";
      echo "</td></tr>";
      echo "</table>\n";
      echo "</div></form>\n";
      return true;
   }


   function replayRulesOnExistingDB($offset=0, $maxtime=0, $items=array(), $params=array ()) {
      global $DB;

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB started : " . date("r") . "\n";
      }
      $nb = 0;
      $i  = $offset;

      if (count($items) == 0) {
         //Select all the differents software
         $sql = "SELECT DISTINCT `glpi_softwares`.`name`,
                        `glpi_manufacturers`.`name` AS manufacturer,
                        `glpi_softwares`.`manufacturers_id` AS manufacturers_id
                 FROM `glpi_softwares`
                 LEFT JOIN `glpi_manufacturers`
                     ON (`glpi_manufacturers`.`id` = `glpi_softwares`.`manufacturers_id`)";

         // Do not replay on trash and templates
         $sql .= "WHERE `glpi_softwares`.`is_deleted` = '0'
                        AND `glpi_softwares`.`is_template` = '0' ";

         if (isset ($params['manufacturer']) && $params['manufacturer'] > 0) {
            $sql .= " AND `manufacturers_id` = '" . $params['manufacturer'] . "'";
         }
         if ($offset) {
            $sql .= " LIMIT " . intval($offset) . ",999999999";
         }

         $res  = $DB->query($sql);
         $nb   = $DB->numrows($res) + $offset;
         $step = ($nb > 1000 ? 50 : ($nb > 20 ? floor($DB->numrows($res) / 20) : 1));

         while ($input = $DB->fetch_array($res)) {
            if (!($i % $step)) {
               if (isCommandLine()) {
                  echo date("H:i:s") . " replayRulesOnExistingDB : $i/$nb (" .
                       round(memory_get_usage() / (1024 * 1024), 2) . " Mo)\n";
               } else {
                  changeProgressBarPosition($i, $nb, "$i / $nb");
               }
            }

            //If manufacturer is set, then first run the manufacturer's dictionnary
            if (isset ($input["manufacturer"])) {
               $input["manufacturer"] = Manufacturer::processName($input["manufacturer"]);
            }

            //Replay software dictionnary rules
            $input    = addslashes_deep($input);
            $res_rule = $this->processAllRules($input, array(), array());
            $res_rule = addslashes_deep($res_rule);

            //If the software's name or version has changed
            if ((isset($res_rule["name"]) && $res_rule["name"] != $input["name"])
                || (isset($res_rule["version"]))
                && $res_rule["version"] != '') {

               $IDs = array ();
               //Find all the softwares in the database with the same name and manufacturer
               $sql = "SELECT `id`
                       FROM `glpi_softwares`
                       WHERE `name` = '" . $input["name"] . "'
                             AND `manufacturers_id` = '" . $input["manufacturers_id"] . "'";
               $res_soft = $DB->query($sql);

               if ($DB->numrows($res_soft) > 0) {
                  //Store all the software's IDs in an array
                  while ($result = $DB->fetch_array($res_soft)) {
                     $IDs[] = $result["id"];
                  }
                  //Replay dictionnary on all the softwares
                  $this->replayDictionnaryOnSoftwaresByID($IDs, $res_rule);
               }
            }
            $i++;
            if ($maxtime) {
               $crt = explode(" ", microtime());
               if ($crt[0] + $crt[1] > $maxtime) {
                  break;
               }
            }
         } // each distinct software

         if (isCommandLine()) {
            echo "replayRulesOnExistingDB : $i/$nb               \n";
         } else {
            changeProgressBarPosition($i, $nb, "$i / $nb");
         }

      } else {
         $this->replayDictionnaryOnSoftwaresByID($items);
         return true;
      }

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB ended : " . date("r") . "\n";
      }

      return ($i == $nb ? -1 : $i);
   }


   /**
    * Replay dictionnary on several softwares
    *
    * @param $IDs array of software IDs to replay
    * @param $res_rule array of rule results
    *
    * @return Query result handler
   **/
   function replayDictionnaryOnSoftwaresByID($IDs, $res_rule=array()) {
      global $DB;

      $new_softs  = array ();
      $delete_ids = array ();

      foreach ($IDs as $ID) {
         $res_soft = $DB->query("SELECT `gs`.`id`,
                                        `gs`.`name` AS name,
                                        `gs`.`entities_id` AS entities_id,
                                        `gm`.`name` AS manufacturer
                                 FROM `glpi_softwares` AS gs
                                 LEFT JOIN `glpi_manufacturers` AS gm
                                       ON (`gs`.`manufacturers_id` = `gm`.`id`)
                                 WHERE `gs`.`is_template` = '0'
                                       AND `gs`.`id` = '$ID'");

         if ($DB->numrows($res_soft)) {
            $soft = $DB->fetch_array($res_soft);
            //For each software
            $this->replayDictionnaryOnOneSoftware($new_softs, $res_rule, $ID, $soft["entities_id"],
                                                  (isset($soft["name"]) ? $soft["name"] : ''),
                                                  (isset($soft["manufacturer"])
                                                         ? $soft["manufacturer"] : ''),
                                                  $delete_ids);
         }
      }
      //Delete software if needed
      $this->putOldSoftsInTrash($delete_ids);
   }


   /**
    * Replay dictionnary on one software
    *
    * @param $new_softs array containing new softwares already computed
    * @param $res_rule array of rule results
    * @param $ID ID of the software
    * @param $entity working entity ID
    * @param $name softwrae name
    * @param $manufacturer manufacturer ID
    * @param $soft_ids array containing replay software need to be trashed
   **/
   function replayDictionnaryOnOneSoftware(& $new_softs, $res_rule, $ID, $entity, $name,
                                           $manufacturer, & $soft_ids) {
      global $DB;

      $input["name"]         = $name;
      $input["manufacturer"] = $manufacturer;
      $input                 = addslashes_deep($input);

      if (empty ($res_rule)) {
         $res_rule = $this->processAllRules($input, array(), array());
         $res_rule = addslashes_deep($res_rule);
      }
     $soft = new Software;

      //Software's name has changed
      if (isset ($res_rule["name"]) && $res_rule["name"] != $name) {
         if (isset ($res_rule["manufacturer"])) {
            $manufacturer = Dropdown::getDropdownName("glpi_manufacturers",
                                                      $res_rule["manufacturer"]);
         }

         //New software not already present in this entity
         if (!isset ($new_softs[$entity][$res_rule["name"]])) {
            // create new software or restore it from trash
            $new_software_id = $soft->addOrRestoreFromTrash($res_rule["name"], $manufacturer,
                                                            $entity);
            $new_softs[$entity][$res_rule["name"]] = $new_software_id;
         } else {
            $new_software_id = $new_softs[$entity][$res_rule["name"]];
         }
         // Move licenses to new software
         $this->moveLicenses($ID, $new_software_id);

      } else {
         $new_software_id = $ID;
         $res_rule["id"]  = $ID;
         if (isset($res_rule["manufacturer"])) {
            $res_rule["manufacturers_id"] = $res_rule["manufacturer"];
            unset($res_rule["manufacturer"]);
         }
         $soft->update($res_rule);
      }

      // Add to software to deleted list
      if ($new_software_id != $ID) {
         $soft_ids[] = $ID;
      }

      //Get all the different versions for a software
      $result = $DB->query("SELECT *
                            FROM `glpi_softwareversions`
                            WHERE `softwares_id` = '$ID'");

      while ($version = $DB->fetch_array($result)) {
         $input["version"] = addslashes($version["name"]);
         $old_version_name = $input["version"];

         if (isset ($res_rule["version"]) && $res_rule["version"] != '') {
            $new_version_name = $res_rule["version"];
         } else {
            $new_version_name = $version["name"];
         }
         if ($ID != $new_software_id
             || $new_version_name != $old_version_name) {
            $this->moveVersions($ID, $new_software_id, $version["id"], $old_version_name,
                                $new_version_name, $entity);
         }
      }
   }


   /**
    * Delete a list of softwares
    *
    * @param $soft_ids array containing replay software need to be trashed
   */
   function putOldSoftsInTrash($soft_ids) {
      global $DB, $LANG;

      if (count($soft_ids) > 0) {
         $ids = implode("','", $soft_ids);

         //Try to delete all the software that are not used anymore (which means that don't have version associated anymore)
         $res_countsoftinstall
            = $DB->query("SELECT `glpi_softwares`.`id`,
                                 COUNT(`glpi_softwareversions`.`softwares_id`) AS `cpt`
                          FROM `glpi_softwares`
                          LEFT JOIN `glpi_softwareversions`
                              ON `glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`
                          WHERE `glpi_softwares`.`id` IN ('$ids')
                                AND `is_deleted` = '0'
                          GROUP BY `glpi_softwares`.`id`
                          HAVING `cpt` = '0'
                          ORDER BY `cpt`");

         $software = new Software;
         while ($soft = $DB->fetch_array($res_countsoftinstall)) {
            $software->putInTrash($soft["id"], $LANG['rulesengine'][87]);
         }
      }
   }


   /**
    * Change software's name, and move versions if needed
    *
    * @param $ID old software ID
    * @param $new_software_id new software ID
    * @param $version_id version ID to move
    * @param $old_version old version name
    * @param $new_version new version name
    * @param $entity entity ID
   */
   function moveVersions($ID, $new_software_id, $version_id, $old_version, $new_version, $entity) {
      global $DB;

      $new_versionID = $this->versionExists($new_software_id, $version_id, $new_version);

      // Do something if it is not the same version
      if ($new_versionID != $version_id) {
         //A version does not exist : update existing one
         if ($new_versionID == -1) {
            //Transfer versions from old software to new software for a specific version
            $DB->query("UPDATE `glpi_softwareversions`
                        SET `name` = '$new_version',
                            `softwares_id` = '$new_software_id'
                        WHERE `id` = '$version_id'");
         } else {
            //Change ID of the version in glpi_computers_softwareversions
            $DB->query("UPDATE `glpi_computers_softwareversions`
                        SET `softwareversions_id` = '$new_versionID'
                        WHERE `softwareversions_id` = '$version_id'");

            // Update licenses version link
            $DB->query("UPDATE `glpi_softwarelicenses`
                        SET `softwareversions_id_buy` = '$new_versionID'
                        WHERE `softwareversions_id_buy` = '$version_id'");

            $DB->query("UPDATE `glpi_softwarelicenses`
                        SET `softwareversions_id_use` = '$new_versionID'
                        WHERE `softwareversions_id_use` = '$version_id'");
            //Delete old version
            $old_version = new SoftwareVersion;
            $old_version->delete(array("id" => $version_id));
         }
      }
   }


   /**
    * Move licenses from a software to another
    *
    * @param $ID old software ID
    * @param $new_software_id new software ID
   **/
   function moveLicenses($ID, $new_software_id) {
      global $DB;

      //Transfer licenses to new software if needed
      if ($ID != $new_software_id) {
         $DB->query("UPDATE `glpi_softwarelicenses`
                     SET `softwares_id` = '$new_software_id'
                     WHERE `softwares_id` = '$ID'");
      }
   }


   /**
    * Check if a version exists
    *
    * @param $software_id software ID
    * @param $version_id version ID to search
    * @param $version version name
   **/
   function versionExists($software_id, $version_id, $version) {
      global $DB;

      //Check if the version exists
      $sql = "SELECT *
              FROM `glpi_softwareversions`
              WHERE `softwares_id` = '$software_id'
                    AND `name` = '$version'";
      $res_version = $DB->query($sql);
      return (!$DB->numrows($res_version) ? -1 : $DB->result($res_version, 0, "id"));
   }

}

?>