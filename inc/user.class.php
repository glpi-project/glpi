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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// And Marco Gaiarin for ldap features

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class User extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
   public $history_blacklist = array('last_login','date_mod');

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][34];
   }

   function canCreate() {
      if (haveRight('user', 'w')             // Write right
          && (haveAccessToEntity(0)          // Access to root entity (required when no default profile)
              || Profile::getDefault()>0)) { // Default profile will be given in current entity
         return true;
      }
      return false;
   }

   function canUpdate() {
      return haveRight('user', 'w');
   }

   function canDelete() {
      return haveRight('user', 'w');
   }

   function canView() {
      return haveRight('user', 'r');
   }

   function canViewItem() {
      $entities = Profile_User::getUserEntities($this->fields['id'],true);
      if (isViewAllEntities() || haveAccessToOneOfEntities($entities)) {
         return true;
      }
      return false;
   }

   function canCreateItem() {
      // New user : no entity defined
      return true;
   }

   function canUpdateItem() {
      $entities = Profile_User::getUserEntities($this->fields['id'],true);
      if (isViewAllEntities() || haveAccessToOneOfEntities($entities)) {
         return true;
      }
      return false;
   }

   function canDeleteItem() {
      $entities = Profile_User::getUserEntities($this->fields['id'],true);
      if (isViewAllEntities() || haveAccessToAllOfEntities($entities)) {
         return true;
      }
      return false;
   }

   function isEntityAssign() {
      // glpi_users.entities_id is only a pref.
      return false;
   }

   /**
   * Compute preferences for the current user mixing config and user data
   **/
   function computePreferences () {
      global $CFG_GLPI;

      if (isset($this->fields['id'])) {
         foreach ($CFG_GLPI['user_pref_field'] as $f) {
            if (is_null($this->fields[$f])) {
               $this->fields[$f] = $CFG_GLPI[$f];
            }
         }
      }
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      // No add process
      if ($this->fields['id'] > 0) {
         $ong[1] = $LANG['Menu'][35]; // principal
         $ong[4] = $LANG['Menu'][36];
         $ong[2] = $LANG['common'][1]; // materiel

         if (haveRight("show_all_ticket", "1")) {
            $ong[3] = $LANG['title'][28]; // tickets
         }
         if (haveRight("reservation_central", "r")) {
            $ong[11] = $LANG['Menu'][17];
         }
         if (haveRight("user_authtype", "w")) {
            $ong[12] = $LANG['ldap'][12];
         }
         $ong[13]=$LANG['title'][38];
      } else { // New item
         $ong[1] = $LANG['title'][26];
      }
      return $ong;
   }

   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields["is_active"] = 1;

      if (isset ($CFG_GLPI["language"])) {
         $this->fields['language'] = $CFG_GLPI["language"];
      } else {
         $this->fields['language'] = "en_GB";
      }
   }

   function pre_deleteItem() {
      global $LANG,$DB;

      $entities = Profile_User::getUserEntities($this->fields["id"]);
      $view_all = isViewAllEntities();
      // Have right on all entities ?
      $all = true;
      if (!$view_all) {
         foreach ($entities as $ent) {
            if (!haveAccessToEntity($ent)) {
               $all = false;
            }
         }
      }
      if ($all) { // Mark as deleted
         return true;
      } else { // only delete profile
         foreach ($entities as $ent) {
            if (haveAccessToEntity($ent)) {
               $all = false;
               $query = "DELETE
                         FROM `glpi_profiles_users`
                         WHERE `users_id` = '".$this->fields["id"]."'
                               AND `entities_id` = '$ent'";
               $DB->query($query);
            }
         }
         return false;
      }
   }

   function cleanDBonMarkDeleted() {
   }

   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_profiles_users`
                WHERE `users_id` = '".$this->fields['id']."'";
      $DB->query($query);

      $query = "DELETE
                FROM `glpi_groups_users`
                WHERE `users_id` = '".$this->fields['id']."'";
      $DB->query($query);

      if ($this->fields['id'] > 0) { // Security
         $query = "DELETE
                   FROM `glpi_displaypreferences`
                   WHERE `users_id` = '".$this->fields['id']."'";
         $DB->query($query);

         $query = "DELETE
                   FROM `glpi_bookmarks_users`
                   WHERE `users_id` = '".$this->fields['id']."'";
         $DB->query($query);
      }

      // Delete private reminder
      $query = "DELETE
                FROM `glpi_reminders`
                WHERE `users_id` = '".$this->fields['id']."'
                      AND `is_private` = '1'";
      $DB->query($query);

      // Set no user to public reminder
      $query = "UPDATE `glpi_reminders`
                SET `users_id` = '0'
                WHERE `users_id` = '".$this->fields['id']."'";
      $DB->query($query);

      // Delete private bookmark
      $query = "DELETE
                FROM `glpi_bookmarks`
                WHERE `users_id` = '".$this->fields['id']."'
                      AND `is_private` = '1'";
      $DB->query($query);

      // Set no user to public bookmark
      $query = "UPDATE `glpi_bookmarks`
                SET `users_id` = '0'
                WHERE `users_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }


   /**
    * Retrieve an item from the database using its login
    *
    *@param $name login of the user
    *@return true if succeed else false
    *
   **/
   function getFromDBbyName($name) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `name` = '$name'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
      }
      return false;
   }


   function prepareInputForAdd($input) {
      global $DB,$LANG;

      // Check if user does not exists
      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `name` = '".$input['name']."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)>0) {
         addMessageAfterRedirect($LANG['setup'][606],false,ERROR);
         return false;
      }

      if (isset ($input["password2"])) {
         if (empty ($input["password"])) {
            unset ($input["password"]);
         } else {

            if ($input["password"] == $input["password2"]) {
               $input["password"] = sha1(unclean_cross_side_scripting_deep(stripslashes($input["password"])));
               unset($input["password2"]);
            } else {
               addMessageAfterRedirect($LANG['setup'][21],false,ERROR);
               return false;
            }
         }
      }
      if (isset ($input["_extauth"])) {
         $input["password"] = "";
      }
      // change email_form to email (not to have a problem with preselected email)
      if (isset ($input["email_form"])) {
         $input["email"] = $input["email_form"];
         unset ($input["email_form"]);
      }

      // Force DB default values : not really needed
      if (!isset($input["is_active"])) {
         $input["is_active"] = 1;
      }

      if (!isset($input["is_deleted"])) {
         $input["is_deleted"] = 0;
      }

      if (!isset($input["entities_id"])) {
         $input["entities_id"] = 0;
      }

      if (!isset($input["profiles_id"])) {
         $input["profiles_id"] = 0;
      }

      if (!isset($input["authtype"])) {
         $input["authtype"] = 0;
      }

      return $input;
   }


   function post_addItem() {

      $this->syncLdapGroups();
      $rulesplayed = $this->applyRightRules();

      // Add default profile
      if (!$rulesplayed) {
         $profile = Profile::getDefault();
         if ($profile) {
            if (isset($this->input["_entities_id"])) {
               // entities_id (user's pref) always set in prepareInputForAdd
               // use _entities_id for default right (but this seems not used)
               $affectation["entities_id"] = $this->input["_entities_id"];
            } else if (isset($_SESSION['glpiactive_entity'])) {
               $affectation["entities_id"] = $_SESSION['glpiactive_entity'];
            } else {
               $affectation["entities_id"] = 0;
            }
            $affectation["profiles_id"]  = $profile;
            $affectation["users_id"]     = $this->fields["id"];
            $affectation["is_recursive"] = 0;
            // Default right as dynamic. If dynamic rights are set it will disappear.
            $affectation["is_dynamic"] = 1;
            $right = new Profile_User();
            $right->add($affectation);
         }
      }
   }


   function prepareInputForUpdate($input) {
      global $LANG,$CFG_GLPI;

      if (isset($input["password2"])) {
         // Empty : do not update
         if (empty($input["password"])) {
            unset($input["password"]);
         } else {

            if ($input["password"]==$input["password2"]) {
               // Check right : my password of user with lesser rights
               if (isset($input['id'])
                   && ($input['id'] == getLoginUserID()
                       || $this->currentUserHaveMoreRightThan($input['id']) )) {
                  $input["password"] = sha1(unclean_cross_side_scripting_deep(stripslashes($input["password"])));
               } else {
                  unset($input["password"]);
               }
               unset($input["password2"]);

            } else {
             addMessageAfterRedirect($LANG['setup'][21],false,ERROR);
               return false;
            }
         }

      } else if (isset($input["password"])) { // From login
         unset($input["password"]);
      }

      // change email_form to email (not to have a problem with preselected email)
      if (isset ($input["email_form"])) {
         $input["email"] = $input["email_form"];
         unset ($input["email_form"]);
      }

      // Update User in the database
      if (!isset ($input["id"]) && isset ($input["name"])) {
         if ($this->getFromDBbyName($input["name"])) {
            $input["id"] = $this->fields["id"];
         }
      }

      if (isset ($input["entities_id"])
          && getLoginUserID() === $input['id']) {

         $_SESSION["glpidefault_entity"] = $input["entities_id"];
      }

      // Manage preferences fields
      if (getLoginUserID() === $input['id']) {
         if (isset($input['use_mode'])
             && $_SESSION['glpi_use_mode'] !=  $input['use_mode']) {
            $_SESSION['glpi_use_mode']=$input['use_mode'];
            //loadLanguage();
         }

         foreach ($CFG_GLPI['user_pref_field'] as $f) {
            if (isset($input[$f])) {
               if ($_SESSION["glpi$f"] != $input[$f]) {
                  $_SESSION["glpi$f"] = $input[$f];
               }
               if ($input[$f] == $CFG_GLPI[$f]) {
                  $input[$f]="NULL";
               }
            }
         }
      }

      return $input;
   }


   function post_updateItem($history=1) {

      $this->syncLdapGroups();
      $this->applyRightRules();
   }


   // SPECIFIC FUNCTIONS
   /**
    * Apply rules to determine dynamic rights of the user
    *
    *@return boolean : true if we play the Rule Engine
   **/
   function applyRightRules() {
      global $DB;

      if (isset($this->fields["authtype"])
          && ($this->fields["authtype"] == Auth::LDAP
              || $this->fields["authtype"] == Auth::MAIL
              || Auth::isAlternateAuthWithLdap($this->fields["authtype"]))) {

         if (isset($this->fields["id"])
             && $this->fields["id"] > 0
             && isset($this->input["_ldap_rules"])
             && count($this->input["_ldap_rules"])) {

            //and add/update/delete only if it's necessary !
            if (isset($this->input["_ldap_rules"]["rules_entities_rights"])) {
               $entities_rules = $this->input["_ldap_rules"]["rules_entities_rights"];
            } else {
               $entities_rules = array();
            }

            if (isset($this->input["_ldap_rules"]["rules_entities"])) {
               $entities = $this->input["_ldap_rules"]["rules_entities"];
            } else {
               $entities = array();
            }

            if (isset($this->input["_ldap_rules"]["rules_rights"])) {
               $rights = $this->input["_ldap_rules"]["rules_rights"];
            } else {
               $rights = array();
            }

            $dynamic_profiles = Profile_User::getForUser($this->fields["id"],true);
            $retrieved_dynamic_profiles=array();

            //For each affectation -> write it in DB
            foreach($entities_rules as $entity) {
               //Multiple entities assignation
               if (is_array($entity[0])) {
                  foreach ($entity[0] as $tmp => $ent) {
                     $affectation['entities_id']  = $ent[0];
                     $affectation['profiles_id']  = $entity[1];
                     $affectation['is_recursive'] = $entity[2];
                     $affectation['users_id']     = $this->fields['id'];
                     $affectation['is_dynamic']   = 1;

                     $retrieved_dynamic_profiles[] = $affectation;
                  }
               } else {
                  $affectation['entities_id']  = $entity[0];
                  $affectation['profiles_id']  = $entity[1];
                  $affectation['is_recursive'] = $entity[2];
                  $affectation['users_id']     = $this->fields['id'];
                  $affectation['is_dynamic']   = 1;

                  $retrieved_dynamic_profiles[] = $affectation;
               }
            }

            if (count($entities)>0 && count($rights)==0) {
               $sql_default_profile = "SELECT `id`
                                       FROM `glpi_profiles`
                                       WHERE `is_default` = '1'";
               $result = $DB->query($sql_default_profile);

               if ($DB->numrows($result)) {
                  $rights[] = $DB->result($result,0,0);
               }
            }

            if (count($rights)>0 && count($entities)>0) {
               foreach($rights as $right) {
                  foreach($entities as $entity_tab) {
                     foreach ($entity_tab as $entity) {
                        $affectation['entities_id']  = $entity[0];
                        $affectation['profiles_id']  = $right;
                        $affectation['users_id']     = $this->fields['id'];
                        $affectation['is_recursive'] = $entity[1];
                        $affectation['is_dynamic']   = 1;

                        $retrieved_dynamic_profiles[] = $affectation;
                     }
                  }
               }
            }

            // Compare retrived profiles to existing ones : clean arrays to do purge and add
            if (count($retrieved_dynamic_profiles)) {
               foreach ($retrieved_dynamic_profiles as $keyretr => $retr_profile) {
                  $found = false;
                  foreach ($dynamic_profiles as $keydb => $db_profile) {
                     // Found existing profile : unset values in array
                     if (!$found
                         && $db_profile['entities_id'] == $retr_profile['entities_id']
                         && $db_profile['profiles_id'] == $retr_profile['profiles_id']
                         && $db_profile['is_recursive'] == $retr_profile['is_recursive']) {

                        unset($retrieved_dynamic_profiles[$keyretr]);
                        unset($dynamic_profiles[$keydb]);
                     }
                  }
               }
            }

            // Add new dynamic profiles
            if (count($retrieved_dynamic_profiles)) {
               $right = new Profile_User();
               foreach ($retrieved_dynamic_profiles as $keyretr => $retr_profile) {
                  $right->add($retr_profile);
               }
            }
            // Delete old dynamic profiles
            if (count($dynamic_profiles)) {
               $right = new Profile_User();
               foreach ($dynamic_profiles as $keydb => $db_profile) {
                  $right->delete($db_profile);
               }
            }

            //Unset all the temporary tables
            unset($this->input["_ldap_rules"]);

            return true;
         }
      }
      return false;
   }


   /**
   * Synchronise LDAP group of the user
   *
   **/
   function syncLdapGroups() {
      global $DB;

      // input["_groups"] not set when update from user.form or preference
      if (isset($this->fields["authtype"])
          && isset($this->input["_groups"])
          && ($this->fields["authtype"] == Auth::LDAP
              || Auth::isAlternateAuthWithLdap($this->fields['authtype']))) {

         if (isset ($this->fields["id"]) && $this->fields["id"]>0) {
            $authtype = Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);

            if (count($authtype)) {
               // Clean groups
               $this->input["_groups"] = array_unique ($this->input["_groups"]);

               // Delete not available groups like to LDAP
               $query = "SELECT `glpi_groups_users`.`id`,
                                `glpi_groups_users`.`groups_id`,
                                `glpi_groups_users`.`is_dynamic`
                         FROM `glpi_groups_users`
                         LEFT JOIN `glpi_groups`
                              ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                         WHERE `glpi_groups_users`.`users_id` = '" . $this->fields["id"] . "'";

               $result = $DB->query($query);
               $groupuser = new Group_User();
               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     if (in_array($data["groups_id"], $this->input["_groups"])) {
                        // Delete found item in order not to add it again
                        unset($this->input["_groups"][array_search($data["groups_id"],
                              $this->input["_groups"])]);
                     } else if ($data['is_dynamic']) {
                        $groupuser->delete(array('id' => $data["id"]));
                     }
                  }
               }

               //If the user needs to be added to one group or more
               if (count($this->input["_groups"]) > 0) {
                  foreach ($this->input["_groups"] as $group) {
                     $groupuser->add(array('users_id'   => $this->fields["id"],
                                           'groups_id'  => $group,
                                           'is_dynamic' => 1));
                  }
                  unset ($this->input["_groups"]);
               }
            }
         }
      }
   }


   /**
    * Get the name of the current user
    * @param $with_comment add comments to name (not used for this type)
    * @return string containing name of the user
   **/
   function getName($with_comment=0) {

      return formatUserName($this->fields["id"],
                            $this->fields["name"],
                            (isset($this->fields["realname"]) ? $this->fields["realname"] : ''),
                            (isset($this->fields["firstname"]) ? $this->fields["firstname"] : ''));
   }


   /**
    * Function that try to load from LDAP the user membership
    * by searching in the attribute of the User
    *
    * @param $ldap_connection ldap connection descriptor
    * @param $ldap_method LDAP method
    * @param $userdn Basedn of the user
    * @param $login User Login
    *
    * @return String : basedn of the user / false if not founded
    */
   private function getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login) {
      global $DB;

      // Search in DB the ldap_field we need to search for in LDAP
      $query = "SELECT DISTINCT `ldap_field`
                FROM `glpi_groups`
                WHERE `ldap_field` != ''
                ORDER BY `ldap_field`";
      $group_fields = array ();

      foreach ($DB->request($query) as $data) {
         $group_fields[] = utf8_strtolower($data["ldap_field"]);
      }
      if (count($group_fields)) {
         //Need to sort the array because edirectory don't like it!
         sort($group_fields);

         // If the groups must be retrieve from the ldap user object
         $sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $group_fields);
         $v = ldap_get_entries_clean($ldap_connection, $sr);

         for ($i=0 ; $i<count($v['count']) ; $i++) {
            //Try to find is DN in present and needed: if yes, then extract only the OU from it
            if (($ldap_method["group_field"] == 'dn' || in_array('ou',$group_fields))
                && isset($v[$i]['dn'])) {

               $v[$i]['ou'] = array();
               for ($tmp=$v[$i]['dn'] ; count($tmptab=explode(',',$tmp,2))==2 ; $tmp=$tmptab[1]) {
                  $v[$i]['ou'][] = $tmptab[1];
               }

               // Search in DB for group with ldap_group_dn
               if ($ldap_method["group_field"] == 'dn' && count($v[$i]['ou']) >0) {
                  $query = "SELECT `id`
                            FROM `glpi_groups`
                            WHERE `ldap_group_dn`
                                       IN ('".implode("','",addslashes_deep($v[$i]['ou']))."')";

                  foreach ($DB->request($query) as $group) {
                     $this->fields["_groups"][] = $group['id'];
                  }
               }

               // searching with ldap_field='OU' and ldap_value is also possible
               $v[$i]['ou']['count'] = count($v[$i]['ou']);
            }

            // For each attribute retrieve from LDAP, search in the DB
            foreach ($group_fields as $field) {
               if (isset($v[$i][$field])
                   && isset($v[$i][$field]['count'])
                   && $v[$i][$field]['count'] > 0) {

                  unset($v[$i][$field]['count']);
                  $query = "SELECT `id`
                            FROM `glpi_groups`
                            WHERE `ldap_field` = '$field'
                                  AND `ldap_value`
                                       IN ('".implode("','",addslashes_deep($v[$i][$field]))."')";

                  foreach ($DB->request($query) as $group) {
                     $this->fields["_groups"][]=$group['id'];
                  }
               }
            }
         } // for each ldapresult
      } // count($group_fields)
   }


   /**
    * Function that try to load from LDAP the user membership
    * by searching in the attribute of the Groups
    *
    * @param $ldap_connection ldap connection descriptor
    * @param $ldap_method LDAP method
    * @param $userdn Basedn of the user
    * @param $login User Login
    *
    * @return nothing : false if not applicable
    */
   private function getFromLDAPGroupDiscret($ldap_connection, $ldap_method, $userdn, $login) {
      global $DB;

      // No group_member_field : unable to get group
      if (empty($ldap_method["group_member_field"])) {
         return false;
      }

      if ($ldap_method["use_dn"]) {
         $user_tmp = $userdn;
      } else {
         //Don't add $ldap_method["login_field"]."=", because sometimes it may not work (for example with posixGroup)
         $user_tmp = $login;
      }

      $v = $this->ldap_get_user_groups($ldap_connection, $ldap_method["basedn"], $user_tmp,
                                       $ldap_method["group_condition"],
                                       $ldap_method["group_member_field"], $ldap_method["use_dn"],
                                       $ldap_method["login_field"]);
      foreach ($v as $result) {
         if (isset($result[$ldap_method["group_member_field"]])
             && is_array($result[$ldap_method["group_member_field"]])
             && count($result[$ldap_method["group_member_field"]])>0) {

            $query = "SELECT `id`
                      FROM `glpi_groups`
                      WHERE `ldap_group_dn`
                        IN ('".implode("','",addslashes_deep($result[$ldap_method["group_member_field"]]))."')";

            foreach ($DB->request($query) as $group) {
               $this->fields["_groups"][]=$group['id'];
            }
         }
      }
      return true;
   }


   /**
    * Function that try to load from LDAP the user information...
    *
    * @param $ldap_connection ldap connection descriptor
    * @param $ldap_method LDAP method
    * @param $userdn Basedn of the user
    * @param $login User Login
    *
    * @return String : basedn of the user / false if not founded
    */
   function getFromLDAP($ldap_connection,$ldap_method, $userdn, $login) {
      global $DB,$CFG_GLPI;

      // we prevent some delay...
      if (empty ($ldap_method["host"])) {
         return false;
      }

      if ($ldap_connection) {
         //Set all the search fields
         $this->fields['password'] = "";

         $fields = AuthLDAP::getSyncFields($ldap_method);

         $fields = array_filter($fields);
         $f = array_values($fields);

         $sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $f);
         $v = ldap_get_entries_clean($ldap_connection, $sr);

         if (!is_array($v) || count($v) == 0 || empty($v[0][$fields['name']][0])) {
            return false;
         }
         foreach ($fields as $k => $e) {
            if (empty($v[0][$e][0])) {
               switch ($k) {
                  case "language" :
                     // Not set value : managed but user class
                     break;

                  case "usertitles_id" :
                  case "usercategories_id" :
                     $this->fields[$k] = 0;
                     break;

                  default :
                     $this->fields[$k] = "";
               }
            } else {
               switch ($k) {
                  case "language" :
                     $language = Config::getLanguage($v[0][$e][0]);
                     if ($language != '') {
                        $this->fields[$k] = $language;
                     }
                     break;

                  case "usertitles_id" :
                     $this->fields[$k] = Dropdown::importExternal('UserTitle',
                                                                  addslashes($v[0][$e][0]));
                     break;

                  case "usercategories_id" :
                     $this->fields[$k] = Dropdown::importExternal('UserCategory',
                                                                  addslashes($v[0][$e][0]));
                     break;

                  default :
                     if (!empty($v[0][$e][0])) {
                        $this->fields[$k] = addslashes($v[0][$e][0]);
                     } else {
                        $this->fields[$k] = "";
                     }
               }
            }
         }

         // Empty array to ensure than syncLdapGroups will be done
         $this->fields["_groups"] = array();

         ///The groups are retrieved by looking into an ldap user object
         if ($ldap_method["group_search_type"] == 0 || $ldap_method["group_search_type"] == 2) {
            $this->getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login);
         }

         ///The groups are retrived by looking into an ldap group object
         if ($ldap_method["group_search_type"] == 1 || $ldap_method["group_search_type"] == 2) {
            $this->getFromLDAPGroupDiscret($ldap_connection, $ldap_method, $userdn, $login);
         }

         ///Only process rules if working on the master database
         if (!$DB->isSlave()) {
            //Instanciate the affectation's rule
            $rule = new RuleRightCollection();

            //Process affectation rules :
            //we don't care about the function's return because all
            //the datas are stored in session temporary
            if (isset($this->fields["_groups"])) {
               $groups = $this->fields["_groups"];
            } else {
               $groups = array();
            }

            $this->fields = $rule->processAllRules($groups, $this->fields,
                                                   array('type'        => 'LDAP',
                                                         'ldap_server' => $ldap_method["id"],
                                                         'connection'  => $ldap_connection,
                                                         'userdn'      => $userdn));

            //If rule  action is ignore import
            if (isset($this->fields["_stop_import"])
               //or use matches no rules & do not import users with no rights
                || (isset($this->fields["_no_rule_matches"])
                    && $this->fields["_no_rule_matches"]
                    && !$CFG_GLPI["use_noright_users_add"])) {
               return false;
            }
            //Hook to retrieve more informations for ldap
            $this->fields = doHookFunction("retrieve_more_data_from_ldap", $this->fields);
         }
         return true;
      }
      return false;

   } // getFromLDAP()


   /**
    * Get all the group a user belongs to
    *
    * @param $ds ldap connection
    * @param $ldap_base_dn Basedn used
    * @param $user_dn Basedn of the user
    * @param $group_condition group search condition
    * @param $group_member_field group field member in a user object
    * @param $use_dn boolean search dn of user ($login_field=$user_dn) in group_member_field
    * @param $login_field string user login field
    *
    * @return String : basedn of the user / false if not founded
    */
   function ldap_get_user_groups($ds, $ldap_base_dn, $user_dn, $group_condition,
                                 $group_member_field, $use_dn, $login_field) {

      $groups = array ();
      $listgroups = array ();

      //Only retrive cn and member attributes from groups
      $attrs = array ('dn');

      if (!$use_dn) {
         $filter = "(& $group_condition (|($group_member_field=$user_dn)($group_member_field=$login_field=$user_dn)))";
      } else {
         $filter = "(& $group_condition ($group_member_field=$user_dn))";
      }

      //Perform the search
      $sr = ldap_search($ds, $ldap_base_dn, $filter, $attrs);

      //Get the result of the search as an array
      $info = ldap_get_entries_clean($ds, $sr);
      //Browse all the groups
      for ($i = 0 ; $i < count($info) ; $i++) {
         //Get the cn of the group and add it to the list of groups
         if (isset ($info[$i]["dn"]) && $info[$i]["dn"] != '') {
            $listgroups[$i] = $info[$i]["dn"];
         }
      }

      //Create an array with the list of groups of the user
      $groups[0][$group_member_field] = $listgroups;
      //Return the groups of the user
      return $groups;
   }


   /**
    * Function that try to load from IMAP the user information...
    *
    * @param $mail_method mail method description array
    * @param $name login of the user
    */
   function getFromIMAP($mail_method, $name) {
      global $DB;

      // we prevent some delay..
      if (empty ($mail_method["host"])) {
         return false;
      }

      // some defaults...
      $this->fields['password'] = "";
      if (strpos($name,"@")) {
         $this->fields['email'] = $name;
      } else {
         $this->fields['email'] = $name . "@" . $mail_method["host"];
      }

      $this->fields['name'] = $name;

      if (!$DB->isSlave()) {
         //Instanciate the affectation's rule
         $rule = new RuleRightCollection();

         //Process affectation rules :
         //we don't care about the function's return because all the datas are stored in session temporary
         if (isset($this->fields["_groups"])) {
            $groups = $this->fields["_groups"];
         } else {
            $groups = array();
         }
         $this->fields = $rule->processAllRules($groups, $this->fields,
                                                array('type'        => 'MAIL',
                                                      'mail_server' => $mail_method["id"],
                                                      'email'       => $this->fields["email"]));
      }
      return true;
   } // getFromIMAP()


   /**
    * Blank passwords field of a user in the DB
    * needed for external auth users
    **/
   function blankPassword() {
      global $DB;

      if (!empty ($this->fields["name"])) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `password` = ''
                   WHERE `name` = '" . $this->fields["name"] . "'";
         $DB->query($query);
      }
   }


   /**
    * Print a good title for user pages
    *
    *@return nothing (display)
    **/
   function title() {
      global $LANG, $CFG_GLPI;

      $buttons = array ();
      $title = $LANG['Menu'][14];

      if ($this->canCreate()) {
         $buttons["user.form.php?new=1"] = $LANG['setup'][2];
         $title = "";

         if (Auth::useAuthExt()) {
            // This requires write access because don't use entity config.
            $buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG['setup'][125];
         }
      }
      if (haveRight("import_externalauth_users", "w")) {
         if (AuthLdap::useAuthLdap()) {
            $buttons["ldap.php"] = $LANG['setup'][3];
         }
      }
      displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", $LANG['Menu'][14], $title, $buttons);
   }


   /**
    * Is the specified user have more right than the current one ?
    *
    *@param $ID Integer : Id of the user
    *
    *@return boolean : true if currrent user have the same right or more right
    **/
   function currentUserHaveMoreRightThan($ID) {

      $user_prof = $this->getUserProfiles($ID);
      return Profile::currentUserHaveMoreRightThan($user_prof);
   }


   /**
    * Get user profiles (no entity association)
    *
    *@param $ID Integer : Id of the user
    *
    *@return array of the IDs of the profiles
    **/
   function getUserProfiles($ID) {
      global $DB;

      $prof = array();
      $query = "SELECT DISTINCT `glpi_profiles_users`.`profiles_id`
                FROM `glpi_profiles_users`
                WHERE `glpi_profiles_users`.`users_id` = '$ID'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $prof[$data['profiles_id']] = $data['profiles_id'];
         }
      }
      return $prof;
   }


   /**
    * Print the user form
    *
    * @param $ID Integer : Id of the user
    * @param $options array
    *     - target form target
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean : user found
    *
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      // Affiche un formulaire User
      if ($ID != getLoginUserID() && !haveRight("user", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $caneditpassword = $this->currentUserHaveMoreRightThan($ID);

      $extauth = !($this->fields["authtype"] == Auth::DB_GLPI
                   || ($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED
                       && !empty ($this->fields["password"])));

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][18] . "&nbsp;:</td>";
      // si on est dans le cas d'un ajout , cet input ne doit plus etre hidden
      if ($this->fields["name"] == "") {
         echo "<td><input name='name' value='" . $this->fields["name"] . "'></td>";
         // si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
      } else {
         if (!empty ($this->fields["password"]) || $this->fields["authtype"] == Auth::DB_GLPI) {
            echo "<td>";
            autocompletionTextField($this, "name");
         } else {
            echo "<td class='b'>" . $this->fields["name"];
            echo "<input type='hidden' name='name' value='" . $this->fields["name"] . "'>";
         }
         echo "</td>";
      }

      //do some rights verification
      if (haveRight("user", "w")) {
         if ((!$extauth || empty($ID))
             && $caneditpassword) {

            echo "<td>" . $LANG['setup'][19] . "&nbsp;:</td>";
            echo "<td><input type='password' name='password' value='' size='20' autocomplete='off'>";
            echo "</td></tr>";
         } else {
            echo "<td colspan='2'>&nbsp;</td></tr>";
         }
      } else {
         echo "<td colspan='2'>&nbsp;</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][48] . "&nbsp;:</td><td>";
      autocompletionTextField($this,"realname");
      echo "</td>";

       //do some rights verification
      if (haveRight("user", "w")) {
         if ((!$extauth || empty($ID))
             && $caneditpassword) {

            echo "<td>" . $LANG['setup'][20] . "&nbsp;:</td>";
            echo "<td><input type='password' name='password2' value='' size='20'
                              autocomplete='off'></td></tr>";
         } else {
            echo "<td colspan='2'>&nbsp;</td></tr>";
         }
      } else {
         echo "<td colspan='2'>&nbsp;</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][43] . "&nbsp;:</td><td>";
      autocompletionTextField($this, "firstname");
      echo "</td>";
     //Authentications informations : auth method used and server used
      //don't display is creation of a new user'
      if (!empty($ID)) {
         if (haveRight("user_authtype", "r")) {
            echo "<td>" . $LANG['login'][10] . "&nbsp;:</td><td>";
            echo Auth::getMethodName($this->fields["authtype"], $this->fields["auths_id"], 1);
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
      } else {
         echo "<td colspan='2'><input type='hidden' name='authtype' value='1'></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][42] . "&nbsp;:</td><td>";
      autocompletionTextField($this, "mobile");
      echo "</td>";
      echo "<td>".$LANG['common'][60]."&nbsp;:</td><td>";
      Dropdown::showYesNo('is_active',$this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][14] . "&nbsp;:</td><td>";
      autocompletionTextField($this, "email", array('name' => "email_form"));

      if (!empty($ID) && !NotificationMail::isUserAddressValid($this->fields["email"])) {
         echo "<br><span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
      }
      echo "</td>";
      echo "<td>" . $LANG['users'][2] . "&nbsp;:</td><td>";
      Dropdown::show('UserCategory', array('value' => $this->fields["usercategories_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['help'][35] . "&nbsp;:</td><td>";
      autocompletionTextField($this, "phone");
      echo "</td>";
      echo "<td rowspan='4' class='middle'>" . $LANG['common'][25] . "&nbsp;:</td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='45' rows='7' name='comment' >".$this->fields["comment"] . "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['help'][35] . " 2&nbsp;:</td><td>";
      autocompletionTextField($this, "phone2");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['users'][1] . "&nbsp;:</td><td>";
      Dropdown::show('UserTitle', array('value' => $this->fields["usertitles_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][15] . "&nbsp;:</td><td>";
      if (!empty($ID)) {
         $entities = Profile_User::getUserEntities($ID, true);
         if (count($entities) > 0) {
            Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                             'entity' => $entities));
         } else {
            echo "&nbsp;";
         }
      } else {
         if (!isMultiEntitiesMode()) {
            // Display all locations : only one entity
            Dropdown::show('Location', array('value' => $this->fields["locations_id"]));
         } else {
            echo "&nbsp;";
         }
      }
      echo "</td></tr>";

      //don't display is creation of a new user'
      if (!empty ($ID)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>" . $LANG['login'][24] . "&nbsp;: ";
         if (!empty($this->fields["date_mod"])) {
            echo convDateTime($this->fields["date_mod"]);
         }
         echo "<br>" . $LANG['login'][0] . "&nbsp;: ";
         if (!empty($this->fields["last_login"])) {
            echo convDateTime($this->fields["last_login"]);
         }
         echo "</td><td colspan='2'class='center'>";
         if ($ID > 0) {
            echo "<a target='_blank' href='".$CFG_GLPI["root_doc"].
                  "/front/user.form.php?getvcard=1&amp;id=$ID'>". $LANG['common'][46]."</a>";
         }
         echo "</td></tr>";
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print the user preference form
    *
    *@param $target form target
    *@param $ID Integer : Id of the user
    *
    *@return boolean : user found
    **/
   function showMyForm($target, $ID) {
      global $CFG_GLPI, $LANG,$PLUGIN_HOOKS;

      // Affiche un formulaire User
      if ($ID != getLoginUserID()) {
         return false;
      }
      if ($this->getFromDB($ID)) {
         $authtype = $this->getAuthMethodsByID();

         $extauth = ! ($this->fields["authtype"] == Auth::DB_GLPI
                       || ($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED
                           && !empty ($this->fields["password"])));

         // No autocopletion :
         $save_autocompletion = $CFG_GLPI["use_ajax_autocompletion"];
         $CFG_GLPI["use_ajax_autocompletion"] = false;

         echo "<div class='center'>";
         echo "<form method='post' name='user_manager' action='$target'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>" . $LANG['setup'][18] . "&nbsp;: " .$this->fields["name"];
         echo "<input type='hidden' name='name' value='" . $this->fields["name"] . "'>";
         echo "<input type='hidden' name='id' value='" . $this->fields["id"] . "'>";
         echo "</th></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][48] . "&nbsp;:</td><td>";
         if ($extauth
             && isset ($authtype['email_field'])
             && !empty ($authtype['realname_field'])) {

            echo $this->fields["realname"];
         } else {
            autocompletionTextField($this, "realname");
         }
         echo "</td>";
         //do some rights verification
         if (!$extauth && haveRight("password_update", "1")) {
            echo "<td>" . $LANG['setup'][19] . "&nbsp;:</td>";
            echo "<td><input type='password' name='password' value='' size='30' autocomplete='off'>";
            echo "</td></tr>";
         } else {
            echo "<td colspan='2'></tr>";
         }

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][43] . "&nbsp;:</td><td>";
         if ($extauth
             && isset ($authtype['firstname_field'])
             && !empty ($authtype['firstname_field'])) {

            echo $this->fields["firstname"];
         } else {
            autocompletionTextField($this, "firstname");
         }
         echo "</td>";

         if (!$extauth && haveRight("password_update", "1")) {
            echo "<td>" . $LANG['setup'][20] . "&nbsp;:</td>";
            echo "<td><input type='password' name='password2' value='' size='30' autocomplete='off'>";
            echo "</td></tr>";
         } else {
            echo "<td colspan='2'></tr>";
         }
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][14] . "&nbsp;:</td><td>";

         if ($extauth && isset ($authtype['email_field']) && !empty ($authtype['email_field'])) {
            echo $this->fields["email"];
         } else {
            autocompletionTextField($this, "email", array('name' => "email_form"));
            if (!NotificationMail::isUserAddressValid($this->fields["email"])) {
               echo "<br><span class='red'>".$LANG['mailing'][110]."</span>";
            }
         }
         echo "</td>";

         if (!GLPI_DEMO_MODE){
            echo "<td>" . $LANG['setup'][41] . "&nbsp;:</td><td>";
            /// Use sesion variable because field in table may be null if same of the global config
            Dropdown::showLanguages("language", array('value' => $_SESSION["glpilanguage"]));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][42] . "&nbsp;:</td><td>";

         if ($extauth && isset ($authtype['mobile_field']) && !empty ($authtype['mobile_field'])) {
            echo $this->fields["mobile"];
         } else {
            autocompletionTextField($this, "mobile");
         }
         echo "</td>";

         if (count($_SESSION['glpiprofiles']) >1) {
            echo "<td>" . $LANG['profiles'][13] . "&nbsp;:</td><td>";
            $options = array(0 => DROPDOWN_EMPTY_VALUE);
            foreach ($_SESSION['glpiprofiles'] as $ID => $prof) {
               $options[$ID] = $prof['name'];
            }
            Dropdown::showFromArray("profiles_id", $options,
                                    array('value' => $this->fields["profiles_id"]));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['help'][35] . "&nbsp;:</td><td>";

         if ($extauth && isset ($authtype['phone_field']) && !empty ($authtype['phone_field'])) {
            echo $this->fields["phone"];
         } else {
            autocompletionTextField($this, "phone");
         }
         echo "</td>";

        if (!GLPI_DEMO_MODE && count($_SESSION['glpiactiveentities'])>1) {
            echo "<td>" . $LANG['profiles'][37] . "&nbsp;:</td><td>";
            Dropdown::show('Entity', array('value'  => $_SESSION["glpidefault_entity"],
                                           'entity' => $_SESSION['glpiactiveentities']));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['help'][35] . " 2 : </td><td>";

         if ($extauth && isset ($authtype['phone2_field']) && !empty ($authtype['phone2_field'])) {
            echo $this->fields["phone2"];
         } else {
            autocompletionTextField($this, "phone2");
         }
         echo "</td>";

        if (haveRight("config", "w")) {
            echo "<td>" . $LANG['setup'][138] . "&nbsp;:</td><td>";
            $modes[NORMAL_MODE] = $LANG['setup'][135];
            $modes[TRANSLATION_MODE] = $LANG['setup'][136];
            $modes[DEBUG_MODE] = $LANG['setup'][137];
            Dropdown::showFromArray('use_mode',$modes,array('value'=>$this->fields["use_mode"]));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         echo "<tr><td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='submit' name='update' value='".$LANG['buttons'][7]."' class='submit' >";
         echo "</td></tr>";

         echo "</table></form></div>";
         $CFG_GLPI["use_ajax_autocompletion"] = $save_autocompletion;
         return true;
      }
      return false;
   }


   ///Get all the authentication method parameters for the current user
   function getAuthMethodsByID() {
      return Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);
   }


   function pre_updateInDB() {
      global $DB,$LANG;

      if (($key=array_search('name',$this->updates)) !== false) {
         /// Check if user does not exists
         $query = "SELECT *
                   FROM `".$this->getTable()."`
                   WHERE `name` = '".$this->input['name']."'
                         AND `id` <> '".$this->input['id']."';";
         $result = $DB->query($query);

         if ($DB->numrows($result) > 0) {
            unset($this->updates[$key]);
            unset($this->oldvalues['name']);

            /// For displayed message
            $this->fields['name'] = $this->oldvalues['name'];
            addMessageAfterRedirect($LANG['setup'][614], false, ERROR);
         }
      }

      /// Security system except for login update
      if (getLoginUserID() && !haveRight("user", "w") && !strpos($_SERVER['PHP_SELF'],
                                                                 "login.php")) {

         if (getLoginUserID() === $this->input['id']) {
            if (isset($this->fields["authtype"])) {

               // extauth ldap case
               if ($_SESSION["glpiextauth"]
                   && ($this->fields["authtype"] == Auth::LDAP
                       || Auth::isAlternateAuthWithLdap($this->fields["authtype"]))) {

                  $authtype = Auth::getMethodsByID($this->fields["authtype"],
                                                   $this->fields["auths_id"]);
                  if (count($authtype)) {
                     $fields = AuthLDAP::getSyncFields($authtype);
                     foreach ($fields as $key => $val) {
                        if (!empty($val)
                            && ($key2 = array_search($key,$this->updates)) !== false) {

                           unset($this->updates[$key2]);
                           unset($this->oldvalues[$key]);

                        }
                     }
                  }
               }

               // extauth imap case
               if (isset($this->fields["authtype"]) && $this->fields["authtype"] == Auth::MAIL) {
                  if (($key = array_search("email", $this->updates)) !== false) {
                     unset ($this->updates[$key]);
                     unset($this->oldvalues['email']);
                  }
               }

               if (($key = array_search("is_active",$this->updates)) !== false) {
                  unset ($this->updates[$key]);
                  unset($this->oldvalues['is_active']);
               }

               if (($key = array_search("comment",$this->updates)) !== false) {
                  unset ($this->updates[$key]);
                  unset($this->oldvalues['comment']);
               }
            }
         }
      }
   }



   function getSearchOptions() {
      global $LANG;

      // forcegroup by on name set force group by for all items
      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['setup'][18];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['forcegroupby']  = true;

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[34]['table']     = $this->getTable();
      $tab[34]['field']     = 'realname';
      $tab[34]['linkfield'] = 'realname';
      $tab[34]['name']      = $LANG['common'][48];

      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'firstname';
      $tab[9]['linkfield'] = 'firstname';
      $tab[9]['name']      = $LANG['common'][43];

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'email';
      $tab[5]['linkfield'] = 'email';
      $tab[5]['name']      = $LANG['setup'][14];
      $tab[5]['datatype']  = 'email';

      $tab += Location::getSearchOptionsToAdd();

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'is_active';
      $tab[8]['linkfield'] = 'is_active';
      $tab[8]['name']      = $LANG['common'][60];
      $tab[8]['datatype']  = 'bool';

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'phone';
      $tab[6]['linkfield'] = 'phone';
      $tab[6]['name']      = $LANG['help'][35];

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'phone2';
      $tab[10]['linkfield'] = 'phone2';
      $tab[10]['name']      = $LANG['help'][35]." 2";

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'mobile';
      $tab[11]['linkfield'] = 'mobile';
      $tab[11]['name']      = $LANG['common'][42];

      $tab[13]['table']          = 'glpi_groups';
      $tab[13]['field']          = 'name';
      $tab[13]['linkfield']      = '';
      $tab[13]['name']           = $LANG['common'][35];
      $tab[13]['forcegroupby']   = true;
      $tab[13]['datatype']       = 'itemlink';
      $tab[13]['itemlink_type']  = 'Group';

      $tab[14]['table']     = $this->getTable();
      $tab[14]['field']     = 'last_login';
      $tab[14]['linkfield'] = '';
      $tab[14]['name']      = $LANG['login'][0];
      $tab[14]['datatype']  = 'datetime';

      $tab[15]['table']      = 'glpi_auth_tables';
      $tab[15]['field']      = 'name';
      $tab[15]['linkfield']  = '';
      $tab[15]['name']       = $LANG['login'][10];
      $tab[15]['searchtype'] = 'contains';

      $tab[30]['table']      = 'glpi_authldaps';
      $tab[30]['field']      = 'name';
      $tab[30]['linkfield']  = '';
      $tab[30]['name']       = $LANG['login'][10]." - ".$LANG['login'][2];

      $tab[31]['table']      = 'glpi_authmails';
      $tab[31]['field']      = 'name';
      $tab[31]['linkfield']  = '';
      $tab[31]['name']       = $LANG['login'][10]." - ".$LANG['login'][3];

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[17]['table']     = $this->getTable();
      $tab[17]['field']     = 'language';
      $tab[17]['linkfield'] = '';
      $tab[17]['name']      = $LANG['setup'][41];
      $tab[17]['datatype']  = 'language';

      $tab[19]['table']     = $this->getTable();
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[20]['table']        = 'glpi_profiles';
      $tab[20]['field']        = 'name';
      $tab[20]['linkfield']    = '';
      $tab[20]['name']         = $LANG['Menu'][35]." (- ".$LANG['entity'][0].")";
      $tab[20]['forcegroupby'] = true;

      $tab[80]['table']        = 'glpi_entities';
      $tab[80]['field']        = 'completename';
      $tab[80]['linkfield']    = 'entities_id';
      $tab[80]['name']         = $LANG['entity'][0]." (- ".$LANG['Menu'][35].")";
      $tab[80]['forcegroupby'] = true;

      $tab[81]['table']     = 'glpi_usertitles';
      $tab[81]['field']     = 'name';
      $tab[81]['linkfield'] = 'usertitles_id';
      $tab[81]['name']      = $LANG['users'][1];

      $tab[82]['table']     = 'glpi_usercategories';
      $tab[82]['field']     = 'name';
      $tab[82]['linkfield'] = 'usercategories_id';
      $tab[82]['name']      = $LANG['users'][2];

      return $tab;
   }


   /**
    * Execute the query to select box with all glpi users where select key = name
    *
    * Internaly used by showGroup_Users, dropdownUsers and ajax/dropdownUsers.php
    *
    * @param $count true if execute an count(*),
    * @param $right limit user who have specific right
    * @param $entity_restrict Restrict to a defined entity
    * @param $value default value
    * @param $used Already used items ID: not to display in dropdown
    * @param $search pattern
    *
    * @return mysql result set.
    *
    */
   static function getSqlSearchResult ($count=true, $right="all", $entity_restrict=-1, $value=0,
                                       $used=array(), $search='') {
      global $DB, $CFG_GLPI;

      if ($entity_restrict < 0) {
         $entity_restrict = $_SESSION["glpiactive_entity"];
      }
      $joinprofile = false;
      switch ($right) {
         case "interface" :
            $where = " `glpi_profiles`.`interface` = 'central' ";
            $joinprofile = true;
            $where .= getEntitiesRestrictRequest("AND","glpi_profiles_users",'',$entity_restrict,1);
            break;

         case "id" :
            $where = " `glpi_users`.`id` = '".getLoginUserID()."' ";
            break;

         case "all" :
            $where = " `glpi_users`.`id` > '1' ".
                     getEntitiesRestrictRequest("AND","glpi_profiles_users",'',$entity_restrict,1);
            break;

         default :
            $joinprofile = true;
            $where = " (`glpi_profiles`.`".$right."`='1' ".
                        getEntitiesRestrictRequest("AND","glpi_profiles_users",'',
                                                   $entity_restrict,1)." ";

            if (!in_array($right,Profile::$helpdesk_rights)) {
               $where .= " AND `glpi_profiles`.`interface` = 'central' ";
            }
            $where .= ')';
      }

      $where .= " AND `glpi_users`.`is_deleted` = '0'
                  AND `glpi_users`.`is_active` = '1' ";

      if ((is_numeric($value) && $value)
          || count($used)) {

         $where .= " AND `glpi_users`.`id` NOT IN (";
         if (is_numeric($value)) {
            $first = false;
            $where .= $value;
         } else {
            $first = true;
         }
         foreach($used as $val) {
            if ($first) {
               $first = false;
            } else {
               $where .= ",";
            }
            $where .= $val;
         }
         $where .= ")";
      }

      if ($count) {
         $query = "SELECT COUNT(DISTINCT `glpi_users`.`id` ) AS cpt
                   FROM `glpi_users` ";
      } else {
         $query = "SELECT DISTINCT `glpi_users`.*
                   FROM `glpi_users` ";
      }
      $query.=" LEFT JOIN `glpi_profiles_users`
                     ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";
      if ($joinprofile) {
         $query .= " LEFT JOIN `glpi_profiles`
                           ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`) ";
      }

      if ($count) {
         $query.= " WHERE $where ";
      } else {
         if (strlen($search)>0 && $search!=$CFG_GLPI["ajax_wildcard"]) {
            $where .= " AND (`glpi_users`.`name` ".makeTextSearch($search)."
                             OR `glpi_users`.`realname` ".makeTextSearch($search)."
                             OR `glpi_users`.`firstname` ".makeTextSearch($search)."
                             OR `glpi_users`.`phone` ".makeTextSearch($search)."
                             OR `glpi_users`.`email` ".makeTextSearch($search)."
                             OR CONCAT(`glpi_users`.`realname`,' ',`glpi_users`.`firstname`) ".
                                      makeTextSearch($search).")";
         }
         $query .= " WHERE $where ";

         if ($CFG_GLPI["names_format"] == FIRSTNAME_BEFORE) {
            $query.=" ORDER BY `glpi_users`.`firstname`,
                               `glpi_users`.`realname`,
                               `glpi_users`.`name` ";
         } else {
            $query.=" ORDER BY `glpi_users`.`realname`,
                               `glpi_users`.`firstname`,
                               `glpi_users`.`name` ";
         }

         if ($search != $CFG_GLPI["ajax_wildcard"]) {
            $query .= " LIMIT 0,".$CFG_GLPI["dropdown_max"];
         }
      }
      return $DB->query($query);
   }


   /**
    * Make a select box with all glpi users where select key = name
    *
    * Parameters which could be used in options array :
    *    - name : string / name of the select (default is users_id)
    *    - right : string / limit user who have specific right :
    *        id -> only current user (default case);
    *        interface -> central ;
    *        all -> all users ;
    *        specific right like show_all_ticket, create_ticket....
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - entity_sons : boolean / if entity restrict specified auto select its sons
    *                   only available if entity is a single value not an array (default false)
    *    - all : Nobody or All display for none selected
    *          all=0 (default) -> Nobody
    *          all=1 -> All
    *         all=-1-> nothing
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *    - helpdesk_ajax : boolean (default 0) / use ajax for helpdesk auto update (mail itemtype)
    *
    * @param $options possible options
    * @return nothing (print out an HTML select box)
    *
    */
   static function dropdown($options=array()) {
      global $DB,$CFG_GLPI,$LANG;

      // Defautl values
      $p['name']           = 'users_id';
      $p['value']          = '';
      $p['right']          = 'id';
      $p['all']            = 0;
      $p['helpdesk_ajax']  = 0;
      $p['comments']       = 1;
      $p['entity']         = -1;
      $p['entity_sons']    = false;
      $p['used']           = array();
      $p['ldap_import']    = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!($p['entity']<0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            echo "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities',$p['entity']);
         }
      }

      // Make a select box with all glpi users
      $rand = mt_rand();
      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         $res = User::getSqlSearchResult (true, $p['right'], $p['entity'], $p['value'], $p['used']);
         $nb = ($res ? $DB->result($res,0,"cpt") : 0);
         if ($nb > $CFG_GLPI["ajax_limit_count"]) {
            $use_ajax = true;
         }
      }
      $user = getUserName($p['value'],2);

      $default_display = "<select id='dropdown_".$p['name'].$rand."' name='".$p['name']."'>";
      $default_display.= "<option value='".$p['value']."'>";
      $default_display.= utf8_substr($user["name"],0,$_SESSION["glpidropdown_chars_limit"]);
      $default_display.= "</option></select>";

      $view_users = (haveRight("user","r"));

      $params = array('searchText'       => '__VALUE__',
                      'value'            => $p['value'],
                      'myname'           => $p['name'],
                      'all'              => $p['all'],
                      'right'            => $p['right'],
                      'comment'          => $p['comments'],
                      'rand'             => $rand,
                      'helpdesk_ajax'    => $p['helpdesk_ajax'],
                      'entity_restrict'  => $p['entity'],
                      'used'             => $p['used']);
      if ($view_users) {
         $params['update_link'] = $view_users;
      }

      $default = "";
      if (!empty($p['value']) && $p['value']>0) {
         $default = $default_display;
      } else {
         $default = "<select name='".$p['name']."' id='dropdown_".$p['name'].$rand."'>";
         if ($p['all']) {
            $default.= "<option value='0'>[ ".$LANG['common'][66]." ]</option></select>";
         } else {
            $default.= "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option></select>\n";
         }
      }

      ajaxDropdown($use_ajax,"/ajax/dropdownUsers.php",$params,$default,$rand);

      // Display comment
      if ($p['comments']) {
         if (!$view_users) {
            $user["link"] = '';
         } else if (empty($user["link"])) {
            $user["link"] = $CFG_GLPI['root_doc']."/front/user.php";
         }
         showToolTip($user["comment"], array('contentid' => "comment_".$p['name'].$rand,
                                             'link'      => $user["link"],
                                             'linkid'    => "comment_link_".$p["name"].$rand));
      }

      if (haveRight('import_externalauth_users','w')
          && $p['ldap_import']
          && EntityData::isEntityDirectoryConfigured($_SESSION['glpiactive_entity'])) {

         echo "<img alt='' title='".$LANG['ldap'][35]."' src='".$CFG_GLPI["root_doc"].
               "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                onClick=\"var w = window.open('".$CFG_GLPI['root_doc'].
               "/front/popup.php?popup=add_ldapuser&amp;rand=$rand&amp;entity=".
               $_SESSION['glpiactive_entity']."' ,'glpipopup', 'height=400, ".
               "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
      }
      return $rand;
   }


   /**
    * Make a select box with all glpi users in tracking table
    *
    * @param $myname the name of the HTML select
    * @param $value the preselected value we want
    * @param $field field of the glpi_tickets table to lookiup for possible users
    * @param $display_comment display the comment near the dropdown
    *
    * @return nothing (print out an HTML select box)
    */
   static function dropdownForTicket($myname,$value,$field,$display_comment=1) {
      global $CFG_GLPI,$LANG,$DB;

      $rand = mt_rand();
      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         if ($CFG_GLPI["ajax_limit_count"] == 0) {
            $use_ajax = true;
         } else {
            $query = "SELECT COUNT(`".$field."`)
                      FROM `glpi_tickets` ".
                      getEntitiesRestrictRequest("WHERE","glpi_tickets");
            $result = $DB->query($query);

            $nb = $DB->result($result,0,0);
            if ($nb > $CFG_GLPI["ajax_limit_count"]) {
               $use_ajax = true;
            }
         }
      }

      $user = getUserName($value, 2);
      $default = "<select name='$myname'><option value='$value'>";
      $default.= utf8_substr($user["name"],0,$_SESSION["glpidropdown_chars_limit"])."</option>";
      $default.= "</select>";
      if (empty($value) || $value==0) {
         $default = "<select name='$myname'><option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";
         $default.= "</select>";
      }

      $params = array('searchText' => '__VALUE__',
                      'value'      => $value,
                      'field'      => $field,
                      'myname'     => $myname,
                      'comment'    => $display_comment,
                      'rand'       => $rand);

      ajaxDropdown($use_ajax, "/ajax/dropdownUsersTracking.php", $params, $default, $rand);

      if (!haveRight("user","r")) {
         $user["link"] = '';
      } else if (empty($user["link"])) {
         $user["link"] = $CFG_GLPI['root_doc']."/front/user.php";
      }
      // Display comment
      if ($display_comment) {
         showToolTip($user["comment"], array('contentid' => "comment_".$myname.$rand,
                                             'link'      => $user["link"],
                                             'linkid'    => "comment_link_".$myname.$rand));
      }
      return $rand;
   }


   /**
    * Simple add user form for external auth
    */
   static function showAddExtAuthForm() {
      global $LANG;

      if (!haveRight("import_externalauth_users","w")) {
         return false;
      }

      echo "<div class='center'>\n";
      echo "<form method='get' action='".getItemTypeFormURL('User')."'>\n";

      echo "<table class='tab_cadre'>\n";
      echo "<tr><th colspan='4'>".$LANG['setup'][126]."</th></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['login'][6]."</td>\n";
      echo "<td><input type='text' name='login'></td>";
      echo "<td class='tab_bg_2 center'>\n";
      echo "<input type='hidden' name='ext_auth' value='1'>\n";
      echo "<input type='submit' name='add_ext_auth_ldap' value='".$LANG['buttons'][8]." ".
            $LANG['login'][2]."' class='submit'>\n";
      echo "</td>";
      echo "<td class='tab_bg_2 center'>\n";
      echo "<input type='submit' name='add_ext_auth_simple' value='".$LANG['buttons'][8]." ".
            $LANG['common'][62]."' class='submit'>\n";
      echo "</td></tr>\n";

      echo "</table></form></div>\n";
   }


   static function changeAuthMethod($IDs=array(), $authtype=1 ,$server=-1) {
      global $DB;

      if (!empty($IDs) && in_array($authtype, array(Auth::DB_GLPI,
                                                    Auth::LDAP,
                                                    Auth::MAIL,
                                                    Auth::EXTERNAL))) {
         $where = implode("','",$IDs);
         $query = "UPDATE `glpi_users`
                   SET `authtype` = '$authtype', `auths_id` = '$server', `password` = ''
                   WHERE `id` IN ('$where')";

         $DB->query($query);
      }
   }


   /**
    * Generate vcard for the current user
    */
   function generateVcard() {

      include_once (GLPI_ROOT . "/lib/vcardclass/classes-vcard.php");

      // build the Vcard
      $vcard = new vCard();

      if (!empty($this->fields["realname"]) || !empty($this->fields["firstname"])) {
         $vcard->setName($this->fields["realname"], $this->fields["firstname"], "", "");
      } else {
         $vcard->setName($this->fields["name"], "", "", "");
      }

      $vcard->setPhoneNumber($this->fields["phone"], "PREF;WORK;VOICE");
      $vcard->setPhoneNumber($this->fields["phone2"], "HOME;VOICE");
      $vcard->setPhoneNumber($this->fields["mobile"], "WORK;CELL");

      $vcard->setEmail($this->fields["email"]);

      $vcard->setNote($this->fields["comment"]);

      // send the  VCard
      $output = $vcard->getVCard();
      $filename = $vcard->getFileName();      // "xxx xxx.vcf"

      @Header("Content-Disposition: attachment; filename=\"$filename\"");
      @Header("Content-Length: ".utf8_strlen($output));
      @Header("Connection: close");
      @Header("content-type: text/x-vcard; charset=UTF-8");

      echo $output;
   }


   /**
    * Show items of the current user
    */
   function showItems() {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $this->getField('id');

      $group_where = "";
      $groups = array();
      $query = "SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
                FROM `glpi_groups_users`
                LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                WHERE `glpi_groups_users`.`users_id` = '$ID'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         $first = true;
         while ($data = $DB->fetch_array($result)) {
            if ($first) {
               $first = false;
            } else {
               $group_where .= " OR ";
            }
            $group_where .= " `groups_id` = '".$data["groups_id"]."' ";
            $groups[$data["groups_id"]] = $data["name"];
         }
      }

      echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]."</th>".
            "<th>".$LANG['entity'][0]."</th>".
            "<th>".$LANG['common'][16]."</th>".
            "<th>".$LANG['common'][19]."</th>".
            "<th>".$LANG['common'][20]."</th><th>&nbsp;</th></tr>";

      foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT *
                      FROM `$itemtable`
                      WHERE `users_id` = '$ID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
               $query .= " AND `is_deleted` = '0' ";
            }
            $result = $DB->query($query);

            $type_name = $item->getTypeName();

            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_array($result)) {
                  $cansee = $item->can($data["id"],"r");
                  $link = $data["name"];
                  if ($cansee) {
                     $link_item = getItemTypeFormURL($itemtype);
                     $link = "<a href='".$link_item."?id=".$data["id"]."'>".$link.
                              (($_SESSION["glpiis_ids_visible"]||empty($link))?" (".$data["id"].")":"")
                              ."</a>";
                  }
                  $linktype = "";
                  if ($data["users_id"] == $ID) {
                     $linktype = $LANG['common'][34];
                  }
                  echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>";
                  echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                       $data["entities_id"])."</td>";
                  echo "<td class='center'>$link</td>";
                  echo "<td class='center'>";
                  if (isset($data["serial"]) && !empty($data["serial"])) {
                     echo $data["serial"];
                  } else {
                     echo '&nbsp;';
                  }
                  echo "</td><td class='center'>";
                  if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                     echo $data["otherserial"];
                  } else {
                     echo '&nbsp;';
                  }
                  echo "<td class='center'>$linktype</td></tr>";
               }
            }
         }
      }
      echo "</table></div><br>";

      if (!empty($group_where)) {
         echo "<div class='center'><table class='tab_cadre_fixe'><tr>".
               "<th>".$LANG['common'][17]."</th>".
               "<th>".$LANG['entity'][0]."</th>".
               "<th>".$LANG['common'][16]."</th>".
               "<th>".$LANG['common'][19]."</th>".
               "<th>".$LANG['common'][20]."</th><th>&nbsp;</th></tr>";

         foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
            if (!class_exists($itemtype)) {
               continue;
            }
            $item = new $itemtype();
            if ($item->canView()) {
               $itemtable = getTableForItemType($itemtype);
               $query = "SELECT *
                         FROM `$itemtable`
                         WHERE $group_where";

               if ($item->maybeTemplate()) {
                  $query .= " AND `is_template` = '0' ";
               }
               if ($item->maybeDeleted()) {
                  $query .= " AND `is_deleted` = '0' ";
               }
               $result = $DB->query($query);

               $type_name= $item->getTypeName();


               if ($DB->numrows($result) >0) {
                  while ($data = $DB->fetch_array($result)) {
                     $cansee = $item->can($data["id"],"r");
                     $link = $data["name"];
                     if ($cansee) {
                        $link_item = getItemTypeFormURL($itemtype);
                        $link = "<a href='".$link_item."?id=".$data["id"]."'>".$link.
                                 (($_SESSION["glpiis_ids_visible"] || empty($link))?" (".$data["id"].")":"").
                                 "</a>";
                     }
                     $linktype = "";
                     if (isset($groups[$data["groups_id"]])) {
                        $linktype = $LANG['common'][35]." ".$groups[$data["groups_id"]];
                     }
                     echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>";
                     echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                          $data["entities_id"]);
                     echo "</td><td class='center'>$link</td>";
                     echo "<td class='center'>";
                     if (isset($data["serial"]) && !empty($data["serial"])) {
                        echo $data["serial"];
                     } else {
                        echo '&nbsp;';
                     }
                     echo "</td><td class='center'>";
                     if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                        echo $data["otherserial"];
                     } else {
                        echo '&nbsp;';
                     }
                     echo "</td><td class='center'>$linktype</td></tr>";
                  }
               }
            }
         }
         echo "</table></div><br>";
      }
   }


   static function getOrImportByEmail($email='') {
      global $DB, $CFG_GLPI;

      $query = "SELECT `id`
                FROM `glpi_users`
                WHERE `email` = '$email'";
      $result = $DB->query($query);

      //User still exists in DB
      if ($result && $DB->numrows($result)) {
         return $DB->result($result,0,"id");
      } else {
         if ($CFG_GLPI["is_users_auto_add"]) {
            //Get all ldap servers with email field configured
            $ldaps = AuthLdap::getServersWithImportByEmailActive();
            //Try to find the user by his email on each ldap server
            foreach ($ldaps as $ldap) {
               $params['method'] = AuthLdap::IDENTIFIER_EMAIL;
               $params['value'] = $email;
               $res = AuthLdap::ldapImportUserByServerId($params, AuthLdap::ACTION_IMPORT,$ldap);
               if (isset($res['id'])) {
                  return $res['id'];
               }
            }
         }
      }
      return 0;
   }


   static function manageDeletedUserInLdap($users_id) {
      global $CFG_GLPI,$LANG;

      //User is present in DB but not in the directory : it's been deleted in LDAP
      $tmp['id'] = $users_id;
      $myuser = new User;

      switch ($CFG_GLPI['user_deleted_ldap']) {
         //DO nothing
         default :
         case 0 :
            break;

         //Put user in trash
         case 1 :
            $tmp['is_deleted'] = 1;
            $myuser->update($tmp);
            break;

         //Delete all user dynamic habilitations and groups
         case 2 :
            Profile_User::deleteRights($users_id,true);
            Group_User::deleteGroups($users_id,true);
            break;

         //Deactivate the user
         case 3 :
            $tmp['is_active'] = 0;
            $myuser->update($tmp);
            break;
      }
      $changes[0] = '0';
      $changes[1] = '';
      $changes[2] = addslashes($LANG['ldap'][48]);
      Log::history($users_id,'User',$changes,0,HISTORY_LOG_SIMPLE_MESSAGE);
   }


   static function getIdByName($login) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_users`
                WHERE `name` = '".addslashes($login)."'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         return $DB->result($result,0,'id');
      }
      return false;
   }
}

?>
