<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\ErrorHandler;
use Glpi\Event;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;

/**
 *  Identification class used to login
 */
class Auth extends CommonGLPI
{
    /** @var array Array of errors */
    private $errors = [];
    /** @var User User class variable */
    public $user;
    /** @var int External authentication variable */
    public $extauth = 0;
    /** @var array External authentication methods */
    public $authtypes;
    /** @var int Indicates if the user is authenticated or not */
    public $auth_succeded = 0;
    /** @var int Indicates if the user is already present in database */
    public $user_present = 0;
    /** @var int Indicates if the user password expired */
    public $password_expired = false;

    /**
     * Indicated if user was found in the directory.
     * @var boolean
     */
    public $user_found = false;

    /**
     * Indicated if an error occurs during connection to the user LDAP.
     * @var boolean
     */
    public $user_ldap_error = false;

    /** @var resource|boolean LDAP connection descriptor */
    public $ldap_connection;
    /** @var bool Store user LDAP dn */
    public $user_dn = false;

    const DB_GLPI  = 1;
    const MAIL     = 2;
    const LDAP     = 3;
    const EXTERNAL = 4;
    const CAS      = 5;
    const X509     = 6;
    const API      = 7;
    const COOKIE   = 8;
    const NOT_YET_AUTHENTIFIED = 0;

