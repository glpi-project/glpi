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
                   array('name'  => 'field',
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
      $tab[4]['datatype']      = 'itemtypename';
      $tab[4]['forcegroupby']  = true;

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'field';
      $tab[6]['name']     = $LANG['rulesengine'][12];
      $tab[6]['datatype'] = 'string';

      $tab[7]['table']    = $this->getTable();
      $tab[7]['field']    = 'value';
      $tab[7]['name']     = $LANG['rulesengine'][13];
      $tab[7]['datatype'] = 'string';

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
            $this->showItemtype();
            break;

         case 'blacklist_field' :
            $this->selectCriterias();
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
   function showItemtype() {
      global $CFG_GLPI;

      if ($this->fields['id'] > 0) {
         $item = new $this->fields['itemtype'];
         echo $item->getTypeName();
      } else {
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
         $rand = Dropdown::showFromArray('itemtype', $options, 
                                         array('value' => $this->fields['value']));
   
         $params = array('itemtype' => '__VALUE__',
                         'id'       => $this->fields['id']);
         ajaxUpdateItemOnSelectEvent("dropdown_itemtype$rand", "span_fields",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownFieldsBlacklist.php",
                                     $params);
      }
   }


   function selectCriterias() {
      global $DB, $CFG_GLPI;

      echo "<span id='span_fields' name='span_fields'>";

      if (!isset($this->fields['itemtype']) || !$this->fields['itemtype']) {
         echo  "</span>";
         return;
      }

      if (!isset($this->fields['entities_id'])) {
         $this->fields['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      $target = new $this->fields['itemtype'];

      $criteria = array();
      foreach ($DB->list_fields($target->getTable()) as $field) {
         $searchOption = $target->getSearchOptionByField('field', $field['Field']);

         if (empty($searchOption)) {
            if ($table = getTableNameForForeignKeyField($field['Field'])) {
               $searchOption = $target->getSearchOptionByField('field', 'name', $table);
            }
         }

         if (!empty($searchOption)
             && !in_array($field['Type'],$target->getUnallowedFieldsForUnicity())
             && !in_array($field['Field'],$target->getUnallowedFieldsForUnicity())) {
            $criteria[$field['Field']] = $searchOption['name'];
         }
      }
      $rand   = Dropdown::showFromArray('field', $criteria,
                                        array('value' => $this->fields['field']));
      /*
      $params = array('itemtype' => $blacklist->fields['itemtype'],
                      'id_field' => '__VALUE__');
      ajaxUpdateItemOnSelectEvent("dropdown_value$rand", "span_values",
                                  $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionField.php",
                                  $params);*/
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
