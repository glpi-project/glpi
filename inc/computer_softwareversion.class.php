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


   static function getTypeName($nb = 0) {
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
    * @since 0.84
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
    * @since 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'add' :
            Software::dropdownSoftwareToInstall('peer_softwareversions_id',
                                                $_SESSION["glpiactive_entity"]);
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction'])."</span>";
            return true;

         case 'move_version' :
            $input = $ma->getInput();
            if (isset($input['options'])) {
               if (isset($input['options']['move'])) {
                  $options = ['softwares_id' => $input['options']['move']['softwares_id']];
                  if (isset($input['options']['move']['used'])) {
                     $options['used'] = $input['options']['move']['used'];
                  }
                  SoftwareVersion::dropdownForOneSoftware($options);
                  echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                  return true;
               }
            }
            return false;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;

      switch ($ma->getAction()) {
         case 'move_version' :
            $input = $ma->getInput();
            if (isset($input['softwareversions_id'])) {
               foreach ($ids as $id) {
                  if ($item->can($id, UPDATE)) {
                     //Process rules
                     if ($item->update(['id' => $id,
                                             'softwareversions_id'
                                                  => $input['softwareversions_id']])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;

         case 'add' :
            $itemtoadd = new Computer_SoftwareVersion();
            if (isset($_POST['peer_softwareversions_id'])) {
               foreach ($ids as $id) {
                  if ($item->can($id, UPDATE)) {
                     //Process rules
                     if ($itemtoadd->add(['computers_id' => $id,
                                               'softwareversions_id'
                                                              => $_POST['peer_softwareversions_id']])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($itemtoadd->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($itemtoadd->getErrorMessage(ERROR_RIGHT));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;

      }

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * @param $computers_id
   **/
   function updateDatasForComputer($computers_id) {
      global $DB;

      $comp = new Computer();
      if ($comp->getFromDB($computers_id)) {
         $result = $DB->update(
            $this->getTable(), [
               'is_template_computer'  => $comp->getField('is_template'),
               'is_deleted_computer'   => $comp->getField('is_deleted')
            ], [
               'computers_id' => $computers_id
            ]
         );
         return $result;
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
   static function countForVersion($softwareversions_id, $entity = '') {
      global $DB;

      $request = [
         'FROM'         => 'glpi_computers_softwareversions',
         'COUNT'        => 'cpt',
         'INNER JOIN'   => [
            'glpi_computers'  => [
               'FKEY'   => [
                  'glpi_computers'                    => 'id',
                  'glpi_computers_softwareversions'   => 'computers_id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_computers_softwareversions.softwareversions_id' => $softwareversions_id,
            'glpi_computers.is_deleted'                           => 0,
            'glpi_computers.is_template'                          => 0,
            'glpi_computers_softwareversions.is_deleted'          => 0
         ] + getEntitiesRestrictCriteria('glpi_computers', '', $entity)
      ];
      $result = $DB->request($request)->next();
      return $result['cpt'];
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

      $request = [
         'FROM'         => 'glpi_softwareversions',
         'COUNT'        => 'cpt',
         'INNER JOIN'   => [
            'glpi_computers_softwareversions'   => [
               'FKEY'   => [
                  'glpi_computers_softwareversions'   => 'softwareversions_id',
                  'glpi_softwareversions'             => 'id'
               ]
            ],
            'glpi_computers'  => [
               'FKEY'   => [
                  'glpi_computers_softwareversions'   => 'computers_id',
                  'glpi_computers'                    => 'id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_softwareversions.softwares_id'         => $softwares_id,
            'glpi_computers.is_deleted'                  => 0,
            'glpi_computers.is_template'                 => 0,
            'glpi_computers_softwareversions.is_deleted' => 0
         ] + getEntitiesRestrictCriteria('glpi_computers')
      ];
      $results = $DB->request($request);
      $result = $DB->request($request)->next();
      return $result['cpt'];
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

      if (!Software::canView() || !$searchID) {
         return false;
      }

      $canedit         = Session::haveRightsOr("software", [CREATE, UPDATE, DELETE, PURGE]);
      $canshowcomputer = Computer::canView();

      $refcolumns = ['vername'           => _n('Version', 'Versions', Session::getPluralNumber()),
                          'compname'          => __('Name'),
                          'entity'            => __('Entity'),
                          'serial'            => __('Serial number'),
                          'otherserial'       => __('Inventory number'),
                          'location,compname' => __('Location'),
                          'state,compname'    => __('Status'),
                          'groupe,compname'   => __('Group'),
                          'username,compname' => __('User'),
                          'lname'             => _n('License', 'Licenses', Session::getPluralNumber()),
                          'date_install'      => __('Installation date')];
      if ($crit != "softwares_id") {
         unset($refcolumns['vername']);
      }

      if (isset($_GET["start"])) {
         $start = $_GET["start"];
      } else {
         $start = 0;
      }

      if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      if (isset($_GET["sort"]) && !empty($_GET["sort"]) && isset($refcolumns[$_GET["sort"]])) {
         // manage several param like location,compname :  order first
         $tmp  = explode(",", $_GET["sort"]);
         $sort = "`".implode("` $order,`", $tmp)."`";

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
         $number = self::countForSoftware($searchID);
      } else {
         //SoftwareVersion ID
         $number = self::countForVersion($searchID);
      }

      echo "<div class='center'>";
      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table></div>\n";
         return;
      }

      // Display the pager
      Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);

      //needs DB::request() to support aliases to get migrated
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
                       AND `glpi_computers`.`is_deleted` = 0
                       AND `glpi_computers`.`is_template` = 0
                       AND `glpi_computers_softwareversions`.`is_deleted` = 0
                ORDER BY $sort $order
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      $rand = mt_rand();

      if ($result = $DB->query($query)) {
         if ($data = $DB->fetch_assoc($result)) {
            $softwares_id  = $data['sID'];
            $soft          = new Software();
            $showEntity    = ($soft->getFromDB($softwares_id) && $soft->isRecursive());
            $linkUser      = User::canView();
            $title         = $soft->fields["name"];

            if ($crit == "id") {
               $title = sprintf(__('%1$s - %2$s'), $title, $data["vername"]);
            }

            Session::initNavigateListItems('Computer',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                  Software::getTypeName(1), $title));

            if ($canedit) {
               $rand = mt_rand();
               Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
               $massiveactionparams
                  = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $number),
                          'container'
                           => 'mass'.__CLASS__.$rand,
                          'specific_actions'
                           => [__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'move_version'
                                          => _x('button', 'Move'),
                                    'purge' => _x('button', 'Delete permanently')]];
               // Options to update version
               $massiveactionparams['extraparams']['options']['move']['softwares_id'] = $softwares_id;
               if ($crit=='softwares_id') {
                  $massiveactionparams['extraparams']['options']['move']['used'] = [];
               } else {
                  $massiveactionparams['extraparams']['options']['move']['used'] = [$searchID];
               }

               Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
               $header_begin  .= "<th width='10'>";
               $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
               $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
               $header_end    .= "</th>";
            }
            $columns = $refcolumns;
            if (!$showEntity) {
               unset($columns['entity']);
            }

            foreach ($columns as $key => $val) {
               // Non order column
               if ($key[0] == '_') {
                  $header_end .= "<th>$val</th>";
               } else {
                  $header_end .= "<th".($sort == "`$key`" ? " class='order_$order'" : '').">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                           (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
               }
            }

            $header_end .= "</tr>\n";
            echo $header_begin.$header_top.$header_end;

            do {
               Session::addToNavigateListItems('Computer', $data["cID"]);

               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                  echo "</td>";
               }

               if ($crit == "softwares_id") {
                  echo "<td><a href='".SoftwareVersion::getFormURLWithID($data['vID'])."'>".
                        $data['version']."</a></td>";
               }

               $compname = $data['compname'];
               if (empty($compname) || $_SESSION['glpiis_ids_visible']) {
                  $compname = sprintf(__('%1$s (%2$s)'), $compname, $data['cID']);
               }

               if ($canshowcomputer) {
                  echo "<td><a href='".Computer::getFormURLWithID($data['cID'])."'>$compname</a></td>";
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

               $lics = Computer_SoftwareLicense::getLicenseForInstallation($data['cID'],
                                                                           $data['vID']);
               echo "<td>";

               if (count($lics)) {
                  foreach ($lics as $lic) {
                     $serial = $lic['serial'];

                     if (!empty($lic['type'])) {
                        $serial = sprintf(__('%1$s (%2$s)'), $serial, $lic['type']);
                     }

                     echo "<a href='".SoftwareLicense::getFormURLWithID($lic['id'])."'>".$lic['name'];
                     echo "</a> - ".$serial;

                     echo "<br>";
                  }
               }
               echo "</td>";

               echo "<td>".Html::convDate($data['date_install'])."</td>";
               echo "</tr>\n";

            } while ($data = $DB->fetch_assoc($result));

            echo $header_begin.$header_bottom.$header_end;

            echo "</table>\n";
            if ($canedit) {
               $massiveactionparams['ontop'] =false;
               Html::showMassiveActions($massiveactionparams);
               Html::closeForm();
            }

         } else { // Not found
            echo __('No item found');
         }
      } // Query
      Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);

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

      if (!Software::canView() || !$softwareversions_id) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".self::getTypeName(Session::getPluralNumber())."</th>";
      echo "</tr>\n";

      $tot = 0;

      $iterator = $DB->request([
         'SELECT' => ['id', 'completename'],
         'FROM'   => 'glpi_entities',
         'WHERE'  => getEntitiesRestrictCriteria('glpi_entities'),
         'ORDER'  => ['completename']
      ]);

      while ($data = $iterator->next()) {
         $nb = self::countForVersion($softwareversions_id, $data['id']);
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
    * @param $withtemplate    template case of the view process (default 0)
    *
    * @return nothing
   **/
   static function showForComputer(Computer $comp, $withtemplate = 0) {
      global $DB, $CFG_GLPI;

      if (!Software::canView()) {
         return false;
      }

      $computers_id = $comp->getField('id');
      $rand         = mt_rand();
      $canedit      = Session::haveRightsOr("software", [CREATE, UPDATE, DELETE, PURGE]);
      $entities_id  = $comp->fields["entities_id"];

      $crit         = Session::getSavedOption(__CLASS__, 'criterion', -1);

      $where        = '';
      if ($crit > -1) {
         $where = " AND `glpi_softwares`.`softwarecategories_id` = ". (int) $crit;
      }

      $add_dynamic  = '';
      if (Plugin::haveImport()) {
         $add_dynamic = "`glpi_computers_softwareversions`.`is_dynamic`,";
      }

      //needs DB::request() to support aliases to get migrated
      $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                       `glpi_softwares`.`name` AS softname,
                       `glpi_computers_softwareversions`.`id`,
                       $add_dynamic
                       `glpi_states`.`name` AS state,
                       `glpi_softwareversions`.`id` AS verid,
                       `glpi_softwareversions`.`softwares_id`,
                       `glpi_softwareversions`.`name` AS version,
                       `glpi_softwares`.`is_valid` AS softvalid,
                       `glpi_computers_softwareversions`.`date_install` AS dateinstall
                FROM `glpi_computers_softwareversions`
                LEFT JOIN `glpi_softwareversions`
                     ON (`glpi_computers_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`)
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                WHERE `glpi_computers_softwareversions`.`computers_id` = '$computers_id'
                      AND `glpi_computers_softwareversions`.`is_deleted` = 0
                      $where
                ORDER BY `softname`, `version`";
      $result = $DB->query($query);
      $i      = 0;

      if ((empty($withtemplate) || ($withtemplate != 2))
          && $canedit) {
         echo "<form method='post' action='".Computer_SoftwareVersion::getFormURL()."'>";
         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><td class='center'>";
         echo _n('Software', 'Software', Session::getPluralNumber())."&nbsp;&nbsp;";
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

      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".Software::getTypeName(Session::getPluralNumber())."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo __('Category')."</td><td>";
      SoftwareCategory::dropdown(['value'      => $crit,
                                       'toadd'      => ['-1' =>  __('All categories')],
                                       'emptylabel' => __('Uncategorized software'),
                                       'on_change'  => 'reloadTab("start=0&criterion="+this.value)']);
      echo "</td></tr></table></div>";
      $number = $DB->numrows($result);
      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }

      $installed = [];

      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager('', $start, $number);

         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = ['num_displayed'
                         => min($_SESSION['glpilist_limit'], $number),
                       'container'
                         => 'mass'.__CLASS__.$rand,
                       'specific_actions'
                         => ['purge' => _x('button', 'Delete permanently')]];

            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";

         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>" . __('Name') . "</th><th>" . __('Status') . "</th>";
         $header_end .= "<th>" .__('Version')."</th><th>" . __('License') . "</th>";
         $header_end .="<th>" . __('Installation date') . "</th>";
         if (Plugin::haveImport()) {
            $header_end .= "<th>".__('Automatic inventory')."</th>";
         }
         $header_end .= "<th>".SoftwareCategory::getTypeName(1)."</th>";
         $header_end .= "<th>".__('Valid license')."</th>";
         $header_end .= "</tr>\n";
         echo $header_begin.$header_top.$header_end;

         for ($row=0; $data=$DB->fetch_assoc($result); $row++) {

            if (($row >= $start) && ($row < ($start + $_SESSION['glpilist_limit']))) {
               $licids = self::softsByCategory($data, $computers_id, $withtemplate,
                                               $canedit, true);
            } else {
               $licids = self::softsByCategory($data, $computers_id, $withtemplate,
                                               $canedit, false);
            }
            Session::addToNavigateListItems('Software', $data["softwares_id"]);

            foreach ($licids as $licid) {
               Session::addToNavigateListItems('SoftwareLicense', $licid);
               $installed[] = $licid;
            }
         }

         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] =false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
      echo "</div>\n";
      if ((empty($withtemplate) || ($withtemplate != 2))
          && $canedit) {
         echo "<form method='post' action='".Computer_SoftwareLicense::getFormURL()."'>";
         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".SoftwareLicense::getTypeName(Session::getPluralNumber())."</th></tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>";
         echo _n('License', 'Licenses', Session::getPluralNumber())."&nbsp;&nbsp;";
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
      //needs DB::request() to support aliases to get migrated
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
                           OR (`glpi_softwarelicenses`.`softwareversions_id_use` = 0
                               AND `glpi_softwarelicenses`.`softwareversions_id_buy`
                                       = `glpi_softwareversions`.`id`))
                LEFT JOIN `glpi_states`
                     ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                WHERE `glpi_computers_softwarelicenses`.`computers_id` = '$computers_id'
                      AND `glpi_computers_softwarelicenses`.`is_deleted` = 0
                      $where";

      if (count($installed)) {
         $query .= " AND `glpi_softwarelicenses`.`id` NOT IN (".implode(',', $installed).")";
      }
      $query .= " ORDER BY `softname`, `version`;";

      $req = $DB->request($query);
      if ($number = $req->numrows()) {
         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('massSoftwareLicense'.$rand);

            $actions = ['Computer_SoftwareLicense'.MassiveAction::CLASS_ACTION_SEPARATOR.
                              'install' => _x('button', 'Install')];
            if (SoftwareLicense::canUpdate()) {
               $actions['purge'] = _x('button', 'Delete permanently');
            }

            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                                         'container'        => 'massSoftwareLicense'.$rand,
                                         'specific_actions' => $actions];

            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";

         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('massSoftwareLicense'.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('massSoftwareLicense'.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>" . __('Name') . "</th><th>" . __('Status') . "</th>";
         $header_end .= "<th>" .__('Version')."</th><th>" . __('License') . "</th>";
         $header_end .= "<th>" .__('Installation date')."</th>";
         $header_end .= "</tr>\n";
         echo $header_begin.$header_top.$header_end;

         $cat = true;
         foreach ($req as $data) {
            self::displaySoftsByLicense($data, $computers_id, $withtemplate, $canedit);
            Session::addToNavigateListItems('SoftwareLicense', $data["id"]);
         }

         echo $header_begin.$header_bottom.$header_end;

         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }

      echo "</div>\n";

   }


   /**
    * Display a installed software for a category
    *
    * @param $data                     data used to display
    * @param $computers_id             ID of the computer
    * @param $withtemplate             template case of the view process
    * @param $canedit         boolean  user can edit software ?
    * @param $display         boolean  display and calculte if true or juste calculate
    *
    * @return array of found license id
   **/
   private static function softsByCategory($data, $computers_id, $withtemplate, $canedit,
                                           $display) {
      global $DB, $CFG_GLPI;

      $ID       = $data["id"];
      $verid    = $data["verid"];
      $multiple = false;

      if ($display) {
         echo "<tr class='tab_bg_1'>";
         if ($canedit) {
            echo "<td>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
            echo "</td>";
         }
         echo "<td class='center b'>";
         echo "<a href='".Software::getFormURLWithID($data['softwares_id'])."'>";
         echo ($_SESSION["glpiis_ids_visible"] ? sprintf(__('%1$s (%2$s)'),
                                                         $data["softname"], $data['softwares_id'])
                                               : $data["softname"]);
         echo "</a></td>";
         echo "<td>" . $data["state"] . "</td>";

         echo "<td>" . $data["version"];
         echo "</td><td>";
      }

      //needs DB::request() to support aliases to get migrated
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
                           OR (`glpi_softwarelicenses`.`softwareversions_id_use` = 0
                               AND `glpi_softwarelicenses`.`softwareversions_id_buy` = '$verid'))";

      $licids = [];
      foreach ($DB->request($query) as $licdata) {
         $licids[]  = $licdata['id'];
         $licserial = $licdata['serial'];

         if (!empty($licdata['type'])) {
            $licserial = sprintf(__('%1$s (%2$s)'), $licserial, $licdata['type']);
         }

         if ($display) {
            echo "<span class='b'>". $licdata['name']. "</span> - ".$licserial;

            $link_item = Toolbox::getItemTypeFormURL('SoftwareLicense');
            $link      = $link_item."?id=".$licdata['id'];
            $comment   = "<table><tr><td>".__('Name')."</td><td>".$licdata['name']."</td></tr>".
                         "<tr><td>".__('Serial number')."</td><td>".$licdata['serial']."</td></tr>".
                         "<tr><td>". __('Comments').'</td><td>'.$licdata['comment']."</td></tr>".
                         "</table>";

            Html::showToolTip($comment, ['link' => $link]);
            echo "<br>";
         }
      }

      if ($display) {
         if (!count($licids)) {
            echo "&nbsp;";
         }

         echo "</td>";

         echo "<td>".Html::convDate($data['dateinstall'])."</td>";

         if (isset($data['is_dynamic'])) {
            echo "<td class='center'>".Dropdown::getYesNo($data['is_dynamic'])."</td>";
         }

         echo "<td class='center'>". Dropdown::getDropdownName("glpi_softwarecategories",
                                                                  $data['softwarecategories_id']);
         echo "</td>";
         echo "<td class='center'>" .Dropdown::getYesNo($data["softvalid"]) . "</td>";
         echo "</tr>\n";
      }

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

      $ID = $data['linkID'];

      $multiple  = false;
      $link_item = Toolbox::getItemTypeFormURL('SoftwareLicense');
      $link      = $link_item."?id=".$data['id'];

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td>";
         if (empty($withtemplate) || ($withtemplate != 2)) {
            Html::showMassiveActionCheckBox('Computer_SoftwareLicense', $ID);
         }
         echo "</td>";
      }

      echo "<td class='center b'>";
      echo "<a href='".Software::getFormURLWithID($data['softwares_id'])."'>";
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
      echo "</td><td class='b'>" .$data["name"]." - ". $serial;

      $comment = "<table><tr><td>".__('Name')."</td>"."<td>".$data['name']."</td></tr>".
                 "<tr><td>".__('Serial number')."</td><td>".$data['serial']."</td></tr>".
                 "<tr><td>". __('Comments')."</td><td>".$data['comment']."</td></tr></table>";

      Html::showToolTip($comment, ['link' => $link]);
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
   function upgrade($instID, $softwareversions_id, $dohistory = 1) {

      if ($this->getFromDB($instID)) {
         $computers_id = $this->fields['computers_id'];
         $this->delete(['id' => $instID]);
         $this->add(['computers_id'        => $computers_id,
                          'softwareversions_id' => $softwareversions_id]);
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

      $iterator = $DB->request([
         'FROM'   => 'glpi_computers_softwareversions',
         'WHERE'  => ['computers_id' => $oldid]
      ]);

      while ($data = $iterator->next()) {
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
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $nb = 0;
      switch ($item->getType()) {
         case 'Software' :
            if (!$withtemplate) {
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForSoftware($item->getID());
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
            break;

         case 'SoftwareVersion' :
            if (!$withtemplate) {
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForVersion($item->getID());
               }
               return [1 => __('Summary'),
                            2 => self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
                                                      $nb)];
            }
            break;

         case 'Computer' :
            // Installation allowed for template
            if (Software::canView()) {
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_computers_softwareversions',
                                            ['computers_id' => $item->getID(),
                                             'is_deleted'   => 0 ]);
               }
               return self::createTabEntry(Software::getTypeName(Session::getPluralNumber()), $nb);
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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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
