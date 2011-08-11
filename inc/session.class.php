<?php
/*
 * @version $Id:
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Session
class Session {


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
    * @return nothing
   **/
   static function init(Auth $auth) {
      global $CFG_GLPI, $LANG;

      if ($auth->auth_succeded) {
         // Restart GLPi session : complete destroy to prevent lost datas
         $tosave = array('glpi_plugins', 'glpicookietest', 'phpCAS');
         $save   = array();
         foreach ($tosave as $t) {
            if (isset($_SESSION[$t])) {
               $save[$t] = $_SESSION[$t];
            }
         }
         self::destroy();
         self::start();
         $_SESSION = $save;

         // Normal mode for this request
         $_SESSION["glpi_use_mode"] = NORMAL_MODE;
         // Check ID exists and load complete user from DB (plugins...)
         if (isset($auth->user->fields['id'])
             && $auth->user->getFromDB($auth->user->fields['id'])) {

            if (!$auth->user->fields['is_deleted'] && $auth->user->fields['is_active']) {
               $_SESSION["glpiID"]              = $auth->user->fields['id'];
               $_SESSION["glpiname"]            = $auth->user->fields['name'];
               $_SESSION["glpirealname"]        = $auth->user->fields['realname'];
               $_SESSION["glpifirstname"]       = $auth->user->fields['firstname'];
               $_SESSION["glpidefault_entity"]  = $auth->user->fields['entities_id'];
               $_SESSION["glpiusers_idisation"] = true;
               $_SESSION["glpiextauth"]         = $auth->extauth;
               $_SESSION["glpiauthtype"]        = $auth->user->fields['authtype'];
               $_SESSION["glpisearchcount"]     = array();
               $_SESSION["glpisearchcount2"]    = array();
               $_SESSION["glpiroot"]            = $CFG_GLPI["root_doc"];
               $_SESSION["glpi_use_mode"]       = $auth->user->fields['use_mode'];
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
               loadLanguage();

               // glpiprofiles -> other available profile with link to the associated entities
               doHook("init_session");

               initEntityProfiles(getLoginUserID());

               // Use default profile if exist
               if (isset($_SESSION['glpiprofiles'][$auth->user->fields['profiles_id']])) {
                  self::changeProfile($auth->user->fields['profiles_id']);

               } else { // Else use first
                  self::changeProfile(key($_SESSION['glpiprofiles']));
               }

               if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
                  $auth->auth_succeded = false;
                  $auth->addToError($LANG['login'][25]);
               }

            } else {
               $auth->auth_succeded = false;
               $auth->addToError($LANG['login'][20]);
            }

         } else {
            $auth->auth_succeded = false;
            $auth->addToError($LANG['login'][25]);
         }
      }
   }


   /**
    * Set the directory where are store the session file
   **/
   static function setPath() {

      if (ini_get("session.save_handler")=="files") {
         session_save_path(GLPI_SESSION_DIR);
      }
   }


   /**
    * Start the GLPI php session
   **/
   static function start() {

      if (!session_id()) {
         @session_start();
      }
      // Define current time for sync of action timing
      $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
   }


   /**
    * Is GLPI used in multi-entities mode ?
    *
    * @return boolean
   **/
   static function isMultiEntitiesMode() {

      if (!isset($_SESSION['glpi_multientitiesmode'])) {
         if (countElementsInTable("glpi_entities")>0) {
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
              || (countElementsInTable("glpi_entities")+1) == count($_SESSION["glpiactiveentities"]));
   }


   /** Add an item to the navigate through search results list
    *
    * @param $itemtype device type
    * @param $ID ID of the item
   **/
   static function addToNavigateListItems($itemtype, $ID) {
      $_SESSION['glpilistitems'][$itemtype][] = $ID;
   }


    /** Initialise a list of items to use navigate through search results
     *
     * @param $itemtype device type
     * @param $title titre de la liste
    **/
    static function initNavigateListItems($itemtype, $title="") {
       global $LANG;

       if (empty($title)) {
          $title = $LANG['common'][53];
       }
       $url = '';

       if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'],"tabs")>0) {
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
    * @param $ID : ID of the new active entity ("all"=>load all possible entities)
    * @param $is_recursive : also display sub entities of the active entity ?
    *
    * @return Nothing
   **/
   static function changeActiveEntities($ID="all", $is_recursive=false) {
      global $LANG;

      $newentities = array();
      $newroots    = array();
      if (isset($_SESSION['glpiactiveprofile'])) {
         if ($ID=="all") {
            $ancestors = array();
            foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
               $ancestors = array_unique(array_merge(getAncestorsOf("glpi_entities", $val['id']),
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
            $ok = false;
            foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
               if ($val['id']== $ID || in_array($val['id'], $ancestors)) {
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

      if (count($newentities)>0) {
         $_SESSION['glpiactiveentities']        = $newentities;
         $_SESSION['glpiactiveentities_string'] = "'".implode("', '", $newentities)."'";
         $active = reset($newentities);
         $_SESSION['glpiparententities']        = $ancestors;
         $_SESSION['glpiparententities_string'] = implode("', '" ,$ancestors);
         if (!empty($_SESSION['glpiparententities_string'])) {
            $_SESSION['glpiparententities_string'] = "'".$_SESSION['glpiparententities_string']."'";
         }
         // Active entity loading
         $_SESSION["glpiactive_entity"]           = $active;
         $_SESSION["glpiactive_entity_name"]      = Dropdown::getDropdownName("glpi_entities", $active);
         $_SESSION["glpiactive_entity_shortname"] = getTreeLeafValueName("glpi_entities", $active);
         if ($is_recursive) {
            $_SESSION["glpiactive_entity_name"]      .= " (".$LANG['entity'][7].")";
            $_SESSION["glpiactive_entity_shortname"] .= " (".$LANG['entity'][7].")";
         }
         if ($ID=="all") {
            $_SESSION["glpiactive_entity_name"]      .= " (".$LANG['buttons'][40].")";
            $_SESSION["glpiactive_entity_shortname"] .= " (".$LANG['buttons'][40].")";
         }
         if (countElementsInTable('glpi_entities')<count($_SESSION['glpiactiveentities'])) {
            $_SESSION['glpishowallentities'] = 1;
         } else {
            $_SESSION['glpishowallentities'] = 0;
         }
         // Clean session variable to search system
         if (isset($_SESSION['glpisearch']) && count($_SESSION['glpisearch'])) {
            foreach ($_SESSION['glpisearch'] as $itemtype => $tab) {
               if (isset($tab['start']) && $tab['start']>0) {
                  $_SESSION['glpisearch'][$itemtype]['start'] = 0;
               }
            }
         }
         loadGroups();
         doHook("change_entity");
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
            $data = $profile->fields;
            $data['entities'] = $_SESSION['glpiprofiles'][$ID]['entities'];

            $_SESSION['glpiactiveprofile']  = $data;
            $_SESSION['glpiactiveentities'] = array();

            Search::resetSaveSearch();
            $active_entity_done = false;

            // Try to load default entity if it is a root entity
            foreach ($data['entities'] as $key => $val) {
               if ($val['id']==$_SESSION["glpidefault_entity"]) {
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
            doHook("change_profile");
         }
      }
      // Clean specific datas
      if (isset($_SESSION['glpi_faqcategories'])) {
         unset($_SESSION['glpi_faqcategories']);
      }
   }


}
?>