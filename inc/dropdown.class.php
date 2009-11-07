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

///
abstract class CommonDropdown extends CommonDBTM {

   /**
    * Constructor
    **/
   function __construct($itemtype){
      global $LINK_ID_TABLE;

      $this->type=$itemtype;
      $this->table=$LINK_ID_TABLE[$itemtype];
   }

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
    * Constructor
    **/
   function __construct($itemtype){
      global $LINK_ID_TABLE;

      $this->type=$itemtype;
      $this->table=$LINK_ID_TABLE[$itemtype];
      $this->keyid=getForeignKeyFieldForTable($this->table);
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   /**
    * Return Additional Fileds for this type
    */
   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => $this->keyid,
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

      if (isset($input[$this->keyid])
          && $input[$this->keyid]>0
          && $parent->getFromDB($input[$this->keyid])) {
         $input['level'] = $parent->fields['level']+1;
         $input['completename'] = $parent->fields['completename'] . " > " . $input['name'];
      } else {
         $input[$this->keyid] = 0;
         $input['level'] = 1;
         $input['completename'] = $input['name'];
      }

      return $input;
   }

   function pre_deleteItem($ID) {
      global $DB;

      $parent = $this->fields[$this->keyid];

      CleanFields($this->table, 'sons_cache', 'ancestors_cache');
      $tmp = clone $this;
      $crit = array('FIELDS'=>'id',
                    $this->keyid=>$ID);
      foreach ($DB->request($this->table, $crit) as $data) {
         $data[$this->keyid] = $parent;
         $tmp->update($data);
      }
      return true;
   }

   function prepareInputForUpdate($input) {
      // Can't move a parent under a child
      if (isset($input[$this->keyid])
          && in_array($input[$this->keyid], getSonsOf($this->table, $input['id']))) {
         return false;
      }
      return $input;
   }

   function post_updateItem($input,$updates,$history=1) {
      if (in_array('name', $updates) || in_array($this->keyid, $updates)) {
         if (in_array($this->keyid, $updates)) {
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
      global $INFOFORM_PAGES;

      $link = '';
      if ($this->fields[$this->keyid]) {
         $papa = clone $this;
         if ($papa->getFromDB($this->fields[$this->keyid])) {
            $link = $papa->getTreeLink() . " > ";
         }
      }
      $name = $this->fields['name'];
      if (empty($name) || $_SESSION['glpiis_ids_visible']) {
         $name .= " (".$this->fields['id'].")";
      }
      return $link . "<a href='".GLPI_ROOT.'/'.$INFOFORM_PAGES[$this->type].
                     "&amp;id=".$this->fields['id']."'>$name</a>";
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

      $crit = array($this->keyid  => $ID,
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
         echo "<input type='hidden' name='".$this->keyid."' value='$ID'></td>";
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

/// TicketCategory class
class TicketCategory extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(TICKETCATEGORY_TYPE);
   }

   function getAdditionalFields() {
      global $LANG;

      return  array(array('name'  => $this->keyid,
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

/// TaskCategory class
class TaskCategory extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(TASKCATEGORY_TYPE);
   }


   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][98];
   }
}

/// Location class
class Location extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(LOCATION_TYPE);
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => $this->keyid,
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
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td>";
            echo "<td class='center'>";
            echo "<a onclick= \"if (markCheckboxes('massiveaction_form')) return false;\"".
                  " href='#'>".$LANG['buttons'][18]."</a></td>";
            echo "<td>/</td><td class='center'>";
            echo "<a onclick= \"if (unMarkCheckboxes('massiveaction_form')) return false;\"".
                  " href='#'>".$LANG['buttons'][19]."</a>";
            echo "</td><td class='left' width='80%'>";
            echo "<input type='hidden' name='itemtype' value='".NETPOINT_TYPE."'>";
            echo "<input type='hidden' name='action' value='delete'>";
            echo "<input type='submit' name='massiveaction' class='submit' value=\"".
                  $LANG['buttons'][6]."\" >\n";

            echo "</td></tr>";
            echo "</table></form>\n";
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

   /**
    * Constructor
    **/
   function __construct() {
      $this->type = NETPOINT_TYPE;
      $this->table = 'glpi_netpoints';
      $this->entity_assign = true;
   }

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
    * @param : $input array of values
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

/// Class ItemState
class ItemState extends CommonDropdown {
   /**
    * Constructor
    **/
   function __construct() {
      $this->type = ITEMSTATE_TYPE;
      $this->table = 'glpi_states';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][83];
   }
}

/// Class ItemState
class RequestType extends CommonDropdown {
   /**
    * Constructor
    **/
   function __construct() {
      $this->type = REQUESTTYPE_TYPE;
      $this->table = 'glpi_requesttypes';
   }

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

   function __construct() {
      $this->type = MANUFACTURER_TYPE;
      $this->table = 'glpi_manufacturers';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][5];
   }
}

/// Class ComputerType
class ComputerType extends CommonDropdown {

