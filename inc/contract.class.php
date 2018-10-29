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
 *  Contract class
 */
class Contract extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = ['ContractCost'];

   static $rightname                   = 'contract';
   protected $usenotepad               = true;



   static function getTypeName($nb = 0) {
      return _n('Contract', 'Contracts', $nb);
   }


   function post_getEmpty() {

      $this->fields["alert"] = Entity::getUsedConfig("use_contracts_alert",
                                                     $this->fields["entities_id"],
                                                     "default_contract_alert", 0);
      $this->fields["notice"] = 0;
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Contract_Item::class,
            Contract_Supplier::class,
            ContractCost::class,
         ]
      );

      // Alert does not extends CommonDBConnexity
      $alert = new Alert();
      $alert->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('ContractCost', $ong, $options);
      $this->addStandardTab('Contract_Supplier', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * Duplicate all contracts from a item template to his clone
    *
    * @since 9.2
    *
    * @param string $itemtype      itemtype of the item
    * @param integer $oldid        ID of the item to clone
    * @param integer $newid        ID of the item cloned
    **/
   static function cloneItem($itemtype, $oldid, $newid) {
      global $DB;

      $result = $DB->request(
         [
            'FROM'   => Contract_Item::getTable(),
            'WHERE'  => [
               'items_id' => $oldid,
               'itemtype' => $itemtype,
            ],
         ]
      );
      foreach ($result as $data) {
         $cd = new Contract_Item();
         unset($data['id']);
         $data['items_id'] = $newid;
         $data             = Toolbox::addslashes_deep($data);

         $cd->add($data);
      }
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {

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
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td><td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Contract type')."</td><td >";
      ContractType::dropdown(['value' => $this->fields["contracttypes_id"]]);
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
      Html::showDateField("begin_date", ['value' => $this->fields["begin_date"]]);
      echo "</td>";
      echo "<td>".__('Initial contract period')."</td><td>";
      Dropdown::showNumber("duration", ['value' => $this->fields["duration"],
                                             'min'   => 1,
                                             'max'   => 120,
                                             'step'  => 1,
                                             'toadd' => [0 => Dropdown::EMPTY_VALUE],
                                             'unit'  => 'month']);
      if (!empty($this->fields["begin_date"])) {
         echo " -> ".Infocom::getWarrantyExpir($this->fields["begin_date"],
                                               $this->fields["duration"], 0, true);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Notice')."</td><td>";
      Dropdown::showNumber("notice", ['value' => $this->fields["notice"],
                                           'min'   => 0,
                                           'max'   => 120,
                                           'step'  => 1,
                                           'toadd' => [],
                                           'unit'  => 'month']);
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
                           ['value' => $this->fields["periodicity"],
                                 'min'   => 12,
                                 'max'   => 60,
                                 'step'  => 12,
                                 'toadd' => [0 => Dropdown::EMPTY_VALUE,
                                                  1 => sprintf(_n('%d month', '%d months', 1), 1),
                                                  2 => sprintf(_n('%d month', '%d months', 2), 2),
                                                  3 => sprintf(_n('%d month', '%d months', 3), 3),
                                                  6 => sprintf(_n('%d month', '%d months', 6), 6)],
                                 'unit'  => 'month']);
      echo "</td>";
      echo "<td>".__('Invoice period')."</td>";
      echo "<td>";
      Dropdown::showNumber("billing",
                           ['value' => $this->fields["billing"],
                                 'min'   => 12,
                                 'max'   => 60,
                                 'step'  => 12,
                                 'toadd' => [0 => Dropdown::EMPTY_VALUE,
                                                  1 => sprintf(_n('%d month', '%d months', 1), 1),
                                                  2 => sprintf(_n('%d month', '%d months', 2), 2),
                                                  3 => sprintf(_n('%d month', '%d months', 3), 3),
                                                  6 => sprintf(_n('%d month', '%d months', 6), 6)],
                                 'unit'  => 'month']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Renewal')."</td><td>";
      self::dropdownContractRenewal("renewal", $this->fields["renewal"]);
      echo "</td>";
      echo "<td>".__('Max number of items')."</td><td>";
      Dropdown::showNumber("max_links_allowed", ['value' => $this->fields["max_links_allowed"],
                                                      'min'   => 1,
                                                      'max'   => 200,
                                                      'step'  => 1,
                                                      'toadd' => [0 => __('Unlimited')]]);
      echo "</td></tr>";

      if (Entity::getUsedConfig("use_contracts_alert", $this->fields["entities_id"])) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Email alarms')."</td>";
         echo "<td>";
         self::dropdownAlert(['name'  => "alert",
                                   'value' => $this->fields["alert"]]);
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
      Dropdown::showHours("week_begin_hour", ['value' => $this->fields["week_begin_hour"]]);
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('End')."</span></td><td width='20%'>";
      Dropdown::showHours("week_end_hour", ['value' => $this->fields["week_end_hour"]]);
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
                          ['value' => $this->fields["saturday_begin_hour"]]);
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('End')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("saturday_end_hour",
                          ['value' => $this->fields["saturday_end_hour"]]);
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
      Dropdown::showHours("monday_begin_hour", ['value' => $this->fields["monday_begin_hour"]]);
      echo "</td><td width='20%'>";
      echo "<span class='small_space'>".__('End')."</span>";
      echo "</td><td width='20%'>";
      Dropdown::showHours("monday_end_hour", ['value' => $this->fields["monday_end_hour"]]);
      echo "</td></tr></table>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   static function rawSearchOptionsToAdd() {
      $tab = [];

      $joinparams = [
         'beforejoin' => [
            'table'      => 'glpi_contracts_items',
            'joinparams' => [
               'jointype' => 'itemtype_item'
            ]
         ]
      ];

      $joinparamscost = [
         'jointype'   => 'child',
         'beforejoin' => [
            'table'      => 'glpi_contracts',
            'joinparams' => $joinparams
         ]
      ];

      $tab[] = [
         'id'                 => 'contract',
         'name'               => self::getTypeName(Session::getPluralNumber())
      ];

      $tab[] = [
         'id'                 => '139',
         'table'              => 'glpi_contracts_items',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of contracts'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item'
         ]
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => 'glpi_contracts',
         'field'              => 'name',
         'name'               => __('Name'),
         'forcegroupby'       => true,
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => 'glpi_contracts',
         'field'              => 'num',
         'name'               => __('Number'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '129',
         'table'              => 'glpi_contracttypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_contracts',
               'joinparams'         => $joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '130',
         'table'              => 'glpi_contracts',
         'field'              => 'duration',
         'name'               => __('Duration'),
         'datatype'           => 'number',
         'max'                => '120',
         'unit'               => 'month',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '131',
         'table'              => 'glpi_contracts',
         'field'              => 'periodicity',
                                 //TRANS: %1$s is Contract, %2$s is field name
         'name'               => __('Periodicity'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams,
         'datatype'           => 'number',
         'min'                => '12',
         'max'                => '60',
         'step'               => '12',
         'toadd'              => [
            0 => Dropdown::EMPTY_VALUE,
            1 => sprintf(_n('%d month', '%d months', 1), 1),
            2 => sprintf(_n('%d month', '%d months', 2), 2),
            3 => sprintf(_n('%d month', '%d months', 3), 3),
            6 => sprintf(_n('%d month', '%d months', 6), 6)
         ],
         'unit'               => 'month'
      ];

      $tab[] = [
         'id'                 => '132',
         'table'              => 'glpi_contracts',
         'field'              => 'begin_date',
         'name'               => __('Start date'),
         'forcegroupby'       => true,
         'datatype'           => 'date',
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '133',
         'table'              => 'glpi_contracts',
         'field'              => 'accounting_number',
         'name'               => __('Account number'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '134',
         'table'              => 'glpi_contracts',
         'field'              => 'end_date',
         'name'               => __('End date'),
         'forcegroupby'       => true,
         'datatype'           => 'date_delay',
         'datafields'         => [
            '1'                  => 'begin_date',
            '2'                  => 'duration'
         ],
         'searchunit'         => 'MONTH',
         'delayunit'          => 'MONTH',
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '135',
         'table'              => 'glpi_contracts',
         'field'              => 'notice',
         'name'               => __('Notice'),
         'datatype'           => 'number',
         'max'                => '120',
         'unit'               => 'month',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '136',
         'table'              => 'glpi_contractcosts',
         'field'              => 'totalcost',
         'name'               => __('Cost'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'decimal',
         'massiveaction'      => false,
         'joinparams'         => $joinparamscost,
         'computation'        => '(SUM(TABLE.`cost`) / COUNT(TABLE.`id`)) * COUNT(DISTINCT TABLE.`id`)'
      ];

      $tab[] = [
         'id'                 => '137',
         'table'              => 'glpi_contracts',
         'field'              => 'billing',
         'name'               => __('Invoice period'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams,
         'datatype'           => 'number',
         'min'                => '12',
         'max'                => '60',
         'step'               => '12',
         'toadd'              => [
            0 => Dropdown::EMPTY_VALUE,
            1 => sprintf(_n('%d month', '%d months', 1), 1),
            2 => sprintf(_n('%d month', '%d months', 2), 2),
            3 => sprintf(_n('%d month', '%d months', 3), 3),
            6 => sprintf(_n('%d month', '%d months', 6), 6)
         ],
         'unit'               => 'month'
      ];

      $tab[] = [
         'id'                 => '138',
         'table'              => 'glpi_contracts',
         'field'              => 'renewal',
         'name'               => __('Renewal'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams,
         'datatype'           => 'specific'
      ];

      return $tab;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $prefix                    = 'Contract_Item'.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'add']    = _x('button', 'Add an item');
         $actions[$prefix.'remove'] = _x('button', 'Remove an item');
      }

      return $actions;
   }


   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
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


   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'alert' :
            return self::getAlertName($values[$field]);

         case 'renewal' :
            return self::getContractRenewalName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
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
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'num',
         'name'               => _x('phone', 'Number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_contracttypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'begin_date',
         'name'               => __('Start date'),
         'datatype'           => 'date',
         'maybefuture'        => true
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'duration',
         'name'               => __('Duration'),
         'datatype'           => 'number',
         'max'                => 120,
         'unit'               => 'month'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => $this->getTable(),
         'field'              => 'end_date',
         'name'               => __('End date'),
         'datatype'           => 'date_delay',
         'datafields'         => [
            '1'                  => 'begin_date',
            '2'                  => 'duration'
         ],
         'searchunit'         => 'MONTH',
         'delayunit'          => 'MONTH',
         'maybefuture'        => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'notice',
         'name'               => __('Notice'),
         'datatype'           => 'number',
         'max'                => 120,
         'unit'               => 'month'
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'periodicity',
         'name'               => __('Periodicity'),
         'massiveaction'      => false,
         'datatype'           => 'number',
         'min'                => 12,
         'max'                => 60,
         'step'               => 12,
         'toadd'              => [
            0 => Dropdown::EMPTY_VALUE,
            1 => sprintf(_n('%d month', '%d months', 1), 1),
            2 => sprintf(_n('%d month', '%d months', 2), 2),
            3 => sprintf(_n('%d month', '%d months', 3), 3),
            6 => sprintf(_n('%d month', '%d months', 6), 6)
         ],
         'unit'               => 'month'
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => $this->getTable(),
         'field'              => 'billing',
         'name'               => __('Invoice period'),
         'massiveaction'      => false,
         'datatype'           => 'number',
         'min'                => 12,
         'max'                => 60,
         'step'               => 12,
         'toadd'              => [
            0 => Dropdown::EMPTY_VALUE,
            1 => sprintf(_n('%d month', '%d months', 1), 1),
            2 => sprintf(_n('%d month', '%d months', 2), 2),
            3 => sprintf(_n('%d month', '%d months', 3), 3),
            6 => sprintf(_n('%d month', '%d months', 6), 6)
         ],
         'unit'               => 'month'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'accounting_number',
         'name'               => __('Account number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'renewal',
         'name'               => __('Renewal'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => ['equals', 'notequals']
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'expire',
         'name'               => __('Expiration'),
         'datatype'           => 'date_delay',
         'datafields'         => [
            '1'                  => 'begin_date',
            '2'                  => 'duration'
         ],
         'searchunit'         => 'DAY',
         'delayunit'          => 'MONTH',
         'maybefuture'        => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'expire_notice',
         'name'               => __('Expiration date + notice'),
         'datatype'           => 'date_delay',
         'datafields'         => [
            '1'                  => 'begin_date',
            '2'                  => 'duration',
            '3'                  => 'notice'
         ],
         'searchunit'         => 'DAY',
         'delayunit'          => 'MONTH',
         'maybefuture'        => true,
         'massiveaction'      => false
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
         'id'                 => '59',
         'table'              => $this->getTable(),
         'field'              => 'alert',
         'name'               => __('Email alarms'),
         'datatype'           => 'specific',
         'searchtype'         => ['equals', 'notequals']
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '72',
         'table'              => 'glpi_contracts_items',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of items'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => 'glpi_suppliers',
         'field'              => 'name',
         'name'               => _n('Associated supplier', 'Associated suppliers',
                                     Session::getPluralNumber()),
         'forcegroupby'       => true,
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_contracts_suppliers',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => 'cost',
         'name'               => __('Cost')
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => 'glpi_contractcosts',
         'field'              => 'totalcost',
         'name'               => __('Total cost'),
         'datatype'           => 'decimal',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'computation'        => '(SUM(TABLE.`cost`) / COUNT(TABLE.`id`))
                                       * COUNT(DISTINCT TABLE.`id`)'
      ];

      $tab[] = [
         'id'                 => '41',
         'table'              => 'glpi_contractcosts',
         'field'              => 'cost',
         'name'               => _n('Cost', 'Costs', Session::getPluralNumber()),
         'datatype'           => 'decimal',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => 'glpi_contractcosts',
         'field'              => 'begin_date',
         'name'               => sprintf(__('%1$s - %2$s'), __('Cost'), __('Begin date')),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '43',
         'table'              => 'glpi_contractcosts',
         'field'              => 'end_date',
         'name'               => sprintf(__('%1$s - %2$s'), __('Cost'), __('End date')),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => 'glpi_contractcosts',
         'field'              => 'name',
         'name'               => sprintf(__('%1$s - %2$s'), __('Cost'), __('Name')),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '45',
         'table'              => 'glpi_budgets',
         'field'              => 'name',
         'name'               => sprintf(__('%1$s - %2$s'), __('Cost'), __('Budget')),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_contractcosts',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      return $tab;
   }


   /**
    * Show central contract resume
    * HTML array
    *
    * @return void
    **/
   static function showCentral() {
      global $DB,$CFG_GLPI;

      if (!Contract::canView()) {
         return;
      }

      // No recursive contract, not in local management
      // contrats echus depuis moins de 30j
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND", "glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>-30
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'0'";
      $result    = $DB->query($query);
      $contract0 = $DB->result($result, 0, 0);

      // contrats  echeance j-7
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND", "glpi_contracts")."
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
                      getEntitiesRestrictRequest("AND", "glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'30'";
      $result     = $DB->query($query);
      $contract30 = $DB->result($result, 0, 0);

      // contrats avec pr??avis echeance j-7
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND", "glpi_contracts")."
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<='7'";
      $result       = $DB->query($query);
      $contractpre7 = $DB->result($result, 0, 0);

      // contrats avec pr??avis echeance j -30
      $query = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0'".
                      getEntitiesRestrictRequest("AND", "glpi_contracts")."
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<'30'";
      $result        = $DB->query($query);
      $contractpre30 = $DB->result($result, 0, 0);

      echo "<table class='tab_cadrehov'>";
      echo "<tr class='noHover'><th colspan='2'>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset\">".
             self::getTypeName(1)."</a></th></tr>";

      echo "<tr class='tab_bg_2'>";
      $options = [
         'reset' => 'reset',
         'sort'  => 12,
         'order' => 'DESC',
         'start' => 0,
         'criteria' => [
            [
               'field'      => 12,
               'value'      => '<0',
               'searchtype' => 'contains',
            ],
            [
               'field'      => 12,
               'link'       => 'AND',
               'value'      => '>-30',
               'searchtype' => 'contains',
            ]
         ]
      ];
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options, '&amp;')."\">".
                 __('Contracts expired in the last 30 days')."</a> </td>";
      echo "<td class='numeric'>".$contract0."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['value'] = 0;
      $options['criteria'][1]['value'] = '<7';
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options, '&amp;')."\">".
                 __('Contracts expiring in less than 7 days')."</a></td>";
      echo "<td class='numeric'>".$contract7."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['value'] = '>6';
      $options['criteria'][1]['value'] = '<30';
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options, '&amp;')."\">".
                 __('Contracts expiring in less than 30 days')."</a></td>";
      echo "<td class='numeric'>".$contract30."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['field'] = 13;
      $options['criteria'][0]['value'] = '>0';
      $options['criteria'][1]['field'] = 13;
      $options['criteria'][1]['value'] = '<7';

      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options, '&amp;')."\">".
                 __('Contracts where notice begins in less than 7 days')."</a></td>";
      echo "<td class='numeric'>".$contractpre7."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      $options['criteria'][0]['value'] = '>6';
      $options['criteria'][1]['value'] = '<30';
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?".
                 Toolbox::append_params($options, '&amp;')."\">".
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
      return ['description' => __('Send alarms on contracts')];
   }


   /**
    * Cron action on contracts : alert depending of the config : on notice and expire
    *
    * @param CronTask $task for log, if NULL display (default NULL)
    *
    * @return integer
   **/
   static function cronContract(CronTask $task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_notifications"]) {
         return 0;
      }

      $message       = [];
      $cron_status   = 0;

      $contract_infos    = [
         Alert::END    => [],
         Alert::NOTICE => [],
      ];
      $contract_messages = [];

      foreach (Entity::getEntitiesToNotify('use_contracts_alert') as $entity => $value) {
         $before       = Entity::getUsedConfig('send_contracts_alert_before_delay', $entity);

         $query_notice = [
            'SELECT'    => [
               'glpi_contracts.*',
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
               'glpi_alerts' => [
                  'FKEY' => [
                     'glpi_alerts'    => 'items_id',
                     'glpi_contracts' => 'id',
                     [
                        'AND' => [
                           'glpi_alerts.itemtype' => 'Contract',
                           'glpi_alerts.type'     => Alert::NOTICE,
                        ],
                     ],
                  ]
               ]
            ],
            'WHERE'     => [
               [
                  'RAW' => [
                     DBmysql::quoteName('glpi_contracts.alert') . ' & ' . pow(2, Alert::NOTICE) => ['>', 0]
                  ]
               ],
               'glpi_alerts.date'           => null,
               'glpi_contracts.is_deleted'  => 0,
               [
                  'NOT' => ['glpi_contracts.begin_date' => null],
               ],
               'glpi_contracts.duration'    => ['!=', 0],
               'glpi_contracts.notice'      => ['!=', 0],
               'glpi_contracts.entities_id' => $entity,
               [
                  'RAW' => [
                     'DATEDIFF(
                         ADDDATE(
                            ' . DBmysql::quoteName('glpi_contracts.begin_date') . ',
                            INTERVAL ' . DBmysql::quoteName('glpi_contracts.duration') . ' MONTH
                         ),
                         CURDATE()
                      )' => ['>', 0]
                  ]
               ],
               [
                  'RAW' => [
                     'DATEDIFF(
                         ADDDATE(
                            ' . DBmysql::quoteName('glpi_contracts.begin_date') . ',
                            INTERVAL (
                               ' . DBmysql::quoteName('glpi_contracts.duration') . '
                               - ' . DBmysql::quoteName('glpi_contracts.notice') . '
                            ) MONTH
                         ),
                         CURDATE()
                      )' => ['<', $before]
                  ]
               ],
            ],
         ];

         $query_end = [
            'SELECT'    => [
               'glpi_contracts.*',
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
               'glpi_alerts' => [
                  'FKEY' => [
                     'glpi_alerts'    => 'items_id',
                     'glpi_contracts' => 'id',
                     [
                        'AND' => [
                           'glpi_alerts.itemtype' => 'Contract',
                           'glpi_alerts.type'     => Alert::END,
                        ],
                     ],
                  ]
               ]
            ],
            'WHERE'     => [
               [
                  'RAW' => [
                     DBmysql::quoteName('glpi_contracts.alert') . ' & ' . pow(2, Alert::END) => ['>', 0]
                  ]
               ],
               'glpi_alerts.date'           => null,
               'glpi_contracts.is_deleted'  => 0,
               [
                  'NOT' => ['glpi_contracts.begin_date' => null],
               ],
               'glpi_contracts.duration'    => ['!=', 0],
               'glpi_contracts.entities_id' => $entity,
               [
                  'RAW' => [
                     'DATEDIFF(
                         ADDDATE(
                            ' . DBmysql::quoteName('glpi_contracts.begin_date') . ',
                            INTERVAL ' . DBmysql::quoteName('glpi_contracts.duration') . ' MONTH
                         ),
                         CURDATE()
                      )' => ['<', $before]
                  ]
               ],
            ],
         ];

         $querys = ['notice' => $query_notice,
                         'end'    => $query_end];

         foreach ($querys as $type => $query) {
            $result = $DB->request($query);
            foreach ($result as $data) {
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
         $valPow = pow(2, Alert::PERIODICITY);
         $query_periodicity = ['FROM' => 'glpi_contracts',
             'WHERE' => ['alert' => ['&', $valPow],
                 'entities_id' => $entity,
                 'is_deleted' => 0
             ]
         ];

         // Foreach ones :
         foreach ($DB->request($query_periodicity) as $data) {
            $entity = $data['entities_id'];

            // For contracts with begin date and periodicity
            if (!empty($data['begin_date']) && $data['periodicity']) {
               $todo = ['periodicity' => Alert::PERIODICITY];
               if ($data['alert'] & pow(2, Alert::NOTICE)) {
                  $todo['periodicitynotice'] = Alert::NOTICE;
               }

               // For the todo...
               foreach ($todo as $type => $event) {
                  /**
                   * Previous alert
                   */
                  // Get previous alerts from DB
                  $previous_alert = [
                     $type => Alert::getAlertDate(__CLASS__, $data['id'], $event),
                  ];
                  // If alert never occurs...
                  if (empty($previous_alert[$type])) {
                     // We define it a long time ago [in a galaxy far, far away... ;-)]
                     $previous_alert[$type] = date('Y-m-d', 0);
                  }

                  /**
                   * Next alert
                   */
                  // Computation of first alert : Contract [begin date + periodicity] - Config [alert xxx days before]
                  $next_alert = [
                     $type => date('Y-m-d', strtotime($data['begin_date'] . " +" . $data['periodicity'] . " month -" . ($before) . " day")),
                  ];
                  // If a notice is defined
                  if ($event == Alert::NOTICE) {
                     // Will decrease of the Contract notice duration
                     $next_alert[$type] = date('Y-m-d', strtotime($next_alert[$type] . " -" . ($data['notice']) . " month"));
                  }

                  // Computation of contract renewal
                  while ($next_alert[$type] < $previous_alert[$type]) {
                     // Increasing of Contract periodicity...
                     $next_alert[$type] = date('Y-m-d', strtotime($next_alert[$type] . " +" . ($data['periodicity']) . " month"));
                  }

                  // If this date is passed : clean alerts and send again
                  if ($next_alert[$type] <= date('Y-m-d')) {
                     $alert = new Alert();
                     $alert->clear(__CLASS__, $data['id'], $event);
                     // Computation of the real date => add Config [alert xxx days before]
                     $real_alert_date = date('Y-m-d', strtotime($next_alert[$type] . " +" . ($before) . " day"));
                     $message = sprintf(__('%1$s: %2$s') . "<br>\n", $data["name"], Html::convDate($real_alert_date));
                     $data['alert_date'] = $real_alert_date;
                     $contract_infos[$type][$entity][$data['id']] = $data;

                     switch ($type) {
                        case 'periodicitynotice' :
                           $contract_messages[$type][$entity] = __('Contract entered in notice time for period') . "<br>";
                           break;

                        case 'periodicity' :
                           $contract_messages[$type][$entity] = __('Contract period ended') . "<br>";
                           break;
                     }
                     $contract_messages[$type][$entity] .= $message;
                  }
               }
            }
         }
      }
      foreach (['notice'            => Alert::NOTICE,
                  'end'               => Alert::END,
                  'periodicity'       => Alert::PERIODICITY,
                  'periodicitynotice' => Alert::NOTICE] as $event => $type ) {
         if (isset($contract_infos[$event]) && count($contract_infos[$event])) {
            foreach ($contract_infos[$event] as $entity => $contracts) {
               if (NotificationEvent::raiseEvent($event, new self(),
                               ['entities_id' => $entity,
                                                       'items'       => $contracts])) {
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

                  $alert = new Alert();
                  $input = [
                     'itemtype' => __CLASS__,
                     'type'     => $type,
                  ];
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
    * @param array $options
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
    * @return string|integer HTML output, or random part of dropdown ID.
   **/
   static function dropdown($options = []) {
      global $DB;

      //$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
      $p = [
         'name'           => 'contracts_id',
         'value'          => '',
         'entity'         => '',
         'rand'           => mt_rand(),
         'entity_sons'    => false,
         'used'           => [],
         'nochecklimit'   => false,
         'on_change'      => '',
         'display'        => true,
         'expired'        => false,
      ];

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
            $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
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
          $idrest = " AND `glpi_contracts`.`id` NOT IN (".implode(",", $p['used']).") ";
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
                WHERE `glpi_contracts`.`is_deleted` = 0 AND `glpi_contracts`.`is_template` = 0
                $entrest $idrest $expired
                ORDER BY `glpi_entities`.`completename`,
                         `glpi_contracts`.`name` ASC,
                         `glpi_contracts`.`begin_date` DESC";
      $result = $DB->query($query);

      $group  = '';
      $prev   = -1;
      $values = [];
      while ($data = $DB->fetch_assoc($result)) {
         if ($p['nochecklimit']
             || ($data["max_links_allowed"] == 0)
             || ($data["max_links_allowed"] > countElementsInTable('glpi_contracts_items',
                                                                   ['contracts_id' => $data['id']]))) {
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
                                     ['value'               => $p['value'],
                                           'on_change'           => $p['on_change'],
                                           'display'             => $p['display'],
                                           'display_emptychoice' => true]);
   }


   /**
    * Print a select with contract renewal
    *
    * Print a select named $name with contract renewal options and selected value $value
    *
    * @param string  $name    HTML select name
    * @param integer $value   HTML select selected value (default = 0)
    * @param boolean $display get or display string ? (true by default)
    *
    * @return string|integer HTML output, or random part of dropdown ID.
   **/
   static function dropdownContractRenewal($name, $value = 0, $display = true) {

      $values = [
         __('Never'),
         __('Tacit'),
         __('Express'),
      ];
      return Dropdown::showFromArray($name, $values, ['value'   => $value,
                                                        'display' => $display]);
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
    * @param string $value the name of the renewal
    *
    * @return integer ID of the renewal
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
    * @param array $options
    *
    * @return string|integer HTML output, or random part of dropdown ID.
   **/
   static function dropdownAlert(array $options) {

      $p = [
         'name'           => 'alert',
         'value'          => 0,
         'display'        => true,
         'inherit_parent' => false,
      ];

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = [];
      if ($p['inherit_parent']) {
         $tab[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $tab += self::getAlertName();

      return Dropdown::showFromArray($p['name'], $tab, $p);
   }


   /**
    * Get the possible value for contract alert
    *
    * @since 0.83
    *
    * @param string|integer|null $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return string|string[]
   **/
   static function getAlertName($val = null) {

      $names = [
         0                                                  => Dropdown::EMPTY_VALUE,
         pow(2, Alert::END)                                 => __('End'),
         pow(2, Alert::NOTICE)                              => __('Notice'),
         (pow(2, Alert::END) + pow(2, Alert::NOTICE))       => __('End + Notice'),
         pow(2, Alert::PERIODICITY)                         => __('Period end'),
         pow(2, Alert::PERIODICITY) + pow(2, Alert::NOTICE) => __('Period end + Notice'),
      ];

      if (is_null($val)) {
         return $names;
      }
      // Default value for display
      $names[0] = ' ';

      if (isset($names[$val])) {
         return $names[$val];
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

      $options = [
         'entities_id' => $this->getEntityID(),
         'contracts'   => [],
         'items'       => [],
      ];
      NotificationEvent::debugEvent($this, $options);
   }


   function getUnallowedFieldsForUnicity() {

      return array_merge(parent::getUnallowedFieldsForUnicity(),
                         ['begin_date', 'duration', 'entities_id', 'monday_begin_hour',
                               'monday_end_hour', 'saturday_begin_hour', 'saturday_end_hour',
                               'week_begin_hour', 'week_end_hour']);
   }


   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0,
                                                CommonDBTM $checkitem = null) {
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
