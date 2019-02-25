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
 * @since 9.1
 */

use Glpi\Exception\ForgetPasswordException;
use Glpi\Exception\PasswordTooWeakException;

abstract class API extends CommonGLPI {

   // permit writing to $_SESSION
   protected $session_write = false;

   static $api_url = "";
   static $content_type = "application/json";
   protected $format;
   protected $iptxt         = "";
   protected $ipnum         = "";
   protected $app_tokens    = [];
   protected $apiclients_id = 0;

   /**
    * First function used on api call
    * Parse sended query/parameters and call the corresponding API::method
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
    * Generic messages
    *
    * @param mixed   $response          string message or array of data to send
    * @param integer $code              http code
    * @param array   $additionalheaders headers to send with http response (must be an array(key => value))
    *
    * @return void
    */
   abstract protected function returnResponse($response, $code, $additionalheaders);

   /**
    * Upload and validate files from request and append to $this->parameters['input']
    *
    * @return void
    */
   abstract protected function manageUploadedFiles();

   /**
    * Constructor
    *
    * @var array $CFG_GLPI
    * @var DBmysql $DB
    *
    * @return void
    */
   public function initApi() {
      global $CFG_GLPI, $DB;

      // Load GLPI configuration
      include_once (GLPI_ROOT . '/inc/includes.php');
      $variables = get_defined_vars();
      foreach ($variables as $var => $value) {
         if ($var === strtoupper($var)) {
            $GLOBALS[$var] = $value;
         }
      }

      // construct api url
      self::$api_url = trim($CFG_GLPI['url_base_api'], "/");

      // Don't display error in result
      set_error_handler(['Toolbox', 'userErrorHandlerNormal']);
      ini_set('display_errors', 'Off');

      // Avoid keeping messages between api calls
      $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];

      // check if api is enabled
      if (!$CFG_GLPI['enable_api']) {
         $this->returnError(__("API disabled"), "", "", false);
         exit;
      }

      // retrieve ip of client
      $this->iptxt = Toolbox::getRemoteIpAddress();
      $this->ipnum = (strstr($this->iptxt, ':')===false ? ip2long($this->iptxt) : '');