   function __construct() {
      $this->type = COMPUTERTYPE_TYPE;
      $this->table = 'glpi_computerstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][4];
   }
}

/// Class ComputerModel
class ComputerModel extends CommonDropdown {

   function __construct() {
      $this->type = COMPUTERMODEL_TYPE;
      $this->table = 'glpi_computersmodels';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][91];
   }
}

/// Class NetworkEquipementType
class NetworkEquipmentType extends CommonDropdown {

   function __construct() {
      $this->type = NETWORKEQUIPMENTTYPE_TYPE;
      $this->table = 'glpi_networkequipmentstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][42];
   }
}

/// Class NetworkEquipementModel
class NetworkEquipementModel extends CommonDropdown {

   function __construct() {
      $this->type = NETWORKEQUIPMENTMODEL_TYPE;
      $this->table = 'glpi_networkequipmentsmodels';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][95];
   }
}

/// Class PrinterType
class PrinterType extends CommonDropdown {

   function __construct() {
      $this->type = PRINTERTYPE_TYPE;
      $this->table = 'glpi_printerstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][43];
   }
}

/// Class PrinterModel
class PrinterModel extends CommonDropdown {

   function __construct() {
      $this->type = PRINTERMODEL_TYPE;
      $this->table = 'glpi_printersmodels';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][96];
   }
}

/// Class MonitorType
class MonitorType extends CommonDropdown {

   function __construct() {
      $this->type = MONITORTYPE_TYPE;
      $this->table = 'glpi_monitorstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][44];
   }
}

/// Class MonitorModel
class MonitorModel extends CommonDropdown {

   function __construct() {
      $this->type = MONITORMODEL_TYPE;
      $this->table = 'glpi_monitorsmodels';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][94];
   }
}

/// Class PeripheralType
class PeripheralType extends CommonDropdown {

   function __construct() {
      $this->type = PERIPHERALTYPE_TYPE;
      $this->table = 'glpi_peripheralstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][69];
   }
}

/// Class PeripheralModel
class PeripheralModel extends CommonDropdown {

   function __construct() {
      $this->type = PERIPHERALMODEL_TYPE;
      $this->table = 'glpi_peripheralsmodels';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][97];
   }
}

/// Class PhoneType
class PhoneType extends CommonDropdown {

   function __construct() {
      $this->type = PHONETYPE_TYPE;
      $this->table = 'glpi_phonestypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][504];
   }
}

/// Class PhoneModel
class PhoneModel extends CommonDropdown {

   function __construct() {
      $this->type = PHONEMODEL_TYPE;
      $this->table = 'glpi_phonesmodels';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][503];
   }
}

/// Class SoftwareLicenseType
class SoftwareLicenseType extends CommonDropdown {

   function __construct() {
      $this->type = SOFTWARELICENSETYPE_TYPE;
      $this->table = 'glpi_softwareslicensestypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['software'][30];
   }
}

/// Class CartridgeItemType
class CartridgeItemType extends CommonDropdown {

   function __construct() {
      $this->type = CARTRIDGEITEMTYPE_TYPE;
      $this->table = 'glpi_cartridgesitemstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][84];
   }
}

/// Class ConsumableItemType
class ConsumableItemType extends CommonDropdown {

   function __construct() {
      $this->type = CONSUMABLEITEMTYPE_TYPE;
      $this->table = 'glpi_consumablesitemstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][92];
   }
}

/// Class ContractType
class ContractType extends CommonDropdown {

   function __construct() {
      $this->type = CONTRACTTYPE_TYPE;
      $this->table = 'glpi_contractstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][85];
   }
}

/// Class ContactType
class ContactType extends CommonDropdown {

   function __construct() {
      $this->type = CONTACTTYPE_TYPE;
      $this->table = 'glpi_contactstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][82];
   }
}

/// Class DeviceMemoryType
class DeviceMemoryType extends CommonDropdown {

   function __construct() {
      $this->type = DEVICEMEMORYTYPE_TYPE;
      $this->table = 'glpi_devicesmemoriestypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][86];
   }
}

