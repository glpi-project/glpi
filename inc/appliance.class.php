<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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
   die("Sorry. You can't access directly to this file");
}

class Appliance extends CommonDBTM {
   use Glpi\Features\Clonable;

   public $dohistory     = true;
   static $rightname     = "appliance";
   protected $usenotepad = true;

   public function getCloneRelations() :array {
      return [
         Infocom::class,
         Contract_Item::class,
         Document_Item::class,
         KnowbaseItem_Item::class
      ];
   }

   static function getTypeName($nb = 0) {
      return _n('Appliance', 'Appliances', $nb);
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = ['id'            => 'common',
                'name'          => $this->getTypeName(Session::getPluralNumber())];

      $tab[] = ['id'            => '1',
               'table'          => $this->getTable(),
               'field'          => 'name',
                'name'          => __('Name'),
                'datatype'      => 'itemlink',
                'massiveaction' => false,
                'autocomplete'  => true,];

      $tab[] = ['id'            => '2',
                'table'         => ApplianceType::getTable(),
                'field'         => 'name',
                'name'          => __('Type'),
                'datatype'      => 'dropdown'];

      $tab[] = ['id'            => '32',
                'table'         => State::getTable(),
                'field'         => 'completename',
                'name'          => __('Status'),
                'datatype'      => 'dropdown'];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = ['id'            => '4',
                'table'         => self::getTable(),
                'field'         =>  'comment',
                'name'          =>  __('Comments'),
                'datatype'      =>  'text'];

      $tab[] = ['id'            => '5',
                'table'         =>  Appliance_Item::getTable(),
                'field'         => 'items_id',
                'name'          => _n('Associated item', 'Associated items', Session::getPluralNumber()),
                'massiveaction' => false,
                'forcegroupby'  =>  true,
                'joinparams'    => ['jointype' => 'child']];

      $tab[] = ['id'            => '6',
                'table'         => User::getTable(),
                'field'         => 'name',
                'name'          => User::getTypeName(1),
                'datatype'      => 'dropdown'];

      $tab[] = ['id'            => '8',
                'table'         => Group::getTable(),
                'field'         => 'completename',
                'name'          => Group::getTypeName(1),
                'condition'     => ['is_itemgroup' => 1],
                'datatype'      => 'dropdown'];

      $tab[] = ['id'            => '24',
                'table'         => User::getTable(),
                'field'         => 'name',
                'linkfield'     => 'users_id_tech',
                'name'          => __('Technician in charge'),
                'datatype'      => 'dropdown',
                'right'         => 'own_ticket'];

      $tab[] = ['id'            => '49',
                'table'         => Group::getTable(),
                'field'         => 'completename',
                'linkfield'     => 'groups_id_tech',
                'name'          => __('Group in charge'),
                'condition'     => ['is_assign' => 1],
                'datatype'      => 'dropdown'];

      $tab[] = ['id'            => '9',
                'table'         => self::getTable(),
                'field'         => 'date_mod',
                'name'          => __('Last update'),
                'massiveaction' => false,
                'datatype'      => 'datetime'];

      $tab[] = ['id'            => '10',
                'table'         => ApplianceEnvironment::getTable(),
                'field'         => 'name',
                'name'          => __('Environment'),
                'datatype'      => 'dropdown'];

      $tab[] = ['id'            => '12',
                'table'         => self::getTable(),
                'field'         => 'serial',
                'name'          => __('Serial number'),
                'autocomplete'  => true,];

      $tab[] = ['id'            => '13',
                'table'         => self::getTable(),
                'field'         => 'otherserial',
                'name'          => __('Inventory number'),
                'autocomplete'  => true,];

      $tab[] = ['id'            => '31',
                'table'         => self::getTable(),
                'field'         => 'id',
                'name'          => __('ID'),
                'datatype'      => 'number',
                'massiveaction' => false];

      $tab[] = ['id'            => '80',
                'table'         => 'glpi_entities',
                'field'         => 'completename',
                'name'          => Entity::getTypeName(1),
                'datatype'      => 'dropdown'];

      $tab[] = ['id'            => '7',
                'table'         => self::getTable(),
                'field'         => 'is_recursive',
                'name'          => __('Child entities'),
                'massiveaction' => false,
                'datatype'      => 'bool'];

      $tab[] = ['id'            => '81',
                'table'         => Entity::getTable(),
                'field'         => 'entities_id',
                'name'          => Entity::getTypeName(1) . "-" . __('ID')];

      return $tab;
   }


