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
class Identification {
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

      error_reporting(16);
      if ($mbox = imap_open($host, $login, $pass)) {
         imap_close($mbox);
         return true;
      }
      $this->addToError(imap_last_error());

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
         $this->destroySession();
         startGlpiSession();

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
      
            $user=$identificat->user->fields['name'];
            // Used for log when login process failed
            $login_name=$user;
      
            $this->auth_succeded = true;
            $this->extauth = 1;
            $this->user_present = $identificat->user->getFromDBbyName(addslashes($user));
            $this->user->fields['authtype'] = $authtype;
      
            // if LDAP enabled too, get user's infos from LDAP
            $this->user->fields["auths_id"] = $CFG_GLPI['authldaps_id_extra'];
            if (canUseLdap()) {
               if (isset($this->authtypes["ldap"][$identificat->user->fields["auths_id"]])) {
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
                        error_reporting(0);
                        try_ldap_auth($this, $login_name, $login_password,
                                      $this->user->fields["auths_id"]);
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
               error_reporting(0);
               try_ldap_auth($this,$login_name,$login_password);
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
            logEvent(-1, "system", 3, "login", $login_name . " $logged: " . $ip);
      
         } else {
            $logged = (GLPI_DEMO_MODE ? "connection failed" : $LANG['log'][41]);
            logEvent(-1, "system", 1, "login", $logged . ": " . $login_name . " ($ip)");
         }
      }
      $this->initSession();
      
      return $this->auth_succeded;
   }
}

/**
 *  Class used to manage Auth mail config
**/
class AuthMail extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_authmails';
   public $type = AUTH_MAIL_TYPE;

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

   /**
    * Print the auth mail form
    *
    *@param $target form target
    *@param $ID Integer : ID of the item
    *
    *@return Nothing (display)
    **/
   function showForm($target, $ID) {
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
         echo "<form action=\"$target\" method=\"post\">";
         if (!empty ($ID)) {
            echo "<input type='hidden' name='id' value='" . $ID . "'>";
         }
         echo "<div class='center'>";
         echo "<table class='tab_cadre'>";
         echo "<tr><th colspan='2'>" . $LANG['login'][3] . "</th></tr>";
         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='name' value='" . $this->fields["name"] . "'>";
         echo "</td></tr>";
         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][164] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='host' value='" . $this->fields["host"] . "'>";
         echo "</td></tr>";

         showMailServerConfig($this->fields["connect_string"]);

         if (empty ($ID)) {
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo "<input type='submit' name='add_mail' class='submit'
                   value=\"" . $LANG['buttons'][2] . "\" ></td></tr></table>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='submit' name='update_mail' class='submit'
                   value=\"" . $LANG['buttons'][7] . "\" >";
            echo "&nbsp<input type='submit' name='delete_mail' class='submit'
                        value=\"" . $LANG['buttons'][6] . "\" ></td></tr></table>";

            echo "<br><table class='tab_cadre'>";
            echo "<tr><th colspan='2'>" . $LANG['login'][21] . "</th></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][6] . "</td>";
            echo "<td><input size='30' type='text' name='imap_login' value=''></td></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][7] . "</td>";
            echo "<td><input size='30' type='password' name='imap_password' value=''></td>";
            echo "</tr><tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='submit' name='test_mail' class='submit'
                   value=\"" . $LANG['buttons'][2] . "\" ></td></tr>";
            echo "</table>&nbsp;";
         }
         echo "</div>";
      } else {
         echo "<input type='hidden' name='IMAP_Test' value='1'>";
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['setup'][162] . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>" . $LANG['setup'][165] . "</p>";
         echo "<p>" . $LANG['setup'][166] . "</p></td></tr></table></div>";
      }
      echo "</form>";
   }
}

