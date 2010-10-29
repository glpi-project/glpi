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

/// CommonTreeDropdown class - Hirearchical and cross entities
abstract class CommonTreeDropdown extends CommonDropdown {

   /**
    * Return Additional Fileds for this type
   **/
   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => $this->getForeignKeyField(),
                         'label' => $LANG['setup'][75],
                         'type'  => 'parent',
                         'list'  => false));
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

      if (!$this->isNewID($ID) && !parent::showTabContent ($ID, $tab)) {
         switch ($tab) {
            case 1 :
               $this->showChildren($ID);
               return true;

            case -1 :
               $this->showChildren($ID);
               return false;
         }
      }
      return false;
   }


   function prepareInputForAdd($input) {

      $parent = clone $this;

      if (isset($input[$this->getForeignKeyField()])
          && $input[$this->getForeignKeyField()]>0
          && $parent->getFromDB($input[$this->getForeignKeyField()])) {

         $input['level']        = $parent->fields['level']+1;
         $input['completename'] = addslashes($parent->fields['completename']) . " > " .
                                  $input['name'];
      } else {
         $input[$this->getForeignKeyField()] = 0;
         $input['level']        = 1;
         $input['completename'] = $input['name'];
      }

      return $input;
   }


   function pre_deleteItem() {
      global $DB;

      // Not set in case of massive delete : use parent
      if (isset($this->input['_replace_by']) && $this->input['_replace_by']) {
         $parent = $this->input['_replace_by'];
      } else {
         $parent = $this->fields[$this->getForeignKeyField()];
      }

      CleanFields($this->getTable(), array('sons_cache', 'ancestors_cache'));
      $tmp  = clone $this;
      $crit = array('FIELDS'                    => 'id',
                    $this->getForeignKeyField() => $this->fields["id"]);

      foreach ($DB->request($this->getTable(), $crit) as $data) {
         $data[$this->getForeignKeyField()] = $parent;
         $tmp->update($data);
      }

      return true;
   }


   function prepareInputForUpdate($input) {

      // Can't move a parent under a child
      if (isset($input[$this->getForeignKeyField()])
          && in_array($input[$this->getForeignKeyField()],
                      getSonsOf($this->getTable(), $input['id']))) {
         return false;
      }
      return $input;
   }


   function post_updateItem($history=1) {

      if (in_array('name', $this->updates)
          || in_array($this->getForeignKeyField(), $this->updates)) {

         if (in_array($this->getForeignKeyField(), $this->updates)) {
            CleanFields($this->getTable(), array('sons_cache', 'ancestors_cache'));
         }

         regenerateTreeCompleteNameUnderID($this->getTable(), $this->input['id']);
      }
   }


   function post_addItem() {
      CleanFields($this->getTable(), 'sons_cache');
   }


   /**
    * Get the this for all the current item and all its parent
    *
    * @return string
   **/
   function getTreeLink() {

      $link = '';
      if ($this->fields[$this->getForeignKeyField()]) {
         $papa = clone $this;

         if ($papa->getFromDB($this->fields[$this->getForeignKeyField()])) {
            $link = $papa->getTreeLink() . " > ";
         }

      }
      return $link . $this->getLink();
   }


   /**
    * Print the HTML array children of a TreeDropdown
    *
    *@param $ID of the dropdown
    *
    *@return Nothing (display)
    **/
    function showChildren($ID) {
      global $DB, $CFG_GLPI, $LANG;

      $this->check($ID, 'r');
      $fields        = $this->getAdditionalFields();
      $nb            = count($fields);
      $entity_assign = $this->isEntityAssign();

      // Minimal form for quick input.
      if ($this->canCreate()) {
         $link = $this->getFormURL();
         echo "<div class='firstbloc'>";
         echo "<form action='".$link."' method='post'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>".$LANG['common'][93]."</th></tr>";

         echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;: ";
         autocompletionTextField($this, "name", array('value' => ''));

         if ($entity_assign && $this->getForeignKeyField()!='entities_id') {
            echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         }

         if ($entity_assign && $this->isRecursive()) {
            echo "<input type='hidden' name='is_recursive' value='1'>";
         }
         echo "<input type='hidden' name='".$this->getForeignKeyField()."' value='$ID'></td>";
         echo "<td><input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table></form></div>\n";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".($nb+3)."'>".$LANG['setup'][75]."&nbsp;: ".$this->getTreeLink();
      echo "</th></tr>";

      echo "<tr><th>".$LANG['common'][16]."</th>"; // Name
      if ($entity_assign) {
         echo "<th>".$LANG['entity'][0]."</th>"; // Entity
      }
      foreach ($fields as $field) {
         if ($field['list']) {
            echo "<th>".$field['label']."</th>";
         }
      }
      echo "<th>".$LANG['common'][25]."</th>";
      echo "</tr>\n";

      $fk   = $this->getForeignKeyField();
      $crit = array($fk     => $ID,
                    'ORDER' => 'name');

      if ($entity_assign) {
         if ($fk == 'entities_id') {
            $crit['id']  = $_SESSION['glpiactiveentities'];
            $crit['id'] += $_SESSION['glpiparententities'];
         } else {
            $crit['entities_id'] = $_SESSION['glpiactiveentities'];
         }
      }

      foreach ($DB->request($this->getTable(), $crit) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td><a href='".$this->getFormURL();
         echo '?id='.$data['id']."'>".$data['name']."</a></td>";
         if ($entity_assign) {
            echo "<td>".Dropdown::getDropdownName("glpi_entities", $data["entities_id"])."</td>";
         }

         foreach ($fields as $field) {
            if ($field['list']) {
               echo "<td>";
               switch ($field['type']) {
                  case 'UserDropdown' :
                     echo getUserName($data[$field['name']]);
                     break;

                  case 'bool' :
                     echo Dropdown::getYesNo($data[$field['name']]);
                     break;

                  case 'dropdownValue' :
                     echo Dropdown::getDropdownName(getTableNameForForeignKeyField($field['name']),
                                                    $data[$field['name']]);
                     break;

                  default:
                     echo $data[$field['name']];
               }
               echo "</td>";
            }
         }
         echo "<td>".$data['comment']."</td>";
         echo "</tr>\n";
      }
      echo "</table></div>\n";
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];;

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'completename';
      $tab[1]['name']          = $LANG['common'][51];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'name';
      $tab[14]['name']          = $LANG['common'][16];
      $tab[14]['datatype']      = 'itemlink';
      $tab[14]['itemlink_link'] = $this->getType();

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      if ($this->isEntityAssign()) {
         $tab[80]['table']         = 'glpi_entities';
         $tab[80]['field']         = 'completename';
         $tab[80]['name']          = $LANG['entity'][0];
         $tab[80]['massiveaction'] = false;
      }

      if ($this->maybeRecursive()) {
         $tab[86]['table']    = $this->getTable();
         $tab[86]['field']    = 'is_recursive';
         $tab[86]['name']     = $LANG['entity'][9];
         $tab[86]['datatype'] = 'bool';
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


   /**
    * Report if a dropdown have Child
    * Used to (dis)allow delete action
   **/
   function haveChildren() {

      $fk = $this->getForeignKeyField();
      $id = $this->fields['id'];

      return (countElementsInTable($this->getTable(), "`$fk`='$id'") > 0);
   }


   /**
    * check if a tree dropdown already exists (before import)
    *
    * @param $input array of value to import (name, ...)
    *
    * @return the ID of the new (or -1 if not found)
   **/
   function getID (&$input) {
      global $DB;

      if (!empty($input["name"])) {
         $fk = $this->getForeignKeyField();

         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `name` = '".$input["name"]."'
                         AND `$fk` = '".(isset($input[$fk]) ? $input[$fk] : 0)."'";

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
    * @param $input array of value to import (name or completename, ...)
    *
    * @return the ID of the new or existing dropdown
   **/
   function import ($input) {

      if (isset($input['name'])) {
         return parent::import($input);
      }

      if (!isset($input['completename']) || empty($input['completename'])) {
         return -1;
      }

      // Import a full tree from completename
      $names  = explode('>',$input['completename']);
      $fk     = $this->getForeignKeyField();
      $i      = count($names);
      $parent = 0;

      foreach ($names as $name) {
         $i--;
         if (empty($name)) {
            // Skip empty name (completename starting/endind with >, double >, ...)
            continue;
         }
         $tmp['name'] = $name;
         $tmp[$fk]    = $parent;

         if (isset($input['entities_id'])) {
            $tmp['entities_id'] = $input['entities_id'];
         }

         if (!$i) {
            // Other fields (comment, ...) only for last node of the tree
            foreach ($input as $key => $val) {
               if ($key != 'completename') {
                  $tmp[$key] = $val;
               }
            }
         }

         $parent = parent::import($tmp);
      }
      return $parent;
   }

}

?>
