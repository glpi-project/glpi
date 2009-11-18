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

/// CommonDropdown class - generic dropdown
abstract class CommonDropdown extends CommonDBTM {

   /**
    * Return Additional Fileds for this type
    */
   function getAdditionalFields() {
      return array();
   }

   /**
    * Get the localized display name of the type
    */
   abstract static function getTypeName();

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1] = $this->getTypeName();
      return $ong;
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
      if ($ID>0) {
         switch ($tab) {
            case -1 :
               displayPluginAction($this->type,$ID,$tab);
               return false;

            default :
               return displayPluginAction($this->type,$ID,$tab);
         }
      }
      return false;
   }

   function showForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, '',getActiveTab($this->type),array('itemtype'=>$this->type));
      $this->showFormHeader($target,$ID,'',2);

      $fields = $this->getAdditionalFields();
      $nb=count($fields);

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='hidden' name='itemtype' value='".$this->type."'>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td>";

      echo "<td rowspan='".($nb+1)."'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='".($nb+1)."'>
            <textarea cols='45' rows='".($nb+2)."' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      foreach ($fields as $field) {
         echo "<tr class='tab_bg_1'><td>".$field['label']."&nbsp;:</td><td>";
         switch ($field['type']) {
            case 'dropdownUsersID' :
               dropdownUsersID($field['name'], $this->fields[$field['name']], "interface", 1,
                                $this->fields["entities_id"]);
               break;
            case 'dropdownValue' :
               dropdownValue(getTableNameForForeignKeyField($field['name']),
                              $field['name'], $this->fields[$field['name']],1,
                              $this->fields["entities_id"]);
               break;
            case 'text' :
               autocompletionTextField($field['name'],$this->table,$field['name'],
                                       $this->fields[$field['name']],40);
               break;
            case 'parent' :
               dropdownValue($this->table, $field['name'],
                             $this->fields[$field['name']], 1,
                             $this->fields["entities_id"], '',
                             ($ID>0 ? getSonsOf($this->table, $ID) : array()));
               break;
            case 'bool' :
               dropdownYesNo($field['name'], $this->fields[$field['name']]);
               break;
         }
         echo "</td></tr>\n";
      }

      $candel=true;
      if (isset($this->fields['is_protected']) && $this->fields['is_protected']) {
         $candel=false;
      }
      $this->showFormButtons($ID,'',2,$candel);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function pre_deleteItem($id) {
      if (isset($this->fields['is_protected']) && $this->fields['is_protected']) {
         return false;
      }
      return true;
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
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->type;

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
         echo "<td><a href='".$CFG_GLPI["root_doc"].'/front/dropdown.form.php?itemtype=';
         echo $this->type.'&amp;id='.$data['id']."'>".$data['name']."</a></td>";
         echo "<td>".getDropdownName("glpi_entities",$data["entities_id"])."</td>";
         foreach ($fields as $field) {
            if ($field['list']) {
               echo "<td>";
               switch ($field['type']) {
                  case 'dropdownUsersID' :
                     echo getUserName($data[$field['name']]);
                     break;
                  case 'dropdownValue' :
                     echo getDropdownName(getTableNameForForeignKeyField($field['name']),
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

      $tab = parent::getSearchOptions();

      $tab[14]['table']         = $this->table;
      $tab[14]['field']         = 'completename';
      $tab[14]['linkfield']     = '';
      $tab[14]['name']          = $LANG['common'][51];
      $tab[14]['datatype']      = 'itemlink';
      $tab[14]['itemlink_type'] = $this->type;

      return $tab;
   }
}

/// TicketsCategory class
class TicketsCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $table = 'glpi_ticketscategories';
   public $type = TICKETCATEGORY_TYPE;
   public $entity_assign = true;
   public $may_be_recursive = true;

   function getAdditionalFields() {
      global $LANG;

      return  array(array('name'  => getForeignKeyFieldForTable($this->table),
                          'label' => $LANG['setup'][75],
                          'type'  => 'parent',
                          'list'  => false),
                   array('name'  => 'users_id',
                          'label' => $LANG['common'][10],
                          'type'  => 'dropdownUsersID',
                          'list'  => true),
                    array('name'  => 'groups_id',
                          'label' => $LANG['common'][35],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'knowbaseitemscategories_id',
                          'label' => $LANG['title'][5],
                          'type'  => 'dropdownValue',
                          'list'  => true));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][10];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      return $tab;
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][79];
   }
}

