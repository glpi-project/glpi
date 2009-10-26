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
            case -1:
               displayPluginAction($this->type,$ID,$tab);
               return false;
            default:
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
         }
         echo "</td></tr>\n";
      }

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

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
      echo "</table></div>\n";
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
      $ong[2] = $LANG['networking'][51];

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
      echo "</div>\n";
   }
}

/// Netpoint class
class Netpoint extends CommonDropdown {

   /**
    * Constructor
    **/
   function __construct(){
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

      return $tab;
   }
}

?>
