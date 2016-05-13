<?php
/*
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
 * FieldUnicity Class
**/
class FieldUnicity extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;

   public $second_level_menu  = "control";
   public $can_be_translated  = false;

   static $rightname          = 'config';


   static function getTypeName($nb=0) {
      return __('Fields unicity');
   }


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }


   function getAdditionalFields() {

      return array(array('name'  => 'is_active',
                         'label' => __('Active'),
                         'type'  => 'bool'),
                   array('name'  => 'itemtype',
                         'label' => __('Type'),
                         'type'  => 'unicity_itemtype'),
                   array('name'  => 'fields',
                         'label' => __('Unique fields'),
                         'type'  => 'unicity_fields'),
                   array('name'  => 'action_refuse',
                         'label' => __('Record into the database denied'),
                         'type'  => 'bool'),
                   array('name'  => 'action_notify',
                         'label' => __('Send a notification'),
                         'type'  => 'bool'));
   }


   /**
    * Define tabs to display
    *
    * @param $options array
   **/
   function defineTabs($options=array()) {

      $ong          = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         if ($item->getType() == $this->getType()) {
            return __('Duplicates');
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()==__CLASS__) {
         self::showDoubles($item);
      }
      return true;
   }


   /**
    * Display specific fields for FieldUnicity
    *
    * @param $ID
    * @param $field array
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'unicity_itemtype' :
            $this->showItemtype($ID, $this->fields['itemtype']);
            break;

         case 'unicity_fields' :
            self::selectCriterias($this);
            break;
      }
   }


   /**
    * Display a dropdown which contains all the available itemtypes
    *
    * @param ID      the field unicity item id
    * @param value   the selected value (default 0)
    *
    * @return nothing
   **/
   function showItemtype($ID, $value=0) {
      global $CFG_GLPI;

      //Criteria already added : only display the selected itemtype
      if ($ID > 0) {
         if ($item = getItemForItemtype($this->fields['itemtype'])) {
            echo $item->getTypeName();
         }
         echo "<input type='hidden' name='itemtype' value='".$this->fields['itemtype']."'>";

      } else {
         //Add criteria : display dropdown
         foreach ($CFG_GLPI['unicity_types'] as $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
               if ($item->canCreate()) {
                  $options[$itemtype] = $item->getTypeName(1);
               }
            }
         }
         asort($options);
         $rand = Dropdown::showFromArray('itemtype', $options, array('display_emptychoice' => true));

         $params = array('itemtype' => '__VALUE__',
                         'id'       => $ID);
         Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "span_fields",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownUnicityFields.php",
                                       $params);
      }

   }


   /**
    * Return criteria unicity for an itemtype, in an entity
    *
    * @param itemtype      the itemtype for which unicity must be checked
    * @param entities_id   the entity for which configuration must be retrivied (default 0)
    * @param $check_active (true by default)
    *
    * @return an array of fields to check, or an empty array if no
   **/
   public static function getUnicityFieldsConfig($itemtype, $entities_id=0, $check_active=true) {
      global $DB;

      //Get the first active configuration for this itemtype
      $query = "SELECT *
                FROM `glpi_fieldunicities`
                WHERE `itemtype` = '$itemtype' ".
                      getEntitiesRestrictRequest("AND", 'glpi_fieldunicities', "", $entities_id,
                                                 true);

      if ($check_active) {
         $query .= " AND `is_active` = '1' ";
      }

      $query .= "ORDER BY `entities_id` DESC";

      $current_entity = false;
      $return         = array();
      foreach ($DB->request($query) as $data) {
         //First row processed
         if (!$current_entity) {
            $current_entity = $data['entities_id'];
         }
         //Process only for one entity, not more
         if ($current_entity != $data['entities_id']) {
            break;
         }
         $return[] = $data;
      }
      return $return;
   }


   /**
    * Display a list of available fields for unicity checks
    *
    * @param $unicity an instance of CommonDBTM class
    *
    * @return nothing
   **/
   static function selectCriterias(CommonDBTM $unicity) {
      global $DB;


      echo "<span id='span_fields'>";

      if (!isset($unicity->fields['itemtype']) || !$unicity->fields['itemtype']) {
         echo  "</span>";
         return;
      }

      if (!isset($unicity->fields['entities_id'])) {
         $unicity->fields['entities_id'] = $_SESSION['glpiactive_entity'];
      }

      $unicity_fields = explode(',', $unicity->fields['fields']);

      self::dropdownFields($unicity->fields['itemtype'],
                           array('values' => $unicity_fields,
                                 'name'   => '_fields'));
      echo "</span>";
   }


   /** Dropdown fields for a specific itemtype
    *
    * @since version 0.84
    *
    * @param $itemtype          itemtype
    * @param $options   array    of options
   **/
   static function dropdownFields($itemtype, $options=array()) {
      global $DB;

      $p['name']    = 'fields';
      $p['display'] = true;
      $p['values']  = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      //Search option for this type
      if ($target = getItemForItemtype($itemtype)) {
         //Do not check unicity on fields in DB with theses types
         $blacklisted_types = array('longtext', 'text');

         //Construct list
         $values = array();
         foreach ($DB->list_fields(getTableForItemType($itemtype)) as $field) {
            $searchOption = $target->getSearchOptionByField('field', $field['Field']);
//             if (empty($searchOption)) {
//                if ($table = getTableNameForForeignKeyField($field['Field'])) {
//                   $searchOption = $target->getSearchOptionByField('field', 'name', $table);
//                }
//             }
            if (!empty($searchOption)
                && !in_array($field['Type'], $blacklisted_types)
                && !in_array($field['Field'], $target->getUnallowedFieldsForUnicity())) {
               $values[$field['Field']] = $searchOption['name'];
            }
         }
         $p['multiple'] = 1;
         $p['size']     = 15;

         return Dropdown::showFromArray($p['name'], $values, $p);
      }
      return false;
   }


   function getSearchOptions() {

      $tab                          = array();
      $tab['common']                = self::getTypeName();

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               = __('Name');
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['datatype']           = 'number';
      $tab[2]['massiveaction']      = false;

      $tab[3]['table']              = $this->getTable();
      $tab[3]['field']              = 'fields';
      $tab[3]['name']               = __('Unique fields');
      $tab[3]['massiveaction']      = false;
      $tab[3]['datatype']           = 'specific';
      $tab[3]['additionalfields']   = array('itemtype');

      $tab[4]['table']              = $this->getTable();
      $tab[4]['field']              = 'itemtype';
      $tab[4]['name']               = __('Type');
      $tab[4]['massiveaction']      = false;
      $tab[4]['datatype']           = 'itemtypename';
      $tab[4]['itemtype_list']      = 'unicity_types';

      $tab[5]['table']              = $this->getTable();
      $tab[5]['field']              = 'action_refuse';
      $tab[5]['name']               = __('Record into the database denied');
      $tab[5]['datatype']           = 'bool';

      $tab[6]['table']              = $this->getTable();
      $tab[6]['field']              = 'action_notify';
      $tab[6]['name']               = __('Send a notification');
      $tab[6]['datatype']           = 'bool';

      $tab[86]['table']             = $this->getTable();
      $tab[86]['field']             = 'is_recursive';
      $tab[86]['name']              = __('Child entities');
      $tab[86]['datatype']          = 'bool';

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[30]['table']             = $this->getTable();
      $tab[30]['field']             = 'is_active';
      $tab[30]['name']              = __('Active');
      $tab[30]['datatype']          = 'bool';
      $tab[30]['massiveaction']     = false;

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['datatype']          = 'dropdown';

      return $tab;
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
         case 'fields':
            if (isset($values['itemtype'])
                && !empty($values['itemtype'])) {
               if ($target = getItemForItemtype($values['itemtype'])) {
                  $searchOption = $target->getSearchOptionByField('field', $values[$field]);
                  $fields       = explode(',', $values[$field]);
                  $message      = array();
                  foreach ($fields as $field) {
                     $searchOption = $target->getSearchOptionByField('field',$field);

                     if (isset($searchOption['name'])) {
                        $message[] = $searchOption['name'];
                     }
                  }
                  return implode(', ',$message);
               }
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {
      global $DB;

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'fields' :
            if (isset($values['itemtype'])
                && !empty($values['itemtype'])) {
               $options['values'] = explode(',', $values[$field]);
               $options['name']   = $name;
               return self::dropdownFields($values['itemtype'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Perform checks to be sure that an itemtype and at least a field are selected
    *
    * @param input the values to insert in DB
    *
    * @return input the values to insert, but modified
   **/
   static function checkBeforeInsert($input) {

      if (!$input['itemtype']
          || empty($input['_fields'])) {
         Session::addMessageAfterRedirect(__("It's mandatory to select a type and at least one field"),
                                          true, ERROR);
         $input = array();

      } else {
         $input['fields'] = implode(',',$input['_fields']);
         unset($input['_fields']);
      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return self::checkBeforeInsert($input);
   }


   function prepareInputForUpdate($input) {

      $input['fields'] = implode(',',$input['_fields']);
      unset($input['_fields']);

      return $input;
   }


   /**
    * Delete all criterias for an itemtype
    *
    * @param itemtype
    *
    * @return nothing
   **/
   static function deleteForItemtype($itemtype) {
      global $DB;

      $query = "DELETE
                FROM `glpi_fieldunicities`
                WHERE `itemtype` LIKE '%Plugin$itemtype%'";
      $DB->query($query);
   }


   /**
    * List doubles
    *
    * @param $unicity an instance of FieldUnicity class
   **/
   static function showDoubles(FieldUnicity $unicity) {
      global $DB;

      $fields       = array();
      $where_fields = array();
      if (!$item = getItemForItemtype($unicity->fields['itemtype'])) {
         return;
      }
      foreach (explode(',',$unicity->fields['fields']) as $field) {
         $fields[]       = $field;
         $where_fields[] = $field;
      }

      if (!empty($fields)) {
         $colspan = count($fields) + 1;
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='".$colspan."'>".__('Duplicates')."</th></tr>";

         $entities = array($unicity->fields['entities_id']);
         if ($unicity->fields['is_recursive']) {
            $entities = getSonsOf('glpi_entities', $unicity->fields['entities_id']);
         }
         $fields_string = implode(',', $fields);

         if ($item->maybeTemplate()) {
            $where_template = " AND `".$item->getTable()."`.`is_template` = '0'";
         } else {
            $where_template = "";
         }

         $where_fields_string = "";
         foreach ($where_fields as $where_field) {
            if (getTableNameForForeignKeyField($where_field)) {
               $where_fields_string.= " AND `$where_field` IS NOT NULL AND `$where_field` <> '0'";
            } else {
               $where_fields_string.= " AND `$where_field` IS NOT NULL AND `$where_field` <> ''";
            }
         }
         $query = "SELECT $fields_string,
                          COUNT(*) AS cpt
                   FROM `".$item->getTable()."`
                   WHERE `".$item->getTable()."`.`entities_id` IN (".implode(',',$entities).")
                         $where_template
                         $where_fields_string
                   GROUP BY $fields_string
                   ORDER BY cpt DESC";
         $results = array();
         foreach ($DB->request($query) as $data) {
            if ($data['cpt'] > 1) {
               $results[] = $data;

            }
         }

         if (empty($results)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='$colspan'>".__('No item to display')."</td></tr>";
         } else {
            echo "<tr class='tab_bg_2'>";
            foreach ($fields as $field) {
               $searchOption = $item->getSearchOptionByField('field',$field);
               echo "<th>".$searchOption["name"]."</th>";
            }
            echo "<th>"._x('quantity', 'Number')."</th></tr>";

            foreach ($results as $result) {
               echo "<tr class='tab_bg_2'>";
               foreach ($fields as $field) {
                  $table = getTableNameForForeignKeyField($field);
                  if ($table != '') {
                     echo "<td>".Dropdown::getDropdownName($table, $result[$field])."</td>";
                  } else {
                     echo "<td>".$result[$field]."</td>";
                  }
               }
               echo "<td class='numeric'>".$result['cpt']."</td></tr>";
            }
         }

      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan='$colspan'>".__('No item to display')."</td></tr>";
      }
      echo "</table>";
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      $params = array('action_type' => true,
                      'action_user' => getUserName(Session::getLoginUserID()),
                      'entities_id' => $_SESSION['glpiactive_entity'],
                      'itemtype'    => get_class($this),
                      'date'        => $_SESSION['glpi_currenttime'],
                      'refuse'      => true,
                      'label'       => array('name' => 'test'),
                      'field'       => array('action_refuse' => true),
                      'double'      => array());

      NotificationEvent::debugEvent($this, $params);
   }

}
