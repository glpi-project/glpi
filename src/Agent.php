<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
use Glpi\Application\View\TemplateRenderer;
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
    public static $rightname = 'computer';
   //static $rightname = 'inventory';

    private static $found_adress = false;

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
                'id'            => '15',
                'table'         => $this->getTable(),
                'field'         => 'name',
                'datatype'      => 'itemlink',
                'name'          => _n('Item', 'Items', 1),
                'joinparams'    => [
                    'jointype' => 'itemtype_item'
                ]
            ]
        ];

        return $tab;
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
            'agenttypes_id' => $atype->fields['id'],
            'itemtype'     => $metadata['itemtype'] ?? 'Computer'
        ];

        if (isset($metadata['provider']['version'])) {
            $input['version'] = $metadata['provider']['version'];
        }

        if (isset($metadata['tag'])) {
            $input['tag'] = $metadata['tag'];
        }

        if ($deviceid === 'foo' || !in_array($metadata['itemtype'], $CFG_GLPI['agent_types'])) {
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
     * Guess possible adresses the agent should answer on
     *
     * @return array
     */
    public function guessAddresses(): array
    {
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
                if (!in_array($row['name'], $adresses)) {
                    if ($row['version'] == 4) {
                        $adresses[] = $row['name'];
                    } else {
                        //surrounds IPV6 with '[' and ']'
                        $adresses[] = "[" . $row['name'] . "]";
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
    public function getAgentURLs(): array
    {
        $adresses = $this->guessAddresses();
        $protocols = ['http', 'https'];
        $port = (int)$this->fields['port'];
        if ($port === 0) {
            $port = self::DEFAULT_PORT;
        }

        $urls = [];
        foreach ($protocols as $protocol) {
            foreach ($adresses as $address) {
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

        if (self::$found_adress !== false) {
            $adresses = [self::$found_adress];
        } else {
            $adresses = $this->getAgentURLs();
        }

        $response = null;
        foreach ($adresses as $adress) {
            $options = [
                'base_uri'        => sprintf('%s/%s', $adress, $endpoint),
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
                self::$found_adress = $adress;
                break;
            } catch (Exception $e) {
                //many adresses will be incorrect
                $cs = true;
            }
        }

        if (!$response) {
            // throw last exception on no response
            throw $e;
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
       //must return json
        try {
            $response = $this->requestAgent('status');
            return $this->handleAgentResponse($response, self::ACTION_STATUS);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            ErrorHandler::getInstance()->handleException($e);
           //not authorized
            return ['answer' => __('Not allowed')];
        } catch (Exception $e) {
           //no response
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
       //must return json
        try {
            $this->requestAgent('now');
            return $this->handleAgentResponse(new Response(), self::ACTION_INVENTORY);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            ErrorHandler::getInstance()->handleException($e);
           //not authorized
            return ['answer' => __('Not allowed')];
        } catch (Exception $e) {
           //no response
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
}
