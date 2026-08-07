<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Config\ConfigContainer;
use Glpi\Config\ProxyExclusions;
use Glpi\Locale\LanguageRegistry;
use Glpi\Marketplace\Controller;
use Glpi\Socket;
use Glpi\SocketModel;

global $CFG_GLPI;

$CFG_GLPI = [];

// set the default app_name
$CFG_GLPI['app_name'] = 'GLPI';

// Languages data.
// The `languages` and `main_languages` arrays are derived from the canonical
// Glpi\Locale\LanguageRegistry catalog and kept here for backward compatibility
// (plugins, ...). GLPI core code should rely on Glpi\Locale\LanguageRegistry
// instead (and the `get_language_name()` Twig function in templates).
//
// Each `languages` entry is a positional array:
// 0 => translated (native) name
// 1 => MO file Name
// 2 => jquery lang code
// 3 => other JS codes. Tinymce has a specific mapping in Html::initEditorSystem().
//      ⚠️ Also used for page lang! ⚠️
// 4 => english name
// 5 => number for plural
$CFG_GLPI['languages']      = LanguageRegistry::toLegacyArray();
$CFG_GLPI['main_languages'] = LanguageRegistry::getMainLanguages();

// Init to store glpi itemtype / tables link
$CFG_GLPI['glpitables'] = [];

$CFG_GLPI['unicity_types']                = [
    Budget::class, Computer::class, Contact::class, Contract::class,
    Infocom::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, Software::class,
    SoftwareLicense::class, Supplier::class, User::class, Certificate::class,
    Rack::class, Enclosure::class, PDU::class, Cluster::class, Item_DeviceSimcard::class,
];

$CFG_GLPI['state_types']                  = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, SoftwareLicense::class,
    Certificate::class, Enclosure::class, PDU::class, Line::class,
    Rack::class, SoftwareVersion::class, Cluster::class, Contract::class,
    Appliance::class, DatabaseInstance::class, Cable::class, Unmanaged::class, PassiveDCEquipment::class,
];

$CFG_GLPI['ruleimportasset_types']                  = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, SoftwareLicense::class,
    Certificate::class, Enclosure::class, PDU::class, Line::class,
    Rack::class, SoftwareVersion::class, Cluster::class, Contract::class,
    Appliance::class, DatabaseInstance::class, Cable::class, Unmanaged::class, PassiveDCEquipment::class,
];


$CFG_GLPI['asset_types']                  = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, SoftwareLicense::class,
    Certificate::class, Unmanaged::class, Appliance::class,
];

$CFG_GLPI['project_asset_types']          = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, Software::class,
    DeviceMotherboard::class, DeviceProcessor::class, DeviceMemory::class,
    DeviceHardDrive::class, DeviceNetworkCard::class, DeviceDrive::class,
    DeviceControl::class, DeviceGraphicCard::class, DeviceSoundCard::class,
    DevicePci::class, DeviceCase::class, DevicePowerSupply::class, DeviceGeneric::class,
    DeviceBattery::class, DeviceFirmware::class, DeviceCamera::class,
    Certificate::class, Appliance::class,
];


$CFG_GLPI['document_types']               = [Budget::class, CartridgeItem::class, Change::class, Computer::class,
    ConsumableItem::class, Contact::class, Contract::class,
    Document::class, Entity::class, KnowbaseItem::class, Monitor::class,
    NetworkEquipment::class, Peripheral::class, Phone::class,
    Printer::class, Problem::class, Project::class, ProjectTask::class,
    Reminder::class, Software::class, Line::class,
    SoftwareLicense::class, Supplier::class, Ticket::class, User::class,
    Certificate::class, Cluster::class, ITILFollowup::class, ITILSolution::class,
    ChangeTask::class, ProblemTask::class, TicketTask::class, Appliance::class,
    DatabaseInstance::class, Rack::class, ComputerModel::class, MonitorModel::class,
    NetworkEquipmentModel::class, PeripheralModel::class, PhoneModel::class, PrinterModel::class,
    DeviceGenericModel::class,  Location::class,
];

$CFG_GLPI['consumables_types']            = [Group::class, User::class];

