<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * Network equipment Class
**/
class NetworkEquipment extends CommonDBTM {
   use Glpi\Features\DCBreadcrumb;
   use Glpi\Features\Clonable;

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = ['Infocom', 'NetworkPort', 'ReservationItem',
                                          'Item_OperatingSystem', 'Item_Disk', 'Item_SoftwareVersion'];

   static $rightname                   = 'networking';
   protected $usenotepad               = true;

   /** RELATIONS */
   public function getCloneRelations() :array {
      return [
         Item_OperatingSystem::class,
         Item_Devices::class,
         Infocom::class,
         NetworkPort::class,
         Contract_Item::class,
         Document_Item::class,
         KnowbaseItem_Item::class
      ];
   }
   /** /RELATIONS */


   /**
    * Name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
   **/
   static function getTypeName($nb = 0) {
      return _n('Network device', 'Network devices', $nb);
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since 0.85
   **/
   static function getAdditionalMenuOptions() {

      if (static::canView()) {
         $options = [
            'networkport' => [
               'title' => NetworkPort::getTypeName(Session::getPluralNumber()),
               'page'  => NetworkPort::getFormURL(false),
            ],
         ];
         return $options;
      }
      return false;
   }


   /**
    * @see CommonGLPI::getMenuName()
    *
    * @since 0.85
   **/
   // bug in translation: https://github.com/glpi-project/glpi/issues/1970
   /*static function getMenuName() {
      return _n('Network', 'Networks', Session::getPluralNumber());
   }*/


   /**
    * @since 0.84
    *
    * @see CommonDBTM::cleanDBonPurge()
   **/
   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Certificate_Item::class,
            Item_Project::class,
         ]
      );

      Item_Devices::cleanItemDeviceDBOnItemDelete($this->getType(), $this->fields['id'],
                                                  (!empty($this->input['keep_devices'])));
   }


   /**
    * @see CommonDBTM::useDeletedToLockIfDynamic()
    *
    * @since 0.84
   **/
   function useDeletedToLockIfDynamic() {
      return false;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Item_OperatingSystem', $ong, $options)
         ->addStandardTab('Item_SoftwareVersion', $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('Item_Disk', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('NetworkName', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Link', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Reservation', $ong, $options)
         ->addStandardTab('Certificate_Item', $ong, $options)
         ->addStandardTab('Domain_Item', $ong, $options)
         ->addStandardTab('Appliance_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   /**
    * Can I change recursive flag to false
    * check if there is "linked" object in another entity
    *
    * Overloaded from CommonDBTM
    *
    * @return boolean
   **/
   function canUnrecurs() {
      global $DB;

      $ID = $this->fields['id'];
      if (($ID < 0)
          || !$this->fields['is_recursive']) {
         return true;
      }
      if (!parent::canUnrecurs()) {
         return false;
      }
      $entities = getAncestorsOf("glpi_entities", $this->fields['entities_id']);
      $entities[] = $this->fields['entities_id'];

      // RELATION : networking -> _port -> _wire -> _port -> device

      // Evaluate connection in the 2 ways
      foreach (["networkports_id_1" => "networkports_id_2",
                "networkports_id_2" => "networkports_id_1"] as $enda => $endb) {

         $criteria = [
            'SELECT'       => [
               'itemtype',
               new QueryExpression('GROUP_CONCAT(DISTINCT '.$DB->quoteName('items_id').') AS '.$DB->quoteName('ids'))
            ],
            'FROM'         => 'glpi_networkports_networkports',
            'INNER JOIN'   => [
               'glpi_networkports'  => [
                  'ON'  => [
                     'glpi_networkports_networkports' => $endb,
                     'glpi_networkports'              => 'id'
                  ]
               ]
            ],
            'WHERE'        => [
               'glpi_networkports_networkports.'.$enda   => new QuerySubQuery([
                  'SELECT' => 'id',
                  'FROM'   => 'glpi_networkports',
                  'WHERE'  => [
                     'itemtype'  => $this->getType(),
                     'items_id'  => $ID
                  ]
               ])
            ],
            'GROUPBY'      => 'itemtype'
         ];

         $res = $DB->request($criteria);
         if ($res) {
            while ($data = $res->next()) {
               $itemtable = getTableForItemType($data["itemtype"]);
               if ($item = getItemForItemtype($data["itemtype"])) {
                  // For each itemtype which are entity dependant
                  if ($item->isEntityAssign()) {
                     if (countElementsInTable($itemtable, ['id' => $data["ids"],
                                              'NOT' => ['entities_id' => $entities ]]) > 0) {
                         return false;
                     }
                  }
               }
            }
         }
      }
      return true;
   }


   /**
    * Print the networking form
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

      $tplmark = $this->getAutofillMark('name', $options);
      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'), $tplmark).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, "name", ['value' => $objectName]);
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_visible_networkequipment' => 1]
      ]);
      echo "</td></tr>";

      $this->showDcBreadcrumb();

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Location::getTypeName(1)."</td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                              'entity' => $this->fields["entities_id"]]);
      echo "</td>";
      echo "<td>"._n('Type', 'Types', 1)."</td>";
      echo "<td>";
      NetworkEquipmentType::dropdown(['value' => $this->fields["networkequipmenttypes_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]]);
      echo "</td>";
      echo "<td>".Manufacturer::getTypeName(1)."</td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown([
         'name'      => 'groups_id_tech',
         'value'     => $this->fields['groups_id_tech'],
         'entity'    => $this->fields['entities_id'],
         'condition' => ['is_assign' => 1]
      ]);
      echo "</td>";
      echo "<td>"._n('Model', 'Models', 1)."</td>";
      echo "<td>";
      NetworkEquipmentModel::dropdown(['value' => $this->fields["networkequipmentmodels_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "contact_num");
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "serial");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "contact");
      echo "</td>";

      $tplmark = $this->getAutofillMark('otherserial', $options);
      echo "<td>".sprintf(__('%1$s%2$s'), __('Inventory number'), $tplmark).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, "otherserial", ['value' => $objectName]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".User::getTypeName(1)."</td>";
      echo "<td>";
      User::dropdown(['value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all']);
      echo "</td>";
      echo "<td>"._n('Network', 'Networks', 1)."</td>";
      echo "<td>";
      Network::dropdown(['value' => $this->fields["networks_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Group::getTypeName(1)."</td>";
      echo "<td>";
      Group::dropdown([
         'value'     => $this->fields["groups_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_itemgroup' => 1]
      ]);
      echo "</td>";
      $rowspan = 3;
      echo "<td rowspan='$rowspan'>" . __('Comments') . "</td>";
      echo "<td rowspan='$rowspan'>";
      Html::textarea(['name'  => 'comment',
                      'value' => $this->fields["comment"],
                      'cols'  => '45',
                      'rows'  => '10']);

      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan=2>".__('The MAC address and the IP of the equipment are included in an aggregated network port')."</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s (%2$s)'), _n('Memory', 'Memories', 1), __('Mio'))."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ram");
      echo "</td></tr>";

      // Display auto inventory information
      if (!empty($ID)
         && $this->fields["is_dynamic"]) {
         echo "<tr class='tab_bg_1'><td colspan='4'>";
         Plugin::doHook("autoinventory_information", $this);
         echo "</td></tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions += [
            'Item_SoftwareLicense'.MassiveAction::CLASS_ACTION_SEPARATOR.'add'
               => "<i class='ma-icon fas fa-key'></i>".
                  _x('button', 'Add a license')
         ];
         KnowbaseItem_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);
      }

      return $actions;
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_networkequipmenttypes',
         'field'              => 'name',
         'name'               => _n('Type', 'Types', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => 'glpi_networkequipmentmodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_networkequipment' => 1]
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'contact',
         'name'               => __('Alternate username'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'contact_num',
         'name'               => __('Alternate username number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => User::getTypeName(1),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '71',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => Group::getTypeName(1),
         'datatype'           => 'dropdown',
         'condition'          => ['is_itemgroup' => 1]
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
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => 'glpi_devicefirmwares',
         'field'              => 'version',
         'name'               => _n('Firmware', 'Firmware', 1),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicefirmwares',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => 'NetworkEquipment'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'ram',
         'name'               => sprintf(__('%1$s (%2$s)'), _n('Memory', 'Memories', 1), __('Mio')),
         'datatype'           => 'number',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => 'glpi_networks',
         'field'              => 'name',
         'name'               => _n('Network', 'Networks', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => Manufacturer::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

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
         'condition'          => ['is_assign' => 1],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '65',
         'table'              => $this->getTable(),
         'field'              => 'template_name',
         'name'               => __('Template name'),
         'datatype'           => 'text',
         'massiveaction'      => false,
         'nosearch'           => true,
         'nodisplay'          => true,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => Entity::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      // add operating system search options
      $tab = array_merge($tab, Item_OperatingSystem::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab = array_merge($tab, Item_Devices::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

      return $tab;
   }

   static function getIcon() {
      return "fas fa-network-wired";
   }
}
