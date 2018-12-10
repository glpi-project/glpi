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
 * Rule Class store all information about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
**/
class Rule extends CommonDBTM {

   public $dohistory             = true;

   // Specific ones
   ///Actions affected to this rule
   public $actions               = [];
   ///Criterias affected to this rule
   public $criterias             = [];
   /// Rules can be sorted ?
   public $can_sort              = false;
   /// field used to order rules
   public $orderby               = 'ranking';

   /// restrict matching to self::AND_MATCHING or self::OR_MATCHING : specify value to activate
   public $restrict_matching     = false;

   protected $rules_id_field     = 'rules_id';
   protected $ruleactionclass    = 'RuleAction';
   protected $rulecriteriaclass  = 'RuleCriteria';

   public $specific_parameters   = false;

   public $regex_results         = [];
   public $criterias_results     = [];

   static $rightname             = 'config';

   const RULE_NOT_IN_CACHE       = -1;
   const RULE_WILDCARD           = '*';

   //Generic rules engine
   const PATTERN_IS              = 0;
   const PATTERN_IS_NOT          = 1;
   const PATTERN_CONTAIN         = 2;
   const PATTERN_NOT_CONTAIN     = 3;
   const PATTERN_BEGIN           = 4;
   const PATTERN_END             = 5;
   const REGEX_MATCH             = 6;
   const REGEX_NOT_MATCH         = 7;
   const PATTERN_EXISTS          = 8;
   const PATTERN_DOES_NOT_EXISTS = 9;
   const PATTERN_FIND            = 10; // Global criteria
   const PATTERN_UNDER           = 11;
   const PATTERN_NOT_UNDER       = 12;
   const PATTERN_IS_EMPTY        = 30; // Global criteria

   const AND_MATCHING            = "AND";
   const OR_MATCHING             = "OR";



   static function getTable($classname = null) {
      return parent::getTable(__CLASS__);
   }


   static function getTypeName($nb = 0) {
      return _n('Rule', 'Rules', $nb);
   }


   /**
    *  Get correct Rule object for specific rule
    *
    *  @since 0.84
    *
    *  @param $rules_id ID of the rule
   **/
   static function getRuleObjectByID($rules_id) {

      $rule = new self();
      if ($rule->getFromDB($rules_id)) {
         $realrule = new $rule->fields['sub_type']();
         return $realrule;
      }
      return null;
   }

   /**
    *  Get condition array for rule. If empty array no condition used
    *  maybe overridden to define conditions using binary combination :
    *   example array(1 => Condition1,
    *                 2 => Condition2,
    *                 3 => Condition1&Condition2)
    *
    *  @since 0.85
    *
    *  @return array of conditions
   **/
   static function getConditionsArray() {
      return [];
   }

   /**
   * Is this rule use condition
   *
   **/
   function useConditions() {
      return (count($this->getConditionsArray()) > 0);
   }

   /**
    * Display a dropdown with all the rule conditions
    *
    * @since 0.85
    *
    * @param $options      array of parameters
   **/
   static function dropdownConditions($options = []) {

      $p['name']      = 'condition';
      $p['value']     = 0;
      $p['display']   = true;
      $p['on_change'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $elements = static::getConditionsArray();
      if (count($elements)) {
         return Dropdown::showFromArray($p['name'], $elements, $p);
      }

      return false;
   }

   /**
    * Get rule condition type Name
    *
    * @param $value condition ID
   **/
   static function getConditionName($value) {

      $cond = static::getConditionsArray();

      if (isset($cond[$value])) {
         return $cond[$value];
      }

      return NOT_AVAILABLE;
   }

   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since 0.85
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = [];

      if (Session::haveRight("rule_ldap", READ)
          || Session::haveRight("rule_import", READ)
          || Session::haveRight("rule_ticket", READ)
          || Session::haveRight("rule_softwarecategories", READ)
          || Session::haveRight("rule_mailcollector", READ)) {

         $menu['rule']['title'] = static::getTypeName(Session::getPluralNumber());
         $menu['rule']['page']  = static::getSearchURL(false);

         foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
            $rulecollection = new $rulecollectionclass();
            if ($rulecollection->canList()) {
               $ruleclassname = $rulecollection->getRuleClassName();
               $menu['rule']['options'][$rulecollection->menu_option]['title']
                              = $rulecollection->getRuleClass()->getTitle();
               $menu['rule']['options'][$rulecollection->menu_option]['page']
                              = $ruleclassname::getSearchURL(false);
               $menu['rule']['options'][$rulecollection->menu_option]['links']['search']
                              = $ruleclassname::getSearchURL(false);
               if ($rulecollection->canCreate()) {
                  $menu['rule']['options'][$rulecollection->menu_option]['links']['add']
                              = $ruleclassname::getFormURL(false);
               }
            }
         }
      }

      if (Transfer::canView()
          && Session::isMultiEntitiesMode()) {

         $menu['rule']['title'] = static::getTypeName(Session::getPluralNumber());
         $menu['rule']['page']  = static::getSearchURL(false);

         $menu['rule']['options']['transfer']['title']           = __('Transfer');
         $menu['rule']['options']['transfer']['page']            = "/front/transfer.php";
         $menu['rule']['options']['transfer']['links']['search'] = "/front/transfer.php";

         if (Session::haveRightsOr("transfer", [CREATE, UPDATE])) {
            $menu['rule']['options']['transfer']['links']['summary']
                                                                 = "/front/transfer.action.php";
            $menu['rule']['options']['transfer']['links']['add'] = Transfer::getFormURL(false);
         }
      }

      if (Session::haveRight("rule_dictionnary_dropdown", READ)
          || Session::haveRight("rule_dictionnary_software", READ)
          || Session::haveRight("rule_dictionnary_printer", READ)) {

         $menu['dictionnary']['title']    = _n('Dictionary', 'Dictionaries', Session::getPluralNumber());
         $menu['dictionnary']['shortcut'] = '';
         $menu['dictionnary']['page']     = '/front/dictionnary.php';

         $menu['dictionnary']['options']['manufacturers']['title']
                           = _n('Manufacturer', 'Manufacturers', Session::getPluralNumber());
         $menu['dictionnary']['options']['manufacturers']['page']
                           = '/front/ruledictionnarymanufacturer.php';
         $menu['dictionnary']['options']['manufacturers']['links']['search']
                           = '/front/ruledictionnarymanufacturer.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['manufacturers']['links']['add']
                              = '/front/ruledictionnarymanufacturer.form.php';
         }

         $menu['dictionnary']['options']['software']['title']
                           = _n('Software', 'Software', Session::getPluralNumber());
         $menu['dictionnary']['options']['software']['page']
                           = '/front/ruledictionnarysoftware.php';
         $menu['dictionnary']['options']['software']['links']['search']
                           = '/front/ruledictionnarysoftware.php';

         if (RuleDictionnarySoftware::canCreate()) {
            $menu['dictionnary']['options']['software']['links']['add']
                              = '/front/ruledictionnarysoftware.form.php';
         }

