<?php
/*
 * @version $Id: holiday.class.php 13161 2010-11-29 13:43:46Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class Holiday
class Fieldblacklist extends CommonDropdown {

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][828];
   }


   function canCreate() {
      return haveRight('config', 'w');
   }


   function canView() {
      return haveRight('config', 'r');
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'itemtype',
                         'label' => $LANG['common'][17],
                         'type'  => 'blacklist_itemtype'),
                   array('name'  => 'name',
                         'label' => $LANG['rulesengine'][12],
                         'type'  => 'blacklist_field'),
                   array('name'  => 'value',
                         'label' => $LANG['rulesengine'][13],
                         'type'  => 'text'));
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['name']          = $LANG['common'][17];
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'itemtype';
      $tab[4]['forcegroupby']  = true;

      $tab[5]['table']    = $this->getTable();
      $tab[5]['field']    = 'name';
      $tab[5]['name']     = $LANG['rulesengine'][12];
      $tab[5]['datatype'] = 'string';

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'value';
      $tab[6]['name']     = $LANG['rulesengine'][13];
      $tab[6]['datatype'] = 'string';

      return $tab;
   }


   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd ($input);
      return $input;
   }


   function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   /**
    * Display specific fields for FieldUnicity
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'blacklist_itemtype' :
            $this->showItemtype($ID, $this->fields['itemtype']);
            break;

         case 'blacklist_field' :
            self::selectCriterias($this);
            break;
      }
   }


   /**
    * Display a dropdown which contains all the available itemtypes
    *
    * @param ID the field unicity item id
    * @param value the selected value
    *
    * @return nothing
   **/
   function showItemtype($ID, $value=0) {
      global $CFG_GLPI;

      //Add criteria : display dropdown
      $options[0] = DROPDOWN_EMPTY_VALUE;
      foreach ($CFG_GLPI['unicity_types'] as $itemtype) {
         if (class_exists($itemtype)) {
            $item = new $itemtype();
            if ($item->can(-1,'r')) {
               $options[$itemtype] = $item->getTypeName($itemtype);
            }
         }
      }
      asort($options);
      $rand = Dropdown::showFromArray('itemtype', $options, array('value'=>$value));

      $params = array('itemtype' => '__VALUE__',
                      'id'       => $ID);
      ajaxUpdateItemOnSelectEvent("dropdown_itemtype$rand", "span_fields",
                                  $CFG_GLPI["root_doc"]."/ajax/dropdownFieldsBlacklist.php",
                                  $params);
   }


   static function selectCriterias(CommonDBTM $blacklist) {
      global $DB, $CFG_GLPI;

      //Do not check unicity on fields with theses names
      $blacklisted_options = array('date_mod', 'id', 'is_recursive');

      //Do not check unicity on fields in DB with theses types
      $blacklisted_types = array('longtext', 'text');

      echo "<span id='span_fields' name='span_fields'>";

      if (!isset($blacklist->fields['itemtype']) || !$blacklist->fields['itemtype']) {
         echo  "</span>";
         return;
      }

      if (!isset($blacklist->fields['entities_id'])) {
         $blacklist->fields['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      $target = new $blacklist->fields['itemtype'];

      $criteria = array();
      foreach ($DB->list_fields($target->getTable()) as $field) {
         $searchOption = $target->getSearchOptionByField('field', $field['Field']);

         if (empty($searchOption)) {
            if ($table = getTableNameForForeignKeyField($field['Field'])) {
               $searchOption = $target->getSearchOptionByField('field', 'name', $table);
            }
         }

         if (!empty($searchOption)
             && !in_array($field['Type'],$blacklisted_types)
             && !in_array($field['Field'],$blacklisted_options)) {
            $criteria[$field['Field']] = $searchOption['name'];
         }
      }
      $rand   = Dropdown::showFromArray('field', $criteria,
                                        array('value' => $blacklist->fields['field']));
      $params = array('itemtype' => $blacklist->fields['itemtype'],
                      'id_field' => '__VALUE__');
      ajaxUpdateItemOnSelectEvent("dropdown_value$rand", "span_values",
                                  $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionField.php",
                                  $params);
      echo "</span>";
   }


   /**
    * Check if a field & value and blacklisted or not
    *
    * @param itemtype itemtype of the blacklisted field
    * @param entities_id  the entity in which the field must be saved
    * @param field the field to check
    * @param value the field's value
    *
    * @return true is value if blacklisted, false otherwise
   **/
   static function isFieldBlacklisted($itemtype, $entities_id, $field, $value) {
      global $DB;

      $query = "SELECT count(*) AS cpt
                FROM `glpi_fieldblacklists`
                WHERE `itemtype` = '$itemtype'
                      AND `field` = '$field'
                      AND `value` = '$value'".
                      getEntitiesRestrictRequest(" AND", "glpi_fieldblacklists", "entities_id",
                                                 $entities_id, true);
      return ($DB->result($DB->query($query),0,'cpt')?true:false);
   }

}

?>
