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
 * CartridgeItem Class
 * This class is used to manage the various types of cartridges.
 * \see Cartridge
**/
class CartridgeItem extends CommonDBTM {

   // From CommonDBTM
   static protected $forward_entity_to = ['Cartridge', 'Infocom'];
   public $dohistory                   = true;
   protected $usenotepad               = true;

   static $rightname                   = 'cartridge';

   static function getTypeName($nb = 0) {
      return _n('Cartridge model', 'Cartridge models', $nb);
   }


   /**
    * @see CommonGLPI::getMenuName()
    *
    * @since 0.85
   **/
   static function getMenuName() {
      return Cartridge::getTypeName(Session::getPluralNumber());
   }


   /**
    * @since 0.84
    *
    * @see CommonDBTM::getPostAdditionalInfosForName
   **/
   function getPostAdditionalInfosForName() {

      if (isset($this->fields["ref"]) && !empty($this->fields["ref"])) {
         return $this->fields["ref"];
      }
      return '';
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Cartridge::class,
            CartridgeItem_PrinterModel::class,
         ]
      );

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function post_getEmpty() {

      $this->fields["alarm_threshold"] = Entity::getUsedConfig("cartriges_alert_repeat",
                                                               $this->fields["entities_id"],
                                                               "default_cartridges_alarm_threshold",
                                                               10);
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Cartridge', $ong, $options);
      $this->addStandardTab('CartridgeItem_PrinterModel', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   ///// SPECIFIC FUNCTIONS

   /**
    * Count cartridge of the cartridge type
    *
    * @param integer $id Item id
    *
    * @return number of cartridges
    *
    * @since 9.2 add $id parameter
    **/
   static function getCount($id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$id."'";

      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         return $number;
      }
      return false;
   }


   /**
    * Add a compatible printer type for a cartridge type
    *
    * @param $cartridgeitems_id  integer: cartridge type identifier
    * @param printermodels_id    integer: printer type identifier
    *
    * @return boolean : true for success
   **/
   function addCompatibleType($cartridgeitems_id, $printermodels_id) {
      global $DB;

      if (($cartridgeitems_id > 0)
          && ($printermodels_id > 0)) {
         $params = [
            'cartridgeitems_id' => $cartridgeitems_id,
            'printermodels_id'  => $printermodels_id
         ];
         $result = $DB->insert('glpi_cartridgeitems_printermodels', $params);

         if ($result && ($DB->affected_rows() > 0)) {
            return true;
         }
      }
      return false;
   }


   /**
    * Print the cartridge type form
    *
    * @param $ID        integer ID of the item
    * @param $options   array os possible options:
    *     - target for the Form
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *
    * @return boolean
   **/
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      CartridgeItemType::dropdown(['value' => $this->fields["cartridgeitemtypes_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Reference')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ref");
      echo "</td>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]]);
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='4'>
             <textarea cols='45' rows='9' name='comment'>".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown(['name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Stock location')."</td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alert threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber('alarm_threshold', ['value' => $this->fields["alarm_threshold"],
                                                    'min'   => 0,
                                                    'max'   => 100,
                                                    'step'  => 1,
                                                    'toadd' => ['-1' => __('Never')]]);
      Alert::displayLastAlert('CartridgeItem', $ID);
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
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
         'id'                 => '34',
         'table'              => $this->getTable(),
         'field'              => 'ref',
         'name'               => __('Reference'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_cartridgeitemtypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => __('Manufacturer'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => '_virtual',
         'name'               => _n('Cartridge', 'Cartridges', Session::getPluralNumber()),
         'datatype'           => 'specific',
         'massiveaction'      => false,
         'nosearch'           => true,
         'nosort'             => true,
         'additionalfields'   => ['alarm_threshold']
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => 'glpi_cartridges',
         'field'              => 'id',
         'name'               => __('Number of used cartridges'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_use` IS NOT NULL
                                     AND NEWTABLE.`date_out` IS NULL'
         ]
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => 'glpi_cartridges',
         'field'              => 'id',
         'name'               => __('Number of worn cartridges'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_out` IS NOT NULL'
         ]
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => 'glpi_cartridges',
         'field'              => 'id',
         'name'               => __('Number of new cartridges'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_use` IS NULL
                                     AND NEWTABLE.`date_out` IS NULL'
         ]
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => '`is_assign`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'alarm_threshold',
         'name'               => __('Alert threshold'),
         'datatype'           => 'number',
         'toadd'              => [
            '-1'                 => 'Never'
         ]
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => 'glpi_printermodels',
         'field'              => 'name',
         'datatype'           => 'dropdown',
         'name'               => _n('Printer model', 'Printer models', Session::getPluralNumber()),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_cartridgeitems_printermodels',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }


   static function cronInfo($name) {
      return ['description' => __('Send alarms on cartridges')];
   }


   /**
    * Cron action on cartridges : alert if a stock is behind the threshold
    *
    * @param CronTask $task for log, display information if NULL? (default NULL)
    *
    * @return void
   **/
   static function cronCartridge($task = null) {
      global $DB, $CFG_GLPI;

      $cron_status = 1;
      if ($CFG_GLPI["use_notifications"]) {
         $message = [];
         $alert   = new Alert();

         foreach (Entity::getEntitiesToNotify('cartridges_alert_repeat') as $entity => $repeat) {
            // if you change this query, please don't forget to also change in showDebug()
            $result = $DB->request(
               [
                  'SELECT'    => [
                     'glpi_cartridgeitems.id AS cartID',
                     'glpi_cartridgeitems.entities_id AS entity',
                     'glpi_cartridgeitems.ref AS ref',
                     'glpi_cartridgeitems.name AS name',
                     'glpi_cartridgeitems.alarm_threshold AS threshold',
                     'glpi_alerts.id AS alertID',
                     'glpi_alerts.date',
                  ],
                  'FROM'      => self::getTable(),
                  'LEFT JOIN' => [
                     'glpi_alerts' => [
                        'FKEY' => [
                           'glpi_alerts'         => 'items_id',
                           'glpi_cartridgeitems' => 'id',
                           [
                              'AND' => ['glpi_alerts.itemtype' => 'CartridgeItem'],
                           ],
                        ]
                     ]
                  ],
                  'WHERE'     => [
                     'glpi_cartridgeitems.is_deleted'      => 0,
                     'glpi_cartridgeitems.alarm_threshold' => ['>=', 0],
                     'glpi_cartridgeitems.entities_id'     => $entity,
                     'OR'                                  => [
                        ['glpi_alerts.date' => null],
                        ['glpi_alerts.date' => ['<', new QueryExpression('CURRENT_TIMESTAMP() - INTERVAL ' . $repeat . ' second')]],
                     ],
                  ],
               ]
            );

            $message = "";
            $items   = [];

            foreach ($result as $cartridge) {
               if (($unused=Cartridge::getUnusedNumber($cartridge["cartID"]))<=$cartridge["threshold"]) {
                  //TRANS: %1$s is the cartridge name, %2$s its reference, %3$d the remaining number
                  $message .= sprintf(__('Threshold of alarm reached for the type of cartridge: %1$s - Reference %2$s - Remaining %3$d'),
                                      $cartridge["name"], $cartridge["ref"], $unused);
                  $message .='<br>';

                  $items[$cartridge["cartID"]] = $cartridge;

                  // if alert exists -> delete
                  if (!empty($cartridge["alertID"])) {
                     $alert->delete(["id" => $cartridge["alertID"]]);
                  }
               }
            }

            if (!empty($items)) {
               $options = [
                  'entities_id' => $entity,
                  'items'       => $items,
               ];

               $entityname = Dropdown::getDropdownName("glpi_entities", $entity);
               if (NotificationEvent::raiseEvent('alert', new CartridgeItem(), $options)) {
                  if ($task) {
                     $task->log(sprintf(__('%1$s: %2$s')."\n", $entityname, $message));
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'),
                                                               $entityname, $message));
                  }

                  $input = [
                     'type'     => Alert::THRESHOLD,
                     'itemtype' => 'CartridgeItem',
                  ];

                  // add alerts
                  foreach (array_keys($items) as $ID) {
                     $input["items_id"] = $ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }

               } else {
                  //TRANS: %s is entity name
                  $msg = sprintf(__('%s: send cartridge alert failed'), $entityname);
                  if ($task) {
                     $task->log($msg);
                  } else {
                     //TRANS: %s is the entity
                     Session::addMessageAfterRedirect($msg, false, ERROR);
                  }
               }
            }
         }
      }

      return $cron_status;
   }


   /**
    * Print a select with compatible cartridge
    *
    * @param $printer Printer object
    *
    * @return string|boolean
   **/
   static function dropdownForPrinter(Printer $printer) {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt,
                       `glpi_locations`.`completename` AS location,
                       `glpi_cartridgeitems`.`ref` AS ref,
                       `glpi_cartridgeitems`.`name` AS name,
                       `glpi_cartridgeitems`.`id` AS tID
                FROM `glpi_cartridgeitems`
                INNER JOIN `glpi_cartridgeitems_printermodels`
                     ON (`glpi_cartridgeitems`.`id`
                         = `glpi_cartridgeitems_printermodels`.`cartridgeitems_id`)
                INNER JOIN `glpi_cartridges`
                     ON (`glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                         AND `glpi_cartridges`.`date_use` IS NULL)
                LEFT JOIN `glpi_locations`
                     ON (`glpi_locations`.`id` = `glpi_cartridgeitems`.`locations_id`)
                WHERE `glpi_cartridgeitems_printermodels`.`printermodels_id`
                           = '".$printer->fields["printermodels_id"]."'
                      ".getEntitiesRestrictRequest('AND', 'glpi_cartridgeitems', '',
                                                   $printer->fields["entities_id"], true)."
                GROUP BY tID
                ORDER BY `name`, `ref`";
      $datas = [];
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data= $DB->fetch_assoc($result)) {
               $text = sprintf(__('%1$s - %2$s'), $data["name"], $data["ref"]);
               $text = sprintf(__('%1$s (%2$s)'), $text, $data["cpt"]);
               $text = sprintf(__('%1$s - %2$s'), $text, $data["location"]);
               $datas[$data["tID"]] = $text;
            }
         }
      }
      if (count($datas)) {
         return Dropdown::showFromArray('cartridgeitems_id', $datas);
      }
      return false;
   }


   function getEvents() {
      return ['alert' => __('Send alarms on cartridges')];
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      // see query_alert in cronCartridge()
      $item = ['cartID'    => $this->fields['id'],
                    'entity'    => $this->fields['entities_id'],
                    'ref'       => $this->fields['ref'],
                    'name'      => $this->fields['name'],
                    'threshold' => $this->fields['alarm_threshold']];

      $options = [];
      $options['entities_id'] = $this->getEntityID();
      $options['items']       = [$item];
      NotificationEvent::debugEvent($this, $options);
   }

}
