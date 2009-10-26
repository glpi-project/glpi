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


abstract class CommonTreeDropdown extends CommonDBTM{

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

      echo "<tr class='tab_bg_1'><td>".$LANG['setup'][75]."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='hidden' name='itemtype' value='".$this->type."'>";
      dropdownValue($this->table, $this->keyid,
                    $this->fields["$this->keyid"], 1,
                    $this->fields["entities_id"], '',
                    ($ID>0 ? getSonsOf($this->table, $ID) : array()));
      echo "</td>";

      echo "<td rowspan='".($nb+2)."'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='".($nb+2)."'>
            <textarea cols='45' rows='".($nb+3)."' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
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
         }
         echo "</td></tr>\n";
      }

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
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

}

class TicketCategory extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(TICKETCATEGORY_TYPE);
   }

   function getAdditionalFields() {
      global $LANG;

      return array (array('name'  => 'users_id',
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

class Location extends CommonTreeDropdown {

   /**
    * Constructor
    **/
   function __construct(){
      parent::__construct(LOCATION_TYPE);
   }


   function getAdditionalFields() {
      global $LANG;

      return array (array('name'  => 'building',
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
}

?>
