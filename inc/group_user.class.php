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
                       `glpi_groups_users`.`id`  as linkID
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
    * @param $target where to go on action
    * @param $user the user
    */
   static function showForUser($target, User $user) {
      global $DB,$CFG_GLPI, $LANG;

      $ID = $user->fields['id'];
      if (!haveRight("group","r") || !$user->can($ID,'r')) {
         return false;
      }

      $canedit = $user->can($ID,'w');

      $rand = mt_rand();
      $nb_per_line = 3;
      if ($canedit) {
         $headerspan = $nb_per_line*2;
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post' action='$target'>";
      } else {
         $headerspan = $nb_per_line;
      }

      echo "<div class='center'><table class='tab_cadre_fixehov'>".
            "<tr><th colspan='$headerspan'>".$LANG['Menu'][36]."</th></tr>";

      $groups = Group_User::getUserGroups($ID);
      $used = array();
      if (!empty($groups)) {
         $i = 0;
         foreach($groups as $data) {
            $used[] = $data["id"];
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
         echo "<tr class='tab_bg_1'>".
               "<td colspan='$headerspan' class='center'>".$LANG['common'][49]."</td></tr>";
      }
      echo "</table></div>";

      if ($canedit) {
         echo "<div class='center'>";
         if (count($used)) {
            openArrowMassive("groupuser_form$rand",true);
            closeArrowMassive('deletegroup', $LANG['buttons'][6]);
         } else {
            echo "<br>";
         }

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['setup'][604]."</tr>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";

         // All entities "edited user" have access
         $strict_entities = Profile_User::getUserEntities($ID,true);

         // Keep only entities "connected user" have access
         foreach ($strict_entities as $key => $val) {
            if (!haveAccessToEntity($val)) {
               unset($strict_entities[$key]);
            }
         }

         if (countElementsInTableForEntity("glpi_groups",$strict_entities) > count($used)) {
            Dropdown::show('Group', array('entity' => $strict_entities, 'used' => $used));
            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='submit' name='addgroup' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         } else {
            echo $LANG['common'][49];
         }
         echo "</td></tr>";
         echo "</table></div></form>";
      }
   }

   /**
    * Show users of a group
    *
    * @param $target string : where to go on action
    * @param $group the group
    */
   static function showForGroup($target, Group $group) {
      global $DB,$CFG_GLPI, $LANG;

      $ID = $group->fields['id'];
      if (!haveRight("user","r") || !$group->can($ID,'r')) {
         return false;
      }
      $canedit=$group->can($ID,"w");

      $rand=mt_rand();
      $nb_per_line=3;
      if ($canedit) {
         $headerspan=$nb_per_line*2;
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post' action=\"$target\">";
      } else {
         $headerspan=$nb_per_line;
      }

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='$headerspan'>".$LANG['Menu'][14]."</th></tr>";
      $query="SELECT `glpi_users`.*, `glpi_groups_users`.`id` AS linkID
              FROM `glpi_groups_users`
              LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)
              WHERE `glpi_groups_users`.`groups_id`='$ID'
              ORDER BY `glpi_users`.`name`, `glpi_users`.`realname`, `glpi_users`.`firstname`";

      $used = array();

      $result=$DB->query($query);
      if ($DB->numrows($result)>0) {
         $i=0;
         while ($data=$DB->fetch_array($result)) {
            if ($i%$nb_per_line==0) {
               if ($i!=0) {
                  echo "</tr>";
               }
               echo "<tr class='tab_bg_1'>";
            }
            if ($canedit) {
               echo "<td width='10'>";
               $sel="";
               if (isset($_GET["select"]) && $_GET["select"]=="all") {
                  $sel="checked";
               }
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
               echo "</td>";
            }

            $used[$data["id"]]=$data["id"];
            echo "<td>";
            echo formatUserName($data["id"],$data["name"],$data["realname"],$data["firstname"],1);
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
      echo "</table></div>";

      if ($canedit) {
         openArrowMassive("groupuser_form$rand", true);
         echo "<input type='hidden' name='groups_id' value='$ID'>";
         closeArrowMassive('deleteuser', $LANG['buttons'][6]);

         if ($group->fields["is_recursive"]) {
            $res=User::getSqlSearchResult (true, "all", getSonsOf("glpi_entities",
                                      $group->fields["entities_id"]), 0, $used);
         } else {
            $res=User::getSqlSearchResult (true, "all", $group->fields["entities_id"], 0, $used);
         }
         $nb=($res ? $DB->result($res,0,"CPT") : 0);

         if ($nb) {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['setup'][603]."</tr>";
            echo "<tr><td class='tab_bg_2 center'>";
            if ($group->fields["is_recursive"]) {
               User::dropdown(array('right'=>"all",'all'=>-1,'entity'=>getSonsOf("glpi_entities",
                                                        $group->fields["entities_id"]),'used'=>$used));
            } else {
               User::dropdown(array('right'=>"all",'all'=>-1,'entity'=>$group->fields["entities_id"],'used'=>$used));
            }
            echo "</td><td class='tab_bg_2 center'>";
            echo "<input type='submit' name='adduser' value=\"".$LANG['buttons'][8]."\" class='submit'>";
            echo "</td></tr>";
            echo "</table></div>";
         }
         echo "</form>";
      }
   }

}

?>
