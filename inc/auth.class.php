<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Identification class used to login
**/
class Auth {
   //! Error string
   var $err ='';
   /** User class variable
    * @see User
    */
   var $user;
   //! External authentification variable : boolean
   var $extauth = 0;
   ///External authentifications methods;
   var $authtypes;
   ///Indicates if the user is authenticated or not
   var $auth_succeded = 0;
   ///Indicates if the user is already present in database
   var $user_present = 0;
   /// Really used ??? define twice but never used...
   var $auth_parameters = array ();
   /// LDAP connection descriptor
   var $ldap_connection;

   /**
    * Constructor
   **/
   function __construct() {
      $this->user = new User();
   }

   /**
    * Is the user exists in the DB
    * @param $name user login to check
    * @return 0 (Not in the DB -> check external auth), 1 ( Exist in the DB with a password -> check first local connection and external after), 2 (Exist in the DB with no password -> check only external auth)
    *
   **/
   function userExists($name) {
      global $DB, $LANG;

      $query = "SELECT *
                FROM `glpi_users`
                WHERE `name` = '$name'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 0) {
         $this->addToError($LANG['login'][14]);
         return 0;
      } else {
         $pwd = $DB->result($result, 0, "password");
         if (empty ($pwd)) {
            return 2;
         } else {
            return 1;
         }
      }
   }

   /**
    * Try a IMAP/POP connection
    *
    * @param $host IMAP/POP host to connect
    * @param $login Login to try
    * @param $pass Password to try
    *
    * @return boolean : connection success
    *
   **/
   function connection_imap($host, $login, $pass) {
      // we prevent some delay...
      if (empty ($host)) {
         return false;
      }

      $oldlevel = error_reporting(16);
      if ($mbox = imap_open($host, $login, $pass)) {
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
    * @param $id ID of the LDAP config (use to find replicate)
    * @param $host LDAP host to connect
    * @param $port LDAP port
    * @param $use_tls use a tls connection
    * @param $basedn Basedn to use
    * @param $rdn Root dn
    * @param $rpass Root Password
    * @param $login_attr login attribute
    * @param $login User Login
    * @param $password User Password
    * @param $condition Condition used to restrict login
    * @param $deref_options Deref option used
    *
    * @return String : basedn of the user / false if not founded
   **/
   function connection_ldap($id,$host, $port, $basedn, $rdn, $rpass, $login_attr, $login, $password,
                            $condition = "", $use_tls = false,$deref_options) {
      // TODO try to pass array of connection config to minimise parameters
      global $CFG_GLPI, $LANG;

      // we prevent some delay...
      if (empty ($host)) {
         return false;
      }

      $this->ldap_connection = try_connect_ldap($host, $port, $rdn, $rpass, $use_tls,$login,
                                                $password,$deref_options,$id);

      if ($this->ldap_connection) {
         $dn = ldap_search_user_dn($this->ldap_connection, $basedn, $login_attr, $login, $condition);
         if (@ldap_bind($this->ldap_connection, $dn, $password)) {

            //Hook to implement to restrict access by checking the ldap directory
            if (doHookFunction("restrict_ldap_auth", $dn)) {
               return $dn;
            } else {
               $this->addToError($LANG['login'][16]);
               return false;
            }
         }
         $this->addToError($LANG['login'][12]);
         return false;
      } else {
         $this->addToError($LANG['ldap'][6]);
         return false;
      }
   }

   /**
    * Find a user in the GLPI DB
    *
    * @param $name User Login
    * @param $password User Password
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
      global $DB, $LANG;

      // sanity check... we prevent empty passwords...
      if (empty ($password)) {
         $this->addToError($LANG['login'][13]);
         return false;
      }

      $query = "SELECT `password`
                FROM `glpi_users`
                WHERE `name` = '" . $name . "'";
      $result = $DB->query($query);
      if (!$result) {
         $this->addToError($LANG['login'][14]);
         return false;
      }
      if ($result) {
         if ($DB->numrows($result) == 1) {
            $password_md5_db = $DB->result($result, 0, "password");
            $password_md5_post = md5($password);

            if (strcmp($password_md5_db, $password_md5_post) == 0) {
               return true;
            }
            $this->addToError($LANG['login'][12]);
            return false;
         } else {
            $this->addToError($LANG['login'][12]);
            return false;
         }
      }
      $this->addToError("#".$DB->errno().": ".$DB->error());

      return false;
   } // connection_db()

   /**
    * Try to get login of external auth method
    *
    * @param $authtype extenral auth type
    *
    * @return boolean : user login success
   **/
   function getAlternateAuthSystemsUserLogin($authtype=0) {
      global $CFG_GLPI;

      switch ($authtype) {
         case AUTH_CAS :
            include (GLPI_ROOT . "/lib/phpcas/CAS.php");
            $cas = new phpCas();
            $cas->client(CAS_VERSION_2_0, $CFG_GLPI["cas_host"], intval($CFG_GLPI["cas_port"]),
                         $CFG_GLPI["cas_uri"]);
            // force CAS authentication
            $cas->forceAuthentication();
            $this->user->fields['name'] = $cas->getUser();
            return true;
            break;

         case AUTH_EXTERNAL :
            $login_string=$_SERVER[$CFG_GLPI["existing_auth_server_field"]];
            $login=$login_string;
            $pos = stripos($login_string,"\\");
            if (!$pos === false) {
               $login = substr($login_string, $pos + 1);
            }
            if ($CFG_GLPI['existing_auth_server_field_clean_domain']) {
               $pos = stripos($login,"@");
               if (!$pos === false) {
                  $login = substr($login, 0,$pos);
               }
            }
            if (isValidLogin($login)) {
               $this->user->fields['name'] = $login;
               return true;
            }
            break;

         case AUTH_X509 :
            // From eGroupWare  http://www.egroupware.org
            // an X.509 subject looks like:
            // CN=john.doe/OU=Department/O=Company/C=xx/Email=john@comapy.tld/L=City/
            $sslattribs = explode('/',$_SERVER['SSL_CLIENT_S_DN']);
            while(($sslattrib = next($sslattribs))) {
               list($key,$val) = explode('=',$sslattrib);
               $sslattributes[$key] = $val;
            }
            if (isset($sslattributes[$CFG_GLPI["x509_email_field"]])
                && isValidEmail($sslattributes[$CFG_GLPI["x509_email_field"]])
                && isValidLogin($sslattributes[$CFG_GLPI["x509_email_field"]])) {
               $this->user->fields['name'] = $sslattributes[$CFG_GLPI["x509_email_field"]];

               // Can do other things if need : only add it here
               $this->user->fields['email']=$this->user->fields['name'];

               return true;
            }
            break;
      }
      return false;
   }

   /**
    * Init session for the user is defined
    *
    * @return nothing
   **/
   function initSession() {
      global $CFG_GLPI, $LANG;

      if ($this->auth_succeded) {
         // Restart GLPi session : complete destroy to prevent lost datas
         $save = $_SESSION["glpi_plugins"];
         $this->destroySession();
         startGlpiSession();
         $_SESSION["glpi_plugins"] = $save;

         // Normal mode for this request
         $_SESSION["glpi_use_mode"] = NORMAL_MODE;
         // Check ID exists and load complete user from DB (plugins...)
         if (isset($this->user->fields['id'])
             && $this->user->getFromDB($this->user->fields['id'])) {
            if (!$this->user->fields['is_deleted'] && $this->user->fields['is_active']) {
               $_SESSION["glpiID"] = $this->user->fields['id'];
               $_SESSION["glpiname"] = $this->user->fields['name'];
               $_SESSION["glpirealname"] = $this->user->fields['realname'];
               $_SESSION["glpifirstname"] = $this->user->fields['firstname'];
               $_SESSION["glpidefault_entity"] = $this->user->fields['entities_id'];
               $_SESSION["glpiusers_idisation"] = true;
               $_SESSION["glpiextauth"] = $this->extauth;
               $_SESSION["glpiauthtype"] = $this->user->fields['authtype'];
               $_SESSION["glpisearchcount"] = array ();
               $_SESSION["glpisearchcount2"] = array ();
               $_SESSION["glpiroot"] = $CFG_GLPI["root_doc"];
               $_SESSION["glpi_use_mode"] = $this->user->fields['use_mode'];
               $_SESSION["glpicrontimer"] = time();
               // Default tab
//               $_SESSION['glpi_tab']=1;
               $_SESSION['glpi_tabs']=array();
               $this->user->computePreferences();
               foreach ($CFG_GLPI['user_pref_field'] as $field) {
                  if (isset($this->user->fields[$field])) {
                     $_SESSION["glpi$field"] = $this->user->fields[$field];
                  }
               }
               // Init not set value for language
               if (empty($_SESSION["glpilanguage"])) {
                  $_SESSION["glpilanguage"]=$CFG_GLPI['language'];
               }
               loadLanguage();

               // glpiprofiles -> other available profile with link to the associated entities
               doHook("init_session");

               initEntityProfiles($_SESSION["glpiID"]);
               // Use default profile if exist
               if (isset($_SESSION['glpiprofiles'][$this->user->fields['profiles_id']])) {
                  changeProfile($this->user->fields['profiles_id']);
               } else { // Else use first
                  changeProfile(key($_SESSION['glpiprofiles']));
               }

               if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
                  $this->auth_succeded=false;
                  $this->addToError($LANG['login'][25]);
               }
            } else {
               $this->auth_succeded=false;
               $this->addToError($LANG['login'][20]);
            }
         } else {
            $this->auth_succeded=false;
            $this->addToError($LANG['login'][25]);
         }
      }
   }

   /**
    * Destroy the current session
    *
    * @return nothing
   **/
   function destroySession() {
      startGlpiSession();
      // Unset all of the session variables.
      session_unset();
      // destroy may cause problems (no login / back to login page)
      $_SESSION = array();
      // write_close may cause troubles (no login / back to login page)
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
    * @todo is it the correct place to this function ? Maybe split it into and add it to AuthMail and AuthLdap classes ?
    *
    * @return nothing
   **/
   function getAuthMethods() {
      global $DB;

      $authtypes_ldap = array ();
      //Get all the ldap directories
      $sql = "SELECT *
              FROM `glpi_authldaps`";
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         //Store in an array all the directories
         while ($ldap_method = $DB->fetch_array($result)) {
            $authtypes_ldap[$ldap_method["id"]] = $ldap_method;
         }
      }
      $authtypes_mail = array ();
      //Get all the pop/imap servers
      $sql = "SELECT *
              FROM `glpi_authmails`";
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         //Store all in an array
         while ($mail_method = $DB->fetch_array($result)) {
            $authtypes_mail[$mail_method["id"]] = $mail_method;
         }
      }
      //Return all the authentication methods in an array
      $this->authtypes = array ('ldap' => $authtypes_ldap,
                                'mail' => $authtypes_mail);
   }

   /**
    * Add a message to the global identification error message
    * @param $message the message to add
    *
    * @return nothing
   **/
   function addToError($message) {
      if (!strstr($this->err,$message)) {
         $this->err.=$message."<br>\n";
      }
   }

   /**
    * Manage use authentication and initialize the session
    *
    * @param $login_name string
    * @param $login_password string
    * @param $noauto boolean
    *
    * @return boolean (success)
    */
   function Login ($login_name, $login_password, $noauto=false) {
      global $DB, $CFG_GLPI, $LANG;

      $this->getAuthMethods();
      $this->user_present=1;
      $this->auth_succeded = false;


      if (!$noauto && $authtype=checkAlternateAuthSystems()) {
         if ($this->getAlternateAuthSystemsUserLogin($authtype)
             && !empty($this->user->fields['name'])) {

            $user=$this->user->fields['name'];
            // Used for log when login process failed
            $login_name=$user;

            $this->auth_succeded = true;
            $this->extauth = 1;
            $this->user_present = $this->user->getFromDBbyName(addslashes($user));
            $this->user->fields['authtype'] = $authtype;

            // if LDAP enabled too, get user's infos from LDAP
            $this->user->fields["auths_id"] = $CFG_GLPI['authldaps_id_extra'];
            if (canUseLdap()) {
               if (isset($this->authtypes["ldap"][$this->user->fields["auths_id"]])) {
                  $ldap_method = $this->authtypes["ldap"][$this->user->fields["auths_id"]];
                  $ds = connect_ldap($ldap_method["host"], $ldap_method["port"], $ldap_method["rootdn"],
                                     $ldap_method["rootdn_password"], $ldap_method["use_tls"],
                                     $ldap_method["deref_option"]);
                  if ($ds) {
                     $user_dn = ldap_search_user_dn($ds, $ldap_method["basedn"],
                                                    $ldap_method["login_field"],
                                                    $user, $ldap_method["condition"]);
                     if ($user_dn) {
                        $this->user->getFromLDAP($ds, $ldap_method, $user_dn, $ldap_method["rootdn"],
                                                        $ldap_method["rootdn_password"]);
                     }
                  }
               }
            }
            // Reset to secure it
            $this->user->fields['name'] = $user;
            $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];
         } else {
            $this->addToError($LANG['login'][8]);
         }
      }

      if ($noauto) {
         $_SESSION["noAUTO"] = 1;
      }

      // If not already auth
      if (!$this->auth_succeded) {
         if (empty($login_name) || empty($login_password)) {
            $this->addToError($LANG['login'][8]);
         } else {
            // exists=0 -> no exist
            // exists=1 -> exist with password
            // exists=2 -> exist without password
            $exists = $this->userExists(addslashes($login_name));

            // Pas en premier car sinon on ne fait pas le blankpassword
            // First try to connect via le DATABASE
            if ($exists == 1) {
               // Without UTF8 decoding
               if (!$this->auth_succeded) {
                  $this->auth_succeded = $this->connection_db(addslashes($login_name),
                                                              $login_password);
                  if ($this->auth_succeded) {
                     $this->extauth=0;
                     $this->user_present
                              = $this->user->getFromDBbyName(addslashes($login_name));
                     $this->user->fields["authtype"] = AUTH_DB_GLPI;
                     $this->user->fields["password"] = $login_password;
                  }
               }
            } else if ($exists == 2) {
               //The user is not authenticated on the GLPI DB, but we need to get informations about him
               //The determine authentication method
               $this->user->getFromDBbyName(addslashes($login_name));

               //If the user has already been logged, the method_auth and auths_id are already set
               //so we test this connection first
               switch ($this->user->fields["authtype"]) {
                  case AUTH_EXTERNAL :
                  case AUTH_LDAP :
                     if (canUseLdap()) {
                        $oldlevel = error_reporting(0);
                        try_ldap_auth($this, $login_name, $login_password,
                                      $this->user->fields["auths_id"]);
                        error_reporting($oldlevel);
                     }
                     break;

                  case AUTH_MAIL :
                     if (canUseImapPop()) {
                        try_mail_auth($this, $login_name, $login_password,
                                      $this->user->fields["auths_id"]);
                     }
                     break;

                  case NOT_YET_AUTHENTIFIED :
                     break;
               }
            }

            //If the last good auth method is not valid anymore, we test all methods !
            //test all ldap servers
            if (!$this->auth_succeded && canUseLdap()) {
               $oldlevel = error_reporting(0);
               try_ldap_auth($this,$login_name,$login_password);
               error_reporting($oldlevel);
            }

            //test all imap/pop servers
            if (!$this->auth_succeded && canUseImapPop()) {
               try_mail_auth($this,$login_name,$login_password);
            }
            // Fin des tests de connexion
         }
      }

      // Ok, we have gathered sufficient data, if the first return false the user
      // is not present on the DB, so we add him.
      // if not, we update him.
      if ($this->auth_succeded) {
         // Prepare data
         $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];
         if ($this->extauth) {
            $this->user->fields["_extauth"] = 1;
         }
         if ($DB->isSlave()) {
            if (!$this->user_present) { // Can't add in slave mode
               $this->addToError($LANG['login'][11]);
               $this->auth_succeded = false;
            }
         } else {
             if ($this->user_present) {
               // update user and Blank PWD to clean old database for the external auth
               $this->user->update($this->user->fields);
               if ($this->extauth) {
                  $this->user->blankPassword();
               }
            } else if ($CFG_GLPI["is_users_auto_add"]) {
               // Auto add user
               $input = $this->user->fields;
               unset ($this->user->fields);
               $this->user->add($input);
            } else {
               // Auto add not enable so auth failed
               $this->addToError($LANG['login'][11]);
               $this->auth_succeded = false;
            }
         }
      }

      // Log Event (if possible)
      if (!$DB->isSlave()) {
         // GET THE IP OF THE CLIENT
         $ip = (getenv("HTTP_X_FORWARDED_FOR") ? getenv("HTTP_X_FORWARDED_FOR") : getenv("REMOTE_ADDR"));

         if ($this->auth_succeded) {
            $logged = (GLPI_DEMO_MODE ? "logged in" : $LANG['log'][40]);
            Event::log(-1, "system", 3, "login", $login_name . " $logged: " . $ip);

         } else {
            $logged = (GLPI_DEMO_MODE ? "connection failed" : $LANG['log'][41]);
            Event::log(-1, "system", 1, "login", $logged . ": " . $login_name . " ($ip)");
         }
      }
      $this->initSession();

      return $this->auth_succeded;
   }

   /**
    * Print all the authentication methods
    * @param name the dropdown name
    * @param $options possible options / not used here
    *
    *@return Nothing (display)
    */
   static function dropdown($name,$options=array()) {
      global $LANG,$DB;

      $methods[0]='-----';
      $methods[AUTH_DB_GLPI]=$LANG['login'][32];

      $sql = "SELECT count(*) AS cpt
              FROM `glpi_authldaps`";
      $result = $DB->query($sql);

      if ($DB->result($result,0,"cpt") > 0) {
         $methods[AUTH_LDAP]=$LANG['login'][31];
         $methods[AUTH_EXTERNAL]=$LANG['setup'][67];
      }

      $sql = "SELECT count(*) AS cpt
              FROM `glpi_authmails`";
      $result = $DB->query($sql);

      if ($DB->result($result,0,"cpt") > 0) {
         $methods[AUTH_MAIL]=$LANG['login'][33];
      }

      return Dropdown::showFromArray($name,$methods);
   }
}


?>