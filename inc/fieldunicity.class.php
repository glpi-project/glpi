<?php
/*
 * @version $Id: calendar.class.php 12774 2010-10-18 15:07:16Z yllen $
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

/// Class Calendar
class FieldUnicity extends CommonDropdown {

   // From CommonDBTM
   var $dohistory = true;

   var $second_level_menu = "control";

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][811];
   }


   function canCreate() {
      return haveRight('config', 'w');
   }


   function canView() {
      return haveRight('config', 'r');
   }

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'is_active',
                         'label' => $LANG['common'][60],
                         'type'  => 'bool'),
                   array('name'  => 'itemtype',
                         'label' => $LANG['common'][17],
                         'type'  => 'unicity_itemtype'),
                   array('name'  => 'fields',
                         'label' => $LANG['setup'][815],
                         'type'  => 'unicity_fields'));
   }
   
   /**
    * Display specific fields for FieldUnicity
    */
   function displaySpecificTypeField($ID, $field = array()) {
      global $CFG_GLPI;
      switch ($field['type']) {
         case 'unicity_itemtype':
            $this->showItemtype($ID, $this->fields['itemtype']);
            break;
         case 'unicity_fields':
            self::selectCriterias($this);
            break;
      }
   }

   function showItemtype($ID, $value) {
      global $CFG_GLPI;
      //Criteria already added : only display the selected itemtype
      if ($ID > 0) {
          $item = new $this->fields['itemtype'];
          echo $item->getTypeName();
          echo "<input type='hidden' name='itemtype' value='".$this->fields['itemtype']."'";
      } else {
         //Add criteria : display dropdown
         $options[0] = DROPDOWN_EMPTY_VALUE;
         foreach ($CFG_GLPI['unicity_types'] as $itemtype) {
            if (class_exists($itemtype)) {
               $item = new $itemtype();
               if ($item->can(-1,'r')) {
                  $result = self::getUnicityFieldsConfig($itemtype,$this->fields['entities_id']);
                  if (empty($result)) {
                     $options[$itemtype] = $item->getTypeName($itemtype);
                  }
               }
            }
         }
         asort($options);
         $rand = Dropdown::showFromArray('itemtype',$options);
         
         $params = array('itemtype' => '__VALUE__', 'id' => $ID);
         ajaxUpdateItemOnSelectEvent("dropdown_itemtype$rand", "span_fields",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownUnicityFields.php",
                                     $params);
         
      }
      
   }
   /**
    * Return criteria unicity for an itemtype, in an entity
    * @param itemtype the itemtype for which unicity must be checked
    * @param entities_id the entity for which configuration must be retrivied
    * @return an array of fields to check, or an empty array if no 
    */
   public static function getUnicityFieldsConfig($itemtype, $entities_id = 0,$check_active = true) {
      global $DB;

      //Get the first active configuration for this itemtype
      $query = "SELECT `fields`, `is_recursive` FROM `glpi_fieldunicities` 
                WHERE `itemtype` = '$itemtype' ";
      $query.= getEntitiesRestrictRequest(" AND","","",$entities_id,true); 
      if ($check_active) {
         $query.=" AND `is_active`='1' ";
      }
      $query.="ORDER BY `entities_id` DESC LIMIT 1";
      $result = $DB->query($query);

      $return = array();
      //A configuration found
      if ($DB->numrows($result)) {
         $tmp['is_recursive'] = $DB->result($result,0,'is_recursive');
         $tmp['fields'] = explode(',',$DB->result($result,0,'fields'));
         $return = $tmp;
      }
      return $return;
   }

   static function selectCriterias(CommonDBTM $item) {
      global $LANG;

      $blacklisted_options = array('id','date_mod');
 
      echo "<span id='span_fields' name='span_fields'>";
      
      if (!isset($item->fields['itemtype']) || !$item->fields['itemtype']) {
         echo  "</span>";
         return;
      }
      if (!isset($item->fields['entities_id'])) {
         $item->fields['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      $criteria = FieldUnicity::getUnicityFieldsConfig($item->fields['itemtype'],
                                                       $item->fields['entities_id'],false);
      
      //Search option for this type
      
      $target = new $item->fields['itemtype'];
      $searchOptions = $target->getSearchOptions();
      //Construct list
      echo "<span id='span_fields' name='span_fields'>";
      echo "<select name='_fields[]' multiple size='10'  style='width:400px'>";
      foreach ($searchOptions as $searchOption) {
         if (is_array($searchOption)) {
            if (isset($searchOptions["linkfield"])) {
               $field_id = $searchOption["linkfield"];
            } else {
               $field_id = $searchOption['field'];
            }
            if (!in_array($field_id,$blacklisted_options)) {
               echo "<option value='".$field_id."'"; 
               if (!empty($criteria) && in_array($field_id,$criteria['fields'])) {
                  echo " selected ";
               }
               echo  ">".$searchOption['name']."</option>";
            }
         }
      }
      echo "</select></span>";
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['setup'][811];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'fields';
      $tab[3]['name']          = $LANG['setup'][815];
      $tab[3]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['name']          = $LANG['common'][17];
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'itemtype';

      $tab[86]['table']    = $this->getTable();
      $tab[86]['field']    = 'is_recursive';
      $tab[86]['name']     = $LANG['entity'][9];
      $tab[86]['datatype'] = 'bool';

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      $tab[30]['table']    = $this->getTable();
      $tab[30]['field']    = 'is_active';
      $tab[30]['name']     = $LANG['common'][60];
      $tab[30]['datatype'] = 'bool';
      $tab[30]['massiveaction'] = false;

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['name']      = $LANG['entity'][0];

      return $tab;
   }

   static function checkBeforeInsert($input) {
      global $LANG;
      if (!$input['itemtype'] || empty($input['_fields'])) {
         addMessageAfterRedirect($LANG['setup'][817],true,ERROR);
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
      return self::checkBeforeInsert($input);
   }
   
   /**
    * Delete all criterias for an itemtype
    * @param itemtype
    * @return nothing
    */
   static function deleteForItemtype($itemtype) {
      global $DB;
      $query = "DELETE FROM `glpi_fieldunicities` WHERE `itemtype` LIKE '%Plugin$itemtype%'";
      $DB->query($query);
   }
}
?>
