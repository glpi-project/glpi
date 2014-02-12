<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/
// And Marco Gaiarin for ldap features

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class User extends CommonDBTM {

   // From CommonDBTM
   public $dohistory         = true;
   public $history_blacklist = array('date_mod', 'date_sync', 'last_login');

   // NAME FIRSTNAME ORDER TYPE
   const REALNAME_BEFORE   = 0;
   const FIRSTNAME_BEFORE  = 1;


   static function getTypeName($nb=0) {
      return _n('User','Users',$nb);
   }


   static function canCreate() {
      return Session::haveRight('user', 'w');
   }


   static function canUpdate() {
      return Session::haveRight('user', 'w');
   }


   static function canDelete() {
      return Session::haveRight('user', 'w');
   }


   static function canView() {
      return Session::haveRight('user', 'r');
   }


   function canViewItem() {

      $entities = Profile_User::getUserEntities($this->fields['id'], true);
      if (Session::isViewAllEntities()
          || Session::haveAccessToOneOfEntities($entities)) {
         return true;
      }
      return false;
   }


   function canCreateItem() {

      // Will be created from form, with selected entity/profile
      if (isset($this->input['_profiles_id']) && ($this->input['_profiles_id'] > 0)
          && Profile::currentUserHaveMoreRightThan(array($this->input['_profiles_id']))
          && isset($this->input['_entities_id'])
          && Session::haveAccessToEntity($this->input['_entities_id'])) {
         return true;
      }
      // Will be created with default value
      if (Session::haveAccessToEntity(0) // Access to root entity (required when no default profile)
          || (Profile::getDefault() > 0)) {
         return true;
      }

      return false;
   }


   function canUpdateItem() {

      $entities = Profile_User::getUserEntities($this->fields['id'], false);
      if (Session::isViewAllEntities()
          || Session::haveAccessToOneOfEntities($entities)) {
         return true;
      }
      return false;
   }


   function canDeleteItem() {

      $entities = Profile_User::getUserEntities($this->fields['id'], true);
      if (Session::isViewAllEntities()
          || Session::haveAccessToAllOfEntities($entities)) {
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
   function computePreferences() {
      global $CFG_GLPI;

      if (isset($this->fields['id'])) {
         foreach ($CFG_GLPI['user_pref_field'] as $f) {
            if (is_null($this->fields[$f])) {
               $this->fields[$f] = $CFG_GLPI[$f];
            }
         }
      }
      /// Specific case for show_count_on_tabs : global config can forbid
      if ($CFG_GLPI['show_count_on_tabs'] == -1) {
         $this->fields['show_count_on_tabs'] = 0;
      }
   }


   /**
    * Load minimal session for user
    *
    * @param $entities_id : entity to use
    * @param $is_recursive : load recursive entity
    *
    * @since version 0.83.7
   **/
   function loadMinimalSession($entities_id, $is_recursive) {
      global $CFG_GLPI;

      if (isset($this->fields['id']) && !isset($_SESSION["glpiID"])) {
         Session::destroy();
         Session::start();
         $_SESSION["glpiID"]                      = $this->fields['id'];
         $_SESSION["glpi_use_mode"]               = Session::NORMAL_MODE;
         $_SESSION["glpiactive_entity"]           = $entities_id;
         $_SESSION["glpiactive_entity_recursive"] = $is_recursive;
         if ($is_recursive) {
            $entities = getSonsOf("glpi_entities", $entities_id);
         } else {
            $entities = array($entities_id);
         }
         $_SESSION['glpiactiveentities']        = $entities;
         $_SESSION['glpiactiveentities_string'] = "'".implode("', '", $entities)."'";
         $this->computePreferences();
         foreach ($CFG_GLPI['user_pref_field'] as $field) {
            if (isset($this->fields[$field])) {
               $_SESSION["glpi$field"] = $this->fields[$field];
            }
         }
         Session::loadGroups();
         Session::loadLanguage();
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $ong    = array();
            $ong[1] = __('Used items');
            $ong[2] = __('Managed items');
            return $ong;

         case 'Preference' :
            return __('Main');
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case __CLASS__ :
            $item->showItems($tabnum==2);
            return true;

         case 'Preference' :
            $user = new self();
            $user->showMyForm($CFG_GLPI['root_doc']."/front/preference.php",
                              Session::getLoginUserID());
            return true;
      }
      return false;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('Profile_User', $ong, $options);
      $this->addStandardTab('Group_User', $ong, $options);
      $this->addStandardTab('Config', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Reservation', $ong, $options);
      $this->addStandardTab('Auth', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function post_getEmpty() {
      global $CFG_GLPI;

      $this->fields["is_active"] = 1;
      if (isset($CFG_GLPI["language"])) {
         $this->fields['language'] = $CFG_GLPI["language"];
      } else {
         $this->fields['language'] = "en_GB";
      }
   }


   function pre_deleteItem() {
      global $DB;

      $entities = Profile_User::getUserEntities($this->fields["id"]);
      $view_all = Session::isViewAllEntities();
      // Have right on all entities ?
      $all      = true;
      if (!$view_all) {
         foreach ($entities as $ent) {
            if (!Session::haveAccessToEntity($ent)) {
               $all = false;
            }
         }
      }
      if ($all) { // Mark as deleted
         return true;
      }
      // only delete profile
      foreach ($entities as $ent) {
         if (Session::haveAccessToEntity($ent)) {
            $all   = false;
            $query = "DELETE
                      FROM `glpi_profiles_users`
                      WHERE `users_id` = '".$this->fields["id"]."'
                            AND `entities_id` = '$ent'";
            $DB->query($query);
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

      // Delete own reminders
      $query = "DELETE
                FROM `glpi_reminders`
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

      // Set no user to consumables
      $query = "UPDATE `glpi_consumables`
                SET `items_id` = '0'
                WHERE `items_id` = '".$this->fields['id']."'
                      AND `itemtype` = 'User'";
      $DB->query($query);


      $gu = new Group_User();
      $gu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $tu = new Ticket_User();
      $tu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $pu = new Problem_User();
      $pu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $kiu = new KnowbaseItem_User();
      $kiu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $ru = new Reminder_User();
      $ru->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $ue = new UserEmail();
      $ue->deleteByCriteria(array('users_id' => $this->fields['id']));

      // Ticket rules use various _users_id_*
      Rule::cleanForItemAction($this, '_users_id%');
      Rule::cleanForItemCriteria($this, '_users_id%');
   }


   /**
    * Retrieve an item from the database using its login
    *
    * @param $name login of the user
    *
    * @return true if succeed else false
   **/
   function getFromDBbyName($name) {
      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`name` = '$name'");
   }


   /**
    * Retrieve an item from the database using it's dn
    *
    * @since version 0.84
    *
    * @param $user_dn dn of the user
    *
    * @return true if succeed else false
   **/
   function getFromDBbyDn($user_dn) {
      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`user_dn` = '$user_dn'");
   }


   /**
    * Retrieve an item from the database using its email
    *
    * @param $email       string   user email
    * @param $condition   string   add condition
    *
    * @return true if succeed else false
   **/
   function getFromDBbyEmail($email, $condition) {

      $request = "LEFT JOIN `glpi_useremails`
                     ON (`glpi_useremails`.`users_id` = `".$this->getTable()."`.`id`)
                  WHERE `glpi_useremails`.`email` = '$email'";

      if (!empty($condition)) {
         $request .= " AND $condition";
      }
      return $this->getFromDBByQuery($request);
   }


   /**
    * Get the default email of the user
    *
    * @return default user email
   **/
   function getDefaultEmail() {

      if (!isset($this->fields['id'])) {
         return '';
      }
      return UserEmail::getDefaultForUser($this->fields['id']);
   }


   /**
    * Get all emails of the user
    *
    * @return array of emails
   **/
   function getAllEmails() {

      if (!isset($this->fields['id'])) {
         return '';
      }
      return UserEmail::getAllForUser($this->fields['id']);
   }


   /**
    * Is the email set to the current user
    *
    * @param $email
    *
    * @return boolean is an email of the user
   **/
   function isEmail($email) {

      if (!isset($this->fields['id'])) {
         return false;
      }
      return UserEmail::isEmailForUser($this->fields['id'], $email);
   }


   /**
    * Retrieve an item from the database using its personal token
    *
    * @param $token user token
    *
    * @return true if succeed else false
   **/
   function getFromDBbyToken($token) {
      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`personal_token` = '$token'");
   }


   function prepareInputForAdd($input) {
      global $DB;

      if (isset($input['_stop_import'])) {
         return false;
      }

      // Check if user does not exists
      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `name` = '".$input['name']."'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         Session::addMessageAfterRedirect(__('Unable to add. The user already exists.'),
                                          false, ERROR);
         return false;
      }

      if (isset($input["password2"])) {
         if (empty($input["password"])) {
            unset ($input["password"]);

         } else {
            if ($input["password"] == $input["password2"]) {
               if (Config::validatePassword($input["password"])) {
                  $input["password"]
                     = sha1(Toolbox::unclean_cross_side_scripting_deep(stripslashes($input["password"])));
               } else {
                  unset($input["password"]);
               }
               unset($input["password2"]);
            } else {
               Session::addMessageAfterRedirect(__('Error: the two passwords do not match'),
                                                false, ERROR);
               return false;
            }
         }
      }

      if (isset($input["_extauth"])) {
         $input["password"] = "";
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

      // add emails (use _useremails set from UI, not _emails set from LDAP)
      if (isset($this->input['_useremails']) && count($this->input['_useremails'])) {
         $useremail = new UserEmail();
         foreach ($this->input['_useremails'] as $id => $email) {
            $email = trim($email);
            $email_input = array('email'    => $email,
                                 'users_id' => $this->getID());
            if (isset($this->input['_default_email'])
                && ($this->input['_default_email'] == $id)) {
               $email_input['is_default'] = 1;
            } else {
               $email_input['is_default'] = 0;
            }
            $useremail->add($email_input);
         }
      }


      $this->syncLdapGroups();
      $this->syncDynamicEmails();
      $rulesplayed = $this->applyRightRules();

      // Add default profile
      if (!$rulesplayed) {
         $affectation = array();
         if (isset($this->input['_profiles_id']) && $this->input['_profiles_id']) {
            $profile                   = $this->input['_profiles_id'];
            // Choosen in form, so not dynamic
            $affectation['is_dynamic'] = 0;
         } else {
            $profile                   = Profile::getDefault();
            // Default right as dynamic. If dynamic rights are set it will disappear.
            $affectation['is_dynamic'] = 1;
         }

         if ($profile) {
            if (isset($this->input["_entities_id"])) {
               // entities_id (user's pref) always set in prepareInputForAdd
               // use _entities_id for default right
               $affectation["entities_id"] = $this->input["_entities_id"];

            } else if (isset($_SESSION['glpiactive_entity'])) {
               $affectation["entities_id"] = $_SESSION['glpiactive_entity'];

            } else {
               $affectation["entities_id"] = 0;
            }
            if (isset($this->input["_is_recursive"])) {
               $affectation["is_recursive"] = $this->input["_is_recursive"];
            } else {
               $affectation["is_recursive"] = 0;
            }

            $affectation["profiles_id"]  = $profile;
            $affectation["users_id"]     = $this->fields["id"];
            $right                       = new Profile_User();
            $right->add($affectation);
         }
      }
   }


   function prepareInputForUpdate($input) {
      global $CFG_GLPI;

      if (isset($input["password2"])) {
         // Empty : do not update
         if (empty($input["password"])) {
            unset($input["password"]);

         } else {
            if ($input["password"] == $input["password2"]) {
               // Check right : my password of user with lesser rights
               if (isset($input['id']) && Config::validatePassword($input["password"])
                   && (($input['id'] == Session::getLoginUserID())
                       || $this->currentUserHaveMoreRightThan($input['id'])
                       || (($input['password_forget_token'] == $this->fields['password_forget_token']) // Permit to change password with token and email
                           && (abs(strtotime($_SESSION["glpi_currenttime"])
                               -strtotime($this->fields['password_forget_token_date'])) < DAY_TIMESTAMP)
                           && $this->isEmail($input['email'])))) {
                  $input["password"]
                     = sha1(Toolbox::unclean_cross_side_scripting_deep(stripslashes($input["password"])));

               } else {
                  unset($input["password"]);
               }
               unset($input["password2"]);

            } else {
               Session::addMessageAfterRedirect(__('Error: the two passwords do not match'),
                                                false, ERROR);
               return false;
            }
         }

      } else if (isset($input["password"])) { // From login
         unset($input["password"]);
      }

      // Update User in the database
      if (!isset($input["id"])
          && isset($input["name"])) {
         if ($this->getFromDBbyName($input["name"])) {
            $input["id"] = $this->fields["id"];
         }
      }

      if (isset($input["entities_id"])
          && (Session::getLoginUserID() === $input['id'])) {
         $_SESSION["glpidefault_entity"] = $input["entities_id"];
      }

      // Security on default profile update
      if (isset($input['profiles_id'])) {
         if (!in_array($input['profiles_id'], Profile_User::getUserProfiles($input['id']))) {
            unset($input['profiles_id']);
         }
      }

      // Security on default entity  update
      if (isset($input['entities_id'])) {
         if (!in_array($input['entities_id'], Profile_User::getUserEntities($input['id']))) {
            unset($input['entities_id']);
         }
      }

      if (isset($input['_reset_personal_token'])) {
         $input['personal_token']      = self::getUniquePersonalToken();
         $input['personal_token_date'] = $_SESSION['glpi_currenttime'];
      }


      // Manage preferences fields
      if (Session::getLoginUserID() === $input['id']) {
         if (isset($input['use_mode'])
             && ($_SESSION['glpi_use_mode'] !=  $input['use_mode'])) {
            $_SESSION['glpi_use_mode'] = $input['use_mode'];
            //Session::loadLanguage();
         }
      }

      foreach ($CFG_GLPI['user_pref_field'] as $f) {
         if (isset($input[$f])) {
            if (Session::getLoginUserID() === $input['id']) {
               if ($_SESSION["glpi$f"] != $input[$f]) {
                  $_SESSION["glpi$f"] = $input[$f];
               }
            }
            if ($input[$f] == $CFG_GLPI[$f]) {
               $input[$f] = "NULL";
            }
         }
      }
      return $input;
   }


   function post_updateItem($history=1) {

      // Update emails  (use _useremails set from UI, not _emails set from LDAP)
      if (isset($this->input['_useremails']) && count($this->input['_useremails'])) {
         $useremail = new UserEmail();
         foreach ($this->input['_useremails'] as $id => $email) {
            $email = trim($email);

            // existing email
            if ($id > 0) {
               $params = array('id' => $id);

               // empty email : delete
               if (strlen($email) == 0) {
                  $useremail->delete($params);

               } else { // Update email
                  $params['email'] = $email;
                  if ($this->input['_default_email'] == $id) {
                     $params['is_default'] = 1;
                  }
                  $useremail->update($params);
               }

            } else { // New email
               $email_input = array('email'    => $email,
                                    'users_id' => $this->getID());
               if (isset($this->input['_default_email'])
                   && ($this->input['_default_email'] == $id)) {
                  $email_input['is_default'] = 1;
               } else {
                  $email_input['is_default'] = 0;
               }
               $useremail->add($email_input);
            }
         }
      }

      $this->syncLdapGroups();
      $this->syncDynamicEmails();
      $this->applyRightRules();
   }



   // SPECIFIC FUNCTIONS
   /**
    * Apply rules to determine dynamic rights of the user
    *
    * @return boolean : true if we play the Rule Engine
   **/
   function applyRightRules() {
      global $DB;

      $return = false;
      if ((isset($this->fields['_ruleright_process'])
           || isset($this->input['_ruleright_process'])) // Add after a getFromLDAP
          && isset($this->fields["authtype"])
          && (($this->fields["authtype"] == Auth::LDAP)
              || ($this->fields["authtype"] == Auth::MAIL)
              || Auth::isAlternateAuth($this->fields["authtype"]))) {

         $dynamic_profiles = Profile_User::getForUser($this->fields["id"], true);

         if (isset($this->fields["id"])
             && ($this->fields["id"] > 0)
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

            $retrieved_dynamic_profiles = array();

            //For each affectation -> write it in DB
            foreach ($entities_rules as $entity) {
               //Multiple entities assignation
               if (is_array($entity[0])) {
                  foreach ($entity[0] as $tmp => $ent) {
                     $affectation['entities_id']  = $ent;
                     $affectation['profiles_id']  = $entity[1];
                     $affectation['is_recursive'] = $entity[2];
                     $affectation['users_id']     = $this->fields['id'];
                     $affectation['is_dynamic']   = 1;

                     $retrieved_dynamic_profiles[] = $affectation;
                  }
               } else {
                  $affectation['entities_id']   = $entity[0];
                  $affectation['profiles_id']   = $entity[1];
                  $affectation['is_recursive']  = $entity[2];
                  $affectation['users_id']      = $this->fields['id'];
                  $affectation['is_dynamic']    = 1;

                  $retrieved_dynamic_profiles[] = $affectation;
               }
            }

            if ((count($entities) > 0)
                && (count($rights) == 0)) {
               if ($def_prof = Profile::getDefault()) {
                  $rights[] = $def_prof;
               }
            }

            if ((count($rights) > 0)
                && (count($entities) > 0)) {
               foreach ($rights as $right) {
                  foreach ($entities as $entity) {
                     $affectation['entities_id']   = $entity[0];
                     $affectation['profiles_id']   = $right;
                     $affectation['users_id']      = $this->fields['id'];
                     $affectation['is_recursive']  = $entity[1];
                     $affectation['is_dynamic']    = 1;

                     $retrieved_dynamic_profiles[] = $affectation;
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
                         && ($db_profile['entities_id']  == $retr_profile['entities_id'])
                         && ($db_profile['profiles_id']  == $retr_profile['profiles_id'])
                         && ($db_profile['is_recursive'] == $retr_profile['is_recursive'])) {

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

            //Unset all the temporary tables
            unset($this->input["_ldap_rules"]);

            $return = true;
         }

         // Delete old dynamic profiles
         if (count($dynamic_profiles)) {
            $right = new Profile_User();
            foreach ($dynamic_profiles as $keydb => $db_profile) {
               $right->delete($db_profile);
            }
         }

      }
      return $return;
   }


   /**
    * Synchronise LDAP group of the user
   **/
   function syncLdapGroups() {
      global $DB;

      // input["_groups"] not set when update from user.form or preference
      if (isset($this->fields["authtype"])
          && isset($this->input["_groups"])
          && (($this->fields["authtype"] == Auth::LDAP)
              || Auth::isAlternateAuth($this->fields['authtype']))) {

         if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
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

               $result    = $DB->query($query);
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
    * Synchronise Dynamics emails of the user
    *
    * Use _emails (set from getFromLDAP), not _usermails set from UI
   **/
   function syncDynamicEmails() {
      global $DB;

      // input["_emails"] not set when update from user.form or preference
      if (isset($this->fields["authtype"])
          && isset($this->input["_emails"])
          && (($this->fields["authtype"] == Auth::LDAP)
              || Auth::isAlternateAuth($this->fields['authtype'])
              || ($this->fields["authtype"] == Auth::MAIL))) {

         if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
            $authtype = Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);

            if (count($authtype)
                || $this->fields["authtype"] == Auth::EXTERNAL) {
               // Clean emails
               $this->input["_emails"] = array_unique ($this->input["_emails"]);

               // Delete not available groups like to LDAP
               $query = "SELECT `glpi_useremails`.`id`,
                                `glpi_useremails`.`users_id`,
                                `glpi_useremails`.`email`,
                                `glpi_useremails`.`is_dynamic`
                         FROM `glpi_useremails`
                         WHERE `glpi_useremails`.`users_id` = '" . $this->fields["id"] . "'";

               $result    = $DB->query($query);
               $useremail = new UserEmail();
               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $i = array_search($data["email"], $this->input["_emails"]);
                     if ($i !== false) {
                        // Delete found item in order not to add it again
                        unset($this->input["_emails"][$i]);
                     } else if ($data['is_dynamic']) {
                        // Delete not found email
                        $useremail->delete(array('id' => $data["id"]));
                     }
                  }
               }

               //If the email need to be added
               if (count($this->input["_emails"]) > 0) {
                  foreach ($this->input["_emails"] as $email) {
                     $useremail->add(array('users_id'   => $this->fields["id"],
                                           'email'      => $email,
                                           'is_dynamic' => 1));
                  }
                  unset ($this->input["_emails"]);
               }
            }
         }
      }
   }


   /**
    * @see CommonDBTM::getName()
   **/
   function getName($options=array()) {

      if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
         return formatUserName($this->fields["id"],
                               $this->fields["name"],
                               (isset($this->fields["realname"]) ? $this->fields["realname"] : ''),
                               (isset($this->fields["firstname"]) ? $this->fields["firstname"] : ''));
      }
      return NOT_AVAILABLE;
   }


   /**
    * Function that try to load from LDAP the user membership
    * by searching in the attribute of the User
    *
    * @param $ldap_connection    ldap connection descriptor
    * @param $ldap_method        LDAP method
    * @param $userdn             Basedn of the user
    * @param $login              User login
    *
    * @return String : basedn of the user / false if not founded
   **/
   private function getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login) {
      global $DB;

      // Search in DB the ldap_field we need to search for in LDAP
      $query = "SELECT DISTINCT `ldap_field`
                FROM `glpi_groups`
                WHERE `ldap_field` != ''
                ORDER BY `ldap_field`";
      $group_fields = array();

      foreach ($DB->request($query) as $data) {
         $group_fields[] = Toolbox::strtolower($data["ldap_field"]);
      }
      if (count($group_fields)) {
         //Need to sort the array because edirectory don't like it!
         sort($group_fields);

         // If the groups must be retrieve from the ldap user object
         $sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $group_fields);
         $v  = AuthLDAP::get_entries_clean($ldap_connection, $sr);

         for ($i=0 ; $i<count($v['count']) ; $i++) {
            //Try to find is DN in present and needed: if yes, then extract only the OU from it
            if ((($ldap_method["group_field"] == 'dn') || in_array('ou', $group_fields))
                && isset($v[$i]['dn'])) {

               $v[$i]['ou'] = array();
               for ($tmp=$v[$i]['dn'] ; count($tmptab=explode(',',$tmp,2))==2 ; $tmp=$tmptab[1]) {
                  $v[$i]['ou'][] = $tmptab[1];
               }

               // Search in DB for group with ldap_group_dn
               if (($ldap_method["group_field"] == 'dn')
                   && (count($v[$i]['ou']) > 0)) {
                  $query = "SELECT `id`
                            FROM `glpi_groups`
                            WHERE `ldap_group_dn`
                                       IN ('".implode("', '",
                                                      Toolbox::addslashes_deep($v[$i]['ou']))."')";

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
                   && ($v[$i][$field]['count'] > 0)) {

                  unset($v[$i][$field]['count']);
                  $query = "SELECT `id`
                            FROM `glpi_groups`
                            WHERE `ldap_field` = '$field'
                                  AND `ldap_value`
                                       IN ('".implode("', '",
                                                      Toolbox::addslashes_deep($v[$i][$field]))."')";

                  foreach ($DB->request($query) as $group) {
                     $this->fields["_groups"][] = $group['id'];
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
    * @param $ldap_connection    ldap connection descriptor
    * @param $ldap_method        LDAP method
    * @param $userdn             Basedn of the user
    * @param $login              User login
    *
    * @return nothing : false if not applicable
   **/
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

      $v = $this->ldap_get_user_groups($ldap_connection, $ldap_method["basedn"],
                                       $user_tmp,
                                       $ldap_method["group_condition"],
                                       $ldap_method["group_member_field"],
                                       $ldap_method["use_dn"],
                                       $ldap_method["login_field"]);
      foreach ($v as $result) {
         if (isset($result[$ldap_method["group_member_field"]])
             && is_array($result[$ldap_method["group_member_field"]])
             && (count($result[$ldap_method["group_member_field"]]) > 0)) {

            $query = "SELECT `id`
                      FROM `glpi_groups`
                      WHERE `ldap_group_dn`
                        IN ('".implode("', '",
                                       Toolbox::addslashes_deep($result[$ldap_method["group_member_field"]]))."')";

            foreach ($DB->request($query) as $group) {
               $this->fields["_groups"][] = $group['id'];
            }
         }
      }
      return true;
   }


   /**
    * Function that try to load from LDAP the user information...
    *
    * @param $ldap_connection          ldap connection descriptor
    * @param $ldap_method              LDAP method
    * @param $userdn                   Basedn of the user
    * @param $login                    User Login
    * @param $import          boolean  true for import, false for update (true by default)
    *
    * @return boolean : true if found / false if not founded
   **/
   function getFromLDAP($ldap_connection, $ldap_method, $userdn, $login, $import=true) {
      global $DB, $CFG_GLPI;

      // we prevent some delay...
      if (empty($ldap_method["host"])) {
         return false;
      }

      if ($ldap_connection) {
         //Set all the search fields
         $this->fields['password'] = "";

         $fields  = AuthLDAP::getSyncFields($ldap_method);

         //Hook to allow plugin to request more attributes from ldap
         $fields = Plugin::doHookFunction("retrieve_more_field_from_ldap", $fields);

         $fields  = array_filter($fields);
         $f       = array_values($fields);

         $sr      = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $f);
         $v       = AuthLDAP::get_entries_clean($ldap_connection, $sr);

         if (!is_array($v)
             || ( count($v) == 0)
             || empty($v[0][$fields['name']][0])) {
            return false;
         }

        //Store user's dn
        $this->fields['user_dn']    = addslashes($userdn);
        //Store date_sync
        $this->fields['date_sync']  = $_SESSION['glpi_currenttime'];
        // Empty array to ensure than syncDynamicEmails will be done
        $this->fields["_emails"]    = array();

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
                  case "email1" :
                  case "email2" :
                  case "email3" :
                  case "email4" :
                     // Manage multivaluable fields
                     if (!empty($v[0][$e])) {
                        foreach ($v[0][$e] as $km => $m) {
                           if (!preg_match('/count/',$km)) {
                              $this->fields["_emails"][] = addslashes($m);
                           }
                        }
                        // Only get them once if duplicated
                        $this->fields["_emails"] = array_unique($this->fields["_emails"]);
                     }
                     break;

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
         if (($ldap_method["group_search_type"] == 0)
             || ($ldap_method["group_search_type"] == 2)) {
            $this->getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login);
         }

         ///The groups are retrived by looking into an ldap group object
         if (($ldap_method["group_search_type"] == 1)
             || ($ldap_method["group_search_type"] == 2)) {
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

            $this->fields = $rule->processAllRules($groups, Toolbox::stripslashes_deep($this->fields),
                                                   array('type'        => 'LDAP',
                                                         'ldap_server' => $ldap_method["id"],
                                                         'connection'  => $ldap_connection,
                                                         'userdn'      => $userdn));

            $this->fields['_ruleright_process'] = true;

            //If rule  action is ignore import
            if ($import
                && isset($this->fields["_stop_import"])) {
               return false;
            }
            //or no rights found & do not import users with no rights
            if ($import
                && !$CFG_GLPI["use_noright_users_add"]) {
               $ok = false;
               if (isset($this->fields["_ldap_rules"])
                   && count($this->fields["_ldap_rules"])) {
                  if (isset($this->fields["_ldap_rules"]["rules_entities_rights"])
                      && count($this->fields["_ldap_rules"]["rules_entities_rights"])) {
                     $ok = true;
                  }
                  if (!$ok) {
                     $entity_count = 0;
                     $right_count  = 0;
                     if (Profile::getDefault()) {
                        $right_count++;
                     }
                     if (isset($this->fields["_ldap_rules"]["rules_entities"])) {
                        $entity_count += count($this->fields["_ldap_rules"]["rules_entities"]);
                     }
                     if (isset($this->input["_ldap_rules"]["rules_rights"])) {
                        $right_count += count($this->fields["_ldap_rules"]["rules_rights"]);
                     }
                     if ($entity_count && $right_count) {
                        $ok = true;
                     }
                  }
               }
               if (!$ok) {
                  $this->fields["_stop_import"] = true;
                  return false;
               }
            }

            // Add ldap result to data send to the hook
            $this->fields['_ldap_result'] = $v;
            $this->fields['_ldap_conn']   = $ldap_connection;
            //Hook to retrieve more information for ldap
            $this->fields = Plugin::doHookFunction("retrieve_more_data_from_ldap", $this->fields);
            unset($this->fields['_ldap_result']);
         }
         return true;
      }
      return false;

   } // getFromLDAP()


   /**
    * Get all groups a user belongs to
    *
    * @param $ds                             ldap connection
    * @param $ldap_base_dn                   Basedn used
    * @param $user_dn                        Basedn of the user
    * @param $group_condition                group search condition
    * @param $group_member_field             group field member in a user object
    * @param $use_dn                boolean  search dn of user ($login_field=$user_dn) in group_member_field
    * @param $login_field           string   user login field
    *
    * @return String : basedn of the user / false if not founded
   **/
   function ldap_get_user_groups($ds, $ldap_base_dn, $user_dn, $group_condition,
                                 $group_member_field, $use_dn, $login_field) {

      $groups     = array();
      $listgroups = array();

      //User dn may contain ( or ), need to espace it!
      $user_dn = str_replace(array("(", ")", "\,", "\+"), array("\(", "\)", "\\\,", "\\\+"),
                             $user_dn);

      //Only retrive cn and member attributes from groups
      $attrs = array('dn');

      if (!$use_dn) {
         $filter = "(& $group_condition (|($group_member_field=$user_dn)
                                          ($group_member_field=$login_field=$user_dn)))";
      } else {
         $filter = "(& $group_condition ($group_member_field=$user_dn))";
      }

      //Perform the search
      $filter = Toolbox::unclean_cross_side_scripting_deep($filter);
      $sr = ldap_search($ds, $ldap_base_dn, $filter, $attrs);

      //Get the result of the search as an array
      $info = AuthLDAP::get_entries_clean($ds, $sr);
      //Browse all the groups
      for ($i = 0 ; $i < count($info) ; $i++) {
         //Get the cn of the group and add it to the list of groups
         if (isset($info[$i]["dn"]) && ($info[$i]["dn"] != '')) {
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
    * @param $mail_method  mail method description array
    * @param $name         login of the user
   **/
   function getFromIMAP($mail_method, $name) {
      global $DB;

      // we prevent some delay..
      if (empty($mail_method["host"])) {
         return false;
      }

      // some defaults...
      $this->fields['password']  = "";
      // Empty array to ensure than syncDynamicEmails will be done
      $this->fields["_emails"]   = array();
      $email                     = '';
      if (strpos($name,"@")) {
         $email = $name;
      } else {
         $email = $name . "@" . $mail_method["host"];
      }
      $this->fields["_emails"][] = $email;

      $this->fields['name']      = $name;
      //Store date_sync
      $this->fields['date_sync'] = $_SESSION['glpi_currenttime'];

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
         $this->fields = $rule->processAllRules($groups, Toolbox::stripslashes_deep($this->fields),
                                                array('type'        => 'MAIL',
                                                      'mail_server' => $mail_method["id"],
                                                      'email'       => $email));
         $this->fields['_ruleright_process'] = true;
      }
      return true;
   } // getFromIMAP()


   /**
    * Function that try to load from the SSO server the user information...
    *
    * @since version 0.84
   **/
   function getFromSSO() {
      global $DB, $CFG_GLPI;

      $a_field = array();
      foreach ($CFG_GLPI as $key=>$value) {
         if (!is_array($value) && !empty($value)
             && strstr($key, "_ssofield")) {
            $key = str_replace('_ssofield', '', $key);
            $a_field[$key] = $value;
         }
      }

      if (count($a_field) == 0) {
         return true;
      }
      $this->fields['_ruleright_process'] = true;
      foreach ($a_field as $field=>$value) {
         if (!isset($_SERVER[$value])
             || empty($_SERVER[$value])) {

            switch ($field) {
               case "title" :
                  $this->fields['usertitles_id'] = 0;
                  break;

               case "category" :
                  $this->fields['usercategories_id'] = 0;
                  break;

               default :
                  $this->fields[$field] = "";
            }

         } else {
            switch ($field) {
               case "email1" :
               case "email2" :
               case "email3" :
               case "email4" :
                  // Manage multivaluable fields
                  if (!preg_match('/count/',$_SERVER[$value])) {
                     $this->fields["_emails"][] = addslashes($_SERVER[$value]);
                  }
                  // Only get them once if duplicated
                  $this->fields["_emails"] = array_unique($this->fields["_emails"]);
                  break;

               case "language" :
                  $language = Config::getLanguage($_SERVER[$value]);
                  if ($language != '') {
                     $this->fields[$field] = $language;
                  }
                  break;

               case "title" :
                  $this->fields['usertitles_id']
                        = Dropdown::importExternal('UserTitle', addslashes($_SERVER[$value]));
                  break;

               case "category" :
                  $this->fields['usercategories_id']
                        = Dropdown::importExternal('UserCategory', addslashes($_SERVER[$value]));
                  break;

               default :
                  $this->fields[$field] = $_SERVER[$value];
                  break;

            }
         }
      }
       ///Only process rules if working on the master database
      if (!$DB->isSlave()) {
         //Instanciate the affectation's rule
         $rule = new RuleRightCollection();

         $this->fields = $rule->processAllRules(array(), Toolbox::stripslashes_deep($this->fields),
                                                array('type'   => 'SSO',
                                                      'email'  => $this->fields["_emails"],
                                                      'login'  => $this->fields["name"]));

         //If rule  action is ignore import
         if (isset($this->fields["_stop_import"])) {
            return false;
         }
      }
      return true;
   }


   /**
    * Blank passwords field of a user in the DB
    * needed for external auth users
   **/
   function blankPassword() {
      global $DB;

      if (!empty($this->fields["name"])) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `password` = ''
                   WHERE `name` = '" . $this->fields["name"] . "'";
         $DB->query($query);
      }
   }


   /**
    * Print a good title for user pages
    *
    * @return nothing (display)
   **/
   function title() {
      global $CFG_GLPI;

      $buttons = array();
      $title   = self::getTypeName(2);

      if (static::canCreate()) {
         $buttons["user.form.php"] = __('Add user...');
         $title                    = "";

         if (Auth::useAuthExt()) {
            // This requires write access because don't use entity config.
            $buttons["user.form.php?new=1&amp;ext_auth=1"] = __('... From an external source');
         }
      }
      if (Session::haveRight("import_externalauth_users", "w")) {
         if (AuthLdap::useAuthLdap()) {
            $buttons["ldap.php"] = __('LDAP directory link');
         }
      }
      Html::displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", self::getTypeName(2), $title,
                         $buttons);
   }


   /**
    * Is the specified user have more right than the current one ?
    *
    * @param $ID  integer : Id of the user
    *
    * @return boolean : true if currrent user have the same right or more right
   **/
   function currentUserHaveMoreRightThan($ID) {

      $user_prof = Profile_User::getUserProfiles($ID);
      return Profile::currentUserHaveMoreRightThan($user_prof);
   }


   /**
    * Print the user form
    *
    * @param $ID        integer : Id of the user
    * @param $options   array
    *     - target form target
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean : user found
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      // Affiche un formulaire User
      if (($ID != Session::getLoginUserID()) && !Session::haveRight("user", "r")) {
         return false;
      }

      $this->initForm($ID, $options);

      if ($ID) {
         $caneditpassword = $this->currentUserHaveMoreRightThan($ID);
      } else {
         // can edit on creation form
         $caneditpassword = true;
      }

      $extauth         = !(($this->fields["authtype"] == Auth::DB_GLPI)
                           || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                               && !empty($this->fields["password"])));

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Login') . "</td>";
      // si on est dans le cas d'un ajout , cet input ne doit plus etre hidden
      if ($this->fields["name"] == "") {
         echo "<td><input name='name' value=\"" . $this->fields["name"] . "\"></td>";
         // si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe

      } else {
         if (!empty($this->fields["password"])
             || ($this->fields["authtype"] == Auth::DB_GLPI)) {
            echo "<td>";
            echo "<input name='name' value=\"" . $this->fields["name"] . "\">";
         } else {
            echo "<td class='b'>" . $this->fields["name"];
            echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
         }
         echo "</td>";
      }

      //do some rights verification
      if (Session::haveRight("user", "w")
          && (!$extauth || empty($ID))
          && $caneditpassword) {
         echo "<td>" . __('Password')."</td>";
         echo "<td><input id='password' type='password' name='password' value='' size='20'
                    autocomplete='off' onkeyup=\"return passwordCheck();\">";
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Surname') . "</td><td>";
      Html::autocompletionTextField($this,"realname");
      echo "</td>";

       //do some rights verification
      if (Session::haveRight("user", "w")
          && (!$extauth || empty($ID))
          && $caneditpassword) {
         echo "<td>" . __('Password confirmation') . "</td>";
         echo "<td><input type='password' name='password2' value='' size='20' autocomplete='off'>";
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";


      echo "<tr class='tab_bg_1'><td>" . __('First name') . "</td><td>";
      Html::autocompletionTextField($this, "firstname");
      echo "</td>";

      if (Session::haveRight("user", "w")
          && (!$extauth || empty($ID))
          && $caneditpassword) {
         echo "<td>".__('Password security policy')."</td>";
         echo "<td>";
         Config::displayPasswordSecurityChecks();
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . _n('Email','Emails',2);
      UserEmail::showAddEmailButton($this);
      echo "</td><td>";
      UserEmail::showForUser($this);
      echo "</td>";

      //Authentications information : auth method used and server used
      //don't display is creation of a new user'
      if (!empty($ID)) {
         if (Session::haveRight("user_authtype", "r")) {
            echo "<td>" . __('Authentication') . "</td><td>";
            echo Auth::getMethodName($this->fields["authtype"], $this->fields["auths_id"]);
            if (!empty($this->fields["date_sync"])) {
               //TRANS: %s is the date of last sync
               echo '<br>'.sprintf(__('Last synchronization on %s'),
                                   HTML::convDateTime($this->fields["date_sync"]));
            }
            if (!empty($this->fields["user_dn"])) {
               //TRANS: %s is the user dn
               echo '<br>'.sprintf(__('%1$s: %2$s'), __('User DN'), $this->fields["user_dn"]);
            }

            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
      } else {
         echo "<td colspan='2'><input type='hidden' name='authtype' value='1'></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" .  __('Phone') . "</td><td>";
      Html::autocompletionTextField($this, "phone");
      echo "</td>";

      echo "<td>".__('Active')."</td><td>";
      Dropdown::showYesNo('is_active',$this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Mobile phone') . "</td><td>";
      Html::autocompletionTextField($this, "mobile");
      echo "</td>";
      echo "<td>" . __('Category') . "</td><td>";
      UserCategory::dropdown(array('value' => $this->fields["usercategories_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" .  __('Phone 2') . "</td><td>";
      Html::autocompletionTextField($this, "phone2");
      echo "</td>";
      echo "<td rowspan='4' class='middle'>" . __('Comments') . "</td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='45' rows='6' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Administrative number') . "</td><td>";
      Html::autocompletionTextField($this, "registration_number");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . _x('person','Title') . "&nbsp;:</td><td>";
      UserTitle::dropdown(array('value' => $this->fields["usertitles_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Location') . "</td><td>";
      if (!empty($ID)) {
         $entities = Profile_User::getUserEntities($ID, true);
         if (count($entities) > 0) {
            Location::dropdown(array('value'  => $this->fields["locations_id"],
                                     'entity' => $entities));
         } else {
            echo "&nbsp;";
         }

      } else {
         if (!Session::isMultiEntitiesMode()) {
            // Display all locations : only one entity
            Location::dropdown(array('value' => $this->fields["locations_id"]));
         } else {
            echo "&nbsp;";
         }
      }
      echo "</td></tr>";

      if (empty($ID)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" .  __('Profile') . "</td><td>";
         Profile::dropdownUnder(array('name'  => '_profiles_id',
                                      'value' => Profile::getDefault()));

         echo "</td><td>" .  __('Entity') . "</td><td>";
         Entity::dropdown(array('name'                => '_entities_id',
                                'display_emptychoice' => false,
                                'entity'              => $_SESSION['glpiactiveentities']));
         echo "</td></tr>";
      } else {
         if ($caneditpassword) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" .  __('Default profile') . "</td><td>";

            $options[0] = Dropdown::EMPTY_VALUE;
            $options   += Dropdown::getDropdownArrayNames('glpi_profiles',
                                                          Profile_User::getUserProfiles($this->fields['id']));
            Dropdown::showFromArray("profiles_id", $options,
                                    array('value' => $this->fields["profiles_id"]));

            echo "</td><td>" .  __('Default entity') . "</td><td>";
            $entities = Profile_User::getUserEntities($this->fields['id'],1);
            Entity::dropdown(array('value'  => $this->fields["entities_id"],
                                   'entity' => $entities));
            echo "</td></tr>";
         }

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>" ;
         //TRANS: %s is the date
         printf(__('Last update on %s'), HTML::convDateTime($this->fields["date_mod"]));
         echo "<br>";
         printf(__('Last login on %s'), HTML::convDateTime($this->fields["last_login"]));
         echo "</td><td colspan='2'class='center'>";

         if ($ID > 0) {
            echo "<a target='_blank' href='".$CFG_GLPI["root_doc"].
                  "/front/user.form.php?getvcard=1&amp;id=$ID'>". __('Vcard')."</a>";
         }
         echo "</td></tr>";
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /** Print the user personnal information for check
    *
    * @param $userid Interger ID of the user
    *
    * @since version 0.84
   **/
   static function showPersonalInformation($userid) {
      global $CFG_GLPI;

      $user = new self();
      if (!$user->can($userid,'r')
          && ($userid != Session::getLoginUserID())) {
         return false;
      }
      echo "<table class='tab_glpi left' width='100%'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' width='20%'>";
      _e('Name');
      echo "</td><td width='30%'>";
      echo getUserName($userid);
      echo "</td>";
      echo "<td class='b'  width='20%'>";
      _e('Phone');
      echo "</td><td width='30%'>";
      echo $user->getField('phone');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b'>";
      _e('Phone 2');
      echo "</td><td>";
      echo $user->getField('phone2');
      echo "</td>";
      echo "<td class='b'>";
      _e('Mobile phone');
      echo "</td><td>";
      echo $user->getField('mobile');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b'>";
      _e('Location');
      echo "</td><td>";
      echo Dropdown::getDropdownName('glpi_locations', $user->getField('locations_id'));
      echo "</td>";
      echo "<td colspan='2' class='center'>";
      if ($userid == Session::getLoginUserID()) {
         echo "<a href='".$CFG_GLPI['root_doc']."/front/preference.php' class='vsubmit'>".
               __('Edit')."</a>";
      } else {
         echo "&nbsp;";
      }
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }


   /**
    * Print the user preference form
    *
    * @param $target          form target
    * @param $ID     integer  Id of the user
    *
    * @return boolean : user found
   **/
   function showMyForm($target, $ID) {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      // Affiche un formulaire User
      if (($ID != Session::getLoginUserID())
          && !$this->currentUserHaveMoreRightThan($ID)) {
         return false;
      }
      if ($this->getFromDB($ID)) {
         $authtype = $this->getAuthMethodsByID();

         $extauth = !(($this->fields["authtype"] == Auth::DB_GLPI)
                      || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                          && !empty($this->fields["password"])));

         // No autocopletion :
         $save_autocompletion                 = $CFG_GLPI["use_ajax_autocompletion"];
         $CFG_GLPI["use_ajax_autocompletion"] = false;

         echo "<div class='center'>";
         echo "<form method='post' name='user_manager' action='".$target."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>".sprintf(__('%1$s: %2$s'), __('Login'), $this->fields["name"]);
         echo "<input type='hidden' name='name' value='" . $this->fields["name"] . "'>";
         echo "<input type='hidden' name='id' value='" . $this->fields["id"] . "'>";
         echo "</th></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Surname') . "</td><td>";

         if ($extauth
             && isset($authtype['realname_field'])
             && !empty($authtype['realname_field'])) {

            echo $this->fields["realname"];
         } else {
            Html::autocompletionTextField($this, "realname");
         }
         echo "</td>";

         //do some rights verification
         if (!$extauth
             && Session::haveRight("password_update", "1")) {
            echo "<td>" . __('Password') . "</td>";
            echo "<td><input id='password' type='password' name='password' value='' size='30' autocomplete='off' onkeyup=\"return passwordCheck();\">";
            echo "</td></tr>";
         } else {
            echo "<td colspan='2'></tr>";
         }

         echo "<tr class='tab_bg_1'><td>" . __('First name') . "</td><td>";
         if ($extauth
             && isset($authtype['firstname_field'])
             && !empty($authtype['firstname_field'])) {

            echo $this->fields["firstname"];
         } else {
            Html::autocompletionTextField($this, "firstname");
         }
         echo "</td>";

         if (!$extauth
             && Session::haveRight("password_update", "1")) {
            echo "<td>" . __('Password confirmation') . "</td>";
            echo "<td><input type='password' name='password2' value='' size='30' autocomplete='off'>";
            echo "</td></tr>";
         } else {
            echo "<td colspan='2'></tr>";
         }

         echo "<tr class='tab_bg_1'><td class='top'>" . _n('Email', 'Emails',2);
         UserEmail::showAddEmailButton($this);
         echo "</td><td>";
         UserEmail::showForUser($this);
         echo "</td>";

         if (!$extauth
             && Session::haveRight("password_update", "1")) {
            echo "<td>".__('Password security policy')."</td>";
            echo "<td>";
            Config::displayPasswordSecurityChecks();
            echo "</td>";
         } else {
            echo "<td colspan='2'>";
         }
         echo "</tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Mobile phone') . "&nbsp;:</td><td>";

         if ($extauth
             && isset($authtype['mobile_field']) && !empty($authtype['mobile_field'])) {
            echo $this->fields["mobile"];
         } else {
            Html::autocompletionTextField($this, "mobile");
         }
         echo "</td>";

         if (!GLPI_DEMO_MODE) {
            echo "<td>" . __('Language') . "</td><td>";
            // Use session variable because field in table may be null if same of the global config
            Dropdown::showLanguages("language", array('value' => $_SESSION["glpilanguage"]));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";


         echo "<tr class='tab_bg_1'><td>" .  __('Phone') . "</td><td>";

         if ($extauth
             && isset($authtype['phone_field']) && !empty($authtype['phone_field'])) {
            echo $this->fields["phone"];
         } else {
            Html::autocompletionTextField($this, "phone");
         }
         echo "</td>";

         if (count($_SESSION['glpiprofiles']) >1) {
            echo "<td>" . __('Default profile') . "</td><td>";

            $options  = array(0 => Dropdown::EMPTY_VALUE);
            $options += Dropdown::getDropdownArrayNames('glpi_profiles',
                                                        Profile_User::getUserProfiles($this->fields['id']));
            Dropdown::showFromArray("profiles_id", $options,
                                    array('value' => $this->fields["profiles_id"]));

         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";



         echo "<tr class='tab_bg_1'><td>" .  __('Phone 2') . "</td><td>";

         if ($extauth
             && isset($authtype['phone2_field']) && !empty($authtype['phone2_field'])) {
            echo $this->fields["phone2"];
         } else {
            Html::autocompletionTextField($this, "phone2");
         }
         echo "</td>";

         $entities = Profile_User::getUserEntities($this->fields['id'], 1);
         if (!GLPI_DEMO_MODE
             && (count($_SESSION['glpiactiveentities']) > 1)) {
            echo "<td>" . __('Default entity') . "</td><td>";
            Entity::dropdown(array('value'  => $this->fields['entities_id'],
                                   'entity' => $entities));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";


         echo "<tr class='tab_bg_1'><td>" . __('Location') . "</td><td>";
         $entities = Profile_User::getUserEntities($ID, true);
         Location::dropdown(array('value'  => $this->fields['locations_id'],
                                  'entity' => $entities));

        if (Session::haveRight("config", "w")) {
            echo "<td>" . __('Use GLPI in mode') . "</td><td>";
            $modes[Session::NORMAL_MODE]      = __('Normal');
            //$modes[Session::TRANSLATION_MODE] = __('Translation');
            $modes[Session::DEBUG_MODE]       = __('Debug');
            Dropdown::showFromArray('use_mode', $modes, array('value' => $this->fields["use_mode"]));
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         echo "<tr><td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
         $CFG_GLPI["use_ajax_autocompletion"] = $save_autocompletion;
         return true;
      }
      return false;
   }


   /**
    * Get all the authentication method parameters for the current user
   **/
   function getAuthMethodsByID() {
      return Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);
   }


   function pre_updateInDB() {
      global $DB;

      if (($key = array_search('name',$this->updates)) !== false) {
         /// Check if user does not exists
         $query = "SELECT *
                   FROM `".$this->getTable()."`
                   WHERE `name` = '".$this->input['name']."'
                         AND `id` <> '".$this->input['id']."';";
         $result = $DB->query($query);

         if ($DB->numrows($result) > 0) {
            //To display a message
            $this->fields['name'] = $this->oldvalues['name'];
            unset($this->updates[$key]);
            unset($this->oldvalues['name']);
            Session::addMessageAfterRedirect(__('Unable to update login. A user already exists.'),
                                             false, ERROR);
         }
      }

      /// Security system except for login update
      if (Session::getLoginUserID()
          && !Session::haveRight("user", "w")
          && !strpos($_SERVER['PHP_SELF'], "login.php")) {

         if (Session::getLoginUserID() === $this->input['id']) {
            if (isset($this->fields["authtype"])) {

               // extauth ldap case
               if ($_SESSION["glpiextauth"]
                   && (($this->fields["authtype"] == Auth::LDAP)
                       || Auth::isAlternateAuth($this->fields["authtype"]))) {

                  $authtype = Auth::getMethodsByID($this->fields["authtype"],
                                                   $this->fields["auths_id"]);
                  if (count($authtype)) {
                     $fields = AuthLDAP::getSyncFields($authtype);
                     foreach ($fields as $key => $val) {
                        if (!empty($val)
                            && (($key2 = array_search($key, $this->updates)) !== false)) {

                           unset ($this->updates[$key2]);
                           unset($this->oldvalues[$key]);

                        }
                     }
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


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['add_user_group']  = __('Associate to a group');
         $actions['add_userprofile'] = __('Associate to a profile');
      }

      if (Session::haveRight("user_authtype","w")) {
         $actions['change_authtype']        = _x('button', 'Change the authentication method');
         $actions['force_user_ldap_update'] = __('Force synchronization');
      }
      return $actions;
   }


   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {
      global $CFG_GLPI;

      switch ($input['action']) {
         case "change_authtype" :
            $rand             = Auth::dropdown(array('name' => 'authtype'));
            $paramsmassaction = array('authtype' => '__VALUE__');

            Ajax::updateItemOnSelectEvent("dropdown_authtype$rand", "show_massiveaction_field",
                                          $CFG_GLPI["root_doc"].
                                             "/ajax/dropdownMassiveActionAuthMethods.php",
                                          $paramsmassaction);
            echo "<span id='show_massiveaction_field'><br><br>";
            echo "<input type='submit' name='massiveaction' class='submit' value='".
                   _sx('button','Post')."'>";
            echo "</span>\n";
            return true;

         case "add_user_group" :
            $gu = new Group_User();
            return $gu->showSpecificMassiveActionsParameters($input);

         case "add_userprofile" :
            Entity::dropdown(array('entity' => $_SESSION['glpiactiveentities']));
            echo ".&nbsp;"._n('Profile', 'Profiles', 1)."&nbsp;";
            Profile::dropdownUnder();
            echo ".&nbsp;".__('Recursive')."&nbsp;";
            Dropdown::showYesNo("is_recursive", 0);
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Add')."'>";
            return true;

         default :
            return parent::showSpecificMassiveActionsParameters($input);

      }
      return false;
   }


   /**
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "add_user_group" :
            $gu = new Group_User();
            return $gu->doSpecificMassiveActions($input);

         case "force_user_ldap_update" :
            if (Session::haveRight("user", "w")) {
               $ids = array();
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {
                     if ($this->getFromDB($key)) {
                        if (($this->fields["authtype"] == Auth::LDAP)
                            || ($this->fields["authtype"] == Auth::EXTERNAL)) {
                           if (AuthLdap::ldapImportUserByServerId(array('method'
                                                                           => AuthLDAP::IDENTIFIER_LOGIN,
                                                                        'value'
                                                                           => $this->fields["name"]),
                                                                        1, $this->fields["auths_id"])) {
                              $res['ok']++;
                           } else {
                              $res['ko']++;
                           }
                        }
                     } else {
                        $res['ko']++;
                     }
                  }
               }
            } else {
               $res['noright']++;
            }
            break;

         case "change_authtype" :
            if (!isset($input["authtype"])
                || !isset($input["auths_id"])) {
               return false;
            }
            if (Session::haveRight("user_authtype","w")) {
               $ids = array();
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {
                     $ids[] = $key;
                  }
               }
               if (User::changeAuthMethod($ids, $input["authtype"], $input["auths_id"])) {
                  $res['ok']++;
               } else {
                  $res['ko']++;
               }
            } else {
               $res['noright']++;
            }
            break;

         case "add_userprofile" :
            $right = new Profile_User();
            if (isset($input['profiles_id']) && ($input['profiles_id'] > 0)
                && isset($input['entities_id']) && ($input['entities_id'] >= 0)) {
               $input2                 = array();
               $input2['entities_id']  = $input['entities_id'];
               $input2['profiles_id']  = $input['profiles_id'];
               $input2['is_recursive'] = $input['is_recursive'];
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {
                     $input2['users_id'] = $key;
                     if ($right->can(-1,'w',$input2)) {
                        if ($right->add($input2)) {
                           $res['ok']++;
                        } else {
                           $res['ko']++;
                        }
                     } else {
                        $res['noright']++;
                     }
                  }
               }
            }
            break;

         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }


   function getSearchOptions() {

      // forcegroup by on name set force group by for all items
      $tab                             = array();
      $tab['common']                   = __('Characteristics');

      $tab[1]['table']                 = $this->getTable();
      $tab[1]['field']                 = 'name';
      $tab[1]['name']                  = __('Login');
      $tab[1]['datatype']              = 'itemlink';
      $tab[1]['forcegroupby']          = true;
      $tab[1]['massiveaction']         = false;

      $tab[2]['table']                 = $this->getTable();
      $tab[2]['field']                 = 'id';
      $tab[2]['name']                  = __('ID');
      $tab[2]['massiveaction']         = false;
      $tab[2]['datatype']              = 'number';

      $tab[34]['table']                = $this->getTable();
      $tab[34]['field']                = 'realname';
      $tab[34]['name']                 = __('Surname');
      $tab[34]['datatype']             = 'string';

      $tab[9]['table']                 = $this->getTable();
      $tab[9]['field']                 = 'firstname';
      $tab[9]['name']                  = __('First name');
      $tab[9]['datatype']              = 'string';

      $tab[5]['table']                 = 'glpi_useremails';
      $tab[5]['field']                 = 'email';
      $tab[5]['name']                  = _n('Email', 'Emails',2);
      $tab[5]['datatype']              = 'email';
      $tab[5]['joinparams']            = array('jointype'=>'child');
      $tab[5]['forcegroupby']          = true;
      $tab[5]['massiveaction']         = false;

      $tab += Location::getSearchOptionsToAdd();

      $tab[8]['table']                 = $this->getTable();
      $tab[8]['field']                 = 'is_active';
      $tab[8]['name']                  = __('Active');
      $tab[8]['datatype']              = 'bool';

      $tab[6]['table']                 = $this->getTable();
      $tab[6]['field']                 = 'phone';
      $tab[6]['name']                  =  __('Phone');
      $tab[6]['datatype']              = 'string';

      $tab[10]['table']                = $this->getTable();
      $tab[10]['field']                = 'phone2';
      $tab[10]['name']                 =  __('Phone 2');
      $tab[10]['datatype']             = 'string';

      $tab[11]['table']                = $this->getTable();
      $tab[11]['field']                = 'mobile';
      $tab[11]['name']                 = __('Mobile phone');
      $tab[11]['datatype']             = 'string';

      $tab[13]['table']                = 'glpi_groups';
      $tab[13]['field']                = 'completename';
      $tab[13]['name']                 = _n('Group','Groups',2);
      $tab[13]['forcegroupby']         = true;
      $tab[13]['datatype']             = 'itemlink';
      $tab[13]['massiveaction']        = false;
      $tab[13]['joinparams']           = array('beforejoin'
                                                => array('table'      => 'glpi_groups_users',
                                                         'joinparams' => array('jointype'=>'child')));

      $tab[14]['table']                = $this->getTable();
      $tab[14]['field']                = 'last_login';
      $tab[14]['name']                 = __('Last login');
      $tab[14]['datatype']             = 'datetime';
      $tab[14]['massiveaction']        = false;

      $tab[15]['table']                = 'glpi_users';
      $tab[15]['field']                = 'authtype';
      $tab[15]['name']                 = __('Authentication');
      $tab[15]['massiveaction']        = false;
      $tab[15]['datatype']             = 'specific';
      $tab[15]['searchtype']           = 'equals';
      $tab[15]['additionalfields']     = array('auths_id');

      $tab[30]['table']                = 'glpi_authldaps';
      $tab[30]['field']                = 'name';
      $tab[30]['linkfield']            = 'auths_id';
      $tab[30]['name']                 = __('LDAP directory for authentication');
      $tab[30]['massiveaction']        = false;
      $tab[30]['joinparams']           = array('condition' => "AND REFTABLE.`authtype` = ".Auth::LDAP);
      $tab[30]['datatype']             = 'dropdown';

      $tab[31]['table']                = 'glpi_authmails';
      $tab[31]['field']                = 'name';
      $tab[31]['linkfield']            = 'auths_id';
      $tab[31]['name']                 = __('Email server for authentication');
      $tab[31]['massiveaction']        = false;
      $tab[31]['joinparams']           = array('condition' => "AND REFTABLE.`authtype` = ".Auth::MAIL);
      $tab[31]['datatype']             = 'dropdown';

      $tab[16]['table']                = $this->getTable();
      $tab[16]['field']                = 'comment';
      $tab[16]['name']                 = __('Comments');
      $tab[16]['datatype']             = 'text';

      $tab[17]['table']                = $this->getTable();
      $tab[17]['field']                = 'language';
      $tab[17]['name']                 = __('Language');
      $tab[17]['datatype']             = 'language';
      $tab[17]['display_emptychoice']  = true;
      $tab[17]['emptylabel']           = __('Default value');

      $tab[19]['table']                = $this->getTable();
      $tab[19]['field']                = 'date_mod';
      $tab[19]['name']                 = __('Last update');
      $tab[19]['datatype']             = 'datetime';
      $tab[19]['massiveaction']        = false;

      $tab[20]['table']                = 'glpi_profiles';
      $tab[20]['field']                = 'name';
      $tab[20]['name']                 = sprintf(__('%1$s (%2$s)'), _n('Profile', 'Profiles', 2),
                                                 _n('Entity', 'Entities', 1));
      $tab[20]['forcegroupby']         = true;
      $tab[20]['massiveaction']        = false;
      $tab[20]['datatype']             = 'dropdown';
      $tab[20]['joinparams']           = array('beforejoin'
                                               => array('table'      => 'glpi_profiles_users',
                                                        'joinparams' => array('jointype' => 'child')));

      $tab[21]['table']                = $this->getTable();
      $tab[21]['field']                = 'user_dn';
      $tab[21]['name']                 = __('User DN');
      $tab[21]['massiveaction']        = false;
      $tab[21]['datatype']             = 'string';

      $tab[22]['table']                = $this->getTable();
      $tab[22]['field']                = 'registration_number';
      $tab[22]['name']                 = __('Administrative number');
      $tab[22]['datatype']             = 'string';

      $tab[23]['table']                = $this->getTable();
      $tab[23]['field']                = 'date_sync';
      $tab[23]['datatype']             = 'datetime';
      $tab[23]['name']                 = __('Last synchronization');
      $tab[23]['massiveaction']        = false;

      $tab[80]['table']                = 'glpi_entities';
      $tab[80]['linkfield']            = 'entities_id';
      $tab[80]['field']                = 'completename';
      $tab[80]['name']                 = sprintf(__('%1$s (%2$s)'), _n('Entity', 'Entities', 2),
                                                 _n('Profile', 'Profiles', 1));
      $tab[80]['forcegroupby']         = true;
      $tab[80]['datatype']             = 'dropdown';
      $tab[80]['massiveaction']        = false;
      $tab[80]['joinparams']           = array('beforejoin'
                                               => array('table'      => 'glpi_profiles_users',
                                                        'joinparams' => array('jointype' => 'child')));

      $tab[81]['table']                = 'glpi_usertitles';
      $tab[81]['field']                = 'name';
      $tab[81]['name']                 = _x('person','Title');
      $tab[81]['datatype']             = 'dropdown';

      $tab[82]['table']                = 'glpi_usercategories';
      $tab[82]['field']                = 'name';
      $tab[82]['name']                 = __('Category');
      $tab[82]['datatype']             = 'dropdown';

      $tab[79]['table']                = 'glpi_profiles';
      $tab[79]['field']                = 'name';
      $tab[79]['name']                 = __('Default profile');
      $tab[79]['datatype']             = 'dropdown';

      $tab[77]['table']                = 'glpi_entities';
      $tab[77]['field']                = 'name';
      $tab[77]['massiveaction']        = true;
      $tab[77]['name']                 = __('Default entity');
      $tab[77]['datatype']             = 'dropdown';

      $tab[60]['table']                = 'glpi_tickets';
      $tab[60]['field']                = 'count';
      $tab[60]['name']                 = __('Number of tickets as requester');
      $tab[60]['forcegroupby']         = true;
      $tab[60]['usehaving']            = true;
      $tab[60]['datatype']             = 'number';
      $tab[60]['massiveaction']        = false;
      $tab[60]['joinparams']           = array('beforejoin'
                                               => array('table'
                                                         => 'glpi_tickets_users',
                                                        'joinparams'
                                                         => array('jointype'
                                                                   => 'child',
                                                                  'condition'
                                                                   => 'AND NEWTABLE.`type`
                                                                        = '.CommonITILActor::REQUESTER)));

      $tab[61]['table']                = 'glpi_tickets';
      $tab[61]['field']                = 'count';
      $tab[61]['name']                 = __('Number of written tickets');
      $tab[61]['forcegroupby']         = true;
      $tab[61]['usehaving']            = true;
      $tab[61]['datatype']             = 'number';
      $tab[61]['massiveaction']        = false;
      $tab[61]['joinparams']           = array('jointype'  => 'child',
                                               'linkfield' => 'users_id_recipient');

      return $tab;
   }


   /**
    * @since ersion 0.84
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
         case 'authtype':
            $auths_id = 0;
            if (isset($values['auths_id']) && !empty($values['auths_id'])) {
               $auths_id = $values['auths_id'];
            }
            return Auth::getMethodName($values[$field], $auths_id);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (defaut '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'authtype' :
            $options['name'] = $name;
            $options['value'] = $values[$field];
            return Auth::dropdown($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get all groups where the current user have delegating
    *
    * @since version 0.83
    *
    * @param $entities_id ID of the entity to restrict (default '')
    *
    * @return array of groups id
   **/
   static function getDelegateGroupsForUser($entities_id='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_groups_users`.`groups_id`
                FROM `glpi_groups_users`
                INNER JOIN `glpi_groups`
                        ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                WHERE `glpi_groups_users`.`users_id` = '".Session::getLoginUserID()."'
                      AND `glpi_groups_users`.`is_userdelegate` = '1' ".
                      getEntitiesRestrictRequest("AND","glpi_groups",'',$entities_id,1);

      $groups = array();
      foreach ($DB->request($query) as $data) {
         $groups[$data['groups_id']] = $data['groups_id'];
      }
      return $groups;
   }


   /**
    * Execute the query to select box with all glpi users where select key = name
    *
    * Internaly used by showGroup_Users, dropdownUsers and ajax/dropdownUsers.php
    *
    * @param $count                    true if execute an count(*) (true by default)
    * @param $right                    limit user who have specific right (default 'all')
    * @param $entity_restrict          Restrict to a defined entity (default -1)
    * @param $value                    default value (default 0)
    * @param $used             array   Already used items ID: not to display in dropdown
    * @param $search                   pattern (default '')
    *
    * @return mysql result set.
   **/
   static function getSqlSearchResult ($count=true, $right="all", $entity_restrict=-1, $value=0,
                                       $used=array(), $search='') {
      global $DB, $CFG_GLPI;

      // No entity define : use active ones
      if ($entity_restrict < 0) {
         $entity_restrict = $_SESSION["glpiactiveentities"];
      }

      $joinprofile = false;
      switch ($right) {
         case "interface" :
            $joinprofile = true;
            $where       = " `glpi_profiles`.`interface` = 'central' ".
                             getEntitiesRestrictRequest("AND", "glpi_profiles_users", '',
                                                        $entity_restrict, 1);
            break;

         case "id" :
            $where = " `glpi_users`.`id` = '".Session::getLoginUserID()."' ";
            break;

         case "delegate" :
            $groups = self::getDelegateGroupsForUser($entity_restrict);
            $users  = array();
            if (count($groups)) {
               $query = "SELECT `glpi_users`.`id`
                         FROM `glpi_groups_users`
                         LEFT JOIN `glpi_users`
                              ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)
                         WHERE `glpi_groups_users`.`groups_id` IN ('".implode("','",$groups)."')
                               AND `glpi_groups_users`.`users_id` <> '".Session::getLoginUserID()."'";
               $result = $DB->query($query);

               if ($DB->numrows($result)) {
                  while ($data = $DB->fetch_assoc($result)) {
                        $users[$data["id"]] = $data["id"];
                  }
               }
            }
            // Add me to users list for central
            if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
               $users[Session::getLoginUserID()] = Session::getLoginUserID();
            }

            if (count($users)) {
               $where = " `glpi_users`.`id` IN ('".implode("','",$users)."')";
            } else {
               $where = '0';
            }
            break;

         case "all" :
            $where = " `glpi_users`.`id` > '1' ".
                     getEntitiesRestrictRequest("AND","glpi_profiles_users",'',$entity_restrict,1);
            break;

         default :
            $joinprofile = true;
            if (!is_array($right)) {
               $right = array($right);
            }
            $forcecentral = true;
            $where        = array();
            
            foreach ($right as $r) {
               // Check read or active for rights
               $where[] = " (`glpi_profiles`.`".$r."` IN ('1', 'r', 'w') ".
                           getEntitiesRestrictRequest("AND", "glpi_profiles_users", '',
                                                      $entity_restrict, 1).") ";

               if (in_array($r, Profile::$helpdesk_rights)) {
                  $forcecentral = false;
               }
            }

            $where = '('.implode(' OR ', $where);

            if ($forcecentral) {
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
            $first  = false;
            $where .= $value;
         } else {
            $first = true;
         }
         foreach ($used as $val) {
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
         $query = "SELECT COUNT(DISTINCT `glpi_users`.`id` ) AS CPT
                   FROM `glpi_users` ";
      } else {
         $query = "SELECT DISTINCT `glpi_users`.*
                   FROM `glpi_users` ";
      }

      $query .= " LEFT JOIN `glpi_useremails`
                     ON (`glpi_users`.`id` = `glpi_useremails`.`users_id`)
                  LEFT JOIN `glpi_profiles_users`
                     ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)";

      if ($joinprofile) {
         $query .= " LEFT JOIN `glpi_profiles`
                        ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`) ";
      }

      if ($count) {
         $query .= " WHERE $where ";
      } else {
         if ((strlen($search) > 0)
             && ($search != $CFG_GLPI["ajax_wildcard"])) {
            $where .= " AND (`glpi_users`.`name` ".Search::makeTextSearch($search)."
                             OR `glpi_users`.`realname` ".Search::makeTextSearch($search)."
                             OR `glpi_users`.`firstname` ".Search::makeTextSearch($search)."
                             OR `glpi_users`.`phone` ".Search::makeTextSearch($search)."
                             OR `glpi_useremails`.`email` ".Search::makeTextSearch($search)."
                             OR CONCAT(`glpi_users`.`realname`,' ',`glpi_users`.`firstname`) ".
                                       Search::makeTextSearch($search).")";
         }
         $query .= " WHERE $where ";

         if ($_SESSION["glpinames_format"] == self::FIRSTNAME_BEFORE) {
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
    * @param $options array of possible options:
    *    - name         : string / name of the select (default is users_id)
    *    - value
    *    - right        : string / limit user who have specific right :
    *                         id -> only current user (default case);
    *                         interface -> central ;
    *                         all -> all users ;
    *                         specific right like show_all_ticket, create_ticket.... (is array passed one of all passed right is needed)
    *    - comments     : boolean / is the comments displayed near the dropdown (default true)
    *    - entity       : integer or array / restrict to a defined entity or array of entities
    *                      (default -1 : no restriction)
    *    - entity_sons  : boolean / if entity restrict specified auto select its sons
    *                      only available if entity is a single value not an array(default false)
    *    - all          : Nobody or All display for none selected
    *                         all=0 (default) -> Nobody
    *                         all=1 -> All
    *                         all=-1-> nothing
    *    - rand         : integer / already computed rand value
    *    - toupdate     : array / Update a specific item on select change on dropdown
    *                      (need value_fieldname, to_update, url
    *                      (see Ajax::updateItemOnSelectEvent for information)
    *                      and may have moreparams)
    *    - used         : array / Already used items ID: not to display in dropdown (default empty)
    *    - ldap_import
    *    - on_change    : string / value to transmit to "onChange"
    *    - display      : boolean / display or get string (default true)
    *
    * @return rand value if displayed / string if not
   **/
   static function dropdown($options=array()) {
      global $DB, $CFG_GLPI;

      // Default values
      $p['name']           = 'users_id';
      $p['value']          = '';
      $p['right']          = 'id';
      $p['all']            = 0;
      $p['on_change']      = '';
      $p['comments']       = 1;
      $p['entity']         = -1;
      $p['entity_sons']    = false;
      $p['used']           = array();
      $p['ldap_import']    = false;
      $p['toupdate']       = '';
      $p['rand']           = mt_rand();
      $p['display']        = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $output = '';
      if (!($p['entity'] < 0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            $output .= "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities',$p['entity']);
         }
      }


      // Make a select box with all glpi users
      $use_ajax = false;

      if ($CFG_GLPI["use_ajax"]) {
         $res = self::getSqlSearchResult (true, $p['right'], $p['entity'], $p['value'], $p['used']);
         $nb  = ($res ? $DB->result($res,0,"CPT") : 0);
         if ($nb > $CFG_GLPI["ajax_limit_count"]) {
            $use_ajax = true;
         }
      }
      $user = getUserName($p['value'], 2);

      $default_display  = "<select id='dropdown_".$p['name'].$p['rand']."' name='".$p['name']."'>";
      $default_display .= "<option value='".$p['value']."'>";
      $default_display .= Toolbox::substr($user["name"], 0, $_SESSION["glpidropdown_chars_limit"]);
      $default_display .= "</option></select>";

      $view_users = (Session::haveRight("user", "r"));

      $params = array('searchText'       => '__VALUE__',
                      'value'            => $p['value'],
                      'myname'           => $p['name'],
                      'all'              => $p['all'],
                      'right'            => $p['right'],
                      'comment'          => $p['comments'],
                      'rand'             => $p['rand'],
                      'on_change'        => $p['on_change'],
                      'entity_restrict'  => $p['entity'],
                      'used'             => $p['used'],
                      'update_item'      => $p['toupdate'],);
      if ($view_users) {
         $params['update_link'] = $view_users;
      }

      $default = "";
      if (!empty($p['value']) && ($p['value'] > 0)) {
         $default = $default_display;

      } else {
         $default = "<select name='".$p['name']."' id='dropdown_".$p['name'].$p['rand']."'>";
         if ($p['all']) {
            $default.= "<option value='0'>--".__('All')."--</option></select>";
         } else {
            $default.= "<option value='0'>".Dropdown::EMPTY_VALUE."</option></select>\n";
         }
      }

      $output .= Ajax::dropdown($use_ajax, "/ajax/dropdownUsers.php", $params, $default,
                                $p['rand'], false);

      // Display comment
      if ($p['comments']) {
         if (!$view_users) {
            $user["link"] = '';
         } else if (empty($user["link"])) {
            $user["link"] = $CFG_GLPI['root_doc']."/front/user.php";
         }
         $output .= Html::showToolTip($user["comment"],
                                      array('contentid' => "comment_".$p['name'].$p['rand'],
                                            'display'   => false,
                                            'link'      => $user["link"],
                                            'linkid'    => "comment_link_".$p["name"].$p['rand']));
      }

      if (Session::haveRight('import_externalauth_users','w')
          && $p['ldap_import']
          && Entity::isEntityDirectoryConfigured($_SESSION['glpiactive_entity'])) {

         $output .= "<img alt='' title=\"".__s('Import a user')."\" src='".$CFG_GLPI["root_doc"].
                      "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                      onClick=\"var w = window.open('".$CFG_GLPI['root_doc'].
                      "/front/popup.php?popup=add_ldapuser&amp;rand=".$p['rand']."&amp;entity=".
                      $_SESSION['glpiactive_entity']."' ,'glpipopup', 'height=400, ".
                      "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
      }

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }


   /**
    * Simple add user form for external auth
   **/
   static function showAddExtAuthForm() {

      if (!Session::haveRight("import_externalauth_users","w")) {
         return false;
      }

      echo "<div class='center'>\n";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL('User')."'>\n";

      echo "<table class='tab_cadre'>\n";
      echo "<tr><th colspan='4'>".__('Automatically add a user of an external source')."</th></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Login')."</td>\n";
      echo "<td><input type='text' name='login'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='tab_bg_2 center' colspan='2'>\n";
      echo "<input type='submit' name='add_ext_auth_ldap' value=\"".__s('Import from directories')."\"
             class='submit'>\n";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='tab_bg_2 center' colspan='2'>\n";
      echo "<input type='submit' name='add_ext_auth_simple' value=\"".__s('Import from other sources')."\"
             class='submit'>\n";
      echo "</td></tr>\n";

      echo "</table>";
      Html::closeForm();
      echo "</div>\n";
   }


   /**
    * @param $IDs       array
    * @param $authtype        (default 1)
    * @param $server          (default -1)
    *
    * @return boolean
   **/
   static function changeAuthMethod($IDs=array(), $authtype=1 ,$server=-1) {
      global $DB;

      if (!Session::haveRight("user_authtype","w")) {
         return false;
      }

      if (!empty($IDs)
          && in_array($authtype, array(Auth::DB_GLPI, Auth::LDAP, Auth::MAIL, Auth::EXTERNAL))) {

         $where = implode("','",$IDs);
         $query = "UPDATE `glpi_users`
                   SET `authtype` = '$authtype', `auths_id` = '$server', `password` = ''
                   WHERE `id` IN ('$where')";
         if ($DB->query($query)) {
            foreach ($IDs as $ID) {
               $changes[0] = 0;
               $changes[1] = '';
               $changes[2] = addslashes(sprintf(__('%1$s: %2$s'),
                                                __('Update authentification method to'),
                                                Auth::getMethodName($authtype, $server)));
               Log::history($ID, __CLASS__, $changes, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            }

            return true;
         }
      }
      return false;
   }


   /**
    * Generate vcard for the current user
   **/
   function generateVcard() {

      include_once (GLPI_ROOT . "/lib/vcardclass/classes-vcard.php");

      // build the Vcard
      $vcard = new vCard();

      if (!empty($this->fields["realname"])
          || !empty($this->fields["firstname"])) {
         $vcard->setName($this->fields["realname"], $this->fields["firstname"], "", "");
      } else {
         $vcard->setName($this->fields["name"], "", "", "");
      }

      $vcard->setPhoneNumber($this->fields["phone"], "PREF;WORK;VOICE");
      $vcard->setPhoneNumber($this->fields["phone2"], "HOME;VOICE");
      $vcard->setPhoneNumber($this->fields["mobile"], "WORK;CELL");

      $vcard->setEmail($this->getDefaultEmail());

      $vcard->setNote($this->fields["comment"]);

      // send the  VCard
      $output   = $vcard->getVCard();
      $filename = $vcard->getFileName();      // "xxx xxx.vcf"

      @Header("Content-Disposition: attachment; filename=\"$filename\"");
      @Header("Content-Length: ".Toolbox::strlen($output));
      @Header("Connection: close");
      @Header("content-type: text/x-vcard; charset=UTF-8");

      echo $output;
   }


   /**
    * Show items of the current user
    *
    * @param $tech
   **/
   function showItems($tech) {
      global $DB, $CFG_GLPI;

      $ID = $this->getField('id');

      if ($tech) {
         $type_user   = $CFG_GLPI['linkuser_tech_types'];
         $type_group  = $CFG_GLPI['linkgroup_tech_types'];
         $field_user  = 'users_id_tech';
         $field_group = 'groups_id_tech';
      } else {
         $type_user   = $CFG_GLPI['linkuser_types'];
         $type_group  = $CFG_GLPI['linkgroup_types'];
         $field_user  = 'users_id';
         $field_group = 'groups_id';
      }

      $group_where = "";
      $groups      = array();
      $query = "SELECT `glpi_groups_users`.`groups_id`,
                       `glpi_groups`.`name`
                FROM `glpi_groups_users`
                LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                WHERE `glpi_groups_users`.`users_id` = '$ID'";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         $first = true;

         while ($data = $DB->fetch_assoc($result)) {
            if ($first) {
               $first = false;
            } else {
               $group_where .= " OR ";
            }

            $group_where               .= " `".$field_group."` = '".$data["groups_id"]."' ";
            $groups[$data["groups_id"]] = $data["name"];
         }
      }

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th>".__('Type')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "<th>".__('Status')."</th>";
      echo "<th>&nbsp;</th></tr>";

      foreach ($type_user as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT *
                      FROM `$itemtable`
                      WHERE `".$field_user."` = '$ID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
               $query .= " AND `is_deleted` = '0' ";
            }
            $result    = $DB->query($query);

            $type_name = $item->getTypeName();

            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $cansee = $item->can($data["id"],"r");
                  $link   = $data["name"];
                  if ($cansee) {
                     $link_item = Toolbox::getItemTypeFormURL($itemtype);
                     if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                        $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
                     }
                     $link = "<a href='".$link_item."?id=".$data["id"]."'>".$link."</a>";
                  }
                  $linktype = "";
                  if ($data[$field_user] == $ID) {
                     $linktype = self::getTypeName(1);
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
                  echo "</td><td class='center'>";
                  if (isset($data["states_id"])) {
                     echo Dropdown::getDropdownName("glpi_states", $data['states_id']);
                  } else {
                     echo '&nbsp;';
                  }

                  echo "</td><td class='center'>$linktype</td></tr>";
               }
            }
         }
      }
      echo "</table></div>";

      if (!empty($group_where)) {
         echo "<div class='spaced'><table class='tab_cadre_fixe'><tr>".
               "<th>".__('Type')."</th>".
               "<th>".__('Entity')."</th>".
               "<th>".__('Name')."</th>".
               "<th>".__('Serial number')."</th>".
               "<th>".__('Inventory number')."</th>".
               "<th>".__('Status')."</th>".
               "<th>&nbsp;</th></tr>";

         foreach ($type_group as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
               continue;
            }
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
               $result    = $DB->query($query);

               $type_name = $item->getTypeName();


               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $cansee = $item->can($data["id"],"r");
                     $link   = $data["name"];
                     if ($cansee) {
                        $link_item = Toolbox::getItemTypeFormURL($itemtype);
                        if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                           $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
                        }
                        $link = "<a href='".$link_item."?id=".$data["id"]."'>".$link."</a>";
                     }
                     $linktype = "";
                     if (isset($groups[$data[$field_group]])) {
                        $linktype = sprintf(__('%1$s = %2$s'), _n('Group','Groups',1),
                                            $groups[$data[$field_group]]);
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
                     echo "</td><td class='center'>";
                     if (isset($data["states_id"])) {
                        echo Dropdown::getDropdownName("glpi_states",$data['states_id']);
                     } else {
                        echo '&nbsp;';
                     }

                     echo "</td><td class='center'>$linktype</td></tr>";
                  }
               }
            }
         }
         echo "</table></div>";
      }
   }


   /**
    * @param $email  (default '')
   **/
   static function getOrImportByEmail($email='') {
      global $DB, $CFG_GLPI;

      $query = "SELECT `users_id` as id
                FROM `glpi_useremails`
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_useremails`.`users_id`)
                WHERE `glpi_useremails`.`email` = '$email'
                ORDER BY `glpi_users`.`is_active`  DESC";
      $result = $DB->query($query);

      //User still exists in DB
      if ($result && $DB->numrows($result)) {
         return $DB->result($result, 0, "id");

      } else {
         if ($CFG_GLPI["is_users_auto_add"]) {
            //Get all ldap servers with email field configured
            $ldaps = AuthLdap::getServersWithImportByEmailActive();
            //Try to find the user by his email on each ldap server

            foreach ($ldaps as $ldap) {
               $params['method'] = AuthLdap::IDENTIFIER_EMAIL;
               $params['value']  = $email;
               $res              = AuthLdap::ldapImportUserByServerId($params,
                                                                      AuthLdap::ACTION_IMPORT,
                                                                      $ldap);

               if (isset($res['id'])) {
                  return $res['id'];
               }
            }
         }
      }
      return 0;
   }


   /**
    * @param $users_id
   **/
   static function manageDeletedUserInLdap($users_id) {
      global $CFG_GLPI;

      //User is present in DB but not in the directory : it's been deleted in LDAP
      $tmp['id'] = $users_id;
      $myuser    = new User();

      switch ($CFG_GLPI['user_deleted_ldap']) {
         //DO nothing
         default :
         case 0 :
            break;

         //Put user in dustbin
         case 1 :
            $myuser->delete($tmp);
            break;

         //Delete all user dynamic habilitations and groups
         case 2 :
            Profile_User::deleteRights($users_id, true);
            Group_User::deleteGroups($users_id, true);
            break;

         //Deactivate the user
         case 3 :
            $tmp['is_active'] = 0;
            $myuser->update($tmp);
            break;
      }
      $changes[0] = '0';
      $changes[1] = '';
      $changes[2] = __('Deleted user in LDAP directory');
      Log::history($users_id, 'User', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);
   }


   /**
    * @param $login
   **/
   static function getIdByName($login) {
      self::getIdByField('name', $login);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $login
   **/
   static function getIdByField($field, $login) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_users`
                WHERE `$field` = '".addslashes($login)."'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         return $DB->result($result, 0, 'id');
      }
      return false;
   }


   /**
    * Show form for password recovery
    *
    * @param $token
   **/
   static function showPasswordForgetChangeForm($token) {
      global $CFG_GLPI, $DB;

      // Verif token.
      $token_ok = false;
      $query = "SELECT *
                FROM `glpi_users`
                WHERE `password_forget_token` = '$token'
                      AND NOW() < ADDDATE(`password_forget_token_date`, INTERVAL 1 DAY)";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) == 1) {
            $token_ok = true;
         }
      }
      echo "<div class='center'>";

      if ($token_ok) {
         echo "<form method='post' name='forgetpassword' action='".$CFG_GLPI['root_doc'].
                "/front/lostpassword.php'>";
         echo "<table class='tab_cadre'>";
         echo "<tr><th colspan='2'>" . __('Forgotten password?')."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>". __('Please confirm your email address and enter your new password.').
              "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . _n('Email', 'Emails', 1)."</td>";
         echo "<td><input type='text' name='email' value='' size='60'></td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Password')."</td>";
         echo "<td><input id='password' type='password' name='password' value='' size='20'
                    autocomplete='off' onkeyup=\"return passwordCheck();\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Password confirmation')."</td>";
         echo "<td><input type='password' name='password2' value='' size='20' autocomplete='off'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>".__('Password security policy')."</td>";
         echo "<td>";
         Config::displayPasswordSecurityChecks();
         echo "</td></tr>";

         echo "<tr class='tab_bg_2 center'><td colspan='2'>";
         echo "<input type='hidden' name='password_forget_token' value='$token'>";
         echo "<input type='submit' name='update' value=\"".__s('Save')."\" class='submit'>";
         echo "</td></tr>";

        echo "</table>";
         Html::closeForm();

      } else {
         _e('Your password reset request has expired or is invalid. Please renew it.');
      }
      echo "</div>";
   }


   /**
    * Show form for password recovery
   **/
   static function showPasswordForgetRequestForm() {
      global $CFG_GLPI;

      echo "<div class='center'>";
      echo "<form method='post' name='forgetpassword' action='".$CFG_GLPI['root_doc'].
             "/front/lostpassword.php'>";
      echo "<table class='tab_cadre'>";
      echo "<tr><th colspan='2'>" . __('Forgotten password?')."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>" .
            __('Please enter your email address. An email will be sent to you and you will be able to choose a new password.').
           "</td></tr>";

      echo "<tr class='tab_bg_2 center'>";
      echo "<td><input type='text' size='60' name='email' value=''></td>";
      echo "<td><input type='submit' name='update' value=\"".__s('Save')."\" class='submit'>";
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   /**
    * @param $input
   **/
   function updateForgottenPassword($input) {
      global $CFG_GLPI;

      echo "<div class='center'>";
      if ($this->getFromDBbyEmail($input['email'],
                                  "`glpi_users`.`is_active` AND NOT `glpi_users`.`is_deleted`")) {
         if (($this->fields["authtype"] == Auth::DB_GLPI)
             || !Auth::useAuthExt()) {

            if (($input['password_forget_token'] == $this->fields['password_forget_token'])
                && (abs(strtotime($_SESSION["glpi_currenttime"])
                        -strtotime($this->fields['password_forget_token_date'])) < DAY_TIMESTAMP)) {

               $input['id'] = $this->fields['id'];
               if (Config::validatePassword($input["password"]) && $this->update($input)) {
                 _e('Reset password successful.');
                 //
                 $input2['password_forget_token']      = '';
                 $input2['password_forget_token_date'] = NULL;
                 $input2['id']                         = $this->fields['id'];
                 $this->update($input2);
               } else {
                  // Force display on error
                  Html::displayMessageAfterRedirect();
               }

            } else {
               _e('Your password reset request has expired or is invalid. Please renew it.');
            }

         } else {
            _e("The authentication method configuration doesn't allow you to change your password.");
         }

      } else {
         _e('Email address not found.');
      }

      echo "<br>";
      echo "<a href='".$CFG_GLPI['root_doc']."'>".__('Back')."</a>";
      echo "</div>";
   }


   /**
    * Send password recovery for a user.
    *
    * @param $email email of the user
    *
    * @return nothing : send email or display error message
   **/
   function forgetPassword($email) {
      global $CFG_GLPI;

      echo "<div class='center'>";
      if ($this->getFromDBbyEmail($email,
                                  "`glpi_users`.`is_active` AND NOT `glpi_users`.`is_deleted`")) {

         // Send token if auth DB or not external auth defined
         if (($this->fields["authtype"] == Auth::DB_GLPI)
             || !Auth::useAuthExt()) {

            if (NotificationMail::isUserAddressValid($email)) {
               $input['password_forget_token']      = sha1(Toolbox::getRandomString(30));
               $input['password_forget_token_date'] = $_SESSION["glpi_currenttime"];
               $input['id']                         = $this->fields['id'];
               $this->update($input);
               // Notication on root entity (glpi_users.entities_id is only a pref)
               NotificationEvent::raiseEvent('passwordforget', $this, array('entities_id' => 0));
               _e('An email has been sent to your email address. The email contains information for reset your password.');
            } else {
               _e('Invalid email address');
            }

         } else {
            _e("The authentication method configuration doesn't allow you to change your password.");
         }

      } else {
         _e('Email address not found.');
      }
      echo "<br>";
      echo "<a href=\"".$CFG_GLPI['root_doc']."/index.php\">".__s('Back')."</a>";
      echo "</div>";
   }


   /**
    * Display information from LDAP server for user
   **/
   private function showLdapDebug() {

      if ($this->fields['authtype'] != Auth::LDAP) {
         return false;
      }
      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".__('LDAP directory')."</th></tr>";

      echo "<tr class='tab_bg_2'><td>".__('User DN')."</td>";
      echo "<td>".$this->fields['user_dn']."</td></tr>\n";

      if ($this->fields['user_dn']) {
         echo "<tr class='tab_bg_2'><td>".__('User information')."</td><td>";
         $config_ldap = new AuthLDAP();
         $ds          = false;

         if ($config_ldap->getFromDB($this->fields['auths_id'])) {
            $ds = $config_ldap->connect();
         }

         if ($ds) {
            $info = AuthLdap::getUserByDn($ds, $this->fields['user_dn'],
                                          array('*', 'createTimeStamp', 'modifyTimestamp'));
            if (is_array($info)) {
               Html::printCleanArray($info);
            } else {
               _e('No item to display');
            }

         } else {
            _e('Connection failed');
         }

         echo "</td></tr>\n";
      }

      echo "</table></div>";
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      NotificationEvent::debugEvent($this);
      $this->showLdapDebug();
   }


   /**
    * Get fields to display in the unicity error message
    *
    * @return an aray which contains field => label
   **/
   function getUnicityFieldsToDisplayInErrorMessage() {

      return array('id'          => __('ID'),
                   'entities_id' => __('Entity'));
   }


   function getUnallowedFieldsForUnicity() {

      return array_merge(parent::getUnallowedFieldsForUnicity(),
                         array('auths_id', 'date_sync', 'entities_id', 'last_login', 'profiles_id'));
   }


   /**
   * Get personal token checking that it is unique
   *
   * @return string personal token
   **/
   static function getUniquePersonalToken() {
      global $DB;

      $ok = false;
      do {
         $key    = Toolbox::getRandomString(40);
         $query  = "SELECT COUNT(*)
                    FROM `glpi_users`
                    WHERE `personal_token` = '$key'";
         $result = $DB->query($query);

         if ($DB->result($result,0,0) == 0) {
            return $key;
         }
      } while (!$ok);

   }


   /**
    * Get personal token of a user. If not exists generate it.
    *
    * @param $ID user ID
    *
    * @return string personal token
   **/
   static function getPersonalToken($ID) {
      global $DB;

      $user = new self();
      if ($user->getFromDB($ID)) {
         if (!empty($user->fields['personal_token'])) {
            return $user->fields['personal_token'];
         }
         $token = self::getUniquePersonalToken();
         $user->update(array('id'                  => $user->getID(),
                             'personal_token'      => $token,
                             'personal_token_date' => $_SESSION['glpi_currenttime']));
         return $user->fields['personal_token'];
      }

      return false;
   }


   static function checkDefaultPasswords() {

      $passwords = array('glpi'      => 'glpi',
                         'tech'      => 'tech',
                         'normal'    => 'normal',
                         'post-only' => 'postonly');
      $default_password_set = array();
      foreach ($passwords as $login => $password) {
         if (countElementsInTable("glpi_users",
                                  "`name`='$login' " .
                                       "AND (`password` = SHA1('$password') " .
                                             "OR `password` = MD5('$password'))
                                        AND `is_active` = '1'")) {
            $default_password_set[] = $login;
         }
      }
      return $default_password_set;
   }

}
?>
