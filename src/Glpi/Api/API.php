<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/**
 * @since 9.1
 */

namespace Glpi\Api;

use AllAssets;
use APIClient;
use Auth;
use Change;
use CommonDBTM;
use CommonDevice;
use CommonITILObject;
use Config;
use Contract;
use Document;
use Dropdown;
use Glpi\Api\HL\Router;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\DBAL\QueryExpression;
use Glpi\Search\Provider\SQLProvider;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\MarkdownRenderer;
use Html;
use Infocom;
use Item_Devices;
use Log;
use MassiveAction;
use NetworkEquipment;
use NetworkPort;
use Notepad;
use Problem;
use Glpi\DBAL\QueryFunction;
use SavedSearch;
use Search;
use Session;
use Software;
use Symfony\Component\DomCrawler\Crawler;
use Ticket;
use Toolbox;
use User;

abstract class API
{
   // permit writing to $_SESSION
    protected $session_write = false;

    public static $api_url = "";
    public static $content_type = "application/json";
    protected $format          = "json";
    protected $iptxt           = "";
    protected $ipnum           = "";
    protected $app_tokens      = [];
    protected $apiclients_id   = 0;
    protected $deprecated_item = null;
    protected $request_uri;
    protected $url_elements;
    protected $verb;
    protected $parameters;
    protected $debug           = 0;

    /**
     * @param integer $nb Unused value
     *
     * @return string
     *
     * @see \CommonGLPI::getTypeName()
     */
    abstract public static function getTypeName($nb = 0);

    /**
     * First function used on api call
     * Parse sended query/parameters and call the corresponding API::method, then send response to client.
     *
     * @return void self::returnResponse called for output
     */
    abstract public function call();

    /**
     * Needed to transform params of called api in $this->parameters attribute
     *
     * @return string endpoint called
     */
    abstract protected function parseIncomingParams();

    /**
     * Send response to client.
     *
     * @since 9.1
     *
     * @param mixed   $response          string message or array of data to send
     * @param integer $httpcode          http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
     * @param array   $additionalheaders headers to send with http response (must be an array(key => value))
     *
     * @return void
     */
    abstract protected function returnResponse($response, $httpcode = 200, $additionalheaders = []);

    /**
     * Upload and validate files from request and append to $this->parameters['input']
     *
     * @return void
     */
    abstract protected function manageUploadedFiles();

    /**
     * Constructor
     *
     * @return void
     */
    public function initApi()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Load GLPI configuration
        $variables = get_defined_vars();
        foreach ($variables as $var => $value) {
            if ($var === strtoupper($var)) {
                $GLOBALS[$var] = $value;
            }
        }

       // construct api url
        $api_version_info = array_filter(Router::getAPIVersions(), static fn ($info) => (int) $info['api_version'] === 1);
        $api_version_info = reset($api_version_info);
        self::$api_url = trim($api_version_info['endpoint'], "/");

       // Don't display error in result
        ini_set('display_errors', 'Off');