/// TasksCategory class
class TasksCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $table = 'glpi_taskscategories';
   public $type = TASKCATEGORY_TYPE;
   public $entity_assign = true;
   public $may_be_recursive = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][98];
   }
}

/// Location class
class Location extends CommonTreeDropdown {

   // From CommonDBTM
   public $table = 'glpi_locations';
   public $type = LOCATION_TYPE;
   public $entity_assign = true;
   public $may_be_recursive = true;

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => getForeignKeyFieldForTable($this->table),
                         'label' => $LANG['setup'][75],
                         'type'  => 'parent',
                         'list'  => false),
                   array('name'  => 'building',
                         'label' => $LANG['setup'][99],
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'room',
                         'label' => $LANG['setup'][100],
                         'type'  => 'text',
                         'list'  => true));
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][15];
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']         = $this->table;
      $tab[11]['field']         = 'building';
      $tab[11]['linkfield']     = 'building';
      $tab[11]['name']          = $LANG['setup'][99];
      $tab[11]['datatype']      = 'text';

      $tab[12]['table']         = $this->table;
      $tab[12]['field']         = 'room';
      $tab[12]['linkfield']     = 'room';
      $tab[12]['name']          = $LANG['setup'][100];
      $tab[12]['datatype']      = 'text';

      return $tab;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=parent::defineTabs($ID,$withtemplate);
      if ($ID>0) {
         $ong[2] = $LANG['networking'][51];
      }

      return $ong;
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
            case 2 :
               $this->showNetpoints($ID);
               return true;
            case -1 :
               $this->showNetpoints($ID);
               return false;
         }
      }
      return false;
   }

   /**
    * Print the HTML array of the Netpoint associated to a Location
    *
    *@param $ID of the Location
    *
    *@return Nothing (display)
    *
    **/
    function showNetpoints($ID) {
      global $DB, $CFG_GLPI, $LANG, $INFOFORM_PAGES;

      $this->check($ID, 'r');
      $canedit = $this->can($ID, 'w');

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }
      $number = countElementsInTable('`glpi_netpoints`', "`locations_id`='$ID'");

      echo "<br><div class='center'>";

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan>".$LANG['networking'][51]." - ".$LANG['search'][15]."</th></tr>";
      } else {
         printAjaxPager($this->getTreeLink()." - ".$LANG['networking'][51],$start,$number);

         if ($canedit) {
            echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action='".
                   $CFG_GLPI["root_doc"]."/front/massiveaction.php'>";
         }
         echo "<table class='tab_cadre_fixe'><tr>";
         if ($canedit) {
            echo "<th width='10'>&nbsp;</th>";
         }
         echo "<th>".$LANG['common'][16]."</th>"; // Name
         echo "<th>".$LANG['common'][25]."</th>"; // Comment
         echo "</tr>\n";

         $crit = array('locations_id' => $ID,
                       'ORDER'        => 'name',
                       'START'        => $start,
                       'LIMIT'        => $_SESSION['glpilist_limit']);

         initNavigateListItems(NETPOINT_TYPE, $this->getTypeName()."= ".$this->fields['name']);
         foreach ($DB->request('glpi_netpoints', $crit) as $data) {
            addToNavigateListItems(NETPOINT_TYPE,$data["id"]);
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<input type='checkbox' name='item[".$data["id"]."]' value='1'>";
            }
            echo "<td><a href='".$CFG_GLPI["root_doc"].'/front/dropdown.form.php?itemtype=';
            echo NETPOINT_TYPE.'&amp;id='.$data['id']."'>".$data['name']."</a></td>";
            echo "<td>".$data['comment']."</td>";
            echo "</tr>\n";
         }
         echo "</table>\n";
         if ($canedit) {
            openArrowMassive("massiveaction_form", true);
            echo "<input type='hidden' name='itemtype' value='".NETPOINT_TYPE."'>";
            echo "<input type='hidden' name='action' value='delete'>";
            closeArrowMassive('massiveaction', $LANG['buttons'][6]);

            echo "</form>\n";
         }
      }
      if ($canedit) {
         // Minimal form for quick input.
         echo "<form action='".GLPI_ROOT.'/'.$INFOFORM_PAGES[NETPOINT_TYPE]."' method='post'>";
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'><td class='b'>".$LANG['common'][87]."</td>";
         echo "<td>".$LANG['common'][16]."&nbsp;: ";
         autocompletionTextField("name",$this->table,"name");
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='locations_id' value='$ID'></td>";
         echo "<td><input type='submit' name='add' value=\"".
              $LANG['buttons'][8]."\" class='submit'></td>";
         echo "</tr>\n";
         echo "</table></form>\n";

         // Minimal form for massive input.
         echo "<form action='".GLPI_ROOT.'/'.$INFOFORM_PAGES[NETPOINT_TYPE]."' method='post'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'><td class='b'>".$LANG['common'][87]."</td>";
         echo "<td>".$LANG['common'][16]."&nbsp;: ";
         echo "<input type='text' maxlength='100' size='10' name='_before'>";
         dropdownInteger('_from', 0, 0, 400);
         echo "-->";
         dropdownInteger('_to', 0, 0, 400);
         echo "<input type='text' maxlength='100' size='10' name='_after'><br>";
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='locations_id' value='$ID'></td>";
         echo "<input type='hidden' name='_method' value='addMulti'></td>";
         echo "<td><input type='submit' name='execute' value=\"".
              $LANG['buttons'][8]."\" class='submit'></td>";
         echo "</tr>\n";
         echo "</table></form>\n";
      }
      echo "</div>\n";
   }
}

