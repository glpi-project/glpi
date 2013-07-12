<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/// Reminder class
class Reminder extends CommonDBTM {

   // For visibility checks
   protected $users     = array();
   protected $groups    = array();
   protected $profiles  = array();
   protected $entities  = array();


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['title'][37];
      }
      return $LANG['title'][36];
   }


   function canCreate() {
      return (Session::haveRight('reminder_public', 'w')
              || $_SESSION['glpiactiveprofile']['interface'] != 'helpdesk');
   }


   function canView() {
      return (Session::haveRight('reminder_public', 'r')
              || $_SESSION['glpiactiveprofile']['interface'] != 'helpdesk');
   }


   function canViewItem() {

      // Is my reminder or is in visibility
      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (Session::haveRight('reminder_public', 'r')
                  && $this->haveVisibilityAccess()));
   }


   function canCreateItem() {
      // Is my reminder
      return ($this->fields['users_id'] == Session::getLoginUserID());
   }


   function canUpdateItem() {

      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (Session::haveRight('reminder_public', 'w')
                   && $this->haveVisibilityAccess()));
   }


   function post_getFromDB() {

      // Users
      $this->users    = Reminder_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = Entity_Reminder::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = Group_Reminder::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = Profile_Reminder::getProfiles($this->fields['id']);
   }

   function cleanDBonPurge() {

      $class = new Reminder_User();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Entity_Reminder();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Group_Reminder();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Profile_Reminder();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }

   /**
    * @since version 0.83
   **/
   function countVisibilities() {

      return (count($this->entities)
              + count($this->users)
              + count($this->groups)
              + count($this->profiles));
   }


   /**
    * Is the login user have access to reminder based on visibility configuration
    *
    * @return boolean
   **/
   function haveVisibilityAccess() {

      // No public reminder right : no visibility check
      if (!Session::haveRight('reminder_public', 'r')) {
         return false;
      }

      // Author
      if ($this->fields['users_id'] == Session::getLoginUserID()) {
         return true;
      }

      // Users
      if (isset($this->users[Session::getLoginUserID()])) {
         return true;
      }

      // Groups
      if (count($this->groups)
          && isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
         foreach ($this->groups as $key => $data) {
            foreach ($data as $group) {
               if (in_array($group['groups_id'], $_SESSION["glpigroups"])) {
                  // All the group
                  if ($group['entities_id'] < 0) {
                     return true;
                  }
                  // Restrict to entities
                  $entities = array($group['entities_id']);
                  if ($group['is_recursive']) {
                     $entities = getSonsOf('glpi_entities', $group['entities_id']);
                  }
                  if (Session::haveAccessToOneOfEntities($entities, true)) {
                     return true;
                  }
               }
            }
         }
      }

      // Entities
      if (count($this->entities)
          && isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         foreach ($this->entities as $key => $data) {
            foreach ($data as $entity) {
               $entities = array($entity['entities_id']);
               if ($entity['is_recursive']) {
                  $entities = getSonsOf('glpi_entities', $entity['entities_id']);
               }
               if (Session::haveAccessToOneOfEntities($entities, true)) {
                  return true;
               }
            }
         }
      }

      // Profiles
      if (count($this->profiles)
          && isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
         if (isset($this->profiles[$_SESSION["glpiactiveprofile"]['id']])) {
            foreach ($this->profiles[$_SESSION["glpiactiveprofile"]['id']] as $profile) {
               // All the profile
               if ($profile['entities_id'] < 0) {
                  return true;
               }
               // Restrict to entities
               $entities = array($profile['entities_id']);
               if ($profile['is_recursive']) {
                  $entities = getSonsOf('glpi_entities',$profile['entities_id']);
               }
               if (Session::haveAccessToOneOfEntities($entities, true)) {
                  return true;
               }
            }
         }
      }

      return false;
   }


   /**
    * Return visibility joins to add to SQL
    *
    * @param $forceall force all joins
    *
    * @return string joins to add
   **/
   static function addVisibilityJoins($forceall=false) {

      if (!Session::haveRight('reminder_public', 'r')) {
         return '';
      }

      // Users
      $join = " LEFT JOIN `glpi_reminders_users`
                     ON (`glpi_reminders_users`.`reminders_id` = `glpi_reminders`.`id`) ";

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join .= " LEFT JOIN `glpi_groups_reminders`
                        ON (`glpi_groups_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join .= " LEFT JOIN `glpi_profiles_reminders`
                        ON (`glpi_profiles_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
      }

      // Entities
      if ($forceall
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join .= " LEFT JOIN `glpi_entities_reminders`
                        ON (`glpi_entities_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
      }

      return $join;

   }


   /**
    * Return visibility SQL restriction to add
    *
    * @return string restrict to add
   **/
   static function addVisibilityRestrict() {

      $restrict = "`glpi_reminders`.`users_id` = '".Session::getLoginUserID()."' ";

      if (!Session::haveRight('reminder_public', 'r')) {
         return $restrict;
      }

      // Users
      $restrict .= " OR `glpi_reminders_users`.`users_id` = '".Session::getLoginUserID()."' ";

      // Groups
      if (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
         $restrict .= " OR (`glpi_groups_reminders`.`groups_id`
                                 IN ('".implode("','",$_SESSION["glpigroups"])."')
                            AND (`glpi_groups_reminders`.`entities_id` < 0
                                 ".getEntitiesRestrictRequest("OR", "glpi_groups_reminders", '', '',
                                                              true).")) ";
      }

      // Profiles
      if (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
         $restrict .= " OR (`glpi_profiles_reminders`.`profiles_id`
                                 = '".$_SESSION["glpiactiveprofile"]['id']."'
                            AND (`glpi_profiles_reminders`.`entities_id` < 0
                                 ".getEntitiesRestrictRequest("OR", "glpi_profiles_reminders", '',
                                                              '', true).")) ";
      }

      // Entities
      if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         // Force complete SQL not summary when access to all entities
         $restrict .= getEntitiesRestrictRequest("OR","glpi_entities_reminders", '', '', true, true);
      }

      return '('.$restrict.')';
   }


   function post_addItem() {
      if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {

         Planning::checkAlreadyPlanned($this->fields["users_id"], $this->fields["begin"],
                                       $this->fields["end"],
                                       array('Reminder' => array($this->fields['id'])));
      }
   }


   function post_updateItem($history=1) {
      if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {
         Planning::checkAlreadyPlanned($this->fields["users_id"], $this->fields["begin"],
                                       $this->fields["end"],
                                       array('Reminder' => array($this->fields['id'])));
      }
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;
      $tab[1]['forcegroupby']  = true;

      $tab[2]['table']         = 'glpi_users';
      $tab[2]['field']         = 'name';
      $tab[2]['name']          = $LANG['common'][37];
      $tab[2]['datatype']      = 'dropdown';
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'state';
      $tab[3]['name']          = $LANG['state'][0];
      $tab[3]['datatype']      = 'dropdown';
      $tab[3]['massiveaction'] = false;
      $tab[3]['searchtype']    = 'equals';

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'text';
      $tab[4]['name']          = $LANG['joblist'][6];
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'text';
      $tab[4]['htmltext']      = true;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'begin_view_date';
      $tab[5]['name']          = $LANG['search'][8];
      $tab[5]['datatype']      = 'datetime';

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'end_view_date';
      $tab[6]['name']          = $LANG['search'][9];
      $tab[6]['datatype']      = 'datetime';

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'is_planned';
      $tab[7]['name']          = $LANG['job'][35];
      $tab[7]['datatype']      = 'bool';
      $tab[7]['massiveaction'] = false;

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'begin';
      $tab[8]['name']          = $LANG['job'][35].' - '.$LANG['search'][8];
      $tab[8]['datatype']      = 'datetime';

      $tab[9]['table']         = $this->getTable();
      $tab[9]['field']         = 'end';
      $tab[9]['name']          = $LANG['job'][35].' - '.$LANG['search'][9];
      $tab[9]['datatype']      = 'datetime';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      return $tab;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (Session::haveRight("reminder_public","r")) {
         switch ($item->getType()) {
            case 'Reminder' :
               if ($item->canUpdate()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     return array(1 => self::createTabEntry($LANG['reminder'][2],
                                                            $item->countVisibilities()));
                  }
                  return array(1 => $LANG['reminder'][2]);
               }
         }
      }
      return '';
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong    = array();
      $this->addStandardTab('Document', $ong, $options);
      $this->addStandardTab('Reminder', $ong, $options);

      return $ong;
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Reminder' :
            $item->showVisibility();
            return true;
      }
      return false;
   }


   function prepareInputForAdd($input) {
      global $LANG;

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      $input["name"] = trim($input["name"]);

      if (empty($input["name"])) {
         $input["name"] = $LANG['reminder'][15];
      }

      $input["begin"] = $input["end"] = "NULL";


      if (isset($input['plan'])) {
         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && $input['plan']["begin"]<$input['plan']["end"]) {

            $input['_plan']      = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else {
            Session::addMessageAfterRedirect($LANG['planning'][1], false, ERROR);
         }
      }

      // set new date.
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function prepareInputForUpdate($input) {
      global $LANG;

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input["name"])) {
         $input["name"] = trim($input["name"]);

         if (empty($input["name"])) {
            $input["name"] = $LANG['reminder'][15];
         }
      }

      if (isset($input['plan'])) {

         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && $input['plan']["begin"]<$input['plan']["end"]) {

            $input['_plan']      = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else {
            Session::addMessageAfterRedirect($LANG['planning'][1], false, ERROR);
         }
      }

      return $input;
   }


   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if ($this->fields['users_id']==0 && $uid=Session::getLoginUserID()) {
         $this->fields['users_id'] = $uid;
         $this->updates[]="users_id";
      }
   }


   function post_getEmpty() {
      global $LANG;

      $this->fields["name"]        = $LANG['reminder'][6];
      $this->fields["users_id"]    = Session::getLoginUserID();
   }


   /**
    * Print the reminder form
    *
    * @param $ID Integer : Id of the item to print
    * @param $options array
    *     - target filename : where to go when done.
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      // Show Reminder or blank form
      $onfocus = "";

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item : do getempty before check right to set default values
         $this->check(-1, 'w');
         $onfocus="onfocus=\"if (this.value=='".$this->fields['name']."') this.value='';\"";
      }

      $canedit = $this->can($ID,'w');

      if ($canedit) {
         Html::initEditorSystem('text');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'><td>".$LANG['common'][57]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      if ($canedit) {
         Html::autocompletionTextField($this, "name",
                                       array('size'   => 80,
                                             'entity' => -1,
                                             'user'   => $this->fields["users_id"],
                                             'option' => $onfocus));
      } else {
         echo $this->fields['name'];
      }
      echo "</td>\n";
      echo "<td>".$LANG['common'][95]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      echo getUserName($this->fields["users_id"]);
      if (!$ID) {
      echo "<input type='hidden' name='users_id' value='".$this->fields['users_id']."'>\n";
      }
      echo "</td></tr>\n";
/*
      echo "<tr class='tab_bg_2'><td>".$LANG['common'][17]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      if ($canedit && Session::haveRight("reminder_public","w")) {
         if (!$ID) {
            if (isset($_GET["is_private"])) {
               $this->fields["is_private"] = $_GET["is_private"];
            }

            if (isset($_GET["is_recursive"])) {
               $this->fields["is_recursive"] = $_GET["is_recursive"];
            }
         }
         Dropdown::showPrivatePublicSwitch($this->fields["is_private"], $this->fields["entities_id"],
                                           $this->fields["is_recursive"]);

      } else {
         if ($this->fields["is_private"]) {
            echo $LANG['common'][77];
         } else {
            echo $LANG['common'][76];
         }
      }

      echo "</td>\n";

      if (Session::haveRight("reminder_public","w") && !$this->fields["is_private"]) {
         echo "<td>".$LANG['tracking'][39]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         if ($canedit) {
            Dropdown::showYesNo('is_helpdesk_visible', $this->fields['is_helpdesk_visible']);
         } else {
            echo Dropdpown::getYesNo($this->fields['is_helpdesk_visible']);
         }
         echo "</td>\n";

      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";
*/
      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['common'][113]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      echo '<table><tr><td>';
      echo $LANG['pager'][6].'&nbsp;:&nbsp;</td><td>';
      Html::showDateTimeFormItem("begin_view_date", $this->fields["begin_view_date"], 1, true,
                                 $canedit);
      echo '</td><td>'.$LANG['pager'][7].'&nbsp;:&nbsp;</td><td>';
      Html::showDateTimeFormItem("end_view_date", $this->fields["end_view_date"], 1, true, $canedit);
      echo '</td></tr></table>';
      echo "</td>";
      echo "<td>".$LANG['state'][0]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Planning::dropdownState("state", $this->fields["state"]);
      echo "</td>\n";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'><td >".$LANG['buttons'][15]."&nbsp;:&nbsp;</td>";
      echo "<td class='center' >";

      if ($canedit) {
         echo "<script type='text/javascript' >\n";
         echo "function showPlan() {\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'remind',
                            'users_id' => $this->fields["users_id"]);

            if ($ID && $this->fields["is_planned"]) {
               $params['begin'] = $this->fields["begin"];
               $params['end']   = $this->fields["end"];
            }

            Ajax::updateItemJsCode('viewplan', $CFG_GLPI["root_doc"]."/ajax/planning.php", $params);
         echo "}";
         echo "</script>\n";
      }

      if (!$ID || !$this->fields["is_planned"]) {
         if (Session::haveRight("show_planning","1")
             || Session::haveRight("show_group_planning","1")
             || Session::haveRight("show_all_planning","1")) {

            echo "<div id='plan' onClick='showPlan()'>\n";
            echo "<span class='showplan'>".$LANG['reminder'][12]."</span>";
         }

      } else {
         if ($canedit) {
            echo "<div id='plan' onClick='showPlan()'>\n";
            echo "<span class='showplan'>";
         }

         echo Html::convDateTime($this->fields["begin"])."->".Html::convDateTime($this->fields["end"]);

         if ($canedit) {
            echo "</span>";
         }
      }

      if ($canedit) {
         echo "</div>\n";
         echo "<div id='viewplan'>\n";
         echo "</div>\n";
      }
      echo "</td><td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_2'><td>".Toolbox::ucfirst($LANG['mailing'][117])."&nbsp;:&nbsp;</td>".
           "<td colspan='3'>";

      if ($canedit) {
         echo "<textarea cols='115' rows='15' name='text'>".$this->fields["text"]."</textarea>";
      } else {
         echo "<div  id='kbanswer'>";
         echo Toolbox::unclean_html_cross_side_scripting_deep($this->fields["text"]);
         echo "</div>";
      }

      echo "</td></tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Populate the planning with planned reminder
    *
    * @param $options options array must contains :
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *
    * @return array of planning item
   **/
   static function populatePlanning($options=array()) {
      global $DB, $CFG_GLPI;

      $interv  = array();

      if (!isset($options['begin']) || ($options['begin'] == 'NULL')
          || !isset($options['end']) || ($options['end'] == 'NULL')) {
         return $interv;
      }

      $who       = $options['who'];
      $who_group = $options['who_group'];
      $begin     = $options['begin'];
      $end       = $options['end'];
      $readpub = $readpriv="";

      $joinstoadd = self::addVisibilityJoins(true);

      // See public reminder ?
      if ($who===Session::getLoginUserID() && Session::haveRight("reminder_public","r")) {
         $readpub    = self::addVisibilityRestrict();
      }

      // See my private reminder ?
      if ($who_group==="mine" || $who===Session::getLoginUserID()) {
         $readpriv = "(`glpi_reminders`.`users_id` = '".Session::getLoginUserID()."')";
      } else {
         if ($who > 0) {
            $readpriv = "`glpi_reminders`.`users_id` = '$who'";
         }
         if ($who_group > 0) {
            if (!empty($readpriv)) {
               $readpriv .= " OR ";
            }
            $readpriv .= " `glpi_groups_reminders`.`groups_id` = '$who_group'";
         }
         if (!empty($readpriv)) {
            $readpriv = '('.$readpriv.')';
         }
      }

      $ASSIGN = '';
      if (!empty($readpub) && !empty($readpriv)) {
         $ASSIGN = "($readpub OR $readpriv)";
      } else if ($readpub) {
         $ASSIGN = $readpub;
      } else {
         $ASSIGN  = $readpriv;
      }

      if ($ASSIGN) {
         $query2 = "SELECT DISTINCT `glpi_reminders`.*
                    FROM `glpi_reminders`
                    $joinstoadd
                    WHERE `glpi_reminders`.`is_planned` = '1'
                          AND $ASSIGN
                          AND `begin` < '$end'
                          AND `end` > '$begin'
                    ORDER BY `begin`";

         $result2 = $DB->query($query2);

         if ($DB->numrows($result2)>0) {
            for ($i=0 ; $data=$DB->fetch_array($result2) ; $i++) {
               $interv[$data["begin"]."$$".$i]["reminders_id"] = $data["id"];
               $interv[$data["begin"]."$$".$i]["id"]           = $data["id"];

               if (strcmp($begin,$data["begin"])>0) {
                  $interv[$data["begin"]."$$".$i]["begin"] = $begin;
               } else {
                  $interv[$data["begin"]."$$".$i]["begin"] = $data["begin"];
               }

               if (strcmp($end,$data["end"])<0) {
                  $interv[$data["begin"]."$$".$i]["end"] = $end;
               } else {
                  $interv[$data["begin"]."$$".$i]["end"] = $data["end"];
               }
               $interv[$data["begin"]."$$".$i]["name"]
                  = Html::resume_text($data["name"], $CFG_GLPI["cut"]);
               $interv[$data["begin"]."$$".$i]["text"]
                  = Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($data["text"])),
                                                  $CFG_GLPI["cut"]);

               $interv[$data["begin"]."$$".$i]["users_id"]   = $data["users_id"];
               $interv[$data["begin"]."$$".$i]["state"]      = $data["state"];
            }
         }
      }
      return $interv;
   }


   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    *
    * @return Already planned information
    **/
   static function getAlreadyPlannedInformation($val) {
      global $CFG_GLPI;
      $out  = self::getTypeName().' : '.Html::convDateTime($val["begin"]).' -> '.
              Html::convDateTime($val["end"]).' : ';
      $out .= "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".
               $val["reminders_id"]."'>";
      $out .= Html::resume_text($val["name"],80).'</a>';
      return $out;
   }


   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
    **/
   static function displayPlanningItem($val, $who, $type="", $complete=0) {
      global $CFG_GLPI, $LANG;

      $rand     = mt_rand();
      $users_id = "";  // show users_id reminder
      $img      = "rdv_private.png"; // default icon for reminder

      if ($val["users_id"] != Session::getLoginUserID()) {
         $users_id = "<br>".$LANG['common'][95]."&nbsp;: ".getUserName($val["users_id"]);
         $img      = "rdv_public.png";
      }

      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/".$img."' alt='' title=\"".$LANG['title'][37].
             "\">&nbsp;";
      echo "<a id='reminder_".$val["reminders_id"].$rand."' href='".
             $CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$val["reminders_id"]."'>";

      switch ($type) {
         case "in" :
            echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": ";
            break;

         case "through" :
            break;

         case "begin" :
            echo $LANG['buttons'][33]." ".date("H:i",strtotime($val["begin"])).": ";
            break;

         case "end" :
            echo $LANG['buttons'][32]." ".date("H:i",strtotime($val["end"])).": ";
            break;
      }

      echo Html::resume_text($val["name"],80);
      echo $users_id;
      echo "</a>";

      if ($complete) {
         echo "<br><span class='b'>".Planning::getState($val["state"])."</span><br>";
         echo $val["text"];

      } else {
         Html::showToolTip("<span class='b'>".Planning::getState($val["state"])."</span><br>
                              ".$val["text"],
                           array('applyto' => "reminder_".$val["reminders_id"].$rand));
      }
      echo "";
   }


   /**
    * Show list for central view
    *
    * @param $personal boolean : display reminders created by me ?
    *
    * @return Nothing (display function)
    **/
   static function showListForCentral($personal = true) {
      global $DB, $CFG_GLPI, $LANG;

      $reminder = new self();

      $users_id = Session::getLoginUserID();
      $today    = date('Y-m-d');
      $now      = date('Y-m-d H:i:s');

      $restrict_visibility = " AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      if ($personal) {


         /// Personal notes only for central view
         if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
            return false;
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders`
                   WHERE `glpi_reminders`.`users_id` = '$users_id'
                         AND (`glpi_reminders`.`end` >= '$today'
                              OR `glpi_reminders`.`is_planned` = '0')
                         $restrict_visibility
                   ORDER BY `glpi_reminders`.`name`";

         $titre = "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.php'>".$LANG['reminder'][0]."</a>";

      } else {
         // Show public reminders / not mines : need to have access to public reminders
         if (!Session::haveRight('reminder_public', 'r')) {
            return false;
         }

         $restrict_user = '1';
         // Only personal on central so do not keep it
         if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $restrict_user = "`glpi_reminders`.`users_id` <> '$users_id'";
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders`
                   ".self::addVisibilityJoins()."
                   WHERE $restrict_user
                         $restrict_visibility
                        AND ".self::addVisibilityRestrict()."
                        ORDER BY `glpi_reminders`.`name`";

//          echo $query;
         if ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk') {
            $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG['reminder'][1].
                     "</a>";
         } else {
            $titre = $LANG['reminder'][1];
         }
      }

      $result = $DB->query($query);
      $nb = $DB->numrows($result);

      echo "<br><table class='tab_cadrehov'>";
      echo "<tr><th><div class='relative'><span>$titre</span>";

      if ($reminder->canCreate()) {
         echo "<span class='reminder_right'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/plus.png' alt='+' title=\"".
               $LANG['buttons'][8]."\"></a></span>";
      }

      echo "</div></th></tr>\n";

      if ($nb) {

         $rand = mt_rand();

         while ($data =$DB->fetch_array($result)) {
            echo "<tr class='tab_bg_2'><td><div class='relative reminder_list'>";
            echo "<a id='content_reminder_".$data["id"].$rand."'
                  href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$data["id"]."'>".
                  $data["name"]."</a>&nbsp;";

            Html::showToolTip(Toolbox::unclean_html_cross_side_scripting_deep($data["text"]),
                              array('applyto' => "content_reminder_".$data["id"].$rand));

            if ($data["is_planned"]) {
               $tab      = explode(" ",$data["begin"]);
               $date_url = $tab[0];
               echo "<span class='reminder_right'>";
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url.
                     "&amp;type=day'>";
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv.png' alt=\"".
                     Toolbox::ucfirst($LANG['log'][16]).
                     "\" title=\"".Html::convDateTime($data["begin"])."=>".
                     Html::convDateTime($data["end"])."\">";
               echo "</a></span>";
            }

            echo "</div></td></tr>\n";
         }

      }
      echo "</table>\n";

   }


//    static function showList($is_private=1, $is_recursive=0) {
//       global $DB, $CFG_GLPI, $LANG;
//
//       // show reminder that are not planned
//       $planningRight = Session::haveRight("show_planning", "1");
//       $users_id      = Session::getLoginUserID();
//
//       $is_helpdesk_visible = '';
//       if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
//           $is_helpdesk_visible = "AND `is_helpdesk_visible` = 1";
//       }
//       // Here do not restrict on visibility. Can view all reminders
//
//       if (!$is_private && $is_recursive) { // show public reminder
//          $query = "SELECT *
//                    FROM `glpi_reminders`
//                    WHERE `is_private` = '0'
//                          AND `is_recursive` = '1'
//                          $is_helpdesk_visible ".
//                          getEntitiesRestrictRequest("AND", "glpi_reminders", "", "", true);
//
//          $titre = $LANG['reminder'][16];
//
//       } else if (!$is_private && !$is_recursive) { // show public reminder
//          $query = "SELECT *
//                    FROM `glpi_reminders`
//                    WHERE `is_private` = '0'
//                          AND `is_recursive` = '0'
//                          $is_helpdesk_visible ".
//                          getEntitiesRestrictRequest("AND", "glpi_reminders");
//
//          $titre = $LANG['reminder'][1];
//
//       } else { // show private reminder
//          $query = "SELECT *
//                    FROM `glpi_reminders`
//                    WHERE `users_id` = '$users_id'
//                          AND `is_private` = '1'
//                          $is_helpdesk_visible";
//
//          $titre = $LANG['reminder'][0];
//       }
//
//       $result = $DB->query($query);
//
//       $tabremind = array();
//       $remind    = new Reminder();
//
//       if ($DB->numrows($result)>0) {
//          for ($i=0 ; $data=$DB->fetch_array($result) ; $i++) {
//             $remind->getFromDB($data["id"]);
//
//             if ($data["is_planned"]) { //Un rdv on va trier sur la date begin
//                $sort = $data["begin"];
//             } else { // non programmé on va trier sur la date de modif...
//                $sort = $data["date"];
//             }
//
//             $tabremind[$sort."$$".$i]["reminders_id"]
//                = $remind->fields["id"];
//             $tabremind[$sort."$$".$i]["users_id"]
//                = $remind->fields["users_id"];
//             $tabremind[$sort."$$".$i]["entity"]
//                = $remind->fields["entities_id"];
//             $tabremind[$sort."$$".$i]["begin"]
//                = ($data["is_planned"]?"".$data["begin"]."":"".$data["date"]."");
//             $tabremind[$sort."$$".$i]["end"]
//                = ($data["is_planned"]?"".$data["end"]."":"");
//             $tabremind[$sort."$$".$i]["name"]
//                = Html::resume_text($remind->fields["name"], $CFG_GLPI["cut"]);
//
//             $tabremind[$sort."$$".$i]["text"]
//                = Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($remind->fields["name"])),
//                                                $CFG_GLPI["cut"]);
//          }
//       }
//       ksort($tabremind);
//
//       echo "<br><table class='tab_cadre_fixehov'>";
//
//       if ($is_private) {
//          echo "<tr><th>"."$titre"."</th><th colspan='2'>".$LANG['common'][27]."</th></tr>\n";
//       } else {
//          echo "<tr><th colspan='5'>"."$titre"."</th></tr>\n";
//          echo "<tr><th>".$LANG['entity'][0]."</th>";
//          echo "<th>".$LANG['common'][37]."</th>";
//          echo "<th>".$LANG['title'][37]."</th>";
//          echo "<th colspan='2'>".$LANG['common'][27]."</th></tr>\n";
//       }
//
//       if (count($tabremind)>0) {
//          foreach ($tabremind as $key => $val) {
//             echo "<tr class='tab_bg_2'>";
//
//             if (!$is_private) {
//                // preg to split line (if needed) before ">" sign in completename
//                echo "<td>" .preg_replace("/ ([[:alnum:]])/", "&nbsp;\\1",
//                                          Dropdown::getDropdownName("glpi_entities",
//                                                                    $val["entity"])). "</td>";
//                echo "<td>" .Dropdown::getDropdownName("glpi_users", $val["users_id"]) . "</td>";
//             }
//
//             echo "<td width='60%' class='left'>";
//             echo "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".
//                   $val["reminders_id"]."'>".$val["name"]."</a>";
//             echo "<div class='kb_resume'>";
//             echo $val['text'];
//             echo "</div></td>";
//
//             if ($val["end"]!="") {
//                echo "<td class='center'>";
//                $tab      = explode(" ",$val["begin"]);
//                $date_url = $tab[0];
//
//                if ($planningRight) {
//                   echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url.
//                         "&amp;type=day'>";
//                }
//
//                echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv.png' alt=\"".
//                      Toolbox::ucfirst($LANG['log'][16]).
//                      "\" title=\"".Toolbox::ucfirst($LANG['log'][16])."\">";
//
//                if ($planningRight) {
//                   echo "</a>";
//                }
//
//                echo "</td>";
//                echo "<td class='center' >".Html::convDateTime($val["begin"]);
//                echo "<br>".Html::convDateTime($val["end"])."";
//
//             } else {
//                echo "<td>&nbsp;</td>";
//                echo "<td class='center'>";
//                echo "<span style='color:#aaaaaa;'>".Html::convDateTime($val["begin"])."</span>";
//             }
//             echo "</td></tr>\n";
//          }
//       }
//       echo "</table>\n";
//   }


   /**
    * Show visibility config for a reminder
    *
   **/
   function showVisibility() {
      global $DB, $CFG_GLPI, $LANG;

      $ID      = $this->fields['id'];
      $canedit = $this->can($ID,'w');

      echo "<div class='center'>";

      $rand = mt_rand();

      if ($canedit) {
         echo "<form name='remindervisibility_form$rand' id='remindervisibility_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('Reminder')."'>";
         echo "<input type='hidden' name='reminders_id' value='$ID'>";
      }

      if (Session::haveRight('reminder_public', 'w')) {
         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['common'][116]."</tr>";
         echo "<tr class='tab_bg_2'><td width='100px'>";

         $types = array( 'Group', 'Profile', 'User', 'Entity');

         $addrand = Dropdown::dropdownTypes('_type', '', $types);
         $params  = array('type'  => '__VALUE__',
                          'right' => 'reminder_public');

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand,"visibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/visibility.php",
                                       $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table></div>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      if ($canedit) {
         echo "<th>&nbsp;</th>";
      }
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['mailing'][121]."</th>";
      echo "</tr>";

      // Users
      if (count($this->users)) {
         foreach ($this->users as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='user[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['common'][34]."</td>";
               echo "<td>".getUserName($data['users_id'])."</td>";
               echo "</tr>";
            }
         }
      }

      // Groups
      if (count($this->groups)) {
         foreach ($this->groups as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='group[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['common'][35]."</td>";
               echo "<td>";
               $names = Dropdown::getDropdownName('glpi_groups', $data['groups_id'],1);
               echo $names["name"]." ";
               echo Html::showToolTip($names["comment"]);
               if ($data['entities_id'] >= 0) {
                  echo " / ";
                  echo Dropdown::getDropdownName('glpi_entities',$data['entities_id']);
                  if ($data['is_recursive']) {
                     echo " <span class='b'>&nbsp;(R)</span>";
                  }
               }
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      // Entity
      if (count($this->entities)) {
         foreach ($this->entities as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='entity[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['entity'][0]."</td>";
               echo "<td>";
               $names = Dropdown::getDropdownName('glpi_entities', $data['entities_id'],1);
               echo $names["name"]." ";
               echo Html::showToolTip($names["comment"]);
               if ($data['is_recursive']) {
                  echo " <span class='b'>&nbsp;(R)</span>";
               }
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      // Profiles
      if (count($this->profiles)) {
         foreach ($this->profiles as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='profile[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['profiles'][22]."</td>";
               echo "<td>";
               $names = Dropdown::getDropdownName('glpi_profiles',$data['profiles_id'],1);
               echo $names["name"]." ";
               echo Html::showToolTip($names["comment"]);
               if ($data['entities_id'] >= 0) {
                  echo " / ";
                  echo Dropdown::getDropdownName('glpi_entities',$data['entities_id']);
                  if ($data['is_recursive']) {
                     echo "<span class='b'>&nbsp;(R)</span>";
                  }
               }
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td colspan='3'>";
         echo "</td></tr>";
      }
      echo "</table>";
      if ($canedit) {
         Html::openArrowMassives("remindervisibility_form$rand", true);
         $confirm= array();
         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $confirm = array('deletevisibility' => $LANG['common'][120]);
         }
         Html::closeArrowMassives(array('deletevisibility' => $LANG['buttons'][6]), $confirm);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }

}
?>