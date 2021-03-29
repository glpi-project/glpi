<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Psr7\Response;

/**
 * @since 10.0.0
 **/
class Agent extends CommonDBTM {

   /** @var integer */
   public const DEFAULT_PORT = 62354;

   /** @var string */
   public const ACTION_STATUS = 'status';

   /** @var string */
   public const ACTION_INVENTORY = 'inventory';


   /** @var integer */
   protected const TIMEOUT  = 5;

   /** @var boolean */
   public $dohistory = true;

   /** @var string */
   static $rightname = 'computer';
   //static $rightname = 'inventory';

   private static $found_adress = false;

   static function getTypeName($nb = 0) {
      return _n('Agent', 'Agents', $nb);
   }

   function rawSearchOptions() {

      $tab = [
         [
            'id'            => 'common',
            'name'          => self::getTypeName(1)
         ], [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'autocomplete'  => true,
         ], [
            'id'            => '2',
            'table'         => Entity::getTable(),
            'field'         => 'completename',
            'name'          => Entity::getTypeName(1),
            'datatype'      => 'dropdown',
         ], [
            'id'            => '3',
            'table'         => $this->getTable(),
            'field'         => 'is_recursive',
            'name'          => __('Child entities'),
            'datatype'      => 'bool',
         ], [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'last_contact',
            'name'          => __('Last contact'),
            'datatype'      => 'datetime',
         ], [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'locked',
            'name'          => __('Locked'),
            'datatype'      => 'bool',
         ], [
            'id'            => '6',
            'table'         => $this->getTable(),
            'field'         => 'deviceid',
            'name'          => __('Device id'),
            'datatype'      => 'text',
            'massiveaction' => false,
         ], [
            'id'            => '8',
            'table'         => $this->getTable(),
            'field'         => 'version',
            'name'          => _n('Version', 'Versions', 1),
            'datatype'      => 'text',
            'massiveaction' => false,
         ], [
            'id'            => '10',
            'table'         => $this->getTable(),
            'field'         => 'useragent',
            'name'          => __('Useragent'),
            'datatype'      => 'text',
            'massiveaction' => false,
         ], [
            'id'            => '11',
            'table'         => $this->getTable(),
            'field'         => 'tag',
            'name'          => __('Tag'),
            'datatype'      => 'text',
            'massiveaction' => false,
         ], [
            'id'            => '14',
            'table'         => $this->getTable(),
            'field'         => 'port',
            'name'          => _n('Port', 'Ports', 1),
            'datatype'      => 'integer',
         ]
      ];

      return $tab;
   }

   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * Display form for agent configuration
    *
    * @param integer $id      ID of the agent
    * @param array   $options Options
    *
    * @return boolean
    */
   function showForm($id, $options = []) {
      global $CFG_GLPI;

      if (!empty($id)) {
         $this->getFromDB($id);
      } else {
         $this->getEmpty();
      }
      $this->initForm($id, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='name'>".__('Name')."</label></td>";
      echo "<td align='center'>";
      Html::autocompletionTextField($this, 'name', ['size' => 40]);
      echo "</td>";
      echo "<td>".__('Locked')."</td>";
      echo "<td align='center'>";
      Dropdown::showYesNo('locked', $this->fields["locked"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='deviceid'>".__('Device id')."</label></td>";
      echo "<td align='center'>";
      echo "<input type='text' name='deviceid' id='deviceid' value='".$this->fields['deviceid']."' required='required'/>";
      echo "</td>";
      echo "<td>"._n('Port', 'Ports', 1)."</td>";
      echo "<td align='center'>";
      echo "<input type='text' name='port' value='".$this->fields['port']."'/>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='agenttypes_id'>".AgentType::getTypeName(1)."</label></td>";
      echo "<td align='center'>";

      $value = $this->isNewItem() ? 1 : $this->fields['agenttypes_id'];
      AgentType::dropdown(['value' => $value]);

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Item type')."</td>";
      echo "<td align='center'>";
      Dropdown::showFromArray('itemtype', $CFG_GLPI['inventory_types'], ['value' => $this->fields['itemtype']]);
      echo "</td>";
      echo "<td>".__('Item link')."</td>";
      echo "<td align='center'>";
      if (!empty($this->fields["items_id"])) {
         $asset = new $this->fields['itemtype'];
         $asset->getFromDB($this->fields['items_id']);
         echo $asset->getLink(1);
         echo Html::hidden(
            'items_id',
            [
               'value' => $this->fields["items_id"]
            ]
         );
      }
      echo "</td>";
      echo "</tr>";

      if (!$this->isNewItem()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>"._n('Version', 'Versions', 1)."</td>";
         echo "<td align='center'>";
         $versions = importArrayFromDB($this->fields["version"]);
         foreach ($versions as $module => $version) {
            echo "<strong>".$module. "</strong>: ".$version."<br/>";
         }
         echo "</td>";
         echo "<td>".__('Tag')."</td>";
         echo "<td align='center'>";
         echo $this->fields["tag"];
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Useragent')."</td>";
         echo "<td align='center'>";
         echo $this->fields["useragent"];
         echo "</td>";
         echo "<td>".__('Last contact')."</td>";
         echo "<td align='center'>";
         echo Html::convDateTime($this->fields["last_contact"]);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>";
         echo "</td>";
         echo "</tr>";
      }

      $this->showFormButtons($options);

      return true;
   }

   /**
    * Handle agent
    *
    * @param array $metadata Agents metadata from Inventory
    *
    * @return integer
    */
   public function handleAgent($metadata) {
      $deviceid = $metadata['deviceid'];

      $aid = false;
      if ($this->getFromDBByCrit(['deviceid' => $deviceid])) {
         $aid = $this->fields['id'];
      }

      $atype = new AgentType();
      $atype->getFromDBByCrit(['name' => 'Core']);

      $input = [
         'deviceid'     => $deviceid,
         'name'         => $deviceid,
         'last_contact' => date('Y-m-d H:i:s'),
         'useragent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
         'agenttypes_id'=> $atype->fields['id'],
         'itemtype'     => $metadata['itemtype'] ?? 'Computer'
      ];

      if (isset($metadata['provider']['version'])) {
         $input['version'] = $metadata['provider']['version'];
      }

      if (isset($metadata['tag'])) {
         $input['tag'] = $metadata['tag'];
      }

      if ($deviceid === 'foo') {
         $input += [
            'items_id' => 0,
            'id' => 0
         ];
         $this->fields = $input;
         return 0;
      }

      $input = Toolbox::addslashes_deep($input);
      if ($aid) {
         $input['id'] = $aid;
         $this->update($input);
      } else {
         $input['items_id'] = 0;
         $aid = $this->add($input);
      }

      return $aid;
   }

   /**
    * Preapre input for add and update
    *
    * @param array $input Input
    *
    * @return array|false
    */
   public function prepareInputs(array $input) {
      if ($this->isNewItem() && (!isset($input['deviceid']) || empty($input['deviceid']))) {
         Session::addMessageAfterRedirect(__('"deviceid" is mandatory!'), false, ERROR);
         return false;
      }
      return $input;
   }

   public function prepareInputForAdd($input) {
      return $this->prepareInputs($input);
   }

   public function prepareInputForUpdate($input) {
      return $this->prepareInputs($input);
   }

   /**
    * Get item linked to current agent
    *
    * @return CommonDBTM
    */
   public function getLinkedItem(): CommonDBTM {
      $itemtype = $this->fields['itemtype'];
      $item = new $itemtype();
      $item->getFromDB($this->fields['items_id']);
      return $item;
   }

   /**
    * Guess possible adresses the agent should answer on
    *
    * @return array
    */
   public function guessAddresses(): array {
      global $DB;

      $adresses = [];

      //retrieve linked items
      $item = $this->getLinkedItem();
      if ((int)$item->getID() > 0) {
         $item_name = $item->getFriendlyName();
         $adresses[] = $item_name;

         //deviceid should contains machines name
         $matches = [];
         preg_match('/^(\s)+-\d{4}(-\d{2}){5}$/', $this->fields['deviceid'], $matches);
         if (isset($matches[1])) {
            if (!in_array($matches[1], $adresses)) {
               $adresses[] = $matches[1];
            }
         }

         //append linked ips
         $ports_iterator = $DB->request([
            'SELECT' => ['ips.name'],
            'FROM'   => NetworkPort::getTable() . ' AS netports',
            'WHERE'  => [
               'netports.itemtype'  => $item->getType(),
               'netports.items_id'  => $item->getID(),
               'NOT'       => [
                  'OR'  => [
                     'netports.instantiation_type' => 'NetworkPortLocal',
                     'ips.name'                    => ['127.0.0.1', '::1']
                  ]
               ]
            ],
            'INNER JOIN'   => [
               NetworkName::getTable() . ' AS netnames' => [
                  'ON'  => [
                     'netnames'  => 'items_id',
                     'netports'  => 'id', [
                        'AND' => [
                           'netnames.itemtype'  => NetworkPort::getType()
                        ]
                     ]
                  ]
               ],
               IPAddress::getTable() . ' AS ips' => [
                  'ON'  => [
                     'ips'       => 'items_id',
                     'netnames'  => 'id', [
                        'AND' => [
                           'ips.itemtype' => NetworkName::getType()
                        ]
                     ]
                  ]
               ]
            ]
         ]);
         while ($row = $ports_iterator->next()) {
            if (!in_array($row['name'], $adresses)) {
               $adresses[] = $row['name'];
            }
         }

         //append linked domains
         $iterator = $DB->request([
            'SELECT' => ['d.name'],
            'FROM'   => Domain_Item::getTable(),
            'WHERE'  => [
               'itemtype'  => $item->getType(),
               'items_id'  => $item->getID()
            ],
            'INNER JOIN'   => [
               Domain::getTable() . ' AS d'  => [
                  'ON'  => [
                     Domain_Item::getTable() => Domain::getForeignKeyField(),
                     'd'                     => 'id'
                  ]
               ]
            ]
         ]);

         while ($row = $iterator->next()) {
            $adresses[] = sprintf('%s.%s', $item_name, $row['name']);
         }
      }

      return $adresses;
   }

   /**
    * Get agent URLs
    *
    * @return array
    */
   public function getAgentURLs(): array {
      $adresses = $this->guessAddresses();
      $protocols = ['https', 'http'];
      $port = (int)$this->fields['port'];
      if ($port === 0) {
         $port = self::DEFAULT_PORT;
      }

      $urls = [];
      foreach ($protocols as $protocol) {
         foreach ($adresses as $adress) {
            $urls[] = sprintf(
               '%s://%s:%s',
               $protocol,
               $adress,
               $port
            );
         }
      }

      return $urls;
   }

   /**
    * Send agent an HTTP request
    *
    * @param string $endpoint Endpoint to reach
    *
    * @return Response
    */
   protected function requestAgent($endpoint): Response {
      global $CFG_GLPI;

      if (self::$found_adress !== false) {
         $adresses = [self::$found_adress];
      } else {
         $adresses = $this->getAgentURLs();
      }

      foreach ($adresses as $adress) {
         $options = [
            'base_uri'        => sprintf('%s/%s', $adress, $endpoint),
            'connect_timeout' => self::TIMEOUT,
         ];

         // add proxy string if configured in glpi
         if (!empty($CFG_GLPI["proxy_name"])) {
            $proxy_creds      = !empty($CFG_GLPI["proxy_user"])
               ? $CFG_GLPI["proxy_user"].":".Toolbox::sodiumDecrypt($CFG_GLPI["proxy_passwd"])."@"
               : "";
            $proxy_string     = "http://{$proxy_creds}".$CFG_GLPI['proxy_name'].":".$CFG_GLPI['proxy_port'];
            $options['proxy'] = $proxy_string;
         }

         // init guzzle client with base options
         $httpClient = new Guzzle_Client($options);
         try {
            $response = $httpClient->request('GET', $endpoint, []);
            self::$found_adress = $adress;
            break;
         } catch (\GuzzleHttp\Exception\ConnectException $e) {
            //many adresses will be incorrect
            $cs = true;
         }
      }
      return $response;
   }

   /**
    * Request status from agent
    *
    * @return array
    */
   public function requestStatus() {
      //must return json
      $response = $this->requestAgent('status');
      return $this->handleAgentResponse($response, self::ACTION_STATUS);
   }

   /**
    * Request inventory from agent
    *
    * @return array
    */
   public function requestInventory() {
      //must return json
      try {
         $this->requestAgent('now');
         return $this->handleAgentResponse(new Response(), self::ACTION_INVENTORY);
      } catch (\GuzzleHttp\Exception\ClientException $e) {
         Toolbox::logError($e->getMessage());
         //not authorized
         return ['answer' => __('Not allowed')];
      }
   }

   /**
    * Handle agent response and returns an array to be serialized as json
    *
    * @param Response $response Response
    * @param string   $request  Request type (either status or now)
    *
    * @return array
    */
   private function handleAgentResponse(Response $response, $request): array {
      $data = [];

      $raw_content = (string)$response->getBody();

      switch ($request) {
         case self::ACTION_STATUS:
            $data['answer'] = preg_replace('/status: /', '', $raw_content);
            break;
         case self::ACTION_INVENTORY:
            $now = new DateTime();
            $data['answer'] = sprintf(
               __('Requested at %s'),
               $now->format(__('Y-m-d H:i:s'))
            );
            break;
         default:
            throw new \RuntimeException(sprintf('Unknown request type %s', $request));
      }

      return $data;
   }

   public static function getIcon() {
      return "fas fa-robot";
   }
}
