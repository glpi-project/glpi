<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/**
 * Session Class
**/
class Session {

   // GLPI MODE
   const NORMAL_MODE       = 0;
   const TRANSLATION_MODE  = 1; // no more used
   const DEBUG_MODE        = 2;


   /**
    * Destroy the current session
    *
    * @return nothing
   **/
   static function destroy() {

      self::start();
      // Unset all of the session variables.
      session_unset();
      // destroy may cause problems (no login / back to login page)
      $_SESSION = array();
      // write_close may cause troubles (no login / back to login page)
   }



   /**
    * Init session for the user is defined
    *
    * @param $auth Auth object to init session
    *
    * @return nothing
   **/
   static function init(Auth $auth) {
      global $CFG_GLPI;

      if ($auth->auth_succeded) {
         // Restart GLPi session : complete destroy to prevent lost datas
         $tosave = array('glpi_plugins', 'glpicookietest', 'phpCAS', 'glpicsrftokens',
                         'glpiskipMaintenance');
         $save   = array();
         foreach ($tosave as $t) {
            if (isset($_SESSION[$t])) {
               $save[$t] = $_SESSION[$t];
            }
         }
         self::destroy();
         session_regenerate_id();
         self::start();
         $_SESSION = $save;
         $_SESSION['valid_id'] = session_id();
         // Define default time :
         $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");

         // Normal mode for this request
         $_SESSION["glpi_use_mode"] = self::NORMAL_MODE;
         // Check ID exists and load complete user from DB (plugins...)
         if (isset($auth->user->fields['id'])
             && $auth->user->getFromDB($auth->user->fields['id'])) {

            if (!$auth->user->fields['is_deleted']
                && ($auth->user->fields['is_active']
                    && (($auth->user->fields['begin_date'] < $_SESSION["glpi_currenttime"])
                        || is_null($auth->user->fields['begin_date']))
                    && (($auth->user->fields['end_date'] > $_SESSION["glpi_currenttime"])
                        || is_null($auth->user->fields['end_date'])))) {
               $_SESSION["glpiID"]              = $auth->user->fields['id'];
               $_SESSION["glpiname"]            = $auth->user->fields['name'];
               $_SESSION["glpirealname"]        = $auth->user->fields['realname'];
               $_SESSION["glpifirstname"]       = $auth->user->fields['firstname'];
               $_SESSION["glpidefault_entity"]  = $auth->user->fields['entities_id'];
               $_SESSION["glpiusers_idisation"] = true;
               $_SESSION["glpiextauth"]         = $auth->extauth;
               $_SESSION["glpiauthtype"]        = $auth->user->fields['authtype'];
               $_SESSION["glpiroot"]            = $CFG_GLPI["root_doc"];
               $_SESSION["glpi_use_mode"]       = $auth->user->fields['use_mode'];
               $_SESSION["glpi_plannings"]      = importArrayFromDB($auth->user->fields['plannings']);
               $_SESSION["glpicrontimer"]       = time();
               // Default tab
//               $_SESSION['glpi_tab']=1;
               $_SESSION['glpi_tabs']           = array();
               $auth->user->computePreferences();
               foreach ($CFG_GLPI['user_pref_field'] as $field) {
                  if (isset($auth->user->fields[$field])) {
                     $_SESSION["glpi$field"] = $auth->user->fields[$field];
                  }
               }
               // Do it here : do not reset on each page, cause export issue
               if ($_SESSION["glpilist_limit"] > $CFG_GLPI['list_limit_max']) {
                  $_SESSION["glpilist_limit"] = $CFG_GLPI['list_limit_max'];
               }
               // Init not set value for language
               if (empty($_SESSION["glpilanguage"])) {
                  $_SESSION["glpilanguage"] = $CFG_GLPI['language'];
               }
               $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

               self::loadLanguage();

               // glpiprofiles -> other available profile with link to the associated entities
               Plugin::doHook("init_session");

               self::initEntityProfiles(self::getLoginUserID());

               // Use default profile if exist
               if (isset($_SESSION['glpiprofiles'][$auth->user->fields['profiles_id']])) {
                  self::changeProfile($auth->user->fields['profiles_id']);

               } else { // Else use first
                  self::changeProfile(key($_SESSION['glpiprofiles']));
               }

               if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
                  $auth->auth_succeded = false;
                  $auth->addToError(__("You don't have right to connect"));
               }

            } else {
               $auth->auth_succeded = false;
               $auth->addToError(__("You don't have access to this application because your account was deactivated or removed"));
            }

         } else {
            $auth->auth_succeded = false;
            $auth->addToError(__("You don't have right to connect"));
         }
      }
   }


   /**
    * Set the directory where are store the session file
   **/
   static function setPath() {

      if (ini_get("session.save_handler") == "files") {
         session_save_path(GLPI_SESSION_DIR);
      }
   }


   /**
    * Start the GLPI php session
   **/
   static function start() {

      if (!session_id()) {
         session_name("glpi_".md5(realpath(GLPI_ROOT)));
         @session_start();
      }
      // Define current time for sync of action timing
      $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
   }


   /**
    * @since version 0.84
   **/
   function getRootEntityName() {

      if (isset($_SESSION['glpirootentityname'])) {
         return $_SESSION['glpirootentityname'];
      }

      $entity = new Entity();
      if ($entity->getFromDB(0)) {
         $_SESSION['glpirootentityname'] = $entity->fields['name'];
      } else {
         $_SESSION['glpirootentityname'] = 'No root entity / DB troubles';
      }
      return $_SESSION['glpirootentityname'];
   }


   /**
    * Is GLPI used in multi-entities mode ?
    *
    * @return boolean
   **/
   static function isMultiEntitiesMode() {

      if (!isset($_SESSION['glpi_multientitiesmode'])) {
         if (countElementsInTable("glpi_entities") > 1) {
            $_SESSION['glpi_multientitiesmode'] = 1;
         } else {
            $_SESSION['glpi_multientitiesmode'] = 0;
         }
      }

      return $_SESSION['glpi_multientitiesmode'];
   }


   /**
    * Is the user have right to see all entities ?
    *
    * @return boolean
   **/
   Static function isViewAllEntities() {

      // Command line can see all entities
      return (isCommandLine()
              || ((countElementsInTable("glpi_entities")) == count($_SESSION["glpiactiveentities"])));
   }


   /** Add an item to the navigate through search results list
    *
    * @param $itemtype  device type
    * @param $ID        ID of the item
   **/
   static function addToNavigateListItems($itemtype, $ID) {
      $_SESSION['glpilistitems'][$itemtype][] = $ID;
   }


    /** Initialise a list of items to use navigate through search results
     *
     * @param $itemtype    device type
     * @param $title       list title (default '')
    **/
    static function initNavigateListItems($itemtype, $title="") {

       if (empty($title)) {
          $title = __('List');
       }
       $url = '';

       if (!isset($_SERVER['REQUEST_URI']) || (strpos($_SERVER['REQUEST_URI'],"tabs") > 0)) {
          if (isset($_SERVER['HTTP_REFERER'])) {
             $url = $_SERVER['HTTP_REFERER'];
          }

       } else {
          $url = $_SERVER['REQUEST_URI'];
       }

       $_SESSION['glpilisttitle'][$itemtype] = $title;
       $_SESSION['glpilistitems'][$itemtype] = array();
       $_SESSION['glpilisturl'][$itemtype]   = $url;
    }


   /**
    * Change active entity to the $ID one. Update glpiactiveentities session variable.
    * Reload groups related to this entity.
    *
    * @param $ID              ID of the new active entity ("all"=>load all possible entities)
    *                         (default 'all')
    * @param $is_recursive    also display sub entities of the active entity ? (false by default)
    *
    * @return Nothing
   **/
   static function changeActiveEntities($ID="all", $is_recursive=false) {

      $newentities = array();
      $newroots    = array();
      if (isset($_SESSION['glpiactiveprofile'])) {
         if ($ID == "all") {
            $ancestors = array();
            foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
               $ancestors               = array_unique(array_merge(getAncestorsOf("glpi_entities",
                                                                                  $val['id']),
                                                                   $ancestors));
               $newroots[$val['id']]    = $val['is_recursive'];
               $newentities[$val['id']] = $val['id'];

               if ($val['is_recursive']) {
                  $entities = getSonsOf("glpi_entities", $val['id']);
                  if (count($entities)) {
                     foreach ($entities as $key2 => $val2) {
                        $newentities[$key2] = $key2;
                     }
                  }
               }
            }

         } else {
            /// Check entity validity
            $ancestors = getAncestorsOf("glpi_entities", $ID);
            $ok        = false;
            foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
               if (($val['id'] == $ID) || in_array($val['id'], $ancestors)) {
                  // Not recursive or recursive and root entity is recursive
                  if (!$is_recursive || $val['is_recursive']) {
                     $ok = true;
                  }
               }
            }
            if (!$ok) {
               return false;
            }

            $newroots[$ID]    = $is_recursive;
            $newentities[$ID] = $ID;
            if ($is_recursive) {
               $entities = getSonsOf("glpi_entities", $ID);
               if (count($entities)) {
                  foreach ($entities as $key2 => $val2) {
                     $newentities[$key2] = $key2;
                  }
               }
            }
         }
      }

      if (count($newentities) > 0) {
         $_SESSION['glpiactiveentities']           = $newentities;
         $_SESSION['glpiactiveentities_string']    = "'".implode("', '", $newentities)."'";
         $active                                   = reset($newentities);
         $_SESSION['glpiparententities']           = $ancestors;
         $_SESSION['glpiparententities_string']    = implode("', '" ,$ancestors);
         if (!empty($_SESSION['glpiparententities_string'])) {
            $_SESSION['glpiparententities_string'] = "'".$_SESSION['glpiparententities_string']."'";
         }
         // Active entity loading
         $_SESSION["glpiactive_entity"]           = $active;
         $_SESSION["glpiactive_entity_recursive"] = $is_recursive;
         $_SESSION["glpiactive_entity_name"]      = Dropdown::getDropdownName("glpi_entities",
                                                                              $active);
         $_SESSION["glpiactive_entity_shortname"] = getTreeLeafValueName("glpi_entities", $active);
         if ($is_recursive || $ID=="all") {
            //TRANS: %s is the entity name
            $_SESSION["glpiactive_entity_name"]      = sprintf(__('%1$s (%2$s)'),
                                                               $_SESSION["glpiactive_entity_name"],
                                                               __('tree structure'));
            $_SESSION["glpiactive_entity_shortname"] = sprintf(__('%1$s (%2$s)'),
                                                               $_SESSION["glpiactive_entity_shortname"],
                                                               __('tree structure'));
         }

         if (countElementsInTable('glpi_entities') <= count($_SESSION['glpiactiveentities'])) {
            $_SESSION['glpishowallentities'] = 1;
         } else {
            $_SESSION['glpishowallentities'] = 0;
         }
         // Clean session variable to search system
         if (isset($_SESSION['glpisearch']) && count($_SESSION['glpisearch'])) {
            foreach ($_SESSION['glpisearch'] as $itemtype => $tab) {
               if (isset($tab['start']) && ($tab['start'] > 0)) {
                  $_SESSION['glpisearch'][$itemtype]['start'] = 0;
               }
            }
         }
         self::loadGroups();
         Plugin::doHook("change_entity");
         return true;
      }
      return false;
   }


   /**
    * Change active profile to the $ID one. Update glpiactiveprofile session variable.
    *
    * @param $ID : ID of the new profile
    *
    * @return Nothing
   **/
   static function changeProfile($ID) {

      if (isset($_SESSION['glpiprofiles'][$ID])
          && count($_SESSION['glpiprofiles'][$ID]['entities'])) {

         $profile = new Profile();
         if ($profile->getFromDB($ID)) {
            $profile->cleanProfile();
            $data             = $profile->fields;
            $data['entities'] = $_SESSION['glpiprofiles'][$ID]['entities'];

            $_SESSION['glpiactiveprofile']  = $data;
            $_SESSION['glpiactiveentities'] = array();

            Search::resetSaveSearch();
            $active_entity_done = false;

            // Try to load default entity if it is a root entity
            foreach ($data['entities'] as $key => $val) {
               if ($val['id'] == $_SESSION["glpidefault_entity"]) {
                  if (self::changeActiveEntities($val['id'],$val['is_recursive'])) {
                     $active_entity_done = true;
                  }
               }
            }
            if (!$active_entity_done) {
               // Try to load default entity
               if (!self::changeActiveEntities($_SESSION["glpidefault_entity"], true)) {
                  // Load all entities
                  self::changeActiveEntities("all");
               }
            }
            Plugin::doHook("change_profile");
         }
      }
      // Clean specific datas
      if (isset($_SESSION['glpimenu'])) {
         unset($_SESSION['glpimenu']);
      }
      if (isset($_SESSION['glpi_faqcategories'])) {
         unset($_SESSION['glpi_faqcategories']);
      }
   }


   /**
    * Set the entities session variable. Load all entities from DB
    *
    * @param $userID : ID of the user
    *
    * @return Nothing
   **/
   static function initEntityProfiles($userID) {
      global $DB;

      $query = "SELECT DISTINCT `glpi_profiles`.`id`, `glpi_profiles`.`name`
                FROM `glpi_profiles_users`
                INNER JOIN `glpi_profiles`
                     ON (`glpi_profiles_users`.`profiles_id` = `glpi_profiles`.`id`)
                WHERE `glpi_profiles_users`.`users_id` = ' $userID'
                ORDER BY `glpi_profiles`.`name`";
      $result = $DB->query($query);

      $_SESSION['glpiprofiles'] = array();
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $_SESSION['glpiprofiles'][$data['id']]['name'] = $data['name'];
         }
         foreach ($_SESSION['glpiprofiles'] as $key => $tab) {
            $query2 = "SELECT `glpi_profiles_users`.`entities_id` AS eID,
                              `glpi_profiles_users`.`id` AS kID,
                              `glpi_profiles_users`.`is_recursive`,
                              `glpi_entities`.*
                       FROM `glpi_profiles_users`
                       LEFT JOIN `glpi_entities`
                                ON (`glpi_profiles_users`.`entities_id` = `glpi_entities`.`id`)
                       WHERE `glpi_profiles_users`.`profiles_id` = '$key'
                             AND `glpi_profiles_users`.`users_id` = '$userID'
                       ORDER BY `glpi_entities`.`completename`";
            $result2 = $DB->query($query2);

            if ($DB->numrows($result2)) {
               while ($data = $DB->fetch_assoc($result2)) {
                  // Do not override existing entity if define as recursive
                  if (!isset($_SESSION['glpiprofiles'][$key]['entities'][$data['eID']])
                      || $data['is_recursive']) {
                     $_SESSION['glpiprofiles'][$key]['entities'][$data['eID']]['id']
                                                                           = $data['eID'];
                     $_SESSION['glpiprofiles'][$key]['entities'][$data['eID']]['name']
                                                                           = $data['name'];
                     $_SESSION['glpiprofiles'][$key]['entities'][$data['eID']]['is_recursive']
                                                                           = $data['is_recursive'];
                  }
               }
            }
         }
      }
   }


   /**
    * Load groups where I am in the active entity.
    *
    * @return Nothing
   **/
   static function loadGroups() {
      global $DB;

      $_SESSION["glpigroups"] = array();

      $query_gp = "SELECT `glpi_groups_users`.`groups_id`
                   FROM `glpi_groups_users`
                   LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                   WHERE `glpi_groups_users`.`users_id`='" . self::getLoginUserID() . "' " .
                         getEntitiesRestrictRequest(" AND ","glpi_groups","entities_id",
                                                    $_SESSION['glpiactiveentities'],true);
      $result_gp = $DB->query($query_gp);
      if ($DB->numrows($result_gp)) {
         while ($data = $DB->fetch_assoc($result_gp)) {
            $_SESSION["glpigroups"][] = $data["groups_id"];
         }
      }
   }


   /**
    * Include the good language dict.
    *
    * Get the default language from current user in $_SESSION["glpilanguage"].
    * And load the dict that correspond.
    *
    * @param $forcelang Force to load a specific lang (default '')
    *
    * @return nothing (make an include)
   **/
   static function loadLanguage($forcelang='') {
      global $LANG, $CFG_GLPI, $TRANSLATE;

      $file = "";

      if (!isset($_SESSION["glpilanguage"])) {
         if (isset($CFG_GLPI["language"])) {
            // Default config in GLPI >= 0.72
            $_SESSION["glpilanguage"] = $CFG_GLPI["language"];

         } else if (isset($CFG_GLPI["default_language"])) {
            // Default config in GLPI < 0.72 : keep it for upgrade process
            $_SESSION["glpilanguage"] = $CFG_GLPI["default_language"];
         } else {
            $_SESSION["glpilanguage"] = "en_GB";
         }
      }

      $trytoload = $_SESSION["glpilanguage"];
      // Force to load a specific lang
      if (!empty($forcelang)) {
         $trytoload = $forcelang;
      }

      // If not set try default lang file
      if (empty($trytoload)) {
         $trytoload = $CFG_GLPI["language"];
      }

      if (isset($CFG_GLPI["languages"][$trytoload])) {
         $newfile = "/locales/" . $CFG_GLPI["languages"][$trytoload][1];
      }

      if (empty($newfile) || !is_file(GLPI_ROOT . $newfile)) {
         $newfile = "/locales/en_GB.mo";
      }
      
      if (isset($CFG_GLPI["languages"][$trytoload][5])) {
        $_SESSION['glpipluralnumber'] = $CFG_GLPI["languages"][$trytoload][5];
      }
      $TRANSLATE = new Zend\I18n\Translator\Translator;
      try {
         $cache = Zend\Cache\StorageFactory::factory(array('adapter' => 'apc'));
         $TRANSLATE->setCache($cache);
      } catch (Exception $e) {
         // ignore when APC not available
         // toolbox::logDebug($e->getMessage());
      }
      $TRANSLATE->addTranslationFile('gettext', GLPI_ROOT.$newfile, 'glpi', $trytoload);


      // Load plugin dicts
      if (isset($_SESSION['glpi_plugins']) && is_array($_SESSION['glpi_plugins'])) {
         if (count($_SESSION['glpi_plugins'])) {
            foreach ($_SESSION['glpi_plugins'] as $plug) {
               Plugin::loadLang($plug, $forcelang, $trytoload);
            }
         }
      }

      // TRANSLATION_MODE deleted : maybe find another solution ?
      // Debug display lang element with item
//       if ($_SESSION['glpi_use_mode'] == Session::TRANSLATION_MODE && $CFG_GLPI["debug_lang"]) {
//          foreach ($LANG as $module => $tab) {
//             foreach ($tab as $num => $val) {
//                $LANG[$module][$num] = "".$LANG[$module][$num].
//                                       "/<span style='font-size:12px; color:red;'>$module/$num</span>";
//             }
//          }
//       }

      $TRANSLATE->setLocale($trytoload);

      return $trytoload;
   }

   static function getPluralNumber() {
      global $DEFAULT_PLURAL_NUMBER;

      if (isset($_SESSION['glpipluralnumber'])) {
         return $_SESSION['glpipluralnumber'];
      } else {
         return $DEFAULT_PLURAL_NUMBER;
      }
   }

   /**
    * Detect cron mode or interactive
    *
    * @since version 0.84
    *
    * @return Boolean
   **/
   static function isCron() {

      return (isset($_SESSION["glpicronuserrunning"])
              && (isCommandLine()
                  || strpos($_SERVER['PHP_SELF'], 'cron.php')
                  || strpos($_SERVER['PHP_SELF'], 'crontask.form.php')));
   }


   /**
    * Get the Login User ID or return cron user ID for cron jobs
    *
    * @param $force_human boolean   force human / do not return cron user (false by default)
    *
    * @return false if user is not logged in
    * @return int or string : int for user id, string for cron jobs
   **/
   static function getLoginUserID($force_human=true) {

      if (!$force_human
          && self::isCron()) { // Check cron jobs
         return $_SESSION["glpicronuserrunning"];
      }

      if (isset($_SESSION["glpiID"])) {
         return $_SESSION["glpiID"];
      }
      return false;
   }


   /**
    * Redirect User to login if not logged in
    *
    * @since version 0.85
    *
    * @return nothing, just redirect if not logged in
   **/
   static function redirectIfNotLoggedIn() {

      if (!self::getLoginUserID()) {
         Html::redirectToLogin();
      }
   }


   /**
    * Global check of session to prevent PHP vulnerability
    *
    * @since version 0.85
    *
    * @see https://wiki.php.net/rfc/strict_sessions
   **/
   static function checkValidSessionId() {

      if (!isset($_SESSION['valid_id'])
          || ($_SESSION['valid_id'] !== session_id())) {
         Html::redirectToLogin('error=3');
      }
      return true;
   }


   /**
    * Check if I have access to the central interface
    *
    * @return Nothing : display error if not permit
   **/
   static function checkCentralAccess() {
      global $CFG_GLPI;

      self::checkValidSessionId();
      if (!isset($_SESSION["glpiactiveprofile"])
          || ($_SESSION["glpiactiveprofile"]["interface"] != "central")) {
         // Gestion timeout session
         self::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }


   /**
    * Check if I have the right to access to the FAQ (profile or anonymous FAQ)
    *
    * @return Nothing : display error if not permit
   **/
   static function checkFaqAccess() {
      global $CFG_GLPI;

      if (!$CFG_GLPI["use_public_faq"]) {
         self::checkValidSessionId();
         if (!self::haveRight('knowbase', KnowbaseItem::READFAQ)) {
            Html::displayRightError();
         }
      }
   }


   /**
    * Check if I have access to the helpdesk interface
    *
    * @return Nothing : display error if not permit
   **/
   static function checkHelpdeskAccess() {
      global $CFG_GLPI;

      self::checkValidSessionId();
      if (!isset($_SESSION["glpiactiveprofile"])
          || ($_SESSION["glpiactiveprofile"]["interface"] != "helpdesk")) {
         // Gestion timeout session
         self::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }

   /**
    * Check if I am logged in
    *
    * @return Nothing : display error if not permit
   **/
   static function checkLoginUser() {
      global $CFG_GLPI;

      self::checkValidSessionId();
      if (!isset($_SESSION["glpiname"])) {
         // Gestion timeout session
         self::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }


   /**
    * Check if I have the right $right to module $module (conpare to session variable)
    *
    * @param $module Module to check
    * @param $right  Right to check
    *
    * @return Nothing : display error if not permit
   **/
   static function checkRight($module, $right) {
      global $CFG_GLPI;

      self::checkValidSessionId();
      if (!self::haveRight($module, $right)) {
         // Gestion timeout session
         self::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }

   /**
    * Check if I one right of array $rights to module $module (conpare to session variable)
    *
    * @param $module           Module to check
    * @param $rights   array   Rights to check
    *
    * @return Nothing : display error if not permit
    **/
   static function checkRightsOr($module, $rights=array()) {
      self::checkValidSessionId();
      if (!self::haveRightsOr($module, $rights)) {
         self::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }


   /**
    * Check if I have one of the right specified
    *
    * You can't use this function if several rights for same module name
    *
    * @param $modules array of modules where keys are modules and value are right
    *
    * @return Nothing : display error if not permit
   **/
   static function checkSeveralRightsOr($modules) {
      global $CFG_GLPI;

      self::checkValidSessionId();

      $valid = false;
      if (count($modules)) {
         foreach ($modules as $mod => $right) {
            // Itemtype
            if (preg_match('/[A-Z]/', $mod[0])) {
               if ($item = getItemForItemtype($mod)) {
                  if ($item->canGlobal($right)) {
                     $valid = true;
                  }
               }
            } else if (self::haveRight($mod, $right)) {
               $valid = true;
            }
         }
      }

      if (!$valid) {
         // Gestion timeout session
         self::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }


   /**
    * Check if you could access to ALL the entities of an list
    *
    * @param $tab : list ID of entities
    *
    * @return Boolean :
   **/
   static function haveAccessToAllOfEntities($tab) {

      if (is_array($tab) && count($tab)) {
         foreach ($tab as $val) {
            if (!self::haveAccessToEntity($val)) {
               return false;
            }
         }
      }
      return true;
   }


   /**
    * Check if you could access (read) to the entity of id = $ID
    *
    * @param $ID                    ID of the entity
    * @param $is_recursive boolean  if recursive item (default 0)
    *
    * @return Boolean : read access to entity
   **/
   static function haveAccessToEntity($ID, $is_recursive=0) {

      // Quick response when passing wrong ID : default value of getEntityID is -1
      if ($ID < 0) {
         return false;
      }

      if (!isset($_SESSION['glpiactiveentities'])) {
         return false;
      }

      if (!$is_recursive) {
         return in_array($ID, $_SESSION['glpiactiveentities']);
      }

      if (in_array($ID, $_SESSION['glpiactiveentities'])) {
         return true;
      }

      /// Recursive object
      foreach ($_SESSION['glpiactiveentities'] as $ent) {
         if (in_array($ID, getAncestorsOf("glpi_entities", $ent))) {
            return true;
         }
      }

      return false;
   }


   /**
    * Check if you could access to one entity of an list
    *
    * @param $tab                   list ID of entities
    * @param $is_recursive boolean  if recursive item (default 0)
    *
    * @return Boolean :
   **/
   static function haveAccessToOneOfEntities($tab, $is_recursive=0) {

      if (is_array($tab) && count($tab)) {
         foreach ($tab as $val) {
            if (self::haveAccessToEntity($val, $is_recursive)) {
               return true;
            }
         }
      }
      return false;
   }


   /**
    * Check if you could create recursive object in the entity of id = $ID
    *
    * @param $ID : ID of the entity
    *
    * @return Boolean :
   **/
   static function haveRecursiveAccessToEntity($ID) {

      // Right by profile
      foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
         if ($val['id'] == $ID) {
            return $val['is_recursive'];
         }
      }
      // Right is from a recursive profile
      if (isset($_SESSION['glpiactiveentities'])) {
         return in_array($ID, $_SESSION['glpiactiveentities']);
      }
      return false;
   }


   /**
    * Have I the right $right to module $module (conpare to session variable)
    *
    * @param $module Module to check
    * @param $right  Right to check
    *
    * @return Boolean : session variable have more than the right specified for the module
   **/
   static function haveRight($module, $right) {
      global $DB;

      //If GLPI is using the slave DB -> read only mode
      if ($DB->isSlave()
          && ($right & (CREATE | UPDATE | DELETE | PURGE))) {
         return false;
      }

      if (isset($_SESSION["glpiactiveprofile"][$module])) {
         return intval($_SESSION["glpiactiveprofile"][$module]) & $right;
      }

      return false;
   }


   /**
    * Have I all rights of array $rights to module $module (conpare to session variable)
    *
    * @param $module           Module to check
    * @param $rights   array   Rights to check
    *
    * @return Boolean : session variable have more than the right specified for the module
    **/
   static function haveRightsAnd($module, $rights=array()) {

      foreach ($rights as $right) {
         if (!Session::haveRight($module, $right)) {
            return false;
         }
      }
      return true;
   }


   /**
    * Have I one right of array $rights to module $module (conpare to session variable)
    *
    * @param $module           Module to check
    * @param $rights   array   Rights to check
    *
    * @return Boolean : session variable have more than the right specified for the module
    **/
   static function haveRightsOr($module, $rights=array()) {

      foreach ($rights as $right) {
         if (Session::haveRight($module, $right)) {
            return true;
         }
      }
      return false;
   }


   /**
    *  Get active Tab for an itemtype
    *
    * @param $itemtype item type
    *
    * @return nothing
   **/
   static function getActiveTab($itemtype) {

      if (isset($_SESSION['glpi_tabs'][strtolower($itemtype)])) {
         return $_SESSION['glpi_tabs'][strtolower($itemtype)];
      }
      return "";
   }


   /**
    * Add a message to be displayed after redirect
    *
    * @param $msg             Message to add
    * @param $check_once      Check if the message is not already added (false by default)
    * @param $message_type    Message type (INFO, WARNING, ERROR) (default INFO)
    * @param $reset           Clear previous added message (false by default)
   **/
   static function addMessageAfterRedirect($msg, $check_once=false, $message_type=INFO,
                                           $reset=false) {

      if (!empty($msg)) {
         if (self::isCron()) {
            // We are in cron mode
            // Do not display message in user interface, but record error
            if ($message_type == ERROR) {
               Toolbox::logInFile('cron', $msg."\n");
            }

         } else {

            if ($reset) {
               $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
            }

            if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"][$message_type])) {
               $_SESSION["MESSAGE_AFTER_REDIRECT"][$message_type] = array();
            }

            if (!$check_once
                || !isset($_SESSION["MESSAGE_AFTER_REDIRECT"][$message_type])
                || in_array($msg, $_SESSION["MESSAGE_AFTER_REDIRECT"][$message_type]) === false) {
               array_push($_SESSION["MESSAGE_AFTER_REDIRECT"][$message_type], $msg);
            }
         }
      }
   }


   /**
    *  Force active Tab for an itemtype
    *
    * @param $itemtype  item type
    * @param $tab       ID of the tab
   **/
   static function setActiveTab($itemtype, $tab) {
      $_SESSION['glpi_tabs'][strtolower($itemtype)] = $tab;
   }


   /**
    * Get a saved option from request or session
    * if get from request, save it
    *
    * @since version 0.83
    *
    * @param $itemtype  string   name of itemtype
    * @param $name      string   name of the option
    * @param $defvalue           mixed default value for option
    *
    * @return Mixed value of the option
   **/
   static function getSavedOption($itemtype, $name, $defvalue) {

      if (isset($_REQUEST[$name])) {
         return $_SESSION['glpi_saved'][$itemtype][$name] = $_REQUEST[$name];
      }

      if (isset($_SESSION['glpi_saved'][$itemtype][$name])) {
         return $_SESSION['glpi_saved'][$itemtype][$name];
      }
      return $defvalue;
   }


   /**
    * Is the current account read-only
    *
    * @since version 0.83
    *
    * @return Boolean
   **/
   static function isReadOnlyAccount() {

      foreach ($_SESSION['glpiactiveprofile'] as $name => $val) {
         if (is_numeric($val)
             && ($name != 'search_config')
             && ($val & ~READ)) {
            return false;
         }
      }
      return true;
   }



   /**
    * Get new CSRF token
    *
    * @since version 0.83.3
    *
    * @return      string  new generated token
   **/
   static public function getNewCSRFToken() {
      global $CURRENTCSRFTOKEN;

      if (empty($CURRENTCSRFTOKEN)) {
         do {
            $CURRENTCSRFTOKEN = md5(uniqid(rand(), TRUE));
         } while ($CURRENTCSRFTOKEN == '');
      }

      if (!isset($_SESSION['glpicsrftokens'])) {
         $_SESSION['glpicsrftokens'] = array();
      }
      $_SESSION['glpicsrftokens'][$CURRENTCSRFTOKEN] = time() + GLPI_CSRF_EXPIRES;
      return $CURRENTCSRFTOKEN;
   }


   /**
    * Clean expires CSRF tokens
    *
    * @since version 0.83.3
    *
    * @return      nothing
   **/
   static public function cleanCSRFTokens() {

      $now = time();
      if (isset($_SESSION['glpicsrftokens']) && is_array($_SESSION['glpicsrftokens'])) {
         if (count($_SESSION['glpicsrftokens'])) {
            foreach ($_SESSION['glpicsrftokens'] as $token => $expires) {
               if ($expires < $now) {
                  unset($_SESSION['glpicsrftokens'][$token]);
               }
            }
            $overflow = count($_SESSION['glpicsrftokens']) - GLPI_CSRF_MAX_TOKENS;
            if ($overflow > 0) {
               $_SESSION['glpicsrftokens'] = array_slice($_SESSION['glpicsrftokens'], $overflow + 1,
                                                         null, true);
            }
         }
      }
   }


   /**
    * Validate that the page has a CSRF token in the POST data
    * and that the token is legit/not expired.  If the token is valid
    * it will be removed from the list of valid tokens.
    *
    * @since version 0.83.3
    *
    * @param $data array $_POST datas
    *
    * @return boolean Valid csrf token.
   **/
   static public function validateCSRF($data) {

      if (!isset($data['_glpi_csrf_token'])) {
         Session::cleanCSRFTokens();
         return false;
      }
      $requestToken = $data['_glpi_csrf_token'];
      if (isset($_SESSION['glpicsrftokens'][$requestToken])
          && ($_SESSION['glpicsrftokens'][$requestToken] >= time())) {
         if (!defined('GLPI_KEEP_CSRF_TOKEN')) { /* When post open a new windows */
            unset($_SESSION['glpicsrftokens'][$requestToken]);
         }
         Session::cleanCSRFTokens();
         return true;
      }
      Session::cleanCSRFTokens();
      return false;
   }


   /**
    * Check CSRF data
    *
    * @since version 0.84.2
    *
    * @param $data array $_POST datas
    *
    * @return nothing : display error if not permit
   **/
   static public function checkCSRF($data) {

      if (GLPI_USE_CSRF_CHECK
          && (!Session::validateCSRF($data))) {
         Html::displayErrorAndDie(__("The action you have requested is not allowed."), true);
      }
   }


   /**
    * Is field having translations ?
    *
    * @since version 0.85
    *
    * @param $itemtype   itemtype
    * @param $field      field
    *
    * @return boolean
   **/
   static function haveTranslations($itemtype, $field) {

      return (isset($_SESSION['glpi_dropdowntranslations'][$itemtype])
              && isset($_SESSION['glpi_dropdowntranslations'][$itemtype][$field]));
   }

}