$CFG_GLPI['report_types']                 = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, Project::class,
    Software::class, SoftwareLicense::class, Certificate::class,
];

// `peripheralhost_types` contains assets that can host peripherals
// `directconnect_types` contains the list of assets that are considered as peripherals
$CFG_GLPI['peripheralhost_types']         = [Computer::class];
$CFG_GLPI['directconnect_types']          = [Monitor::class, Peripheral::class, Phone::class, Printer::class];

$CFG_GLPI['infocom_types']                = [Cartridge::class, CartridgeItem::class, Computer::class,
    Consumable::class, ConsumableItem::class, Monitor::class,
    NetworkEquipment::class, Peripheral::class, Phone::class,
    Printer::class, Software::class, SoftwareLicense::class,
    Line::class, Certificate::class, Domain::class, Appliance::class, Item_DeviceSimcard::class,
    Rack::class, Enclosure::class, PDU::class, PassiveDCEquipment::class,
    DatabaseInstance::class, Cable::class,
];

$CFG_GLPI['reservation_types']            = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, Software::class, Rack::class,
];

$CFG_GLPI['assignable_types'] = [
    Appliance::class,
    Cable::class,
    CartridgeItem::class,
    Certificate::class,
    Cluster::class,
    Computer::class,
    ConsumableItem::class,
    DatabaseInstance::class,
    Domain::class,
    DomainRecord::class,
    Enclosure::class,
    Item_DeviceSimcard::class,
    Line::class,
    Monitor::class,
    NetworkEquipment::class,
    PassiveDCEquipment::class,
    PDU::class,
    Peripheral::class,
    Phone::class,
    Printer::class,
    Rack::class,
    Software::class,
    SoftwareLicense::class,
    Unmanaged::class,
];
$CFG_GLPI['linkuser_types']         = $CFG_GLPI['assignable_types'];
$CFG_GLPI['linkgroup_types']        = $CFG_GLPI['assignable_types'];
$CFG_GLPI['linkuser_tech_types']    = $CFG_GLPI['assignable_types'];
$CFG_GLPI['linkgroup_tech_types']   = $CFG_GLPI['assignable_types'];

$CFG_GLPI['location_types']               = [Budget::class, CartridgeItem::class, ConsumableItem::class,
    Computer::class, Monitor::class, Socket::class,
    NetworkEquipment::class, Peripheral::class, Phone::class,
    Printer::class, Software::class, SoftwareLicense::class,
    Ticket::class, User::class, Certificate::class, Item_DeviceSimcard::class,
    Line::class, Appliance::class, PassiveDCEquipment::class, Datacenter::class,
    DCRoom::class, Rack::class, Enclosure::class, PDU::class,
];

$CFG_GLPI['ticket_types']                 = [Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, Software::class,
    SoftwareLicense::class, Certificate::class,
    Line::class, DCRoom::class, Rack::class, Enclosure::class, Cluster::class, PDU::class,
    Domain::class, DomainRecord::class, Appliance::class, Item_DeviceSimcard::class, PassiveDCEquipment::class,
    DatabaseInstance::class, Database::class, Cable::class,
];

$CFG_GLPI['link_types']                   = [Budget::class, CartridgeItem::class, Computer::class,
    ConsumableItem::class, Contact::class, Contract::class, Monitor::class,
    NetworkEquipment::class, Peripheral::class, Phone::class,
    Printer::class, Software::class, Supplier::class, User::class, Certificate::class, Cluster::class,
    DCRoom::class, Domain::class, Appliance::class, DatabaseInstance::class,
];

$CFG_GLPI['dictionnary_types']            = [ComputerModel::class, ComputerType::class, Manufacturer::class,
    MonitorModel::class, MonitorType::class,
    NetworkEquipmentModel::class, NetworkEquipmentType::class,
    OperatingSystem::class, OperatingSystemServicePack::class,
    OperatingSystemVersion::class, PeripheralModel::class,
    PeripheralType::class, PhoneModel::class, PhoneType::class,
    Printer::class, PrinterModel::class, PrinterType::class,
    Software::class, OperatingSystemArchitecture::class,
    OperatingSystemKernel::class, OperatingSystemKernelVersion::class,
    OperatingSystemEdition::class, ImageResolution::class, ImageFormat::class,
    DatabaseInstanceType::class, SocketModel::class, CableType::class,
];

