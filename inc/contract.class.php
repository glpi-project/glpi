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
 *  Contract class
 */
class Contract extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = array('ContractCost');

   static $rightname                   = 'contract';
   protected $usenotepad               = true;



   static function getTypeName($nb=0) {
      return _n('Contract', 'Contracts', $nb);
   }


   function post_getEmpty() {

      $this->fields["alert"] = Entity::getUsedConfig("use_contracts_alert",
                                                     $this->fields["entities_id"],
                                                     "default_contract_alert", 0);
      $this->fields["notice"] = 0;
   }


   function cleanDBonPurge() {

      $class = new Contract_Supplier();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $class = new ContractCost();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $class = new Contract_Item();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('ContractCost', $ong, $options);
      $this->addStandardTab('Contract_Supplier', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @since version 0.83.3
    *
    * @see CommonDBTM::prepareInputForAdd()
    */
   function prepareInputForAdd($input) {

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   /**
    * @since version 0.84
   **/
   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Devices
         ContractCost::cloneContract($this->input["_oldID"], $this->fields['id']);
      }
   }


   function pre_updateInDB() {

      // Clean end alert if begin_date is after old one
      // Or if duration is greater than old one
      if ((isset($this->oldvalues['begin_date'])
           && ($this->oldvalues['begin_date'] < $this->fields['begin_date']))
          || (isset($this->oldvalues['duration'])
              && ($this->oldvalues['duration'] < $this->fields['duration']))) {

         $alert = new Alert();
         $alert->clear($this->getType(), $this->fields['id'], Alert::END);
      }

      // Clean notice alert if begin_date is after old one
      // Or if duration is greater than old one
      // Or if notice is lesser than old one
      if ((isset($this->oldvalues['begin_date'])
           && ($this->oldvalues['begin_date'] < $this->fields['begin_date']))
          || (isset($this->oldvalues['duration'])
              && ($this->oldvalues['duration'] < $this->fields['duration']))
          || (isset($this->oldvalues['notice'])
              && ($this->oldvalues['notice'] > $this->fields['notice']))) {

         $alert = new Alert();
         $alert->clear($this->getType(), $this->fields['id'], Alert::NOTICE);
      }
   }


   /**
    * Print the contract form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
   **/
   function showForm($ID,$options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td><td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Contract type')."</td><td >";
      ContractType::dropdown(array('value' => $this->fields["contracttypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('phone', 'Number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "num");
      echo "</td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Start date')."</td>";
      echo "<td>";
      Html::showDateField("begin_date", array('value' => $this->fields["begin_date"]));
      echo "</td>";
      echo "<td>".__('Initial contract period')."</td><td>";
      Dropdown::showNumber("duration", array('value' => $this->fields["duration"],
                                             'min'   => 1,
                                             'max'   => 120,
                                             'step'  => 1,
                                             'toadd' => array(0 => Dropdown::EMPTY_VALUE),
                                             'unit'  => 'month'));
      if (!empty($this->fields["begin_date"])) {
         echo " -> ".Infocom::getWarrantyExpir($this->fields["begin_date"],
                                               $this->fields["duration"], 0, true);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Notice')."</td><td>";
      Dropdown::showNumber("notice", array('value' => $this->fields["notice"],
                                           'min'   => 0,
                                           'max'   => 120,
                                           'step'  => 1,
                                           'toadd' => array(),
                                           'unit'  => 'month'));
      if (!empty($this->fields["begin_date"])
          && ($this->fields["notice"] > 0)) {
         echo " -> ".Infocom::getWarrantyExpir($this->fields["begin_date"],
                                               $this->fields["duration"], $this->fields["notice"],
                                               true);
      }
      echo "</td>";
      echo "<td>".__('Account number')."</td><td>";
      Html::autocompletionTextField($this, "accounting_number");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Contract renewal period')."</td><td>";
      Dropdown::showNumber("periodicity",
                           array('value' => $this->fields["periodicity"],
                                 'min'   => 12,
                                 'max'   => 60,
                                 'step'  => 12,
                                 'toadd' => array(0 => Dropdown::EMPTY_VALUE,
                                                  1 => sprintf(_n('%d month', '%d months', 1), 1),
                                                  2 => sprintf(_n('%d month', '%d months', 2), 2),
                                                  3 => sprintf(_n('%d month', '%d months', 3), 3),
                                                  6 => sprintf(_n('%d month', '%d months', 6), 6)),
                                 'unit'  => 'month'));
      echo "</td>";
      echo "<td>".__('Invoice period')."</td>";
      echo "<td>";
      Dropdown::showNumber("billing",
                           array('value' => $this->fields["billing"],
                                 'min'   => 12,
                                 'max'   => 60,
                                 'step'  => 12,
                                 'toadd' => array(0 => Dropdown::EMPTY_VALUE,
                                                  1 => sprintf(_n('%d month', '%d months', 1), 1),
                                                  2 => sprintf(_n('%d month', '%d months', 2), 2),
                                                  3 => sprintf(_n('%d month', '%d months', 3), 3),
                                                  6 => sprintf(_n('%d month', '%d months', 6), 6)),
                                 'unit'  => 'month'));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Renewal')."</td><td>";
      self::dropdownContractRenewal("renewal", $this->fields["renewal"]);
      echo "</td>";
      echo "<td>".__('Max number of items')."</td><td>";
      Dropdown::showNumber("max_links_allowed", array('value' => $this->fields["max_links_allowed"],
                                                      'min'   => 1,
                                                      'max'   => 200,
                                                      'step'  => 1,
                                                      'toadd' => array(0 => __('Unlimited'))));
      echo "</td></tr>";


      if (Entity::getUsedConfig("use_contracts_alert", $this->fields["entities_id"])) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Email alarms')."</td>";
         echo "<td>";
         self::dropdownAlert(array('name'  => "alert",
                                   'value' => $this->fields["alert"]));
         Alert::displayLastAlert(__CLASS__, $ID);
         echo "</td>";
         echo "<td colspan='2'>&nbsp;</td>";
         echo "</tr>";
      }
      echo "<tr class='tab_bg_1'><td class='top'>".__('Comments')."</td>";
      echo "<td class='center' colspan='3'>";
      echo "<textarea cols='50' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".__('Support hours')."</td>";
      echo "<td colspan='3'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('on week')."</td>";
      echo "<td colspan='3'>";
      echo "<table width='100%'><tr><td width='20%'>&nbsp;</td>";
      echo "<td width='20%'>";
      echo "<span class='small_space'>".__('Start')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("week_begin_hour", array('value' => $this->fields["week_begin_hour"]));
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('End')."</span></td><td width='20%'>";
      Dropdown::showHours("week_end_hour", array('value' => $this->fields["week_end_hour"]));
      echo "</td></tr></table>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('on Saturday')."</td>";
      echo "<td colspan='3'>";
      echo "<table width='100%'><tr><td width='20%'>";
      Dropdown::showYesNo("use_saturday", $this->fields["use_saturday"]);
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('Start')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("saturday_begin_hour",
                          array('value' => $this->fields["saturday_begin_hour"]));
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('End')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("saturday_end_hour",
                          array('value' => $this->fields["saturday_end_hour"]));
      echo "</td></tr></table>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Sundays and holidays')."</td>";
      echo "<td colspan='3'>";
      echo "<table width='100%'><tr><td width='20%'>";
      Dropdown::showYesNo("use_monday", $this->fields["use_monday"]);
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('Start')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("monday_begin_hour", array('value' => $this->fields["monday_begin_hour"]));
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('End')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("monday_end_hour", array('value' => $this->fields["monday_end_hour"]));
      echo "</td></tr></table>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   static function getSearchOptionsToAdd() {

      $tab                       = array();
      $tab['contract']           = self::getTypeName(Session::getPluralNumber());

      $joinparams                = array('beforejoin'
                                          => array('table'      => 'glpi_contracts_items',
                                                   'joinparams' => array('jointype'
                                                                            => 'itemtype_item')));

      $joinparamscost            = array('jointype'   => 'child',
                                         'beforejoin' => array('table'      => 'glpi_contracts',
                                                               'joinparams' => $joinparams));

      $tab[139]['table']         = 'glpi_contracts_items';
      $tab[139]['field']         = 'id';
      $tab[139]['name']          = _x('quantity', 'Number of contracts');
      $tab[139]['forcegroupby']  = true;
      $tab[139]['usehaving']     = true;
      $tab[139]['datatype']      = 'count';
      $tab[139]['massiveaction'] = false;
      $tab[139]['joinparams']    = array('jointype' => 'itemtype_item');

      $tab[29]['table']          = 'glpi_contracts';
      $tab[29]['field']          = 'name';
      $tab[29]['name']           = self::getTypeName(1);
      $tab[29]['forcegroupby']   = true;
      $tab[29]['datatype']       = 'itemlink';
      $tab[29]['massiveaction']  = false;
      $tab[29]['joinparams']     = $joinparams;

      $tab[30]['table']          = 'glpi_contracts';
      $tab[30]['field']          = 'num';
      $tab[30]['name']           = __('Contract number');
      $tab[30]['forcegroupby']   = true;
      $tab[30]['massiveaction']  = false;
      $tab[30]['joinparams']     = $joinparams;
      $tab[30]['datatype']       = 'string';

      $tab[129]['table']         = 'glpi_contracttypes';
      $tab[129]['field']         = 'name';
      $tab[129]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Type'));
      $tab[129]['datatype']      = 'dropdown';
      $tab[129]['massiveaction'] = false;
      $tab[129]['joinparams']    = array('beforejoin' => array('table' => 'glpi_contracts',
                                         'joinparams' => $joinparams));

      $tab[130]['table']         = 'glpi_contracts';
      $tab[130]['field']         = 'duration';
      $tab[130]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'),__('Duration'));
      $tab[130]['datatype']      = 'number';
      $tab[130]['max']           = 120;
      $tab[130]['unit']          = 'month';
      $tab[130]['forcegroupby']  = true;
      $tab[130]['massiveaction'] = false;
      $tab[130]['joinparams']    = $joinparams;

      $tab[131]['table']         = 'glpi_contracts';
      $tab[131]['field']         = 'periodicity';
                                    //TRANS: %1$s is Contract, %2$s is field name
      $tab[131]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Periodicity'));
      $tab[131]['forcegroupby']  = true;
      $tab[131]['massiveaction'] = false;
      $tab[131]['joinparams']    = $joinparams;
      $tab[131]['datatype']      = 'number';
      $tab[131]['min']           = 12;
      $tab[131]['max']           = 60;
      $tab[131]['step']          = 12;
      $tab[131]['toadd']         = array(0 => Dropdown::EMPTY_VALUE,
                                         1 => sprintf(_n('%d month', '%d months', 1), 1),
                                         2 => sprintf(_n('%d month', '%d months', 2), 2),
                                         3 => sprintf(_n('%d month', '%d months', 3), 3),
                                         6 => sprintf(_n('%d month', '%d months', 6), 6));
      $tab[131]['unit']          = 'month';

      $tab[132]['table']         = 'glpi_contracts';
      $tab[132]['field']         = 'begin_date';
      $tab[132]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Start date'));
      $tab[132]['forcegroupby']  = true;
      $tab[132]['datatype']      = 'date';
      $tab[132]['massiveaction'] = false;
      $tab[132]['joinparams']    = $joinparams;

      $tab[133]['table']         = 'glpi_contracts';
      $tab[133]['field']         = 'accounting_number';
      $tab[133]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Account number'));
      $tab[133]['forcegroupby']  = true;
      $tab[133]['massiveaction'] = false;
      $tab[133]['datatype']      = 'string';
      $tab[133]['joinparams']    = $joinparams;

      $tab[134]['table']         = 'glpi_contracts';
      $tab[134]['field']         = 'end_date';
      $tab[134]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('End date'));
      $tab[134]['forcegroupby']  = true;
      $tab[134]['datatype']      = 'date_delay';
      $tab[134]['datafields'][1] = 'begin_date';
      $tab[134]['datafields'][2] = 'duration';
      $tab[134]['searchunit']    = 'MONTH';
      $tab[134]['delayunit']     = 'MONTH';
      $tab[134]['massiveaction'] = false;
      $tab[134]['joinparams']    = $joinparams;

      $tab[135]['table']         = 'glpi_contracts';
      $tab[135]['field']         = 'notice';
      $tab[135]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Notice'));
      $tab[135]['datatype']      = 'number';
      $tab[135]['max']           = 120;
      $tab[135]['unit']          = 'month';
      $tab[135]['forcegroupby']  = true;
      $tab[135]['massiveaction'] = false;
      $tab[135]['joinparams']    = $joinparams;

      $tab[136]['table']         = 'glpi_contractcosts';
      $tab[136]['field']         = 'totalcost';
      $tab[136]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Cost'));
      $tab[136]['forcegroupby']  = true;
      $tab[136]['usehaving']     = true;
      $tab[136]['datatype']      = 'decimal';
      $tab[136]['massiveaction'] = false;
      $tab[136]['joinparams']    = $joinparamscost;
      $tab[136]['computation']   = "(SUM(TABLE.`cost`) / COUNT(TABLE.`id`)) * COUNT(DISTINCT TABLE.`id`)";

      $tab[137]['table']         = 'glpi_contracts';
      $tab[137]['field']         = 'billing';
      $tab[137]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Invoice period'));
      $tab[137]['forcegroupby']  = true;
      $tab[137]['massiveaction'] = false;
      $tab[137]['joinparams']    = $joinparams;
      $tab[137]['datatype']      = 'number';
      $tab[137]['min']           = 12;
      $tab[137]['max']           = 60;
      $tab[137]['step']          = 12;
      $tab[137]['toadd']         = array(0 => Dropdown::EMPTY_VALUE,
                                         1 => sprintf(_n('%d month', '%d months', 1), 1),
                                         2 => sprintf(_n('%d month', '%d months', 2), 2),
                                         3 => sprintf(_n('%d month', '%d months', 3), 3),
                                         6 => sprintf(_n('%d month', '%d months', 6), 6));
      $tab[137]['unit']          = 'month';

      $tab[138]['table']         = 'glpi_contracts';
      $tab[138]['field']         = 'renewal';
      $tab[138]['name']          = sprintf(__('%1$s - %2$s'), __('Contract'), __('Renewal'));
      $tab[138]['forcegroupby']  = true;
      $tab[138]['massiveaction'] = false;
      $tab[138]['joinparams']    = $joinparams;
      $tab[138]['datatype']      = 'specific';


      return $tab;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $prefix                    = 'Contract_Item'.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'add']    = _x('button', 'Add an item');
         $actions[$prefix.'remove'] = _x('button', 'Remove an item');
      }

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'alert' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return self::dropdownAlert($options);

         case 'renewal' :
            $options['name']  = $name;
            return self::dropdownContractRenewal($name, $values[$field], false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'alert' :
            return self::getAlertName($values[$field]);

         case 'renewal' :
            return self::getContractRenewalName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
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

      $tab[3]['table']              = $this->getTable();
      $tab[3]['field']              = 'num';
      $tab[3]['name']               = _x('phone', 'Number');
      $tab[3]['datatype']           = 'string';

      $tab[4]['table']              = 'glpi_contracttypes';
      $tab[4]['field']              = 'name';
      $tab[4]['name']               = __('Type');
      $tab[4]['datatype']           = 'dropdown';

      $tab[5]['table']              = $this->getTable();
      $tab[5]['field']              = 'begin_date';
      $tab[5]['name']               = __('Start date');
      $tab[5]['datatype']           = 'date';
      $tab[5]['maybefuture']        = true;

      $tab[6]['table']              = $this->getTable();
      $tab[6]['field']              = 'duration';
      $tab[6]['name']               = __('Duration');
      $tab[6]['datatype']           = 'number';
      $tab[6]['max']                = 120;
      $tab[6]['unit']               = 'month';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[121]['table']          = $this->getTable();
      $tab[121]['field']          = 'date_creation';
      $tab[121]['name']           = __('Creation date');
      $tab[121]['datatype']       = 'datetime';
      $tab[121]['massiveaction']  = false;

      $tab[20]['table']             = $this->getTable();
      $tab[20]['field']             = 'end_date';
      $tab[20]['name']              = __('End date');
      $tab[20]['datatype']          = 'date_delay';
      $tab[20]['datafields'][1]     = 'begin_date';
      $tab[20]['datafields'][2]     = 'duration';
      $tab[20]['searchunit']        = 'MONTH';
      $tab[20]['delayunit']         = 'MONTH';
      $tab[20]['maybefuture']       = true;
      $tab[20]['massiveaction']     = false;

      $tab[7]['table']              = $this->getTable();
      $tab[7]['field']              = 'notice';
      $tab[7]['name']               = __('Notice');
      $tab[7]['datatype']           = 'number';
      $tab[7]['max']                = 120;
      $tab[7]['unit']               = 'month';

      $tab[21]['table']             = $this->getTable();
      $tab[21]['field']             = 'periodicity';
      $tab[21]['name']              = __('Periodicity');
      $tab[21]['massiveaction']     = false;
      $tab[21]['datatype']          = 'number';
      $tab[21]['min']               = 12;
      $tab[21]['max']               = 60;
      $tab[21]['step']              = 12;
      $tab[21]['toadd']             = array(0 => Dropdown::EMPTY_VALUE,
                                            1 => sprintf(_n('%d month', '%d months', 1), 1),
                                            2 => sprintf(_n('%d month', '%d months', 2), 2),
                                            3 => sprintf(_n('%d month', '%d months', 3), 3),
                                            6 => sprintf(_n('%d month', '%d months', 6), 6));
      $tab[21]['unit']              = 'month';

      $tab[22]['table']             = $this->getTable();
      $tab[22]['field']             = 'billing';
      $tab[22]['name']              = __('Invoice period');
      $tab[22]['massiveaction']     = false;
      $tab[22]['datatype']          = 'number';
      $tab[22]['min']               = 12;
      $tab[22]['max']               = 60;
      $tab[22]['step']              = 12;
      $tab[22]['toadd']             = array(0 => Dropdown::EMPTY_VALUE,
                                            1 => sprintf(_n('%d month', '%d months', 1), 1),
                                            2 => sprintf(_n('%d month', '%d months', 2), 2),
                                            3 => sprintf(_n('%d month', '%d months', 3), 3),
                                            6 => sprintf(_n('%d month', '%d months', 6), 6));
      $tab[22]['unit']              = 'month';

      $tab[10]['table']             = $this->getTable();
      $tab[10]['field']             = 'accounting_number';
      $tab[10]['name']              = __('Account number');
      $tab[10]['datatype']          = 'string';

      $tab[23]['table']             = $this->getTable();
      $tab[23]['field']             = 'renewal';
      $tab[23]['name']              = __('Renewal');
      $tab[23]['massiveaction']     = false;
      $tab[23]['datatype']          = 'specific';
      $tab[23]['searchtype']        = array('equals', 'notequals');

      $tab[12]['table']             = $this->getTable();
      $tab[12]['field']             = 'expire';
      $tab[12]['name']              = __('Expiration');
      $tab[12]['datatype']          = 'date_delay';
      $tab[12]['datafields'][1]     = 'begin_date';
      $tab[12]['datafields'][2]     = 'duration';
      $tab[12]['searchunit']        = 'DAY';
      $tab[12]['delayunit']         = 'MONTH';
      $tab[12]['maybefuture']       = true;
      $tab[12]['massiveaction']     = false;

      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'expire_notice';
      $tab[13]['name']              = __('Expiration date + notice');
      $tab[13]['datatype']          = 'date_delay';
      $tab[13]['datafields'][1]     = 'begin_date';
      $tab[13]['datafields'][2]     = 'duration';
      $tab[13]['datafields'][3]     = 'notice';
      $tab[13]['searchunit']        = 'DAY';
      $tab[13]['delayunit']         = 'MONTH';
      $tab[13]['maybefuture']       = true;
      $tab[13]['massiveaction']     = false;

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['massiveaction']     = false;
      $tab[80]['datatype']          = 'dropdown';

      $tab[59]['table']             = $this->getTable();
      $tab[59]['field']             = 'alert';
      $tab[59]['name']              = __('Email alarms');
      $tab[59]['datatype']          = 'specific';
      $tab[59]['searchtype']        = array('equals', 'notequals');

      $tab[86]['table']             = $this->getTable();
      $tab[86]['field']             = 'is_recursive';
      $tab[86]['name']              = __('Child entities');
      $tab[86]['datatype']          = 'bool';

      $tab[72]['table']             = 'glpi_contracts_items';
      $tab[72]['field']             = 'id';
      $tab[72]['name']              = _x('quantity', 'Number of items');
      $tab[72]['forcegroupby']      = true;
      $tab[72]['usehaving']         = true;
      $tab[72]['datatype']          = 'count';
      $tab[72]['massiveaction']     = false;
      $tab[72]['joinparams']        = array('jointype' => 'child');

      $tab[29]['table']             = 'glpi_suppliers';
      $tab[29]['field']             = 'name';
      $tab[29]['name']              = _n('Associated supplier', 'Associated suppliers',
                                         Session::getPluralNumber());
      $tab[29]['forcegroupby']      = true;
      $tab[29]['datatype']          = 'itemlink';
      $tab[29]['massiveaction']     = false;
      $tab[29]['joinparams']        = array('beforejoin'
                                             => array('table'      => 'glpi_contracts_suppliers',
                                                      'joinparams' => array('jointype' => 'child')));


      // add objectlock search options
      $tab += ObjectLock::getSearchOptionsToAdd( get_class($this) ) ;

      $tab += Notepad::getSearchOptionsToAdd();

      $tab['cost']                  = __('Cost');

      $tab[11]['table']             = 'glpi_contractcosts';
      $tab[11]['field']             = 'totalcost';
      $tab[11]['name']              = __('Total cost');
      $tab[11]['datatype']          = 'decimal';
      $tab[11]['forcegroupby']      = true;
      $tab[11]['usehaving']         = true;
      $tab[11]['massiveaction']     = false;
      $tab[11]['joinparams']        = array('jointype' => 'child');
      $tab[11]['computation']       = "(SUM(TABLE.`cost`) / COUNT(TABLE.`id`))
                                       * COUNT(DISTINCT TABLE.`id`)";

      $tab[41]['table']             = 'glpi_contractcosts';
      $tab[41]['field']             = 'cost';
      $tab[41]['name']              = _n('Cost', 'Costs', Session::getPluralNumber());
      $tab[41]['datatype']          = 'decimal';
      $tab[41]['forcegroupby']      = true;
      $tab[41]['massiveaction']     = false;
      $tab[41]['joinparams']        = array('jointype' => 'child');

      $tab[42]['table']             = 'glpi_contractcosts';
      $tab[42]['field']             = 'begin_date';
      $tab[42]['name']              = sprintf(__('%1$s - %2$s'), __('Cost'), __('Begin date'));
      $tab[42]['datatype']          = 'date';
      $tab[42]['forcegroupby']      = true;
      $tab[42]['massiveaction']     = false;
      $tab[42]['joinparams']        = array('jointype' => 'child');

      $tab[43]['table']             = 'glpi_contractcosts';
      $tab[43]['field']             = 'end_date';
      $tab[43]['name']              = sprintf(__('%1$s - %2$s'), __('Cost'), __('End date'));
      $tab[43]['datatype']          = 'date';
      $tab[43]['forcegroupby']      = true;
      $tab[43]['massiveaction']     = false;
      $tab[43]['joinparams']        = array('jointype' => 'child');

      $tab[44]['table']             = 'glpi_contractcosts';
      $tab[44]['field']             = 'name';
      $tab[44]['name']              = sprintf(__('%1$s - %2$s'), __('Cost'), __('Name'));
      $tab[44]['forcegroupby']      = true;
      $tab[44]['massiveaction']     = false;
      $tab[44]['joinparams']        = array('jointype' => 'child');
      $tab[44]['datatype']          = 'dropdown';

      $tab[45]['table']             = 'glpi_budgets';
      $tab[45]['field']             = 'name';
      $tab[45]['name']              = sprintf(__('%1$s - %2$s'), __('Cost'), __('Budget'));
      $tab[45]['datatype']          = 'dropdown';
      $tab[45]['forcegroupby']      = true;
      $tab[45]['massiveaction']     = false;
      $tab[45]['joinparams']        = array('beforejoin'
                                             => array('table'      => 'glpi_contractcosts',
                                                      'joinparams' => array('jointype' => 'child')));


      return $tab;
   }


   /**
    * Show central contract resume
    * HTML array
    *
    * @return Nothing (display)
    **/
   static function showCentral() {
      global $DB,$CFG_GLPI;

      if (!Contract::canView()) {
         return false;
      }

      // No recursive contract, not in local management
      // contrats echus depuis moins de 30j
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>-30
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'0'";
      $result    = $DB->query($query);
      $contract0 = $DB->result($result,0,0);

      // contrats  echeance j-7
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<='7'";
      $result    = $DB->query($query);
      $contract7 = $DB->result($result, 0, 0);

      // contrats echeance j -30
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'30'";
      $result     = $DB->query($query);
      $contract30 = $DB->result($result,0,0);

      // contrats avec préavis echeance j-7
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<='7'";
      $result       = $DB->query($query);
      $contractpre7 = $DB->result($result,0,0);

      // contrats avec préavis echeance j -30
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0'".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<'30'";
      $result        = $DB->query($query);
      $contractpre30 = $DB->result($result,0,0);

      echo "<table class='tab_cadrehov'>";
      echo "<tr class='noHover'><th colspan='2'>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset\">".
             self::getTypeName(1)."</a></th></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['reset'] = 'reset';
      $options['sort']  = 12;
      $options['order'] = 'DESC';
      $options['start'] = 0;

      $options['criteria'][0] = array('field'      => 12,
                                      'value'      => '<0',
                                      'searchtype' => 'contains');
      $options['criteria'][1] = array('field'      => 12,
                                      'link'       => 'AND',
                                      'value'      => '>-30',
                                      'searchtype' => 'contains');

      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options,'&amp;')."\">".
                 __('Contracts expired in the last 30 days')."</a> </td>";
      echo "<td class='numeric'>".$contract0."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['value'] = 0;
      $options['criteria'][1]['value'] = '<7';
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options,'&amp;')."\">".
                 __('Contracts expiring in less than 7 days')."</a></td>";
      echo "<td class='numeric'>".$contract7."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['value'] = '>6';
      $options['criteria'][1]['value'] = '<30';
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options,'&amp;')."\">".
                 __('Contracts expiring in less than 30 days')."</a></td>";
      echo "<td class='numeric'>".$contract30."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['field'] = 13;
      $options['criteria'][0]['value'] = '>0';
      $options['criteria'][1]['field'] = 13;
      $options['criteria'][1]['value'] = '<7';

      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options,'&amp;')."\">".
                 __('Contracts where notice begins in less than 7 days')."</a></td>";
      echo "<td class='numeric'>".$contractpre7."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['value'] = '>6';
      $options['criteria'][1]['value'] = '<30';
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options,'&amp;')."\">".
                 __('Contracts where notice begins in less than 30 days')."</a></td>";
      echo "<td class='numeric'>".$contractpre30."</td></tr>";
      echo "</table>";
   }


   /**
    * Get the entreprise name  for the contract
    *
    *@return string of names (HTML)
   **/
   function getSuppliersNames() {
      global $DB;

      $query = "SELECT `glpi_suppliers`.`id`
                FROM `glpi_contracts_suppliers`,
                     `glpi_suppliers`
                WHERE `glpi_contracts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`
                      AND `glpi_contracts_suppliers`.`contracts_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
      $out    = "";
      while ($data = $DB->fetch_assoc($result)) {
         $out .= Dropdown::getDropdownName("glpi_suppliers", $data['id'])."<br>";
      }
      return $out;
   }


   static function cronInfo($name) {
      return array('description' => __('Send alarms on contracts'));
   }


   /**
    * Cron action on contracts : alert depending of the config : on notice and expire
    *
    * @param $task for log, if NULL display (default NULL)
   **/
   static function cronContract($task=NULL) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message       = array();
      $items_notice  = array();
      $items_end     = array();
      $cron_status   = 0;

      $contract_infos[Alert::END]    = array();
      $contract_infos[Alert::NOTICE] = array();
      $contract_messages             = array();

      foreach (Entity::getEntitiesToNotify('use_contracts_alert') as $entity => $value) {
         $before       = Entity::getUsedConfig('send_contracts_alert_before_delay', $entity);
         $query_notice = "SELECT `glpi_contracts`.*
                          FROM `glpi_contracts`
                          LEFT JOIN `glpi_alerts`
                              ON (`glpi_contracts`.`id` = `glpi_alerts`.`items_id`
                                  AND `glpi_alerts`.`itemtype` = 'Contract'
                                  AND `glpi_alerts`.`type`='".Alert::NOTICE."')
                          WHERE (`glpi_contracts`.`alert` & ".pow(2,Alert::NOTICE).") >'0'
                                AND `glpi_contracts`.`is_deleted` = '0'
                                AND `glpi_contracts`.`begin_date` IS NOT NULL
                                AND `glpi_contracts`.`duration` <> '0'
                                AND `glpi_contracts`.`notice` <> '0'
                                AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`,
                                                     INTERVAL `glpi_contracts`.`duration` MONTH),
                                             CURDATE()) > '0'
                                AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`,
                                                     INTERVAL (`glpi_contracts`.`duration`
                                                                -`glpi_contracts`.`notice`) MONTH),
                                             CURDATE()) < '$before'
                                AND `glpi_alerts`.`date` IS NULL
                                AND `glpi_contracts`.`entities_id` = '".$entity."'";

         $query_end = "SELECT `glpi_contracts`.*
                       FROM `glpi_contracts`
                       LEFT JOIN `glpi_alerts`
                           ON (`glpi_contracts`.`id` = `glpi_alerts`.`items_id`
                               AND `glpi_alerts`.`itemtype` = 'Contract'
                               AND `glpi_alerts`.`type`='".Alert::END."')
                       WHERE (`glpi_contracts`.`alert` & ".pow(2,Alert::END).") > '0'
                             AND `glpi_contracts`.`is_deleted` = '0'
                             AND `glpi_contracts`.`begin_date` IS NOT NULL
                             AND `glpi_contracts`.`duration` <> '0'
                             AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`,
                                                  INTERVAL (`glpi_contracts`.`duration`) MONTH),
                                          CURDATE()) < '$before'
                             AND `glpi_alerts`.`date` IS NULL
                             AND `glpi_contracts`.`entities_id` = '".$entity."'";

         $querys = array('notice' => $query_notice,
                         'end'    => $query_end);

         foreach ($querys as $type => $query) {
            foreach ($DB->request($query) as $data) {
               $entity  = $data['entities_id'];

               $message = sprintf(__('%1$s: %2$s')."<br>\n", $data["name"],
                                  Infocom::getWarrantyExpir($data["begin_date"],
                                                            $data["duration"], $data["notice"]));
               $data['items']      = Contract_Item::getItemsForContract($data['id'], $entity);
               $contract_infos[$type][$entity][$data['id']] = $data;

               if (!isset($contract_messages[$type][$entity])) {
                  switch ($type) {
                     case 'notice' :
                        $contract_messages[$type][$entity] = __('Contract entered in notice time').
                                                             "<br>";
                        break;

                     case 'end' :
                        $contract_messages[$type][$entity] = __('Contract ended')."<br>";
                        break;
                  }
               }
               $contract_messages[$type][$entity] .= $message;
            }
         }

         // Get contrats with periodicity alerts
         $query_periodicity = "SELECT `glpi_contracts`.*
                               FROM `glpi_contracts`
                               WHERE `glpi_contracts`.`alert` & ".pow(2,Alert::PERIODICITY)." > '0'
                                     AND `glpi_contracts`.`entities_id` = '".$entity."' ";

         // Foreach ones :
         foreach ($DB->request($query_periodicity) as $data) {
            $entity    = $data['entities_id'];
            // Compute end date + 12 month : do not send alerts after
            $end_alert = date('Y-m-d',
                              strtotime($data['begin_date']." +".($data['duration']+12)." month"));
            if (!empty($data['begin_date'])
                && $data['periodicity']
                && ($end_alert > date('Y-m-d'))) {
               $todo = array('periodicity' => Alert::PERIODICITY);
               if ($data['alert']&pow(2,Alert::NOTICE)) {
                  $todo['periodicitynotice'] = Alert::NOTICE;
               }

               // Get previous alerts
               foreach ($todo as $type => $event) {
                  $previous_alerts[$type] = Alert::getAlertDate(__CLASS__, $data['id'], $event);
               }
               // compute next alert date based on already send alerts (or not)
               foreach ($todo as $type => $event) {
                  $next_alerts[$type] = date('Y-m-d',
                                             strtotime($data['begin_date']." -".($before)." day"));
                  if ($type == Alert::NOTICE) {
                     $next_alerts[$type]
                           = date('Y-m-d',
                                  strtotime($next_alerts[$type]." -".($data['notice'])." month"));
                  }

                  $today_limit = date('Y-m-d',
                                      strtotime(date('Y-m-d')." -" .($data['periodicity'])." month"));

                  // Init previous by begin date if not set
                  if (empty($previous_alerts[$type])) {
                     $previous_alerts[$type] = $today_limit;
                  }

                  while (($next_alerts[$type] < $previous_alerts[$type])
                         && ($next_alerts[$type] < $end_alert)) {
                     $next_alerts[$type]
                        = date('Y-m-d',
                               strtotime($next_alerts[$type]." +".($data['periodicity'])." month"));
                  }

                  // If this date is passed : clean alerts and send again
                  if ($next_alerts[$type] <= date('Y-m-d')) {
                     $alert              = new Alert();
                     $alert->clear(__CLASS__, $data['id'], $event);
                     $real_alert_date    = date('Y-m-d',
                                                strtotime($next_alerts[$type]." +".($before)." day"));
                     $message            = sprintf(__('%1$s: %2$s')."<br>\n",
                                                 $data["name"], Html::convDate($real_alert_date));
                     $data['alert_date'] = $real_alert_date;
                     $contract_infos[$type][$entity][$data['id']] = $data;

                     switch ($type) {
                        case 'periodicitynotice' :
                           $contract_messages[$type][$entity]
                                 = __('Contract entered in notice time for period')."<br>";
                           break;

                        case 'periodicity' :
                           $contract_messages[$type][$entity] = __('Contract period ended')."<br>";
                           break;
                     }
                     $contract_messages[$type][$entity] .= $message;
                  }
               }
            }
         }
      }
      foreach (array('notice'            => Alert::NOTICE,
                     'end'               => Alert::END,
                     'periodicity'       => Alert::PERIODICITY,
                     'periodicitynotice' => Alert::NOTICE) as $event => $type ) {
         if (isset($contract_infos[$event]) && count($contract_infos[$event])) {
            foreach ($contract_infos[$event] as $entity => $contracts) {
               if (NotificationEvent::raiseEvent($event, new self(),
                                                 array('entities_id' => $entity,
                                                       'items'       => $contracts))) {
                  $message     = $contract_messages[$event][$entity];
                  $cron_status = 1;
                  $entityname  = Dropdown::getDropdownName("glpi_entities", $entity);
                  if ($task) {
                     $task->log(sprintf(__('%1$s: %2$s')."\n", $entityname, $message));
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'),
                                                              $entityname, $message));
                  }

                  $alert               = new Alert();
                  $input["itemtype"]   = __CLASS__;
                  $input["type"]       = $type;
                  foreach ($contracts as $id => $contract) {
                     $input["items_id"] = $id;

                     $alert->add($input);
                     unset($alert->fields['id']);
                  }

               } else {
                  $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
                  //TRANS: %1$s is entity name, %2$s is the message
                  $msg = sprintf(__('%1$s: %2$s'), $entityname, __('send contract alert failed'));
                  if ($task) {
                     $task->log($msg);
                  } else {
                     Session::addMessageAfterRedirect($msg, false, ERROR);
                  }
               }
            }
         }
      }

      return $cron_status;
   }


   /**
    * Print a select with contracts
    *
    * Print a select named $name with contracts options and selected value $value
    * @param $options   array of possible options:
    *    - name          : string / name of the select (default is contracts_id)
    *    - value         : integer / preselected value (default 0)
    *    - entity        : integer or array / restrict to a defined entity or array of entities
    *                      (default -1 : no restriction)
    *    - rand          : (defauolt mt_rand)
    *    - entity_sons   : boolean / if entity restrict specified auto select its sons
    *                      only available if entity is a single value not an array (default false)
    *    - used          : array / Already used items ID: not to display in dropdown (default empty)
    *    - nochecklimit  : boolean / disable limit for nomber of device (for supplier, default false)
    *    - on_change     : string / value to transmit to "onChange"
    *    - display       : boolean / display or return string (default true)
    *    - expired       : boolean / display expired contract (default false)
    *
    * @return Nothing (display)
   **/
   static function dropdown($options=array()) {
      global $DB;

      //$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
      $p['name']           = 'contracts_id';
      $p['value']          = '';
      $p['entity']         = '';
      $p['rand']           = mt_rand();
      $p['entity_sons']    = false;
      $p['used']           = array();
      $p['nochecklimit']   = false;
      $p['on_change']      = '';
      $p['display']        = true;
      $p['expired']        = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!($p['entity'] < 0)
          && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            // no translation needed (only for dev)
            echo "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities',$p['entity']);
         }
      }

      $entrest = "";
      $idrest = "";
      $expired = "";
      if ($p['entity'] >= 0) {
         $entrest = getEntitiesRestrictRequest("AND", "glpi_contracts", "entities_id",
                                               $p['entity'], true);
      }
      if (count($p['used'])) {
          $idrest = " AND `glpi_contracts`.`id` NOT IN (".implode(",",$p['used']).") ";
      }
      if (!$p['expired']) {
         $expired = " AND (DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                               `glpi_contracts`.`duration` MONTH), CURDATE()) > '0'
                           OR `glpi_contracts`.`begin_date` IS NULL
                           OR (`glpi_contracts`.`duration` = 0
                               AND DATEDIFF(`glpi_contracts`.`begin_date`, CURDATE() ) < '0' ))";
      }


      $query = "SELECT `glpi_contracts`.*
                FROM `glpi_contracts`
                LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id` = `glpi_entities`.`id`)
                WHERE `glpi_contracts`.`is_deleted` = '0' AND `glpi_contracts`.`is_template` = '0'
                $entrest $idrest $expired
                ORDER BY `glpi_entities`.`completename`,
                         `glpi_contracts`.`name` ASC,
                         `glpi_contracts`.`begin_date` DESC";
      $result = $DB->query($query);


      $group  = '';
      $prev   = -1;
      $values = array();
      while ($data = $DB->fetch_assoc($result)) {
         if ($p['nochecklimit']
             || ($data["max_links_allowed"] == 0)
             || ($data["max_links_allowed"] > countElementsInTable("glpi_contracts_items",
                                                                   "contracts_id
                                                                     = '".$data['id']."'" ))) {
            if ($data["entities_id"] != $prev) {
               $group = Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
               $prev = $data["entities_id"];
            }

            $name = $data["name"];
            if ($_SESSION["glpiis_ids_visible"]
                || empty($data["name"])) {
               $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
            }

            $tmp = sprintf(__('%1$s - %2$s'), $name, $data["num"]);
            $tmp = sprintf(__('%1$s - %2$s'), $tmp, Html::convDateTime($data["begin_date"]));
            $values[$group][$data['id']] = $tmp;
         }
      }
      return Dropdown::showFromArray($p['name'], $values,
                                     array('value'               => $p['value'],
                                           'on_change'           => $p['on_change'],
                                           'display'             => $p['display'],
                                           'display_emptychoice' => true));
   }


   /**
    * Print a select with contract renewal
    *
    * Print a select named $name with contract renewal options and selected value $value
    *
    * @param $name      string   HTML select name
    * @param $value     integer  HTML select selected value (default = 0)
    * @param $display   boolean  get or display string ? (true by default)
    *
    * @return Nothing (display)
   **/
   static function dropdownContractRenewal($name, $value=0, $display=true) {

      $tmp[0] = __('Never');
      $tmp[1] = __('Tacit');
      $tmp[2] = __('Express');
      return Dropdown::showFromArray($name, $tmp, array('value'   => $value,
                                                        'display' => $display));
   }


   /**
    * Get the renewal type name
    *
    * @param $value integer   HTML select selected value
    *
    * @return string
   **/
   static function getContractRenewalName($value) {

      switch ($value) {
         case 0 :
            return __('Never');

         case 1 :
            return __('Tacit');

         case 2 :
            return __('Express');

         default :
            return "";
      }
   }


   /**
    * Get renewal ID by name
    *
    * @param $value the name of the renewal
    *
    * @return the ID of the renewal
   **/
   static function getContractRenewalIDByName($value) {

      if (stristr($value, __('Tacit'))) {
         return 1;
      }
      if (stristr($value, __('Express'))) {
         return 2;
      }
      return 0;
   }


   /**
    * @param $options array
   **/
   static function dropdownAlert(array $options) {

      $p['name']           = 'alert';
      $p['value']          = 0;
      $p['display']        = true;
      $p['inherit_parent'] = false;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = array();
      if ($p['inherit_parent']) {
         $tab[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $tab += self::getAlertName();

      return Dropdown::showFromArray($p['name'], $tab, $p);
   }


   /**
    * Get the possible value for contract alert
    *
    * @since version 0.83
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getAlertName($val=NULL) {

      $tmp[0]                                                  = Dropdown::EMPTY_VALUE;
      $tmp[pow(2, Alert::END)]                                 = __('End');
      $tmp[pow(2, Alert::NOTICE)]                              = __('Notice');
      $tmp[(pow(2, Alert::END) + pow(2, Alert::NOTICE))]       = __('End + Notice');
      $tmp[pow(2, Alert::PERIODICITY)]                         = __('Period end');
      $tmp[pow(2, Alert::PERIODICITY) + pow(2, Alert::NOTICE)] = __('Period end + Notice');

      if (is_null($val)) {
         return $tmp;
      }
      // Default value for display
      $tmp[0] = ' ';

      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      // If not set and is a string return value
      if (is_string($val)) {
         return $val;
      }
      return NOT_AVAILABLE;
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      $options['entities_id'] = $this->getEntityID();
      $options['contracts']   = array();
      $options['items']       = array();
      NotificationEvent::debugEvent($this, $options);
   }


   function getUnallowedFieldsForUnicity() {

      return array_merge(parent::getUnallowedFieldsForUnicity(),
                         array('begin_date', 'duration', 'entities_id', 'monday_begin_hour',
                               'monday_end_hour', 'saturday_begin_hour', 'saturday_end_hour',
                               'week_begin_hour', 'week_end_hour'));
   }



   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getMassiveActionsForItemtype()
   **/
   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted=0,
                                                CommonDBTM $checkitem=NULL) {
      global $CFG_GLPI;

      if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
         if (self::canUpdate()) {
            $action_prefix                    = 'Contract_Item'.MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$action_prefix.'add']    = _x('button', 'Add a contract');
            $actions[$action_prefix.'remove'] = _x('button', 'Remove a contract');
         }
      }
   }

}
?>
