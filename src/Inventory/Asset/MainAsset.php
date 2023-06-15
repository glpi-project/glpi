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

namespace Glpi\Inventory\Asset;

use Auth;
use AutoUpdateSystem;
use Blacklist;
use CommonDBTM;
use Dropdown;
use Glpi\Inventory\Asset\Printer as AssetPrinter;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Request;
use Glpi\Toolbox\Sanitizer;
use NetworkEquipment;
use Printer;
use RefusedEquipment;
use RuleImportAssetCollection;
use RuleImportEntityCollection;
use RuleLocationCollection;
use RuleMatchedLog;
use stdClass;
use Transfer;

abstract class MainAsset extends InventoryAsset
{
    use InventoryNetworkPort;

    /** @var array */
    protected $extra_data = [
        'hardware'     => null,
        'bios'         => null,
        'users'        => null,
        '\Glpi\Inventory\Asset\NetworkCard' => null
    ];
    /** @var mixed */
    protected $raw_data;
   /* @var array */
    protected $hardware;
    /** @var integer */
    protected $states_id_default;
    /** @var \stdClass */
    private $current_data;
    /** @var array */
    protected $assets = [];
    /** @var Conf */
    protected $conf;
    /** @var array */
    protected $refused = [];
    /** @var array */
    protected $inventoried = [];
    /** @var boolean */
    protected $partial = false;
    /** @var bool */
    protected bool $is_discovery = false;

    protected $current_key;

    public function __construct(CommonDBTM $item, $data)
    {
        $namespaced = explode('\\', static::class);
        $this->itemtype = array_pop($namespaced);
        $this->item = $item;
        //store raw data for reference
        $this->raw_data = $data;
    }

    /**
     * Get model foreign key field name
     *
     * @return string
     */
    abstract protected function getModelsFieldName();

    /**
     * Get model foreign key field name
     *
     * @return string
     */
    abstract protected function getTypesFieldName();

    public function prepare(): array
    {
        $raw_data = $this->raw_data;
        if (!is_array($raw_data)) {
            $raw_data = [$raw_data];
        }

        $this->data = [];
        foreach ($raw_data as $entry) {
            if (property_exists($entry, 'partial') && $entry->partial) {
                $this->setPartial();
            }

            $val = new \stdClass();

            //set update system
            $val->autoupdatesystems_id = $entry->content->autoupdatesystems_id ?? AutoUpdateSystem::NATIVE_INVENTORY;
            $val->last_inventory_update = $_SESSION["glpi_currenttime"];
            $val->is_deleted = 0;

            //try to get "last_boot" only available from "operatingsystem->boot_time" node
            if (
                $this->raw_data instanceof stdClass
                && property_exists($this->raw_data, 'content')
                && property_exists($this->raw_data->content, 'operatingsystem')
                && property_exists($this->raw_data->content->operatingsystem, 'boot_time')
            ) {
                $val->last_boot = $entry->content->operatingsystem->boot_time;
            }

            if (isset($this->extra_data['hardware'])) {
                $this->prepareForHardware($val);
            }

            $this->prepareForUsers($val);

            if (isset($this->extra_data['bios'])) {
                $this->prepareForBios($val);
            }

            if (method_exists($this, 'postPrepare')) {
                $this->postPrepare($val);
            }

            $this->data[] = $val;
        }

        return $this->data;
    }

    /**
     * Prepare hardware information
     *
     * @param stdClass $val
     *
     * @return void
     */
    protected function prepareForHardware($val)
    {
        $hardware = (object)$this->extra_data['hardware'];

        $hw_mapping = [
            'name'           => 'name',
            'winprodid'      => 'licenseid',
            'winprodkey'     => 'license_number',
            'workgroup'      => 'domains_id',
            'lastloggeduser' => 'users_id',
        ];

        foreach ($hw_mapping as $origin => $dest) {
            if (property_exists($hardware, $origin)) {
                $hardware->$dest = $hardware->$origin;
            }
        }
        $this->hardware = $hardware;

        foreach ($hardware as $key => $property) {
            $val->$key = $property;
        }

        // * Type of the asset
        $types_id = $this->getTypesFieldName();
        if (
            property_exists($hardware, 'vmsystem')
            && $hardware->vmsystem != ''
            && $hardware->vmsystem != 'Physical'
        ) {
            $val->$types_id = $hardware->vmsystem;
            // HACK FOR BSDJail, remove serial and UUID (because it's of host, not container)
            if ($hardware->vmsystem == 'BSDJail') {
                if (property_exists($val, 'serial')) {
                    $val->serial = '';
                }
                $val->uuid .= '-' . $val->name;
            }
        } else {
            if (array_key_exists('bios', $this->extra_data)) {
                $bios = (object)$this->extra_data['bios'];
                if (
                    property_exists($hardware, 'chassis_type')
                    && !empty($hardware->chassis_type)
                ) {
                    $val->$types_id = $hardware->chassis_type;
                } else if (
                    property_exists($bios, 'type')
                    && !empty($bios->type)
                ) {
                    $val->$types_id = $bios->type;
                } else if (
                    property_exists($bios, 'mmodel')
                    && !empty($bios->mmodel)
                ) {
                    $val->$types_id = $bios->mmodel;
                }
            }
        }
    }

