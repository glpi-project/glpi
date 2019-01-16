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

/**
 *  Class used to manage Auth mail config
 */
class AuthMail extends CommonDBTM {


   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';

   static function getTypeName($nb = 0) {
      return _n('Mail server', 'Mail servers', $nb);
   }

   function prepareInputForUpdate($input) {

      if (isset($input['mail_server']) && !empty($input['mail_server'])) {
         $input["connect_string"] = Toolbox::constructMailServerConfig($input);
      }
      return $input;
   }

   static function canCreate() {
      return static::canUpdate();
   }

   static function canPurge() {
      return static::canUpdate();
   }

   function prepareInputForAdd($input) {

      if (isset($input['mail_server']) && !empty($input['mail_server'])) {
         $input["connect_string"] = Toolbox::constructMailServerConfig($input);
      }
      return $input;
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Email server')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'host',
         'name'               => __('Server'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'connect_string',
         'name'               => __('Connection string'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
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
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      return $tab;
   }

   /**
    * Print the auth mail form
    *
    * @param integer $ID      ID of the item
    * @param array   $options Options
    *
    * @return void (display)
    */
   function showForm($ID, $options = []) {

      if (!Config::canUpdate()) {
         return false;
      }
      if (empty($ID)) {
         $this->getEmpty();
      } else {
         $this->getFromDB($ID);
      }

      if (Toolbox::canUseImapPop()) {
         $options['colspan'] = 1;
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
         echo "<td><input size='30' type='text' name='name' value='". $this->fields["name"] ."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Active') . "</td>";
         echo "<td colspan='3'>";
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>". __('Email domain Name (users email will be login@domain)') ."</td>";
         echo "<td><input size='30' type='text' name='host' value='" . $this->fields["host"] . "'>";
         echo "</td></tr>";

         Toolbox::showMailServerConfig($this->fields["connect_string"]);

         echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
         echo "<td>";
         echo "<textarea cols='40' rows='4' name='comment'>".$this->fields["comment"]."</textarea>";
         if ($ID>0) {
            echo "<br>";
            //TRANS: %s is the datetime of update
            printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
         }

         echo "</td></tr>";

         $this->showFormButtons($options);

      } else {
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . __('Email server configuration') . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>".sprintf(__('%s extension is missing'), 'IMAP')."</p>";
         echo "<p>". __('Impossible to use email server as external source of connection')."</p>";
         echo "</td></tr></table></div>";
      }
   }

   /**
    * Show test mail form
    *
    * @return void
    */
   function showFormTestMail() {

      $ID = $this->getField('id');

      if ($this->getFromDB($ID)) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
         echo "<input type='hidden' name='imap_string' value=\"".$this->fields['connect_string']."\">";
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr><th colspan='2'>" . __('Test connection to email server') . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Login') . "</td>";
         echo "<td><input size='30' type='text' name='imap_login' value=''></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Password') . "</td>";
         echo "<td><input size='30' type='password' name='imap_password' value=''
                    autocomplete='off'></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input type='submit' name='test' class='submit' value=\""._sx('button', 'Test')."\">".
              "</td>";
         echo "</tr></table></div>";
         Html::closeForm();
      }
   }


   /**
    * Is the Mail authentication used?
    *
    * @return boolean
    */
   static function useAuthMail() {
      return (countElementsInTable('glpi_authmails', ['is_active' => 1]) > 0);
   }


   /**
    * Test a connexion to the IMAP/POP server
    *
    * @param string $connect_string mail server
    * @param string $login          user login
    * @param string $password       user password
    *
    * @return boolean authentification succeeded?
    */
   static function testAuth($connect_string, $login, $password) {

      $auth = new Auth();
      return $auth->connection_imap($connect_string, Toolbox::decodeFromUtf8($login),
                                    Toolbox::decodeFromUtf8($password));
   }


   /**
    * Authentify a user by checking a specific mail server
    *
    * @param object $auth        identification object
    * @param string $login       user login
    * @param string $password    user password
    * @param string $mail_method mail_method array to use
    *
    * @return object identification object
    */
   static function mailAuth($auth, $login, $password, $mail_method) {

      if (isset($mail_method["connect_string"]) && !empty($mail_method["connect_string"])) {
         $auth->auth_succeded = $auth->connection_imap($mail_method["connect_string"],
                                                       Toolbox::decodeFromUtf8($login),
                                                       Toolbox::decodeFromUtf8($password));
         if ($auth->auth_succeded) {
            $auth->extauth      = 1;
            $auth->user_present = $auth->user->getFromDBbyName($login);
            $auth->user->getFromIMAP($mail_method, Toolbox::decodeFromUtf8($login));
            //Update the authentication method for the current user
            $auth->user->fields["authtype"] = Auth::MAIL;
            $auth->user->fields["auths_id"] = $mail_method["id"];
         }
      }
      return $auth;
   }


   /**
    * Try to authentify a user by checking all the mail server
    *
    * @param object  $auth     identification object
    * @param string  $login    user login
    * @param string  $password user password
    * @param integer $auths_id auths_id already used for the user (default 0)
    * @param boolean $break    if user is not found in the first directory,
    *                          stop searching or try the following ones (true by default)
    *
    * @return object identification object
    */
   static function tryMailAuth($auth, $login, $password, $auths_id = 0, $break = true) {

      if ($auths_id <= 0) {
         foreach ($auth->authtypes["mail"] as $mail_method) {
            if (!$auth->auth_succeded && $mail_method['is_active']) {
               $auth = self::mailAuth($auth, $login, $password, $mail_method);
            } else {
               if ($break) {
                  break;
               }
            }
         }

      } else if (array_key_exists($auths_id, $auth->authtypes["mail"])) {
         //Check if the mail server indicated as the last good one still exists !
         $auth = self::mailAuth($auth, $login, $password, $auth->authtypes["mail"][$auths_id]);
      }
      return $auth;
   }

   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this, 'MAIL_SERVER');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate && $item->can($item->getField('id'), READ)) {
         $ong = [];
         $ong[1] = _sx('button', 'Test');    // test connexion

         return $ong;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($tabnum) {
         case 1 :
            $item->showFormTestMail();
            break;
      }
      return true;
   }

}