$CFG_GLPI['helpdesk_visible_types']       = [Software::class, Appliance::class, DatabaseInstance::class];

$CFG_GLPI['networkport_types']            = [Computer::class, Monitor::class, NetworkEquipment::class, Peripheral::class,
    Phone::class, Printer::class, Enclosure::class, PDU::class, Cluster::class, Unmanaged::class,
];

// Warning: the order is used for displaying different NetworkPort types ! Keep it !
$CFG_GLPI['networkport_instantiations']   = [NetworkPortEthernet::class, NetworkPortWifi::class,
    NetworkPortAggregate::class, NetworkPortAlias::class,
    NetworkPortDialup::class,   NetworkPortLocal::class,
    NetworkPortFiberchannel::class,
];

$CFG_GLPI['contract_types']               = [
    Computer::class, Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, Project::class, Line::class,
    Software::class, SoftwareLicense::class, Certificate::class,
    DCRoom::class, Rack::class, Enclosure::class, Cluster::class, PDU::class, Appliance::class, Domain::class,
    DatabaseInstance::class, PassiveDCEquipment::class,
];

$CFG_GLPI['device_types']                 = [DeviceMotherboard::class, DeviceFirmware::class, DeviceProcessor::class,
    DeviceMemory::class, DeviceHardDrive::class, DeviceNetworkCard::class,
    DeviceDrive::class, DeviceBattery::class, DeviceGraphicCard::class,
    DeviceSoundCard::class, DeviceControl::class, DevicePci::class,
    DeviceCase::class, DevicePowerSupply::class, DeviceGeneric::class,
    DeviceSimcard::class, DeviceSensor::class, DeviceCamera::class,
];


$CFG_GLPI['socket_types']                  = [Computer::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class, PassiveDCEquipment::class,
];

foreach ($CFG_GLPI['device_types'] as $dtype) {
    $CFG_GLPI['location_types'][] = 'Item_' . $dtype;
    $CFG_GLPI['state_types'][] = 'Item_' . $dtype;
    $CFG_GLPI['contract_types'][] = 'Item_' . $dtype;
}

$CFG_GLPI['itemdevices_types']            = [Computer::class, NetworkEquipment::class, Peripheral::class,
    Phone::class, Printer::class, Enclosure::class, PDU::class,
];

$CFG_GLPI['itemdevices_itemaffinity']     = [Computer::class];

$CFG_GLPI['itemdevicememory_types']       = [Computer::class, NetworkEquipment::class, Peripheral::class, Printer::class, Phone::class];

$CFG_GLPI['itemdevicepowersupply_types']  = [Computer::class, NetworkEquipment::class, Peripheral::class, Enclosure::class, Phone::class];

$CFG_GLPI['itemdevicenetworkcard_types']  = [Computer::class, NetworkEquipment::class, Peripheral::class, Phone::class, Printer::class];

$CFG_GLPI['itemdeviceharddrive_types']    = [Computer::class, Peripheral::class, NetworkEquipment::class, Printer::class, Phone::class];

$CFG_GLPI['itemdevicebattery_types']      = [Computer::class, Peripheral::class, Phone::class, Printer::class];

$CFG_GLPI['itemdevicefirmware_types']     = [
    Computer::class,
    Peripheral::class,
    Phone::class,
    NetworkEquipment::class,
    Printer::class,
    Item_DeviceHardDrive::class,
    Item_DeviceNetworkCard::class,
    Item_DevicePowerSupply::class,
    Item_DeviceGraphicCard::class,
];

$CFG_GLPI['itemdevicesimcard_types']      = [Computer::class, Peripheral::class, Phone::class, NetworkEquipment::class, Printer::class];

$CFG_GLPI['itemdevicegeneric_types']      = ['*'];

$CFG_GLPI['itemdevicepci_types']          = ['*'];

$CFG_GLPI['itemdevicecontrol_types']      = [Computer::class];

$CFG_GLPI['itemdevicedrive_types']        = [Computer::class];

