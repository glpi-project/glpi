<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Group class
**/
class Group extends CommonTreeDropdown {

   public $dohistory       = true;

   static $rightname       = 'group';

   protected $usenotepad  = true;


   static function getTypeName($nb = 0) {
      return _n('Group', 'Groups', $nb);
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since 0.85
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
    * @since 0.85
   **/
   static function getMenuShorcut() {
      return 'g';
   }


   function post_getEmpty () {

      $this->fields['is_requester'] = 1;
      $this->fields['is_watcher']   = 1;
      $this->fields['is_assign']    = 1;
      $this->fields['is_notify']    = 1;
      $this->fields['is_itemgroup'] = 1;
      $this->fields['is_usergroup'] = 1;
      $this->fields['is_manager']   = 1;
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Change_Group::class,
            Group_KnowbaseItem::class,
            Group_Problem::class,
            Group_Reminder::class,
            Group_RSSFeed::class,
            Group_Ticket::class,
            Group_User::class,
            ProjectTaskTeam::class,
            ProjectTeam::class,
         ]
      );

      // Ticket rules use various _groups_id_*
      Rule::cleanForItemAction($this, '_groups_id%');
      Rule::cleanForItemCriteria($this, '_groups_id%');
      // GROUPS for RuleMailcollector
      Rule::cleanForItemCriteria($this, 'GROUPS');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate && self::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Group' :
               $ong = [];
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             ['groups_id' => $item->getID()]);
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


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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


   function defineTabs($options = []) {

      $ong = [];

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
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
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
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='10' class='middle'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='10'>";
      echo "<textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('As child of')."</td><td>";
      self::dropdown(['value'  => $this->fields['groups_id'],
                           'name'   => 'groups_id',
                           'entity' => $this->fields['entities_id'],
                           'used'   => (($ID > 0) ? getSonsOf($this->getTable(), $ID) : [])]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='subheader' colspan='2'>".__('Visible in a ticket');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Requester')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_requester', $this->fields['is_requester']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Watcher')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_watcher', $this->fields['is_watcher']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Assigned to')."</td><td>";
      Dropdown::showYesNo('is_assign', $this->fields['is_assign']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Task')."</td><td>";
      Dropdown::showYesNo('is_task', $this->fields['is_task']);
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

      $buttons = [];
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
   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $prefix                            = 'Group_User'.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'add']            = _x('button', 'Add a user');
         $actions[$prefix.'add_supervisor'] = _x('button', 'Add a manager');
         $actions[$prefix.'add_delegatee']  = _x('button', 'Add a delegatee');
         $actions[$prefix.'remove']         = _x('button', 'Remove a user');
      }

      return $actions;
   }


   /**
    * @since 0.85
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
                     $condition = [];
                     if ($input['is_tech']) {
                        $condition['is_assign'] = 1;
                     } else {
                        $condition['is_itemgroup'] = 1;
                     }
                     self::dropdown([
                        'entity'    => $group->fields["entities_id"],
                        'used'      => [$group->fields["id"]],
                        'condition' => $condition
                     ]);
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
    * @since 0.85
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
                     if ($item->update(['id'            => $id,
                                             $input["field"] => $input["groups_id"]])) {
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


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      if (AuthLdap::useAuthLdap()) {
         $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'ldap_field',
            'name'               => __('Attribute of the user containing its groups'),
            'datatype'           => 'string'
         ];

         $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'ldap_value',
            'name'               => __('Attribute value'),
            'datatype'           => 'text'
         ];

         $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'ldap_group_dn',
            'name'               => __('Group DN'),
            'datatype'           => 'text'
         ];
      }

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'is_requester',
         'name'               => __('Requester'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'is_assign',
         'name'               => __('Assigned to'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'is_manager',
         'name'               => __('Can be manager'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => $this->getTable(),
         'field'              => 'is_notify',
         'name'               => __('Can be notified'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'is_itemgroup',
         'name'               => sprintf(__('%1$s %2$s'), __('Can contain'), _n('Item', 'Items', Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'is_usergroup',
         'name'               => sprintf(__('%1$s %2$s'), __('Can contain'), User::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Manager'),
         'datatype'           => 'dropdown',
         'right'              => 'all',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_groups_users',
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => 'AND NEWTABLE.`is_manager` = 1'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '71',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Delegatee'),
         'datatype'           => 'dropdown',
         'right'              => 'all',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_groups_users',
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => 'AND NEWTABLE.`is_userdelegate` = 1'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '72',
         'table'              => $this->getTable(),
         'field'              => 'is_task',
         'name'               => __('Can be in charge of a task'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * @param $ID
   **/
   function showLDAPForm($ID) {
      $options = [];
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

      $options = ['colspan' => 1,
                       'candel'  => false];
      $this->showFormButtons($options);
   }


   /**
    * get list of Computers in a group
    *
    * @since 0.83
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
         $groups_ids = getSonsOf('glpi_groups', $this->getID());
      } else {
         $groups_ids = [$this->getID()];
      }
      // include items of members
      $groups_criteria = [];
      if ($user) {
         $ufield = str_replace('groups', 'users', $field);
         $groups_criteria['OR'] = [
            $field => $groups_ids,
            'AND'  => [
               $field  => 0,
               $ufield => new QuerySubQuery(
                  [
                     'SELECT' => 'users_id',
                     'FROM'   => 'glpi_groups_users',
                     'WHERE'  => [
                        'groups_id'  => $groups_ids,
                     ]
                  ]
               )
            ]
         ];
      } else {
         $groups_criteria[$field] = $groups_ids;
      }

      // Count the total of item
      $nb  = [];
      $tot = 0;
      $savfield = $field;
      $restrict = [];
      foreach ($types as $itemtype) {
         $nb[$itemtype] = 0;
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if (!$item->canView()) {
            continue;
         }
         if ($itemtype == 'Consumable') {
            $field = 'items_id';
         } else {
            $field = $savfield;
         }
         if (!$item->isField($field)) {
            continue;
         }
         $restrict[$itemtype] = $groups_criteria;

         if ($itemtype == 'Consumable') {
            $restrict[$itemtype] = [
               $field               => $groups_ids,
               'itemtype'           => 'Group',
               'consumableitems_id' =>  new QuerySubQuery(
                  [
                     'SELECT' => 'id',
                     'FROM'   => 'glpi_consumableitems',
                     'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems', '', '', true)
                  ]
               ),
            ];
         }

         if ($item->isEntityAssign() && $itemtype != 'Consumable') {
            $restrict[$itemtype] += getEntitiesRestrictCriteria(
               $item->getTable(),
               '',
               '',
               $item->maybeRecursive()
            );
         }
         if ($item->maybeTemplate()) {
            $restrict[$itemtype]['is_template'] = 0;
         }
         if ($item->maybeDeleted()) {
            $restrict[$itemtype]['is_deleted'] = 0;
         }
         $tot += $nb[$itemtype] = countElementsInTable($item->getTable(), $restrict[$itemtype]);
      }
      $max = $_SESSION['glpilist_limit'];
      if ($start >= $tot) {
         $start = 0;
      }
      $res = [];
      foreach ($types as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($start >= $nb[$itemtype]) {
            // No need to read
            $start -= $nb[$itemtype];
         } else {
            $request = [
               'SELECT' => 'id',
               'FROM'   => $item->getTable(),
               'WHERE'  => $restrict[$itemtype],
               'ORDER'  => 'name',
               'LIMIT'  => $max,
               'START'  => $start
            ];

            if ($itemtype == 'Consumable') {
               $request['SELECT'] = 'glpi_consumableitems.id';
               $request['LEFT JOIN'] = [
                  'glpi_consumableitems' => [
                     'FKEY'   => [
                        'glpi_consumables'     => 'consumableitems_id',
                        'glpi_consumableitems' => 'id'
                     ]
                  ]
               ];
            }

            $iterator = $DB->request($request);
            while ($data = $iterator->next()) {
               $res[] = ['itemtype' => $itemtype,
                              'items_id' => $data['id']];
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
                             ['value'      => $type,
                                   'name'       => 'onlytype',
                                   'plural'     => true,
                                   'on_change'  => 'reloadTab("start=0&onlytype="+this.value)',
                                   'checkright' => true]);
      if ($this->haveChildren()) {
         echo "</td><td class='center'>".__('Child groups')."&nbsp;";
         Dropdown::showYesNo('tree', $tree, -1,
                             ['on_change' => 'reloadTab("start=0&tree="+this.value)']);
      } else {
         $tree = 0;
      }
      if ($this->getField('is_usergroup')) {
         echo "</td><td class='center'>".User::getTypeName(Session::getPluralNumber())."&nbsp;";
         Dropdown::showYesNo('user', $user, -1,
                             ['on_change' => 'reloadTab("start=0&user="+this.value)']);
      } else {
         $user = 0;
      }
      echo "</td></tr></table>";

      $datas = [];
      if ($type) {
         $types = [$type];
      }
      $start  = (isset($_GET['start']) ? intval($_GET['start']) : 0);
      $nb     = $this->getDataItems($types, $field, $tree, $user, $start, $datas);
      $nbcan  = 0;

      if ($nb) {
         Html::printAjaxPager('', $start, $nb);
         foreach ($datas as $data) {
            if (!($item = getItemForItemtype($data['itemtype']))) {
               continue;
            }
         }
         if ($item->canUpdate($data['items_id'])
             || ($item->canView($data['items_id'])
                 && self::canUpdate())) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            echo Html::hidden('field', ['value'                 => $field,
                                             'data-glpicore-ma-tags' => 'common']);

            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $nb),
                                         'check_itemtype'   => 'Group',
                                         'check_items_id'   => $ID,
                                         'container'        => 'mass'.__CLASS__.$rand,
                                         'extraparams'      => ['is_tech' => $tech,
                                                                  'massive_action_fields' => ['field']],
                                         'specific_actions' => [__CLASS__.
                                                                    MassiveAction::CLASS_ACTION_SEPARATOR.
                                                                    'changegroup' => __('Move')] ];
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr><th width='10'>";
         if ($item->canUpdate($data['items_id'])
             || ($item->canView($data['items_id'])
                 && self::canUpdate())) {
            $header_top    = Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom = Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         } else {
            $header_top = $header_bottom = '';
         }
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
            $item->getFromDB($data['items_id']);
            echo "<tr class='tab_bg_1'><td>";
            if ($item->canUpdate($data['items_id'])
                || ($item->canView($data['items_id'])
                    && self::canUpdate())) {
               Html::showMassiveActionCheckBox($data['itemtype'], $data['items_id']);
            }
            echo "</td><td>".$item->getTypeName(1);
            echo "</td><td>".$item->getLink(['comments' => true]);
            echo "</td><td>".Dropdown::getDropdownName("glpi_entities", $item->getEntityID());
            if ($tree || $user) {
               echo "</td><td>";
               if ($grp = $item->getField($field)) {
                  if ($group->getFromDB($grp)) {
                     echo $group->getLink(['comments' => true]);
                  }

               } else if ($usr = $item->getField(str_replace('groups', 'users', $field))) {
                  if ($tuser->getFromDB($usr)) {
                     echo $tuser->getLink(['comments' => true]);
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
         if ($item->canUpdate($data['items_id'])
             || ($item->canView($data['items_id'])
                 && self::canUpdate())) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
         }
      }
      Html::closeForm();

      if ($nb) {
         Html::printAjaxPager('', $start, $nb);
      }

      echo "</div>";
   }


   function cleanRelationData() {

      global $DB;

      parent::cleanRelationData();

      if ($this->isUsedInConsumables()) {
         // Replace relation with Consumable
         $newval = (isset($this->input['_replace_by']) ? $this->input['_replace_by'] : 0);

         $fields_updates = [
            'items_id' => $newval,
         ];
         if (empty($newval)) {
            $fields_updates['itemtype'] = 'NULL';
            $fields_updates['date_out'] = 'NULL';
         }

         $DB->update(
            'glpi_consumables',
            $fields_updates,
            [
               'items_id' => $this->fields['id'],
               'itemtype' => self::class,
            ]
         );
      }
   }


   function isUsed() {

      if (parent::isUsed()) {
         return true;
      }

      return $this->isUsedInConsumables();
   }


   /**
    * Check if group is used in consumables.
    *
    * @return boolean
    */
   private function isUsedInConsumables() {

      return countElementsInTable(
         Consumable::getTable(),
         [
            'items_id' => $this->fields['id'],
            'itemtype' => self::class,
         ]
      ) > 0;
   }
}