       // Avoid keeping messages between api calls
        $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];

       // check if api is enabled
        if (!$CFG_GLPI['enable_api']) {
            $this->returnError(__("API disabled"), "", "", false);
        }

       // retrieve ip of client
        $this->iptxt = Toolbox::getRemoteIpAddress();
        $this->ipnum = (strstr($this->iptxt, ':') === false ? ip2long($this->iptxt) : '');

       // check ip access
        $apiclient = new APIClient();
        $where_ip = [];
        if ($this->ipnum) {
            $where_ip = [
                'OR' => [
                    'ipv4_range_start' => null,
                    [
                        'ipv4_range_start'   => ['<=', $this->ipnum],
                        'ipv4_range_end'     => ['>=', $this->ipnum]
                    ]
                ]
            ];
        } else {
            $where_ip = [
                'OR' => [
                    ['ipv6'  => null],
                    ['ipv6'  => $this->iptxt]
                ]
            ];
        }
        $found_clients = $apiclient->find(['is_active' => 1] + $where_ip);
        if (count($found_clients) <= 0) {
            $this->returnError(
                __("There isn't an active API client matching your IP address in the configuration") .
                            " (" . $this->iptxt . ")",
                "",
                "ERROR_NOT_ALLOWED_IP",
                false
            );
        }
        $app_tokens = array_column($found_clients, 'app_token');
        $apiclients_id = array_column($found_clients, 'id');
        $this->app_tokens = array_combine($apiclients_id, $app_tokens);
    }

    /**
     * Set headers according to cross origin ressource sharing
     *
     * @return void
     */
    protected function cors()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: *");
        }

        if ($this->verb == 'GET' || $this->verb == 'OPTIONS') {
            header("Access-Control-Expose-Headers: content-type, content-range, accept-range");
        }

        if ($this->verb == "OPTIONS") {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: " .
                   "origin, content-type, accept, session-token, authorization, app-token");
            }
            exit(0);
        }
    }


    /**
     * Init GLPI Session
     *
     * @param array $params array with those options :
     *    - a couple 'name' & 'password' : 2 parameters to login with user authentication
     *         OR
     *    - an 'user_token' defined in User Configuration
     *
     * @return array|void array with session_token, or void when error response is send in case of error
     */
    protected function initSession($params = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->checkAppToken();
        $this->logEndpointUsage(__FUNCTION__);

        if (
            (!isset($params['login'])
            || empty($params['login'])
            || !isset($params['password'])
            || empty($params['password']))
            && (!isset($params['user_token'])
             || empty($params['user_token']))
        ) {
            $this->returnError(
                __("parameter(s) login, password or user_token are missing"),
                400,
                "ERROR_LOGIN_PARAMETERS_MISSING"
            );
        }

        $auth = new Auth();

       // fill missing params (in case of user_token)
        if (!isset($params['login'])) {
            $params['login'] = '';
        }
        if (!isset($params['password'])) {
            $params['password'] = '';
        }

        $noAuto = true;
        if (isset($params['user_token']) && !empty($params['user_token'])) {
            $_REQUEST['user_token'] = $params['user_token'];
            $noAuto = false;
        } else if (!$CFG_GLPI['enable_api_login_credentials']) {
            $this->returnError(
                __("usage of initSession resource with credentials is disabled"),
                400,
                "ERROR_LOGIN_WITH_CREDENTIALS_DISABLED",
                false
            );
        }

        if (!isset($params['auth'])) {
            $params['auth'] = '';
        }

       // login on glpi
        if (!$auth->login($params['login'], $params['password'], $noAuto, false, $params['auth'])) {
            $err = implode(' ', $auth->getErrors());
            if (
                isset($params['user_token'])
                && !empty($params['user_token'])
            ) {
                $this->returnError(__("parameter user_token seems invalid"), 401, "ERROR_GLPI_LOGIN_USER_TOKEN", false);
            }
            $this->returnError($err, 401, "ERROR_GLPI_LOGIN", false);
        }

       // stop session and return session key
        session_write_close();
        $data = ['session_token' => $_SESSION['valid_id']];

       // Insert session data if requested
        $get_full_session = $params['get_full_session'] ?? false;
        if ($get_full_session) {
            $data['session'] = $_SESSION;
        }

        return $data;
    }


    /**
     * Kill GLPI Session
     * Use 'session_token' param in $this->parameters
     *
     * @return boolean
     */
    protected function killSession()
    {
        Session::destroy();
        return true;
    }


    /**
     * Retrieve GLPI Session initialised by initSession function
     * Use 'session_token' param in $this->parameters
     *
     * @return void
     */
    protected function retrieveSession()
    {

        if (
            isset($this->parameters['session_token'])
            && !empty($this->parameters['session_token'])
        ) {
            $current = session_id();
            $session = trim($this->parameters['session_token']);

            if (file_exists(GLPI_ROOT . '/inc/downstream.php')) {
                include_once(GLPI_ROOT . '/inc/downstream.php');
            }

            if ($session != $current && !empty($current)) {
                session_destroy();
            }
            if ($session != $current && !empty($session)) {
                session_id($session);

                // Restart the session
                Session::start();
                Session::loadLanguage();
            }
        }
    }


    /**
     * Change active entity to the entities_id one.
     *
     * @param array $params array with those options :
     *   - 'entities_id': (default 'all') ID of the new active entity ("all" = load all possible entities). Optional
     *   - 'is_recursive': (default false) Also display sub entities of the active entity.  Optional
     *
     * @return bool|void success status or void when error response is send in case of error
     */
    protected function changeActiveEntities($params = [])
    {
        if (!isset($params['entities_id'])) {
            $entities_id = 'all';
        } else {
            $entities_id = intval($params['entities_id']);
        }

        if (!isset($params['is_recursive'])) {
            $params['is_recursive'] = false;
        } else if (!is_bool($params['is_recursive'])) {
            $this->returnError();
        }

        if (!Session::changeActiveEntities($entities_id, $params['is_recursive'])) {
            return false;
        }

        return true;
    }


    /**
     * Return all the possible entity of the current logged user (and for current active profile)
     *
     * @param array $params array with those options :
     *   - 'is_recursive': (default false) Also display sub entities of the active entity. Optional
     *
     * @return array of entities (with id and name)
     */
    protected function getMyEntities($params = [])
    {
        if (!isset($params['is_recursive'])) {
            $params['is_recursive'] = false;
        }

        $myentities = [];
        foreach ($_SESSION['glpiactiveprofile']['entities'] as $entity) {
            if ($entity['is_recursive'] == 1 && $params['is_recursive'] == 1) {
                $sons = getSonsOf('glpi_entities', $entity['id']);
                foreach ($sons as $entity_id) {
                    if ($entity_id != $entity['id']) {
                        $myentities[] = ['id'   => $entity_id,
                            'name' => Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity_id
                            )
                        ];
                    }
                }
            }
            $myentities[] = ['id' => $entity['id'],
                'name' => Dropdown::getDropdownName(
                    "glpi_entities",
                    $entity['id']
                )
            ];
        }
        return ['myentities' => $myentities];
    }




    /**
     * return active entities of current logged user
     *
     * @return array with 3 keys :
     *  - active_entity : current set entity
     *  - active_entity_recursive : boolean, if we see sons of this entity
     *  - active_entities : array all active entities (active_entity and its sons)
     */
    protected function getActiveEntities()
    {
        $actives_entities = [];
        foreach (array_values($_SESSION['glpiactiveentities']) as $active_entity) {
            $actives_entities[] = ['id' => $active_entity];
        }

        return ["active_entity" => [
            "id"                      => $_SESSION['glpiactive_entity'],
            "active_entity_recursive" => $_SESSION['glpiactive_entity_recursive'],
            "active_entities"         => $actives_entities
        ]
        ];
    }




    /**
     * set a profile to active
     *
     * @param array $params with those options :
     *    - profiles_id : identifier of profile to set
     *
     * @return boolean|void success status, or void when error response is send in case of error
     */
    protected function changeActiveProfile($params = [])
    {
        if (!isset($params['profiles_id'])) {
            $this->returnError();
        }

        $profiles_id = intval($params['profiles_id']);
        if (isset($_SESSION['glpiprofiles'][$profiles_id])) {
            Session::changeProfile($profiles_id);
            return true;
        }

        $this->messageNotfoundError();
    }




    /**
     * Return all the profiles associated to logged user
     *
     * @return array of profiles (with associated rights)
     */
    protected function getMyProfiles()
    {
        $myprofiles = [];
        foreach ($_SESSION['glpiprofiles'] as $profiles_id => $profile) {
           // append if of the profile into values
            $profile = ['id' => $profiles_id] + $profile;

           // don't keep keys for entities
            $profile['entities'] = array_values($profile['entities']);

           // don't keep keys for profiles
            $myprofiles[] = $profile;
        }
        return ['myprofiles' => $myprofiles];
    }




    /**
     * Return the current active profile
     *
     * @return integer the profiles_id
     */
    protected function getActiveProfile()
    {
        return ["active_profile" => $_SESSION['glpiactiveprofile']];
    }




    /**
     * Return the current php $_SESSION
     *
     * @return array
     */
    protected function getFullSession()
    {
        return ['session' => $_SESSION];
    }



    /**
     * Return the current $CFG_GLPI
     *
     * @return array
     */
    protected function getGlpiConfig()
    {
        return ['cfg_glpi' => Config::getSafeConfig()];
    }


    /**
     * Return the instance fields of itemtype identified by id
     *
     * @param string  $itemtype itemtype (class) of object
     * @param integer $id       identifier of object
     * @param array   $params   with those options :
     *    - 'expand_dropdowns': Show dropdown's names instead of id. default: false. Optional
     *    - 'get_hateoas':      Show relation of current item in a links attribute. default: true. Optional
     *    - 'get_sha1':         Get a sha1 signature instead of the full answer. default: false. Optional
     *    - 'with_devices':  Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], Optional.
     *    - 'with_disks':       Only for Computer, retrieve the associated filesystems. Optional.
     *    - 'with_softwares':   Only for Computer, retrieve the associated software installations. Optional.
     *    - 'with_connections': Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optional.
     *    - 'with_networkports':Retrieve all network connections and advanced information. Optional.
     *    - 'with_infocoms':    Retrieve financial and administrative information. Optional.
     *    - 'with_contracts':   Retrieve associated contracts. Optional.
     *    - 'with_documents':   Retrieve associated external documents. Optional.
     *    - 'with_tickets':     Retrieve associated itil tickets. Optional.
     *    - 'with_problems':    Retrieve associated itil problems. Optional.
     *    - 'with_changes':     Retrieve associated itil changes. Optional.
     *    - 'with_notes':       Retrieve Notes (if exists, not all itemtypes have notes). Optional.
     *    - 'with_logs':        Retrieve historical. Optional.
     *    - 'add_keys_names':   Get friendly names. Optional.
     *
     * @return array    fields of found object
     */
    protected function getItem($itemtype, $id, $params = [])
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $itemtype = $this->handleDepreciation($itemtype);

       // default params
        $default = ['expand_dropdowns'  => false,
            'get_hateoas'       => true,
            'get_sha1'          => false,
            'with_devices'   => false,
            'with_disks'        => false,
            'with_softwares'    => false,
            'with_connections'  => false,
            'with_networkports' => false,
            'with_infocoms'     => false,
            'with_contracts'    => false,
            'with_documents'    => false,
            'with_tickets'      => false,
            'with_problems'     => false,
            'with_changes'      => false,
            'with_notes'        => false,
            'with_logs'         => false,
            'add_keys_names'    => [],
        ];
        $params = array_merge($default, $params);

        $item = new $itemtype();
        if (!$item->getFromDB($id)) {
            $this->messageNotfoundError();
        }
        if (!$item->can($id, READ)) {
            $this->messageRightError();
        }

        $fields = $item->fields;

       // avoid disclosure of critical fields
        $item::unsetUndisclosedFields($fields);

       // retrieve devices
        if (
            isset($params['with_devices'])
            && $params['with_devices']
            && in_array($itemtype, Item_Devices::getConcernedItems())
        ) {
            $all_devices = [];
            foreach (Item_Devices::getItemAffinities($item->getType()) as $device_type) {
                $found_devices = getAllDataFromTable(
                    $device_type::getTable(),
                    [
                        'items_id'     => $item->getID(),
                        'itemtype'     => $item->getType(),
                        'is_deleted'   => 0
                    ],
                    true
                );

                foreach ($found_devices as &$device) {
                     unset($device['items_id']);
                     unset($device['itemtype']);
                     unset($device['is_deleted']);
                }

                if (!empty($found_devices)) {
                    $all_devices[$device_type] = $found_devices;
                }
            }
            $fields['_devices'] = $all_devices;
        }

       // retrieve computer disks
        if (
            isset($params['with_disks'])
            && $params['with_disks']
            && in_array($itemtype, $CFG_GLPI['itemdeviceharddrive_types'])
        ) {
           // build query to retrive filesystems
            $fs_iterator = $DB->request([
                'SELECT'    => [
                    'glpi_filesystems.name AS fsname',
                    'glpi_items_disks.*'
                ],
                'FROM'      => 'glpi_items_disks',
                'LEFT JOIN'  => [
                    'glpi_filesystems' => [
                        'ON' => [
                            'glpi_items_disks'   => 'filesystems_id',
                            'glpi_filesystems'   => 'id'
                        ]
                    ]
                ],
                'WHERE'     => [
                    'items_id'     => $id,
                    'itemtype'     => $itemtype,
                    'is_deleted'   => 0
                ]
            ]);
            $fields['_disks'] = [];
            foreach ($fs_iterator as $data) {
                unset($data['items_id']);
                unset($data['is_deleted']);
                $fields['_disks'][] = ['name' => $data];
            }
        }

       // retrieve computer softwares
        if (
            isset($params['with_softwares'])
            && $params['with_softwares']
            && in_array($itemtype, $CFG_GLPI['software_types'])
        ) {
            $fields['_softwares'] = [];
            if (!Software::canView()) {
                $fields['_softwares'] = $this->arrayRightError();
            } else {
                $soft_iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_softwares.softwarecategories_id',
                        'glpi_softwares.id AS softwares_id',
                        'glpi_softwareversions.id AS softwareversions_id',
                        'glpi_items_softwareversions.is_dynamic',
                        'glpi_softwareversions.states_id',
                        'glpi_softwares.is_valid'
                    ],
                    'FROM'      => 'glpi_items_softwareversions',
                    'LEFT JOIN' => [
                        'glpi_softwareversions' => [
                            'ON' => [
                                'glpi_items_softwareversions' => 'softwareversions_id',
                                'glpi_softwareversions'       => 'id'
                            ]
                        ],
                        'glpi_softwares'        => [
                            'ON' => [
                                'glpi_softwareversions' => 'softwares_id',
                                'glpi_softwares'        => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_items_softwareversions.items_id'   => $id,
                        'glpi_items_softwareversions.itemtype'   => $itemtype,
                        'glpi_items_softwareversions.is_deleted' => 0
                    ],
                    'ORDERBY'   => [
                        'glpi_softwares.name',
                        'glpi_softwareversions.name'
                    ]
                ]);
                foreach ($soft_iterator as $data) {
                    $fields['_softwares'][] = $data;
                }
            }
        }

       // retrieve item connections
        if (
            isset($params['with_connections'])
            && $params['with_connections']
            && in_array($itemtype, Asset_PeripheralAsset::getPeripheralHostItemtypes(), true)
        ) {
            $fields['_connections'] = [];
            foreach ($CFG_GLPI["directconnect_types"] as $connect_type) {
                $connect_item = new $connect_type();
                if ($connect_item->canView()) {
                    $connect_table  = getTableForItemType($connect_type);
                    $relation_table = Asset_PeripheralAsset::getTable();
                    $iterator = $DB->request([
                        'SELECT'    => [
                            $relation_table . '.id AS assoc_id',
                            $relation_table . '.itemtype_item',
                            $relation_table . '.items_id_item',
                            $relation_table . '.itemtype_peripheral',
                            $relation_table . '.items_id_peripheral',
                            $relation_table . '.is_dynamic AS assoc_is_dynamic',
                            $connect_table  . '.*',
                        ],
                        'FROM'      => $relation_table,
                        'LEFT JOIN' => [
                            $connect_table => [
                                'ON' => [
                                    $relation_table => 'items_id_peripheral',
                                    $connect_table  => 'id',
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            $relation_table . '.itemtype_item' => $itemtype,
                            $relation_table . '.items_id_item' => $id,
                            $relation_table . '.itemtype_peripheral' => $connect_type,
                            $relation_table . '.is_deleted' => 0,
                        ]
                    ]);
                    foreach ($iterator as $data) {
                        $fields['_connections'][$connect_type][] = $data;
                    }
                }
            }
        }

       // retrieve item networkports
        if (isset($params['with_networkports']) && $params['with_networkports']) {
            $fields['_networkports'] = $this->getNetworkPorts($id, $itemtype);
        }

       // retrieve item infocoms
        if (
            isset($params['with_infocoms'])
            && $params['with_infocoms']
        ) {
            $fields['_infocoms'] = [];
            if (!Infocom::canView()) {
                $fields['_infocoms'] = $this->arrayRightError();
            } else {
                $ic = new Infocom();
                if ($ic->getFromDBforDevice($itemtype, $id)) {
                    $fields['_infocoms'] = $ic->fields;
                }
            }
        }

       // retrieve item contracts
        if (
            isset($params['with_contracts'])
            && $params['with_contracts']
        ) {
            $fields['_contracts'] = [];
            if (!Contract::canView()) {
                $fields['_contracts'] = $this->arrayRightError();
            } else {
                $iterator = $DB->request([
                    'SELECT'    => ['glpi_contracts_items.*'],
                    'FROM'      => 'glpi_contracts_items',
                    'LEFT JOIN' => [
                        'glpi_contracts'  => [
                            'ON' => [
                                'glpi_contracts_items'  => 'contracts_id',
                                'glpi_contracts'        => 'id'
                            ]
                        ],
                        'glpi_entities'   => [
                            'ON' => [
                                'glpi_contracts'    => 'entities_id',
                                'glpi_entities'     => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_contracts_items.items_id'  => $id,
                        'glpi_contracts_items.itemtype'  => $itemtype
                    ] + getEntitiesRestrictCriteria('glpi_contracts', '', '', true),
                    'ORDERBY'   => 'glpi_contracts.name'
                ]);
                foreach ($iterator as $data) {
                    $fields['_contracts'][] = $data;
                }
            }
        }

       // retrieve item documents
        if (
            isset($params['with_documents'])
            && $params['with_documents']
        ) {
            $fields['_documents'] = [];
            if (
                !($item instanceof CommonITILObject)
                && $itemtype != 'KnowbaseItem'
                && $itemtype != 'Reminder'
                && !Document::canView()
            ) {
                $fields['_documents'] = $this->arrayRightError();
            } else {
                $doc_criteria = [
                    'glpi_documents_items.items_id'  => $id,
                    'glpi_documents_items.itemtype'  => $itemtype
                ];
                if ($item instanceof CommonITILObject) {
                    $doc_criteria = [
                        $item->getAssociatedDocumentsCriteria(),
                        'timeline_position' => ['>', CommonITILObject::NO_TIMELINE], // skip inlined images
                    ];
                }
                $doc_iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_documents_items.id AS assocID',
                        'glpi_documents_items.date_creation AS assocdate',
                        'glpi_entities.id AS entityID',
                        'glpi_entities.completename AS entity',
                        'glpi_documentcategories.completename AS headings',
                        'glpi_documents.*'
                    ],
                    'FROM'      => 'glpi_documents_items',
                    'LEFT JOIN' => [
                        'glpi_documents'           => [
                            'ON' => [
                                'glpi_documents_items'  => 'documents_id',
                                'glpi_documents'        => 'id'
                            ]
                        ],
                        'glpi_entities'            => [
                            'ON' => [
                                'glpi_documents'  => 'entities_id',
                                'glpi_entities'   => 'id'
                            ]
                        ],
                        'glpi_documentcategories'  => [
                            'ON' => [
                                'glpi_documents'           => 'documentcategories_id',
                                'glpi_documentcategories'  => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => $doc_criteria,
                ]);
                foreach ($doc_iterator as $data) {
                    $fields['_documents'][] = $data;
                }
            }
        }

       // retrieve item tickets
        if (
            isset($params['with_tickets'])
            && $params['with_tickets']
        ) {
            $fields['_tickets'] = [];
            if (!Ticket::canView()) {
                $fields['_tickets'] = $this->arrayRightError();
            } else {
                $criteria = Ticket::getCommonCriteria();
                $criteria['WHERE'] = [
                    'glpi_items_tickets.items_id' => $id,
                    'glpi_items_tickets.itemtype' => $itemtype
                ] + getEntitiesRestrictCriteria(Ticket::getTable());
                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    $fields['_tickets'][] = $data;
                }
            }
        }

       // retrieve item problems
        if (
            isset($params['with_problems'])
            && $params['with_problems']
        ) {
            $fields['_problems'] = [];
            if (!Problem::canView()) {
                $fields['_problems'] = $this->arrayRightError();
            } else {
                $criteria = Problem::getCommonCriteria();
                $criteria['WHERE'] = [
                    'glpi_items_problems.items_id' => $id,
                    'glpi_items_problems.itemtype' => $itemtype
                ] + getEntitiesRestrictCriteria(Problem::getTable());
                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    $fields['_problems'][] = $data;
                }
            }
        }

       // retrieve item changes
        if (
            isset($params['with_changes'])
            && $params['with_changes']
        ) {
            $fields['_changes'] = [];
            if (!Change::canView()) {
                $fields['_changes'] = $this->arrayRightError();
            } else {
                $criteria = Change::getCommonCriteria();
                $criteria['WHERE'] = [
                    'glpi_changes_items.items_id' => $id,
                    'glpi_changes_items.itemtype' => $itemtype
                ] + getEntitiesRestrictCriteria(Change::getTable());
                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    $fields['_changes'][] = $data;
                }
            }
        }

       // retrieve item notes
        if (
            isset($params['with_notes'])
            && $params['with_notes']
        ) {
            $fields['_notes'] = [];
            if (!Session::haveRight($itemtype::$rightname, READNOTE)) {
                $fields['_notes'] = $this->arrayRightError();
            } else {
                $fields['_notes'] = Notepad::getAllForItem($item);
            }
        }

       // retrieve item logs
        if (
            isset($params['with_logs'])
            && $params['with_logs']
        ) {
            $fields['_logs'] = [];
            if (!Log::canView()) {
                $fields['_logs'] = $this->arrayRightError();
            } else {
                $fields['_logs'] = getAllDataFromTable(
                    "glpi_logs",
                    [
                        'items_id'  => $item->getID(),
                        'itemtype'  => $item->getType()
                    ]
                );
            }
        }

       // expand dropdown (retrieve name of dropdowns) and get hateoas from foreign keys
        $fields = self::parseDropdowns($fields, $params);

       // get hateoas from children
        if ($params['get_hateoas']) {
            $hclasses = self::getHatoasClasses($itemtype);
            foreach ($hclasses as $hclass) {
                $fields['links'][] = ['rel'  => $hclass,
                    'href' => self::$api_url . "/$itemtype/" . $item->getID() . "/$hclass/"
                ];
            }
        }

       // get sha1 footprint if needed
        if ($params['get_sha1']) {
            $fields = sha1(json_encode($fields, JSON_UNESCAPED_UNICODE
                                             | JSON_UNESCAPED_SLASHES
                                             | JSON_NUMERIC_CHECK));
        }

        if (count($params['add_keys_names']) > 0) {
            $fields["_keys_names"] = $this->getFriendlyNames(
                $fields,
                $params,
                $itemtype
            );
        }

       // Convert fields to the format expected by the deprecated type
        if ($this->isDeprecated()) {
            $fields = $this->deprecated_item->mapCurrentToDeprecatedFields($fields);
            $fields["links"] = $this->deprecated_item->mapCurrentToDeprecatedHateoas(
                $fields["links"] ?? []
            );
        }

        return $fields;
    }



    /**
     * Fill a sub array with a right error
     *
     * @return array
     */
    protected function arrayRightError()
    {

        return ['error'   => 401,
            'message' => __("You don't have permission to perform this action.")
        ];
    }





    /**
     * Return a collection of rows of the desired itemtype
     *
     * @param class-string<CommonDBTM>  $itemtype   itemtype (class) of object
     * @param array   $params     with those options :
     * - 'expand_dropdowns' (default: false): show dropdown's names instead of id. Optional
     * - 'get_hateoas'      (default: true): show relations of items in a links attribute. Optional
     * - 'only_id'          (default: false): keep only id in fields list. Optional
     * - 'range'            (default: 0-49): limit the list to start-end attributes
     * - 'sort'             (default: id): sort by the field.
     * - 'order'            (default: ASC): ASC(ending) or DESC(ending).
     * - 'searchText'       (default: NULL): array of filters to pass on the query (with key = field and value the search)
     * - 'is_deleted'       (default: false): show trashbin. Optional
     * - 'add_keys_names'   (default: []): insert raw name(s) for given itemtype(s) and fkey(s)
     * - 'with_networkports'(default: false): Retrieve all network connections and advanced information. Optional.
     * @param integer $totalcount output parameter who receive the total count of the query result.
     *                            As this function paginate results (with a mysql LIMIT),
     *                            we can have the full range. (default 0)
     *
     * @return array|void collection of fields, or void when error response is send in case of error
     */
    protected function getItems($itemtype, $params = [], &$totalcount = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $itemtype = $this->handleDepreciation($itemtype);

        // default params
        $default = ['expand_dropdowns' => false,
            'get_hateoas'       => true,
            'only_id'           => false,
            'range'             => "0-" . ($_SESSION['glpilist_limit'] - 1),
            'sort'              => "id",
            'order'             => "ASC",
            'searchText'        => null,
            'is_deleted'        => false,
            'add_keys_names'    => [],
            'with_networkports' => false,
        ];
        $params = array_merge($default, $params);

        if (!$itemtype::canView()) {
            $this->messageRightError();
        }

        $found = [];
        $item = new $itemtype();
        $item->getEmpty();
        $table = getTableForItemType($itemtype);

        // transform range parameter in start and limit variables
        if (preg_match("/^[0-9]+-[0-9]+\$/", $params['range'])) {
            $range = explode("-", $params['range']);
            $params['start']      = $range[0];
            $params['list_limit'] = (int)$range[1] - (int)$range[0] + 1;
            $params['range']      = $range;
        } else {
            $this->returnError("range must be in format : [start-end] with integers");
        }

        // check parameters
        if (
            isset($params['order'])
            && !in_array(strtoupper($params['order']), ['DESC', 'ASC'])
        ) {
            $this->returnError("order must be DESC or ASC");
        }
        if (!isset($item->fields[$params['sort']])) {
            $this->returnError("sort param is not a field of $table");
        }

       //specific case for restriction
        $already_linked_table = [];
        $criteria = SQLProvider::getDefaultJoinCriteria($itemtype, $table, $already_linked_table);
        $criteria['WHERE'] = SQLProvider::getDefaultWhereCriteria($itemtype);
        if (!isset($criteria['LEFT JOIN'])) {
            $criteria['LEFT JOIN'] = [];
        }

        if (empty($criteria['WHERE'])) {
            $criteria['WHERE'] = [
                new QueryExpression('true')
            ];
        }
        if ($item->maybeDeleted()) {
            $criteria['WHERE']["$table.is_deleted"] = (int) $params['is_deleted'];
        }

       // add filter for a parent itemtype
        if (
            isset($this->parameters['parent_itemtype'])
            && isset($this->parameters['parent_id'])
        ) {
           // check parent itemtype
            if (
                !Toolbox::isCommonDBTM($this->parameters['parent_itemtype'])
                && !Toolbox::isAPIDeprecated($this->parameters['parent_itemtype'])
            ) {
                $this->returnError(
                    __("parent itemtype not found or not an instance of CommonDBTM"),
                    400,
                    "ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM"
                );
            }

            $fk_parent = getForeignKeyFieldForItemType($this->parameters['parent_itemtype']);
            $fk_child = getForeignKeyFieldForItemType($itemtype);

           // check parent rights
            $parent_item = new $this->parameters['parent_itemtype']();
            if (!$parent_item->getFromDB($this->parameters['parent_id'])) {
                $this->messageNotfoundError();
            }
            if (!$parent_item->can($this->parameters['parent_id'], READ)) {
                $this->messageRightError();
            }

           // filter with parents fields
            if (isset($item->fields[$fk_parent])) {
                $criteria['WHERE']["$table.$fk_parent"] = (int) $this->parameters['parent_id'];
            } else if (
                isset($item->fields['itemtype'], $item->fields['items_id'])
            ) {
                $criteria['WHERE']["$table.itemtype"] = $this->parameters['parent_itemtype'];
                $criteria['WHERE']["$table.items_id"] = (int) $this->parameters['parent_id'];
            } else if (isset($parent_item->fields[$fk_child])) {
                $parentTable = getTableForItemType($this->parameters['parent_itemtype']);
                $criteria['LEFT JOIN'][$parentTable] = [
                    'ON' => [
                        $parentTable => $fk_child,
                        $table       => 'id'
                    ]
                ];
                $criteria['WHERE']["$parentTable.id"] = (int) $this->parameters['parent_id'];
            } else if (
                isset($parent_item->fields['itemtype'])
                 && isset($parent_item->fields['items_id'])
            ) {
                $parentTable = getTableForItemType($this->parameters['parent_itemtype']);
                $criteria['LEFT JOIN'][$parentTable] = [
                    'ON' => [
                        $parentTable    => 'items_id',
                        $table          => 'id',
                        [
                            'AND' => [
                                "$parentTable.itemtype" => $itemtype,
                            ]
                        ]
                    ]
                ];
                $criteria['WHERE']["$parentTable.id"] = (int) $this->parameters['parent_id'];
            }
        }

       // filter by searchText parameter
        if (is_array($params['searchText'])) {
            if (array_keys($params['searchText']) == ['all']) {
                $labelfield = "name";
                if ($item instanceof CommonDevice) {
                    $labelfield = "designation";
                } else if ($item instanceof Item_Devices) {
                    $labelfield = "itemtype";
                }
                $search_value                      = $params['searchText']['all'];
                $params['searchText'][$labelfield] = $search_value;
                if ($DB->fieldExists($table, 'comment')) {
                    $params['searchText']['comment'] = $search_value;
                }
            }

            // ensure search feature is not used to enumerate sensitive fields value
            $search_values = $params['searchText'];
            $item::unsetUndisclosedFields($search_values);

            // make text search
            foreach ($search_values as $filter_field => $filter_value) {
                if (!$DB->fieldExists($table, $filter_field)) {
                    $this->returnError(
                        sprintf(__('Field %s is not valid for %s item.'), $filter_field, $item->getType()),
                        400,
                        "ERROR_FIELD_NOT_FOUND"
                    );
                }
                if (!empty($filter_value)) {
                    $criteria['WHERE']["$table.$filter_field"] = ['LIKE', SQLProvider::makeTextSearchValue($filter_value)];
                }
            }
        }

       // filter with entity
        if ($item->getType() == 'Entity') {
            $criteria['WHERE'][] = getEntitiesRestrictCriteria($itemtype::getTable());
        } else if (
            $item->isEntityAssign()
            // some CommonDBChild classes may not have entities_id fields and isEntityAssign still return true (like ITILTemplateMandatoryField)
            && array_key_exists('entities_id', $item->fields)
        ) {
            $entity_restrict = getEntitiesRestrictCriteria($itemtype::getTable(), '', $_SESSION['glpiactiveentities'], $item->maybeRecursive(), true);

            if ($item instanceof SavedSearch) {
                $criteria['WHERE'][] = [
                    'OR' => [
                        $entity_restrict,
                        "$table.is_private" => 1,
                    ]
                ];
            } else {
                $criteria['WHERE'][] = $entity_restrict;
            }
        }

       // Check if we need to add raw names later on
        $add_keys_names = count($params['add_keys_names']) > 0;

       // build query
        $criteria['SELECT'] = ["$table.id", "$table.*"];
        $criteria['DISTINCT'] = true;
        $criteria['FROM'] = $table;
        $criteria['ORDER'] = $params['sort'] . ' ' . $params['order'];
        $criteria['START'] = (int) $params['start'];
        $criteria['LIMIT'] = (int) $params['list_limit'];
        $result = $DB->request($criteria);
        foreach ($result as $data) {
            if ($add_keys_names) {
                // Insert raw names into the data row
                $data["_keys_names"] = $this->getFriendlyNames(
                    $data,
                    $params,
                    $itemtype
                );
            }

            if (isset($params['with_networkports']) && $params['with_networkports']) {
                $data['_networkports'] = $this->getNetworkPorts($data['id'], $itemtype);
            }

            $found[] = $data;
        }

        // get result full row counts
        $count_result = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => $table,
            'LEFT JOIN' => $criteria['LEFT JOIN'],
            'WHERE' => $criteria['WHERE'],
        ]);
        $totalcount = $count_result->current()['cpt'];

        if ($params['range'][0] > $totalcount) {
            $this->returnError(
                "Provided range exceed total count of data: " . $totalcount,
                400,
                "ERROR_RANGE_EXCEED_TOTAL"
            );
        }

        foreach ($found as &$fields) {
           // only keep id in field list
            if ($params['only_id']) {
                $fields = ['id' => $fields['id']];
            }

           // avioid disclosure of critical fields
            $item::unsetUndisclosedFields($fields);

           // expand dropdown (retrieve name of dropdowns) and get hateoas
            $fields = self::parseDropdowns($fields, $params);

           // get hateoas from children
            if ($params['get_hateoas']) {
                $hclasses = self::getHatoasClasses($itemtype);
                foreach ($hclasses as $hclass) {
                    $fields['links'][] = ['rel' => $hclass,
                        'href' => self::$api_url . "/$itemtype/" . $fields['id'] . "/$hclass/"
                    ];
                }
            }
        }
       // Break reference
        unset($fields);

       // Map values for deprecated itemtypes
        if ($this->isDeprecated()) {
            $found = array_map(function ($fields) {
                return $this->deprecated_item->mapCurrentToDeprecatedFields($fields);
            }, $found);
        }

        return array_values($found);
    }

    /**
     * Return a collection of items queried in input ($items)
     *
     * Call self::getItem for each line of $items
     *
     * @param array $params with those options :
     *    - items:               array containing lines with itemtype and items_id keys
     *                               Ex: [
     *                                      [itemtype => 'Ticket', id => 102],
     *                                      [itemtype => 'User',   id => 10],
     *                                      [itemtype => 'User',   id => 11],
     *                                   ]
     *    - 'expand_dropdowns':  Show dropdown's names instead of id. default: false. Optional
     *    - 'get_hateoas':       Show relation of current item in a links attribute. default: true. Optional
     *    - 'get_sha1':          Get a sha1 signature instead of the full answer. default: false. Optional
     *    - 'with_devices':   Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], Optional.
     *    - 'with_disks':        Only for Computer, retrieve the associated filesystems. Optional.
     *    - 'with_softwares':    Only for Computer, retrieve the associated software installations. Optional.
     *    - 'with_connections':  Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optional.
     *    - 'with_networkports': Retrieve all network connections and advanced information. Optional.
     *    - 'with_infocoms':     Retrieve financial and administrative information. Optional.
     *    - 'with_contracts':    Retrieve associated contracts. Optional.
     *    - 'with_documents':    Retrieve associated external documents. Optional.
     *    - 'with_tickets':      Retrieve associated itil tickets. Optional.
     *    - 'with_problems':     Retrieve associated itil problems. Optional.
     *    - 'with_changes':      Retrieve associated itil changes. Optional.
     *    - 'with_notes':        Retrieve Notes (if exists, not all itemtypes have notes). Optional.
     *    - 'with_logs':         Retrieve historical. Optional.
     *
     * @return array collection of glpi object's fields
     */
    protected function getMultipleItems($params = [])
    {

        if (!is_array($params['items'])) {
            $this->messageBadArrayError();
        }

        $allitems = [];
        foreach ($params['items'] as $item) {
            if (!isset($item['items_id']) && !isset($item['itemtype'])) {
                $this->messageBadArrayError();
            }

            $fields = $this->getItem($item['itemtype'], $item['items_id'], $params);
            $allitems[] = $fields;
        }

        return $allitems;
    }


    /**
     * List the searchoptions of provided itemtype. To use with searchItems function
     *
     * @param string $itemtype             itemtype (class) of object
     * @param array  $params               parameters
     * @param bool   $check_depreciation   disable depreciation check, useful
     *                                     if depreciation have already been
     *                                     handled by a parent call (e.g. search)
     *
     * @return array all searchoptions of specified itemtype
     */
    protected function listSearchOptions(
        $itemtype,
        $params = [],
        bool $check_depreciation = true
    ) {
        if ($check_depreciation) {
            $itemtype = $this->handleDepreciation($itemtype);
        }

        $soptions = SearchOption::getOptionsForItemtype($itemtype);

        if (isset($params['raw'])) {
            return $soptions;
        }

        $cleaned_soptions = [];
        foreach ($soptions as $sID => $option) {
            if (is_int($sID)) {
                $available_searchtypes = Search::getActionsFor($itemtype, $sID);
                unset($available_searchtypes['searchopt']);
                $available_searchtypes = array_keys($available_searchtypes);

                $cleaned_soptions[$sID] = ['name'                  => $option['name'],
                    'table'                 => $option['table'],
                    'field'                 => $option['field'],
                    'datatype'              => isset($option['datatype'])
                                                                       ? $option['datatype']
                                                                       : "",
                    'nosearch'              => isset($option['nosearch'])
                                                                       ? $option['nosearch']
                                                                       : false,
                    'nodisplay'             => isset($option['nodisplay'])
                                                                       ? $option['nodisplay']
                                                                       : false,
                    'available_searchtypes' => $available_searchtypes
                ];
                $cleaned_soptions[$sID]['uid'] = $this->getSearchOptionUniqID(
                    $itemtype,
                    $option
                );
            } else {
                if (is_string($option)) {
                    $option = ['name' => $option];
                }
                $cleaned_soptions[$sID] = $option;
            }
        }

        if ($check_depreciation && $this->isDeprecated()) {
            $cleaned_soptions = $this->deprecated_item->mapCurrentToDeprecatedSearchOptions($cleaned_soptions);
        }

        return $cleaned_soptions;
    }


    /**
     * Generate an unique id of a searchoption based on:
     *  - itemtype
     *  - linkfield
     *  - joinparams
     *  - field
     *
     * It permits to identify a searchoption with an named index instead a numeric one
     *
     * @param CommonDBTM $itemtype current itemtype called on ressource listSearchOption
     * @param array      $option   current option to generate an unique id
     *
     * @return string the unique id
     */
    private function getSearchOptionUniqID($itemtype, $option = [])
    {

        $uid_parts = [$itemtype];

        $sub_itemtype = getItemTypeForTable($option['table']);

        if (
            (isset($option['joinparams']['beforejoin']['table'])
            || empty($option['joinparams']))
            && $option['linkfield'] != getForeignKeyFieldForItemType($sub_itemtype)
            && $option['linkfield'] != $option['field']
        ) {
            $uid_parts[] = $option['linkfield'];
        }

        if (isset($option['joinparams'])) {
            if (isset($option['joinparams']['beforejoin'])) {
                $sub_parts  = $this->getSearchOptionUniqIDJoins($option['joinparams']['beforejoin']);
                $uid_parts = array_merge($uid_parts, $sub_parts);
            }
        }

        if (
            isset($option['joinparams']['beforejoin']['table'])
            || $sub_itemtype != $itemtype
        ) {
            $uid_parts[] = $sub_itemtype;
        }

        $uid_parts[] = $option['field'];

        $uuid = implode('.', $uid_parts);

        return $uuid;
    }


    /**
     * Generate subpart of a unique id of a search option with parsing joinparams recursively
     *
     * @param array $option ['joinparams']['beforejoin'] subpart of a searchoption
     *
     * @return array unique id parts
     */
    private function getSearchOptionUniqIDJoins($option)
    {

        $uid_parts = [];
        if (isset($option['joinparams']['beforejoin'])) {
            $sub_parts  = $this->getSearchOptionUniqIDJoins($option['joinparams']['beforejoin']);
            $uid_parts = array_merge($uid_parts, $sub_parts);
        }

        if (isset($option['table'])) {
            $uid_parts[] = getItemTypeForTable($option['table']);
        }

        return $uid_parts;
    }


    /**
     * Expose the GLPI searchEngine
     *
     * @param string $itemtype itemtype (class) of object
     * @param array  $params   with those options :
     *    - 'criteria': array of criterion object to filter search.
     *        Optional.
     *        Each criterion object must provide :
     *           - link: (optional for 1st element) logical operator in [AND, OR, AND NOT, AND NOT].
     *           - field: id of searchoptions.
     *           - searchtype: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
     *           - value : value to search.
     *    - 'metacriteria' (optional): array of metacriterion object to filter search.
     *                                  Optional.
     *                                  A meta search is a link with another itemtype
     *                                  (ex: Computer with software).
     *         Each metacriterion object must provide :
     *            - link: logical operator in [AND, OR, AND NOT, AND NOT]. Mandatory
     *            - itemtype: second itemtype to link.
     *            - field: id of searchoptions.
     *            - searchtype: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
     *            - value : value to search.
     *    - 'sort' :  id of searchoption to sort by (default 1). Optional.
     *    - 'order' : ASC - Ascending sort / DESC Descending sort (default ASC). Optional.
     *    - 'range' : a string with a couple of number for start and end of pagination separated by a '-'. Ex : 150-199. (default 0-49)
     *                Optional.
     *    - 'forcedisplay': array of columns to display (default empty = empty use display pref and search criterias).
     *                      Some columns will be always presents (1-id, 2-name, 80-Entity).
     *                      Optional.
     *    - 'rawdata': boolean for displaying raws data of Search engine of glpi (like sql request, and full searchoptions)
     *
     * @return array|void array of raw rows from Search class, or void when error response is sent in case of error
     */
    protected function searchItems($itemtype, $params = [])
    {
        $itemtype = $this->handleDepreciation($itemtype);

       // check rights
        if (!$itemtype::canView()) {
            $this->messageRightError();
        }

       // retrieve searchoptions
        $soptions = $this->listSearchOptions($itemtype, [], false);

        if ($this->isDeprecated()) {
            $criteria = $this->deprecated_item->mapDeprecatedToCurrentCriteria(
                $params['criteria'] ?? []
            );

            if (count($criteria)) {
                $params['criteria'] = $criteria;
            }
        }

       // Check the criterias are valid
        if (isset($params['criteria']) && is_array($params['criteria'])) {
           // use a recursive closure to check each nested criteria
            $check_criteria = function (&$criteria) use (&$check_criteria, $soptions) {
                foreach ($criteria as &$criterion) {
                     // recursive call
                    if (isset($criterion['criteria'])) {
                        return $check_criteria($criterion['criteria']);
                    }

                    if (
                        !isset($criterion['field']) || !isset($criterion['searchtype'])
                        || !isset($criterion['value'])
                    ) {
                        return __("Malformed search criteria");
                    }

                    if (
                        !ctype_digit((string) $criterion['field'])
                        || !array_key_exists($criterion['field'], $soptions)
                    ) {
                        return __("Bad field ID in search criteria");
                    }

                    if (
                        isset($soptions[$criterion['field']])
                        && isset($soptions[$criterion['field']]['nosearch'])
                        && $soptions[$criterion['field']]['nosearch']
                    ) {
                        return __("Forbidden field ID in search criteria");
                    }
                }

                return true;
            };

           // call the closure
            $check_criteria_result = $check_criteria($params['criteria']);
            if ($check_criteria_result !== true) {
                $this->returnError($check_criteria_result);
            }
        }

       // manage forcedisplay
        if (isset($params['forcedisplay'])) {
            if (!is_array($params['forcedisplay'])) {
                $params['forcedisplay'] = [intval($params['forcedisplay'])];
            }
            $params['forcedisplay'] = array_combine($params['forcedisplay'], $params['forcedisplay']);
        } else {
            $params['forcedisplay'] = [];
        }
        foreach ($params['forcedisplay'] as $forcedisplay) {
            if (
                isset($soptions[$forcedisplay]) && isset($soptions[$forcedisplay]['nodisplay'])
                && $soptions[$forcedisplay]['nodisplay']
            ) {
                $this->returnError(__("ID is forbidden along with 'forcedisplay' parameter."));
            }
        }

       // transform range parameter in start and limit variables
        if (isset($params['range'])) {
            if (preg_match("/^[0-9]+-[0-9]+\$/", $params['range'])) {
                $range = explode("-", $params['range']);
                $params['start']      = $range[0];
                $params['list_limit'] = (int)$range[1] - (int)$range[0] + 1;
                $params['range']      = $range;
            } else {
                $this->returnError("range must be in format : [start-end] with integers");
            }
        } else {
            $params['range'] = [0, $_SESSION['glpilist_limit'] - 1];
        }

       // force reset
        $params['reset'] = 'reset';

       // call Core Search method
        $rawdata = Search::getDatas($itemtype, $params, $params['forcedisplay']);

       // probably a sql error
        if (!isset($rawdata['data']) || count($rawdata['data']) === 0) {
            $this->returnError(
                'An internal error occured while trying to fetch the data.',
                500,
                "ERROR_SQL",
                false
            );
        }

        $cleaned_data = ['totalcount' => $rawdata['data']['totalcount'],
            'count'      => count($rawdata['data']['rows']),
            'sort'       => $rawdata['search']['sort'],
            'order'      => $rawdata['search']['order']
        ];

        if ($params['range'][0] > $cleaned_data['totalcount']) {
            $this->returnError(
                "Provided range exceed total count of data: " . $cleaned_data['totalcount'],
                400,
                "ERROR_RANGE_EXCEED_TOTAL"
            );
        }

       // fix end range
        if ($params['range'][1] > $cleaned_data['totalcount'] - 1) {
            $params['range'][1] = $cleaned_data['totalcount'] - 1;
        }

       //prepare cols (searchoptions_id) for cleaned data
        $cleaned_cols = [];
        $uid_cols = [];
        foreach ($rawdata['data']['cols'] as $col) {
            $cleaned_cols[] = $col['id'];
            if (isset($params['uid_cols'])) {
               // prepare cols with uid
                if (isset($col['meta']) && $col['meta']) {
                    $meta_opts = $this->listSearchOptions($col['itemtype'], [], false);
                    $uid_cols[] = $meta_opts[$col['id']]['uid'];
                } else {
                    $uid_cols[] = $soptions[$col['id']]['uid'];
                }
            }
        }

        foreach ($rawdata['data']['rows'] as $row) {
            $raw = $row['raw'];
            $id = $raw['id'];

           // retrive value (and manage multiple values)
            $clean_values = [];
            foreach ($rawdata['data']['cols'] as $col) {
                $rvalues = $row[$col['itemtype'] . '_' . $col['id']];

               // manage multiple values (ex: IP adresses)
                $current_values = [];
                for ($valindex = 0; $valindex < $rvalues['count']; $valindex++) {
                    $current_values[] = $rvalues[$valindex]['name'];
                }
                if (count($current_values) == 1) {
                    $current_values = $current_values[0];
                }

                // Undisclose sensitive fields
                // Pass any additional field listed by the corresponding search option
                $col_ref_table    = $col['searchopt']['table'] ?? '';
                $col_ref_field    = $col['searchopt']['field'] ?? '';
                $col_ref_itemtype = $col_ref_table !== '' && $col_ref_field !== ''
                    ? \getItemTypeForTable($col['searchopt']['table'] ?? '')
                    : null;
                if ($col_ref_itemtype !== null && \is_a($col_ref_itemtype, CommonDBTM::class, true)) {
                    $tmp_fields = [$col_ref_field => $current_values];
                    if (array_key_exists('additionalfields', $col['searchopt'])) {
                        foreach ($col['searchopt']['additionalfields'] as $field_name) {
                            $field_value_key = 'ITEM_' . $col['itemtype'] . '_' . $col['id'] . '_' . $field_name;
                            $tmp_fields[$field_name] = $raw[$field_value_key];
                        }
                    }
                    $col_ref_itemtype::unsetUndisclosedFields($tmp_fields);
                    $current_values = $tmp_fields[$col_ref_field] ?? null;
                }

                $clean_values[] = $current_values;
            }

           // combine cols (searchoptions_id) with values (raws data)
            if (isset($params['uid_cols'])) {
                $current_line = array_combine($uid_cols, $clean_values);
            } else {
                $current_line = array_combine($cleaned_cols, $clean_values);
            }

           // if all asset, provide type in returned data
            if ($itemtype == AllAssets::getType()) {
                $current_line['id']       = $raw['id'];
                $current_line['itemtype'] = $raw['TYPE'];
            }

           // append to final array
            if (isset($params['withindexes'])) {
                $cleaned_data['data'][$id] = $current_line;
            } else {
                $cleaned_data['data'][] = $current_line;
            }
        }

       // add rows with their html
        if (isset($params['giveItems'])) {
            $cleaned_data['data_html'] = [];
            foreach ($rawdata['data']['rows'] as $row) {
                $new_row = [];
                foreach ($row as $cell_key => $cell) {
                    if (isset($cell['displayname'])) {
                        $new_row[$cell_key] = $cell['displayname'];
                    }
                }
                $new_row = array_combine($cleaned_cols, $new_row);

                if (isset($params['withindexes'])) {
                    $cleaned_data['data_html'][$row['id']] = $new_row;
                } else {
                    $cleaned_data['data_html'][] = $new_row;
                }
            }
        }

        if (
            isset($params['rawdata'])
            && $params['rawdata']
        ) {
            $cleaned_data['rawdata'] = $rawdata;
        }

        $cleaned_data['content-range'] = implode('-', $params['range']) .
                                       "/" . $cleaned_data['totalcount'];

       // return data
        return $cleaned_data;
    }


    /**
     * Add an object to GLPI
     *
     * @param string $itemtype itemtype (class) of object
     * @param array  $params   with those options :
     *    - 'input' : object with fields of itemtype to be inserted.
     *                You can add several items in one action by passing array of input object.
     *                Mandatory.
     *
     * @return array|void array of id, or void when error response is send in case of error
     */
    protected function createItems($itemtype, $params = [])
    {
        $itemtype = $this->handleDepreciation($itemtype);

        $input    = isset($params['input']) ? $params["input"] : null;

        if (is_object($input)) {
            $input = [$input];
            $isMultiple = false;
        } else {
            $isMultiple = true;
        }

        if ($this->isDeprecated()) {
            $input = array_map(function ($item) {
                return $this->deprecated_item->mapDeprecatedToCurrentFields($item);
            }, $input);
        }

        if (is_array($input)) {
            $idCollection = [];
            $failed       = 0;
            $index        = 0;
            foreach ($input as $object) {
                // Use a new instance each time to avoid side effects with data from a previous item (See #14490)
                $item     = new $itemtype();
                $object      = $this->inputObjectToArray($object);
                $current_res = [];

               //check rights
                if (!$item->can(-1, CREATE, $object)) {
                    $failed++;
                    $current_res = ['id'      => false,
                        'message' => __("You don't have permission to perform this action.")
                    ];
                } else {
                   // add missing entity
                    if (!isset($object['entities_id'])) {
                        $object['entities_id'] = $_SESSION['glpiactive_entity'];
                    }

                   // add an entry to match gui post (which contains submit button)
                   // to force having messages after redirect
                    $object["_add"] = true;

                   //add current item
                    $new_id = $item->add($object);
                    if ($new_id === false) {
                        $failed++;
                    }

                    $message = $this->getGlpiLastMessage();
                    $current_res = ['id'      => $new_id,
                        'message' => $message
                    ];
                }

               // attach fileupload answer
                if (
                    isset($params['upload_result'])
                    && isset($params['upload_result'][$index])
                ) {
                    $current_res['upload_result'] = $params['upload_result'][$index];
                }

               // append current result to final collection
                $idCollection[] = $current_res;
                $index++;
            }

            if ($isMultiple) {
                if ($failed == count($input)) {
                    $this->returnError($idCollection, 400, "ERROR_GLPI_ADD", false);
                } else if ($failed > 0) {
                    $this->returnError($idCollection, 207, "ERROR_GLPI_PARTIAL_ADD", false);
                }
            } else {
                if ($failed > 0) {
                    $this->returnError($idCollection[0]['message'], 400, "ERROR_GLPI_ADD", false);
                } else {
                    return $idCollection[0];
                }
            }
            return $idCollection;
        } else {
            $this->messageBadArrayError();
        }
    }

    /**
     * Transform all stdobject retrieved from a json_decode into arrays
     *
     * @since 9.1
     *
     * @param  mixed $input can be an object or array
     *
     * @return array the cleaned input
     */
    private function inputObjectToArray($input)
    {
        if (is_object($input)) {
            $input = get_object_vars($input);
        }

        if (is_array($input)) {
            foreach ($input as &$sub_input) {
                $sub_input = self::inputObjectToArray($sub_input);
            }
        }

        return $input;
    }


    /**
     * Update an object to GLPI
     *
     * @param string $itemtype itemtype (class) of object
     * @param array  $params   with those options :
     *    - 'input' : Array of objects with fields of itemtype to be updated.
     *                Mandatory.
     *                You must provide in each object a key named 'id' to identify item to update.
     *
     * @return array|void  array of boolean, or void when error response is send in case of error
     */
    protected function updateItems($itemtype, $params = [])
    {
        $itemtype = $this->handleDepreciation($itemtype);
        $input    = isset($params['input']) ? $params["input"] : null;

        if (is_object($input)) {
            $input = [$input];
            $isMultiple = false;
        } else {
            $isMultiple = true;
        }

        if ($this->isDeprecated()) {
            $input = array_map(function ($item) {
                return $this->deprecated_item->mapDeprecatedToCurrentFields($item);
            }, $input);
        }

        if (is_array($input)) {
            $idCollection = [];
            $failed       = 0;
            $index        = 0;
            foreach ($input as $object) {
                // Use a new instance each time to avoid side effects with data from a previous item (See #14490)
                $item     = new $itemtype();
                $current_res = [];
                if (isset($object->id)) {
                    if (!$item->getFromDB($object->id)) {
                        $failed++;
                        $current_res = [$object->id => false,
                            'message'   => __("Item not found")
                        ];
                        continue;
                    }

                    //check rights
                    if (!$item->can($object->id, UPDATE)) {
                        $failed++;
                        $current_res = [$object->id => false,
                            'message'    => __("You don't have permission to perform this action.")
                        ];
                    } else {
                        // if parent key not provided in input and present in parameter
                        // (detected from url for example), try to appent it do input
                        // This is usefull to have logs in parent (and avoid some warnings in commonDBTM)
                        if (
                            isset($params['parent_itemtype'])
                            && isset($params['parent_id'])
                        ) {
                            $fk_parent = getForeignKeyFieldForItemType($params['parent_itemtype']);
                            if (!property_exists($object, $fk_parent)) {
                                $object->$fk_parent = $params['parent_id'];
                            }
                        }

                     //update item
                        $object = $this->inputObjectToArray($object);
                        $update_return = $item->update($object);
                        if ($update_return === false) {
                             $failed++;
                        }
                        $current_res = [$item->fields["id"] => $update_return,
                            'message'           => $this->getGlpiLastMessage()
                        ];
                    }
                }
               // attach fileupload answer
                if (
                    isset($params['upload_result'])
                    && isset($params['upload_result'][$index])
                ) {
                    $current_res['upload_result'] = $params['upload_result'][$index];
                }

               // append current result to final collection
                $idCollection[] = $current_res;
                $index++;
            }
            if ($isMultiple) {
                if ($failed == count($input)) {
                    $this->returnError($idCollection, 400, "ERROR_GLPI_UPDATE", false);
                } else if ($failed > 0) {
                    $this->returnError($idCollection, 207, "ERROR_GLPI_PARTIAL_UPDATE", false);
                }
            } else {
                if ($failed > 0) {
                    $this->returnError($idCollection[0]['message'], 400, "ERROR_GLPI_UPDATE", false);
                } else {
                    return $idCollection; // Return collection, even if the request affects a single item
                }
            }
            return $idCollection;
        } else {
            $this->messageBadArrayError();
        }
    }


    /**
     * Delete one or more objects in GLPI
     *
     * @param string $itemtype itemtype (class) of object
     * @param array  $params   with those options :
     *    - 'input' : Array of objects with fields of itemtype to be updated.
     *                Mandatory.
     *                You must provide in each object a key named 'id' to identify item to delete.*
     *    - 'force_purge' : boolean, if itemtype have a trashbin, you can force purge (delete finally).
     *                      Optional.
     *    - 'history' : boolean, default true, false to disable saving of deletion in global history.
     *                  Optional.
     *
     * @return boolean|boolean[]|void success status, or void when error response is send in case of error
     */
    protected function deleteItems($itemtype, $params = [])
    {
        $itemtype = $this->handleDepreciation($itemtype);

        $default  = ['force_purge' => false,
            'history'     => true
        ];
        $params   = array_merge($default, $params);
        $input    = $params['input'];
        $item     = new $itemtype();

        if (is_object($input)) {
            $input = [$input];
            $isMultiple = false;
        } else {
            $isMultiple = true;
        }

        if ($this->isDeprecated()) {
            $input = array_map(function ($item) {
                return $this->deprecated_item->mapDeprecatedToCurrentFields($item);
            }, $input);
        }

        if (is_array($input)) {
            $idCollection = [];
            $failed = 0;
            foreach ($input as $object) {
                if (isset($object->id)) {
                    if (!$item->getFromDB($object->id)) {
                        $failed++;
                        $idCollection[] = [$object->id => false, 'message' => __("Item not found")];
                        continue;
                    }

                    // Force purge for templates / may not to be deleted / not dynamic lockable items
                    // see CommonDBTM::delete()
                    // TODO Needs factorization
                    if (
                        $item->isTemplate()
                        || !$item->maybeDeleted()
                        // Do not take into account deleted field if maybe dynamic but not dynamic
                        || ($item->useDeletedToLockIfDynamic()
                        && !$item->isDynamic())
                    ) {
                        $params['force_purge'] = 1;
                    } else {
                        $params['force_purge'] = filter_var($params['force_purge'], FILTER_VALIDATE_BOOLEAN);
                    }

                   //check rights
                    if (
                        ($params['force_purge']
                        && !$item->can($object->id, PURGE))
                        || (!$params['force_purge']
                        && !$item->can($object->id, DELETE))
                    ) {
                          $failed++;
                          $idCollection[] = [
                              $object->id => false,
                              'message' => __("You don't have permission to perform this action.")
                          ];
                    } else {
                   //delete item
                        $delete_return = $item->delete(
                            (array) $object,
                            $params['force_purge'],
                            $params['history']
                        );
                        if ($delete_return === false) {
                             $failed++;
                        }
                        $idCollection[] = [$object->id => $delete_return, 'message' => $this->getGlpiLastMessage()];
                    }
                }
            }
            if ($isMultiple) {
                if ($failed == count($input)) {
                    $this->returnError($idCollection, 400, "ERROR_GLPI_DELETE", false);
                } else if ($failed > 0) {
                    $this->returnError($idCollection, 207, "ERROR_GLPI_PARTIAL_DELETE", false);
                }
            } else {
                if ($failed > 0) {
                    $this->returnError($idCollection[0]['message'], 400, "ERROR_GLPI_DELETE", false);
                } else {
                    return $idCollection; // Return collection, even if the request affects a single item
                }
            }

            return $idCollection;
        } else {
            $this->messageBadArrayError();
        }
    }


    /**
     * Handle "lostPassword" endpoint.
     *
     * @param array $params
     *
     * @return array|void response array, or void when error response is send in case of error
     */
    protected function lostPassword($params = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($CFG_GLPI['use_notifications'] == '0' || $CFG_GLPI['notifications_mailing'] == '0') {
            $this->returnError(__("Email notifications are disabled"));
        }

        if (!isset($params['email']) && !$params['password_forget_token']) {
            $this->returnError(__("email parameter missing"));
        }

        if (isset($_SESSION['glpiID'])) {
            $this->returnError(__("A session is active"));
        }

        $user = new User();
        if (!isset($params['password_forget_token'])) {
            try {
                $user->forgetPassword($params['email']);
            } catch (\Glpi\Exception\ForgetPasswordException $e) {
                $this->returnError($e->getMessage());
            }
            return [
                __('If the given email address match an existing GLPI user, you will receive an email containing the information required to reset your password. Please contact your administrator if you do not receive any email.')
            ];
        } else {
            $password = isset($params['password']) ? $params['password'] : '';
            $input = [
                'password_forget_token'    => $params['password_forget_token'],
                'password'                 => $password,
                'password2'                => $password,
            ];
            try {
                $user->updateForgottenPassword($input);
            } catch (\Glpi\Exception\ForgetPasswordException $e) {
                $this->returnError($e->getMessage());
            } catch (\Glpi\Exception\PasswordTooWeakException $e) {
                implode('\n', $e->getMessages());
                $this->returnError(implode('\n', $e->getMessages()));
            }
            return [__("Reset password successful.")];
        }
    }


    /**
     * Function called by each common function of the API.
     *
     * We need for each of these to :
     *  - checks app_token
     *  - log
     *  - check session token
     *  - unlock session if needed (set ip to read-only to permit concurrent calls)
     *
     * @param boolean $unlock_session do we need to unlock session (default true)
     * @param string  $endpoint       name of the current function (default '')
     *
     * @return void
     */
    protected function initEndpoint($unlock_session = true, $endpoint = "")
    {

        if ($endpoint === "") {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $endpoint = $backtrace[1]['function'];
        }
        $this->checkAppToken();
        $this->logEndpointUsage($endpoint);
        $this->checkSessionToken();
        if ($unlock_session) {
            $this->unlockSessionIfPossible();
        }
    }


    /**
     * Check if the app_toke in case of config ask to
     *
     * @return void
     */
    private function checkAppToken()
    {

       // check app token (if needed)
        if (!isset($this->parameters['app_token'])) {
            $this->parameters['app_token'] = "";
        }
        if (!$this->apiclients_id = array_search($this->parameters['app_token'], $this->app_tokens)) {
            if ($this->parameters['app_token'] != "") {
                $this->returnError(
                    __("parameter app_token seems wrong"),
                    400,
                    "ERROR_WRONG_APP_TOKEN_PARAMETER"
                );
            } else {
                $this->returnError(
                    __("missing parameter app_token"),
                    400,
                    "ERROR_APP_TOKEN_PARAMETERS_MISSING"
                );
            }
        }
    }


    /**
     * Log usage of the api into glpi historical or log files (defined by api config)
     *
     * It stores the ip and the username of the current session.
     *
     * @param string $endpoint function called by api to log (default '')
     *
     * @return void
     */
    private function logEndpointUsage($endpoint = "")
    {

        $username = "";
        if (isset($_SESSION['glpiname'])) {
            $username = "(" . $_SESSION['glpiname'] . ")";
        }

        $apiclient = new APIClient();
        if ($apiclient->getFromDB($this->apiclients_id)) {
            $changes = [
                0,
                "",
                "Enpoint '$endpoint' called by " . $this->iptxt . " $username"
            ];

            switch ($apiclient->fields['dolog_method']) {
                case APIClient::DOLOG_HISTORICAL:
                    Log::history(
                        $this->apiclients_id,
                        'APIClient',
                        $changes,
                        0,
                        Log::HISTORY_LOG_SIMPLE_MESSAGE
                    );
                    break;

                case APIClient::DOLOG_LOGS:
                    Toolbox::logInFile("api", $changes[2] . "\n");
                    break;
            }
        }
    }


    /**
     * Check that the session_token is provided and match to a valid php session
     *
     * @return void
     */
    protected function checkSessionToken()
    {

        if (
            !isset($this->parameters['session_token'])
            || empty($this->parameters['session_token'])
        ) {
            $this->messageSessionTokenMissing();
        }

        $current = session_id();
        if (
            $this->parameters['session_token'] != $current
            && !empty($current)
            || !isset($_SESSION['glpiID'])
        ) {
            $this->messageSessionError();
        }
    }


    /**
     * Unlock the current session (readonly) to permit concurrent call
     *
     * @return void
     */
    private function unlockSessionIfPossible()
    {

        if (!$this->session_write) {
            session_write_close();
        }
    }


    /**
     * Get last message added in $_SESSION by Session::addMessageAfterRedirect
     *
     * @return string Last message
     */
    private function getGlpiLastMessage()
    {
        $all_messages             = [];

        $messages_after_redirect  = [];

        if (
            isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
            && count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0
        ) {
            $messages_after_redirect = $_SESSION["MESSAGE_AFTER_REDIRECT"];
           // Clean messages
            $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
        };

       // clean html
        foreach ($messages_after_redirect as $messages) {
            foreach ($messages as $message) {
                $all_messages[] = Toolbox::stripTags($message);
            }
        }

        if (!end($all_messages)) {
            return '';
        }
        return end($all_messages);
    }


    /**
     * Show API header
     *
     * in debug, it add body and some libs (essentialy to colorise markdown)
     * otherwise, it change only Content-Type of the page
     *
     * @param boolean $html  (default false)
     * @param string  $title (default '')
     *
     * @return void
     */
    protected function header($html = false, $title = "")
    {

       // Send UTF8 Headers
        $content_type = static::$content_type;
        if ($html) {
            $content_type = "text/html";
        }
        header("Content-Type: $content_type; charset=UTF-8");

       // Send extra expires header
        Html::header_nocache();

        if ($html) {
            if (empty($title)) {
                $title = $this->getTypeName();
            }

            Html::includeHeader($title);

           // Body with configured stuff
            echo "<body>";
            echo "<div id='page'>";
        }
    }


    /**
     * Display the API Documentation in Html (parsed from markdown)
     *
     * @param string $file relative path of documentation file
     *
     * @return void
     */
    public function inlineDocumentation($file)
    {
        $this->header(true, __("API Documentation"));

        $documentation = file_get_contents(GLPI_ROOT . '/' . $file);
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class='documentation'>{{ md|raw }}</div>
            <script type="module">
                import('{{ path("js/modules/Monaco/MonacoEditor.js") }}').then(() => {
                    const lang_elements = $('code[class^="language-"]');
                    lang_elements.each((index, element) => {
                        const el = $(element);
                        const code = el.text();
                        let lang = el.attr('class').replace('language-', '');
                        switch (lang) {
                            case 'bash':
                                lang = 'shell';
                                break;
                            case 'json':
                                lang = 'javascript';
                                break;
                        }
                        window.GLPI.Monaco.colorizeText(code, lang).then((html) => {
                            el.html(html);
                        });
                    });
                });
            </script>
TWIG, ['md' => (new MarkdownRenderer())->render($documentation)]);

        Html::nullFooter();
        exit;
    }


    /**
     * Transform array of fields passed in parameter :
     * change value from  integer id to string name of foreign key
     * You can pass an array of array, this method is recursive.
     *
     * @param array   $fields to check and transform
     * @param array   $params array of option to enable, could be :
     *                                 - expand_dropdowns (default false)
     *                                 - get_hateoas      (default true)
     *
     * @return array altered $fields
     */
    protected static function parseDropdowns($fields, $params = [])
    {

       // default params
        $default = ['expand_dropdowns' => false,
            'get_hateoas'      => true
        ];
        $params = array_merge($default, $params);

       // parse fields recursively
        foreach ($fields as $key => &$value) {
            if (is_array($value)) {
                $value = self::parseDropdowns($value, $params);
            }
            if (is_integer($key)) {
                continue;
            }
            if (isForeignKeyField($key)) {
               // specific key transformations
                if ($key == "items_id" && isset($fields['itemtype'])) {
                    $key = getForeignKeyFieldForItemType($fields['itemtype']);
                }
                if (
                    $key == "auths_id"
                    && isset($fields['authtype']) && $fields['authtype'] == Auth::LDAP
                ) {
                    $key = "authldaps_id";
                }
                if ($key == "default_requesttypes_id") {
                    $key = "requesttypes_id";
                }
               // mainitems_id mainitemtype
                if ($key == "mainitems_id" && isset($fields['mainitemtype'])) {
                    $key = getForeignKeyFieldForItemType($fields['mainitemtype']);
                }

                if (
                    is_integer($value)
                    && ($value > 0 || ($key === 'entities_id' && $value >= 0))
                ) {
                    $tablename = getTableNameForForeignKeyField($key);
                    $itemtype = getItemTypeForTable($tablename);

                   // get hateoas
                    if ($params['get_hateoas']) {
                        $fields['links'][] = ['rel'  => $itemtype,
                            'href' => self::$api_url . "/$itemtype/" . $value
                        ];
                    }

                   // expand dropdown
                    if ($params['expand_dropdowns']) {
                        $value = Dropdown::getDropdownName($tablename, $value, false, true, false, '');
                    }
                }
            }
        }
        return $fields;
    }


    /**
     * Retrieve all child class for itemtype parameter
     *
     * @param string $itemtype Item type
     *
     * @return array child classes
     */
    public static function getHatoasClasses($itemtype)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $hclasses = [];
        if (in_array($itemtype, $CFG_GLPI["reservation_types"])) {
            $hclasses[] = "ReservationItem";
        }
        if (in_array($itemtype, $CFG_GLPI["document_types"])) {
            $hclasses[] = "Document_Item";
        }
        if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
            $hclasses[] = "Contract_Item";
        }
        if (in_array($itemtype, $CFG_GLPI["infocom_types"])) {
            $hclasses[] = "Infocom";
        }
        if (in_array($itemtype, $CFG_GLPI["ticket_types"])) {
            $hclasses[] = "Item_Ticket";
        }if (in_array($itemtype, $CFG_GLPI["project_asset_types"])) {
            $hclasses[] = "Item_Project";
        }
        if (in_array($itemtype, $CFG_GLPI["networkport_types"])) {
            $hclasses[] = "NetworkPort";
        }
        if (in_array($itemtype, $CFG_GLPI["itemdevices_types"])) {
           //$hclasses[] = "Item_Devices";
            foreach ($CFG_GLPI['device_types'] as $device_type) {
                if (
                    (($device_type == "DeviceMemory")
                    && !in_array($itemtype, $CFG_GLPI["itemdevicememory_types"]))
                    || (($device_type == "DevicePowerSupply")
                    && !in_array($itemtype, $CFG_GLPI["itemdevicepowersupply_types"]))
                    || (($device_type == "DeviceNetworkCard")
                    && !in_array($itemtype, $CFG_GLPI["itemdevicenetworkcard_types"]))
                ) {
                    continue;
                }
                $hclasses[] = "Item_" . $device_type;
            }
        }

       //specific case
        switch ($itemtype) {
            case 'Ticket':
                $hclasses[] = "TicketTask";
                $hclasses[] = "TicketValidation";
                $hclasses[] = "TicketCost";
                $hclasses[] = "Problem_Ticket";
                $hclasses[] = "Change_Ticket";
                $hclasses[] = 'Ticket_Ticket';
                $hclasses[] = "Item_Ticket";
                $hclasses[] = "ITILSolution";
                $hclasses[] = "ITILFollowup";
                $hclasses[] = "Ticket_User";
                $hclasses[] = "Group_Ticket";
                $hclasses[] = "Supplier_Ticket";
                break;

            case 'Problem':
                $hclasses[] = "ProblemTask";
                $hclasses[] = "ProblemCost";
                $hclasses[] = "Change_Problem";
                $hclasses[] = "Problem_Ticket";
                $hclasses[] = 'Problem_Problem';
                $hclasses[] = "Item_Problem";
                $hclasses[] = "ITILSolution";
                $hclasses[] = "ITILFollowup";
                $hclasses[] = "Problem_User";
                $hclasses[] = "Group_Problem";
                $hclasses[] = "Supplier_Problem";
                break;

            case 'Change':
                $hclasses[] = "ChangeTask";
                $hclasses[] = "ChangeCost";
                $hclasses[] = "Itil_Project";
                $hclasses[] = "Change_Problem";
                $hclasses[] = "Change_Ticket";
                $hclasses[] = 'Change_Change';
                $hclasses[] = "Change_Item";
                $hclasses[] = "ITILSolution";
                $hclasses[] = "ITILFollowup";
                $hclasses[] = "Change_User";
                $hclasses[] = "Group_Change";
                $hclasses[] = "Supplier_Change";
                break;

            case 'Project':
                $hclasses[] = "ProjectTask";
                $hclasses[] = "ProjectCost";
                $hclasses[] = "Itil_Project";
                $hclasses[] = "Item_Project";
                break;
        }

        return $hclasses;
    }


    /**
     * Send 404 error to client
     *
     * @param boolean $return_error (default true)
     *
     * @return void
     */
    public function messageNotfoundError($return_error = true)
    {

        $this->returnError(
            __("Item not found"),
            404,
            "ERROR_ITEM_NOT_FOUND",
            false,
            $return_error
        );
    }


    /**
     * Send 400 error to client
     *
     * @param boolean $return_error (default true)
     *
     * @return void
     */
    public function messageBadArrayError($return_error = true)
    {

        $this->returnError(
            __("input parameter must be an array of objects"),
            400,
            "ERROR_BAD_ARRAY",
            true,
            $return_error
        );
    }


    /**
     * Send 405 error to client
     *
     * @param boolean $return_error (default true)
     *
     * @return void
     */
    public function messageLostError($return_error = true)
    {

        $this->returnError(
            __("Method Not Allowed"),
            405,
            "ERROR_METHOD_NOT_ALLOWED",
            true,
            $return_error
        );
    }


    /**
     * Send 401 error to client
     *
     * @param boolean $return_error (default true)
     *
     * @return void
     */
    public function messageRightError($return_error = true)
    {

        $this->returnError(
            __("You don't have permission to perform this action."),
            403,
            "ERROR_RIGHT_MISSING",
            false,
            $return_error
        );
    }


    /**
     * Session Token KO
     *
     * @param boolean $return_error (default true)
     *
     * @return void
     */
    public function messageSessionError($return_error = true)
    {
        $this->returnError(
            __("session_token seems invalid"),
            401,
            "ERROR_SESSION_TOKEN_INVALID",
            false,
            $return_error
        );
    }


    /**
     * Session Token missing
     *
     * @param boolean $return_error (default true)
     *
     * @return void
     */
    public function messageSessionTokenMissing($return_error = true)
    {

        $this->returnError(
            __("parameter session_token is missing or empty"),
            400,
            "ERROR_SESSION_TOKEN_MISSING",
            true,
            $return_error
        );
    }


    /**
     * Generic function to send a error message and an error code to client
     *
     * @param string  $message         message to send (human readable)(default 'Bad Request')
     * @param integer $httpcode        http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
     *                                      (default 400)
     * @param string  $statuscode      API status (to represent more precisely the current error)
     *                                      (default ERROR)
     * @param boolean $docmessage      if true, add a link to inline document in message
     *                                      (default true)
     * @param boolean $return_response if true, the error will be send to returnResponse function
     *                                      (who may exit after sending data), otherwise,
     *                                      we will return an array with the error
     *                                      (default true)
     *
     * @return array|void
     */
    public function returnError(
        $message = "Bad Request",
        $httpcode = 400,
        $statuscode = "ERROR",
        $docmessage = true,
        $return_response = true
    ) {

        if (empty($httpcode)) {
            $httpcode = 400;
        }
        if (empty($statuscode)) {
            $statuscode = "ERROR";
        }

        if ($docmessage) {
            $message .= "; " . sprintf(
                __("view documentation in your browser at %s"),
                self::$api_url . "/#$statuscode"
            );
        }
        if ($return_response) {
            $this->returnResponse([$statuscode, $message], $httpcode);
        }
        return [$statuscode, $message];
    }


    /**
     * Get the raw HTTP request body
     *
     * @return string
     */
    protected function getHttpBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * Get raw names
     *
     * @since 9.5
     *
     * @param array  $data           A raw from the database
     * @param array  $params         API parameters
     * @param string $self_itemtype  Itemtype the API was called on
     *
     * @return array
     */
    protected function getFriendlyNames(
        array $data,
        array $params,
        string $self_itemtype
    ) {
        $_names = [];

        foreach ($params['add_keys_names'] as $kn_fkey) {
            if ($kn_fkey == "id") {
                // Get friendlyname for current item
                $kn_itemtype = $self_itemtype;
                $kn_id = $data[$kn_itemtype::getIndexName()];
            } else {
                if (!isset($data[$kn_fkey])) {
                    trigger_error(sprintf('Invalid value: "%s" doesn\'t exist.', $kn_fkey), E_USER_WARNING);
                    continue;
                }

               // Get friendlyname for given fkey
                $kn_itemtype = getItemtypeForForeignKeyField($kn_fkey);
                $kn_id = $data[$kn_fkey];
            }

           // Check itemtype is valid
            $kn_item = getItemForItemtype($kn_itemtype);
            if (!$kn_item) {
                trigger_error(sprintf('Invalid itemtype "%s" for fkey "%s".', $kn_itemtype, $kn_fkey), E_USER_WARNING);
                continue;
            }

            $kn_name = $kn_item::getFriendlyNameById($kn_id);
            $_names[$kn_fkey] = $kn_name;
        }

        return $_names;
    }

    /**
     * Get network ports
     *
     * @since 10.0.0
     *
     * @param int    $id         Id of the source item
     * @param string  $itemtype  Type of the source item
     *
     * @return array
     */
    protected function getNetworkPorts(
        int $id,
        string $itemtype
    ): array {
        /** @var \DBmysql $DB */
        global $DB;

        $_networkports = [];

        if (!NetworkEquipment::canView()) {
            $_networkports = $this->arrayRightError();
        } else {
            $networkport_types = NetworkPort::getNetworkPortInstantiations();
            foreach ($networkport_types as $networkport_type) {
                $_networkports[$networkport_type] = [];

                $netport_table = $networkport_type::getTable();
                $netp_iterator = $DB->request([
                    'SELECT'    => [
                        'netp.id AS netport_id',
                        'netp.entities_id',
                        'netp.is_recursive',
                        'netp.logical_number',
                        'netp.name',
                        'netp.mac',
                        'netp.comment',
                        'netp.is_dynamic',
                        'netp_subtable.*'
                    ],
                    'FROM'      => 'glpi_networkports AS netp',
                    'LEFT JOIN' => [
                        "$netport_table AS netp_subtable" => [
                            'ON' => [
                                'netp_subtable'   => 'networkports_id',
                                'netp'            => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'netp.instantiation_type'  => $networkport_type,
                        'netp.items_id'            => $id,
                        'netp.itemtype'            => $itemtype,
                        'netp.is_deleted'          => 0
                    ]
                ]);

                foreach ($netp_iterator as $data) {
                    if (isset($data['netport_id'])) {
                        // append contact
                        $npo = new NetworkPort();
                        $oppositecontactID = $npo->getContact($data['netport_id']) ;
                        if ($oppositecontactID) {
                            $data['networkports_id_opposite'] = $oppositecontactID ;
                        } else {
                            $data['networkports_id_opposite'] = null;
                        }

                        // append network name
                        $concat_expr = QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                'ipadr.id',
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                'ipadr.name',
                            ]),
                            separator: Search::LONGSEP,
                            alias: 'ipadresses'
                        );
                        $netn_iterator = $DB->request([
                            'SELECT'    => [
                                $concat_expr,
                                'netn.id AS networknames_id',
                                'netn.name AS networkname',
                                'netn.fqdns_id',
                                'fqdn.name AS fqdn_name',
                                'fqdn.fqdn'
                            ],
                            'FROM'      => [
                                'glpi_networknames AS netn'
                            ],
                            'LEFT JOIN' => [
                                'glpi_ipaddresses AS ipadr'               => [
                                    'ON' => [
                                        'ipadr'  => 'items_id',
                                        'netn'   => 'id',
                                        [
                                            'AND' => ['ipadr.itemtype' => 'NetworkName']
                                        ]
                                    ]
                                ],
                                'glpi_fqdns AS fqdn'                      => [
                                    'ON' => [
                                        'fqdn'   => 'id',
                                        'netn'   => 'fqdns_id'
                                    ]
                                ],
                                'glpi_ipaddresses_ipnetworks AS ipadnet'  => [
                                    'ON' => [
                                        'ipadnet'   => 'ipaddresses_id',
                                        'ipadr'     => 'id'
                                    ]
                                ],
                                'glpi_ipnetworks AS ipnet'                => [
                                    'ON' => [
                                        'ipnet'     => 'id',
                                        'ipadnet'   => 'ipnetworks_id'
                                    ]
                                ]
                            ],
                            'WHERE'     => [
                                'netn.itemtype'   => 'NetworkPort',
                                'netn.items_id'   => $data['netport_id']
                            ],
                            'GROUPBY'   => [
                                'netn.id',
                                'netn.name',
                                'netn.fqdns_id',
                                'fqdn.name',
                                'fqdn.fqdn'
                            ]
                        ]);

                        if (count($netn_iterator)) {
                               $data_netn = $netn_iterator->current();

                               $raw_ipadresses = explode(Search::LONGSEP, $data_netn['ipadresses']);
                               $ipadresses = [];
                            foreach ($raw_ipadresses as $ipadress) {
                                $ipadress = explode(Search::SHORTSEP, $ipadress);

                               //find ip network attached to these ip
                                $ipnetworks = [];
                                $ipnet_iterator = $DB->request([
                                    'SELECT'       => [
                                        'ipnet.id',
                                        'ipnet.completename',
                                        'ipnet.name',
                                        'ipnet.address',
                                        'ipnet.netmask',
                                        'ipnet.gateway',
                                        'ipnet.ipnetworks_id',
                                        'ipnet.comment'
                                    ],
                                    'FROM'         => 'glpi_ipnetworks AS ipnet',
                                    'INNER JOIN'   => [
                                        'glpi_ipaddresses_ipnetworks AS ipadnet' => [
                                            'ON' => [
                                                'ipadnet'   => 'ipnetworks_id',
                                                'ipnet'     => 'id'
                                            ]
                                        ]
                                    ],
                                    'WHERE'        => [
                                        'ipadnet.ipaddresses_id'  => $ipadress[0]
                                    ]
                                ]);
                                foreach ($ipnet_iterator as $data_ipnet) {
                                              $ipnetworks[] = $data_ipnet;
                                }

                                $ipadresses[] = [
                                    'id'        => $ipadress[0],
                                    'name'      => $ipadress[1],
                                    'IPNetwork' => $ipnetworks
                                ];
                            }

                               $data['NetworkName'] = [
                                   'id'         => $data_netn['networknames_id'],
                                   'name'       => $data_netn['networkname'],
                                   'fqdns_id'   => $data_netn['fqdns_id'],
                                   'FQDN'       => [
                                       'id'   => $data_netn['fqdns_id'],
                                       'name' => $data_netn['fqdn_name'],
                                       'fqdn' => $data_netn['fqdn']
                                   ],
                                   'IPAddress' => $ipadresses
                               ];
                        }
                    }

                     $_networkports[$networkport_type][] = $data;
                }
            }
        }

        return $_networkports;
    }

    /**
     * Send to client the profile picture of the given user
     *
     * @since 9.5
     *
     * @param int|boolean $user_id
     *
     * @return void
     */
    protected function userPicture($user_id)
    {
        // Try to load target user
        $user = new User();
        if (!$user->getFromDB($user_id)) {
            $this->returnError("Bad request: user with id '$user_id' not found");
        }

        if (!empty($user->fields['picture'])) {
           // Send file
            $file = GLPI_PICTURE_DIR . '/' . $user->fields['picture'];
            Toolbox::sendFile($file, $user->fields['picture']);
        } else {
           // No content
            http_response_code(204);
        }
        die;
    }

    /**
     * If the given itemtype is deprecated, replace it by it's current
     * equivalent and keep a reference to the deprecation logic so we can convert
     * the API input and/or output to the exêcted format.
     *
     * @param string  $itemtype
     * @return string The corrected itemtype.
     */
    public function handleDepreciation(string $itemtype): string
    {
        $deprecated = Toolbox::isAPIDeprecated($itemtype);

        if ($deprecated) {
           // Keep a reference to deprecated item
            $class = "Glpi\Api\Deprecated\\$itemtype";
            $this->deprecated_item = new $class();

           // Get correct itemtype
            $itemtype = $this->deprecated_item->getType();
        }

        return $itemtype;
    }

    /**
     * Check if the current call is using a deprecated item
     *
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return $this->deprecated_item !== null;
    }

    /**
     * "getMassiveActions" endpoint.
     * Return possible massive actions for a given item or itemtype.
     *
     * @param string $itemtype    Itemtype for which to show possible massive actions
     * @param int    $id          If >0, will load given item and restrict massive actions to this item
     * @param bool   $is_deleted  Should we show massive action in "deleted" mode ?
     *
     * @return array|void array of massive actions, or void when error response is send in case of error
     */
    protected function getMassiveActions(
        string $itemtype,
        ?int $id = null,
        bool $is_deleted = false
    ) {
        if (is_null($id)) {
           // No id supplied, show massive actions for the given itemtype
            $actions = $this->getMassiveActionsForItemtype(
                $itemtype,
                $is_deleted
            );
        } else {
            $item = new $itemtype();
            if (!$item->getFromDB($id)) {
               // Id was supplied but item can't be loaded -> error
                return $this->returnError(
                    "Failed to load item (itemtype = '$itemtype', id = '$id')",
                    400,
                    "ERROR_ITEM_NOT_FOUND"
                );
            }

           // Id supplied and item was loaded, show massive action for this
           // specific item
            $actions = $this->getMassiveActionsForItem($item);
        }

        if (count($actions) === 0) {
            // An error occurred
            return;
        }

       // Build response array
        $response = [];
        foreach ($actions as $key => $label) {
            $response[] = [
                'key'   => $key,
                'label' => $label,
            ];
        }
        return $response;
    }

    /**
     * Return possible massive actions for a given itemtype.
     *
     * @param string $itemtype    Itemtype for which to show possible massive actions
     * @param bool   $is_deleted  Should we show massive action in "deleted" mode ?
     * @return array
     */
    public function getMassiveActionsForItemtype(
        string $itemtype,
        bool $is_deleted = false
    ): array {
        // Return massive actions for a given itemtype
        $actions = MassiveAction::getAllMassiveActions($itemtype, $is_deleted);
        if ($actions === false) {
            $this->returnError(
                "Unable to get massive actions for itemtype '$itemtype'. Please check that it is a valid itemtype.",
                400,
                "ERROR_MASSIVEACTION_NOT_FOUND"
            );
            return [];
        }
        return $actions;
    }

    /**
     * Return possible massive actions for a given item.
     *
     * @param CommonDBTM $item    Item for which to show possible massive actions
     * @return array
     */
    public function getMassiveActionsForItem(CommonDBTM $item): array
    {
       // Return massive actions for a given item
        $actions = MassiveAction::getAllMassiveActions(
            $item::getType(),
            $item->isDeleted(),
            $item,
            $item->getID()
        );
        if ($actions === false) {
            $this->returnError(
                "Unable to get massive actions for item of type '{$item::getType()}'. Please check that it is a valid itemtype.",
                400,
                "ERROR_MASSIVEACTION_NOT_FOUND"
            );
            return [];
        }
        return $actions;
    }

    /**
     * "getMassiveActionParameters" endpoint.
     * Return required parameters for a given massive action key.
     *
     * @param string        $itemtype      Target itemtype
     * @param string|null   $action_key    Target massive action
     * @param bool          $is_deleted    Is this massive action to be used on items in the trashbin ?
     *
     * @return array|void array of massive actions parameters, or void when error response is send in case of error
     */
    protected function getMassiveActionParameters(
        string $itemtype,
        ?string $action_key,
        bool $is_deleted
    ) {
        if (is_null($action_key)) {
            return $this->returnError(
                "Missing action key, run 'getMassiveActions' endpoint to see available keys",
                400,
                "ERROR_MASSIVEACTION_KEY"
            );
        }

        $action = explode(':', $action_key);
        if (($action[1] ?? "") == 'update') {
           // Specific case, update form call "exit" function so we don't want to run the actual code
            return [];
        }

        $actions = MassiveAction::getAllMassiveActions($itemtype, $is_deleted);
        if ($actions === false) {
            $this->returnError(
                "Unable to get massive actions for itemtype '$itemtype'. Please check that it is a valid itemtype.",
                400,
                "ERROR_MASSIVEACTION_NOT_FOUND"
            );
            return;
        }

        if (!isset($actions[$action_key])) {
            $this->returnError(
                "Invalid action key parameter, run 'getMassiveActions' endpoint to see available keys",
                400,
                "ERROR_MASSIVEACTION_KEY"
            );
        }

        // Get massive action for the given key
        $ma = new MassiveAction([
            'action'     => $action_key,
            'actions'    => $actions,
            'items'      => [],
            'is_deleted' => $is_deleted
        ], [], 'specialize');

       // Capture form display
        ob_start();
        $ma->showSubForm();
        $html = ob_get_clean();

       // Parse html to find all non hidden inputs, textareas and select
        $inputs = [];
        $crawler = new Crawler($html);
        $crawler->filterXPath('//input')->each(function (Crawler $node, $i) use (&$inputs) {
            if ($node->attr('type') != "hidden") {
                  $inputs[] = [
                      'name' => $node->attr('name'),
                      'type' => $node->attr('type'),
                  ];
            }
        });
        $crawler->filterXPath('//select')->each(function (Crawler $node, $i) use (&$inputs) {
            $type = 'select';
            if (str_starts_with($node->attr('id'), 'dropdown_')) {
                  $type = 'dropdown';
            }
            $inputs[] = [
                'name' => $node->attr('name'),
                'type' => $type,
            ];
        });
        $crawler->filterXPath('//textarea')->each(function (Crawler $node, $i) use (&$inputs) {
            $inputs[] = [
                'name' => $node->attr('name'),
                'type' => 'text',
            ];
        });

        return $inputs;
    }


    /**
     * Handle "applyMassiveAction" endpoint and send response to client.
     * Execute the given massive action
     *
     * @param string        $itemtype      Target itemtype
     * @param string|null   $action_key    Target massive action
     * @param array         $ids           Ids of items to execute the action on
     * @param array         $params        Action parameters
     * @return void
     */
    protected function applyMassiveAction(
        string $itemtype,
        ?string $action_key,
        array $ids,
        array $params
    ) {
        if (is_null($action_key)) {
            $this->returnError(
                "Missing action key, run 'getMassiveActions' endpoint to see available keys",
                400,
                "ERROR_MASSIVEACTION_KEY"
            );
        }

       // Get processor
        $action = explode(':', $action_key);
        $processor = $action[0];

        $ma = new MassiveAction([
            'action'      => $action[1],
            'action_name' => $action_key,
            'items'       => [$itemtype => $ids],
            'processor'   => $processor,
        ] + $params, [], 'process');

        $results = $ma->process();
        unset($results['redirect']);

        if ($results['ok'] == 0 && $results['noaction'] == 0 && $results['ko'] == 0 && $results['noright'] == 0) {
           // No items were processed, invalid action key -> 400
            return $this->returnError(
                "Invalid action key parameter, run 'getMassiveActions' endpoint to see available keys",
                400,
                "ERROR_MASSIVEACTION_KEY"
            );
        }

        if ($results['ok'] > 0 && $results['ko'] == 0) {
           // Success -> 200
            $code = 200;
        } else if ($results['ko'] > 0 && $results['ok'] > 0) {
           // Failure AND success -> 207
            $code = 207;
        } else {
           // Failure -> 422
            $code = 422;
        }

        $this->returnResponse($results, $code);
    }

    /**
     * List of API ressources for which a valid session isn't required
     *
     * @return array
     */
    protected function getRessourcesAllowedWithoutSession(): array
    {
        return [
            "initSession",
            "lostPassword",
        ];
    }

    /**
     * List of API ressources that may write php session data
     *
     * @return array
     */
    protected function getRessourcesWithSessionWrite(): array
    {
        return [
            "initSession",
            "killSession",
            "changeActiveEntities",
            "changeActiveProfile",
        ];
    }
}
