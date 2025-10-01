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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Socket;
use Glpi\Toolbox\ArrayPathAccessor;

/**
 * NetworkPortInstantiation class
 *
 * Represents the type of given network port. As such, its ID field is the same one than the ID
 * of the network port it instantiates. This class don't have any table associated. It just
 * provides usefull and default methods for the instantiations.
 * Several kind of instanciations are available for a given port :
 *    - NetworkPortLocal
 *    - NetworkPortEthernet
 *    - NetworkPortWifi
 *    - NetworkPortAggregate
 *    - NetworkPortAlias
 *
 * @since 0.84
 *
 **/
class NetworkPortInstantiation extends CommonDBChild
{
    // From CommonDBTM
    public $auto_message_on_action   = false;

    // From CommonDBChild
    public static $itemtype       = 'NetworkPort';
    public static $items_id       = 'networkports_id';
    public $dohistory             = false;

    // Instantiation properties
    public $canHaveVLAN           = true;
    public $canHaveVirtualPort    = true;
    public $haveMAC               = true;

    public static function getIndexName()
    {
        return 'networkports_id';
    }

    /**
     * Show the instanciation element for the form of the NetworkPort
     * By default, just print that there is no parameter for this type of NetworkPort
     *
     * @param NetworkPort $netport         the port that owns this instantiation
     *                                     (usefull, for instance to get network port attributs
     * @param array       $options         array of options given to NetworkPort::showForm
     * @param array       $recursiveItems  list of the items on which this port is attached
     **/
    public function showInstantiationForm(NetworkPort $netport, $options, $recursiveItems)
    {
        echo "<div class='alert alert-info'>" . __s('No options available for this port type.') . "</div>";
    }

    public function prepareInput($input)
    {
        // Try to get mac address from the instantiation ...
        if (!empty($input['mac'])) {
            $input['mac'] = strtolower($input['mac']);
        }
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        return parent::prepareInputForAdd($this->prepareInput($input));
    }

    public function prepareInputForUpdate($input)
    {
        return parent::prepareInputForUpdate($this->prepareInput($input));
    }

    public function post_addItem()
    {
        $this->manageSocket();
    }

    public function post_updateItem($history = true)
    {
        $this->manageSocket();
    }

    public function manageSocket()
    {
        // add link to define
        if (isset($this->input['sockets_id']) && $this->input['sockets_id'] > 0) {
            $networkport = new NetworkPort();
            if ($networkport->getFromDB($this->fields['networkports_id'])) {
                $socket = new Socket();
                $socket->getFromDB($this->input['sockets_id']);
                $socket->update([
                    "id"              => $socket->getID(),
                    "itemtype"        => $networkport->fields['itemtype'],
                    "name"            => $socket->fields['name'],
                    "position"        => $networkport->fields['logical_number'],
                    "items_id"        => $networkport->fields['items_id'],
                    "networkports_id" => $this->fields['networkports_id'],
                ]);
            }
        } else {
            // Retrieve the associated socket to disconnect it from the NetworkPortEthernet
            $socket = new Socket();
            if ($socket->getFromDBByCrit(["networkports_id" => $this->fields['networkports_id']])) {
                $socket->update([
                    "id" => $socket->getID(),
                    "networkports_id" => 0,
                ]);
            }
        }
    }

