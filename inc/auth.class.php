<?php
/*
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
 *  Identification class used to login
**/
class Auth extends CommonGLPI {

   //! Error string
   public $err ='';
   /** User class variable
    * @see User
   **/
   public $user;
   //! External authentification variable : boolean
   public $extauth = 0;
   ///External authentifications methods;
   public $authtypes;
   ///Indicates if the user is authenticated or not
   public $auth_succeded = 0;
   ///Indicates if the user is already present in database
   public $user_present = 0;
   //Indicates if the user is deleted in the directory (doesn't mean that it can login)
   public $user_deleted_ldap = 0;
   /// LDAP connection descriptor
   public $ldap_connection;
   //Store user LDAP dn
   public $user_dn = false;

   const DB_GLPI  = 1;
   const MAIL     = 2;
   const LDAP     = 3;
   const EXTERNAL = 4;
   const CAS      = 5;
   const X509     = 6;
   const API      = 7;
   const NOT_YET_AUTHENTIFIED = 0;


   /**
    * Constructor
   **/
   function __construct() {
      $this->user = new User();
   }


   /**
    * @since version 0.85
   **/
   static function canView() {
      return Session::haveRight('config', READ);
   }


   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since version 0.85
   **/
   static function getMenuContent() {

      $menu = array();
      if (Config::canUpdate()) {
            $menu['title']                              = __('Authentication');
            $menu['page']                               = '/front/setup.auth.php';

            $menu['options']['ldap']['title']           = AuthLDAP::getTypeName(Session::getPluralNumber());
            $menu['options']['ldap']['page']            = '/front/authldap.php';
            $menu['options']['ldap']['links']['search'] = '/front/authldap.php';
            $menu['options']['ldap']['links']['add']    = '' .'/front/authldap.form.php';

            $menu['options']['imap']['title']           = AuthMail::getTypeName(Session::getPluralNumber());
            $menu['options']['imap']['page']            = '/front/authmail.php';
            $menu['options']['imap']['links']['search'] = '/front/authmail.php';
            $menu['options']['imap']['links']['add']    = '' .'/front/authmail.form.php';

            $menu['options']['others']['title']         = __('Others');
            $menu['options']['others']['page']          = '/front/auth.others.php';

            $menu['options']['settings']['title']       = __('Setup');
            $menu['options']['settings']['page']        = '/front/auth.settings.php';

      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   /**
    * Is the user exists in the DB
    * @param $options array containing condition : array('name'=>'glpi')
    *                                              or array('email' => 'test at test.com')
    *
    * @return 0 (Not in the DB -> check external auth),
    *         1 ( Exist in the DB with a password -> check first local connection and external after),
    *         2 (Exist in the DB with no password -> check only external auth)
   **/
   function userExists($options=array()) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_users`
                LEFT JOIN `glpi_useremails` ON (`glpi_users`.`id` = `glpi_useremails`.`users_id`)
                WHERE ";
      $first = true;
      foreach ($options as $key => $value) {
         if ($first) {
            $first = false;
         } else {
            $query .= " AND ";
         }
         $query.=" `$key`='$value'";
      }

      $result = $DB->query($query);
      if ($DB->numrows($result) == 0) {
         $this->addToError(__('Incorrect username or password'));
         return 0;

      } else {
         $pwd = $DB->result($result, 0, "password");

         if (empty($pwd)) {
            //If the user has an LDAP DN, then store it in the Auth object
            $user_dn = $DB->result($result, 0, "user_dn");
            if ($user_dn) {
               $this->user_dn = $user_dn;
            }
            return 2;

         }
         return 1;
      }
   }


   /**
    * Try a IMAP/POP connection
    *
    * @param $host   IMAP/POP host to connect
    * @param $login  Login to try
    * @param $pass   Password to try
    *
    * @return boolean : connection success
   **/
   function connection_imap($host, $login, $pass) {

      // we prevent some delay...
      if (empty($host)) {
         return false;
      }

      $oldlevel = error_reporting(16);
      // No retry (avoid lock account when password is not correct)
      if ($mbox = imap_open($host, $login, $pass, NULL, 1)) {
         imap_close($mbox);
         error_reporting($oldlevel);
         return true;
      }
      $this->addToError(imap_last_error());

      error_reporting($oldlevel);
      return false;
   }


   /**
    * Find a user in a LDAP and return is BaseDN
    * Based on GRR auth system
    *
    * @param $ldap_method  ldap_method array to use
    * @param $login        User Login
    * @param $password     User Password
    *
    * @return String : basedn of the user / false if not founded
   **/
   function connection_ldap($ldap_method, $login, $password) {

      // we prevent some delay...
      if (empty($ldap_method['host'])) {
         return false;
      }

      $this->ldap_connection   = AuthLdap::tryToConnectToServer($ldap_method, $login, $password);
      $this->user_deleted_ldap = false;

      if ($this->ldap_connection) {
         $params['method']                             = AuthLDAP::IDENTIFIER_LOGIN;
         $params['fields'][AuthLDAP::IDENTIFIER_LOGIN] = $ldap_method['login_field'];
         $infos = AuthLdap::searchUserDn($this->ldap_connection,
                                         array('basedn'            => $ldap_method['basedn'],
                                               'login_field'       => $ldap_method['login_field'],
                                               'search_parameters' => $params,
                                               'user_params'
                                                   => array('method' => AuthLDAP::IDENTIFIER_LOGIN,
                                                            'value'  => $login),
                                               'condition'         => $ldap_method['condition'],
                                               'user_dn'           => $this->user_dn));
         $dn = $infos['dn'];
         if (!empty($dn) && @ldap_bind($this->ldap_connection, $dn, $password)) {

            //Hook to implement to restrict access by checking the ldap directory
            if (Plugin::doHookFunction("restrict_ldap_auth", $dn)) {
               return $dn;
            }
            $this->addToError(__('User not authorized to connect in GLPI'));
            //Use is present by has no right to connect because of a plugin
            return false;

         } else {
            // Incorrect login
            $this->addToError(__('Incorrect username or password'));
            //Use is not present anymore in the directory!
            if ($dn == '') {
               $this->user_deleted_ldap = true;
            }
            return false;
         }

      } else {
         $this->addToError(__('Unable to connect to the LDAP directory'));
         //Directory is not available
         return false;
      }
   }


   /**
    * Check is a password match the stored hash
    *
    * @since version 0.85
    *
    * @param $pass string
    * @param $hash string
    *
    * @return boolean
   **/
   static function checkPassword($pass, $hash) {

      $tmp = password_get_info($hash);

      if (isset($tmp['algo']) && $tmp['algo']) {
         $ok = password_verify($pass, $hash);

      } else if (strlen($hash)==32) {
         $ok = md5($pass) == $hash;

      } else if (strlen($hash)==40) {
         $ok = sha1($pass) == $hash;

      } else {
         $salt = substr($hash,0,8);
         $ok = ($salt.sha1($salt.$pass) == $hash);
      }

      return $ok;
   }


   /**
    * Is the hash stored need to be regenerated
    *
    * @since version 0.85
    *
    * @param $hash string
    *
    * @return boolean
   **/
   static function needRehash($hash) {

      return password_needs_rehash($hash, PASSWORD_DEFAULT);
   }


   /**
    * Compute the hash for a password
    *
    * @since version 0.85
    *
    * @param $pass string
    *
    * @return string
   **/
   static function getPasswordHash($pass) {

      return password_hash($pass, PASSWORD_DEFAULT);
   }


   /**
    * Find a user in the GLPI DB
    *
    * @param $name      User Login
    * @param $password  User Password
    *
    * try to connect to DB
    * update the instance variable user with the user who has the name $name
    * and the password is $password in the DB.
    * If not found or can't connect to DB updates the instance variable err
    * with an eventual error message
    *
    * @return boolean : user in GLPI DB with the right password
   **/
   function connection_db($name, $password) {
      global $DB;

      // sanity check... we prevent empty passwords...
      if (empty($password)) {
         $this->addToError(__('Password field is empty'));
         return false;
      }

      $query = "SELECT `id`,`password`
                FROM `glpi_users`
                WHERE `name` = '" . $name . "'";
      $result = $DB->query($query);

      if (!$result) {
         $this->addToError(__('Incorrect username or password'));
         return false;
      }
      if ($result) {
         if ($DB->numrows($result) == 1) {
            $password_db = $DB->result($result, 0, "password");

            if (self::checkPassword($password, $password_db)) {

               // Update password if needed
               if (self::needRehash($password_db)) {
                  $input['id']        = $DB->result($result, 0, "id");
                  // Set glpiID to allow passwod update
                  $_SESSION['glpiID'] = $input['id'];
                  $input['password']  = $password;
                  $input['password2'] = $password;
                  $user               = new User();
                  $user->update($input);
               }
               return true;
            }
         }
         $this->addToError(__('Incorrect username or password'));
         return false;
      }
      $this->addToError("#".$DB->errno().": ".$DB->error());
      return false;
   } // connection_db()


   /**
    * Try to get login of external auth method
    *
    * @param $authtype external auth type (default 0)
    *
    * @return boolean : user login success
   **/
   function getAlternateAuthSystemsUserLogin($authtype=0) {
      global $CFG_GLPI;

      switch ($authtype) {
         case self::CAS :
            phpCAS::client(CAS_VERSION_2_0, $CFG_GLPI["cas_host"], intval($CFG_GLPI["cas_port"]),
                           $CFG_GLPI["cas_uri"],false);

            // no SSL validation for the CAS server
            phpCAS::setNoCasServerValidation();

            // force CAS authentication
            phpCAS::forceAuthentication();
            $this->user->fields['name'] = phpCAS::getUser();
            return true;

         case self::EXTERNAL :
            $ssovariable = Dropdown::getDropdownName('glpi_ssovariables',
                                                     $CFG_GLPI["ssovariables_id"]);
            $login_string = '';
            // MoYo : checking REQUEST create a security hole for me !
            if (isset($_SERVER[$ssovariable])) {
               $login_string = $_SERVER[$ssovariable];
            }
//             else {
//                $login_string = $_REQUEST[$ssovariable];
//             }
            $login        = $login_string;
            $pos          = stripos($login_string,"\\");
            if (!$pos === false) {
               $login = substr($login_string, $pos + 1);
            }
            if ($CFG_GLPI['existing_auth_server_field_clean_domain']) {
               $pos = stripos($login,"@");
               if (!$pos === false) {
                  $login = substr($login, 0, $pos);
               }
            }
            if (self::isValidLogin($login)) {
               $this->user->fields['name'] = $login;
               // Get data from SSO if defined
               $ret = $this->user->getFromSSO();
               if (!$ret) {
                  return false;
               }
               return true;
            }
            break;

         case self::X509 :
            // From eGroupWare  http://www.egroupware.org
            // an X.509 subject looks like:
            // CN=john.doe/OU=Department/O=Company/C=xx/Email=john@comapy.tld/L=City/
            $sslattribs = explode('/', $_SERVER['SSL_CLIENT_S_DN']);
            while ($sslattrib = next($sslattribs)) {
               list($key,$val)      = explode('=', $sslattrib);
               $sslattributes[$key] = $val;
            }
            if (isset($sslattributes[$CFG_GLPI["x509_email_field"]])
                && NotificationMail::isUserAddressValid($sslattributes[$CFG_GLPI["x509_email_field"]])
                && self::isValidLogin($sslattributes[$CFG_GLPI["x509_email_field"]])) {

               $restrict = false;
               $CFG_GLPI["x509_ou_restrict"] = trim($CFG_GLPI["x509_ou_restrict"]);
               if (!empty($CFG_GLPI["x509_ou_restrict"])) {
                  $split = explode ('$',$CFG_GLPI["x509_ou_restrict"]);

                  if (!in_array($sslattributes['OU'], $split)) {
                     $restrict = true;
                  }
               }
               $CFG_GLPI["x509_o_restrict"] = trim($CFG_GLPI["x509_o_restrict"]);
               if (!empty($CFG_GLPI["x509_o_restrict"])) {
                  $split = explode ('$',$CFG_GLPI["x509_o_restrict"]);

                  if (!in_array($sslattributes['O'], $split)) {
                     $restrict = true;
                  }
               }
               $CFG_GLPI["x509_cn_restrict"] = trim($CFG_GLPI["x509_cn_restrict"]);
               if (!empty($CFG_GLPI["x509_cn_restrict"])) {
                  $split = explode ('$',$CFG_GLPI["x509_cn_restrict"]);

                  if (!in_array($sslattributes['CN'], $split)) {
                     $restrict = true;
                  }
               }

               if (!$restrict) {
                  $this->user->fields['name'] = $sslattributes[$CFG_GLPI["x509_email_field"]];

                  // Can do other things if need : only add it here
                  $this->user->fields['email'] = $this->user->fields['name'];
                  return true;
               }
            }
            break;

         case self::API:
            if ($CFG_GLPI['enable_api_login_external_token']) {
               $user = new User();
               if ($user->getFromDBbyToken($_REQUEST['user_token'])) {
                  $this->user->fields['name'] = $user->fields['name'];
                  return true;
               }
            } else {
               $this->addToError(__("Login with external token disabled"));
            }
            break;
      }
      return false;
   }


   /**
    * Get the current identification error
    *
    * @return string : current identification error
   **/
   function getErr() {
      return $this->err;
   }


   /**
    * Get the current user object
    *
    * @return object : current user
   **/
   function getUser() {
      return $this->user;
   }


   /**
    * Get all the authentication methods parameters
    * and return it as an array
    *
    * @return nothing
   **/
   function getAuthMethods() {

      //Return all the authentication methods in an array
      $this->authtypes = array('ldap' => getAllDatasFromTable('glpi_authldaps'),
                               'mail' => getAllDatasFromTable('glpi_authmails'));
   }


   /**
    * Add a message to the global identification error message
    *
    * @param $message the message to add
    *
    * @return nothing
   **/
   function addToError($message) {

      if (!strstr($this->err,$message)) {
         $this->err .= $message."<br>\n";
      }
   }


   /**
    * Manage use authentication and initialize the session
    *
    * @param $login_name      string
    * @param $login_password  string
    * @param $noauto          boolean (false by default)
    *
    * @return boolean (success)
   */
   function Login($login_name, $login_password, $noauto=false) {
      global $DB, $CFG_GLPI;

      $this->getAuthMethods();
      $this->user_present  = 1;
      $this->auth_succeded = false;
      //In case the user was deleted in the LDAP directory
      $user_deleted_ldap   = false;

      // Trim login_name : avoid LDAP search errors
      $login_name = trim($login_name);

      if (!$noauto && ($authtype = self::checkAlternateAuthSystems())) {
         if ($this->getAlternateAuthSystemsUserLogin($authtype)
             && !empty($this->user->fields['name'])) {
            // Used for log when login process failed
            $login_name                        = $this->user->fields['name'];
            $this->auth_succeded               = true;
            $this->user_present                = $this->user->getFromDBbyName(addslashes($login_name));
            $this->extauth                     = 1;
            $user_dn                           = false;

            $ldapservers = '';
            //if LDAP enabled too, get user's infos from LDAP
            if (Toolbox::canUseLdap()) {
               $ldapservers = array();
               //User has already authenticate, at least once : it's ldap server if filled
               if (isset($this->user->fields["auths_id"])
                   && ($this->user->fields["auths_id"] > 0)) {
                  $authldap = new AuthLdap();
                  //If ldap server is enabled
                  if ($authldap->getFromDB($this->user->fields["auths_id"])
                      && $authldap->fields['is_active']) {
                     $ldapservers[] = $authldap->fields;
                  }
               //User has never beeen authenticated : try all active ldap server to find the right one
               } else {
                  foreach (getAllDatasFromTable('glpi_authldaps', "`is_active`='1'") as $ldap_config) {
                     $ldapservers[] = $ldap_config;
                  }
               }
               foreach ($ldapservers as $ldap_method) {
                  $ds = AuthLdap::connectToServer($ldap_method["host"],
                                                  $ldap_method["port"],
                                                  $ldap_method["rootdn"],
                                                  Toolbox::decrypt($ldap_method["rootdn_passwd"],
                                                                   GLPIKEY),
                                                  $ldap_method["use_tls"],
                                                  $ldap_method["deref_option"]);

                  if ($ds) {
                     $params['method']                             = AuthLdap::IDENTIFIER_LOGIN;
                     $params['fields'][AuthLdap::IDENTIFIER_LOGIN] = $ldap_method["login_field"];
                     $user_dn
                        = AuthLdap::searchUserDn($ds,
                                                 array('basedn'      => $ldap_method["basedn"],
                                                       'login_field' => $ldap_method['login_field'],
                                                       'search_parameters'
                                                                     => $params,
                                                       'user_params'
                                                         => array('method' => AuthLDAP::IDENTIFIER_LOGIN,
                                                                  'value'  => $login_name),
                                                       'condition'   => $ldap_method["condition"]));
                     if ($user_dn) {
                        $this->user->fields['auths_id'] = $ldap_method['id'];
                        $this->user->getFromLDAP($ds, $ldap_method, $user_dn['dn'], $login_name,
                                                 !$this->user_present);
                        break;
                     }
                  }
               }
            }
            if ((count($ldapservers) == 0)
                && ($authtype == self::EXTERNAL)) {
               // Case of using external auth and no LDAP servers, so get data from external auth
               $this->user->getFromSSO();
            } else {
               //If user is set as present in GLPI but no LDAP DN found : it means that the user
               //is not present in an ldap directory anymore
               if ($this->user->fields['authtype'] == self::LDAP
                   && !$user_dn
                   && $this->user_present) {
                  $user_deleted_ldap       = true;
                  $this->user_deleted_ldap = true;
               }
            }
            // Reset to secure it
            $this->user->fields['name']       = $login_name;
            $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];

         } else {
            $this->addToError(__('Empty login or password'));
         }
      }

      // If not already auth
      if (!$this->auth_succeded) {
         if (empty($login_name) || strstr($login_name, "\0")
             || empty($login_password) || strstr($login_password, "\0")) {
            $this->addToError(__('Empty login or password'));
         } else {
            // exists=0 -> user doesn't yet exist
            // exists=1 -> user is present in DB with password
            // exists=2 -> user is present in DB but without password
            $exists = $this->userExists(array('name' => addslashes($login_name)));

            // Pas en premier car sinon on ne fait pas le blankpassword
            // First try to connect via le DATABASE
            if ($exists == 1) {
               // Without UTF8 decoding
               if (!$this->auth_succeded) {
                  $this->auth_succeded = $this->connection_db(addslashes($login_name),
                                                              $login_password);
                  if ($this->auth_succeded) {
                     $this->extauth                  = 0;
                     $this->user_present = $this->user->getFromDBbyName(addslashes($login_name));
                     $this->user->fields["authtype"] = self::DB_GLPI;
                     $this->user->fields["password"] = $login_password;
                  }
               }

            } else if ($exists == 2) {
               //The user is not authenticated on the GLPI DB, but we need to get information about him
               //to find out his authentication method
               $this->user->getFromDBbyName(addslashes($login_name));

               //If the user has already been logged, the method_auth and auths_id are already set
               //so we test this connection first
               switch ($this->user->fields["authtype"]) {
                  case self::CAS :
                  case self::EXTERNAL :
                  case self::LDAP :
                     if (Toolbox::canUseLdap()) {
                        AuthLdap::tryLdapAuth($this, $login_name, $login_password,
                                              $this->user->fields["auths_id"],
                                              toolbox::addslashes_deep($this->user->fields["user_dn"]));
                        if (!$this->auth_succeded && $this->user_deleted_ldap) {
                           $user_deleted_ldap = true;
                        }
                     }
                     break;

                  case self::MAIL :
                     if (Toolbox::canUseImapPop()) {
                        AuthMail::tryMailAuth($this, $login_name, $login_password,
                                              $this->user->fields["auths_id"]);
                     }
                     break;

                  case self::NOT_YET_AUTHENTIFIED :
                     break;
               }

            } else if (!$exists) {
               //test all ldap servers only is user is not present in glpi's DB
               if (!$this->auth_succeded && Toolbox::canUseLdap()) {
                  AuthLdap::tryLdapAuth($this, $login_name, $login_password, 0, false, false);
               }

               //test all imap/pop servers
               if (!$this->auth_succeded && Toolbox::canUseImapPop()) {
                  AuthMail::tryMailAuth($this, $login_name, $login_password, 0, false);
               }
            }
            // Fin des tests de connexion
         }
      }

      if ($user_deleted_ldap) {
         User::manageDeletedUserInLdap($this->user->fields["id"]);
         $this->auth_succeded = false;
      }
      // Ok, we have gathered sufficient data, if the first return false the user
      // is not present on the DB, so we add him.
      // if not, we update him.
      if ($this->auth_succeded) {

         //Set user an not deleted from LDAP
         $this->user->fields['is_deleted_ldap'] = 0;

         // Prepare data
         $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];
         if ($this->extauth) {
            $this->user->fields["_extauth"] = 1;
         }

         if ($DB->isSlave()) {
            if (!$this->user_present) { // Can't add in slave mode
               $this->addToError(__('User not authorized to connect in GLPI'));
               $this->auth_succeded = false;
            }
         } else {
             if ($this->user_present) {
               // First stripslashes to avoid double slashes
               $input = Toolbox::stripslashes_deep($this->user->fields);
               // Then ensure addslashes
               $input = Toolbox::addslashes_deep($input);

               $this->user->update($input);
            } else if ($CFG_GLPI["is_users_auto_add"]) {
               // Auto add user
               // First stripslashes to avoid double slashes
               $input = Toolbox::stripslashes_deep($this->user->fields);
               // Then ensure addslashes
               $input = Toolbox::addslashes_deep($input);
               unset ($this->user->fields);
               $this->user->add($input);
            } else {
               // Auto add not enable so auth failed
               $this->addToError(__('User not authorized to connect in GLPI'));
               $this->auth_succeded = false;
            }
         }
      }

      // Log Event (if possible)
      if (!$DB->isSlave()) {
         // GET THE IP OF THE CLIENT
         $ip = (getenv("HTTP_X_FORWARDED_FOR")?getenv("HTTP_X_FORWARDED_FOR"):getenv("REMOTE_ADDR"));

         if ($this->auth_succeded) {
            if (GLPI_DEMO_MODE) {
               // not translation in GLPI_DEMO_MODE
               Event::log(-1, "system", 3, "login", $login_name." log in from ".$ip);
            } else {
               //TRANS: %1$s is the login of the user and %2$s its IP address
               Event::log(-1, "system", 3, "login", sprintf(__('%1$s log in from IP %2$s'),
                                                            $login_name, $ip));
            }

         } else {
            if (GLPI_DEMO_MODE) {
               Event::log(-1, "system", 3, "login", "login",
                          "Connection failed for " . $login_name . " ($ip)");
            } else {
               //TRANS: %1$s is the login of the user and %2$s its IP address
               Event::log(-1, "system", 3, "login", sprintf(__('Failed login for %1$s from IP %2$s'),
                                                            $login_name, $ip));
            }
         }
      }

      Session::init($this);

      if ($noauto) {
         $_SESSION["noAUTO"] = 1;
      }

      return $this->auth_succeded;
   }


   /**
   * Print all the authentication methods
   *
   * Parameters which could be used in options array :
   *    - name : string / name of the select (default is auths_id)
   *
   * @param $options array of possible options / not used here
   *
   *@return Nothing (display)
   */
   static function dropdown($options=array()) {
      global $DB;

      $p['name']                = 'auths_id';
      $p['value']               = 0;
      $p['display']             = true;
      $p['display_emptychoice'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $methods[self::DB_GLPI] = __('Authentication on GLPI database');

      $sql = "SELECT COUNT(*) AS cpt
              FROM `glpi_authldaps`
              WHERE `is_active` = 1";
      $result = $DB->query($sql);

      if ($DB->result($result, 0, "cpt") > 0) {
         $methods[self::LDAP]     = __('Authentication on a LDAP directory');
         $methods[self::EXTERNAL] = __('External authentications');
      }

      $sql = "SELECT COUNT(*) AS cpt
              FROM `glpi_authmails`
              WHERE `is_active` = 1";
      $result = $DB->query($sql);

      if ($DB->result($result,0,"cpt") > 0) {
         $methods[self::MAIL] = __('Authentication on mail server');
      }

      return Dropdown::showFromArray($p['name'], $methods, $p);
   }


   /**
    * Get name of an authentication method
    *
    * @param $authtype  Authentication method
    * @param $auths_id  Authentication method ID
    * @param $link      show links to config page ? (default 0)
    * @param $name      override the name if not empty (default '')
    *
    * @return string
    */
   static function getMethodName($authtype, $auths_id, $link=0, $name='') {

      switch ($authtype) {
         case self::LDAP :
            $auth = new AuthLdap();
            if ($auth->getFromDB($auths_id)) {
               //TRANS: %1$s is the auth method type, %2$s the auth method name or link
               return sprintf(__('%1$s: %2$s'), AuthLdap::getTypeName(1), $auth->getLink());
            }
            return sprintf(__('%1$s: %2$s'), __('LDAP directory'), $name);

         case self::MAIL :
            $auth = new AuthMail();
            if ($auth->getFromDB($auths_id)) {
               //TRANS: %1$s is the auth method type, %2$s the auth method name or link
               return sprintf(__('%1$s: %2$s'), AuthLdap::getTypeName(1), $auth->getLink());
            }
            return sprintf(__('%1$s: %2$s'),__('Email server'), $name);

         case self::CAS :
            if ($auths_id > 0) {
               $auth = new AuthLdap();
               if ($auth->getFromDB($auths_id)) {
                  return sprintf(__('%1$s: %2$s'),
                                 sprintf(__('%1$s + %2$s'),
                                         __('CAS'),AuthLdap::getTypeName(1)),
                                 $auth->getLink());
               }
            }
            return __('CAS');

         case self::X509 :
            if ($auths_id > 0) {
               $auth = new AuthLdap();
               if ($auth->getFromDB($auths_id)) {
                  return sprintf(__('%1$s: %2$s'),
                                 sprintf(__('%1$s + %2$s'),
                                         __('x509 certificate authentication'),
                                         AuthLdap::getTypeName(1)),
                                 $auth->getLink());
               }
            }
            return __('x509 certificate authentication');

         case self::EXTERNAL :
            if ($auths_id > 0) {
               $auth = new AuthLdap();
               if ($auth->getFromDB($auths_id)) {
                  return sprintf(__('%1$s: %2$s'),
                                 sprintf(__('%1$s + %2$s'),
                                         __('Other'), AuthLdap::getTypeName(1)),
                                 $auth->getLink());
               }
            }
            return __('Other');

         case self::DB_GLPI :
            return __('GLPI internal database');

         case self::API :
            return __("API");

         case self::NOT_YET_AUTHENTIFIED :
            return __('Not yet authenticated');
      }
      return '';
   }


   /**
    * Get all the authentication methods parameters for a specific authtype
    *  and auths_id and return it as an array
    *
    * @param $authtype Authentication method
    * @param $auths_id Authentication method ID
   **/
   static function getMethodsByID($authtype, $auths_id) {
      global $CFG_GLPI;

      switch ($authtype) {
         case self::X509 :
         case self::EXTERNAL :
         case self::CAS :
         case self::LDAP :
            $auth = new AuthLdap();
            if ($auths_id>0 && $auth->getFromDB($auths_id)) {
               return ($auth->fields);
            }
            break;

         case self::MAIL :
            $auth = new AuthMail();
            if ($auths_id>0 && $auth->getFromDB($auths_id)) {
               return ($auth->fields);
            }
            break;
      }
      return array();
   }


   /**
    * Is an external authentication used ?
    *
    * @return boolean
   **/
   static function useAuthExt() {

      //Get all the ldap directories
      if (AuthLdap::useAuthLdap()) {
         return true;
      }

      if (AuthMail::useAuthMail()) {
         return true;
      }

      if (!empty($CFG_GLPI["x509_email_field"])) {
         return true;
      }

      // Existing auth method
      if (!empty($CFG_GLPI["ssovariables_id"])) {
         return true;
      }

      // Using CAS server
      if (!empty($CFG_GLPI["cas_host"])) {
         return true;
      }

      // Using API login with personnal token
      if (!empty($_REQUEST['user_token'])) {
         return true;
      }

      return false;
   }


   /**
    * Is an alternate auth ?
    *
    * @param $authtype auth type
    *
    * @return boolean
   **/
   static function isAlternateAuth($authtype) {
      return in_array($authtype, array(self::X509, self::CAS, self::EXTERNAL, self::API));
   }


   /**
    * Check alternate authentication systems
    *
    * @param $redirect           need to redirect (true) or get type of Auth system which match
    *                            (false by default)
    * @param $redirect_string    redirect string if exists (default '')
    *
    * @return nothing if redirect is true, else Auth system ID
   **/
   static function checkAlternateAuthSystems($redirect=false, $redirect_string='') {
      global $CFG_GLPI;

      if (isset($_GET["noAUTO"]) || isset($_POST["noAUTO"])) {
         return false;
      }
      $redir_string = "";
      if (!empty($redirect_string)) {
         $redir_string = "?redirect=".$redirect_string;
      }
      // Using x509 server
      if (!empty($CFG_GLPI["x509_email_field"])
          && isset($_SERVER['SSL_CLIENT_S_DN'])
          && strstr($_SERVER['SSL_CLIENT_S_DN'], $CFG_GLPI["x509_email_field"])) {

         if ($redirect) {
            Html::redirect($CFG_GLPI["root_doc"]."/front/login.php".$redir_string);
         } else {
            return self::X509;
         }
      }
      // Existing auth method
      //Look for the field in $_SERVER AND $_REQUEST
      // MoYo : checking REQUEST create a security hole for me !
      $ssovariable = Dropdown::getDropdownName('glpi_ssovariables', $CFG_GLPI["ssovariables_id"]);
      if ($CFG_GLPI["ssovariables_id"]
          && ((isset($_SERVER[$ssovariable]) && !empty($_SERVER[$ssovariable]))
              /*|| (isset($_REQUEST[$ssovariable]) && !empty($_REQUEST[$ssovariable]))*/)) {

         if ($redirect) {
            Html::redirect($CFG_GLPI["root_doc"]."/front/login.php".$redir_string);
         } else {
            return self::EXTERNAL;
         }
      }

      // Using CAS server
      if (!empty($CFG_GLPI["cas_host"])) {
         if ($redirect) {
            Html::redirect($CFG_GLPI["root_doc"]."/front/login.php".$redir_string);
         } else {
            return self::CAS;
         }
      }

      // using user token for api login
      if (!empty($_REQUEST['user_token'])) {
         return self::API;
      }
   return false;
   }


   /** Display refresh button in the user page
    *
    * @param $user User object
    *
    * @return nothing
   **/
   static function showSynchronizationForm(User $user) {
      global $DB, $CFG_GLPI;

      if (Session::haveRight("user", User::UPDATEAUTHENT)) {
         echo "<form method='post' action='".Toolbox::getItemTypeFormURL('User')."'>";
         echo "<div class='firstbloc'>";

         switch($user->getField('authtype')) {
            case self::CAS :
            case self::EXTERNAL :
            case self::X509 :
            case self::LDAP :
               //Look it the auth server still exists !
               // <- Bad idea : id not exists unable to change anything
               $sql = "SELECT `name`
                       FROM `glpi_authldaps`
                       WHERE `id` = '" . $user->getField('auths_id') . "'
                             AND `is_active` = 1";
               $result = $DB->query($sql);
               if ($DB->numrows($result) > 0) {
                  echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
                  echo "<input type='hidden' name='id' value='".$user->getID()."'>";
                  echo "<input class=submit type='submit' name='force_ldap_resynch' value='" .
                         __s('Force synchronization') . "'>";
                  echo "</td></tr></table>";
               }
               break;

            case self::DB_GLPI :
            case self::MAIL :
               break;
         }
         echo "</div>";

         echo "<div class='spaced'>";
         echo "<table class='tab_cadre'>";
         echo "<tr><th>".__('Change of the authentication method')."</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         $rand             = self::dropdown(array('name' => 'authtype'));
         $paramsmassaction = array('authtype' => '__VALUE__',
                                   'name'     => 'change_auth_method');
         Ajax::updateItemOnSelectEvent("dropdown_authtype$rand", "show_massiveaction_field",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionAuthMethods.php",
                                       $paramsmassaction);
         echo "<input type='hidden' name='id' value='" . $user->getID() . "'>";
         echo "<span id='show_massiveaction_field'></span>";
         echo "</td></tr></table>";
         echo "</div>";
         Html::closeForm();
      }
   }


    /**
     * Determine if a login is valid
     *
     * @param $login string: login to check
     *
     * @return boolean
    **/
    static function isValidLogin($login) {
       return preg_match( "/^[[:alnum:]@.\-_ ]+$/iu", $login);
    }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'User' :
               if (Session::haveRight("user", User::UPDATEAUTHENT)) {
                  return __('Synchronization');
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='User') {
         self::showSynchronizationForm($item);
      }
      return true;
   }


   /**
    * Form for configuration authentification
   **/
   static function showOtherAuthList() {
      global $DB, $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }
      echo "<form name=cas action='".$CFG_GLPI['root_doc']."/front/auth.others.php' method='post'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";

      // CAS config
      echo "<tr><th>" . __('CAS authentication').'</th><th>';
      if (!empty($CFG_GLPI["cas_host"])) {
         echo _x('authentication','Enabled');
      }
      echo "</th></tr>\n";

      if (function_exists('curl_init')) {

         //TRANS: for CAS SSO system
         echo "<tr class='tab_bg_2'><td class='center'>" . __('CAS Host') . "</td>";
         echo "<td><input type='text' name='cas_host' value=\"".$CFG_GLPI["cas_host"]."\"></td></tr>\n";
         //TRANS: for CAS SSO system
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Port') . "</td>";
         echo "<td><input type='text' name='cas_port' value=\"".$CFG_GLPI["cas_port"]."\"></td></tr>\n";
         //TRANS: for CAS SSO system
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Root directory (optional)')."</td>";
         echo "<td><input type='text' name='cas_uri' value=\"".$CFG_GLPI["cas_uri"]."\"></td></tr>\n";
         //TRANS: for CAS SSO system
         echo "<tr class='tab_bg_2'><td class='center'>" . __('Log out fallback URL') . "</td>";
         echo "<td><input type='text' name='cas_logout' value=\"".$CFG_GLPI["cas_logout"]."\"></td>".
              "</tr>\n";
      } else {
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<p class='red'>".__("The CURL extension for your PHP parser isn't installed");
         echo "</p>";
         echo "<p>" .__('Impossible to use CAS as external source of connection')."</p></td></tr>\n";
      }
      // X509 config
      echo "<tr><th>" . __('x509 certificate authentication')."</th><th>";
      if (!empty($CFG_GLPI["x509_email_field"])) {
         echo _x('authentication','Enabled');
      }
      echo "</th></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>". __('Email attribute for x509 authentication') ."</td>";
      echo "<td><input type='text' name='x509_email_field' value=\"".$CFG_GLPI["x509_email_field"]."\">";
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>". sprintf(__('Restrict %s field for x509 authentication (separator $)'),'OU') ."</td>";
      echo "<td><input type='text' name='x509_ou_restrict' value=\"".$CFG_GLPI["x509_ou_restrict"]."\">";
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>". sprintf(__('Restrict %s field for x509 authentication (separator $)'),'CN') ."</td>";
      echo "<td><input type='text' name='x509_cn_restrict' value=\"".$CFG_GLPI["x509_cn_restrict"]."\">";
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>". sprintf(__('Restrict %s field for x509 authentication (separator $)'),'O') ."</td>";
      echo "<td><input type='text' name='x509_o_restrict' value=\"".$CFG_GLPI["x509_o_restrict"]."\">";
      echo "</td></tr>\n";


      //Other configuration
      echo "<tr><th>" . __('Other authentication sent in the HTTP request')."</th><th>";
      if (!empty($CFG_GLPI["ssovariables_id"])) {
         echo _x('authentication', 'Enabled');
      }
      echo "</th></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>". __('Field storage of the login in the HTTP request')."</td>";
      echo "<td>";
      SsoVariable::dropdown(array('name'  => 'ssovariables_id',
                                  'value' => $CFG_GLPI["ssovariables_id"]));
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Remove the domain of logins like login@domain')."</td><td>";
      Dropdown::showYesNo('existing_auth_server_field_clean_domain',
                          $CFG_GLPI['existing_auth_server_field_clean_domain']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Surname') . "</td>";
      echo "<td><input type='text' name='realname_ssofield' value='".
                 $CFG_GLPI['realname_ssofield']."'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('First name') . "</td>";
      echo "<td><input type='text' name='firstname_ssofield' value='".
                 $CFG_GLPI['firstname_ssofield']."'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Comments') . "</td>";
      echo "<td><input type='text' name='comment_ssofield' value='".
                 $CFG_GLPI['comment_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Administrative number') . "</td>";
      echo "<td><input type='text' name='registration_number_ssofield' value='".
                  $CFG_GLPI['registration_number_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Email') . "</td>";
      echo "<td><input type='text' name='email1_ssofield' value='".$CFG_GLPI['email1_ssofield']."'>";
      echo "</td>";
       echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . sprintf(__('%1$s %2$s'),_n('Email','Emails',1), '2') . "</td>";
      echo "<td><input type='text' name='email2_ssofield' value='".$CFG_GLPI['email2_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . sprintf(__('%1$s %2$s'),_n('Email','Emails',1),  '3') . "</td>";
      echo "<td><input type='text' name='email3_ssofield' value='".$CFG_GLPI['email3_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . sprintf(__('%1$s %2$s'),_n('Email','Emails',1),  '4') . "</td>";
      echo "<td><input type='text' name='email4_ssofield' value='".$CFG_GLPI['email4_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Phone') . "</td>";
      echo "<td><input type='text' name='phone_ssofield' value='".$CFG_GLPI['phone_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" .  __('Phone 2') . "</td>";
      echo "<td><input type='text' name='phone2_ssofield' value='".$CFG_GLPI['phone2_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Mobile phone') . "</td>";
      echo "<td><input type='text' name='mobile_ssofield' value='".$CFG_GLPI['mobile_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . _x('person','Title') . "</td>";
      echo "<td><input type='text' name='title_ssofield' value='".$CFG_GLPI['title_ssofield']."'>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Category') . "</td>";
      echo "<td><input type='text' name='category_ssofield' value='".
                 $CFG_GLPI['category_ssofield']."'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Language') . "</td>";
      echo "<td><input type='text' name='language_ssofield' value='".
                 $CFG_GLPI['language_ssofield']."'></td></tr>";

      echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
      echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\" >";
      echo "</td></tr>\n";

      echo "</table></div>\n";
      Html::closeForm();
   }

}