/**
 *  Class used to manage Auth LDAP config
**/
class AuthLDAP extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_authldaps';
   public $type = AUTH_LDAP_TYPE;

   function post_getEmpty () {
      $this->fields['port']='389';
      $this->fields['condition']='';
      $this->fields['login_field']='uid';
      $this->fields['use_tls']=0;
      $this->fields['group_field']='';
      $this->fields['group_condition']='';
      $this->fields['group_search_type']=0;
      $this->fields['group_member_field']='';
      $this->fields['email_field']='mail';
      $this->fields['realname_field']='cn';
      $this->fields['firstname_field']='givenname';
      $this->fields['phone_field']='telephonenumber';
      $this->fields['phone2_field']='';
      $this->fields['mobile_field']='';
      $this->fields['comment_field']='';
      $this->fields['title_field']='';
      $this->fields['use_dn']=0;
   }

   /**
    * Preconfig datas for standard system
    * @param $type type of standard system : AD
    *@return nothing
    **/
   function preconfig($type) {
      switch($type) {
         case 'AD' :
            $this->fields['port']="389";
            $this->fields['condition']=
               '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['login_field']='samaccountname';
            $this->fields['use_tls']=0;
            $this->fields['group_field']='memberof';
            $this->fields['group_condition']=
               '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['group_search_type']=0;
            $this->fields['group_member_field']='';
            $this->fields['email_field']='mail';
            $this->fields['realname_field']='sn';
            $this->fields['firstname_field']='givenname';
            $this->fields['phone_field']='telephonenumber';
            $this->fields['phone2_field']='othertelephone';
            $this->fields['mobile_field']='mobile';
            $this->fields['comment_field']='info';
            $this->fields['title_field']='title';
            $this->fields['language_field']='preferredlanguage';
            $this->fields['use_dn']=1;
            break;

         default:
            $this->post_getEmpty();
            break;
      }
   }

   function prepareInputForUpdate($input) {
      if (isset($input["rootdn_password"]) && empty($input["rootdn_password"])) {
         unset($input["rootdn_password"]);
      }
      return $input;
   }

   /**
    * Print the auth ldap form
    *
    *@param $target form target
    *@param $ID Integer : ID of the item
    *
    *@return Nothing (display)
    **/
   function showForm($target, $ID) {
      global $LANG;

      if (!haveRight("config", "w")) {
         return false;
      }
      $spotted = false;
      if (empty ($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }
         if (isset($_GET['preconfig'])) {
            $this->preconfig($_GET['preconfig']);
         }
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (canUseLdap()) {
         echo "<form action=\"$target\" method='post'>";

         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>" . $LANG['login'][2] . "</th></tr>";
         if (empty($ID)) {
            echo "<tr class='tab_bg_2'><td class='center' >".$LANG['ldap'][16].":</td> ";
            echo "<td colspan='3'>";
            echo "<a href='$target?next=extauth_ldap&amp;preconfig=AD'>".$LANG['ldap'][17]."</a>";
            echo "&nbsp;&nbsp;/&nbsp;&nbsp;";
            echo "<a href='$target?next=extauth_ldap&amp;preconfig=default'>".$LANG['common'][44];
            echo "</a></td></tr>";
         }
         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][16] . "</td>";
         echo "<td><input type='text' name='name' value='" . $this->fields["name"] . "'></td>";
         echo "<td class='center'>" . $LANG['common'][88] . " </td>";
         echo "<td><strong>" . $this->fields["id"] . "</strong></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][52] . "</td>";
         echo "<td><input type='text' name='host' value='" . $this->fields["host"] . "'></td>";
         echo "<td class='center'>" . $LANG['setup'][172] . "</td>";
         echo "<td><input id='port' type='text' name='port' value='" . $this->fields["port"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][154] . "</td>";
         echo "<td><input type='text' name='basedn' value='" . $this->fields["basedn"] . "'>";
         echo "</td>";
         echo "<td class='center'>" . $LANG['setup'][155] . "</td>";
         echo "<td><input type='text' name='rootdn' value='" . $this->fields["rootdn"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][156] . "</td>";
         echo "<td><input type='password' name='rootdn_password' value=''></td>";
         echo "<td class='center'>" . $LANG['setup'][228] . "</td>";
         echo "<td><input type='text' name='login_field' value='".$this->fields["login_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][159] . "</td>";
         echo "<td colspan='3'><input type='text' name='condition' value='".
                                $this->fields["condition"]."' size='100'></td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='center'>" . $LANG['setup'][180] . "</td><td>";
         if (function_exists("ldap_start_tls")) {
            $use_tls = $this->fields["use_tls"];
            echo "<select name='use_tls'>";
            echo "<option value='0' " . (!$use_tls ? " selected " : "") . ">" . $LANG['choice'][0] .
                  "</option>";
            echo "<option value='1' " . ($use_tls ? " selected " : "") . ">" . $LANG['choice'][1] .
                  "</option>";
            echo "</select>";
         } else {
            echo "<input type='hidden' name='use_tls' value='0'>";
            echo $LANG['setup'][181];
         }
         echo "</td>";
         echo "<td class='center'>" . $LANG['setup'][186] . "</td><td>";
         dropdownGMT("time_offset",$this->fields["time_offset"]);
         echo"</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='center'>" . $LANG['ldap'][30] . "</td><td colspan='3'>";
         $alias_options[LDAP_DEREF_NEVER] = $LANG['ldap'][31];
         $alias_options[LDAP_DEREF_ALWAYS] = $LANG['ldap'][32];
         $alias_options[LDAP_DEREF_SEARCHING] = $LANG['ldap'][33];
         $alias_options[LDAP_DEREF_FINDING] = $LANG['ldap'][34];
         dropdownArrayValues("deref_option",$alias_options,$this->fields["deref_option"]);
         echo"</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th class='center' colspan='4'>" . $LANG['setup'][259] . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][254] . "</td><td>";
         $group_search_type = $this->fields["group_search_type"];

         echo "<select name='group_search_type'>";
         echo "<option value='0' " . (($group_search_type == 0) ? " selected " : "") . ">" .
                $LANG['setup'][256] . "</option>";
         echo "<option value='1' " . (($group_search_type == 1) ? " selected " : "") . ">" .
                $LANG['setup'][257] . "</option>";
         echo "<option value='2' " . (($group_search_type == 2) ? " selected " : "") . ">" .
                $LANG['setup'][258] . "</option>";
         echo "</select></td>";
         echo "<td class='center'>" . $LANG['setup'][260] . "</td>";
         echo "<td><input type='text' name='group_field' value='".$this->fields["group_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][253] . "</td><td>";
         echo "<input type='text' name='group_condition' value='".
                $this->fields["group_condition"]."'></td>";
         echo "<td class='center'>" . $LANG['setup'][255] . "</td>";
         echo "<td><input type='text' name='group_member_field' value='".
                    $this->fields["group_member_field"]."'></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][262] . "</td>";
         echo "<td>";
         dropdownYesNo("use_dn",$this->fields["use_dn"]);
         echo"</td>";
         echo "<td class='center' colspan='2'></td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th class='center' colspan='4'>" . $LANG['setup'][167] . "</th></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][48] . "</td>";
         echo "<td><input type='text' name='realname_field' value='".
                    $this->fields["realname_field"]."'></td>";
         echo "<td class='center'>" . $LANG['common'][43] . "</td>";
         echo "<td><input type='text' name='firstname_field' value='".
                    $this->fields["firstname_field"]."'></td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][25] . "</td>";
         echo "<td><input type='text' name='comment_field' value='".
                    $this->fields["comment_field"]."'></td>";
         echo "<td class='center'>" . $LANG['setup'][14] . "</td>";
         echo "<td><input type='text' name='email_field' value='".$this->fields["email_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['help'][35] . "</td>";
         echo "<td><input type='text' name='phone_field'value='".$this->fields["phone_field"]."'>";
         echo "</td>";
         echo "<td class='center'>" . $LANG['help'][35] . " 2</td>";
         echo "<td><input type='text' name='phone2_field'value='".$this->fields["phone2_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][42] . "</td>";
         echo "<td><input type='text' name='mobile_field'value='".$this->fields["mobile_field"]."'>";
         echo "</td>";
         echo "<td class='center'>" . $LANG['users'][1] . " </td>";
         echo "<td><input type='text' name='title_field' value='".$this->fields["title_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['users'][2] . "</td>";
         echo "<td><input type='text' name='category_field' value='".
                    $this->fields["category_field"]."'></td>";
         echo "<td class='center'>" . $LANG['setup'][41] . " </td>";
         echo "<td><input type='text' name='language_field' value='".
                    $this->fields["language_field"]. "'></td></tr>";

         if (empty ($ID)) {
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo "<input type='submit' name='add_ldap' class='submit' value='".
                   $LANG['buttons'][2]."'></td></tr></table>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='hidden' name='id' value='" . $ID . "'>";
            echo "<input type='submit' name='update_ldap' class='submit' value='".
                   $LANG['buttons'][2]."'></td>";
            echo "<td class='center' colspan=2>";
            echo "<input type='submit' name='delete_ldap' class='submit' value='".
                   $LANG['buttons'][6]."'></td></tr>";
            echo "</table>";
            echo "<br><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>" . $LANG['ldap'][9] . "</th></tr>";

            if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
               echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
               echo $_SESSION["LDAP_TEST_MESSAGE"];
               echo"</td></tr>";
               unset($_SESSION["LDAP_TEST_MESSAGE"]);
            }
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo "<input type='submit' name='test_ldap' class='submit' value='".
                   $LANG['buttons'][2]."'></td></tr>";
            echo "</table>&nbsp;";
         }
         echo "</div></form>";

         if (!empty ($ID)) {
            showReplicatesList($target,$ID);
         }
      } else {
         echo "<input type='hidden' name='LDAP_Test' value='1'>";
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['setup'][152] . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>" . $LANG['setup'][157] . "</p>";
         echo "<p>" . $LANG['setup'][158] . "</p></td></tr></table></div>";
      }
   }
}

/**
 *  Class used to manage LDAP replicate config
**/
class AuthLdapReplicate extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_authldapsreplicates';

   function prepareInputForAdd($input) {
      if (isset($input["port"]) && intval($input["port"])==0) {
         $input["port"]=389;
      }
      return $input;
   }

   function prepareInputForUpdate($input) {
      if (isset($input["port"]) && intval($input["port"])==0) {
         $input["port"]=389;
      }
      return $input;
   }
}
?>