/// Netpoint class
class Netpoint extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_netpoints';
   public $type = NETPOINT_TYPE;
   public $entity_assign = true;

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'locations_id',
                         'label' => $LANG['common'][15],
                         'type'  => 'dropdownValue',
                         'list'  => true));
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][73];
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[3]['table']         = 'glpi_locations';
      $tab[3]['field']         = 'completename';
      $tab[3]['linkfield']     = 'locations_id';
      $tab[3]['name']          = $LANG['common'][15];
      $tab[3]['datatype']      = 'itemlink';
      $tab[3]['itemlink_type'] = LOCATION_TYPE;

      return $tab;
   }

   /**
    * Handled Multi add item
    *
    * @param $input array of values
    *
    */
   function addMulti ($input) {
      global $LANG;

      $this->check(-1,'w',$input);
      for ($i=$input["_from"] ; $i<=$input["_to"] ; $i++) {
         $input["name"]=$input["_before"].$i.$input["_after"];
         $this->add($input);
      }
      logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]);
      refreshMainWindow();
   }
}

/// Class State
class State extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_states';
   public $type = ITEMSTATE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][83];
   }
}

/// Class RequestType
class RequestType extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_requesttypes';
   public $type = REQUESTTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['job'][44];
   }

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'is_helpdesk_default',
                         'label' => $LANG['tracking'][9],
                         'type'  => 'bool'),
                   array('name'  => 'is_mail_default',
                         'label' => $LANG['tracking'][10],
                         'type'  => 'bool'));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[14]['table']         = $this->table;
      $tab[14]['field']         = 'is_helpdesk_default';
      $tab[14]['linkfield']     = '';
      $tab[14]['name']          = $LANG['tracking'][9];
      $tab[14]['datatype']      = 'bool';

      $tab[15]['table']         = $this->table;
      $tab[15]['field']         = 'is_mail_default';
      $tab[15]['linkfield']     = '';
      $tab[15]['name']          = $LANG['tracking'][10];
      $tab[15]['datatype']      = 'bool';

      return $tab;
   }

   function post_addItem($newID,$input) {
      global $DB;

      if (isset($input["is_helpdesk_default"]) && $input["is_helpdesk_default"]) {
         $query = "UPDATE ".
                   $this->table."
                   SET `is_helpdesk_default` = '0'
                   WHERE `id` <> '$newID'";
         $DB->query($query);
      }
      if (isset($input["is_mail_default"]) && $input["is_mail_default"]) {
         $query = "UPDATE ".
                   $this->table."
                   SET `is_mail_default` = '0'
                   WHERE `id` <> '$newID'";
         $DB->query($query);
      }
   }

   function post_updateItem($input,$updates,$history=1) {
      global $DB, $LANG;

      if (in_array('is_helpdesk_default',$updates)) {
         if ($input["is_helpdesk_default"]) {
            $query = "UPDATE ".
                      $this->table."
                      SET `is_helpdesk_default` = '0'
                      WHERE `id` <> '".$input['id']."'";
            $DB->query($query);
         } else {
            addMessageAfterRedirect($LANG['setup'][313], true);
         }
      }
      if (in_array('is_mail_default',$updates)) {
         if ($input["is_mail_default"]) {
            $query = "UPDATE ".
                      $this->table."
                      SET `is_mail_default` = '0'
                      WHERE `id` <> '".$input['id']."'";
            $DB->query($query);
         } else {
            addMessageAfterRedirect($LANG['setup'][313], true);
         }
      }
   }

   /**
    * Get the default request type for a given source (mail, helpdesk)
    *
    * @param $source string
    *
    * @return requesttypes_id
    */
   static function getDefault($source) {
      global $DB;

      if (!in_array($source, array('mail','helpdesk'))) {
         return 0;
      }
      foreach ($DB->request('glpi_requesttypes', array('is_'.$source.'_default'=>1)) as $data) {
         return $data['id'];
      }
      return 0;
   }
}