    /**
     * Get all NetworkPort and NetworkEquipments that have a specific MAC address
     *
     * @param string  $mac              address to search
     * @param boolean $wildcard_search  true if we search with wildcard (false by default)
     *
     * @return array  each value of the array (corresponding to one NetworkPort) is an array of the
     *                items from the master item to the NetworkPort
     **/
    public static function getItemsByMac($mac, $wildcard_search = false)
    {
        global $DB;

        $mac = strtolower($mac);
        if ($wildcard_search) {
            $count = 0;
            $mac = str_replace('*', '%', $mac, $count);
            if ($count === 0) {
                $mac = '%' . $mac . '%';
            }
            $relation = ['LIKE', $mac];
        } else {
            $relation = $mac;
        }

        $macItemWithItems = [];

        $netport = new NetworkPort();

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => NetworkPort::getTable(),
            'WHERE'  => ['mac' => $relation],
        ]);

        foreach ($iterator as $element) {
            if ($netport->getFromDB($element['id'])) {
                $macItemWithItems[] = array_merge(
                    array_reverse($netport->recursivelyGetItems()),
                    [clone $netport]
                );
            }
        }

        return $macItemWithItems;
    }

    /**
     * Get an Object ID by its MAC address (only if one result is found in the entity)
     *
     * @param string  $value   the mac address
     * @param integer $entity  the entity to look for
     *
     * @return array containing the object ID
     *         or an empty array is no value of serverals ID where found
     **/
    public static function getUniqueItemByMac($value, $entity)
    {

        $macs_with_items = self::getItemsByMac($value);
        if (count($macs_with_items)) {
            foreach ($macs_with_items as $key => $tab) {
                if (
                    isset($tab[0])
                    && ($tab[0]->getEntityID() != $entity
                    || $tab[0]->isDeleted()
                    || $tab[0]->isTemplate())
                ) {
                    unset($macs_with_items[$key]);
                }
            }
        }

        if (count($macs_with_items)) {
            // Get the first item that is matching entity
            foreach ($macs_with_items as $items) {
                foreach ($items as $item) {
                    if ($item->getEntityID() == $entity) {
                        $result = ["id"       => $item->getID(),
                            "itemtype" => $item->getType(),
                        ];
                        unset($macs_with_items);
                        return $result;
                    }
                }
            }
        }
        return [];
    }


    /**
     * In case of NetworkPort attached to a network card, list the fields that must be duplicate
     * from the network card to the network port (mac address, port type, ...)
     *
     * @return array Array with SQL field (for instance : device.type) => form field (type)
     **/
    public function getNetworkCardInterestingFields()
    {
        return [];
    }

    /**
     * Select which network card to attach to the current NetworkPort (for the moment, only ethernet
     * and wifi ports). Whenever a card is attached, its information (mac, type, ...) are
     * autmatically set to the required field.
     *
     * @param NetworkPort $netport   NetworkPort object :the port that owns this instantiation
     *                               (usefull, for instance to get network port attributs
     * @param array $options         array of options given to NetworkPort::showForm
     * @param array $recursiveItems  list of the items on which this port is attached
     **/
    public function showNetworkCardField(NetworkPort $netport, $options = [], $recursiveItems = [])
    {
        global $CFG_GLPI, $DB;

        $alert = '';
        $device_attributes = [];
        $device_names = [];

        if (count($recursiveItems) > 0) {
            $lastItem = $recursiveItems[count($recursiveItems) - 1];

            if (
                !$options['several']
                && in_array($lastItem::class, $CFG_GLPI["itemdevicenetworkcard_types"], true)
            ) {
                // Query each link to network cards
                $criteria = [
                    'SELECT'    => [
                        'link.id AS link_id',
                        'device.designation AS name',
                    ],
                    'FROM'      => 'glpi_devicenetworkcards AS device',
                    'INNER JOIN' => [
                        'glpi_items_devicenetworkcards AS link'   => [
                            'ON' => [
                                'link'   => 'devicenetworkcards_id',
                                'device' => 'id',
                            ],
                        ],
                    ],
                    'WHERE'     => [
                        'link.items_id'   => $lastItem->getID(),
                        'link.itemtype'   => $lastItem::class,
                    ],
                ];

                // $deviceFields contains the list of fields to update
                $deviceFields = [];
                foreach ($this->getNetworkCardInterestingFields() as $SQL_field => $form_field) {
                    $deviceFields[] = $form_field;
                    $criteria['SELECT'][] = "$SQL_field AS $form_field";
                }

                $iterator = $DB->request($criteria);

                foreach ($iterator as $available_device) {
                    $linkid               = $available_device['link_id'];
                    $device_names[$linkid] = $available_device['name'];
                    $device_attributes[$linkid] = [];
                    if (isset($available_device['mac'])) {
                        $device_names[$linkid] = sprintf(
                            __('%1$s - %2$s'),
                            $device_names[$linkid],
                            $available_device['mac']
                        );
                    }
                    // get fields that must be copied from those of the network card
                    foreach ($deviceFields as $field) {
                        // Each field is actually a path in dot notation, so we need to use the array path helper to set the value.
                        ArrayPathAccessor::setElementByArrayPath($device_attributes[$linkid], $field, $available_device[$field]);
                    }
                }
            } else {
                $alert = __('Equipment without network card');
            }
        } else {
            $alert = __('Item not linked to an object');
        }

        $twig_params = [
            'device_attributes' => $device_attributes,
            'device_names'      => $device_names,
            'alert'             => $alert,
            'item'              => $this,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if alert is not empty %}
                {% set alert_field %}
                    <div class="alert alert-info mb-0">{{ alert }}</div>
                {% endset %}
                {{ fields.htmlField('', alert_field, 'DeviceNetworkCard'|itemtype_name) }}
            {% else %}
                {{ fields.dropdownArrayField(
                    'items_devicenetworkcards_id',
                    item.fields['items_devicenetworkcards_id'],
                    device_names,
                    'DeviceNetworkCard'|itemtype_name,
                    {
                        display_emptychoice: true,
                    }
                ) }}
                <script>
                    $(`select[name="items_devicenetworkcards_id"]`).on('change', (e) => {
                        const val = e.target.value;
                        const fields = {{ device_attributes|json_encode|raw }};
                        Object.keys(fields[val]).forEach((fieldName) => {
                            const field = document.getElementsByName(fieldName)[0];
                            if (field && fields[val][fieldName]) {
                                field.value = fields[val][fieldName];
                            }
                        });
                    });
                </script>
            {% endif %}
TWIG, $twig_params);
    }

    /**
     * Display the MAC field. Used by Ethernet, Wifi, Aggregate and alias NetworkPorts
     *
     * @param NetworkPort $netport object : the port that owns this instantiation
     *                         (usefull, for instance to get network port attributs
     * @param array $options Array of options given to NetworkPort::showForm
     **/
    public function showMacField(NetworkPort $netport, $options = [])
    {
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {{ fields.textField('mac', mac, label) }}
TWIG, ['label' => __('MAC'), 'mac' => $netport->fields['mac']]);
    }

    /**
     * Display the Socket field. Used by Ethernet, and Migration
     *
     * @param NetworkPort $netport         NetworkPort object :the port that owns this instantiation
     *                                     (usefull, for instance to get network port attributs
     * @param array       $options         array of options given to NetworkPort::showForm
     * @param array       $recursiveItems  list of the items on which this port is attached
     **/
    public function showSocketField(NetworkPort $netport, $options = [], $recursiveItems = [])
    {
        $socket_id = 0;
        if (count($recursiveItems) > 0) {
            // find socket attached to NetworkPortEthernet
            $socket = new Socket();
            if ($netport->getID() && $socket->getFromDBByCrit(["networkports_id" => $netport->getID()])) {
                $socket_id = $socket->getID();
            }
        }
        $twig_params = [
            'socket_id' => $socket_id,
            'recursive_items' => $recursiveItems,
            'label' => _n('Network socket', 'Network sockets', 1),
            'no_link_label' => __('Item not linked to an object'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if recursive_items|length > 0 %}
                {{ fields.dropdownField('Glpi\\\\Socket', 'sockets_id', socket_id, label) }}
            {% else %}
                <div class="alert alert-info">{{ no_link_label }}</div>
            {% endif %}
TWIG, $twig_params);
    }

    /**
     * Select which NetworkPort to attach
     *
     * NetworkPortAlias and NetworkPortAggregate ara based on other physical network ports
     * (Ethernet or Wifi). This method Allows us to select which one to select.
     *
     * @param array $recursiveItems
     * @param 'NetworkPortAlias'|'NetworkPortAggregate' $origin
     * <ul>
     *     <li>NetworkPortAlias are based on one NetworkPort wherever</li>
     *     <li>NetworkPortAggregate are based on several NetworkPort</li>
     * </ul>
     **/
    public function showNetworkPortSelector($recursiveItems, $origin)
    {
        global $DB;

        if (count($recursiveItems) === 0) {
            return;
        }

        $lastItem = $recursiveItems[count($recursiveItems) - 1];

        echo "<td>" . __s('Origin port') . "</td><td>\n";
        $netport_types = ['NetworkPortEthernet', 'NetworkPortWifi'];
        $selectOptions = [];
        $possible_ports = [];

        switch ($origin) {
            case 'NetworkPortAlias':
                $field_name                           = 'networkports_id_alias';
                $selectOptions['display_emptychoice'] = true;
                $selectOptions['multiple']            = false;
                $selectOptions['on_change']           = 'updateForm(this.options[this.selectedIndex].value)';
                $netport_types[]                      = 'NetworkPortAggregate';
                break;

            case 'NetworkPortAggregate':
                $field_name                       = 'networkports_id_list';
                $selectOptions['multiple']        = true;
                $selectOptions['size']            = 4;
                $netport_types[]                  = 'NetworkPortAlias';
                break;

            default:
                throw new RuntimeException(sprintf('Unexpected origin `%s`.', $origin));
        }

        if (isset($this->fields[$field_name])) {
            if (is_array($this->fields[$field_name])) {
                $selectOptions['values'] = $this->fields[$field_name];
            } else {
                $selectOptions['values'] = [$this->fields[$field_name]];
            }
        }

        $macAddresses = [];
        foreach ($netport_types as $netport_type) {
            $instantiationTable = getTableForItemType($netport_type);
            $iterator = $DB->request([
                'SELECT' => [
                    'port.id',
                    'port.name',
                    'port.mac',
                ],
                'FROM'   => 'glpi_networkports AS port',
                'WHERE'  => [
                    'items_id'           => $lastItem->getID(),
                    'itemtype'           => $lastItem->getType(),
                    'instantiation_type' => $netport_type,
                ],
                'ORDER'  => ['logical_number', 'name'],
            ]);

            if (count($iterator)) {
                $array_element_name = call_user_func(
                    [$netport_type, 'getTypeName'],
                    count($iterator)
                );
                $possible_ports[$array_element_name] = [];

                foreach ($iterator as $portEntry) {
                    $macAddresses[$portEntry['id']] = $portEntry['mac'];
                    if (!empty($portEntry['mac'])) {
                        $portEntry['name'] = sprintf(
                            __('%1$s - %2$s'),
                            $portEntry['name'],
                            $portEntry['mac']
                        );
                    }
                    $possible_ports[$array_element_name][$portEntry['id']] = $portEntry['name'];
                }
            }
        }

        if (!$selectOptions['multiple']) {
            $js = 'var device_mac_addresses = [];';
            foreach ($macAddresses as $port_id => $macAddress) {
                $js .= sprintf('device_mac_addresses[%d] = "%s";', (int) $port_id, jsescape($macAddress));
            }
            $js .= "
                function updateForm(devID) {
                    var field = document.getElementsByName('mac')[0];
                    if ((field != undefined) && (device_mac_addresses[devID] != undefined)) {
                        field.value = device_mac_addresses[devID];
                    }
                }
            ";
            echo Html::scriptBlock($js);
        }

        Dropdown::showFromArray($field_name, $possible_ports, $selectOptions);
        echo "</td>";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::class === NetworkPort::class) {
            $instantiation = $item->getInstantiation();
            if ($instantiation !== false) {
                $log = new Log();
                //TRANS: %1$s is a type, %2$s is a table

                return $log::createTabEntry(sprintf(
                    __('%1$s - %2$s'),
                    $log::getTypeName(),
                    $instantiation::getTypeName()
                ), 0, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === NetworkPort::class) {
            $instantiation = $item->getInstantiation();
            if ($instantiation !== false) {
                Log::displayTabContentForItem($instantiation, $tabnum, $withtemplate);
            }
        }
        return true;
    }

    /**
     * @param array $tab
     * @param array $joinparams
     **/
    public static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams) {}


    /**
     * Display a connection of a networking port
     *
     * @param NetworkPort $netport  to be displayed
     * @param boolean     $edit     permit to edit ? (false by default)
     **/
    public static function showConnection($netport, $edit = false)
    {
        $ID = $netport->getID();
        if (static::isNewID($ID)) {
            return false;
        }

        $device1 = $netport->getItem();

        if (!$device1->can($device1->getID(), READ)) {
            return false;
        }
        $canedit      = $device1->canEdit($device1->fields["id"]);
        $relations_id = 0;
        $oppositePort = NetworkPort_NetworkPort::getOpposite($netport, $relations_id);

        if ($oppositePort !== false) {
            $device2 = $oppositePort->getItem();

            if ($device2->can($device2->fields["id"], READ)) {
                echo $oppositePort->getLink();
                if ($device1->fields["entities_id"] !== $device2->fields["entities_id"]) {
                    echo "<br>(" . htmlescape(Dropdown::getDropdownName(
                        "glpi_entities",
                        $device2->getEntityID()
                    )) . ")";
                }

                // write rights on dev1 + READ on dev2 OR READ on dev1 + write rights on dev2
                if (
                    $canedit
                    || $device2->canEdit($device2->fields["id"])
                ) {
                    echo "&nbsp;";
                    Html::showSimpleForm(
                        $oppositePort::getFormURL(),
                        'disconnect',
                        _x('button', 'Disconnect'),
                        ['id' => $relations_id],
                        'fa-unlink netport',
                        'class="btn btn-sm btn-outline-danger"'
                    );
                }
            } else {
                if (rtrim($oppositePort->fields["name"]) !== "") {
                    $netname = $oppositePort->fields["name"];
                } else {
                    $netname = __('Without name');
                }
                printf(
                    __s('%1$s on %2$s'),
                    "<span class='b'>" . htmlescape($netname) . "</span>",
                    "<span class='b'>" . htmlescape($device2->getName()) . "</span>"
                );
                echo "<br>(" . htmlescape(Dropdown::getDropdownName(
                    "glpi_entities",
                    $device2->getEntityID()
                )) . ")";
            }
        } else {
            if ($canedit) {
                if (!$device1->isTemplate()) {
                    if ($edit) {
                        self::dropdownConnect(
                            $ID,
                            ['name'        => 'NetworkPortConnect_networkports_id_2',
                                'entity'      => $device1->fields["entities_id"],
                                'entity_sons' => $device1->isRecursive(),
                            ]
                        );
                    } else {
                        echo "<a href=\"" . htmlescape($netport->getFormURLWithID($ID)) . "\">" . _sx('button', 'Connect') . "</a>";
                    }
                } else {
                    echo "&nbsp;";
                }
            } else {
                echo "<div id='not_connected_display$ID'>" . __s('Not connected.') . "</div>";
            }
        }
    }


    /**
     * Make a select box for  connected port
     *
     * @param integer $ID        ID of the current port to connect
     * @param array   $options   array of possible options:
     *    - name : string / name of the select (default is networkports_id)
     *    - comments : boolean / is the comments displayed near the dropdown (default true)
     *    - entity : integer or array / restrict to a defined entity or array of entities
     *                   (default -1 : no restriction)
     *    - entity_sons : boolean / if entity restrict specified auto select its sons
     *                   only available if entity is a single value not an array (default false)
     *
     * @return integer random part of elements id
     **/
    public static function dropdownConnect($ID, $options = [])
    {
        global $CFG_GLPI;

        $p['name']        = 'networkports_id';
        $p['comments']    = 1;
        $p['entity']      = -1;
        $p['entity_sons'] = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        // Manage entity_sons
        if ($p['entity'] >= 0 && $p['entity_sons']) {
            if (is_array($p['entity'])) {
                echo "entity_sons options is not available with entity option as array";
            } else {
                $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
            }
        }

        echo "<input type='hidden' name='NetworkPortConnect_networkports_id_1'value='" . htmlescape($ID) . "'>";
        $rand = Dropdown::showItemTypes('NetworkPortConnect_itemtype', $CFG_GLPI["networkport_types"]);

        $params = ['itemtype'           => '__VALUE__',
            'entity_restrict'    => Session::getMatchingActiveEntities($p['entity']),
            'networkports_id'    => $ID,
            'comments'           => $p['comments'],
            'myname'             => $p['name'],
            'instantiation_type' => static::class,
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_NetworkPortConnect_itemtype$rand",
            "show_" . $p['name'] . "$rand",
            $CFG_GLPI["root_doc"]
                                       . "/ajax/dropdownConnectNetworkPortDeviceType.php",
            $params
        );

        echo "<span id='show_" . htmlescape($p['name']) . "$rand'>&nbsp;</span>";

        return $rand;
    }
}
