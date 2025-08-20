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
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;
use Glpi\Features\DCBreadcrumb;
use Glpi\Features\DCBreadcrumbInterface;
use Glpi\Features\Inventoriable;
use Glpi\Features\StateInterface;
use Glpi\Socket;

/**
 *  Computer class
 **/
class Computer extends CommonDBTM implements AssignableItemInterface, DCBreadcrumbInterface, StateInterface
{
    use DCBreadcrumb;
    use Clonable;
    use Inventoriable;
    use Glpi\Features\State;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        post_updateItem as post_updateItemAssignableItem;
    }

    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Item_Disk','ItemVirtualMachine',
        'Item_SoftwareVersion', 'Infocom',
        'NetworkPort', 'ReservationItem',
        'Item_OperatingSystem',
    ];
    // Specific ones
    ///Device container - format $device = array(ID,"device type","ID in device table","specificity value")
    public $devices                     = [];

    public static $rightname                   = 'computer';
    protected $usenotepad               = true;

    public function getCloneRelations(): array
    {
        return [
            Item_OperatingSystem::class,
            Item_Devices::class,
            Infocom::class,
            Item_Disk::class,
            Item_Process::class,
            Item_Environment::class,
            Item_SoftwareVersion::class,
            Item_SoftwareLicense::class,
            Contract_Item::class,
            Document_Item::class,
            NetworkPort::class,
            Asset_PeripheralAsset::class,
            Notepad::class,
            KnowbaseItem_Item::class,
            Item_RemoteManagement::class,
            ItemAntivirus::class,
            Appliance_Item::class,
            Certificate_Item::class,
            // FIXME DatabaseInstance must be a CommonDBChild to be clonable
            // DatabaseInstance::class,
            Domain_Item::class,
            Item_Project::class,
            ItemVirtualMachine::class,
            ManualLink::class,
            Socket::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Computer', 'Computers', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'inventory';
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }


    public static function getMenuShorcut()
    {
        return 'o';
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(Item_OperatingSystem::class, $ong, $options)
         ->addStandardTab(Item_Devices::class, $ong, $options)
         ->addStandardTab(Item_Line::class, $ong, $options)
         ->addStandardTab(Item_Disk::class, $ong, $options)
         ->addStandardTab(Item_SoftwareVersion::class, $ong, $options)
         ->addStandardTab(Item_Process::class, $ong, $options)
         ->addStandardTab(Item_Environment::class, $ong, $options)
         ->addStandardTab(Asset_PeripheralAsset::class, $ong, $options)
         ->addStandardTab(NetworkPort::class, $ong, $options)
         ->addStandardTab(Socket::class, $ong, $options)
         ->addStandardTab(Item_RemoteManagement::class, $ong, $options)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Contract_Item::class, $ong, $options)
         ->addStandardTab(Document_Item::class, $ong, $options)
         ->addStandardTab(ItemVirtualMachine::class, $ong, $options)
         ->addStandardTab(ItemAntivirus::class, $ong, $options)
         ->addStandardTab(KnowbaseItem_Item::class, $ong, $options)
         ->addStandardTab(Item_Ticket::class, $ong, $options)
         ->addStandardTab(Item_Problem::class, $ong, $options)
         ->addStandardTab(Change_Item::class, $ong, $options)
         ->addStandardTab(Item_Project::class, $ong, $options)
         ->addStandardTab(ManualLink::class, $ong, $options)
         ->addStandardTab(Certificate_Item::class, $ong, $options)
         ->addStandardTab(Lock::class, $ong, $options)
         ->addStandardTab(Notepad::class, $ong, $options)
         ->addStandardTab(Reservation::class, $ong, $options)
         ->addStandardTab(Domain_Item::class, $ong, $options)
         ->addStandardTab(Appliance_Item::class, $ong, $options)
         ->addStandardTab(DatabaseInstance::class, $ong, $options)
         ->addStandardTab(RuleMatchedLog::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    public function post_restoreItem()
    {

        $comp_softvers = new Item_SoftwareVersion();
        $comp_softvers->updateDatasForItem('Computer', $this->fields['id']);
    }


    public function post_deleteItem()
    {

        $comp_softvers = new Item_SoftwareVersion();
        $comp_softvers->updateDatasForItem('Computer', $this->fields['id']);
    }


    public function post_updateItem($history = true)
    {
        global $CFG_GLPI, $DB;

        $this->post_updateItemAssignableItem($history);

        $changes = [];
        $update_count = count($this->updates);
        $input = $this->fields;
        for ($i = 0; $i < $update_count; $i++) {
            // Update contact of attached items
            if ($this->updates[$i] == 'contact_num' && Entity::getUsedConfig('is_contact_autoupdate', $this->getEntityID())) {
                $changes['contact_num'] = $input['contact_num'];
            }
            if ($this->updates[$i] == 'contact' && Entity::getUsedConfig('is_contact_autoupdate', $this->getEntityID())) {
                $changes['contact'] = $input['contact'];
            }
            // Update users and groups of attached items
            if (
                $this->updates[$i] == 'users_id'
                && Entity::getUsedConfig('is_user_autoupdate', $this->getEntityID())
            ) {
                $changes['users_id'] = $input['users_id'];
            }
            // Update state of attached items
            if (
                ($this->updates[$i] == 'states_id')
                && (Entity::getUsedConfig('state_autoupdate_mode', $this->getEntityID()) < 0)
            ) {
                $changes['states_id'] = $input['states_id'];
            }
            // Update location of attached items
            if (
                $this->updates[$i] == 'locations_id'
                && Entity::getUsedConfig('is_location_autoupdate', $this->getEntityID())
            ) {
                $changes['locations_id'] = $input['locations_id'];
            }
        }

        // Group is handled differently since the field was changed to support multiple groups and was therefore moved to a separate table
        if (array_key_exists('_groups_id', $this->input) && Entity::getUsedConfig('is_group_autoupdate', $this->getEntityID())) {
            $changes['groups_id'] = $this->input['_groups_id'];
        }

        if (count($changes)) {
            $update_done = false;
            $is_input_dynamic = (bool) ($this->input['is_dynamic'] ?? false);

            // Propagates the changes to linked items
            foreach ($CFG_GLPI['directconnect_types'] as $type) {
                $items_result = $DB->request(
                    [
                        'SELECT' => ['items_id_peripheral'],
                        'FROM'   => Asset_PeripheralAsset::getTable(),
                        'WHERE'  => [
                            'itemtype_peripheral' => $type,
                            'itemtype_asset'      => self::getType(),
                            'items_id_asset'      => $this->fields["id"],
                            'is_deleted'          => 0,
                        ],
                    ]
                );
                $item = getItemForItemtype($type);
                foreach ($items_result as $data) {
                    $tID = $data['items_id_peripheral'];
                    $item->getFromDB($tID);
                    if (!$item->getField('is_global')) {
                        $item_input = $changes;
                        $item_input['id'] = $item->getID();
                        //propage is_dynamic value if needed to prevent locked fields
                        if ((bool) ($item->fields['is_dynamic'] ?? false) && $is_input_dynamic) {
                            $item_input['is_dynamic'] = 1;
                        }
                        if ($item->update($item_input)) {
                            $update_done = true;
                        }
                    }
                }
            }

            $alternate_username_updated = isset($changes['contact']) || isset($changes['contact_num']);
            $user_or_group_updated = isset($changes['groups_id']) || isset($changes['users_id']);

            //fields that are not present for devices
            unset($changes['groups_id']);
            unset($changes['users_id']);
            unset($changes['contact_num']);
            unset($changes['contact']);

            if (count($changes) > 0) {
                // Propagates the changes to linked devices
                foreach (Item_Devices::getDeviceTypes() as $device) {
                    $item = getItemForItemtype($device);
                    $devices_result = $DB->request(
                        [
                            'SELECT' => ['id'],
                            'FROM'   => $item::getTable(),
                            'WHERE'  => [
                                'itemtype'     => self::getType(),
                                'items_id'     => $this->fields["id"],
                                'is_deleted'   => 0,
                            ],
                        ]
                    );
                    foreach ($devices_result as $data) {
                        $tID = $data['id'];
                        $item->getFromDB($tID);
                        $item_input = $changes;
                        $item_input['id'] = $item->getID();
                        //propage is_dynamic value if needed to prevent locked fields
                        if ((bool) ($item->fields['is_dynamic'] ?? false) && $is_input_dynamic) {
                            $item_input['is_dynamic'] = 1;
                        }
                        if ($item->update($item_input)) {
                            $update_done = true;
                        }
                    }
                }
            }

            if ($update_done) {
                if ($alternate_username_updated) {
                    Session::addMessageAfterRedirect(
                        __s('Alternate username updated. The connected items have been updated using this alternate username.'),
                        true
                    );
                }
                if ($user_or_group_updated) {
                    Session::addMessageAfterRedirect(
                        __s('User or group updated. The connected items have been moved in the same values.'),
                        true
                    );
                }
                if (isset($changes['states_id'])) {
                    Session::addMessageAfterRedirect(
                        __s('Status updated. The connected items have been updated using this status.'),
                        true
                    );
                }
                if (isset($changes['locations_id'])) {
                    Session::addMessageAfterRedirect(
                        __s('Location updated. The connected items have been moved in the same location.'),
                        true
                    );
                }
            }
        }
    }


    public function prepareInputForAdd($input)
    {
        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id']);
        unset($input['withtemplate']);

        $input = $this->prepareInputForAddAssignableItem($input);
        return $input;
    }


    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Asset_PeripheralAsset::class,
                ItemAntivirus::class,
                ItemVirtualMachine::class,
                Item_Environment::class,
                Item_Process::class,
            ]
        );
    }


    public function getLinkedItems()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'itemtype_peripheral',
                'items_id_peripheral',
            ],
            'FROM'   => Asset_PeripheralAsset::getTable(),
            'WHERE'  => [
                'itemtype_asset' => self::getType(),
                'items_id_asset' => $this->getID(),
            ],
        ]);

        $tab = [];
        foreach ($iterator as $data) {
            $tab[$data['itemtype_peripheral']][$data['items_id_peripheral']] = $data['items_id_peripheral'];
        }
        return $tab;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions += [
                'Item_OperatingSystem' . MassiveAction::CLASS_ACTION_SEPARATOR . 'update'
                => htmlescape(OperatingSystem::getTypeName()),
                Asset_PeripheralAsset::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
                => "<i class='ti ti-plug'></i>"
                  . _sx('button', 'Connect'),
                'Item_SoftwareVersion' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
                => "<i class='" . htmlescape(Software::getIcon()) . "'></i>"
                  . _sx('button', 'Install'),
                'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
                => "<i class='" . htmlescape(SoftwareLicense::getIcon()) . "'></i>"
                  . _sx('button', 'Add a license'),
                'Domain' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_item'
                => "<i class='" . htmlescape(Domain::getIcon()) . "'></i>"
                    . _sx('button', 'Add a domain'),
                'Domain' . MassiveAction::CLASS_ACTION_SEPARATOR . 'remove_domain'
                => "<i class='" . htmlescape(Domain::getIcon()) . "'></i>"
                    . _sx('button', 'Remove a domain'),
            ];

            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
        }

        return $actions;
    }


    public function rawSearchOptions()
    {

        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_computertypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => 'glpi_computermodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => 'glpi_autoupdatesystems',
            'field'              => 'name',
            'name'               => AutoUpdateSystem::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '47',
            'table'              => $this->getTable(),
            'field'              => 'uuid',
            'name'               => __('UUID'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'last_inventory_update',
            'name'               => __('Last inventory date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'last_boot',
            'name'               => __('Last boot date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => 'glpi_networks',
            'field'              => 'name',
            'name'               => _n('Network', 'Networks', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '65',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        // add operating system search options
        $tab = array_merge($tab, Item_OperatingSystem::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Item_Devices::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Item_Disk::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, ItemVirtualMachine::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, ItemAntivirus::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Monitor::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Peripheral::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Printer::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Phone::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Rack::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Agent::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ComputerModel::rawSearchOptionsToAdd());

        $tab = array_merge($tab, DCRoom::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Item_RemoteManagement::rawSearchOptionsToAdd(self::class));

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-device-laptop";
    }
}
