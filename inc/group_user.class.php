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
 * Group_User Class
 *
 *  Relation between Group and User
**/
class Group_User extends CommonDBRelation{

   // From CommonDBRelation
   static $itemtype_1                 = 'User';
   static $items_id_1                 = 'users_id';

   static $itemtype_2                 = 'Group';
   static $items_id_2                 = 'groups_id';

   /**
   * Check if a user belongs to a group
   *
   * @since 9.4
   *
   * @param integer $users_id  the user ID
   * @param integer $groups_id the group ID
   *
   * @return boolean true if the user belongs to the group
   */
   static function isUserInGroup($users_id, $groups_id) {
      return countElementsInTable(
         'glpi_groups_users', [
            'users_id' => $users_id,
            'groups_id' => $groups_id
         ]
      ) > 0;
   }

   /**
    * Get groups for a user
    *
    * @param integer $users_id  User id
    * @param array   $condition Query extra condition (default [])
    *
    * @return array
   **/
   static function getUserGroups($users_id, $condition = []) {
      global $DB;

      $groups = [];
      $iterator = $DB->request([
         'SELECT' => [
            'glpi_groups.*',
            'glpi_groups_users.id AS IDD',
            'glpi_groups_users.id AS linkID',
            'glpi_groups_users.is_dynamic AS is_dynamic',
            'glpi_groups_users.is_manager AS is_manager',
            'glpi_groups_users.is_userdelegate AS is_userdelegate'
         ],
         'FROM'   => self::getTable(),
         'LEFT JOIN'    => [
            Group::getTable() => [
               'FKEY' => [
                  Group::getTable() => 'id',
                  self::getTable()  => 'groups_id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_groups_users.users_id' => $users_id
         ] + $condition,
         'ORDER'        => 'glpi_groups.name'
      ]);
      while ($row = $iterator->next()) {
         $groups[] = $row;
      }

      return $groups;
   }


   /**
    * Get users for a group
    *
    * @since 0.84
    *
    * @param integer $groups_id Group ID
    * @param array   $condition Query extra condition (default [])
    *
    * @return array
   **/
   static function getGroupUsers($groups_id, $condition = []) {
      global $DB;

      $users = [];

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_users.*',
            'glpi_groups_users.id AS IDD',
            'glpi_groups_users.id AS linkID',
            'glpi_groups_users.is_dynamic AS is_dynamic',
            'glpi_groups_users.is_manager AS is_manager',
            'glpi_groups_users.is_userdelegate AS is_userdelegate'
         ],
         'FROM'   => self::getTable(),
         'LEFT JOIN'    => [
            User::getTable() => [
               'FKEY' => [
                  User::getTable() => 'id',
                  self::getTable()  => 'users_id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_groups_users.groups_id' => $groups_id
         ] + $condition,
         'ORDER'        => 'glpi_users.name'
      ]);
      while ($row = $iterator->next()) {
         $users[] = $row;
      }

      return $users;
   }


   /**  Show groups of a user
    *
    * @param $user   User object
   **/
   static function showForUser(User $user) {
      global $CFG_GLPI;

      $ID = $user->fields['id'];
      if (!Group::canView()
          || !$user->can($ID, READ)) {
         return false;
      }

      $canedit = $user->can($ID, UPDATE);

      $rand    = mt_rand();

      $iterator = self::getListForItem($user);
      $groups = [];
      //$groups  = self::getUserGroups($ID);
      $used    = [];
      while ($data = $iterator->next()) {
         $used[$data["id"]] = $data["id"];
         $groups[] = $data;
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'";
         echo " action='".Toolbox::getItemTypeFormURL('User')."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Associate to a group')."</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";

         $params = ['used'      => $used,
                    'condition' => "is_usergroup
                                     AND (`entities_id` =  ".$_SESSION['glpiactive_entity']."
                                          OR (`entities_id` IN (0,".implode(',', $_SESSION['glpiactiveentities']).")
                                              AND is_recursive))"];
         Group::dropdown($params);
         echo "</td><td>".__('Manager')."</td><td>";
         Dropdown::showYesNo('is_manager');

         echo "</td><td>".__('Delegatee')."</td><td>";
         Dropdown::showYesNo('is_userdelegate');

         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='addgroup' value=\""._sx('button', 'Add')."\"
                class='submit'>";

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && count($used)) {
         $rand = mt_rand();
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         echo "<input type='hidden' name='users_id' value='".$user->fields['id']."'>";
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], count($used)),
                           'container'     => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && count($used)) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".Group::getTypeName(1)."</th>";
      $header_end .= "<th>".__('Dynamic')."</th>";
      $header_end .= "<th>".__('Manager')."</th>";
      $header_end .= "<th>".__('Delegatee')."</th></tr>";
      echo $header_begin.$header_top.$header_end;

      $group = new Group();
      if (!empty($groups)) {
         Session::initNavigateListItems('Group',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                User::getTypeName(1), $user->getName()));

         foreach ($groups as $data) {
            if (!$group->getFromDB($data["id"])) {
               continue;
            }
            Session::addToNavigateListItems('Group', $data["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit && count($used)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
               echo "</td>";
            }
            echo "<td>".$group->getLink()."</td>";
            echo "<td class='center'>";
            if ($data['is_dynamic']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Dynamic')."\">";
            }
            echo "<td class='center'>";
            if ($data['is_manager']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Manager')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_userdelegate']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Delegatee')."\">";
            }
            echo "</td></tr>";
         }
         echo $header_begin.$header_bottom.$header_end;

      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='5' class='center'>".__('None')."</td></tr>";
      }
      echo "</table>";

