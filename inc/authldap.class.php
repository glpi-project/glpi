<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

/**
 *  Class used to manage Auth LDAP config
**/
class AuthLDAP extends CommonDBTM {

   const SIMPLE_INTERFACE = 'simple';
   const EXPERT_INTERFACE = 'expert';

   const ACTION_IMPORT      = 0;
   const ACTION_SYNCHRONIZE = 1;
   const ACTION_ALL         = 2;

   const USER_IMPORTED     = 0;
   const USER_SYNCHRONIZED = 1;
   const USER_DELETED_LDAP = 2;

   //Import user by giving his login
   const IDENTIFIER_LOGIN = 'login';

   //Import user by giving his email
   const IDENTIFIER_EMAIL = 'email';

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';


   static function getTypeName($nb=0) {
      return _n('LDAP directory', 'LDAP directories', $nb);
   }


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }


   function post_getEmpty() {

      $this->fields['port']                        = '389';
      $this->fields['condition']                   = '';
      $this->fields['login_field']                 = 'uid';
      $this->fields['use_tls']                     = 0;
      $this->fields['group_field']                 = '';
      $this->fields['group_condition']             = '';
      $this->fields['group_search_type']           = 0;
      $this->fields['group_member_field']          = '';
      $this->fields['email1_field']                = 'mail';
      $this->fields['email2_field']                = '';
      $this->fields['email3_field']                = '';
      $this->fields['email4_field']                = '';
      $this->fields['realname_field']              = 'sn';
      $this->fields['firstname_field']             = 'givenname';
      $this->fields['phone_field']                 = 'telephonenumber';
      $this->fields['phone2_field']                = '';
      $this->fields['mobile_field']                = '';
      $this->fields['registration_number_field']   = '';
      $this->fields['comment_field']               = '';
      $this->fields['title_field']                 = '';
      $this->fields['use_dn']                      = 0;
      $this->fields['picture_field']               = '';
   }


   /**
    * Preconfig datas for standard system
    *
    * @param $type type of standard system : AD
    *
    * @return nothing
   **/
   function preconfig($type) {

      switch($type) {
         case 'AD' :
            $this->fields['port']                      = "389";
            $this->fields['condition']
               = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['login_field']               = 'samaccountname';
            $this->fields['use_tls']                   = 0;
            $this->fields['group_field']               = 'memberof';
            $this->fields['group_condition']
               = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['group_search_type']         = 0;
            $this->fields['group_member_field']        = '';
            $this->fields['email1_field']              = 'mail';
            $this->fields['email2_field']              = '';
            $this->fields['email3_field']              = '';
            $this->fields['email4_field']              = '';
            $this->fields['realname_field']            = 'sn';
            $this->fields['firstname_field']           = 'givenname';
            $this->fields['phone_field']               = 'telephonenumber';
            $this->fields['phone2_field']              = 'othertelephone';
            $this->fields['mobile_field']              = 'mobile';
            $this->fields['registration_number_field'] = 'employeenumber';
            $this->fields['comment_field']             = 'info';
            $this->fields['title_field']               = 'title';
            $this->fields['entity_field']              = 'ou';
            $this->fields['entity_condition']          = '(objectclass=organizationalUnit)';
            $this->fields['use_dn']                    = 1 ;
            $this->fields['can_support_pagesize']      = 1 ;
            $this->fields['pagesize']                  = '1000';
            $this->fields['picture_field']             = '';
            break;

         default:
            $this->post_getEmpty();
      }
   }


   function prepareInputForUpdate($input) {

      if (isset($input["rootdn_passwd"])) {
         if (empty($input["rootdn_passwd"])) {
            unset($input["rootdn_passwd"]);
         } else {
            $input["rootdn_passwd"] = Toolbox::encrypt(stripslashes($input["rootdn_passwd"]),
                                                       GLPIKEY);
         }
      }

         if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
         $input['rootdn_passwd'] = '';
      }

      // Set attributes in lower case
      if (count($input)) {
         foreach ($input as $key => $val) {
            if (preg_match('/_field$/',$key)) {
               $input[$key] = Toolbox::strtolower($val);
            }
         }
      }
      return $input;
   }


   /**
    * @since version 0.84
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
         case 'group_search_type' :
            return self::getGroupSearchTypeName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param  $field
    * @param  $name              (default '')
    * @param  $values            (default('')
    * @param  $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'group_search_type' :
            $options['value'] = $values[$field];
            $options['name']  = $name;
            return self::dropdownGroupSearchType($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $CFG_GLPI;

      $input = $ma->getInput();

      switch ($ma->getAction()) {
         case 'import_group' :
            $group = new Group;
            if (!Session::haveRight("user", User::UPDATEAUTHENT)
                || !$group->canGlobal(UPDATE)) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
               $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               return;
            }
            foreach ($ids as $id) {
               if (isset($input["dn"][$id])) {
                  $group_dn = $input["dn"][$id];
                  if (isset($input["ldap_import_entities"][$id])) {
                     $entity = $input["ldap_import_entities"][$id];
                  } else {
                     $entity = $_SESSION["glpiactive_entity"];
                  }
                  // Is recursive is in the main form and thus, don't pass through
                  // zero_on_empty mechanism inside massive action form ...
                  $is_recursive = (empty($input['ldap_import_recursive'][$id]) ? 0 : 1);
                  $options      = array('authldaps_id' => $_SESSION['ldap_server'],
                                        'entities_id'  => $entity,
                                        'is_recursive' => $is_recursive,
                                        'type'         => $input['ldap_import_type'][$id]);
                  if (AuthLdap::ldapImportGroup($group_dn, $options)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  }  else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION, $group_dn));
                  }
               }
               // Clean history as id does not correspond to group
               $_SESSION['glpimassiveactionselected'] = array();
            }
            return;

         case 'import' :
         case 'sync' :
            if (!Session::haveRight("user", User::IMPORTEXTAUTHUSERS)) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
               $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               return;
            }
            foreach ($ids as $id) {
               if (AuthLdap::ldapImportUserByServerId(array('method' => AuthLDAP::IDENTIFIER_LOGIN,
                                                            'value'  => $id),
                                                      $_SESSION['ldap_import']['mode'],
                                                      $_SESSION['ldap_import']['authldaps_id'],
                                                      true)) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               }  else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION, $id));
               }
            }
            return;
      }

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Print the auth ldap form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the form
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {

      if (!Config::canUpdate()) {
         return false;
      }
      $spotted = false;
      if (empty($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }
         if (isset($options['preconfig'])) {
            $this->preconfig($options['preconfig']);
         }
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (Toolbox::canUseLdap()) {
         $this->showFormHeader($options);
         if (empty($ID)) {
            $target = $this->getFormURL();
            echo "<tr class='tab_bg_2'><td>".__('Preconfiguration')."</td> ";
            echo "<td colspan='3'>";
            echo "<a href='$target?preconfig=AD'>".__('Active Directory')."</a>";
            echo "&nbsp;&nbsp;/&nbsp;&nbsp;";
            echo "<a href='$target?preconfig=default'>".__('Default values');
            echo "</a></td></tr>";
         }
         echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
         echo "<td><input type='text' name='name' value='". $this->fields["name"] ."'></td>";
         if ($ID > 0) {
            echo "<td>".__('Last update')."</td><td>".Html::convDateTime($this->fields["date_mod"]);
          } else {
          echo "<td colspan='2'>&nbsp;";
          }
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Default server') . "</td>";
         echo "<td>";
         Dropdown::showYesNo('is_default', $this->fields['is_default']);
         echo "</td>";
         echo "<td>" . __('Active'). "</td>";
         echo "<td>";
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Server') . "</td>";
         echo "<td><input type='text' name='host' value='" . $this->fields["host"] . "'></td>";
         echo "<td>" . __('Port (default=389)') . "</td>";
         echo "<td><input id='port' type='text' name='port' value='".$this->fields["port"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Connection filter') . "</td>";
         echo "<td colspan='3'>";
         echo "<textarea cols='100' rows='1' name='condition'>".$this->fields["condition"];
         echo "</textarea>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('BaseDN') . "</td>";
         echo "<td colspan='3'>";
         echo "<input type='text' name='basedn' size='100' value=\"".$this->fields["basedn"]."\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('RootDN (for non anonymous binds)') . "</td>";
         echo "<td colspan='3'><input type='text' name='rootdn' size='100' value=\"".
                $this->fields["rootdn"]."\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Password (for non-anonymous binds)') . "</td>";
         echo "<td><input type='password' name='rootdn_passwd' value='' autocomplete='off'>";
         if ($ID) {
            echo "<input type='checkbox' name='_blank_passwd'>&nbsp;".__('Clear');
         }

         echo "</td>";
         echo "<td>" . __('Login field') . "</td>";
         echo "<td><input type='text' name='login_field' value='".$this->fields["login_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
         echo "<td colspan='3'>";
         echo "<textarea cols='40' rows='4' name='comment'>".$this->fields["comment"]."</textarea>";

         //Fill fields when using preconfiguration models
         if (!$ID) {
            $hidden_fields = array('comment_field', 'condition', 'email1_field', 'email2_field',
                                   'email3_field', 'email4_field', 'entity_condition',
                                   'entity_field', 'firstname_field', 'group_condition',
                                   'group_field', 'group_member_field', 'group_search_type',
                                   'mobile_field', 'phone_field', 'phone2_field', 'port',
                                   'realname_field', 'registration_number_field', 'title_field',
                                   'use_dn', 'use_tls');

            foreach ($hidden_fields as $hidden_field) {
               echo "<input type='hidden' name='$hidden_field' value='".
                      $this->fields[$hidden_field]."'>";
            }
         }

         echo "</td></tr>";

         $this->showFormButtons($options);

      } else {
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . self::getTypeName(1) . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>". __("The LDAP extension of your PHP parser isn't installed")."</p>";
         echo "<p>".__('Impossible to use LDAP as external source of connection')."</p>".
              "</td></tr></table></div>";
      }
   }


   function showFormAdvancedConfig() {

      $ID = $this->getField('id');
      $hidden = '';

      echo "<div class='center'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_2'><th colspan='4'>";
      echo "<input type='hidden' name='id' value='$ID'>". __('Advanced information')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Use TLS') . "</td><td>";
      if (function_exists("ldap_start_tls")) {
         Dropdown::showYesNo('use_tls', $this->fields["use_tls"]);
      } else {
         echo "<input type='hidden' name='use_tls' value='0'>".__('ldap_start_tls does not exist');
      }
      echo "</td>";
      echo "<td>" . __('LDAP directory time zone') . "</td><td>";
      Dropdown::showGMT("time_offset", $this->fields["time_offset"]);
      echo"</td></tr>";

      if (self::isLdapPageSizeAvailable(false, false)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Use paged results') . "</td><td>";
         Dropdown::showYesNo('can_support_pagesize', $this->fields["can_support_pagesize"]);
         echo "</td>";
         echo "<td>" . __('Page size') . "</td><td>";
         Dropdown::showNumber("pagesize", array('value' => $this->fields['pagesize'],
                                                'min'   => 100,
                                                'max'   => 100000,
                                                'step'  => 100));
         echo"</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Maximum number of results') . "</td><td>";
         Dropdown::showNumber('ldap_maxlimit', array('value' => $this->fields['ldap_maxlimit'],
                                                     'min'   => 100,
                                                     'max'   => 999999,
                                                     'step'  => 100,
                                                     'toadd' => array(0 => __('Unlimited'))));
         echo "</td><td colspan='2'></td></tr>";

      } else {
         $hidden .= "<input type='hidden' name='can_support_pagesize' value='0'>";
         $hidden .= "<input type='hidden' name='pagesize' value='0'>";
         $hidden .= "<input type='hidden' name='ldap_maxlimit' value='0'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('How LDAP aliases should be handled') . "</td><td colspan='4'>";
      $alias_options[LDAP_DEREF_NEVER]     = __('Never dereferenced (default)');
      $alias_options[LDAP_DEREF_ALWAYS]    = __('Always dereferenced');
      $alias_options[LDAP_DEREF_SEARCHING] = __('Dereferenced during the search (but not when locating)');
      $alias_options[LDAP_DEREF_FINDING]   = __('Dereferenced when locating (not during the search)');
      Dropdown::showFromArray("deref_option", $alias_options,
                              array('value' => $this->fields["deref_option"]));
      echo"</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo $hidden;
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();
      echo "</div>";

   }


   function showFormReplicatesConfig() {
      global $DB;

      $ID     = $this->getField('id');
      $target = $this->getFormURL();
      $rand   = mt_rand();

      AuthLdapReplicate::addNewReplicateForm($target, $ID);

      $sql = "SELECT *
              FROM `glpi_authldapreplicates`
              WHERE `authldaps_id` = '$ID'
              ORDER BY `name`";
      $result = $DB->query($sql);

      if (($nb = $DB->numrows($result)) > 0) {
         echo "<br>";
         $canedit = Config::canUpdate();
         echo "<div class='center'>";
         Html::openMassiveActionsForm('massAuthLdapReplicate'.$rand);
         $massiveactionparams = array('num_displayed' => $nb,
                                      'container'     => 'massAuthLdapReplicate'.$rand);
         Html::showMassiveActions($massiveactionparams);
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'>".
              "<th colspan='4'>".__('List of LDAP directory replicates') . "</th></tr>";

         if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
            echo $_SESSION["LDAP_TEST_MESSAGE"];
            echo"</td></tr>";
            unset($_SESSION["LDAP_TEST_MESSAGE"]);
         }
         $header_begin   = "<tr>";
         $header_top     = "<th>".Html::getCheckAllAsCheckbox('massAuthLdapReplicate'.$rand)."</th>";
         $header_bottom  = "<th>".Html::getCheckAllAsCheckbox('massAuthLdapReplicate'.$rand)."</th>";
         $header_end     = "<th class='center b'>".__('Name')."</th>";
         $header_end    .= "<th class='center b'>"._n('Replicate', 'Replicates', 1)."</th>".
              "<th class='center'></th></tr>";
         echo $header_begin.$header_top.$header_end;

         while ($ldap_replicate = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'><td class='center' width='10'>";
            Html::showMassiveActionCheckBox('AuthLdapReplicate', $ldap_replicate["id"]);
            echo "</td>";
            echo "<td class='center'>" . $ldap_replicate["name"] . "</td>";
            echo "<td class='center'>".sprintf(__('%1$s: %2$s'), $ldap_replicate["host"],
                                               $ldap_replicate["port"]);
            echo "</td>";
            echo "<td class='center'>";
            Html::showSimpleForm(Toolbox::getItemTypeFormURL(self::getType()),
                                 'test_ldap_replicate', _sx('button', 'Test'),
                                 array('id'                => $ID,
                                       'ldap_replicate_id' => $ldap_replicate["id"]));
            echo "</td></tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);

         Html::closeForm();
         echo "</div>";
      }
   }


   /**
    * @since version 0.84
    *
    * @param $options array
   **/
   static function dropdownGroupSearchType(array $options) {

      $p['name']    = 'group_search_type';
      $p['value']   = 0;
      $p['display'] = true;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = self::getGroupSearchTypeName();
      return Dropdown::showFromArray($p['name'], $tab, $p);
   }


   /**
    * Get the possible value for contract alert
    *
    * @since version 0.83
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getGroupSearchTypeName($val=NULL) {

      $tmp[0] = __('In users');
      $tmp[1] = __('In groups');
      $tmp[2] = __('In users and groups');

      if (is_null($val)) {
         return $tmp;
      }
      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      return NOT_AVAILABLE;
   }


   function showFormGroupsConfig() {

      $ID = $this->getField('id');

      echo "<div class='center'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th class='center' colspan='4'>" . __('Belonging to groups') . "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Search type') . "</td><td>";
      self::dropdownGroupSearchType(array('value' => $this->fields["group_search_type"]));
      echo "</td>";
      echo "<td>" . __('User attribute containing its groups') . "</td>";
      echo "<td><input type='text' name='group_field' value='".$this->fields["group_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Filter to search in groups')."</td><td colspan='3'>";
      echo "<input type='text' name='group_condition' value='".$this->fields["group_condition"]."'
             size='100'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Group attribute containing its users') . "</td>";
      echo "<td><input type='text' name='group_member_field' value='".
                 $this->fields["group_member_field"]."'></td>";
      echo "<td>" . __('Use DN in the search') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_dn", $this->fields["use_dn"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   function showFormTestLDAP () {

      $ID = $this->getField('id');

      if ($ID > 0) {
         echo "<div class='center'>";
         echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>" . __('Test of connection to LDAP directory') . "</th></tr>";

         if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
            echo $_SESSION["LDAP_TEST_MESSAGE"];
            echo"</td></tr>";
            unset($_SESSION["LDAP_TEST_MESSAGE"]);
         }

         echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
         echo "<input type='submit' name='test_ldap' class='submit' value=\"".
                _sx('button','Test')."\">";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
   }


   function showFormUserConfig() {

      $ID = $this->getField('id');

      echo "<div class='center'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th class='center' colspan='4'>" . __('Binding to the LDAP directory') . "</th></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Surname') . "</td>";
      echo "<td><input type='text' name='realname_field' value='".
                 $this->fields["realname_field"]."'></td>";
      echo "<td>" . __('First name') . "</td>";
      echo "<td><input type='text' name='firstname_field' value='".
                 $this->fields["firstname_field"]."'></td></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Comments') . "</td>";
      echo "<td><input type='text' name='comment_field' value='".$this->fields["comment_field"]."'>";
      echo "</td>";
      echo "<td>" . __('Administrative number') . "</td>";
      echo "<td>";
      echo "<input type='text' name='registration_number_field' value='".
             $this->fields["registration_number_field"]."'>";
      echo "</td></tr>";


      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Email') . "</td>";
      echo "<td><input type='text' name='email1_field' value='".$this->fields["email1_field"]."'>";
      echo "</td>";
      echo "<td>" . sprintf(__('%1$s %2$s'),_n('Email','Emails',1), '2') . "</td>";
      echo "<td><input type='text' name='email2_field' value='".$this->fields["email2_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . sprintf(__('%1$s %2$s'),_n('Email','Emails',1),  '3') . "</td>";
      echo "<td><input type='text' name='email3_field' value='".$this->fields["email3_field"]."'>";
      echo "</td>";
      echo "<td>" . sprintf(__('%1$s %2$s'),_n('Email','Emails',1),  '4') . "</td>";
      echo "<td><input type='text' name='email4_field' value='".$this->fields["email4_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Phone') . "</td>";
      echo "<td><input type='text' name='phone_field'value='".$this->fields["phone_field"]."'>";
      echo "</td>";
      echo "<td>" .  __('Phone 2') . "</td>";
      echo "<td><input type='text' name='phone2_field'value='".$this->fields["phone2_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Mobile phone') . "</td>";
      echo "<td><input type='text' name='mobile_field'value='".$this->fields["mobile_field"]."'>";
      echo "</td>";
      echo "<td>" . _x('person','Title') . "</td>";
      echo "<td><input type='text' name='title_field' value='".$this->fields["title_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Category') . "</td>";
      echo "<td><input type='text' name='category_field' value='".
                 $this->fields["category_field"]."'></td>";
      echo "<td>" . __('Language') . "</td>";
      echo "<td><input type='text' name='language_field' value='".
                 $this->fields["language_field"]."'></td></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Picture') . "</td>";
      echo "<td><input type='text' name='picture_field' value='".
                 $this->fields["picture_field"]."'></td><td colspan='2'></td></tr>";


      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   function showFormEntityConfig() {

      $ID = $this->getField('id');

      echo "<div class='center'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th class='center' colspan='4'>". __('Import entities from LDAP directory').
           "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Attribute representing entity') . "</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='entity_field' value='".$this->fields["entity_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Search filter for entities') . "</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='entity_condition' value='".$this->fields["entity_condition"]."'
             size='100'></td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function getSearchOptions() {

      $tab                      = array();
      $tab['common']            = $this->getTypeName(1);

      $tab[1]['table']          = $this->getTable();
      $tab[1]['field']          = 'name';
      $tab[1]['name']           = __('Name');
      $tab[1]['datatype']       = 'itemlink';
      $tab[1]['massiveaction']  = false;

      $tab[2]['table']          = $this->getTable();
      $tab[2]['field']          = 'id';
      $tab[2]['name']           = __('ID');
      $tab[2]['datatype']       = 'number';
      $tab[2]['massiveaction']  = false;

      $tab[3]['table']          = $this->getTable();
      $tab[3]['field']          = 'host';
      $tab[3]['name']           = __('Server');
      $tab[3]['datatype']       = 'string';

      $tab[4]['table']          = $this->getTable();
      $tab[4]['field']          = 'port';
      $tab[4]['name']           = __('Port');
      $tab[4]['datatype']       = 'integer';

      $tab[5]['table']          = $this->getTable();
      $tab[5]['field']          = 'basedn';
      $tab[5]['name']           = __('BaseDN');
      $tab[5]['datatype']       = 'string';

      $tab[6]['table']          = $this->getTable();
      $tab[6]['field']          = 'condition';
      $tab[6]['name']           = __('Connection filter');
      $tab[6]['datatype']       = 'text';

      $tab[7]['table']          = $this->getTable();
      $tab[7]['field']          = 'is_default';
      $tab[7]['name']           = __('Default server');
      $tab[7]['datatype']       = 'bool';
      $tab[7]['massiveaction']  = false;

      $tab[8]['table']          = $this->getTable();
      $tab[8]['field']          = 'login_field';
      $tab[8]['name']           = __('Login field');
      $tab[8]['massiveaction']  = false;
      $tab[8]['datatype']       = 'string';

      $tab[9]['table']          = $this->getTable();
      $tab[9]['field']          = 'realname_field';
      $tab[9]['name']           = __('Surname');
      $tab[9]['massiveaction']  = false;
      $tab[9]['datatype']       = 'string';

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'firstname_field';
      $tab[10]['name']          = __('First name');
      $tab[10]['massiveaction'] = false;
      $tab[10]['datatype']      = 'string';

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'phone_field';
      $tab[11]['name']          = __('Phone');
      $tab[11]['massiveaction'] = false;
      $tab[11]['datatype']      = 'string';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'phone2_field';
      $tab[12]['name']          = __('Phone 2');
      $tab[12]['massiveaction'] = false;
      $tab[12]['datatype']      = 'string';

      $tab[13]['table']         = $this->getTable();
      $tab[13]['field']         = 'mobile_field';
      $tab[13]['name']          = __('Mobile phone');
      $tab[13]['massiveaction'] = false;
      $tab[13]['datatype']      = 'string';

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'title_field';
      $tab[14]['name']          = _x('person','Title');
      $tab[14]['massiveaction'] = false;
      $tab[14]['datatype']      = 'string';

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'category_field';
      $tab[15]['name']          = __('Category');
      $tab[15]['massiveaction'] = false;
      $tab[15]['datatype']      = 'string';

      $tab[16]['table']         = $this->getTable();
      $tab[16]['field']         = 'comment';
      $tab[16]['name']          = __('Comments');
      $tab[16]['datatype']      = 'text';

      $tab[17]['table']         = $this->getTable();
      $tab[17]['field']         = 'email1_field';
      $tab[17]['name']          = __('Email');
      $tab[17]['massiveaction'] = false;
      $tab[17]['datatype']      = 'string';

      $tab[25]['table']         = $this->getTable();
      $tab[25]['field']         = 'email2_field';
      $tab[25]['name']          = sprintf(__('%1$s %2$s'),_n('Email','Emails',1), '2');
      $tab[25]['massiveaction'] = false;
      $tab[25]['datatype']      = 'string';

      $tab[26]['table']         = $this->getTable();
      $tab[26]['field']         = 'email3_field';
      $tab[26]['name']          = sprintf(__('%1$s %2$s'),_n('Email','Emails',1), '3');
      $tab[26]['massiveaction'] = false;
      $tab[26]['datatype']      = 'string';

      $tab[27]['table']         = $this->getTable();
      $tab[27]['field']         = 'email4_field';
      $tab[27]['name']          = sprintf(__('%1$s %2$s'),_n('Email','Emails',1), '4');
      $tab[27]['massiveaction'] = false;
      $tab[27]['datatype']      = 'string';

      $tab[18]['table']         = $this->getTable();
      $tab[18]['field']         = 'use_dn';
      $tab[18]['name']          = __('Use DN in the search');
      $tab[18]['datatype']      = 'bool';
      $tab[18]['massiveaction'] = false;

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = __('Last update');
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[20]['table']         = $this->getTable();
      $tab[20]['field']         = 'language_field';
      $tab[20]['name']          = __('Language');
      $tab[20]['massiveaction'] = false;
      $tab[20]['datatype']      = 'string';

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'group_field';
      $tab[21]['name']          = __('User attribute containing its groups');
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'string';

      $tab[22]['table']         = $this->getTable();
      $tab[22]['field']         = 'group_condition';
      $tab[22]['name']          = __('Filter to search in groups');
      $tab[22]['massiveaction'] = false;
      $tab[22]['datatype']      = 'string';

      $tab[23]['table']         = $this->getTable();
      $tab[23]['field']         = 'group_member_field';
      $tab[23]['name']          = __('Group attribute containing its users');
      $tab[23]['massiveaction'] = false;
      $tab[23]['datatype']      = 'string';

      $tab[24]['table']         = $this->getTable();
      $tab[24]['field']         = 'group_search_type';
      $tab[24]['datatype']      = 'specific';
      $tab[24]['name']          = __('Search type');
      $tab[24]['massiveaction'] = false;


      $tab[30]['table']         = $this->getTable();
      $tab[30]['field']         = 'is_active';
      $tab[30]['name']          = __('Active');
      $tab[30]['datatype']      = 'bool';

      return $tab;
   }


   /**
    * @param $width
   **/
   function showSystemInformations($width) {

      // No need to translate, this part always display in english (for copy/paste to forum)

      $ldap_servers = self::getLdapServers();

      if (!empty($ldap_servers)) {
         echo "<tr class='tab_bg_2'><th>" . self::getTypeName(Session::getPluralNumber()) . "</th></tr>\n";
         echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
         foreach ($ldap_servers as $ID => $value) {
            $fields = array('Server'            => 'host',
                            'Port'              => 'port',
                            'BaseDN'            => 'basedn',
                            'Connection filter' => 'condition',
                            'RootDN'            => 'rootdn',
                            'Use TLS'           => 'use_tls');
            $msg   = '';
            $first = true;
            foreach ($fields as $label => $field) {
               $msg .= (!$first ? ', ' : '').
                        $label.': '.
                        ($value[$field]? '\''.$value[$field].'\'' : 'none');
               $first = false;
            }
            echo wordwrap($msg."\n", $width, "\n\t\t");
         }
         echo "\n</pre></td></tr>";
      }
   }


   /**
    * Get LDAP fields to sync to GLPI data from a glpi_authldaps array
    *
    * @param $authtype_array  array Authentication method config array (from table)
    *
    * @return array of "user table field name" => "config value"
   **/
   static function getSyncFields(array $authtype_array) {

      $ret    = array();
      $fields = array('login_field'               => 'name',
                      'email1_field'              => 'email1',
                      'email2_field'              => 'email2',
                      'email3_field'              => 'email3',
                      'email4_field'              => 'email4',
                      'realname_field'            => 'realname',
                      'firstname_field'           => 'firstname',
                      'phone_field'               => 'phone',
                      'phone2_field'              => 'phone2',
                      'mobile_field'              => 'mobile',
                      'comment_field'             => 'comment',
                      'title_field'               => 'usertitles_id',
                      'category_field'            => 'usercategories_id',
                      'language_field'            => 'language',
                      'registration_number_field' => 'registration_number',
                      'picture_field'             => 'picture');

      foreach ($fields as $key => $val) {
         if (isset($authtype_array[$key]) && !empty($authtype_array[$key])) {
            $ret[$val] = $authtype_array[$key];
         }
      }
      return $ret;
   }


   /** Display LDAP filter
    *
    * @param $target          target for the form
    * @param $users  boolean  for user ? (true by default)
    *
    * @return nothing
   **/
   static function displayLdapFilter($target, $users=true) {

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($_SESSION["ldap_server"]);

      if ($users) {
         $filter_name1 = "condition";
         $filter_var   = "ldap_filter";

      } else {
         $filter_var = "ldap_group_filter";
         switch ($config_ldap->fields["group_search_type"]) {
            case 0 :
               $filter_name1 = "condition";
               break;

            case 1 :
               $filter_name1 = "group_condition";
               break;

            case 2 :
               $filter_name1 = "group_condition";
               $filter_name2 = "condition";
               break;
         }
      }

      if (!isset($_SESSION[$filter_var]) || ($_SESSION[$filter_var] == '')) {
         $_SESSION[$filter_var] = $config_ldap->fields[$filter_name1];
      }

      echo "<div class='center'>";
      echo "<form method='post' action='$target'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . ($users?__('Search filter for users')
                                           :__('Filter to search in groups')) . "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='text' name='ldap_filter' value='". $_SESSION[$filter_var] ."' size='70'>";
      //Only display when looking for groups in users AND groups
      if (!$users
          && ($config_ldap->fields["group_search_type"] == 2)) {

         if (!isset($_SESSION["ldap_group_filter2"]) || ($_SESSION["ldap_group_filter2"] == '')) {
            $_SESSION["ldap_group_filter2"] = $config_ldap->fields[$filter_name2];
         }
         echo "</td></tr>";

         echo "<tr><th colspan='2'>" . __('Search filter for users') . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='text' name='ldap_filter2' value='".$_SESSION["ldap_group_filter2"]."'
                size='70'></td></tr>";
      }

      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input class=submit type='submit' name='change_ldap_filter' value=\"".
             _sx('button','Post')."\"></td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   /** Converts LDAP timestamps over to Unix timestamps
    *
    * @param $ldapstamp          LDAP timestamp
    * @param $ldap_time_offset   time offset (default 0)
    *
    * @return unix timestamp
   **/
   static function ldapStamp2UnixStamp($ldapstamp, $ldap_time_offset=0) {
      global $CFG_GLPI;

      $year    = substr($ldapstamp,0,4);
      $month   = substr($ldapstamp,4,2);
      $day     = substr($ldapstamp,6,2);
      $hour    = substr($ldapstamp,8,2);
      $minute  = substr($ldapstamp,10,2);
      $seconds = substr($ldapstamp,12,2);
      $stamp   = gmmktime($hour,$minute,$seconds,$month,$day,$year);
      $stamp  += $CFG_GLPI["time_offset"]-$ldap_time_offset;

      return $stamp;
   }


   /** Converts a Unix timestamp to an LDAP timestamps
    *
    * @param $date datetime
    *
    * @return ldap timestamp
   **/
   static function date2ldapTimeStamp($date) {
      return date("YmdHis",strtotime($date)).'.0Z';
   }


   /** Test a LDAP connection
    *
    * @param $auths_id     ID of the LDAP server
    * @param $replicate_id use a replicate if > 0 (default -1)
    *
    * @return  boolean connection succeeded ?
   **/
   static function testLDAPConnection($auths_id, $replicate_id=-1) {

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($auths_id);
      $ldap_users  = array();

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      //Test connection to a replicate
      if ($replicate_id != -1) {
         $replicate = new AuthLdapReplicate();
         $replicate->getFromDB($replicate_id);
         $host = $replicate->fields["host"];
         $port = $replicate->fields["port"];

      } else {
         //Test connection to a master ldap server
         $host = $config_ldap->fields['host'];
         $port = $config_ldap->fields['port'];
      }
      $ds = self::connectToServer($host, $port, $config_ldap->fields['rootdn'],
                                  Toolbox::decrypt($config_ldap->fields['rootdn_passwd'], GLPIKEY),
                                  $config_ldap->fields['use_tls'],
                                  $config_ldap->fields['deref_option']);
      if ($ds) {
         return true;
      }
      return false;
   }


   /**
    * @since version 0.84
    *
    * @param $limitexceeded   (false by default)
   **/
   static function displaySizeLimitWarning($limitexceeded=false) {
      global $CFG_GLPI;

      if ($limitexceeded) {
         echo "<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><th class='red'>";
         echo "<img class='center' src='".$CFG_GLPI["root_doc"]."/pics/warning.png'
                alt='".__('Warning')."'>&nbsp;".
             __('Warning: The request exceeds the limit of the directory. The results are only partial.');
         echo "</th></tr></table><div>";
      }
   }


   /** Show LDAP users to add or synchronise
    *
    * @return  nothing
   **/
   static function showLdapUsers() {
      global $CFG_GLPI;

      $values['order'] = 'DESC';
      $values['start'] = 0;

      foreach ($_SESSION['ldap_import'] as $option => $value) {
         $values[$option] = $value;
      }

      $rand          = mt_rand();
      $results       = array();
      $limitexceeded = false;
      $ldap_users    = self::getAllUsers($values, $results, $limitexceeded);

      if (is_array($ldap_users)) {
         $numrows = count($ldap_users);

         if ($numrows > 0) {
            self::displaySizeLimitWarning($limitexceeded);

            Html::printPager($values['start'], $numrows, $_SERVER['PHP_SELF'], '');

            // delete end
            array_splice($ldap_users, $values['start'] + $_SESSION['glpilist_limit']);
            // delete begin
            if ($values['start'] > 0) {
               array_splice($ldap_users, 0, $values['start']);
            }

            $form_action = '';
            $textbutton  = '';
            if ($_SESSION['ldap_import']['mode']) {
               $textbutton  = _x('button','Synchronize');
               $form_action = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'sync';
            } else {
               $textbutton  = _x('button','Import');
               $form_action = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'import';
            }

            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = array('num_displayed'    => min(count($ldap_users),
                                                        $_SESSION['glpilist_limit']),
                              'container'        => 'mass'.__CLASS__.$rand,
                              'specific_actions' => array($form_action => $textbutton));
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='10'>";
            Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
            $num = 0;
            echo Search::showHeaderItem(Search::HTML_OUTPUT, _n('User', 'Users', Session::getPluralNumber()), $num,
                                        $_SERVER['PHP_SELF'].
                                            "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            echo "<th>".__('Last update in the LDAP directory')."</th>";
            if ($_SESSION['ldap_import']['mode']) {
               echo "<th>".__('Last update in GLPI')."</th>";
            }
            echo "</tr>";

            foreach ($ldap_users as $userinfos) {
               $link = $user = $userinfos["user"];
               if (isset($userinfos['id']) && User::canView()) {
                  $link = "<a href='".Toolbox::getItemTypeFormURL('User').'?id='.$userinfos['id'].
                          "'>$user</a>";
               }
               if (isset($userinfos["timestamp"])) {
                  $stamp = $userinfos["timestamp"];
               } else {
                  $stamp = '';
               }

               if (isset($userinfos["date_sync"])) {
                  $date_sync = $userinfos["date_sync"];
               } else {
                  $date_sync = '';
               }

               echo "<tr class='tab_bg_2 center'>";
               //Need to use " instead of ' because it doesn't work with names with ' inside !
               echo "<td>";
               echo Html::getMassiveActionCheckBox(__CLASS__,$user);
               //echo "<input type='checkbox' name=\"item[" . $user . "]\" value='1'>";
               echo "</td>";
               echo "<td>" . $link . "</td>";

               if ($stamp != '') {
                  echo "<td>" .Html::convDateTime(date("Y-m-d H:i:s",$stamp)). "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               if ($_SESSION['ldap_import']['mode']) {
                  if ($date_sync != '') {
                     echo "<td>" . Html::convDateTime($date_sync) . "</td>";
                  } else {
                     echo "<td>&nbsp;</td>";
                  }
               }
               echo "</tr>";
            }
            echo "<tr>";
            echo "<th width='10'>";
            Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
            $num = 0;
            echo Search::showHeaderItem(Search::HTML_OUTPUT, _n('User', 'Users', Session::getPluralNumber()), $num,
                                        $_SERVER['PHP_SELF'].
                                                "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            echo "<th>".__('Last update in the LDAP directory')."</th>";
            if ($_SESSION['ldap_import']['mode']) {
               echo "<th>".__('Last update in GLPI')."</th>";
            }
            echo "</tr>";
            echo "</table>";

            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();

            Html::printPager($values['start'], $numrows, $_SERVER['PHP_SELF'], '');
         } else {
            echo "<div class='center b'>".
                  ($_SESSION['ldap_import']['mode']?__('No user to be synchronized')
                                                   :__('No user to be imported'))."</div>";
         }
      } else {
         echo "<div class='center b'>".
               ($_SESSION['ldap_import']['mode']?__('No user to be synchronized')
                                                :__('No user to be imported'))."</div>";
      }
   }


   static function searchForUsers($ds, $values, $filter, $attrs, &$limitexceeded, &$user_infos,
                                  &$ldap_users, $config_ldap) {

      //If paged results cannot be used (PHP < 5.4)
      $cookie   = ''; //Cookie used to perform query using pages
      $count    = 0;  //Store the number of results ldap_search

      do {
         if (self::isLdapPageSizeAvailable($config_ldap)) {
            ldap_control_paged_result($ds, $config_ldap->fields['pagesize'], true, $cookie);
         }
         $filter = Toolbox::unclean_cross_side_scripting_deep($filter);
         $sr     = @ldap_search($ds, $values['basedn'], $filter, $attrs);
         if ($sr) {
            if (in_array(ldap_errno($ds),array(4,11))) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $info = self::get_entries_clean($ds, $sr);
            if (in_array(ldap_errno($ds),array(4,11))) {
               $limitexceeded = true;
            }

            $count += $info['count'];
            //If page results are enabled and the number of results is greater than the maximum allowed
            //warn user that limit is exceeded and stop search
            if (self::isLdapPageSizeAvailable($config_ldap)
                && $config_ldap->fields['ldap_maxlimit']
                && ($count > $config_ldap->fields['ldap_maxlimit'])) {
               $limitexceeded = true;
               break;
            }
            for ($ligne = 0 ; $ligne < $info["count"] ; $ligne++) {
               //If ldap add
               if ($values['mode'] == self::ACTION_IMPORT) {
                  if (in_array($config_ldap->fields['login_field'], $info[$ligne])) {
                     $ldap_users[$info[$ligne][$config_ldap->fields['login_field']][0]]
                        = $info[$ligne][$config_ldap->fields['login_field']][0];
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["timestamp"]
                        = self::ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],
                                                    $config_ldap->fields['time_offset']);
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["user_dn"]
                        = $info[$ligne]['dn'];
                  }

               } else {
                  //If ldap synchronisation
                  if (in_array($config_ldap->fields['login_field'],$info[$ligne])) {
                     $ldap_users[$info[$ligne][$config_ldap->fields['login_field']][0]]
                        = self::ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],
                                                    $config_ldap->fields['time_offset']);
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["timestamp"]
                        = self::ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],
                                                    $config_ldap->fields['time_offset']);
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["user_dn"]
                        = $info[$ligne]['dn'];
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["name"]
                        = $info[$ligne][$config_ldap->fields['login_field']][0];
                  }
               }
            }
         } else {
            return false;
         }
         if (self::isLdapPageSizeAvailable($config_ldap)) {
            ldap_control_paged_result_response($ds, $sr, $cookie);
         }

      } while (($cookie !== null) && ($cookie != ''));
      return true;
   }


   /** Get the list of LDAP users to add/synchronize
    *
    * @param $options          array of possible options:
    *          - authldaps_id ID of the server to use
    *          - mode user to synchronise or add ?
    *          - ldap_filter ldap filter to use
    *          - basedn force basedn (default authldaps_id one)
    *          - order display order
    *          - begin_date begin date to time limit
    *          - end_date end date to time limit
    *          - script true if called by an external script
    * @param &$results         result stats
    * @param &$limitexceeded   limit exceeded exception
    *
    * @return  array of the user
   **/
   static function getAllUsers($options=array(), &$results, &$limitexceeded) {
      global $DB, $CFG_GLPI;

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($options['authldaps_id']);

      $values['order']        = 'DESC';
      $values['mode']         = self::ACTION_SYNCHRONIZE;
      $values['ldap_filter']  = '';
      $values['basedn']       = $config_ldap->fields['basedn'];
      $values['begin_date']   = NULL;
      $values['end_date']     = date('Y-m-d H:i:s', time()-DAY_TIMESTAMP);
      //Called by an external script or not
      $values['script']       = 0;
      foreach ($options as $option => $value) {
         // this test break mode detection - if ($value != '') {
         $values[$option] = $value;
         //}
      }

      $ldap_users    = array();
      $user_infos    = array();
      $limitexceeded = false;

      // we prevent some delay...
      if (!$res) {
         return false;
      }
      if ($values['order'] != "DESC") {
         $values['order'] = "ASC";
      }
      $ds = $config_ldap->connect();
      if ($ds) {
         //Search for ldap login AND modifyTimestamp,
         //which indicates the last update of the object in directory
         $attrs = array($config_ldap->fields['login_field'], "modifyTimestamp");

         // Try a search to find the DN
         if ($values['ldap_filter'] == '') {
            $filter = "(".$config_ldap->fields['login_field']."=*)";
         } else {
            $filter = $values['ldap_filter'];
         }

         if ($values['script'] && !empty($values['begin_date'])) {
            $filter_timestamp = self::addTimestampRestrictions($values['begin_date'],
                                                               $values['end_date']);
            $filter           = "(&$filter $filter_timestamp)";
         }
         $result = self::searchForUsers($ds, $values, $filter, $attrs, $limitexceeded,
                                        $user_infos, $ldap_users, $config_ldap);
         if (!$result) {
            return false;
         }
      } else {
         return false;
      }

      $glpi_users = array();
      $sql        = "SELECT *
                     FROM `glpi_users`";

      if ($values['mode'] != self::ACTION_IMPORT) {
         $sql .= " WHERE `authtype` IN (-1,".Auth::LDAP.",".Auth::EXTERNAL.", ". Auth::CAS.")
                         AND `auths_id` = '".$options['authldaps_id']."'";
      }
      $sql .= " ORDER BY `name` ".$values['order'];

      foreach ($DB->request($sql) as $user) {
         $tmpuser = new User();

         //Ldap add : fill the array with the login of the user
         if ($values['mode'] == self::ACTION_IMPORT) {
            $glpi_users[$user['name']] = $user['name'];
         } else {
            //Ldap synchronisation : look if the user exists in the directory
            //and compares the modifications dates (ldap and glpi db)
            $userfound = false;
            if (!empty($ldap_users[$user['name']])
                || ($userfound = self::dnExistsInLdap($user_infos, $user['user_dn']))) {
               // userfound seems that user dn is present in GLPI DB but do not correspond to an GLPI user
               // -> renaming case
               if ($userfound) {
                  //Get user in DB with this dn
                  $tmpuser->getFromDBByDn($user['user_dn']);
                  $glpi_users[] = array('id'        => $user['id'],
                                        'user'      => $userfound['name'],
                                        'timestamp' => $user_infos[$userfound['name']]['timestamp'],
                                        'date_sync' => $tmpuser->fields['date_sync'],
                                        'dn'        => $user['user_dn']);
               //If entry was modified or if script should synchronize all the users
               } else if (($values['action'] == self::ACTION_ALL)
                          || (($ldap_users[$user['name']] - strtotime($user['date_sync'])) > 0)) {
                  $glpi_users[] = array('id'        => $user['id'],
                                        'user'      => $user['name'],
                                        'timestamp' => $user_infos[$user['name']]['timestamp'],
                                        'date_sync' => $user['date_sync'],
                                        'dn'        => $user['user_dn']);
               }

            // Only manage deleted user if ALL (because of entity visibility in delegated mode)
             } else if (($values['action'] == self::ACTION_ALL)
                        && !$limitexceeded) {

                //If user is marked as coming from LDAP, but is not present in it anymore
                if (!$user['is_deleted']
                    && ($user['auths_id'] == $options['ldapservers_id'])) {
                   User::manageDeletedUserInLdap($user['id']);
                   $results[self::USER_DELETED_LDAP] ++;
                }
            }
         }
      }

      //If add, do the difference between ldap users and glpi users
      if ($values['mode'] == self::ACTION_IMPORT) {
         $diff    = array_diff_ukey($ldap_users,$glpi_users,'strcasecmp');
         $list    = array();
         $tmpuser = new User();

         foreach ($diff as $user) {
            //If user dn exists in DB, it means that user login field has changed
            if (!$tmpuser->getFromDBByDn(toolbox::addslashes_deep($user_infos[$user]["user_dn"]))) {
               $list[] = array("user"      => $user,
                               "timestamp" => $user_infos[$user]["timestamp"],
                               "date_sync" => Dropdown::EMPTY_VALUE);
            }
         }
         if ($values['order'] == 'DESC') {
            rsort($list);
         } else {
            sort($list);
         }

         return $list;
      }
      return $glpi_users;
   }


   /**
    * Check if a user DN exists in a ldap user search result
    *
    * @since version 0.84
    *
    * @param $ldap_infos   ldap user search result
    * @param $user_dn      user dn to look for
    *
    * @return false if the user dn doesn't exist, user ldap infos otherwise
   **/
   static function dnExistsInLdap($ldap_infos, $user_dn) {

      $found = false;
      foreach ($ldap_infos as $ldap_info) {
         if ($ldap_info['user_dn'] == $user_dn) {
            $found = $ldap_info;
            break;
         }
      }
      return $found;
   }


   /** Show LDAP groups to add or synchronise in an entity
    *
    * @param $target    target page for the form
    * @param $start     where to start the list
    * @param $sync      synchronise or add ? (default 0)
    * @param $filter    ldap filter to use (default '')
    * @param $filter2   second ldap filter to use (which case ?) (default '')
    * @param $entity    working entity
    * @param $order     display order (default DESC)
    *
    * @return  nothing
   **/
   static function showLdapGroups($target, $start, $sync=0, $filter='', $filter2='',
                                  $entity, $order='DESC') {

      echo "<br>";
      $limitexceeded = false;
      $ldap_groups   = self::getAllGroups($_SESSION["ldap_server"], $filter, $filter2, $entity,
                                          $limitexceeded, $order);

      if (is_array($ldap_groups)) {
         $numrows     = count($ldap_groups);
         $rand        = mt_rand();
         $colspan     = (Session::isMultiEntitiesMode()?5:4);
         if ($numrows > 0) {
            self::displaySizeLimitWarning($limitexceeded);
            $parameters = '';
            Html::printPager($start, $numrows, $target,$parameters);

            // delete end
            array_splice($ldap_groups, $start + $_SESSION['glpilist_limit']);
            // delete begin
            if ($start > 0) {
               array_splice($ldap_groups, 0, $start);
            }

            echo "<div class='center'>";
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = array('num_displayed'
                           => min($_SESSION['glpilist_limit'], count($ldap_groups)),
                       'container'
                           => 'mass'.__CLASS__.$rand,
                       'specific_actions'
                           => array(__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'import_group'
                                       => _sx('button','Import')),
                       'extraparams'
                           => array('massive_action_fields' => array('dn', 'ldap_import_type',
                                                                     'ldap_import_entities',
                                                                     'ldap_import_recursive')));
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='10'>";
            Html::showCheckbox(array('criterion' => array('tag_for_massive' => 'select_item')));
            echo "</th>";
            $header_num = 0;
            echo Search::showHeaderItem(Search::HTML_OUTPUT, __('Group'), $header_num,
                                        $target."?order=".($order=="DESC"?"ASC":"DESC"),
                                        1, $order);
            echo "<th>".__('Group DN')."</th>";
            echo "<th>".__('Destination entity')."</th>";
            if (Session::isMultiEntitiesMode()) {
               echo"<th>".__('Child entities')."</th>";
            }
            echo "</tr>";

            $dn_index = 0;
            foreach ($ldap_groups as $groupinfos) {
               $group       = $groupinfos["cn"];
               $group_dn    = $groupinfos["dn"];
               $search_type = $groupinfos["search_type"];

               echo "<tr class='tab_bg_2 center'>";
               echo "<td>";
               echo Html::hidden("dn[$dn_index]", array('value'                 => $group_dn,
                                                        'data-glpicore-ma-tags' => 'common'));
               echo Html::hidden("ldap_import_type[$dn_index]", array('value'                 => $search_type,
                                                                      'data-glpicore-ma-tags' => 'common'));
               Html::showMassiveActionCheckBox(__CLASS__, $dn_index,
                                               array('massive_tags' => 'select_item'));
               echo "</td>";
               echo "<td>" . $group . "</td>";
               echo "<td>" .$group_dn. "</td>";
               echo "<td>";
               Entity::dropdown(array('value'         => $entity,
                                      'name'          => "ldap_import_entities[$dn_index]",
                                      'specific_tags' => array('data-glpicore-ma-tags' => 'common')));
               echo "</td>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<td>";
                  Html::showCheckbox(array('name'          => "ldap_import_recursive[$dn_index]",
                                           'specific_tags' => array('data-glpicore-ma-tags' => 'common')));
                  echo "</td>";
               } else {
                  echo Html::hidden("ldap_import_recursive[$dn_index]", array('value'                 => 0,
                                                                              'data-glpicore-ma-tags' => 'common'));
               }
               echo "</tr>\n";
               $dn_index++;
            }

            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
            echo "</div>";
            Html::printPager($start, $numrows, $target, $parameters);

         } else {
            echo "<div class='center b'>" . __('No group to be imported') . "</div>";
         }
      } else {
         echo "<div class='center b'>" . __('No group to be imported') . "</div>";
      }
   }


   /** Get all LDAP groups from a ldap server which are not already in an entity
    *
    * @since version 0.84 new parameter $limitexceeded
    *
    * @param $auths_id        ID of the server to use
    * @param $filter          ldap filter to use
    * @param $filter2         second ldap filter to use if needed
    * @param $entity          entity to search
    * @param $limitexceeded
    * @param $order           order to use (default DESC)
    *
    * @return  array of the groups
   **/
   static function getAllGroups($auths_id, $filter, $filter2, $entity, &$limitexceeded,
                                $order='DESC') {
      global $DB;

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($auths_id);
      $infos       = array();
      $groups      = array();

      $ds = $config_ldap->connect();
      if ($ds) {
         switch ($config_ldap->fields["group_search_type"]) {
            case 0 :
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter, false, $infos,
                                                $limitexceeded);
               break;

            case 1 :
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter, true, $infos,
                                                $limitexceeded);
               break;

            case 2 :
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter ,true, $infos,
                                                $limitexceeded);
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter2, false, $infos,
                                                $limitexceeded);
               break;
         }
         if (!empty($infos)) {
            $glpi_groups = array();
            //Get all groups from GLPI DB for the current entity and the subentities
            $sql = "SELECT `name`
                    FROM `glpi_groups` ".
                    getEntitiesRestrictRequest("WHERE","glpi_groups");

            $res = $DB->query($sql);
            //If the group exists in DB -> unset it from the LDAP groups
            while ($group = $DB->fetch_assoc($res)) {
               $glpi_groups[$group["name"]] = 1;
            }
            $ligne = 0;

            foreach ($infos as $dn => $info) {
               if (!isset($glpi_groups[$info["cn"]])) {
                  $groups[$ligne]["dn"]          = $dn;
                  $groups[$ligne]["cn"]          = $info["cn"];
                  $groups[$ligne]["search_type"] = $info["search_type"];
                  $ligne++;
               }
            }
         }

         if ($order == 'DESC') {
            function local_cmp($b, $a) {
               return strcasecmp($a['cn'], $b['cn']);
            }

         } else {
            function local_cmp($a ,$b) {
               return strcasecmp($a['cn'], $b['cn']);
            }
         }
         usort($groups,'local_cmp');

      }
      return $groups;
   }


   /**
    * Get the group's cn by giving his DN
    *
    * @param $ldap_connection ldap connection to use
    * @param $group_dn        the group's dn
    *
    * @return the group cn
   **/
   static function getGroupCNByDn($ldap_connection, $group_dn) {

      $sr = @ ldap_read($ldap_connection, $group_dn, "objectClass=*", array("cn"));
      $v  = self::get_entries_clean($ldap_connection, $sr);
      if (!is_array($v) || (count($v) == 0) || empty($v[0]["cn"][0])) {
         return false;
      }
      return $v[0]["cn"][0];
   }


   /**
    * @since version 0.84 new parameter $limitexceeded
    *
    * @param $ldap_connection
    * @param $config_ldap
    * @param $filter
    * @param $search_in_groups         (true by default)
    * @param $groups             array
    * @param $limitexceeded
   **/
   static function getGroupsFromLDAP($ldap_connection, $config_ldap, $filter,
                                     $search_in_groups=true, $groups=array(),
                                     &$limitexceeded) {
      global $DB;

      //First look for groups in group objects
      $extra_attribute = ($search_in_groups?"cn":$config_ldap->fields["group_field"]);
      $attrs           = array("dn", $extra_attribute);

      if ($filter == '') {
         if ($search_in_groups) {
            $filter = (!empty($config_ldap->fields['group_condition'])
                       ? $config_ldap->fields['group_condition'] : "(objectclass=*)");
         } else {
            $filter = (!empty($config_ldap->fields['condition'])
                       ? $config_ldap->fields['condition'] : "(objectclass=*)");
         }
      }
      $cookie = '';
      $count  = 0;
      do {
         if (self::isLdapPageSizeAvailable($config_ldap)) {
            ldap_control_paged_result($ldap_connection, $config_ldap->fields['pagesize'],
                                      true, $cookie);
         }

         $filter = Toolbox::unclean_cross_side_scripting_deep($filter);
         $sr     = @ldap_search($ldap_connection, $config_ldap->fields['basedn'], $filter ,
                                $attrs);

         if ($sr) {
            if (in_array(ldap_errno($ldap_connection),array(4,11))) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $infos  = self::get_entries_clean($ldap_connection, $sr);
            if (in_array(ldap_errno($ldap_connection),array(4,11))) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $count += $infos['count'];
            //If page results are enabled and the number of results is greater than the maximum allowed
            //warn user that limit is exceeded and stop search
            if (self::isLdapPageSizeAvailable($config_ldap)
                && $config_ldap->fields['ldap_maxlimit']
                && ($count > $config_ldap->fields['ldap_maxlimit'])) {
               $limitexceeded = true;
               break;
            }

            for ($ligne=0 ; $ligne < $infos["count"] ; $ligne++) {
               if ($search_in_groups) {
                  // No cn : not a real object
                  if (isset($infos[$ligne]["cn"][0])) {
                     $cn                           = $infos[$ligne]["cn"][0];
                     $groups[$infos[$ligne]["dn"]] = (array("cn"          => $infos[$ligne]["cn"][0],
                                                            "search_type" => "groups"));
                  }

               } else {
                  if (isset($infos[$ligne][$extra_attribute])) {
                     if (($config_ldap->fields["group_field"] == 'dn')
                         || in_array('ou', $groups)) {
                        $dn = $infos[$ligne][$extra_attribute];
                        $ou = array();
                        for ($tmp=$dn ; count($tmptab=explode(',',$tmp,2))==2 ; $tmp=$tmptab[1]) {
                           $ou[] = $tmptab[1];
                        }

                        /// Search in DB for group with ldap_group_dn
                        if (($config_ldap->fields["group_field"] == 'dn')
                            && (count($ou) > 0)) {
                           $query = "SELECT `ldap_value`
                                     FROM `glpi_groups`
                                     WHERE `ldap_group_dn`
                                             IN ('".implode("', '",
                                                            Toolbox::addslashes_deep($ou))."')";

                           foreach ($DB->request($query) as $group) {
                              $groups[$group['ldap_value']] = array("cn"   => $group['ldap_value'],
                                                                    "search_type"
                                                                           => "users");
                           }
                        }

                     } else {
                        for ($ligne_extra=0 ; $ligne_extra<$infos[$ligne][$extra_attribute]["count"] ;
                             $ligne_extra++) {
                           $groups[$infos[$ligne][$extra_attribute][$ligne_extra]]
                              = array("cn"   => self::getGroupCNByDn($ldap_connection,
                                                   $infos[$ligne][$extra_attribute][$ligne_extra]),
                                      "search_type"
                                             => "users");
                        }
                     }
                  }
               }
            }
         }
         if (self::isLdapPageSizeAvailable($config_ldap)) {
            ldap_control_paged_result_response($ldap_connection, $sr, $cookie);
         }
      } while (($cookie !== null) && ($cookie != ''));

      return $groups;
   }


   /** Form to choose a ldap server
    *
    * @param   $target target page for the form
    *
    * @return  nothing
   **/
   static function ldapChooseDirectory($target) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_authldaps`
                WHERE `is_active` = '1'
                ORDER BY `name` ASC";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         //If only one server, do not show the choose ldap server window
         $ldap                    = $DB->fetch_assoc($result);
         $_SESSION["ldap_server"] = $ldap["id"];
         Html::redirect($_SERVER['PHP_SELF']);
      }

      echo "<div class='center'>";
      echo "<form action='$target' method=\"post\">";
      echo "<p>" . __('Please choose LDAP directory to import users from') . "</p>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='2'>" . __('LDAP directory choice') . "</th></tr>";

      //If more than one ldap server
      if ($DB->numrows($result) > 1) {
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         AuthLDAP::Dropdown(array('name'                => 'ldap_server',
                                  'display_emptychoice' => false,
                                  'comment'             => true,
                                  'condition'           => "`is_active`='1'"));
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input class='submit' type='submit' name='ldap_showusers' value=\"".
               _sx('button','Post') . "\"></td></tr>";

      } else {
         //No ldap server
         echo "<tr class='tab_bg_2'>".
              "<td class='center' colspan='2'>".__('No LDAP directory defined in GLPI')."</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   /** Import a user from a specific ldap server
    *
    * @param $params       array of parameters: method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
    * @param $action             synchoronize (true) or import (false)
    * @param $ldap_server        ID of the LDAP server to use
    * @param $display            display message information on redirect (false by default)
    *
    * @return  nothing
   **/
   static function ldapImportUserByServerId($params=array(), $action, $ldap_server,
                                            $display=false) {
      global $DB;
      static $conn_cache = array();

      $params      = Toolbox::stripslashes_deep($params);
      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($ldap_server);
      $ldap_users  = array();

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      $search_parameters = array();
      //Connect to the directory
      if (isset($conn_cache[$ldap_server])) {
         $ds = $conn_cache[$ldap_server];
      } else {
         $ds = $config_ldap->connect();
      }
      if ($ds) {
         $conn_cache[$ldap_server]                            = $ds;
         $search_parameters['method']                         = $params['method'];
         $search_parameters['fields'][self::IDENTIFIER_LOGIN] = $config_ldap->fields['login_field'];

         if ($params['method'] == self::IDENTIFIER_EMAIL) {
            $search_parameters['fields'][self::IDENTIFIER_EMAIL]
                                       = $config_ldap->fields['email1_field'];
         }

         //Get the user's dn & login
         $attribs = array('basedn'      => $config_ldap->fields['basedn'],
                          'login_field' => $search_parameters['fields'][$search_parameters['method']],
                          'search_parameters'
                                        => $search_parameters,
                          'user_params' => $params,
                          'condition'   => $config_ldap->fields['condition']);

         $infos = self::searchUserDn($ds,$attribs);

         if ($infos && $infos['dn']) {
            $user_dn = $infos['dn'];
            $login   = $infos[$config_ldap->fields['login_field']];
            $groups  = array();
            $user    = new User();

            //Get information from LDAP
            if ($user->getFromLDAP($ds, $config_ldap->fields, $user_dn, addslashes($login),
                                   ($action == self::ACTION_IMPORT))) {
               // Add the auth method
               // Force date sync
               $user->fields["date_sync"] = $_SESSION["glpi_currenttime"];
               $user->fields['is_deleted_ldap'] = 0;

               if ($action == self::ACTION_IMPORT) {
                  $user->fields["authtype"] = Auth::LDAP;
                  $user->fields["auths_id"] = $ldap_server;
                  //Save information in database !
                  $input = $user->fields;
                  // Display message after redirect
                  if ($display) {
                     $input['add'] = 1;
                  }

                  //clean picture  from input (picture managed in User::post_addItem)
                  unset($input['picture']);

                  $user->fields["id"] = $user->add($input);
                  return array('action' => self::USER_IMPORTED,
                               'id'     => $user->fields["id"]);
               }
               $input = $user->fields;
               //Get the ID by user name
               if (!($id = User::getIdByfield('name', $login))) {
                  //In case user id as changed : get id by dn
                  $id = User::getIdByfield('user_dn', $user_dn);
               }
               $input['id'] = $id;

               if ($display) {
                  $input['update'] = 1;
               }
               $user->update($input);
               return array('action' => self::USER_SYNCHRONIZED,
                            'id'     => $input['id']);
            }
            return false;

         }
         if ($action != self::ACTION_IMPORT) {
            $users_id = User::getIdByField('name', $params['value']);
            User::manageDeletedUserInLdap($users_id);
            return array('action' => self::USER_DELETED_LDAP,
                          'id'     => $users_id);
         }

      } else {
         return false;
      }
   }


   /** Converts an array of parameters into a query string to be appended to a URL.
    *
    * @param $group_dn        dn of the group to import
    * @param $options   array for
    *             - authldaps_id
    *             - entities_id where group must to be imported
    *             - is_recursive
    *
    * @return  nothing
   **/
   static function ldapImportGroup ($group_dn, $options=array()) {

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($options['authldaps_id']);
      $ldap_users  = array();
      $group_dn    = $group_dn;

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      //Connect to the directory
      $ds = $config_ldap->connect();
      if ($ds) {
         $group_infos = self::getGroupByDn($ds, stripslashes($group_dn));
         $group       = new Group();
         if ($options['type'] == "groups") {
            return $group->add(array("name"          => addslashes($group_infos["cn"][0]),
                                     "ldap_group_dn" => addslashes($group_infos["dn"]),
                                     "entities_id"   => $options['entities_id'],
                                     "is_recursive"  => $options['is_recursive']));
         }
         return $group->add(array("name"         => addslashes($group_infos["cn"][0]),
                                  "ldap_field"   => $config_ldap->fields["group_field"],
                                  "ldap_value"   => addslashes($group_infos["dn"]),
                                  "entities_id"  => $options['entities_id'],
                                  "is_recursive" => $options['is_recursive']));
      }
      return false;
   }


   /**
    * Open LDAP connexion to current serveur
   **/
   function connect() {

      return $this->connectToServer($this->fields['host'], $this->fields['port'],
                                    $this->fields['rootdn'],
                                    Toolbox::decrypt($this->fields['rootdn_passwd'], GLPIKEY),
                                    $this->fields['use_tls'],
                                    $this->fields['deref_option']);
   }


   /**
    * Connect to a LDAP serveur
    *
    * @param $host            LDAP host to connect
    * @param $port            port to use
    * @param $login           login to use (default '')
    * @param $password        password to use (default '')
    * @param $use_tls         use a tls connection ? (false by default)
    * @param $deref_options   deref options used
    *
    * @return link to the LDAP server : false if connection failed
   **/
   static function connectToServer($host, $port, $login="", $password="", $use_tls=false,
                                   $deref_options) {

      $ds = @ldap_connect($host, intval($port));
      if ($ds) {
         @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
         @ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
         @ldap_set_option($ds, LDAP_OPT_DEREF, $deref_options);
         if ($use_tls) {
            if (!@ldap_start_tls($ds)) {
               return false;
            }
         }
         // Auth bind
         if ($login != '') {
            $b = @ldap_bind($ds, $login, $password);
         } else { // Anonymous bind
            $b = @ldap_bind($ds);
         }
         if ($b) {
            return $ds;
         }
      }
      return false;
   }


   /**
    * Try to connect to a ldap server
    *
    * @param $ldap_method  ldap_method array to use
    * @param $login        User Login
    * @param $password     User Password
    *
    * @return link to the LDAP server : false if connection failed
   **/
   static function tryToConnectToServer($ldap_method, $login, $password) {

      $ds = self::connectToServer($ldap_method['host'], $ldap_method['port'],
                                  $ldap_method['rootdn'],
                                  Toolbox::decrypt($ldap_method['rootdn_passwd'], GLPIKEY),
                                  $ldap_method['use_tls'], $ldap_method['deref_option']);

      // Test with login and password of the user if exists
      if (!$ds
          && !empty($login)) {
         $ds = self::connectToServer($ldap_method['host'], $ldap_method['port'], $login,
                                     $password, $ldap_method['use_tls'],
                                     $ldap_method['deref_option']);
      }

      //If connection is not successfull on this directory, try replicates (if replicates exists)
      if (!$ds
          && ($ldap_method['id'] > 0)) {
         foreach (self::getAllReplicateForAMaster($ldap_method['id']) as $replicate) {
            $ds = self::connectToServer($replicate["host"], $replicate["port"],
                                        $ldap_method['rootdn'],
                                        Toolbox::decrypt($ldap_method['rootdn_passwd'], GLPIKEY),
                                        $ldap_method['use_tls'], $ldap_method['deref_option']);

            // Test with login and password of the user
            if (!$ds
                && !empty($login)) {
               $ds = self::connectToServer($replicate["host"], $replicate["port"], $login,
                                           $password, $ldap_method['use_tls'],
                                           $ldap_method['deref_option']);
            }
            if ($ds) {
               return $ds;
            }
         }
      }
      return $ds;
   }


   static function getLdapServers() {
      return getAllDatasFromTable('glpi_authldaps', '', false, '`is_default` DESC');
   }


   /**
    * Is the LDAP authentication used ?
    *
    * @return boolean
   **/
   static function useAuthLdap() {
      global $DB;

      //Get all the ldap directories
      $sql = "SELECT COUNT(*)
              FROM `glpi_authldaps`
              WHERE `is_active` = 1";
      $result = $DB->query($sql);

      if ($DB->result($result,0,0) > 0) {
         return true;
      }
      return false;
   }


   /**
    * Import a user from ldap
    * Check all the directories. When the user is found, then import it
    *
    * @param $options array containing condition:
    *                 array('name'=>'glpi') or array('email' => 'test at test.com')
   **/
   static function importUserFromServers($options=array()) {

      $auth   = new Auth();
      $params = array();
      if (isset($options['name'])) {
         $params['value']  = $options['name'];
         $params['method'] = self::IDENTIFIER_LOGIN;
      }
      if (isset($options['email'])) {
         $params['value']  = $options['email'];
         $params['method'] = self::IDENTIFIER_EMAIL;
      }

      $auth->user_present = $auth->userExists($options);

      //If the user does not exists
      if ($auth->user_present == 0) {
         $auth->getAuthMethods();
         $ldap_methods = $auth->authtypes["ldap"];
         $userid       = -1;

         foreach ($ldap_methods as $ldap_method) {
            if ($ldap_method['is_active']) {
               $result = self::ldapImportUserByServerId($params, 0, $ldap_method["id"], true);
               if ($result != false) {
                  return $result;
               }
            }
         }
         Session::addMessageAfterRedirect(__('User not found or several users found'), false, ERROR);

      } else {
         Session::addMessageAfterRedirect(__('Unable to add. The user already exist.'), false,
                                          ERROR);
      }
      return false;
   }


   /**
    * Authentify a user by checking a specific directory
    *
    * @param $auth         identification object
    * @param $login        user login
    * @param $password     user password
    * @param $ldap_method  ldap_method array to use
    * @param $user_dn      user LDAP DN if present
    *
    * @return identification object
   **/
   static function ldapAuth($auth, $login, $password, $ldap_method, $user_dn) {

      $oldlevel = error_reporting(0);
      $user_dn  = $auth->connection_ldap($ldap_method, $login, $password);
      error_reporting($oldlevel);

      $auth->auth_succeded            = false;
      $auth->extauth                  = 1;

      if ($user_dn) {
         $auth->auth_succeded            = true;
         //There's already an existing user in DB with the same DN but its login field has changed
         if ($auth->user->getFromDBbyDn(toolbox::addslashes_deep($user_dn))) {
            //Change user login
            $auth->user->fields['name'] = $login;
            $auth->user_present         = true;
         //The user is a new user
         } else {
            $auth->user_present = $auth->user->getFromDBbyName(addslashes($login));
         }
         $auth->user->getFromLDAP($auth->ldap_connection, $ldap_method, $user_dn, $login,
                                  !$auth->user_present);
         $auth->user->fields["authtype"] = Auth::LDAP;
         $auth->user->fields["auths_id"] = $ldap_method["id"];
      }
      return $auth;
   }


   /**
    * Try to authentify a user by checking all the directories
    *
    * @param $auth      identification object
    * @param $login     user login
    * @param $password  user password
    * @param $auths_id  auths_id already used for the user (default 0)
    * @param $user_dn   user LDAP DN if present (false by default)
    * @param $break     if user is not found in the first directory,
    *                   stop searching or try the following ones (true by default)
    *
    * @return identification object
   **/
   static function tryLdapAuth($auth, $login, $password, $auths_id=0, $user_dn=false, $break=true) {

      //If no specific source is given, test all ldap directories
      if ($auths_id <= 0) {
         foreach  ($auth->authtypes["ldap"] as $ldap_method) {
            if (!$auth->auth_succeded
                && $ldap_method['is_active']) {
               $auth = self::ldapAuth($auth, $login, $password, $ldap_method, $user_dn);
            } else {
               if ($break) {
                  break;
               }
            }
         }

      //Check if the ldap server indicated as the last good one still exists !
      } else if (array_key_exists($auths_id, $auth->authtypes["ldap"])) {
         //A specific ldap directory is given, test it and only this one !
         $auth = self::ldapAuth($auth, $login, $password, $auth->authtypes["ldap"][$auths_id],
                                $user_dn);
      }
      return $auth;
   }


   /**
    * Get dn for a user
    *
    * @param $ds              LDAP link
    * @param $options   array of possible options:
    *          - basedn : base dn used to search
    *          - login_field : attribute to store login
    *          - search_parameters array of search parameters
    *          - user_params  array of parameters : method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
    *          - condition : ldap condition used
    *
    * @return dn of the user, else false
   **/
   static function searchUserDn($ds, $options=array()) {

      $values['basedn']            = '';
      $values['login_field']       = '';
      $values['search_parameters'] = array();
      $values['user_params']       = '';
      $values['condition']         = '';
      $values['user_dn']           = false;

      foreach  ($options as $key => $value) {
         $values[$key] = $value;
      }

      //By default authentify users by login
      //$authentification_value = '';
      $login_attr      = $values['search_parameters']['fields'][self::IDENTIFIER_LOGIN];
      $ldap_parameters = array("dn");
      foreach ($values['search_parameters']['fields'] as $parameter) {
         $ldap_parameters[] = $parameter;
      }

      //First : if a user dn is provided, look for it in the directory
      //Before trying to find the user using his login_field
      if ($values['user_dn']) {
         $info = self::getUserByDn($ds, $values['user_dn'], $ldap_parameters);

         if ($info) {
            return array('dn'        => $values['user_dn'],
                         $login_attr => $info[$login_attr][0]);
         }
      }

      //$authentification_value = $values['user_params']['value'];
      // Tenter une recherche pour essayer de retrouver le DN
      $filter = "(".$values['login_field']."=".$values['user_params']['value'].")";

      if (!empty($values['condition'])) {
         $filter = "(& $filter ".$values['condition'].")";
      }

      $filter = Toolbox::unclean_cross_side_scripting_deep($filter);
      if ($result = @ldap_search($ds, $values['basedn'], $filter, $ldap_parameters)) {
         $info = self::get_entries_clean($ds, $result);

         if (is_array($info) && ($info['count'] == 1)) {
            return array('dn'        => $info[0]['dn'],
                         $login_attr => $info[0][$login_attr][0]);
         }
      }
      return false;
   }


   /**
    * Get an object from LDAP by giving his DN
    *
    * @param ds                  the active connection to the directory
    * @param condition           the LDAP filter to use for the search
    * @param $dn        string   DN of the object
    * @param attrs      array    of the attributes to retreive
    * @param $clean              (true by default)
   **/
   static function getObjectByDn($ds, $condition, $dn, $attrs=array(), $clean=true) {

      if ($result = @ ldap_read($ds, $dn, $condition, $attrs)) {
         if ($clean) {
            $info = self::get_entries_clean($ds, $result);
         } else $info = ldap_get_entries($ds, $result);

         if (is_array($info) && ($info['count'] == 1)) {
            return $info[0];
         }
      }

      return false;
   }


   /**
    * @param $ds
    * @param $user_dn
    * @param $attrs
    * @param $clean      (true by default)
   **/
   static function getUserByDn($ds, $user_dn, $attrs, $clean=true) {
      return self::getObjectByDn($ds, "objectClass=*", $user_dn, $attrs, $clean);
   }

   /**
    * Get infos for groups
    *
    * @param $ds        LDAP link
    * @param $group_dn  dn of the group
    *
    * @return group infos if found, else false
   **/
   static function getGroupByDn($ds, $group_dn) {
      return self::getObjectByDn($ds, "objectClass=*", $group_dn, array("cn"));
   }


   /**
    * @param $options   array
    * @param $delete          (false by default)
   **/
   static function manageValuesInSession($options=array(), $delete=false) {

      $fields = array('action', 'authldaps_id', 'basedn', 'begin_date', 'criterias',  'end_date',
                      'entities_id', 'interface', 'ldap_filter', 'mode');

      //If form accessed via modal, do not show expert mode link
      // Manage new value is set : entity or mode
      if (isset($options['entity'])
          || isset($options['mode'])) {
         if (isset($options['_in_modal']) && $options['_in_modal']) {
            //If coming form the helpdesk form : reset all criterias
            $_SESSION['ldap_import']['_in_modal']      = 1;
            $_SESSION['ldap_import']['no_expert_mode'] = 1;
            $_SESSION['ldap_import']['action']         = 'show';
            $_SESSION['ldap_import']['interface']      = self::SIMPLE_INTERFACE;
            $_SESSION['ldap_import']['mode']           = self::ACTION_IMPORT;
         } else {
            $_SESSION['ldap_import']['_in_modal']      = 0;
         }
      }

      if (!$delete) {

         if (!isset($_SESSION['ldap_import']['entities_id'])) {
            $options['entities_id'] = $_SESSION['glpiactive_entity'];
         }

         if (isset($options['toprocess'])) {
            $_SESSION['ldap_import']['action'] = 'process';
         }

         if (isset($options['change_directory'])) {
            $options['ldap_filter'] = '';
         }

         if (!isset($_SESSION['ldap_import']['authldaps_id'])) {
            $_SESSION['ldap_import']['authldaps_id'] = NOT_AVAILABLE;
         }

         if ((!Config::canUpdate()
              && !Entity::canUpdate())
             || (!isset($_SESSION['ldap_import']['interface']) && !isset($options['interface']))) {
            $options['interface'] = self::SIMPLE_INTERFACE;
         }

         foreach ($fields as $field) {
            if (isset($options[$field])) {
               $_SESSION['ldap_import'][$field] = $options[$field];
            }
         }
         if (isset($_SESSION['ldap_import']['begin_date'])
             && ($_SESSION['ldap_import']['begin_date'] == 'NULL')) {
            $_SESSION['ldap_import']['begin_date'] = '';
         }
         if (isset($_SESSION['ldap_import']['end_date'])
             && ($_SESSION['ldap_import']['end_date'] == 'NULL')) {
            $_SESSION['ldap_import']['end_date'] = '';
         }
         if (!isset($_SESSION['ldap_import']['criterias'])) {
            $_SESSION['ldap_import']['criterias'] = array();
         }

         $authldap = new self();
         //Filter computation
         if ($_SESSION['ldap_import']['interface'] == self::SIMPLE_INTERFACE) {
            $entity = new Entity();

            if ($entity->getFromDB($_SESSION['ldap_import']['entities_id'])
                && ($entity->getField('authldaps_id') > 0)) {

               $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);
               $_SESSION['ldap_import']['authldaps_id'] = $entity->getField('authldaps_id');
               $_SESSION['ldap_import']['basedn']       = $entity->getField('ldap_dn');

               // No dn specified in entity : use standard one
               if (empty($_SESSION['ldap_import']['basedn'])) {
                  $_SESSION['ldap_import']['basedn'] = $authldap->getField('basedn');
               }

               if ($entity->getField('entity_ldapfilter') != NOT_AVAILABLE) {
                  $_SESSION['ldap_import']['entity_filter']
                     = $entity->getField('entity_ldapfilter');
               }

            } else {
               $_SESSION['ldap_import']['authldaps_id'] = self::getDefault();

               if ($_SESSION['ldap_import']['authldaps_id'] > 0) {
                  $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);
                  $_SESSION['ldap_import']['basedn'] = $authldap->getField('basedn');
               }
            }

            if ($_SESSION['ldap_import']['authldaps_id'] > 0) {
               $_SESSION['ldap_import']['ldap_filter'] = self::buildLdapFilter($authldap);
            }

         } else {
            if ($_SESSION['ldap_import']['authldaps_id'] == NOT_AVAILABLE
                || !$_SESSION['ldap_import']['authldaps_id']) {

               $_SESSION['ldap_import']['authldaps_id'] = self::getDefault();

               if ($_SESSION['ldap_import']['authldaps_id'] > 0) {
                  $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);
                  $_SESSION['ldap_import']['basedn'] = $authldap->getField('basedn');
               }
            }
            if (!isset($_SESSION['ldap_import']['ldap_filter'])
                || $_SESSION['ldap_import']['ldap_filter'] == '') {

               $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);
               $_SESSION['ldap_import']['basedn']      = $authldap->getField('basedn');
               $_SESSION['ldap_import']['ldap_filter'] = self::buildLdapFilter($authldap);
            }
         }
      //Unset all values in session
      } else {
         unset($_SESSION['ldap_import']);
      }
   }


   /**
    * @param $authldap  AuthLDAP object
   **/
   static function showUserImportForm(AuthLDAP $authldap) {
      global $DB;

      //Get data related to entity (directory and ldap filter)
      $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);
      echo "<div class='center'>";

      echo "<form method='post' action='".$_SERVER['PHP_SELF']."'>";

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4' class='middle'><div class='relative'>";
      echo "<span>" .($_SESSION['ldap_import']['mode']?__('Synchronizing already imported users')
                                                      :__('Import new users'));

      // Expert interface allow user to override configuration.
      // If not coming from the ticket form, then give expert/simple link
      if ((Config::canUpdate()
           || Entity::canUpdate())
          && !isset($_SESSION['ldap_import']['no_expert_mode'])) {

         echo "</span>&nbsp;<span class='floatright'><a href='".$_SERVER['PHP_SELF']."?action=".
              $_SESSION['ldap_import']['action']."&amp;mode=".$_SESSION['ldap_import']['mode'];

         if ($_SESSION['ldap_import']['interface'] == self::SIMPLE_INTERFACE) {
            echo "&amp;interface=".self::EXPERT_INTERFACE."'>".__('Expert mode')."</a>";
         } else {
            echo "&amp;interface=".self::SIMPLE_INTERFACE."'>".__('Simple mode')."</a>";
         }
      } else {
         $_SESSION['ldap_import']['interface'] = self::SIMPLE_INTERFACE;
      }
      echo "</span></div>";
      echo "</th></tr>";

      switch ($_SESSION['ldap_import']['interface']) {
         case self::EXPERT_INTERFACE :
            //If more than one directory configured
            //Display dropdown ldap servers
            if (($_SESSION['ldap_import']['authldaps_id'] !=  NOT_AVAILABLE)
                && ($_SESSION['ldap_import']['authldaps_id'] > 0)) {

               if (self::getNumberOfServers() > 1) {
                  echo "<tr class='tab_bg_2'><td>".__('LDAP directory choice')."</td>";
                  echo "<td colspan='3'>";
                  self::dropdown(array('name'        => 'authldaps_id',
                                       'value'       => $_SESSION['ldap_import']['authldaps_id'],
                                       'condition'   => "`is_active` = '1'",
                                       'display_emptychoice'
                                                     => false));
                  echo "&nbsp;<input class='submit' type='submit' name='change_directory'
                        value=\""._sx('button','To change')."\">";
                  echo "</td></tr>";
               }

               echo "<tr class='tab_bg_2'><td>".__('BaseDN')."</td><td colspan='3'>";
               echo "<input type='text' name='basedn' value=\"".$_SESSION['ldap_import']['basedn'].
                     "\" size='90' ".(!$_SESSION['ldap_import']['basedn']?"disabled":"").">";
               echo "</td></tr>";

               echo "<tr class='tab_bg_2'><td>".__('Search filter for users')."</td><td colspan='3'>";
               echo "<input type='text' name='ldap_filter' value=\"".
                      $_SESSION['ldap_import']['ldap_filter']."\" size='90'>";
               echo "</td></tr>";
            }
            break;

         //case self::SIMPLE_INTERFACE :
         default :
            //If multi-entity mode and more than one entity visible
            //else no need to select entity
            if (Session::isMultiEntitiesMode()
                && (count($_SESSION['glpiactiveentities']) > 1)) {
               echo "<tr class='tab_bg_2'><td>".__('Select the desired entity')."</td>".
                    "<td colspan='3'>";
               Entity::dropdown(array('value'       => $_SESSION['ldap_import']['entities_id'],
                                      'entity'      => $_SESSION['glpiactiveentities'],
                                      'on_change'    => 'submit()'));
               echo "</td></tr>";
            } else {
               //Only one entity is active, store it
               echo "<tr><td><input type='hidden' name='entities_id' value='".
                              $_SESSION['glpiactive_entity']."'></td></tr>";
            }

            if ((isset($_SESSION['ldap_import']['begin_date'])
                 && !empty($_SESSION['ldap_import']['begin_date']))
                || (isset($_SESSION['ldap_import']['end_date'])
                    && !empty($_SESSION['ldap_import']['end_date']))) {
               $enabled = 1;
            } else {
               $enabled = 0;
            }
            Dropdown::showAdvanceDateRestrictionSwitch($enabled);

            echo "<table class='tab_cadre_fixe'>";

            if (($_SESSION['ldap_import']['authldaps_id'] !=  NOT_AVAILABLE)
                && ($_SESSION['ldap_import']['authldaps_id'] > 0)) {

               $field_counter = 0;
               $fields        = array('login_field'     => __('Login'),
                                      'email1_field'    => __('Email'),
                                      'email2_field'    => sprintf(__('%1$s %2$s'),
                                                                   _n('Email','Emails',1), '2'),
                                      'email3_field'    => sprintf(__('%1$s %2$s'),
                                                                   _n('Email','Emails',1), '3'),
                                      'email4_field'    => sprintf(__('%1$s %2$s'),
                                                                   _n('Email','Emails',1), '4'),
                                      'realname_field'  => __('Surname'),
                                      'firstname_field' => __('First name'),
                                      'phone_field'     => __('Phone'),
                                      'phone2_field'    => __('Phone 2'),
                                      'mobile_field'    => __('Mobile phone'),
                                      'title_field'     => _x('person','Title'),
                                      'category_field'  => __('Category'),
                                      'picture_field'   => __('Picture'));
               $available_fields = array();
               foreach ($fields as $field => $label) {
                  if (isset($authldap->fields[$field]) && ($authldap->fields[$field] != '')) {
                     $available_fields[$field] = $label;
                  }
               }
               echo "<tr><th colspan='4'>" . __('Search criteria for users') . "</th></tr>";
               foreach ($available_fields as $field => $label) {
                  if ($field_counter == 0) {
                     echo "<tr class='tab_bg_1'>";
                  }
                  echo "<td>$label</td><td>";
                  $field_counter++;
                  echo "<input type='text' name='criterias[$field]' value='".
                        (isset($_SESSION['ldap_import']['criterias'][$field])
                         ?$_SESSION['ldap_import']['criterias'][$field]:'')."'>";
                  echo "</td>";
                  if ($field_counter == 2) {
                     echo "</tr>";
                     $field_counter = 0;
                  }
               }
               if ($field_counter > 0) {
                  while ($field_counter < 2) {
                     echo "<td colspan='2'></td>";
                     $field_counter++;
                  }
                  $field_counter = 0;
                  echo "</tr>";
                }
            }
            break;
      }

      if (($_SESSION['ldap_import']['authldaps_id'] !=  NOT_AVAILABLE)
          && ($_SESSION['ldap_import']['authldaps_id'] > 0)) {

         if ($_SESSION['ldap_import']['authldaps_id']) {
            echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
            echo "<input class='submit' type='submit' name='search' value=\"".
                   _sx('button','Search')."\">";
            echo "</td></tr>";
         } else {
            echo "<tr class='tab_bg_2'><".
                 "td colspan='4' class='center'>".__('No directory selected')."</td></tr>";
         }

      } else {
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>".
                __('No directory associated to entity: impossible search')."</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   static function getNumberOfServers() {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_authldaps`
                WHERE `is_active` = '1'";
      $result = $DB->query($query);

      return $DB->result($result,0,'cpt');
   }


   /**
    * @param $authldap  AuthLDAP object
   **/
   static private function buildLdapFilter(AuthLdap $authldap) {
      //Build search filter
      $counter = 0;
      $filter  = '';

      if (!empty($_SESSION['ldap_import']['criterias'])
          && ($_SESSION['ldap_import']['interface'] == self::SIMPLE_INTERFACE)) {

         foreach ($_SESSION['ldap_import']['criterias'] as $criteria => $value) {
            if ($value != '') {
               $begin = 0;
               $end   = 0;
               if (($length = strlen($value)) > 0) {
                  if ($value[0] == '^') {
                     $begin = 1;
                  }
                  if ($value[$length-1] == '$') {
                     $end = 1;
                  }
               }
               if ($begin || $end) {
                  // no Toolbox::substr, to be consistent with strlen result
                  $value = substr($value, $begin, $length-$end-$begin);
               }
               $counter++;
               $filter .= '('.$authldap->fields[$criteria].'='.($begin?'':'*').$value.($end?'':'*').')';
             }
          }

      } else {
         $filter = "(".$authldap->getField("login_field")."=*)";
      }

      //If time restriction
      $begin_date = (isset($_SESSION['ldap_import']['begin_date'])
                     && !empty($_SESSION['ldap_import']['begin_date'])
                        ? $_SESSION['ldap_import']['begin_date'] : NULL);
      $end_date   = (isset($_SESSION['ldap_import']['end_date'])
                     && !empty($_SESSION['ldap_import']['end_date'])
                        ? $_SESSION['ldap_import']['end_date'] : NULL);
      $filter    .= self::addTimestampRestrictions($begin_date, $end_date);
      $ldap_condition = $authldap->getField('condition');
      //Add entity filter and filter filled in directory's configuration form
      return  "(&".(isset($_SESSION['ldap_import']['entity_filter'])
                    ?$_SESSION['ldap_import']['entity_filter']
                    :'')." $filter $ldap_condition)";
   }


   /**
    * @param $begin_date   datetime begin date to search (NULL if not take into account)
    * @param $end_date     datetime end date to search (NULL if not take into account)
   **/
   static function addTimestampRestrictions($begin_date, $end_date) {

      $condition = '';
      //If begin date
      if (!empty($begin_date)) {
         $stampvalue = self::date2ldapTimeStamp($begin_date);
         $condition .= "(modifyTimestamp> = ".$stampvalue.")";
      }
      //If end date
      if (!empty($end_date)) {
         $stampvalue = self::date2ldapTimeStamp($end_date);
         $condition .= "(modifyTimestamp <= ".$stampvalue.")";
      }
      return $condition;
   }


   /**
    * @param $authldap  AuthLDAP object
   **/
   static function searchUser(AuthLDAP $authldap) {

      if (self::connectToServer($authldap->getField('host'), $authldap->getField('port'),
                                $authldap->getField('rootdn'),
                                Toolbox::decrypt($authldap->getField('rootdn_passwd'), GLPIKEY),
                                $authldap->getField('use_tls'),
                                $authldap->getField('deref_option'))) {
         self::showLdapUsers();

      } else {
         echo "<div class='center b firstbloc'>".__('Unable to connect to the LDAP directory');
      }
   }


   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_authldaps', array('is_default' => 1)) as $data) {
         return $data['id'];
      }
      return 0;
   }


   function post_updateItem($history=1) {
      global $DB;

      if (in_array('is_default',$this->updates) && $this->input["is_default"]==1) {
         $query = "UPDATE `". $this->getTable()."`
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'";
         $DB->query($query);
      }
   }


   function post_addItem() {
      global $DB;

      if (isset($this->fields['is_default']) && $this->fields["is_default"]==1) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }
   }


   function prepareInputForAdd($input) {

      //If it's the first ldap directory then set it as the default directory
      if (!self::getNumberOfServers()) {
         $input['is_default'] = 1;
      }

      if (isset($input["rootdn_passwd"]) && !empty($input["rootdn_passwd"])) {
         $input["rootdn_passwd"] = Toolbox::encrypt(stripslashes($input["rootdn_passwd"]), GLPIKEY);
      }

      return $input;
   }


   /**
    * @param $value  (default 0)
   **/
   static function dropdownUserDeletedActions($value=0) {

      $options[0] = __('Preserve');
      $options[1] = __('Put in dustbin');
      $options[2] = __('Withdraw dynamic authorizations and groups');
      $options[3] = __('Disable');
      asort($options);
      return Dropdown::showFromArray('user_deleted_ldap', $options, array('value' => $value));
   }


   /**
    * Return all the ldap servers where email field is configured
    *
    * @return array of LDAP server's ID
   **/
   static function getServersWithImportByEmailActive() {
      global $DB;

      $ldaps = array();
      // Always get default first
      $query = "SELECT `id`
                FROM `glpi_authldaps`
                WHERE `is_active` = 1
                      AND (`email1_field` <> ''
                           OR `email2_field` <> ''
                           OR `email3_field` <> ''
                           OR `email4_field` <> '')
                ORDER BY `is_default` DESC";
      foreach ($DB->request($query) as $data) {
         $ldaps[] = $data['id'];
      }
      return $ldaps;
   }


   /**
    * @param $options  array
   **/
   static function showDateRestrictionForm($options=array()) {

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";

      $enabled = (isset($options['enabled'])?$options['enabled']:false);
      if (!$enabled) {
         echo "<td colspan='4' class='center'>";
         echo "<a href='#' onClick='activateRestriction()'>".__('Enable filtering by date')."</a>";
         echo "</td></tr>";
      }
      if ($enabled) {
         echo "<td>".__('View updated users')."</td>";
         echo "<td>".__('from')."</td>";
         echo "<td>";
         $begin_date = (isset($_SESSION['ldap_import']['begin_date'])
                           ?$_SESSION['ldap_import']['begin_date'] :'');
         Html::showDateTimeField("begin_date", array('value'    => $begin_date,
                                                     'timestep' => 1));
         echo "</td>";
         echo "<td>".__('to')."</td>";
         echo "<td>";
         $end_date = (isset($_SESSION['ldap_import']['end_date'])
                        ?$_SESSION['ldap_import']['end_date']
                        :date('Y-m-d H:i:s',time()-DAY_TIMESTAMP));
         Html::showDateTimeField("end_date", array('value'    => $end_date,
                                                   'timestep' => 1));
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
         echo "<a href='#' onClick='deactivateRestriction()'>".__('Disable filtering by date')."</a>";
         echo "</td></tr>";
      }
      echo "</table>";
   }


   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this, 'LDAP_SERVER');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate
          && $item->can($item->getField('id'),READ)) {
         $ong     = array();
         $ong[1]  = _sx('button','Test');                     // test connexion
         $ong[2]  = _n('User', 'Users', Session::getPluralNumber());
         $ong[3]  = _n('Group', 'Groups', Session::getPluralNumber());
/// TODO clean fields entity_XXX if not used
//          $ong[4]  = __('Entity');                  // params for entity config
         $ong[5]  = __('Advanced information');   // params for entity advanced config
         $ong[6]  = _n('Replicate', 'Replicates', Session::getPluralNumber());

         return $ong;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($tabnum) {
         case 1 :
            $item->showFormTestLDAP();
            break;

         case 2 :
            $item->showFormUserConfig();
            break;

         case 3 :
            $item->showFormGroupsConfig();
            break;

         case 4 :
            $item->showFormEntityConfig();
            break;

         case 5 :
            $item->showFormAdvancedConfig();
            break;

         case 6 :
            $item->showFormReplicatesConfig();
            break;
      }
      return true;
   }


   /**
    * Get ldap query results and clean them at the same time
    *
    * @param link    the directory connection
    * @param result  the query results
    *
    * @return an array which contains ldap query results
   **/
   static function get_entries_clean($link, $result) {
      return Toolbox::clean_cross_side_scripting_deep(ldap_get_entries($link, $result));
   }


   /**
    * Get all replicate servers for a master one
    *
    * @param $master_id : master ldap server ID
    *
    * @return array of the replicate servers
   **/
   static function getAllReplicateForAMaster($master_id) {
      global $DB;

      $replicates = array();
      $query = "SELECT `id`, `host`, `port`
                FROM `glpi_authldapreplicates`
                WHERE `authldaps_id` = '$master_id'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         while ($replicate = $DB->fetch_assoc($result)) {
            $replicates[] = array("id"   => $replicate["id"],
                                  "host" => $replicate["host"],
                                  "port" => $replicate["port"]);
         }
      }
      return $replicates;
   }

   /**
    *
    * Check if ldap results can be paged or not
    * This functionnality is available for PHP 5.4 and higer
    * @since 0.84
    * return true if maxPageSize can be used, false otherwise
    */
   static function isLdapPageSizeAvailable($config_ldap, $check_config_value = true) {
      return ((!$check_config_value
               || ($check_config_value && $config_ldap->fields['can_support_pagesize']))
                  && function_exists('ldap_control_paged_result')
                     && function_exists('ldap_control_paged_result_response'));
   }
}
?>
