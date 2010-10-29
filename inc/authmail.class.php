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

/**
 *  Class used to manage Auth mail config
**/
class AuthMail extends CommonDBTM {


   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['login'][3];
   }


   function canCreate() {
      return haveRight('config', 'w');
   }


   function canView() {
      return haveRight('config', 'r');
   }


   function prepareInputForUpdate($input) {

      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["connect_string"] = constructMailServerConfig($input);
      }
      return $input;
   }


   function prepareInputForAdd($input) {

      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["connect_string"] = constructMailServerConfig($input);
      }
      return $input;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];

      if ($this->fields['id'] > 0) {
         $ong[12] = $LANG['title'][38];
      }
      return $ong;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['login'][2];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'host';
      $tab[3]['name']          = $LANG['common'][52];

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'connect_string';
      $tab[4]['name']          = $LANG['setup'][170];
      $tab[4]['massiveaction'] = false;

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'is_active';
      $tab[6]['name']      = $LANG['common'][60];
      $tab[6]['datatype']  = 'bool';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      return $tab;
   }


   /**
    * Print the auth mail form
    *
    * @param $ID Integer : ID of the item
    * @param $options array
    *
    * @return Nothing (display)
    **/
   function showForm($ID, $options=array()) {
      global $LANG;

      if (!haveRight("config", "w")) {
         return false;
      }
      $spotted = false;
      if (empty ($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (canUseImapPop()) {
         $options['colspan']=1;
         $this->showTabs($options);
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='name' value='" . $this->fields["name"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . $LANG['common'][60] . "&nbsp;:</td>";
         echo "<td colspan='3'>";
         Dropdown::showYesNo('is_active',$this->fields['is_active']);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][164] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='host' value='" . $this->fields["host"] . "'>";
         echo "</td></tr>";

         showMailServerConfig($this->fields["connect_string"]);

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][25] . "&nbsp;:</td>";
         echo "<td>";
         echo "<textarea cols='40' rows='4' name='comment'>".$this->fields["comment"]."</textarea>";
         if ($ID>0) {
            echo "<br>".$LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"]);
         }

         echo "</td></tr>";


         $this->showFormButtons($options);
         $this->addDivForTabs();

      } else {
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['setup'][162] . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>" . $LANG['setup'][165] . "</p>";
         echo "<p>" . $LANG['setup'][166] . "</p></td></tr></table></div>";
      }
   }


   function showFormTestMail ($ID) {
      global $LANG;

      if ($this->getFromDB($ID)) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
         echo "<input type='hidden' name='imap_string' value=\"".$this->fields['connect_string']."\">";
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr><th colspan='2'>" . $LANG['login'][21] . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][6] . "</td>";
         echo "<td><input size='30' type='text' name='imap_login' value=''></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][7] . "</td>";
         echo "<td><input size='30' type='password' name='imap_password' value=''
                    autocomplete='off'></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
         echo "<input type='submit' name='test' class='submit' value=\"".$LANG['buttons'][2]."\"></td>";
         echo "</tr></table></div></form>";
      }
   }


   /**
    * Is the Mail authentication used ?
    *
    * @return boolean
   **/
   static function useAuthMail() {
      global $DB;

      //Get all the pop/imap servers
      $sql = "SELECT count(*)
              FROM `glpi_authmails`";
      $result = $DB->query($sql);

      if ($DB->result($result, 0, 0) > 0) {
         return true;
      }
      return false;
   }


   /**
    * Test a connexion to the IMAP/POP server
    * @param $connect_string : mail server
    * @param $login : user login
    * @param $password : user password
    *
    * @return authentification succeeded ?
   **/
   static function testAuth($connect_string, $login, $password) {

      $auth = new Auth();
      return $auth->connection_imap($connect_string, decodeFromUtf8($login),
                                    decodeFromUtf8($password));
   }


   /**
    * Authentify a user by checking a specific mail server
    * @param $auth : identification object
    * @param $login : user login
    * @param $password : user password
    * @param $mail_method : mail_method array to use
    *
    * @return identification object
   **/
   static function mailAuth($auth, $login, $password, $mail_method) {

      if (isset($mail_method["connect_string"]) && !empty ($mail_method["connect_string"])) {
         $auth->auth_succeded = $auth->connection_imap($mail_method["connect_string"],
                                                       decodeFromUtf8($login),
                                                       decodeFromUtf8($password));
         if ($auth->auth_succeded) {
            $auth->extauth = 1;
            $auth->user_present = $auth->user->getFromDBbyName(addslashes($login));
            $auth->user->getFromIMAP($mail_method, decodeFromUtf8($login));
            //Update the authentication method for the current user
            $auth->user->fields["authtype"] = Auth::MAIL;
            $auth->user->fields["auths_id"] = $mail_method["id"];
         }
      }
      return $auth;
   }


   /**
    * Try to authentify a user by checking all the mail server
    * @param $auth : identification object
    * @param $login : user login
    * @param $password : user password
    * @param $auths_id : auths_id already used for the user
    * @param $break : if user is not found in the first directory, stop searching or try the following ones
    *
    * @return identification object
   **/
   static function tryMailAuth($auth, $login, $password, $auths_id = 0, $break=true) {

      if ($auths_id <= 0) {
         foreach ($auth->authtypes["mail"] as $mail_method) {
            if (!$auth->auth_succeded && $mail_method['is_active']) {
               $auth = AuthMail::mailAuth($auth, $login, $password, $mail_method);
            } else {
               if ($break) {
                  break;
               }
            }
         }

      } else if (array_key_exists($auths_id,$auth->authtypes["mail"])) {
         //Check if the mail server indicated as the last good one still exists !
         $auth = AuthMail::mailAuth($auth, $login, $password, $auth->authtypes["mail"][$auths_id]);
      }
      return $auth;
   }

}

?>