<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Computer_SoftwareVersion extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1 = 'Computer';
   static public $items_id_1 = 'computers_id';
   static public $itemtype_2 = 'SoftwareVersion';
   static public $items_id_2 = 'softwareversions_id';


   static public $log_history_1_add    = Log::HISTORY_INSTALL_SOFTWARE;
   static public $log_history_1_delete = Log::HISTORY_UNINSTALL_SOFTWARE;

   static public $log_history_2_add    = Log::HISTORY_INSTALL_SOFTWARE;
   static public $log_history_2_delete = Log::HISTORY_UNINSTALL_SOFTWARE;


   static function getTypeName($nb=0) {
      return _n('Installation', 'Installations', $nb);
   }


   function prepareInputForAdd($input) {

      if (!isset($input['is_template_computer'])
          || !isset($input['is_deleted_computer'])) {
         // Get template and deleted information from computer
         // If computer set update is_template / is_deleted infos to ensure data validity
         if (isset($input['computers_id'])) {
            $computer = new Computer();
            if ($computer->getFromDB($input['computers_id'])) {
               $input['is_template_computer'] = $computer->getField('is_template');
               $input['is_deleted_computer']  = $computer->getField('is_deleted');
            }
         }
      }
      return parent::prepareInputForAdd($input);
   }


   /**
    * @since version 0.84
   **/
   function prepareInputForUpdate($input) {

      if (!isset($input['is_template_computer'])
          || !isset($input['is_deleted_computer'])) {
         // If computer set update is_template / is_deleted infos to ensure data validity
         if (isset($input['computers_id'])) {
            // Get template and deleted information from computer
            $computer = new Computer();
            if ($computer->getFromDB($input['computers_id'])) {
               $input['is_template_computer'] = $computer->getField('is_template');
               $input['is_deleted_computer']  = $computer->getField('is_deleted');
            }
         }
      }
      return parent::prepareInputForUpdate($input);
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {

      switch ($input['action']) {
         case "move_version" :
            if (isset($input['options'])) {
               $input['options'] = Toolbox::decodeArrayFromInput($input['options']);
               if (isset($input['options']['move'])) {
                  $options = array('softwares_id' => $input['options']['move']['softwares_id']);
                     if (isset($input['options']['move']['used'])) {
                        $options['used'] = $input['options']['move']['used'];
                     }
                     SoftwareVersion::dropdown($options);
                     echo "<br><br><input type='submit' name='massiveaction' value=\"".
                                    _sx('button','Move')."\" class='submit'>&nbsp;";
                  return true;
               }
            }
            return false;

         default :
            return parent::showSpecificMassiveActionsParameters($input);

      }
      return false;
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "move_version" :
            if (isset($input['softwareversions_id'])) {
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {
                     //Get software name and manufacturer
                     if ($this->can($key,'w')) {
                        //Process rules
                        if ($this->update(array('id' => $key,
                                                'softwareversions_id'
                                                     => $input['softwareversions_id']))) {
                           $res['ok']++;
                        } else {
                           $res['ko']++;
                        }
                     } else {
                        $res['noright']++;
                     }
                  }
               }
            } else {
               $res['ko']++;
            }
            break;

         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }


   /**
    * @param $computers_id
   **/
   function updateDatasForComputer($computers_id) {
      global $DB;

      $comp = new Computer();
      if ($comp->getFromDB($computers_id)) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `is_template_computer` = '".$comp->getField('is_template')."',
                       `is_deleted_computer` = '".$comp->getField('is_deleted')."'
                   WHERE `computers_id` = '$computers_id';";

         return $DB->query($query);
      }
      return false;
   }


   /**
    * Get number of installed licenses of a version
    *
    * @param $softwareversions_id   version ID
    * @param $entity                to search for computer in (default '' = all active entities)
    *                               (default '')
    *
    * @return number of installations
   **/
   static function countForVersion($softwareversions_id, $entity='') {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwareversions`.`id`)
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_computers`
                     ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_computers_softwareversions`.`softwareversions_id` = '$softwareversions_id'
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0'
                      AND `glpi_computers_softwareversions`.`is_deleted` = '0'" .
                      getEntitiesRestrictRequest('AND', 'glpi_computers', '', $entity);

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
    *
    * @return number of installations
   **/
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
                      AND `glpi_computers`.`is_template` = '0'
                      AND `glpi_computers_softwareversions`.`is_deleted` = '0'" .
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
    * @param $software Software object
    *
    * @return nothing
   **/
   static function showForSoftware(Software $software) {
      self::showInstallations($software->getField('id'), 'softwares_id');
   }


   /**
    * Show installation of a Version
    *
    * @param $version SoftwareVersion object
    *
    * @return nothing
   **/
   static function showForVersion(SoftwareVersion $version) {
      self::showInstallations($version->getField('id'), 'id');
   }


   /**
    * Show installations of a software
    *
    * @param $searchID  value of the ID to search
    * @param $crit      to search : softwares_id (software) or id (version)
    *
    * @return nothing
   **/
   private static function showInstallations($searchID, $crit) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("software", "r") || !$searchID) {
         return false;
      }

      $canedit         = Session::haveRight("software", "w");
      $canshowcomputer = Session::haveRight("computer", "r");

      if (isset($_POST["start"])) {
         $start = $_POST["start"];
      } else {
         $start = 0;
      }

      if (isset($_POST["order"]) && ($_POST["order"] == "DESC")) {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      if (isset($_POST["sort"]) && !empty($_POST["sort"])) {
         // manage several param like location,compname :  order first
         $tmp  = explode(",",$_POST["sort"]);
         $sort = "`".implode("` $order,`",$tmp)."`";

      } else {
         if ($crit == "softwares_id") {
            $sort = "`entity` $order, `version`, `compname`";
         } else {
            $sort = "`entity` $order, `compname`";
         }
      }

      // Total Number of events
      if ($crit == "softwares_id") {
         // Software ID
         $query_number = "SELECT COUNT(*) AS cpt
                          FROM `glpi_computers_softwareversions`
                          INNER JOIN `glpi_softwareversions`
                              ON (`glpi_computers_softwareversions`.`softwareversions_id`
                                    = `glpi_softwareversions`.`id`)
                          INNER JOIN `glpi_computers`
                              ON (`glpi_computers_softwareversions`.`computers_id`
                                    = `glpi_computers`.`id`)
                          WHERE `glpi_softwareversions`.`softwares_id` = '$searchID'" .
                                getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                                AND `glpi_computers`.`is_deleted` = '0'
                                AND `glpi_computers`.`is_template` = '0'
                                AND `glpi_computers_softwareversions`.`is_deleted` = '0'";

      } else {
         //SoftwareVersion ID
         $query_number = "SELECT COUNT(*) AS cpt
                          FROM `glpi_computers_softwareversions`
                          INNER JOIN `glpi_computers`
                              ON (`glpi_computers_softwareversions`.`computers_id`
                                    = `glpi_computers`.`id`)
                          WHERE `glpi_computers_softwareversions`.`softwareversions_id`
                                       = '$searchID'".
                                getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                                AND `glpi_computers`.`is_deleted` = '0'
                                AND `glpi_computers`.`is_template` = '0'
                                AND `glpi_computers_softwareversions`.`is_deleted` = '0'";
      }

      $number = 0;
      if ($result =$DB->query($query_number)) {
         $number = $DB->result($result,0,0);
      }


      echo "<div class='center'>";
      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table></div>\n";
         return;
      }

      // Display the pager
      Html::printAjaxPager(self::getTypeName(2), $start, $number);

      $query = "SELECT DISTINCT `glpi_computers_softwareversions`.*,
                       `glpi_computers`.`name` AS compname,
                       `glpi_computers`.`id` AS cID,
                       `glpi_computers`.`serial`,
                       `glpi_computers`.`otherserial`,
                       `glpi_users`.`name` AS username,
                       `glpi_users`.`id` AS userid,
                       `glpi_users`.`realname` AS userrealname,
                       `glpi_users`.`firstname` AS userfirstname,
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
                LEFT JOIN `glpi_locations`
                     ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                LEFT JOIN `glpi_states` ON (`glpi_computers`.`states_id` = `glpi_states`.`id`)
                LEFT JOIN `glpi_groups` ON (`glpi_computers`.`groups_id` = `glpi_groups`.`id`)
                LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)
                WHERE (`glpi_softwareversions`.`$crit` = '$searchID') " .
                       getEntitiesRestrictRequest(' AND', 'glpi_computers') ."
                       AND `glpi_computers`.`is_deleted` = '0'
                       AND `glpi_computers`.`is_template` = '0'
                       AND `glpi_computers_softwareversions`.`is_deleted` = '0'
                ORDER BY $sort $order
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      $rand = mt_rand();

      if ($result = $DB->query($query)) {
         if ($data = $DB->fetch_assoc($result)) {
            $softwares_id  = $data['sID'];
            $soft          = new Software();
            $showEntity    = ($soft->getFromDB($softwares_id) && $soft->isRecursive());
            $linkUser      = Session::haveRight('user', 'r');
            $title         = $soft->fields["name"];

            if ($crit == "id") {
               $title = sprintf(__('%1$s - %2$s'), $title, $data["vername"]);
            }

            Session::initNavigateListItems('Computer',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                  Software::getTypeName(1), $title));


            $sort_img = "<img src='".$CFG_GLPI["root_doc"]."/pics/".
                          ($order == "DESC" ? "puce-down.png" : "puce-up.png") . "' alt=''
                          title=''>";


            if ($canedit) {
               $rand = mt_rand();
               Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
               $paramsma = array('num_displayed' => $number,
                                 'specific_actions' => array('move_version'
                                                                     => _x('button', 'Move'),
                                                             'purge' => _x('button',
                                                                           'Delete permanently')));
               // Options to update version
               $paramsma['extraparams']['options']['move']['softwares_id'] = $softwares_id;
               if ($crit=='softwares_id') {
                  $paramsma['extraparams']['options']['move']['used'] = array();
               } else {
                  $paramsma['extraparams']['options']['move']['used'] = array($searchID);
               }

               Html::showMassiveActions(__CLASS__, $paramsma);
            }

            echo "<table class='tab_cadre_fixehov'><tr>";
           if ($canedit) {
               echo "<th width='10'>";
               Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
               echo "</th>";
            }
            if ($crit == "softwares_id") {
               echo "<th>".($sort=="`vername`"?$sort_img:"").
                    "<a href='javascript:reloadTab(\"sort=vername&amp;order=".
                      ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>"._n('Version', 'Versions',2).
                    "</a></th>";
            }
            echo "<th>".($sort=="`compname`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Name')."</a></th>";

            if ($showEntity) {
               echo "<th>".(strstr($sort,"entity")?$sort_img:"").
                    "<a href='javascript:reloadTab(\"sort=entity,compname&amp;order=".
                      ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Entity')."</a></th>";
            }
            echo "<th>".($sort=="`serial`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=serial&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Serial number')."</a></th>";
            echo "<th>".($sort=="`otherserial`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=otherserial&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Inventory number').
                 "</a></th>";
            echo "<th>".(strstr($sort,"`location`")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=location,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Location')."</a></th>";
            echo "<th>".(strstr($sort,"state")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=state,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Status')."</a></th>";
            echo "<th>".(strstr($sort,"groupe")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=groupe,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('Group')."</a></th>";
            echo "<th>".(strstr($sort,"username")?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=username,compname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".__('User')."</a></th>";
            echo "<th>".($sort=="`lname`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=lname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>"._n('License', 'Licenses', 2).
                 "</a></th>";
            echo "</tr>\n";

            do {
               Session::addToNavigateListItems('Computer',$data["cID"]);

               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                  echo "</td>";
               }

               if ($crit == "softwares_id") {
                  echo "<td><a href='softwareversion.form.php?id=".$data['vID']."'>".
                        $data['version']."</a></td>";
               }

               $compname = $data['compname'];
               if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
                  $compname = sprintf(__('%1$s (%2$s)'), $compname, $data['cID']);
               }

               if ($canshowcomputer) {
                  echo "<td><a href='computer.form.php?id=".$data['cID']."'>$compname</a></td>";
               } else {
                  echo "<td>".$compname."</td>";
               }

               if ($showEntity) {
                  echo "<td>".$data['entity']."</td>";
               }
               echo "<td>".$data['serial']."</td>";
               echo "<td>".$data['otherserial']."</td>";
               echo "<td>".$data['location']."</td>";
               echo "<td>".$data['state']."</td>";
               echo "<td>".$data['groupe']."</td>";
               echo "<td>".formatUserName($data['userid'], $data['username'], $data['userrealname'],
                                          $data['userfirstname'], $linkUser)."</td>";

               $lics = Computer_SoftwareLicense::GetLicenseForInstallation($data['cID'],
                                                                           $data['vID']);
               echo "<td>";

               if (count($lics)) {
                  foreach ($lics as $data) {
                     $serial = $data['serial'];

                     if (!empty($data['type'])) {
                        $serial = sprintf(__('%1$s (%2$s)'), $serial, $data['type']);
                     }

                     echo "<a href='softwarelicense.form.php?id=".$data['id']."'>".$data['name'];
                     echo "</a> - ".$serial;

                     echo "<br>";
                  }
               }
               echo "</td>";
               echo "</tr>\n";

            } while ($data = $DB->fetch_assoc($result));

            echo "</table>\n";
            if ($canedit) {
               $paramsma['ontop'] =false;
               Html::showMassiveActions(__CLASS__, $paramsma);
               Html::closeForm();
            }

         } else { // Not found
            _e('No item found');
         }
      } // Query
      Html::printAjaxPager(self::getTypeName(2), $start, $number);

      echo "</div>\n";
   }


   /**
    * Show number of installations per entity
    *
    * @param $version SoftwareVersion object
    *
    * @return nothing
   **/
   static function showForVersionByEntity(SoftwareVersion $version) {
      global $DB, $CFG_GLPI;

      $softwareversions_id = $version->getField('id');

      if (!Session::haveRight("software", "r") || !$softwareversions_id) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".self::getTypeName(2)."</th>";
      echo "</tr>\n";

      $tot = 0;

      $sql = "SELECT `id`, `completename`
              FROM `glpi_entities` " .
              getEntitiesRestrictRequest('WHERE', 'glpi_entities') ."
              ORDER BY `completename`";

      foreach ($DB->request($sql) as $ID => $data) {
         $nb = self::countForVersion($softwareversions_id,$ID);
         if ($nb > 0) {
            echo "<tr class='tab_bg_2'><td>" . $data["completename"] . "</td>";
            echo "<td class='numeric'>".$nb."</td></tr>\n";
            $tot += $nb;
         }
      }

      if ($tot > 0) {
         echo "<tr class='tab_bg_1'><td class='center b'>".__('Total')."</td>";
         echo "<td class='numeric b'>".$tot."</td></tr>\n";
      } else {
         echo "<tr class='tab_bg_1'><td colspan='2 b'>" . __('No item found') . "</td></tr>\n";
      }
      echo "</table></div>";
   }


   /**
    * Show software installed on a computer
    *
    * @param $comp            Computer object
    * @param $withtemplate    template case of the view process (default '')
    *
    * @return nothing
   **/
   static function showForComputer(Computer $comp, $withtemplate='') {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("software", "r")) {
         return false;
      }

      $computers_id = $comp->getField('id');
      $rand         = mt_rand();
      $canedit      = Session::haveRight("software", "w");
      $entities_id  = $comp->fields["entities_id"];

      $add_dynamic = '';
      if (Plugin::haveImport()) {
         $add_dynamic = "`glpi_computers_softwareversions`.`is_dynamic`,";
      }

      $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_computers_softwareversions`.`id`,
                       $add_dynamic
                       `glpi_states`.`name` AS state,
                       `glpi_softwareversions`.`id` AS verid,
                       `glpi_softwareversions`.`softwares_id`,
                       `glpi_softwareversions`.`name` AS version
                FROM `glpi_computers_softwareversions`
                LEFT JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`)
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                WHERE `glpi_computers_softwareversions`.`computers_id` = '$computers_id'
                      AND `glpi_computers_softwareversions`.`is_deleted` = '0'
                ORDER BY `softwarecategories_id`, `softname`, `version`";
      $result = $DB->query($query);
      $i      = 0;


      if ((empty($withtemplate) || ($withtemplate != 2))
          && $canedit) {
         echo "<form method='post' action='".
                $CFG_GLPI["root_doc"]."/front/computer_softwareversion.form.php'>";
         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><td class='center'>";
         echo _n('Software', 'Software', 2)."&nbsp;&nbsp;";
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
         Software::dropdownSoftwareToInstall("softwareversions_id", $entities_id);
         echo "</td><td width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Install')."\"
                class='submit'>";
         echo "</td>";
         echo "</tr>\n";
         echo "</table></div>\n";
         Html::closeForm();
      }
      echo "<div class='spaced'>";

      $cat = -1;

      Session::initNavigateListItems('Software',
                           //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'),
                                             Computer::getTypeName(1), $comp->getName()));
      Session::initNavigateListItems('SoftwareLicense',
                           //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'),
                                             Computer::getTypeName(1), $comp->getName()));

      $installed = array();
      if ($number = $DB->numrows($result)) {
         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $paramsma = array('num_displayed'    => $number,
                              'specific_actions' => array('purge' => _x('button',
                                                                        'Delete permanently')));

            Html::showMassiveActions(__CLASS__, $paramsma);
         }
         echo "<table class='tab_cadre_fixe'>";
         while ($data = $DB->fetch_assoc($result)) {
            if ($data["softwarecategories_id"] != $cat) {
               self::displayCategoryFooter($cat, $rand, $canedit);
               $cat = self::displayCategoryHeader($computers_id, $data, $rand, $canedit);
            }

            $licids = self::displaySoftsByCategory($data, $computers_id, $withtemplate, $canedit);
            Session::addToNavigateListItems('Software', $data["softwares_id"]);

            foreach ($licids as $licid) {
               Session::addToNavigateListItems('SoftwareLicense', $licid);
               $installed[] = $licid;
            }
         }
         self::displayCategoryFooter($cat, $rand, $canedit);
         echo "</table>";
         if ($canedit) {
            $paramsma['ontop'] =false;
            Html::showMassiveActions(__CLASS__, $paramsma);
            Html::closeForm();
         }
      }
      echo "</div>\n";
      if ((empty($withtemplate) || ($withtemplate != 2))
          && $canedit) {
         echo "<form method='post' action='".$CFG_GLPI["root_doc"].
                "/front/computer_softwarelicense.form.php'>";
         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>";
         echo _n('License', 'Licenses', 2)."&nbsp;&nbsp;";
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
         Software::dropdownLicenseToInstall("softwarelicenses_id", $entities_id);
         echo "</td><td width='20%'>";
         echo "<input type='submit' name='add' value=\"" ._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table></div>\n";
         Html::closeForm();
      }
      echo "<div class='spaced'>";
      // Affected licenses NOT installed
      $query = "SELECT `glpi_softwarelicenses`.*,
                       `glpi_computers_softwarelicenses`.`id` AS linkID,
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
                      ON (`glpi_softwarelicenses`.`softwareversions_id_use`
                              = `glpi_softwareversions`.`id`
                           OR (`glpi_softwarelicenses`.`softwareversions_id_use` = '0'
                               AND `glpi_softwarelicenses`.`softwareversions_id_buy`
                                       = `glpi_softwareversions`.`id`))
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$computers_id'
                      AND `glpi_computers_softwarelicenses`.`is_deleted` = '0'";

      if (count($installed)) {
         $query .= " AND `glpi_softwarelicenses`.`id` NOT IN (".implode(',',$installed).")";
      }

      $req = $DB->request($query);
      if ($number = $req->numrows()) {
         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('massSoftwareLicense'.$rand);
            $actions = array('install' => _x('button', 'Install'));
            if (SoftwareLicense::canUpdate()) {
               $actions['purge'] = _x('button', 'Delete permanently');
            }
            $paramsma = array('num_displayed'    => $number,
                              'specific_actions' => $actions);

            Html::showMassiveActions('Computer_SoftwareLicense', $paramsma);
            echo "<input type='hidden' name='computers_id' value='$computers_id'>";
         }
         echo "<table class='tab_cadre_fixe'>";
         $cat = true;
         foreach ($req as $data) {
            if ($cat) {
               self::displayCategoryHeader($computers_id, $data, $rand, $canedit);
               $cat = false;
            }
            self::displaySoftsByLicense($data, $computers_id, $withtemplate, $canedit);
            Session::addToNavigateListItems('SoftwareLicense', $data["id"]);
         }
         self::displayCategoryFooter(NULL, $rand, $canedit);
         echo "</table>";
         if ($canedit) {
            $paramsma['ontop'] = false;
            Html::showMassiveActions('Computer_SoftwareLicense', $paramsma);
            Html::closeForm();
         }
      }

      echo "</div>\n";

   }


   /**
    * Display category footer for Computer_SoftwareVersion::showForComputer function
    *
    * @param $cat                current category ID
    * @param $rand               random for unicity / no more used
    * @param $canedit   boolean / no more used
    *
    * @return new category ID
   **/
   private static function displayCategoryFooter($cat, $rand, $canedit) {

      // Close old one
      if ($cat != -1) {
         echo "</table>";

         echo "</div></td></tr>";
      }
   }


   /**
    * Display category header for Computer_SoftwareVersion::showForComputer function
    *
    * @param $computers_ID             ID of the computer
    * @param $data                     data used to display
    * @param $rand                     random for unicity
    * @param $canedit         boolean
    *
    * @return new category ID
   **/
   private static function displayCategoryHeader($computers_ID, $data, $rand, $canedit) {
      global $CFG_GLPI;

      $display = "none";

      if (isset($data["softwarecategories_id"])) {
         $cat = $data["softwarecategories_id"];

         if ($cat) {
            // Categorized
            $catname = Dropdown::getDropdownName('glpi_softwarecategories', $cat);
            $display = $_SESSION["glpiis_categorized_soft_expanded"];
         } else {
            // Not categorized
            $catname = __('Uncategorized software');
            $display = $_SESSION["glpiis_not_categorized_soft_expanded"];
         }

      } else {
         // Not installed
         $cat     = '';
         $catname = __('Affected licenses of not installed software');
         $display = true;
      }

      echo "<tr class='tab_bg_2'><td class='center' colspan='5'>";
      echo "<a href=\"javascript:showHideDiv('softcat$cat$rand','imgcat$cat','" . $CFG_GLPI['root_doc'] .
             "/pics/folder.png','" . $CFG_GLPI['root_doc'] . "/pics/folder-open.png');\">";
      echo "<img alt='' name='imgcat$cat' src='".$CFG_GLPI['root_doc']."/pics/folder".
             (!$display ? '' : "-open") . ".png'>&nbsp;<span class='b'>". $catname. "</span>";
      echo "</a></td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='5'>";
      echo "<div class='center' id='softcat$cat$rand' ".(!$display ?"style=\"display:none;\"" :'').">";

      echo "<table class='tab_cadre_fixe'><tr>";
      if ($canedit) {
         echo "<th width='10'>";
         Html::checkAllAsCheckbox("softcat$cat$rand");
         echo "</th>";
      }
      echo "<th>" . __('Name') . "</th><th>" . __('Status') . "</th>";
      echo "<th>" .__('Version')."</th><th>" . __('License') . "</th>";
      if (isset($data['is_dynamic'])) {
         echo "<th>".__('Automatic inventory')."</th>";
      }
      echo "</tr>\n";
      return $cat;
   }


   /**
    * Display a installed software for a category
    *
    * @param $data                     data used to display
    * @param $computers_id             ID of the computer
    * @param $withtemplate             template case of the view process
    * @param $canedit         boolean  user can edit software ?
    *
    * @return array of found license id
   **/
   private static function displaySoftsByCategory($data, $computers_id, $withtemplate, $canedit) {
      global $DB, $CFG_GLPI;

      $ID       = $data["id"];
      $verid    = $data["verid"];
      $multiple = false;

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td>";
         Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
         echo "</td>";
      }
      echo "<td class='center b'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/software.form.php?id=".$data['softwares_id']."'>";
      echo ($_SESSION["glpiis_ids_visible"] ? sprintf(__('%1$s (%2$s)'),
                                                      $data["softname"], $data['softwares_id'])
                                            : $data["softname"]);
      echo "</a></td>";
      echo "<td>" . $data["state"] . "</td>";

      echo "<td>" . $data["version"];
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
         $licids[]  = $licdata['id'];
         $licserial = $licdata['serial'];

         if (!empty($licdata['type'])) {
            $licserial = sprintf(__('%1$s (%2$s)'), $licserial, $licdata['type']);
         }

         echo "<span class='b'>". $licdata['name']. "</span> - ".$licserial;

         $link_item = Toolbox::getItemTypeFormURL('SoftwareLicense');
         $link      = $link_item."?id=".$licdata['id'];
         $comment   = "<table><tr><td>".__('Name')."</td><td>".$licdata['name']."</td></tr>".
                        "<tr><td>".__('Serial number')."</td><td>".$licdata['serial']."</td></tr>".
                        "<tr><td>". __('Comments').'</td><td>'.$licdata['comment'].'</td></tr>".
                      "</table>';

         Html::showToolTip($comment, array('link' => $link));
         echo "<br>";
      }

      if (!count($licids)) {
         echo "&nbsp;";
      }

      echo "</td>";

      echo "</td>";
      if (isset($data['is_dynamic'])) {
         echo "<td class='center'>";
         echo Dropdown::getYesNo($data['is_dynamic']);
         echo "</td>";
      }

      echo "</tr>\n";

      return $licids;
   }


   /**
    * Display a software for a License (not installed)
    *
    * @param $data                  data used to display
    * @param $computers_id          ID of the computer
    * @param $withtemplate          template case of the view process
    * @param $canedit      boolean  user can edit software ?
    *
    * @return nothing
   */
   private static function displaySoftsByLicense($data, $computers_id, $withtemplate, $canedit) {
      global $CFG_GLPI;
      $version = 0;
      if ($data["softwareversions_id_use"]>0) {
         $version = $data["softwareversions_id_use"];
      } else {
         $version = $data["softwareversions_id_buy"];
      }

      $ID = $data['linkID'];

      $multiple  = false;
      $link_item = Toolbox::getItemTypeFormURL('SoftwareLicense');
      $link      = $link_item."?id=".$data['id'];

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td>";
         if ((empty($withtemplate) || ($withtemplate != 2))
             && ($version > 0)) {
            Html::showMassiveActionCheckBox('Computer_SoftwareLicense', $ID);
         }
         echo "</td>";
      }

      echo "<td class='center b'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/software.form.php?id=".$data['softwares_id']."'>";
      echo ($_SESSION["glpiis_ids_visible"] ? sprintf(__('%1$s (%2$s)'),
                                                      $data["softname"], $data['softwares_id'])
                                            : $data["softname"]);
      echo "</a></td>";
      echo "<td>" . $data["state"] . "</td>";

      echo "<td>" . $data["version"];

      $serial = $data["serial"];

      if ($data["softwarelicensetypes_id"]) {
         $serial = sprintf(__('%1$s (%2$s)'), $serial,
                           Dropdown::getDropdownName("glpi_softwarelicensetypes",
                                                     $data["softwarelicensetypes_id"]));
      }
      echo "</td></td><td class='b'>" .$data["name"]." - ". $serial;

      $comment = "<table><tr><td>".__('Name')."</td>"."<td>".$data['name']."</td></tr>".
                 "<tr><td>".__('Serial number')."</td><td>".$data['serial']."</td></tr>".
                 "<tr><td>". __('Comments')."</td><td>".$data['comment']."</td></tr></table>";

      Html::showToolTip($comment, array('link' => $link));
      echo "</td></tr>\n";
   }

   /**
    * Update version installed on a computer
    *
    * @param $instID                ID of the install software lienk
    * @param $softwareversions_id   ID of the new version
    * @param $dohistory             Do history ? (default 1)
    *
    * @return nothing
   **/
   function upgrade($instID, $softwareversions_id, $dohistory=1) {

      if ($this->getFromDB($instID)) {
         $computers_id = $this->fields['computers_id'];
         $this->delete(array('id' => $instID));
         $this->add(array('computers_id'        => $computers_id,
                          'softwareversions_id' => $softwareversions_id));
      }
   }


   /**
    * Duplicate all software from a computer template to its clone
    *
    * @param $oldid ID of the computer to clone
    * @param $newid ID of the computer cloned
   **/
   static function cloneComputer($oldid, $newid) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$oldid'";

      foreach ($DB->request($query) as $data) {
         $csv                  = new self();
         unset($data['id']);
         $data['computers_id'] = $newid;
         $data['_no_history']  = true;

         $csv->add($data);
      }
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
  function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Software' :
            if (!$withtemplate) {
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              self::countForSoftware($item->getID()));
               }
               return self::getTypeName(2);
            }
            break;

         case 'SoftwareVersion' :
            if (!$withtemplate) {
               $nb = 0;
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForVersion($item->getID());
               }
               return array(1 => SoftwareVersion::getTypeName(1),
                            2 => self::createTabEntry(self::getTypeName(2), $nb));
            }
            break;

         case 'Computer' :
            // Installation allowed for template
            if (Session::haveRight("software","r")) {
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(Software::getTypeName(2),
                                              countElementsInTable('glpi_computers_softwareversions',
                                                                   "computers_id = '".$item->getID()."'
                                                                      AND `is_deleted`='0'"));
               }
               return Software::getTypeName(2);
            }
            break;
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Software') {
         self::showForSoftware($item);

      } else if ($item->getType()=='Computer') {
         self::showForComputer($item, $withtemplate);

      } else if ($item->getType()=='SoftwareVersion') {
         switch ($tabnum) {
            case 1 :
               self::showForVersionByEntity($item);
               break;

            case 2 :
               self::showForVersion($item);
               break;
         }
      }
      return true;
   }

}
?>
