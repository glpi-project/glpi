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

use Glpi\Socket;

/**
 *  Computer class
 **/
class Computer extends CommonDBTM
{
    use Glpi\Features\DCBreadcrumb;
    use Glpi\Features\Clonable;
    use Glpi\Features\Inventoriable;

   // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Item_Disk','ComputerVirtualMachine',
        'Item_SoftwareVersion', 'Infocom',
        'NetworkPort', 'ReservationItem',
        'Item_OperatingSystem'
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
            Item_SoftwareVersion::class,
            Item_SoftwareLicense::class,
            Contract_Item::class,
            Document_Item::class,
            NetworkPort::class,
            Computer_Item::class,
            Notepad::class,
            KnowbaseItem_Item::class,
            Item_RemoteManagement::class,
            ComputerAntivirus::class
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Computer', 'Computers', $nb);
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
         ->addStandardTab('Item_OperatingSystem', $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('Item_Disk', $ong, $options)
         ->addStandardTab('Item_SoftwareVersion', $ong, $options)
         ->addStandardTab('Computer_Item', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab(Socket::class, $ong, $options)
         ->addStandardTab('Item_RemoteManagement', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('ComputerVirtualMachine', $ong, $options)
         ->addStandardTab('ComputerAntivirus', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('ManualLink', $ong, $options)
         ->addStandardTab('Certificate_Item', $ong, $options)
         ->addStandardTab('Lock', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Reservation', $ong, $options)
         ->addStandardTab('Domain_Item', $ong, $options)
         ->addStandardTab('Appliance_Item', $ong, $options)
         ->addStandardTab('DatabaseInstance', $ong, $options)
         ->addStandardTab('RuleMatchedLog', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

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


    public function post_updateItem($history = 1)
    {
        global $DB, $CFG_GLPI;

        $changes = [];
        $update_count = count($this->updates ?? []);
        $input = Toolbox::addslashes_deep($this->fields);
        for ($i = 0; $i < $update_count; $i++) {
           // Update contact of attached items
            if ($this->updates[$i] == 'contact_num' && $CFG_GLPI['is_contact_autoupdate']) {
                $changes['contact_num'] = $input['contact_num'];
            }
            if ($this->updates[$i] == 'contact' && $CFG_GLPI['is_contact_autoupdate']) {
                $changes['contact'] = $input['contact'];
            }
           // Update users and groups of attached items
            if (
                $this->updates[$i] == 'users_id'
                && $CFG_GLPI['is_user_autoupdate']
            ) {
                $changes['users_id'] = $input['users_id'];
            }
            if (
                $this->updates[$i] == 'groups_id'
                && $CFG_GLPI['is_group_autoupdate']
            ) {
                $changes['groups_id'] = $input['groups_id'];
            }
           // Update state of attached items
            if (
                ($this->updates[$i] == 'states_id')
                && ($CFG_GLPI['state_autoupdate_mode'] < 0)
            ) {
                $changes['states_id'] = $input['states_id'];
            }
           // Update loction of attached items
            if (
                $this->updates[$i] == 'locations_id'
                && $CFG_GLPI['is_location_autoupdate']
            ) {
                $changes['locations_id'] = $input['locations_id'];
            }
        }

        if (count($changes)) {
            $update_done = false;
            $is_input_dynamic = (bool) ($this->input['is_dynamic'] ?? false);

            // Propagates the changes to linked items
            foreach ($CFG_GLPI['directconnect_types'] as $type) {
                $items_result = $DB->request(
                    [
                        'SELECT' => ['items_id'],
                        'FROM'   => Computer_Item::getTable(),
                        'WHERE'  => [
                            'itemtype'     => $type,
                            'computers_id' => $this->fields["id"],
                            'is_deleted'   => 0
                        ]
                    ]
                );
                $item      = new $type();
                foreach ($items_result as $data) {
                     $tID = $data['items_id'];
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

           //fields that are not present for devices
            unset($changes['groups_id']);
            unset($changes['users_id']);
            unset($changes['contact_num']);
            unset($changes['contact']);

            if (count($changes) > 0) {
               // Propagates the changes to linked devices
                foreach ($CFG_GLPI['itemdevices'] as $device) {
                    $item = new $device();
                    $devices_result = $DB->request(
                        [
                            'SELECT' => ['id'],
                            'FROM'   => $item::getTable(),
                            'WHERE'  => [
                                'itemtype'     => self::getType(),
                                'items_id'     => $this->fields["id"],
                                'is_deleted'   => 0
                            ]
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
                if (isset($changes['contact']) || isset($changes['contact_num'])) {
                    Session::addMessageAfterRedirect(
                        __('Alternate username updated. The connected items have been updated using this alternate username.'),
                        true
                    );
                }
                if (isset($changes['groups_id']) || isset($changes['users_id'])) {
                    Session::addMessageAfterRedirect(
                        __('User or group updated. The connected items have been moved in the same values.'),
                        true
                    );
                }
                if (isset($changes['states_id'])) {
                    Session::addMessageAfterRedirect(
                        __('Status updated. The connected items have been updated using this status.'),
                        true
                    );
                }
                if (isset($changes['locations_id'])) {
                    Session::addMessageAfterRedirect(
                        __('Location updated. The connected items have been moved in the same location.'),
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

        return $input;
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Computer_Item::class,
                ComputerAntivirus::class,
                ComputerVirtualMachine::class,
            ]
        );
    }


    public function getLinkedItems()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['itemtype', 'items_id'],
            'FROM'   => 'glpi_computers_items',
            'WHERE'  => ['computers_id' => $this->getID()]
        ]);

        $tab = [];
        foreach ($iterator as $data) {
            $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
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
               => OperatingSystem::getTypeName(),
                'Computer_Item' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
               => "<i class='fa-fw ti ti-plug'></i>" .
                  _x('button', 'Connect'),
                'Item_SoftwareVersion' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
               => "<i class='fa-fw fas fa-laptop-medical'></i>" .
                  _x('button', 'Install'),
                'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
               => "<i class='fa-fw " . SoftwareLicense::getIcon() . "'></i>" .
                  _x('button', 'Add a license'),

            ];

            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);
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
            'datatype'           => 'number'
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_computertypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => 'glpi_computermodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => 'glpi_states',
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => ['is_visible_computer' => 1]
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => 'glpi_autoupdatesystems',
            'field'              => 'name',
            'name'               => AutoUpdateSystem::getTypeName(1),
            'datatype'           => 'dropdown'
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
            'name'               => __('Comments'),
            'datatype'           => 'text'
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
            'right'              => 'all'
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => 'glpi_networks',
            'field'              => 'name',
            'name'               => _n('Network', 'Networks', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge of the hardware'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket'
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge of the hardware'),
            'condition'          => ['is_assign' => 1],
            'datatype'           => 'dropdown'
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
            'datatype'           => 'dropdown'
        ];

       // add operating system search options
        $tab = array_merge($tab, Item_OperatingSystem::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Item_Devices::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Item_Disk::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, ComputerVirtualMachine::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, ComputerAntivirus::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Monitor::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Peripheral::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Printer::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Phone::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Rack::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Socket::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Agent::rawSearchOptionsToAdd());

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-device-laptop";
    }
}