      if ($canedit && count($used)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Show form to add a user in current group
    *
    * @since 0.83
    *
    * @param $group                    Group object
    * @param $used_ids        Array    of already add users
    * @param $entityrestrict  Array    of entities
    * @param $crit            String   for criteria (for default dropdown)
   **/
   private static function showAddUserForm(Group $group, $used_ids, $entityrestrict, $crit) {
      global $CFG_GLPI, $DB;

      $rand = mt_rand();
      $res  = User::getSqlSearchResult(true, "all", $entityrestrict, 0, $used_ids);
      $nb = count($res);

      if ($nb) {
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='groups_id' value='".$group->fields['id']."'>";

         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Add a user')."</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";

         User::dropdown(['right'  => "all",
                              'entity' => $entityrestrict,
                              'used'   => $used_ids]);

         echo "</td><td>".__('Manager')."</td><td>";
         Dropdown::showYesNo('is_manager', (($crit == 'is_manager') ? 1 : 0));

         echo "</td><td>".__('Delegatee')."</td><td>";
         Dropdown::showYesNo('is_userdelegate', (($crit == 'is_userdelegate') ? 1 : 0));

         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name'is_dynamic' value='0'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();
      }
   }


   /**
    * Retrieve list of member of a Group
    *
    * @since 0.83
    *
    * @param $group              Group object
    * @param $members   Array    filled on output of member (filtered)
    * @param $ids       Array    of ids (not filtered)
    * @param $crit      String   filter (is_manager, is_userdelegate) (default '')
    * @param $tree      Boolean  true to include member of sub-group (default 0)
    *
    * @return String tab of entity for restriction
   **/
   static function getDataForGroup(Group $group, &$members, &$ids, $crit = '', $tree = 0) {
      global $DB;

      // Entity restriction for this group, according to user allowed entities
      if ($group->fields['is_recursive']) {
         $entityrestrict = getSonsOf('glpi_entities', $group->fields['entities_id']);

         // active entity could be a child of object entity
         if (($_SESSION['glpiactive_entity'] != $group->fields['entities_id'])
             && in_array($_SESSION['glpiactive_entity'], $entityrestrict)) {
            $entityrestrict = getSonsOf('glpi_entities', $_SESSION['glpiactive_entity']);
         }
      } else {
         $entityrestrict = $group->fields['entities_id'];
      }

      if ($tree) {
         $restrict = "IN (".implode(',', getSonsOf('glpi_groups', $group->getID())).")";
      } else {
         $restrict = "='".$group->getID()."'";
      }

      // All group members
      $query = "SELECT DISTINCT `glpi_users`.`id`,
                       `glpi_groups_users`.`id` AS linkID,
                       `glpi_groups_users`.`groups_id`,
                       `glpi_groups_users`.`is_dynamic` AS is_dynamic,
                       `glpi_groups_users`.`is_manager` AS is_manager,
                       `glpi_groups_users`.`is_userdelegate` AS is_userdelegate
                FROM `glpi_groups_users`
                INNER JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)
                INNER JOIN `glpi_profiles_users`
                        ON (`glpi_profiles_users`.`users_id`=`glpi_users`.`id`)
                WHERE `glpi_groups_users`.`groups_id` $restrict ".
                      getEntitiesRestrictRequest('AND', 'glpi_profiles_users', '',
                                                 $entityrestrict, 1)."
                ORDER BY `glpi_users`.`realname`,
                         `glpi_users`.`firstname`,
                         `glpi_users`.`name`";

      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         while ($data=$DB->fetch_assoc($result)) {
            // Add to display list, according to criterion
            if (empty($crit) || $data[$crit]) {
               $members[] = $data;
            }
            // Add to member list (member of sub-group are not member)
            if ($data['groups_id'] == $group->getID()) {
               $ids[]  = $data['id'];
            }
         }
      }

