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
   die("Sorry. You can't access this file directly");
}

class DomainRecord extends CommonDBChild {
   const DEFAULT_TTL = 3600;

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

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_domains',
         'field'              => 'name',
         'name'               => Domain::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => DomainRecordType::getTable(),
         'field'              => 'name',
         'name'               => DomainRecordType::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'ttl',
         'name'               => __('TTL'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'data',
         'name'               => __('Data'),
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_groups',
         'field'              => 'name',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'massiveaction'      => false,
         'name'               => __('Last update'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   public function canCreateItem() {
      return count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes']);
   }

   static function canCreate() {
      if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
         return true;
      }
      return parent::canCreate();
   }

   static function canUpdate() {
      if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
         return true;
      }
      return parent::canUpdate();
   }


   public function canUpdateItem() {
      return parent::canUpdateItem()
         && ($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] == [-1]
         || in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])
         );
   }

   function canDeleteItem() {
      return parent::canDeleteItem()
         && ($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] == [-1]
         || in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])
         );
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

   /**
    * Prepare input for add and update
    *
    * @param array   $input Input values
    * @param boolean $add   True when we're adding a record
    *
    * @return aray|false
    */
   private function prepareInput($input, $add = false) {

      if ($add) {
         if (isset($input['date_creation']) && empty($input['date_creation'])) {
            $input['date_creation'] = 'NULL';
         }

         if (!isset($input['ttl']) || empty($input['ttl'])) {
            $input['ttl'] = self::DEFAULT_TTL;
         }
      }

      //search entity
      if ($add && !isset($input['entities_id'])) {
         $input['entities_id'] = $_SESSION['glpiactive_entity'] ?? 0;
         $input['is_recursive'] = $_SESSION['glpiactive_entity_recursive'] ?? 0;
         $domain = new Domain();
         if (isset($input['domains_id']) && $domain->getFromDB($input['domains_id'])) {
            $input['entities_id'] = $domain->fields['entities_id'];
            $input['is_recursive'] = $domain->fields['is_recursive'];
         }
      }

      if (!Session::isCron() && (isset($input['domainrecordtypes_id']) || isset($this->fields['domainrecordtypes_id']))) {
         if (!($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] == [-1])) {
            if (isset($input['domainrecordtypes_id']) && !(in_array($input['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes']))) {
               //no right to use selected type
               Session::addMessageAfterRedirect(
                  __('You are not allowed to use this type of records'),
                  true,
                  ERROR
               );
               return false;
            }
            if ($add === false && !(in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes']))) {
               //no right to change existing type
               Session::addMessageAfterRedirect(
                  __('You are not allowed to edit this type of records'),
                  true,
                  ERROR
               );
               return false;
            }
         }
      }

      return $input;
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input, true);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

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
      $condition = null;
      if ($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] != [-1]) {
         if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
            $condition = ['id' => $_SESSION['glpiactiveprofile']['managed_domainrecordtypes']];
         } else {
            $condition = ['id' => null];
         }
      }
      Dropdown::show(
         'DomainRecordType', [
            'name'      => "domainrecordtypes_id",
            'value'     => $this->fields["domainrecordtypes_id"],
            'entity'    => $this->fields["entities_id"],
            'condition' => $condition
         ]
      );
      echo "</td>";
      echo "<td>" . __('Creation date') . "</td>";
      echo "<td>";
      Html::showDateField("date_creation", ['value' => $this->fields["date_creation"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Data') . "</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this, "data");
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
      echo "<td>" . __('TTL') . "</td>";
      echo "<td>";
      echo "<input type='number' name='ttl' value='{$this->fields['ttl']}'/>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Comments') . "</td>";
      echo "<td colspan='3' class='center'>";
      echo "<textarea cols='115' rows='5' name='comment' >" . $this->fields["comment"] . "</textarea>";
      echo "</td>";

      echo "</tr>";

      if (isset($_REQUEST['_in_modal'])) {
         echo "<input type='hidden' name='_in_modal' value='1'>";
      }
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
      $canedit = $domain->can($instID, UPDATE) || count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes']);
      $rand    = mt_rand();

      $iterator = $DB->request([
         'SELECT'    => 'record.*',
         'FROM'      => self::getTable() . ' AS record',
         'WHERE'     => ['domains_id' => $instID],
         'LEFT JOIN' => [
            DomainRecordType::getTable() . ' AS rtype'  => [
               'ON'  => [
                  'rtype'  => 'id',
                  'record' => 'domainrecordtypes_id'
               ]
            ]
         ],
         'ORDER'     => ['rtype.name ASC', 'record.name ASC']
      ]);

      $number = count($iterator);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='domain_form$rand'
         id='domain_form$rand'  action='" . Toolbox::getItemTypeFormURL("Domain") . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" .
              __('Link a record') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td class='center'>";
         $used_iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'domains_id'   => ['>', 0],
               'NOT'          => ['domains_id' => null]
            ]
         ]);

         $used = [];
         while ($row = $used_iterator->next()) {
            $used[$row['id']] = $row['id'];
         }

         Dropdown::show(
            'DomainRecord', [
               'name'   => "domainrecords_id",
               'used'   => $used
            ]
         );

         echo "<span class='fa fa-plus-circle pointer' title=\"".__s('Add')."\"
                        onClick=\"".Html::jsGetElementbyID('add_dropdowndomainrecords_id').".dialog('open');\"
                     ><span class='sr-only'>" . __s('Add') . "</span></span>";
         echo Ajax::createIframeModalWindow(
            'add_dropdowndomainrecords_id',
            DomainRecord::getFormURL() . "?domains_id=$instID",
            ['display' => false, 'reloadonclose' => true]
         );

         echo "</td><td class='center' class='tab_bg_1'>";
         echo "<input type='hidden' name='domains_id' value='$instID'>";
         echo "<input type='submit' name='addrecord' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = [];
         Html::showMassiveActions($massiveactionparams);
      }
      if ($number) {
         Session::initNavigateListItems(
            'DomainRecord',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(__('%1$s = %2$s'),
            Domain::getTypeName(1), $domain->getName()));
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }

      echo "<th>" . __('Type') . "</th>";
      echo "<th>" . __('Name') . "</th>";
      echo "<th>" . __('TTL') . "</th>";
      echo "<th>" . __('Target') . "</th>";
      echo "</tr>";

      while ($data = $iterator->next()) {
         Session::addToNavigateListItems('DomainRecord', $data['id']);
         Session::addToNavigateListItems('Domain', $domain->fields['id']);

         $ID = "";

         if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
            $ID = " (" . $data["id"] . ")";
         }

         $link = Toolbox::getItemTypeFormURL('DomainRecord');
         $name = "<a href=\"" . $link . "?id=" . $data["id"] . "\">"
                  . self::getDisplayName($domain, $data['name']) . "$ID</a>";

         echo "<tr class='tab_bg_1'>";

         if ($canedit) {
            echo "<td width='10'>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
            echo "</td>";
         }
         echo "<td>" . Dropdown::getDropdownName(DomainRecordType::getTable(), $data['domainrecordtypes_id']) . "</td>";
         echo "<td " . (isset($data['is_deleted']) && $data['is_deleted'] ? "class='tab_bg_2_2'" : "") .
               ">" . $name . "</td>";
         echo "<td>" . $data['ttl'] . "</td>";
         echo "<td>" . $data['data'] . "</td>";
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

   public static function getDisplayName(Domain $domain, $name) {
      $name_txt = rtrim(
         str_replace(
            rtrim($domain->getCanonicalName(), '.'),
            '',
            $name
         ),
         '.'
      );
      if (empty($name_txt)) {
         //dns root
         $name_txt = '@';
      }
      return $name_txt;
   }
}
