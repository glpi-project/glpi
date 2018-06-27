<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class RuleDictionnarySoftwareCollection extends RuleCollection {
   // From RuleCollection

   public $stop_on_first_match = true;
   public $can_replay_rules    = true;
   public $menu_type           = 'dictionnary';
   public $menu_option         = 'software';

   static $rightname           = 'rule_dictionnary_software';

   /**
    * @see RuleCollection::getTitle()
   **/
   function getTitle() {
      //TRANS: software in plural
      return __('Dictionnary of software');
   }


   /**
    * @see RuleCollection::cleanTestOutputCriterias()
   **/
   function cleanTestOutputCriterias(array $output) {

      //If output array contains keys begining with _ : drop it
      foreach ($output as $criteria => $value) {
         if (($criteria[0] == '_') && ($criteria != '_ignore_import')) {
            unset ($output[$criteria]);
         }
      }
      return $output;
   }


   /**
    * @see RuleCollection::warningBeforeReplayRulesOnExistingDB()
   **/
   function warningBeforeReplayRulesOnExistingDB($target) {
      global $CFG_GLPI;

      echo "<form name='testrule_form' id='softdictionnary_confirmation' method='post' action=\"" .
             $target . "\">\n";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='b'>" .
            __('Warning before running rename based on the dictionary rules') . "</th></tr>\n";
      echo "<tr><td class='tab_bg_2 center'>";
      echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\"></td>";
      echo "<td class='tab_bg_2 center'>" .
            __('Warning! This operation can put merged software in the dustbin.<br>Sure to notify your users.').
           "</td></tr>\n";
      echo "<tr><th colspan='2' class='b'>" . __('Manufacturer choice') . "</th></tr>\n";
      echo "<tr><td class='tab_bg_2 center'>" .
            __('Replay dictionary rules for manufacturers (----- = All)') . "</td>";
      echo "<td class='tab_bg_2 center'>";
      Manufacturer::dropdown(['name' => 'manufacturer']);
      echo "</td></tr>\n";

      echo "<tr><td class='tab_bg_2 center' colspan='2'>";
      echo "<input type='submit' name='replay_rule' value=\""._sx('button', 'Post')."\"
             class='submit'>";
      echo "<input type='hidden' name='replay_confirm' value='replay_confirm'>";
      echo "</td></tr>";
      echo "</table>\n";
      echo "</div>\n";
      Html::closeForm();
      return true;
   }


   /**
    * @see RuleCollection::replayRulesOnExistingDB()
   **/
   function replayRulesOnExistingDB($offset = 0, $maxtime = 0, $items = [], $params = []) {
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
                        `glpi_softwares`.`manufacturers_id` AS manufacturers_id,
                        `glpi_softwares`.`entities_id` AS entities_id,
                        `glpi_softwares`.`is_helpdesk_visible` AS helpdesk
                 FROM `glpi_softwares`
                 LEFT JOIN `glpi_manufacturers`
                     ON (`glpi_manufacturers`.`id` = `glpi_softwares`.`manufacturers_id`)";

         // Do not replay on dustbin and templates
         $sql .= "WHERE `glpi_softwares`.`is_deleted` = 0
                        AND `glpi_softwares`.`is_template` = 0 ";

         if (isset($params['manufacturer']) && $params['manufacturer']) {
            $sql .= " AND `glpi_softwares`.`manufacturers_id` = '" . $params['manufacturer'] . "'";
         }
         if ($offset) {
            $sql .= " LIMIT " . intval($offset) . ",999999999";
         }

         $res  = $DB->query($sql);
         $nb   = $DB->numrows($res) + $offset;
         $step = (($nb > 1000) ? 50 : (($nb > 20) ? floor($DB->numrows($res) / 20) : 1));

         while ($input = $DB->fetch_assoc($res)) {
            if (!($i % $step)) {
               if (isCommandLine()) {
                  printf(__('%1$s - replay rules on existing database: %2$s/%3$s (%4$s Mio)')."\n",
                      date("H:i:s"), $i, $nb, round(memory_get_usage() / (1024 * 1024), 2));
               } else {
                  Html::changeProgressBarPosition($i, $nb, "$i / $nb");
               }
            }

            //If manufacturer is set, then first run the manufacturer's dictionnary
            if (isset($input["manufacturer"])) {
               $input["manufacturer"] = Manufacturer::processName(addslashes($input["manufacturer"]));
            }

            //Replay software dictionnary rules
            $res_rule = $this->processAllRules($input, [], []);

            if ((isset($res_rule["name"]) && (strtolower($res_rule["name"]) != strtolower($input["name"])))
                || (isset($res_rule["version"]) && ($res_rule["version"] != ''))
                || (isset($res_rule['new_entities_id'])
                    && ($res_rule['new_entities_id'] != $input['entities_id']))
                || (isset($res_rule['is_helpdesk_visible'])
                    && ($res_rule['is_helpdesk_visible'] != $input['helpdesk']))
                || (isset($res_rule['manufacturer'])
                    && ($res_rule['manufacturer'] != $input['manufacturer']))) {

               $IDs = [];
               //Find all the softwares in the database with the same name and manufacturer
               $sql = "SELECT `id`
                       FROM `glpi_softwares`
                       WHERE `name` = '" . $input["name"] . "'
                             AND `manufacturers_id` = '" . $input["manufacturers_id"] . "'";
               $res_soft = $DB->query($sql);

               if ($DB->numrows($res_soft) > 0) {
                  //Store all the software's IDs in an array
                  while ($result = $DB->fetch_assoc($res_soft)) {
                     $IDs[] = $result["id"];
                  }
                  //Replay dictionnary on all the softwares
                  $this->replayDictionnaryOnSoftwaresByID($IDs, $res_rule);
               }
            }
            $i++;
            if ($maxtime) {
               $crt = explode(" ", microtime());
               if (($crt[0] + $crt[1]) > $maxtime) {
                  break;
               }
            }
         } // each distinct software

         if (isCommandLine()) {
            printf(__('Replay rules on existing database: %1$s/%2$s')."   \n", $i, $nb);
         } else {
            Html::changeProgressBarPosition($i, $nb, "$i / $nb");
         }

      } else {
         $this->replayDictionnaryOnSoftwaresByID($items);
         return true;
      }

      if (isCommandLine()) {
         printf(__('Replay rules on existing database ended on %s')."\n", date("r"));
      }

      return (($i == $nb) ? -1 : $i);
   }


   /**
    * Replay dictionnary on several softwares
    *
    * @param $IDs       array of software IDs to replay
    * @param $res_rule  array of rule results
    *
    * @return Query result handler
   **/
   function replayDictionnaryOnSoftwaresByID(array $IDs, $res_rule = []) {
      global $DB;

      $new_softs  = [];
      $delete_ids = [];

      foreach ($IDs as $ID) {
         $res_soft = $DB->query("SELECT `gs`.`id`,
                                        `gs`.`name` AS name,
                                        `gs`.`entities_id` AS entities_id,
                                        `gm`.`name` AS manufacturer
                                 FROM `glpi_softwares` AS gs
                                 LEFT JOIN `glpi_manufacturers` AS gm
                                       ON (`gs`.`manufacturers_id` = `gm`.`id`)
                                 WHERE `gs`.`is_template` = 0
                                       AND `gs`.`id` = '$ID'");

         if ($DB->numrows($res_soft)) {
            $soft = $DB->fetch_assoc($res_soft);
            //For each software
            $this->replayDictionnaryOnOneSoftware($new_softs, $res_rule, $ID,
                                                  (isset($res_rule['new_entities_id'])
                                                      ?$res_rule['new_entities_id']
                                                      :$soft["entities_id"]),
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
    * @param &$new_softs      array containing new softwares already computed
    * @param $res_rule        array of rule results
    * @param $ID                    ID of the software
    * @param $entity                working entity ID
    * @param $name                  softwrae name
    * @param $manufacturer          manufacturer name
    * @param &$soft_ids       array containing replay software need to be dustbined
   **/
   function replayDictionnaryOnOneSoftware(array &$new_softs, array $res_rule, $ID, $entity, $name,
                                           $manufacturer, array &$soft_ids) {
      global $DB;

      $input["name"]         = $name;
      $input["manufacturer"] = $manufacturer;
      $input["entities_id"]  = $entity;

      if (empty($res_rule)) {
         $res_rule = $this->processAllRules($input, [], []);
      }
      $soft = new Software();
      if (isset($res_rules['_ignore_import']) && ($res_rules['_ignore_import'] == 1)) {
          $soft->putInTrash($ID, __('Software deleted by GLPI dictionary rules'));
          return;
      }

      //Software's name has changed or entity
      if ((isset($res_rule["name"]) && (strtolower($res_rule["name"]) != strtolower($name)))
            //Entity has changed, and new entity is a parent of the current one
          || (!isset($res_rule["name"])
              && isset($res_rule['new_entities_id'])
              && in_array($res_rule['new_entities_id'],
                          getAncestorsOf('glpi_entities', $entity)))) {

         if (isset($res_rule["name"])) {
            $new_name = $res_rule["name"];
         } else {
            $new_name = addslashes($name);
         }

         if (isset($res_rule["manufacturer"]) && $res_rule["manufacturer"]) {
            $manufacturer = $res_rule["manufacturer"];
         } else {
            $manufacturer = addslashes($manufacturer);
         }

         //New software not already present in this entity
         if (!isset($new_softs[$entity][$new_name])) {
            // create new software or restore it from dustbin
            $new_software_id               = $soft->addOrRestoreFromTrash($new_name, $manufacturer,
                                                                          $entity, '', true);
            $new_softs[$entity][$new_name] = $new_software_id;
         } else {
            $new_software_id = $new_softs[$entity][$new_name];
         }
         // Move licenses to new software
         $this->moveLicenses($ID, $new_software_id);

      } else {
         $new_software_id = $ID;
         $res_rule["id"]  = $ID;
         if (isset($res_rule["manufacturer"]) && $res_rule["manufacturer"]) {
            $res_rule["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                                     $res_rule["manufacturer"]);
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

      while ($version = $DB->fetch_assoc($result)) {
         $input["version"] = addslashes($version["name"]);
         $old_version_name = $input["version"];

         if (isset($res_rule['version_append']) && $res_rule['version_append'] != '') {
             $new_version_name = $old_version_name . $res_rule['version_append'];
         } else if (isset($res_rule["version"]) && $res_rule["version"] != '') {
            $new_version_name = $res_rule["version"];
         } else {
            $new_version_name = $version["name"];
         }
         if (($ID != $new_software_id)
             || ($new_version_name != $old_version_name)) {
            $this->moveVersions($ID, $new_software_id, $version["id"], $old_version_name,
                                $new_version_name, $entity);
         }
      }
   }


   /**
    * Delete a list of softwares
    *
    * @param $soft_ids array containing replay software need to be dustbined
   **/
   function putOldSoftsInTrash(array $soft_ids) {
      global $DB;

      if (count($soft_ids) > 0) {

         //Try to delete all the software that are not used anymore
         // (which means that don't have version associated anymore)
         $res_countsoftinstall
            = $DB->query("SELECT `glpi_softwares`.`id`,
                                 COUNT(`glpi_softwareversions`.`softwares_id`) AS `cpt`
                          FROM `glpi_softwares`
                          LEFT JOIN `glpi_softwareversions`
                              ON `glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`
                          WHERE `glpi_softwares`.`id` IN (".implode(",", $soft_ids).")
                                AND `is_deleted` = 0
                          GROUP BY `glpi_softwares`.`id`
                          HAVING `cpt` = 0
                          ORDER BY `cpt`");

         $software = new Software();
         while ($soft = $DB->fetch_assoc($res_countsoftinstall)) {
            $software->putInTrash($soft["id"], __('Software deleted by GLPI dictionary rules'));
         }
      }
   }


   /**
    * Change software's name, and move versions if needed
    *
    * @param $ID                    old software ID
    * @param $new_software_id       new software ID
    * @param $version_id            version ID to move
    * @param $old_version           old version name
    * @param $new_version           new version name
    * @param $entity                entity ID
   */
   function moveVersions($ID, $new_software_id, $version_id, $old_version, $new_version, $entity) {
      global $DB;

      $new_versionID = $this->versionExists($new_software_id, $new_version);

      // Do something if it is not the same version
      if ($new_versionID != $version_id) {
         //A version does not exist : update existing one
         if ($new_versionID == -1) {
            //Transfer versions from old software to new software for a specific version
            $DB->update(
               'glpi_softwareversions', [
                  'name'         => $new_version,
                  'softwares_id' => $new_software_id
               ], [
                  'id' => $version_id
               ]
            );
         } else {
            // Delete software can be in double after update
            $sql = "SELECT gcs_2.*
                    FROM `glpi_computers_softwareversions`
                    LEFT JOIN  `glpi_computers_softwareversions` AS gcs_2
                       ON `glpi_computers_softwareversions`.`computers_id` = gcs_2.`computers_id`
                    WHERE `glpi_computers_softwareversions`.`softwareversions_id` = '$new_versionID'
                          AND gcs_2.`softwareversions_id` = '$version_id'";
            $res = $DB->query($sql);
            if ($DB->numrows($res) > 0) {
               while ($result = $DB->fetch_assoc($res)) {
                  $DB->delete(
                     'glpi_computers_softwareversions', [
                        'id' => $result['id']
                     ]
                  );
               }
            }

            //Change ID of the version in glpi_computers_softwareversions
            $DB->update(
               'glpi_computers_softwareversions', [
                  'softwareversions_id' => $new_versionID
               ], [
                  'softwareversions_id' => $version_id
               ]
            );

            // Update licenses version link
            $DB->update(
               'glpi_softwarelicenses', [
                  'softwareversions_id_buy' => $new_versionID
               ], [
                  'softwareversions_id_buy' => $version_id
               ]
            );

            $DB->update(
               'glpi_softwarelicenses', [
                  'softwareversions_id_use' => $new_versionID
               ], [
                  'softwareversions_id_use' => $version_id
               ]
            );

            //Delete old version
            $old_version = new SoftwareVersion();
            $old_version->delete(["id" => $version_id]);
         }
      }
   }


   /**
    * Move licenses from a software to another
    *
    * @param $old_software_id    old software ID
    * @param $new_software_id    new software ID
    * @return true if move was successful
   **/
   function moveLicenses($old_software_id, $new_software_id) {
      global $DB;

      //Return false if one of the 2 softwares doesn't exists
      if (!countElementsInTable('glpi_softwares', ['id' => $old_software_id])
         || !countElementsInTable('glpi_softwares', ['id' => $new_software_id])) {
         return false;
      }

      //Transfer licenses to new software if needed
      if ($old_software_id != $new_software_id) {
         $DB->update(
            'glpi_softwarelicenses', [
               'softwares_id' => $new_software_id
            ], [
               'softwares_id' => $old_software_id
            ]
         );
      }
      return true;
   }


   /**
    * Check if a version exists
    *
    * @param $software_id  software ID
    * @param $version      version name
   **/
   function versionExists($software_id, $version) {
      global $DB;

      //Check if the version exists
      $iterator = $DB->request([
         'FROM'   => 'glpi_softwareversions',
         'WHERE'  => [
            'softwares_id' => $software_id,
            'name'         => $version
         ]
      ]);
      if (count($iterator)) {
         $current = $iterator->next();
         return $current['id'];
      }
      return -1;
   }

}
