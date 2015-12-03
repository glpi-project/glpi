<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}


/**
 * Reminder Class
**/
class Reminder extends CommonDBTM {

   // For visibility checks
   protected $users     = array();
   protected $groups    = array();
   protected $profiles  = array();
   protected $entities  = array();

   static $rightname    = 'reminder_public';



   static function getTypeName($nb=0) {

      if (Session::haveRight('reminder_public',READ)) {
         return _n('Reminder', 'Reminders', $nb);
      }
      return _n('Personal reminder', 'Personal reminders', $nb);
   }


   static function canCreate() {

      return (Session::haveRight(self::$rightname, CREATE)
              || ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'));
   }


   static function canView() {

      return (Session::haveRight(self::$rightname, READ)
              || ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'));
   }


   function canViewItem() {

      // Is my reminder or is in visibility
      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (Session::haveRight(self::$rightname, READ)
                  && $this->haveVisibilityAccess()));
   }


   function canCreateItem() {
      // Is my reminder
      return ($this->fields['users_id'] == Session::getLoginUserID());
   }


   function canUpdateItem() {

      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (Session::haveRight(self::$rightname, UPDATE)
                  && $this->haveVisibilityAccess()));
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::canPurgeItem()
   **/
   function canPurgeItem() {

      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (Session::haveRight(self::$rightname, PURGE)
                  && $this->haveVisibilityAccess()));
   }


   /**
    * @since 0.85
    * for personnal reminder
   **/
   static function canUpdate() {
      return ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk');
   }


   /**
    * @since 0.85
    * for personnal reminder
   **/
   static function canPurge() {
      return ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk');
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


   /**
    * @see CommonDBTM::cleanDBonPurge()
    *
    * @since version 0.83.1
   **/
   function cleanDBonPurge() {
      global $DB;

      $class = new Reminder_User();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Entity_Reminder();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Group_Reminder();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Profile_Reminder();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new PlanningRecall();
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
      if (!self::canView()) {
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
          && isset($_SESSION["glpiactiveprofile"])
          && isset($_SESSION["glpiactiveprofile"]['id'])) {
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
    * @param $forceall force all joins (false by default)
    *
    * @return string joins to add
   **/
   static function addVisibilityJoins($forceall=false) {

      if (!Session::haveRight(self::$rightname, READ)) {
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
          || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))) {
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

      if (!Session::haveRight(self::$rightname, READ)) {
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
      if (isset($this->input['_planningrecall'])) {
         $this->input['_planningrecall']['items_id'] = $this->fields['id'];
         PlanningRecall::manageDatas($this->input['_planningrecall']);
      }

   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {

      if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {
         Planning::checkAlreadyPlanned($this->fields["users_id"], $this->fields["begin"],
                                       $this->fields["end"],
                                       array('Reminder' => array($this->fields['id'])));
      }
      if (in_array("begin",$this->updates)) {
         PlanningRecall::managePlanningUpdates($this->getType(), $this->getID(),
                                               $this->fields["begin"]);
      }

   }


   function getSearchOptions() {

      $tab                     = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Title');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['massiveaction'] = false;
      $tab[1]['forcegroupby']  = true;

      $tab[2]['table']         = 'glpi_users';
      $tab[2]['field']         = 'name';
      $tab[2]['name']          = __('Writer');
      $tab[2]['datatype']      = 'dropdown';
      $tab[2]['massiveaction'] = false;
      $tab[2]['right']           = 'all';

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'state';
      $tab[3]['name']          = __('Status');
      $tab[3]['datatype']      = 'specific';
      $tab[3]['massiveaction'] = false;
      $tab[3]['searchtype']    = array('equals', 'notequals');

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'text';
      $tab[4]['name']          = __('Description');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'text';
      $tab[4]['htmltext']      = true;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'begin_view_date';
      $tab[5]['name']          = __('Visibility start date');
      $tab[5]['datatype']      = 'datetime';

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'end_view_date';
      $tab[6]['name']          = __('Visibility end date');
      $tab[6]['datatype']      = 'datetime';

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'is_planned';
      $tab[7]['name']          = __('Planning');
      $tab[7]['datatype']      = 'bool';
      $tab[7]['massiveaction'] = false;

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'begin';
      $tab[8]['name']          = __('Planning start date');
      $tab[8]['datatype']      = 'datetime';

      $tab[9]['table']         = $this->getTable();
      $tab[9]['field']         = 'end';
      $tab[9]['name']          = __('Planning end date');
      $tab[9]['datatype']      = 'datetime';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = __('Last update');
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'state':
            return Planning::getState($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
    **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'state' :
            return Planning::dropdownState($name, $values[$field], false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (self::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Reminder' :
               if ($item->canUpdate()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = $item->countVisibilities();
                  }
                  return array(1 => self::createTabEntry(_n('Target','Targets',
                                                            Session::getPluralNumber()), $nb));
               }
         }
      }
      return '';
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Reminder', $ong, $options);

      return $ong;
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Reminder' :
            $item->showVisibility();
            return true;
      }
      return false;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      $input["name"] = trim($input["name"]);

      if (empty($input["name"])) {
         $input["name"] = __('Without title');
      }

      $input["begin"] = $input["end"] = "NULL";


      if (isset($input['plan'])) {
         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && ($input['plan']["begin"] < $input['plan']["end"])) {

            $input['_plan']      = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else {
            Session::addMessageAfterRedirect(
                     __('Error in entering dates. The starting date is later than the ending date'),
                                             false, ERROR);
         }
      }

      // set new date.
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input['_planningrecall'])) {
         PlanningRecall::manageDatas($input['_planningrecall']);
      }

      if (isset($input["name"])) {
         $input["name"] = trim($input["name"]);

         if (empty($input["name"])) {
            $input["name"] = __('Without title');
         }
      }

      if (isset($input['plan'])) {

         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && ($input['plan']["begin"] < $input['plan']["end"])) {

            $input['_plan']      = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else {
            Session::addMessageAfterRedirect(
                     __('Error in entering dates. The starting date is later than the ending date'),
                                             false, ERROR);
         }
      }

      return $input;
   }


   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if (($this->fields['users_id'] == 0)
          && ($uid = Session::getLoginUserID())) {
         $this->fields['users_id'] = $uid;
         $this->updates[]          ="users_id";
      }
   }