$CFG_GLPI['itemdevicesensor_types']       = [Computer::class, Peripheral::class, Phone::class];

$CFG_GLPI['itemdeviceprocessor_types']    = [Computer::class, Phone::class];

$CFG_GLPI['itemdevicesoundcard_types']    = [Computer::class];

$CFG_GLPI['itemdevicegraphiccard_types']  = [Computer::class, Phone::class];

$CFG_GLPI['itemdevicemotherboard_types']  = [Computer::class, Phone::class];

$CFG_GLPI['itemdevicecamera_types']  = [Computer::class, Phone::class];

$CFG_GLPI['itemdevicedrive_types']  = [Computer::class, Peripheral::class];

$CFG_GLPI['itemdevicecontrol_types']  = [Computer::class, Peripheral::class, Phone::class, NetworkEquipment::class, Printer::class];

$CFG_GLPI['notificationtemplates_types']  = [CartridgeItem::class, Change::class, ConsumableItem::class,
    Contract::class, CronTask::class, DBConnection::class,
    FieldUnicity::class, Infocom::class, MailCollector::class,
    ObjectLock::class, PlanningRecall::class, Problem::class,
    Project::class, ProjectTask::class, Reservation::class,
    SoftwareLicense::class, Ticket::class, User::class,
    SavedSearch_Alert::class, Certificate::class, Controller::class,
    Domain::class, KnowbaseItem::class,
];


$CFG_GLPI['union_search_type']            = [
    ReservationItem::class => 'reservation_types',
    AllAssets::class       => 'asset_types',
];

$CFG_GLPI['systeminformations_types']     = [AuthLDAP::class, DBConnection::class, MailCollector::class,
    Plugin::class,
];

$CFG_GLPI['rulecollections_types']        = [
    RuleDefineItemtypeCollection::class,
    RuleImportAssetCollection::class,
    RuleImportEntityCollection::class,
    RuleLocationCollection::class,
    RuleMailCollectorCollection::class,
    RuleRightCollection::class,
    RuleSoftwareCategoryCollection::class,
    RuleTicketCollection::class,
    RuleChangeCollection::class,
    RuleProblemCollection::class,
    RuleAssetCollection::class,
];

// Items which can planned something
$CFG_GLPI['planning_types']               = [ChangeTask::class, ProblemTask::class, Reminder::class,
    TicketTask::class, ProjectTask::class, PlanningExternalEvent::class,
];
$CFG_GLPI['planning_add_types']           = [PlanningExternalEvent::class];

// supported components send by caldav server
// - VTODO: All possible planning events of GLPI with a status of TODO/DONE,
//    You can generaly retrieve them in the todo tab of your caldav client
// - VJOURNAL: Glpi Reminders/Tasks with "Information" status and **not planned**, you can retrieve them in the notes tab
// - VEVENT: all **planned** events without todo/done status, displayed in the calendar of your client
// The two first entry fallback on VEVENT if they are disabled (and they are planned, other are not displayed)
$CFG_GLPI['caldav_supported_components']  = ['VEVENT', 'VJOURNAL'];

$CFG_GLPI['globalsearch_types']           = [Computer::class, Contact::class, Contract::class,
    Document::class,  Monitor::class,
    NetworkEquipment::class, Peripheral::class, Phone::class,
    Printer::class, Software::class, SoftwareLicense::class,
    Ticket::class, Problem::class, Change::class,
    User::class, Group::class, Project::class, Supplier::class,
    Budget::class, Certificate::class, Line::class, Datacenter::class,
    DCRoom::class, Enclosure::class, PDU::class, Rack::class, Cluster::class, PassiveDCEquipment::class,
    Domain::class, Appliance::class,
];

// New config options which can be missing during migration
$CFG_GLPI['number_format']  = 0;
$CFG_GLPI['decimal_number'] = 2;

