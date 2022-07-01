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

namespace Glpi\Inventory;

use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Agent\Communication\Headers\Common;
use Glpi\Plugin\Hooks;
use Plugin;
use Unmanaged;

/**
 * Handle inventory request
 * Both XML (legacy) and JSON inventory formats are supported.
 *
 * @see https://github.com/glpi-project/inventory_format/blob/master/inventory.schema.json
 */
class Request extends AbstractRequest
{
    /** @var Inventory */
    private $inventory;

    /** @var bool */
    private bool $is_discovery = false;

    /** @var string */
    private string $network_inventory_mode;

    protected function initHeaders(): Common
    {
        return new Common();
    }


    /**
     * Handle Query
     *
     * @param string $query   Query mode (one of self::*_QUERY or self::*_ACTION)
     * @param mixed  $content Contents, optional
     *
     * @return boolean
     */
    protected function handleAction($query, $content = null): bool
    {
        $this->query = $query;
        switch ($query) {
            case self::GET_PARAMS:
                $this->getParams($content);
                break;
            case self::CONTACT_ACTION:
                $this->contact($content);
                break;
            case self::PROLOG_QUERY:
                $this->prolog($content);
                break;
            case self::INVENT_QUERY:
            case self::INVENT_ACTION:
                $this->inventory($content);
                break;
            case self::NETDISCOVERY_ACTION:
                $this->networkDiscovery($content);
                break;
            case self::SNMP_QUERY:
            case self::OLD_SNMP_QUERY:
            case self::NETINV_ACTION:
                $this->networkInventory($content);
                break;
            case self::REGISTER_ACTION:
            case self::CONFIG_ACTION:
            case self::ESX_ACTION:
            case self::COLLECT_ACTION:
            case self::DEPLOY_ACTION:
            case self::WOL_ACTION:
            default:
                $this->addError("Query '$query' is not supported.", 501);
                return false;
        }
        return true;
    }

    /**
     * Handle Task
     *
     * @param string $task  Task (one of self::*_TASK)
     *
     * @return array
     */
    protected function handleTask($task): array
    {
        $params = [
            'options' => [
                'response' => []
            ],
            'item' => $this->inventory->getAgent(),
        ];
        switch ($task) {
            case self::INVENT_TASK:
                return $this->handleInventoryTask($params);
            case self::NETDISCOVERY_TASK:
                return $this->handleNetDiscoveryTask($params);
            case self::NETINV_TASK:
                return $this->handleNetInventoryTask($params);
            case self::ESX_TASK:
                return $this->handleESXTask($params);
            case self::COLLECT_TASK:
                return $this->handleCollectTask($params);
            case self::DEPLOY_TASK:
                return $this->handleDeployTask($params);
            case self::WOL_TASK:
                return $this->handleWakeOnLanTask($params);
            case self::REMOTEINV_TASK:
                return $this->handleRemoteInventoryTask($params);
            default:
                $this->addError("Task '$task' is not supported.", 400);
                return [];
        }
    }


    /**
     * Handle agent GETPARAMS request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function getParams($data)
    {
        global $CFG_GLPI;

        $this->inventory = new Inventory();
        $this->inventory->contact($data);

        $response = [
            'expiration' => $CFG_GLPI['inventory_frequency'] ?? self::DEFAULT_FREQUENCY,
            'status'     => 'ok'
        ];

        $params = [
            'options' => [
                'content' => $data,
                'response' => $response
            ],
            'item' => $this->inventory->getAgent()
        ];

        $params = Plugin::doHookFunction(Hooks::INVENTORY_GET_PARAMS, $params);

        $this->addToResponse($params['options']['response']);
    }


    /**
     * Handle agent prolog request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function prolog($data)
    {
        global $CFG_GLPI;

        if ($this->headers->hasHeader('GLPI-Agent-ID')) {
            $this->setMode(self::JSON_MODE);
            $response = [
                'expiration' => $CFG_GLPI['inventory_frequency'] ?? self::DEFAULT_FREQUENCY,
                'status'     => 'ok'
            ];
        } else {
            $response = [
                'PROLOG_FREQ'  => self::DEFAULT_FREQUENCY,
                'RESPONSE'     => 'SEND'
            ];
        }

        $hook_params = [
            'mode' => $this->getMode(),
            'deviceid' => $this->getDeviceID(),
            'response' => $response
        ];
        $hook_response = Plugin::doHookFunction(
            Hooks::PROLOG_RESPONSE,
            $hook_params
        );

        $response = $hook_response['response'];

        $this->addToResponse($response);
    }


    /**
     * Handle agent network discovery request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function networkDiscovery($data)
    {
        $this->network_inventory_mode = Hooks::NETWORK_DISCOVERY;
        $this->is_discovery = true;
        $this->network($data);
    }


    /**
     * Handle agent network inventory request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function networkInventory($data)
    {
        $this->network_inventory_mode = Hooks::NETWORK_INVENTORY;
        $this->network($data);
    }

    /**
     * Handle agent network inventory request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function network($data)
    {
        $this->inventory = new Inventory();
        $this->inventory
            ->setDiscovery($this->isDiscovery())
            ->setData($data, $this->getMode());

        $response = [];
        $hook_params = [
            'mode' => $this->getMode(),
            'inventory' => $this->inventory,
            'deviceid' => $this->getDeviceID(),
            'response' => $response,
            'query' => $this->query
        ];

        $hook_response = Plugin::doHookFunction(
            $this->network_inventory_mode,
            $hook_params
        );

        if ($hook_response == $hook_params) {
            //no hook, use native capabilities
            $this->inventory($data);
        } else {
            //try to use hook response
            if (isset($hook_response['response']) && count($hook_response['response'])) {
                $this->addToResponse($response);
            } else if (isset($hook_response['errors']) && count($hook_response['errors'])) {
                $this->addError($hook_response['errors'], 400);
            } else {
                //nothing expected happens; this is an error
                $this->addError("Query '" . $this->query . "' is not supported.", 400);
            }
        }
    }

    /**
     * Handle agent CONTACT request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function contact($data)
    {
        global $CFG_GLPI;

        $this->inventory = new Inventory();
        $this->inventory->contact($data);

        $response = [
            'expiration' => $CFG_GLPI['inventory_frequency'] ?? self::DEFAULT_FREQUENCY,
            'status'     => 'ok'
        ];

        //For the moment it's the Agent who informs us about the active tasks
        $raw_data = $this->inventory->getRawData();
        if ($raw_data !== null && property_exists($raw_data, 'enabled-tasks')) {
            foreach ($raw_data->{'enabled-tasks'} as $task) {
                $handle = $this->handleTask($task);
                if (is_array($handle) && count($handle)) {
                    // Insert related task information under tasks list property
                    $response['tasks'][$task] = $handle;
                } else {
                    // Task is not supported, disable it and add unsupported message in response
                    $this->addToResponse([
                        "message" => "$task task not supported",
                        "disabled" => $task
                    ]);
                }
            }
        }

        $this->addToResponse($response);
    }

    /**
     * Handle agent inventory request
     *
     * @param mixed $data Inventory input following specs
     *
     * @return void
     */
    public function inventory($data)
    {
        global $CFG_GLPI;

        if ($this->isDiscovery()) {
            //force "partial" mode on network discoveries.
            $data->partial = true;
        }

        $this->inventory = new Inventory();
        $this->inventory
            ->setDiscovery($this->isDiscovery())
            ->setRequestQuery($this->query)
            ->setData($data, $this->getMode());

        if (!$this->inventory->inError()) {
            $this->inventory->doInventory($this->test_rules);
        }

        if ($this->inventory->inError()) {
            foreach ($this->inventory->getErrors() as $error) {
                $this->addError($error, 500);
            }
        } else {
            if ($this->headers->hasHeader('GLPI-Agent-ID')) {
                $response = [
                    'expiration' => $CFG_GLPI['inventory_frequency'] ?? self::DEFAULT_FREQUENCY,
                    'status'     => 'ok'
                ];
            } else {
                $response = ['RESPONSE' => 'SEND'];
            }

            $this->addToResponse($response);
        }
    }

