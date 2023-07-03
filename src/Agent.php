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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Inventory\Conf;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;
use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Psr7\Response;

/**
 * @since 10.0.0
 **/
class Agent extends CommonDBTM
{
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
    public static $rightname = 'agent';
   //static $rightname = 'inventory';

    private static $found_address = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Agent', 'Agents', $nb);
    }

    public function rawSearchOptions()
    {

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
            ], [
                'id'               => '15',
                'table'            => $this->getTable(),
                'field'            => 'items_id',
                'name'             =>  _n('Item', 'Items', 1),
                'nosearch'         => true,
                'massiveaction'    => false,
                'forcegroupby'     => true,
                'datatype'         => 'specific',
                'searchtype'       => 'equals',
                'additionalfields' => ['itemtype'],
                'joinparams'       => ['jointype' => 'child']
            ],
            [
                'id'            => '16',
                'table'         => $this->getTable(),
                'field'         => 'remote_addr',
                'name'          => __('Public contact address'),
                'datatype'      => 'text',
                'massiveaction' => false,
            ],
            [
                'id'            => 17,
                'table'         => $this->getTable(),
                'field'         => 'use_module_wake_on_lan',
                'name'          => __('Wake on LAN'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 18,
                'table'         => $this->getTable(),
                'field'         => 'use_module_computer_inventory',
                'name'          => __('Computer inventory'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 19,
                'table'         => $this->getTable(),
                'field'         => 'use_module_esx_remote_inventory',
                'name'          => __('ESX remote inventory'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 20,
                'table'         => $this->getTable(),
                'field'         => 'use_module_network_inventory',
                'name'          => __('Network inventory (SNMP)'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 21,
                'table'         => $this->getTable(),
                'field'         => 'use_module_network_discovery',
                'name'          => __('Network discovery (SNMP)'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 22,
                'table'         => $this->getTable(),
                'field'         => 'use_module_package_deployment',
                'name'          => __('Package Deployment'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 23,
                'table'         => $this->getTable(),
                'field'         => 'use_module_collect_data',
                'name'          => __('Collect data'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ],
            [
                'id'            => 24,
                'table'         => $this->getTable(),
                'field'         => 'use_module_remote_inventory',
                'name'          => __('Remote inventory'),
                'datatype'      => 'bool',
                'massiveaction' => false,
            ]

        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'items_id':
                $itemtype = $values[str_replace('items_id', 'itemtype', $field)] ?? null;
                if ($itemtype !== null && class_exists($itemtype)) {
                    if ($values[$field] > 0) {
                        $item = new $itemtype();
                        $item->getFromDB($values[$field]);
                        return "<a href='" . $item->getLinkURL() . "'>" . $item->fields['name'] . "</a>";
                    }
                } else {
                    return ' ';
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        // separator
        $tab[] = [
            'id'   => 'agent',
            'name' => self::getTypeName(1),
        ];

        $baseopts = [
            'table'      => self::getTable(),
            'joinparams' => [
                'jointype' => 'itemtype_item',
            ],
        ];

        $tab[] = [
            'id'         => 900,
            'field'      => 'name',
            'name'       => __('Name'),
            'datatype'   => 'itemlink',
        ] + $baseopts;

        $tab[] = [
            'id'         => 901,
            'field'      => 'tag',
            'name'       => __('Tag'),
            'datatype'   => 'text',
        ] + $baseopts;

        $tab[] = [
            'id'         => 902,
            'field'      => 'last_contact',
            'name'       => __('Last contact'),
            'datatype'   => 'datetime',
        ] + $baseopts;

        $tab[] = [
            'id'         => 903,
            'field'      => 'version',
            'name'       => _n('Version', 'Versions', 1),
            'datatype'   => 'text',
        ] + $baseopts;

        $tab[] = [
            'id'         => 904,
            'field'      => 'deviceid',
            'name'       => __('Device id'),
            'datatype'   => 'text',
        ] + $baseopts;

        $tab[] = [
            'id'         => 905,
            'field'      => 'remote_addr',
            'name'       => __('Public contact address'),
            'datatype'   => 'text',
        ] + $baseopts;

        $tab[] = [
            'id'         => 906,
            'field'      => 'useragent',
            'name'       => __('Useragent'),
            'datatype'   => 'text',
        ] + $baseopts;

        $tab[] = [
            'id'         => 907,
            'field'      => 'tag',
            'name'       => __('TAG'),
            'datatype'   => 'text',
        ] + $baseopts;

        return $tab;
    }

    /**
     * Define tabs to display on form page
     *
     * @param array $options
     * @return array containing the tabs name
     */
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('RuleMatchedLog', $ong, $options);
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
    public function showForm($id, array $options = [])
    {
        global $CFG_GLPI;

        if (!empty($id)) {
            $this->getFromDB($id);
        } else {
            $this->getEmpty();
        }
        $this->initForm($id, $options);

        // avoid generic form to display generic version field as we have an exploded view
        $versions = $this->fields['version'] ?? '';
        unset($this->fields['version']);

        TemplateRenderer::getInstance()->display('pages/admin/inventory/agent.html.twig', [
            'item'           => $this,
            'params'         => $options,
            'itemtypes'      => array_combine($CFG_GLPI['inventory_types'], $CFG_GLPI['inventory_types']),
            'versions_field' => $versions,
        ]);
        return true;
    }

    /**
     * Handle agent
     *
     * @param array $metadata Agents metadata from Inventory
     *
     * @return integer
     */
    public function handleAgent($metadata)
    {
        global $CFG_GLPI;

        $deviceid = $metadata['deviceid'];

        $aid = false;
        if ($this->getFromDBByCrit(Sanitizer::dbEscapeRecursive(['deviceid' => $deviceid]))) {
            $aid = $this->fields['id'];
        }

        $atype = new AgentType();
        if (!$atype->getFromDBByCrit(['name' => 'Core'])) {
            $atype->add([
                'name' => 'Core',
            ]);
        }

        $input = [
            'deviceid'     => $deviceid,
            'name'         => $deviceid,
            'last_contact' => date('Y-m-d H:i:s'),
            'useragent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'agenttypes_id' => $atype->fields['id'],
            'itemtype'     => $metadata['itemtype'] ?? 'Computer'
        ];

        if (isset($metadata['provider']['version'])) {
            $input['version'] = $metadata['provider']['version'];
        }

        if (isset($metadata['tag'])) {
            $input['tag'] = $metadata['tag'];
        }

        if (isset($metadata['port'])) {
            $input['port'] = $metadata['port'];
        }

        if (isset($metadata['enabled-tasks'])) {
            $input['use_module_computer_inventory']   = in_array("inventory", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_network_discovery']    = in_array("netdiscovery", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_network_inventory']    = in_array("netinventory", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_remote_inventory']     = in_array("remoteinventory", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_wake_on_lan']          = in_array("wakeonlan", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_esx_remote_inventory'] = in_array("esx", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_package_deployment']   = in_array("deploy", $metadata['enabled-tasks']) ? 1 : 0;
            $input['use_module_collect_data']         = in_array("collect", $metadata['enabled-tasks']) ? 1 : 0;
        }

        $remote_ip = "";
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //Managing IP through a PROXY
            $remote_ip = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            //try with X-Real-IP
            $remote_ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            //then get connected IP
            $remote_ip = $_SERVER['REMOTE_ADDR'];
        }

        $remote_ip = new IPAddress($remote_ip);
        if ($remote_ip->is_valid()) {
            $input['remote_addr'] = $remote_ip->getTextual();
        }


        $has_expected_agent_type = in_array($metadata['itemtype'], $CFG_GLPI['agent_types']);
        if ($deviceid === 'foo' || (!$has_expected_agent_type && !$aid)) {
            $input += [
                'items_id' => 0,
                'id' => 0
            ];
            $this->fields = $input;
            return 0;
        }

        $input = Sanitizer::sanitize($input);
        if ($aid) {
            $input['id'] = $aid;
            // We should not update itemtype in db if not an expected one
            if (!$has_expected_agent_type) {
                unset($input['itemtype']);
            }
            $this->update($input);
            // Don't keep linked item unless having expected agent type
            if (!$has_expected_agent_type) {
                $this->fields['items_id'] = 0;
                // But always keep itemtype for class instantiation
                $this->fields['itemtype'] = $metadata['itemtype'];
            }
        } else {
            $input['items_id'] = 0;
            $aid = $this->add($input);
        }

        return $aid;
    }

    /**
     * Prepare input for add and update
     *
     * @param array $input Input
     *
     * @return array|false
     */
    public function prepareInputs(array $input)
    {
        if ($this->isNewItem() && (!isset($input['deviceid']) || empty($input['deviceid']))) {
            Session::addMessageAfterRedirect(__('"deviceid" is mandatory!'), false, ERROR);
            return false;
        }
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        global $CFG_GLPI;

        if (isset($CFG_GLPI['threads_networkdiscovery']) && !isset($input['threads_networkdiscovery'])) {
            $input['threads_networkdiscovery'] = 0;
        }
        if (isset($CFG_GLPI['threads_networkinventory']) && !isset($input['threads_networkinventory'])) {
            $input['threads_networkinventory'] = 0;
        }
        if (isset($CFG_GLPI['timeout_networkdiscovery']) && !isset($input['timeout_networkdiscovery'])) {
            $input['timeout_networkdiscovery'] = 0;
        }
        if (isset($CFG_GLPI['timeout_networkinventory']) && !isset($input['timeout_networkinventory'])) {
            $input['timeout_networkinventory'] = 0;
        }

        return $this->prepareInputs($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInputs($input);
    }

    /**
     * Get item linked to current agent
     *
     * @return CommonDBTM
     */
    public function getLinkedItem(): CommonDBTM
    {
        $itemtype = $this->fields['itemtype'];
        $item = new $itemtype();
        $item->getFromDB($this->fields['items_id']);
        return $item;
    }

    /**
     * Guess possible addresses the agent should answer on
     *
     * @return array
     */
    public function guessAddresses(): array
    {
        global $DB;

        $addresses = [];

       //retrieve linked items
        $item = $this->getLinkedItem();
        if ((int)$item->getID() > 0) {
            $item_name = $item->getFriendlyName();
            $addresses[] = $item_name;

           //deviceid should contains machines name
            $matches = [];
            preg_match('/^(\s)+-\d{4}(-\d{2}){5}$/', $this->fields['deviceid'], $matches);
            if (isset($matches[1])) {
                if (!in_array($matches[1], $addresses)) {
                    $addresses[] = $matches[1];
                }
            }

           //append linked ips
            $ports_iterator = $DB->request([
                'SELECT' => ['ips.name', 'ips.version'],
                'FROM'   => NetworkPort::getTable() . ' AS netports',
                'WHERE'  => [
                    'netports.itemtype'  => $item->getType(),
                    'netports.items_id'  => $item->getID(),
                    'NOT'       => [
                        'OR'  => [
                            'AND' => [
                                'NOT' => [
                                    'netports.instantiation_type' => 'NULL'
                                ],
                                'netports.instantiation_type' => 'NetworkPortLocal'
                            ],
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
            foreach ($ports_iterator as $row) {
                if (!in_array($row['name'], $addresses)) {
                    if ($row['version'] == 4) {
                        $addresses[] = $row['name'];
                    } else {
                        //surrounds IPV6 with '[' and ']'
                        $addresses[] = "[" . $row['name'] . "]";
                    }
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

            foreach ($iterator as $row) {
                 $addresses[] = sprintf('%s.%s', $item_name, $row['name']);
            }
        }

        return $addresses;
    }

    /**
     * Get agent URLs
     *
     * @return array
     */
    public function getAgentURLs(): array
    {
        $addresses = $this->guessAddresses();
        $protocols = ['http', 'https'];
        $port = (int)$this->fields['port'];
        if ($port === 0) {
            $port = self::DEFAULT_PORT;
        }

        $urls = [];
        foreach ($protocols as $protocol) {
            foreach ($addresses as $address) {
                $urls[] = sprintf(
                    '%s://%s:%s',
                    $protocol,
                    $address,
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
    public function requestAgent($endpoint): Response
    {
        global $CFG_GLPI;

        if (self::$found_address !== false) {
            $addresses = [self::$found_address];
        } else {
            $addresses = $this->getAgentURLs();
        }

        $exception = null;
        $response = null;
        foreach ($addresses as $address) {
            $options = [
                'base_uri'        => $address,
                'connect_timeout' => self::TIMEOUT,
            ];

            // add proxy string if configured in glpi
            if (!empty($CFG_GLPI["proxy_name"])) {
                $proxy_creds      = !empty($CFG_GLPI["proxy_user"])
                ? $CFG_GLPI["proxy_user"] . ":" . (new GLPIKey())->decrypt($CFG_GLPI["proxy_passwd"]) . "@"
                : "";
                $proxy_string     = "http://{$proxy_creds}" . $CFG_GLPI['proxy_name'] . ":" . $CFG_GLPI['proxy_port'];
                $options['proxy'] = $proxy_string;
            }

            // init guzzle client with base options
            $httpClient = new Guzzle_Client($options);
            try {
                $response = $httpClient->request('GET', $endpoint, []);
                self::$found_address = $address;
                break;
            } catch (\GuzzleHttp\Exception\RequestException $exception) {
                // got an error response, we don't need to try other addresses
                break;
            } catch (\Throwable $exception) {
                // many addresses will be incorrect
            }
        }

        if ($response === null && $exception !== null) {
            // throw last exception on no response
            throw $exception;
        }

        return $response;
    }

    /**
     * Request status from agent
     *
     * @return array
     */
    public function requestStatus()
    {
        // must return json
        try {
            $response = $this->requestAgent('status');
            return $this->handleAgentResponse($response, self::ACTION_STATUS);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            ErrorHandler::getInstance()->handleException($e);
            // not authorized
            return ['answer' => __('Not allowed')];
        } catch (\Throwable $e) {
            // no response
            return ['answer' => __('Unknown')];
        }
    }

    /**
     * Request inventory from agent
     *
     * @return array
     */
    public function requestInventory()
    {
        // must return json
        try {
            $this->requestAgent('now');
            return $this->handleAgentResponse(new Response(), self::ACTION_INVENTORY);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            ErrorHandler::getInstance()->handleException($e);
            // not authorized
            return ['answer' => __('Not allowed')];
        } catch (\Throwable $e) {
            // no response
            return ['answer' => __('Unknown')];
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
    private function handleAgentResponse(Response $response, $request): array
    {
        $data = [];

        $raw_content = (string)$response->getBody();

        switch ($request) {
            case self::ACTION_STATUS:
                $data['answer'] = Sanitizer::encodeHtmlSpecialChars(preg_replace('/status: /', '', $raw_content));
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

    public static function getIcon()
    {
        return "ti ti-robot";
    }

    /**
     * Cron task: clean and do other defined actions when agent not have been contacted
     * the server since xx days
     *
     * @global object $DB, $PLUGIN_HOOKS
     * @param object $task
     * @return int
     *
     * @copyright 2010-2022 by the FusionInventory Development Team.
     */
    public static function cronCleanoldagents($task = null)
    {
        global $DB, $PLUGIN_HOOKS;

        $config = \Config::getConfigurationValues('inventory');

        $retention_time = $config['stale_agents_delay'] ?? 0;
        if ($retention_time <= 0) {
            return 0;
        }

        $total  = 0;
        $errors = 0;

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => self::getTable(),
            'WHERE' => [
                'last_contact' => ['<', new QueryExpression("date_add(now(), interval -" . $retention_time . " day)")]
            ]
        ]);

        foreach ($iterator as $data) {
            $agent = new self();
            if (!$agent->getFromDB($data['id'])) {
                $errors++;
                continue;
            }

            $item = is_a($agent->fields['itemtype'], CommonDBTM::class, true) ? new $agent->fields['itemtype']() : null;
            if (
                $item !== null
                && (
                    $item->getFromDB($agent->fields['items_id']) === false
                    || $item->fields['is_dynamic'] != 1
                )
            ) {
                $item = null;
            }

            $actions = importArrayFromDB($config['stale_agents_action']);
            foreach ($actions as $action) {
                switch ($action) {
                    case Conf::STALE_AGENT_ACTION_CLEAN:
                        //delete agents
                        if ($agent->delete($data)) {
                            $task->addVolume(1);
                            $total++;
                        } else {
                            $errors++;
                        }
                        break;
                    case Conf::STALE_AGENT_ACTION_STATUS:
                        if (isset($config['stale_agents_status']) && $item !== null) {
                            //change status of agents linked assets
                            $input = [
                                'id'        => $item->fields['id'],
                                'states_id' => $config['stale_agents_status'],
                                'is_dynamic' => 1
                            ];
                            if ($item->update($input)) {
                                $task->addVolume(1);
                                $total++;
                            } else {
                                $errors++;
                            }
                        }
                        break;
                    case Conf::STALE_AGENT_ACTION_TRASHBIN:
                        //put linked assets in trashbin
                        if ($item !== null) {
                            if ($item->delete(['id' => $item->fields['id']])) {
                                $task->addVolume(1);
                                $total++;
                            } else {
                                $errors++;
                            }
                        }
                        break;
                }
            }

            $plugin_actions = $PLUGIN_HOOKS[Hooks::STALE_AGENT_CONFIG] ?? [];
            /**
             * @var string $plugin
             * @phpstan-var array{label: string, item_action: boolean, render_callback: callable, action_callback: callable}[] $actions
             */
            foreach ($plugin_actions as $plugin => $actions) {
                if (is_array($actions) && Plugin::isPluginActive($plugin)) {
                    foreach ($actions as $action) {
                        if (!is_callable($action['action_callback'] ?? null)) {
                            trigger_error(
                                sprintf('Invalid plugin "%s" action callback for "%s" hook.', $plugin, Hooks::STALE_AGENT_CONFIG),
                                E_USER_WARNING
                            );
                            continue;
                        }
                        // Run the action
                        if ($action['action_callback']($agent, $config, $item)) {
                            $task->addVolume(1);
                            $total++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }
        }

        return $errors > 0 ? -1 : ($total > 0 ? 1 : 0);
    }
}