// User Prefs fields which override $CFG_GLPI config
$CFG_GLPI['user_pref_field'] = ['backcreated', 'csv_delimiter', 'date_format',
    'default_requesttypes_id', 'display_count_on_home',
    'duedatecritical_color',
    'duedatecritical_less', 'duedatecritical_unit',
    'duedateok_color', 'duedatewarning_color',
    'duedatewarning_less', 'duedatewarning_unit',
    'followup_private', 'is_ids_visible',
    'keep_devices_when_purging_item', 'language', 'list_limit',
    'lock_autolock_mode', 'lock_directunlock_notification',
    'names_format', 'notification_to_myself',
    'number_format', 'pdffont', 'priority_1',
    'priority_2', 'priority_3', 'priority_4', 'priority_5',
    'priority_6', 'refresh_views', 'set_default_tech',
    'set_followup_tech', 'set_solution_tech',
    'set_default_requester', 'show_count_on_tabs',
    'show_jobs_at_login', 'task_private', 'task_state', 'planned_task_state',
    'use_flat_dropdowntree', 'use_flat_dropdowntree_on_search_result', 'palette', 'page_layout',
    'highcontrast_css', 'default_dashboard_central', 'default_dashboard_assets',
    'default_dashboard_helpdesk', 'default_dashboard_mini_ticket', 'default_central_tab',
    'fold_menu', 'savedsearches_pinned', 'richtext_layout', 'timeline_order',
    'itil_layout', 'toast_location', 'timeline_action_btn_layout', 'timeline_date_format', 'is_notif_enable_default',
    'show_search_form', 'search_pagination_on_top', 'timezone',
];

$CFG_GLPI['lock_lockable_objects'] = [Budget::class,  Change::class, Contact::class, Contract::class, Document::class,
    CartridgeItem::class, Computer::class, ConsumableItem::class, Entity::class,
    Group::class, KnowbaseItem::class, Line::class, Link::class, Monitor::class,
    NetworkEquipment::class, NetworkName::class, Peripheral::class, Phone::class,
    Printer::class, Problem::class, Profile::class, Project::class, Reminder::class,
    RSSFeed::class, Software::class, Supplier::class, Ticket::class, User::class,
    SoftwareLicense::class, Certificate::class,
];

$CFG_GLPI['inventory_types'] = [
    Computer::class,
    Phone::class,
    Printer::class,
    NetworkEquipment::class,
];

$CFG_GLPI['inventory_lockable_objects'] = [Asset_PeripheralAsset::class,  Item_SoftwareLicense::class,
    Item_SoftwareVersion::class, Item_Disk::class, ItemVirtualMachine::class, ItemAntivirus::class,
    NetworkPort::class, NetworkName::class, IPAddress::class, Item_OperatingSystem::class, Item_DeviceBattery::class, Item_DeviceCase::class,
    Item_DeviceControl::class, Item_DeviceDrive::class, Item_DeviceFirmware::class, Item_DeviceGeneric::class, Item_DeviceGraphicCard::class,
    Item_DeviceHardDrive::class, Item_DeviceMemory::class, Item_DeviceMotherboard::class, Item_DeviceNetworkCard::class, Item_DevicePci::class,
    Item_DevicePowerSupply::class, Item_DeviceProcessor::class, Item_DeviceSensor::class, Item_DeviceSimcard::class, Item_DeviceSoundCard::class,
    DatabaseInstance::class, Item_RemoteManagement::class, Monitor::class, Domain_Item::class, Peripheral::class, Unmanaged::class, Database::class,
    Item_DeviceCamera::class, Item_DeviceCamera_ImageFormat::class, Item_DeviceCamera_ImageResolution::class,
    Item_Environment::class, Item_Process::class,
];

$CFG_GLPI['printer_types'] = ['Printer'];

$CFG_GLPI['kb_types']              = [Budget::class, Change::class, Computer::class,
    Contract::class, Entity::class,
    Monitor::class, NetworkEquipment::class,
    Peripheral::class, Phone::class, Printer::class,
    Problem::class, Project::class, Software::class,
    SoftwareLicense::class, Supplier::class,
    Ticket::class, Certificate::class, Appliance::class,
    DatabaseInstance::class,
];
$CFG_GLPI['certificate_types']     = [Computer::class,
    NetworkEquipment::class, Peripheral::class,
    Phone::class, Printer::class,
    SoftwareLicense::class, User::class, Domain::class, Appliance::class,
    DatabaseInstance::class,
];