      // check ip access
      $apiclient = new APIClient;
      $where_ip = "";
      if ($this->ipnum) {
         $where_ip .= " AND (`ipv4_range_start` IS NULL
                             OR (`ipv4_range_start` <= '$this->ipnum'
                                 AND `ipv4_range_end` >= '$this->ipnum'))";
      } else {
         $where_ip .= " AND (`ipv6` IS NULL
                             OR `ipv6` = '".$DB->escape($this->iptxt)."')";
      }
      $found_clients = $apiclient->find("`is_active` = 1 $where_ip");
      if (count($found_clients) <= 0) {
         $this->returnError(__("There isn't an active API client matching your IP address in the configuration").
                            " (".$this->iptxt.")",
                            "", "ERROR_NOT_ALLOWED_IP", false);
      }
      $app_tokens = array_column($found_clients, 'app_token');
      $apiclients_id = array_column($found_clients, 'id');
      $this->app_tokens = array_combine($apiclients_id, $app_tokens);
   }

   /**
    * Set headers according to cross origin ressource sharing
    *
    * @param string $verb Http verb (GET, POST, PUT, DELETE, OPTIONS)
    *
    * @return void
    */
   protected function cors($verb = 'GET') {
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
            header("Access-Control-Allow-Headers: ".
                   "origin, content-type, accept, session-token, authorization");
         }
         exit(0);
      }
   }


   /**
    * Init GLPI Session
    *
    * @param array $params array with theses options :
    *    - a couple 'name' & 'password' : 2 parameters to login with user authentication
    *         OR
    *    - an 'user_token' defined in User Configuration
    *
    * @return array with session_token
    */
   protected function initSession($params = []) {
      global $CFG_GLPI;

      $this->checkAppToken();
      $this->logEndpointUsage(__FUNCTION__);

      if ((!isset($params['login'])
           || empty($params['login'])
           || !isset($params['password'])
           || empty($params['password']))
         && (!isset($params['user_token'])
             || empty($params['user_token']))) {
         $this->returnError(__("parameter(s) login, password or user_token are missing"), 400,
                            "ERROR_LOGIN_PARAMETERS_MISSING");
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
         $this->returnError(__("usage of initSession resource with credentials is disabled"), 400,
                            "ERROR_LOGIN_WITH_CREDENTIALS_DISABLED", false);
      }

      if (!isset($params['auth'])) {
         $params['auth'] = '';
      }

      // login on glpi
      if (!$auth->login($params['login'], $params['password'], $noAuto, false, $params['auth'])) {
         $err = Html::clean($auth->getErr());
         if (isset($params['user_token'])
             && !empty($params['user_token'])) {
            return $this->returnError(__("parameter user_token seems invalid"), 401, "ERROR_GLPI_LOGIN_USER_TOKEN", false);
         }
         return $this->returnError($err, 401, "ERROR_GLPI_LOGIN", false);
      }

      // stop session and return session key
      session_write_close();
      return ['session_token' => $_SESSION['valid_id']];
   }


   /**
    * Kill GLPI Session
    * Use 'session_token' param in $this->parameters
    *
    * @return boolean
    */
   protected function killSession() {

      $this->initEndpoint(false, __FUNCTION__);
      return Session::destroy();
   }


   /**
    * Retrieve GLPI Session initialised by initSession function
    * Use 'session_token' param in $this->parameters
    *
    * @return void
    */
   protected function retrieveSession() {

      if (isset($this->parameters['session_token'])
          && !empty($this->parameters['session_token'])) {
         $current = session_id();
         $session = trim($this->parameters['session_token']);

         if (file_exists(GLPI_ROOT . '/inc/downstream.php')) {
            include_once (GLPI_ROOT . '/inc/downstream.php');
         }

         if ($session!=$current && !empty($current)) {
            session_destroy();
         }
         if ($session!=$current && !empty($session)) {
            session_id($session);
         }
      }
   }


   /**
    * Change active entity to the entities_id one.
    *
    * @param array $params array with theses options :
    *   - 'entities_id': (default 'all') ID of the new active entity ("all" = load all possible entities). Optionnal
    *   - 'is_recursive': (default false) Also display sub entities of the active entity.  Optionnal
    *
    * @return array|bool
    */
   protected function changeActiveEntities($params = []) {

      $this->initEndpoint();

      if (!isset($params['entities_id'])) {
         $entities_id = 'all';
      } else {
         $entities_id = intval($params['entities_id']);
      }

      if (!isset($params['is_recursive'])) {
         $params['is_recursive'] = false;
      } else if (!is_bool($params['is_recursive'])) {
         return $this->returnError();
      }

      if (!Session::changeActiveEntities($entities_id, $params['is_recursive'])) {
         return $this->returnError();
      }

      return true;
   }


   /**
    * Return all the possible entity of the current logged user (and for current active profile)
    *
    * @param array $params array with theses options :
    *   - 'is_recursive': (default false) Also display sub entities of the active entity. Optionnal
    *
    * @return array of entities (with id and name)
    */
   protected function getMyEntities($params = []) {

      $this->initEndpoint();

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
                                   'name' => Dropdown::getDropdownName("glpi_entities",
                                                                       $entity_id)];
               }
            }
         }
         $myentities[] = ['id' => $entity['id'],
                          'name' => Dropdown::getDropdownName("glpi_entities",
                                                                   $entity['id'])];
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
   protected function getActiveEntities() {

      $this->initEndpoint();

      $actives_entities = [];
      foreach (array_values($_SESSION['glpiactiveentities']) as $active_entity) {
         $actives_entities[] = ['id' => $active_entity];
      }

      return ["active_entity" => [
                     "id"                      => $_SESSION['glpiactive_entity'],
                     "active_entity_recursive" => $_SESSION['glpiactive_entity_recursive'],
                     "active_entities"         => $actives_entities]];

   }




   /**
    * set a profile to active
    *
    * @param array $params with theses options :
    *    - profiles_id : identifier of profile to set
    *
    * @return boolean
    */
   protected function changeActiveProfile($params = []) {

      $this->initEndpoint();

      if (!isset($params['profiles_id'])) {
         $this->returnError();
      }

      $profiles_id = intval($params['profiles_id']);
      if (isset($_SESSION['glpiprofiles'][$profiles_id])) {
         return Session::changeProfile($profiles_id);
      }

      $this->messageNotfoundError();
   }




   /**
    * Return all the profiles associated to logged user
    *
    * @return array of profiles (with associated rights)
    */
   protected function getMyProfiles() {

      $this->initEndpoint();

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
   protected function getActiveProfile() {

      $this->initEndpoint();
      return ["active_profile" => $_SESSION['glpiactiveprofile']];
   }




   /**
    * Return the current php $_SESSION
    *
    * @return array
    */
   protected function getFullSession() {

      $this->initEndpoint();
      return ['session' => $_SESSION];
   }



   /**
    * Return the current $CFG_GLPI
    *
    * @return array
     */
   protected function getGlpiConfig() {

      global $CFG_GLPI;
      $this->initEndpoint();

      $excludedKeys = array_flip(Config::$undisclosedFields);
      return ['cfg_glpi' => array_diff_key($CFG_GLPI, $excludedKeys)];
   }



   /**
    * Return the instance fields of itemtype identified by id
    *
    * @param string  $itemtype itemtype (class) of object
    * @param integer $id       identifier of object
    * @param array   $params   with theses options :
    *    - 'expand_dropdowns': Show dropdown's names instead of id. default: false. Optionnal
    *    - 'get_hateoas':      Show relation of current item in a links attribute. default: true. Optionnal
    *    - 'get_sha1':         Get a sha1 signature instead of the full answer. default: false. Optionnal
    *    - 'with_devices':  Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], Optionnal.
    *    - 'with_disks':       Only for Computer, retrieve the associated filesystems. Optionnal.
    *    - 'with_softwares':   Only for Computer, retrieve the associated softwares installations. Optionnal.
    *    - 'with_connections': Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optionnal.
    *    - 'with_networkports':Retrieve all network connections and advanced network informations. Optionnal.
    *    - 'with_infocoms':    Retrieve financial and administrative informations. Optionnal.
    *    - 'with_contracts':   Retrieve associated contracts. Optionnal.
    *    - 'with_documents':   Retrieve associated external documents. Optionnal.
    *    - 'with_tickets':     Retrieve associated itil tickets. Optionnal.
    *    - 'with_problems':    Retrieve associated itil problems. Optionnal.
    *    - 'with_changes':     Retrieve associated itil changes. Optionnal.
    *    - 'with_notes':       Retrieve Notes (if exists, not all itemtypes have notes). Optionnal.
    *    - 'with_logs':        Retrieve historical. Optionnal.
    *
    * @return array    fields of found object
    */
   protected function getItem($itemtype, $id, $params = []) {
      global $CFG_GLPI, $DB;

      $this->initEndpoint();

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
                       'with_logs'         => false];
      $params = array_merge($default, $params);

      $item = new $itemtype;
      if (!$item->getFromDB($id)) {
         return $this->messageNotfoundError();
      }
      if (!$item->can($id, READ)) {
         return $this->messageRightError();
      }

      $fields = $item->fields;

      // avoid disclosure of critical fields
      $item::unsetUndisclosedFields($fields);

      // retrieve devices
      if (isset($params['with_devices'])
          && $params['with_devices']
          && in_array($itemtype, Item_Devices::getConcernedItems())) {
         $all_devices = [];
         foreach (Item_Devices::getItemAffinities($item->getType()) as $device_type) {
            $found_devices = getAllDatasFromTable(
               $device_type::getTable(), [
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
      if (isset($params['with_disks'])
          && $params['with_disks']
          && in_array($itemtype, $CFG_GLPI['itemdeviceharddrive_types'])) {
         // build query to retrive filesystems
         $query = "SELECT `glpi_filesystems`.`name` AS fsname,
                          `glpi_items_disks`.*
                   FROM `glpi_items_disks`
                   LEFT JOIN `glpi_filesystems`
                             ON (`glpi_items_disks`.`filesystems_id` = `glpi_filesystems`.`id`)
                   WHERE `items_id` = '$id'
                         AND `itemtype` = '$itemtype'
                         AND `is_deleted` = 0";
         $fields['_disks'] = [];
         if ($result = $DB->query($query)) {
            while ($data = $DB->fetch_assoc($result)) {
               unset($data['items_id']);
               unset($data['is_deleted']);
               $fields['_disks'][] = ['name' => $data];
            }
         }
      }

      // retrieve computer softwares
      if (isset($params['with_softwares'])
          && $params['with_softwares']
          && $itemtype == "Computer") {
         $fields['_softwares'] = [];
         if (!Software::canView()) {
            $fields['_softwares'] = self::arrayRightError();
         } else {
            $query = "SELECT `glpi_softwares`.`softwarecategories_id`,
                             `glpi_softwares`.`id` AS softwares_id,
                             `glpi_softwareversions`.`id` AS softwareversions_id,
                             `glpi_computers_softwareversions`.`is_dynamic`,
                             `glpi_softwareversions`.`states_id`,
                             `glpi_softwares`.`is_valid`
                      FROM `glpi_computers_softwareversions`
                      LEFT JOIN `glpi_softwareversions`
                           ON (`glpi_computers_softwareversions`.`softwareversions_id`
                                 = `glpi_softwareversions`.`id`)
                      LEFT JOIN `glpi_softwares`
                           ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
                      WHERE `glpi_computers_softwareversions`.`computers_id` = '$id'
                            AND `glpi_computers_softwareversions`.`is_deleted` = 0
                      ORDER BY `glpi_softwares`.`name`, `glpi_softwareversions`.`name`";
            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $fields['_softwares'][] = $data;
               }
            }
         }
      }

      // retrieve item connections
      if (isset($params['with_connections'])
          && $params['with_connections']
          && $itemtype == "Computer") {
         $fields['_connections'] = [];
         foreach ($CFG_GLPI["directconnect_types"] as $connect_type) {
            $connect_item = new $connect_type();
            if ($connect_item->canView()) {
               $query = "SELECT `glpi_computers_items`.`id` AS assoc_id,
                         `glpi_computers_items`.`computers_id` AS assoc_computers_id,
                         `glpi_computers_items`.`itemtype` AS assoc_itemtype,
                         `glpi_computers_items`.`items_id` AS assoc_items_id,
                         `glpi_computers_items`.`is_dynamic` AS assoc_is_dynamic,
                         ".getTableForItemType($connect_type).".*
                         FROM `glpi_computers_items`
                         LEFT JOIN `".getTableForItemType($connect_type)."`
                           ON (`".getTableForItemType($connect_type)."`.`id`
                                 = `glpi_computers_items`.`items_id`)
                         WHERE `computers_id` = '$id'
                               AND `itemtype` = '".$connect_type."'
                               AND `glpi_computers_items`.`is_deleted` = 0";
               if ($result = $DB->query($query)) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $fields['_connections'][$connect_type][] = $data;
                  }
               }
            }
         }
      }

      // retrieve item networkports
      if (isset($params['with_networkports'])
          && $params['with_networkports']) {
         $fields['_networkports'] = [];
         if (!NetworkEquipment::canView()) {
            $fields['_networkports'] = self::arrayRightError();
         } else {
            foreach (NetworkPort::getNetworkPortInstantiations() as $networkport_type) {
               $netport_table = $networkport_type::getTable();
               $query = "SELECT
                           netp.`id` as netport_id,
                           netp.`entities_id`,
                           netp.`is_recursive`,
                           netp.`logical_number`,
                           netp.`name`,
                           netp.`mac`,
                           netp.`comment`,
                           netp.`is_dynamic`,
                           netp_subtable.*
                         FROM glpi_networkports AS netp
                         LEFT JOIN `$netport_table` AS netp_subtable
                           ON netp_subtable.`networkports_id` = netp.`id`
                         WHERE netp.`instantiation_type` = '$networkport_type'
                           AND netp.`items_id` = '$id'
                           AND netp.`itemtype` = '$itemtype'
                           AND netp.`is_deleted` = 0";
               if ($result = $DB->query($query)) {
                  while ($data = $DB->fetch_assoc($result)) {
                     if (isset($data['netport_id'])) {
                        // append network name
                        $query_netn = "SELECT
                              GROUP_CONCAT(CONCAT(ipadr.`id`, '".Search::SHORTSEP."' , ipadr.`name`)
                                           SEPARATOR '".Search::LONGSEP."') as ipadresses,
                              netn.`id` as networknames_id,
                              netn.`name` as networkname,
                              netn.`fqdns_id`,
                              fqdn.`name` as fqdn_name,
                              fqdn.`fqdn`
                           FROM `glpi_networknames` AS netn
                           LEFT JOIN `glpi_ipaddresses` AS ipadr
                              ON ipadr.`itemtype` = 'NetworkName' AND ipadr.`items_id` = netn.`id`
                           LEFT JOIN `glpi_fqdns` AS fqdn
                              ON fqdn.`id` = netn.`fqdns_id`
                           LEFT JOIN `glpi_ipaddresses_ipnetworks` ipadnet
                              ON ipadnet.`ipaddresses_id` = ipadr.`id`
                           LEFT JOIN `glpi_ipnetworks` `ipnet`
                              ON ipnet.`id` = ipadnet.`ipnetworks_id`
                           WHERE netn.`itemtype` = 'NetworkPort'
                             AND netn.`items_id` = ".$data['netport_id']."
                           GROUP BY netn.`id`, netn.`name`, netn.fqdns_id, fqdn.name, fqdn.fqdn";
                        if ($result_netn = $DB->query($query_netn)) {
                           $data_netn = $DB->fetch_assoc($result_netn);

                           $raw_ipadresses = explode(Search::LONGSEP, $data_netn['ipadresses']);
                           $ipadresses = [];
                           foreach ($raw_ipadresses as $ipadress) {
                              $ipadress = explode(Search::SHORTSEP, $ipadress);

                              //find ip network attached to these ip
                              $ipnetworks = [];
                              $query_ipnet = "SELECT
                                    ipnet.`id`,
                                    ipnet.`completename`,
                                    ipnet.`name`,
                                    ipnet.`address`,
                                    ipnet.`netmask`,
                                    ipnet.`gateway`,
                                    ipnet.`ipnetworks_id`,
                                    ipnet.`comment`
                                 FROM `glpi_ipnetworks` ipnet
                                 INNER JOIN `glpi_ipaddresses_ipnetworks` ipadnet
                                    ON ipnet.`id` = ipadnet.`ipnetworks_id`
                                    AND ipadnet.`ipaddresses_id` = ".$ipadress[0];
                              if ($result_ipnet = $DB->query($query_ipnet)) {
                                 while ($data_ipnet = $DB->fetch_assoc($result_ipnet)) {
                                    $ipnetworks[] = $data_ipnet;
                                 }
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

                     $fields['_networkports'][$networkport_type][] = $data;
                  }
               }
            }
         }
      }

      // retrieve item infocoms
      if (isset($params['with_infocoms'])
          && $params['with_infocoms']) {
         $fields['_infocoms'] = [];
         if (!Infocom::canView()) {
            $fields['_infocoms'] = self::arrayRightError();
         } else {
            $ic = new Infocom();
            if ($ic->getFromDBforDevice($itemtype, $id)) {
               $fields['_infocoms'] = $ic->fields;
            }
         }
      }

      // retrieve item contracts
      if (isset($params['with_contracts'])
          && $params['with_contracts']) {
         $fields['_contracts'] = [];
         if (!Contract::canView()) {
            $fields['_contracts'] = self::arrayRightError();
         } else {
            $query = "SELECT `glpi_contracts_items`.*
                     FROM `glpi_contracts_items`,
                          `glpi_contracts`
                     LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id`=`glpi_entities`.`id`)
                     WHERE `glpi_contracts`.`id`=`glpi_contracts_items`.`contracts_id`
                           AND `glpi_contracts_items`.`items_id` = '$id'
                           AND `glpi_contracts_items`.`itemtype` = '$itemtype'".
                           getEntitiesRestrictRequest(" AND", "glpi_contracts", '', '', true)."
                     ORDER BY `glpi_contracts`.`name`";
            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $fields['_contracts'][] = $data;
               }
            }
         }
      }

      // retrieve item contracts
      if (isset($params['with_documents'])
          && $params['with_documents']) {
         $fields['_documents'] = [];
         if (!$itemtype != 'Ticket'
             && $itemtype != 'KnowbaseItem'
             && $itemtype != 'Reminder'
             && !Document::canView()) {
            $fields['_documents'] = self::arrayRightError();
         } else {
            $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                             `glpi_documents_items`.`date_mod` AS assocdate,
                             `glpi_entities`.`id` AS entityID,
                             `glpi_entities`.`completename` AS entity,
                             `glpi_documentcategories`.`completename` AS headings,
                             `glpi_documents`.*
                      FROM `glpi_documents_items`
                      LEFT JOIN `glpi_documents`
                                ON (`glpi_documents_items`.`documents_id`=`glpi_documents`.`id`)
                      LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                      LEFT JOIN `glpi_documentcategories`
                              ON (`glpi_documents`.`documentcategories_id`=`glpi_documentcategories`.`id`)
                      WHERE `glpi_documents_items`.`items_id` = '$id'
                            AND `glpi_documents_items`.`itemtype` = '$itemtype' ";
            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $fields['_documents'][] = $data;
               }
            }
         }
      }

      // retrieve item tickets
      if (isset($params['with_tickets'])
          && $params['with_tickets']) {
         $fields['_tickets'] = [];
         if (!Ticket::canView()) {
            $fields['_tickets'] = self::arrayRightError();
         } else {
            $query = "SELECT ".Ticket::getCommonSelect()."
                      FROM `glpi_tickets` ".
                      Ticket::getCommonLeftJoin()."
                      WHERE `glpi_items_tickets`.`items_id` = '$id'
                             AND `glpi_items_tickets`.`itemtype` = '$itemtype' ".
                            getEntitiesRestrictRequest("AND", "glpi_tickets")."
                      ORDER BY `glpi_tickets`.`date_mod` DESC";
            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $fields['_tickets'][] = $data;
               }
            }
         }
      }

      // retrieve item problems
      if (isset($params['with_problems'])
          && $params['with_problems']) {
         $fields['_problems'] = [];
         if (!Problem::canView()) {
            $fields['_problems'] = self::arrayRightError();
         } else {
            $query = "SELECT ".Problem::getCommonSelect()."
                            FROM `glpi_problems`
                            LEFT JOIN `glpi_items_problems`
                              ON (`glpi_problems`.`id` = `glpi_items_problems`.`problems_id`) ".
                            Problem::getCommonLeftJoin()."
                            WHERE `items_id` = '$id'
                                  AND `itemtype` = '$itemtype' ".
                                  getEntitiesRestrictRequest("AND", "glpi_problems")."
                            ORDER BY `glpi_problems`.`date_mod` DESC";
            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $fields['_problems'][] = $data;
               }
            }
         }
      }

      // retrieve item changes
      if (isset($params['with_changes'])
          && $params['with_changes']) {
         $fields['_changes'] = [];
         if (!Change::canView()) {
            $fields['_changes'] = self::arrayRightError();
         } else {
            $query = "SELECT ".Change::getCommonSelect()."
                            FROM `glpi_changes`
                            LEFT JOIN `glpi_changes_items`
                              ON (`glpi_changes`.`id` = `glpi_changes_items`.`problems_id`) ".
                            Change::getCommonLeftJoin()."
                            WHERE `items_id` = '$id'
                                  AND `itemtype` = '$itemtype' ".
                                  getEntitiesRestrictRequest("AND", "glpi_changes")."
                            ORDER BY `glpi_changes`.`date_mod` DESC";
            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $fields['_changes'][] = $data;
               }
            }
         }
      }

      // retrieve item notes
      if (isset($params['with_notes'])
          && $params['with_notes']) {
         $fields['_notes'] = [];
         if (!Session::haveRight($itemtype::$rightname, READNOTE)) {
            $fields['_notes'] = self::arrayRightError();
         } else {
            $fields['_notes'] = Notepad::getAllForItem($item);
         }
      }

      // retrieve item logs
      if (isset($params['with_logs'])
          && $params['with_logs']) {
         $fields['_logs'] = [];
         if (!Session::haveRight($itemtype::$rightname, READNOTE)) {
            $fields['_logs'] = self::arrayRightError();
         } else {
            $fields['_logs'] = getAllDatasFromTable(
               "glpi_logs", [
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
                                       'href' => self::$api_url."/$itemtype/".$item->getID()."/$hclass/"];
         }
      }

      // get sha1 footprint if needed
      if ($params['get_sha1']) {
         $fields = sha1(json_encode($fields, JSON_UNESCAPED_UNICODE
                                             | JSON_UNESCAPED_SLASHES
                                             | JSON_NUMERIC_CHECK));
      }

      return $fields;
   }



   /**
    * Fill a sub array with a right error
    *
    * @return array
    */
   protected function arrayRightError() {

      return ['error'   => 401,
                   'message' => __("You don't have permission to perform this action.")];
   }





   /**
    * Return a collection of rows of the desired itemtype
    *
    * @param string  $itemtype   itemtype (class) of object
    * @param array   $params     with theses options :
    * - 'expand_dropdowns' (default: false): show dropdown's names instead of id. Optionnal
    * - 'get_hateoas'      (default: true): show relations of items in a links attribute. Optionnal
    * - 'only_id'          (default: false): keep only id in fields list. Optionnal
    * - 'range'            (default: 0-50): limit the list to start-end attributes
    * - 'sort'             (default: id): sort by the field.
    * - 'order'            (default: ASC): ASC(ending) or DESC(ending).
    * - 'searchText'       (default: NULL): array of filters to pass on the query (with key = field and value the search)
    * - 'is_deleted'       (default: false): show trashbin. Optionnal
    * @param integer $totalcount output parameter who receive the total count of the query resulat.
    *                            As this function paginate results (with a mysql LIMIT),
    *                            we can have the full range. (default 0)
    *
    * @return array collection of fields
    */
   protected function getItems($itemtype, $params = [], &$totalcount = 0) {
      global $DB;

      $this->initEndpoint();

      // default params
      $default = ['expand_dropdowns' => false,
                       'get_hateoas'      => true,
                       'only_id'          => false,
                       'range'            => "0-".$_SESSION['glpilist_limit'],
                       'sort'             => "id",
                       'order'            => "ASC",
                       'searchText'       => null,
                       'is_deleted'       => false];
      $params = array_merge($default, $params);

      if (!$itemtype::canView()) {
         return $this->messageRightError();
      }

      $found = [];
      $item = new $itemtype();
      $item->getEmpty();
      $table = getTableForItemType($itemtype);

      // transform range parameter in start and limit variables
      if (isset($params['range']) > 0) {
         if (preg_match("/^[0-9]+-[0-9]+\$/", $params['range'])) {
            $range = explode("-", $params['range']);
            $params['start']      = $range[0];
            $params['list_limit'] = $range[1]-$range[0]+1;
            $params['range']      = $range;
         } else {
            $this->returnError("range must be in format : [start-end] with integers");
         }
      } else {
         $params['range'] = [0, $_SESSION['glpilist_limit']];
      }

      // check parameters
      if (isset($params['order'])
          && !in_array(strtoupper($params['order']), ['DESC', 'ASC'])) {
         $this->returnError("order must be DESC or ASC");
      }
      if (!isset($item->fields[$params['sort']])) {
         $this->returnError("sort param is not a field of $table");
      }

      //specific case for restriction
      $already_linked_table = [];
      $join = Search::addDefaultJoin($itemtype, $table, $already_linked_table);
      $where = Search::addDefaultWhere($itemtype);
      if ($where == '') {
         $where = "1=1 ";
      }
      if ($item->maybeDeleted()) {
         $where.= "AND `$table`.`is_deleted` = ".intval($params['is_deleted']);
      }

      // add filter for a parent itemtype
      if (isset($this->parameters['parent_itemtype'])
          && isset($this->parameters['parent_id'])) {

         // check parent itemtype
         if (!class_exists($this->parameters['parent_itemtype'])
             || !is_subclass_of($this->parameters['parent_itemtype'], 'CommonDBTM')) {
            $this->returnError(__("parent itemtype not found or not an instance of CommonDBTM"),
                               400,
                               "ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM");
         }

         $fk_parent = getForeignKeyFieldForItemType($this->parameters['parent_itemtype']);
         $fk_child = getForeignKeyFieldForItemType($itemtype);

         // check parent rights
         $parent_item = new $this->parameters['parent_itemtype'];
         if (!$parent_item->getFromDB($this->parameters['parent_id'])) {
            return $this->messageNotfoundError();
         }
         if (!$parent_item->can($this->parameters['parent_id'], READ)) {
            return $this->messageRightError();
         }

         // filter with parents fields
         if (isset($item->fields[$fk_parent])) {
            $where.= " AND `$table`.`$fk_parent` = ".$this->parameters['parent_id'];
         } else if (isset($item->fields['itemtype'])
                 && isset($item->fields['items_id'])) {
            $where.= " AND `$table`.`itemtype` = '".$this->parameters['parent_itemtype']."'
                       AND `$table`.`items_id` = ".$this->parameters['parent_id'];
         } else if (isset($parent_item->fields[$fk_child])) {
            $parentTable = getTableForItemType($this->parameters['parent_itemtype']);
            $join.= " LEFT JOIN `$parentTable` ON `$parentTable`.`$fk_child` = `$table`.`id` ";
            $where.= " AND `$parentTable`.`id` = '" . $this->parameters['parent_id'] . "'";
         } else if (isset($parent_item->fields['itemtype'])
                 && isset($parent_item->fields['items_id'])) {
            $parentTable = getTableForItemType($this->parameters['parent_itemtype']);
            $join.= " LEFT JOIN `$parentTable` ON `itemtype`='$itemtype' AND `$parentTable`.`items_id` = `$table`.`id` ";
            $where.= " AND `$parentTable`.`id` = '" . $this->parameters['parent_id'] . "'";
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

         // make text search
         foreach ($params['searchText']  as $filter_field => $filter_value) {
            if (!empty($filter_value)) {
               $search = Search::makeTextSearch($filter_value);
               $where.= " AND (`$table`.`$filter_field` $search)";
            }
         }
      }

      // filter with entity
      if ($item->isEntityAssign()
          // some CommonDBChild classes may not have entities_id fields and isEntityAssign still return true (like TicketTemplateMandatoryField)
          && array_key_exists('entities_id', $item->fields)) {
         $where.= " AND (". getEntitiesRestrictRequest("",
                                             $itemtype::getTable(),
                                             '',
                                             $_SESSION['glpiactiveentities'],
                                             false,
                                             true);

         if ($item instanceof SavedSearch) {
            $where.= " OR ".$itemtype::getTable().".is_private = 1";
         }

         $where.= ")";
      }

      // build query
      $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `$table`.id,  `$table`.*
                FROM `$table`
                $join
                WHERE $where
                ORDER BY ".$params['sort']." ".$params['order']."
                LIMIT ".$params['start'].", ".$params['list_limit'];
      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            $found[] = $data;
         }
      }

      // get result full row counts
      $query_numtotalrow = "Select FOUND_ROWS()";
      $result_numtotalrow = $DB->query($query_numtotalrow);
      $data_numtotalrow = $DB->fetch_assoc($result_numtotalrow);
      $totalcount = $data_numtotalrow['FOUND_ROWS()'];

      if ($params['range'][0] > $totalcount) {
         $this->returnError("Provided range exceed total count of data: ".$totalcount,
                            400,
                            "ERROR_RANGE_EXCEED_TOTAL");
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
                                          'href' => self::$api_url."/$itemtype/".$fields['id']."/$hclass/"];
            }
         }
      }

      return array_values($found);
   }


   /**
    * Return a collection of items queried in input ($items)
    *
    * Call self::getItem for each line of $items
    *
    * @param array $params with theses options :
    *    - items:               array containing lines with itemtype and items_id keys
    *                               Ex: [
    *                                      [itemtype => 'Ticket', id => 102],
    *                                      [itemtype => 'User',   id => 10],
    *                                      [itemtype => 'User',   id => 11],
    *                                   ]
    *    - 'expand_dropdowns':  Show dropdown's names instead of id. default: false. Optionnal
    *    - 'get_hateoas':       Show relation of current item in a links attribute. default: true. Optionnal
    *    - 'get_sha1':          Get a sha1 signature instead of the full answer. default: false. Optionnal
    *    - 'with_devices':   Only for [Computer, NetworkEquipment, Peripheral, Phone, Printer], Optionnal.
    *    - 'with_disks':        Only for Computer, retrieve the associated filesystems. Optionnal.
    *    - 'with_softwares':    Only for Computer, retrieve the associated softwares installations. Optionnal.
    *    - 'with_connections':  Only for Computer, retrieve the associated direct connections (like peripherals and printers) .Optionnal.
    *    - 'with_networkports': Retrieve all network connections and advanced network informations. Optionnal.
    *    - 'with_infocoms':     Retrieve financial and administrative informations. Optionnal.
    *    - 'with_contracts':    Retrieve associated contracts. Optionnal.
    *    - 'with_documents':    Retrieve associated external documents. Optionnal.
    *    - 'with_tickets':      Retrieve associated itil tickets. Optionnal.
    *    - 'with_problems':     Retrieve associated itil problems. Optionnal.
    *    - 'with_changes':      Retrieve associated itil changes. Optionnal.
    *    - 'with_notes':        Retrieve Notes (if exists, not all itemtypes have notes). Optionnal.
    *    - 'with_logs':         Retrieve historical. Optionnal.
    *
    * @return array collection of glpi object's fields
    */
   protected function getMultipleItems($params = []) {

      if (!is_array($params['items'])) {
         return $this->messageBadArrayError();
      }

      $allitems = [];
      foreach ($params['items'] as $item) {
         if (!isset($item['items_id']) && !isset($item['itemtype'])) {
            return $this->messageBadArrayError();
         }

         $fields = $this->getItem($item['itemtype'], $item['items_id'], $params);
         $allitems[] = $fields;
      }

      return $allitems;
   }


   /**
    * List the searchoptions of provided itemtype. To use with searchItems function
    *
    * @param string $itemtype itemtype (class) of object
    * @param array  $params   parameters
    *
    * @return array all searchoptions of specified itemtype
    */
   protected function listSearchOptions($itemtype, $params = []) {

      $this->initEndpoint();
      $soptions = Search::getOptions($itemtype);

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
                                                                       ?$option['datatype']
                                                                       :"",
                                            'nosearch'              => isset($option['nosearch'])
                                                                       ?$option['nosearch']
                                                                       :false,
                                            'nodisplay'             => isset($option['nodisplay'])
                                                                       ?$option['nodisplay']
                                                                       :false,
                                            'available_searchtypes' => $available_searchtypes];
            $cleaned_soptions[$sID]['uid'] = $this->getSearchOptionUniqID($itemtype,
                                                                               $option);
         } else {
            $cleaned_soptions[$sID] = $option;
         }
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
   private function getSearchOptionUniqID($itemtype, $option = []) {

      $uid_parts = [$itemtype];

      $sub_itemtype = getItemTypeForTable($option['table']);

      if ((isset($option['joinparams']['beforejoin']['table'])
           || empty($option['joinparams']))
          && $option['linkfield'] != getForeignKeyFieldForItemType($sub_itemtype)
          && $option['linkfield'] != $option['field']) {
         $uid_parts[] = $option['linkfield'];
      }

      if (isset($option['joinparams'])) {
         if (isset($option['joinparams']['beforejoin'])) {
            $sub_parts  = $this->getSearchOptionUniqIDJoins($option['joinparams']['beforejoin']);
            $uid_parts = array_merge($uid_parts, $sub_parts);
         }
      }

      if (isset($option['joinparams']['beforejoin']['table'])
          || $sub_itemtype != $itemtype) {
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
   private function getSearchOptionUniqIDJoins($option) {

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
    * @param array  $params   with theses options :
    *    - 'criteria': array of criterion object to filter search.
    *        Optionnal.
    *        Each criterion object must provide :
    *           - link: (optionnal for 1st element) logical operator in [AND, OR, AND NOT, AND NOT].
    *           - field: id of searchoptions.
    *           - searchtype: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
    *           - value : value to search.
    *    - 'metacriteria' (optionnal): array of metacriterion object to filter search.
    *                                  Optionnal.
    *                                  A meta search is a link with another itemtype
    *                                  (ex: Computer with softwares).
    *         Each metacriterion object must provide :
    *            - link: logical operator in [AND, OR, AND NOT, AND NOT]. Mandatory
    *            - itemtype: second itemtype to link.
    *            - field: id of searchoptions.
    *            - searchtype: type of search in [contains, equals, notequals, lessthan, morethan, under, notunder].
    *            - value : value to search.
    *    - 'sort' :  id of searchoption to sort by (default 1). Optionnal.
    *    - 'order' : ASC - Ascending sort / DESC Descending sort (default ASC). Optionnal.
    *    - 'range' : a string with a couple of number for start and end of pagination separated by a '-'. Ex : 150-200. (default 0-50)
    *                Optionnal.
    *    - 'forcedisplay': array of columns to display (default empty = empty use display pref and search criterias).
    *                      Some columns will be always presents (1-id, 2-name, 80-Entity).
    *                      Optionnal.
    *    - 'rawdata': boolean for displaying raws data of Search engine of glpi (like sql request, and full searchoptions)
    *
    * @return array of raw rows from Search class
    */
   protected function searchItems($itemtype, $params = []) {
      global $DEBUG_SQL;

      $this->initEndpoint();

      // check rights
      if ($itemtype != 'AllAssets'
          && !$itemtype::canView()) {
         return $this->messageRightError();
      }

      // retrieve searchoptions
      $soptions = $this->listSearchOptions($itemtype);

      // Check the criterias are valid
      if (isset($params['criteria']) && is_array($params['criteria'])) {
         foreach ($params['criteria'] as $criteria) {
            if (!isset($criteria['field']) || !isset($criteria['searchtype'])
                || !isset($criteria['value'])) {
               return $this->returnError(__("Malformed search criteria"));
            }

            if (!ctype_digit((string) $criteria['field'])
                  || !array_key_exists($criteria['field'], $soptions)) {
               return $this->returnError(__("Bad field ID in search criteria"));
            }

            if (isset($soptions[$criteria['field']]) && isset($soptions[$criteria['field']]['nosearch'])
                && $soptions[$criteria['field']]['nosearch']) {
               return $this->returnError(__("Forbidden field ID in search criteria"));
            }
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
         if (isset($soptions[$forcedisplay]) && isset($soptions[$forcedisplay]['nodisplay'])
             && $soptions[$forcedisplay]['nodisplay']) {
            return $this->returnError(__("ID is forbidden along with 'forcedisplay' parameter."));
         }
      }

      // transform range parameter in start and limit variables
      if (isset($params['range']) > 0) {
         if (preg_match("/^[0-9]+-[0-9]+\$/", $params['range'])) {
            $range = explode("-", $params['range']);
            $params['start']      = $range[0];
            $params['list_limit'] = $range[1]-$range[0]+1;
            $params['range']      = $range;
         } else {
            $this->returnError("range must be in format : [start-end] with integers");
         }
      } else {
         $params['range'] = [0, $_SESSION['glpilist_limit']];
      }

      // force reset
      $params['reset'] = 'reset';

      // force logging sql queries
      $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;

      // call Core Search method
      $rawdata = Search::getDatas($itemtype, $params, $params['forcedisplay']);

      // probably a sql error
      if (!isset($rawdata['data']) || count($rawdata['data']) === 0) {
         $this->returnError("Unexpected SQL Error : ".array_splice($DEBUG_SQL['errors'], -2)[0],
                            500, "ERROR_SQL", false);
      }

      $cleaned_data = ['totalcount' => $rawdata['data']['totalcount'],
                            'count'      => count($rawdata['data']['rows']),
                            'sort'       => $rawdata['search']['sort'],
                            'order'      => $rawdata['search']['order']];

      if ($params['range'][0] > $cleaned_data['totalcount']) {
         $this->returnError("Provided range exceed total count of data: ".$cleaned_data['totalcount'],
                            400,
                            "ERROR_RANGE_EXCEED_TOTAL");
      }

      // fix end range
      if ($params['range'][1] > $cleaned_data['totalcount'] - 1) {
         $params['range'][1] = $cleaned_data['totalcount'] - 1;
      }

      //prepare cols (searchoptions_id) for cleaned data
      $cleaned_cols = [];
      foreach ($rawdata['data']['cols'] as $col) {
         $cleaned_cols[] = $col['id'];
      }

      // prepare cols wwith uid
      if (isset($params['uid_cols'])) {
         $uid_cols = [];
         foreach ($cleaned_cols as $col) {
            $uid_cols[] = $soptions[$col]['uid'];
         }
      }

      foreach ($rawdata['data']['rows'] as $row) {
         $raw = $row['raw'];
         $id = $raw['id'];

         // keep row itemtype for all asset
         if ($itemtype == 'AllAssets') {
            $current_id       = $raw['id'];
            $current_itemtype = $raw['TYPE'];
         }

         // retrive value (and manage multiple values)
         $clean_values = [];
         foreach ($row as $rkey => $rvalues) {
            // skip index who are not real columns (ex: raw, entities_id, etc)
            if (!is_integer($rkey)) {
               continue;
            }

            // manage multiple values (ex: IP adresses)
            $current_values = [];
            for ($valindex= 0; $valindex < $rvalues['count']; $valindex++) {
               $current_values[] = $rvalues[$valindex]['name'];
            }
            if (count($current_values) == 1) {
               $current_values = $current_values[0];
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
         if ($itemtype == 'AllAssets') {
            $current_line['id']       = $current_id;
            $current_line['itemtype'] = $current_itemtype;
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

      if (isset($params['rawdata'])
          && $params['rawdata']) {
         $cleaned_data['rawdata'] = $rawdata;
      }

      $cleaned_data['content-range'] = implode('-', $params['range']).
                                       "/".$cleaned_data['totalcount'];

      // return data
      return $cleaned_data;
   }


   /**
    * Add an object to GLPI
    *
    * @param string $itemtype itemtype (class) of object
    * @param array  $params   with theses options :
    *    - 'input' : object with fields of itemtype to be inserted.
    *                You can add several items in one action by passing array of input object.
    *                Mandatory.
    *
    * @return array of id
    */
   protected function createItems($itemtype, $params = []) {
      $this->initEndpoint();
      $input    = isset($params['input']) ? $params["input"] : null;
      $item     = new $itemtype;

      if (is_object($input)) {
         $input = [$input];
         $isMultiple = false;
      } else {
         $isMultiple = true;
      }

      if (is_array($input)) {
         $idCollection = [];
         $failed       = 0;
         $index        = 0;
         foreach ($input as $object) {
            $object      = self::inputObjectToArray($object);
            $current_res = [];

            //check rights
            if (!$item->can(-1, CREATE, $object)) {
               $failed++;
               $current_res = ['id'      => false,
                               'message' => __("You don't have permission to perform this action.")];
            } else {
               // add missing entity
               if (!isset($object['entities_id'])) {
                  $object['entities_id'] = $_SESSION['glpiactive_entity'];
               }

               // add an entry to match gui post (which contains submit button)
               // to force having messages after redirect
               $object["_add"] = true;

               //add current item
               $object = Toolbox::sanitize($object);
               $new_id = $item->add($object);
               if ($new_id === false) {
                  $failed++;
               }
               $current_res = ['id'      => $new_id,
                               'message' => $this->getGlpiLastMessage()];
            }

            // attach fileupload answer
            if (isset($params['upload_result'])
                && isset($params['upload_result'][$index])) {
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
   private function inputObjectToArray($input) {
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
    * @param array  $params   with theses options :
    *    - 'input' : Array of objects with fields of itemtype to be updated.
    *                Mandatory.
    *                You must provide in each object a key named 'id' to identify item to update.
    *
    * @return   array of boolean
    */
   protected function updateItems($itemtype, $params = []) {

      $this->initEndpoint();
      $input    = isset($params['input']) ? $params["input"] : null;
      $item     = new $itemtype;

      if (is_object($input)) {
         $input = [$input];
         $isMultiple = false;
      } else {
         $isMultiple = true;
      }

      if (is_array($input)) {
         $idCollection = [];
         $failed       = 0;
         $index        = 0;
         foreach ($input as $object) {
            $current_res = [];
            if (isset($object->id)) {
               if (!$item->getFromDB($object->id)) {
                  $failed++;
                  $current_res = [$object->id => false,
                                  'message'   => __("Item not found")];
                  continue;
               }

               //check rights
               if (!$item->can($object->id, UPDATE)) {
                  $failed++;
                  $current_res = [$object->id => false,
                                 'message'    => __("You don't have permission to perform this action.")];
               } else {
                  // if parent key not provided in input and present in parameter
                  // (detected from url for example), try to appent it do input
                  // This is usefull to have logs in parent (and avoid some warnings in commonDBTM)
                  if (isset($params['parent_itemtype'])
                      && isset($params['parent_id'])) {
                     $fk_parent = getForeignKeyFieldForItemType($params['parent_itemtype']);
                     if (!property_exists($input, $fk_parent)) {
                        $input->$fk_parent = $params['parent_id'];
                     }
                  }

                  //update item
                  $object = Toolbox::sanitize((array)$object);
                  $update_return = $item->update($object);
                  if ($update_return === false) {
                     $failed++;
                  }
                  $current_res = [$item->fields["id"] => $update_return,
                                  'message'           => $this->getGlpiLastMessage()];
               }

            }

            // attach fileupload answer
            if (isset($params['upload_result'])
                && isset($params['upload_result'][$index])) {
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
    * @param array  $params   with theses options :
    *    - 'input' : Array of objects with fields of itemtype to be updated.
    *                Mandatory.
    *                You must provide in each object a key named 'id' to identify item to delete.*
    *    - 'force_purge' : boolean, if itemtype have a trashbin, you can force purge (delete finally).
    *                      Optionnal.
    *    - 'history' : boolean, default true, false to disable saving of deletion in global history.
    *                  Optionnal.
    *
    * @return boolean|boolean[]
    */
   protected function deleteItems($itemtype, $params = []) {

      $this->initEndpoint();
      $default  = ['force_purge' => false,
                        'history'     => true];
      $params   = array_merge($default, $params);
      $input    = $params['input'];
      $item     = new $itemtype;

      if (is_object($input)) {
         $input = [$input];
         $isMultiple = false;
      } else {
         $isMultiple = true;
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
               if ($item->isTemplate()
                  || !$item->maybeDeleted()
                  // Do not take into account deleted field if maybe dynamic but not dynamic
                  || ($item->useDeletedToLockIfDynamic()
                        && !$item->isDynamic())) {
                  $params['force_purge'] = 1;
               } else {
                  $params['force_purge'] = filter_var($params['force_purge'], FILTER_VALIDATE_BOOLEAN);
               }

               //check rights
               if (($params['force_purge']
                    && !$item->can($object->id, PURGE))
                   || (!$params['force_purge']
                       && !$item->can($object->id, DELETE))) {
                  $failed++;
                  $idCollection[] = [
                        $object->id => false,
                        'message' => __("You don't have permission to perform this action.")
                  ];
               } else {
                  //delete item
                  $delete_return = $item->delete((array) $object,
                                                 $params['force_purge'],
                                                 $params['history']);
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


   protected function lostPassword($params = []) {
      global $CFG_GLPI;

      if ($CFG_GLPI['use_notifications'] == '0' || $CFG_GLPI['notifications_mailing'] == '0') {
         return $this->returnError(__("Email notifications are disabled"));
      }

      if (!isset($params['email'])) {
         return $this->returnError(__("email parameter missing"));
      }

      if (isset($_SESSION['glpiID'])) {
         return $this->returnError(__("A session is active"));
      }

      $user = new User();
      if (!isset($params['password_forget_token'])) {
         $email = Toolbox::addslashes_deep($params['email']);
         try {
            $user->forgetPassword($email);
         } catch (ForgetPasswordException $e) {
            return $this->returnError($e->getMessage());
         }
         return $this->returnResponse([
            __("An email has been sent to your email address. The email contains information for reset your password.")
         ]);
      } else {
         $password = isset($params['password']) ? $params['password'] : '';
         $input = [
            'email'                    => Toolbox::addslashes_deep($params['email']),
            'password_forget_token'    => Toolbox::addslashes_deep($params['password_forget_token']),
            'password'                 => Toolbox::addslashes_deep($password),
            'password2'                => Toolbox::addslashes_deep($password),
         ];
         try {
            $user->updateForgottenPassword($input);
            return $this->returnResponse([__("Reset password successful.")]);
         } catch (ForgetPasswordException $e) {
            return $this->returnError($e->getMessage());
         } catch (PasswordTooWeakException $e) {
            implode('\n', $e->getMessages());
            return $this->returnError(implode('\n', $e->getMessages()));
         }
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
   private function initEndpoint($unlock_session = true, $endpoint = "") {

      if ($endpoint === "") {
         $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
         $endpoint = $backtrace[1]['function'];
      }
      $this->checkAppToken();
      $this->logEndpointUsage($endpoint);
      self::checkSessionToken();
      if ($unlock_session) {
         self::unlockSessionIfPossible();
      }
   }


   /**
    * Check if the app_toke in case of config ask to
    *
    * @return void
    */
   private function checkAppToken() {

      // check app token (if needed)
      if (!isset($this->parameters['app_token'])) {
         $this->parameters['app_token'] = "";
      }
      if (!$this->apiclients_id = array_search($this->parameters['app_token'], $this->app_tokens)) {
         if ($this->parameters['app_token'] != "") {
            $this->returnError(__("parameter app_token seems wrong"), 400,
                               "ERROR_WRONG_APP_TOKEN_PARAMETER");
         } else {
            $this->returnError(__("missing parameter app_token"), 400,
                               "ERROR_APP_TOKEN_PARAMETERS_MISSING");
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
   private function logEndpointUsage($endpoint = "") {

      $username = "";
      if (isset($_SESSION['glpiname'])) {
         $username = "(".$_SESSION['glpiname'].")";
      }

      $apiclient = new APIClient;
      if ($apiclient->getFromDB($this->apiclients_id)) {
         $changes = [
            0,
            "",
            "Enpoint '$endpoint' called by ".$this->iptxt." $username"
         ];

         switch ($apiclient->fields['dolog_method']) {
            case APIClient::DOLOG_HISTORICAL:
               Log::history($this->apiclients_id, 'APIClient', $changes, 0,
                            Log::HISTORY_LOG_SIMPLE_MESSAGE);
               break;

            case APIClient::DOLOG_LOGS:
               Toolbox::logInFile("api", $changes[2]."\n");
               break;
         }
      }
   }


   /**
    * Check that the session_token is provided and match to a valid php session
    *
    * @return boolean
    */
   protected function checkSessionToken() {

      if (!isset($this->parameters['session_token'])
          || empty($this->parameters['session_token'])) {
         return $this->messageSessionTokenMissing();
      }

      $current = session_id();
      if ($this->parameters['session_token'] != $current
          && !empty($current)
          || !isset($_SESSION['glpiID'])) {
         return $this->messageSessionError();
      }
   }


   /**
    * Unlock the current session (readonly) to permit concurrent call
    *
    * @return void
    */
   private function unlockSessionIfPossible() {

      if (!$this->session_write) {
         session_write_close();
      }
   }


   /**
    * Get last message added in $_SESSION by Session::addMessageAfterRedirect
    *
    * @return array  of messages
    */
   private function getGlpiLastMessage() {
      global $DEBUG_SQL;

      $all_messages             = [];

      $messages_after_redirect  = [];

      if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
          && count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0) {
         $messages_after_redirect = $_SESSION["MESSAGE_AFTER_REDIRECT"];
         // Clean messages
         $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
      };

      // clean html
      foreach ($messages_after_redirect as $messages) {
         foreach ($messages as $message) {
            $all_messages[] = Html::clean($message);
         }
      }

      // get sql errors
      if (count($all_messages) <= 0
          && $DEBUG_SQL['errors'] !== null) {
         $all_messages = $DEBUG_SQL['errors'];
      }

      if (!end($all_messages)) {
         return '';
      }
      return end($all_messages);
   }


   /**
    * Show API Debug
    *
    * @return void
    */
   protected function showDebug() {
      Html::printCleanArray($this);
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
   protected function header($html = false, $title = "") {

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
   public function inlineDocumentation($file) {
      self::header(true, __("API Documentation"));
      echo Html::css("lib/prism/prism.css");
      echo Html::script("lib/prism/prism.js");

      echo "<div class='documentation'>";
      $documentation = file_get_contents(GLPI_ROOT.'/'.$file);
      $md = new Michelf\MarkdownExtra();
      $md->code_class_prefix = "language-";
      $md->header_id_func = function($headerName) {
         $headerName = str_replace(['(', ')'], '', $headerName);
         return rawurlencode(strtolower(strtr($headerName, [' ' => '-'])));
      };
      echo $md->transform($documentation);
      echo "</div>";

      Html::nullFooter();
   }


   /**
    * Transform array of fields passed in parameter :
    * change value from  integer id to string name of foreign key
    * You can pass an array of array, this method is recursive.
    *
    * @param array   $fields to check and transform
    * @param boolean $params array of option to enable, could be :
    *                                 - expand_dropdowns (default false)
    *                                 - get_hateoas      (default true)
    *
    * @return array altered $fields
    */
   protected static function parseDropdowns($fields, $params = []) {

      // default params
      $default = ['expand_dropdowns' => false,
                       'get_hateoas'      => true];
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
            if ($key == "auths_id"
                && isset($fields['authtype']) && $fields['authtype'] == Auth::LDAP) {
               $key = "authldaps_id";
            }
            if ($key == "default_requesttypes_id") {
               $key = "requesttypes_id";
            }

            if (!empty($value)
                || $key == 'entities_id' && $value >= 0) {

               $tablename = getTableNameForForeignKeyField($key);
               $itemtype = getItemTypeForTable($tablename);

               // get hateoas
               if ($params['get_hateoas']) {
                  $fields['links'][] = ['rel'  => $itemtype,
                                             'href' => self::$api_url."/$itemtype/".$value];
               }

               // expand dropdown
               if ($params['expand_dropdowns']) {
                  $value = Dropdown::getDropdownName($tablename, $value);
                  // fix value for inexistent items
                  if ($value == "&nbsp;") {
                     $value = "";
                  }
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
   static function getHatoasClasses($itemtype) {
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
            if ((($device_type =="DeviceMemory")
                 && !in_array($itemtype, $CFG_GLPI["itemdevicememory_types"]))
                || (($device_type =="DevicePowerSupply")
                    && !in_array($itemtype, $CFG_GLPI["itemdevicepowersupply_types"]))
                || (($device_type =="DeviceNetworkCard")
                    && !in_array($itemtype, $CFG_GLPI["itemdevicenetworkcard_types"]))) {
               continue;
            }
            $hclasses[] = "Item_".$device_type;
         }
      }

      //specific case
      switch ($itemtype) {
         case 'Ticket' :
            $hclasses[] = "TicketFollowup";
            $hclasses[] = "TicketTask";
            $hclasses[] = "TicketValidation";
            $hclasses[] = "TicketCost";
            $hclasses[] = "Problem_Ticket";
            $hclasses[] = "Change_Ticket";
            $hclasses[] = "Item_Ticket";
            $hclasses[] = "ITILSolution";
            break;

         case 'Problem' :
            $hclasses[] = "ProblemTask";
            $hclasses[] = "ProblemCost";
            $hclasses[] = "Change_Problem";
            $hclasses[] = "Problem_Ticket";
            $hclasses[] = "Item_Problem";
            $hclasses[] = "ITILSolution";
            break;

         case 'Change' :
            $hclasses[] = "ChangeTask";
            $hclasses[] = "ChangeCost";
            $hclasses[] = "Change_Project";
            $hclasses[] = "Change_Problem";
            $hclasses[] = "Change_Ticket";
            $hclasses[] = "Change_Item";
            $hclasses[] = "ITILSolution";
            break;

         case 'Project' :
            $hclasses[] = "ProjectTask";
            $hclasses[] = "ProjectCost";
            $hclasses[] = "Change_Project";
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
   public function messageNotfoundError($return_error = true) {

      $this->returnError(__("Item not found"),
                         404,
                         "ERROR_ITEM_NOT_FOUND",
                         false,
                         $return_error);
   }


   /**
    * Send 400 error to client
    *
    * @param boolean $return_error (default true)
    *
    * @return void
    */
   public function messageBadArrayError($return_error = true) {

      $this->returnError(__("input parameter must be an array of objects"),
                         400,
                         "ERROR_BAD_ARRAY",
                         true,
                         $return_error);
   }


   /**
    * Send 405 error to client
    *
    * @param boolean $return_error (default true)
    *
    * @return void
    */
   public function messageLostError($return_error = true) {

      $this->returnError(__("Method Not Allowed"),
                         405,
                         "ERROR_METHOD_NOT_ALLOWED",
                         true,
                         $return_error);
   }


   /**
    * Send 401 error to client
    *
    * @param boolean $return_error (default true)
    *
    * @return void
    */
   public function messageRightError($return_error = true) {

      $this->returnError(__("You don't have permission to perform this action."),
                         401,
                         "ERROR_RIGHT_MISSING",
                         false,
                         $return_error);
   }


   /**
    * Session Token KO
    *
    * @param boolean $return_error (default true)
    *
    * @return void
    */
   public function messageSessionError($return_error = true) {
      $this->returnError(__("session_token seems invalid"),
                         401,
                         "ERROR_SESSION_TOKEN_INVALID",
                         false,
                         $return_error);
   }


   /**
    * Session Token missing
    *
    * @param boolean $return_error (default true)
    *
    * @return void
    */
   public function messageSessionTokenMissing($return_error = true) {

      $this->returnError(__("parameter session_token is missing or empty"),
                         400,
                         "ERROR_SESSION_TOKEN_MISSING",
                         true,
                         $return_error);
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
    * @return array
    */
   public function returnError($message = "Bad Request", $httpcode = 400, $statuscode = "ERROR",
                               $docmessage = true, $return_response = true) {

      if (empty($httpcode)) {
         $httpcode = 400;
      }
      if (empty($statuscode)) {
         $statuscode = "ERROR";
      }

      if ($docmessage) {
         $message .= "; ".sprintf(__("view documentation in your browser at %s"),
                                  self::$api_url."/#$statuscode");
      }
      if ($return_response) {
         return $this->returnResponse([$statuscode, $message], $httpcode);
      }
      return [$statuscode, $message];
   }


   /**
    * Get the raw HTTP request body
    *
    * @return string
    */
   protected function getHttpBody() {
      return file_get_contents('php://input');
   }
}
