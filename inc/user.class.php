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

use Sabre\VObject;
use Glpi\Exception\ForgetPasswordException;
use Glpi\Exception\PasswordTooWeakException;

class User extends CommonDBTM {

   // From CommonDBTM
   public $dohistory         = true;
   public $history_blacklist = ['date_mod', 'date_sync', 'last_login',
                                     'publicbookmarkorder', 'privatebookmarkorder'];

   // NAME FIRSTNAME ORDER TYPE
   const REALNAME_BEFORE   = 0;
   const FIRSTNAME_BEFORE  = 1;

   const IMPORTEXTAUTHUSERS  = 1024;
   const READAUTHENT         = 2048;
   const UPDATEAUTHENT       = 4096;

   static $rightname = 'user';

   private $entities = null;


   static function getTypeName($nb = 0) {
      return _n('User', 'Users', $nb);
   }

   static function getMenuShorcut() {
      return 'u';
   }

   static function getAdditionalMenuOptions() {

      if (Session::haveRight('user', self::IMPORTEXTAUTHUSERS)) {
         return [
            'ldap' => [
               'title' => AuthLDAP::getTypeName(Session::getPluralNumber()),
               'page'  => '/front/ldap.php',
            ],
         ];
      }
      return false;
   }


   function canViewItem() {
      if (Session::canViewAllEntities()
          || Session::haveAccessToOneOfEntities($this->getEntities())) {
         return true;
      }
      return false;
   }


   function canCreateItem() {

      // Will be created from form, with selected entity/profile
      if (isset($this->input['_profiles_id']) && ($this->input['_profiles_id'] > 0)
          && Profile::currentUserHaveMoreRightThan([$this->input['_profiles_id']])
          && isset($this->input['_entities_id'])
          && Session::haveAccessToEntity($this->input['_entities_id'])) {
         return true;
      }
      // Will be created with default value
      if (Session::haveAccessToEntity(0) // Access to root entity (required when no default profile)
          || (Profile::getDefault() > 0)) {
         return true;
      }

      if (($_SESSION['glpiactive_entity'] > 0)
          && (Profile::getDefault() == 0)) {
         echo "<div class='tab_cadre_fixe warning'>".
                __('You must define a default profile to create a new user')."</div>";
      }

      return false;
   }


   function canUpdateItem() {

      $entities = Profile_User::getUserEntities($this->fields['id'], false);
      if (Session::canViewAllEntities()
          || Session::haveAccessToOneOfEntities($entities)) {
         return true;
      }
      return false;
   }


   function canDeleteItem() {
      if (Session::canViewAllEntities()
          || Session::haveAccessToAllOfEntities($this->getEntities())) {
         return true;
      }
      return false;
   }


   function canPurgeItem() {
      return $this->canDeleteItem();
   }


   function isEntityAssign() {
      // glpi_users.entities_id is only a pref.
      return false;
   }


   /**
    * Compute preferences for the current user mixing config and user data.
    *
    * @return void
    */
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
    * Load minimal session for user.
    *
    * @param integer $entities_id  Entity to use
    * @param boolean $is_recursive Whether to load entities recursivly or not
    *
    * @return void
    *
    * @since 0.83.7
    */
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
            $entities = [$entities_id];
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


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $ong    = [];
            $ong[1] = __('Used items');
            $ong[2] = __('Managed items');
            return $ong;

         case 'Preference' :
            return __('Main');
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
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


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Profile_User', $ong, $options);
      $this->addStandardTab('Group_User', $ong, $options);
      $this->addStandardTab('Config', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Reservation', $ong, $options);
      $this->addStandardTab('Auth', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Certificate_Item', $ong, $options);
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

   static public function unsetUndisclosedFields(&$fields) {
      unset($fields['password']);
   }

   function pre_deleteItem() {
      global $DB;

      $entities = $this->getEntities();
      $view_all = Session::canViewAllEntities();
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
            $DB->delete(
               'glpi_profiles_users', [
                  'users_id'     => $this->fields['id'],
                  'entities_id'  => $ent
               ]
            );
         }
         return false;
      }
   }


   function cleanDBonPurge() {

      global $DB;

      // ObjectLock does not extends CommonDBConnexity
      $ol = new ObjectLock();
      $ol->deleteByCriteria(['users_id' => $this->fields['id']]);

      // Reminder does not extends CommonDBConnexity
      $r = new Reminder();
      $r->deleteByCriteria(['users_id' => $this->fields['id']]);

      // Delete private bookmark
      $ss = new SavedSearch();
      $ss->deleteByCriteria(
         [
            'users_id'   => $this->fields['id'],
            'is_private' => 1,
         ]
      );

      // Set no user to public bookmark
      $DB->update(
         SavedSearch::getTable(), [
            'users_id' => 0
         ], [
            'users_id' => $this->fields['id']
         ]
      );

      // Set no user to consumables
      $DB->update(
         'glpi_consumables', [
            'items_id' => 0,
            'itemtype' => 'NULL',
            'date_out' => 'NULL'
         ], [
            'items_id' => $this->fields['id'],
            'itemtype' => 'User'
         ]
      );

      $this->deleteChildrenAndRelationsFromDb(
         [
            Certificate_Item::class,
            Change_User::class,
            Group_User::class,
            KnowbaseItem_User::class,
            Problem_User::class,
            Profile_User::class,
            ProjectTaskTeam::class,
            ProjectTeam::class,
            Reminder_User::class,
            RSSFeed_User::class,
            SavedSearch_User::class,
            Ticket_User::class,
            UserEmail::class,
         ]
      );

      if ($this->fields['id'] > 0) { // Security
         // DisplayPreference does not extends CommonDBConnexity
         $dp = new DisplayPreference();
         $dp->deleteByCriteria(['users_id' => $this->fields['id']]);
      }

      $this->dropPictureFiles($this->fields['picture']);

      // Ticket rules use various _users_id_*
      Rule::cleanForItemAction($this, '_users_id%');
      Rule::cleanForItemCriteria($this, '_users_id%');
   }


   /**
    * Retrieve a user from the database using its login.
    *
    * @param string $name Login of the user
    *
    * @return boolean
    */
   function getFromDBbyName($name) {
      return $this->getFromDBByCrit(['name' => $name]);
   }

   /**
    * Retrieve a user from the database using its login.
    *
    * @param string  $name     Login of the user
    * @param integer $authtype Auth type (see Auth constants)
    * @param integer $auths_id ID of auth server
    *
    * @return boolean
    */
   function getFromDBbyNameAndAuth($name, $authtype, $auths_id) {
      return $this->getFromDBByCrit([
         'name'     => $name,
         'authtype' => $authtype,
         'auths_id' => $auths_id
         ]);
   }

   /**
    * Retrieve a user from the database using value of the sync field.
    *
    * @param string $value Value of the sync field
    *
    * @return boolean
    */
   function getFromDBbySyncField($value) {
      return $this->getFromDBByCrit(['sync_field' => $value]);
   }

   /**
    * Retrieve a user from the database using it's dn.
    *
    * @since 0.84
    *
    * @param string $user_dn dn of the user
    *
    * @return boolean
    */
   function getFromDBbyDn($user_dn) {
      return $this->getFromDBByCrit(['user_dn' => $user_dn]);
   }


   /**
    * Retrieve a user from the database using its email.
    *
    * @since 9.3 Can pass condition as a parameter
    *
    * @param string $email     user email
    * @param array  $condition add condition
    *
    * @return boolean
    */
   function getFromDBbyEmail($email, $condition = []) {
      global $DB;

      $crit = [
         'SELECT'    => $this->getTable() . '.id',
         'FROM'      => $this->getTable(),
         'LEFT JOIN'  => [
            'glpi_useremails' => [
               'FKEY' => [
                  $this->getTable() => 'id',
                  'glpi_useremails' => 'users_id'
               ]
            ]
         ],
         'WHERE'     => ['glpi_useremails.email' => $email] + $condition
      ];

      $iter = $DB->request($crit);
      if ($iter->numrows()==1) {
         $row = $iter->next();
         return $this->getFromDB($row['id']);
      }
      return false;
   }


   /**
    * Get the default email of the user.
    *
    * @return string
    */
   function getDefaultEmail() {

      if (!isset($this->fields['id'])) {
         return '';
      }

      return UserEmail::getDefaultForUser($this->fields['id']);
   }


   /**
    * Get all emails of the user.
    *
    * @return string[]
    */
   function getAllEmails() {

      if (!isset($this->fields['id'])) {
         return [];
      }
      return UserEmail::getAllForUser($this->fields['id']);
   }


   /**
    * Check if the email is attached to the current user.
    *
    * @param string $email
    *
    * @return boolean
    */
   function isEmail($email) {

      if (!isset($this->fields['id'])) {
         return false;
      }
      return UserEmail::isEmailForUser($this->fields['id'], $email);
   }


   /**
    * Retrieve a user from the database using its personal token.
    *
    * @param string $token user token
    * @param string $field the field storing the token
    *
    * @return boolean
    */
   function getFromDBbyToken($token, $field = 'personal_token') {
      $fields = ['personal_token', 'api_token'];
      if (!in_array($field, $fields)) {
         Toolbox::logWarning('User::getFromDBbyToken() can only be called with $field parameter with theses values: \'' . implode('\', \'', $fields) . '\'');
         return false;
      }

      return $this->getFromDBByCrit([$this->getTable() . ".$field" => $token]);
   }