/// Class Manufacturer
class Manufacturer extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_manufacturers';
   public $type = MANUFACTURER_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][5];
   }
}

/// Class ComputersType
class ComputersType extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_computerstypes';
   public $type = COMPUTERTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][4];
   }
}

/// Class ComputersModel
class ComputersModel extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_computersmodels';
   public $type = COMPUTERMODEL_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][91];
   }
}

/// Class NetworkEquipementsType
class NetworkEquipmentsType extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_networkequipmentstypes';
   public $type = NETWORKEQUIPMENTTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][42];
   }
}

/// Class NetworkEquipementsModel
class NetworkEquipementsModel extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_networkequipmentsmodels';
   public $type = NETWORKEQUIPMENTMODEL_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][95];
   }
}

/// Class PrintersType
class PrintersType extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_printerstypes';
   public $type = PRINTERTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][43];
   }
}

/// Class PrintersModel
class PrintersModel extends CommonDropdown {

   // From CommonDBTM
   public $table = 'glpi_printersmodels';
   public $type = PRINTERMODEL_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][96];
   }
}

/// Class MonitorsType
class MonitorsType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_monitorstypes';
   public $type = MONITORTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][44];
   }
}

/// Class MonitorsModel
class MonitorsModel extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_monitorsmodels';
   public $type = MONITORMODEL_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][94];
   }
}

/// Class PeripheralsType
class PeripheralsType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_peripheralstypes';
   public $type = PERIPHERALTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][69];
   }
}

/// Class PeripheralsModel
class PeripheralsModel extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_peripheralsmodels';
   public $type = PERIPHERALMODEL_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][97];
   }
}

/// Class Phonesype
class PhonesType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_phonestypes';
   public $type = PHONETYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][504];
   }
}

/// Class PhonesModel
class PhonesModel extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_phonesmodels';
   public $type = PHONEMODEL_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][503];
   }
}

