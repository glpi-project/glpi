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
 * ITILCategory class
**/
class ITILCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'itilcategory';

   function getAdditionalFields() {

      $tab = [['name'      => $this->getForeignKeyField(),
                         'label'     => __('As child of'),
                         'type'      => 'parent',
                         'list'      => false],
                   ['name'      => 'users_id',
                         'label'     => __('Technician in charge of the hardware'),
                         'type'      => 'UserDropdown',
                         'right'     => 'own_ticket',
                         'list'      => true],
                   ['name'      => 'groups_id',
                         'label'     => __('Group in charge of the hardware'),
                         'type'      => 'dropdownValue',
                         'condition' => ['is_assign' => 1],
                         'list'      => true],
                   ['name'      => 'knowbaseitemcategories_id',
                         'label'     => __('Knowledge base'),
                         'type'      => 'dropdownValue',
                         'list'      => true],
                   ['name'      => 'code',
                         'label'     => __('Code representing the ticket category'),
                         'type'      => 'text',
                         'list'      => false],
                   ['name'      => 'is_helpdeskvisible',
                         'label'     => __('Visible in the simplified interface'),
                         'type'      => 'bool',
                         'list'      => true],
                   ['name'      => 'is_incident',
                         'label'     => __('Visible for an incident'),
                         'type'      => 'bool',
                         'list'      => true],
                   ['name'      => 'is_request',
                         'label'     => __('Visible for a request'),
                         'type'      => 'bool',
                         'list'      => true],
                   ['name'      => 'is_problem',
                         'label'     => __('Visible for a problem'),
                         'type'      => 'bool',
                         'list'      => true],
                   ['name'      => 'is_change',
                         'label'     => __('Visible for a change'),
                         'type'      => 'bool',
                         'list'      => true],
                   ['name'      => 'tickettemplates_id_demand',
                         'label'     => __('Template for a request'),
                         'type'      => 'dropdownValue',
                         'list'      => true],
                   ['name'      => 'tickettemplates_id_incident',
                         'label'     => __('Template for an incident'),
                         'type'      => 'dropdownValue',
                         'list'      => true],
                   ['name'      => 'changetemplates_id',
                         'label'     => __('Template for a change'),
                         'type'      => 'dropdownValue',
                         'list'      => true],
                   ['name'      => 'problemtemplates_id',
                         'label'     => __('Template for a problem'),
                         'type'      => 'dropdownValue',
                         'list'      => true],
                  ];

      if (!Session::haveRightsOr('problem', [CREATE, UPDATE, DELETE,
                                                  Problem::READALL, Problem::READMY])) {

         unset($tab[7]);
      }
      return $tab;

   }


   function rawSearchOptions() {
      $tab                       = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '71',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => Group::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '72',
         'table'              => TicketTemplate::getTable(),
         'field'              => 'name',
         'linkfield'          => 'tickettemplates_id_demand',
         'name'               => __('Template for a request'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '73',
         'table'              => TicketTemplate::getTable(),
         'field'              => 'name',
         'linkfield'          => 'tickettemplates_id_incident',
         'name'               => __('Template for an incident'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '100',
         'table'              => ChangeTemplate::getTable(),
         'field'              => 'name',
         'linkfield'          => 'changetemplates_id',
         'name'               => __('Template for a change'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '101',
         'table'              => ProblemTemplate::getTable(),
         'field'              => 'name',
         'linkfield'          => 'problemtemplates_id',
         'name'               => __('Template for a problem'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '74',
         'table'              => $this->getTable(),
         'field'              => 'is_incident',
         'name'               => __('Visible for an incident'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '75',
         'table'              => $this->getTable(),
         'field'              => 'is_request',
         'name'               => __('Visible for a request'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '76',
         'table'              => $this->getTable(),
         'field'              => 'is_problem',
         'name'               => __('Visible for a problem'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '85',
         'table'              => $this->getTable(),
         'field'              => 'is_change',
         'name'               => __('Visible for a change'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'is_helpdeskvisible',
         'name'               => __('Visible in the simplified interface'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '77',
         'table'              => 'glpi_tickets',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of tickets'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '78',
         'table'              => 'glpi_problems',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of problems'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '98',
         'table'              => 'glpi_changes',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of changes'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '79',
         'table'              => 'glpi_knowbaseitemcategories',
         'field'              => 'completename',
         'name'               => __('Knowledge base'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '99',
         'table'              => $this->getTable(),
         'field'              => 'code',
         'name'               => __('Code representing the ticket category'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      return $tab;
   }


   static function getTypeName($nb = 0) {
      return _n('ITIL category', 'ITIL categories', $nb);
   }


   function post_getEmpty() {

      $this->fields['is_helpdeskvisible'] = 1;
      $this->fields['is_request']         = 1;
      $this->fields['is_incident']        = 1;
      $this->fields['is_problem']         = 1;
      $this->fields['is_change']          = 1;
   }


   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this);
   }

   /**
    * @since 9.5.0
    *
    * @param $value
   **/
   static function getITILCategoryIDByCode($value) {
      return self::getITILCategoryIDByField("code", $value);
   }

   /**
    * @since 9.5.0
    *
    * @param string $field
    * @param mixed  $value must be addslashes
   **/
   private static function getITILCategoryIDByField($field, $value) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => [$field => $value]
      ]);

      if (count($iterator) == 1) {
         $result = $iterator->next();
         return $result['id'];
      }
      return -1;
   }

   function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);

      $input['code'] = isset($input['code']) ? trim($input['code']) : '';
      if (!empty($input["code"])
            && ITILCategory::getITILCategoryIDByCode($input["code"]) != -1) {
         Session::addMessageAfterRedirect(__("Code representing the ticket category is already used"),
                                          false, ERROR);
         return false;
      }
      return $input;
   }


   function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);

      $input['code'] = isset($input['code']) ? trim($input['code']) : '';
      if (!empty($input["code"])
            && !in_array(ITILCategory::getITILCategoryIDByCode($input["code"]), [$input['id'],-1])) {
         Session::addMessageAfterRedirect(__("Code representing the ticket category is already used"),
                                          false, ERROR);
         return false;
      }
      return $input;
   }

   /**
    * @since 0.84
    *
    * @param $item         CommonGLPI object
    * @param $withtemplate (default 0)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (Session::haveRight(self::$rightname, READ)) {
         if ($item instanceof ITILTemplate) {
            $ong[1] = $this->getTypeName(Session::getPluralNumber());
            return $ong;
         }
      }
      return parent::getTabNameForItem($item, $withtemplate);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item instanceof ITILTemplate) {
         self::showForITILTemplate($item, $withtemplate);
      }
      return parent::displayTabContentForItem($item, $tabnum, $withtemplate);
   }


   /**
    * @param $tt           ITILTemplate object
    * @param $withtemplate (default 0)
   **/
   static function showForITILTemplate(ITILTemplate $tt, $withtemplate = 0) {
      global $DB, $CFG_GLPI;

      $itilcategory = new self();
      $ID           = $tt->fields['id'];

      if (!$tt->getFromDB($ID)
          || !$tt->can($ID, READ)) {
         return false;
      }

      echo "<div class='center'>";

      $iterator = $DB->request([
         'FROM'   => 'glpi_itilcategories',
         'WHERE'  => [
            'OR' => [
               'tickettemplates_id_incident' => $ID,
               'tickettemplates_id_demand'   => $ID,
               'changetemplates_id'          => $ID,
               'problemtemplates_id'         => $ID
            ]
         ],
         'ORDER'  => 'name'
      ]);

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='5'>";
      $itilcategory_type = $itilcategory->getType();
      echo "<a href='".$itilcategory_type::getSearchURL()."'>";
      echo self::getTypeName(count($iterator));
      echo "</a>";
      echo "</th></tr>";
      if (count($iterator)) {
         echo "<th>".__('Name')."</th>";
         echo "<th>".__('Incident')."</th>";
         echo "<th>".__('Request')."</th>";
         echo "<th>".Change::getTypeName(1)."</th>";
         echo "<th>".Problem::getTypeName(1)."</th>";
         echo "</tr>";

         while ($data = $iterator->next()) {
            echo "<tr class='tab_bg_2'>";
            $itilcategory->getFromDB($data['id']);
            echo "<td>".$itilcategory->getLink(['comments' => true])."</td>";
            if ($data['tickettemplates_id_incident'] == $ID) {
               echo "<td class='center'>
                     <img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt=\"".__('OK').
                        "\" width='14' height='14'>
                     </td>";
            } else {
               echo "<td>&nbsp;</td>";
            }
            if ($data['tickettemplates_id_demand'] == $ID) {
               echo "<td class='center'>
                     <img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt=\"".__('OK').
                        "\" width='14' height='14'>
                     </td>";
            } else {
               echo "<td>&nbsp;</td>";
            }
            if ($data['changetemplates_id'] == $ID) {
               echo "<td class='center'>
                     <img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt=\"".__('OK').
                        "\" width='14' height='14'>
                     </td>";
            } else {
               echo "<td>&nbsp;</td>";
            }
            if ($data['problemtemplates_id'] == $ID) {
               echo "<td class='center'>
                     <img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt=\"".__('OK').
                        "\" width='14' height='14'>
                     </td>";
            } else {
               echo "<td>&nbsp;</td>";
            }
         }

      } else {
         echo "<tr><th colspan='5'>".__('No item found')."</th></tr>";
      }

      echo "</table></div>";
   }

}
