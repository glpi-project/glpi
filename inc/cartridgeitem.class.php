<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}


/**
 * CartridgeItem Class
 * This class is used to manage the various types of cartridges.
 * \see Cartridge
**/
class CartridgeItem extends CommonDBTM {

   // From CommonDBTM
   static protected $forward_entity_to = array('Cartridge', 'Infocom');
   public $dohistory                   = true;
   protected $usenotepad               = true;

   static $rightname                   = 'cartridge';

   static function getTypeName($nb=0) {
      return _n('Cartridge model','Cartridge models',$nb);
   }


   /**
    * @see CommonGLPI::getMenuName()
    *
    * @since version 0.85
   **/
   static function getMenuName() {
      return Cartridge::getTypeName(Session::getPluralNumber());
   }


   /**
    * @since version 0.84
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

      $class = new Cartridge();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $class = new CartridgeItem_PrinterModel();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function post_getEmpty() {

      $this->fields["alarm_threshold"] = Entity::getUsedConfig("cartriges_alert_repeat",
                                                               $this->fields["entities_id"],
                                                               "default_cartridges_alarm_threshold",
                                                               10);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Cartridge', $ong, $options);
      $this->addStandardTab('CartridgeItem_PrinterModel', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Document_Item',$ong, $options);
      $this->addStandardTab('Link',$ong, $options);
      $this->addStandardTab('Notepad',$ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   ///// SPECIFIC FUNCTIONS

   /**
   * Count cartridge of the cartridge type
   *
   * @return number of cartridges
   **/
   static function getCount() {
      global $DB;

      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$this->fields["id"]."'";

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
         $query = "INSERT INTO `glpi_cartridgeitems_printermodels`
                          (`cartridgeitems_id`, `printermodels_id`)
                   VALUES ('$cartridgeitems_id', '$printermodels_id');";

         if ($result = $DB->query($query)
             && ($DB->affected_rows() > 0)) {
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
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      CartridgeItemType::dropdown(array('value' => $this->fields["cartridgeitemtypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Reference')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ref");
      echo "</td>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Manufacturer::dropdown(array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='4'>
             <textarea cols='45' rows='9' name='comment'>".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown(array('name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`'));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Stock location')."</td>";
      echo "<td>";
      Location::dropdown(array('value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alert threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber('alarm_threshold', array('value' => $this->fields["alarm_threshold"],
                                                    'min'   => 0,
                                                    'max'   => 100,
                                                    'step'  => 1,
                                                    'toadd' => array('-1' => __('Never'))));
      Alert::displayLastAlert('CartridgeItem', $ID);
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

      $tab                          = array();
      $tab['common']                = __('Characteristics');

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               = __('Name');
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['massiveaction']      = false;
      $tab[2]['datatype']           = 'number';

      $tab[34]['table']             = $this->getTable();
      $tab[34]['field']             = 'ref';
      $tab[34]['name']              = __('Reference');
      $tab[34]['datatype']          = 'string';

      $tab[4]['table']              = 'glpi_cartridgeitemtypes';
      $tab[4]['field']              = 'name';
      $tab[4]['name']               = __('Type');
      $tab[4]['datatype']           = 'dropdown';

      $tab[23]['table']             = 'glpi_manufacturers';
      $tab[23]['field']             = 'name';
      $tab[23]['name']              = __('Manufacturer');
      $tab[23]['datatype']          = 'dropdown';

      $tab[9]['table']              = 'glpi_cartridgeitems';
      $tab[9]['field']              = '_virtual';
      $tab[9]['name']               = _n('Cartridge','Cartridges', Session::getPluralNumber());
      $tab[9]['datatype']           = 'specific';
      $tab[9]['massiveaction']      = false;
      $tab[9]['nosearch']           = true;
      $tab[9]['nosort']             = true;
      $tab[9]['additionalfields']   = array('alarm_threshold');

      $tab[17]['table']             = 'glpi_cartridges';
      $tab[17]['field']             = 'id';
      $tab[17]['name']              = __('Number of used cartridges');
      $tab[17]['datatype']          = 'count';
      $tab[17]['forcegroupby']      = true;
      $tab[17]['usehaving']         = true;
      $tab[17]['massiveaction']     = false;
      $tab[17]['joinparams']        = array('jointype' => 'child',
                                            'condition' => "AND NEWTABLE.`date_use` IS NOT NULL
                                                            AND NEWTABLE.`date_out` IS NULL");

      $tab[18]['table']             = 'glpi_cartridges';
      $tab[18]['field']             = 'id';
      $tab[18]['name']              = __('Number of worn cartridges');
      $tab[18]['datatype']          = 'count';
      $tab[18]['forcegroupby']      = true;
      $tab[18]['usehaving']         = true;
      $tab[18]['massiveaction']     = false;
      $tab[18]['joinparams']        = array('jointype' => 'child',
                                            'condition' => "AND NEWTABLE.`date_out` IS NOT NULL");

      $tab[19]['table']             = 'glpi_cartridges';
      $tab[19]['field']             = 'id';
      $tab[19]['name']              = __('Number of new cartridges');
      $tab[19]['datatype']          = 'count';
      $tab[19]['forcegroupby']      = true;
      $tab[19]['usehaving']         = true;
      $tab[19]['massiveaction']     = false;
      $tab[19]['joinparams']        = array('jointype' => 'child',
                                            'condition' => "AND NEWTABLE.`date_use` IS NULL
                                                            AND NEWTABLE.`date_out` IS NULL");

      $tab += Location::getSearchOptionsToAdd();

      $tab[24]['table']             = 'glpi_users';
      $tab[24]['field']             = 'name';
      $tab[24]['linkfield']         = 'users_id_tech';
      $tab[24]['name']              = __('Technician in charge of the hardware');
      $tab[24]['datatype']          = 'dropdown';
      $tab[24]['right']             = 'own_ticket';

      $tab[49]['table']             = 'glpi_groups';
      $tab[49]['field']             = 'completename';
      $tab[49]['linkfield']         = 'groups_id_tech';
      $tab[49]['name']              = __('Group in charge of the hardware');
      $tab[49]['condition']         = '`is_assign`';
      $tab[49]['datatype']          = 'dropdown';

      $tab[8]['table']              = $this->getTable();
      $tab[8]['field']              = 'alarm_threshold';
      $tab[8]['name']               = __('Alert threshold');
      $tab[8]['datatype']           = 'number';
      $tab[8]['toadd']              = array('-1' => __('Never'));

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['massiveaction']     = false;
      $tab[80]['datatype']          = 'dropdown';

      $tab[40]['table']             = 'glpi_printermodels';
      $tab[40]['field']             = 'name';
      $tab[40]['datatype']          = 'dropdown';
      $tab[40]['name']              = _n('Printer model', 'Printer models', Session::getPluralNumber());
      $tab[40]['forcegroupby']      = true;
      $tab[40]['massiveaction']     = false;
      $tab[40]['joinparams']        = array('beforejoin'
                                             => array('table'
                                                         => 'glpi_cartridgeitems_printermodels',
                                                      'joinparams'
                                                         => array('jointype' => 'child')));

      // add objectlock search options
      $tab += ObjectLock::getSearchOptionsToAdd( get_class($this) ) ;

      $tab += Notepad::getSearchOptionsToAdd();

      return $tab;
   }


   static function cronInfo($name) {
      return array('description' => __('Send alarms on cartridges'));
   }


   /**
    * Cron action on cartridges : alert if a stock is behind the threshold
    *
    * @param $task for log, display information if NULL? (default NULL)
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronCartridge($task=NULL) {
      global $DB, $CFG_GLPI;

      $cron_status = 1;
      if ($CFG_GLPI["use_mailing"]) {
         $message = array();
         $alert   = new Alert();

         foreach (Entity::getEntitiesToNotify('cartridges_alert_repeat') as $entity => $repeat) {
            // if you change this query, please don't forget to also change in showDebug()
            $query_alert = "SELECT `glpi_cartridgeitems`.`id` AS cartID,
                                   `glpi_cartridgeitems`.`entities_id` AS entity,
                                   `glpi_cartridgeitems`.`ref` AS ref,
                                   `glpi_cartridgeitems`.`name` AS name,
                                   `glpi_cartridgeitems`.`alarm_threshold` AS threshold,
                                   `glpi_alerts`.`id` AS alertID,
                                   `glpi_alerts`.`date`
                            FROM `glpi_cartridgeitems`
                            LEFT JOIN `glpi_alerts`
                                 ON (`glpi_cartridgeitems`.`id` = `glpi_alerts`.`items_id`
                                     AND `glpi_alerts`.`itemtype` = 'CartridgeItem')
                            WHERE `glpi_cartridgeitems`.`is_deleted` = '0'
                                  AND `glpi_cartridgeitems`.`alarm_threshold` >= '0'
                                  AND `glpi_cartridgeitems`.`entities_id` = '".$entity."'
                                  AND (`glpi_alerts`.`date` IS NULL
                                       OR (`glpi_alerts`.`date`+$repeat) < CURRENT_TIMESTAMP());";
            $message = "";
            $items   = array();

            foreach ($DB->request($query_alert) as $cartridge) {
               if (($unused=Cartridge::getUnusedNumber($cartridge["cartID"]))<=$cartridge["threshold"]) {
                  //TRANS: %1$s is the cartridge name, %2$s its reference, %3$d the remaining number
                  $message .= sprintf(__('Threshold of alarm reached for the type of cartridge: %1$s - Reference %2$s - Remaining %3$d'),
                                      $cartridge["name"], $cartridge["ref"], $unused);
                  $message .='<br>';

                  $items[$cartridge["cartID"]] = $cartridge;

                  // if alert exists -> delete
                  if (!empty($cartridge["alertID"])) {
                     $alert->delete(array("id" => $cartridge["alertID"]));
                  }
               }
            }

            if (!empty($items)) {
               $options['entities_id'] = $entity;
               $options['items']       = $items;

               $entityname = Dropdown::getDropdownName("glpi_entities", $entity);
               if (NotificationEvent::raiseEvent('alert', new CartridgeItem(), $options)) {
                  if ($task) {
                     $task->log(sprintf(__('%1$s: %2$s')."\n", $entityname, $message));
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'),
                                                               $entityname, $message));
                  }

                  $input["type"]     = Alert::THRESHOLD;
                  $input["itemtype"] = 'CartridgeItem';

                  // add alerts
                  foreach ($items as $ID => $consumable) {
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
   }


   /**
    * Print a select with compatible cartridge
    *
    * @param $printer Printer object
    *
    * @return nothing (display)
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
      $datas = array();
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
      return array('alert' => __('Send alarms on cartridges'));
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      // see query_alert in cronCartridge()
      $item = array('cartID'    => $this->fields['id'],
                    'entity'    => $this->fields['entities_id'],
                    'ref'       => $this->fields['ref'],
                    'name'      => $this->fields['name'],
                    'threshold' => $this->fields['alarm_threshold']);

      $options = array();
      $options['entities_id'] = $this->getEntityID();
      $options['items']       = array($item);
      NotificationEvent::debugEvent($this, $options);
   }

}
?>