      return $entityrestrict;
   }


   /**
    * Show users of a group
    *
    * @since 0.83
    *
    * @param $group  Group object: the group
   **/
   static function showForGroup(Group $group) {
      global $DB, $CFG_GLPI;

      $ID = $group->getID();
      if (!User::canView()
          || !$group->can($ID, READ)) {
         return false;
      }

      // Have right to manage members
      $canedit = self::canUpdate();
      $rand    = mt_rand();
      $user    = new User();
      $crit    = Session::getSavedOption(__CLASS__, 'criterion', '');
      $tree    = Session::getSavedOption(__CLASS__, 'tree', 0);
      $used    = [];
      $ids     = [];

      // Retrieve member list
      // TODO: migrate to use CommonDBRelation::getListForItem()
      $entityrestrict = self::getDataForGroup($group, $used, $ids, $crit, $tree);

      if ($canedit) {
         self::showAddUserForm($group, $ids, $entityrestrict, $crit);
      }

      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".User::getTypeName(Session::getPluralNumber())."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo _n('Criterion', 'Criteria', 1)."&nbsp;";
      $crits = ['is_manager'      => __('Manager'),
                     'is_userdelegate' => __('Delegatee')];
      Dropdown::showFromArray('crit', $crits,
                              ['value'               => $crit,
                                    'on_change'           => 'reloadTab("start=0&criterion="+this.value)',
                                    'display_emptychoice' => true]);
      if ($group->haveChildren()) {
         echo "</td><td class='center'>".__('Child groups');
         Dropdown::showYesNo('tree', $tree, -1,
                             ['on_change' => 'reloadTab("start=0&tree="+this.value)']);
      } else {
         $tree = 0;
      }
      echo "</td></tr></table>";
      $number = count($used);
      $start  = (isset($_GET['start']) ? intval($_GET['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }

      // Display results
      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager(sprintf(__('%1$s (%2$s)'),
                                      User::getTypeName(Session::getPluralNumber()), __('D=Dynamic')),
                              $start, $number);

         Session::initNavigateListItems('User',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Group::getTypeName(1), $group->getName()));

         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min($number-$start,
                                                                   $_SESSION['glpilist_limit']),
                                         'container'        => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";

         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';

         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>".User::getTypeName(1)."</th>";
         if ($tree) {
            $header_end .= "<th>".Group::getTypeName(1)."</th>";
         }
         $header_end .= "<th>".__('Dynamic')."</th>";
         $header_end .= "<th>".__('Manager')."</th>";
         $header_end .= "<th>".__('Delegatee')."</th>";
         $header_end .= "<th>".__('Active')."</th></tr>";
         echo $header_begin.$header_top.$header_end;

         $tmpgrp = new Group();

         for ($i=$start, $j=0; ($i < $number) && ($j < $_SESSION['glpilist_limit']); $i++, $j++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);
            Session::addToNavigateListItems('User', $data["id"]);

            echo "\n<tr class='tab_bg_".($user->isDeleted() ? '1_2' : '1')."'>";
            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["linkID"]);
               echo "</td>";
            }
            echo "<td>".$user->getLink();
            if ($tree) {
               echo "</td><td>";
               if ($tmpgrp->getFromDB($data['groups_id'])) {
                  echo $tmpgrp->getLink(['comments' => true]);
               }
            }
            echo "</td><td class='center'>";
            if ($data['is_dynamic']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Dynamic')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_manager']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Manager')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_userdelegate']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Delegatee')."\">";
            }
            echo "</td><td class='center'>";
            if ($user->fields['is_active']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
               __('Active')."\">";
            }
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         Html::printAjaxPager(sprintf(__('%1$s (%2$s)'),
                                      User::getTypeName(Session::getPluralNumber()), __('D=Dynamic')),
                              $start, $number);

         echo "</div>";
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationMassiveActionsSpecificities()
   **/
   static function getRelationMassiveActionsSpecificities() {
      global $CFG_GLPI;

      $specificities                           = parent::getRelationMassiveActionsSpecificities();

      $specificities['select_items_options_1'] = ['right'     => 'all'];
      $specificities['select_items_options_2'] = ['condition' => '`is_usergroup`'];

      // Define normalized action for add_item and remove_item
      $specificities['normalized']['add'][]    = 'add_supervisor';
      $specificities['normalized']['add'][]    = 'add_delegatee';

      $specificities['button_labels']['add_supervisor'] = $specificities['button_labels']['add'];
      $specificities['button_labels']['add_delegatee']  = $specificities['button_labels']['add'];

      $specificities['update_if_different'] = true;

      return $specificities;
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationInputForProcessingOfMassiveActions()
   **/
   static function getRelationInputForProcessingOfMassiveActions($action, CommonDBTM $item,
                                                                 array $ids, array $input) {
      switch ($action) {
         case 'add_supervisor' :
            return ['is_manager' => 1];

         case 'add_delegatee' :
            return ['is_userdelegate' => 1];
      }

      return [];
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'is_dynamic',
         'name'               => __('Dynamic'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => __('Group'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'is_manager',
         'name'               => __('Manager'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_userdelegate',
         'name'               => __('Delegatee'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * @param $user_ID
    * @param $only_dynamic (false by default
   **/
   static function deleteGroups($user_ID, $only_dynamic = false) {
      global $DB;

      $crit['users_id'] = $user_ID;
      if ($only_dynamic) {
         $crit['is_dynamic'] = '1';
      }
      $obj = new self();
      $obj->deleteByCriteria($crit);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'User' :
               if (Group::canView()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForItem($item);
                  }
                  return self::createTabEntry(Group::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;

            case 'Group' :
               if (User::canView()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForItem($item);
                  }
                  return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'User' :
            self::showForUser($item);
            break;

         case 'Group' :
            self::showForGroup($item);
            break;
      }
      return true;
   }

   /**
    * Get linked items list for specified item
    *
    * @since 9.3.1
    *
    * @param CommonDBTM $item  Item instance
    * @param boolean    $noent Flag to not compute entity informations (see Document_Item::getListForItemParams)
    *
    * @return array
    */
   protected static function getListForItemParams(CommonDBTM $item, $noent = false) {
      $params = parent::getListForItemParams($item, $noent);
      $params['SELECT'][] = self::getTable() . '.is_manager';
      $params['SELECT'][] = self::getTable() . '.is_userdelegate';
      return $params;
   }
}
