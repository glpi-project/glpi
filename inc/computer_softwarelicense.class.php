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

/**
 * Manage link beetween computer and software licenses.
 */
class Computer_SoftwareLicense extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1 = 'Computer';
   static public $items_id_1 = 'computers_id';

   static public $itemtype_2 = 'SoftwareLicense';
   static public $items_id_2 = 'softwarelicenses_id';


   function post_addItem() {

      SoftwareLicense::updateValidityIndicator($this->fields['softwarelicenses_id']);

      parent::post_addItem();
   }


   function post_deleteFromDB() {

      SoftwareLicense::updateValidityIndicator($this->fields['softwarelicenses_id']);

      parent::post_deleteFromDB();
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'name',
         'name'               => _n('License', 'Licenses', 1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_computers',
         'field'              => 'name',
         'name'               => _n('Computer', 'Computers', 1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   static function showMassiveActionsSubForm(MassiveAction $ma) {

      $input = $ma->getInput();
      switch ($ma->getAction()) {
         case 'move_license' :
            if (isset($input['options'])) {
               if (isset($input['options']['move'])) {
                  SoftwareLicense::dropdown(['condition'
                                                    => "`glpi_softwarelicenses`.`softwares_id`
                                                         = '".$input['options']['move']['softwares_id']."'",
                                                  'used'
                                                    => $input['options']['move']['used']]);
                  echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                  return true;
               }
            }
            return false;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'move_license' :
            $input = $ma->getInput();
            if (isset($input['softwarelicenses_id'])) {
               foreach ($ids as $id) {
                  if ($item->can($id, UPDATE)) {
                     //Process rules
                     if ($item->update(['id'  => $id,
                                             'softwarelicenses_id'
                                             => $input['softwarelicenses_id']])) {
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

         case 'install' :
            $csl = new self();
            $csv = new Computer_SoftwareVersion();
            foreach ($ids as $id) {
               if ($csl->getFromDB($id)) {
                  $sl = new SoftwareLicense();

                  if ($sl->getFromDB($csl->fields["softwarelicenses_id"])) {
                     $version = 0;
                     if ($sl->fields["softwareversions_id_use"]>0) {
                        $version = $sl->fields["softwareversions_id_use"];
                     } else {
                        $version = $sl->fields["softwareversions_id_buy"];
                     }
                     if ($version > 0) {
                        $params = ['computers_id'        => $csl->fields['computers_id'],
                                        'softwareversions_id' => $version];
                        //Get software name and manufacturer
                        if ($csv->can(-1, CREATE, $params)) {
                           //Process rules
                           if ($csv->add($params)) {
                              $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                           } else {
                              $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           }
                        } else {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        }
                     } else {
                        Session::addMessageAfterRedirect(__('A version is required!'), false, ERROR);
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Get number of installed licenses of a license
    *
    * @param integer $softwarelicenses_id license ID
    * @param integer $entity              to search for computer in (default = all entities)
    *                                     (default '') -1 means no entity restriction
    *
    * @return integer number of installations
   **/
   static function countForLicense($softwarelicenses_id, $entity = '') {
      global $DB;

      $request = [
         'FROM'         => 'glpi_computers_softwarelicenses',
         'COUNT'        => 'cpt',
         'INNER JOIN'   => [
            'glpi_computers'  => [
               'FKEY'   => [
                  'glpi_computers'                    => 'id',
                  'glpi_computers_softwarelicenses'   => 'computers_id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_computers_softwarelicenses.softwarelicenses_id' => $softwarelicenses_id,
            'glpi_computers.is_deleted'                           => 0,
            'glpi_computers.is_template'                          => 0,
            'glpi_computers_softwarelicenses.is_deleted'          => 0
         ]
      ];

      if ($entity != -1) {
         $request['WHERE'] = $request['WHERE'] + getEntitiesRestrictCriteria('glpi_computers', '', $entity);
      }

      $result = $DB->request($request)->next();
      return $result['cpt'];
   }


   /**
    * Get number of installed licenses of a software
    *
    * @param integer $softwares_id software ID
    *
    * @return integer number of installations
   **/
   static function countForSoftware($softwares_id) {
      global $DB;

      $request = [
         'FROM'   => 'glpi_softwarelicenses',
         'COUNT'  => 'cpt',
         'INNER JOIN'   => [
            'glpi_computers_softwarelicenses'   => [
               'FKEY'   => [
                  'glpi_softwarelicenses'             => 'id',
                  'glpi_computers_softwarelicenses'   => 'softwarelicenses_id'
               ]
            ],
            'glpi_computers'   => [
               'FKEY'   => [
                  'glpi_computers'                    => 'id',
                  'glpi_computers_softwarelicenses'   => 'computers_id'
               ]
            ]
         ],
         'WHERE'  => [
            'glpi_softwarelicenses.softwares_id'         => $softwares_id,
            'glpi_computers.is_deleted'                  => 0,
            'glpi_computers.is_template'                 => 0,
            'glpi_computers_softwarelicenses.is_deleted' => 0
         ] + getEntitiesRestrictCriteria('glpi_computers')
      ];

      $result = $DB->request($request)->next();
      return $result['cpt'];
   }


   /**
    * Show number of installation per entity
    *
    * @param SoftwareLicense $license SoftwareLicense instance
    *
    * @return void
   **/
   static function showForLicenseByEntity(SoftwareLicense $license) {
      global $DB;

      $softwarelicense_id = $license->getField('id');

      if (!Software::canView() || !$softwarelicense_id) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Number of affected computers')."</th>";
      echo "</tr>\n";

      $tot = 0;

      $iterator = $DB->request([
         'SELECT' => ['id', 'completename'],
         'FROM'   => 'glpi_entities',
         'WHERE'  => getEntitiesRestrictCriteria('glpi_entities'),
         'ORDER'  => ['completename']
      ]);

      while ($data = $iterator->next()) {
         $nb = self::countForLicense($softwarelicense_id, $data['id']);
         if ($nb > 0) {
            echo "<tr class='tab_bg_2'><td>" . $data["completename"] . "</td>";
            echo "<td class='numeric'>".$nb."</td></tr>\n";
            $tot += $nb;
         }
      }

      if ($tot > 0) {
         echo "<tr class='tab_bg_1'><td class='center b'>".__('Total')."</td>";
         echo "<td class='numeric b '>".$tot."</td></tr>\n";
      } else {
         echo "<tr class='tab_bg_1'><td colspan='2 b'>" . __('No item found') . "</td></tr>\n";
      }
      echo "</table></div>";
   }


   /**
    * Show computers linked to a License
    *
    * @param SoftwareLicense $license SoftwareLicense instance
    *
    * @return void
   **/
   static function showForLicense(SoftwareLicense $license) {
      global $DB;

      $searchID = $license->getField('id');

      if (!Software::canView() || !$searchID) {
         return false;
      }

      $canedit         = Session::haveRightsOr("software", [CREATE, UPDATE, DELETE, PURGE]);
      $canshowcomputer = Computer::canView();

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

      if (isset($_GET["sort"]) && !empty($_GET["sort"])) {
         // manage several param like location,compname : order first
         $tmp  = explode(",", $_GET["sort"]);
         $sort = "`".implode("` $order,`", $tmp)."`";
      } else {
         $sort = "`entity` $order, `compname`";
      }

      //SoftwareLicense ID
      $number = self::countForLicense($searchID);

      echo "<div class='center'>";

      //If the number of linked assets have reached the number defined in the license,
      //do not allow to add more assets
      if ($canedit
         && ($license->getField('number') == -1 || $number < $license->getField('number'))) {
         echo "<form method='post' action='".Computer_SoftwareLicense::getFormURL()."'>";
         echo "<input type='hidden' name='softwarelicenses_id' value='$searchID'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td>";
         Computer::dropdown(['entity'      => $license->fields['entities_id'],
                                  'entity_sons' => $license->fields['is_recursive']]);

         echo "</td>";
         echo "<td><input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
      }

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table></div>\n";
         return;
      }

      // Display the pager
      Html::printAjaxPager(__('Affected computers'), $start, $number);

      $iterator = $DB->request([
         'SELECT'    => [
            self::getTable() . '.*',
            'glpi_computers.name AS compname',
            'glpi_computers.id AS cID',
            'glpi_computers.serial',
            'glpi_computers.otherserial',
            'glpi_users.name AS username',
            'glpi_users.id AS userid',
            'glpi_users.realname AS userrealname',
            'glpi_users.firstname AS userfirstname',
            'glpi_softwarelicenses.name AS license',
            'glpi_softwarelicenses.id AS vID',
            'glpi_softwarelicenses.softwares_id AS softid',
            'glpi_entities.completename AS entity',
            'glpi_locations.completename AS location',
            'glpi_states.name AS state',
            'glpi_groups.name AS groupe'
         ],
         'FROM'      => self::getTable(),
         'INNER JOIN'   => [
            'glpi_softwarelicenses' => [
               'FKEY'   => [
                  self::getTable()        => 'softwarelicenses_id',
                  'glpi_softwarelicenses' => 'id'
               ]
            ],
            'glpi_computers'  => [
               'FKEY'   => [
                  'glpi_computers_softwarelicenses'   => 'computers_id',
                  'glpi_computers'                    => 'id'
               ]
            ]
         ],
         'LEFT JOIN'    => [
            'glpi_entities'   => [
               'FKEY'   => [
                  'glpi_computers'  => 'entities_id',
                  'glpi_entities'   => 'id'
               ]
            ],
            'glpi_locations'  => [
               'FKEY'   => [
                  'glpi_computers'  => 'locations_id',
                  'glpi_locations'  => 'id'
               ]
            ],
            'glpi_states'  => [
               'FKEY'   => [
                  'glpi_computers'  => 'states_id',
                  'glpi_states'     => 'id'
               ]
            ],
            'glpi_groups'  => [
               'FKEY'   => [
                  'glpi_computers'  => 'groups_id',
                  'glpi_groups'     => 'id'
               ]
            ],
            'glpi_users'   => [
               'FKEY'   => [
                  'glpi_computers'  => 'users_id',
                  'glpi_users'      => 'id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_softwarelicenses.id'                   => $searchID,
            'glpi_computers.is_deleted'                  => 0,
            'glpi_computers.is_template'                 => 0,
            'glpi_computers_softwarelicenses.is_deleted' => 0

         ] + getEntitiesRestrictCriteria('glpi_computers'),
         'ORDER'        => "$sort $order",
         'LIMIT'        => $_SESSION['glpilist_limit'],
         'START'        => $start
      ]);

      $rand = mt_rand();

      if ($data = $iterator->next()) {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], count($iterator)),
                                          'container'        => 'mass'.__CLASS__.$rand,
                                          'specific_actions' => ['purge' => _x('button', 'Delete permanently')]];

            // show transfer only if multi licenses for this software
            if (self::countLicenses($data['softid']) > 1) {
               $massiveactionparams['specific_actions'][__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'move_license'] =_x('button', 'Move');
            }

            // Options to update license
            $massiveactionparams['extraparams']['options']['move']['used'] = [$searchID];
            $massiveactionparams['extraparams']['options']['move']['softwares_id']
                                                                  = $license->fields['softwares_id'];

            Html::showMassiveActions($massiveactionparams);
         }

         $soft       = new Software();
         $soft->getFromDB($license->fields['softwares_id']);
         $showEntity = ($license->isRecursive());
         $linkUser   = User::canView();

         $text = sprintf(__('%1$s = %2$s'), Software::getTypeName(1), $soft->fields["name"]);
         $text = sprintf(__('%1$s - %2$s'), $text, $data["license"]);

         Session::initNavigateListItems('Computer', $text);

         echo "<table class='tab_cadre_fixehov'>";

         $columns = ['compname'          => __('Name'),
                           'entity'            => __('Entity'),
                           'serial'            => __('Serial number'),
                           'otherserial'       => __('Inventory number'),
                           'location,compname' => __('Location'),
                           'state,compname'    => __('Status'),
                           'groupe,compname'   => __('Group'),
                           'username,compname' => __('User')];
         if (!$showEntity) {
            unset($columns['entity']);
         }

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
               echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
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
            echo "</tr>\n";

         } while ($data = $iterator->next());
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>\n";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }

      } else { // Not found
         echo __('No item found');
      }
      Html::printAjaxPager(__('Affected computers'), $start, $number);

      echo "</div>\n";

   }


   /**
    * Update license associated on a computer
    *
    * @param integer $licID               ID of the install software lienk
    * @param integer $softwarelicenses_id ID of the new license
    *
    * @return void
   **/
   function upgrade($licID, $softwarelicenses_id) {

      if ($this->getFromDB($licID)) {
         $computers_id = $this->fields['computers_id'];
         $this->delete(['id' => $licID]);
         $this->add(['computers_id'        => $computers_id,
                     'softwarelicenses_id' => $softwarelicenses_id]);
      }
   }


   /**
    * Get licenses list corresponding to an installation
    *
    * @param integer $computers_id        ID of the computer
    * @param integer $softwareversions_id ID of the version
    *
    * @return void
   **/
   static function getLicenseForInstallation($computers_id, $softwareversions_id) {
      global $DB;

      $lic = [];

      $iterator = $DB->request([
         'SELECT'       => [
            'glpi_softwarelicenses.*',
            'glpi_softwarelicensetypes.name AS type'
         ],
         'FROM'         => 'glpi_softwarelicenses',
         'INNER JOIN'   => [
            self::getTable()  => [
               'FKEY'   => [
                  self::getTable()        => 'softwarelicenses_id',
                  'glpi_softwarelicenses' => 'id'
               ]
            ]
         ],
         'LEFT JOIN'    => [
            'glpi_softwarelicensetypes'   => [
               'FKEY'   => [
                  'glpi_softwarelicenses'       => 'softwarelicensetypes_id',
                  'glpi_softwarelicensetypes'   => 'id'
               ]
            ]
         ],
         'WHERE'        => [
            self::getTable() . '.computers_id'  => $computers_id,
            'OR'                                => [
               'glpi_softwarelicenses.softwareversions_id_use' => $softwareversions_id,
               'glpi_softwarelicenses.softwareversions_id_buy' => $softwareversions_id
            ]
         ]
      ]);

      while ($data = $iterator->next()) {
         $lic[$data['id']] = $data;
      }
      return $lic;
   }


   /**
    * Duplicate all software licenses from a computer template to its clone
    *
    * @param integer $oldid ID of the computer to clone
    * @param integer $newid ID of the computer cloned
    *
    * @return void
   **/
   static function cloneComputer($oldid, $newid) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => 'glpi_computers_softwarelicenses',
         'WHERE'  => ['computers_id' => $oldid]
      ]);

      while ($data = $iterator->next()) {
         $csl                  = new self();
         unset($data['id']);
         $data['computers_id'] = $newid;
         $data['_no_history']  = true;

         $csl->add($data);
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $nb = 0;
      switch ($item->getType()) {
         case 'SoftwareLicense' :
            if (!$withtemplate) {
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForLicense($item->getID());
               }
               return [1 => __('Summary'),
                            2 => self::createTabEntry(Computer::getTypeName(Session::getPluralNumber()),
                                                      $nb)];
            }
            break;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'SoftwareLicense') {
         switch ($tabnum) {
            case 1 :
               self::showForLicenseByEntity($item);
               break;

            case 2 :
               self::showForLicense($item);
               break;
         }
      }
      return true;
   }


   /**
    * Count number of licenses for a software
    *
    * @since 0.85
    *
    * @param integer $softwares_id Software ID
    *
    * @return void
    **/
   static function countLicenses($softwares_id) {
      global $DB;

      $result = $DB->request([
         'FROM'   => 'glpi_softwarelicenses',
         'COUNT'  => 'cpt',
         'WHERE'  => [
            'softwares_id' => $softwares_id
         ] + getEntitiesRestrictCriteria('glpi_softwarelicenses')
      ])->next();
      return $result['cpt'];
   }
}
