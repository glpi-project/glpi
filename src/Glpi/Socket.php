<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi;

use Cable;
use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use HTMLTableCell;
use HTMLTableRow;
use Location;
use Log;
use NetworkPort;
use Notepad;
use Session;

/// Socket class
class Socket extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public static $checkParentRights  = self::DONT_CHECK_ITEM_RIGHTS;

    // From CommonDBTM
    public $dohistory          = true;
    public static $rightname          = 'cable_management';
    public $can_be_translated  = false;

    public const REAR    = 1;
    public const FRONT   = 2;
    public const BOTH    = 3;

    public static function getIcon()
    {
        return NetworkPort::getIcon();
    }

    public function canCreateItem(): bool
    {
        return Session::haveRight(static::$rightname, CREATE);
    }

    public function canPurgeItem(): bool
    {
        return Session::haveRight(static::$rightname, PURGE);
    }

    public function isEntityAssign()
    {
        return false;
    }

    public function maybeRecursive()
    {
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    /**
     * Print the version form
     *
     * @param integer $ID ID of the item
     * @param array $options
     *     - target for the Form
     *     - itemtype type of the item for add process
     *     - items_id ID of the item for add process
     *
     * @return boolean true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        $itemtype = null;
        if (!empty($options['_add_fromitem'])) {
            $itemtype = $options['_add_fromitem']['_from_itemtype'];
        } elseif (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
        }

        $items_id = null;
        if ($itemtype !== null) {
            if ($ID > 0) {
                $this->check($ID, READ);
                $items_id = $this->fields['items_id'];
            } else {
                $this->check(-1, CREATE, $options);
                if (isset($options['_add_fromitem'])) {
                    $items_id = $options['_add_fromitem']['_from_items_id'];
                }
            }
        }

        TemplateRenderer::getInstance()->display('pages/assets/socket.html.twig', [
            'item'   => $this,
            'params' => $options,
            'parent' => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ],
        ]);
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['items_id'])) {
            unset($input['itemtype'], $input['items_id']);
        }
        $input = $this->retrievedataFromNetworkPort($input);
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['items_id']) && empty($input['items_id'])) {
            unset($input['itemtype'], $input['items_id']);
        }
        $input = $this->retrievedataFromNetworkPort($input);
        return $input;
    }

    public function retrievedataFromNetworkPort($input)
    {
        // get position from networkport if needed
        if ((isset($input["networkports_id"]) && $input["networkports_id"] > 0) && $input["position"] == 'auto') {
            $networkport = new NetworkPort();
            $networkport->getFromDB($input["networkports_id"]);
            $input['position'] = $networkport->fields['logical_number'];
        }

        // get name from networkport if needed
        if ((isset($input["networkports_id"]) && $input["networkports_id"] > 0) && empty($input["name"])) {
            $networkport = new NetworkPort();
            $networkport->getFromDB($input["networkports_id"]);
            $input['name'] = $networkport->fields['name'];
        }

        return $input;
    }

    /**
     * Get possible itemtype
     * @return array Array of types
     **/
    public static function getSocketLinkTypes()
    {
        global $CFG_GLPI;
        $values = [];
        foreach ($CFG_GLPI["socket_types"] as $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                $values[$itemtype] = $item::getTypeName();
            }
        }
        return $values;
    }

    /**
     * Get all Socket already linked to Cable for given asset
     * @return array Array of linked sockets
     **/
    public static function getSocketAlreadyLinked(string $itemtype, int $items_id): array
    {
        global $DB;
        $already_use = [];
        $sub_query = [];

        $sub_query[] = new QuerySubQuery([
            'SELECT' => ['sockets.id AS socket_id'],
            'FROM'   => self::getTable() . ' AS sockets',
            'LEFT JOIN'   => [
                Cable::getTable() . ' AS cables' => [
                    'ON'  => [
                        'cables'  => 'sockets_id_endpoint_a',
                        'sockets'  => 'id',
                    ],
                ],
            ],
            'WHERE'  => [
                'NOT' => [
                    'cables.sockets_id_endpoint_a' => 'NULL',
                ],
                'sockets.itemtype' => $itemtype,
                'sockets.items_id' => $items_id,
            ],
        ]);

        $sub_query[] = new QuerySubQuery([
            'SELECT' => ['sockets.id AS socket_id'],
            'FROM'   => self::getTable() . ' AS sockets',
            'LEFT JOIN'   => [
                Cable::getTable() . ' AS cables' => [
                    'ON'  => [
                        'cables'  => 'sockets_id_endpoint_b',
                        'sockets'  => 'id',
                    ],
                ],
            ],
            'WHERE'  => [
                'NOT' => [
                    'cables.sockets_id_endpoint_b' => 'NULL',
                ],
                'sockets.itemtype' => $itemtype,
                'sockets.items_id' => $items_id,
            ],
        ]);

        $sockets_iterator = $DB->request([
            'FROM' => new QueryUnion($sub_query),
        ]);

        foreach ($sockets_iterator as $row) {
            $already_use[$row['socket_id']] = $row['socket_id'];
        }

        return $already_use;
    }

    /**
     * Dropdown of Wiring Side
     *
     * @param string $name   select name
     * @param array  $options possible options:
     *    - value       : integer / preselected value (default 0)
     *    - display
     * @return string ID of the select
     **/
    public static function dropdownWiringSide($name, $options = [], bool $full = false)
    {
        $params = [
            'value'     => 0,
            'display'   => true,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        return Dropdown::showFromArray($name, self::getSides($full), $params);
    }

    /**
     * Get sides
     * @return array Array of types
     **/
    public static function getSides(bool $full = false)
    {
        $data =  [
            self::REAR   => __('Rear'),
            self::FRONT  => __('Front'),
        ];

        if ($full) {
            $data[self::BOTH] =  __('Create both');
        }

        return $data;
    }

    public function post_getEmpty()
    {
        $this->fields['itemtype'] = 'Computer';
        $this->fields['position'] = -1;
    }

    /**
     * Get wiring side name
     *
     * @since 0.84
     *
     * @param integer $value     status ID
     **/
    public static function getWiringSideName($value)
    {
        $tab  = static::getSides();
        // Return $value if not defined
        return ($tab[$value] ?? $value);
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Socket', 'Sockets', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', Cable::class, self::class];
    }

    public function rawSearchOptions()
    {
        $tab  = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '5',
            'table'              => Socket::getTable(),
            'field'              => 'position',
            'name'               => __('Position'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => SocketModel::getTable(),
            'field'              => 'name',
            'name'               => SocketModel::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => Socket::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'socket_types',
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => Socket::getTable(),
            'field'              => 'wiring_side',
            'name'               => __('Wiring side'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        foreach ($tab as &$t) {
            if ($t['id'] == 3) {
                $t['datatype']      = 'itemlink';
                break;
            }
        }

        return $tab;
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '1310',
            'table'              => Socket::getTable(),
            'field'              => 'name',
            'name'               => Socket::getTypeName(0),
            'searchtype'         => 'equals',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '1311',
            'table'              => SocketModel::getTable(),
            'field'              => 'name',
            'name'               => SocketModel::getTypeName(0),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => self::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '1312',
            'table'              => Socket::getTable(),
            'field'              => 'wiring_side',
            'name'               => __('Wiring side'),
            'searchtype'         => 'equals',
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
            'datatype'           => 'specific',
        ];

        return $tab;
    }

    /**
     * @since 0.84
     *
     * @param $field
     * @param $name            (default '')
     * @param $values          (default '')
     * @param array $options   array
     *
     * @return string
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'items_id':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    $options['name']  = $name;
                    $options['value'] = $values[$field];
                    return Dropdown::show($values['itemtype'], $options);
                }
                break;

            case 'wiring_side':
                return self::dropdownWiringSide($name, $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * @since 0.84
     *
     * @param $field
     * @param $values
     * @param array $options
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'items_id':
                if (isset($values['itemtype']) && is_a($values['itemtype'], CommonDBTM::class, true)) {
                    if ($values[$field] > 0) {
                        $item = new $values['itemtype']();
                        $item->getFromDB($values[$field]);
                        return "<a href='" . \htmlescape($item->getLinkURL()) . "'>" . \htmlescape($item->fields['name']) . "</a>";
                    }
                }
                return ' ';
            case 'wiring_side':
                return \htmlescape(self::getWiringSideName($values[$field]));
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * check if a socket already exists (before import)
     *
     * @param array $input Array of values to import (name, locations_id)
     *
     * @return integer the ID of the new (or -1 if not found)
     **/
    public function findID(array &$input)
    {
        global $DB;

        if (!empty($input["name"])) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'name'         => $input['name'],
                    'locations_id' => $input["locations_id"] ?? 0,
                ],
            ]);

            // Check twin :
            if (count($iterator)) {
                $result = $iterator->current();
                return $result['id'];
            }
        }
        return -1;
    }

    public function post_addItem()
    {
        $parent = $this->fields['locations_id'];
        if ($parent) {
            $changes[0] = '0';
            $changes[1] = '';
            $changes[2] = $this->getNameID(['forceid' => true]);
            Log::history($parent, 'Location', $changes, $this->getType(), Log::HISTORY_ADD_SUBITEM);
        }

        $this->cleanIfStealNetworkPort();
    }

    public function post_updateItem($history = true)
    {
        $this->cleanIfStealNetworkPort();
    }

    public function cleanIfStealNetworkPort()
    {
        global $DB;
        // find other socket with same networkport and reset it
        if ($this->fields['networkports_id'] > 0) {
            $iter = $DB->request([
                'SELECT' => 'id',
                'FROM'  => getTableForItemType(self::getType()),
                'WHERE' => [
                    'networkports_id' => $this->fields['networkports_id'],
                    ['NOT' => ['id' => $this->fields['id']]],
                ],
            ]);

            foreach (self::getFromIter($iter) as $socket) {
                $socket->fields['networkports_id'] = 0;
                $socket->update($socket->fields);
            }
        }
    }

    public function post_deleteFromDB()
    {
        $parent = $this->fields['locations_id'];
        if ($parent) {
            $changes[0] = '0';
            $changes[1] = $this->getNameID(['forceid' => true]);
            $changes[2] = '';
            Log::history($parent, 'Location', $changes, $this->getType(), Log::HISTORY_DELETE_SUBITEM);
        }
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;
        if (!$withtemplate) {
            $nb = 0;
            switch (get_class($item)) {
                case Location::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  countElementsInTable(
                            $this->getTable(),
                            ['locations_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                default:
                    /** @var CommonDBTM $item */
                    if (in_array($item->getType(), $CFG_GLPI['socket_types'])) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb =  countElementsInTable(
                                $this->getTable(),
                                ['itemtype' => $item->getType(),
                                    'items_id' => $item->getID(),
                                ]
                            );
                        }
                        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                    }
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;
        if ($item instanceof Location) {
            return self::showForLocation($item);
        } elseif (in_array($item::class, $CFG_GLPI['socket_types'])) {
            /** @var CommonDBTM $item */
            return self::showListForItem($item);
        }
        return false;
    }

    /**
     * Print the HTML array of the Socket associated to a Location
     *
     * @param CommonDBTM $item
     *
     * @return bool
     **/
    public static function showListForItem($item): bool
    {

        global $DB;

        $canedit = self::canUpdate();

        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }
        $rand = mt_rand();

        // Link to open a new socket
        if ($item->getID() && self::canCreate()) {
            $twig_params = [
                'socket_itemtypes' => self::getSocketLinkTypes(),
                '_add_fromitem'    => [
                    '_from_itemtype' => $item::class,
                    '_from_items_id' => $item->getID(),
                ],
            ];
            TemplateRenderer::getInstance()->display('pages/assets/socket_short_form.html.twig', $twig_params);
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'   => $item->getType(),
                'items_id'   => $item->getID(),
            ],
        ]);

        $entries = [];
        $socket = new Socket();
        $networkport = new NetworkPort();
        $cable = new Cable();
        foreach ($iterator as $data) {
            $socket->getFromDB($data['id']);

            $socket_name = $canedit
                ? sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape($socket->getLinkURL()),
                    htmlescape($socket->fields['name'])
                )
                : htmlescape($socket->fields['name']);
            $netport_name = '';
            if ($networkport->getFromDB($socket->fields["networkports_id"])) {
                $netport_name = sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape($networkport->getLinkURL()),
                    htmlescape($networkport->fields['name'])
                );
            }
            $has_cable = $cable->getFromDBByCrit([
                'OR' => [
                    'sockets_id_endpoint_a' => $socket->fields["id"],
                    'sockets_id_endpoint_b' => $socket->fields["id"],
                ],
            ]);

            $cable_name = $has_cable
                ? sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape($cable->getLinkURL()),
                    htmlescape($cable->getName())
                )
                : '';
            $itemtype = '';
            $item_id = '';
            if ($has_cable) {
                if (
                    $cable->fields['itemtype_endpoint_a'] === $item->getType()
                    && $cable->fields['items_id_endpoint_a'] === $item->getID()
                ) {
                    $itemtype = $cable->fields['itemtype_endpoint_b'];
                    $item_id = $cable->fields['items_id_endpoint_b'];
                } else {
                    $itemtype = $cable->fields['itemtype_endpoint_a'];
                    $item_id = $cable->fields['items_id_endpoint_a'];
                }
            }
            $endpoint = getItemForItemtype($itemtype);
            if ($endpoint !== false && $item_id !== 0 && $endpoint->getFromDB($item_id)) {
                $itemtype_label = $endpoint::getType();
                $item_label = sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape($endpoint->getLinkURL()),
                    htmlescape($endpoint->getName())
                );
            } else {
                $itemtype_label = '';
                $item_label = '';
            }
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $socket->getID(),
                'name'     => $socket_name,
                'position' => $socket->fields["position"],
                'socketmodels_id' => Dropdown::getDropdownName(SocketModel::getTable(), $socket->fields["socketmodels_id"]),
                'wiring_side' => self::getWiringSideName($socket->fields["wiring_side"]),
                'networkports_id' => $netport_name,
                'cable' => $cable_name,
                'endpoint_itemtype' => $itemtype_label,
                'endpoint_itemname' => $item_label,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
                'position' => __('Position'),
                'socketmodels_id' => SocketModel::getTypeName(1),
                'wiring_side' => __('Wiring side'),
                'networkports_id' => _n('Network port', 'Network ports', 1),
                'cable' => Cable::getTypeName(1),
                'endpoint_itemtype' => __('Itemtype'),
                'endpoint_itemname' => __('Item Name'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'networkports_id' => 'raw_html',
                'cable' => 'raw_html',
                'endpoint_itemname' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container' => 'mass' . str_replace('\\', '', self::class) . $rand,
                'specific_actions' => [
                    'update' => _x('button', 'Update'),
                    'purge'  => _x('button', 'Delete permanently'),
                ],
            ],
        ]);

        return true;
    }

    /**
     * Print the HTML array of the Socket associated to a Location
     *
     * @param Location $item
     *
     * @return bool
     **/
    public static function showForLocation(Location $item): bool
    {
        global $DB;

        $ID       = $item->getField('id');
        $item->check($ID, READ);
        $canedit  = $item->canEdit($ID);

        $start       = (int) ($_GET["start"] ?? 0);
        $sort        = $_GET["sort"] ?? '';
        $order       = strtoupper($_GET["order"] ?? '');
        if ($sort === '') {
            $sort = 'name';
        }
        if ($order === '') {
            $order = 'ASC';
        }
        $rand = mt_rand();
        $number = countElementsInTable('glpi_sockets', ['locations_id' => $ID]);

        if ($canedit) {
            $socket_itemtypes = array_keys(self::getSocketLinkTypes());
            $twig_params = [
                'socket_itemtypes' => $socket_itemtypes,
                '_add_fromitem'    => [
                    '_from_itemtype' => Location::class,
                    '_from_items_id' => $ID,
                ],
            ];
            TemplateRenderer::getInstance()->display('pages/assets/socket_short_form.html.twig', $twig_params);
        }

        $entries = [];

        $it = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => [
                'locations_id' => $ID,
            ],
            'ORDER' => "$sort $order",
            'START' => $start,
            'LIMIT' => $_SESSION['glpilist_limit'],
        ]);

        foreach ($it as $data) {
            $name = sprintf(
                '<a href="%s">%s</a>',
                htmlescape(self::getFormURLWithID($data['id'])),
                htmlescape($data['name'])
            );

            $socketmodel = new SocketModel();
            $socketmodel->getFromDB($data['socketmodels_id']);
            $link = '';
            if (isset($data['itemtype']) && class_exists($data['itemtype']) && is_a($data['itemtype'], CommonDBTM::class, true)) {
                $itemtype = $data['itemtype'];
                $asset = new $itemtype();
                if ($asset->getFromDB($data['items_id'])) {
                    $link = $asset->getLink();
                }
            }
            $networkport = new NetworkPort();
            $networkport->getFromDB($data['networkports_id']);

            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'name'     => $name,
                'socketmodels_id' => $socketmodel->getLink(),
                'asset' => $link,
                'networkports_id' => $networkport->getLink(),
                'wiring_side' => self::getWiringSideName($data['wiring_side']),
                'comment' => $data['comment'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'sort' => $sort,
            'order' => $order,
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => __('Name'),
                'socketmodels_id' => SocketModel::getTypeName(1),
                'asset' => _n('Asset', 'Assets', 1),
                'networkports_id' => NetworkPort::getTypeName(1),
                'wiring_side' => __('Wiring side'),
                'comment' => _n('Comment', 'Comments', Session::getPluralNumber()),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'socketmodels_id' => 'raw_html',
                'asset' => 'raw_html',
                'networkports_id' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $number,
            'filtered_number' => $number,
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], $number),
                'container' => 'mass' . str_replace('\\', '', self::class) . $rand,
                'specific_actions' => [
                    'purge' => _x('button', 'Delete permanently'),
                ],
            ],
        ]);

        return true;
    }

    /**
     * @since 0.84
     *
     * @param $row             HTMLTableRow object (default NULL)
     * @param $item            CommonDBTM object (default NULL)
     * @param $father          HTMLTableCell object (default NULL)
     * @param $options   array
     **/
    public static function getHTMLTableCellsForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        $options = []
    ) {

        $column_name = self::class;

        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        $row->addCell(
            $row->getHeaderByName($column_name),
            htmlescape(Dropdown::getDropdownName("glpi_sockets", $item->fields["sockets_id"])),
            $father
        );
    }
}