$CFG_GLPI['rackable_types']        = [Computer::class, Monitor::class, NetworkEquipment::class, Peripheral::class, Enclosure::class, PDU::class, PassiveDCEquipment::class];

$CFG_GLPI['cluster_types']        = [Computer::class, NetworkEquipment::class];

$CFG_GLPI['operatingsystem_types'] = [Computer::class, Monitor::class, NetworkEquipment::class, Peripheral::class, Phone::class, Printer::class];

$CFG_GLPI['software_types']      = $CFG_GLPI['operatingsystem_types'];

$CFG_GLPI['disk_types'] = [Computer::class, NetworkEquipment::class, Phone::class, Printer::class];

$CFG_GLPI['kanban_types']        = [Project::class];

$CFG_GLPI['domain_types']        = [Computer::class, Monitor::class, NetworkEquipment::class, Peripheral::class,
    Phone::class, Printer::class, Software::class, Appliance::class, Certificate::class, DatabaseInstance::class, Database::class, Unmanaged::class,
];

$CFG_GLPI['appliance_types']     = [Computer::class, Monitor::class, NetworkEquipment::class, Peripheral::class, Phone::class,
    Printer::class, Software::class, Appliance::class, Cluster::class, DatabaseInstance::class, Database::class,
];

$CFG_GLPI['appliance_relation_types'] = [Location::class, Network::class, Domain::class, ApplianceEnvironment::class];

$CFG_GLPI['remote_management_types'] = [Computer::class, Phone::class];

$CFG_GLPI['databaseinstance_types'] = [Computer::class];

$CFG_GLPI['agent_types'] = [Computer::class, Phone::class];

$CFG_GLPI['line_types'] = $CFG_GLPI['itemdevicesimcard_types'];

$CFG_GLPI['itil_types'] = [Ticket::class, Change::class, Problem::class];

$reservations_libs = ['fullcalendar', 'reservations'];

$CFG_GLPI['javascript'] = [
    'central'   => [
        'central' => [
            'fullcalendar',
            'planning',
            'masonry',
            'dashboard',
        ],
    ],
    'assets'    => [
        'dashboard'   => ['dashboard'],
        'rack'        => ['gridstack', 'rack'],
        'printer'     => ['dashboard'],
        'cable'       => ['cable'],
        'socket'      => ['cable'],
        'networkport' => ['dashboard'],
    ],
    'helpdesk'  => [
        'dashboard' => ['dashboard'],
        'planning'  => ['clipboard', 'fullcalendar', 'planning'],
        'ticket'    => ['dashboard'],
        'problem'   => ['sortable'],
        'change'    => ['sortable'],
        'stat'      => ['charts'],
    ],
    'tools'     => [
        'project'                 => ['sortable'],
        'knowbaseitem'            => ['tiptap'],
        'knowbaseitemtranslation' => [],
        'report'                  => ['dashboard'],
        'reservationitem'         => $reservations_libs,
    ],
    'management' => [
        'datacenter' => [
            'DCRoom' => ['gridstack', 'rack'],
        ],
    ],
    'config' => [
        'glpi\asset\assetdefinition'  => ['sortable'],
        'plugin' => [
            'marketplace' => ['marketplace'],
        ],
        'config' => ['clipboard'],
        'webhook' => ['monaco'],
        'link' => ['monaco'],
    ],
    'admin'        => ['clipboard', 'monaco'],
    'preference'   => ['clipboard'],
    'self-service' => $reservations_libs,
    'reservation'   => $reservations_libs,
    'helpdesk-home' => ['home-scss-file', 'dashboard'],
];

// push reservations libs to reservations itemtypes (they shoul in asset sector)
foreach ($CFG_GLPI['reservation_types'] as $reservation_type) {
    $CFG_GLPI['javascript']['assets'][strtolower($reservation_type)] = array_merge(
        $CFG_GLPI['javascript']['assets'][strtolower($reservation_type)] ?? [],
        $reservations_libs
    );
}

//Maximum time, in miliseconds a saved search should not exeed
//so we count it on display (using automatic mode).
$CFG_GLPI['max_time_for_count'] = 200;

