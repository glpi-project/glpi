<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

/**
 *  Class used to manage connections to an ldap directory
 */
class LdapConnection {

   /**
    * Test a LDAP connection
    *
    * @param integer $auths_id     ID of the LDAP server
    * @param integer $replicate_id use a replicate if > 0 (default -1)
    *
    * @return boolean connection succeeded?
    */
   static function testLDAPConnection(AuthLDAP $config_ldap, $replicate_id=-1) {

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
   static function connectToServer($host, $port, $login="", $password="",
                                   $use_tls=false, $deref_options=0) {

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
   function read($ds, $condition, $dn, $attrs=array(), $clean=true) {
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
               || ($check_config_value && $config_ldap->fields['can_support_pagesize'])));
   }
}