         $menu['dictionnary']['options']['model.computer']['title']
                           = _n('Computer model', 'Computer models', Session::getPluralNumber());
         $menu['dictionnary']['options']['model.computer']['page']
                           = '/front/ruledictionnarycomputermodel.php';
         $menu['dictionnary']['options']['model.computer']['links']['search']
                           = '/front/ruledictionnarycomputermodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.computer']['links']['add']
                              = '/front/ruledictionnarycomputermodel.form.php';
         }

         $menu['dictionnary']['options']['model.monitor']['title']
                           = _n('Monitor model', 'Monitor models', Session::getPluralNumber());
         $menu['dictionnary']['options']['model.monitor']['page']
                           = '/front/ruledictionnarymonitormodel.php';
         $menu['dictionnary']['options']['model.monitor']['links']['search']
                           = '/front/ruledictionnarymonitormodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.monitor']['links']['add']
                              = '/front/ruledictionnarymonitormodel.form.php';
         }

         $menu['dictionnary']['options']['model.printer']['title']
                           = _n('Printer model', 'Printer models', Session::getPluralNumber());
         $menu['dictionnary']['options']['model.printer']['page']
                           = '/front/ruledictionnaryprintermodel.php';
         $menu['dictionnary']['options']['model.printer']['links']['search']
                           = '/front/ruledictionnaryprintermodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.printer']['links']['add']
                              = '/front/ruledictionnaryprintermodel.form.php';
         }

         $menu['dictionnary']['options']['model.peripheral']['title']
                           = _n('Peripheral model', 'Peripheral models', Session::getPluralNumber());
         $menu['dictionnary']['options']['model.peripheral']['page']
                           = '/front/ruledictionnaryperipheralmodel.php';
         $menu['dictionnary']['options']['model.peripheral']['links']['search']
                           = '/front/ruledictionnaryperipheralmodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.peripheral']['links']['add']
                              = '/front/ruledictionnaryperipheralmodel.form.php';
         }

         $menu['dictionnary']['options']['model.networking']['title']
                           = _n('Networking equipment model', 'Networking equipment models', Session::getPluralNumber());
         $menu['dictionnary']['options']['model.networking']['page']
                           = '/front/ruledictionnarynetworkequipmentmodel.php';
         $menu['dictionnary']['options']['model.networking']['links']['search']
                           = '/front/ruledictionnarynetworkequipmentmodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.networking']['links']['add']
                              = '/front/ruledictionnarynetworkequipmentmodel.form.php';
         }

         $menu['dictionnary']['options']['model.phone']['title']
                           = _n('Phone model', 'Phone models', Session::getPluralNumber());
         $menu['dictionnary']['options']['model.phone']['page']
                           = '/front/ruledictionnaryphonemodel.php';
         $menu['dictionnary']['options']['model.phone']['links']['search']
                           = '/front/ruledictionnaryphonemodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.phone']['links']['add']
                              = '/front/ruledictionnaryphonemodel.form.php';
         }

         $menu['dictionnary']['options']['type.computer']['title']
                           = _n('Computer type', 'Computer types', Session::getPluralNumber());
         $menu['dictionnary']['options']['type.computer']['page']
                           = '/front/ruledictionnarycomputertype.php';
         $menu['dictionnary']['options']['type.computer']['links']['search']
                           = '/front/ruledictionnarycomputertype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.computer']['links']['add']
                              = '/front/ruledictionnarycomputertype.form.php';
         }

         $menu['dictionnary']['options']['type.monitor']['title']
                           = _n('Monitor type', 'Monitors types', Session::getPluralNumber());
         $menu['dictionnary']['options']['type.monitor']['page']
                           = '/front/ruledictionnarymonitortype.php';
         $menu['dictionnary']['options']['type.monitor']['links']['search']
                           = '/front/ruledictionnarymonitortype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.monitor']['links']['add']
                              = '/front/ruledictionnarymonitortype.form.php';
         }

         $menu['dictionnary']['options']['type.printer']['title']
                           = _n('Printer type', 'Printer types', Session::getPluralNumber());
         $menu['dictionnary']['options']['type.printer']['page']
                           = '/front/ruledictionnaryprintertype.php';
         $menu['dictionnary']['options']['type.printer']['links']['search']
                           = '/front/ruledictionnaryprintertype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.printer']['links']['add']
                              = '/front/ruledictionnaryprintertype.form.php';
         }

         $menu['dictionnary']['options']['type.peripheral']['title']
                           = _n('Peripheral type', 'Peripheral types', Session::getPluralNumber());
         $menu['dictionnary']['options']['type.peripheral']['page']
                           = '/front/ruledictionnaryperipheraltype.php';
         $menu['dictionnary']['options']['type.peripheral']['links']['search']
                           = '/front/ruledictionnaryperipheraltype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.peripheral']['links']['add']
                              = '/front/ruledictionnaryperipheraltype.form.php';
         }

         $menu['dictionnary']['options']['type.networking']['title']
                           = _n('Networking equipment type', 'Networking equipment types', Session::getPluralNumber());
         $menu['dictionnary']['options']['type.networking']['page']
                           = '/front/ruledictionnarynetworkequipmenttype.php';
         $menu['dictionnary']['options']['type.networking']['links']['search']
                           = '/front/ruledictionnarynetworkequipmenttype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.networking']['links']['add']
                              = '/front/ruledictionnarynetworkequipmenttype.form.php';
         }

         $menu['dictionnary']['options']['type.phone']['title']
                           = _n('Phone type', 'Phone types', Session::getPluralNumber());
         $menu['dictionnary']['options']['type.phone']['page']
                           = '/front/ruledictionnaryphonetype.php';
         $menu['dictionnary']['options']['type.phone']['links']['search']
                           = '/front/ruledictionnaryphonetype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.phone']['links']['add']
                              = '/front/ruledictionnaryphonetype.form.php';
         }

         $menu['dictionnary']['options']['os']['title']
                           = __('Operating system');
         $menu['dictionnary']['options']['os']['page']
                           = '/front/ruledictionnaryoperatingsystem.php';
         $menu['dictionnary']['options']['os']['links']['search']
                           = '/front/ruledictionnaryoperatingsystem.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os']['links']['add']
                              = '/front/ruledictionnaryoperatingsystem.form.php';
         }

         $menu['dictionnary']['options']['os_sp']['title']
                           = __('Service pack');
         $menu['dictionnary']['options']['os_sp']['page']
                           = '/front/ruledictionnaryoperatingsystemservicepack.php';
         $menu['dictionnary']['options']['os_sp']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemservicepack.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os_sp']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemservicepack.form.php';
         }

         $menu['dictionnary']['options']['os_version']['title']
                           = __('Version of the operating system');
         $menu['dictionnary']['options']['os_version']['page']
                           = '/front/ruledictionnaryoperatingsystemversion.php';
         $menu['dictionnary']['options']['os_version']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemversion.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os_version']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemversion.form.php';
         }

         $menu['dictionnary']['options']['os_arch']['title']
                           = __('Operating system architecture');
         $menu['dictionnary']['options']['os_arch']['page']
                           = '/front/ruledictionnaryoperatingsystemarchitecture.php';
         $menu['dictionnary']['options']['os_arch']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemarchitecture.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os_arch']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemarchitecture.form.php';
         }

         $menu['dictionnary']['options']['printer']['title']
                           = _n('Printer', 'Printers', Session::getPluralNumber());
         $menu['dictionnary']['options']['printer']['page']
                           = '/front/ruledictionnaryprinter.php';
         $menu['dictionnary']['options']['printer']['links']['search']
                           = '/front/ruledictionnaryprinter.php';

         if (RuleDictionnaryPrinter::canCreate()) {
            $menu['dictionnary']['options']['printer']['links']['add']
                              = '/front/ruledictionnaryprinter.form.php';
         }
      }

      if (count($menu)) {
         $menu['is_multi_entries'] = true;
         return $menu;
      }

      return false;
   }


   /**
    * @since versin 0.84
   **/
   function getRuleActionClass () {
      return $this->ruleactionclass;
   }


   /**
    * @since versin 0.84
   **/
   function getRuleCriteriaClass () {
      return $this->rulecriteriaclass;
   }


   /**
    * @since versin 0.84
   **/
   function getRuleIdField () {
      return $this->rules_id_field;
   }


   function isEntityAssign() {
      return false;
   }


   function post_getEmpty() {
      $this->fields['is_active'] = 0;
   }


   /**
    * Get title used in rule
    *
    * @return Title of the rule
   **/
   function getTitle() {
      return __('Rules management');
   }


   /**
    * @since 0.84
    *
    * @return string
   **/
   function getCollectionClassName() {
      return $this->getType().'Collection';
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      $collectiontype = $this->getCollectionClassName();
      if ($collection = getItemForItemtype($collectiontype)) {
         if ($isadmin
             && ($collection->orderby == "ranking")) {
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'move_rule'] = __('Move');
         }
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'duplicate'] = _x('button', 'Duplicate');
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'export']    = _x('button', 'Export');
      }
      return $actions;
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'duplicate' :
            $entity_assign = false;
            foreach ($ma->getitems() as $itemtype => $ids) {
               if ($item = getItemForItemtype($itemtype)) {
                  if ($item->isEntityAssign()) {
                     $entity_assign = true;
                     break;
                  }
               }
            }
            if ($entity_assign) {
               Entity::dropdown();
            }
            echo "<br><br>".Html::submit(_x('button', 'Duplicate'),
                                         ['name' => 'massiveaction']);
            return true;

         case 'move_rule' :
            $input = $ma->getInput();
            $values = ['after'  => __('After'),
                            'before' => __('Before')];
            Dropdown::showFromArray('move_type', $values, ['width' => '20%']);

            if (isset($input['entity'])) {
               $entity = $input['entity'];
            } else {
               $entity = "";
            }

            if (isset($input['condition'])) {
               $condition = $input['condition'];
            } else {
               $condition = 0;
            }
            echo Html::hidden('rule_class_name', ['value' => $input['rule_class_name']]);

            Rule::dropdown(['sub_type'        => $input['rule_class_name'],
                                 'name'            => "ranking",
                                 'condition'       => $condition,
                                 'entity'          => $entity,
                                 'width'           => '50%']);
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Move')."'>\n";
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;

      switch ($ma->getAction()) {
         case 'duplicate':
            $rulecollection = new RuleCollection();
            foreach ($ids as $id) {
               if ($item->getFromDB($id)) {
                  if ($rulecollection->duplicateRule($id)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
               }
            }
            break;

         case 'export':
            if (count($ids)) {
               $_SESSION['exportitems'] = $ids;
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
               $ma->setRedirect('rule.backup.php?action=download&itemtype='.$item->getType());
            }
         break;

         case 'move_rule' :
            $input          = $ma->getInput();
            $collectionname = $input['rule_class_name'].'Collection';
            $rulecollection = new $collectionname();
            if ($rulecollection->canUpdate()) {
               foreach ($ids as $id) {
                  if ($item->getFromDB($id)) {
                     if ($rulecollection->moveRule($id, $input['ranking'], $input['move_type'])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
               $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
            }
            break;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'ranking',
         'name'               => __('Ranking'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'description',
         'name'               => __('Description'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'match',
         'name'               => __('Logical operator'),
         'datatype'           => 'specific',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
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
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool',
         'massiveaction'      => false
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

      return $tab;
   }


   /**
    * @param  $field
    * @param  $values
    * @param  $options   array
    *
    * @return string
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'match' :
            switch ($values[$field]) {
               case self::AND_MATCHING :
                  return __('and');

               case self::OR_MATCHING :
                  return __('or');

               default :
                  return NOT_AVAILABLE;
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param  $field
    * @param  $name              (default '')
    * @param  $values            (default '')
    * @param  $options   array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'match' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['value'] = $values[$field];
               $options['name']  = $name;
               $rule             = new static();
               return $rule->dropdownRulesMatch($options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Show the rule
    *
    * @param $ID              ID of the rule
    * @param $options   array of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return nothing
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;
      if (!$this->isNewID($ID)) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->checkGlobal(UPDATE);
      }

      $canedit = $this->canEdit(static::$rightname);
      $rand = mt_rand();
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Description')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "description");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Logical operator')."</td>";
      echo "<td>";
      $this->dropdownRulesMatch(['value' => $this->fields["match"]]);
      echo "</td>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td></tr>\n";

      if ($this->useConditions()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Use rule for')."</td>";
         echo "<td>";
         $this->dropdownConditions(['value' => $this->fields["condition"]]);
         echo "</td>";
         echo "<td colspan='2'>";
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td class='middle' colspan='3'>";
      echo "<textarea cols='110' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";

      if (!$this->isNewID($ID)) {
         if ($this->fields["date_mod"]) {
            echo "<br>";
            printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
         }
      }
      if ($canedit) {
         echo "<input type='hidden' name='ranking' value='".$this->fields["ranking"]."'>";
         echo "<input type='hidden' name='sub_type' value='".get_class($this)."'>";
      }
      echo "</td></tr>\n";

      if ($canedit) {
         if ($ID > 0) {
            if ($plugin = isPluginItemType($this->getType())) {
               $url = $CFG_GLPI["root_doc"]."/plugins/".strtolower($plugin['plugin']);
            } else {
               $url = $CFG_GLPI["root_doc"];
            }
            echo "<tr><td class='tab_bg_2 center' colspan='4'>";
            echo "<a class='vsubmit' href='#' onClick=\"".
                  Html::jsGetElementbyID('ruletest'.$rand).".dialog('open'); return false;\">".
                  _x('button', 'Test')."</a>";
            Ajax::createIframeModalWindow('ruletest'.$rand,
                                          $url."/front/rule.test.php?". "sub_type=".$this->getType().
                                             "&rules_id=".$this->fields["id"],
                                          ['title' => _x('button', 'Test')]);
            echo "</td></tr>\n";
         }
      }

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Display a dropdown with all the rule matching
    *
    * @since 0.84 new proto
    *
    * @param $options      array of parameters
   **/
   function dropdownRulesMatch($options = []) {

      $p['name']     = 'match';
      $p['value']    = '';
      $p['restrict'] = $this->restrict_matching;
      $p['display']  = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!$p['restrict'] || ($p['restrict'] == self::AND_MATCHING)) {
         $elements[self::AND_MATCHING] = __('and');
      }

      if (!$p['restrict'] || ($p['restrict'] == self::OR_MATCHING)) {
         $elements[self::OR_MATCHING]  = __('or');
      }

      return Dropdown::showFromArray($p['name'], $elements, $p);
   }


   /**
    * Get all criterias for a given rule
    *
    * @param $ID              the rule_description ID
    * @param $withcriterias   1 to retrieve all the criterias for a given rule (default 0)
    * @param $withactions     1 to retrive all the actions for a given rule (default 0)
   **/
   function getRuleWithCriteriasAndActions($ID, $withcriterias = 0, $withactions = 0) {

      if ($ID == "") {
         return $this->getEmpty();
      }
      if ($ret = $this->getFromDB($ID)) {
         if ($withactions
             && ($RuleAction = getItemForItemtype($this->ruleactionclass))) {
            $this->actions = $RuleAction->getRuleActions($ID);
         }

         if ($withcriterias
             && ($RuleCriterias = getItemForItemtype($this->rulecriteriaclass))) {
            $this->criterias = $RuleCriterias->getRuleCriterias($ID);
         }

         return true;
      }

      return false;
   }


   /**
    * display title for action form
   **/
   function getTitleAction() {

      foreach ($this->getActions() as $key => $val) {
         if (isset($val['force_actions'])
             && (in_array('regex_result', $val['force_actions'])
                 || in_array('append_regex_result', $val['force_actions']))) {

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><td>".
                  __('It is possible to affect the result of a regular expression using the string #0').
                 "</td></tr>\n";
            echo "</table><br>";
            return;
         }
      }
   }


   /**
    * Get maximum number of Actions of the Rule (0 = unlimited)
    *
    * @return the maximum number of actions
   **/
   function maxActionsCount() {
      // Unlimited
      return 0;
   }


   /**
    * Display all rules actions
    *
    * @param $rules_id        rule ID
    * @param $options   array of options : may be readonly
   **/
   function showActionsList($rules_id, $options = []) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->canEdit($rules_id);
      $style   = "class='tab_cadre_fixehov'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadrehov'";
      }
      $this->getTitleAction();

      if ($canedit) {
         echo "<div id='viewaction" . $rules_id . "$rand'></div>\n";
      }

      if ($canedit
          && (($this->maxActionsCount() == 0)
              || (sizeof($this->actions) < $this->maxActionsCount()))) {

         echo "<script type='text/javascript' >\n";
         echo "function viewAddAction" . $rules_id . "$rand() {\n";
         $params = ['type'                => $this->ruleactionclass,
                         'parenttype'          => $this->getType(),
                         $this->rules_id_field => $rules_id,
                         'id'                  => -1];
         Ajax::updateItemJsCode("viewaction" . $rules_id . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddAction".$rules_id."$rand();'>";
         echo __('Add a new action')."</a></div>\n";
      }

      $nb = count($this->actions);

      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.$this->ruleactionclass.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $nb),
                                      'check_itemtype' => get_class($this),
                                      'check_items_id' => $rules_id,
                                      'container'      => 'mass'.$this->ruleactionclass.$rand,
                                      'extraparams'    => ['rule_class_name'
                                                                    => $this->getType()]];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table $style>";
      echo "<tr class='noHover'>";
      echo "<th colspan='".($canedit && $nb?'4':'3')."'>" . _n('Action', 'Actions', Session::getPluralNumber()) . "</th></tr>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
         $header_bottom .= "<th width='10'>";
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
      }

      $header_end .= "<th class='center b'>"._n('Field', 'Fields', Session::getPluralNumber())."</th>";
      $header_end .= "<th class='center b'>".__('Action type')."</th>";
      $header_end .= "<th class='center b'>".__('Value')."</th>";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      foreach ($this->actions as $action) {
         $this->showMinimalActionForm($action->fields, $canedit, $rand);
      }
      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function maybeRecursive() {
      return false;
   }


   /**
    * Display all rules criterias
    *
    * @param $rules_id
    * @param $options   array of options : may be readonly
   **/
   function showCriteriasList($rules_id, $options = []) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->canEdit($rules_id);
      $style   = "class='tab_cadre_fixehov'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadrehov'";
      }

      if ($canedit) {
         echo "<div id='viewcriteria" . $rules_id . "$rand'></div>\n";

         echo "<script type='text/javascript' >\n";
         echo "function viewAddCriteria" . $rules_id . "$rand() {\n";
         $params = ['type'                => $this->rulecriteriaclass,
                         'parenttype'          => $this->getType(),
                         $this->rules_id_field => $rules_id,
                         'id'                  => -1];
         Ajax::updateItemJsCode("viewcriteria" . $rules_id . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddCriteria".$rules_id."$rand();'>";
         echo __('Add a new criteria')."</a></div>\n";
      }

      echo "<div class='spaced'>";

      $nb = sizeof($this->criterias);

      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.$this->rulecriteriaclass.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $nb),
                                      'check_itemtype' => get_class($this),
                                      'check_items_id' => $rules_id,
                                      'container'      => 'mass'.$this->rulecriteriaclass.$rand,
                                      'extraparams'    => ['rule_class_name'
                                                                    => $this->getType()]];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table $style>";
      echo "<tr class='noHover'>".
           "<th colspan='".($canedit&&$nb?" 4 ":"3")."'>". _n('Criterion', 'Criteria', Session::getPluralNumber())."</th>".
           "</tr>\n";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>";
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th class='center b'>"._n('Criterion', 'Criteria', 1)."</th>\n";
      $header_end .= "<th class='center b'>".__('Condition')."</th>\n";
      $header_end .= "<th class='center b'>".__('Reason')."</th>\n";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      foreach ($this->criterias as $criteria) {
         $this->showMinimalCriteriaForm($criteria->fields, $canedit, $rand);
      }

      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>\n";
   }



   /**
    * Display the dropdown of the criterias for the rule
    *
    * @since 0.84 new proto
    *
    * @param $options   array of options : may be readonly
    *
    * @return the initial value (first)
   **/
   function dropdownCriteria($options = []) {
      global $CFG_GLPI;

      $p['name']                = 'criteria';
      $p['display']             = true;
      $p['value']               = '';
      $p['display_emptychoice'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $group      = [];
      $groupname  = _n('Criterion', 'Criteria', Session::getPluralNumber());
      foreach ($this->getAllCriteria() as $ID => $crit) {
         // Manage group system
         if (!is_array($crit)) {
            if (count($group)) {
               asort($group);
               $items[$groupname] = $group;
            }
            $group     = [];
            $groupname = $crit;
         } else {
            $group[$ID] = $crit['name'];
         }
      }
      if (count($group)) {
         asort($group);
         $items[$groupname] = $group;
      }
      return Dropdown::showFromArray($p['name'], $items, $p);
   }


   /**
    * Display the dropdown of the actions for the rule
    *
    * @param $options already used actions
    *
    * @return the initial value (first non used)
   **/
   function dropdownActions($options = []) {
      global $CFG_GLPI;

      $p['name']                = 'field';
      $p['display']             = true;
      $p['used']                = [];
      $p['value']               = '';
      $p['display_emptychoice'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $actions = $this->getAllActions();

      // For each used actions see if several set is available
      // Force actions to available actions for several
      foreach ($p['used'] as $key => $ID) {
         if (isset($actions[$ID]['permitseveral'])) {
            unset($p['used'][$key]);
         }
      }

      // Complete used array with duplicate items
      // add duplicates of used items
      foreach ($p['used'] as $ID) {
         if (isset($actions[$ID]['duplicatewith'])) {
            $p['used'][$actions[$ID]['duplicatewith']] = $actions[$ID]['duplicatewith'];
         }
      }

      // Parse for duplicates of already used items
      foreach ($actions as $ID => $act) {
         if (isset($actions[$ID]['duplicatewith'])
             && in_array($actions[$ID]['duplicatewith'], $p['used'])) {
            $p['used'][$ID] = $ID;
         }
      }

      $value = '';

      foreach ($actions as $ID => $act) {
         $items[$ID] = $act['name'];

         if (empty($value) && !isset($used[$ID])) {
            $value = $ID;
         }
      }
      return Dropdown::showFromArray($p['name'], $items, $p);
   }


   /**
    * Get a criteria description by his ID
    *
    * @param $ID the criteria's ID
    *
    * @return the criteria array
   **/
   function getCriteria($ID) {

      $criterias = $this->getAllCriteria();
      if (isset($criterias[$ID])) {
         return $criterias[$ID];
      }
      return [];
   }


   /**
    * Get a action description by his ID
    *
    * @param $ID the action's ID
    *
    * @return the action array
   **/
   function getAction($ID) {

      $actions = $this->getAllActions();
      if (isset($actions[$ID])) {
         return $actions[$ID];
      }
      return [];
   }


   /**
    * Get a criteria description by his ID
    *
    * @param $ID the criteria's ID
    *
    * @return the criteria's description
   **/

   function getCriteriaName($ID) {

      $criteria = $this->getCriteria($ID);
      if (isset($criteria['name'])) {
         return $criteria['name'];
      }
      return __('Unavailable')."&nbsp;";
   }


   /**
    * Get a action description by his ID
    *
    * @param $ID the action's ID
    *
    * @return the action's description
   **/
   function getActionName($ID) {

      $action = $this->getAction($ID);
      if (isset($action['name'])) {
         return $action['name'];
      }
      return "&nbsp;";
   }


   /**
    * Process the rule
    *
    * @param &$input          the input data used to check criterias
    * @param &$output         the initial ouput array used to be manipulate by actions
    * @param &$params         parameters for all internal functions
    * @param &options   array options:
    *                     - only_criteria : only react on specific criteria
    *
    * @return the output array updated by actions.
    *         If rule matched add field _rule_process to return value
   **/
   function process(&$input, &$output, &$params, &$options = []) {

      if ($this->validateCriterias($options)) {
         $this->regex_results     = [];
         $this->criterias_results = [];
         $input = $this->prepareInputDataForProcess($input, $params);

         if ($this->checkCriterias($input)) {
            unset($output["_no_rule_matches"]);
            $refoutput = $output;
            $output    = $this->executeActions($output, $params, $input);

            $this->updateOnlyCriteria($options, $refoutput, $output);
            //Hook
            $hook_params["sub_type"] = $this->getType();
            $hook_params["ruleid"]   = $this->fields["id"];
            $hook_params["input"]    = $input;
            $hook_params["output"]   = $output;
            Plugin::doHook("rule_matched", $hook_params);
            $output["_rule_process"] = true;
         }
      }
   }


   /**
    * Update Only criteria options if needed
    *
    * @param &options   options :
    *                     - only_criteria : only react on specific criteria
    * @param $refoutput   the initial ouput array used to be manipulate by actions
    * @param $newoutput   the ouput array after actions process
    *
    * @return the options array updated.
   **/
   function updateOnlyCriteria(&$options, $refoutput, $newoutput) {

      if (count($this->actions)) {
         if (isset($options['only_criteria'])
             && !is_null($options['only_criteria'])
             && is_array($options['only_criteria'])) {
            foreach ($this->actions as $action) {
               if (!isset($refoutput[$action->fields["field"]])
                   || ($refoutput[$action->fields["field"]]
                       != $newoutput[$action->fields["field"]])) {
                  if (!in_array($action->fields["field"], $options['only_criteria'])) {
                     $options['only_criteria'][] = $action->fields["field"];
                  }

                  // Add linked criteria if available
                  $crit = $this->getCriteria($action->fields["field"]);
                  if (isset($crit['linked_criteria'])) {
                     $tmp = $crit['linked_criteria'];
                     if (!is_array($crit['linked_criteria'])) {
                        $tmp = [$tmp];
                     }
                     foreach ($tmp as $toadd) {
                        if (!in_array($toadd, $options['only_criteria'])) {
                           $options['only_criteria'][] = $toadd;
                        }
                     }
                  }
               }
            }
         }
      }
   }


   /// Are criterias valid to be processed
   /**
    *  Are criterias valid to be processed
    *
    *  @since 0.85
    *
    * @param $options
    *
    * @return boolean
   **/
   function validateCriterias($options) {

      if (count($this->criterias)) {
         if (isset($options['only_criteria'])
             && !is_null($options['only_criteria'])
             && is_array($options['only_criteria'])) {
            foreach ($this->criterias as $criteria) {
               if (in_array($criteria->fields['criteria'], $options['only_criteria'])) {
                  return true;
               }
            }
            return false;
         }
         return true;
      }

      return false;
   }


   /**
    * Check criterias
    *
    * @param $input the input data used to check criterias
    *
    * @return boolean if criterias match
   **/
   function checkCriterias($input) {

      reset($this->criterias);

      if ($this->fields["match"] == self::AND_MATCHING) {
         $doactions = true;

         foreach ($this->criterias as $criteria) {

            $definition_criteria = $this->getCriteria($criteria->fields['criteria']);
            if (!isset($definition_criteria['is_global']) || !$definition_criteria['is_global']) {
               $doactions &= $this->checkCriteria($criteria, $input);
               if (!$doactions) {
                  break;
               }
            }
         }

      } else { // OR MATCHING
         $doactions = false;
         foreach ($this->criterias as $criteria) {
            $definition_criteria = $this->getCriteria($criteria->fields['criteria']);

            if (!isset($definition_criteria['is_global'])
                || !$definition_criteria['is_global']) {
               $doactions |= $this->checkCriteria($criteria, $input);
               if ($doactions) {
                  break;
               }
            }
         }
      }

      //If all simple criteria match, and if necessary, check complex criteria
      if ($doactions) {
         return $this->findWithGlobalCriteria($input);
      }
      return false;
   }


   /**
    * Check criterias
    *
    * @param $input           the input data used to check criterias
    * @param &$check_results
    *
    * @return boolean if criterias match
   **/
   function testCriterias($input, &$check_results) {

      reset($this->criterias);

      foreach ($this->criterias as $criteria) {
         $result = $this->checkCriteria($criteria, $input);
         $check_results[$criteria->fields["id"]]["name"]   = $criteria->fields["criteria"];
         $check_results[$criteria->fields["id"]]["value"]  = $criteria->fields["pattern"];
         $check_results[$criteria->fields["id"]]["result"] = ((!$result)?0:1);
         $check_results[$criteria->fields["id"]]["id"]     = $criteria->fields["id"];
      }
   }


   /**
    * Process a criteria of a rule
    *
    * @param &$criteria  criteria to check
    * @param &$input     the input data used to check criterias
   **/
   function checkCriteria(&$criteria, &$input) {

      $partial_regex_result = [];
      // Undefine criteria field : set to blank
      if (!isset($input[$criteria->fields["criteria"]])) {
         $input[$criteria->fields["criteria"]] = '';
      }

      //If the value is not an array
      if (!is_array($input[$criteria->fields["criteria"]])) {
         $value = $this->getCriteriaValue($criteria->fields["criteria"],
                                          $criteria->fields["condition"],
                                          $input[$criteria->fields["criteria"]]);

         $res   = RuleCriteria::match($criteria, $value, $this->criterias_results,
                                      $partial_regex_result);
      } else {

         //If the value is, in fact, an array of values
         // Negative condition : Need to match all condition (never be)
         if (in_array($criteria->fields["condition"], [self::PATTERN_IS_NOT,
                                                            self::PATTERN_NOT_CONTAIN,
                                                            self::REGEX_NOT_MATCH,
                                                            self::PATTERN_DOES_NOT_EXISTS])) {
            $res = true;
            foreach ($input[$criteria->fields["criteria"]] as $tmp) {
               $value = $this->getCriteriaValue($criteria->fields["criteria"],
                                                $criteria->fields["condition"], $tmp);

               $res &= RuleCriteria::match($criteria, $value, $this->criterias_results,
                                           $partial_regex_result);
               if (!$res) {
                  break;
               }
            }

         } else {
            // Positive condition : Need to match one
            $res = false;
            foreach ($input[$criteria->fields["criteria"]] as $crit) {
               $value = $this->getCriteriaValue($criteria->fields["criteria"],
                                                $criteria->fields["condition"], $crit);

               $res |= RuleCriteria::match($criteria, $value, $this->criterias_results,
                                           $partial_regex_result);
            }
         }
      }

      // Found regex on this criteria
      if (count($partial_regex_result)) {
         // No regex existing : put found
         if (!count($this->regex_results)) {
            $this->regex_results = $partial_regex_result;

         } else { // Already existing regex : append found values
            $temp_result = [];
            foreach ($partial_regex_result as $new) {

               foreach ($this->regex_results as $old) {
                  $temp_result[] = array_merge($old, $new);
               }
            }
            $this->regex_results = $temp_result;
         }
      }

      return $res;
   }


   /**
    * @param $input
   **/
   function findWithGlobalCriteria($input) {
      return true;
   }


   /**
    * Specific prepare input datas for the rule
    *
    * @param $input  the input data used to check criterias
    * @param $params parameters
    *
    * @return the updated input datas
   **/
   function prepareInputDataForProcess($input, $params) {
      return $input;
   }


   /**
    * Get all data needed to process rules (core + plugins)
    *
    * @since 0.84
    * @param $input  the input data used to check criterias
    * @param $params parameters
    *
    * @return the updated input datas
   **/
   function prepareAllInputDataForProcess($input, $params) {
      global $PLUGIN_HOOKS;

      $input = $this->prepareInputDataForProcess($input, $params);
      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "rulePrepareInputDataForProcess",
                                            ['input'  => $input,
                                                  'params' => $params]);
               if (is_array($results)) {
                  foreach ($results as $result) {
                     $input[] = $result;
                  }
               }
            }
         }
      }
      return $input;
   }


   /**
    * Execute plugins actions if needed.
    *
    * @since 9.3.2 Added $input parameter
    * @since 0.84
    *
    * @param RuleAction $action
    * @param array      $output  rule execution output
    * @param array      $params  parameters
    * @param array      $input   the input data
    *
    * @return array Updated output
    */
   function executePluginsActions($action, $output, $params, array $input = []) {
      global $PLUGIN_HOOKS;

      if (isset($PLUGIN_HOOKS['use_rules'])) {
         $params['criterias_results'] = $this->criterias_results;
         $params['rule_itemtype']     = $this->getType();
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "executeActions", ['output' => $output,
                                                                        'params' => $params,
                                                                        'action' => $action,
                                                                        'input'  => $input]);
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $output[$id] = $result;
                  }
               }

            }
         }
      }
      return $output;
   }


   /**
    * Execute the actions as defined in the rule.
    *
    * @since 9.3.2 Added $input parameter
    *
    * @param array $output  the fields to manipulate
    * @param array $params  parameters
    * @param array $input   the input data
    *
    * @return array Updated output
   **/
   function executeActions($output, $params, array $input = []) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "append" :
                  $actions = $this->getActions();
                  $value   = $action->fields["value"];
                  if (isset($actions[$action->fields["field"]]["appendtoarray"])
                      && isset($actions[$action->fields["field"]]["appendtoarrayfield"])) {
                     $value = $actions[$action->fields["field"]]["appendtoarray"];
                     $value[$actions[$action->fields["field"]]["appendtoarrayfield"]]
                            = $action->fields["value"];
                  }
                  $output[$actions[$action->fields["field"]]["appendto"]][] = $value;
                  break;

               case "regex_result" :
               case "append_regex_result" :
                  //Regex result : assign value from the regex
                  //Append regex result : append result from a regex
                  if (isset($this->regex_results[0])) {
                     $res = RuleAction::getRegexResultById($action->fields["value"],
                                                            $this->regex_results[0]);
                  } else {
                     $res = $action->fields["value"];
                  }

                  if ($action->fields["action_type"] == "append_regex_result") {
                     if (isset($params[$action->fields["field"]])) {
                        $res = $params[$action->fields["field"]] . $res;
                     } else {
                        //keep rule value to append in a separate entry
                        $output[$action->fields['field'] . '_append'] = $res;
                     }
                  }

                  $output[$action->fields["field"]] = $res;
                  break;

               default:
                  //plugins actions
                  $executeaction = clone $this;
                  $ouput = $executeaction->executePluginsActions($action, $output, $params, $input);
                  break;
            }
         }
      }
      return $output;
   }


   function cleanDBonPurge() {

      // Delete a rule and all associated criterias and actions
      if (!empty($this->ruleactionclass)) {
         $ruleactionclass = $this->ruleactionclass;
         $ra = new $ruleactionclass();
         $ra->deleteByCriteria([$this->rules_id_field => $this->fields['id']]);
      }

      if (!empty($this->rulecriteriaclass)) {
         $rulecriteriaclass = $this->rulecriteriaclass;
         $rc = new $rulecriteriaclass();
         $rc->deleteByCriteria([$this->rules_id_field => $this->fields['id']]);
      }
   }


   /**
    * Show the minimal form for the rule
    *
    * @param $target             link to the form page
    * @param $first              is it the first rule ?(false by default)
    * @param $last               is it the last rule ? (false by default)
    * @param $display_entities   display entities / make it read only display (false by default)
    * @param $active_condition   active condition used (default 0)
   **/
   function showMinimalForm($target, $first = false, $last = false, $display_entities = false, $active_condition = 0) {
      global $CFG_GLPI;

      $canedit = (self::canUpdate() && !$display_entities);
      echo "<tr class='tab_bg_1'>";

      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->getType(), $this->fields["id"]);
         echo "</td>";

      } else {
         echo "<td>&nbsp;</td>";
      }

      $link = $this->getLink();
      if (!empty($this->fields["comment"])) {
         $link = sprintf(__('%1$s %2$s'), $link,
                         Html::showToolTip($this->fields["comment"], ['display' => false]));
      }
      echo "<td>".$link."</td>";
      echo "<td>".$this->fields["description"]."</td>";
      if ($this->useConditions()) {
         echo "<td>".$this->getConditionName($this->fields["condition"])."</td>";
      }
      echo "<td>".Dropdown::getYesNo($this->fields["is_active"])."</td>";

      if ($display_entities) {
         $entname = Dropdown::getDropdownName('glpi_entities', $this->fields['entities_id']);
         if ($this->maybeRecursive()
             && $this->fields['is_recursive']) {
            $entname = sprintf(__('%1$s %2$s'), $entname, "<span class='b'>(".__('R').")</span>");
         }

         echo "<td>".$entname."</td>";
      }

      if (!$display_entities) {
         if ($this->can_sort
             && !$first
             && $canedit) {
            echo "<td>";
            Html::showSimpleForm($target, ['action' => 'up',
                                                'condition' => $active_condition], '',
                                 ['type' => $this->fields["sub_type"],
                                       'id'   => $this->fields["id"],],
                                 $CFG_GLPI["root_doc"]."/pics/deplier_up.png");
            echo "</td>";
         } else {
            echo "<td>&nbsp;</td>";
         }
      }

      if (!$display_entities) {
         if ($this->can_sort
             && !$last
             && $canedit) {
            echo "<td>";
            Html::showSimpleForm($target, ['action' => 'down',
                                                'condition' => $active_condition], '',
                                 ['type' => $this->fields["sub_type"],
                                       'id'   => $this->fields["id"]],
                                 $CFG_GLPI["root_doc"]."/pics/deplier_down.png");
            echo "</td>";
         } else {
            echo "<td>&nbsp;</td>";
         }
      }
      echo "</tr>\n";
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      // Before adding, add the ranking of the new rule
      $input["ranking"] = $this->getNextRanking();
      //If no uuid given, generate a new one
      if (!isset($input['uuid'])) {
         $input["uuid"] = self::getUuid();
      }

      return $input;
   }


   /**
    * Get the next ranking for a specified rule
   **/
   function getNextRanking() {
      global $DB;

      $sql = "SELECT MAX(`ranking`) AS rank
              FROM `glpi_rules`
              WHERE `sub_type` = '".$this->getType()."'";
      $result = $DB->query($sql);

      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_assoc($result);
         return $datas["rank"] + 1;
      }
      return 0;
   }


   /**
    * Show the minimal form for the action rule
    *
    * @param $fields    datas used to display the action
    * @param $canedit   can edit the actions rule ?
    * @param $rand      random value of the form
   **/
   function showMinimalActionForm($fields, $canedit, $rand) {
      global $CFG_GLPI;

      $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditAction".
                         $fields[$this->rules_id_field].$fields["id"]."$rand();\""
                        : '');
      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->ruleactionclass, $fields["id"]);
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditAction". $fields[$this->rules_id_field].$fields["id"]."$rand() {\n";
         $params = ['type'                => $this->ruleactionclass,
                         'parenttype'          => $this->getType(),
                         $this->rules_id_field => $fields[$this->rules_id_field],
                         'id'                  => $fields["id"]];
         Ajax::updateItemJsCode("viewaction" . $fields[$this->rules_id_field] . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "</td>";
      }
      echo $this->getMinimalActionText($fields, $edit);
      echo "</tr>\n";
   }


   /**
    * Show preview result of a rule
    *
    * @param $target    where to go if action
    * @param $input     input data array
    * @param $params    params used (see addSpecificParamsForPreview)
   **/
   function showRulePreviewResultsForm($target, $input, $params) {

      $actions       = $this->getAllActions();
      $check_results = [];
      $output        = [];

      //Test all criterias, without stopping at the first good one
      $this->testCriterias($input, $check_results);
      //Process the rule
      $this->process($input, $output, $params);
      if (!$criteria = getItemForItemtype($this->rulecriteriaclass)) {
         return;
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='4'>" . __('Result details') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center b'>"._n('Criterion', 'Criteria', 1)."</td>";
      echo "<td class='center b'>".__('Condition')."</td>";
      echo "<td class='center b'>".__('Reason')."</td>";
      echo "<td class='center b'>".__('Validation')."</td>";
      echo "</tr>\n";

      foreach ($check_results as $ID => $criteria_result) {
         echo "<tr class='tab_bg_1'>";
         $criteria->getFromDB($criteria_result["id"]);
         echo $this->getMinimalCriteriaText($criteria->fields);
         if ($criteria->fields['condition'] != self::PATTERN_FIND) {
            echo "<td class='b'>".Dropdown::getYesNo($criteria_result["result"])."</td></tr>\n";
         } else {
            echo "<td class='b'>".Dropdown::EMPTY_VALUE."</td></tr>\n";
         }
      }
      echo "</table></div>";

      $global_result = (isset($output["_rule_process"])?1:0);

      echo "<div class='spaced'>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . __('Rule results') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center b'>".__('Validation')."</td><td>";
      echo Dropdown::getYesNo($global_result)."</td></tr>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         if (isset($actions[$criteria])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$actions[$criteria]["name"]."</td>";
            if (isset($actions[$criteria]['type'])) {
               $actiontype = $actions[$criteria]['type'];
            } else {
               $actiontype ='';
            }
            echo "<td>".$this->getActionValue($criteria, $actiontype, $value);
            echo "</td></tr>\n";
         }
      }

      //If a regular expression was used, and matched, display the results
      if (count($this->regex_results)) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".__('Result of the regular expression')."</td>";
         echo "<td>";
         if (!empty($this->regex_results[0])) {
            echo "<table class='tab_cadre'>";
            echo "<tr><th>".__('Key')."</th><th>".__('Value')."</th></tr>";
            foreach ($this->regex_results[0] as $key => $value) {
               echo "<tr class='tab_bg_1'>";
               echo "<td>$key</td><td>$value</td></tr>";
            }
            echo "</table>";
         }
         echo "</td></tr>\n";
      }
      echo "</tr>\n";
      echo "</table></div>";
   }


   /**
    * Show the minimal form for the criteria rule
    *
    * @param $fields    datas used to display the criteria
    * @param $canedit   can edit the criterias rule ?
    * @param $rand      random value of the form
   **/
   function showMinimalCriteriaForm($fields, $canedit, $rand) {
      global $CFG_GLPI;

      $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditCriteria".
                         $fields[$this->rules_id_field].$fields["id"]."$rand();\""
                        : '');
      echo "<tr class='tab_bg_1' >";
      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->rulecriteriaclass, $fields["id"]);
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditCriteria". $fields[$this->rules_id_field].$fields["id"]."$rand() {\n";
         $params = ['type'               => $this->rulecriteriaclass,
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $fields[$this->rules_id_field],
                        'id'                  => $fields["id"]];
         Ajax::updateItemJsCode("viewcriteria" . $fields[$this->rules_id_field] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "</td>";
      }

      echo $this->getMinimalCriteriaText($fields, $edit);
      echo "</tr>\n";
   }


   /**
    * @param $fields
    * @param $addtotd   (default '')
   **/
   function getMinimalCriteriaText($fields, $addtotd = '') {

      $text  = "<td $addtotd>" . $this->getCriteriaName($fields["criteria"]) . "</td>";
      $text .= "<td $addtotd>" . RuleCriteria::getConditionByID($fields["condition"],
                                                                get_class($this),
                                                                $fields["criteria"])."</td>";
      $text .= "<td $addtotd>" . $this->getCriteriaDisplayPattern($fields["criteria"],
                                                                  $fields["condition"],
                                                                  $fields["pattern"]) . "</td>";
      return $text;
   }


   /**
    * @param $fields
    * @param $addtotd   (default '')
   **/
   function getMinimalActionText($fields, $addtotd = '') {

      $text  = "<td $addtotd>" . $this->getActionName($fields["field"]) . "</td>";
      $text .= "<td $addtotd>" . RuleAction::getActionByID($fields["action_type"]) . "</td>";
      if (isset($fields["value"])) {
         $text .= "<td $addtotd>" . $this->getActionValue($fields["field"], $fields['action_type'],
                                                          $fields["value"]) . "</td>";
      } else {
         $text .= "<td $addtotd>&nbsp;</td>";
      }
      return $text;
   }


   /**
    * Return a value associated with a pattern associated to a criteria to display it
    *
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $pattern   the pattern
   **/
   function getCriteriaDisplayPattern($ID, $condition, $pattern) {

      if (($condition == self::PATTERN_EXISTS)
          || ($condition == self::PATTERN_DOES_NOT_EXISTS)
          || ($condition == self::PATTERN_FIND)) {
          return __('Yes');

      } else if (in_array($condition, [self::PATTERN_IS, self::PATTERN_IS_NOT,
                                            self::PATTERN_NOT_UNDER, self::PATTERN_UNDER])) {
         $crit = $this->getCriteria($ID);

         if (isset($crit['type'])) {
            switch ($crit['type']) {
               case "yesonly" :
               case "yesno" :
                  return Dropdown::getYesNo($pattern);

               case "dropdown" :
                  $addentity = Dropdown::getDropdownName($crit["table"], $pattern);
                  if ($this->isEntityAssign()) {
                     $itemtype = getItemTypeForTable($crit["table"]);
                     $item     = getItemForItemtype($itemtype);
                     if ($item
                         && $item->getFromDB($pattern)
                         && $item->isEntityAssign()) {
                        $addentity = sprintf(__('%1$s (%2$s)'), $addentity,
                                             Dropdown::getDropdownName('glpi_entities',
                                                                       $item->getEntityID()));
                     }
                  }
                  $tmp = $addentity;
                  return (($tmp == '&nbsp;') ? NOT_AVAILABLE : $tmp);

               case "dropdown_users" :
                  return getUserName($pattern);

               case "dropdown_assets_itemtype" :
               case "dropdown_tracking_itemtype" :
                  if ($item = getItemForItemtype($pattern)) {
                     return $item->getTypeName(1);
                  }
                  if (empty($pattern)) {
                     return __('General');
                  }
                  break;

               case "dropdown_status" :
                  return Ticket::getStatus($pattern);

               case "dropdown_priority" :
                  return Ticket::getPriorityName($pattern);

               case "dropdown_urgency" :
                  return Ticket::getUrgencyName($pattern);

               case "dropdown_impact" :
                  return Ticket::getImpactName($pattern);

               case "dropdown_tickettype" :
                  return Ticket::getTicketTypeName($pattern);
            }
         }
      }
      if ($result = $this->getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern)) {
         return $result;
      }
      return $pattern;
   }


   /**
    * Used to get specific criteria patterns
    *
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $pattern   the pattern
    *
    * @return a value associated with the criteria, or false otherwise
   **/
   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {
      return false;
   }


   /**
    * Display item used to select a pattern for a criteria
    *
    * @param $name      criteria name
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $value     the pattern (default '')
    * @param $test      Is to test rule ? (false by default)
   **/
   function displayCriteriaSelectPattern($name, $ID, $condition, $value = "", $test = false) {
      global $CFG_GLPI;

      $crit    = $this->getCriteria($ID);
      $display = false;
      $tested  = false;

      if (isset($crit['type'])
          && ($test
              || in_array($condition, [self::PATTERN_IS, self::PATTERN_IS_NOT,
                                            self::PATTERN_NOT_UNDER, self::PATTERN_UNDER]))) {

         $tested = true;
         switch ($crit['type']) {
            case "yesonly" :
               Dropdown::showYesNo($name, $crit['table'], 0);
               $display = true;
               break;

            case "yesno" :
               Dropdown::showYesNo($name, $value);
               $display = true;
               break;

            case "dropdown" :
               $param = ['name'  => $name,
                              'value' => $value];
               if (isset($crit['condition'])) {
                  $param['condition'] = $crit['condition'];
               }
               Dropdown::show(getItemTypeForTable($crit['table']), $param);

               $display = true;
               break;

            case "dropdown_users" :
               User::dropdown(['value'  => $value,
                                    'name'   => $name,
                                    'right'  => 'all']);
               $display = true;
               break;

            case "dropdown_tracking_itemtype" :
               Dropdown::showItemTypes($name, array_keys(Ticket::getAllTypesForHelpdesk()));
               $display = true;
               break;

            case "dropdown_assets_itemtype" :
               Dropdown::showItemTypes($name, $CFG_GLPI['asset_types'], ['value' => $value]);
               $display = true;
               break;

            case "dropdown_import_type" :
               RuleAsset::dropdownImportType($name, $value);
               $display = true;
               break;

            case "dropdown_urgency" :
               Ticket::dropdownUrgency(['name'  => $name,
                                             'value' => $value]);
               $display = true;
               break;

            case "dropdown_impact" :
               Ticket::dropdownImpact(['name'  => $name,
                                            'value' => $value]);
               $display = true;
               break;

            case "dropdown_priority" :
               Ticket::dropdownPriority(['name'  => $name,
                                              'value' => $value,
                                              'withmajor' => true]);
               $display = true;
               break;

            case "dropdown_status" :
               Ticket::dropdownStatus(['name'  => $name,
                                            'value' => $value]);
               $display = true;
               break;

            case "dropdown_tickettype" :
               Ticket::dropdownType($name, ['value' => $value]);
               $display = true;
               break;

            default:
               $tested = false;
               break;
         }
      }
      //Not a standard condition
      if (!$tested) {
         $display = $this->displayAdditionalRuleCondition($condition, $crit, $name, $value, $test);
      }

      if (($condition == self::PATTERN_EXISTS)
          || ($condition == self::PATTERN_DOES_NOT_EXISTS)) {
         echo "<input type='hidden' name='$name' value='1'>";
         $display = true;
      }

      if (!$display
          && ($rc = getItemForItemtype($this->rulecriteriaclass))) {
         Html::autocompletionTextField($rc, "pattern", ['name'  => $name,
                                                             'value' => $value,
                                                             'size'  => 70]);
      }
   }


   /**
    * Return a "display" value associated with a pattern associated to a criteria
    *
    * @param $ID     the given action
    * @param $type   the type of action
    * @param $value  the value
   **/
   function getActionValue($ID, $type, $value) {

      $action = $this->getAction($ID);
      if (isset($action['type'])) {

         switch ($action['type']) {
            case "dropdown" :
               if ($type=='fromuser' || $type=='fromitem') {
                  return Dropdown::getYesNo($value);
               }
               // $type == assign
               $tmp = Dropdown::getDropdownName($action["table"], $value);
               return (($tmp == '&nbsp;') ? NOT_AVAILABLE : $tmp);

            case "dropdown_status" :
               return Ticket::getStatus($value);

            case "dropdown_assign" :
            case "dropdown_users" :
            case "dropdown_users_validate" :
               return getUserName($value);

            case "dropdown_groups_validate" :
               return Dropdown::getDropdownName('glpi_groups', $value);

            case "dropdown_validation_percent" :
               return Dropdown::getValueWithUnit($value, '%');

            case "yesonly" :
            case "yesno" :
               return Dropdown::getYesNo($value);

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($value);

            case "dropdown_impact" :
               return Ticket::getImpactName($value);

            case "dropdown_priority" :
               return Ticket::getPriorityName($value);

            case "dropdown_tickettype" :
               return Ticket::getTicketTypeName($value);

            case "dropdown_management" :
               return Dropdown::getGlobalSwitch($value);

            default :
               return $this->displayAdditionRuleActionValue($value);
         }
      }

      return $value;
   }


   /**
    * Return a value associated with a pattern associated to a criteria to display it
    *
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $value     the pattern
   **/
   function getCriteriaValue($ID, $condition, $value) {

      if (!in_array($condition, [self::PATTERN_DOES_NOT_EXISTS, self::PATTERN_EXISTS,
                                      self::PATTERN_IS, self::PATTERN_IS_NOT,
                                      self::PATTERN_NOT_UNDER, self::PATTERN_UNDER])) {
         $crit = $this->getCriteria($ID);
         if (isset($crit['type'])) {

            switch ($crit['type']) {
               case "dropdown" :
                  $tmp = Dropdown::getDropdownName($crit["table"], $value, false, false);
                  //$tmp = Dropdown::getDropdownName($crit["table"], $value);
                  // return empty string to be able to check if set
                  if ($tmp == '&nbsp;') {
                     return '';
                  }
                  return $tmp;

               case "dropdown_assign" :
               case "dropdown_users" :
                  return getUserName($value);

               case "yesonly" :
               case "yesno"  :
                  return Dropdown::getYesNo($value);

               case "dropdown_impact" :
                  return Ticket::getImpactName($value);

               case "dropdown_urgency" :
                  return Ticket::getUrgencyName($value);

               case "dropdown_priority" :
                  return Ticket::getPriorityName($value);

            }
         }
      }
      return $value;
   }


   /**
    * Function used to display type specific criterias during rule's preview
    *
    * @param $fields fields values
   **/
   function showSpecificCriteriasForPreview($fields) {
   }


   /**
    * Function used to add specific params before rule processing
    *
    * @param $params parameters
   **/
   function addSpecificParamsForPreview($params) {
      return $params;
   }


   /**
    * Criteria form used to preview rule
    *
    * @param $target    target of the form
    * @param $rules_id  ID of the rule
   **/
   function showRulePreviewCriteriasForm($target, $rules_id) {
      global $DB;

      $criterias = $this->getAllCriteria();

      if ($this->getRuleWithCriteriasAndActions($rules_id, 1, 0)) {
         echo "<form name='testrule_form' id='testrule_form' method='post' action='$target'>\n";
         echo "<div class='spaced'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>" . _n('Criterion', 'Criteria', Session::getPluralNumber()) . "</th></tr>";

         $type_match        = (($this->fields["match"] == self::AND_MATCHING) ?__('and') :__('or'));
         $already_displayed = [];
         $first             = true;

         //Brower all criterias
         foreach ($this->criterias as $criteria) {

            //Look for the criteria in the field of already displayed criteria :
            //if present, don't display it again
            if (!in_array($criteria->fields["criteria"], $already_displayed)) {
               $already_displayed[] = $criteria->fields["criteria"];
               echo "<tr class='tab_bg_1'>";
               echo "<td>";

               if ($first) {
                  echo "&nbsp;";
                  $first = false;
               } else {
                  echo $type_match;
               }

               echo "</td>";
               $criteria_constants = $criterias[$criteria->fields["criteria"]];
               echo "<td>".$criteria_constants["name"]."</td>";
               echo "<td>";
               $value = "";
               if (isset($_POST[$criteria->fields["criteria"]])) {
                  $value = $_POST[$criteria->fields["criteria"]];
               }

               $this->displayCriteriaSelectPattern($criteria->fields['criteria'],
                                                   $criteria->fields['criteria'],
                                                   $criteria->fields['condition'], $value, true);
               echo "</td></tr>\n";
            }
         }
         $this->showSpecificCriteriasForPreview($_POST);

         echo "<tr><td class='tab_bg_2 center' colspan='3'>";
         echo "<input type='submit' name='test_rule' value=\""._sx('button', 'Test')."\"
                class='submit'>";
         echo "<input type='hidden' name='".$this->rules_id_field."' value='$rules_id'>";
         echo "<input type='hidden' name='sub_type' value='" . $this->getType() . "'>";
         echo "</td></tr>\n";
         echo "</table></div>\n";
         Html::closeForm();
      }
   }


   /**
    * @param $output
   **/
   function preProcessPreviewResults($output) {
      global $PLUGIN_HOOKS;

      if (isset($PLUGIN_HOOKS['use_rules'])) {
         $params['criterias_results'] = $this->criterias_results;
         $params['rule_itemtype']     = $this->getType();
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "preProcessRulePreviewResults",
                                            ['output' => $output,
                                                  'params' => $params]);
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $output[$id] = $result;
                  }
               }
            }
         }
      }
      return $output;
   }


   /**
    * Dropdown rules for a defined sub_type of rule
    *
    * @param $options   array of possible options:
    *    - name : string / name of the select (default is depending itemtype)
    *    - sub_type : integer / sub_type of rule
   **/
   static function dropdown($options = []) {
      global $DB, $CFG_GLPI;

      $p['sub_type']        = '';
      $p['name']            = 'rules_id';
      $p['entity'] = '';
      $p['condition'] = 0;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if ($p['sub_type'] == '') {
         return false;
      }

      $conditions = [
         'sub_type' => $p['sub_type']
      ];
      if ($p['condition'] > 0) {
         $conditions['condition'] = ['&', (int)$p['condition']];
      }

      $p['condition'] = $conditions;
      return Dropdown::show($p['sub_type'], $p);
   }


   /**
    * @since 0.84
   **/
   function getAllCriteria() {

      return self::doHookAndMergeResults("getRuleCriteria", $this->getCriterias(),
                                         $this->getType());
   }


   function getCriterias() {
      return [];
   }


   /**
    * @since 0.84
   */
   function getAllActions() {
      return self::doHookAndMergeResults("getRuleActions", $this->getActions(), $this->getType());
   }


   function getActions() {
      return [];
   }


   /**
    *  Execute a hook if necessary and merge results
    *
    *  @since 0.84
    *
    * @param $hook            the hook to execute
    * @param $params   array  input parameters
    * @param $itemtype        (default '')
    *
    * @return input parameters merged with hook parameters
   **/
   static function doHookAndMergeResults($hook, $params = [], $itemtype = '') {
      global $PLUGIN_HOOKS;

      if (empty($itemtype)) {
         $itemtype = static::getType();
      }

      //Agregate all plugins criteria for this rules engine
      $toreturn = $params;
      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($itemtype, $val)) {
               $results = Plugin::doOneHook($plugin, $hook, ['rule_itemtype' => $itemtype,
                                                                  'values'        => $params]);
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $toreturn[$id] = $result;
                  }
               }
            }
         }
      }
      return $toreturn;
   }


   /**
    * @param $sub_type
   **/
   static function getActionsByType($sub_type) {

      if ($rule = getItemForItemtype($sub_type)) {
         return $rule->getAllActions();
      }
      return [];
   }


   /**
    * Return all rules from database
    *
    * @param $crit array of criteria (at least, 'field' and 'value')
    *
    * @return array of Rule objects
   **/
   function getRulesForCriteria($crit) {
      global $DB;

      $rules = [];

      /// TODO : not working for SLALevels : no sub_type

      //Get all the rules whose sub_type is $sub_type and entity is $ID
      $query = [
         'SELECT' => $this->getTable() . '.id',
         'FROM'   => [
            getTableForItemType($this->ruleactionclass),
            $this->getTable()
         ],
         'WHERE'  => [
            getTableForItemType($this->ruleactionclass).".".$this->rules_id_field   => new \QueryExpression(DBmysql::quoteName($this->getTable().'.id')),
            $this->getTable().'.sub_type'                                           => get_class($this)

         ]
      ];

      foreach ($crit as $field => $value) {
         $query['WHERE'][getTableForItemType($this->ruleactionclass).'.'.$field] = $value;
      }

      $iterator = $DB->request($query);

      while ($rule = $iterator->next()) {
         $affect_rule = new Rule();
         $affect_rule->getRuleWithCriteriasAndActions($rule["id"], 0, 1);
         $rules[]     = $affect_rule;
      }
      return $rules;
   }


   /**
    * @param $ID
   **/
   function showNewRuleForm($ID) {

      echo "<form method='post' action='".Toolbox::getItemTypeFormURL('Entity')."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . $this->getTitle() . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name') . "</td><td>";
      Html::autocompletionTextField($this, "name", ['value' => '',
                                                         'size'  => 33]);
      echo "</td><td>".__('Description') . "</td><td>";
      Html::autocompletionTextField($this, "description", ['value' => '',
                                                                'size'  => 33]);
      echo "</td><td>".__('Logical operator') . "</td><td>";
      $this->dropdownRulesMatch();
      echo "</td><td class='tab_bg_2 center'>";
      echo "<input type=hidden name='sub_type' value='".get_class($this)."'>";
      echo "<input type=hidden name='entities_id' value='-1'>";
      echo "<input type=hidden name='affectentity' value='$ID'>";
      echo "<input type=hidden name='_method' value='AddRule'>";
      echo "<input type='submit' name='execute' value=\""._sx('button', 'Add')."\" class='submit'>";
      echo "</td></tr>\n";
      echo "</table>";
      Html::closeForm();
   }


   /**
    * @param $item
   **/
   function showAndAddRuleForm($item) {

      $rand    = mt_rand();
      $canedit = self::canUpdate();

      if ($canedit
          && ($item->getType() == 'Entity')) {
         $this->showNewRuleForm($item->getField('id'));
      }

         //Get all rules and actions
      $crit = ['field' => getForeignKeyFieldForTable($item->getTable()),
                    'value' => $item->getField('id')];

      $rules = $this->getRulesForCriteria($crit);
      $nb    = count($rules);
      echo "<div class='spaced'>";

      if (!$nb) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>" . __('No item found') . "</th>";
         echo "</tr>\n";
         echo "</table>\n";

      } else {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.get_called_class().$rand);
            $massiveactionparams
               = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $nb),
                       'specific_actions'
                           => ['update' => _x('button', 'Update'),
                                    'purge'  => _x('button', 'Delete permanently')]];
                  //     'extraparams'
                //           => array('rule_class_name' => $this->getRuleClassName()));
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>" . $this->getTitle() . "</th>";
         $header_end .= "<th>" . __('Description') . "</th>";
         $header_end .= "<th>" . __('Active') . "</th>";
         $header_end .= "</tr>\n";
         echo $header_begin.$header_top.$header_end;

         Session::initNavigateListItems(get_class($this),
                              //TRANS: %1$s is the itemtype name,
                              //       %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($rules as $rule) {
            Session::addToNavigateListItems(get_class($this), $rule->fields["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $rule->fields["id"]);
               echo "</td>";
               echo "<td><a href='".$this->getFormURLWithID($rule->fields["id"])
                                   . "&amp;onglet=1'>" .$rule->fields["name"] ."</a></td>";

            } else {
               echo "<td>" . $rule->fields["name"] . "</td>";
            }

            echo "<td>" . $rule->fields["description"] . "</td>";
            echo "<td>" . Dropdown::getYesNo($rule->fields["is_active"]) . "</td>";
            echo "</tr>\n";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>\n";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
      }
      echo "</div>";
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Add more criteria specific to this type of rule
   **/
   static function addMoreCriteria() {
      return [];
   }


   /**
    * Add more actions specific to this type of rule
    *
    * @param $value
   **/
   function displayAdditionRuleActionValue($value) {
      return $value;
   }


   /**
    * @param $condition
    * @param $criteria
    * @param $name
    * @param $value
    * @param $test         (false by default)
   **/
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false) {
      return false;
   }


   /**
    * @param $action array
    * @param $value          value to display (default '')
   **/
   function displayAdditionalRuleAction(array $action, $value = '') {
      return false;
   }


   /**
    * Clean Rule with Action or Criteria linked to an item
    *
    * @param $item                  Object
    * @param $field        string   name (default is FK to item)
    * @param $ruleitem              object (instance of Rules of SlaLevel)
    * @param $table        string   (glpi_ruleactions, glpi_rulescriterias or glpi_slalevelcriterias)
    * @param $valfield     string   (value or pattern)
    * @param $fieldfield   string   (criteria of field)
   **/
   private static function cleanForItemActionOrCriteria($item, $field, $ruleitem, $table,
                                                        $valfield, $fieldfield) {
      global $DB;

      $fieldid = getForeignKeyFieldForTable($ruleitem->getTable());

      if (empty($field)) {
         $field = getForeignKeyFieldForTable($item->getTable());
      }

      if (isset($item->input['_replace_by']) && ($item->input['_replace_by'] > 0)) {
         $DB->update(
            $table, [
               $valfield => $item->input['_replace_by']
            ], [
               $valfield   => $item->getField('id'),
               $fieldfield => ['LIKE', $field]
            ]
         );
      } else {
         $iterator = $DB->request([
            'SELECT' => [$fieldid],
            'FROM'   => $table,
            'WHERE'  => [
               $valfield   => $item->getField('id'),
               $fieldfield => ['LIKE', $field]
            ]
         ]);

         if (count($iterator) > 0) {
            $input['is_active'] = 0;

            while ($data = $iterator->next()) {
               $input['id'] = $data[$fieldid];
               $ruleitem->update($input);
            }
            Session::addMessageAfterRedirect(__('Rules using the object have been disabled.'),
                                             true);
         }
      }
   }


   /**
    * Clean Rule with Action is assign to an item
    *
    * @param $item            Object
    * @param $field  string   name (default is FK to item) (default '')
   **/
   static function cleanForItemAction($item, $field = '') {

      self::cleanForItemActionOrCriteria($item, $field,
                                         new self(), 'glpi_ruleactions', 'value', 'field');

      self::cleanForItemActionOrCriteria($item, $field,
                                         new SlaLevel(), 'glpi_slalevelactions', 'value', 'field');

      self::cleanForItemActionOrCriteria($item, $field,
                                         new OlaLevel(), 'glpi_olalevelactions', 'value', 'field');

   }


   /**
    * Clean Rule with Criteria on an item
    *
    * @param $item            Object
    * @param $field  string   name (default is FK to item) (default '')
   **/
   static function cleanForItemCriteria($item, $field = '') {

      self::cleanForItemActionOrCriteria($item, $field,
                                         new self(), 'glpi_rulecriterias', 'pattern', 'criteria');
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Entity' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $types      = [];
                  $collection = new RuleRightCollection();
                  if ($collection->canList()) {
                     $types[] = 'RuleRight';
                  }
                  $collection = new RuleImportEntityCollection();
                  if ($collection->canList()) {
                     $types[] = 'RuleImportEntity';
                  }
                  $collection = new RuleMailCollectorCollection();
                  if ($collection->canList()) {
                     $types[] = 'RuleMailCollector';
                  }
                  if (count($types)) {
                     $nb = countElementsInTable(
                        ['glpi_rules', 'glpi_ruleactions'], [
                           'glpi_ruleactions.rules_id'   => new \QueryExpression(Db::quoteName('glpi_rules.id')),
                           'glpi_rules.sub_type'         => $types,
                           'glpi_ruleactions.field'      => 'entities_id',
                           'glpi_ruleactions.value'      => $item->getID()
                        ]);
                  }
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

            case 'SLA' :
            case 'OLA' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_ruleactions',
                                            ['field' => $item::getFieldNames($item->fields['type'])[1],
                                             'value' => $item->getID()]);
               }
               return self::createTabEntry(self::getTypeName($nb), $nb);

            default:
               if ($item instanceof Rule) {
                  $ong    = [];
                  $nbcriteria = 0;
                  $nbaction   = 0;
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nbcriteria = countElementsInTable(getTableForItemType($item->getRuleCriteriaClass()),
                                                        [$item->getRuleIdField() => $item->getID()]);
                     $nbaction   = countElementsInTable(getTableForItemType($item->getRuleActionClass()),
                                                        [$item->getRuleIdField() => $item->getID()]);

                  }

                  $ong[1] = self::createTabEntry(RuleCriteria::getTypeName(Session::getPluralNumber()),
                                                 $nbcriteria);
                  $ong[2] = self::createTabEntry(RuleAction::getTypeName(Session::getPluralNumber()),
                                                 $nbaction);
                  return $ong;
               }
         }
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Entity') {
         $collection = new RuleRightCollection();
         if ($collection->canList()) {
            $ldaprule = new RuleRight();
            $ldaprule->showAndAddRuleForm($item);
         }

         $collection = new RuleImportEntityCollection();
         if ($collection->canList()) {
            $importrule = new RuleImportEntity();
            $importrule->showAndAddRuleForm($item);
         }

         $collection = new RuleMailCollectorCollection();
         if ($collection->canList()) {
            $mailcollector = new RuleMailCollector();
            $mailcollector->showAndAddRuleForm($item);
         }
      } else if ($item instanceof LevelAgreement) {
         $item->showRulesList();
      } else if ($item instanceof Rule) {
         $item->getRuleWithCriteriasAndActions($item->getID(), 1, 1);
         switch ($tabnum) {
            case 1 :
               $item->showCriteriasList($item->getID());
               break;

            case 2 :
               $item->showActionsList($item->getID());
               break;
         }
      }

      return true;
   }


   /**
    * Generate unique id for rule based on server name, glpi directory and basetime
    *
    * @since 0.85
    *
    * @return uuid
   **/
   static function getUuid() {

      //encode uname -a, ex Linux localhost 2.4.21-0.13mdk #1 Fri Mar 14 15:08:06 EST 2003 i686
      $serverSubSha1 = substr(sha1(php_uname('a')), 0, 8);
      // encode script current dir, ex : /var/www/glpi_X
      $dirSubSha1    = substr(sha1(__FILE__), 0, 8);

      return uniqid("$serverSubSha1-$dirSubSha1-", true);
   }


   /**
    * Display debug information for current object
    *
    * @since 0.85
   **/
   function showDebug() {

      echo "<div class='spaced'>";
      printf(__('%1$s: %2$s'), "<b>UUID</b>", $this->fields['uuid']);
      echo "</div>";
   }


}
