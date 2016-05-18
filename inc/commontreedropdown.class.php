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
 * CommonTreeDropdown Class
 *
 * Hierarchical and cross entities
**/
abstract class CommonTreeDropdown extends CommonDropdown {

   public $can_be_translated = false;


   /**
    * Return Additional Fileds for this type
   **/
   function getAdditionalFields() {

      return array(array('name'  => $this->getForeignKeyField(),
                         'label' => __('As child of'),
                         'type'  => 'parent',
                         'list'  => false));
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);

      $this->addStandardTab($this->getType(), $ong, $options);
      if ($this->dohistory) {
         $this->addStandardTab('Log',$ong, $options);
      }

      if (DropdownTranslation::canBeTranslated($this)) {
         $this->addStandardTab('DropdownTranslation',$ong, $options);
      }

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate
          && ($item->getType() == $this->getType())) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable($this->getTable(),
                                       "`".$this->getForeignKeyField()."` = '".$item->getID()."'");
         }
         return self::createTabEntry($this->getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item instanceof CommonTreeDropdown) {
         $item->showChildren();
      }
      return true;
   }


   /**
    * Compute completename based on parent one
    *
    * @param $parentCompleteName string parent complete name (need to be stripslashes / comes from DB)
    * @param $thisName           string item name (need to be addslashes : comes from input)
   **/
   static function getCompleteNameFromParents($parentCompleteName, $thisName) {
     return addslashes($parentCompleteName). " > ".$thisName;
   }


   /**
    * @param $input
   **/
   function adaptTreeFieldsFromUpdateOrAdd($input) {

      $parent = clone $this;
      // Update case input['name'] not set :
      if (!isset($input['name']) && isset($this->fields['name'])) {
         $input['name'] = addslashes($this->fields['name']);
      }
      // leading/ending space will break findID/import
      $input['name'] = trim($input['name']);

      if (isset($input[$this->getForeignKeyField()])
          && !$this->isNewID($input[$this->getForeignKeyField()])
          && $parent->getFromDB($input[$this->getForeignKeyField()])) {
         $input['level']        = $parent->fields['level']+1;
         // Sometimes (internet address), the complete name may be different ...
/*         if ($input[$this->getForeignKeyField()]==0) { // Root entity case
            $input['completename'] =  $input['name'];
         } else {*/
         $input['completename'] = self::getCompleteNameFromParents($parent->fields['completename'],
                                                                   $input['name']);
//          }
      } else {
         $input[$this->getForeignKeyField()] = 0;
         $input['level']                     = 1;
         $input['completename']              = $input['name'];
      }
      return $input;
   }


   function prepareInputForAdd($input) {
     return $this->adaptTreeFieldsFromUpdateOrAdd($input);
   }


   function pre_deleteItem() {
      global $DB;

      // Not set in case of massive delete : use parent
      if (isset($this->input['_replace_by']) && $this->input['_replace_by']) {
         $parent = $this->input['_replace_by'];
      } else {
         $parent = $this->fields[$this->getForeignKeyField()];
      }

      $this->recursiveCleanSonsAboveID($parent);
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

      if (isset($input[$this->getForeignKeyField()])) {
         // Can't move a parent under a child
         if (in_array($input[$this->getForeignKeyField()],
             getSonsOf($this->getTable(), $input['id']))) {
         return false;
         }
         // Parent changes => clear ancestors and update its level and completename
         if ($input[$this->getForeignKeyField()] != $this->fields[$this->getForeignKeyField()]) {
            $input["ancestors_cache"] = '';
            return $this->adaptTreeFieldsFromUpdateOrAdd($input);
         }
      }

      // Name changes => update its completename (and its level : side effect ...)
      if ((isset($input['name'])) && ($input['name'] != $this->fields['name'])) {
         return $this->adaptTreeFieldsFromUpdateOrAdd($input);
      }
      return $input;
   }


   /**
    * @param $ID
    * @param $updateName
    * @param $changeParent
   **/
   function regenerateTreeUnderID($ID, $updateName, $changeParent) {
      global $DB;

      if (($updateName) || ($changeParent)) {
         $currentNode = clone $this;

         if ($currentNode->getFromDB($ID)) {
            $currentNodeCompleteName = $currentNode->getField("completename");
            $nextNodeLevel           = ($currentNode->getField("level") + 1);
         } else {
            $nextNodeLevel = 1;
         }

         $query = "SELECT `id`, `name`
                   FROM `".$this->getTable()."`
                   WHERE `".$this->getForeignKeyField()."` = '$ID'";
        if (Session::haveTranslations($this->getType(), 'completename')) {
            DropdownTranslation::regenerateAllCompletenameTranslationsFor($this->getType(), $ID);
        }

         foreach ($DB->request($query) as $data) {
            $query = "UPDATE `".$this->getTable()."`
                      SET ";
            $fieldsToUpdate = array();

            if ($updateName || $changeParent) {
               if (isset($currentNodeCompleteName)) {
                  $fieldsToUpdate[] = "`completename`='".
                  self::getCompleteNameFromParents($currentNodeCompleteName,
                                                   addslashes($data["name"]))."'";
               } else {
                  $fieldsToUpdate[] = "`completename`='".addslashes($data["name"])."'";
               }
            }

            if ($changeParent) {
               // We have to reset the ancestors as only these changes (ie : not the children).
               $fieldsToUpdate[] = "`ancestors_cache` = NULL";
               // And we must update the level of the current node ...
               $fieldsToUpdate[] = "`level` = '$nextNodeLevel'";
            }
            $query .= implode(', ',$fieldsToUpdate)." WHERE `id`= '".$data["id"]."'";
            $DB->query($query);
            // Translations :
            if (Session::haveTranslations($this->getType(), 'completename')) {
                DropdownTranslation::regenerateAllCompletenameTranslationsFor($this->getType(), $data['id']);
            }

            $this->regenerateTreeUnderID($data["id"], $updateName, $changeParent);
         }
      }
   }


   /**
    * @param $ID
   **/
   function recursiveCleanSonsAboveID($ID) {
      global $DB;

      if ($ID > 0) {
         $query = "UPDATE `".$this->getTable()."`
                    SET `sons_cache` = NULL
                    WHERE `id` = '$ID'";
         $DB->query($query);

         $currentNode = clone $this;
         if ($currentNode->getFromDB($ID)) {
            $parentID = $currentNode->getField($this->getForeignKeyField());
            if ($ID != $parentID) {
               $this->recursiveCleanSonsAboveID($parentID);
            }
         }
      }
   }


   function post_addItem() {

      $parent = $this->fields[$this->getForeignKeyField()];
      $this->recursiveCleanSonsAboveID($parent);
      if ($parent && $this->dohistory) {
         $changes[0] = '0';
         $changes[1] = '';
         $changes[2] = addslashes($this->getNameID());
         Log::history($parent, $this->getType(), $changes, $this->getType(),
                      Log::HISTORY_ADD_SUBITEM);
      }
   }


   function post_updateItem($history=1) {

      $ID           = $this->getID();
      $changeParent = in_array($this->getForeignKeyField(), $this->updates);
      $this->regenerateTreeUnderID($ID, in_array('name', $this->updates), $changeParent);
      $this->recursiveCleanSonsAboveID($ID);

      if ($changeParent) {
         $oldParentID     = $this->oldvalues[$this->getForeignKeyField()];
         $newParentID     = $this->fields[$this->getForeignKeyField()];
         $oldParentNameID = '';
         $newParentNameID = '';

         $parent = clone $this;
         if ($oldParentID > 0) {
            $this->recursiveCleanSonsAboveID($oldParentID);
            if ($history) {
               if ($parent->getFromDB($oldParentID)) {
                  $oldParentNameID = $parent->getNameID();
               }
               $changes[0] = '0';
               $changes[1] = addslashes($this->getNameID());
               $changes[2] = '';
               Log::history($oldParentID, $this->getType(), $changes, $this->getType(),
                            Log::HISTORY_DELETE_SUBITEM);
            }
         }

         if ($newParentID > 0) {
            if ($history) {
               if ($parent->getFromDB($newParentID)) {
                  $newParentNameID = $parent->getNameID();
               }
               $changes[0] = '0';
               $changes[1] = '';
               $changes[2] = addslashes($this->getNameID());
               Log::history($newParentID, $this->getType(), $changes, $this->getType(),
                            Log::HISTORY_ADD_SUBITEM);
            }
         }

         if ($history) {
            $changes[0] = '0';
            $changes[1] = $oldParentNameID;
            $changes[2] = $newParentNameID;
            Log::history($ID, $this->getType(), $changes, $this->getType(),
                         Log::HISTORY_UPDATE_SUBITEM);
         }
         getAncestorsOf(getTableForItemType($this->getType()), $ID);
      }
   }


   function post_deleteFromDB() {

      $parent = $this->fields[$this->getForeignKeyField()];
      if ($parent && $this->dohistory) {
         $changes[0] = '0';
         $changes[1] = addslashes($this->getNameID());
         $changes[2] = '';
         Log::history($parent, $this->getType(), $changes, $this->getType(),
                      Log::HISTORY_DELETE_SUBITEM);
      }
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
    * @return Nothing (display)
    **/
   function showChildren() {
      global $DB, $CFG_GLPI;

      $ID            = $this->getID();
      $this->check($ID, READ);
      $fields        = $this->getAdditionalFields();
      $nb            = count($fields);
      $entity_assign = $this->isEntityAssign();

      // Minimal form for quick input.
      if (static::canCreate()) {
         $link = $this->getFormURL();
         echo "<div class='firstbloc'>";
         echo "<form action='".$link."' method='post'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>".__('New child heading')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>".__('Name')."</td><td>";
         Html::autocompletionTextField($this, "name", array('value' => ''));

         if ($entity_assign
             && ($this->getForeignKeyField() != 'entities_id')) {
            echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         }

         if ($entity_assign && $this->isRecursive()) {
            echo "<input type='hidden' name='is_recursive' value='1'>";
         }
         echo "<input type='hidden' name='".$this->getForeignKeyField()."' value='$ID'></td>";
         echo "<td><input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>";
         Html::closeForm();
         echo "</div>\n";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='".($nb+3)."'>".sprintf(__('Sons of %s'),
                                                                    $this->getTreeLink());
      echo "</th></tr>";

      $header = "<tr><th>".__('Name')."</th>";
      if ($entity_assign) {
         $header .= "<th>".__('Entity')."</th>";
      }
      foreach ($fields as $field) {
         if ($field['list']) {
            $header .= "<th>".$field['label']."</th>";
         }
      }
      $header .= "<th>".__('Comments')."</th>";
      $header .= "</tr>\n";
      echo $header;

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
      $nb = 0;
      foreach ($DB->request($this->getTable(), $crit) as $data) {
         $nb++;
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
      if ($nb) {
         echo $header;
      }
      echo "</table></div>\n";
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'move_under']
                  = _x('button', 'Move');
      }

      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'move_under' :
            $itemtype = $ma->getItemType(true);
            _e('As child of');
            Dropdown::show($itemtype, array('name'     => 'parent',
                                            'comments' => 0,
                                            'entity'   => $_SESSION['glpiactive_entity']));
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Move')."'>\n";
            return true;

      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      $input = $ma->getInput();

      switch ($ma->getAction()) {
         case 'move_under' :
            if (isset($input['parent'])) {
               $fk     = $item->getForeignKeyField();
               $parent = clone $item;
               if (!$parent->getFromDB($input['parent'])) {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                  $ma->addMessage($parent->getErrorMessage(ERROR_NOT_FOUND));
                  return;
               }
               foreach ($ids as $id) {
                  if ($item->can($id, UPDATE)) {
                     // Check if parent is not a child of the original one
                     if (!in_array($parent->getID(), getSonsOf($item->getTable(),
                                                               $item->getID()))) {
                        if ($item->update(array('id' => $id,
                                                $fk  => $parent->getID()))) {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               $ma->addMessage($parent->getErrorMessage(ERROR_COMPAT));
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                          = array();
      $tab['common']                = __('Characteristics');

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'completename';
      $tab[1]['name']               = __('Complete name');
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['massiveaction']      = false;
      $tab[2]['datatype']           = 'number';

      $tab[14]['table']             = $this->getTable();
      $tab[14]['field']             = 'name';
      $tab[14]['name']              = __('Name');
      $tab[14]['datatype']          = 'itemlink';

      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'completename';
      $tab[13]['name']              = __('Father');
      $tab[13]['datatype']          = 'dropdown';
      $tab[13]['massiveaction']     = false;
      // Add virtual condition to relink table
      $tab[13]['joinparams']        = array('condition' => "AND 1=1");

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      if ($this->isEntityAssign()) {
         $tab[80]['table']          = 'glpi_entities';
         $tab[80]['field']          = 'completename';
         $tab[80]['name']           = __('Entity');
         $tab[80]['massiveaction']  = false;
         $tab[80]['datatype']       = 'dropdown';
      }

      if ($this->maybeRecursive()) {
         $tab[86]['table']          = $this->getTable();
         $tab[86]['field']          = 'is_recursive';
         $tab[86]['name']           = __('Child entities');
         $tab[86]['datatype']       = 'bool';
      }

      if ($this->isField('date_mod')) {
         $tab[19]['table']          = $this->getTable();
         $tab[19]['field']          = 'date_mod';
         $tab[19]['name']           = __('Last update');
         $tab[19]['datatype']       = 'datetime';
         $tab[19]['massiveaction']  = false;
      }

      if ($this->isField('date_creation')) {
         $tab[121]['table']          = $this->getTable();
         $tab[121]['field']          = 'date_creation';
         $tab[121]['name']           = __('Creation date');
         $tab[121]['datatype']       = 'datetime';
         $tab[121]['massiveaction']  = false;
      }

      // add objectlock search options
      $tab += ObjectLock::getSearchOptionsToAdd( get_class($this) ) ;

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
    * reformat text field describing a tree (such as completename)
    *
    * @param $value string
    *
    * @return string
   **/
   static function cleanTreeText($value) {

      $tmp = explode('>', $value);
      foreach ($tmp as $k => $v) {
         $v = trim($v);
         if (empty($v)) {
            unset($tmp[$k]);
         } else {
            $tmp[$k] = $v;
         }
      }
      return implode(' > ', $tmp);
   }


   /**
    * check if a tree dropdown already exists (before import)
    *
    * @param &$input array of value to import (name, ...)
    *
    * @return the ID of the new (or -1 if not found)
   **/
   function findID(array &$input) {
      global $DB;

      if (isset($input['completename'])) {
         // Clean datas
         $input['completename'] = self::cleanTreeText($input['completename']);
      }

      if (isset($input['completename']) && !empty($input['completename'])) {
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `completename` = '".$input['completename']."'";
         if ($this->isEntityAssign()) {
            $query .= getEntitiesRestrictRequest(' AND ', $this->getTable(), '',
                                                 $input['entities_id'], $this->maybeRecursive());
         }
         // Check twin :
         if ($result_twin = $DB->query($query) ) {
            if ($DB->numrows($result_twin) > 0) {
               return $DB->result($result_twin,0,"id");
            }
         }

      } else if (isset($input['name']) && !empty($input['name'])) {
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
    * @return the ID of the new or existing dropdown (0 on failure)
   **/
   function import(array $input) {

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
         $name = trim($name);
         if (empty($name)) {
            // Skip empty name (completename starting/endind with >, double >, ...)
            continue;
         }
         $tmp['name'] = $name;
         $tmp[$fk]    = $parent;

         if (isset($input['is_recursive'])) {
            $tmp['is_recursive'] = $input['is_recursive'];
         }
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
