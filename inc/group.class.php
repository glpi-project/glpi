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
 * Group class
**/
class Group extends CommonTreeDropdown {

   public $dohistory = true;

   static $rightname = 'group';


   static function getTypeName($nb=0) {
      return _n('Group', 'Groups', $nb);
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuOptions() {

      if (Session::haveRight('user', User::UPDATEAUTHENT)) {
         $options['ldap']['title'] = AuthLDAP::getTypeName(Session::getPluralNumber());
         $options['ldap']['page']  = "/front/ldap.group.php";
         return $options;
      }
      return false;
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since version 0.85
   **/
   static function getMenuShorcut() {
      return 'g';
   }


   function post_getEmpty () {

      $this->fields['is_requester'] = 1;
      $this->fields['is_assign']    = 1;
      $this->fields['is_notify']    = 1;
      $this->fields['is_itemgroup'] = 1;
      $this->fields['is_usergroup'] = 1;
      $this->fields['is_manager']   = 1;
   }


   function cleanDBonPurge() {
      global $DB;

      $gu = new Group_User();
      $gu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gt = new Group_Ticket();
      $gt->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gp = new Group_Problem();
      $gp->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $cg = new Change_Group();
      $cg->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $query1 = "DELETE
                 FROM `glpi_projecttaskteams`
                 WHERE `items_id` = '".$this->fields['id']."'
                       AND `itemtype` = '".__CLASS__."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_projectteams`
                 WHERE `items_id` = '".$this->fields['id']."'
                       AND `itemtype` = '".__CLASS__."'";
      $DB->query($query1);

      $gki = new Group_KnowbaseItem();
      $gki->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gr = new Group_Reminder();
      $gr->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      // Ticket rules use various _groups_id_*
      Rule::cleanForItemAction($this, '_groups_id%');
      Rule::cleanForItemCriteria($this, '_groups_id%');
      // GROUPS for RuleMailcollector
      Rule::cleanForItemCriteria($this, 'GROUPS');

      // Set no group to consumables
      $query = "UPDATE `glpi_consumables`
                SET `items_id` = '0'
                WHERE `items_id` = '".$this->fields['id']."'
                      AND `itemtype` = 'Group'";
      $DB->query($query);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Group::canUpdate()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Group' :
               $ong = array();
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             "`groups_id` = '".$item->getID()."'");
               }
               $ong[4] = self::createTabEntry(__('Child groups'), $nb);

               if ($item->getField('is_itemgroup')) {
                  $ong[1] = __('Used items');
               }
               if ($item->getField('is_assign')) {
                  $ong[2] = __('Managed items');
               }
               if ($item->getField('is_usergroup')
                   && Group::canUpdate()
                   && Session::haveRight("user", User::UPDATEAUTHENT)
                   && AuthLdap::useAuthLdap()) {
                  $ong[3] = __('LDAP directory link');
               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Group' :
            switch ($tabnum) {
               case 1 :
                  $item->showItems(false);
                  return true;

               case 2 :
                  $item->showItems(true);
                  return true;

               case 3 :
                  $item->showLDAPForm($item->getID());
                  return true;

               case 4 :
                  $item->showChildren();
                  return true;
            }
            break;
      }
      return false;
   }


   function defineTabs($options=array()) {

      $ong = array();

      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Group', $ong, $options);
      if (isset($this->fields['is_usergroup'])
          && $this->fields['is_usergroup']) {
         $this->addStandardTab('Group_User', $ong, $options);
      }
      if (isset($this->fields['is_notify'])
          && $this->fields['is_notify']) {
         $this->addStandardTab('NotificationTarget', $ong, $options);
      }
      if (isset($this->fields['is_requester'])
          && $this->fields['is_requester']) {
         $this->addStandardTab('Ticket', $ong, $options);
      }
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);