   function post_getEmpty() {

      $this->fields["name"]        = __('New note');
      $this->fields["users_id"]    = Session::getLoginUserID();
   }


   /**
    * Print the reminder form
    *
    * @param $ID        integer  Id of the item to print
    * @param $options   array of possible options:
    *     - target filename : where to go when done.
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;


      $this->initForm($ID, $options);

      // Show Reminder or blank form
      $onfocus = "";
      if (!$ID > 0) {
         // Create item : do getempty before check right to set default values
         $onfocus="onfocus=\"if (this.value=='".$this->fields['name']."') this.value='';\"";
      }

      $canedit = $this->can($ID, UPDATE);

      if ($canedit) {
         Html::initEditorSystem('text');
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'><td>".__('Title')."</td>";
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
      echo "<td>".__('By')."</td>";
      echo "<td>";
      echo getUserName($this->fields["users_id"]);
      if (!$ID) {
      echo "<input type='hidden' name='users_id' value='".$this->fields['users_id']."'>\n";
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Visibility')."</td>";
      echo "<td>";
      echo '<table><tr><td>';
      echo __('Begin').'</td><td>';
      Html::showDateTimeField("begin_view_date",
                              array('value'      => $this->fields["begin_view_date"],
                                    'timestep'   => 1,
                                    'maybeempty' => true,
                                    'canedit'    => $canedit));
      echo '</td><td>'.__('End').'</td><td>';
      Html::showDateTimeField("end_view_date",
                              array('value'      => $this->fields["end_view_date"],
                                    'timestep'   => 1,
                                    'maybeempty' => true,
                                    'canedit'    => $canedit));
      echo '</td></tr></table>';
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      if ($canedit) {
         Planning::dropdownState("state", $this->fields["state"]);
      } else {
         echo Planning::getState($this->fields["state"]);
      }
      echo "</td>\n";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'><td >".__('Calendar')."</td>";
      echo "<td class='center'>";

      if ($canedit) {
         echo "<script type='text/javascript' >\n";
         echo "function showPlan() {\n";
         echo Html::jsHide('plan');
            $params = array('form'     => 'remind',
                            'users_id' => $this->fields["users_id"],
                            'itemtype' => $this->getType(),
                            'items_id' => $this->getID());

            if ($ID
                && $this->fields["is_planned"]) {
               $params['begin'] = $this->fields["begin"];
               $params['end']   = $this->fields["end"];
            }

            Ajax::updateItemJsCode('viewplan', $CFG_GLPI["root_doc"]."/ajax/planning.php", $params);
         echo "}";
         echo "</script>\n";
      }

      if (!$ID
          || !$this->fields["is_planned"]) {

         if (Session::haveRightsOr("planning", array(Planning::READMY, Planning::READGROUP,
                                                     Planning::READALL))) {

            echo "<div id='plan' onClick='showPlan()'>\n";
            echo "<a href='#' class='vsubmit'>".__('Add to schedule')."</a>";
         }

      } else {
         if ($canedit) {
            echo "<div id='plan' onClick='showPlan()'>\n";
            echo "<span class='showplan'>";
         }

         //TRANS: %1$s is the begin date, %2$s is the end date
         printf(__('From %1$s to %2$s'), Html::convDateTime($this->fields["begin"]),
                Html::convDateTime($this->fields["end"]));

         if ($canedit) {
            echo "</span>";
         }
      }

      if ($canedit) {
         echo "</div>\n";
         echo "<div id='viewplan'>\n</div>\n";
      }
      echo "</td>";

      if ($ID
          && $this->fields["is_planned"]
          && PlanningRecall::isAvailable()) {
         echo "<td>"._x('Planning','Reminder')."</td>";
         echo "<td>";
         if ($canedit) {
            PlanningRecall::dropdown(array('itemtype' => 'Reminder',
                                           'items_id' => $ID));
         } else { // No edit right : use specific Planning Recall Form
            PlanningRecall::specificForm(array('itemtype' => 'Reminder',
                                               'items_id' => $ID));
         }
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'><td>".__('Description')."</td>".
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

      return true;
   }


   /**
    * Populate the planning with planned reminder
    *
    * @param $options   array of possible options:
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

      $who        = $options['who'];
      $who_group  = $options['who_group'];
      $begin      = $options['begin'];
      $end        = $options['end'];

      $readpub    = $readpriv = "";

      $joinstoadd = self::addVisibilityJoins(true);

      // See public reminder ?
      if (($who === Session::getLoginUserID())
          && self::canView()) {
         $readpub    = self::addVisibilityRestrict();
      }

      // See my private reminder ?
      if (($who_group === "mine") || ($who === Session::getLoginUserID())) {
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
      if (!empty($readpub)
          && !empty($readpriv)) {
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

         if ($DB->numrows($result2) > 0) {
            for ($i=0 ; $data=$DB->fetch_assoc($result2) ; $i++) {
               $key                          = $data["begin"]."$$".$i;
               $interv[$key]["itemtype"]     = 'Reminder';
               $interv[$key]["reminders_id"] = $data["id"];
               $interv[$key]["id"]           = $data["id"];

               if (strcmp($begin,$data["begin"]) > 0) {
                  $interv[$key]["begin"] = $begin;
               } else {
                  $interv[$key]["begin"] = $data["begin"];
               }

               if (strcmp($end,$data["end"]) < 0) {
                  $interv[$key]["end"] = $end;
               } else {
                  $interv[$key]["end"] = $data["end"];
               }
               $interv[$key]["name"] = Html::resume_text($data["name"], $CFG_GLPI["cut"]);
               $interv[$key]["text"]
                  = Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($data["text"])),
                                      $CFG_GLPI["cut"]);

               $interv[$key]["users_id"]   = $data["users_id"];
               $interv[$key]["state"]      = $data["state"];
               $interv[$key]["state"]      = $data["state"];
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
   static function getAlreadyPlannedInformation(array $val) {
      global $CFG_GLPI;

      //TRANS: %1$s is the begin date, %2$s is the end date
      $beginend = sprintf(__('From %1$s to %2$s'),
                          Html::convDateTime($val["begin"]), Html::convDateTime($val["end"]));
      $out      = sprintf(__('%1$s: %2$s'), $beginend,
                          "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".
                            $val["reminders_id"]."'>".Html::resume_text($val["name"],80)."</a>");
      return $out;
   }


   /**
    * Display a Planning Item
    *
    * @param $val       array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param $type            position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param $complete        complete display (more details) (default 0)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem(array $val, $who, $type="", $complete=0) {
      global $CFG_GLPI;

      $rand     = mt_rand();
      $users_id = "";  // show users_id reminder
      $img      = "rdv_private.png"; // default icon for reminder

      if ($val["users_id"] != Session::getLoginUserID()) {
         $users_id = "<br>".sprintf(__('%1$s: %2$s'), __('By'), getUserName($val["users_id"]));
         $img      = "rdv_public.png";
      }

      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/".$img."' alt='' title=\"".
             self::getTypeName(1)."\">&nbsp;";
      echo "<a id='reminder_".$val["reminders_id"].$rand."' href='".
             $CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$val["reminders_id"]."'>";

      switch ($type) {
         case "in" :
            //TRANS: %1$s is the start time of a planned item, %2$s is the end
            $beginend = sprintf(__('From %1$s to %2$s'), date("H:i",strtotime($val["begin"])),
                                date("H:i",strtotime($val["end"])));
            printf(__('%1$s: %2$s'), $beginend, Html::resume_text($val["name"],80)) ;

            break;

         case "through" :
            echo Html::resume_text($val["name"],80);
            break;

         case "begin" :
            $start = sprintf(__('Start at %s'), date("H:i", strtotime($val["begin"])));
            printf(__('%1$s: %2$s'), $start, Html::resume_text($val["name"],80)) ;
            break;

         case "end" :
            $end = sprintf(__('End at %s'), date("H:i", strtotime($val["end"])));
            printf(__('%1$s: %2$s'), $end,  Html::resume_text($val["name"],80)) ;
            break;
      }

      echo $users_id;
      echo "</a>";
      $recall = '';
      if (isset($val['reminders_id'])) {
         $pr = new PlanningRecall();
         if ($pr->getFromDBForItemAndUser($val['itemtype'], $val['reminders_id'],
                                          Session::getLoginUserID())) {
            $recall = "<br><span class='b'>".sprintf(__('Recall on %s'),
                                                     Html::convDateTime($pr->fields['when'])).
                      "<span>";
         }
      }


      if ($complete) {
         echo "<br><span class='b'>".Planning::getState($val["state"])."</span><br>";
         echo $val["text"].$recall;
      } else {
         Html::showToolTip("<span class='b'>".Planning::getState($val["state"])."</span><br>
                              ".$val["text"].$recall,
                           array('applyto' => "reminder_".$val["reminders_id"].$rand));
      }
      echo "";
   }


   /**
    * Show list for central view
    *
    * @param $personal boolean : display reminders created by me ? (true by default)
    *
    * @return Nothing (display function)
    **/
   static function showListForCentral($personal=true) {
      global $DB, $CFG_GLPI;

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

         $titre = "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.php'>".
                    _n('Personal reminder', 'Personal reminders', Session::getPluralNumber())."</a>";

      } else {
         // Show public reminders / not mines : need to have access to public reminders
         if (!self::canView()) {
            return false;
         }

         $restrict_user = '1';
         // Only personal on central so do not keep it
         if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $restrict_user = "`glpi_reminders`.`users_id` <> '$users_id'";
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders` ".
                   self::addVisibilityJoins()."
                   WHERE $restrict_user
                         $restrict_visibility
                         AND ".self::addVisibilityRestrict()."
                   ORDER BY `glpi_reminders`.`name`";

         if ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk') {
            $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".
                       _n('Public reminder', 'Public reminders', Session::getPluralNumber())."</a>";
         } else {
            $titre = _n('Public reminder', 'Public reminders', Session::getPluralNumber());
         }
      }

      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      echo "<br><table class='tab_cadrehov'>";
      echo "<tr class='noHover'><th><div class='relative'><span>$titre</span>";

      if (($personal && self::canCreate())
        || (!$personal && Session::haveRight(self::$rightname, CREATE))) {
         echo "<span class='floatright'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/plus.png' alt='".__s('Add')."'
                title=\"". __s('Add')."\"></a></span>";
      }

      echo "</div></th></tr>\n";

      if ($nb) {
         $rand = mt_rand();

         while ($data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_2'><td>";
            $link = "<a id='content_reminder_".$data["id"].$rand."'
                      href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$data["id"]."'>".
                      $data["name"]."</a>";

            $tooltip = Html::showToolTip(Toolbox::unclean_html_cross_side_scripting_deep($data["text"]),
                                         array('applyto' => "content_reminder_".$data["id"].$rand,
                                               'display' => false));
            printf(__('%1$s %2$s'), $link, $tooltip);

            if ($data["is_planned"]) {
               $tab      = explode(" ",$data["begin"]);
               $date_url = $tab[0];
               echo "<span class='floatright'>";
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.php?date=".$date_url.
                     "&amp;type=day'>";
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv.png' alt=\"". __s('Planning').
                     "\" title=\"".sprintf(__s('From %1$s to %2$s'),
                                           Html::convDateTime($data["begin"]),
                                           Html::convDateTime($data["end"]))."\">";
               echo "</a></span>";
            }

            echo "</td></tr>\n";
         }

      }
      echo "</table>\n";

   }


   /**
    * Show visibility config for a reminder
   **/
   function showVisibility() {
      global $DB, $CFG_GLPI;

      $ID      = $this->fields['id'];
      $canedit = $this->canEdit($ID);

      $rand = mt_rand();

      $nb = count($this->users) + count($this->groups) + count($this->profiles)
            + count($this->entities);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='remindervisibility_form$rand' id='remindervisibility_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('Reminder')."'>";
         echo "<input type='hidden' name='reminders_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Add a target')."</tr>";
         echo "<tr class='tab_bg_2'><td width='100px'>";

         $types = array('Entity', 'Group', 'Profile', 'User');

         $addrand = Dropdown::showItemTypes('_type', $types);
         $params  = array('type'  => '__VALUE__',
                          'right' => 'reminder_public');

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand,"visibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/visibility.php", $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams
            = array('num_displayed'
                        => $nb,
                    'container'
                        => 'mass'.__CLASS__.$rand,
                    'specific_actions'
                        => array('delete' => _x('button', 'Delete permanently')) );

         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $massiveactionparams['confirm']
               = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
         }
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>"._n('Recipient', 'Recipients', Session::getPluralNumber())."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      // Users
      if (count($this->users)) {
         foreach ($this->users as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('Reminder_User',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('User')."</td>";
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
                  Html::showMassiveActionCheckBox('Group_Reminder',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Group')."</td>";

               $names    = Dropdown::getDropdownName('glpi_groups', $data['groups_id'],1);
               $entname = sprintf(__('%1$s %2$s'), $names["name"],
                                   Html::showToolTip($names["comment"], array('display' => false)));
               if ($data['entities_id'] >= 0) {
                  $entname = sprintf(__('%1$s / %2$s'), $entname,
                                     Dropdown::getDropdownName('glpi_entities',
                                                               $data['entities_id']));
                  if ($data['is_recursive']) {
                     //TRANS: R for Recursive
                     sprintf(__('%1$s %2$s'), $entname,
                             "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
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
                  Html::showMassiveActionCheckBox('Entity_Reminder',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Entity')."</td>";
               $names   = Dropdown::getDropdownName('glpi_entities', $data['entities_id'],1);
               $tooltip = Html::showToolTip($names["comment"], array('display' => false));
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['is_recursive']) {
                  $entname = sprintf(__('%1$s %2$s'), $entname,
                                     "<span class='b'>(".__('R').")</span>");
               }
               echo "<td>".$entname."</td>";
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
                  Html::showMassiveActionCheckBox('Profile_Reminder',$data["id"]);
                  echo "</td>";
               }
               echo "<td>"._n('Profile', 'Profiles', 1)."</td>";

               $names   = Dropdown::getDropdownName('glpi_profiles',$data['profiles_id'],1);
               $tooltip = Html::showToolTip($names["comment"], array('display' => false));
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['entities_id'] >= 0) {
                  $entname = sprintf(__('%1$s / %2$s'), $entname,
                                     Dropdown::getDropdownName('glpi_entities',
                                                               $data['entities_id']));
                  if ($data['is_recursive']) {
                     $entname = sprintf(__('%1$s %2$s'), $entname,
                                        "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }
      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] =false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface='central') {

      if ($interface == 'helpdesk') {
         $values = array(READ => __('Read'));
      } else {
         $values = parent::getRights();
      }
      return $values;
   }

}
?>
