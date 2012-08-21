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


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Profile_User class
class Profile_User extends CommonDBTM {

   // From CommonDBTM
   var $auto_message_on_action = false;

   function getForbiddenStandardMassiveAction() {
      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   function maybeRecursive() {
      // Store is_recursive fields but not really recursive object
      return false;
   }


   function canView() {
      return Session::haveRight('user','r');
   }


   function canCreate() {
      return Session::haveRight('user','w');
   }


   function canCreateItem() {

      $user = new User();
      return $user->can($this->fields['users_id'],'r')
             && Profile::currentUserHaveMoreRightThan(array($this->fields['profiles_id']
                                                               => $this->fields['profiles_id']))
             && Session::haveAccessToEntity($this->fields['entities_id']);
   }


   /****/
   function prepareInputForAdd($input) {

      if (!isset($input['profiles_id'])
          || ($input['profiles_id'] <= 0)
          || !isset($input['entities_id'])
          || ($input['entities_id'] < 0)
          || !isset($input['users_id'])
          || ($input['users_id'] < 0)) {

         Session::addMessageAfterRedirect(__('No selected element or badly defined operation'),
                                          false, ERROR);
         return false;
      }
      return $input;
   }


   /**
    * Show rights of a user
    *
    * @param $user User object
   **/
   static function showForUser(User $user) {
      global $DB,$CFG_GLPI;

      $ID = $user->getField('id');
      if (!$user->can($ID,'r')) {
         return false;
      }

      $canedit = $user->can($ID,'w');

      $strict_entities = self::getUserEntities($ID,false);
      if (!Session::haveAccessToOneOfEntities($strict_entities)
          && !Session::isViewAllEntities()) {
         $canedit = false;
      }

      $canshowentity = Session::haveRight("entity","r");
      $rand          = mt_rand();

      if ($canedit) {
         echo "<form name='entityuser_form$rand' id='entityuser_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Add an authorization to a user')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";
         Entity::dropdown(array('entity' => $_SESSION['glpiactiveentities']));
         echo "</td><td class='center'>".self::getTypeName(1)."</td><td>";
         Profile::dropdownUnder(array('value' => Profile::getDefault()));
         echo "</td><td>".__('Recursive')."</td><td>";
         Dropdown::showYesNo("is_recursive",0);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table></div>";
         Html::closeForm();
      }

      $query = "SELECT DISTINCT `glpi_profiles_users`.`id` AS linkID,
                       `glpi_profiles`.`id`,
                       `glpi_profiles`.`name`,
                       `glpi_profiles_users`.`is_recursive`,
                       `glpi_profiles_users`.`is_dynamic`,
                       `glpi_entities`.`completename`,
                       `glpi_profiles_users`.`entities_id`
                FROM `glpi_profiles_users`
                LEFT JOIN `glpi_profiles`
                     ON (`glpi_profiles_users`.`profiles_id` = `glpi_profiles`.`id`)
                LEFT JOIN `glpi_entities`
                     ON (`glpi_profiles_users`.`entities_id` = `glpi_entities`.`id`)
                WHERE `glpi_profiles_users`.`users_id` = '$ID'
                ORDER BY `glpi_profiles`.`name`, `glpi_entities`.`completename`";
      $result = $DB->query($query);
      $num    = $DB->numrows($result);

      echo "<div class='spaced'>";
      Html::openMassiveActionsForm('mass'.__CLASS__.$rand);

      if ($canedit && $num) {
         $paramsma = array('num_displayed' => $num);
         Html::showMassiveActions(__CLASS__, $paramsma);
      }

      if ($num > 0) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr>";
         if ($canedit) {
            echo "<th>";
            Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
         }
         echo "<th>"._n('Entity', 'Entities', 2)."</th>";
         echo "<th>".sprintf(__('%1$s (%2$s)'), self::getTypeName(2), __('D=Dynamic, R=Recursive'));
         echo "</th></tr>";
         while ($data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               if (in_array($data["entities_id"], $_SESSION['glpiactiveentities'])) {
                  echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";
            }
            echo "<td>";

            $link = $data["completename"];
            if ($_SESSION["glpiis_ids_visible"]) {
               $link = sprintf(__('%1$s (%2$s)'), $link, $data["entities_id"]);
            }

            if ($canshowentity) {
               echo "<a href='".Toolbox::getItemTypeFormURL('Entity')."?id=".$data["entities_id"]."'>";
            }
            echo $link.($canshowentity ? "</a>" : '');
            echo "</td>";

            if (Session::haveRight('profile', 'r')) {
               $entname = "<a href='".Toolbox::getItemTypeFormURL('Profile')."?id=".$data["id"]."'>".
                            $data["name"]."</a>";
            } else {
               $entname =  $data["name"];
            }

            if ($data["is_dynamic"] || $data["is_recursive"]) {
               $entname = sprintf(__('%1$s %2$s'), $entname, "<span class='b'>(");
               if ($data["is_dynamic"]) {
                  //TRANS: letter 'D' for Dynamic
                  $entname = sprintf(__('%1$s%2$s'), $entname, __('D'));
               }
               if ($data["is_dynamic"] && $data["is_recursive"]) {
                  $entname = sprintf(__('%1$s%2$s'), $entname, ", ");
               }
               if ($data["is_recursive"]) {
                  //TRANS: letter 'R' for Recursive
                  $entname = sprintf(__('%1$s%2$s'), $entname, __('R'));
               }
               $entname = sprintf(__('%1$s%2$s'), $entname, ")</span>");
            }
             echo "<td>".$entname."</td>";
         echo "</tr>";
         }
         echo "</table>";
      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table>\n";
      }

      if ($canedit && $num) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $paramsma);
      }
      Html::closeForm();
      echo "</div>";
   }


   /**
    * Show users of an entity
    *
    * @param $entity Entity object
   **/
   static function showForEntity(Entity $entity) {
      global $DB;


      $ID = $entity->getField('id');
      if (!$entity->can($ID, "r")) {
         return false;
      }

      $canedit     = $entity->can($ID,"w");
      $canshowuser = Session::haveRight("user", "r");
      $nb_per_line = 3;
      $rand        = mt_rand();

      if ($canedit) {
         echo "<form name='entityuser_form$rand' id='entityuser_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         $headerspan = $nb_per_line*2;
      } else {
         $headerspan = $nb_per_line;
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Add an authorization to a user')."</tr>";
         echo "<tr><td class='tab_bg_2 center'>".__('User')."&nbsp;";
         echo "<input type='hidden' name='entities_id' value='$ID'>";
         User::dropdown(array('right' => 'all'));
         echo "</td><td class='tab_bg_2 center'>".self::getTypeName(1)."</td><td>";
         Profile::dropdownUnder(array('value' => Profile::getDefault()));
         echo "</td><td class='tab_bg_2 center'>".__('Recursive')."</td><td>";
         Dropdown::showYesNo("is_recursive", 0);
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table></div>";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='$headerspan'>".sprintf(__('%1$s (%2$s)'), _n('User', 'Users', 2),
                                                    __('D=Dynamic, R=Recursive'))."</th></tr>";

      $query = "SELECT DISTINCT `glpi_profiles`.`id`, `glpi_profiles`.`name`
                FROM `glpi_profiles_users`
                LEFT JOIN `glpi_profiles`
                     ON (`glpi_profiles_users`.`profiles_id` = `glpi_profiles`.`id`)
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                WHERE `glpi_profiles_users`.`entities_id` = '$ID'
                     AND `glpi_users`.`is_deleted` = '0'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {

         Session::initNavigateListItems('User',
         //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $entity->getTypeName(1),
                                                $entity->getName()));

         while ($data = $DB->fetch_assoc($result)) {
            echo "<tr><th colspan='$headerspan'>".sprintf(__('%1$s: %2$s'), __('Profile'),
                                                          $data["name"]);
            echo "</th></tr>";

            $query = "SELECT `glpi_users`.*,
                             `glpi_profiles_users`.`id` AS linkID,
                             `glpi_profiles_users`.`is_recursive`,
                             `glpi_profiles_users`.`is_dynamic`
                      FROM `glpi_profiles_users`
                      LEFT JOIN `glpi_users`
                           ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                      WHERE `glpi_profiles_users`.`entities_id` = '$ID'
                            AND `glpi_users`.`is_deleted` = '0'
                            AND `glpi_profiles_users`.`profiles_id` = '".$data['id']."'
                      ORDER BY `glpi_profiles_users`.`profiles_id`,
                               `glpi_users`.`name`,
                               `glpi_users`.`realname`,
                               `glpi_users`.`firstname`";

            $result2 = $DB->query($query);
            if ($DB->numrows($result2) > 0) {
               $i = 0;

               while ($data2 = $DB->fetch_assoc($result2)) {
                  Session::addToNavigateListItems('User',$data2["id"]);

                  if (($i%$nb_per_line) == 0) {
                     if ($i  !=0) {
                        echo "</tr>";
                     }
                     echo "<tr class='tab_bg_1'>";
                  }
                  if ($canedit) {
                     echo "<td width='10'>";
                     echo "<input type='checkbox' name='item[".$data2["linkID"]."]' value='1'>";
                     echo "</td>";
                  }

                  $username = formatUserName($data2["id"], $data2["name"], $data2["realname"],
                                             $data2["firstname"], $canshowuser);

                  if ($data2["is_dynamic"] || $data2["is_recursive"]) {
                     $username = sprintf(__('%1$s %2$s'), $username, "<span class='b'>(");
                     if ($data2["is_dynamic"]) {
                        $username = sprintf(__('%1$s%2$s'), $username, __('D'));
                     }
                     if ($data2["is_dynamic"] && $data2["is_recursive"]) {
                        $username = sprintf(__('%1$s%2$s'), $username, ", ");
                     }
                     if ($data2["is_recursive"]) {
                        $username = sprintf(__('%1$s%2$s'), $username, __('R'));
                     }
                     $username = sprintf(__('%1$s%2$s'), $username, ")</span>");
                  }
                  echo "<td>".$username."</td>";
                  $i++;
               }

               while (($i%$nb_per_line) != 0) {
                  echo "<td>&nbsp;</td>";
                  if ($canedit) {
                     echo "<td>&nbsp;</td>";
                  }
                  $i++;
               }
               echo "</tr>";

            } else {
               echo "<tr colspan='$headerspan'>".__('Item not found')."</tr>";
            }
         }
      }
      echo "</table>";

      if ($canedit) {
         Html::openArrowMassives("entityuser_form$rand", true);
         Html::closeArrowMassives(array('delete' => __('Delete')));
         Html::closeForm();
      }
      echo "</div>";

   }


   /**
    * Show the User having a profile, in allowed Entity
    *
    * @param $prof Profile object
   **/
   static function showForProfile(Profile $prof) {
      global $DB;

      $ID      = $prof->fields['id'];
      $canedit = Session::haveRight("user", "w");

      if (!$prof->can($ID,'r')) {
         return false;
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'><tr>";
      echo "<th>".sprintf(__('%1$s: %2$s'), __('Profile'), $prof->fields["name"])."</th></tr>\n";

      echo "<tr><th colspan='2'>".sprintf(__('%1$s (%2$s)'), _n('User', 'Users', 2),
                                          __('D=Dynamic, R=Recursive'))."</th></tr>";
      echo "</table>\n";

      $query = "SELECT `glpi_users`.*,
                       `glpi_profiles_users`.`entities_id` AS entity,
                       `glpi_profiles_users`.`id` AS linkID,
                       `glpi_profiles_users`.`is_dynamic`,
                       `glpi_profiles_users`.`is_recursive`
                FROM `glpi_profiles_users`
                LEFT JOIN `glpi_entities`
                     ON (`glpi_entities`.`id`=`glpi_profiles_users`.`entities_id`)
                LEFT JOIN `glpi_users`
                     ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                WHERE `glpi_profiles_users`.`profiles_id` = '$ID'
                      AND `glpi_users`.`is_deleted` = '0' ".
                      getEntitiesRestrictRequest("AND", "glpi_profiles_users", 'entities_id',
                                                 $_SESSION['glpiactiveentities'], true)."
                ORDER BY `glpi_entities`.`completename`";

      echo "<table class='tab_cadre_fixe'>";

      $i              = 0;
      $nb_per_line    = 3;
      $rand           = mt_rand(); // Just to avoid IDE warning
      $canedit_entity = false;

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            $temp = -1;

            while ($data = $DB->fetch_assoc($result)) {
               if ($data["entity"] != $temp) {

                  while (($i%$nb_per_line) != 0) {
                     if ($canedit_entity) {
                        echo "<td width='10'>&nbsp;</td>";
                     }
                     echo "<td class='tab_bg_1'>&nbsp;</td>\n";
                     $i++;
                  }

                  if ($i != 0) {
                     echo "</table>";

                     if ($canedit_entity) {
                        Html::openArrowMassives("profileuser_form".$rand."_$temp", true);
                        Entity::dropdown(array('entity' => $_SESSION['glpiactiveentities']));
                        echo "&nbsp;<input type='submit' name='moveentity' value='".
                              _sx('button','Move')."' class='submit'>&nbsp;";
                        Html::closeArrowMassives(array('delete' => __('Delete')));
                     }
                     echo "</div>";
                     Html::closeForm();
                     echo "</td></tr>\n";
                  }

                  // New entity
                  $i              = 0;
                  $temp           = $data["entity"];
                  $canedit_entity = $canedit && in_array($temp, $_SESSION['glpiactiveentities']);
                  $rand           = mt_rand();
                  echo "<tr class='tab_bg_2'>";
                  echo "<td>";
                  echo "<a href=\"javascript:showHideDiv('entity$temp$rand','imgcat$temp', '".
                         GLPI_ROOT."/pics/folder.png','".GLPI_ROOT."/pics/folder-open.png');\">";
                  echo "<img alt='' name='imgcat$temp' src=\"".GLPI_ROOT."/pics/folder.png\">&nbsp;";
                  echo "<span class='b'>".Dropdown::getDropdownName('glpi_entities', $data["entity"]).
                        "</span>";
                  echo "</a></td></tr>\n";

                  echo "<tr class='tab_bg_2'><td>";
                  echo "<form name='profileuser_form".$rand."_$temp' id='profileuser_form".$rand.
                         "_$temp' method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
                  echo "<div class='center' id='entity$temp$rand' style='display:none;'>\n";
                  echo "<table class='tab_cadre_fixe'>\n";
               }

               if (($i%$nb_per_line) == 0) {
                  if ($i != 0) {
                     echo "</tr>\n";
                  }
                  echo "<tr class='tab_bg_1'>\n";
                  $i = 0;
               }

               if ($canedit_entity) {
                  echo "<td width='10'>";
                  $sel = "";

                  if (isset($_GET["select"]) && ($_GET["select"] == "all")) {
                     $sel = "checked";
                  }

                  echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
                  echo "</td>";
               }

               $username = formatUserName($data["id"], $data["name"], $data["realname"],
                                          $data["firstname"], 1);

               if ($data["is_dynamic"] || $data["is_recursive"]) {
                  $username = sprintf(__('%1$s %2$s'), $username, "<span class='b'>(");
                  if ($data["is_dynamic"]) {
                     $username = sprintf(__('%1$s%2$s'), $username, __('D'));
                  }
                  if ($data["is_dynamic"] && $data["is_recursive"]) {
                     $username = sprintf(__('%1$s%2$s'), $username, ", ");
                  }
                  if ($data["is_recursive"]) {
                     $username = sprintf(__('%1$s%2$s'), $username, __('R'));
                  }
                  $username = sprintf(__('%1$s%2$s'), $username, ")</span>");
               }
               echo "<td class='tab_bg_1'>". $username."</td>\n";
               $i++;
            }

            if (($i%$nb_per_line) != 0) {
               while (($i%$nb_per_line) != 0) {
                  if ($canedit_entity) {
                     echo "<td width='10'>&nbsp;</td>";
                  }
                  echo "<td class='tab_bg_1'>".Dropdown::EMPTY_VALUE."</td>";
                  $i++;
               }
            }

            if ($i != 0) {
               echo "</table>\n";
               if ($canedit_entity) {
                  Html::openArrowMassives("profileuser_form".$rand."_$temp", true);
                  Entity::dropdown(array('entity' => $_SESSION['glpiactiveentities']));
                  echo "&nbsp;<input type='submit' name='moveentity' value='"._sx('button','Move').
                               "' class='submit'>&nbsp;";
                  Html::closeArrowMassives(array('delete' => __('Delete')));
               }
               echo "</div>";
               Html::closeForm();
               echo "</td></tr>\n";
            }

         } else {
            echo "<tr class='tab_bg_2'><td class='tab_bg_1 center'>".__('No user found').
                 "</td></tr>\n";
         }
      }
      echo "</table></div>\n";
   }


   /**
    * Get entities for which a user have a right
    *
    * @param $user_ID         user ID
    * @param $is_recursive    check also using recursive rights (true by default)
    * @param $default_first   user default entity first (false by default)
    *
    * @return array of entities ID
   **/
   static function getUserEntities($user_ID, $is_recursive=true, $default_first=false) {
      global $DB;

      $query = "SELECT DISTINCT `entities_id`, `is_recursive`
                FROM `glpi_profiles_users`
                WHERE `users_id` = '$user_ID'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         $entities = array();

         while ($data = $DB->fetch_assoc($result)) {
            if ($data['is_recursive'] && $is_recursive) {
               $tab      = getSonsOf('glpi_entities', $data['entities_id']);
               $entities = array_merge($tab, $entities);
            } else {
               $entities[] = $data['entities_id'];
            }
         }

         // Set default user entity at the begin
         if ($default_first) {
            $user = new User();
            if ($user->getFromDB($user_ID)) {
               $ent = $user->getField('entities_id');
               if (in_array($ent, $entities)) {
                  array_unshift($entities, $ent);
               }
            }
         }

         return array_unique($entities);
      }

      return array();
   }


   /**
    * Get user profiles (no entity association, use sqlfilter if needed)
    *
    * @param $user_ID            user ID
    * @param $sqlfilter  string  additional filter (must start with AND) (default '')
    *
    * @return array of the IDs of the profiles
   **/
   static function getUserProfiles($user_ID, $sqlfilter='') {
      global $DB;

      $query = "SELECT DISTINCT `profiles_id`
                FROM `glpi_profiles_users`
                WHERE `users_id` = '$user_ID'
                      $sqlfilter";
      $result = $DB->query($query);

      $profiles = array();
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $profiles[$data['profiles_id']] = $data['profiles_id'];
         }
      }

      return $profiles;
   }


   /**
    * retrieve the entities allowed to a user for a profile
    *
    * @param $users_id     Integer  ID of the user
    * @param $profiles_id  Integer  ID of the profile
    * @param $child        Boolean  when true, include child entity when recursive right
    *                               (false by default)
    *
    * @return Array of entity ID
   **/
   static function getEntitiesForProfileByUser($users_id, $profiles_id, $child=false) {
      global $DB;

      $query = "SELECT `entities_id`, `is_recursive`
                FROM `glpi_profiles_users`
                WHERE `users_id` = '$users_id'
                      AND `profiles_id` = '$profiles_id'";

      $entities = array();
      foreach ($DB->request($query) as $data) {
         if ($child
             && $data['is_recursive']) {
            foreach (getSonsOf('glpi_entities', $data['entities_id']) as $id) {
               $entities[$id] = $id;
            }
         } else {
            $entities[$data['entities_id']] = $data['entities_id'];
         }
      }
      return $entities;
   }


   /**
    * Get entities for which a user have a right
    *
    * @param $user_ID         user ID
    * @param $only_dynamic    get only recursive rights (false by default)
    *
    * @return array of entities ID
   **/
   static function getForUser($user_ID, $only_dynamic=false) {
      global $DB;

      $condition = "`users_id` = '$user_ID'";

      if ($only_dynamic) {
         $condition .= " AND `is_dynamic` = 1";
      }

      return getAllDatasFromTable('glpi_profiles_users', $condition);
   }


   /**
    * @param $user_ID
    * @param $profile_id
   **/
   static function haveUniqueRight($user_ID, $profile_id) {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_profiles_users`
                WHERE `users_id` = '$user_ID'
                      AND `profiles_id` = '$profile_id'";
      $result = $DB->query($query);

      return $DB->result($result, 0, 'cpt');
   }


   /**
    * @param $user_ID
    * @param $only_dynamic    (false by default)
   **/
   static function deleteRights($user_ID, $only_dynamic=false) {

      $crit['users_id'] = $user_ID;

      if ($only_dynamic) {
         $crit['is_dynamic'] = '1';
      }

      $obj = new self();
      $obj->deleteByCriteria($crit);
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'is_dynamic';
      $tab[3]['name']            = __('Dynamic');
      $tab[3]['datatype']        = 'bool';
      $tab[3]['massiveaction']   = false;

      $tab[4]['table']           = 'glpi_profiles';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = self::getTypeName(1);
      $tab[4]['datatype']        = 'dropdown';
      $tab[4]['massiveaction']   = false;

      $tab[5]['table']           = 'glpi_users';
      $tab[5]['field']           = 'name';
      $tab[5]['name']            = __('User');
      $tab[5]['massiveaction']   = false;
      $tab[5]['datatype']        = 'dropdown';
      $tab[5]['right']           = 'all';


      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';
      $tab[86]['massiveaction']   = false;

      return $tab;
   }


   static function getTypeName($nb=0) {
      return _n('Profile', 'Profiles', $nb);
   }


   /**
    * @see inc/CommonDBTM::getName()
   **/
   function getName($with_comment=0) {

            //TRANS: D for Dynamic
      $dyn = ", ".__('D');
            //TRANS: R for Recursive
      $rec = ", ".__('R');
      return Dropdown::getDropdownName('glpi_profiles', $this->fields['profiles_id']).', '.
             Dropdown::getDropdownName('glpi_entities', $this->fields['entities_id']).
             (isset($this->fields['is_dynamic']) && $this->fields['is_dynamic'] ? $dyn : '').
             (isset($this->fields['is_recursive']) && $this->fields['is_recursive'] ? $rec : '');
   }


   function post_addItem() {

      if (isset($this->input['_no_history'])) {
         return false;
      }
      $changes[0] = '0';
      $changes[1] = '';
      $changes[2] = addslashes($this->getName());
      Log::history($this->fields['users_id'], 'User', $changes, get_class($this),
                   Log::HISTORY_ADD_SUBITEM);
   }


   function post_deleteFromDB() {

      if (isset($this->input['_no_history'])) {
         return false;
      }
      $changes[0] = '0';
      $changes[1] = addslashes($this->getName());
      $changes[2] = '';
      Log::history($this->fields['users_id'], 'User', $changes, get_class($this),
                   Log::HISTORY_DELETE_SUBITEM);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Entity' :
               if (Session::haveRight('user', 'r')) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     // Keep this ? (only approx. as count deleted users)
                     $nb = countElementsInTable($this->getTable(),
                                                "entities_id = '".$item->getID()."'");
                  }
                  return self::createTabEntry(_n('User', 'Users', 2), $nb);
               }
               break;

            case 'Profile' :
               if (Session::haveRight('user', 'r')) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     // Keep this ? (only approx. as count deleted users)
                     $nb = countElementsInTable($this->getTable(),
                                                "profiles_id = '".$item->getID()."'".
                                                getEntitiesRestrictRequest('AND',
                                                                           'glpi_profiles_users',
                                                                           'entities_id',
                                                                           $_SESSION['glpiactiveentities'],
                                                                           true));
                  }
                  return self::createTabEntry(_n('User', 'Users', 2), $nb);
               }
               break;

            case 'User' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             "users_id = '".$item->getID()."'");
               }
               return self::createTabEntry(_n('Authorization','Authorizations',2), $nb);

         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Entity' :
            self::showForEntity($item);
            break;

         case 'Profile' :
            self::showForProfile($item);
            break;

         case 'User' :
            self::showForUser($item);
            break;
      }
      return true;
   }

}
?>