    /**
     * Handle agent enabled inventory task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleInventoryTask(array $params): array
    {
        // Preset response as GLPI supports native inventory by default
        $params['options']['response'][self::INVENT_TASK] = [
            'server' => 'glpi',
            'version' => GLPI_VERSION
        ];
        $params = Plugin::doHookFunction(Hooks::HANDLE_INVENTORY_TASK, $params);

        // Return inventory task support
        return $params['options']['response'][self::INVENT_TASK] ?? [];
    }

    /**
     * Handle agent enabled netdiscovery task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleNetDiscoveryTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_NETDISCOVERY_TASK, $params);

        return $params['options']['response'][self::NETDISCOVERY_TASK] ?? [];
    }

    /**
     * Handle agent enabled netinventory task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleNetInventoryTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_NETINVENTORY_TASK, $params);

        return $params['options']['response'][self::NETINV_TASK] ?? [];
    }

    /**
     * Handle agent enabled ESX task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleESXTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_ESX_TASK, $params);

        return $params['options']['response'][self::ESX_TASK] ?? [];
    }

    /**
     * Handle agent enabled collect task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleCollectTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_COLLECT_TASK, $params);

        return $params['options']['response'][self::COLLECT_TASK] ?? [];
    }

    /**
     * Handle agent enabled deploy task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleDeployTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_DEPLOY_TASK, $params);

        return $params['options']['response'][self::DEPLOY_TASK] ?? [];
    }

    /**
     * Handle agent enabled wakeonlan task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleWakeOnLanTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_WAKEONLAN_TASK, $params);

        return $params['options']['response'][self::WOL_TASK] ?? [];
    }

    /**
     * Handle agent enabled remoteinventory task support on contact request
     *
     * @param array $params Required hooks params
     *
     * @return array
     */
    public function handleRemoteInventoryTask(array $params): array
    {
        $params = Plugin::doHookFunction(Hooks::HANDLE_REMOTEINV_TASK, $params);

        return $params['options']['response'][self::REMOTEINV_TASK] ?? [];
    }

    /**
     * Get inventory request status
     *
     * @return array
     */
    public function getInventoryStatus(): array
    {
        $items = $this->inventory->getItems();
        $status = [
            'metadata' => $this->inventory->getMetadata(),
            'items'    => $items
        ];

        if (count($items) == 1) {
            $item = $items[0];
            $status += [
                'itemtype' => $item->getType(),
                'items_id' => $item->fields['id']
            ];
        } else if (count($items)) {
            // Defines 'itemtype' only if all items has same type
            $itemtype = null;
            foreach ($items as $item) {
                if ($itemtype === null && $item->getType() != Unmanaged::class) {
                    $itemtype = $item->getType();
                } else if ($itemtype !== $item->getType()) {
                    $itemtype = false;
                    break;
                }
            }
            if ($itemtype) {
                $status['itemtype'] = $itemtype;
            }
        }

        return $status;
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function isDiscovery(): bool
    {
        return $this->is_discovery;
    }
}
