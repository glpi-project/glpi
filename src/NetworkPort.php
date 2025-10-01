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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use Glpi\Plugin\Hooks;
use Glpi\Socket;

use function Safe\preg_replace;
use function Safe\strtotime;

/**
 * NetworkPort Class
 *
 * There is two parts for a given NetworkPort.
 * The first one, generic, only contains the link to the item, the name and the type of network port.
 * All specific characteristics are owned by the instanciation of the network port : NetworkPortInstantiation.
 * Whenever a port is display (through its form or though item port listing), the NetworkPort class
 * load its instantiation from the instantiation database to display the elements.
 * Moreover, in NetworkPort form, if there is no more than one NetworkName attached to the current
 * port, then, the fields of NetworkName are display. Thus, NetworkPort UI remain similar to 0.83
 **/
class NetworkPort extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype             = 'itemtype';
    public static $items_id             = 'items_id';
    public $dohistory                   = true;

    public static $checkParentRights    = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

    protected static $forward_entity_to = ['NetworkName'];

    public static $rightname                   = 'networking';
    protected $displaylist = false;

    /**
     * Subset of input that will be used for NetworkPortInstantiation.
     * @var array|null
     */
    private ?array $input_for_instantiation = null;
    /**
     * Subset of input that will be used for NetworkName.
     * @var array|null
     */
    private ?array $input_for_NetworkName = null;
    /**
     * Subset of input that will be used for NetworkPort_NetworkPort.
     * @var array|null
     */
    private ?array $input_for_NetworkPortConnect = null;

    public function __get(string $property)
    {
        $value = null;
        switch ($property) {
            case 'input_for_instantiation':
            case 'input_for_NetworkName':
            case 'input_for_NetworkPortConnect':
                Toolbox::deprecated(sprintf('Reading private property %s::%s is deprecated', self::class, $property));
                $value = $this->$property;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', self::class, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
        return $value;
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }

    public function __set(string $property, $value)
    {
        switch ($property) {
            case 'input_for_instantiation':
            case 'input_for_NetworkName':
            case 'input_for_NetworkPortConnect':
                Toolbox::deprecated(sprintf('Writing private property %s::%s is deprecated', self::class, $property));
                $this->$property = $value;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', self::class, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getPreAdditionalInfosForName()
    {
        if ($item = $this->getItem()) {
            return $item->getName();
        }
        return '';
    }

    /**
     * Get the list of available network port type.
     *
     * @since 0.84
     *
     * @return class-string<NetworkPortInstantiation>[] Array of available type of network ports
     **/
    public static function getNetworkPortInstantiations()
    {
        global $CFG_GLPI;

        return $CFG_GLPI['networkport_instantiations'];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Network port', 'Network ports', $nb);
    }

    /**
     * Get the instantiation of the current NetworkPort
     * The instantiation rely on the instantiation_type field and the id of the NetworkPort. If the
     * network port exists, but not its instantiation, then, the instantiation will be empty.
     *
     * @since 0.84
     *
     * @return NetworkPortInstantiation|false  the instantiation object or false if the type of instantiation is not known
     **/
    public function getInstantiation()
    {
        if (
            isset($this->fields['instantiation_type'])
            && in_array($this->fields['instantiation_type'], self::getNetworkPortInstantiations(), true)
        ) {
            if ($instantiation = getItemForItemtype($this->fields['instantiation_type'])) {
                if (!$instantiation->getFromDB($this->getID())) {
                    if (!$instantiation->getEmpty()) {
                        unset($instantiation);
                        return false;
                    }
                }
                return $instantiation;
            }
        }
        return false;
    }

    /**
     * Change the instantion type of a NetworkPort : check validity of the new type of
     * instantiation and that it is not equal to current ones. Update the NetworkPort and delete
     * the previous instantiation. It is up to the caller to create the new instantiation !
     *
     * @since 0.84
     *
     * @param class-string<NetworkPortInstantiation> $new_instantiation_type  the name of the new instaniation type
     *
     * @return NetworkPortInstantiation|boolean false on error, true if the previous instantiation is not available
     *                 (ie.: invalid instantiation type) or the object of the previous instantiation.
     **/
    public function switchInstantiationType($new_instantiation_type)
    {
        // First, check if the new instantiation is a valid one ...
        if (!in_array($new_instantiation_type, self::getNetworkPortInstantiations())) {
            return false;
        }

        // Load the previous instantiation
        $previousInstantiation = $this->getInstantiation();

        // If the previous instantiation is the same than the new one: nothing to do !
        if (
            ($previousInstantiation !== false)
            && ($previousInstantiation::class === $new_instantiation_type)
        ) {
            return $previousInstantiation;
        }

        // We update the current NetworkPort
        $input                       = $this->fields;
        $input['instantiation_type'] = $new_instantiation_type;
        $this->update($input);

        // Then, we delete the previous instantiation
        if ($previousInstantiation !== false) {
            $previousInstantiation->delete($previousInstantiation->fields);
            return $previousInstantiation;
        }

        return true;
    }

    public function prepareInputForUpdate($input)
    {
        if (!isset($input["_no_history"])) {
            $input['_no_history'] = false;
        }
        if (
            isset($input['_create_children'])
            && $input['_create_children']
        ) {
            return $this->splitInputForElements($input);
        }

        return $input;
    }

    public function post_updateItem($history = true)
    {
        global $DB;

        if (count($this->updates)) {
            // Update Ticket Tco
            if (
                in_array("itemtype", $this->updates, true)
                || in_array("items_id", $this->updates, true)
            ) {
                $ip = new IPAddress();
                // Update IPAddress
                foreach (
                    $DB->request([
                        'FROM' => 'glpi_networknames',
                        'WHERE' => [
                            'itemtype' => 'NetworkPort',
                            'items_id' => $this->getID(),
                        ],
                    ]) as $dataname
                ) {
                    foreach (
                        $DB->request([
                            'FROM' => 'glpi_ipaddresses',
                            'WHERE' => [
                                'itemtype' => 'NetworkName',
                                'items_id' => $dataname['id'],
                            ],
                        ]) as $data
                    ) {
                        $ip->update(['id'           => $data['id'],
                            'mainitemtype' => $this->fields['itemtype'],
                            'mainitems_id' => $this->fields['items_id'],
                        ]);
                    }
                }
            }
        }
        parent::post_updateItem($history);

        $this->updateDependencies(!$this->input['_no_history']);
        $this->updateMetrics();
    }

    public function post_clone($source, $history)
    {
        $instantiation = $source->getInstantiation();
        if ($instantiation !== false) {
            $instantiation->fields[$instantiation->getIndexName()] = $this->getID();
            return $instantiation->clone([], $history);
        }
    }

    /**
     * Split input fields when validating a port
     *
     * The form of the NetworkPort can contain the details of the NetworkPortInstantiation as well as
     * NetworkName elements (if no more than one name is attached to this port). Feilds from both
     * NetworkPortInstantiation and NetworkName must not be process by the NetworkPort::add or
     * NetworkPort::update. But they must be kept for adding or updating these elements. This is
     * done after creating or updating the current port. Otherwise, its ID may not be known (in case
     * of new port).
     * To keep the unused fields, we check each field key. If it is owned by NetworkPort (ie :
     * exists inside the $this->fields array), then they remain inside $input. If they are prefix by
     * "Networkname_", then they are added to $this->input_for_NetworkName. Else, they are for the
     * instantiation and added to $this->input_for_instantiation.
     *
     * This method must be call before NetworkPort::add or NetworkPort::update in case of NetworkPort
     * form. Otherwise, the entry of the database may contain wrong values.
     *
     * @since 0.84
     *
     * @param ?array $input
     * @return array|void
     *
     * @see self::updateDependencies() for the update
     **/
    public function splitInputForElements($input)
    {

        if (
            $this->input_for_instantiation !== null
            || $this->input_for_NetworkName !== null
            || $this->input_for_NetworkPortConnect !== null
            || !isset($input)
        ) {
            return;
        }

        $this->input_for_instantiation      = [];
        $this->input_for_NetworkName        = [];
        $this->input_for_NetworkPortConnect = [];

        $clone = clone $this;
        $clone->getEmpty();

        foreach ($input as $field => $value) {
            if (array_key_exists($field, $clone->fields) || $field[0] === '_') {
                continue;
            }
            if (str_starts_with($field, "NetworkName_")) {
                $networkName_field = preg_replace('/^NetworkName_/', '', $field);
                $this->input_for_NetworkName[$networkName_field] = $value;
            } elseif (str_starts_with($field, "NetworkPortConnect_")) {
                $networkName_field = preg_replace('/^NetworkPortConnect_/', '', $field);
                $this->input_for_NetworkPortConnect[$networkName_field] = $value;
            } else {
                $this->input_for_instantiation[$field] = $value;
            }
            unset($input[$field]);
        }

        return $input;
    }

    /**
     * Update all related elements after adding or updating an element
     *
     * splitInputForElements() prepare the data for adding or updating NetworkPortInstantiation and
     * NetworkName. This method will update NetworkPortInstantiation and NetworkName. I must be call
     * after NetworkPort::add or NetworkPort::update otherwise, the networkport ID will not be known
     * and the dependencies won't have a valid items_id field.
     *
     * @since 0.84
     *
     * @param $history
     *
     * @see splitInputForElements() for preparing the input
     **/
    public function updateDependencies($history = true)
    {
        $instantiation = $this->getInstantiation();
        if (
            $instantiation !== false
            && is_array($this->input_for_instantiation)
            && count($this->input_for_instantiation) > 0
        ) {
            $this->input_for_instantiation['networkports_id'] = $this->getID();
            if ($instantiation::isNewID($instantiation->getID())) {
                $instantiation->add($this->input_for_instantiation, [], $history);
            } else {
                $instantiation->update($this->input_for_instantiation, $history);
            }
        }
        $this->input_for_instantiation = null;

        if (
            is_array($this->input_for_NetworkName)
            && count($this->input_for_NetworkName) > 0
            && !isset($_POST['several'])
        ) {
            // Check to see if the NetworkName is empty
            $empty_networkName = empty($this->input_for_NetworkName['name'])
                              && empty($this->input_for_NetworkName['fqdns_id']);
            if (($empty_networkName) && is_array($this->input_for_NetworkName['_ipaddresses'])) {
                foreach ($this->input_for_NetworkName['_ipaddresses'] as $ip_address) {
                    if (!empty($ip_address)) {
                        $empty_networkName = false;
                        break;
                    }
                }
            }

            $network_name = new NetworkName();
            if (isset($this->input_for_NetworkName['id'])) {
                if ($empty_networkName) {
                    // If the NetworkName is empty, then delete it !
                    $network_name->delete($this->input_for_NetworkName, true, $history);
                } else {
                    // Else, update it
                    $this->input_for_NetworkName['entities_id'] = $this->fields['entities_id'];
                    $network_name->update($this->input_for_NetworkName, $history);
                }
            } elseif (!$empty_networkName) { // Only create a NetworkName if it is not empty
                $this->input_for_NetworkName['itemtype']    = 'NetworkPort';
                $this->input_for_NetworkName['items_id']    = $this->getID();
                $this->input_for_NetworkName['entities_id'] = $this->fields['entities_id'];
                $network_name->add($this->input_for_NetworkName, [], $history);
            }
        }
        $this->input_for_NetworkName = null;

        if (
            is_array($this->input_for_NetworkPortConnect)
            && count($this->input_for_NetworkPortConnect) > 0
        ) {
            if (
                isset($this->input_for_NetworkPortConnect['networkports_id_1'], $this->input_for_NetworkPortConnect['networkports_id_2'])
                && !empty($this->input_for_NetworkPortConnect['networkports_id_2'])
            ) {
                $nn  = new NetworkPort_NetworkPort();
                $nn->add($this->input_for_NetworkPortConnect, [], $history);
            }
        }
        $this->input_for_NetworkPortConnect = null;
    }

    public function updateMetrics()
    {
        $unicity_input = [
            'networkports_id' => $this->fields['id'],
            'date'            => date('Y-m-d', strtotime($_SESSION['glpi_currenttime'])),
        ];
        $input = array_merge(
            [
                'ifinbytes'       => $this->fields['ifinbytes'] ?? 0,
                'ifoutbytes'      => $this->fields['ifoutbytes'] ?? 0,
                'ifinerrors'      => $this->fields['ifinerrors'] ?? 0,
                'ifouterrors'     => $this->fields['ifouterrors'] ?? 0,
                'is_dynamic'     =>  $this->fields['is_dynamic'] ?? 0,
            ],
            $unicity_input
        );

        $metrics = new NetworkPortMetrics();
        if ($metrics->getFromDBByCrit($unicity_input)) {
            $input['id'] = $metrics->fields['id'];
            $metrics->update($input, false);
        } else {
            $metrics->add($input, [], false);
        }
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input["logical_number"]) && ($input["logical_number"] === '')) {
            unset($input["logical_number"]);
        }

        if (!isset($input["_no_history"])) {
            $input['_no_history'] = false;
        }

        if (
            isset($input['_create_children'])
            && $input['_create_children']
        ) {
            $input = $this->splitInputForElements($input);
        }

        return parent::prepareInputForAdd($input);
    }

    public function post_addItem()
    {
        parent::post_addItem(); //for history
        $this->updateDependencies(!$this->input['_no_history']);
        $this->updateMetrics();
    }

    public function cleanDBonPurge()
    {
        $instantiation = $this->getInstantiation();
        if ($instantiation !== false) {
            $instantiation->cleanDBonItemDelete(static::class, $this->getID());
            unset($instantiation);
        }

        $this->deleteChildrenAndRelationsFromDb(
            [
                NetworkName::class,
                NetworkPort_NetworkPort::class,
                NetworkPort_Vlan::class,
                NetworkPortAggregate::class,
                NetworkPortAlias::class,
                NetworkPortDialup::class,
                NetworkPortEthernet::class,
                NetworkPortFiberchannel::class,
                NetworkPortLocal::class,
                NetworkPortMetrics::class,
                NetworkPortWifi::class,
            ]
        );
    }

    /**
     * Get port opposite port ID if linked item
     *
     * @param integer $ID  networking port ID
     *
     * @return integer|false  ID of the NetworkPort found, false if not found
     **/
    public function getContact($ID): bool|int
    {
        $wire = new NetworkPort_NetworkPort();
        if ($contact_id = $wire->getOppositeContact($ID)) {
            return $contact_id;
        }
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(NetworkPortMetrics::class, $ong, $options);
        $this->addStandardTab(NetworkName::class, $ong, $options);
        $this->addStandardTab(NetworkPort_Vlan::class, $ong, $options);
        $this->addStandardTab(Lock::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);
        $this->addStandardTab(NetworkPortConnectionLog::class, $ong, $options);
        $this->addStandardTab(NetworkPortInstantiation::class, $ong, $options);
        $this->addStandardTab(NetworkPort::class, $ong, $options);

        return $ong;
    }

    /**
     * Show ports for an item
     *
     * @param CommonDBTM $item
     * @param integer $withtemplate
     * @return false|void
     */
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $CFG_GLPI, $DB;

        $itemtype = $item::class;
        $items_id = $item->getField('id');

        $netport = new self();
        $netport_table = $netport->getTable();

        //no matter if the main item is dynamic,
        //deleted and dynamic networkport are displayed from lock tab
        //deleted and non dynamic networkport are always displayed (with is_deleted column)
        $deleted_criteria = [
            'OR'  => [
                'AND' => [
                    "$netport_table.is_deleted" => 0,
                    "$netport_table.is_dynamic" => 1,
                ],
                "$netport_table.is_dynamic" => 0,
            ],
        ];

        if (
            !NetworkEquipment::canView()
            || !$item->can($items_id, READ)
        ) {
            return false;
        }

        if ($itemtype === self::class || $withtemplate == 2) {
            $canedit = false;
        } else {
            $canedit = $item->canEdit($items_id);
        }

        $aggegate_iterator = $DB->request([
            'FROM'   => $netport::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $item->getID(),
            ],
            'ORDER'  => 'logical_number',
        ]);

        $aggregated_ports = [];
        foreach ($aggegate_iterator as $row) {
            $port_iterator = $DB->request([
                'FROM'   => 'glpi_networkportaggregates',
                'WHERE'  => ['networkports_id' => $row['id']],
                'LIMIT'  => 1,
            ]);

            foreach ($port_iterator as $prow) {
                $aggregated_ports = array_merge(
                    $aggregated_ports,
                    importArrayFromDB($prow['networkports_id_list'])
                );
            }
        }

        $criteria = [
            'FROM'   => $netport_table,
            'WHERE'  => [
                "$netport_table.items_id"  => $item->getID(),
                "$netport_table.itemtype"  => $item->getType(), [
                    'OR' => [
                        ["$netport_table.name" => ['!=', 'Management']],
                        ["$netport_table.name" => null],
                    ],
                ],
            ] + $deleted_criteria,
        ];

        $so = $netport->rawSearchOptions();
        foreach (Plugin::getAddSearchOptions(self::class) as $key => $data) {
            $so[] = ['id' => $key] + $data;
        }

        $ports_iterator = $DB->request($criteria);

        $dprefs = DisplayPreference::getForTypeUser(
            'Networkport',
            Session::getLoginUserID()
        );
        // hardcode add name column
        array_unshift($dprefs, 1);
        $colspan = count($dprefs);

        $showmassiveactions = false;
        if ($withtemplate !== 2) {
            $showmassiveactions = $canedit;
            ++$colspan;
        }

        // Show Add Form
        if (
            $canedit
            && (empty($withtemplate) || ($withtemplate != 2))
        ) {
            echo "<div class='firstbloc'>";
            echo "<form method='get' action='" . htmlescape($netport::getFormURL()) . "'>";
            echo "<input type='hidden' name='items_id' value='" . $item->getID() . "'>";
            echo "<input type='hidden' name='itemtype' value='" . htmlescape($itemtype) . "'>";
            echo __s('Network port type to be added');
            echo "<div class='d-flex'>";
            echo "<div class='col-auto'>";
            $instantiations = [];
            foreach (self::getNetworkPortInstantiations() as $inst_type) {
                if (call_user_func([$inst_type, 'canCreate'])) {
                    $instantiations[$inst_type] = call_user_func([$inst_type, 'getTypeName']);
                }
            }
            Dropdown::showFromArray(
                'instantiation_type',
                $instantiations,
                ['value' => 'NetworkPortEthernet']
            );
            echo "</div>";

            echo "<div class='col-auto m-2'>";
            echo "<label for='several'>" . __s('Add several ports') . "</label>";
            echo "&nbsp;<input type='checkbox' name='several' id='several' value='1'></td>";
            echo "</div>";

            echo "<div class='col-auto'>";
            echo "<button type='submit' name='add' value='1' class='btn btn-primary ms-1'>";
            echo "<i class='ti ti-link'></i>" . _sx('button', 'Add');
            echo "</button>";
            echo "</div>";

            echo "</div>"; //d-flex
            Html::closeForm();
            echo "</div>"; //firstbloc
        }

        Plugin::doHook(Hooks::DISPLAY_NETPORT_LIST_BEFORE, ['item' => $item]);

        $stencil = NetworkEquipmentModelStencil::getStencilFromItem($item);
        if ($stencil) {
            $stencil->displayStencil();
        }

        $search_config_top    = '';
        if (
            Session::haveRightsOr('search_config', [
                DisplayPreference::PERSONAL,
                DisplayPreference::GENERAL,
            ])
        ) {
            $search_config_top .= "<span class='ti ti-table-row cursor-pointer' title='"
            . __s('Select default items to show') . "' data-bs-toggle='modal' data-bs-target='#search_config_top'>
            <span class='sr-only'>" . __s('Select default items to show') . "</span></span>";

            $pref_url = $CFG_GLPI["root_doc"] . "/front/displaypreference.form.php?itemtype="
                     . self::getType();
            $search_config_top .= Ajax::createIframeModalWindow(
                'search_config_top',
                $pref_url,
                [
                    'title'         => __('Select default items to show'),
                    'reloadonclose' => true,
                    'display'       => false,
                ]
            );
        }

        $rand = mt_rand();
        if ($showmassiveactions) {
            Html::openMassiveActionsForm('mass' . self::class . $rand);
        }

        Session::initNavigateListItems(
            'NetworkPort',
            //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $item->getTypeName(1),
                $item->getName()
            )
        );

        echo "<div class='table-responsive'>";
        if ($showmassiveactions) {
            $massiveactionparams = [
                'num_displayed'  => min($_SESSION['glpilist_limit'], count($ports_iterator)),
                'check_itemtype' => $itemtype,
                'container'      => 'mass' . self::class . $rand,
                'check_items_id' => $items_id,
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";

        echo "<thead><tr><td colspan='$colspan'>";
        echo "<table class='netport-legend'>";
        echo "<thead><tr><th colspan='4'>" . __s('Connections legend') . "</th></tr></thead><tr>";
        echo "<td class='netport trunk'>" . __s('Equipment in trunk or tagged mode') . "</td>";
        echo "<td class='netport hub'>" . __s('Hub ') . "</td>";
        echo "<td class='netport cotrunk'>" . __s('Other equipments') . "</td>";
        echo "<td class='netport aggregated'>" . __s('Aggregated port') . "</td>";
        echo "</tr></table>";
        echo "</td></tr>";

        echo "<tr><th colspan='$colspan'>";
        echo htmlescape(sprintf(
            __('%s %s'),
            count($ports_iterator),
            NetworkPort::getTypeName(count($ports_iterator))
        ));
        echo ' ' . $search_config_top;
        echo "</td></tr></thead>";

        //display table headers
        echo "<tr>";
        if ($canedit) {
            echo "<td>" . Html::getCheckAllAsCheckbox('mass' . self::class . $rand, '__RAND__') . "</td>";
        }
        foreach ($dprefs as $dpref) {
            echo "<th>";
            foreach ($so as $option) {
                if ($option['id'] == $dpref) {
                    echo htmlescape($option['name']);
                    continue;
                }
            }
            echo "</th>";
        }
        echo "</tr>";

        //display row contents
        if (!count($ports_iterator)) {
            echo "<tr><th colspan='$colspan'>" . __s('No network port found') . "</th></tr>";
        }
        foreach ($ports_iterator as $row) {
            echo $netport->showPort(
                $row,
                $dprefs,
                $so,
                $canedit,
                (count($aggregated_ports) && in_array($row['id'], $aggregated_ports, true)),
                $rand
            );
        }

        echo "</table>";
        echo "</div>";

        if ($showmassiveactions) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }

        //management ports
        $criteria = [
            'FROM'   => $netport::getTable(),
            'WHERE'  => [
                'items_id'  => $item->getID(),
                'itemtype'  => $item->getType(),
                'name'      => 'Management',
            ] + $deleted_criteria,
        ];

        $mports_iterator = $DB->request($criteria);

        if (count($mports_iterator)) {
            echo "<hr/>";
            echo "<table class='tab_cadre_fixehov'>";

            //hardcode display preferences form management port
            $dprefs = [
                1, //name
                4, //mac
                127, //network names
                126, // IPs
            ];

            echo "<thead><tr><th colspan='" . count($dprefs) . "'>";
            echo htmlescape(sprintf(
                __('%s %s'),
                count($mports_iterator),
                _n('Management port', 'Management ports', count($mports_iterator))
            ));
            echo "</th></tr></thead>";

            echo "<tr>";
            // display table headers
            foreach ($dprefs as $dpref) {
                echo "<th>";
                foreach ($so as $option) {
                    if ($option['id'] == $dpref) {
                        echo htmlescape($option['name']);
                        continue;
                    }
                }
                echo "</th>";
            }
            echo "</tr>";

            //display row contents
            foreach ($mports_iterator as $row) {
                echo $netport->showPort(
                    $row,
                    $dprefs,
                    $so,
                    $canedit,
                    (count($aggregated_ports) && in_array($row['id'], $aggregated_ports, true)),
                    $rand,
                    false
                );
            }

            echo "</table>";
        }
    }

    /**
     * Display port row
     *
     * @param array $port    Port entry in db
     * @param array $dprefs  Display preferences
     * @param array $so      Search options
     * @param bool  $canedit Can edit ACL
     * @param bool  $agg     Is an aggregated port
     * @param int   $rand    Random value
     * @param bool  $with_ma Flag massive actions
     *
     * @return string
     */
    protected function showPort(array $port, $dprefs, $so, $canedit, $agg, $rand, $with_ma = true)
    {
        global $DB, $CFG_GLPI;

        $css_class = 'netport';
        if ((int) $port['ifstatus'] === 1) {
            if ((int) $port['trunk'] === 1) {
                $css_class .= ' trunk'; // port_trunk.png
            } elseif ($this->isHubConnected($port['id'])) {
                $css_class .= ' hub'; //multiple_mac_addresses.png
            } else {
                $css_class .= ' cotrunk'; //connected_trunk.png
            }
        }

        $port_number = $port['logical_number'] ?? "";
        $whole_output = "<tr class='$css_class' id='port_number_" . htmlescape($port_number) . "'>";
        if ($canedit && $with_ma) {
            $whole_output .= "<td>" . Html::getMassiveActionCheckBox(self::class, $port['id']) . "</td>";
        }
        foreach ($dprefs as $dpref) {
            $output = '';
            $td_class = '';
            foreach ($so as $option) {
                if ((int) $option['id'] === (int) $dpref) {
                    switch ($dpref) {
                        case 6:
                            $output .= htmlescape(Dropdown::getYesNo($port['is_deleted']));
                            break;
                        case 1:
                            if ($agg === true) {
                                $td_class = 'aggregated';
                            }

                            $name = $port['name'];
                            $url = NetworkPort::getFormURLWithID($port['id']);
                            if ($_SESSION["glpiis_ids_visible"] || empty($name)) {
                                $name = sprintf(__('%1$s (%2$s)'), $name, $port['id']);
                            }

                            $output .= '<a href="' . htmlescape($url) . '">' . htmlescape($name) . '</a>';
                            break;
                        case 31:
                            $speed = $port[$option['field']];
                            //TRANS: list of unit (bps for bytes per second)
                            $bytes = [__('bps'), __('Kbps'), __('Mbps'), __('Gbps'), __('Tbps')];
                            foreach ($bytes as $val) {
                                if ($speed >= 1000) {
                                    $speed /= 1000;
                                } else {
                                    break;
                                }
                            }
                            //TRANS: %1$s is a number maybe float or string and %2$s the unit
                            $output .= htmlescape(sprintf(__('%1$s %2$s'), round($speed, 2), $val));
                            break;
                        case 32:
                            $state_class = '';
                            $state_title = __('Unknown');
                            switch ($port[$option['field']]) {
                                case 1: //up
                                    $state_class = 'text-green';
                                    $state_title = __('Up');
                                    break;
                                case 2: //down
                                    $state_class = 'text-red';
                                    $state_title = __('Down');
                                    break;
                                case 3: //testing
                                    $state_class = 'text-orange';
                                    $state_title = __('Test');
                                    break;
                            }
                            $output .= sprintf(
                                '<i class="ti ti-circle-filled %s" title="%s"></i> <span class="sr-only">%s</span>',
                                htmlescape($state_class),
                                htmlescape($state_title),
                                htmlescape($state_title)
                            );
                            break;
                        case 34:
                            $in = $port[$option['field']];
                            $out = $port['ifoutbytes'];

                            if (empty($in) && empty($out)) {
                                break;
                            }

                            if (!empty($in)) {
                                $in = Toolbox::getSize($in);
                            } else {
                                $in = ' - ';
                            }

                            if (!empty($out)) {
                                $out = Toolbox::getSize($out);
                            } else {
                                $out = ' - ';
                            }

                            $output .= htmlescape(sprintf('%s / %s', $in, $out));
                            break;
                        case 35:
                            $in = $port[$option['field']];
                            $out = $port['ifouterrors'];

                            if ($in == 0 && $out == 0) {
                                break;
                            }

                            if ($in > 0 || $out > 0) {
                                $td_class = 'orange';
                            }

                            $output .= htmlescape(sprintf('%s / %s', $in, $out));
                            break;
                        case 36:
                            switch ($port[$option['field']]) {
                                case 2: //half
                                    $td_class = 'orange';
                                    $output .= __s('Half');
                                    break;
                                case 3: //full
                                    $output .= __s('Full');
                                    break;
                            }
                            break;
                        case 38:
                            $vlans = $DB->request([
                                'SELECT' => [
                                    NetworkPort_Vlan::getTable() . '.id',
                                    Vlan::getTable() . '.name',
                                    NetworkPort_Vlan::getTable() . '.tagged',
                                    Vlan::getTable() . '.tag',
                                ],
                                'FROM'   => NetworkPort_Vlan::getTable(),
                                'INNER JOIN'   => [
                                    Vlan::getTable() => [
                                        'ON' => [
                                            NetworkPort_Vlan::getTable()  => 'vlans_id',
                                            Vlan::getTable()              => 'id',
                                        ],
                                    ],
                                ],
                                'WHERE'  => ['networkports_id' => $port['id']],
                            ]);

                            if (count($vlans) > 10) {
                                $output .= sprintf(
                                    __s('%s linked VLANs'),
                                    count($vlans)
                                );
                            } else {
                                foreach ($vlans as $row) {
                                    $output .= $row['name'];
                                    if (!empty($row['tag'])) {
                                        $output .= ' [' . htmlescape($row['tag']) . ']';
                                    }
                                    $output .= ($row['tagged'] == 1 ? 'T' : 'U');
                                    $output .= '<br/>';
                                }
                            }
                            break;
                        case 39:
                            $netport = new NetworkPort();
                            $netport->getFromDB($port['id']);

                            $device1 = $netport->getItem();

                            if ($device1 === false || !$device1->can($device1->getID(), READ)) {
                                break;
                            }

                            $relations_id = 0;
                            $oppositePort = NetworkPort_NetworkPort::getOpposite($netport, $relations_id);

                            if ($oppositePort === false) {
                                break;
                            }

                            $device2 = $oppositePort->getItem();
                            if ($device2 !== false) {
                                $output .= $this->getAssetLink($oppositePort);

                                //equipments connected to hubs
                                if ($device2::class === Unmanaged::class && $device2->fields['hub'] == 1) {
                                    $houtput = "<div class='hub'>";

                                    $hub_ports = $DB->request([
                                        'FROM'   => self::getTable(),
                                        'WHERE'  => [
                                            'itemtype'  => $device2::class,
                                            'items_id'  => $device2->getID(),
                                        ],
                                    ]);

                                    $list_ports = [];
                                    foreach ($hub_ports as $hrow) {
                                        $npo = $this->getContact($hrow['id']);
                                        $list_ports[] = $npo;
                                    }

                                    $itemtypes = $CFG_GLPI["networkport_types"];
                                    $union = new QueryUnion();
                                    foreach ($itemtypes as $related_class) {
                                        $table = getTableForItemType($related_class);
                                        $union->addQuery([
                                            'SELECT' => [
                                                'asset.id',
                                                'netp.mac',
                                                'netp.itemtype',
                                                'netp.items_id',
                                            ],
                                            'FROM'   => $table . ' AS asset',
                                            'INNER JOIN'   => [
                                                NetworkPort::getTable() . ' AS netp' => [
                                                    'ON' => [
                                                        'netp'   => 'items_id',
                                                        'asset'    => 'id',
                                                        [
                                                            'AND' => [
                                                                'netp.itemtype' => $related_class,
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'WHERE'  => [
                                                'netp.itemtype'  => $related_class,
                                                'netp.id'        => $list_ports,
                                                'NOT'                => [
                                                    'netp.itemtype'  => $device1::class, // Do not include the current asset
                                                    'netp.items_id'  => $device1->getID(),
                                                ],
                                            ],
                                        ]);
                                    }

                                    $hub_equipments = $DB->request(['FROM' => $union]);

                                    if (count($hub_equipments) > 10) {
                                        $houtput .= '<div>' . sprintf(
                                            __s('%s equipments connected to the hub'),
                                            count($hub_equipments)
                                        ) . '</div>';
                                    } else {
                                        foreach ($hub_equipments as $hrow) {
                                            $asset = getItemForItemtype($hrow['itemtype']);
                                            $asset->getFromDB($hrow['items_id']);
                                            $asset->fields['mac'] = $hrow['mac'];
                                            $houtput .= '<div>' . $this->getAssetLink($asset) . '</div>';
                                        }
                                    }

                                    $houtput .= "</div>";
                                    $output .= $houtput;
                                }
                            }
                            break;
                        case 40:
                            $co_class = '';
                            switch ($port['ifstatus']) {
                                case 1: //up
                                    $co_class = 'ti-link netport text-green';
                                    $title = __('Connected');
                                    break;
                                case 2: //down
                                    $co_class = 'ti-unlink netport text-red';
                                    $title = __('Not connected');
                                    break;
                                case 3: //testing
                                    $co_class = 'ti-link netport text-orange';
                                    $title = __('Testing');
                                    break;
                                case 5: //dormant
                                    $co_class = 'ti-link netport text-gray';
                                    $title = __('Dormant');
                                    break;
                                case 4: //unknown
                                default:
                                    $co_class = 'ti-help';
                                    $title = __('Unknown');
                                    break;
                            }
                            $output .= sprintf(
                                '<i class="ti %s" title="%s"></i> <span class="sr-only">%s</span>',
                                htmlescape($co_class),
                                htmlescape($title),
                                htmlescape($title)
                            );
                            break;
                        case 41:
                            if ($port['ifstatus'] == 1) {
                                $output .= sprintf("<i class='ti ti-circle-filled text-green' title='%s'></i>", __s('Connected'));
                            } elseif (!empty($port['lastup'])) {
                                $time = strtotime(date('Y-m-d H:i:s')) - strtotime($port['lastup']);
                                $output .= htmlescape(Html::timestampToString($time, false));
                            }
                            break;
                        case 126: //IP address
                            $ips_iterator = $this->getIpsForPort('NetworkPort', $port['id']);
                            $ip_names = [];
                            foreach ($ips_iterator as $iprow) {
                                $ip_names[] = htmlescape($iprow['name']);
                            }
                            $output .= implode('<br />', $ip_names);
                            break;
                        case 127:
                            $names_iterator = $DB->request([
                                'FROM'   => 'glpi_networknames',
                                'WHERE'  => [
                                    'itemtype'  => 'NetworkPort',
                                    'items_id'  => $port['id'],
                                ],
                            ]);
                            $network_names = [];
                            foreach ($names_iterator as $namerow) {
                                $netname = new NetworkName();
                                $netname->getFromDB($namerow['id']);
                                $network_names[] = $netname->getLink();
                            }
                            $output .= implode('<br />', $network_names);
                            break;
                        default:
                            if (
                                isset($option["linkfield"])
                                && isset($option['joinparams'])
                            ) {
                                $netport_table = $this->getTable();
                                $already_link_tables = [];
                                $join = Search::addLeftJoin(
                                    self::class,
                                    $netport_table,
                                    $already_link_tables,
                                    $option["table"],
                                    $option["linkfield"],
                                    false,
                                    '',
                                    $option["joinparams"],
                                    $option["field"]
                                );
                                $iterator = $DB->request([
                                    'FROM'   => $netport_table,
                                    'JOIN'   => [new QueryExpression($join)],
                                    'WHERE'  => [
                                        "$netport_table.id" => $port['id'],
                                    ],
                                ]);
                                foreach ($iterator as $row) {
                                    $output .= htmlescape($row[$option['field']]);
                                }
                            } else {
                                $output .= htmlescape($port[$option['field']]);
                            }
                            break;
                    }
                }
            }
            $whole_output .= "<td class='$td_class'>" . $output . "</td>";
        }
        $whole_output .= '</tr>';
        return $whole_output;
    }

    protected function getIpsForPort($itemtype, $items_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'ipa.*',
            'FROM'   => IPAddress::getTable() . ' AS ipa',
            'INNER JOIN'   => [
                NetworkName::getTable() . ' AS netname' => [
                    'ON' => [
                        'ipa' => 'items_id',
                        'netname' => 'id', [
                            'AND' => [
                                'ipa.itemtype' => 'NetworkName',
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'  => [
                'netname.items_id'   => $items_id,
                'netname.itemtype'   => $itemtype,
            ],
        ]);
        return $iterator;
    }

    private function getAssetLink(CommonDBTM $asset): string
    {

        if ($asset instanceof NetworkPort) {
            $link = $asset->getLink();
        } else {
            $link = sprintf(
                '<i class="%1$s"></i> %2$s </i>',
                htmlescape($asset->getIcon()),
                $asset->getLink(),
            );
        }


        if (!empty($asset->fields['mac'])) {
            $link .= '<br/>' . htmlescape($asset->fields['mac']);
        }

        $ips_iterator = $this->getIpsForPort($asset->getType(), $asset->getID());
        $ips = '';
        foreach ($ips_iterator as $ipa) {
            $ips .= ' ' . htmlescape($ipa['name']);
        }
        if (!empty($ips)) {
            $link .= '<br/>' . $ips;
        }

        return $link;
    }

    public function showForm($ID, array $options = [])
    {
        if (!isset($options['several'])) {
            $options['several'] = false;
        }

        if (!self::canView()) {
            return false;
        }

        $recursiveItems = $this->recursivelyGetItems();
        if (count($recursiveItems) > 0) {
            $lastItem             = $recursiveItems[count($recursiveItems) - 1];
            $options['entities_id'] = $lastItem->getField('entities_id');
        } else {
            $options['entities_id'] = $_SESSION['glpiactive_entity'];
        }

        TemplateRenderer::getInstance()->display('pages/assets/networkport/form.html.twig', [
            'item'   => $this,
            'recursive_items' => $recursiveItems,
            'instantiation' => $this->getInstantiation(),
            'params' => $options,
            'no_inventory_footer' => true,
        ]);

        return true;
    }

    /**
     * @param ?string $itemtype
     * @return array
     */
    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'network',
            'name'               => __('Networking'),
        ];

        $joinparams = ['jointype' => 'itemtype_item'];

        $tab[] = [
            'id'                 => '21',
            'table'              => 'glpi_networkports',
            'field'              => 'mac',
            'name'               => __('MAC address'),
            'datatype'           => 'mac',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '87',
            'table'              => 'glpi_networkports',
            'field'              => 'instantiation_type',
            'name'               => NetworkPortType::getTypeName(1),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'networkport_instantiations',
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $networkNameJoin = ['jointype'          => 'itemtype_item',
            'specific_itemtype' => 'NetworkPort',
            'condition'         => ['NEWTABLE.is_deleted' => 0],
            'beforejoin'        => ['table'      => 'glpi_networkports',
                'joinparams' => $joinparams,
            ],
        ];
        NetworkName::rawSearchOptionsToAdd($tab, $networkNameJoin);

        $instantjoin = ['jointype'   => 'child',
            'beforejoin' => ['table'      => 'glpi_networkports',
                'joinparams' => $joinparams,
            ],
        ];
        foreach (self::getNetworkPortInstantiations() as $instantiationType) {
            $instantiationType::getSearchOptionsToAddForInstantiation($tab, $instantjoin);
        }

        $netportjoin = [
            [
                'table'      => 'glpi_networkports',
                'joinparams' => ['jointype' => 'itemtype_item'],
            ],
            [
                'table'      => 'glpi_networkports_vlans',
                'joinparams' => ['jointype' => 'child'],
            ],
        ];

        $tab[] = [
            'id'                 => '88',
            'table'              => 'glpi_vlans',
            'field'              => 'name',
            'name'               => Vlan::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => ['beforejoin' => $netportjoin],
        ];

        return $tab;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = $checkitem !== null && $checkitem->canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        // add purge action if main item is not dynamic
        // NetworkPort delete / purge are handled a different way on dynamic asset (lock)
        if ($checkitem instanceof CommonDBTM && !$checkitem->isDynamic()) {
            $actions['NetworkPort' . MassiveAction::CLASS_ACTION_SEPARATOR . 'purge']    = __s('Delete permanently');
        }

        if ($isadmin) {
            $vlan_prefix                    = 'NetworkPort_Vlan' . MassiveAction::CLASS_ACTION_SEPARATOR;
            $actions[$vlan_prefix . 'add']    = __s('Associate a VLAN');
            $actions[$vlan_prefix . 'remove'] = __s('Dissociate a VLAN');
        }
        return $actions;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'purge':
                foreach ($ids as $id) {
                    if ($item->can($id, PURGE)) {
                        // Only mark deletion for
                        if (!$item->isDeleted()) {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                            $ma->addMessage(sprintf(__s('%1$s: %2$s'), $item->getLink(), __s('Item need to be deleted first')));
                        } else {
                            $delete_array = ['id' => $id];

                            if ($item->delete($delete_array, true)) {
                                $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        }
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;
        }
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'type'               => 'text',
            'massiveaction'      => false,
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'logical_number',
            'name'               => _n('Port number', 'Port numbers', 1),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'mac',
            'name'               => __('MAC address'),
            'datatype'           => 'mac',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'instantiation_type',
            'name'               => NetworkPortType::getTypeName(1),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'networkport_instantiations',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'is_deleted',
            'name'               => __('Deleted'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        if ($this->isField('sockets_id')) {
            $tab[] = [
                'id'                 => '9',
                'table'              => 'glpi_sockets',
                'field'              => 'name',
                'name'               => Socket::getTypeName(1),
                'datatype'           => 'dropdown',
            ];
        }

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => static::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'networkport_types',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'items_id',
            'name'               => __('ID'),
            'datatype'           => 'integer',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'    => '30',
            'table' => static::getTable(),
            'field' => 'ifmtu',
            'name'  => __('MTU'),
        ];

        $tab[] = [
            'id'    => '31',
            'table' => static::getTable(),
            'field' => 'ifspeed',
            'name'  => __('Speed'),
        ];

        $tab[] = [
            'id'    => '32',
            'table' => static::getTable(),
            'field' => 'ifinternalstatus',
            'name'  => __('Internal status'),
        ];

        $tab[] = [
            'id'    => '33',
            'table' => static::getTable(),
            'field' => 'iflastchange',
            'name'  => __('Last change'),
        ];

        $tab[] = [
            'id'    => '34',
            'table' => static::getTable(),
            'field' => 'ifinbytes',
            'name'  => __('Number of I/O bytes'),
        ];

        $tab[] = [
            'id'    => '35',
            'table' => static::getTable(),
            'field' => 'ifinerrors',
            'name'  => __('Number of I/O errors'),
        ];

        $tab[] = [
            'id'    => '36',
            'table' => static::getTable(),
            'field' => 'portduplex',
            'name'  => __('Duplex'),
        ];

        $netportjoin = [
            [
                'table'      => 'glpi_networkports',
                'joinparams' => ['jointype' => 'itemtype_item'],
            ], [
                'table'      => 'glpi_networkports_vlans',
                'joinparams' => ['jointype' => 'child'],
            ],
        ];

        $tab[] = [
            'id' => '38',
            'table' => Vlan::getTable(),
            'field' => 'name',
            'name' => Vlan::getTypeName(1),
            'datatype' => 'dropdown',
            'forcegroupby' => true,
            'massiveaction' => false,
            'joinparams' => ['beforejoin' => $netportjoin],
        ];

        $tab[] = [
            'id'    => '39',
            'table' => static::getTable(),
            'field' => '_virtual_connected_to',
            'name' => __('Connected to'),
            'nosearch' => true,
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'    => '40',
            'table' => static::getTable(),
            'field' => 'ifconnectionstatus',
            'name'  => _n('Connection', 'Connections', 1),
        ];

        $tab[] = [
            'id'       => '41',
            'table'    => static::getTable(),
            'field'    => 'lastup',
            'name'     => __('Last connection'),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'    => '42',
            'table' => static::getTable(),
            'field' => 'ifalias',
            'name'  => __('Alias'),
        ];

        $joinparams = ['jointype' => 'itemtype_item'];
        $networkNameJoin = [
            'jointype'          => 'itemtype_item',
            'specific_itemtype' => 'NetworkPort',
            'condition'         => ['NEWTABLE.is_deleted' => 0],
            'beforejoin'        => ['table'      => 'glpi_networkports',
                'joinparams' => $joinparams,
            ],
        ];
        NetworkName::rawSearchOptionsToAdd($tab, $networkNameJoin);

        return $tab;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM) {
            return '';
        }

        // Can exist on template
        $nb = 0;
        if (NetworkEquipment::canView()) {
            if (in_array($item::class, $CFG_GLPI["networkport_types"], true)) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countForItem($item);
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }

        if ($item::class === self::class) {
            $nbAlias = countElementsInTable(
                'glpi_networkportaliases',
                ['networkports_id_alias' => $item->getField('id')]
            );
            if ($nbAlias > 0) {
                $aliases = self::createTabEntry(NetworkPortAlias::getTypeName(Session::getPluralNumber()), $nbAlias, $item::class);
            } else {
                $aliases = '';
            }
            $nbAggregates = countElementsInTable(
                'glpi_networkportaggregates',
                ['networkports_id_list'   => ['LIKE', '%"' . $item->getField('id') . '"%']]
            );
            if ($nbAggregates > 0) {
                $aggregates = self::createTabEntry(
                    NetworkPortAggregate::getTypeName(Session::getPluralNumber()),
                    $nbAggregates,
                    $item::class
                );
            } else {
                $aggregates = '';
            }
            if (!empty($aggregates) && !empty($aliases)) {
                return $aliases . '/' . $aggregates;
            }
            return $aliases . $aggregates;
        }
        return '';
    }

    /**
     * @param CommonDBTM $item
     * @return int
     */
    public static function countForItem(CommonDBTM $item): int
    {
        return countElementsInTable(
            'glpi_networkports',
            [
                'itemtype'   => $item::class,
                'items_id'   => $item->getField('id'),
                'is_deleted' => 0,
            ]
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if (
            $item::class === self::class
            || in_array($item::class, $CFG_GLPI["networkport_types"], true)
        ) {
            self::showForItem($item, $withtemplate);
        }
        return true;
    }

    public static function getConnexityMassiveActionsSpecificities()
    {
        $specificities                           = parent::getConnexityMassiveActionsSpecificities();

        $specificities['reaffect']               = true;
        $specificities['itemtypes']              = ['Computer', 'NetworkEquipment'];

        $specificities['normalized']['unaffect'] = [];
        $specificities['action_name']['affect']  = _sx('button', 'Move');

        return $specificities;
    }

    public function getLink($options = [])
    {
        $port_link = parent::getLink($options);

        if (!isset($this->fields['itemtype']) || !class_exists($this->fields['itemtype'])) {
            return $port_link;
        }

        $itemtype = $this->fields['itemtype'];
        $equipment = getItemForItemtype($itemtype);

        if ($equipment && $equipment->getFromDB($this->fields['items_id'])) {
            return sprintf(
                '<i class="%1$s"></i> %2$s > <i class="%3$s"></i> %4$s',
                htmlescape($equipment::getIcon()),
                $equipment->getLink(),
                htmlescape(self::getIcon()),
                $port_link,
            );
        }

        return $port_link;
    }

    /**
     * Is port connected to a hub?
     *
     * @param integer $networkports_id Port ID
     *
     * @return boolean
     */
    public function isHubConnected($networkports_id): bool
    {
        global $DB;

        $wired = new NetworkPort_NetworkPort();
        $opposite = $wired->getOppositeContact($networkports_id);

        if (empty($opposite)) {
            return false;
        }

        $table = static::getTable();
        $result = $DB->request([
            'FROM'         => Unmanaged::getTable(),
            'COUNT'        => 'cpt',
            'INNER JOIN'   => [
                $table => [
                    'ON' => [
                        $table       => 'items_id',
                        Unmanaged::getTable()   => 'id', [
                            'AND' => [
                                $table . '.itemtype' => Unmanaged::getType(),
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'        => [
                'hub' => 1,
                $table . '.id' => $opposite,
            ],
        ])->current();

        return ($result['cpt'] > 0);
    }

    public function getNonLoggedFields(): array
    {
        return [
            'ifinbytes',
            'ifoutbytes',
            'ifinerrors',
            'ifouterrors',
        ];
    }

    public static function getIcon()
    {
        return "ti ti-network";
    }
}