   function prepareInputForAdd($input) {
      global $DB;

      if (isset($input['_stop_import'])) {
         return false;
      }

      if (!Auth::isValidLogin($input['name'])) {
         Session::addMessageAfterRedirect(__('The login is not valid. Unable to add the user.'),
                                          false, ERROR);
         return false;
      }

      if (!isset($input["authtype"])) {
         $input["authtype"] = Auth::DB_GLPI;
      }

      if (!isset($input["auths_id"])) {
         $input["auths_id"] = 0;
      }

      // Check if user does not exists
      $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            'name'      => $input['name'],
            'authtype'  => $input['authtype'],
            'auths_id'  => $input['auths_id']
         ],
         'LIMIT'  => 1
      ]);

      if (count($iterator)) {
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
                     = Auth::getPasswordHash(Toolbox::unclean_cross_side_scripting_deep(stripslashes($input["password"])));
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

      return $input;
   }


   function post_addItem() {

      $this->updateUserEmails();
      $this->syncLdapGroups();
      $this->syncDynamicEmails();

      $rulesplayed = $this->applyRightRules();
      $picture     = $this->syncLdapPhoto();

      //add picture in user fields
      if (!empty($picture)) {
         $this->update(['id'      => $this->fields['id'],
                             'picture' => $picture]);
      }

      // Add default profile
      if (!$rulesplayed) {
         $affectation = [];
         if (isset($this->input['_profiles_id']) && $this->input['_profiles_id']
            && Profile::currentUserHaveMoreRightThan([$this->input['_profiles_id']])
            ) {
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

      //picture manually uploaded by user
      if (isset($input["_blank_picture"]) && $input["_blank_picture"]) {
         self::dropPictureFiles($this->fields['picture']);
         $input['picture'] = 'NULL';
      } else {
         $newPicture = false;
         if (!isAPI()) {
            if (isset($input["_picture"][0]) && !empty($input["_picture"][0])) {
               $input["_picture"] = $input["_picture"][0];
            }
         }
         if (isset($input["_picture"]) && !empty($input["_picture"])) {
            $newPicture = true;
         }
         if ($newPicture) {
            $fullpath = GLPI_TMP_DIR."/".$input["_picture"];
            if (toolbox::getMime($fullpath, 'image')) {
               // Unlink old picture (clean on changing format)
               self::dropPictureFiles($this->fields['picture']);
               // Move uploaded file
               $filename     = uniqid($this->fields['id'].'_');
               $sub          = substr($filename, -2); /* 2 hex digit */
               $tmp          = explode(".", $input["_picture"]);
               $extension    = Toolbox::strtolower(array_pop($tmp));
               @mkdir(GLPI_PICTURE_DIR . "/$sub");
               $picture_path = GLPI_PICTURE_DIR  . "/$sub/${filename}.$extension";
               self::dropPictureFiles($filename.".".$extension);

               if (Document::isImage($input["_picture"])
                   && Document::renameForce($fullpath, $picture_path)) {
                  Session::addMessageAfterRedirect(__('The file is valid. Upload is successful.'));
                  // For display
                  $input['picture'] = "$sub/${filename}.$extension";

                  //prepare a thumbnail
                  $thumb_path = GLPI_PICTURE_DIR . "/$sub/${filename}_min.$extension";
                  Toolbox::resizePicture($picture_path, $thumb_path);
               } else {
                  Session::addMessageAfterRedirect(__('Potential upload attack or file too large. Moving temporary file failed.'),
                        false, ERROR);
               }
            } else {
               Session::addMessageAfterRedirect(__('The file is not an image file.'),
                     false, ERROR);
            }
         } else {
            //ldap jpegphoto synchronisation.
            $picture = $this->syncLdapPhoto();
            if (!empty($picture)) {
               $input['picture'] = $picture;
            }
         }
      }

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
                     = Auth::getPasswordHash(Toolbox::unclean_cross_side_scripting_deep(stripslashes($input["password"])));

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

      // blank password when authtype changes
      if (isset($input["authtype"])
          && $input["authtype"] != Auth::DB_GLPI
          && $input["authtype"] != $this->getField('authtype')) {
         $input["password"] = "";
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

      // Security on default group  update
      if (isset($input['groups_id'])
         && !Group_User::isUserInGroup($input['id'], $input['groups_id'])) {
            unset($input['groups_id']);
      }

      if (isset($input['_reset_personal_token'])
          && $input['_reset_personal_token']) {
         $input['personal_token']      = self::getUniqueToken('personal_token');
         $input['personal_token_date'] = $_SESSION['glpi_currenttime'];
      }

      if (isset($input['_reset_api_token'])
          && $input['_reset_api_token']) {
         $input['api_token']      = self::getUniqueToken('api_token');
         $input['api_token_date'] = $_SESSION['glpi_currenttime'];
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
                  // reinit translations
                  if ($f == 'language') {
                     $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
                     unset($_SESSION['glpimenu']);
                  }
               }
            }
            if ($input[$f] == $CFG_GLPI[$f]) {
               $input[$f] = "NULL";
            }
         }
      }

      if (isset($input['language']) && GLPI_DEMO_MODE) {
         unset($input['language']);
      }
      return $input;
   }


   function post_updateItem($history = 1) {

      $this->updateUserEmails();
      $this->syncLdapGroups();
      $this->syncDynamicEmails();
      $this->applyRightRules();
   }



   /**
    * Apply rules to determine dynamic rights of the user.
    *
    * @return boolean true if rules are applied, false otherwise
    */
   function applyRightRules() {

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
               $entities_rules = [];
            }

            if (isset($this->input["_ldap_rules"]["rules_entities"])) {
               $entities = $this->input["_ldap_rules"]["rules_entities"];
            } else {
               $entities = [];
            }

            if (isset($this->input["_ldap_rules"]["rules_rights"])) {
               $rights = $this->input["_ldap_rules"]["rules_rights"];
            } else {
               $rights = [];
            }

            $retrieved_dynamic_profiles = [];

            //For each affectation -> write it in DB
            foreach ($entities_rules as $entity) {
               //Multiple entities assignation
               if (is_array($entity[0])) {
                  foreach ($entity[0] as $ent) {
                     $retrieved_dynamic_profiles[] = [
                        'entities_id'  => $ent,
                        'profiles_id'  => $entity[1],
                        'is_recursive' => $entity[2],
                        'users_id'     => $this->fields['id'],
                        'is_dynamic'   => 1,
                     ];
                  }
               } else {
                  $retrieved_dynamic_profiles[] = [
                     'entities_id'  => $entity[0],
                     'profiles_id'  => $entity[1],
                     'is_recursive' => $entity[2],
                     'users_id'     => $this->fields['id'],
                     'is_dynamic'   => 1,
                  ];
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
                     $retrieved_dynamic_profiles[] = [
                        'entities_id'  => $entity[0],
                        'profiles_id'  => $right,
                        'is_recursive' => $entity[1],
                        'users_id'     => $this->fields['id'],
                        'is_dynamic'   => 1,
                     ];
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
    * Synchronise LDAP group of the user.
    *
    * @return void
    */
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
               $iterator = $DB->request([
                  'SELECT'    => [
                     'glpi_groups_users.id',
                     'glpi_groups_users.groups_id',
                     'glpi_groups_users.is_dynamic'
                  ],
                  'FROM'      => 'glpi_groups_users',
                  'LEFT JOIN' => [
                     'glpi_groups'  => [
                        'FKEY'   => [
                           'glpi_groups_users'  => 'groups_id',
                           'glpi_groups'        => 'id'
                        ]
                     ]
                  ],
                  'WHERE'     => [
                     'glpi_groups_users.users_id' => $this->fields['id']
                  ]
               ]);

               $groupuser = new Group_User();
               while ($data =  $iterator->next()) {

                  if (in_array($data["groups_id"], $this->input["_groups"])) {
                     // Delete found item in order not to add it again
                     unset($this->input["_groups"][array_search($data["groups_id"],
                           $this->input["_groups"])]);

                  } else if ($data['is_dynamic']) {
                     $groupuser->delete(['id' => $data["id"]]);
                  }
               }

               //If the user needs to be added to one group or more
               if (count($this->input["_groups"]) > 0) {
                  foreach ($this->input["_groups"] as $group) {
                     $groupuser->add(['users_id'   => $this->fields["id"],
                                           'groups_id'  => $group,
                                           'is_dynamic' => 1]);
                  }
                  unset ($this->input["_groups"]);
               }
            }
         }
      }
   }


   /**
    * Synchronize picture (photo) of the user.
    *
    * @since 0.85
    *
    * @return string|boolean Filename to be stored in user picture field, false if no picture found
    */
   function syncLdapPhoto() {

      if (isset($this->fields["authtype"])
          && (($this->fields["authtype"] == Auth::LDAP)
               || ($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED
                   && !empty($this->fields["auths_id"]))
               || Auth::isAlternateAuth($this->fields['authtype']))) {

         if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
            $config_ldap = new AuthLDAP();
            $ds          = false;

            //connect ldap server
            if ($config_ldap->getFromDB($this->fields['auths_id'])) {
               $ds = $config_ldap->connect();
            }

            if ($ds) {
               //get picture fields
               $picture_field = $config_ldap->fields['picture_field'];
               if (empty($picture_field)) {
                  return false;
               }

               //get picture content in ldap
               $info = AuthLdap::getUserByDn($ds, $this->fields['user_dn'],
                                             [$picture_field], false);

               //getUserByDn returns an array. If the picture is empty,
               //$info[$picture_field][0] is null
               if (!isset($info[$picture_field][0]) || empty($info[$picture_field][0])) {
                  return "";
               }
               //prepare paths
               $img       = array_pop($info[$picture_field]);
               $filename  = uniqid($this->fields['id'].'_');
               $sub       = substr($filename, -2); /* 2 hex digit */
               $file      = GLPI_PICTURE_DIR . "/$sub/${filename}.jpg";

               if (array_key_exists('picture', $this->fields)) {
                  $oldfile = GLPI_PICTURE_DIR . "/" . $this->fields["picture"];
               } else {
                  $oldfile = null;
               }

               // update picture if not exist or changed
               if (empty($this->fields["picture"])
                   || !file_exists($oldfile)
                   || sha1_file($oldfile) !== sha1($img)) {
                  if (!is_dir(GLPI_PICTURE_DIR . "/$sub")) {
                     mkdir(GLPI_PICTURE_DIR . "/$sub");
                  }

                  //save picture
                  $outjpeg = fopen($file, 'wb');
                  fwrite($outjpeg, $img);
                  fclose ($outjpeg);

                  //save thumbnail
                  $thumb = GLPI_PICTURE_DIR . "/$sub/${filename}_min.jpg";
                  Toolbox::resizePicture($file, $thumb);

                  return "$sub/${filename}.jpg";
               }
               return $this->fields["picture"];
            }
         }
      }

      return false;
   }


   /**
    * Update emails of the user.
    * Uses _useremails set from UI, not _emails set from LDAP.
    *
    * @return void
    */
   function updateUserEmails() {
      // Update emails  (use _useremails set from UI, not _emails set from LDAP)

      $userUpdated = false;

      if (isset($this->input['_useremails']) && count($this->input['_useremails'])) {
         $useremail = new UserEmail();
         foreach ($this->input['_useremails'] as $id => $email) {
            $email = trim($email);

            // existing email
            if ($id > 0) {
               $params = ['id' => $id];

               // empty email : delete
               if (strlen($email) == 0) {
                  $deleted = $useremail->delete($params);
                  $userUpdated = $userUpdated || $deleted;

               } else { // Update email
                  $params['email'] = $email;
                  $params['is_default'] = $this->input['_default_email'] == $id ? 1 : 0;

                  $existingUserEmail = new UserEmail();
                  $existingUserEmail->getFromDB($id);
                  if ($params['email'] == $existingUserEmail->fields['email']
                      && $params['is_default'] == $existingUserEmail->fields['is_default']) {
                     // Do not update if email has not changed
                     continue;
                  }

                  $updated = $useremail->update($params);
                  $userUpdated = $userUpdated || $updated;
               }

            } else { // New email
               $email_input = ['email'    => $email,
                               'users_id' => $this->fields['id']];
               if (isset($this->input['_default_email'])
                   && ($this->input['_default_email'] == $id)) {
                  $email_input['is_default'] = 1;
               } else {
                  $email_input['is_default'] = 0;
               }
               $added = $useremail->add($email_input);
               $userUpdated = $userUpdated || $added;
            }
         }
      }

      if ($userUpdated) {
         // calling $this->update() here leads to loss in $this->input
         $user = new User();
         $user->update(['id' => $this->fields['id'], 'date_mod' => $_SESSION['glpi_currenttime']]);
      }
   }


   /**
    * Synchronise Dynamics emails of the user.
    * Uses _emails (set from getFromLDAP), not _usermails set from UI.
    *
    * @return void
    */
   function syncDynamicEmails() {
      global $DB;

      $userUpdated = false;

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
               $iterator = $DB->request([
                  'SELECT' => [
                     'id',
                     'users_id',
                     'email',
                     'is_dynamic'
                  ],
                  'FROM'   => 'glpi_useremails',
                  'WHERE'  => ['users_id' => $this->fields['id']]
               ]);

               $useremail = new UserEmail();
               while ($data = $iterator->next()) {
                  $i = array_search($data["email"], $this->input["_emails"]);
                  if ($i !== false) {
                     // Delete found item in order not to add it again
                     unset($this->input["_emails"][$i]);
                  } else if ($data['is_dynamic']) {
                     // Delete not found email
                     $deleted = $useremail->delete(['id' => $data["id"]]);
                     $userUpdated = $userUpdated || $deleted;
                  }
               }

               //If the email need to be added
               if (count($this->input["_emails"]) > 0) {
                  foreach ($this->input["_emails"] as $email) {
                     $added = $useremail->add(['users_id'   => $this->fields["id"],
                                               'email'      => $email,
                                               'is_dynamic' => 1]);
                     $userUpdated = $userUpdated || $added;
                  }
                  unset ($this->input["_emails"]);
               }
            }
         }
      }

      if ($userUpdated) {
         // calling $this->update() here leads to loss in $this->input
         $user = new User();
         $user->update(['id' => $this->fields['id'], 'date_mod' => $_SESSION['glpi_currenttime']]);
      }
   }

   function getRawName() {
      global $CFG_GLPI;

      if (isset($this->fields["id"]) && ($this->fields["id"] > 0)) {
         //getRawName should not add ID
         $bkp_conf = $CFG_GLPI['is_ids_visible'];
         $CFG_GLPI['is_ids_visible'] = 0;
         $bkp_sessconf = (isset($_SESSION['glpiis_ids_visible']) ? $_SESSION["glpiis_ids_visible"] : 0);
         $_SESSION["glpiis_ids_visible"] = 0;
         $name = formatUserName($this->fields["id"],
                               $this->fields["name"],
                               (isset($this->fields["realname"]) ? $this->fields["realname"] : ''),
                               (isset($this->fields["firstname"]) ? $this->fields["firstname"] : ''));

         $CFG_GLPI['is_ids_visible'] = $bkp_conf;
         $_SESSION["glpiis_ids_visible"] = $bkp_sessconf;
         return $name;
      }
      return '';
   }


   /**
    * Function that tries to load the user membership from LDAP
    * by searching in the attributes of the User.
    *
    * @param resource $ldap_connection LDAP connection
    * @param array    $ldap_method     LDAP method
    * @param string   $userdn          Basedn of the user
    * @param string   $login           User login
    *
    * @return string|boolean Basedn of the user / false if not found
    */
   private function getFromLDAPGroupVirtual($ldap_connection, array $ldap_method, $userdn, $login) {
      global $DB;

      // Search in DB the ldap_field we need to search for in LDAP
      $iterator = $DB->request([
         'SELECT DISTINCT' => 'ldap_field',
         'FROM'            => 'glpi_groups',
         'WHERE'           => ['NOT' => ['ldap_field' => '']],
         'ORDER'           => 'ldap_field'
      ]);
      $group_fields = [];

      while ($data = $iterator->next()) {
         $group_fields[] = Toolbox::strtolower($data["ldap_field"]);
      }
      if (count($group_fields)) {
         //Need to sort the array because edirectory don't like it!
         sort($group_fields);

         // If the groups must be retrieve from the ldap user object
         $sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $group_fields);
         $v  = AuthLDAP::get_entries_clean($ldap_connection, $sr);

         for ($i=0; $i < $v['count']; $i++) {
            //Try to find is DN in present and needed: if yes, then extract only the OU from it
            if ((($ldap_method["group_field"] == 'dn') || in_array('ou', $group_fields))
                && isset($v[$i]['dn'])) {

               $v[$i]['ou'] = [];
               for ($tmp=$v[$i]['dn']; count($tmptab = explode(',', $tmp, 2))==2; $tmp=$tmptab[1]) {
                  $v[$i]['ou'][] = $tmptab[1];
               }

               // Search in DB for group with ldap_group_dn
               if (($ldap_method["group_field"] == 'dn')
                   && (count($v[$i]['ou']) > 0)) {
                  $group_iterator = $DB->request([
                     'SELECT' => 'id',
                     'FROM'   => 'glpi_groups',
                     'WHERE'  => ['ldap_group_dn' => Toolbox::addslashes_deep($v[$i]['ou'])]
                  ]);

                  while ($group = $group_iterator->next()) {
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
                  $lgroups = [];
                  foreach (Toolbox::addslashes_deep($v[$i][$field]) as $lgroup) {
                     $lgroups[] = [
                        new \QueryExpression($DB::quoteValue($lgroup).
                                             " LIKE ".
                                             $DB::quoteName('ldap_value'))
                     ];
                  }
                  $group_iterator = $DB->request([
                     'SELECT' => 'id',
                     'FROM'   => 'glpi_groups',
                     'WHERE'  => [
                        'ldap_field' => $field,
                        'OR'         => $lgroups
                     ]
                  ]);

                  while ($group = $group_iterator->next()) {
                     $this->fields["_groups"][] = $group['id'];
                  }
               }
            }
         } // for each ldapresult
      } // count($group_fields)
   }


   /**
    * Function that tries to load the user membership from LDAP
    * by searching in the attributes of the Groups.
    *
    * @param resource $ldap_connection    LDAP connection
    * @param array    $ldap_method        LDAP method
    * @param string   $userdn             Basedn of the user
    * @param string   $login              User login
    *
    * @return boolean true if search is applicable, false otherwise
    */
   private function getFromLDAPGroupDiscret($ldap_connection, array $ldap_method, $userdn, $login) {
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

             $iterator = $DB->request([
               'SELECT' => 'id',
               'FROM'   => 'glpi_groups',
               'WHERE'  => ['ldap_group_dn' => Toolbox::addslashes_deep($result[$ldap_method["group_member_field"]])]
             ]);

            while ($group = $iterator->next()) {
               $this->fields["_groups"][] = $group['id'];
            }
         }
      }
      return true;
   }


   /**
    * Function that tries to load the user informations from LDAP.
    *
    * @param resource $ldap_connection LDAP connection
    * @param array    $ldap_method     LDAP method
    * @param string   $userdn          Basedn of the user
    * @param string   $login           User Login
    * @param boolean  $import          true for import, false for update
    *
    * @return boolean true if found / false if not
    */
   function getFromLDAP($ldap_connection, array $ldap_method, $userdn, $login, $import = true) {
      global $DB, $CFG_GLPI;

      // we prevent some delay...
      if (empty($ldap_method["host"])) {
         return false;
      }

      if (is_resource($ldap_connection)) {
         //Set all the search fields
         $this->fields['password'] = "";

         $fields  = AuthLDAP::getSyncFields($ldap_method);

         //Hook to allow plugin to request more attributes from ldap
         $fields = Plugin::doHookFunction("retrieve_more_field_from_ldap", $fields);

         $fields  = array_filter($fields);
         $f       = self::getLdapFieldNames($fields);

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
         $this->fields["_emails"]    = [];
         // force authtype as we retrieve this user by ldap (we could have login with SSO)
         $this->fields["authtype"] = Auth::LDAP;

         foreach ($fields as $k => $e) {
            $val = AuthLDAP::getFieldValue(
               [$e => self::getLdapFieldValue($e, $v)],
               $e
            );
            if (empty($val)) {
               switch ($k) {
                  case "language" :
                     // Not set value : managed but user class
                     break;

                  case "usertitles_id" :
                  case "usercategories_id" :
                  case 'locations_id' :
                  case 'users_id_supervisor' :
                     $this->fields[$k] = 0;
                     break;

                  default :
                     $this->fields[$k] = "";
               }

            } else {
               $val = Toolbox::addslashes_deep($val);
               switch ($k) {
                  case "email1" :
                  case "email2" :
                  case "email3" :
                  case "email4" :
                     // Manage multivaluable fields
                     if (!empty($v[0][$e])) {
                        foreach ($v[0][$e] as $km => $m) {
                           if (!preg_match('/count/', $km)) {
                              $this->fields["_emails"][] = addslashes($m);
                           }
                        }
                        // Only get them once if duplicated
                        $this->fields["_emails"] = array_unique($this->fields["_emails"]);
                     }
                     break;

                  case "language" :
                     $language = Config::getLanguage($val);
                     if ($language != '') {
                        $this->fields[$k] = $language;
                     }
                     break;

                  case "usertitles_id" :
                     $this->fields[$k] = Dropdown::importExternal('UserTitle', $val);
                     break;

                  case 'locations_id' :
                     // use import to build the location tree
                     $this->fields[$k] = Dropdown::import('Location',
                                                          ['completename' => $val,
                                                           'entities_id'  => 0,
                                                           'is_recursive' => 1]);
                    break;

                  case "usercategories_id" :
                     $this->fields[$k] = Dropdown::importExternal('UserCategory', $val);
                     break;

                  case 'users_id_supervisor':
                     $this->fields[$k] = self::getIdByField('user_dn', $val);
                     break;

                  default :
                     $this->fields[$k] = $val;
               }
            }
         }

         // Empty array to ensure than syncLdapGroups will be done
         $this->fields["_groups"] = [];

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
               $groups = [];
            }

            $this->fields = $rule->processAllRules($groups, Toolbox::stripslashes_deep($this->fields),
                                                   ['type'        => 'LDAP',
                                                         'ldap_server' => $ldap_method["id"],
                                                         'connection'  => $ldap_connection,
                                                         'userdn'      => $userdn,
                                                         'login'       => $this->fields['name'],
                                                         'mail_email'  => $this->fields['_emails']]);

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
    * Get all groups a user belongs to.
    *
    * @param resource $ds                 ldap connection
    * @param string   $ldap_base_dn       Basedn used
    * @param string   $user_dn            Basedn of the user
    * @param string   $group_condition    group search condition
    * @param string   $group_member_field group field member in a user object
    * @param boolean  $use_dn             search dn of user ($login_field=$user_dn) in group_member_field
    * @param string   $login_field        user login field
    *
    * @return array Groups of the user located in [0][$group_member_field] in returned array
    */
   function ldap_get_user_groups($ds, $ldap_base_dn, $user_dn, $group_condition,
                                 $group_member_field, $use_dn, $login_field) {

      $groups     = [];
      $listgroups = [];

      //User dn may contain ( or ), need to espace it!
      $user_dn = str_replace(["(", ")", "\,", "\+"], ["\(", "\)", "\\\,", "\\\+"],
                             $user_dn);

      //Only retrive cn and member attributes from groups
      $attrs = ['dn'];

      if (!$use_dn) {
         $filter = "(& $group_condition (|($group_member_field=$user_dn)
                                          ($group_member_field=$login_field=$user_dn)))";
      } else {
         $filter = "(& $group_condition ($group_member_field=$user_dn))";
      }

      //Perform the search
      $filter = Toolbox::unclean_cross_side_scripting_deep($filter);
      $sr     = ldap_search($ds, $ldap_base_dn, $filter, $attrs);

      //Get the result of the search as an array
      $info = AuthLDAP::get_entries_clean($ds, $sr);
      //Browse all the groups
      for ($i = 0; $i < count($info); $i++) {
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
    * Function that tries to load the user informations from IMAP.
    *
    * @param array  $mail_method  mail method description array
    * @param string $name         login of the user
    *
    * @return boolean true if method is applicable, false otherwise
    */
   function getFromIMAP(array $mail_method, $name) {
      global $DB;

      // we prevent some delay..
      if (empty($mail_method["host"])) {
         return false;
      }

      // some defaults...
      $this->fields['password']  = "";
      // Empty array to ensure than syncDynamicEmails will be done
      $this->fields["_emails"]   = [];
      $email                     = '';
      if (strpos($name, "@")) {
         $email = $name;
      } else {
         $email = $name . "@" . $mail_method["host"];
      }
      $this->fields["_emails"][] = $email;

      $this->fields['name']      = $name;
      //Store date_sync
      $this->fields['date_sync'] = $_SESSION['glpi_currenttime'];
      // force authtype as we retrieve this user by imap (we could have login with SSO)
      $this->fields["authtype"] = Auth::MAIL;

      if (!$DB->isSlave()) {
         //Instanciate the affectation's rule
         $rule = new RuleRightCollection();

         //Process affectation rules :
         //we don't care about the function's return because all the datas are stored in session temporary
         if (isset($this->fields["_groups"])) {
            $groups = $this->fields["_groups"];
         } else {
            $groups = [];
         }
         $this->fields = $rule->processAllRules($groups, Toolbox::stripslashes_deep($this->fields),
                                                ['type'        => 'MAIL',
                                                      'mail_server' => $mail_method["id"],
                                                      'login'       => $name,
                                                      'email'       => $email]);
         $this->fields['_ruleright_process'] = true;
      }
      return true;
   }


   /**
    * Function that tries to load the user informations from the SSO server.
    *
    * @since 0.84
    *
    * @return boolean true if method is applicable, false otherwise
    */
   function getFromSSO() {
      global $DB, $CFG_GLPI;

      $a_field = [];
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
                  if (!preg_match('/count/', $_SERVER[$value])) {
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

         $this->fields = $rule->processAllRules([], Toolbox::stripslashes_deep($this->fields),
                                                ['type'   => 'SSO',
                                                      'email'  => $this->fields["_emails"],
                                                      'login'  => $this->fields["name"]]);

         //If rule  action is ignore import
         if (isset($this->fields["_stop_import"])) {
            return false;
         }
      }
      return true;
   }


   /**
    * Blank passwords field of a user in the DB.
    * Needed for external auth users.
    *
    * @return void
    */
   function blankPassword() {
      global $DB;

      if (!empty($this->fields["name"])) {
         $DB->update(
            $this->getTable(), [
               'password' => ''
            ], [
               'name' => $this->fields['name']
            ]
         );
      }
   }


   /**
    * Print a good title for user pages.
    *
    * @return void
    */
   function title() {
      global $CFG_GLPI;

      $buttons = [];
      $title   = self::getTypeName(Session::getPluralNumber());

      if (static::canCreate()) {
         $buttons["user.form.php"] = __('Add user...');
         $title                    = "";

         if (Auth::useAuthExt()
             && Session::haveRight("user", self::IMPORTEXTAUTHUSERS)) {
            // This requires write access because don't use entity config.
            $buttons["user.form.php?new=1&amp;ext_auth=1"] = __('... From an external source');
         }
      }
      if (Session::haveRight("user", self::IMPORTEXTAUTHUSERS)
         && (static::canCreate() || static::canUpdate())) {
         if (AuthLdap::useAuthLdap()) {
            $buttons["ldap.php"] = __('LDAP directory link');
         }
      }
      Html::displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", self::getTypeName(Session::getPluralNumber()), $title,
                         $buttons);
   }


   /**
    * Check if current user have more right than the specified one.
    *
    * @param integer $ID ID of the user
    *
    * @return boolean
    */
   function currentUserHaveMoreRightThan($ID) {

      $user_prof = Profile_User::getUserProfiles($ID);
      return Profile::currentUserHaveMoreRightThan($user_prof);
   }


   /**
    * Print the user form.
    *
    * @param integer $ID    ID of the user
    * @param array $options Options
    *     - string   target        Form target
    *     - boolean  withtemplate  Template or basic item
    *
    * @return boolean true if user found, false otherwise
    */
   function showForm($ID, array $options = []) {
      global $CFG_GLPI;

      // Affiche un formulaire User
      if (($ID != Session::getLoginUserID()) && !self::canView()) {
         return false;
      }

      $this->initForm($ID, $options);

      $ismyself = $ID == Session::getLoginUserID();
      $higherrights = $this->currentUserHaveMoreRightThan($ID);
      if ($ID) {
         $caneditpassword = $higherrights || ($ismyself && Session::haveRight('password_update', 1));
      } else {
         // can edit on creation form
         $caneditpassword = true;
      }

      $extauth = !(($this->fields["authtype"] == Auth::DB_GLPI)
                   || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                       && !empty($this->fields["password"])));

      $formtitle = $this->getTypeName(1);

      if ($ID > 0) {
         $formtitle .= "<a class='pointer far fa-address-card' target='_blank' href='".$CFG_GLPI["root_doc"].
                       User::getFormURLWithID($ID)."&amp;getvcard=1' title='".__s('Download user VCard').
                       "'><span class='sr-only'>". __('Vcard')."</span></a>";
      }

      $options['formtitle']   = $formtitle;
      $options['formoptions'] = " enctype='multipart/form-data'";
      $this->showFormHeader($options);
      $rand = mt_rand();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='name'>" . __('Login') . "</label></td>";
      if ($this->fields["name"] == "" ||
          !empty($this->fields["password"])
          || ($this->fields["authtype"] == Auth::DB_GLPI) ) {
         //display login field for new records, or if this is not external auth
         echo "<td><input name='name' id='name' value=\"" . $this->fields["name"] . "\"></td>";
      } else {
         echo "<td class='b'>" . $this->fields["name"];
         echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\"></td>";
      }

      if (!empty($this->fields["name"])) {
         echo "<td rowspan='4'>" . __('Picture') . "</td>";
         echo "<td rowspan='4'>";
         echo "<div class='user_picture_border_small' id='picture$rand'>";
         echo "<img class='user_picture_small' alt=\"".__s('Picture')."\" src='".
                User::getThumbnailURLForPicture($this->fields['picture'])."'>";
         // echo "<img src='".self::getURLForPicture($this->fields["picture"])."' class='user_picture'/>";
         echo "</div>";
         $full_picture = "<div class='user_picture_border'>";
         $full_picture .= "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
                            User::getURLForPicture($this->fields['picture'])."'>";
         $full_picture .= "</div>";

         Html::showTooltip($full_picture, ['applyto' => "picture$rand"]);
         echo Html::file(['name' => 'picture', 'display' => false, 'onlyimages' => true]);
         echo "<input type='checkbox' name='_blank_picture'>&nbsp;".__('Clear');
         echo "</td>";
      } else {
         echo "<td rowspan='4'></td>";
         echo "<td rowspan='4'></td>";
      }
      echo "</tr>";

      //If it's an external auth, check if the sync_field must be displayed
      if ($extauth
         && $this->fields['auths_id']
            && AuthLDAP::isSyncFieldConfigured($this->fields['auths_id'])) {
         $syncrand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_sync_field$syncrand'>" . __('Synchronization field') . "</label></td><td>";
         if (self::canUpdate()
             && (!$extauth || empty($ID))) {
                Html::autocompletionTextField($this, "sync_field", ['rand' => $syncrand]);
         } else {
            if (empty($this->fields['sync_field'])) {
               echo Dropdown::EMPTY_VALUE;
            } else {
               echo $this->fields['sync_field'];
            }
         }
         echo "</td></tr>";
      } else {
         echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
      }

      $surnamerand = mt_rand();
      echo "<tr class='tab_bg_1'><td><label for='textfield_realname$surnamerand'>" . __('Surname') . "</label></td><td>";
      Html::autocompletionTextField($this, "realname", ['rand' => $surnamerand]);
      echo "</td></tr>";

      $firstnamerand = mt_rand();
      echo "<tr class='tab_bg_1'><td><label for='textfield_firstname$firstnamerand'>" . __('First name') . "</label></td><td>";
      Html::autocompletionTextField($this, "firstname", ['rand' => $firstnamerand]);
      echo "</td></tr>";

      //do some rights verification
      if (self::canUpdate()
          && (!$extauth || empty($ID))
          && $caneditpassword) {
         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='password'>" . __('Password')."</label></td>";
         echo "<td><input id='password' type='password' name='password' value='' size='20'
                    autocomplete='off' onkeyup=\"return passwordCheck();\"></td>";
         echo "<td rowspan='2'>";
         if ($CFG_GLPI["use_password_security"]) {
            echo __('Password security policy');
         }
         echo "</td>";
         echo "<td rowspan='2'>";
         Config::displayPasswordSecurityChecks();
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='password2'>" . __('Password confirmation') . "</label></td>";
         echo "<td><input type='password' id='password2' name='password2' value='' size='20' autocomplete='off'>";
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      if (!GLPI_DEMO_MODE) {
         $activerand = mt_rand();
         echo "<td><label for='dropdown_is_active$activerand'>".__('Active')."</label></td><td>";
         Dropdown::showYesNo('is_active', $this->fields['is_active'], -1, ['rand' => $activerand]);
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "<td>" . _n('Email', 'Emails', Session::getPluralNumber());
      UserEmail::showAddEmailButton($this);
      echo "</td><td>";
      UserEmail::showForUser($this);
      echo "</td>";
      echo "</tr>";

      if (!GLPI_DEMO_MODE) {
         $sincerand = mt_rand();
         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='showdate$sincerand'>".__('Valid since')."</label></td><td>";
         Html::showDateTimeField("begin_date", ['value'       => $this->fields["begin_date"],
                                                'rand'        => $sincerand,
                                                'timestep'    => 1,
                                                'maybeempty'  => true]);
         echo "</td>";

         $untilrand = mt_rand();
         echo "<td><label for='showdate$untilrand'>".__('Valid until')."</label></td><td>";
         Html::showDateTimeField("end_date", ['value'       => $this->fields["end_date"],
                                              'rand'        => $untilrand,
                                              'timestep'    => 1,
                                              'maybeempty'  => true]);
         echo "</td></tr>";
      }

      $phonerand = mt_rand();
      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_phone$phonerand'>" .  __('Phone') . "</label></td><td>";
      Html::autocompletionTextField($this, "phone", ['rand' => $phonerand]);
      echo "</td>";
      //Authentications information : auth method used and server used
      //don't display is creation of a new user'
      if (!empty($ID)) {
         if (Session::haveRight(self::$rightname, self::READAUTHENT)) {
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
            if ($this->fields['is_deleted_ldap']) {
               echo '<br>'.__('User missing in LDAP directory');
            }

            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
      } else {
         echo "<td colspan='2'><input type='hidden' name='authtype' value='1'></td>";
      }

      echo "</tr>";

      $mobilerand = mt_rand();
      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_mobile$mobilerand'>" . __('Mobile phone') . "</label></td><td>";
      Html::autocompletionTextField($this, "mobile", ['rand' => $mobilerand]);
      echo "</td>";
      $catrand = mt_rand();
      echo "<td><label for='dropdown_usercategories_id$catrand'>" . __('Category') . "</label></td><td>";
      UserCategory::dropdown(['value' => $this->fields["usercategories_id"], 'rand' => $catrand]);
      echo "</td></tr>";

      $phone2rand = mt_rand();
      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_phone2$phone2rand'>" .  __('Phone 2') . "</label></td><td>";
      Html::autocompletionTextField($this, "phone2", ['rand' => $phone2rand]);
      echo "</td>";
      echo "<td rowspan='4' class='middle'><label for='comment'>" . __('Comments') . "</label></td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='45' rows='6' id='comment' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      $admnumrand = mt_rand();
      echo "<tr class='tab_bg_1'><td><label for='textfield_registration_number$admnumrand'>" . __('Administrative number') . "</label></td><td>";
      Html::autocompletionTextField($this, "registration_number", ['rand' => $admnumrand]);
      echo "</td></tr>";

      $titlerand = mt_rand();
      echo "<tr class='tab_bg_1'><td><label for='dropdown_usertitles_id$titlerand'>" . _x('person', 'Title') . "</label></td><td>";
      UserTitle::dropdown(['value' => $this->fields["usertitles_id"], 'rand' => $titlerand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      if (!empty($ID)) {
         $locrand = mt_rand();
         echo "<td><label for='dropdown_locations_id$locrand'>" . __('Location') . "</label></td><td>";
         $entities = $this->getEntities();
         if (count($entities) <= 0) {
            $entities = -1;
         }
         Location::dropdown(['value'  => $this->fields["locations_id"],
                             'rand'   => $locrand,
                             'entity' => $entities]);
         echo "</td>";
      }
      echo "</tr>";

      if (empty($ID)) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='2'>"._n('Authorization', 'Authorizations', 1)."</th>";
         $recurrand = mt_rand();
         echo "<td><label for='dropdown__is_recursive$recurrand'>" .  __('Recursive') . "</label></td><td>";
         Dropdown::showYesNo("_is_recursive", 0, -1, ['rand' => $recurrand]);
         echo "</td></tr>";
         $profilerand = mt_rand();
         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='dropdown__profiles_id$profilerand'>" .  __('Profile') . "</label></td><td>";
         Profile::dropdownUnder(['name'  => '_profiles_id',
                                 'rand'  => $profilerand,
                                 'value' => Profile::getDefault()]);

         $entrand = mt_rand();
         echo "</td><td><label for='dropdown__entities_id$entrand'>" .  __('Entity') . "</label></td><td>";
         Entity::dropdown(['name'                => '_entities_id',
                           'display_emptychoice' => false,
                           'rand'                => $entrand,
                           'entity'              => $_SESSION['glpiactiveentities']]);
         echo "</td></tr>";
      } else {
         if ($higherrights || $ismyself) {
            $profilerand = mt_rand();
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='dropdown_profiles_id$profilerand'>" .  __('Default profile') . "</label></td><td>";

            $options   = Dropdown::getDropdownArrayNames('glpi_profiles',
                                                         Profile_User::getUserProfiles($this->fields['id']));

            Dropdown::showFromArray("profiles_id", $options,
                                    ['value'               => $this->fields["profiles_id"],
                                     'rand'                => $profilerand,
                                     'display_emptychoice' => true]);
         }
         if ($higherrights) {
            $entrand = mt_rand();
            echo "</td><td><label for='dropdown_entities_id$entrand'>" .  __('Default entity') . "</label></td><td>";
            $entities = $this->getEntities();
            Entity::dropdown(['value'  => $this->fields["entities_id"],
                              'rand'   => $entrand,
                              'entity' => $entities]);
            echo "</td></tr>";

            $grouprand = mt_rand();
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='dropdown_profiles_id$grouprand'>" .  __('Default group') . "</label></td><td>";

            $options = [];
            foreach (Group_User::getUserGroups($this->fields['id']) as $group) {
               $options[$group['id']] = $group['completename'];
            }

            Dropdown::showFromArray("groups_id", $options,
                                    ['value'               => $this->fields["groups_id"],
                                     'rand'                => $grouprand,
                                     'display_emptychoice' => true]);

            echo "</td>";
            $userrand = mt_rand();
            echo "<td><label for='dropdown_users_id_supervisor_$userrand'>" .  __('Responsible') . "</label></td><td>";

            User::dropdown(['name'   => 'users_id_supervisor',
                            'value'  => $this->fields["users_id_supervisor"],
                            'rand'   => $userrand,
                            'entity' => $_SESSION["glpiactive_entity"],
                            'right'  => 'all']);
            echo "</td></tr>";
         }

         if ($this->can($ID, UPDATE)) {
            echo "<tr class='tab_bg_1'><th colspan='4'>". __('Remote access keys') ."</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            echo __("Personal token");
            echo "</td><td colspan='2'>";

            if (!empty($this->fields["personal_token"])) {
               echo "<div class='copy_to_clipboard_wrapper'>";
               echo Html::input('_personal_token', [
                                    'value'    => $this->fields["personal_token"],
                                    'style'    => 'width:90%'
                                ]);
               echo "</div>";
               echo "(".sprintf(__('generated on %s'),
                                   Html::convDateTime($this->fields["personal_token_date"])).")";
            }
            echo "</td><td>";
            Html::showCheckbox(['name'  => '_reset_personal_token',
                                'title' => __('Regenerate')]);
            echo "&nbsp;&nbsp;".__('Regenerate');
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>";
            echo __("API token");
            echo "</td><td colspan='2'>";
            if (!empty($this->fields["api_token"])) {
               echo "<div class='copy_to_clipboard_wrapper'>";
               echo Html::input('_api_token', [
                                    'value'    => $this->fields["api_token"],
                                    'style'    => 'width:90%'
                                ]);
               echo "</div>";
               echo "(".sprintf(__('generated on %s'),
                                   Html::convDateTime($this->fields["api_token_date"])).")";
            }
            echo "</td><td>";
            Html::showCheckbox(['name'  => '_reset_api_token',
                                'title' => __('Regenerate')]);
            echo "&nbsp;&nbsp;".__('Regenerate');
            echo "</td></tr>";
         }

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>";
         if ($this->fields["last_login"]) {
            printf(__('Last login on %s'), HTML::convDateTime($this->fields["last_login"]));
         }
         echo "</td><td colspan='2'class='center'>";

         echo "</td></tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   /** Print the user personnal information for check.
    *
    * @param integer $userid ID of the user
    *
    * @return void|boolean false if user is not the current user, otherwise print form
    *
    * @since 0.84
    */
   static function showPersonalInformation($userid) {
      global $CFG_GLPI;

      $user = new self();
      if (!$user->can($userid, READ)
          && ($userid != Session::getLoginUserID())) {
         return false;
      }
      echo "<table class='tab_glpi left' width='100%'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' width='20%'>";
      echo __('Name');
      echo "</td><td width='30%'>";
      echo getUserName($userid);
      echo "</td>";
      echo "<td class='b'  width='20%'>";
      echo __('Phone');
      echo "</td><td width='30%'>";
      echo $user->getField('phone');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b'>";
      echo __('Phone 2');
      echo "</td><td>";
      echo $user->getField('phone2');
      echo "</td>";
      echo "<td class='b'>";
      echo __('Mobile phone');
      echo "</td><td>";
      echo $user->getField('mobile');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b'>";
      echo __('Email');
      echo "</td><td>";
      echo $user->getDefaultEmail();
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b'>";
      echo __('Location');
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
    * Print the user preference form.
    *
    * @param string  $target Form target
    * @param integer $ID     ID of the user
    *
    * @return boolean true if user found, false otherwise
    */
   function showMyForm($target, $ID) {
      global $CFG_GLPI;

      // Affiche un formulaire User
      if (($ID != Session::getLoginUserID())
          && !$this->currentUserHaveMoreRightThan($ID)) {
         return false;
      }
      if ($this->getFromDB($ID)) {
         $rand     = mt_rand();
         $authtype = $this->getAuthMethodsByID();

         $extauth  = !(($this->fields["authtype"] == Auth::DB_GLPI)
                       || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                           && !empty($this->fields["password"])));

         // No autocopletion :
         $save_autocompletion                 = $CFG_GLPI["use_ajax_autocompletion"];
         $CFG_GLPI["use_ajax_autocompletion"] = false;

         echo "<div class='center'>";
         echo "<form method='post' name='user_manager' enctype='multipart/form-data' action='".$target."' autocomplete='off'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>".sprintf(__('%1$s: %2$s'), __('Login'), $this->fields["name"]);
         echo "<input type='hidden' name='name' value='" . $this->fields["name"] . "'>";
         echo "<input type='hidden' name='id' value='" . $this->fields["id"] . "'>";
         echo "</th></tr>";

         $surnamerand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_realname$surnamerand'>" . __('Surname') . "</label></td><td>";

         if ($extauth
             && isset($authtype['realname_field'])
             && !empty($authtype['realname_field'])) {

            echo $this->fields["realname"];
         } else {
            Html::autocompletionTextField($this, "realname", ['rand' => $surnamerand]);
         }
         echo "</td>";

         if (!empty($this->fields["name"])) {
            echo "<td rowspan='4'>" . __('Picture') . "</td>";
            echo "<td rowspan='4'>";
            echo "<div class='user_picture_border_small' id='picture$rand'>";
            echo "<img class='user_picture_small' alt=\"".__s('Picture')."\" src='".
                   User::getThumbnailURLForPicture($this->fields['picture'])."'>";
            echo "</div>";
            $full_picture  = "<div class='user_picture_border'>";
            $full_picture .= "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
                              User::getURLForPicture($this->fields['picture'])."'>";
            $full_picture .= "</div>";

            Html::showTooltip($full_picture, ['applyto' => "picture$rand"]);
            echo Html::file(['name' => 'picture', 'display' => false, 'onlyimages' => true]);

            echo "&nbsp;";
            Html::showCheckbox(['name' => '_blank_picture', 'title' => __('Clear')]);
            echo "&nbsp;".__('Clear');

            echo "</td>";
            echo "</tr>";
         }

         $firstnamerand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_firstname$firstnamerand'>" . __('First name') . "</label></td><td>";
         if ($extauth
             && isset($authtype['firstname_field'])
             && !empty($authtype['firstname_field'])) {

            echo $this->fields["firstname"];
         } else {
            Html::autocompletionTextField($this, "firstname", ['rand' => $firstnamerand]);
         }
         echo "</td></tr>";

         if ($extauth
            && $this->fields['auths_id']
               && AuthLDAP::isSyncFieldConfigured($this->fields['auths_id'])) {
            echo "<tr class='tab_bg_1'><td>" . __('Synchronization field') . "</td><td>";
            if (empty($this->fields['sync_field'])) {
               echo Dropdown::EMPTY_VALUE;
            } else {
               echo $this->fields['sync_field'];
            }
            echo "</td></tr>";
         } else {
            echo "<tr class='tab_bg_1'><td colspan='2'></td></tr>";
         }

         echo "<tr class='tab_bg_1'>";

         if (!GLPI_DEMO_MODE) {
            $langrand = mt_rand();
            echo "<td><label for='dropdown_language$langrand'>" . __('Language') . "</label></td><td>";
            // Language is stored as null in DB if value is same as the global config.
            $language = $this->fields["language"];
            if (null === $this->fields["language"]) {
               $language = $CFG_GLPI['language'];
            }
            Dropdown::showLanguages(
               "language",
               [
                  'rand'  => $langrand,
                  'value' => $language,
               ]
            );
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";

         //do some rights verification
         if (!$extauth
             && Session::haveRight("password_update", "1")) {
            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='password'>" . __('Password') . "</label></td>";
            echo "<td><input id='password' type='password' name='password' value='' size='30' autocomplete='off' onkeyup=\"return passwordCheck();\">";
            echo "</td>";
            echo "<td rowspan='2'>";
            if ($CFG_GLPI["use_password_security"]) {
               echo __('Password security policy');
            }
            echo "</td>";
            echo "<td rowspan='2'>";
            Config::displayPasswordSecurityChecks();
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td><label for='password2'>" . __('Password confirmation') . "</label></td>";
            echo "<td><input type='password' name='password2' id='password2' value='' size='30' autocomplete='off'>";
            echo "</td></tr>";

         }

         $phonerand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_phone$phonerand'>" .  __('Phone') . "</label></td><td>";

         if ($extauth
             && isset($authtype['phone_field']) && !empty($authtype['phone_field'])) {
            echo $this->fields["phone"];
         } else {
            Html::autocompletionTextField($this, "phone", ['rand' => $phonerand]);
         }
         echo "</td>";
         echo "<td class='top'>" . _n('Email', 'Emails', Session::getPluralNumber());
         UserEmail::showAddEmailButton($this);
         echo "</td><td>";
         UserEmail::showForUser($this);
         echo "</td>";
         echo "</tr>";

         $mobilerand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_mobile$mobilerand'>" . __('Mobile phone') . "</label></td><td>";

         if ($extauth
             && isset($authtype['mobile_field']) && !empty($authtype['mobile_field'])) {
            echo $this->fields["mobile"];
         } else {
            Html::autocompletionTextField($this, "mobile", ['rand' => $mobilerand]);
         }
         echo "</td>";

         if (count($_SESSION['glpiprofiles']) >1) {
            $profilerand = mt_rand();
            echo "<td><label for='dropdown_profiles_id$profilerand'>" . __('Default profile') . "</label></td><td>";

            $options = Dropdown::getDropdownArrayNames('glpi_profiles',
                                                       Profile_User::getUserProfiles($this->fields['id']));
            Dropdown::showFromArray("profiles_id", $options,
                                    ['value'               => $this->fields["profiles_id"],
                                     'rand'                => $profilerand,
                                     'display_emptychoice' => true]);
            echo "</td>";

         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";

         $phone2rand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_phone2$phone2rand'>" .  __('Phone 2') . "</label></td><td>";

         if ($extauth
             && isset($authtype['phone2_field']) && !empty($authtype['phone2_field'])) {
            echo $this->fields["phone2"];
         } else {
            Html::autocompletionTextField($this, "phone2", ['rand' => $phone2rand]);
         }
         echo "</td>";

         $entities = $this->getEntities();
         if (!GLPI_DEMO_MODE
             && (count($_SESSION['glpiactiveentities']) > 1)) {
            $entrand = mt_rand();
            echo "<td><label for='dropdown_entities_id$entrand'>" . __('Default entity') . "</td><td>";
            Entity::dropdown(['value'  => $this->fields['entities_id'],
                              'rand'   => $entrand,
                              'entity' => $entities]);
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         $admnumrand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='textfield_registration_number$admnumrand'>" . __('Administrative number') . "</label></td><td>";
         if ($extauth
             && isset($authtype['registration_number_field']) && !empty($authtype['registration_number_field'])) {
            echo $this->fields["registration_number"];
         } else {
            Html::autocompletionTextField($this, "registration_number", ['rand' => $admnumrand]);
         }
         echo "</td><td colspan='2'></td></tr>";

         $locrand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='dropdown_locations_id$locrand'>" . __('Location') . "</label></td><td>";
         Location::dropdown(['value'  => $this->fields['locations_id'],
                             'rand'   => $locrand,
                             'entity' => $entities]);

         if (Config::canUpdate()) {
            $moderand = mt_rand();
            echo "<td><label for='dropdown_use_mode$moderand'>" . __('Use GLPI in mode') . "</label></td><td>";
            $modes = [
               Session::NORMAL_MODE => __('Normal'),
               Session::DEBUG_MODE  => __('Debug'),
            ];
            Dropdown::showFromArray('use_mode', $modes, ['value' => $this->fields["use_mode"], 'rand' => $moderand]);
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><th colspan='4'>". __('Remote access keys') ."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         echo __("Personal token");
         echo "</td><td colspan='2'>";

         if (!empty($this->fields["personal_token"])) {
            echo "<div class='copy_to_clipboard_wrapper'>";
            echo Html::input('_personal_token', [
                                 'value'    => $this->fields["personal_token"],
                                 'style'    => 'width:90%'
                             ]);
            echo "</div>";
            echo "(".sprintf(__('generated on %s'),
                                Html::convDateTime($this->fields["personal_token_date"])).")";
         }
         echo "</td><td>";
         Html::showCheckbox(['name'  => '_reset_personal_token',
                             'title' => __('Regenerate')]);
         echo "&nbsp;&nbsp;".__('Regenerate');
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>";
         echo __("API token");
         echo "</td><td colspan='2'>";
         if (!empty($this->fields["api_token"])) {
            echo "<div class='copy_to_clipboard_wrapper'>";
            echo Html::input('_api_token', [
                                 'value'    => $this->fields["api_token"],
                                 'style'    => 'width:90%'
                             ]);
            echo "</div>";
            echo "(".sprintf(__('generated on %s'),
                                Html::convDateTime($this->fields["api_token_date"])).")";
         }
         echo "</td><td>";
         Html::showCheckbox(['name'  => '_reset_api_token',
                             'title' => __('Regenerate')]);
         echo "&nbsp;&nbsp;".__('Regenerate');
         echo "</td></tr>";

         echo "<tr><td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
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
    * Get all the authentication method parameters for the current user.
    *
    * @return array
    */
   function getAuthMethodsByID() {
      return Auth::getMethodsByID($this->fields["authtype"], $this->fields["auths_id"]);
   }


   function pre_updateInDB() {
      global $DB;

      if (($key = array_search('name', $this->updates)) !== false) {
         /// Check if user does not exists
         $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
               'name'   => $this->input['name'],
               'id'     => ['<>', $this->input['id']]
            ]
         ]);

         if (count($iterator)) {
            //To display a message
            $this->fields['name'] = $this->oldvalues['name'];
            unset($this->updates[$key]);
            unset($this->oldvalues['name']);
            Session::addMessageAfterRedirect(__('Unable to update login. A user already exists.'),
                                             false, ERROR);
         }

         if (!Auth::isValidLogin($this->input['name'])) {
            $this->fields['name'] = $this->oldvalues['name'];
            unset($this->updates[$key]);
            unset($this->oldvalues['name']);
            Session::addMessageAfterRedirect(__('The login is not valid. Unable to update login.'),
                                             false, ERROR);
         }

      }

      /// Security system except for login update
      if (Session::getLoginUserID()
          && !Session::haveRight("user", UPDATE)
          && !strpos($_SERVER['PHP_SELF'], "/front/login.php")) {

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

               if (($key = array_search("is_active", $this->updates)) !== false) {
                  unset ($this->updates[$key]);
                  unset($this->oldvalues['is_active']);
               }

               if (($key = array_search("comment", $this->updates)) !== false) {
                  unset ($this->updates[$key]);
                  unset($this->oldvalues['comment']);
               }
            }
         }
      }
   }

   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['Group_User'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
                                                         = __('Associate to a group');
         $actions['Group_User'.MassiveAction::CLASS_ACTION_SEPARATOR.'remove']
                                                         = __('Dissociate from a group');
         $actions['Profile_User'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
                                                         = __('Associate to a profile');
         $actions['Profile_User'.MassiveAction::CLASS_ACTION_SEPARATOR.'remove']
                                                         = __('Dissociate from a profile');
         $actions['Group_User'.MassiveAction::CLASS_ACTION_SEPARATOR.'change_group_user']
                                                         = __("Move to group");
      }

      if (Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
         $prefix                                    = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'change_authtype']        = _x('button', 'Change the authentication method');
         $actions[$prefix.'force_user_ldap_update'] = __('Force synchronization');
      }
      return $actions;
   }

   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'change_authtype' :
            $rand             = Auth::dropdown(['name' => 'authtype']);
            $paramsmassaction = ['authtype' => '__VALUE__'];
            Ajax::updateItemOnSelectEvent("dropdown_authtype$rand", "show_massiveaction_field",
                                          $CFG_GLPI["root_doc"].
                                             "/ajax/dropdownMassiveActionAuthMethods.php",
                                          $paramsmassaction);
            echo "<span id='show_massiveaction_field'><br><br>";
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction'])."</span>";
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'force_user_ldap_update' :
            foreach ($ids as $id) {
               if ($item->can($id, UPDATE)) {
                  if (($item->fields["authtype"] == Auth::LDAP)
                      || ($item->fields["authtype"] == Auth::EXTERNAL)) {
                     if (AuthLdap::forceOneUserSynchronization($item, false)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case 'change_authtype' :
            $input = $ma->getInput();
            if (!isset($input["authtype"])
                || !isset($input["auths_id"])) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
               return;
            }
            if (Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
               if (User::changeAuthMethod($ids, $input["authtype"], $input["auths_id"])) {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
               $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   function rawSearchOptions() {
      // forcegroup by on name set force group by for all items
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Login'),
         'datatype'           => 'itemlink',
         'forcegroupby'       => true,
         'massiveaction'      => false
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
         'id'                 => '34',
         'table'              => $this->getTable(),
         'field'              => 'realname',
         'name'               => __('Last name'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'firstname',
         'name'               => __('First name'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_useremails',
         'field'              => 'email',
         'name'               => _n('Email', 'Emails', Session::getPluralNumber()),
         'datatype'           => 'email',
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '150',
         'table'              => $this->getTable(),
         'field'              => 'picture',
         'name'               => __('Picture'),
         'datatype'           => 'specific',
         'nosearch'           => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => $this->getTable(),
         'field'              => 'sync_field',
         'name'               => __('Synchronization field'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'phone',
         'name'               => __('Phone'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'phone2',
         'name'               => __('Phone 2'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'mobile',
         'name'               => __('Mobile phone'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => _n('Group', 'Groups', Session::getPluralNumber()),
         'forcegroupby'       => true,
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_groups_users',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'last_login',
         'name'               => __('Last login'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'authtype',
         'name'               => __('Authentication'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'additionalfields'   => [
            '0'                  => 'auths_id'
         ]
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => 'glpi_authldaps',
         'field'              => 'name',
         'linkfield'          => 'auths_id',
         'name'               => __('LDAP directory for authentication'),
         'massiveaction'      => false,
         'joinparams'         => [
             'condition'          => 'AND REFTABLE.`authtype` = ' . Auth::LDAP
         ],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_authmails',
         'field'              => 'name',
         'linkfield'          => 'auths_id',
         'name'               => __('Email server for authentication'),
         'massiveaction'      => false,
         'joinparams'         => [
            'condition'          => 'AND REFTABLE.`authtype` = ' . Auth::MAIL
         ],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'language',
         'name'               => __('Language'),
         'datatype'           => 'language',
         'display_emptychoice' => true,
         'emptylabel'         => 'Default value'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => 'glpi_profiles',
         'field'              => 'name',
         'name'               => sprintf(__('%1$s (%2$s)'), _n('Profile', 'Profiles', Session::getPluralNumber()),
                                                 _n('Entity', 'Entities', 1)),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_profiles_users',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'user_dn',
         'name'               => __('User DN'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => $this->getTable(),
         'field'              => 'registration_number',
         'name'               => __('Administrative number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'date_sync',
         'datatype'           => 'datetime',
         'name'               => __('Last synchronization'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => $this->getTable(),
         'field'              => 'is_deleted_ldap',
         'name'               => __('Deleted user in LDAP directory'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'linkfield'          => 'entities_id',
         'field'              => 'completename',
         'name'               => sprintf(__('%1$s (%2$s)'), _n('Entity', 'Entities', Session::getPluralNumber()),
                                                 _n('Profile', 'Profiles', 1)),
         'forcegroupby'       => true,
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_profiles_users',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '81',
         'table'              => 'glpi_usertitles',
         'field'              => 'name',
         'name'               => __('Title'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '82',
         'table'              => 'glpi_usercategories',
         'field'              => 'name',
         'name'               => __('Category'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '79',
         'table'              => 'glpi_profiles',
         'field'              => 'name',
         'name'               => __('Default profile'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '77',
         'table'              => 'glpi_entities',
         'field'              => 'name',
         'massiveaction'      => true,
         'name'               => __('Default entity'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '62',
         'table'              => $this->getTable(),
         'field'              => 'begin_date',
         'name'               => __('Begin date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '63',
         'table'              => $this->getTable(),
         'field'              => 'end_date',
         'name'               => __('End date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => 'glpi_tickets',
         'field'              => 'id',
         'name'               => __('Number of tickets as requester'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_tickets_users',
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => 'AND NEWTABLE.`type` = ' . CommonITILActor::REQUESTER
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => 'glpi_tickets',
         'field'              => 'id',
         'name'               => __('Number of written tickets'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'linkfield'          => 'users_id_recipient'
         ]
      ];

      $tab[] = [
         'id'                 => '64',
         'table'              => 'glpi_tickets',
         'field'              => 'id',
         'name'               => __('Number of assigned tickets'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_tickets_users',
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => 'AND NEWTABLE.`type` = '.CommonITILActor::ASSIGN
               ]
            ]
         ]
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      return $tab;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'authtype':
            $auths_id = 0;
            if (isset($values['auths_id']) && !empty($values['auths_id'])) {
               $auths_id = $values['auths_id'];
            }
            return Auth::getMethodName($values[$field], $auths_id);
         case 'picture':
            if (isset($options['html']) && $options['html']) {
               return Html::image(self::getThumbnailURLForPicture($values['picture']),
                                  ['class' => 'user_picture_small', 'alt' => __('Picture')]);
            }
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
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
    * Get all groups where the current user have delegating.
    *
    * @since 0.83
    *
    * @param integer|string $entities_id ID of the entity to restrict
    *
    * @return integer[]
    */
   static function getDelegateGroupsForUser($entities_id = '') {
      global $DB;

      $iterator = $DB->request([
         'SELECT DISTINCT' => 'glpi_groups_users.groups_id',
         'FROM'            => 'glpi_groups_users',
         'INNER JOIN'      => [
            'glpi_groups'  => [
               'FKEY'   => [
                  'glpi_groups_users'  => 'groups_id',
                  'glpi_groups'        => 'id'
               ]
            ]
         ],
         'WHERE'           => [
            'glpi_groups_users.users_id'        => Session::getLoginUserID(),
            'glpi_groups_users.is_userdelegate' => 1
         ] + getEntitiesRestrictCriteria('glpi_groups', '', $entities_id, 1)
      ]);

      $groups = [];
      while ($data = $iterator->next()) {
         $groups[$data['groups_id']] = $data['groups_id'];
      }
      return $groups;
   }


   /**
    * Execute the query to select box with all glpi users where select key = name
    *
    * Internaly used by showGroup_Users, dropdownUsers and ajax/getDropdownUsers.php
    *
    * @param boolean         $count            true if execute an count(*) (true by default)
    * @param string|string[] $right            limit user who have specific right (default 'all')
    * @param integer         $entity_restrict  Restrict to a defined entity (default -1)
    * @param integer         $value            default value (default 0)
    * @param integer[]       $used             Already used items ID: not to display in dropdown
    * @param string          $search           pattern (default '')
    * @param integer         $start            start LIMIT value (default 0)
    * @param integer         $limit            limit LIMIT value (default -1 no limit)
    * @param boolean         $inactive_deleted true to retreive also inactive or deleted users
    *
    * @return mysqli_result|boolean
    */
   static function getSqlSearchResult ($count = true, $right = "all", $entity_restrict = -1, $value = 0,
                                       array $used = [], $search = '', $start = 0, $limit = -1,
                                       $inactive_deleted = 0) {
      global $DB;

      // No entity define : use active ones
      if ($entity_restrict < 0) {
         $entity_restrict = $_SESSION["glpiactiveentities"];
      }

      $joinprofile      = false;
      $joinprofileright = false;
      $WHERE = [];

      switch ($right) {
         case "interface" :
            $joinprofile = true;
            $WHERE = [
               'glpi_profiles.interface' => 'central'
            ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1);
            break;

         case "id" :
            $WHERE = ['glpi_users.id' => Session::getLoginUserID()];
            break;

         case "delegate" :
            $groups = self::getDelegateGroupsForUser($entity_restrict);
            $users  = [];
            if (count($groups)) {
               $iterator = $DB->request([
                  'SELECT'    => 'glpi_users.id',
                  'FROM'      => 'glpi_groups_users',
                  'LEFT JOIN' => [
                     'glpi_users'   => [
                        'FKEY'   => [
                           'glpi_groups_users'  => 'users_id',
                           'glpi_users'         => 'id'
                        ]
                     ]
                  ],
                  'WHERE'     => [
                     'glpi_groups_users.groups_id' => $groups,
                     'glpi_groups_users.users_id'  => ['<>', Session::getLoginUserID()]
                  ]
               ]);
               while ($data = $iterator->next()) {
                     $users[$data["id"]] = $data["id"];
               }
            }
            // Add me to users list for central
            if (Session::getCurrentInterface() == 'central') {
               $users[Session::getLoginUserID()] = Session::getLoginUserID();
            }

            if (count($users)) {
               $WHERE = ['glpi_users.id' => $users];
            }
            break;

         case "groups" :
            $groups = [];
            if (isset($_SESSION['glpigroups'])) {
               $groups = $_SESSION['glpigroups'];
            }
            $users  = [];
            if (count($groups)) {
               $iterator = $DB->request([
                  'SELECT'    => 'glpi_users.id',
                  'FROM'      => 'glpi_groups_users',
                  'LEFT JOIN' => [
                     'glpi_users'   => [
                        'FKEY'   => [
                           'glpi_groups_users'  => 'users_id',
                           'glpi_users'         => 'id'
                        ]
                     ]
                  ],
                  'WHERE'     => [
                     'glpi_groups_users.groups_id' => $groups,
                     'glpi_groups_users.users_id'  => ['<>', Session::getLoginUserID()]
                  ]
               ]);
               while ($data = $iterator->next()) {
                  $users[$data["id"]] = $data["id"];
               }
            }
            // Add me to users list for central
            if (Session::getCurrentInterface() == 'central') {
               $users[Session::getLoginUserID()] = Session::getLoginUserID();
            }

            if (count($users)) {
               $WHERE = ['glpi_users.id' => $users];
            }

            break;

         case "all" :
            $WHERE = [
               'glpi_users.id' => ['>', 0]
            ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1);
            break;

         default :
            $joinprofile = true;
            $joinprofileright = true;
            if (!is_array($right)) {
               $right = [$right];
            }
            $forcecentral = true;

            $ORWHERE = [];
            foreach ($right as $r) {
               switch ($r) {
                  case  'own_ticket' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'ticket',
                           'glpi_profilerights.rights'   => ['&', Ticket::OWN]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     break;

                  case 'create_ticket_validate' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'  => 'ticketvalidation',
                           'OR'                       => [
                              'glpi_profilerights.rights'   => ['&', TicketValidation::CREATEREQUEST],
                              'glpi_profilerights.rights'   => ['&', TicketValidation::CREATEINCIDENT]
                           ]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     $forcecentral = false;
                     break;

                  case 'validate_request' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'ticketvalidation',
                           'glpi_profilerights.rights'   => ['&', TicketValidation::VALIDATEREQUEST]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     $forcecentral = false;
                     break;

                  case 'validate_incident' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'ticketvalidation',
                           'glpi_profilerights.rights'   => ['&', TicketValidation::VALIDATEINCIDENT]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     $forcecentral = false;
                     break;

                  case 'validate' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'changevalidation',
                           'glpi_profilerights.rights'   => ['&', ChangeValidation::VALIDATE]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     break;

                  case 'create_validate' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'changevalidation',
                           'glpi_profilerights.rights'   => ['&', ChangeValidation::CREATE]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     break;

                  case 'see_project' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'project',
                           'glpi_profilerights.rights'   => ['&', Project::READMY]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
                     break;

                  case 'faq' :
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => 'knowbase',
                           'glpi_profilerights.rights'   => ['&', KnowbaseItem::READFAQ]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];

                  default :
                     // Check read or active for rights
                     $ORWHERE[] = [
                        [
                           'glpi_profilerights.name'     => $r,
                           'glpi_profilerights.rights'   => [
                              '&',
                              READ | CREATE | UPDATE | DELETE | PURGE
                           ]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                     ];
               }
               if (in_array($r, Profile::$helpdesk_rights)) {
                  $forcecentral = false;
               }
            }

            if (count($ORWHERE)) {
               $WHERE[] = ['OR' => $ORWHERE];
            }

            if ($forcecentral) {
               $WHERE['glpi_profiles.interface'] = 'central';
            }
      }

      if (!$inactive_deleted) {
         $WHERE = array_merge(
            $WHERE, [
               'glpi_users.is_deleted' => 0,
               'glpi_users.is_active'  => 1,
               [
                  'OR' => [
                     ['glpi_users.begin_date' => null],
                     ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]]
                  ]
               ],
               [
                  'OR' => [
                     ['glpi_users.end_date' => null],
                     ['glpi_users.end_date' => ['>', new QueryExpression('NOW()')]]
                  ]
               ]

            ]
         );
      }

      if ((is_numeric($value) && $value)
          || count($used)) {

         $WHERE[] = [
            'NOT' => [
               'glpi_users.id' => $used
            ]
         ];
      }

      $criteria = [
         'FROM'            => 'glpi_users',
         'LEFT JOIN'       => [
            'glpi_useremails'       => [
               'ON' => [
                  'glpi_useremails' => 'users_id',
                  'glpi_users'      => 'id'
               ]
            ],
            'glpi_profiles_users'   => [
               'ON' => [
                  'glpi_profiles_users'   => 'users_id',
                  'glpi_users'            => 'id'
               ]
            ]
         ]
      ];
      if ($count) {
         $criteria['COUNT DISTINCT'] = 'glpi_users.*';
      } else {
         $criteria['SELECT DISTINCT'] = 'glpi_users.*';
      }

      if ($joinprofile) {
         $criteria['LEFT JOIN']['glpi_profiles'] = [
            'ON' => [
               'glpi_profiles_users'   => 'profiles_id',
               'glpi_profiles'         => 'id'
            ]
         ];
         if ($joinprofileright) {
            $criteria['LEFT JOIN']['glpi_profilerights'] = [
               'ON' => [
                  'glpi_profilerights' => 'profiles_id',
                  'glpi_profiles'      => 'id'
               ]
            ];
         }
      }

      if (!$count) {
         if ((strlen($search) > 0)) {
            $txt_search = Search::makeTextSearchValue($search);
            $concat = new \QueryExpression(
               "CONCAT(
                  glpi_users.realname,
                  glpi_users.firstname,
                  glpi_users.firstname
               ) LIKE '$txt_search'"
            );
            $WHERE[] = [
               'OR' => [
                  'glpi_users.name'       => ['LIKE', $txt_search],
                  'glpi_users.realname'   => ['LIKE', $txt_search],
                  'glpi_users.firstname'  => ['LIKE', $txt_search],
                  'glpi_users.phone'      => ['LIKE', $txt_search],
                  'glpi_useremails.email' => ['LIKE', $txt_search],
                  $concat
               ]
            ];
         }

         if ($_SESSION["glpinames_format"] == self::FIRSTNAME_BEFORE) {
            $criteria['ORDERBY'] = [
               'glpi_users.firstname',
               'glpi_users.realname',
               'glpi_users.name'
            ];
         } else {
            $criteria['ORDERBY'] = [
               'glpi_users.realname',
               'glpi_users.firstname',
               'glpi_users.name'
            ];
         }

         if ($limit > 0) {
            $criteria['LIMIT'] = $limit;
            $criteria['START'] = $start;
         }
      }
      $criteria['WHERE'] = $WHERE;
      return $DB->request($criteria);
   }


   /**
    * Make a select box with all glpi users where select key = name
    *
    * @param $options array of possible options:
    *    - name             : string / name of the select (default is users_id)
    *    - value
    *    - right            : string / limit user who have specific right :
    *                             id -> only current user (default case);
    *                             interface -> central;
    *                             all -> all users;
    *                             specific right like Ticket::READALL, CREATE.... (is array passed one of all passed right is needed)
    *    - comments         : boolean / is the comments displayed near the dropdown (default true)
    *    - entity           : integer or array / restrict to a defined entity or array of entities
    *                          (default -1 : no restriction)
    *    - entity_sons      : boolean / if entity restrict specified auto select its sons
    *                          only available if entity is a single value not an array(default false)
    *    - all              : Nobody or All display for none selected
    *                             all=0 (default) -> Nobody
    *                             all=1 -> All
    *                             all=-1-> nothing
    *    - rand             : integer / already computed rand value
    *    - toupdate         : array / Update a specific item on select change on dropdown
    *                          (need value_fieldname, to_update, url
    *                          (see Ajax::updateItemOnSelectEvent for information)
    *                          and may have moreparams)
    *    - used             : array / Already used items ID: not to display in dropdown (default empty)
    *    - ldap_import
    *    - on_change        : string / value to transmit to "onChange"
    *    - display          : boolean / display or get string (default true)
    *    - width            : specific width needed (default 80%)
    *    - specific_tags    : array of HTML5 tags to add to the field
    *    - url              : url of the ajax php code which should return the json data to show in
    *                         the dropdown (default /ajax/getDropdownUsers.php)
    *    - inactive_deleted : retreive also inactive or deleted users
    *
    * @return integer|string Random value if displayed, string otherwise
    */
   static function dropdown($options = []) {
      global $CFG_GLPI;

      // Default values
      $p = [
         'name'             => 'users_id',
         'value'            => '',
         'right'            => 'id',
         'all'              => 0,
         'on_change'        => '',
         'comments'         => 1,
         'width'            => '80%',
         'entity'           => -1,
         'entity_sons'      => false,
         'used'             => [],
         'ldap_import'      => false,
         'toupdate'         => '',
         'rand'             => mt_rand(),
         'display'          => true,
         '_user_index'      => 0,
         'specific_tags'    => [],
         'url'              => $CFG_GLPI['root_doc']."/ajax/getDropdownUsers.php",
         'inactive_deleted' => 0,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      // check default value (in case of multiple observers)
      if (is_array($p['value'])) {
         $p['value'] = $p['value'][$p['_user_index']];
      }

      // Check default value for dropdown : need to be a numeric
      if ((strlen($p['value']) == 0) || !is_numeric($p['value'])) {
         $p['value'] = 0;
      }

      $output = '';
      if (!($p['entity'] < 0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            $output .= "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
         }
      }

      // Make a select box with all glpi users
      $user = getUserName($p['value'], 2);

      $view_users = self::canView();

      if (!empty($p['value']) && ($p['value'] > 0)) {
          $default = $user["name"];
      } else {
         if ($p['all']) {
            $default = __('All');
         } else {
            $default = Dropdown::EMPTY_VALUE;
         }
      }
      $field_id = Html::cleanId("dropdown_".$p['name'].$p['rand']);
      $param    = ['value'               => $p['value'],
                        'valuename'           => $default,
                        'width'               => $p['width'],
                        'all'                 => $p['all'],
                        'right'               => $p['right'],
                        'on_change'           => $p['on_change'],
                        'used'                => $p['used'],
                        'inactive_deleted'    => $p['inactive_deleted'],
                        'entity_restrict'     => (is_array($p['entity']) ? json_encode(array_values($p['entity'])) : $p['entity']),
                        'specific_tags'       => $p['specific_tags']];

      $output   = Html::jsAjaxDropdown($p['name'], $field_id,
                                       $p['url'],
                                       $param);

      // Display comment
      if ($p['comments']) {
         $comment_id = Html::cleanId("comment_".$p['name'].$p['rand']);
         $link_id = Html::cleanId("comment_link_".$p["name"].$p['rand']);
         if (!$view_users) {
            $user["link"] = '';
         } else if (empty($user["link"])) {
            $user["link"] = $CFG_GLPI['root_doc']."/front/user.php";
         }

         if (empty($user['comment'])) {
            $user['comment'] = Toolbox::ucfirst(
               sprintf(
                  __('Show %1$s'),
                  self::getTypeName(Session::getPluralNumber())
               )
            );
         }
         $output .= "&nbsp;".Html::showToolTip($user["comment"],
                                      ['contentid' => $comment_id,
                                            'display'   => false,
                                            'link'      => $user["link"],
                                            'linkid'    => $link_id]);

         $paramscomment = ['value' => '__VALUE__',
                                'table' => "glpi_users"];

         if ($view_users) {
            $paramscomment['withlink'] = $link_id;
         }
         $output .= Ajax::updateItemOnSelectEvent($field_id, $comment_id,
                                                  $CFG_GLPI["root_doc"]."/ajax/comments.php",
                                                  $paramscomment, false);
      }
      $output .= Ajax::commonDropdownUpdateItem($p, false);

      if (Session::haveRight('user', self::IMPORTEXTAUTHUSERS)
          && $p['ldap_import']
          && Entity::isEntityDirectoryConfigured($_SESSION['glpiactive_entity'])) {

         $output .= "<span title=\"".__s('Import a user')."\" class='fa fa-plus pointer'".
                     " onClick=\"".Html::jsGetElementbyID('userimport'.$p['rand']).".dialog('open');\">
                     <span class='sr-only'>" . __s('Import a user') . "</span></span>";
         $output .= Ajax::createIframeModalWindow('userimport'.$p['rand'],
                                                  $CFG_GLPI["root_doc"].
                                                      "/front/ldap.import.php?entity=".
                                                      $_SESSION['glpiactive_entity'],
                                                  ['title'   => __('Import a user'),
                                                        'display' => false]);
      }

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }


   /**
    * Show simple add user form for external auth.
    *
    * @return void|boolean false if user does not have rights to import users from external sources,
    *    print form otherwise
    */
   static function showAddExtAuthForm() {

      if (!Session::haveRight("user", self::IMPORTEXTAUTHUSERS)) {
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
    * Change auth method for given users.
    *
    * @param integer[] $IDs      IDs of users
    * @param integer   $authtype Auth type (see Auth constants)
    * @param integer   $server   ID of auth server
    *
    * @return boolean
    */
   static function changeAuthMethod(array $IDs = [], $authtype = 1, $server = -1) {
      global $DB;

      if (!Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
         return false;
      }

      if (!empty($IDs)
          && in_array($authtype, [Auth::DB_GLPI, Auth::LDAP, Auth::MAIL, Auth::EXTERNAL])) {

         $result = $DB->update(
            self::getTable(), [
               'authtype'        => $authtype,
               'auths_id'        => $server,
               'password'        => '',
               'is_deleted_ldap' => 0
            ], [
               'id' => $IDs
            ]
         );
         if ($result) {
            foreach ($IDs as $ID) {
               $changes = [
                  0,
                  '',
                  addslashes(
                     sprintf(
                        __('%1$s: %2$s'),
                        __('Update authentification method to'),
                        Auth::getMethodName($authtype, $server)
                     )
                  )
               ];
               Log::history($ID, __CLASS__, $changes, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            }

            return true;
         }
      }
      return false;
   }


   /**
    * Generate vcard for the current user.
    *
    * @return void
    */
   function generateVcard() {

      // prepare properties for the Vcard
      if (!empty($this->fields["realname"])
          || !empty($this->fields["firstname"])) {
         $name = [$this->fields["realname"], $this->fields["firstname"], "", "", ""];
      } else {
         $name = [$this->fields["name"], "", "", "", ""];
      }

      // create vcard
      $vcard = new VObject\Component\VCard([
         'N'     => $name,
         'EMAIL' => $this->getDefaultEmail(),
         'NOTE'  => $this->fields["comment"],
      ]);
      $vcard->add('TEL', $this->fields["phone"], ['type' => 'PREF;WORK;VOICE']);
      $vcard->add('TEL', $this->fields["phone2"], ['type' => 'HOME;VOICE']);
      $vcard->add('TEL', $this->fields["mobile"], ['type' => 'WORK;CELL']);

      // send the  VCard
      $output   = $vcard->serialize();
      $filename = implode("_", array_filter($name)).".vcf";

      @Header("Content-Disposition: attachment; filename=\"$filename\"");
      @Header("Content-Length: ".Toolbox::strlen($output));
      @Header("Connection: close");
      @Header("content-type: text/x-vcard; charset=UTF-8");

      echo $output;
   }


   /**
    * Show items of the current user.
    *
    * @param boolean $tech false to display items owned by user, true to display items managed by user
    *
    * @return void
    */
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
      $groups      = [];

      $iterator = $DB->request([
         'SELECT'    => [
            'glpi_groups_users.groups_id',
            'glpi_groups.name'
         ],
         'FROM'      => 'glpi_groups_users',
         'LEFT JOIN' => [
            'glpi_groups' => [
               'FKEY' => [
                  'glpi_groups_users'  => 'groups_id',
                  'glpi_groups'        => 'id'
               ]
            ]
         ],
         'WHERE'     => ['glpi_groups_users.users_id' => $ID]
      ]);
      $number = count($iterator);

      $group_where = [];
      while ($data = $iterator->next()) {
         $group_where[$field_group][] = $data['groups_id'];
         $groups[$data["groups_id"]] = $data["name"];
      }

      echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
      $header = "<tr><th>".__('Type')."</th>";
      $header .= "<th>".__('Entity')."</th>";
      $header .= "<th>".__('Name')."</th>";
      $header .= "<th>".__('Serial number')."</th>";
      $header .= "<th>".__('Inventory number')."</th>";
      $header .= "<th>".__('Status')."</th>";
      $header .= "<th>&nbsp;</th></tr>";
      echo $header;

      foreach ($type_user as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $iterator_params = [
               'FROM'   => $itemtable,
               'WHERE'  => [$field_user => $ID]
            ];

            if ($item->maybeTemplate()) {
               $iterator_params['WHERE']['is_template'] = 0;
            }
            if ($item->maybeDeleted()) {
               $iterator_params['WHERE']['is_deleted'] = 0;
            }

            $item_iterator = $DB->request($iterator_params);

            $type_name = $item->getTypeName();

            while ($data = $item_iterator->next()) {
               $cansee = $item->can($data["id"], READ);
               $link   = $data["name"];
               if ($cansee) {
                  $link_item = $item::getFormURLWithID($data['id']);
                  if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                     $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
                  }
                  $link = "<a href='".$link_item."'>".$link."</a>";
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
      if ($number) {
         echo $header;
      }
      echo "</table></div>";

      if (count($group_where)) {
         echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
         $header = "<tr>".
               "<th>".__('Type')."</th>".
               "<th>".__('Entity')."</th>".
               "<th>".__('Name')."</th>".
               "<th>".__('Serial number')."</th>".
               "<th>".__('Inventory number')."</th>".
               "<th>".__('Status')."</th>".
               "<th>&nbsp;</th></tr>";
         echo $header;
         $nb = 0;
         foreach ($type_group as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
               continue;
            }
            if ($item->canView() && $item->isField($field_group)) {
               $itemtable = getTableForItemType($itemtype);
               $iterator_params = [
                  'FROM'   => $itemtable,
                  'WHERE'  => ['OR' => $group_where]
               ];

               if ($item->maybeTemplate()) {
                  $iterator_params['WHERE']['is_template'] = 0;
               }
               if ($item->maybeDeleted()) {
                  $iterator_params['WHERE']['is_deleted'] = 0;
               }

               $group_iterator = $DB->request($iterator_params);

               $type_name = $item->getTypeName();

               while ($data = $group_iterator->next()) {
                  $nb++;
                  $cansee = $item->can($data["id"], READ);
                  $link   = $data["name"];
                  if ($cansee) {
                     $link_item = $item::getFormURLWithID($data['id']);
                     if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
                        $link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
                     }
                     $link = "<a href='".$link_item."'>".$link."</a>";
                  }
                  $linktype = "";
                  if (isset($groups[$data[$field_group]])) {
                     $linktype = sprintf(__('%1$s = %2$s'), _n('Group', 'Groups', 1),
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
                     echo Dropdown::getDropdownName("glpi_states", $data['states_id']);
                  } else {
                     echo '&nbsp;';
                  }

                  echo "</td><td class='center'>$linktype</td></tr>";
               }
            }
         }
         if ($nb) {
            echo $header;
         }
         echo "</table></div>";
      }
   }


   /**
    * Get user by email, importing it from LDAP if not existing.
    *
    * @param string $email
    *
    * @return integer ID of user, 0 if not found nor imported
    */
   static function getOrImportByEmail($email = '') {
      global $DB, $CFG_GLPI;

      $iterator = $DB->request([
         'SELECT'    => 'users_id AS id',
         'FROM'      => 'glpi_useremails',
         'LEFT JOIN' => [
            'glpi_users' => [
               'FKEY' => [
                  'glpi_useremails' => 'users_id',
                  'glpi_users'      => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_useremails.email' => $DB->escape(stripslashes($email))
         ],
         'ORDER'     => ['glpi_users.is_active DESC', 'is_deleted ASC']
      ]);

      //User still exists in DB
      if (count($iterator)) {
         $result = $iterator->next();
         return $result['id'];
      } else {
         if ($CFG_GLPI["is_users_auto_add"]) {
            //Get all ldap servers with email field configured
            $ldaps = AuthLdap::getServersWithImportByEmailActive();
            //Try to find the user by his email on each ldap server

            foreach ($ldaps as $ldap) {
               $params = [
                  'method' => AuthLdap::IDENTIFIER_EMAIL,
                  'value'  => $email,
               ];
               $res = AuthLdap::ldapImportUserByServerId($params,
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
    * Handle user deleted in LDAP using configured policy.
    *
    * @param integer $users_id
    *
    * @return void
    */
   static function manageDeletedUserInLdap($users_id) {
      global $CFG_GLPI;

      //The only case where users_id can be null if when a user has been imported into GLPI
      //it's dn still exists, but doesn't match the connection filter anymore
      //In this case, do not try to process the user
      if (!$users_id) {
         return;
      }

      //User is present in DB but not in the directory : it's been deleted in LDAP
      $tmp = [
         'id'              => $users_id,
         'is_deleted_ldap' => 1,
      ];
      $myuser = new self();
      $myuser->getFromDB($users_id);

      //User is already considered as delete from ldap
      if ($myuser->fields['is_deleted_ldap'] == 1) {
         return;
      }

      switch ($CFG_GLPI['user_deleted_ldap']) {
         //DO nothing
         default :
         case AuthLDAP::DELETED_USER_PRESERVE:
            $myuser->update($tmp);
            break;

         //Put user in trashbin
         case AuthLDAP::DELETED_USER_DELETE:
            $myuser->delete($tmp);
            break;

         //Delete all user dynamic habilitations and groups
         case AuthLDAP::DELETED_USER_WITHDRAWDYNINFO:
            Profile_User::deleteRights($users_id, true);
            Group_User::deleteGroups($users_id, true);
            $myuser->update($tmp);
            break;

         //Deactivate the user
         case AuthLDAP::DELETED_USER_DISABLE:
            $tmp['is_active'] = 0;
            $myuser->update($tmp);
            break;

         //Deactivate the user+ Delete all user dynamic habilitations and groups
         case AuthLDAP::DELETED_USER_DISABLEANDWITHDRAWDYNINFO:
            $tmp['is_active'] = 0;
            $myuser->update($tmp);
            Profile_User::deleteRights($users_id, true);
            Group_User::deleteGroups($users_id, true);
            break;

      }
      /*
      $changes[0] = '0';
      $changes[1] = '';
      $changes[2] = __('Deleted user in LDAP directory');
      Log::history($users_id, 'User', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);*/
   }

   /**
    * Get user ID from its name.
    *
    * @param string $name User name
    *
    * @return integer
    */
   static function getIdByName($name) {
      return self::getIdByField('name', $name);
   }


   /**
    * Get user ID from a field
    *
    * @since 0.84
    *
    * @param string $field Field name
    * @param string $value Field value
    *
    * @return integer
    */
   static function getIdByField($field, $value) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => [$field => addslashes($value)]
      ]);

      if (count($iterator) == 1) {
         $row = $iterator->next();
         return (int)$row['id'];
      }
      return false;
   }


   /**
    * Show new password form of password recovery process.
    *
    * @param $token
    *
    * @return void
    */
   static function showPasswordForgetChangeForm($token) {
      global $CFG_GLPI, $DB;

      // Verif token.
      $token_ok = false;
      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'password_forget_token'       => $token,
            new \QueryExpression('NOW() < ADDDATE(' . $DB->quoteName('password_forget_token_date') . ', INTERVAL 1 DAY)')
         ]
      ]);

      if (count($iterator) == 1) {
         $token_ok = true;
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
         echo __('Your password reset request has expired or is invalid. Please renew it.');
      }
      echo "</div>";
   }


   /**
    * Show request form of password recovery process.
    *
    * @return void
    */
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
    * Handle password recovery form submission.
    *
    * @param array $input
    *
    * @throws ForgetPasswordException when requirements are not met
    *
    * @return boolean true if password successfully changed, false otherwise
    */
   public function updateForgottenPassword(array $input) {
      $condition = [
         'glpi_users.is_active'  => 1,
         'glpi_users.is_deleted' => 0, [
            'OR' => [
               ['glpi_users.begin_date' => null],
               ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]]
            ],
         ], [
            'OR'  => [
               ['glpi_users.end_date'   => null],
               ['glpi_users.end_date'   => ['>', new QueryExpression('NOW()')]]
            ]
         ]
      ];
      if ($this->getFromDBbyEmail($input['email'], $condition)) {
         if (($this->fields["authtype"] == Auth::DB_GLPI)
             || !Auth::useAuthExt()) {

            if (($input['password_forget_token'] == $this->fields['password_forget_token'])
                && (abs(strtotime($_SESSION["glpi_currenttime"])
                        -strtotime($this->fields['password_forget_token_date'])) < DAY_TIMESTAMP)) {

               $input['id'] = $this->fields['id'];
               Config::validatePassword($input["password"], false); // Throws exception if password is invalid
               if (!$this->update($input)) {
                  return false;
               }
               $input2 = [
                  'password_forget_token'      => '',
                  'password_forget_token_date' => null,
                  'id'                         => $this->fields['id']
               ];
               $this->update($input2);
               return true;

            } else {
               throw new ForgetPasswordException(__('Your password reset request has expired or is invalid. Please renew it.'));
            }

         } else {
            throw new ForgetPasswordException(__("The authentication method configuration doesn't allow you to change your password."));
         }

      } else {
         throw new ForgetPasswordException(__('Email address not found.'));
      }

      return false;
   }


   /**
    * Displays password recovery result.
    *
    * @param array $input
    *
    * @return void
    */
   public function showUpdateForgottenPassword(array $input) {
      global $CFG_GLPI;

      echo "<div class='center'>";
      try {
         if (!$this->updateForgottenPassword($input)) {
            Html::displayMessageAfterRedirect();
         } else {
            echo __('Reset password successful.');
         }
      } catch (ForgetPasswordException $e) {
         echo $e->getMessage();
      } catch (PasswordTooWeakException $e) {
         // Force display on error
         foreach ($e->getMessages() as $message) {
            Session::addMessageAfterRedirect($message);
         }
         Html::displayMessageAfterRedirect();
      }

      echo "<br>";
      echo "<a href=\"".$CFG_GLPI['root_doc']."/index.php\">".__s('Back')."</a>";
      echo "</div>";
   }


   /**
    * Send password recovery for a user and display result message.
    *
    * @param string $email email of the user
    *
    * @return void
    */
   public function showForgetPassword($email) {

      echo "<div class='center'>";
      try {
         $this->forgetPassword($email);
      } catch (ForgetPasswordException $e) {
         echo $e->getMessage();
         return;
      }
      echo __('An email has been sent to your email address. The email contains information for reset your password.');
   }

   /**
    * Send password recovery email for a user.
    *
    * @param string $email
    *
    * @throws ForgetPasswordException when requirements are not met
    *
    * @return boolean true if notification successfully created, false if user not found
    */
   public function forgetPassword($email) {
      $condition = [
         'glpi_users.is_active'  => 1,
         'glpi_users.is_deleted' => 0, [
            'OR' => [
               ['glpi_users.begin_date' => null],
               ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]]
            ],
         ], [
            'OR'  => [
               ['glpi_users.end_date'   => null],
               ['glpi_users.end_date'   => ['>', new QueryExpression('NOW()')]]
            ]
         ]
      ];

      if ($this->getFromDBbyEmail($email, $condition)) {

         // Send token if auth DB or not external auth defined
         if (($this->fields["authtype"] == Auth::DB_GLPI)
             || !Auth::useAuthExt()) {

            if (NotificationMailing::isUserAddressValid($email)) {
               $input = [
                  'password_forget_token'      => sha1(Toolbox::getRandomString(30)),
                  'password_forget_token_date' => $_SESSION["glpi_currenttime"],
                  'id'                         => $this->fields['id'],
               ];
               $this->update($input);
               // Notication on root entity (glpi_users.entities_id is only a pref)
               NotificationEvent::raiseEvent('passwordforget', $this, ['entities_id' => 0]);
               QueuedNotification::forceSendFor($this->getType(), $this->fields['id']);
               return true;
            } else {
               throw new ForgetPasswordException(__('Invalid email address'));
            }

         } else {
            throw new ForgetPasswordException(__("The authentication method configuration doesn't allow you to change your password."));
         }

      }

      throw new ForgetPasswordException(__('Email address not found.'));
   }


   /**
    * Display information from LDAP server for user.
    *
    * @return void
    */
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
                                          ['*', 'createTimeStamp', 'modifyTimestamp']);
            if (is_array($info)) {
               Html::printCleanArray($info);
            } else {
               echo __('No item to display');
            }

         } else {
            echo __('Connection failed');
         }

         echo "</td></tr>\n";
      }

      echo "</table></div>";
   }


   /**
    * Display debug information for current object.
    *
    * @return void
    */
   function showDebug() {

      NotificationEvent::debugEvent($this);
      $this->showLdapDebug();
   }

   function getUnicityFieldsToDisplayInErrorMessage() {

      return ['id'          => __('ID'),
                   'entities_id' => __('Entity')];
   }


   function getUnallowedFieldsForUnicity() {

      return array_merge(parent::getUnallowedFieldsForUnicity(),
                         ['auths_id', 'date_sync', 'entities_id', 'last_login', 'profiles_id']);
   }


   /**
    * Get a unique generated token.
    *
    * @param string $field Field storing the token
    *
    * @return string
    */
   static function getUniqueToken($field = 'personal_token') {
      global $DB;

      $ok = false;
      do {
         $key    = Toolbox::getRandomString(40);
         $row = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => self::getTable(),
            'WHERE'  => [$field => $key]
         ])->next();

         if ($row['cpt'] == 0) {
            return $key;
         }
      } while (!$ok);

   }


   /**
    * Get token of a user. If not exists generate it.
    *
    * @param integer $ID    User ID
    * @param string  $field Field storing the token
    *
    * @return string|boolean User token, false if user does not exist
    */
   static function getToken($ID, $field = 'personal_token') {

      $user = new self();
      if ($user->getFromDB($ID)) {
         return $user->getAuthToken($field);
      }

      return false;
   }

   /**
    * Get token of a user. If it does not exists  then generate it.
    *
    * @since 9.4
    *
    * @param string $field the field storing the token
    *
    * @return string|false token or false in case of error
    */
   public function getAuthToken($field = 'personal_token') {
      global $DB;

      if ($this->isNewItem()) {
         return false;
      }

      if (!empty($this->fields[$field])) {
         return $this->fields[$field];
      }
      $token = self::getUniqueToken($field);
      $this->update(['id'             => $this->getID(),
                     $field           => $token,
                     $field . "_date" => $_SESSION['glpi_currenttime']]);
      return $this->fields[$field];
   }


   /**
    * Get name of users using default passwords
    *
    * @return string[]
    */
   static function checkDefaultPasswords() {
      global $DB;

      $passwords = ['glpi'      => 'glpi',
                         'tech'      => 'tech',
                         'normal'    => 'normal',
                         'post-only' => 'postonly'];
      $default_password_set = [];

      $crit = ['FIELDS'     => ['name', 'password'],
                    'is_active'  => 1,
                    'is_deleted' => 0,
                    'name'       => array_keys($passwords)];

      foreach ($DB->request('glpi_users', $crit) as $data) {
         if (Auth::checkPassword($passwords[$data['name']], $data['password'])) {
            $default_password_set[] = $data['name'];
         }
      }

      return $default_password_set;
   }


   /**
    * Get picture URL from picture field.
    *
    * @since 0.85
    *
    * @param string $picture Picture field value
    *
    * @return string
    */
   static function getURLForPicture($picture) {
      global $CFG_GLPI;

      if (!empty($picture)) {
         return $CFG_GLPI["root_doc"]."/front/document.send.php?file=_pictures/$picture";
      }
      return $CFG_GLPI["root_doc"]."/pics/picture.png";
   }


   /**
    * Get thumbnail URL from picture field.
    *
    * @since 0.85
    *
    * @param string $picture Picture field value
    *
    * @return string
    */
   static function getThumbnailURLForPicture($picture) {
      global $CFG_GLPI;

      if (!empty($picture)) {
         $tmp = explode(".", $picture);
         if (count($tmp) ==2) {
            return $CFG_GLPI["root_doc"]."/front/document.send.php?file=_pictures/".$tmp[0].
                   "_min.".$tmp[1];
         }
         return $CFG_GLPI["root_doc"]."/pics/picture_min.png";
      }
      return $CFG_GLPI["root_doc"]."/pics/picture_min.png";

   }


   /**
    * Drop existing files for user picture.
    *
    * @since 0.85
    *
    * @param string $picture Picture field value
    *
    * @return void
    */
   static function dropPictureFiles($picture) {

      if (!empty($picture)) {
         // unlink main file
         if (file_exists(GLPI_PICTURE_DIR."/$picture")) {
            @unlink(GLPI_DOC_DIR."/_pictures/$picture");
         }
         // unlink Thunmnail
         $tmp = explode(".", $picture);
         if (count($tmp) == 2) {
            if (file_exists(GLPI_PICTURE_DIR."/".$tmp[0]."_min.".$tmp[1])) {
               @unlink(GLPI_PICTURE_DIR."/".$tmp[0]."_min.".$tmp[1]);
            }
         }
      }
   }

   function getRights($interface = 'central') {

      $values = parent::getRights();
      //TRANS: short for : Add users from an external source
      $values[self::IMPORTEXTAUTHUSERS] = ['short' => __('Add external'),
                                                'long'  => __('Add users from an external source')];
       //TRANS: short for : Read method for user authentication and synchronization
      $values[self::READAUTHENT]        = ['short' => __('Read auth'),
                                                'long'  => __('Read user authentication and synchronization method')];
      //TRANS: short for : Update method for user authentication and synchronization
      $values[self::UPDATEAUTHENT]      = ['short' => __('Update auth and sync'),
                                                'long'  => __('Update method for user authentication and synchronization')];

      return $values;
   }


   /**
    * Retrieve the list of LDAP field names from a list of fields
    * allow pattern substitution, e.g. %{name}.
    *
    * @since 9.1
    *
    * @param string[] $map array of fields
    *
    * @return string[]
    */
   private static function getLdapFieldNames(array $map) {

      $ret =  [];
      foreach ($map as $v) {
         /** @var array $reg */
         if (preg_match_all('/%{(.*)}/U', $v, $reg)) {
            // e.g. "%{country} > %{city} > %{site}"
            foreach ($reg [1] as $f) {
               $ret [] = $f;
            }
         } else {
            // single field name
            $ret [] = $v;
         }
      }
      return $ret;
   }


   /**
    * Retrieve the value of a fields from a LDAP result applying needed substitution of %{value}.
    *
    * @since 9.1
    *
    * @param string $map String with field format
    * @param array  $res LDAP result
    *
    * @return string
    */
   private static function getLdapFieldValue($map, array $res) {

      $map = Toolbox::unclean_cross_side_scripting_deep($map);
      $ret = preg_replace_callback('/%{(.*)}/U',
                                    function ($matches) use ($res) {
                                       return (isset($res[0][$matches[1]][0]) ? $res[0][$matches[1]][0] : '');
                                    }, $map );

      return $ret == $map ? (isset($res[0][$map][0]) ? $res[0][$map][0] : '') : $ret;
   }

   /**
    * Get/Print the switch language form.
    *
    * @param boolean $display Whether to display or return output
    * @param array   $options Options
    *    - string   value       Selected language value
    *    - boolean  showbutton  Whether to display or not submit button
    *
    * @return void|string Nothing if displayed, string to display otherwise
    */
   function showSwitchLangForm($display = true, array $options = []) {

      $params = [
         'value'        => $_SESSION["glpilanguage"],
         'display'      => false,
         'showbutton'   => true
      ];

      foreach ($options as $key => $value) {
         $params[$key] = $value;
      }

      $out = '';
      $out .= "<form method='post' name='switchlang' action='".User::getFormURL()."' autocomplete='off'>";
      $out .= "<p class='center'>";
      $out .= Dropdown::showLanguages("language", $params);
      if ($params['showbutton'] === true) {
         $out .= "&nbsp;<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
      }
      $out .= "</p>";
      $out .= Html::closeForm(false);

      if ($display === true) {
         echo $out;
      } else {
         return $out;
      }
   }

   /**
    * Get list of entities ids for current user.
    *
    * @return integer[]
    */
   private function getEntities() {
      //get user entities
      if ($this->entities == null) {
         $this->entities = Profile_User::getUserEntities($this->fields['id'], true);
      }
      return $this->entities;
   }
}
