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
 *  Class used to manage Auth LDAP config
 */
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

   const GROUP_SEARCH_USER    = 0;
   const GROUP_SEARCH_GROUP   = 1;
   const GROUP_SEARCH_BOTH    = 2;

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';

   //connection caching stuff
   static $conn_cache = [];

   static function getTypeName($nb = 0) {
      return _n('LDAP directory', 'LDAP directories', $nb);
   }

   static function canCreate() {
      return static::canUpdate();
   }

   static function canPurge() {
      return static::canUpdate();
   }

   function post_getEmpty() {

      $this->fields['port']                        = '389';
      $this->fields['condition']                   = '';
      $this->fields['login_field']                 = 'uid';
      $this->fields['sync_field']                  = null;
      $this->fields['use_tls']                     = 0;
      $this->fields['group_field']                 = '';
      $this->fields['group_condition']             = '';
      $this->fields['group_search_type']           = self::GROUP_SEARCH_USER;
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

   static public function unsetUndisclosedFields(&$fields) {
      unset($fields['rootdn_passwd']);
   }

   /**
    * Preconfig datas for standard system
    *
    * @param string $type type of standard system : AD
    *
    * @return void
    */
   function preconfig($type) {

      switch ($type) {
         case 'AD' :
            $this->fields['port']                      = "389";
            $this->fields['condition']
               = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['login_field']               = 'samaccountname';
            $this->fields['sync_field']                = 'objectguid';
            $this->fields['use_tls']                   = 0;
            $this->fields['group_field']               = 'memberof';
            $this->fields['group_condition']
               = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['group_search_type']         = self::GROUP_SEARCH_USER;
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
            $this->fields['use_dn']                    = 1;
            $this->fields['can_support_pagesize']      = 1;
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
            if (preg_match('/_field$/', $key)) {
               $input[$key] = Toolbox::strtolower($val);
            }
         }
      }

      //do not permit to override sync_field
      if ($this->isSyncFieldEnabled()
         && isset($input['sync_field'])
         && $this->isSyncFieldUsed()
      ) {
         if ($input['sync_field'] == $this->fields['sync_field']) {
            unset($input['sync_field']);
         } else {
            Session::addMessageAfterRedirect(
               __('Synchronization field cannot be changed once in use.'),
               false,
               ERROR
            );
            return false;
         };
      }
      return $input;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'group_search_type' :
            return self::getGroupSearchTypeName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
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

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
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
                  $options      = ['authldaps_id' => $_SESSION['ldap_server'],
                                        'entities_id'  => $entity,
                                        'is_recursive' => $is_recursive,
                                        'type'         => $input['ldap_import_type'][$id]];
                  if (AuthLdap::ldapImportGroup($group_dn, $options)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION, $group_dn));
                  }
               }
               // Clean history as id does not correspond to group
               $_SESSION['glpimassiveactionselected'] = [];
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
               if (AuthLdap::ldapImportUserByServerId(['method' => AuthLDAP::IDENTIFIER_LOGIN,
                                                       'value'  => $id],
                                                      $_SESSION['ldap_import']['mode'],
                                                      $_SESSION['ldap_import']['authldaps_id'],
                                                      true)) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } else {
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
    * @param integer $ID      ID of the item
    * @param array   $options Options
    *     - target for the form
    *
    * @return void (display)
    */
   function showForm($ID, $options = []) {

      if (!Config::canUpdate()) {
         return false;
      }
      if (empty($ID)) {
         $this->getEmpty();
         if (isset($options['preconfig'])) {
            $this->preconfig($options['preconfig']);
         }
      } else {
         $this->getFromDB($ID);
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
         echo "<tr class='tab_bg_1'><td><label for='name'>" . __('Name') . "</label></td>";
         echo "<td><input type='text' id='name' name='name' value='". $this->fields["name"] ."'></td>";
         if ($ID > 0) {
            echo "<td>".__('Last update')."</td><td>".Html::convDateTime($this->fields["date_mod"]);
         } else {
            echo "<td colspan='2'>&nbsp;";
         }
         echo "</td></tr>";

         $defaultrand = mt_rand();
         echo "<tr class='tab_bg_1'><td><label for='dropdown_is_default$defaultrand'>" . __('Default server') . "</label></td>";
         echo "<td>";
         Dropdown::showYesNo('is_default', $this->fields['is_default'], -1, ['rand' => $defaultrand]);
         echo "</td>";
         $activerand = mt_rand();
         echo "<td><label for='dropdown_is_active$activerand'>" . __('Active'). "</label></td>";
         echo "<td>";
         Dropdown::showYesNo('is_active', $this->fields['is_active'], -1, ['rand' => $activerand]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='host'>" . __('Server') . "</label></td>";
         echo "<td><input type='text' id='host' name='host' value='" . $this->fields["host"] . "'></td>";
         echo "<td><label for='port'>" . __('Port (default=389)') . "</label></td>";
         echo "<td><input id='port' type='text' id='port' name='port' value='".$this->fields["port"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='condition'>" . __('Connection filter') . "</label></td>";
         echo "<td colspan='3'>";
         echo "<textarea cols='100' rows='1' id='condition' name='condition'>".$this->fields["condition"];
         echo "</textarea>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='basedn'>" . __('BaseDN') . "</label></td>";
         echo "<td colspan='3'>";
         echo "<input type='text' id='basedn' name='basedn' size='100' value=\"".$this->fields["basedn"]."\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='rootdn'>" . __('RootDN (for non anonymous binds)') . "</label></td>";
         echo "<td colspan='3'><input type='text' name='rootdn' id='rootdn' size='100' value=\"".
                $this->fields["rootdn"]."\">";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td><label for='rootdn_passwd'>" .
            __('Password (for non-anonymous binds)') . "</label></td>";
         echo "<td><input type='password' id='rootdn_passwd' name='rootdn_passwd' value='' autocomplete='off'>";
         if ($ID) {
            echo "<input type='checkbox' name='_blank_passwd' id='_blank_passwd'>&nbsp;"
               . "<label for='_blank_passwd'>" . __('Clear') . "</label>";
         }
         echo "</td>";
         echo "<td rowspan='3'><label for='comment'>".__('Comments')."</label></td>";
         echo "<td rowspan='3' class='middle'>";
         echo "<textarea cols='40' rows='4' name='comment' id='comment'>".$this->fields["comment"]."</textarea>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='login_field'>" . __('Login field') . "</label></td>";
         echo "<td><input type='text' id='login_field' name='login_field' value='".$this->fields["login_field"]."'>";
         echo "</td></tr>";

         $info_message = __s('Synchronization field cannot be changed once in use.');
         echo "<tr class='tab_bg_1'>";
         echo "<td><label for='sync_field'>" . __('Synchronization field') . "<i class='pointer fa fa-info' title='$info_message'></i></td>";
         echo "<td><input type='text' id='sync_field' name='sync_field' value='{$this->fields["sync_field"]}' title='$info_message'";
         if ($this->isSyncFieldEnabled() && $this->isSyncFieldUsed()) {
            echo " disabled='disabled'";
         }
         echo ">";
         echo "</td></tr>";

         //Fill fields when using preconfiguration models
         if (!$ID) {
            $hidden_fields = ['comment_field', 'condition', 'email1_field', 'email2_field',
                                   'email3_field', 'email4_field', 'entity_condition',
                                   'entity_field', 'firstname_field', 'group_condition',
                                   'group_field', 'group_member_field', 'group_search_type',
                                   'mobile_field', 'phone_field', 'phone2_field', 'port',
                                   'realname_field', 'registration_number_field', 'title_field',
                                   'use_dn', 'use_tls'];

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
         echo "<p class='red'>".sprintf(__('%s extension is missing'), 'LDAP')."</p>";
         echo "<p>".__('Impossible to use LDAP as external source of connection')."</p>".
              "</td></tr></table>";

         echo "<p><strong>".GLPINetwork::getErrorMessage()."</strong></p>";
         echo "</div>";

      }
   }

   /**
    * Show advanced config form
    *
    * @return void
    */
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
         Dropdown::showNumber("pagesize", ['value' => $this->fields['pagesize'],
                                                'min'   => 100,
                                                'max'   => 100000,
                                                'step'  => 100]);
         echo"</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Maximum number of results') . "</td><td>";
         Dropdown::showNumber('ldap_maxlimit', ['value' => $this->fields['ldap_maxlimit'],
                                                     'min'   => 100,
                                                     'max'   => 999999,
                                                     'step'  => 100,
                                                     'toadd' => [0 => __('Unlimited')]]);
         echo "</td><td colspan='2'></td></tr>";

      } else {
         $hidden .= "<input type='hidden' name='can_support_pagesize' value='0'>";
         $hidden .= "<input type='hidden' name='pagesize' value='0'>";
         $hidden .= "<input type='hidden' name='ldap_maxlimit' value='0'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('How LDAP aliases should be handled') . "</td><td colspan='4'>";
      $alias_options = [
         LDAP_DEREF_NEVER     => __('Never dereferenced (default)'),
         LDAP_DEREF_ALWAYS    => __('Always dereferenced'),
         LDAP_DEREF_SEARCHING => __('Dereferenced during the search (but not when locating)'),
         LDAP_DEREF_FINDING   => __('Dereferenced when locating (not during the search)'),
      ];
      Dropdown::showFromArray("deref_option", $alias_options,
                              ['value' => $this->fields["deref_option"]]);
      echo"</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Domain name used by inventory tool for link the user')."</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this, "inventory_domain", ['size' => 100]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo $hidden;
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();
      echo "</div>";

   }

   /**
    * Show config replicates form
    *
    * @var DBmysql $DB
    *
    * @return void
    */
   function showFormReplicatesConfig() {
      global $DB;

      $ID     = $this->getField('id');
      $target = $this->getFormURL();
      $rand   = mt_rand();

      AuthLdapReplicate::addNewReplicateForm($target, $ID);

      $iterator = $DB->request([
         'FROM'   => 'glpi_authldapreplicates',
         'WHERE'  => [
            'authldaps_id' => $ID
         ],
         'ORDER'  => ['name']
      ]);

      if (($nb = count($iterator)) > 0) {
         echo "<br>";

         echo "<div class='center'>";
         Html::openMassiveActionsForm('massAuthLdapReplicate'.$rand);
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $nb),
                                      'container'     => 'massAuthLdapReplicate'.$rand];
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

         while ($ldap_replicate = $iterator->next()) {
            echo "<tr class='tab_bg_1'><td class='center' width='10'>";
            Html::showMassiveActionCheckBox('AuthLdapReplicate', $ldap_replicate["id"]);
            echo "</td>";
            echo "<td class='center'>" . $ldap_replicate["name"] . "</td>";
            echo "<td class='center'>".sprintf(__('%1$s: %2$s'), $ldap_replicate["host"],
                                               $ldap_replicate["port"]);
            echo "</td>";
            echo "<td class='center'>";
            Html::showSimpleForm(static::getFormURL(),
                                 'test_ldap_replicate', _sx('button', 'Test'),
                                 ['id'                => $ID,
                                       'ldap_replicate_id' => $ldap_replicate["id"]]);
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
    * Build a dropdown
    *
    * @since 0.84
    *
    * @param array $options Options
    *
    * @return string
    */
   static function dropdownGroupSearchType(array $options) {

      $p = [
         'name'    => 'group_search_type',
         'value'   => self::GROUP_SEARCH_USER,
         'display' => true,
      ];

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
    * @since 0.83
    *
    * @param integer $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array|string
    */
   static function getGroupSearchTypeName($val = null) {
      $tmp = [
         self::GROUP_SEARCH_USER    => __('In users'),
         self::GROUP_SEARCH_GROUP   => __('In groups'),
         self::GROUP_SEARCH_BOTH    => __('In users and groups')
      ];

      if (is_null($val)) {
         return $tmp;
      }
      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      return NOT_AVAILABLE;
   }

   /**
    * Show group config form
    *
    * @return void
    */
   function showFormGroupsConfig() {

      $ID = $this->getField('id');

      echo "<div class='center'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th class='center' colspan='4'>" . __('Belonging to groups') . "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Search type') . "</td><td>";
      self::dropdownGroupSearchType(['value' => $this->fields["group_search_type"]]);
      echo "</td>";
      echo "<td>" . __('User attribute containing its groups') . "</td>";
      echo "<td><input type='text' name='group_field' value='".$this->fields["group_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Filter to search in groups')."</td><td colspan='3'>";
      echo "<textarea cols='100' rows='1' name='group_condition'>".$this->fields["group_condition"];
      echo "</textarea>";
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

   /**
    * Show ldap test form
    *
    * @return void
    */
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
                _sx('button', 'Test')."\">";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
   }

   /**
    * Show user config form
    *
    * @return void
    */
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
      echo "<td>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '2') . "</td>";
      echo "<td><input type='text' name='email2_field' value='".$this->fields["email2_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '3') . "</td>";
      echo "<td><input type='text' name='email3_field' value='".$this->fields["email3_field"]."'>";
      echo "</td>";
      echo "<td>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '4') . "</td>";
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
      echo "<td>" . _x('person', 'Title') . "</td>";
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
                 $this->fields["picture_field"]."'></td>";
      echo "<td>" . __('Location') . "</td>";
      echo "<td><input type='text' name='location_field' value='".$this->fields["location_field"]."'>";
      echo "</td></tr>";

      echo "<tr><td colspan=4 class='center green'>".__('You can use a field name or an expression using various %{fieldname}').
           " <br />".__('Example for location: %{city} > %{roomnumber}')."</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

   /**
    * Show entity config form
    *
    * @return void
    */
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
         'name'               => $this->getTypeName(1)
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
         'field'              => 'port',
         'name'               => __('Port'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'basedn',
         'name'               => __('BaseDN'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'condition',
         'name'               => __('Connection filter'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_default',
         'name'               => __('Default server'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'login_field',
         'name'               => __('Login field'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'realname_field',
         'name'               => __('Surname'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'firstname_field',
         'name'               => __('First name'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'phone_field',
         'name'               => __('Phone'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'phone2_field',
         'name'               => __('Phone 2'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'mobile_field',
         'name'               => __('Mobile phone'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'title_field',
         'name'               => _x('person', 'Title'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'category_field',
         'name'               => __('Category'),
         'massiveaction'      => false,
         'datatype'           => 'string'
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
         'field'              => 'email1_field',
         'name'               => __('Email'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '25',
         'table'              => $this->getTable(),
         'field'              => 'email2_field',
         'name'               => sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '2'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => $this->getTable(),
         'field'              => 'email3_field',
         'name'               => sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '3'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => $this->getTable(),
         'field'              => 'email4_field',
         'name'               => sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '4'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'use_dn',
         'name'               => __('Use DN in the search'),
         'datatype'           => 'bool',
         'massiveaction'      => false
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
         'table'              => $this->getTable(),
         'field'              => 'language_field',
         'name'               => __('Language'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'group_field',
         'name'               => __('User attribute containing its groups'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => $this->getTable(),
         'field'              => 'group_condition',
         'name'               => __('Filter to search in groups'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'group_member_field',
         'name'               => __('Group attribute containing its users'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => $this->getTable(),
         'field'              => 'group_search_type',
         'datatype'           => 'specific',
         'name'               => __('Search type'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => $this->getTable(),
         'field'              => 'sync_field',
         'name'               => __('Synchronization field'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      return $tab;
   }

   /**
    * Show system informations form
    *
    * @param integer $width The number of characters at which the string will be wrapped.
    *
    * @return void
    */
   function showSystemInformations($width) {

      // No need to translate, this part always display in english (for copy/paste to forum)

      $ldap_servers = self::getLdapServers();

      if (!empty($ldap_servers)) {
         echo "<tr class='tab_bg_2'><th>" . self::getTypeName(Session::getPluralNumber()) . "</th></tr>\n";
         echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
         foreach ($ldap_servers as $value) {
            $fields = ['Server'            => 'host',
                            'Port'              => 'port',
                            'BaseDN'            => 'basedn',
                            'Connection filter' => 'condition',
                            'RootDN'            => 'rootdn',
                            'Use TLS'           => 'use_tls'];
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
    * @param array $authtype_array Authentication method config array (from table)
    *
    * @return array of "user table field name" => "config value"
    */
   static function getSyncFields(array $authtype_array) {

      $ret    = [];
      $fields = ['login_field'               => 'name',
                  'email1_field'              => 'email1',
                  'email2_field'              => 'email2',
                  'email3_field'              => 'email3',
                  'email4_field'              => 'email4',
                  'realname_field'            => 'realname',
                  'firstname_field'           => 'firstname',
                  'phone_field'               => 'phone',
                  'phone2_field'              => 'phone2',
                  'mobile_field'              => 'mobile',
                  'location_field'            => 'locations_id',
                  'comment_field'             => 'comment',
                  'title_field'               => 'usertitles_id',
                  'category_field'            => 'usercategories_id',
                  'language_field'            => 'language',
                  'registration_number_field' => 'registration_number',
                  'picture_field'             => 'picture',
                  'sync_field'                => 'sync_field'];

      foreach ($fields as $key => $val) {
         if (isset($authtype_array[$key]) && !empty($authtype_array[$key])) {
            $ret[$val] = $authtype_array[$key];
         }
      }
      return $ret;
   }


   /**
    * Display LDAP filter
    *
    * @param string  $target target for the form
    * @param boolean $users  for user? (true by default)
    *
    * @return void
    */
   static function displayLdapFilter($target, $users = true) {

      $config_ldap = new self();
      if (!isset($_SESSION['ldap_server'])) {
         throw new \RuntimeException('LDAP server must be set!');
      }
      $config_ldap->getFromDB($_SESSION['ldap_server']);

      if ($users) {
         $filter_name1 = "condition";
         $filter_var   = "ldap_filter";

      } else {
         $filter_var = "ldap_group_filter";
         switch ($config_ldap->fields["group_search_type"]) {
            case self::GROUP_SEARCH_USER:
               $filter_name1 = "condition";
               break;

            case self::GROUP_SEARCH_GROUP:
               $filter_name1 = "group_condition";
               break;

            case self::GROUP_SEARCH_BOTH:
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
          && ($config_ldap->fields["group_search_type"] == self::GROUP_SEARCH_BOTH)) {

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
             _sx('button', 'Post')."\"></td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   /**
    * Converts LDAP timestamps over to Unix timestamps
    *
    * @param string  $ldapstamp        LDAP timestamp
    * @param integer $ldap_time_offset time offset (default 0)
    *
    * @return integer unix timestamp
    */
   static function ldapStamp2UnixStamp($ldapstamp, $ldap_time_offset = 0) {
      global $CFG_GLPI;

      //Check if timestamp is well format, otherwise return ''
      if (!preg_match("/[\d]{14}(\.[\d]{0,4})*Z/", $ldapstamp)) {
         return '';
      }

      $year    = substr($ldapstamp, 0, 4);
      $month   = substr($ldapstamp, 4, 2);
      $day     = substr($ldapstamp, 6, 2);
      $hour    = substr($ldapstamp, 8, 2);
      $minute  = substr($ldapstamp, 10, 2);
      $seconds = substr($ldapstamp, 12, 2);
      $stamp   = gmmktime($hour, $minute, $seconds, $month, $day, $year);
      $stamp  += $CFG_GLPI["time_offset"]-$ldap_time_offset;

      return $stamp;
   }


   /**
    * Converts a Unix timestamp to an LDAP timestamps
    *
    * @param string $date datetime
    *
    * @return string ldap timestamp
    */
   static function date2ldapTimeStamp($date) {
      return date("YmdHis", strtotime($date)).'.0Z';
   }


   /**
    * Return the LDAP field to use for user synchronization
    * It may be sync_field if defined, or login_field
    * @since 9.2
    *
    * @return string the ldap field to use for user synchronization
    */
   public function getLdapIdentifierToUse() {
      if (!empty($this->fields['sync_field'])) {
         return $this->fields['sync_field'];
      } else {
         return $this->fields['login_field'];
      }
   }

   /**
    * Return the database field to use for user synchronization
    * @since 9.2
    *
    * @return string the database field to use for user synchronization
    */
   public function getDatabaseIdentifierToUse() {
      if (!empty($this->fields['sync_field'])) {
         return 'sync_field';
      } else {
         return 'name';
      }
   }

   /**
    * Indicates if there's a sync_field enabled in the LDAP configuration
    * @since 9.2
    *
    * @return boolean true if the sync_field is enabled (the field is filled)
    */
   public function isSyncFieldEnabled() {
      return (!empty($this->fields['sync_field']));
   }

   /**
   * Check if the sync_field is configured for an LDAP server
   *
   * @since 9.2
   * @param integer authldaps_id the LDAP server ID
   * @return boolean true if configured, false if not configured
   */
   public static function isSyncFieldConfigured($authldaps_id) {
      $authldap = new self();
      $authldap->getFromDB($authldaps_id);
      return ($authldap->isSyncFieldEnabled());
   }

   /**
    * Test a LDAP connection
    *
    * @param integer $auths_id     ID of the LDAP server
    * @param integer $replicate_id use a replicate if > 0 (default -1)
    *
    * @return boolean connection succeeded?
    */
   static function testLDAPConnection($auths_id, $replicate_id = -1) {

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($auths_id);

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
    * Display a warnign about size limit
    *
    * @since 0.84
    *
    * @param boolean $limitexceeded (false by default)
    *
    * @return void
    */
   static function displaySizeLimitWarning($limitexceeded = false) {
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


   /**
    * Show LDAP users to add or synchronise
    *
    * @return void
    */
   static function showLdapUsers() {

      $values = [
         'order' => 'DESC',
         'start' => 0,
      ];

      foreach ($_SESSION['ldap_import'] as $option => $value) {
         $values[$option] = $value;
      }

      $rand          = mt_rand();
      $results       = [];
      $limitexceeded = false;
      $ldap_users    = self::getUsers($values, $results, $limitexceeded);

      $config_ldap   = new AuthLDAP();
      $config_ldap->getFromDB($values['authldaps_id']);

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
               $textbutton  = _x('button', 'Synchronize');
               $form_action = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'sync';
            } else {
               $textbutton  = _x('button', 'Import');
               $form_action = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'import';
            }

            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min(count($ldap_users),
                                                        $_SESSION['glpilist_limit']),
                              'container'        => 'mass'.__CLASS__.$rand,
                              'specific_actions' => [$form_action => $textbutton]];
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
            $num = 0;
            if ($config_ldap->isSyncFieldEnabled()) {
               echo Search::showHeaderItem(Search::HTML_OUTPUT, __('Synchronization field'), $num,
                                           $_SERVER['PHP_SELF'].
                                               "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            }
            echo Search::showHeaderItem(Search::HTML_OUTPUT, _n('User', 'Users', Session::getPluralNumber()), $num,
                                        $_SERVER['PHP_SELF'].
                                            "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            echo "<th>".__('Last update in the LDAP directory')."</th>";
            if ($_SESSION['ldap_import']['mode']) {
               echo "<th>".__('Last update in GLPI')."</th>";
            }
            echo "</tr>";

            foreach ($ldap_users as $userinfos) {
               echo "<tr class='tab_bg_2 center'>";
               //Need to use " instead of ' because it doesn't work with names with ' inside !
               echo "<td>";
               echo Html::getMassiveActionCheckBox(__CLASS__, $userinfos['uid']);
               echo "</td>";
               if ($config_ldap->isSyncFieldEnabled()) {
                  echo "<td>" . $userinfos['uid'] . "</td>";
               }
               echo "<td>";
               if (isset($userinfos['id']) && User::canView()) {
                  echo "<a href='".$userinfos['link']."'>". $userinfos['name'] . "</a>";
               } else {
                  echo $userinfos['link'];
               }
               echo "</td>";

               if ($userinfos['stamp'] != '') {
                  echo "<td>" .Html::convDateTime(date("Y-m-d H:i:s", $userinfos['stamp'])). "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               if ($_SESSION['ldap_import']['mode']) {
                  if ($userinfos['date_sync'] != '') {
                     echo "<td>" . Html::convDateTime($userinfos['date_sync']) . "</td>";
                  }
               }
               echo "</tr>";
            }
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
            $num = 0;

            if ($config_ldap->isSyncFieldEnabled()) {
               echo Search::showHeaderItem(Search::HTML_OUTPUT, __('Synchronization field'), $num,
                                           $_SERVER['PHP_SELF'].
                                               "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            }
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

   /**
    * Search users
    *
    * @param resource $ds            An LDAP link identifier
    * @param array    $values        values to search
    * @param string   $filter        search filter
    * @param array    $attrs         An array of the required attributes
    * @param boolean  $limitexceeded is limit exceeded
    * @param array    $user_infos    user informations
    * @param array    $ldap_users    ldap users
    * @param object   $config_ldap   ldap configuration
    *
    * @return boolean
    */
   static function searchForUsers($ds, $values, $filter, $attrs, &$limitexceeded, &$user_infos,
                                  &$ldap_users, $config_ldap) {

      //If paged results cannot be used (PHP < 5.4)
      $cookie   = ''; //Cookie used to perform query using pages
      $count    = 0;  //Store the number of results ldap_search

      do {
         if (self::isLdapPageSizeAvailable($config_ldap)) {
            ldap_control_paged_result($ds, $config_ldap->fields['pagesize'], true, $cookie);
         }
         $filter = Toolbox::unclean_cross_side_scripting_deep(Toolbox::stripslashes_deep($filter));
         $sr     = @ldap_search($ds, $values['basedn'], $filter, $attrs);
         if ($sr) {
            if (in_array(ldap_errno($ds), [4,11])) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $info = self::get_entries_clean($ds, $sr);
            if (in_array(ldap_errno($ds), [4,11])) {
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

            $field_for_sync = $config_ldap->getLdapIdentifierToUse();
            $login_field = $config_ldap->fields['login_field'];

            for ($ligne = 0; $ligne < $info["count"]; $ligne++) {
               if (in_array($field_for_sync, $info[$ligne])) {
                  $uid = self::getFieldValue($info[$ligne], $field_for_sync);

                  if ($login_field != $field_for_sync && !isset($info[$ligne][$login_field])) {
                     Toolbox::logWarning("Missing field $login_field for LDAP entry $field_for_sync $uid");
                     //Login field may be missing... Skip the user
                     continue;
                  }

                  $user_infos[$uid]["timestamp"] = self::ldapStamp2UnixStamp(
                     $info[$ligne]['modifytimestamp'][0],
                     $config_ldap->fields['time_offset']
                  );
                  $user_infos[$uid]["user_dn"] = $info[$ligne]['dn'];
                  $user_infos[$uid][$field_for_sync] = $uid;
                  if ($config_ldap->isSyncFieldEnabled()) {
                     $user_infos[$uid][$login_field] = $info[$ligne][$login_field][0];
                  }

                  if ($values['mode'] == self::ACTION_IMPORT) {
                     //If ldap add
                     $ldap_users[$uid] = $uid;
                  } else {
                     //If ldap synchronisation
                     $ldap_users[$uid] = self::ldapStamp2UnixStamp(
                        $info[$ligne]['modifytimestamp'][0],
                        $config_ldap->fields['time_offset']
                     );
                     $user_infos[$uid]["name"] = $info[$ligne][$login_field][0];
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


   /**
    * Get the list of LDAP users to add/synchronize
    *
    * @param array   $options       possible options:
    *          - authldaps_id ID of the server to use
    *          - mode user to synchronise or add?
    *          - ldap_filter ldap filter to use
    *          - basedn force basedn (default authldaps_id one)
    *          - order display order
    *          - begin_date begin date to time limit
    *          - end_date end date to time limit
    *          - script true if called by an external script
    * @param array   $results       result stats
    * @param boolean $limitexceeded limit exceeded exception
    *
    * @return array of the user
    */
   static function getAllUsers(array $options, &$results, &$limitexceeded) {
      global $DB;

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($options['authldaps_id']);

      $values = [
              'order'       => 'DESC',
              'mode'        => self::ACTION_SYNCHRONIZE,
              'ldap_filter' => '',
              'basedn'      => $config_ldap->fields['basedn'],
              'begin_date'  => null,
              'end_date'    => date('Y-m-d H:i:s', time()-DAY_TIMESTAMP),
              'script'      => 0, //Called by an external script or not
      ];

      foreach ($options as $option => $value) {
         // this test break mode detection - if ($value != '') {
         $values[$option] = $value;
         //}
      }

      $ldap_users    = [];
      $user_infos    = [];
      $limitexceeded = false;

      // we prevent some delay...
      if (!$res) {
         return false;
      }
      if ($values['order'] != "DESC") {
         $values['order'] = "ASC";
      }
      $ds = $config_ldap->connect();
      $field_for_sync = $config_ldap->getLdapIdentifierToUse();
      $field_for_db   = $config_ldap->getDatabaseIdentifierToUse();
      if ($ds) {
         //Search for ldap login AND modifyTimestamp,
         //which indicates the last update of the object in directory
         $attrs = [$config_ldap->fields['login_field'], "modifyTimestamp"];
         if ($field_for_sync != $config_ldap->fields['login_field']) {
            $attrs[] = $field_for_sync;
         }

         // Try a search to find the DN
         if ($values['ldap_filter'] == '') {
            $filter = "(".$field_for_sync."=*)";
            if (!empty($config_ldap->fields['condition'])) {
               $filter = "(& $filter ".$config_ldap->fields['condition'].")";
            }
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

      $glpi_users = [];

      $select = [
         'FROM'   => User::getTable(),
         'ORDER'  => ['name ' . $values['order']]
      ];

      if ($values['mode'] != self::ACTION_IMPORT) {
         $select['WHERE'] = [
            'authtype'  => [-1, Auth::NOT_YET_AUTHENTIFIED, Auth::LDAP, Auth::EXTERNAL, Auth::CAS],
            'auths_id'  => $options['authldaps_id']
         ];
      }

      $iterator = $DB->request($select);

      while ($user = $iterator->next()) {
         $tmpuser = new User();

         //Ldap add : fill the array with the login of the user
         if ($values['mode'] == self::ACTION_IMPORT) {
            $glpi_users[$user['name']] = $user['name'];
         } else {
            //Ldap synchronisation : look if the user exists in the directory
            //and compares the modifications dates (ldap and glpi db)
            $userfound = self::dnExistsInLdap($user_infos, $user['user_dn']);
            if (!empty($ldap_users[$user[$field_for_db]]) || $userfound) {
               // userfound seems that user dn is present in GLPI DB but do not correspond to an GLPI user
               // -> renaming case
               if ($userfound) {
                  //Get user in DB with this dn
                  if (!$tmpuser->getFromDBByDn(Toolbox::addslashes_deep($user['user_dn']))) {
                     //This should never happened
                     //If a user_dn is present more than one time in database
                     //Just skip user synchronization to avoid errors
                     continue;
                  }
                  $glpi_users[] = ['id'         => $user['id'],
                                   'user'       => $userfound['name'],
                                   $field_for_sync => (isset($userfound[$config_ldap->fields['sync_field']]) ? $userfound[$config_ldap->fields['sync_field']] : 'NULL'),
                                   'timestamp'  => $user_infos[$userfound[$field_for_sync]]['timestamp'],
                                   'date_sync'  => $tmpuser->fields['date_sync'],
                                   'dn'         => $user['user_dn']];
               } else if (($values['action'] == self::ACTION_ALL)
                          || (($ldap_users[$user[$field_for_db]] - strtotime($user['date_sync'])) > 0)) {
                  //If entry was modified or if script should synchronize all the users
                  $glpi_users[] = ['id'         => $user['id'],
                                   'user'       => $user['name'],
                                   $field_for_sync => $user['sync_field'],
                                   'timestamp'  => $user_infos[$user[$field_for_db]]['timestamp'],
                                   'date_sync'  => $user['date_sync'],
                                   'dn'         => $user['user_dn']];
               }

            } else if (($values['action'] == self::ACTION_ALL)
                        && !$limitexceeded) {
               // Only manage deleted user if ALL (because of entity visibility in delegated mode)

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
         $diff    = array_diff_ukey($ldap_users, $glpi_users, 'strcasecmp');
         $list    = [];
         $tmpuser = new User();

         foreach ($diff as $user) {
            //If user dn exists in DB, it means that user login field has changed
            if (!$tmpuser->getFromDBByDn(toolbox::addslashes_deep($user_infos[$user]["user_dn"]))) {
               $entry  = ["user"      => $user_infos[$user][$config_ldap->fields['login_field']],
                          "timestamp" => $user_infos[$user]["timestamp"],
                          "date_sync" => Dropdown::EMPTY_VALUE];
               if ($config_ldap->isSyncFieldEnabled()) {
                  $entry[$field_for_sync] = $user_infos[$user][$field_for_sync];
               }
               $list[] = $entry;
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
    * @since 0.84
    *
    * @param array  $ldap_infos ldap user search result
    * @param string $user_dn    user dn to look for
    *
    * @return boolean false if the user dn doesn't exist, user ldap infos otherwise
    */
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


   /**
    * Show LDAP groups to add or synchronize in an entity
    *
    * @param string  $target  target page for the form
    * @param integer $start   where to start the list
    * @param integer $sync    synchronize or add? (default 0)
    * @param string  $filter  ldap filter to use (default '')
    * @param string  $filter2 second ldap filter to use (which case?) (default '')
    * @param integer $entity  working entity
    * @param string  $order   display order (default DESC)
    *
    * @return void
    */
   static function showLdapGroups($target, $start, $sync = 0, $filter = '', $filter2 = '',
                                  $entity = 0, $order = 'DESC') {

      echo "<br>";
      $limitexceeded = false;
      $ldap_groups   = self::getAllGroups($_SESSION["ldap_server"], $filter, $filter2, $entity,
                                          $limitexceeded, $order);

      if (is_array($ldap_groups)) {
         $numrows     = count($ldap_groups);
         $rand        = mt_rand();
         if ($numrows > 0) {
            self::displaySizeLimitWarning($limitexceeded);
            $parameters = '';
            Html::printPager($start, $numrows, $target, $parameters);

            // delete end
            array_splice($ldap_groups, $start + $_SESSION['glpilist_limit']);
            // delete begin
            if ($start > 0) {
               array_splice($ldap_groups, 0, $start);
            }

            echo "<div class='center'>";
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], count($ldap_groups)),
                       'container'
                           => 'mass'.__CLASS__.$rand,
                       'specific_actions'
                           => [__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'import_group'
                                       => _sx('button', 'Import')],
                           'extraparams'
                           => ['massive_action_fields' => ['dn', 'ldap_import_type',
                                                                     'ldap_import_entities',
                                                                     'ldap_import_recursive']]];
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='10'>";
            Html::showCheckbox(['criterion' => ['tag_for_massive' => 'select_item']]);
            echo "</th>";
            $header_num = 0;
            echo Search::showHeaderItem(Search::HTML_OUTPUT, __('Group'), $header_num,
                                        $target."?order=".($order=="DESC"?"ASC":"DESC"),
                                        1, $order);
            echo "<th>".__('Group DN')."</th>";
            echo "<th>".__('Destination entity')."</th>";
            if (Session::isMultiEntitiesMode()) {
               echo"<th>";
               Html::showCheckbox(['criterion' => ['tag_for_massive' => 'select_item_child_entities']]);
               echo "&nbsp;".__('Child entities');
               echo "</th>";
            }
            echo "</tr>";

            $dn_index = 0;
            foreach ($ldap_groups as $groupinfos) {
               $group       = $groupinfos["cn"];
               $group_dn    = $groupinfos["dn"];
               $search_type = $groupinfos["search_type"];

               echo "<tr class='tab_bg_2 center'>";
               echo "<td>";
               echo Html::hidden("dn[$dn_index]", ['value'                 => $group_dn,
                                                        'data-glpicore-ma-tags' => 'common']);
               echo Html::hidden("ldap_import_type[$dn_index]", ['value'                 => $search_type,
                                                                      'data-glpicore-ma-tags' => 'common']);
               Html::showMassiveActionCheckBox(__CLASS__, $dn_index,
                                               ['massive_tags' => 'select_item']);
               echo "</td>";
               echo "<td>" . $group . "</td>";
               echo "<td>" .$group_dn. "</td>";
               echo "<td>";
               Entity::dropdown(['value'         => $entity,
                                      'name'          => "ldap_import_entities[$dn_index]",
                                      'specific_tags' => ['data-glpicore-ma-tags' => 'common']]);
               echo "</td>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(__CLASS__, $dn_index,
                                               ['massive_tags'  => 'select_item_child_entities',
                                                     'name'          => "ldap_import_recursive[$dn_index]",
                                                     'specific_tags' => ['data-glpicore-ma-tags' => 'entities_id']]);
                  echo "</td>";
               } else {
                  echo Html::hidden("ldap_import_recursive[$dn_index]", ['value'                 => 0,
                                                                              'data-glpicore-ma-tags' => 'entities_id']);
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


   /**
    * Get all LDAP groups from a ldap server which are not already in an entity
    *
    * @since 0.84 new parameter $limitexceeded
    *
    * @param integer $auths_id      ID of the server to use
    * @param string  $filter        ldap filter to use
    * @param string  $filter2       second ldap filter to use if needed
    * @param string  $entity        entity to search
    * @param boolean $limitexceeded is limit exceeded
    * @param string  $order         order to use (default DESC)
    *
    * @return array of the groups
    */
   static function getAllGroups($auths_id, $filter, $filter2, $entity, &$limitexceeded,
                                $order = 'DESC') {
      global $DB;

      $config_ldap = new self();
      $config_ldap->getFromDB($auths_id);
      $infos       = [];
      $groups      = [];

      $ds = $config_ldap->connect();
      if ($ds) {
         switch ($config_ldap->fields["group_search_type"]) {
            case self::GROUP_SEARCH_USER:
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter,
                                                $limitexceeded, false, $infos);
               break;

            case self::GROUP_SEARCH_GROUP:
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter,
                                                $limitexceeded, true, $infos);
               break;

            case self::GROUP_SEARCH_BOTH:
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter,
                                                $limitexceeded, true, $infos);
               $infos = self::getGroupsFromLDAP($ds, $config_ldap, $filter2,
                                                $limitexceeded, false, $infos);
               break;
         }
         if (!empty($infos)) {
            $glpi_groups = [];

            //Get all groups from GLPI DB for the current entity and the subentities
            $iterator = $DB->request([
               'SELECT' => ['name'],
               'FROM'   => 'glpi_groups',
               'WHERE'  => getEntitiesRestrictCriteria('glpi_groups')
            ]);

            //If the group exists in DB -> unset it from the LDAP groups
            while ($group = $iterator->next()) {
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
            function local_cmp($a, $b) {
               return strcasecmp($a['cn'], $b['cn']);
            }
         }
         usort($groups, 'local_cmp');

      }
      return $groups;
   }


   /**
    * Get the group's cn by giving his DN
    *
    * @param resource $ldap_connection ldap connection to use
    * @param string   $group_dn        the group's dn
    *
    * @return string the group cn
    */
   static function getGroupCNByDn($ldap_connection, $group_dn) {

      $sr = @ ldap_read($ldap_connection, $group_dn, "objectClass=*", ["cn"]);
      if ($sr === false) {
         //group does not exists
         return false;
      }
      $v  = self::get_entries_clean($ldap_connection, $sr);
      if (!is_array($v) || (count($v) == 0) || empty($v[0]["cn"][0])) {
         return false;
      }
      return $v[0]["cn"][0];
   }


   /**
    * Set groups from ldap
    *
    * @since 0.84 new parameter $limitexceeded
    *
    * @param resource $ldap_connection  LDAP connection
    * @param object   $config_ldap      LDAP configuration
    * @param string   $filter           Filters
    * @param boolean  $limitexceeded    Is limit exceeded
    * @param boolean  $search_in_groups Search in groups (true by default)
    * @param array    $groups           Groups to search
    *
    * @return array
    */
   static function getGroupsFromLDAP($ldap_connection, $config_ldap, $filter,
                                     &$limitexceeded, $search_in_groups = true,
                                     $groups = []) {
      global $DB;

      //First look for groups in group objects
      $extra_attribute = ($search_in_groups?"cn":$config_ldap->fields["group_field"]);
      $attrs           = ["dn", $extra_attribute];

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
         $sr     = @ldap_search($ldap_connection, $config_ldap->fields['basedn'], $filter,
                                $attrs);

         if ($sr) {
            if (in_array(ldap_errno($ldap_connection), [4,11])) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $infos  = self::get_entries_clean($ldap_connection, $sr);
            if (in_array(ldap_errno($ldap_connection), [4,11])) {
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

            for ($ligne=0; $ligne < $infos["count"]; $ligne++) {
               if ($search_in_groups) {
                  // No cn : not a real object
                  if (isset($infos[$ligne]["cn"][0])) {
                     $groups[$infos[$ligne]["dn"]] = (["cn" => $infos[$ligne]["cn"][0],
                                                       "search_type" => "groups"]);
                  }

               } else {
                  if (isset($infos[$ligne][$extra_attribute])) {
                     if (($config_ldap->fields["group_field"] == 'dn')
                         || in_array('ou', $groups)) {
                        $dn = $infos[$ligne][$extra_attribute];
                        $ou = [];
                        for ($tmp=$dn; count($tmptab = explode(',', $tmp, 2))==2; $tmp=$tmptab[1]) {
                           $ou[] = $tmptab[1];
                        }

                        /// Search in DB for group with ldap_group_dn
                        if (($config_ldap->fields["group_field"] == 'dn')
                            && (count($ou) > 0)) {
                           $iterator = $DB->request([
                              'SELECT' => ['ldap_value'],
                              'FROM'   => 'glpi_groups',
                              'WHERE'  => [
                                 'ldap_group_dn' => Toolbox::addslashes_deep($ou)
                              ]
                           ]);

                           while ($group = $iterator->next()) {
                              $groups[$group['ldap_value']] = ["cn"          => $group['ldap_value'],
                                                               "search_type" => "users"];
                           }
                        }

                     } else {
                        for ($ligne_extra=0; $ligne_extra<$infos[$ligne][$extra_attribute]["count"];
                             $ligne_extra++) {
                           $groups[$infos[$ligne][$extra_attribute][$ligne_extra]]
                              = ["cn"   => self::getGroupCNByDn($ldap_connection,
                                                   $infos[$ligne][$extra_attribute][$ligne_extra]),
                                      "search_type"
                                             => "users"];
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


   /**
    * Form to choose a ldap server
    *
    * @param string $target target page for the form
    *
    * @return void
    */
   static function ldapChooseDirectory($target) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'is_active' => 1
         ],
         'ORDER'  => 'name ASC'
      ]);

      if (count($iterator) == 1) {
         //If only one server, do not show the choose ldap server window
         $ldap                    = $iterator->next();
         $_SESSION["ldap_server"] = $ldap["id"];
         Html::redirect($_SERVER['PHP_SELF']);
      }

      echo "<div class='center'>";
      echo "<form action='$target' method=\"post\">";
      echo "<p>" . __('Please choose LDAP directory to import users and groups from') . "</p>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='2'>" . __('LDAP directory choice') . "</th></tr>";

      //If more than one ldap server
      if (count($iterator) > 1) {
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         AuthLDAP::Dropdown(['name'                => 'ldap_server',
                             'display_emptychoice' => false,
                             'comment'             => true,
                             'condition'           => "`is_active`='1'"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input class='submit' type='submit' name='ldap_showusers' value=\"".
               _sx('button', 'Post') . "\"></td></tr>";

      } else {
         //No ldap server
         echo "<tr class='tab_bg_2'>".
              "<td class='center' colspan='2'>".__('No LDAP directory defined in GLPI')."</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

   /**
    * Force synchronization for one user
    *
    * @param User    $user    User to synchronize
    * @param boolean $display Display message information on redirect (true by default)
    *
    * @return array|boolean  with state, else false
    */
   static function forceOneUserSynchronization(User $user, $display = true) {
      $authldap = new AuthLdap();

      //Get the LDAP server from which the user has been imported
      if ($authldap->getFromDB($user->fields['auths_id'])) {
         $user_field = 'name';
         $id_field = $authldap->fields['login_field'];
         if ($authldap->isSyncFieldEnabled() && !empty($user->fields['sync_field'])) {
            $user_field = 'sync_field';
            $id_field   = $authldap->fields['sync_field'];
         }
         return AuthLdap::ldapImportUserByServerId(
            [
               'method'             => self::IDENTIFIER_LOGIN,
               'value'              => $user->fields[$user_field],
               'identifier_field'   => $id_field,
               'user_field'         => $user_field
            ],
            true,
            $user->fields["auths_id"],
            $display
         );
      }
      return false;
   }

   /**
    * Import a user from a specific ldap server
    *
    * @param array   $params      of parameters: method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
    * @param boolean $action      synchoronize (true) or import (false)
    * @param integer $ldap_server ID of the LDAP server to use
    * @param boolean $display     display message information on redirect (false by default)
    *
    * @return array|boolean  with state, else false
    */
   static function ldapImportUserByServerId(array $params, $action, $ldap_server,
                                            $display = false) {

      $params      = Toolbox::stripslashes_deep($params);
      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($ldap_server);
      $input = [];

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      if (!isset($params['identifier_field'])) {
         $params['identifier_field'] = $config_ldap->getLdapIdentifierToUse();
      }
      if (!isset($params['user_field'])) {
         $params['user_field'] = $config_ldap->getDatabaseIdentifierToUse();
      }

      $search_parameters = [];
      //Connect to the directory
      if (isset(self::$conn_cache[$ldap_server])) {
         $ds = self::$conn_cache[$ldap_server];
      } else {
         $ds = $config_ldap->connect();
      }
      if ($ds) {
         self::$conn_cache[$ldap_server] = $ds;
         $search_parameters['method']                         = $params['method'];
         $search_parameters['fields'][self::IDENTIFIER_LOGIN] = $params['identifier_field'];

         if ($params['method'] == self::IDENTIFIER_EMAIL) {
            $search_parameters['fields'][self::IDENTIFIER_EMAIL]
                                       = $config_ldap->fields['email1_field'];
         }

         //Get the user's dn & login
         $attribs = ['basedn'            => $config_ldap->fields['basedn'],
                     'login_field'       => $search_parameters['fields'][$search_parameters['method']],
                     'search_parameters' => $search_parameters,
                     'user_params'       => $params,
                     'condition'         => $config_ldap->fields['condition']];

         try {
            $infos = self::searchUserDn($ds, $attribs);
            $login   = self::getFieldValue($infos, $search_parameters['fields'][$search_parameters['method']]);

            if ($infos && $infos['dn']) {
               $user_dn = $infos['dn'];
               $user    = new User();

               //Get information from LDAP
               if ($user->getFromLDAP($ds, $config_ldap->fields, $user_dn, addslashes($login),
                                    ($action == self::ACTION_IMPORT))) {
                  // Add the auth method
                  // Force date sync
                  $user->fields["date_sync"] = $_SESSION["glpi_currenttime"];
                  $user->fields['is_deleted_ldap'] = 0;

                  //Save information in database !
                  $input = $user->fields;

                  //clean picture from input
                  // (picture managed in User::post_addItem and prepareInputForUpdate)
                  unset($input['picture']);

                  if ($action == self::ACTION_IMPORT) {
                     $input["authtype"] = Auth::LDAP;
                     $input["auths_id"] = $ldap_server;
                     // Display message after redirect
                     if ($display) {
                        $input['add'] = 1;
                     }

                     $user->fields["id"] = $user->add($input);
                     return ['action' => self::USER_IMPORTED,
                           'id'     => $user->fields["id"]];
                  }
                  //Get the ID by user name
                  if (!($id = User::getIdByfield($params['user_field'], $login))) {
                     //In case user id as changed : get id by dn
                     $id = User::getIdByfield('user_dn', $user_dn);
                  }
                  $input['id'] = $id;

                  if ($display) {
                     $input['update'] = 1;
                  }
                  $user->update($input);
                  return ['action' => self::USER_SYNCHRONIZED,
                        'id'     => $input['id']];
               }
               return false;

            }
            if ($action != self::ACTION_IMPORT) {
               $users_id = User::getIdByField($params['user_field'], $params['value']);
               User::manageDeletedUserInLdap($users_id);
               return ['action' => self::USER_DELETED_LDAP,
                     'id'     => $users_id];
            }
         } catch (\RuntimeException $e) {
            Toolbox::logError($e->getMessage());
            return false;
         }
      } else {
         return false;
      }
   }


   /**
    * Import grousp from an LDAP directory
    *
    * @param string $group_dn dn of the group to import
    * @param array  $options  array for
    *             - authldaps_id
    *             - entities_id where group must to be imported
    *             - is_recursive
    *
    * @return integer|false
    */
   static function ldapImportGroup($group_dn, $options = []) {

      $config_ldap = new self();
      $res         = $config_ldap->getFromDB($options['authldaps_id']);

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
            return $group->add(["name"          => addslashes($group_infos["cn"][0]),
                                "ldap_group_dn" => addslashes($group_infos["dn"]),
                                "entities_id"   => $options['entities_id'],
                                "is_recursive"  => $options['is_recursive']]);
         }
         return $group->add(["name"         => addslashes($group_infos["cn"][0]),
                             "ldap_field"   => $config_ldap->fields["group_field"],
                             "ldap_value"   => addslashes($group_infos["dn"]),
                             "entities_id"  => $options['entities_id'],
                             "is_recursive" => $options['is_recursive']]);
      }
      return false;
   }


   /**
    * Open LDAP connection to current server
    *
    * @return resource|boolean
    */
   function connect() {

      return $this->connectToServer($this->fields['host'], $this->fields['port'],
                                    $this->fields['rootdn'],
                                    Toolbox::decrypt($this->fields['rootdn_passwd'], GLPIKEY),
                                    $this->fields['use_tls'],
                                    $this->fields['deref_option']);
   }


   /**
    * Connect to a LDAP server
    *
    * @param string  $host          LDAP host to connect
    * @param string  $port          port to use
    * @param string  $login         login to use (default '')
    * @param string  $password      password to use (default '')
    * @param boolean $use_tls       use a TLS connection? (false by default)
    * @param integer $deref_options deref options used
    *
    * @return resource link to the LDAP server : false if connection failed
    */
   static function connectToServer($host, $port, $login = "", $password = "",
                                   $use_tls = false, $deref_options = 0) {

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
    * @param array  $ldap_method ldap_method array to use
    * @param string $login       User Login
    * @param string $password    User Password
    *
    * @return resource|boolean link to the LDAP server : false if connection failed
    */
   static function tryToConnectToServer($ldap_method, $login, $password) {
      if (!function_exists('ldap_connect')) {
         Toolbox::logError("ldap_connect function is missing. Did you miss install php-ldap extension?");
         return false;
      }
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

   /**
    * Get LDAP servers
    *
    * @return array
    */
   static function getLdapServers() {
      return getAllDatasFromTable('glpi_authldaps', [], false, '`is_default` DESC');
   }


   /**
    * Is the LDAP authentication used?
    *
    * @return boolean
    */
   static function useAuthLdap() {
      return (countElementsInTable('glpi_authldaps', ['is_active' => 1]) > 0);
   }


   /**
    * Import a user from ldap
    * Check all the directories. When the user is found, then import it
    *
    * @param array $options array containing condition:
    *                 array('name'=>'glpi') or array('email' => 'test at test.com')
    *
    * @return array|boolean false if fail
    */
   static function importUserFromServers($options = []) {

      $auth   = new Auth();
      $params = [];
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

         foreach ($ldap_methods as $ldap_method) {
            if ($ldap_method['is_active']) {
               //we're looking for a user login
               $params['identifier_field']   = $ldap_method['login_field'];
               $params['user_field']         = 'name';
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
    * @param object $auth        identification object
    * @param string $login       user login
    * @param string $password    user password
    * @param array  $ldap_method ldap_method array to use
    * @param string $user_dn     user LDAP DN if present
    *
    * @return object identification object
    */
   static function ldapAuth($auth, $login, $password, $ldap_method, $user_dn) {

      $oldlevel = error_reporting(0);

      $infos  = $auth->connection_ldap($ldap_method, $login, $password);
      $user_dn = $infos['dn'];
      $user_sync = (isset($infos['sync_field']) ? $infos['sync_field'] : null);

      error_reporting($oldlevel);

      $auth->auth_succeded            = false;
      $auth->extauth                  = 1;

      if ($user_dn) {
         $auth->auth_succeded            = true;
         if ($auth->user->getFromDBbyDn(toolbox::addslashes_deep($user_dn))) {
            //There's already an existing user in DB with the same DN but its login field has changed
            $auth->user->fields['name'] = $login;
            $auth->user_present         = true;
            $auth->user_dn              = $user_dn;
         } else if ($user_sync !== null && $auth->user->getFromDBbySyncField($user_sync)) {
            //user login/dn have changed
            $auth->user->fields['name']      = $login;
            $auth->user->fields['user_dn']   = $user_dn;
            $auth->user_present              = true;
            $auth->user_dn                   = $user_dn;
         } else { // The user is a new user
            $auth->user_present = false;
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
    * @param object  $auth     identification object
    * @param string  $login    user login
    * @param string  $password user password
    * @param integer $auths_id auths_id already used for the user (default 0)
    * @param boolean $user_dn  user LDAP DN if present (false by default)
    * @param boolean $break    if user is not found in the first directory,
    *                          stop searching or try the following ones (true by default)
    *
    * @return object identification object
    */
   static function tryLdapAuth($auth, $login, $password, $auths_id = 0, $user_dn = false, $break = true) {

      //If no specific source is given, test all ldap directories
      if ($auths_id <= 0) {
         foreach ($auth->authtypes["ldap"] as $ldap_method) {
            if (!$auth->auth_succeded
                && $ldap_method['is_active']) {
               $auth = self::ldapAuth($auth, $login, $password, $ldap_method, $user_dn);
            } else {
               if ($break) {
                  break;
               }
            }
         }

      } else if (array_key_exists($auths_id, $auth->authtypes["ldap"])) {
         // Check if the ldap server indicated as the last good one still exists !
         //A specific ldap directory is given, test it and only this one !
         $auth = self::ldapAuth($auth, $login, $password, $auth->authtypes["ldap"][$auths_id],
                                $user_dn);
      }
      return $auth;
   }


   /**
    * Get dn for a user
    *
    * @param resource $ds      LDAP link
    * @param array    $options array of possible options:
    *          - basedn : base dn used to search
    *          - login_field : attribute to store login
    *          - search_parameters array of search parameters
    *          - user_params  array of parameters : method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
    *          - condition : ldap condition used
    *
    * @return array|boolean dn of the user, else false
    */
   static function searchUserDn($ds, $options = []) {

      $values = [
         'basedn'            => '',
         'login_field'       => '',
         'search_parameters' => [],
         'user_params'       => '',
         'condition'         => '',
         'user_dn'           => false,
      ];

      foreach ($options as $key => $value) {
         $values[$key] = $value;
      }

      //By default authentify users by login
      //$authentification_value = '';
      $login_attr      = $values['search_parameters']['fields'][self::IDENTIFIER_LOGIN];
      $sync_attr       = (isset($values['search_parameters']['fields']['sync_field'])) ?
         $values['search_parameters']['fields']['sync_field'] : null;

      $ldap_parameters = ["dn"];
      foreach ($values['search_parameters']['fields'] as $parameter) {
         $ldap_parameters[] = $parameter;
      }

      //First : if a user dn is provided, look for it in the directory
      //Before trying to find the user using his login_field
      if ($values['user_dn']) {
         $info = self::getUserByDn($ds, $values['user_dn'], $ldap_parameters);

         if ($info) {
            $ret = [
               'dn'        => $values['user_dn'],
               $login_attr => $info[$login_attr][0]
            ];
            if ($sync_attr !== null && isset($info[0][$sync_attr])) {
               $ret['sync_field'] = self::getFieldValue($info[0], $sync_attr);
            }
            return $ret;
         }
      }

      //$authentification_value = $values['user_params']['value'];
      // Tenter une recherche pour essayer de retrouver le DN
      $filter_value = $values['user_params']['value'];
      if ($values['login_field'] == 'objectguid' && self::isValidGuid($filter_value)) {
         $filter_value = self::guidToHex($filter_value);
      }
      $filter = "(".$values['login_field']."=".$filter_value.")";

      if (!empty($values['condition'])) {
         $filter = "(& $filter ".$values['condition'].")";
      }

      if ($result = @ldap_search($ds, $values['basedn'], $filter, $ldap_parameters)) {
         //search has been done, let's check for found results
         $info = self::get_entries_clean($ds, $result);

         if (is_array($info) && ($info['count'] == 1)) {
            $ret = [
               'dn'        => $info[0]['dn'],
               $login_attr => $info[0][$login_attr][0]
            ];
            if ($sync_attr !== null && isset($info[0][$sync_attr])) {
               $ret['sync_field'] = self::getFieldValue($info[0], $sync_attr);
            }
            return $ret;
         }
         return false;
      }
      throw new \RuntimeException('Something went wrong searching in LDAP directory');
   }


   /**
    * Get an object from LDAP by giving his DN
    *
    * @param resource $ds        the active connection to the directory
    * @param string   $condition the LDAP filter to use for the search
    * @param string   $dn        DN of the object
    * @param array    $attrs     of the attributes to retreive
    * @param boolean  $clean     (true by default)
    *
    * @return array|boolean false if failed
    */
   static function getObjectByDn($ds, $condition, $dn, $attrs = [], $clean = true) {
      if ($result = @ ldap_read($ds, $dn, $condition, $attrs)) {
         if ($clean) {
            $info = self::get_entries_clean($ds, $result);
         } else {
            $info = ldap_get_entries($ds, $result);
         }
         if (is_array($info) && ($info['count'] == 1)) {
            return $info[0];
         }
      }

      return false;
   }


   /**
    * Get user by domain name
    *
    * @param resource $ds      the active connection to the directory
    * @param string   $user_dn domain name
    * @param array    $attrs   attributes
    * @param boolean  $clean   (true by default)
    *
    * @return array|boolean false if failed
    */
   static function getUserByDn($ds, $user_dn, $attrs, $clean = true) {
      return self::getObjectByDn($ds, "objectClass=*", $user_dn, $attrs, $clean);
   }

   /**
    * Get infos for groups
    *
    * @param resource $ds       LDAP link
    * @param string   $group_dn dn of the group
    *
    * @return array|boolean group infos if found, else false
    */
   static function getGroupByDn($ds, $group_dn) {
      return self::getObjectByDn($ds, "objectClass=*", $group_dn, ["cn"]);
   }


   /**
    * Manage values stored in session
    *
    * @param array   $options Options
    * @param boolean $delete  (false by default)
    *
    * @return void
    */
   static function manageValuesInSession($options = [], $delete = false) {

      $fields = ['action', 'authldaps_id', 'basedn', 'begin_date', 'criterias',  'end_date',
                      'entities_id', 'interface', 'ldap_filter', 'mode'];

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
            $_SESSION['ldap_import']['no_expert_mode'] = 0;
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
             || (!isset($_SESSION['ldap_import']['interface'])
                && !isset($options['interface']))) {
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
            $_SESSION['ldap_import']['criterias'] = [];
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
               if ($_SESSION['ldap_import']['authldaps_id'] == NOT_AVAILABLE
                   || !$_SESSION['ldap_import']['authldaps_id']) {
                     $_SESSION['ldap_import']['authldaps_id'] = self::getDefault();
               }

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
      } else { // Unset all values in session
         unset($_SESSION['ldap_import']);
      }
   }


   /**
    * Show import user form
    *
    * @param object $authldap AuthLDAP object
    *
    * @return void
    */
   static function showUserImportForm(AuthLDAP $authldap) {

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
          && (!isset($_SESSION['ldap_import']['no_expert_mode'])
              || $_SESSION['ldap_import']['no_expert_mode'] != 1)) {

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
                  $rand = mt_rand();
                  echo "<tr class='tab_bg_2'><td><label for='dropdown_authldaps_id$rand'>".__('LDAP directory choice')."</label></td>";
                  echo "<td colspan='3'>";
                  self::dropdown(['name'                 => 'authldaps_id',
                                  'value'                => $_SESSION['ldap_import']['authldaps_id'],
                                  'condition'            => "`is_active` = 1",
                                  'display_emptychoice'  => false,
                                  'rand'                 => $rand]);
                  echo "&nbsp;<input class='submit' type='submit' name='change_directory'
                        value=\""._sx('button', 'Change')."\">";
                  echo "</td></tr>";
               }

               echo "<tr class='tab_bg_2'><td><label for='basedn'>".__('BaseDN')."</label></td><td colspan='3'>";
               echo "<input type='text' id='basedn' name='basedn' value=\"".$_SESSION['ldap_import']['basedn'].
                     "\" size='90' ".(!$_SESSION['ldap_import']['basedn']?"disabled":"").">";
               echo "</td></tr>";

               echo "<tr class='tab_bg_2'><td><label for='ldap_filter'>".__('Search filter for users')."</label></td><td colspan='3'>";
               echo "<input type='text' id='ldap_filter' name='ldap_filter' value=\"".
                      $_SESSION['ldap_import']['ldap_filter']."\" size='90'>";
               echo "</td></tr>";
            }
            break;

         //case self::SIMPLE_INTERFACE :
         default :
            if (self::getNumberOfServers() > 1) {
               $rand = mt_rand();
               echo "<tr class='tab_bg_2'><td><label for='dropdown_authldaps_id$rand'>".__('LDAP directory choice')."</label></td>";
               echo "<td colspan='3'>";
               self::dropdown(['name'                 => 'authldaps_id',
                                 'value'                => $_SESSION['ldap_import']['authldaps_id'],
                                 'condition'            => "`is_active` = 1",
                                 'display_emptychoice'  => false,
                                 'rand'                 => $rand]);
               echo "&nbsp;<input class='submit' type='submit' name='change_directory'
                     value=\""._sx('button', 'Change')."\">";
               echo "</td></tr>";
            }

            //If multi-entity mode and more than one entity visible
            //else no need to select entity
            if (Session::isMultiEntitiesMode()
                && (count($_SESSION['glpiactiveentities']) > 1)) {
               echo "<tr class='tab_bg_2'><td>".__('Select the desired entity')."</td>".
                    "<td colspan='3'>";
               Entity::dropdown(['value'       => $_SESSION['ldap_import']['entities_id'],
                                      'entity'      => $_SESSION['glpiactiveentities'],
                                      'on_change'    => 'submit()']);
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
               $fields        = ['login_field'     => __('Login'),
                                 'sync_field'      => __('Synchronization field') . ' (' . $authldap->fields['sync_field'] . ')',
                                 'email1_field'    => __('Email'),
                                 'email2_field'    => sprintf(__('%1$s %2$s'),
                                                              _n('Email', 'Emails', 1), '2'),
                                 'email3_field'    => sprintf(__('%1$s %2$s'),
                                                              _n('Email', 'Emails', 1), '3'),
                                 'email4_field'    => sprintf(__('%1$s %2$s'),
                                                              _n('Email', 'Emails', 1), '4'),
                                 'realname_field'  => __('Surname'),
                                 'firstname_field' => __('First name'),
                                 'phone_field'     => __('Phone'),
                                 'phone2_field'    => __('Phone 2'),
                                 'mobile_field'    => __('Mobile phone'),
                                 'title_field'     => _x('person', 'Title'),
                                 'category_field'  => __('Category'),
                                 'picture_field'   => __('Picture')];
               $available_fields = [];
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
                  echo "<td><label for='criterias$field'>$label</label></td><td>";
                  $field_counter++;
                  $field_value = '';
                  if (isset($_SESSION['ldap_import']['criterias'][$field])) {
                     $field_value = Html::entities_deep(Toolbox::unclean_cross_side_scripting_deep(Toolbox::stripslashes_deep($_SESSION['ldap_import']['criterias'][$field])));
                  }
                  echo "<input type='text' id='criterias$field' name='criterias[$field]' value='$field_value'>";
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
                   _sx('button', 'Search')."\">";
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

   /**
    * Get number of servers
    *
    * @var DBmysql $DB
    *
    * @return integer
    */
   static function getNumberOfServers() {
      return countElementsInTable('glpi_authldaps', ['is_active' => 1]);
   }


   /**
    * Build LDAP filter
    *
    * @param resource $authldap AuthLDAP object
    *
    * @return string
    */
   static function buildLdapFilter(AuthLdap $authldap) {
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
                        ? $_SESSION['ldap_import']['begin_date'] : null);
      $end_date   = (isset($_SESSION['ldap_import']['end_date'])
                     && !empty($_SESSION['ldap_import']['end_date'])
                        ? $_SESSION['ldap_import']['end_date'] : null);
      $filter    .= self::addTimestampRestrictions($begin_date, $end_date);
      $ldap_condition = $authldap->getField('condition');
      //Add entity filter and filter filled in directory's configuration form
      return  "(&".(isset($_SESSION['ldap_import']['entity_filter'])
                    ?$_SESSION['ldap_import']['entity_filter']
                    :'')." $filter $ldap_condition)";
   }


   /**
    * Add timestamp restriction
    *
    * @param string $begin_date datetime begin date to search (NULL if not take into account)
    * @param string $end_date   datetime end date to search (NULL if not take into account)
    *
    * @return string
    */
   static function addTimestampRestrictions($begin_date, $end_date) {

      $condition = '';
      //If begin date
      if (!empty($begin_date)) {
         $stampvalue = self::date2ldapTimeStamp($begin_date);
         $condition .= "(modifyTimestamp>=".$stampvalue.")";
      }
      //If end date
      if (!empty($end_date)) {
         $stampvalue = self::date2ldapTimeStamp($end_date);
         $condition .= "(modifyTimestamp<=".$stampvalue.")";
      }
      return $condition;
   }


   /**
    * Search user
    *
    * @param resource $authldap AuthLDAP object
    *
    * @return void
    */
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

   /**
    * Get default ldap
    *
    * @var DBmysql $DB DB instance
    *
    * @return integer
    */
   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_authldaps', ['is_default' => 1, 'is_active' => 1]) as $data) {
         return $data['id'];
      }
      return 0;
   }

   function post_updateItem($history = 1) {
      global $DB;

      if (in_array('is_default', $this->updates) && $this->input["is_default"]==1) {
         $DB->update(
            $this->getTable(),
            ['is_default' => 0],
            ['id' => ['<>', $this->input['id']]]
         );
      }
   }

   function post_addItem() {
      global $DB;

      if (isset($this->fields['is_default']) && $this->fields["is_default"]==1) {
         $DB->update(
            $this->getTable(),
            ['is_default' => 0],
            ['id' => ['<>', $this->fields['id']]]
         );
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
    * Builds deleted actions dropdown
    *
    * @param integer $value (default 0)
    *
    * @return string
    */
   static function dropdownUserDeletedActions($value = 0) {

      $options = [
         __('Preserve'),
         __('Put in trashbin'),
         __('Withdraw dynamic authorizations and groups'),
         __('Disable'),
         __('Disable').' + '.__('Withdraw dynamic authorizations and groups'),
      ];
      asort($options);
      return Dropdown::showFromArray('user_deleted_ldap', $options, ['value' => $value]);
   }


   /**
    * Return all the ldap servers where email field is configured
    *
    * @return array of LDAP server's ID
    */
   static function getServersWithImportByEmailActive() {
      global $DB;

      $ldaps = [];
      // Always get default first

      $iterator = $DB->request([
         'SELECT' => ['id'],
         'FROM'  => 'glpi_authldaps',
         'WHERE' => [
            'is_active' => 1,
            'OR'        => [
               'email1_field' => ['<>', ''],
               'email2_field' => ['<>', ''],
               'email3_field' => ['<>', ''],
               'email4_field' => ['<>', '']
            ]
         ],
         'ORDER'  => ['is_default DESC']
      ]);
      while ($data = $iterator->next()) {
         $ldaps[] = $data['id'];
      }
      return $ldaps;
   }


   /**
    * Show date restriction form
    *
    * @param array $options Options
    *
    * @return void
    */
   static function showDateRestrictionForm($options = []) {

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
         Html::showDateTimeField("begin_date", ['value'    => $begin_date,
                                                     'timestep' => 1]);
         echo "</td>";
         echo "<td>".__('to')."</td>";
         echo "<td>";
         $end_date = (isset($_SESSION['ldap_import']['end_date'])
                        ?$_SESSION['ldap_import']['end_date']
                        :date('Y-m-d H:i:s', time()-DAY_TIMESTAMP));
         Html::showDateTimeField("end_date", ['value'    => $end_date,
                                                   'timestep' => 1]);
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

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate
          && $item->can($item->getField('id'), READ)) {
         $ong     = [];
         $ong[1]  = _sx('button', 'Test');                     // test connexion
         $ong[2]  = _n('User', 'Users', Session::getPluralNumber());
         $ong[3]  = _n('Group', 'Groups', Session::getPluralNumber());
         // TODO clean fields entity_XXX if not used
         // $ong[4]  = __('Entity');                  // params for entity config
         $ong[5]  = __('Advanced information');   // params for entity advanced config
         $ong[6]  = _n('Replicate', 'Replicates', Session::getPluralNumber());

         return $ong;
      }
      return '';
   }

   /**
    * Choose wich form to show
    *
    * @param CommonGLPI $item         Item instance
    * @param integer    $tabnum       Tab number
    * @param integer    $withtemplate Unused
    *
    * @return boolean (TRUE)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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
    * @param resource $link   link to the directory connection
    * @param array    $result the query results
    *
    * @return array which contains ldap query results
    */
   static function get_entries_clean($link, $result) {
      return ldap_get_entries($link, $result);
   }


   /**
    * Get all replicate servers for a master one
    *
    * @param integer $master_id master ldap server ID
    *
    * @return array of the replicate servers
    */
   static function getAllReplicateForAMaster($master_id) {
      global $DB;

      $replicates = [];
      $query = ['FIELDS' => ['id', 'host', 'port'],
                'FROM'   => 'glpi_authldapreplicates',
                'WHERE'  => ['authldaps_id' => $master_id]
               ];
      foreach ($DB->request($query) as $replicate) {
         $replicates[] = ["id"   => $replicate["id"],
                          "host" => $replicate["host"],
                          "port" => $replicate["port"]
                         ];
      }
      return $replicates;
   }

   /**
    * Check if ldap results can be paged or not
    * This functionnality is available for PHP 5.4 and higher
    *
    * @since 0.84
    *
    * @param object  $config_ldap        LDAP configuration
    * @param boolean $check_config_value Whether to check config values
    *
    * @return boolean true if maxPageSize can be used, false otherwise
    */
   static function isLdapPageSizeAvailable($config_ldap, $check_config_value = true) {
      return ((!$check_config_value
               || ($check_config_value && $config_ldap->fields['can_support_pagesize']))
                  && function_exists('ldap_control_paged_result')
                     && function_exists('ldap_control_paged_result_response'));
   }

   /**
    * Does LDAP user already exists in the database?
    *
    * @param string $name User login/name
    * @param string $sync Sync field
    *
    * @return false|User
    */
   public function getLdapExistingUser($name, $authldaps_id, $sync = null) {
      global $DB;
      $user = new User();

      if ($sync !== null && $user->getFromDBbySyncField($sync)) {
         return $user;
      }

      if ($user->getFromDBbyNameAndAuth($DB->escape($name), Auth::LDAP, $authldaps_id)) {
         return $user;
      }

      return false;
   }

   /**
    * Is synchronisation field used for current server
    *
    * @return boolean
    */
   public function isSyncFieldUsed() {
      $count = countElementsInTable(
         'glpi_users',
         [
            'auths_id'  => $this->getID(),
            'NOT'       => ['sync_field' => null]
         ]
      );
      return $count > 0;
   }

   /**
    * Get a LDAP field value
    *
    * @param array  $infos LDAP entry infos
    * @param string $field Field name to retrieve
    *
    * @return string
    */
   public static function getFieldValue($infos, $field) {
      $value = null;
      if (is_array($infos[$field])) {
         $value = $infos[$field][0];
      } else {
         $value = $infos[$field];
      }
      if ($field != 'objectguid') {
         return $value;
      }

      //handle special objectguid from AD directories
      try {
         //prevent double encoding
         if (!self::isValidGuid($value)) {
            $value = self::guidToString($value);
            if (!self::isValidGuid($value)) {
               throw new \RuntimeException('Not an objectguid!');
            }
         }
      } catch (\Exception $e) {
         //well... this is not an objectguid apparently
         $value = $infos[$field];
      }

      return $value;
   }

   /**
    * Converts a string representation of an objectguid to hexadecimal
    * Used to build filters
    *
    * @param string $guid_str String representation
    *
    * @return string
    */
   public static function guidToHex($guid_str) {
      $str_g = explode('-', $guid_str);

      $str_g[0] = strrev($str_g[0]);
      $str_g[1] = strrev($str_g[1]);
      $str_g[2] = strrev($str_g[2]);

      $guid_hex = '\\';
      $strrev = 0;
      foreach ($str_g as $str) {
         for ($i = 0; $i < strlen($str)+2; $i++) {
            if ($strrev < 3) {
               $guid_hex .= strrev(substr($str, 0, 2)).'\\';
            } else {
               $guid_hex .= substr($str, 0, 2).'\\';
            }
            $str = substr($str, 2);
         }
         if ($strrev < 3) {
            $guid_hex .= strrev($str);
         } else {
            $guid_hex .= $str;
         }
         $strrev++;
      }
      return $guid_hex;
   }

   /**
    * Converts binary objectguid to string representation
    *
    * @param mixed $binary_guid Binary objectguid from AD
    *
    * @return string
    */
   public static function guidToString($guid_bin) {
      $guid_hex = unpack("H*hex", $guid_bin);
      $hex = $guid_hex["hex"];

      $hex1 = substr($hex, -26, 2) . substr($hex, -28, 2) . substr($hex, -30, 2) . substr($hex, -32, 2);
      $hex2 = substr($hex, -22, 2) . substr($hex, -24, 2);
      $hex3 = substr($hex, -18, 2) . substr($hex, -20, 2);
      $hex4 = substr($hex, -16, 4);
      $hex5 = substr($hex, -12, 12);

      $guid_str = $hex1 . "-" . $hex2 . "-" . $hex3 . "-" . $hex4 . "-" . $hex5;
      return $guid_str;
   }

   /**
    * Check if text representation of an objectguid is valid
    *
    * @param string $string Strign representation
    *
    * @return boolean
    */
   public static function isValidGuid($guid_str) {
      return (bool) preg_match('/^([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}$/', $guid_str);
   }

   /**
    * Get the list of LDAP users to add/synchronize
    * When importing, already existing users will be filtered
    *
    * @param array   $options       possible options:
    *          - authldaps_id ID of the server to use
    *          - mode user to synchronise or add?
    *          - ldap_filter ldap filter to use
    *          - basedn force basedn (default authldaps_id one)
    *          - order display order
    *          - begin_date begin date to time limit
    *          - end_date end date to time limit
    *          - script true if called by an external script
    * @param array   $results       result stats
    * @param boolean $limitexceeded limit exceeded exception
    *
    * @return array
    */
   public static function getUsers($values, &$results, &$limitexceeded) {
      $users = [];
      $ldap_users    = self::getAllUsers($values, $results, $limitexceeded);

      $config_ldap   = new AuthLDAP();
      $config_ldap->getFromDB($values['authldaps_id']);

      if (!is_array($ldap_users) || count($ldap_users) == 0) {
         return $users;
      }

      foreach ($ldap_users as $userinfos) {
         $user_to_add = [];
         $user = new User();

         $user_sync_field = null;
         if ($config_ldap->isSyncFieldEnabled()) {
            $sync_field = $config_ldap->fields['sync_field'];
            if (isset($userinfos[$sync_field])) {
               $user_sync_field = self::getFieldValue($userinfos, $sync_field);
            }
         }

         $user = $config_ldap->getLdapExistingUser($userinfos['user'], $values['authldaps_id'],
                                                   $user_sync_field);
         if (isset($_SESSION['ldap_import']) && !$_SESSION['ldap_import']['mode'] && $user) {
            continue;
         }
         $user_to_add['link'] = $userinfos["user"];
         if (isset($userinfos['id']) && User::canView()) {
            $user_to_add['id']   = $userinfos['id'];
            $user_to_add['name'] = $user->fields['name'];
            $user_to_add['link'] = Toolbox::getItemTypeFormURL('User').'?id='.$userinfos['id'];
         }

         $user_to_add['stamp']      = (isset($userinfos["timestamp"])) ? $userinfos["timestamp"] : '';
         $user_to_add['date_sync']  = (isset($userinfos["date_sync"])) ? $userinfos["date_sync"] : '';

         $user_to_add['uid'] = $userinfos['user'];
         if ($config_ldap->isSyncFieldEnabled()) {
            if (isset($userinfos[$sync_field])) {
               $user_to_add['uid'] = self::getFieldValue($userinfos, $sync_field);
            }

            $field_for_sync = $config_ldap->getLdapIdentifierToUse();
            if (isset($userinfos[$field_for_sync])) {
               $user_to_add['sync_field'] = $userinfos[$field_for_sync];
            }
         }

         $users[] = $user_to_add;
      }

      return $users;
   }
}
