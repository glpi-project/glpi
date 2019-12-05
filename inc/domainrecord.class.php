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

class DomainRecord extends CommonDBChild {

   static $rightname              = 'domain';
   // From CommonDBChild
   static public $itemtype        = 'Domain';
   static public $items_id        = 'domains_id';
   public $dohistory              = true;

   static function getTypeName($nb = 0) {
      return _n('Domain record', 'Domains records', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate) {
         if ($item->getType() == 'Domain') {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(_n('Record', 'Records', Session::getPluralNumber()), self::countForDomain($item));
            }
            return _n('Record', 'Records', Session::getPluralNumber());
         }
      }
      return '';
   }

   static function countForDomain(Domain $item) {
      return countElementsInTable(
         self::getTable(), [
            "domains_id"   => $item->getID(),
         ]
      );
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Domain') {
         self::showForDomain($item);
      }
      return true;
   }

   function rawSearchOptions() {
      $tab = [];

      $tab = array_merge($tab, parent::rawSearchOptions());

      /*$tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'itemlink_type'      => $this->getType(),
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_domaintypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'date_expiration',
         'name'               => __('Expiration date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => 'glpi_domains_items',
         'field'              => 'items_id',
         'nosearch'           => true,
         'massiveaction'      => false,
         'name'               => _n('Associated items', 'Associated items', 2),
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'others',
         'name'               => __('Others')
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => 'glpi_groups',
         'field'              => 'name',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => '`is_assign`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'is_helpdesk_visible',
         'name'               => __('Associable to a ticket'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'massiveaction'      => false,
         'name'               => __('Last update'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '81',
         'table'              => 'glpi_entities',
         'field'              => 'entities_id',
         'name'               => __('Entity-ID')
      ];*/

      return $tab;
   }

   /*public static function rawSearchOptionsToAdd($itemtype = null) {
      $tab = [];

      if (in_array($itemtype, Domain::getTypes(true))) {
         if (Session::haveRight("domain", READ)) {
            $tab[] = [
               'id'                 => 'domain',
               'name'               => self::getTypeName(Session::getPluralNumber())
            ];

            $tab[] = [
               'id'                 => '205',
               'table'              => Domain::getTable(),
               'field'              => 'name',
               'name'               => __('Name'),
               'forcegroupby'       => true,
               'datatype'           => 'itemlink',
               'itemlink_type'      => 'Domain',
               'massiveaction'      => false,
               'joinparams'         => [
                  'beforejoin' => [
                     'table'      => Domain_Item::getTable(),
                     'joinparams' => ['jointype' => 'itemtype_item']
                  ]
               ]
            ];

            $tab[] = [
               'id'                 => '206',
               'table'              => DomainType::getTable(),
               'field'              => 'name',
               'name'               => DomainType::getTypeName(1),
               'forcegroupby'       => true,
               'datatype'           => 'dropdown',
               'massiveaction'      => false,
               'joinparams'         => [
                  'beforejoin' => [
                     'table'      => Domain::getTable(),
                     'joinparams'         => [
                        'beforejoin' => [
                           'table'      => Domain_Item::getTable(),
                           'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                     ]
                  ]
               ]
            ];
         }
      }

      return $tab;
   }*/

   function canCreateItem() {
      return true;
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /*private function prepareInput($input) {
      if (isset($input['date_creation']) && empty($input['date_creation'])) {
         $input['date_creation'] = 'NULL';
      }
      if (isset($input['date_expiration']) && empty($input['date_expiration'])) {
         $input['date_expiration'] = 'NULL';
      }

      return $input;
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }*/

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . Domain::getTypeName(1) . "</td>";
      echo "<td>";
      Dropdown::show(
         'Domain', [
            'name'   => "domains_id",
            'value'  => $this->fields["domains_id"],
            'entity' => $this->fields["entities_id"]
         ]
      );
      echo "</td>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'>";

      echo "<td>" . DomainRecordType::getTypeName(1) . "</td>";
      echo "<td>";
      Dropdown::show(
         'DomainRecordType', [
            'name'   => "domainrecordtypes_id",
            'value'  => $this->fields["domainrecordtypes_id"],
            'entity' => $this->fields["entities_id"]
         ]
      );
      echo "</td>";
      echo "<td>" . __('Creation date') . "</td>";
      echo "<td>";
      Html::showDateField("date_creation", ['value' => $this->fields["date_creation"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Technician in charge') . "</td><td>";
      User::dropdown(['name'   => "users_id_tech",
                           'value'  => $this->fields["users_id_tech"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'interface']);
      echo "</td>";

      echo "<td>" . __('Group in charge') . "</td>";
      echo "<td>";
      Dropdown::show('Group', ['name'      => "groups_id_tech",
                                    'value'     => $this->fields["groups_id_tech"],
                                    'entity'    => $this->fields["entities_id"],
                                    'condition' => ['is_assign' => 1]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Comments') . "</td>";
      echo "<td colspan='3' class='center'>";
      echo "<textarea cols='115' rows='5' name='comment' >" . $this->fields["comment"] . "</textarea>";
      echo "</td>";

      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * Show records for a domain
    *
    * @param Domain $domain Domain object
    *
    * @return void
    **/
   public static function showForDomain(Domain $domain) {
      global $DB;

      $instID = $domain->fields['id'];
      if (!$domain->can($instID, READ)) {
         return false;
      }
      $canedit = $domain->can($instID, UPDATE);
      $rand    = mt_rand();

      $iterator = $DB->request([
         'FROM'      => self::getTable(),
         'WHERE'     => ['domains_id' => $instID],
      ]);

      $number = count($iterator);

      if (Session::isMultiEntitiesMode()) {
         $colsup = 1;
      } else {
         $colsup = 0;
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = [];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }

      echo "<th>" . __('Type') . "</th>";
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . __('Entity') . "</th>";
      }
      echo "</tr>";

      while ($data = $iterator->next()) {
         Session::initNavigateListItems('DomainRecord', Domain::getTypeName(2) . " = " . $domain->fields['name']);

         $ID = "";

         if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
            $ID = " (" . $data["id"] . ")";
         }

         $link = Toolbox::getItemTypeFormURL('DomainRecord');
         $name = "<a href=\"" . $link . "?id=" . $data["id"] . "\">"
                  . $data["name"] . "$ID</a>";

         echo "<tr class='tab_bg_1'>";

         if ($canedit) {
            echo "<td width='10'>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
            echo "</td>";
         }
         echo "<td class='center'>" . Dropdown::getDropdownName(DomainRecordType::getTable(), $data['domainrecordtypes_id']) . "</td>";
         echo "<td class='center' " . (isset($data['is_deleted']) && $data['is_deleted'] ? "class='tab_bg_2_2'" : "") .
               ">" . $name . "</td>";
         if (Session::isMultiEntitiesMode()) {
            echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entities_id']) . "</td>";
         }
         echo "</tr>";
      }
      echo "</table>";

      if ($canedit && $number) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($paramsma);
         Html::closeForm();
      }
      echo "</div>";

   }
}