/// Class SoftwareLicenseType
class SoftwareLicenseType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_softwareslicensestypes';
   public $type = SOFTWARELICENSETYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['software'][30];
   }
}

/// Class CartridgeItemType
class CartridgeItemType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_cartridgesitemstypes';
   public $type = CARTRIDGEITEMTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][84];
   }
}

/// Class ConsumableItemType
class ConsumableItemType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_consumablesitemstypes';
   public $type = CONSUMABLEITEMTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][92];
   }
}

/// Class ContractsType
class ContractsType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_contractstypes';
   public $type = CONTRACTTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][85];
   }
}

/// Class ContactsType
class ContactsType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_contactstypes';
   public $type = CONTACTTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][82];
   }
}

/// Class DeviceMemoryType
class DeviceMemoryType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_devicesmemoriestypes';
   public $type = DEVICEMEMORYTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][86];
   }
}

/// Class SupplierType
class SupplierType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_supplierstypes';
   public $type = SUPPLIERTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][80];
   }
}

/// Class InterfacesType (Interface is a reserved keyword)
class InterfacesType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_interfacestypes';
   public $type = INTERFACESTYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][93];
   }
}

/// Class DeviceCaseType (Interface is a reserved keyword)
class DeviceCaseType extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_devicescasestypes';
   public $type = DEVICECASETYPE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][45];
   }
}

/// Class PhonePowerSupply
class PhonePowerSupply extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_phonespowersupplies';
   public $type = PHONEPOWERSUPPLY_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][505];
   }
}

/// Class Filesystem
class Filesystem extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_filesystems';
   public $type = FILESYSTEM_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][4];
   }
}

/// Class DocumentCategory
class DocumentCategory extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_documentscategories';
   public $type = DOCUMENTCATEGORY_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][81];
   }
}

/// Class KnowbaseItemCategory
class KnowbaseItemCategory extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_knowbaseitemscategories';
   public $type = KNOWBASEITEMCATEGORY_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][5];
   }
}

/// Class OperatingSystem
class OperatingSystem extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_operatingsystems';
   public $type = OPERATINGSYSTEM_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][5];
   }
}

/// Class OperatingSystemVersion
class OperatingSystemVersion extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_operatingsystemsversions';
   public $type = OPERATINGSYSTEMVERSION_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][52];
   }
}

/// Class OperatingSystemServicePack
class OperatingSystemServicePack extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_operatingsystemsservicepacks';
   public $type = OPERATINGSYSTEMSERVICEPACK_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][53];
   }
}

/// Class AutoUpdateSystem
class AutoUpdateSystem extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_autoupdatesystems';
   public $type = AUTOUPDATESYSTEM_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][51];
   }
}

/// Class NetworkInterface
class NetworkInterface extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_networkinterfaces';
   public $type = NETWORKINTERFACE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][9];
   }
}

/// Class NetworkEquipmentFirmware
class NetworkEquipmentsFirmware extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_networkequipmentsfirmwares';
   public $type = NETWORKEQUIPMENTFIRMWARE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][71];
   }
}

/// Class Domain
class Domain extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_domains';
   public $type = DOMAIN_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][89];
   }
}

/// Class Network
class Network extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_networks';
   public $type = NETWORK_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][88];
   }
}

/// Class Vlan
class Vlan extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_vlans';
   public $type = VLAN_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][90];
   }
}

/// Class SoftwareCategory
class SoftwaresCategory extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_softwarescategories';
   public $type = SOFTWARECATEGORY_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['softwarecategories'][5];
   }
}

/// Class UsersTitle
class UsersTitle extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_userstitles';
   public $type = USERTITLE_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['users'][1];
   }
}

/// Class UsersCategory
class UsersCategory extends CommonDropdown {

      // From CommonDBTM
   public $table = 'glpi_userscategories';
   public $type = USERCATEGORY_TYPE;

   static function getTypeName() {
      global $LANG;

      return $LANG['users'][1];
   }
}

?>