    const USER_DOESNT_EXIST       = 0;
    const USER_EXISTS_WITH_PWD    = 1;
    const USER_EXISTS_WITHOUT_PWD = 2;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = new User();
    }


    public static function getMenuContent()
    {

        $menu = [];
        if (Config::canUpdate()) {
            $menu['title']                              = __('Authentication');
            $menu['page']                               = '/front/setup.auth.php';
            $menu['icon']                               = self::getIcon();

            $menu['options']['ldap']['icon']            = AuthLDAP::getIcon();
            $menu['options']['ldap']['title']           = AuthLDAP::getTypeName(Session::getPluralNumber());
            $menu['options']['ldap']['page']            = AuthLDAP::getSearchURL(false);
            $menu['options']['ldap']['links']['search'] = AuthLDAP::getSearchURL(false);
            $menu['options']['ldap']['links']['add']    = AuthLDAP::getFormURL(false);

            $menu['options']['imap']['icon']            = AuthMail::getIcon();
            $menu['options']['imap']['title']           = AuthMail::getTypeName(Session::getPluralNumber());
            $menu['options']['imap']['page']            = AuthMail::getSearchURL(false);
            $menu['options']['imap']['links']['search'] = AuthMail::getSearchURL(false);
            $menu['options']['imap']['links']['add']    = AuthMail::getFormURL(false);

            $menu['options']['others']['icon']          = 'ti ti-login';
            $menu['options']['others']['title']         = __('Others');
            $menu['options']['others']['page']          = '/front/auth.others.php';

            $menu['options']['settings']['icon']        = 'ti ti-adjustments';
            $menu['options']['settings']['title']       = __('Setup');
            $menu['options']['settings']['page']        = '/front/auth.settings.php';
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }

    /**
     * Check user existence in DB
     *
     * @global DBmysql $DB
     * @param  array   $options conditions : array('name'=>'glpi')
     *                                    or array('email' => 'test at test.com')
     *
     * @return integer {@link Auth::USER_DOESNT_EXIST}, {@link Auth::USER_EXISTS_WITHOUT_PWD} or {@link Auth::USER_EXISTS_WITH_PWD}
     */
    public function userExists($options = [])
    {
        global $DB;

        $result = $DB->request(
            'glpi_users',
            ['WHERE'    => $options,
                'LEFT JOIN' => ['glpi_useremails' => ['FKEY' => ['glpi_users'      => 'id',
                    'glpi_useremails' => 'users_id'
                ]
                ]
                ]
            ]
        );
       // Check if there is a row
        if ($result->numrows() == 0) {
            $this->addToError(__('Incorrect username or password'));
            return self::USER_DOESNT_EXIST;
        } else {
           // Get the first result...
            $row = $result->current();

           // Check if we have a password...
            if (empty($row['password'])) {
                //If the user has an LDAP DN, then store it in the Auth object
                if ($row['user_dn']) {
                    $this->user_dn = $row['user_dn'];
                }
                return self::USER_EXISTS_WITHOUT_PWD;
            }
            return self::USER_EXISTS_WITH_PWD;
        }
    }

    /**
     * Try a IMAP/POP connection
     *
     * @param string $host  IMAP/POP host to connect
     * @param string $login Login to try
     * @param string $pass  Password to try
     *
     * @return boolean connection success
     */
    public function connection_imap($host, $login, $pass)
    {

       // we prevent some delay...
        if (empty($host)) {
            return false;
        }

        $oldlevel = error_reporting(16);
       // No retry (avoid lock account when password is not correct)
        try {
            $config = Toolbox::parseMailServerConnectString($host);

            $ssl = false;
            if ($config['ssl']) {
                $ssl = 'SSL';
            }
            if ($config['tls']) {
                $ssl = 'TLS';
            }

            $protocol = Toolbox::getMailServerProtocolInstance($config['type']);
            if ($protocol === null) {
                throw new \RuntimeException(sprintf(__('Unsupported mail server type:%s.'), $config['type']));
            }
            if ($config['validate-cert'] === false) {
                $protocol->setNoValidateCert(true);
            }
            $protocol->connect(
                $config['address'],
                $config['port'],
                $ssl
            );

            return $protocol->login($login, $pass);
        } catch (\Throwable $e) {
            $this->addToError($e->getMessage());
            return false;
        } finally {
            error_reporting($oldlevel);
        }

        return false;
    }

    /**
     * Find a user in a LDAP and return is BaseDN
     * Based on GRR auth system
     *
     * @param string    $ldap_method ldap_method array to use
     * @param string    $login       User Login
     * @param string    $password    User Password
     * @param bool|null $error       Boolean flag that will be set to `true` if a LDAP error occurs during connection
     *
     * @return string basedn of the user / false if not founded
     */
    public function connection_ldap($ldap_method, $login, $password, ?bool &$error = null)
    {
        $error = false;

       // we prevent some delay...
        if (empty($ldap_method['host'])) {
            $error = true;
            return false;
        }

        $this->ldap_connection   = AuthLDAP::tryToConnectToServer($ldap_method, $login, $password);
        $this->user_found = false;

        if ($this->ldap_connection) {
            $params = [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'fields' => [
                    AuthLDAP::IDENTIFIER_LOGIN => $ldap_method['login_field'],
                ],
            ];
            if (!empty($ldap_method['sync_field'])) {
                $params['fields']['sync_field'] = $ldap_method['sync_field'];
            }
            try {
                $info = AuthLDAP::searchUserDn($this->ldap_connection, [
                    'basedn'            => $ldap_method['basedn'],
                    'login_field'       => $ldap_method['login_field'],
                    'search_parameters' => $params,
                    'user_params'       => [
                        'method' => AuthLDAP::IDENTIFIER_LOGIN,
                        'value'  => $login
                    ],
                    'condition'         => Sanitizer::unsanitize($ldap_method['condition']),
                    'user_dn'           => $this->user_dn
                ]);
            } catch (\Throwable $e) {
                ErrorHandler::getInstance()->handleException($e, true);
                $info = false;
            }

            $ldap_errno = ldap_errno($this->ldap_connection);
            if ($info === false) {
                if ($ldap_errno > 0 && $ldap_errno !== 32) {
                    $this->addToError(__('Unable to connect to the LDAP directory'));
                    $error = true;
                } else {
                    // 32 = LDAP_NO_SUCH_OBJECT => This should not be considered as a connection error, as it just means that user was not found.
                    $this->addToError(__('Incorrect username or password'));
                }
                return false;
            }

            $dn = $info['dn'];
            $this->user_found = $dn != '';

            $bind_result = $this->user_found ? @ldap_bind($this->ldap_connection, $dn, $password) : false;

            if ($this->user_found && $bind_result !== false) {
                //Hook to implement to restrict access by checking the ldap directory
                if (Plugin::doHookFunction(Hooks::RESTRICT_LDAP_AUTH, $info)) {
                    return $info;
                }
                $this->addToError(__('User not authorized to connect in GLPI'));
                //Use is present by has no right to connect because of a plugin
                return false;
            } else {
                // Incorrect login
                $this->addToError(__('Incorrect username or password'));
                //Use is not present anymore in the directory!
                return false;
            }
        } else {
            //Directory is not available
            $this->addToError(__('Unable to connect to the LDAP directory'));
            $error = true;
            return false;
        }
    }

    /**
     * Check is a password match the stored hash
     *
     * @since 0.85
     *
     * @param string $pass Password (pain-text)
     * @param string $hash Hash
     *
     * @return boolean
     */
    public static function checkPassword($pass, $hash)
    {

        $tmp = password_get_info($hash);

        if (isset($tmp['algo']) && $tmp['algo']) {
            $ok = password_verify($pass, $hash);
        } else if (strlen($hash) == 32) {
            $ok = md5($pass) === $hash;
        } else if (strlen($hash) == 40) {
            $ok = sha1($pass) === $hash;
        } else {
            $salt = substr($hash, 0, 8);
            $ok = ($salt . sha1($salt . $pass) === $hash);
        }

        return $ok;
    }

    /**
     * Is the hash stored need to be regenerated
     *
     * @since 0.85
     *
     * @param string $hash Hash
     *
     * @return boolean
     */
    public static function needRehash($hash)
    {

        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Compute the hash for a password
     *
     * @since 0.85
     *
     * @param string $pass Password
     *
     * @return string
     */
    public static function getPasswordHash($pass)
    {

        return password_hash($pass, PASSWORD_DEFAULT);
    }

    /**
     * Find a user in the GLPI DB
     *
     * try to connect to DB
     * update the instance variable user with the user who has the name $name
     * and the password is $password in the DB.
     * If not found or can't connect to DB updates the instance variable err
     * with an eventual error message
     *
     * @global DBmysql $DB
     * @param string $name     User Login
     * @param string $password User Password
     *
     * @return boolean user in GLPI DB with the right password
     */
    public function connection_db($name, $password)
    {
        global $CFG_GLPI, $DB;

        $pass_expiration_delay = (int)$CFG_GLPI['password_expiration_delay'];
        $lock_delay            = (int)$CFG_GLPI['password_expiration_lock_delay'];

       // SQL query
        $result = $DB->request(
            [
                'SELECT' => [
                    'id',
                    'password',
                    new QueryExpression(
                        sprintf(
                            'ADDDATE(%s, INTERVAL %d DAY) AS ' . $DB->quoteName('password_expiration_date'),
                            $DB->quoteName('password_last_update'),
                            $pass_expiration_delay
                        )
                    ),
                    new QueryExpression(
                        sprintf(
                            'ADDDATE(%s, INTERVAL %d DAY) AS ' . $DB->quoteName('lock_date'),
                            $DB->quoteName('password_last_update'),
                            $pass_expiration_delay + $lock_delay
                        )
                    )
                ],
                'FROM'   => User::getTable(),
                'WHERE'  =>  [
                    'name'     => $name,
                    'authtype' => self::DB_GLPI,
                    'auths_id' => 0,
                ]
            ]
        );

       // Have we a result ?
        if ($result->numrows() == 1) {
            $row = $result->current();
            $password_db = $row['password'];

            if (self::checkPassword($password, $password_db)) {
               // Disable account if password expired
                if (
                    -1 !== $pass_expiration_delay && -1 !== $lock_delay
                    && $row['lock_date'] < $_SESSION['glpi_currenttime']
                ) {
                    $user = new User();
                    $user->update(
                        [
                            'id'        => $row['id'],
                            'is_active' => 0,
                        ]
                    );
                }
                if (
                    -1 !== $pass_expiration_delay
                    && $row['password_expiration_date'] < $_SESSION['glpi_currenttime']
                ) {
                    $this->password_expired = 1;
                }

               // Update password if needed
                if (self::needRehash($password_db)) {
                    $input = [
                        'id' => $row['id'],
                    ];
                 // Set glpiID to allow password update
                    $_SESSION['glpiID'] = $input['id'];
                    $input['password'] = $password;
                    $input['password2'] = $password;
                    $user = new User();
                    $user->update($input);
                }
                $this->user->getFromDBByCrit(['id' => $row['id']]);
                $this->extauth                  = 0;
                $this->user_present             = 1;
                $this->user->fields["authtype"] = self::DB_GLPI;
                $this->user->fields["password"] = $password;

             // apply rule rights on local user
                $rules  = new RuleRightCollection();
                $groups = Group_User::getUserGroups($row['id']);
                $groups_id = array_column($groups, 'id');
                $result = $rules->processAllRules(
                    $groups_id,
                    $this->user->fields,
                    [
                        'type'  => Auth::DB_GLPI,
                        'login' => $this->user->fields['name'],
                        'email' => UserEmail::getDefaultForUser($row['id'])
                    ]
                );

                $this->user->fields = $result + [
                    '_ruleright_process' => true,
                ];

                return true;
            }
        }
        $this->addToError(__('Incorrect username or password'));
        return false;
    }

    /**
     * Try to get login of external auth method
     *
     * @param integer $authtype external auth type (default 0)
     *
     * @return boolean user login success
     */
    public function getAlternateAuthSystemsUserLogin($authtype = 0)
    {
        global $CFG_GLPI;

        switch ($authtype) {
            case self::CAS:
                if (!Toolbox::canUseCAS()) {
                    trigger_error("CAS lib not installed", E_USER_WARNING);
                    return false;
                }

                // Adapt phpCAS::client() signature.
                // A new signature has been introduced in 1.6.0 version of the official package.
                // This new signature has been backported in the `1.3.6-1` version of the debian package,
                // so we have to check for method argument names too.
                $has_service_base_url_arg = version_compare(phpCAS::getVersion(), '1.6.0', '>=')
                    || ((new ReflectionMethod(phpCAS::class, 'client'))->getParameters()[4]->getName() ?? null) === 'service_base_url';
                if (!$has_service_base_url_arg) {
                    // Prior to version 1.6.0, `$service_base_url` argument was not present, and 5th argument was `$changeSessionID`.
                    phpCAS::client(
                        constant($CFG_GLPI["cas_version"]),
                        $CFG_GLPI["cas_host"],
                        intval($CFG_GLPI["cas_port"]),
                        $CFG_GLPI["cas_uri"],
                        false
                    );
                } else {
                    // Starting from version 1.6.0, `$service_base_url` argument was added at 5th position, and `$changeSessionID`
                    // was moved at 6th position.
                    phpCAS::client(
                        constant($CFG_GLPI["cas_version"]),
                        $CFG_GLPI["cas_host"],
                        intval($CFG_GLPI["cas_port"]),
                        $CFG_GLPI["cas_uri"],
                        $CFG_GLPI["url_base"],
                        false
                    );
                }

                // no SSL validation for the CAS server
                phpCAS::setNoCasServerValidation();

                // force CAS authentication
                phpCAS::forceAuthentication();
                $this->user->fields['name'] = phpCAS::getUser();

                // extract e-mail information
                if (phpCAS::hasAttribute("mail")) {
                      $this->user->fields['_useremails'] = [phpCAS::getAttribute("mail")];
                }

                return true;

            case self::EXTERNAL:
                $ssovariable = Dropdown::getDropdownName(
                    'glpi_ssovariables',
                    $CFG_GLPI["ssovariables_id"]
                );
                $login_string = '';
               // MoYo : checking REQUEST create a security hole for me !
                if (isset($_SERVER[$ssovariable])) {
                      $login_string = $_SERVER[$ssovariable];
                }
               // else {
               //    $login_string = $_REQUEST[$ssovariable];
               // }
                $login        = $login_string;
                $pos          = stripos($login_string, "\\");
                if (!$pos === false) {
                    $login = substr($login_string, $pos + 1);
                }
                if ($CFG_GLPI['existing_auth_server_field_clean_domain']) {
                    $pos = stripos($login, "@");
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

            case self::X509:
               // From eGroupWare  http://www.egroupware.org
               // an X.509 subject looks like:
               // CN=john.doe/OU=Department/O=Company/C=xx/Email=john@comapy.tld/L=City/
                $sslattribs = explode('/', $_SERVER['SSL_CLIENT_S_DN']);
                $sslattributes = [];
                while ($sslattrib = next($sslattribs)) {
                    list($key,$val)      = explode('=', $sslattrib);
                    $sslattributes[$key] = $val;
                }
                if (
                    isset($sslattributes[$CFG_GLPI["x509_email_field"]])
                    && NotificationMailing::isUserAddressValid($sslattributes[$CFG_GLPI["x509_email_field"]])
                    && self::isValidLogin($sslattributes[$CFG_GLPI["x509_email_field"]])
                ) {
                    $restrict = false;
                    $CFG_GLPI["x509_ou_restrict"] = trim($CFG_GLPI["x509_ou_restrict"]);
                    if (!empty($CFG_GLPI["x509_ou_restrict"])) {
                        $split = explode('$', $CFG_GLPI["x509_ou_restrict"]);

                        if (!in_array($sslattributes['OU'], $split)) {
                            $restrict = true;
                        }
                    }
                    $CFG_GLPI["x509_o_restrict"] = trim($CFG_GLPI["x509_o_restrict"]);
                    if (!empty($CFG_GLPI["x509_o_restrict"])) {
                        $split = explode('$', $CFG_GLPI["x509_o_restrict"]);

                        if (!in_array($sslattributes['O'], $split)) {
                            $restrict = true;
                        }
                    }
                    $CFG_GLPI["x509_cn_restrict"] = trim($CFG_GLPI["x509_cn_restrict"]);
                    if (!empty($CFG_GLPI["x509_cn_restrict"])) {
                        $split = explode('$', $CFG_GLPI["x509_cn_restrict"]);

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
                    if ($user->getFromDBbyToken($_REQUEST['user_token'], 'api_token')) {
                        $this->user->fields['name'] = $user->fields['name'];
                        return true;
                    }
                } else {
                    $this->addToError(__("Login with external token disabled"));
                }
                break;
            case self::COOKIE:
                $cookie_name   = session_name() . '_rememberme';

                if ($CFG_GLPI["login_remember_time"]) {
                    $data = json_decode($_COOKIE[$cookie_name], true);
                    if (count($data) === 2) {
                        list ($cookie_id, $cookie_token) = $data;

                        $user = new User();
                        $user->getFromDB($cookie_id);
                        $hash = $user->getAuthToken('cookie_token');

                        if (Auth::checkPassword($cookie_token, $hash)) {
                            $this->user->fields['name'] = $user->fields['name'];
                            return true;
                        } else {
                            $this->addToError(__("Invalid cookie data"));
                        }
                    }
                } else {
                    $this->addToError(__("Auto login disabled"));
                }

               //Remove cookie to allow new login
                Auth::setRememberMeCookie('');
                break;
        }
        return false;
    }

    /**
     * Get the current identification error
     *
     * @return string current identification error
     *
     * @TODO Deprecate this in GLPI 10.1.
     */
    public function getErr()
    {
        return implode("<br>\n", $this->getErrors());
    }

    /**
     * Get errors
     *
     * @since 9.4
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the current user object
     *
     * @return object current user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get all the authentication methods parameters
     * and return it as an array
     *
     * @return void
     */
    public function getAuthMethods()
    {

       //Return all the authentication methods in an array
        $this->authtypes = [
            'ldap' => getAllDataFromTable('glpi_authldaps'),
            'mail' => getAllDataFromTable('glpi_authmails')
        ];
    }

    /**
     * Add a message to the global identification error message
     *
     * @param string $message the message to add
     *
     * @return void
     */
    public function addToError($message)
    {
        if (!in_array($message, $this->errors)) {
            $this->errors[] = $message;
        }
    }

    /**
     * Manage use authentication and initialize the session
     *
     * @param string  $login_name      Login
     * @param string  $login_password  Password
     * @param boolean $noauto          (false by default)
     * @param bool    $remember_me
     * @param string  $login_auth      Type of auth - id of the auth
     *
     * @return boolean (success)
     */
    public function login($login_name, $login_password, $noauto = false, $remember_me = false, $login_auth = '')
    {
        global $DB, $CFG_GLPI;

        $this->getAuthMethods();
        $this->user_present  = 1;
        $this->auth_succeded = false;
       //In case the user was deleted in the LDAP directory
        $user_deleted_ldap   = false;

       // Trim login_name : avoid LDAP search errors
        $login_name = trim($login_name);

       // manage the $login_auth (force the auth source of the user account)
        $this->user->fields["auths_id"] = 0;
        $authtype = null;
        if ($login_auth == 'local') {
            $authtype = self::DB_GLPI;
            $this->user->fields["authtype"] = self::DB_GLPI;
        } else if (preg_match('/^(?<type>ldap|mail|external)-(?<id>\d+)$/', $login_auth, $auth_matches)) {
            $this->user->fields["auths_id"] = (int)$auth_matches['id'];
            if ($auth_matches['type'] == 'ldap') {
                $authtype = self::LDAP;
            } else if ($auth_matches['type'] == 'mail') {
                $authtype = self::MAIL;
            } else if ($auth_matches['type'] == 'external') {
                $authtype = self::EXTERNAL;
            }
            if ($authtype !== null) {
                $this->user->fields['authtype'] = $authtype;
            }
        }
        if (!$noauto && ($authtype = self::checkAlternateAuthSystems())) {
            if (
                $this->getAlternateAuthSystemsUserLogin($authtype)
                && !empty($this->user->fields['name'])
            ) {
               // Used for log when login process failed
                $login_name                        = $this->user->fields['name'];
                $this->auth_succeded               = true;
                $this->user_present                = $this->user->getFromDBbyName(addslashes($login_name));
                $this->extauth                     = 1;
                $user_dn                           = false;

                if (array_key_exists('_useremails', $this->user->fields)) {
                    $email = $this->user->fields['_useremails'];
                }

                $ldapservers = [];
                $ldapservers_status = false;
               //if LDAP enabled too, get user's infos from LDAP
                if (Toolbox::canUseLdap()) {
                   //User has already authenticated, at least once: it's ldap server if filled
                    if (
                        isset($this->user->fields["auths_id"])
                        && ($this->user->fields["auths_id"] > 0)
                    ) {
                        $authldap = new AuthLDAP();
                       //If ldap server is enabled
                        if (
                            $authldap->getFromDB($this->user->fields["auths_id"])
                            && $authldap->fields['is_active']
                        ) {
                            $ldapservers[] = $authldap->fields;
                        }
                    } else { // User has never been authenticated: try all active ldap server to find the right one
                        foreach (getAllDataFromTable('glpi_authldaps', ['is_active' => 1]) as $ldap_config) {
                            $ldapservers[] = $ldap_config;
                        }
                    }

                    foreach ($ldapservers as $ldap_method) {
                        $ds = AuthLDAP::connectToServer(
                            $ldap_method["host"],
                            $ldap_method["port"],
                            $ldap_method["rootdn"],
                            (new GLPIKey())->decrypt($ldap_method["rootdn_passwd"]),
                            $ldap_method["use_tls"],
                            $ldap_method["deref_option"],
                            $ldap_method["tls_certfile"],
                            $ldap_method["tls_keyfile"],
                            $ldap_method["use_bind"],
                            $ldap_method["timeout"]
                        );

                        if ($ds) {
                             $ldapservers_status = true;
                             $params = [
                                 'method' => AuthLDAP::IDENTIFIER_LOGIN,
                                 'fields' => [
                                     AuthLDAP::IDENTIFIER_LOGIN => $ldap_method["login_field"],
                                 ],
                             ];
                             try {
                                 $user_dn = AuthLDAP::searchUserDn($ds, [
                                     'basedn'            => $ldap_method["basedn"],
                                     'login_field'       => $ldap_method['login_field'],
                                     'search_parameters' => $params,
                                     'condition'         => Sanitizer::unsanitize($ldap_method["condition"]),
                                     'user_params'       => [
                                         'method' => AuthLDAP::IDENTIFIER_LOGIN,
                                         'value'  => $login_name
                                     ],
                                 ]);
                             } catch (\RuntimeException $e) {
                                 ErrorHandler::getInstance()->handleException($e, true);
                                 $user_dn = false;
                             }
                             if ($user_dn) {
                                 $this->user_found = true;
                                 $this->user->fields['auths_id'] = $ldap_method['id'];
                                 $this->user->getFromLDAP(
                                     $ds,
                                     $ldap_method,
                                     $user_dn['dn'],
                                     $login_name,
                                     !$this->user_present
                                 );
                                 break;
                             }
                        }
                    }
                }
                if (
                    (count($ldapservers) == 0)
                    && ($authtype == self::EXTERNAL)
                ) {
                   // Case of using external auth and no LDAP servers, so get data from external auth
                    $this->user->getFromSSO();
                } else {
                    if ($this->user->fields['authtype'] == self::LDAP) {
                        if (!$ldapservers_status) {
                             $this->auth_succeded = false;
                             $this->addToError(_n(
                                 'Connection to LDAP directory failed',
                                 'Connection to LDAP directories failed',
                                 count($ldapservers)
                             ));
                        } else if (!$user_dn && $this->user_present) {
                           //If user is set as present in GLPI but no LDAP DN found : it means that the user
                           //is not present in an ldap directory anymore
                            $user_deleted_ldap = true;
                            $this->addToError(_n(
                                'User not found in LDAP directory',
                                'User not found in LDAP directories',
                                count($ldapservers)
                            ));
                        }
                    }
                }
               // Reset to secure it
                $this->user->fields['name']       = $login_name;
                $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];
            } else {
                $this->addToError(__('Empty login or password'));
            }
        }

        if (!$this->auth_succeded) {
            if (
                empty($login_name) || strstr($login_name, "\0")
                || empty($login_password) || strstr($login_password, "\0")
            ) {
                $this->addToError(__('Empty login or password'));
            } else {
               // Try connect local user if not yet authenticated
                if (
                    empty($login_auth)
                    || $this->user->fields["authtype"] == $this::DB_GLPI
                ) {
                    $this->auth_succeded = $this->connection_db(
                        addslashes($login_name),
                        $login_password
                    );
                }

               // Try to connect LDAP user if not yet authenticated
                if (!$this->auth_succeded) {
                    if (
                        empty($login_auth)
                        || $this->user->fields["authtype"] == $this::CAS
                        || $this->user->fields["authtype"] == $this::EXTERNAL
                        || $this->user->fields["authtype"] == $this::LDAP
                    ) {
                        if (Toolbox::canUseLdap()) {
                            AuthLDAP::tryLdapAuth(
                                $this,
                                $login_name,
                                $login_password,
                                $this->user->fields["auths_id"]
                            );
                            if ($this->user_ldap_error === false && !$this->auth_succeded && !$this->user_found) {
                                 // Mark user as deleted, unless an error occured during connection to user LDAP server.
                                 $search_params = [
                                     'name'     => addslashes($login_name),
                                     'authtype' => $this::LDAP
                                 ];
                                 if (!empty($login_auth)) {
                                     $search_params['auths_id'] = $this->user->fields["auths_id"];
                                 }
                                 if ($this->user->getFromDBByCrit($search_params)) {
                                     $user_deleted_ldap = true;
                                 };
                            }
                        }
                    }
                }

               // Try connect MAIL server if not yet authenticated
                if (!$this->auth_succeded) {
                    if (
                        empty($login_auth)
                        || $this->user->fields["authtype"] == $this::MAIL
                    ) {
                        AuthMail::tryMailAuth(
                            $this,
                            $login_name,
                            $login_password,
                            $this->user->fields["auths_id"]
                        );
                    }
                }
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
                   // Add the user e-mail if present
                    if (isset($email)) {
                         $this->user->fields['_useremails'] = $email;
                    }
                    $this->user->update(Sanitizer::sanitize($this->user->fields));
                } else if ($CFG_GLPI["is_users_auto_add"]) {
                   // Auto add user
                    $input = $this->user->fields;
                    unset($this->user->fields);
                    if ($authtype == self::EXTERNAL && !isset($input["authtype"])) {
                        $input["authtype"] = $authtype;
                    }
                    $this->user->add(Sanitizer::sanitize($input));
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
            $ip = getenv("HTTP_X_FORWARDED_FOR") ?
            Sanitizer::encodeHtmlSpecialChars(getenv("HTTP_X_FORWARDED_FOR")) :
            getenv("REMOTE_ADDR");

            if ($this->auth_succeded) {
                if (GLPI_DEMO_MODE) {
                    // not translation in GLPI_DEMO_MODE
                    Event::log(0, "system", 3, "login", $login_name . " log in from " . $ip);
                } else {
                   //TRANS: %1$s is the login of the user and %2$s its IP address
                    Event::log(0, "system", 3, "login", sprintf(
                        __('%1$s log in from IP %2$s'),
                        $login_name,
                        $ip
                    ));
                }
            } else {
                if (GLPI_DEMO_MODE) {
                    Event::log(
                        0,
                        "system",
                        3,
                        "login",
                        "Connection failed for " . $login_name . " ($ip)"
                    );
                } else {
                   //TRANS: %1$s is the login of the user and %2$s its IP address
                    Event::log(0, "system", 3, "login", sprintf(
                        __('Failed login for %1$s from IP %2$s'),
                        $login_name,
                        $ip
                    ));
                }
            }
        }

        Session::init($this);

        if ($noauto) {
            $_SESSION["noAUTO"] = 1;
        }

        if ($this->auth_succeded && $CFG_GLPI['login_remember_time'] > 0 && $remember_me) {
            $token = $this->user->getAuthToken('cookie_token', true);

            if ($token) {
                $data = json_encode([
                    $this->user->fields['id'],
                    $token,
                ]);

               //Send cookie to browser
                Auth::setRememberMeCookie($data);
            }
        }

        if ($this->auth_succeded && !empty($this->user->fields['timezone']) && 'null' !== strtolower($this->user->fields['timezone'])) {
           //set user timezone, if any
            $_SESSION['glpi_tz'] = $this->user->fields['timezone'];
            $DB->setTimezone($this->user->fields['timezone']);
        }

        return $this->auth_succeded;
    }

    /**
     * Print all the authentication methods
     *
     * @param array $options Possible options:
     * - name : Name of the select (default is auths_id)
     * - value : Selected value (default 0)
     * - display : If true, the dropdown is displayed instead of returned (default true)
     * - display_emptychoice : If true, an empty option is added (default true)
     * - hide_if_no_elements  : boolean / hide dropdown if there is no elements (default false)
     *
     * @return void|string (Based on 'display' option)
     */
    public static function dropdown($options = [])
    {
        global $DB;

        $p = [
            'name'                => 'auths_id',
            'value'               => 0,
            'display'             => true,
            'display_emptychoice' => true,
            'hide_if_no_elements' => false,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $methods = [
            self::DB_GLPI  => __('Authentication on GLPI database'),
            self::EXTERNAL => __('External authentications'),
        ];

        $result = $DB->request([
            'FROM'   => 'glpi_authldaps',
            'COUNT'  => 'cpt',
            'WHERE'  => [
                'is_active' => 1
            ]
        ])->current();

        if ($result['cpt'] > 0) {
            $methods[self::LDAP] = __('Authentication on a LDAP directory');
        }

        $result = $DB->request([
            'FROM'   => 'glpi_authmails',
            'COUNT'  => 'cpt',
            'WHERE'  => [
                'is_active' => 1
            ]
        ])->current();

        if ($result['cpt'] > 0) {
            $methods[self::MAIL] = __('Authentication on mail server');
        }

        return Dropdown::showFromArray($p['name'], $methods, $p);
    }

    /**
     * Builds CAS versions dropdown
     * @param string $value (default 'CAS_VERSION_2_0')
     *
     * @return string
     */
    public static function dropdownCasVersion($value = 'CAS_VERSION_2_0')
    {
        $options['CAS_VERSION_1_0'] = __('Version 1');
        $options['CAS_VERSION_2_0'] = __('Version 2');
        $options['CAS_VERSION_3_0'] = __('Version 3+');
        return Dropdown::showFromArray('cas_version', $options, ['value' => $value]);
    }

    /**
     * Get name of an authentication method
     *
     * @param integer $authtype Authentication method
     * @param integer $auths_id Authentication method ID
     * @param integer $link     show links to config page? (default 0)
     * @param string  $name     override the name if not empty (default '')
     *
     * @return string
     */
    public static function getMethodName($authtype, $auths_id, $link = 0, $name = '')
    {

        switch ($authtype) {
            case self::LDAP:
                $auth = new AuthLDAP();
                if ($auth->getFromDB($auths_id)) {
                   //TRANS: %1$s is the auth method type, %2$s the auth method name or link
                    return sprintf(__('%1$s: %2$s'), AuthLDAP::getTypeName(1), $auth->getLink());
                }
                return sprintf(__('%1$s: %2$s'), AuthLDAP::getTypeName(1), $name);

            case self::MAIL:
                $auth = new AuthMail();
                if ($auth->getFromDB($auths_id)) {
                    //TRANS: %1$s is the auth method type, %2$s the auth method name or link
                    return sprintf(__('%1$s: %2$s'), AuthMail::getTypeName(1), $auth->getLink());
                }
                return sprintf(__('%1$s: %2$s'), __('Email server'), $name);

            case self::CAS:
                if ($auths_id > 0) {
                    $auth = new AuthLDAP();
                    if ($auth->getFromDB($auths_id)) {
                        return sprintf(
                            __('%1$s: %2$s'),
                            sprintf(
                                __('%1$s + %2$s'),
                                __('CAS'),
                                AuthLDAP::getTypeName(1)
                            ),
                            $auth->getLink()
                        );
                    }
                }
                return __('CAS');

            case self::X509:
                if ($auths_id > 0) {
                    $auth = new AuthLDAP();
                    if ($auth->getFromDB($auths_id)) {
                        return sprintf(
                            __('%1$s: %2$s'),
                            sprintf(
                                __('%1$s + %2$s'),
                                __('x509 certificate authentication'),
                                AuthLDAP::getTypeName(1)
                            ),
                            $auth->getLink()
                        );
                    }
                }
                return __('x509 certificate authentication');

            case self::EXTERNAL:
                if ($auths_id > 0) {
                    $auth = new AuthLDAP();
                    if ($auth->getFromDB($auths_id)) {
                        return sprintf(
                            __('%1$s: %2$s'),
                            sprintf(
                                __('%1$s + %2$s'),
                                __('Other'),
                                AuthLDAP::getTypeName(1)
                            ),
                            $auth->getLink()
                        );
                    }
                }
                return __('Other');

            case self::DB_GLPI:
                return __('GLPI internal database');

            case self::API:
                return __("API");

            case self::NOT_YET_AUTHENTIFIED:
                return __('Not yet authenticated');
        }
        return '';
    }

    /**
     * Get all the authentication methods parameters for a specific authtype
     *  and auths_id and return it as an array
     *
     * @param integer $authtype Authentication method
     * @param integer $auths_id Authentication method ID
     *
     * @return mixed
     */
    public static function getMethodsByID($authtype, $auths_id)
    {

        switch ($authtype) {
            case self::X509:
            case self::EXTERNAL:
            case self::CAS:
            case self::LDAP:
                $auth = new AuthLDAP();
                if ($auths_id > 0 && $auth->getFromDB($auths_id)) {
                    return ($auth->fields);
                }
                break;

            case self::MAIL:
                $auth = new AuthMail();
                if ($auths_id > 0 && $auth->getFromDB($auths_id)) {
                    return ($auth->fields);
                }
                break;
        }
        return [];
    }

    /**
     * Is an external authentication used?
     *
     * @return boolean
     */
    public static function useAuthExt()
    {

        global $CFG_GLPI;

       //Get all the ldap directories
        if (AuthLDAP::useAuthLdap()) {
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
     * Is an alternate auth?
     *
     * @param integer $authtype auth type
     *
     * @return boolean
     */
    public static function isAlternateAuth($authtype)
    {
        return in_array($authtype, [self::X509, self::CAS, self::EXTERNAL, self::API, self::COOKIE]);
    }

    /**
     * Check alternate authentication systems
     *
     * @param boolean $redirect        need to redirect (true) or get type of Auth system which match
     *                                (false by default)
     * @param string  $redirect_string redirect string if exists (default '')
     *
     * @return void|integer nothing if redirect is true, else Auth system ID
     */
    public static function checkAlternateAuthSystems($redirect = false, $redirect_string = '')
    {
        global $CFG_GLPI;

        if (isset($_GET["noAUTO"]) || isset($_POST["noAUTO"])) {
            return false;
        }
        $redir_string = "";
        if (!empty($redirect_string)) {
            $redir_string = "?redirect=" . rawurlencode($redirect_string);
        }
       // Using x509 server
        if (
            !empty($CFG_GLPI["x509_email_field"])
            && isset($_SERVER['SSL_CLIENT_S_DN'])
            && strstr($_SERVER['SSL_CLIENT_S_DN'], $CFG_GLPI["x509_email_field"])
        ) {
            if ($redirect) {
                Html::redirect($CFG_GLPI["root_doc"] . "/front/login.php" . $redir_string);
            } else {
                return self::X509;
            }
        }
       // Existing auth method
       //Look for the field in $_SERVER AND $_REQUEST
       // MoYo : checking REQUEST create a security hole for me !
        $ssovariable = Dropdown::getDropdownName('glpi_ssovariables', $CFG_GLPI["ssovariables_id"]);
        if (
            $CFG_GLPI["ssovariables_id"]
            && ((isset($_SERVER[$ssovariable]) && !empty($_SERVER[$ssovariable]))
              /*|| (isset($_REQUEST[$ssovariable]) && !empty($_REQUEST[$ssovariable]))*/)
        ) {
            if ($redirect) {
                Html::redirect($CFG_GLPI["root_doc"] . "/front/login.php" . $redir_string);
            } else {
                return self::EXTERNAL;
            }
        }

       // using user token for api login
        if (!empty($_REQUEST['user_token'])) {
            return self::API;
        }

       // Using CAS server
        if (!empty($CFG_GLPI["cas_host"])) {
            if ($redirect) {
                Html::redirect($CFG_GLPI["root_doc"] . "/front/login.php" . $redir_string);
            } else {
                return self::CAS;
            }
        }

        $cookie_name = session_name() . '_rememberme';
        if ($CFG_GLPI["login_remember_time"] && isset($_COOKIE[$cookie_name])) {
            if ($redirect) {
                Html::redirect($CFG_GLPI["root_doc"] . "/front/login.php" . $redir_string);
            } else {
                return self::COOKIE;
            }
        }

        return false;
    }

    /**
     * Redirect user to page if authenticated
     *
     * @param string $redirect redirect string if exists, if null, check in $_POST or $_GET
     *
     * @return void|boolean nothing if redirect is true, else false
     */
    public static function redirectIfAuthenticated($redirect = null)
    {
        global $CFG_GLPI;

        if (!Session::getLoginUserID()) {
            return false;
        }

        if (Session::mustChangePassword()) {
            Html::redirect($CFG_GLPI['root_doc'] . '/front/updatepassword.php');
        }

        if (!$redirect) {
            if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
                $redirect = $_POST['redirect'];
            } else if (isset($_GET['redirect']) && strlen($_GET['redirect']) > 0) {
                $redirect = $_GET['redirect'];
            }
            $redirect = $redirect ? Sanitizer::unsanitize($redirect) : '';
        }

       //Direct redirect
        if ($redirect) {
            Toolbox::manageRedirect($redirect);
        }

       // Redirect to Command Central if not post-only
        if (Session::getCurrentInterface() == "helpdesk") {
            if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
                Html::redirect($CFG_GLPI['root_doc'] . "/front/helpdesk.public.php?create_ticket=1");
            }
            Html::redirect($CFG_GLPI['root_doc'] . "/front/helpdesk.public.php");
        } else {
            if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
                Html::redirect(Ticket::getFormURL());
            }
            Html::redirect($CFG_GLPI['root_doc'] . "/front/central.php");
        }
    }

    /**
     * Display refresh button in the user page
     *
     * @param User $user User object
     *
     * @return void
     */
    public static function showSynchronizationForm(User $user)
    {
        global $DB, $CFG_GLPI;

        if (Session::haveRight("user", User::UPDATEAUTHENT)) {
            echo "<form method='post' action='" . Toolbox::getItemTypeFormURL('User') . "'>";
            echo "<div class='firstbloc'>";
            echo "<input type='hidden' name='id' value='" . $user->getID() . "'>";

            switch ($user->getField('authtype')) {
                case self::CAS:
                case self::EXTERNAL:
                case self::X509:
                case self::LDAP:
                   //Look it the auth server still exists !
                   // <- Bad idea : id not exists unable to change anything
                    $authldap = new AuthLDAP();
                    if (
                        $authldap->getFromDBByCrit([
                            'id'        => $user->getField('auths_id'),
                            'is_active' => 1,
                        ])
                    ) {
                        echo Html::submit("<i class='fas fa-sync-alt'></i><span>" . __s('Force synchronization') . "</span>", [
                            'name' => 'force_ldap_resynch'
                        ]);

                        if (strlen($authldap->fields['sync_field'] ?? "") > 0) {
                            echo Html::submit("<i class='fas fa-broom'></i><span>" . __s('Clean LDAP fields and force synchronisation') . "</span>", [
                                'name' => 'clean_ldap_fields'
                            ]);
                        }
                    }
                    break;

                case self::DB_GLPI:
                case self::MAIL:
                    break;
            }

            echo "</div>";

            echo "<div class='spaced'>";
            echo "<h3>" . __('Change of the authentication method') . "</h3>";
            $rand             = self::dropdown(['name' => 'authtype']);
            $paramsmassaction = ['authtype' => '__VALUE__',
                'name'     => 'change_auth_method'
            ];
            Ajax::updateItemOnSelectEvent(
                "dropdown_authtype$rand",
                "show_massiveaction_field",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionAuthMethods.php",
                $paramsmassaction
            );
            echo "<span id='show_massiveaction_field'></span>";
            echo "</div>";
            Html::closeForm();
        }
    }

    /**
     * Check if a login is valid
     *
     * @param string $login login to check
     *
     * @return boolean
     */
    public static function isValidLogin($login)
    {
        return $login !== null && (
            preg_match("/^[[:alnum:]'@.\-_ ]+$/iu", $login)
            || filter_var($login, FILTER_VALIDATE_EMAIL) != false
        );
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            switch ($item->getType()) {
                case 'User':
                    if (Session::haveRight("user", User::UPDATEAUTHENT)) {
                        return __('Synchronization');
                    }
                    break;
            }
        }
        return '';
    }

    /**
     * Show Tab content
     *
     * @since 0.83
     *
     * @param CommonGLPI $item         Item instance
     * @param integer    $tabnum       Unused (default 0)
     * @param integer    $withtemplate Unused (default 0)
     *
     * @return boolean
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'User') {
            self::showSynchronizationForm($item);
        }
        return true;
    }

    /**
     * Show form for authentication configuration.
     *
     * @return void|boolean False if the form is not shown due to right error. Form is directly printed.
     */
    public static function showOtherAuthList()
    {
        global $CFG_GLPI;

        if (!Config::canUpdate()) {
            return false;
        }
        echo "<form name=cas action='" . $CFG_GLPI['root_doc'] . "/front/auth.others.php' method='post'>";
        echo "<div class='card'>";
        echo "<table class='tab_cadre_fixe'>";

       // CAS config
        echo "<tr><th>" . __('CAS authentication') . '</th><th>';
        if (!empty($CFG_GLPI["cas_host"])) {
            echo _x('authentication', 'Enabled');
        }
        echo "</th></tr>";

        if (
            function_exists('curl_init')
            && Toolbox::canUseCAS()
        ) {
           //TRANS: for CAS SSO system
            echo "<tr class='tab_bg_2'><td class='center'>" . __('CAS Host') . "</td>";
            echo "<td><input type='text' class='form-control' name='cas_host' value=\"" . $CFG_GLPI["cas_host"] . "\"></td></tr>";
           //TRANS: for CAS SSO system
            echo "<tr class='tab_bg_2'><td class='center'>" . __('CAS Version') . "</td>";
            echo "<td>";
            Auth::dropdownCasVersion($CFG_GLPI["cas_version"] ?? null);
            echo "</td>";
            echo "</tr>";
           //TRANS: for CAS SSO system
            echo "<tr class='tab_bg_2'><td class='center'>" . _n('Port', 'Ports', 1) . "</td>";
            echo "<td><input type='text' class='form-control' name='cas_port' value=\"" . $CFG_GLPI["cas_port"] . "\"></td></tr>";
           //TRANS: for CAS SSO system
            echo "<tr class='tab_bg_2'><td class='center'>" . __('Root directory (optional)') . "</td>";
            echo "<td><input type='text' class='form-control' name='cas_uri' value=\"" . $CFG_GLPI["cas_uri"] . "\"></td></tr>";
           //TRANS: for CAS SSO system
            echo "<tr class='tab_bg_2'><td class='center'>" . __('Log out fallback URL') . "</td>";
            echo "<td><input type='text' class='form-control' name='cas_logout' value=\"" . $CFG_GLPI["cas_logout"] . "\"></td>" .
              "</tr>";
        } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            if (!function_exists('curl_init')) {
                echo "<p class='red'>" . __("The CURL extension for your PHP parser isn't installed");
                echo "</p>";
            }
            if (!Toolbox::canUseCAS()) {
                echo "<p class='red'>" . __("The CAS lib isn't available, GLPI doesn't package it anymore for license compatibility issue.");
                echo "</p>";
            }
            echo "<p>" . __('Impossible to use CAS as external source of connection') . "</p>";
            echo "<p><strong>" . GLPINetwork::getSupportPromoteMessage() . "</strong></p>";

            echo "</td></tr>";
        }
       // X509 config
        echo "<tr><th>" . __('x509 certificate authentication') . "</th><th>";
        if (!empty($CFG_GLPI["x509_email_field"])) {
            echo _x('authentication', 'Enabled');
        }
        echo "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Email attribute for x509 authentication') . "</td>";
        echo "<td><input type='text' class='form-control' name='x509_email_field' value=\"" . $CFG_GLPI["x509_email_field"] . "\">";
        echo "</td></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . sprintf(__('Restrict %s field for x509 authentication (separator $)'), 'OU') . "</td>";
        echo "<td><input type='text' class='form-control' name='x509_ou_restrict' value=\"" . $CFG_GLPI["x509_ou_restrict"] . "\">";
        echo "</td></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . sprintf(__('Restrict %s field for x509 authentication (separator $)'), 'CN') . "</td>";
        echo "<td><input type='text' class='form-control' name='x509_cn_restrict' value=\"" . $CFG_GLPI["x509_cn_restrict"] . "\">";
        echo "</td></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . sprintf(__('Restrict %s field for x509 authentication (separator $)'), 'O') . "</td>";
        echo "<td><input type='text' class='form-control' name='x509_o_restrict' value=\"" . $CFG_GLPI["x509_o_restrict"] . "\">";
        echo "</td></tr>";

       //Other configuration
        echo "<tr><th>" . __('Other authentication sent in the HTTP request') . "</th><th>";
        if (!empty($CFG_GLPI["ssovariables_id"])) {
            echo _x('authentication', 'Enabled');
        }
        echo "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . SsoVariable::getTypeName(1) . "</td>";
        echo "<td>";
        SsoVariable::dropdown(['name'  => 'ssovariables_id',
            'value' => $CFG_GLPI["ssovariables_id"]
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('SSO logout url') . "</td>";
        echo "<td><input type='text' class='form-control' name='ssologout_url' value='" .
                 $CFG_GLPI['ssologout_url'] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Remove the domain of logins like login@domain') . "</td><td>";
        Dropdown::showYesNo(
            'existing_auth_server_field_clean_domain',
            $CFG_GLPI['existing_auth_server_field_clean_domain']
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Surname') . "</td>";
        echo "<td><input type='text' class='form-control' name='realname_ssofield' value='" .
                 $CFG_GLPI['realname_ssofield'] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('First name') . "</td>";
        echo "<td><input type='text' class='form-control' name='firstname_ssofield' value='" .
                 $CFG_GLPI['firstname_ssofield'] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Comments') . "</td>";
        echo "<td><input type='text' class='form-control' name='comment_ssofield' value='" .
                 $CFG_GLPI['comment_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . _x('user', 'Administrative number') . "</td>";
        echo "<td><input type='text' class='form-control' name='registration_number_ssofield' value='" .
                  $CFG_GLPI['registration_number_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . _n('Email', 'Emails', 1) . "</td>";
        echo "<td><input type='text' class='form-control' name='email1_ssofield' value='" . $CFG_GLPI['email1_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '2') . "</td>";
        echo "<td><input type='text' class='form-control' name='email2_ssofield' value='" . $CFG_GLPI['email2_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '3') . "</td>";
        echo "<td><input type='text' class='form-control' name='email3_ssofield' value='" . $CFG_GLPI['email3_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '4') . "</td>";
        echo "<td><input type='text' class='form-control' name='email4_ssofield' value='" . $CFG_GLPI['email4_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . Phone::getTypeName(1) . "</td>";
        echo "<td><input type='text' class='form-control' name='phone_ssofield' value='" . $CFG_GLPI['phone_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" .  __('Phone 2') . "</td>";
        echo "<td><input type='text' class='form-control' name='phone2_ssofield' value='" . $CFG_GLPI['phone2_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Mobile phone') . "</td>";
        echo "<td><input type='text' class='form-control' name='mobile_ssofield' value='" . $CFG_GLPI['mobile_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . _x('person', 'Title') . "</td>";
        echo "<td><input type='text' class='form-control' name='title_ssofield' value='" . $CFG_GLPI['title_ssofield'] . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . _n('Category', 'Categories', 1) . "</td>";
        echo "<td><input type='text' class='form-control' name='category_ssofield' value='" .
                 $CFG_GLPI['category_ssofield'] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Language') . "</td>";
        echo "<td><input type='text' class='form-control' name='language_ssofield' value='" .
                 $CFG_GLPI['language_ssofield'] . "'></td></tr>";

        echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . __s('Save') . "\" >";
        echo "</td></tr>";

        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * Get authentication methods available
     *
     * @return array
     */
    public static function getLoginAuthMethods()
    {
        global $DB;

        $elements = [
            '_default'  => 'local',
            'local'     => __("GLPI internal database")
        ];

       // Get LDAP
        if (Toolbox::canUseLdap()) {
            $iterator = $DB->request([
                'FROM'   => 'glpi_authldaps',
                'WHERE'  => [
                    'is_active' => 1
                ],
                'ORDER'  => ['name']
            ]);
            foreach ($iterator as $data) {
                $elements['ldap-' . $data['id']] = $data['name'];
                if ($data['is_default'] == 1) {
                    $elements['_default'] = 'ldap-' . $data['id'];
                }
            }
        }

       // GET Mail servers
        $iterator = $DB->request([
            'FROM'   => 'glpi_authmails',
            'WHERE'  => [
                'is_active' => 1
            ],
            'ORDER'  => ['name']
        ]);
        foreach ($iterator as $data) {
            $elements['mail-' . $data['id']] = $data['name'];
        }

        return $elements;
    }

    /**
     * Display the authentication source dropdown for login form
     */
    public static function dropdownLogin(bool $display = true, $rand = 1)
    {
        $out = "";
        $elements = self::getLoginAuthMethods();
        $default = $elements['_default'];
        unset($elements['_default']);
       // show dropdown of login src only when multiple src
        $out .= Dropdown::showFromArray('auth', $elements, [
            'display'   => false,
            'rand'      => $rand,
            'value'     => $default,
            'width'     => '100%'
        ]);

        if ($display) {
            echo $out;
            return "";
        }

        return $out;
    }


    public static function getIcon()
    {
        return "ti ti-login";
    }

    /**
     * Defines "rememberme" cookie.
     *
     * @param string $cookie_value
     *
     * @return void
     */
    public static function setRememberMeCookie(string $cookie_value): void
    {
        global $CFG_GLPI;

        $cookie_name     = session_name() . '_rememberme';
        $cookie_lifetime = empty($cookie_value) ? time() - 3600 : time() + $CFG_GLPI['login_remember_time'];
        $cookie_path     = ini_get('session.cookie_path');
        $cookie_domain   = ini_get('session.cookie_domain');
        $cookie_secure   = (bool)ini_get('session.cookie_secure');
        $cookie_httponly = (bool)ini_get('session.cookie_httponly');
        $cookie_samesite = ini_get('session.cookie_samesite');

        if (empty($cookie_value) && !isset($_COOKIE[$cookie_name])) {
            return;
        }

        setcookie(
            $cookie_name,
            $cookie_value,
            [
                'expires'  => $cookie_lifetime,
                'path'     => $cookie_path,
                'domain'   => $cookie_domain,
                'secure'   => $cookie_secure,
                'httponly' => $cookie_httponly,
                'samesite' => $cookie_samesite,
            ]
        );

        if (empty($cookie_value)) {
            unset($_COOKIE[$cookie_name]);
        } else {
            $_COOKIE[$cookie_name] = $cookie_value;
        }
    }
}