    /**
     * Prepare users information
     *
     * @param stdClass $val
     *
     * @return void
     */
    protected function prepareForUsers($val)
    {
        global $DB;

        if ($this->isPartial()) {
            unset($val->users_id);
            return;
        }

        if (property_exists($val, 'users_id')) {
            if ($val->users_id == '') {
                unset($val->users_id);
            } else {
                $val->contact = $val->users_id;
                $split_user = explode("@", $val->users_id);
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_users',
                    'WHERE'  => [
                        'name'   => $split_user[0]
                    ],
                    'LIMIT'  => 1
                ]);

                if (count($iterator)) {
                    $result = $iterator->current();
                    $val->users_id = $result['id'];
                } else {
                    $val->users_id = 0;
                }
            }
        }

        // * USERS
        $cnt = 0;
        if (isset($this->extra_data['users'])) {
            if (count($this->extra_data['users']) > 0) {
                $user_temp = '';
                if (property_exists($val, 'contact')) {
                    $user_temp = $val->contact;
                }
                $val->contact = '';
            }
            foreach ($this->extra_data['users'] as $a_users) {
                $user = '';
                if (property_exists($a_users, 'login')) {
                    $user = $a_users->login;
                    if (
                        property_exists($a_users, 'domain')
                        && !empty($a_users->domain)
                    ) {
                        $user .= "@" . $a_users->domain;
                    }
                }
                if ($cnt == 0) {
                    if (property_exists($a_users, 'login')) {
                       // Search on domain
                        $where_add = [];
                        if (
                            property_exists($a_users, 'domain')
                            && !empty($a_users->domain)
                        ) {
                            $ldaps = $DB->request(
                                'glpi_authldaps',
                                ['WHERE'  => ['inventory_domain' => $a_users->domain]]
                            );
                             $ldaps_ids = [];
                            foreach ($ldaps as $data_LDAP) {
                                $ldaps_ids[] = $data_LDAP['id'];
                            }
                            if (count($ldaps_ids)) {
                                $where_add['authtype'] = Auth::LDAP;
                                $where_add['auths_id'] = $ldaps_ids;
                            }
                        }
                        $iterator = $DB->request([
                            'SELECT' => ['id'],
                            'FROM'   => 'glpi_users',
                            'WHERE'  => [
                                'name'   => $a_users->login
                            ] + $where_add,
                            'LIMIT'  => 1
                        ]);
                        if ($row = $iterator->current()) {
                              $val->users_id = $row['id'];
                        }
                    }
                }

                if ($user != '') {
                    if (property_exists($val, 'contact')) {
                        if ($val->contact == '') {
                             $val->contact = $user;
                        } else {
                            $val->contact .= "/" . $user;
                        }
                    } else {
                        $val->contact = $user;
                    }
                }
                $cnt++;
            }
            if (empty($val->contact)) {
                $val->contact = $user_temp ?? '';
            }
        }
    }

    protected function prepareForBios($val)
    {
        $bios = (object)$this->extra_data['bios'];

        if (property_exists($bios, 'assettag') && !empty($bios->assettag)) {
            $val->otherserial = $bios->assettag;
        }

        if (property_exists($bios, 'smanufacturer') && !empty($bios->smanufacturer)) {
            $val->manufacturers_id = $bios->smanufacturer;
        } else if (property_exists($bios, 'mmanufacturer') && !empty($bios->mmanufacturer)) {
            $val->manufacturers_id = $bios->mmanufacturer;
            $val->mmanufacturer = $bios->mmanufacturer;
        } else if (property_exists($bios, 'bmanufacturer') && !empty($bios->bmanufacturer)) {
            $val->manufacturers_id = $bios->bmanufacturer;
            $val->bmanufacturer = $bios->bmanufacturer;
        }

        $models_id = $this->getModelsFieldName();
        if (property_exists($bios, 'smodel') && $bios->smodel != '') {
            $val->$models_id = $bios->smodel;
        } else if (property_exists($bios, 'mmodel') && $bios->mmodel != '') {
            $val->$models_id = $bios->mmodel;
            $val->model = $bios->mmodel;
        }

        if (property_exists($bios, 'ssn')) {
            $val->serial = trim($bios->ssn);
            // HP patch for serial begin with 'S'
            if (
                property_exists($val, 'manufacturers_id')
                && strstr($val->manufacturers_id, "ewlett")
                && preg_match("/^[sS]/", $val->serial)
            ) {
                $val->serial = trim(
                    preg_replace(
                        "/^[sS]/",
                        "",
                        $val->serial
                    )
                );
            }
        }

        if (property_exists($bios, 'msn')) {
            $val->mserial = $bios->msn;
        }
    }

    /**
     * Prepare input rules input, for both Entities rules and Import ones
     *
     * @param \stdClass $val Current data values
     *
     * @return array
     */
    public function prepareAllRulesInput(\stdClass $val): array
    {
        $input = [];

        if (isset($this->getAgent()->fields['tag'])) {
            $input['tag'] = $this->getAgent()->fields['tag'];
        }

        if (isset($this->getAgent()->fields['deviceid'])) {
            $input['deviceid'] = $this->getAgent()->fields['deviceid'];
        }

        $models_id = $this->getModelsFieldName();
        foreach ($val as $prop => $value) {
            switch ($prop) {
                case $models_id:
                    $prop = 'model';
                    break;
                case 'domains_id':
                    $prop = 'domain';
                    break;
                case 'ips':
                    $prop = 'ip';
                    break;
            }
            if (!empty($value)) {
                $input[$prop] = $value;
            }
        }

        if (!isset($input['name'])) {
            $input['name'] = '';
        }

        if (isset($this->extra_data['\Glpi\Inventory\Asset\NetworkCard'])) {
            $blacklist = new Blacklist();
            foreach ($this->extra_data['\Glpi\Inventory\Asset\NetworkCard'] as $networkcard) {
                $netports = $networkcard->getNetworkPorts();
                $this->ports += $netports;
                foreach ($netports as $network) {
                    if (
                        (property_exists($network, 'virtualdev')
                        //if not virtualdev or is it and inventory conf allow networkcardvirtual import
                        && ($network->virtualdev != 1  || $network->virtualdev == 1 && $this->conf->component_networkcardvirtual))
                        || !property_exists($network, 'virtualdev') //if not virtual
                    ) {
                        if (property_exists($network, 'mac') && !empty($network->mac)) {
                            if ('' != $blacklist->process(Blacklist::MAC, $network->mac)) {
                                $input['mac'][] = $network->mac;
                            }
                        }
                        foreach ($network->ipaddress as $ip) {
                            if ('' != $blacklist->process(Blacklist::IP, $ip)) {
                                $input['ip'][] = $ip;
                            }
                        }
                        if (property_exists($network, 'subnet') && !empty($network->subnet)) {
                                $input['subnet'][] = $network->subnet;
                        }
                    }
                }

                // Case of virtualmachines
                if (
                    !isset($input['mac'])
                     && !isset($input['ip'])
                ) {
                    foreach ($netports as $network) {
                        if (property_exists($network, 'mac') && !empty($network->mac)) {
                             $input['mac'][] = $network->mac;
                        }
                        foreach ($network->ipaddress as $ip) {
                            if ($ip != '127.0.0.1' && $ip != '::1') {
                                $input['ip'][] = $ip;
                            }
                        }
                        if (property_exists($network, 'subnet') && !empty($network->subnet)) {
                            $input['subnet'][] = $network->subnet;
                        }
                    }
                }
            }
        }

        $input['itemtype'] = $this->item->getType();

        // * entity rules
        $input['entities_id'] = $this->entities_id;

        return $input;
    }

    /**
     * Prepare input for Entities rules
     *
     * @param \stdClass $val   Current data values
     * @param array     $input Input processed or all rules
     *
     * @return array
     */
    public function prepareEntitiesRulesInput(\stdClass $val, array $input): array
    {
        if (property_exists($val, 'domains_id') && (!empty($val->domains_id))) {
            $input['domain'] = $val->domains_id;
        }

        if (isset($input['serial'])) {
            $input['serialnumber'] = $input['serial'];
        }

        return $input;
    }

    public function handle()
    {
        $blacklist = new Blacklist();

        foreach ($this->data as $key => $data) {
            $blacklist->processBlackList($data);

            $this->current_key = $key;
            $input = $this->prepareAllRulesInput($data);

            if (!$this->isAccessPoint($data)) {
                $entity_input = $this->prepareEntitiesRulesInput($data, $input);

                $ruleEntity = new RuleImportEntityCollection();
                $ruleEntity->getCollectionPart();
                $dataEntity = $ruleEntity->processAllRules($entity_input, []);

                if (isset($dataEntity['_ignore_import'])) {
                    $input['rules_id'] = $dataEntity['rules_id'];
                    $this->addRefused($input);
                    return;
                }

                if (!isset($dataEntity['entities_id']) || $dataEntity['entities_id'] == -1) {
                    $input['entities_id'] = $this->conf->entities_id_default ?? 0; //use default entity
                } else {
                    $input['entities_id'] = $dataEntity['entities_id'];
                }
                $this->entities_id = $input['entities_id'];

                // get data from rules (like locations_id, states_id, groups_id_tech, etc)
                // we don't want virtual action (prefixed by _)
                $ruleentity_actions = $ruleEntity->getRuleClass()->getAllActions();
                foreach ($ruleentity_actions as $action_key => $action_data) {
                    if (
                        $action_key[0] !== '_'
                        && $action_key !== "entities_id"
                        && isset($dataEntity[$action_key])
                    ) {
                        $this->ruleentity_data[$action_key] = $dataEntity[$action_key];
                    }
                }

                $ruleLocation = new RuleLocationCollection();
                $ruleLocation->getCollectionPart();
                $dataLocation = $ruleLocation->processAllRules($input, []);

                if (isset($dataLocation['locations_id']) && $dataLocation['locations_id'] != -1) {
                    $this->rulelocation_data['locations_id'] = $dataLocation['locations_id'];
                }
            }

            //call rules on current collected data to find item
            //a callback on rulepassed() will be done if one is found.
            $rule = new RuleImportAssetCollection();
            $rule->getCollectionPart();
            $datarules = $rule->processAllRules($input, [], ['class' => $this]);

            if (isset($datarules['_no_rule_matches']) and ($datarules['_no_rule_matches'] == '1')) {
                //no rule matched, this is a new one
                $this->rulepassed(0, $this->item->getType(), null);
            } else if (!isset($datarules['found_inventories'])) {
                if ($this->isAccessPoint($data)) {
                    //Only main item is stored as refused, not all APs
                    unset($this->data[$key]);
                } else {
                    $input['rules_id'] = $datarules['rules_id'];
                    $this->addRefused($input);
                }
            }
        }
    }

    protected function addRefused(array $input)
    {
        $refused_input = [
            'name'         => $input['name'],
            'itemtype'     => $input['itemtype'],
            'serial'       => $input['serial'] ?? '',
            'ip'           => $input['ip'] ?? '',
            'mac'          => $input['mac'] ?? '',
            'uuid'         => $input['uuid'] ?? '',
            'rules_id'     => $input['rules_id'],
            'entities_id'  => $input['entities_id'],
            'autoupdatesystems_id' => $input['autoupdatesystems_id']
        ];

        foreach (['ip', 'mac'] as $array) {
            if (is_array($refused_input[$array])) {
                $refused_input[$array] = exportArrayToDB($refused_input[$array]);
            }
        }

        if (!is_numeric($input['autoupdatesystems_id'])) {
            $system_name = Sanitizer::sanitize($input['autoupdatesystems_id']);
            $auto_update_system = new AutoUpdateSystem();
            if ($auto_update_system->getFromDBByCrit(['name' => $system_name])) {
                // Load from DB
                $input['autoupdatesystems_id'] = $auto_update_system->getID();
            } else {
                // Import
                $input['autoupdatesystems_id'] = Dropdown::importExternal(
                    getItemtypeForForeignKeyField('autoupdatesystems_id'),
                    $system_name,
                    $input['entities_id']
                );
            }
        }
        $refused_input['autoupdatesystems_id'] = $input['autoupdatesystems_id'];

        $refused = new \RefusedEquipment();
        $refused->add(Sanitizer::sanitize($refused_input));
        $this->refused[] = $refused;
    }

    public function checkConf(Conf $conf): bool
    {
        $this->conf = $conf;
        $this->states_id_default = $conf->states_id_default;
        return true;
    }

    /**
     * After rule engine passed, update task (log) and create item if required
     *
     * @param integer $items_id id of the item (0 if new)
     * @param string  $itemtype Item type
     * @param integer $rules_id Matched rule id, if any
     * @param integer $ports_id Matched port id, if any
     */
    public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = 0)
    {
        global $CFG_GLPI, $DB;

        $key = $this->current_key;
        $val = &$this->data[$key];
        $entities_id = $this->entities_id;
        $val->is_dynamic = 1;
        $val->entities_id = $entities_id;
        $default_states_id = $this->states_id_default ?? 0;
        if ($items_id != 0 && $default_states_id != '-1') {
            $val->states_id = $default_states_id;
        } elseif ($items_id == 0) {
            //if create mode default states_id can't be '-1' put 0 if needed
            $val->states_id = $default_states_id > 0 ? $default_states_id : 0;
        }

        // append data from RuleImportEntity
        foreach ($this->ruleentity_data as $attribute => $value) {
            $known_key = md5($attribute . $value);
            $this->known_links[$known_key] = $value;
            $val->{$attribute} = $value;
        }
        // append data from RuleLocation
        foreach ($this->rulelocation_data as $attribute => $value) {
            $known_key = md5($attribute . $value);
            $this->known_links[$known_key] = $value;
            $val->{$attribute} = $value;
        }

        $orig_glpiactive_entity = $_SESSION['glpiactive_entity'] ?? null;
        $orig_glpiactiveentities = $_SESSION['glpiactiveentities'] ?? null;
        $orig_glpiactiveentities_string = $_SESSION['glpiactiveentities_string'] ?? null;

        //set entity in session
        $_SESSION['glpiactiveentities']        = [$entities_id];
        $_SESSION['glpiactiveentities_string'] = $entities_id;
        $_SESSION['glpiactive_entity']         = $entities_id;

        if ($items_id != 0) {
            $this->item->getFromDB($items_id);
        }

        //handleLinks relies on $this->data; update it before the call
        $this->handleLinks();

        if ($items_id == 0) {
            $input = $this->handleInput($val, $this->item);
            unset($input['ap_port']);
            unset($input['firmware']);
            $items_id = $this->item->add(Sanitizer::sanitize($input));
            $this->setNew();
        }

        $val->id = $this->item->fields['id'];

        if ($entities_id == -1) {
            $entities_id = $this->item->fields['entities_id'];
        }
        $val->entities_id = $entities_id;

        //handle domains
        if (property_exists($val, 'domains_id')) {
            $domain = new \Domain();
            $matching_domains = $DB->request([
                'FROM' => $domain->getTable(),
                'WHERE' => [
                    'name' => Sanitizer::sanitize($val->domains_id),
                    'is_deleted' => 0,
                ] + getEntitiesRestrictCriteria($domain->getTable(), '', $entities_id, true),
                'LIMIT' => 1, // Get the first domain, as we assume that a domain should not be declared multiple times in the same entity scope
            ]);
            if ($matching_domains->count() > 0) {
                $domain->getFromResultSet($matching_domains->current());
            } else {
                $domain->add(
                    Sanitizer::sanitize([
                        'name' => $val->domains_id,
                        'entities_id' => $entities_id,
                    ]),
                    [],
                    false
                );
            }

            $ditem = new \Domain_Item();

            $criteria = [
                'domains_id' => $domain->getID(),
                'itemtype' => $itemtype,
                'items_id' => $items_id
            ];
            if (!$ditem->getFromDBByCrit($criteria)) {
                $ditem->add($criteria + ['domainrelations_id' => \DomainRelation::BELONGS, 'is_dynamic' => 1], [], false);
            }

            //cleanup old dynamic relations
            $ditem->deleteByCriteria(
                [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                    'domainrelations_id' => \DomainRelation::BELONGS,
                    'is_dynamic' => 1,
                    ['NOT' => ['domains_id' => $domain->getID()]]
                ],
                0,
                0
            );
        }


        if ($entities_id != $this->item->fields['entities_id']) {
            //asset entity has changed in rules; do transfer
            $doTransfer = \Entity::getUsedConfig('transfers_strategy', $this->item->fields['entities_id'], 'transfers_id', 0);
            $transfer = new Transfer();
            if ($doTransfer > 0 && $transfer->getFromDB($doTransfer)) {
                $item_to_transfer = [$this->itemtype => [$items_id => $items_id]];
                $transfer->moveItems($item_to_transfer, $entities_id, $transfer->fields);
                //and set new entity in session
                $_SESSION['glpiactiveentities']        = [$entities_id];
                $_SESSION['glpiactiveentities_string'] = $entities_id;
                $_SESSION['glpiactive_entity']         = $entities_id;
            } else {
                //no transfert so revert to old entities_id
                $val->entities_id = $this->item->fields['entities_id']; //for GLPI item
                $this->entities_id = $val->entities_id; //for this class (usefull for handleAsset step)
                $this->agent->fields['entities_id'] = $this->item->fields['entities_id']; //for Agent
            }
        }

        if (in_array($itemtype, $CFG_GLPI['agent_types'])) {
            $this->agent->update(['id' => $this->agent->fields['id'], 'items_id' => $items_id, 'entities_id' => $val->entities_id]);
        } else {
            $this->agent->fields['items_id'] = $items_id;
            $this->agent->fields['entities_id'] = $entities_id;
        }

        //check for any old agent to remove
        $agent = new \Agent();
        $agent->deleteByCriteria([
            'itemtype' => $this->item->getType(),
            'items_id' => $items_id,
            'NOT' => [
                'id' => $this->agent->fields['id']
            ]
        ]);

        if ($this->is_discovery === true && !$this->isNew()) {
            //if NetworkEquipement
            //Or printer that has not changed its IP
            //do not update to prevents discoveries to remove all ports, IPs and so on found with network inventory
            if (
                $itemtype == NetworkEquipment::getType()
                ||
                (
                $itemtype == Printer::getType()
                && !AssetPrinter::needToBeUpdatedFromDiscovery($this->item, $val)
                )
            ) {
                //only update autoupdatesystems_id, last_inventory_update, snmpcredentials_id
                $input = $this->handleInput($val, $this->item);
                $this->item->update(Sanitizer::sanitize(['id' => $input['id'],
                    'autoupdatesystems_id'  => $input['autoupdatesystems_id'],
                    'last_inventory_update' => $input['last_inventory_update'],
                    'snmpcredentials_id'    => $input['snmpcredentials_id'],
                    'is_dynamic'            => true
                ]));
                return;
            }
        }

        //Ports are handled a different way on network equipments and printers
        if (
            $this->item->getType() != 'NetworkEquipment'
            && $this->item->getType() != 'Printer'
        ) {
            $this->handlePorts();
        }

        if (method_exists($this, 'isWirelessController') && $this->isWirelessController()) {
            if (property_exists($val, 'firmware') && $val->firmware instanceof \stdClass) {
                $fw = new Firmware($this->item, [$val->firmware]);
                if ($fw->checkConf($this->conf)) {
                    $fw->setAgent($this->getAgent());
                    $fw->prepare();
                    $fw->handleLinks();
                    $this->assets['Glpi\Inventory\Asset\Firmware'] = [$fw];
                    unset($val->firmware);
                }
            }

            if (property_exists($val, 'ap_port') && method_exists($this, 'setManagementPorts')) {
                $this->setManagementPorts(['management' => $val->ap_port]);
                unset($val->ap_port);
            }
        }

        $input = $this->handleInput($val, $this->item);
        $this->item->update(Sanitizer::sanitize($input));

        if (!($this->item instanceof RefusedEquipment)) {
            $this->handleAssets();
        }

        $rulesmatched = new RuleMatchedLog();
        $inputrulelog = [
            'date'      => date('Y-m-d H:i:s'),
            'rules_id'  => $rules_id,
            'items_id'  => $items_id,
            'itemtype'  => $itemtype,
            'agents_id' => $this->agent->fields['id'],
            'method'    => $this->request_query ?? Request::INVENT_QUERY
        ];
        $rulesmatched->add($inputrulelog, [], false);
        $rulesmatched->cleanOlddata($items_id, $itemtype);

        //keep trace of inventoried assets, but not APs.
        if (!$this->isAccessPoint($val)) {
            $this->inventoried[] = clone $this->item;
        }

        //Restore entities in session
        if ($orig_glpiactive_entity !== null) {
            $_SESSION['glpiactive_entity'] = $orig_glpiactive_entity;
        }

        if ($orig_glpiactiveentities !== null) {
            $_SESSION['glpiactiveentities'] = $orig_glpiactiveentities;
        }

        if ($orig_glpiactiveentities_string !== null) {
            $_SESSION['glpiactiveentities_string'] = $orig_glpiactiveentities_string;
        }
    }

    /**
     * Get modified hardware
     *
     * @return \stdClass
     */
    public function getHardware()
    {
        return $this->hardware;
    }

    /**
     * Retrieve computer entities id
     *
     * @return integer
     */
    public function getEntityID()
    {
        return $this->entities_id;
    }

    public function handleAssets()
    {
        $key = $this->current_key;
        $val = $this->data[$key];

        $mainasset = clone $this;
        $mainasset->setData([$key => $val]);

        $assets_list = $this->assets;

        $controllers = [];
        $ignored_controllers = [];

        //ensure controllers are done last, some components will
        //ask to ignore their associated controller
        if (isset($assets_list['\Glpi\Inventory\Asset\Controller'])) {
            $controllers = $assets_list['\Glpi\Inventory\Asset\Controller'];
            unset($assets_list['\Glpi\Inventory\Asset\Controller']);
        }

        foreach ($assets_list as $assets) {
            foreach ($assets as $asset) {
                $asset->setEntityID($this->getEntityID());
                $asset->setExtraData($this->assets);
                foreach ($this->assets as $asset_type => $asset_list) {
                    if ($asset_type != '\\' . get_class($asset)) {
                        $asset->setExtraData([$asset_type => $asset_list]);
                    }
                }
                $asset->setExtraData(['\\' . get_class($this) => $mainasset]);
                $asset->handleLinks();
                $asset->handle();
                $ignored_controllers = array_merge($ignored_controllers, $asset->getIgnored('controllers'));
            }
        }

        //do controllers
        foreach ($controllers as $asset) {
            $asset->setEntityID($this->getEntityID());
            $asset->setExtraData($this->assets);
            $asset->setExtraData(['\\' . get_class($this) => $mainasset]);
            //do not handle ignored controllers
            $asset->setExtraData(['ignored' => $ignored_controllers]);
            $asset->handleLinks();
            $asset->handle();
        }
    }

    /**
     * Set prepared assets
     *
     * @param array $assets Prepared assets list
     *
     * @return MainAsset
     */
    public function setAssets(array $assets): MainAsset
    {
        $this->assets = $assets;
        $this->setExtraData($assets);
        return $this;
    }

    /**
     * Get current item
     *
     * @return CommonDBTM
     */
    public function getItem(): CommonDBTM
    {
        return $this->item;
    }

    /**
     * Get inventoried assets
     *
     * @return CommonDBTM[]
     */
    public function getInventoried(): array
    {
        return $this->inventoried;
    }

    /**
     * Get refused entries
     *
     * @return RefusedEquipment[]
     */
    public function getRefused(): array
    {
        return $this->refused;
    }

    /**
     * Set partial inventory
     *
     * @return Inventory
     */
    protected function setPartial(): self
    {
        $this->partial = true;
        return $this;
    }

    /**
     * Is inventory partial
     *
     * @return boolean
     */
    public function isPartial(): bool
    {
        return $this->partial;
    }


    /**
     * Is an access point
     *
     * @return boolean
     */
    protected function isAccessPoint($object): bool
    {
        return property_exists($object, 'is_ap') && $object->is_ap == true;
    }

    /**
     * Mark as discovery
     *
     * @param bool $disco
     *
     * @return $this
     */
    public function setDiscovery(bool $disco): self
    {
        $this->is_discovery = $disco;
        return $this;
    }
}
