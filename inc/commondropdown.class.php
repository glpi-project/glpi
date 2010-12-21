<?php
/*
 * @version $Id$
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

/// CommonDropdown class - generic dropdown
abstract class CommonDropdown extends CommonDBTM {

   // For delete operation (entity will overload this value)
   public $must_be_replace = false;

   //Indicates if only the dropdown or the whole page is refreshed when a new dropdown value
   //is added using popup window
   public $refresh_page = false;


   /**
    * Return Additional Fileds for this type
    **/
   function getAdditionalFields() {
      return array();
   }


   function defineTabs($options=array()) {

      $ong = array();
      $ong[1] = $this->getTypeName();
      return $ong;
   }


   /**
    * Have I the right to "create" the Object
    *
    * MUST be overloaded for entity_dropdown
    *
    * @return booleen
    **/
   function canCreate() {
      return haveRight('dropdown','w');
   }


   /**
    * Have I the right to "view" the Object
    *
    * MUST be overloaded for entity_dropdown
    *
    * @return booleen
    **/
   function canView() {
      return haveRight('dropdown','r');
   }


   /**
    * Display content of Tab
    *
    * @param $ID of the item
    * @param $tab number of the tab
    *
    * @return true if handled (for class stack)
    **/
   function showTabContent ($ID, $tab) {

      if (!$this->isNewID($ID)) {
         switch ($tab) {
            case -1 :
               Plugin::displayAction($this, $tab);
               return false;

            default :
               return Plugin::displayAction($this, $tab);
         }
      }
      return false;
   }


   /**
    * Display title above search engine
    *
    * @return nothing (HTML display if needed)
    **/
   function title() {
      global $LANG;

      Dropdown::showItemTypeMenu($LANG['setup'][0],
                                 Dropdown::getStandardDropdownItemTypes(), $this->getSearchUrl());
   }


   function displayHeader () {
      commonHeader($this->getTypeName(), '', "config", "dropdowns", get_class($this));
   }


   function showForm ($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!$this->isNewID($ID)) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }
      $this->showTabs($options);
      $this->showFormHeader($options);

      $fields = $this->getAdditionalFields();
      $nb = count($fields);

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='hidden' name='itemtype' value='".$this->getType()."'>";
      if ($this instanceof CommonDevice) {
         // Awfull hack for CommonDevice where name is designation
         autocompletionTextField($this, "designation");
      } else {
         autocompletionTextField($this, "name");
      }
      echo "</td>";

      echo "<td rowspan='".($nb+1)."'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='".($nb+1)."'>
            <textarea cols='45' rows='".($nb+2)."' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      foreach ($fields as $field) {
         echo "<tr class='tab_bg_1'><td>".$field['label']."&nbsp;:</td><td>";
         switch ($field['type']) {
            case 'UserDropdown' :
               User::dropdown(array('name'   => $field['name'],
                                    'value'  => $this->fields[$field['name']],
                                    'right'  => 'interface',
                                    'entity' => $this->fields["entities_id"]));

               break;

            case 'dropdownValue' :
               Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField($field['name'])),
                              array('value'  => $this->fields[$field['name']],
                                    'name'   => $field['name'],
                                    'entity' => $this->getEntityID()));
               break;

            case 'text' :
               autocompletionTextField($this, $field['name']);
               break;

            case 'textarea' :
               echo "<textarea name='".$field['name']."' cols='40' rows='3'>";
               echo $this->fields[$field['name']];
               echo "</textarea >";
               break;

            case 'parent' :
               if ($field['name']=='entities_id') {
                  $restrict = -1;
               } else {
                  $restrict = $this->getEntityID();
               }
               Dropdown::show(getItemTypeForTable($this->getTable()),
                              array('value'  => $this->fields[$field['name']],
                                    'name'   => $field['name'],
                                    'entity' => $restrict,
                                    'used'   => ($ID>0 ? getSonsOf($this->getTable(), $ID) : array())));
               break;

            case 'icon' :
               Dropdown::dropdownIcons($field['name'], $this->fields[$field['name']],
                                       GLPI_ROOT."/pics/icones");
               if (!empty($this->fields[$field['name']])) {
                  echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".
                       $CFG_GLPI["typedoc_icon_dir"]."/".$this->fields[$field['name']]."'>";
               }
               break;

            case 'bool' :
               Dropdown::showYesNo($field['name'], $this->fields[$field['name']]);
               break;

            case 'date' :
               showDateFormItem($field['name'], $this->fields[$field['name']]);
               break;

            case 'datetime' :
               showDateTimeFormItem($field['name'], $this->fields[$field['name']]);
               break;
         }
         echo "</td></tr>\n";
      }

      if (isset($this->fields['is_protected']) && $this->fields['is_protected']) {
         $options['candel'] = false;
      }
      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function pre_deleteItem() {

      if (isset($this->fields['is_protected']) && $this->fields['is_protected']) {
         return false;
      }
      return true;
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
    **/
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      if ($this->isEntityAssign()) {
         $tab[80]['table']         = 'glpi_entities';
         $tab[80]['field']         = 'completename';
         $tab[80]['name']          = $LANG['entity'][0];
         $tab[80]['massiveaction'] = false;
      }
      if ($this->maybeRecursive()) {
         $tab[86]['table']     = $this->getTable();
         $tab[86]['field']     = 'is_recursive';
         $tab[86]['name']      = $LANG['entity'][9];
         $tab[86]['datatype']  = 'bool';
      }
      if ($this->isField('date_mod')) {
         $tab[19]['table']         = $this->getTable();
         $tab[19]['field']         = 'date_mod';
         $tab[19]['name']          = $LANG['common'][26];
         $tab[19]['datatype']      = 'datetime';
         $tab[19]['massiveaction'] = false;
      }

      return $tab;
   }


   /** Check if the dropdown $ID is used into item tables
    *
    * @return boolean : is the value used ?
    **/
   function isUsed() {
      global $DB;

      $ID = $this->fields['id'];

      $RELATION = getDbRelations();
      if (isset ($RELATION[$this->getTable()])) {
         foreach ($RELATION[$this->getTable()] as $tablename => $field) {
            if ($tablename[0]!='_') {
               if (!is_array($field)) {
                  $query = "SELECT COUNT(*) AS cpt
                            FROM `$tablename`
                            WHERE `$field` = '$ID'";
                  $result = $DB->query($query);
                  if ($DB->result($result, 0, "cpt") > 0) {
                     return true;
                  }

               } else {
                  foreach ($field as $f) {
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `$tablename`
                               WHERE `$f` = '$ID'";
                     $result = $DB->query($query);
                     if ($DB->result($result, 0, "cpt") > 0) {
                        return true;
                     }
                  }
               }
            }
         }
      }
      return false;
   }


   /**
    * Report if a dropdown have Child
    * Used to (dis)allow delete action
    **/
   function haveChildren() {
      return false;
   }


   /**
    * Show a dialog to Confirm delete action
    * And propose a value to replace
    *
    * @param $target string URL
    *
    *
    **/
   function showDeleteConfirmForm($target) {
      global $LANG;

      if ($this->haveChildren()) {
         echo "<div class='center'><p class='red'>" . $LANG['setup'][74] . "</p></div>";
         return false;
      }

      $ID = $this->fields['id'];

      echo "<div class='center'>";
      echo "<p class='red'>" . $LANG['setup'][63] . "</p>";

      if (!$this->must_be_replace) {
         // Delete form (set to 0)
         echo "<p>" . $LANG['setup'][64] . "</p>";
         echo "<form action='$target' method='post'>";
         echo "<table class='tab_cadre'><tr>";
         echo "<td><input type='hidden' name='id' value='$ID'>";
         echo "<input type='hidden' name='forcedelete' value='1'>";
         echo "<input class='button' type='submit' name='delete' value=\"".$LANG['buttons'][2]."\">";
         echo "</td>";
         echo "<td><input class='button' type='submit' name='annuler' value=\"".
                    $LANG['buttons'][34]."\">";
         echo "</td></tr></table>\n";
         echo "</form>";
      }

      // Replace form (set to new value)
      echo "<p>" . $LANG['setup'][65] . "</p>";
      echo "<form action='$target' method='post'>";
      echo "<table class='tab_cadre'><tr><td>";

      if ($this instanceof CommonTreeDropdown) {
         // TreeDropdown => default replacement is parent
         $fk=$this->getForeignKeyField();
         Dropdown::show(getItemTypeForTable($this->getTable()),
                        array('name'   => '_replace_by',
                              'value'  => $this->fields[$fk],
                              'entity' => $this->getEntityID(),
                              'used'   => getSonsOf($this->getTable(), $ID)));

      } else {
         Dropdown::show(getItemTypeForTable($this->getTable()),
                        array('name'   => '_replace_by',
                              'entity' => $this->getEntityID(),
                              'used'   => array($ID)));
      }
      echo "<input type='hidden' name='id' value='$ID'/>";
      echo "</td><td>";
      echo "<input class='button' type='submit' name='replace' value=\"".$LANG['buttons'][39]."\">";
      echo "</td><td>";
      echo "<input class='button' type='submit' name='annuler' value=\"".$LANG['buttons'][34]."\">";
      echo "</td></tr></table>\n";
      echo "</form>";
      echo "</div>";
   }


   /* Replace a dropdown item (this) by another one (newID)  and update all linked fields
    * @param $new integer ID of the replacement item
   function replace($newID) {
      global $DB,$CFG_GLPI;

      $oldID = $this->fields['id'];

      $RELATION = getDbRelations();

      if (isset ($RELATION[$this->getTable()])) {
         foreach ($RELATION[$this->getTable()] as $table => $field) {
            if ($table[0]!='_') {
               if (!is_array($field)) {
                  // Manage OCS lock for items - no need for array case
                  if ($table=="glpi_computers" && $CFG_GLPI['use_ocs_mode']) {
                     $query = "SELECT `id`
                               FROM `glpi_computers`
                               WHERE `is_ocs_import` = '1'
                                     AND `$field` = '$oldID'";
                     $result=$DB->query($query);
                     if ($DB->numrows($result)) {
                        if (!function_exists('OcsServer::mergeOcsArray')) {
                           include_once (GLPI_ROOT . "/inc/ocsng.function.php");
                        }
                        while ($data=$DB->fetch_array($result)) {
                           OcsServer::mergeOcsArray($data['id'],array($field),"computer_update");
                        }
                     }
                  }
                  $query = "UPDATE
                            `$table`
                            SET `$field` = '$newID'
                            WHERE `$field` = '$oldID'";
                  $DB->query($query);
               } else {
                  foreach ($field as $f) {
                     $query = "UPDATE
                               `$table`
                               SET `$f` = '$newID'
                               WHERE `$f` = '$oldID'";
                     $DB->query($query);
                  }
               }
            }
         }
      }
   }
    */


   /**
    * check if a dropdown already exists (before import)
    *
    * @param $input array of value to import (name)
    *
    * @return the ID of the new (or -1 if not found)
    */
   function getID (&$input) {
      global $DB;

      if (!empty($input["name"])) {

         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `name` = '".$input["name"]."'";
         if ($this->isEntityAssign()) {
            $query .= getEntitiesRestrictRequest(' AND ', $this->getTable(), '',
                                                 $input['entities_id'], $this->maybeRecursive());
         }

         // Check twin :
         if ($result_twin = $DB->query($query) ) {
            if ($DB->numrows($result_twin) > 0) {
               return $DB->result($result_twin, 0, "id");
            }
         }
      }
      return -1;
   }


   /**
    * Import a dropdown - check if already exists
    *
    * @param $input array of value to import (name, ...)
    *
    * @return the ID of the new or existing dropdown
    */
   function import ($input) {

      if (!isset($input['name'])) {
         return -1;
      }
      // Clean datas
      $input['name'] = trim($input['name']);

      if (empty($input['name'])) {
         return -1;
      }

      // Check twin :
      if ($ID = $this->getID($input)) {
         if ($ID>0) {
            return $ID;
         }
      }

      return $this->add($input);
   }


   /**
    * Import a value in a dropdown table.
    *
    * This import a new dropdown if it doesn't exist - Play dictionnary if needed
    *
    * @param $value string : Value of the new dropdown.
    * @param $entities_id int : entity in case of specific dropdown
    * @param $external_params array (manufacturer)
    * @param $comment
    * @param $add if true, add it if not found. if false, just check if exists
    *
    * @return integer : dropdown id.
    **/
   function importExternal($value, $entities_id = -1, $external_params=array(), $comment="",
                           $add=true) {

      $value = trim($value);
      if (strlen($value) == 0) {
         return 0;
      }

      $ruleinput = array("name" => $value);
      $rulecollection = RuleCollection::getClassByType($this->getType(),true);

      switch ($this->getTable()) {
         case "glpi_computermodels" :
         case "glpi_monitormodels" :
         case "glpi_printermodels" :
         case "glpi_peripheralmodels" :
         case "glpi_phonemodels" :
         case "glpi_networkequipmentmodels" :
            $ruleinput["manufacturer"] = $external_params["manufacturer"];
            break;
      }

      $input["name"] = $value;
      $input["comment"] = $comment;
      $input["entities_id"] = $entities_id;

      if ($rulecollection) {
         $res_rule = $rulecollection->processAllRules($ruleinput, array (), array());
         if (isset($res_rule["name"])) {
            $input["name"] = $res_rule["name"];
         }
      }
      return ($add ? $this->import($input) : $this->getID($input));
   }


   function refreshParentInfos() {

      if (!$this->refresh_page) {
         refreshDropdownPopupInMainWindow();
      } else {
         refreshPopupMainWindow();
      }
   }
}

?>