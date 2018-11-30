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

// Ensure current directory when run from crontab
chdir(__DIR__);

if (isset($_SERVER['argv'])) {
   for ($i=1; $i<$_SERVER['argc']; $i++) {
      $it    = explode("=", $_SERVER['argv'][$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

echo "Usage of this script is deprecated, please use 'bin/console ldap:sync' command.\n";

if ((isset($_SERVER['argv']) && in_array('help', $_SERVER['argv']))
    || isset($_GET['help'])) {
   echo "Usage: php -q -f ldap_mass_sync.php [action=<option>]  [ldapservers_id=ID]\n";
   echo "Options values:\n";
   echo "0: import users only\n";
   echo "1: synchronize existing users only\n";
   echo "2: import & synchronize users\n";
   echo "before-days: restrict user import or synchronization to the last x days\n";
   echo "after-days: restrict user import or synchronization until the last x days\n";
   echo "ldap_filter: ldap filter to use for the search. Value must be surrounded by \"\"\n";
   exit (0);
}

include ('../inc/includes.php');

// Default action : synchro
// - possible option :
//  - 0 : import new users
//  - 1 : synchronize users
//  - 2 : force synchronization of all the users (even if ldap timestamp wasn't modified)
$options['action']         = AuthLDAP::ACTION_SYNCHRONIZE;
$options['ldapservers_id'] = NOT_AVAILABLE;
$options['ldap_filter']    = '';
$options['before-days']    = 0;
$options['after-days']     = 0;
$options['script']         = 1;

foreach ($_GET as $key => $value) {
   $options[$key] = $value;
}

if ($options['before-days'] && $options['after-days']) {
   echo "You cannot use options before-days and after-days at the same time.";
   exit(1);
}

if ($options['before-days']) {
   $options['begin_date'] = date('Y-m-d H:i:s', time()-$options['before-days']*DAY_TIMESTAMP);
   $options['end_date']   = '';
   unset($options['before-days']);
}
if ($options['after-days']) {
   $options['begin_date'] = '';
   $options['end_date']   = date('Y-m-d H:i:s', time()-$options['after-days']*DAY_TIMESTAMP);
   unset($options['after-days']);
}

if (!Toolbox::canUseLdap() || !countElementsInTable('glpi_authldaps')) {
   echo "LDAP extension is not active or no LDAP directory defined";
}

$sql = "SELECT `id`, `name`
        FROM `glpi_authldaps`
        WHERE `is_active` = 1";

//Get the ldap server's id by his name
if ($options['ldapservers_id'] != NOT_AVAILABLE) {
   $sql .= " AND `id` = '" . $options['ldapservers_id']."'";
}

$result = $DB->query($sql);

if (($DB->numrows($result) == 0)
    && ($_GET["ldapservers_id"] != NOT_AVAILABLE)) {
   echo "LDAP Server not found";
} else {
   foreach ($DB->request($sql) as $data) {
      echo "Processing LDAP Server: ".$data['name'].", ID: ".$data['id']." \n";
      $options['ldapservers_id'] = $data['id'];
      import ($options);
   }
}


/**
 * Function to import or synchronise all the users from an ldap directory
 *
 * @param $options   array
**/
function import(array $options) {
   global $CFG_GLPI;

   $results = [AuthLDAP::USER_IMPORTED     => 0,
                    AuthLDAP::USER_SYNCHRONIZED => 0,
                    AuthLDAP::USER_DELETED_LDAP => 0];
   //The ldap server id is passed in the script url (parameter server_id)
   $limitexceeded = false;
   $actions_to_do = [];

   switch ($options['action']) {
      case AuthLDAP::ACTION_IMPORT :
         $actions_to_do = [AuthLDAP::ACTION_IMPORT];
        break;

      case AuthLDAP::ACTION_SYNCHRONIZE :
         $actions_to_do = [AuthLDAP::ACTION_SYNCHRONIZE];
        break;

      case AuthLDAP::ACTION_ALL :
         $actions_to_do = [AuthLDAP::ACTION_IMPORT, AuthLDAP::ACTION_ALL];
        break;
   }

   foreach ($actions_to_do as $action_to_do) {
      $options['mode']         = $action_to_do;
      $options['authldaps_id'] = $options['ldapservers_id'];
      $authldap = new \AuthLdap();
      $authldap->getFromDB($options['authldaps_id']);
      $users                   = AuthLdap::getAllUsers($options, $results, $limitexceeded);
      $contact_ok              = true;

      if (is_array($users)) {
         foreach ($users as $user) {
            //check if user exists
            $user_sync_field = null;
            if ($authldap->isSyncFieldEnabled()) {
               $sync_field = $authldap->fields['sync_field'];
               if (isset($user[$sync_field])) {
                  $user_sync_field = $authldap::getFieldValue($user, $sync_field);
               }
            }
            $dbuser = $authldap->getLdapExistingUser(
               $user['user'],
               $options['authldaps_id'],
               $user_sync_field
            );

            if ($dbuser && $action_to_do == AuthLdap::ACTION_IMPORT) {
               continue;
            }

            $user_field = 'name';
            $id_field = $authldap->fields['login_field'];
            $value = $user['user'];
            if ($authldap->isSyncFieldEnabled() && (!$dbuser || !empty($dbuser->fields['sync_field']))) {
               $value = $user_sync_field;
               $user_field = 'sync_field';
               $id_field   = $authldap->fields['sync_field'];
            }

            $result = AuthLdap::ldapImportUserByServerId(
               [
                  'method'             => AuthLDAP::IDENTIFIER_LOGIN,
                  'value'              => $value,
                  'identifier_field'   => $id_field,
                  'user_field'         => $user_field
               ],
               $action_to_do,
               $options['ldapservers_id']
            );

            if ($result) {
               $results[$result['action']] += 1;
            }
            echo ".";
         }
      } else if (!$users) {
         $contact_ok = false;
      }
   }

   if ($limitexceeded) {
      echo "\nLDAP Server size limit exceeded";
      if ($CFG_GLPI['user_deleted_ldap']) {
         echo ": user deletion disabled\n";
      }
      echo "\n";
   }
   if ($contact_ok) {
      echo "\nImported: ".$results[AuthLDAP::USER_IMPORTED]."\n";
      echo "Synchronized: ".$results[AuthLDAP::USER_SYNCHRONIZED]."\n";
      echo "Deleted from LDAP: ".$results[AuthLDAP::USER_DELETED_LDAP]."\n";
   } else {
      echo "Cannot contact LDAP server!\n";
   }
   echo "\n\n";
}
