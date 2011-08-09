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

      Sesion::start();
      // Unset all of the session variables.
      session_unset();
      // destroy may cause problems (no login / back to login page)
      $_SESSION = array();
      // write_close may cause troubles (no login / back to login page)
   }



   /**
    * Init session for the user is defined
    *
    * @return nothing
   **/
   static function init() {
      global $CFG_GLPI, $LANG;

      if ($this->auth_succeded) {
         // Restart GLPi session : complete destroy to prevent lost datas
         $tosave = array('glpi_plugins', 'glpicookietest', 'phpCAS');
         $save   = array();
         foreach ($tosave as $t) {
            if (isset($_SESSION[$t])) {
               $save[$t] = $_SESSION[$t];
            }
         }
         self::destroy();
         Session::start();
         $_SESSION = $save;

         // Normal mode for this request
         $_SESSION["glpi_use_mode"] = NORMAL_MODE;
         // Check ID exists and load complete user from DB (plugins...)
         if (isset($this->user->fields['id'])
             && $this->user->getFromDB($this->user->fields['id'])) {

            if (!$this->user->fields['is_deleted'] && $this->user->fields['is_active']) {
               $_SESSION["glpiID"]              = $this->user->fields['id'];
               $_SESSION["glpiname"]            = $this->user->fields['name'];
               $_SESSION["glpirealname"]        = $this->user->fields['realname'];
               $_SESSION["glpifirstname"]       = $this->user->fields['firstname'];
               $_SESSION["glpidefault_entity"]  = $this->user->fields['entities_id'];
               $_SESSION["glpiusers_idisation"] = true;
               $_SESSION["glpiextauth"]         = $this->extauth;
               $_SESSION["glpiauthtype"]        = $this->user->fields['authtype'];
               $_SESSION["glpisearchcount"]     = array();
               $_SESSION["glpisearchcount2"]    = array();
               $_SESSION["glpiroot"]            = $CFG_GLPI["root_doc"];
               $_SESSION["glpi_use_mode"]       = $this->user->fields['use_mode'];
               $_SESSION["glpicrontimer"]       = time();
               // Default tab
//               $_SESSION['glpi_tab']=1;
               $_SESSION['glpi_tabs']           = array();
               $this->user->computePreferences();
               foreach ($CFG_GLPI['user_pref_field'] as $field) {
                  if (isset($this->user->fields[$field])) {
                     $_SESSION["glpi$field"] = $this->user->fields[$field];
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
               if (isset($_SESSION['glpiprofiles'][$this->user->fields['profiles_id']])) {
                  changeProfile($this->user->fields['profiles_id']);

               } else { // Else use first
                  changeProfile(key($_SESSION['glpiprofiles']));
               }

               if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
                  $this->auth_succeded = false;
                  $this->addToError($LANG['login'][25]);
               }

            } else {
               $this->auth_succeded = false;
               $this->addToError($LANG['login'][20]);
            }

         } else {
            $this->auth_succeded = false;
            $this->addToError($LANG['login'][25]);
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


}
?>