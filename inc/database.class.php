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
 * Database Class
**/
class Database extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action   = true;
   static $rightname                   = 'database';

   // From CommonDBChild
   static public $itemtype       = 'DatabaseInstance';
   static public $items_id       = 'databaseinstances_id';

   static function getTypeName($nb = 0) {
      return _n('Database', 'Databases', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }


   function showForm($ID, $options = []) {
      $rand = mt_rand();
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      $rand = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      echo "<td><label for='textfield_name$rand'>".__('Name') . "</label></td>";
      echo "<td>";
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $this->fields["name"],
            'rand'      => $rand
         ]
      );
      echo "</td>";
      echo "<td><label for='dropdown_states_id$rand'>".__('Status')."</label></td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_visible_database' => 1],
         'rand'      => $rand
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_locations_id$rand'>".Location::getTypeName(1)."</label></td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
         'entity' => $this->fields["entities_id"],
         'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_databaseservertypes_id$rand'>".DatabaseInstanceType::getFieldLabel()."</label></td>";
      echo "<td>";
      DatabaseInstanceType::dropdown(['value' => $this->fields["databaseservertypes_id"], 'rand' => $rand]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='version$rand'>"._n('Version', 'Versions', 1)."</label></td>";
      echo "<td>";
      echo Html::input(
         'version', [
            'id' => 'version'.$rand,
            'value' => $this->fields['version']
         ]
      );
      echo "</td>";
      echo "<td><label for='dropdown_databaseservercategories_id$rand'>".DatabaseInstanceCategory::getTypeName(1)."</label></td>";
      echo "<td>";
      DatabaseInstanceCategory::dropdown(['value' => $this->fields["databaseservercategories_id"], 'rand' => $rand]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='is_active$rand'>".__('Is active')."</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "<td>" . __('Associable to a ticket') . "</td><td>";
      Dropdown::showYesNo('is_helpdesk_visible', $this->fields['is_helpdesk_visible']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_groups_id_tech$rand'>".__('Group in charge of the hardware')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'name'      => 'groups_id_tech',
         'value'     => $this->fields['groups_id_tech'],
         'entity'    => $this->fields['entities_id'],
         'condition' => ['is_assign' => 1],
         'rand' => $rand
      ]);

      echo "</td>";

      $rowspan        = 3;

      echo "<td rowspan='$rowspan'><label for='comment'>".__('Comments')."</label></td>";
      echo "<td rowspan='$rowspan' class='middle'>";

      echo "<textarea cols='45' rows='".($rowspan+2)."' id='comment' name='comment' >".
         $this->fields["comment"];
      echo "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_users_id_tech$rand'>".__('Technician in charge of the hardware')."</label></td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
         'value'  => $this->fields["users_id_tech"],
         'right'  => 'own_ticket',
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand]);
      echo "</td></tr>";
      echo "<tr><td><label for='dropdown_manufacturers_id$rand'>".Manufacturer::getTypeName(1)."</label></td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"], 'rand' => $rand]);
      echo "</td></tr>\n";

      if ($this->isNewItem($ID)) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='4' class='center'>".__('Default instance')."</th>";
         echo "</tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='_instance_port$rand'>"._n('Port', 'Ports', 1)."</label></td>";
         echo "<td>";
         echo Html::input(
            '_instance_port', [
               'type' => 'number',
               'id' => '_instance_port'.$rand
            ]
         );

         echo "</td>";
         echo "<td><label for='dropdown__instance_is_onbackup$rand'>".__('Has backup')."</label></td>";
         echo "<td>";
         Dropdown::showYesNo('_instance_is_onbackup', 0, -1, ['rand' => $rand]);
         echo "</td></tr>\n";
      }

      $this->showInventoryInfo();

      $this->showFormButtons($options);

      return true;
   }



   function post_addItem() {
      //create default instance
      $this->getFromDB($this->fields['id']);
      $instance = new DatabaseInstance();

      if ((!isset($this->input['_instance_name']) || empty($this->input['_instance_name'])) && isset($this->input['name'])) {
         $this->input['_instance_name'] = sprintf(
            //TRANS: first parameter is the database name
            __('"%1$s" default instance'),
            $this->input['name']
         );
      }

      $instance->add([
         'name' => ($this->input['_instance_name'] ?? $this->input['name']),
         'port' => ($this->input['_instance_port'] ?? 0),
         'size' => ($this->input['_instance_size'] ?? 0),
         'is_onbackup' => ($this->input['_instance_is_onbackup'] ?? 0),
         'is_active' => ($this->input['_instance_is_active'] ?? $this->fields['is_active']),
         'entities_id' => $this->fields['entities_id'],
         'is_recursive' => $this->fields['is_recursive'],
         'databaseinstances_id' => $this->fields['id'],
         'date_lastboot' => ($this->input['_instance_date_lastboot'] ?? null),
         'date_lastbackup' => ($this->input['_instance_date_lastbackup'] ?? null),
         'is_dynamic' => $this->fields['is_dynamic']
      ]);

      parent::post_addItem();
   }

   static function getIcon() {
      return "fas fa-database";
   }

   public function getInstances(): array {
      global $DB;
      $instances = [];

      $iterator = $DB->request([
         'FROM' => DatabaseInstance::getTable(),
         'WHERE' => ['databaseinstances_id' => $this->fields['id']]
      ]);

      while ($row = $iterator->next()) {
         $instances[] = $row;
      }

      return $instances;
   }

   /**
    * Get item types that can be linked to a database
    *
    * @param boolean $all Get all possible types or only allowed ones
    *
    * @return array
    */
   public static function getTypes($all = false): array {
      global $CFG_GLPI;

      $types = $CFG_GLPI['database_types'];

      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }

         if ($all === false && !$type::canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   function cleanDBonPurge() {
      $this->deleteChildrenAndRelationsFromDb(
         [
            DatabaseInstance::class,
            DatabaseInstance_Item::class
         ]
      );
   }

   public function pre_purgeInventory() {
      return true;
   }

   static public function rawSearchOptionsToAdd() {
      $tab = [];
      $name = self::getTypeName(Session::getPluralNumber());

      $tab[] = [
         'id'                 => 'database',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '167',
         'table'              => self::getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '166',
         'table'              => self::getTable(),
         'field'              => 'size',
         'name'               => __('Size'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'integer',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '169',
         'table'              => self::getTable(),
         'field'              => 'is_active',
         'linkfield'          => '',
         'name'               => __('Active'),
         'datatype'           => 'bool',
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'searchtype'         => ['equals']
      ];

      $tab[] = [
         'id'                 => '170',
         'table'              => self::getTable(),
         'field'              => 'is_onbackup',
         'linkfield'          => '',
         'name'               => __('Is on backup'),
         'datatype'           => 'bool',
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'searchtype'         => ['equals']
      ];

      $tab[] = [
         'id'                 => '171',
         'table'              => self::getTable(),
         'field'              => 'date_lastboot',
         'name'               => __('Last boot date'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'date',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '172',
         'table'              => self::getTable(),
         'field'              => 'date_lastbackup',
         'name'               => __('Last backup date'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'date',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      return $tab;
   }
}