   function cleanDBonPurge() {
      $temp = new Appliance_Item();
      $temp->deleteByCriteria(['appliances_id' => $this->fields['id']], true);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Appliance_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


    /**
     * Print appliance
     *
     * @param integer $ID      ID
     * @param array   $options Options
     *
     * @return bool
     */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $canedit = $this->can($ID, UPDATE);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td><td>";
      Html::autocompletionTextField($this, "name", ['size' => 34]);
      echo "</td><td>".__('Status')."</td><td>";
      if ($canedit) {
         State::dropdown(['value' => $this->fields["states_id"]]);
      } else {
         echo Dropdown::getDropdownName("glpi_states", $this->fields["states_id"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Location::getTypeName(1)."</td><td>";
      if ($canedit) {
         Location::dropdown([
            'value'  => $this->fields["locations_id"],
            'entity' => $this->fields["entities_id"]
         ]);
      } else {
         echo Dropdown::getDropdownName("glpi_locations", $this->fields["locations_id"]);
      }
      echo "</td><td>".__('Type')."</td><td>";
      if ($canedit) {
         Dropdown::show(
            'ApplianceType', [
               'value'  => $this->fields["appliancetypes_id"],
               'entity' => $this->fields["entities_id"]
            ]
         );
      } else {
         echo Dropdown::getDropdownName("appliancetypes", $this->fields["appliancetypes_id"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td><td>";
      if ($canedit) {
         User::dropdown([
            'name'   => 'users_id_tech',
            'value'  => $this->fields['users_id_tech'],
            'right'  => 'own_ticket',
            'entity' => $this->fields['entities_id']
         ]);
      } else {
         echo getUserName($this->fields['users_id_tech']);
      }
      echo "</td><td>".__('Environment')."</td><td>";
      if ($canedit) {
         Dropdown::show(
            'ApplianceEnvironment', [
               'value' => $this->fields["applianceenvironments_id"]
            ]
         );
      } else {
         echo Dropdown::getDropdownName("ApplianceEnvironment", $this->fields["applianceenvironments_id"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td><td>";
      if ($canedit) {
         Group::dropdown([
            'name'      => 'groups_id_tech',
            'value'     => $this->fields['groups_id_tech'],
            'entity'    => $this->fields['entities_id'],
            'condition' => ['is_assign' => 1]
         ]);
      } else {
         echo Dropdown::getDropdownName("glpi_groups", $this->fields["groups_id_tech"]);
      }
      echo "</td><td>".__('Serial number')."</td><td>";
      Html::autocompletionTextField($this, 'serial');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".User::getTypeName(1)."</td>";
      echo "<td>";
      if ($canedit) {
         User::dropdown([
            'value'  => $this->fields["users_id"],
            'entity' => $this->fields["entities_id"],
            'right'  => 'all'
         ]);
      } else {
         echo getUserName($this->fields['users_id']);
      }
      echo "</td><td>".__('Inventory number')."</td><td>";
      Html::autocompletionTextField($this, 'otherserial');
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Group::getTypeName(1)."</td>";
      echo "<td>";
      if ($canedit) {
         Group::dropdown([
            'value'     => $this->fields["groups_id"],
            'entity'    => $this->fields["entities_id"],
            'condition' => ['is_itemgroup' => 1]
         ]);
      } else {
         echo Dropdown::getDropdownName("glpi_groups", $this->fields["groups_id"]);
      }
      echo "</td>";
      echo "<td rowspan='2'>".__('Comments')."</td>";
      echo "<td rowspan='2' class='middle'>";
      echo "<textarea cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      // dropdown relationtype added
      echo "<td>".__('Item to link')."</td><td>";
      $count = countElementsInTable([
         'glpi_appliancerelations',
         'glpi_appliances_items'
      ], [
         'glpi_appliances_items.appliances_id' => $ID,
         'FKEY' => [
            'glpi_appliancerelations' => 'appliances_items_id',
            'glpi_appliances_items'   => 'id',
         ],
      ]);
      if ($canedit && !($ID && $count)) {
         ApplianceRelation::dropdownType(
            "relationtype",
            $this->fields["relationtype"] ?? ''
         );
      } else {
         echo ApplianceRelation::getTypeName($this->fields["relationtype"] ?? '');
         $rand    = mt_rand();
         $comment = __('Flag change forbidden. Linked items found.');
         $image   = "/pics/lock.png";
         echo "&nbsp;<img alt='' src='".$CFG_GLPI["root_doc"].$image.
               "' onmouseout=\"cleanhide('comment_relationtypes$rand')\" ".
               " onmouseover=\"cleandisplay('comment_relationtypes$rand')\">";
         echo "<span class='over_link' id='comment_relationtypes$rand'>$comment</span>";
      }
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Diplay a dropdown to select an Appliance in massive action
    *
    * @see CommonDBTM::dropdown()
   **/
   static function dropdownMA($options = []) {
      global $DB, $CFG_GLPI;

      $p = [
         'name'    => 'appliances_id',
         'entity'  => '',
         'used'    => [],
         'display' => true
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $sub_query = new \QuerySubQuery([
         'SELECT'    => 'appliancetypes_id',
         'DISTINCT'  => true,
         'FROM'      => self::getTable(),
         'WHERE'     => [self::getTable() . '.is_deleted' => 0] +
                        getEntitiesRestrictCriteria('glpi_appliances', '', $p['entity'], true)]);

      $p['used'] = array_filter($p['used']);
      if (count($p['used'])) {
         $sub_query['WHERE'][] = ['NOT' => ['id', $p['used']]];
      }

      $query = [
         'FROM'   => ApplianceType::getTable(),
         'WHERE'  => ['id' => $sub_query],
         'ORDER'  => 'name'
      ];

      $result = $DB->request($query);

      $values = [0 => Dropdown::EMPTY_VALUE];

      while ($data =$result->next()) {
         $values[$data['id']] = $data['name'];
      }
      $rand     = mt_rand();
      $out      = Dropdown::showFromArray(
         '_appliancetype',
         $values, [
            'width'   => '30%',
            'rand'    => $rand,
            'display' => false
         ]
      );
      $field_id = Html::cleanId("dropdown__appliancetype$rand");

      $params   = [
         'appliancetype' => '__VALUE__',
         'entity'        => $p['entity'],
         'rand'          => $rand,
         'myname'        => $p['name'],
         'used'          => $p['used']
      ];

      $out .= Ajax::updateItemOnSelectEvent(
         $field_id,
         "show_".$p['name'].$rand,
         $CFG_GLPI["root_doc"]."/ajax/dropdownTypeAppliances.php",
         $params,
         false
      );
      $out .= "<span id='show_".$p['name']."$rand'>";
      $out .= "</span>\n";

      $params['appliancetype'] = 0;
      $out .= Ajax::updateItem(
         "show_".$p['name'].$rand,
         $CFG_GLPI["root_doc"]. "/ajax/dropdownTypeAppliances.php",
         $params,
         '',
         false
      );
      if ($p['display']) {
         echo $out;
         return $rand;
      }
      return $out;
   }


   /**
    * Type than could be linked to a Appliance
    *
    * @param boolean $all All types  or only allowed ones (defauts to false)
    *
    * @return array of types
   **/
   static function getTypes($all = false) {
      global $CFG_GLPI;

      $types = $CFG_GLPI['appliance_types'];

      if ($all) {
         return $types;
      }

      // Only allowed types
      foreach ($types as $key => $type) {
         if (!($item = getItemForItemtype($type))) {
            continue;
         }

         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
         if ($isadmin) {
            $actions['Appliance'.MassiveAction::CLASS_ACTION_SEPARATOR.'install'] = _x('button', 'Associate');
            $actions['Appliance'.MassiveAction::CLASS_ACTION_SEPARATOR.'uninstall'] = _x('button', 'Dissociate');
         }
      }

      KnowbaseItem_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);

      return $actions;
   }


   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add_item':
            self::dropdownMA([]);
            echo "&nbsp;". Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;

         case "uninstall":
         case "install" :
            Dropdown::showSelectItemFromItemtypes([
               'items_id_name' => 'item_item',
               'itemtype_name' => 'typeitem',
               'itemtypes'     => self::getTypes(true),
               'checkright'    => true
            ]);
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;

      }
      return parent::showMassiveActionsSubForm($ma);
   }


   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {

      $appliance_item = new Appliance_Item();

      switch ($ma->getAction()) {
         case "add_item":
            $input = $ma->getInput();
            foreach ($ids as $id) {
               $input = [
                  'appliances_id'   => $input['appliances_id'],
                  'items_id'        => $id,
                  'itemtype'        => $item->getType()
               ];
               if ($appliance_item->can(-1, CREATE, $input)) {
                  if ($appliance_item->getFromDBByCrit($input) || $appliance_item->add($input)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case 'install' :
            $input = $ma->getInput();
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  $values = [
                     'appliances_id'   => $key,
                     'items_id'        => $input["item_item"],
                     'itemtype'        => $input['typeitem']
                  ];
                  $exists = $appliance_item->getFromDBByCrit($values);
                  if ($exists || $appliance_item->add($values)) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case 'uninstall':
            $input = $ma->getInput();
            foreach ($ids as $key) {
               if ($appliance_item->deleteItemByAppliancesAndItem($key, $input['item_item'], $input['typeitem'])) {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   static function getIcon() {
      return "fas fa-cubes";
   }
}