/// Class SupplierType
class SupplierType extends CommonDropdown {

   function __construct() {
      $this->type = SUPPLIERTYPE_TYPE;
      $this->table = 'glpi_supplierstypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][80];
   }
}

/// Class InterfacesType (Interface is a reserved keyword)
class InterfacesType extends CommonDropdown {

   function __construct() {
      $this->type = INTERFACESTYPE_TYPE;
      $this->table = 'glpi_interfacestypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][93];
   }
}

/// Class DeviceCaseType (Interface is a reserved keyword)
class DeviceCaseType extends CommonDropdown {

   function __construct() {
      $this->type = DEVICECASETYPE_TYPE;
      $this->table = 'glpi_devicescasestypes';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][45];
   }
}

/// Class PhonePowerSupply
class PhonePowerSupply extends CommonDropdown {

   function __construct() {
      $this->type = PHONEPOWERSUPPLY_TYPE;
      $this->table = 'glpi_phonespowersupplies';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][505];
   }
}

/// Class Filesystem
class Filesystem extends CommonDropdown {

   function __construct() {
      $this->type = FILESYSTEM_TYPE;
      $this->table = 'glpi_filesystems';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][4];
   }
}

/// Class DocumentCategory
class DocumentCategory extends CommonDropdown {

   function __construct() {
      $this->type = DOCUMENTCATEGORY_TYPE;
      $this->table = 'glpi_documentscategories';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][81];
   }
}

/// Class KnowbaseItemCategory
class KnowbaseItemCategory extends CommonDropdown {

   function __construct() {
      $this->type = KNOWBASEITEMCATEGORY_TYPE;
      $this->table = 'glpi_knowbaseitemscategories';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][5];
   }
}

/// Class OperatingSystem
class OperatingSystem extends CommonDropdown {

   function __construct() {
      $this->type = OPERATINGSYSTEM_TYPE;
      $this->table = 'glpi_operatingsystems';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][5];
   }
}

/// Class OperatingSystemVersion
class OperatingSystemVersion extends CommonDropdown {

   function __construct() {
      $this->type = OPERATINGSYSTEMVERSION_TYPE;
      $this->table = 'glpi_operatingsystemsversions';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][52];
   }
}

/// Class OperatingSystemServicePack
class OperatingSystemServicePack extends CommonDropdown {

   function __construct() {
      $this->type = OPERATINGSYSTEMSERVICEPACK_TYPE;
      $this->table = 'glpi_operatingsystemsservicepacks';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][53];
   }
}

/// Class AutoUpdateSystem
class AutoUpdateSystem extends CommonDropdown {

   function __construct() {
      $this->type = AUTOUPDATESYSTEM_TYPE;
      $this->table = 'glpi_autoupdatesystems';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][51];
   }
}

/// Class NetworkInterface
class NetworkInterface extends CommonDropdown {

   function __construct() {
      $this->type = NETWORKINTERFACE_TYPE;
      $this->table = 'glpi_networkinterfaces';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][9];
   }
}

/// Class NetworkEquipmentFirmware
class NetworkEquipmentFirmware extends CommonDropdown {

   function __construct() {
      $this->type = NETWORKEQUIPMENTFIRMWARE_TYPE;
      $this->table = 'glpi_networkequipmentsfirmwares';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][71];
   }
}

/// Class Domain
class Domain extends CommonDropdown {

   function __construct() {
      $this->type = DOMAIN_TYPE;
      $this->table = 'glpi_domains';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][89];
   }
}

/// Class Network
class Network extends CommonDropdown {

   function __construct() {
      $this->type = NETWORK_TYPE;
      $this->table = 'glpi_networks';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][88];
   }
}

/// Class Vlan
class Vlan extends CommonDropdown {

   function __construct() {
      $this->type = VLAN_TYPE;
      $this->table = 'glpi_vlans';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][90];
   }
}

/// Class SoftwareCategory
class SoftwareCategory extends CommonDropdown {

   function __construct() {
      $this->type = SOFTWARECATEGORY_TYPE;
      $this->table = 'glpi_softwarescategories';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['softwarecategories'][5];
   }
}

/// Class UserTitle
class UserTitle extends CommonDropdown {

   function __construct() {
      $this->type = USERTITLE_TYPE;
      $this->table = 'glpi_userstitles';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['users'][1];
   }
}

/// Class UserCategory
class UserCategory extends CommonDropdown {

   function __construct() {
      $this->type = USERCATEGORY_TYPE;
      $this->table = 'glpi_userscategories';
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['users'][1];
   }
}

?>