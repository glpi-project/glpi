<?php
/*
 * @version $Id: document.class.php 9112 2009-10-13 20:17:16Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// CommonTreeDropdown class - Hirearchical and cross entities
abstract class CommonTreeDropdown extends CommonDropdown {

   /**
    * Return Additional Fileds for this type
    */
   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => getForeignKeyFieldForTable($this->table),
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
    */
   function showTabContent ($ID, $tab) {
      if ($ID>0 && !parent::showTabContent ($ID, $tab)) {
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

      if (isset($input[getForeignKeyFieldForTable($this->table)])
          && $input[getForeignKeyFieldForTable($this->table)]>0
          && $parent->getFromDB($input[getForeignKeyFieldForTable($this->table)])) {
         $input['level'] = $parent->fields['level']+1;
         $input['completename'] = $parent->fields['completename'] . " > " . $input['name'];
      } else {
         $input[getForeignKeyFieldForTable($this->table)] = 0;
         $input['level'] = 1;
         $input['completename'] = $input['name'];
      }

      return $input;
   }

   function pre_deleteItem($ID) {
      global $DB;

      $parent = $this->fields[getForeignKeyFieldForTable($this->table)];

      CleanFields($this->table, 'sons_cache', 'ancestors_cache');
      $tmp = clone $this;
      $crit = array('FIELDS'=>'id',
                    getForeignKeyFieldForTable($this->table)=>$ID);
      foreach ($DB->request($this->table, $crit) as $data) {
         $data[getForeignKeyFieldForTable($this->table)] = $parent;
         $tmp->update($data);
      }
      return true;
   }

   function prepareInputForUpdate($input) {
      // Can't move a parent under a child
      if (isset($input[getForeignKeyFieldForTable($this->table)])
          && in_array($input[getForeignKeyFieldForTable($this->table)],
                      getSonsOf($this->table, $input['id']))) {
         return false;
      }
      return $input;
   }

   function post_updateItem($input,$updates,$history=1) {
      if (in_array('name', $updates) || in_array(getForeignKeyFieldForTable($this->table), $updates)) {
         if (in_array(getForeignKeyFieldForTable($this->table), $updates)) {
            CleanFields($this->table, 'sons_cache', 'ancestors_cache');
         }
         regenerateTreeCompleteNameUnderID($this->table, $input['id']);
      }
   }

   /**
    * Get the this for all the current item and all its parent
    *
    * @return string
    */
   function getTreeLink() {

      $link = '';
      if ($this->fields[getForeignKeyFieldForTable($this->table)]) {
         $papa = clone $this;
         if ($papa->getFromDB($this->fields[getForeignKeyFieldForTable($this->table)])) {
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
    *
    **/
    function showChildren($ID) {
      global $DB, $CFG_GLPI, $LANG, $INFOFORM_PAGES;

      $this->check($ID, 'r');
      $fields = $this->getAdditionalFields();
      $nb=count($fields);

      echo "<br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".($nb+3)."'>".$LANG['setup'][75]."&nbsp;: ";
      echo $this->getTreeLink();
      echo "</th></tr>";
      echo "<tr><th>".$LANG['common'][16]."</th>"; // Name
      echo "<th>".$LANG['entity'][0]."</th>"; // Entity
      foreach ($fields as $field) {
         if ($field['list']) {
            echo "<th>".$field['label']."</th>";
         }
      }
      echo "<th>".$LANG['common'][25]."</th>";
      echo "</tr>\n";

      $crit = array(getForeignKeyFieldForTable($this->table)  => $ID,
                    'entities_id' => $_SESSION['glpiactiveentities']);
      foreach ($DB->request($this->table, $crit) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td><a href='".$this->getFormURL();
         echo '?id='.$data['id']."'>".$data['name']."</a></td>";
         echo "<td>".CommonDropdown::getDropdownName("glpi_entities",$data["entities_id"])."</td>";
         foreach ($fields as $field) {
            if ($field['list']) {
               echo "<td>";
               switch ($field['type']) {
                  case 'dropdownUsersID' :
                     echo getUserName($data[$field['name']]);
                     break;
                  case 'dropdownValue' :
                     echo CommonDropdown::getDropdownName(getTableNameForForeignKeyField($field['name']),
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
      echo "</table>\n";

      // Minimal form for quick input.
      if ($this->canCreate()) {
         echo "<form action='".GLPI_ROOT.'/'.$INFOFORM_PAGES[$this->type]."' method='post'>";
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'><td class='b'>".$LANG['common'][87]."</td>";
         echo "<td>".$LANG['common'][16]."&nbsp;: ";
         autocompletionTextField("name",$this->table,"name");
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='".getForeignKeyFieldForTable($this->table)."' value='$ID'></td>";
         echo "<td><input type='submit' name='add' value=\"".
              $LANG['buttons'][8]."\" class='submit'></td>";
         echo "</tr>\n";
         echo "</table></form></div>\n";
      }
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;

      $tab[1]['table']         = $this->table;
      $tab[1]['field']         = 'completename';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['common'][51];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->type;

      $tab[14]['table']         = $this->table;
      $tab[14]['field']         = 'name';
      $tab[14]['linkfield']     = '';
      $tab[14]['name']          = $LANG['common'][16];
      $tab[14]['datatype']      = 'itemlink';
      $tab[14]['itemlink_link'] = $this->type;

      $tab[16]['table']     = $this->table;
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      if ($this->entity_assign) {
         $tab[80]['table']     = 'glpi_entities';
         $tab[80]['field']     = 'completename';
         $tab[80]['linkfield'] = 'entities_id';
         $tab[80]['name']      = $LANG['entity'][0];
      }
      if ($this->may_be_recursive) {
         $tab[86]['table']     = $this->table;
         $tab[86]['field']     = 'is_recursive';
         $tab[86]['linkfield'] = 'is_recursive';
         $tab[86]['name']      = $LANG['entity'][9];
         $tab[86]['datatype']  = 'bool';
      }
      return $tab;
   }

   /**
    * Report if a dropdown have Child
    * Used to (dis)allow delete action
    */
   function haveChildren() {
      $fk = getForeignKeyFieldForTable($this->table);
      $id = $this->fields['id'];

      return (countElementsInTable($this->table,"`$fk`='$id'")>0);
   }
}

?>