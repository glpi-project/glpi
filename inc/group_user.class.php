<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Group_User class - Relation between Group and User
class Group_User extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'User';
   public $items_id_1 = 'users_id';

   public $itemtype_2 = 'Group';
   public $items_id_2 = 'groups_id';

   static function getUserGroups($users_id) {
      global $DB;

      $groups = array();
      $query = "SELECT `glpi_groups`.*,
                       `glpi_groups_users`.`id` AS IDD,
                       `glpi_groups_users`.`id`  as linkID,
                       `glpi_groups_users`.`is_dynamic` AS is_dynamic
                FROM `glpi_groups_users`
                LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                WHERE `glpi_groups_users`.`users_id` = '$users_id'
                ORDER BY `glpi_groups`.`name`";

      foreach ($DB->request($query) as $data) {
         $groups[] = $data;
      }
      return $groups;
   }


   /**  Show groups of a user
    *
    * @param $user the user
    */
   static function showForUser(User $user) {
      global $CFG_GLPI, $LANG;

      $ID = $user->fields['id'];
      if (!haveRight("group","r") || !$user->can($ID,'r')) {
         return false;
      }

      $canedit = $user->can($ID,'w');

      $rand = mt_rand();
      $nb_per_line = 3;
      if ($canedit) {
         $headerspan = $nb_per_line*2;
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'";
         echo " action='".getItemTypeFormURL('User')."'>";
      } else {
         $headerspan = $nb_per_line;
      }

      $groups = Group_User::getUserGroups($ID);
      $used   = array();
      if (!empty($groups)) {
         foreach($groups as $data) {
            $used[$data["id"]] = $data["id"];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['setup'][604]."</th></tr>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";

         // All entities "edited user" have access
         $strict_entities = Profile_User::getUserEntities($ID, true);

         // Keep only entities "connected user" have access
         foreach ($strict_entities as $key => $val) {
            if (!haveAccessToEntity($val)) {
               unset($strict_entities[$key]);
            }
         }

         if (countElementsInTableForEntity("glpi_groups", $strict_entities) > count($used)) {
            Dropdown::show('Group', array('entity' => $strict_entities,
                                          'used'   => $used));
            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='submit' name='addgroup' value='".$LANG['buttons'][8]."' class='submit'>";
         } else {
            echo $LANG['common'][49];
         }

         echo "</td></tr>";
         echo "</table></div>";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixehov'><tr>";
      echo "<th colspan='$headerspan'>".$LANG['Menu'][36]."&nbsp;(D=".$LANG['profiles'][29].")</th>";
      echo "</tr>";


      if (!empty($groups)) {
         initNavigateListItems('Group', $user->getTypeName()." = ".$user->getName());
         $i = 0;
         foreach($groups as $data) {
            addToNavigateListItems('Group', $data["id"]);
            if ($i%$nb_per_line == 0) {
               if ($i != 0) {
                  echo "</tr>";
               }
               echo "<tr class='tab_bg_1'>";
            }

            if ($canedit) {
               echo "<td width='10'>";
               $sel = "";
               if (isset($_GET["select"]) && $_GET["select"]=="all") {
                  $sel = "checked";
               }
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
               echo "</td>";
            }
            echo "<td><a href='".$CFG_GLPI["root_doc"]."/front/group.form.php?id=".$data["id"]."'>".
                  $data["name"].($_SESSION["glpiis_ids_visible"]?" (".$data["id"].")":"")."</a>";
            echo "&nbsp;";

            if ($data["is_dynamic"]) {
               echo "<strong>&nbsp;(D)</strong>";
            }
            echo "</td>";
            $i++;
         }

         while ($i%$nb_per_line != 0) {
            if ($canedit) {
               echo "<td>&nbsp;</td>";
            }
            echo "<td>&nbsp;</td>";
            $i++;
         }
         echo "</tr>";

      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='$headerspan' class='center'>".$LANG['common'][49]."</td></tr>";
      }
      echo "</table>";

      if ($canedit) {
         if (count($used)) {
            openArrowMassive("groupuser_form$rand", true);
            closeArrowMassive('deletegroup', $LANG['buttons'][6]);
         }
         echo "</form>";
      }
      echo "</div>";
   }


   /**
    * Show users of a group
    *
    * @param $target string : where to go on action
    * @param $group the group
    */
   static function showForGroup($target, Group $group) {
      global $DB, $LANG;

      $ID = $group->fields['id'];
      if (!haveRight("user","r") || !$group->can($ID,'r')) {
         return false;
      }
      $canedit     = $group->can($ID,"w");
      $rand        = mt_rand();
      $nb_per_line = 3;


      $query = "SELECT `glpi_users`.*,
                       `glpi_groups_users`.`id` AS linkID,
                      `glpi_groups_users`.`is_dynamic` AS is_dynamic
                FROM `glpi_groups_users`
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)
                WHERE `glpi_groups_users`.`groups_id`='$ID'
                ORDER BY `glpi_users`.`name`,
                         `glpi_users`.`realname`,
                         `glpi_users`.`firstname`";

      $used   = array();
      $result = $DB->query($query);

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            $used[$data["id"]] = $data;
         }
      }
      $used_ids = array_keys($used);
      if ($canedit) {
         $headerspan = $nb_per_line*2;
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post' action='$target'>";

         if ($group->fields["is_recursive"]) {
            $res = User::getSqlSearchResult (true, "all", getSonsOf("glpi_entities",
                                                                     $group->fields["entities_id"]),
                                             0, $used_ids);
         } else {
            $res = User::getSqlSearchResult (true, "all", $group->fields["entities_id"], 0,
                                             $used_ids);
         }
         $nb = ($res ? $DB->result($res,0,"CPT") : 0);

         if ($nb) {
            echo "<div class='firstbloc'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['setup'][603]."</tr>";
            echo "<tr><td class='tab_bg_2 center'>";

            if ($group->fields["is_recursive"]) {
               User::dropdown(array('right'  => "all",
                                    'all'    => -1,
                                    'entity' => getSonsOf("glpi_entities",
                                                          $group->fields["entities_id"]),
                                    'used'   => $used_ids));
            } else {
               User::dropdown(array('right'  => "all",
                                    'all'    => -1,
                                    'entity' => $group->fields["entities_id"],
                                    'used'   => $used_ids));
            }
            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='hidden' name'is_dynamic' value='0'>";
            echo "<input type='submit' name='adduser' value='".$LANG['buttons'][8]."' class='submit'>";
            echo "</td></tr>";
            echo "</table></div>";
         }

      } else {
         $headerspan = $nb_per_line;
      }

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='$headerspan'>".$LANG['Menu'][14]." (D=".$LANG['profiles'][29].")";
      echo "</th></tr>";

      if (count($used)) {
         initNavigateListItems('User', $group->getTypeName()." = ".$group->getName());
         $i = 0;
         foreach  ($used as $id => $data) {
            addToNavigateListItems('User', $data["id"]);
            if ($i%$nb_per_line==0) {
               if ($i!=0) {
                  echo "</tr>";
               }
               echo "<tr class='tab_bg_1'>";
            }
            if ($canedit) {
               echo "<td width='10'>";
               $sel = "";
               if (isset($_GET["select"]) && $_GET["select"]=="all") {
                  $sel = "checked";
               }
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
               echo "</td>";
            }

            echo "<td>";
            echo formatUserName($data["id"],$data["name"],$data["realname"],$data["firstname"],1);
            if ($data["is_dynamic"]) {
               echo "<strong>&nbsp;(D)</strong>";
            }

            echo "</td>";
            $i++;
         }
         while ($i%$nb_per_line!=0) {
            echo "<td>&nbsp;</td>";
            if ($canedit) {
               echo "<td>&nbsp;</td>";
            }
            $i++;
         }
         echo "</tr>";
      }
      echo "</table>";

      if ($canedit) {
         openArrowMassive("groupuser_form$rand", true);
         echo "<input type='hidden' name='groups_id' value='$ID'>";
         closeArrowMassive('deleteuser', $LANG['buttons'][6]);

         echo "</form>";
      }
      echo "</div>";
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'is_dynamic';
      $tab[3]['linkfield'] = 'is_dynamic';
      $tab[3]['name']      = $LANG['profiles'][29];
      $tab[3]['datatype']  = 'bool';

      $tab[4]['table']     = 'glpi_groups';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'groups_id';
      $tab[4]['name']      = $LANG['common'][35];

      $tab[5]['table']     = 'glpi_users';
      $tab[5]['field']     = 'name';
      $tab[5]['linkfield'] = 'users_id';
      $tab[5]['name']      = $LANG['common'][34];

      return $tab;
   }


   static function deleteGroups($user_ID, $only_dynamic = false) {
      global $DB;

      $crit['users_id'] = $user_ID;
      if ($only_dynamic) {
         $crit['is_dynamic'] = '1';
      }
      $obj = new Group_User();
      $obj->deleteByCriteria($crit);
   }


}

?>