/**
 * Impact itemtypes enabled by default
 */
$CFG_GLPI['default_impact_asset_types'] = [
    Appliance::class          => '/pics/impact/appliance.png',
    Cluster::class            => '/pics/impact/cluster.png',
    Computer::class           => '/pics/impact/computer.png',
    Datacenter::class         => '/pics/impact/datacenter.png',
    DCRoom::class             => '/pics/impact/dcroom.png',
    Domain::class             => '/pics/impact/domain.png',
    Enclosure::class          => '/pics/impact/enclosure.png',
    Monitor::class            => '/pics/impact/monitor.png',
    NetworkEquipment::class   => '/pics/impact/networkequipment.png',
    PDU::class                => '/pics/impact/pdu.png',
    Peripheral::class         => '/pics/impact/peripheral.png',
    Phone::class              => '/pics/impact/phone.png',
    Printer::class            => '/pics/impact/printer.png',
    Rack::class               => '/pics/impact/rack.png',
    Software::class           => '/pics/impact/software.png',
    DatabaseInstance::class   => '/pics/impact/databaseinstance.png',
];

/**
 * All possible impact itemtypes: default + extra itemtypes that can be
 * added in GLPI configuration
 */
$CFG_GLPI['impact_asset_types'] = $CFG_GLPI['default_impact_asset_types'] + [
    AuthLDAP::class           => '/pics/impact/authldap.png',
    CartridgeItem::class      => '/pics/impact/cartridgeitem.png',
    Contract::class           => '/pics/impact/contract.png',
    CronTask::class           => '/pics/impact/crontask.png',
    DeviceSimcard::class      => '/pics/impact/devicesimcard.png',
    Entity::class             => '/pics/impact/entity.png',
    Group::class              => '/pics/impact/group.png',
    ITILCategory::class       => '/pics/impact/itilcategory.png',
    Line::class               => '/pics/impact/line.png',
    Location::class           => '/pics/impact/location.png',
    MailCollector::class      => '/pics/impact/mailcollector.png',
    Notification::class       => '/pics/impact/notification.png',
    Profile::class            => '/pics/impact/profile.png',
    Project::class            => '/pics/impact/project.png',
    Rack::class               => '/pics/impact/rack.png',
    SLM::class                => '/pics/impact/slm.png',
    SoftwareLicense::class    => '/pics/impact/softwarelicense.png',
    Supplier::class           => '/pics/impact/supplier.png',
    User::class               => '/pics/impact/user.png',
    Database::class           => '/pics/impact/database.png',
];

$CFG_GLPI['itemantivirus_types'] = [Computer::class, Phone::class];
$CFG_GLPI['itemvirtualmachines_types'] = [Computer::class];
$CFG_GLPI['plug_types'] = [PDU::class];

$CFG_GLPI['management_types'] = [
    Budget::class, Supplier::class, Contact::class, Contract::class, Document::class, Project::class, Certificate::class, Appliance::class, Database::class,
];

$CFG_GLPI['tools_types'] = [
    Reminder::class, RSSFeed::class,
];

$CFG_GLPI['admin_types'] = [
    User::class, Group::class, Entity::class, Profile::class,
];

$CFG_GLPI['process_types'] = [Computer::class];
$CFG_GLPI['environment_types'] = [Computer::class];


$CFG_GLPI['possible_proxy_exclusions'] = new ProxyExclusions();

// Wrap the config into a container so specific keys can be deprecated while
// staying backward compatible (see Glpi\Config\ConfigContainer). Declarations
// above still operate on a plain array; the wrapping happens once, here.
$CFG_GLPI = new ConfigContainer($CFG_GLPI);

$CFG_GLPI->deprecateKey(
    'languages',
    "Accessing `\$CFG_GLPI['languages']` is deprecated, use `Glpi\\Locale\\LanguageRegistry` instead.",
    '11.0'
);
$CFG_GLPI->deprecateKey(
    'main_languages',
    "Accessing `\$CFG_GLPI['main_languages']` is deprecated, use `Glpi\\Locale\\LanguageRegistry::getMainLanguages()` instead.",
    '11.0'
);