      $this->addStandardTab('Log',$ong, $options);
      return $ong;
   }


   /**
   * Print the group form
   *
   * @param $ID      integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='9' class='middle'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='9'>";
      echo "<textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('As child of')."</td><td>";
      self::dropdown(array('value'  => $this->fields['groups_id'],
                           'name'   => 'groups_id',
                           'entity' => $this->fields['entities_id'],
                           'used'   => (($ID > 0) ? getSonsOf($this->getTable(), $ID) : array())));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='subheader' colspan='2'>".__('Visible in a ticket');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Requester')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_requester', $this->fields['is_requester']);
      echo "</td></tr>";

      echo "<tr  class='tab_bg_1'>";
      echo "<td>".__('Assigned to')."</td><td>";
      Dropdown::showYesNo('is_assign', $this->fields['is_assign']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Can be notified')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_notify', $this->fields['is_notify']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='subheader' colspan='2'>".__('Visible in a project');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Can be manager')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_manager', $this->fields['is_manager']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='subheader' colspan='2'>".__('Can contain');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Item', 'Items', Session::getPluralNumber())."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_itemgroup', $this->fields['is_itemgroup']);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('User', 'Users', Session::getPluralNumber())."</td><td>";
      Dropdown::showYesNo('is_usergroup', $this->fields['is_usergroup']);
      echo "</td>";
      echo "<td colspan='2' class='center'>";
      if (!$ID) {
         //TRANS: %s is the datetime of insertion
         printf(__('Created on %s'), Html::convDateTime($_SESSION["glpi_currenttime"]));
      } else {
         //TRANS: %s is the datetime of update
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print a good title for group pages
    *
    *@return nothing (display)
    **/
   function title() {
      global $CFG_GLPI;

      $buttons = array();
      if (Group::canUpdate()
          && Session::haveRight("user", User::UPDATEAUTHENT)
          && AuthLdap::useAuthLdap()) {

         $buttons["ldap.group.php"] = __('LDAP directory link');
         $title                     = "";

      } else {
         $title = self::getTypeName(Session::getPluralNumber());
      }

      Html::displayTitle($CFG_GLPI["root_doc"] . "/pics/groupes.png", self::getTypeName(Session::getPluralNumber()), $title,
                         $buttons);
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $prefix                            = 'Group_User'.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'add']            = _x('button', 'Add a user');
         $actions[$prefix.'add_supervisor'] = _x('button', 'Add a manager');
         $actions[$prefix.'add_delegatee']  = _x('button', 'Add a delegatee');
         $actions[$prefix.'remove']         = _x('button', 'Remove a user');
      }

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      $input = $ma->getInput();

      switch ($ma->getAction()) {
         case 'changegroup' :
            if (isset($input['is_tech'])
                && isset($input['check_items_id'])
                && isset($input['check_itemtype'])) {
               if ($group = getItemForItemtype($input['check_itemtype'])) {
                  if ($group->getFromDB($input['check_items_id'])) {
                     self::dropdown(array('entity'    => $group->fields["entities_id"],
                                          'used'      => array($group->fields["id"]),
                                          'condition' => ($input['is_tech'] ? '`is_assign`'
                                                                            : '`is_itemgroup`')));
                     echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                                    _sx('button', 'Move')."'>";
                     return true;
                  }
               }
            }
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

      switch ($ma->getAction()) {
         case 'changegroup' :
            $input = $ma->getInput();
            if (isset($input["field"])
                && isset($input['groups_id'])) {
               foreach ($ids as $id) {
                  if ($item->can($id, UPDATE)) {
                     if ($item->update(array('id'            => $id,
                                             $input["field"] => $input["groups_id"]))) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $baseitem, $ids);
   }


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      if (AuthLdap::useAuthLdap()) {
         $tab[3]['table']       = $this->getTable();
         $tab[3]['field']       = 'ldap_field';
         $tab[3]['name']        = __('Attribute of the user containing its groups');
         $tab[3]['datatype']    = 'string';

         $tab[4]['table']       = $this->getTable();
         $tab[4]['field']       = 'ldap_value';
         $tab[4]['name']        = __('Attribute value');
         $tab[4]['datatype']    = 'text';

         $tab[5]['table']       = $this->getTable();
         $tab[5]['field']       = 'ldap_group_dn';
         $tab[5]['name']        = __('Group DN');
         $tab[5]['datatype']    = 'text';
      }

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'is_requester';
      $tab[11]['name']          = __('Requester');
      $tab[11]['datatype']      = 'bool';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'is_assign';
      $tab[12]['name']          = __('Assigned to');
      $tab[12]['datatype']      = 'bool';

      $tab[18]['table']         = $this->getTable();
      $tab[18]['field']         = 'is_manager';
      $tab[18]['name']          = __('Can be manager');
      $tab[18]['datatype']      = 'bool';

      $tab[13]['table']         = $this->getTable();
      $tab[13]['field']         = 'is_notify';
      $tab[13]['name']          = __('Can be notified');
      $tab[13]['datatype']      = 'bool';

      $tab[17]['table']         = $this->getTable();
      $tab[17]['field']         = 'is_itemgroup';
      $tab[17]['name']          = sprintf(__('%1$s %2$s'), __('Can contain'), _n('Item', 'Items', Session::getPluralNumber()));
      $tab[17]['datatype']      = 'bool';

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'is_usergroup';
      $tab[15]['name']          = sprintf(__('%1$s %2$s'), __('Can contain'), User::getTypeName(Session::getPluralNumber()));
      $tab[15]['datatype']      = 'bool';

      $tab[70]['table']         = 'glpi_users';
      $tab[70]['field']         = 'name';
      $tab[70]['name']          = __('Manager');
      $tab[70]['datatype']      = 'dropdown';
      $tab[70]['right']         = 'all';
      $tab[70]['forcegroupby']  = true;
      $tab[70]['massiveaction'] = false;
      $tab[70]['joinparams']    = array('beforejoin'
                                        => array('table'      => 'glpi_groups_users',
                                                 'joinparams' => array('jointype' => 'child',
                                                 'condition'  => "AND NEWTABLE.`is_manager` = 1")));

      $tab[71]['table']         = 'glpi_users';
      $tab[71]['field']         = 'name';
      $tab[71]['name']          = __('Delegatee');
      $tab[71]['datatype']      = 'dropdown';
      $tab[71]['right']         = 'all';
      $tab[71]['forcegroupby']  = true;
      $tab[71]['massiveaction'] = false;
      $tab[71]['joinparams']    = array('beforejoin'
                                        => array('table'      => 'glpi_groups_users',
                                                 'joinparams' => array('jointype' => 'child',
                                                 'condition'  => "AND NEWTABLE.`is_userdelegate` = 1")));

      return $tab;
   }


   /**
    * @param $ID
   **/
   function showLDAPForm($ID) {
      $options = array();
      $this->initForm($ID, $options);

      echo "<form name='groupldap_form' id='groupldap_form' method='post' action='".
             $this->getFormURL()."'>";
      echo "<div class='spaced'><table class='tab_cadre_fixe'>";

      if (Group::canUpdate()
          && Session::haveRight("user", User::UPDATEAUTHENT)
          && AuthLdap::useAuthLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='2' class='center'>".__('In users')."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Attribute of the user containing its groups')."</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_field");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Attribute value')."</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_value");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='2' class='center'>".__('In groups')."</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Group DN')."</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_group_dn");
         echo "</td></tr>";
      }

      $options = array('colspan' => 1,
                       'candel'  => false);
      $this->showFormButtons($options);
   }


   /**
    * get list of Computers in a group
    *
    * @since version 0.83
    *
    * @param $types  Array    of types
    * @param $field  String   field name
    * @param $tree   Boolean  include child groups
    * @param $user   Boolean  include members (users)
    * @param $start  Integer  (first row to retrieve)
    * @param $res    Array    result filled on ouput
    *
    * @return integer total of items
   **/
   function getDataItems(array $types, $field, $tree, $user, $start, array &$res) {
      global $DB;

      // include item of child groups ?
      if ($tree) {
         $grprestrict = "IN (".implode(',', getSonsOf('glpi_groups', $this->getID())).")";
      } else {
         $grprestrict = "='".$this->getID()."'";
      }
      // include items of members
      if ($user) {
         $ufield      = str_replace('groups', 'users', $field);
         $grprestrict = "(`$field` $grprestrict
                          OR (`$field`=0
                              AND `$ufield` IN (SELECT `users_id`
                                                FROM `glpi_groups_users`
                                                WHERE `groups_id` $grprestrict)))";
      } else {
         $grprestrict = "`$field` $grprestrict";
      }
      // Count the total of item
      $nb  = array();
      $tot = 0;
      foreach ($types as $itemtype) {
         $nb[$itemtype] = 0;
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if (!$item->canView()) {
            continue;
         }
         if (!$item->isField($field)) {
            continue;
         }
         $restrict[$itemtype] = $grprestrict;

         if ($item->isEntityAssign()) {
            $restrict[$itemtype] .= getEntitiesRestrictRequest(" AND ", $item->getTable(), '', '',
                                                               $item->maybeRecursive());
         }
         if ($item->maybeTemplate()) {
            $restrict[$itemtype] .= " AND NOT `is_template`";
         }
         if ($item->maybeDeleted()) {
            $restrict[$itemtype] .= " AND NOT `is_deleted`";
         }

         $tot += $nb[$itemtype] = countElementsInTable($item->getTable(), $restrict[$itemtype]);
      }
      $max = $_SESSION['glpilist_limit'];
      if ($start >= $tot) {
         $start = 0;
      }
      $res = array();
      foreach ($types as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($start >= $nb[$itemtype]) {
            // No need to read
            $start -= $nb[$itemtype];
         } else {
            $query = "SELECT `id`
                      FROM `".$item->getTable()."`
                      WHERE ".$restrict[$itemtype]."
                      ORDER BY `name`
                      LIMIT $start,$max";
            foreach ($DB->request($query) as $data) {
               $res[] = array('itemtype' => $itemtype,
                              'items_id' => $data['id']);
               $max--;
            }
            // For next type
            $start = 0;
         }
         if (!$max) {
            break;
         }
      }
      return $tot;
   }


   /**
    * Show items for the group
    *
    * @param $tech   boolean  false search groups_id, true, search groups_id_tech
   **/
   function showItems($tech) {
      global $DB, $CFG_GLPI;

      $rand = mt_rand();

      $ID = $this->fields['id'];
      if ($tech) {
         $types = $CFG_GLPI['linkgroup_tech_types'];
         $field = 'groups_id_tech';
         $title = __('Managed items');
      } else {
         $types = $CFG_GLPI['linkgroup_types'];
         $field = 'groups_id';
         $title = __('Used items');
      }

      $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
      $user = Session::getSavedOption(__CLASS__, 'user', 0);
      $type = Session::getSavedOption(__CLASS__, 'onlytype', '');
      if (!in_array($type, $types)) {
         $type = '';
      }
      echo "<div class='spaced'>";
      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='3'>$title</tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo __('Type')."&nbsp;";
      Dropdown::showItemType($types,
                             array('value'      => $type,
                                   'name'       => 'onlytype',
                                   'plural'     => true,
                                   'on_change'  => 'reloadTab("start=0&onlytype="+this.value)',
                                   'checkright' => true));
      if ($this->haveChildren()) {
         echo "</td><td class='center'>".__('Child groups')."&nbsp;";
         Dropdown::showYesNo('tree', $tree, -1,
                             array('on_change' => 'reloadTab("start=0&tree="+this.value)'));
      } else {
         $tree = 0;
      }
      if ($this->getField('is_usergroup')) {
         echo "</td><td class='center'>".User::getTypeName(Session::getPluralNumber())."&nbsp;";
         Dropdown::showYesNo('user', $user, -1,
                             array('on_change' => 'reloadTab("start=0&user="+this.value)'));
      } else {
         $user = 0;
      }
      echo "</td></tr></table>";

      $datas = array();
      if ($type) {
         $types = array($type);
      }
      $start  = (isset($_GET['start']) ? intval($_GET['start']) : 0);
      $nb     = $this->getDataItems($types, $field, $tree, $user, $start, $datas);
      $nbcan  = 0;

      if ($nb) {
         Html::printAjaxPager('', $start, $nb);

         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         echo Html::hidden('field', array('value'                 => $field,
                                          'data-glpicore-ma-tags' => 'common'));

         $massiveactionparams = array('num_displayed'    => $nb,
                           'check_itemtype'   => 'Group',
                           'check_items_id'   => $ID,
                           'container'        => 'mass'.__CLASS__.$rand,
                           'extraparams'      => array('is_tech' => $tech,
                                                       'massive_action_fields' => array('field')),
                           'specific_actions' => array(__CLASS__.
                                                       MassiveAction::CLASS_ACTION_SEPARATOR.
                                                       'changegroup' => __('Move')) );
         Html::showMassiveActions($massiveactionparams);

         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr><th width='10'>";
         $header_top    = Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom = Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    = '</th>';

         $header_end .= "<th>".__('Type')."</th><th>".__('Name')."</th><th>".__('Entity')."</th>";
         if ($tree || $user) {
            $header_end .= "<th>".
                             sprintf(__('%1$s / %2$s'), self::getTypeName(1), User::getTypeName(1)).
                           "</th>";
         }
         $header_end .= "</tr>";
         echo $header_begin.$header_top.$header_end;

         $tuser = new User();
         $group = new Group();

         foreach ($datas as $data) {
            if (!($item = getItemForItemtype($data['itemtype']))) {
               continue;
            }
            echo "<tr class='tab_bg_1'><td>";
            if ($item->canEdit($data['items_id'])) {
               Html::showMassiveActionCheckBox($data['itemtype'], $data['items_id']);
            }
            echo "</td><td>".$item->getTypeName(1);
            echo "</td><td>".$item->getLink(array('comments' => true));
            echo "</td><td>".Dropdown::getDropdownName("glpi_entities", $item->getEntityID());
            if ($tree || $user) {
               echo "</td><td>";
               if ($grp = $item->getField($field)) {
                  if ($group->getFromDB($grp)) {
                     echo $group->getLink(array('comments' => true));
                  }

               } else if ($usr = $item->getField(str_replace('groups', 'users', $field))) {
                  if ($tuser->getFromDB($usr)) {
                     echo $tuser->getLink(array('comments' => true));
                  }
               }
            }
            echo "</td></tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }

      if ($nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
      }
      Html::closeForm();

      if ($nb) {
         Html::printAjaxPager('', $start, $nb);
      }

      echo "</div>";
   }

}
?>