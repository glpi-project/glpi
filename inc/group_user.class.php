<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Group_User class - Relation between Group and User
class Group_User extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'User';
   public $items_id_1 = 'users_id';

   public $itemtype_2 = 'Group';
   public $items_id_2 = 'groups_id';

   public $checks_only_for_itemtype1 = true;
   public $logs_only_for_itemtype1   = false;


   /**
    * @param $users_id
    * @param $condition    (default '')
   **/
   static function getUserGroups($users_id, $condition='') {
      global $DB;

      $groups = array();
      $query  = "SELECT `glpi_groups`.*,
                        `glpi_groups_users`.`id` AS IDD,
                        `glpi_groups_users`.`id` AS linkID,
                        `glpi_groups_users`.`is_dynamic` AS is_dynamic,
                        `glpi_groups_users`.`is_manager` AS is_manager,
                        `glpi_groups_users`.`is_userdelegate` AS is_userdelegate
                 FROM `glpi_groups_users`
                 LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                 WHERE `glpi_groups_users`.`users_id` = '$users_id' ";
      if (!empty($condition)) {
         $query .= " AND $condition ";
      }
      $query.=" ORDER BY `glpi_groups`.`name`";

      foreach ($DB->request($query) as $data) {
         $groups[] = $data;
      }
      return $groups;
   }


   /**
    * @since version 0.84
    *
    * @param $groups_id
    * @param $condition    (default '')
   **/
   static function getGroupUsers($groups_id, $condition='') {
      global $DB;

      $users = array();
      $query = "SELECT `glpi_users`.*,
                       `glpi_groups_users`.`id` AS IDD,
                       `glpi_groups_users`.`id` AS linkID,
                       `glpi_groups_users`.`is_dynamic` AS is_dynamic,
                       `glpi_groups_users`.`is_manager` AS is_manager,
                       `glpi_groups_users`.`is_userdelegate` AS is_userdelegate
                FROM `glpi_groups_users`
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)
                WHERE `glpi_groups_users`.`groups_id` = '$groups_id'";
      if (!empty($condition)) {
         $query .= " AND $condition ";
      }
      $query .= "ORDER BY `glpi_users`.`name`";

      foreach ($DB->request($query) as $data) {
         $users[] = $data;
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
      if (!Session::haveRight("group","r")
          || !$user->can($ID,'r')) {
         return false;
      }

      $canedit     = $user->can($ID,'w');

      $rand        = mt_rand();
      $nb_per_line = 3;
      if ($canedit) {
         $headerspan = $nb_per_line*2;
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'";
         echo " action='".Toolbox::getItemTypeFormURL('User')."'>";
      } else {
         $headerspan = $nb_per_line;
      }

      $groups = self::getUserGroups($ID);
      $used   = array();
      if (!empty($groups)) {
         foreach ($groups as $data) {
            $used[$data["id"]] = $data["id"];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".__('Associate to a group')."</th></tr>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";

         // All entities "edited user" have access
         $strict_entities = Profile_User::getUserEntities($ID, true);

         // Keep only entities "connected user" have access
         foreach ($strict_entities as $key => $val) {
            if (!Session::haveAccessToEntity($val)) {
               unset($strict_entities[$key]);
            }
         }

         if (countElementsInTableForEntity("glpi_groups", $strict_entities) > count($used)) {
            Group::dropdown(array('entity'    => $strict_entities,
                                  'used'      => $used,
                                  'condition' => '`is_usergroup`'));
            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='submit' name='addgroup' value=\""._sx('button','Add')."\"
                   class='submit'>";
         } else {
            _e('None');
         }

         echo "</td></tr>";
         echo "</table></div>";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixehov'><tr>";
      echo "<th colspan='$headerspan'>".sprintf(__('%1$s (%2$s)'),
                                                Group::getTypeName(2), __('D=Dynamic')).
           "</th>";
      echo "</tr>";


      if (!empty($groups)) {
         Session::initNavigateListItems('Group',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $user->getTypeName(1), $user->getName()));

         $i = 0;
         foreach ($groups as $data) {
            Session::addToNavigateListItems('Group', $data["id"]);
            if (($i%$nb_per_line) == 0) {
               if ($i != 0) {
                  echo "</tr>";
               }
               echo "<tr class='tab_bg_1'>";
            }

            if ($canedit) {
               echo "<td width='10'>";
               $sel = "";
               if (isset($_GET["select"]) && ($_GET["select"] == "all")) {
                  $sel = "checked";
               }
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
               echo "</td>";
            }
            if ($_SESSION["glpiis_ids_visible"]) {
               $link = sprintf(__('%1$s (%2$s)'), $data["completename"], $data["id"]);
            } else {
               $link = $data["completename"];
            }
            $href = "<a href='".$CFG_GLPI["root_doc"]."/front/group.form.php?id=".$data["id"]."'>".
                      $link."</a>";
            if ($data["is_dynamic"]) {
               $href = sprintf(__('%1$s (%2$s)'), $href, "<span class='b'>".__('D')."</span>");
            }
            echo "<td>".$href."</td>";
            $i++;
         }

         while (($i%$nb_per_line) != 0) {
            if ($canedit) {
               echo "<td>&nbsp;</td>";
            }
            echo "<td>&nbsp;</td>";
            $i++;
         }
         echo "</tr>";

      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='$headerspan' class='center'>".__('None')."</td></tr>";
      }
      echo "</table>";

      if ($canedit) {
         if (count($used)) {
            Html::openArrowMassives("groupuser_form$rand", true);
            Html::closeArrowMassives(array('deletegroup' => __('Delete')));
         }
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Show form to add a user in current group
    *
    * @since version 0.83
    *
    * @param $group                    Group object
    * @param $used_ids        Array    of already add users
    * @param $entityrestrict  Array    of entities
    * @param $crit            String   for criteria (for default dropdown)
   **/
   private static function showAddUserForm(Group $group, $used_ids, $entityrestrict, $crit) {
      global $CFG_GLPI, $DB;

      $rand = mt_rand();
      $res  = User::getSqlSearchResult (true, "all", $entityrestrict, 0, $used_ids);
      $nb   = ($res ? $DB->result($res,0,"CPT") : 0);

      if ($nb) {
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'
                action='".$CFG_GLPI['root_doc']."/front/group.form.php'>";
         echo "<input type='hidden' name='groups_id' value='".$group->fields['id']."'>";

         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Add a user')."</tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";

         User::dropdown(array('right'  => "all",
                              'entity' => $entityrestrict,
                              'used'   => $used_ids));

         echo "</td><td>".__('Manager')."</td><td>";
         Dropdown::showYesNo('is_manager', (($crit == 'is_manager') ? 1 : 0));

         echo "</td><td>".__('Delegatee')."</td><td>";
         Dropdown::showYesNo('is_userdelegate', (($crit == 'is_userdelegate') ? 1 : 0));

         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name'is_dynamic' value='0'>";
         echo "<input type='submit' name='adduser' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();
      }
   }


   /**
    * Retrieve list of member of a Group
    *
    * @since version 0.83
    *
    * @param $group              Group object
    * @param $members   Array    filled on output of member (filtered)
    * @param $ids       Array    of ids (not filtered)
    * @param $crit      String   filter (is_manager, is_userdelegate) (default '')
    * @param $tree      Boolean  true to include member of sub-group (default 0)
    *
    * @return String tab of entity for restriction
   **/
   static function getDataForGroup(Group $group, &$members, &$ids, $crit='', $tree=0) {
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
    * @since version 0.83
    *
    * @param $group  Group object: the group
   **/
   static function showForGroup(Group $group) {
      global $DB, $CFG_GLPI;

      $ID = $group->getID();
      if (!Session::haveRight("user","r")
          || !$group->can($ID,'r')) {
         return false;
      }

      // Have right to manage members
      $canedit = ($group->canManageUsersItem());
      $rand    = mt_rand();
      $user    = new User();
      $crit    = Session::getSavedOption(__CLASS__, 'criterion', '');
      $tree    = Session::getSavedOption(__CLASS__, 'tree', 0);
      $used    = array();
      $ids     = array();

      // Retrieve member list
      $entityrestrict = self::getDataForGroup($group, $used, $ids, $crit, $tree);

      if ($canedit) {
         self::showAddUserForm($group, $ids, $entityrestrict, $crit);
      }

      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".User::getTypeName(2)."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo _n('Criterion', 'Criteria', 1)."&nbsp;";
      $crits = array(''                => Dropdown::EMPTY_VALUE,
                     'is_manager'      => __('Manager'),
                     'is_userdelegate' => __('Delegatee'));
      Dropdown::showFromArray('crit', $crits,
                              array('value'     => $crit,
                                    'on_change' => 'reloadTab("start=0&criterion="+this.value)'));
      if ($group->haveChildren()) {
         echo "</td><td class='center'>".__('Child groups');
         Dropdown::showYesNo('tree', $tree, -1,
                             array('on_change' => 'reloadTab("start=0&tree="+this.value)'));
      } else {
         $tree = 0;
      }
      echo "</td></tr></table>";
      $number = count($used);
      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }

      // Display results
      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager(sprintf(__('%1$s (%2$s)'),
                                      User::getTypeName(2), __('D=Dynamic')),
                              $start, $number);

         Session::initNavigateListItems('User',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $group->getTypeName(1), $group->getName()));

         if ($canedit) {
            echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'
                   action='".$CFG_GLPI["root_doc"]."/front/massiveaction.php'>";
            echo "<input type='hidden' name='groups_id' value='".$group->fields['id']."'>";
         }
         if ($canedit) {
            $paramsma = array('num_displayed' =>
                                 min($number-$start, $_SESSION['glpilist_limit']));
            Html::displayMassiveActions('Group_User', $paramsma);
         }
         
         echo "<table class='tab_cadre_fixehov'><tr>";
         if ($canedit) {
            echo "<th width='10'>&nbsp;</th>";
         }
         echo "<th>".User::getTypeName(1)."</th>";
         if ($tree) {
           echo "<th>".Group::getTypeName(1)."</th>";
         }
         echo "<th>".__('Manager')."</th>";
         echo "<th>".__('Delegatee')."</th></tr>";

         $tmpgrp = new Group();

         for ($i=$start, $j=0 ; ($i < $number) && ($j < $_SESSION['glpilist_limit']) ; $i++, $j++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);
            Session::addToNavigateListItems('User', $data["id"]);

            echo "\n<tr class='tab_bg_".($user->isDeleted() ? '1_2' : '1')."'>";
            if ($canedit) {
               echo "<td width='10'>";
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
               echo "</td>";
            }
            $link = $user->getLink();
            if ($data["is_dynamic"]) {
               $link = sprintf(__('%1$s %2$s'), $link, "<span class='b'>(".__('D').")</span>");
            }
            echo "<td>".$link;
            if ($tree) {
               echo "</td><td>";
               if ($tmpgrp->getFromDB($data['groups_id'])) {
                  echo $tmpgrp->getLink(true);
               }
            }
            echo "</td><td class='center'>";
            if ($data['is_manager']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14'/>";
            }
            echo "</td><td class='center'>";
            if ($data['is_userdelegate']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14'/>";
            }
            echo "</tr>";
         }
         echo "</table>";
         if ($canedit) {
            $paramsma['ontop'] =false;

            Html::displayMassiveActions('Group_User', $paramsma);
         }
         Html::printAjaxPager(sprintf(__('%1$s (%2$s)'),
                                      User::getTypeName(2), __('D=Dynamic')),
                              $start, $number);

         echo "</div>";
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'is_dynamic';
      $tab[3]['name']            = __('Dynamic');
      $tab[3]['datatype']        = 'bool';
      $tab[3]['massiveaction']   = false;

      $tab[4]['table']           = 'glpi_groups';
      $tab[4]['field']           = 'completename';
      $tab[4]['name']            = __('Group');
      $tab[4]['massiveaction']   = false;

      $tab[5]['table']           = 'glpi_users';
      $tab[5]['field']           = 'name';
      $tab[5]['name']            = __('User');
      $tab[5]['massiveaction']   = false;

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'is_manager';
      $tab[6]['name']            = __('Manager');
      $tab[6]['datatype']        = 'bool';

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'is_delegatee';
      $tab[7]['name']            = __('Delegatee');
      $tab[7]['datatype']        = 'bool';

      return $tab;
   }


   /**
    * @param $user_ID
    * @param $only_dynamic (false by default
   **/
   static function deleteGroups($user_ID, $only_dynamic=false) {
      global $DB;

      $crit['users_id'] = $user_ID;
      if ($only_dynamic) {
         $crit['is_dynamic'] = '1';
      }
      $obj = new self();
      $obj->deleteByCriteria($crit);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'User' :
               if (Session::haveRight("group","r")) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     return self::createTabEntry(Group::getTypeName(2),
                                                 countElementsInTable($this->getTable(),
                                                                      "users_id
                                                                        = '".$item->getID()."'"));
                  }
                  return Group::getTypeName(2);
               }
               break;

            case 'Group' :
               if (Session::haveRight("user","r")) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     return self::createTabEntry(User::getTypeName(2),
                                                 countElementsInTable("glpi_groups_users",
                                                                      "`groups_id`
                                                                        = '".$item->getID()."'" ));
                  }
                  return User::getTypeName(2);
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

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

}
?>
