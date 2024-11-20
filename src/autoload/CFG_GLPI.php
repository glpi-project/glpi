<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\SocketModel;

/**
 * @var array $CFG_GLPI
 */
global $CFG_GLPI;

$CFG_GLPI = [];

// set the default app_name
$CFG_GLPI['app_name'] = 'GLPI';

// dictionnaries
$CFG_GLPI['languages'] = [
   //Code       Name in native lang          LANG FILE      jquery tinymce english name            standard plural number
    'ar_SA'  => ['العَرَبِيَّةُ',                   'ar_SA.mo',    'ar',    'ar', 'arabic',               103],
   //'az_AZ'  => ['Azerbaijani',               'az_AZ.mo',    'az',    'az', 'azeri',                2], //asked on transifex, not present
    'bg_BG'  => ['Български',                 'bg_BG.mo',    'bg',    'bg', 'bulgarian',            2],
    'id_ID'  => ['Bahasa Indonesia',          'id_ID.mo',    'id',    'id', 'indonesian',           2],
    'ms_MY'  => ['Bahasa Melayu',             'ms_MY.mo',    'ms',    'ms', 'malay',                2],
    'ca_ES'  => ['Català',                    'ca_ES.mo',    'ca',    'ca', 'catalan',              2], // ca_CA
    'cs_CZ'  => ['Čeština',                   'cs_CZ.mo',    'cs',    'cs', 'czech',                10],
    'de_DE'  => ['Deutsch',                   'de_DE.mo',    'de',    'de', 'german',               2],
    'da_DK'  => ['Dansk',                     'da_DK.mo',    'da',    'da', 'danish',               2]     , // dk_DK
    'et_EE'  => ['Eesti',                     'et_EE.mo',    'et',    'et', 'estonian',             2], // ee_ET
    'en_GB'  => ['English',                   'en_GB.mo',    'en-GB', 'en', 'english',              2],
    'en_US'  => ['English (US)',              'en_US.mo',    'en-GB', 'en', 'english',              2],
    'es_AR'  => ['Español (Argentina)',       'es_AR.mo',    'es',    'es', 'spanish',              2],
    'es_EC'  => ['Español (Ecuador)',         'es_EC.mo',    'es',    'es', 'spanish',              2],
    'es_CO'  => ['Español (Colombia)',        'es_CO.mo',    'es',    'es', 'spanish',              2],
    'es_ES'  => ['Español (España)',          'es_ES.mo',    'es',    'es', 'spanish',              2],
    'es_419' => ['Español (América Latina)',  'es_419.mo',   'es',    'es', 'spanish',              2],
    'es_MX'  => ['Español (Mexico)',          'es_MX.mo',    'es',    'es', 'spanish',              2],
    'es_VE'  => ['Español (Venezuela)',       'es_VE.mo',    'es',    'es', 'spanish',              2],
    'eu_ES'  => ['Euskara',                   'eu_ES.mo',    'eu',    'eu', 'basque',               2],
    'fr_FR'  => ['Français',                  'fr_FR.mo',    'fr',    'fr', 'french',               2],
    'fr_CA'  => ['Français (Canada)',         'fr_CA.mo',    'fr',    'fr', 'french',               2],
    'fr_BE'  => ['Français (Belgique)',       'fr_BE.mo',    'fr',    'fr', 'french',               2],
    'gl_ES'  => ['Galego',                    'gl_ES.mo',    'gl',    'gl', 'galician',             2],
    'el_GR'  => ['Ελληνικά',                  'el_GR.mo',    'el',    'el', 'greek',                2], // el_EL
    'he_IL'  => ['עברית',                     'he_IL.mo',    'he',    'he', 'hebrew',               2], // he_HE
    'hi_IN'  => ['हिन्दी',                     'hi_IN.mo',    'hi',    'hi_IN', 'hindi' ,            2],
    'hr_HR'  => ['Hrvatski',                  'hr_HR.mo',    'hr',    'hr', 'croatian',             2],
    'hu_HU'  => ['Magyar',                    'hu_HU.mo',    'hu',    'hu', 'hungarian',            2],
    'it_IT'  => ['Italiano',                  'it_IT.mo',    'it',    'it', 'italian',              2],
    'kn'     => ['ಕನ್ನಡ',                      'kn.mo',       'en-GB', 'en', 'kannada',              2],
    'lv_LV'  => ['Latviešu',                  'lv_LV.mo',    'lv',    'lv', 'latvian',              2],
    'lt_LT'  => ['Lietuvių',                  'lt_LT.mo',    'lt',    'lt', 'lithuanian',           2],
    'mn_MN'  => ['Монгол хэл',                'mn_MN.mo',    'mn',    'mn', 'mongolian',            2],
    'nl_NL'  => ['Nederlands',                'nl_NL.mo',    'nl',    'nl', 'dutch',                2],
    'nl_BE'  => ['Flemish',                   'nl_BE.mo',    'nl',    'nl', 'flemish',              2],
    'nb_NO'  => ['Norsk (Bokmål)',            'nb_NO.mo',    'no',    'nb', 'norwegian',            2], // no_NB
    'nn_NO'  => ['Norsk (Nynorsk)',           'nn_NO.mo',    'no',    'nn', 'norwegian',            2], // no_NN
    'fa_IR'  => ['فارسی',                     'fa_IR.mo',    'fa',    'fa', 'persian',              2],
    'pl_PL'  => ['Polski',                    'pl_PL.mo',    'pl',    'pl', 'polish',               2],
    'pt_PT'  => ['Português',                 'pt_PT.mo',    'pt',    'pt', 'portuguese',           2],
    'pt_BR'  => ['Português do Brasil',       'pt_BR.mo',    'pt-BR', 'pt', 'brazilian portuguese', 2],
    'ro_RO'  => ['Română',                    'ro_RO.mo',    'ro',    'en', 'romanian',             2],
    'ru_RU'  => ['Русский',                   'ru_RU.mo',    'ru',    'ru', 'russian',              2],
    'sk_SK'  => ['Slovenčina',                'sk_SK.mo',    'sk',    'sk', 'slovak',               10],
    'sl_SI'  => ['Slovenščina',               'sl_SI.mo',    'sl',    'sl', 'slovenian slovene',    2],
    'sr_RS'  => ['Srpski',                    'sr_RS.mo',    'sr',    'sr', 'serbian',              2],
    'fi_FI'  => ['Suomi',                     'fi_FI.mo',    'fi',    'fi', 'finish',               2],
    'sv_SE'  => ['Svenska',                   'sv_SE.mo',    'sv',    'sv', 'swedish',              2],
    'vi_VN'  => ['Tiếng Việt',                'vi_VN.mo',    'vi',    'vi', 'vietnamese',           2],
    'th_TH'  => ['ภาษาไทย',                   'th_TH.mo',    'th',    'th', 'thai',                 2],
    'tr_TR'  => ['Türkçe',                    'tr_TR.mo',    'tr',    'tr', 'turkish',              2],
    'uk_UA'  => ['Українська',                'uk_UA.mo',    'uk',    'en', 'ukrainian',            2], // ua_UA
    'ja_JP'  => ['日本語',                    'ja_JP.mo',    'ja',    'ja', 'japanese',             2],
    'zh_CN'  => ['简体中文',                  'zh_CN.mo',    'zh-CN', 'zh', 'chinese',              2],
    'zh_TW'  => ['繁體中文',                  'zh_TW.mo',    'zh-TW', 'zh', 'chinese',              2],
    'ko_KR'  => ['한국/韓國',                 'ko_KR.mo',    'ko',    'ko', 'korean',               1],
    'zh_HK'  => ['香港',                      'zh_HK.mo',    'zh-HK', 'zh', 'chinese',              2],
    'be_BY'  => ['Belarussian',               'be_BY.mo',    'be',    'be', 'belarussian',          3],
    'is_IS'  => ['íslenska',                  'is_IS.mo',    'is',    'en', 'icelandic',            2],
    'eo'     => ['Esperanto',                 'eo.mo',       'eo',    'en', 'esperanto',            2],
    'es_CL'  => ['Español chileno',           'es_CL',       'es',    'es', 'spanish chilean',      2]
];

// Init to store glpi itemtype / tables link
$CFG_GLPI['glpitables'] = [];

$CFG_GLPI["unicity_types"]                = ['Budget', 'Computer', 'Contact', 'Contract',
    'Infocom', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'Software',
    'SoftwareLicense', 'Supplier','User', 'Certificate',
    'Rack', 'Enclosure', 'PDU', 'Cluster', 'Item_DeviceSimcard'
];

$CFG_GLPI["state_types"]                  = ['Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
    'Certificate', 'Enclosure', 'PDU', 'Line',
    'Rack', 'SoftwareVersion', 'Cluster', 'Contract',
    'Appliance', 'DatabaseInstance', 'Cable', 'Unmanaged', 'PassiveDCEquipment'
];

$CFG_GLPI["asset_types"]                  = ['Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
    'Certificate', 'Unmanaged', 'Appliance'
];

$CFG_GLPI["project_asset_types"]          = ['Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'Software',
    'DeviceMotherboard', 'DeviceProcessor', 'DeviceMemory',
    'DeviceHardDrive', 'DeviceNetworkCard', 'DeviceDrive',
    'DeviceControl', 'DeviceGraphicCard', 'DeviceSoundCard',
    'DevicePci', 'DeviceCase', 'DevicePowerSupply', 'DeviceGeneric',
    'DeviceBattery', 'DeviceFirmware', 'DeviceCamera',
    'Certificate', 'Appliance'
];


$CFG_GLPI["document_types"]               = ['Budget', 'CartridgeItem', 'Change', 'Computer',
    'ConsumableItem', 'Contact', 'Contract',
    'Document', 'Entity', 'KnowbaseItem', 'Monitor',
    'NetworkEquipment', 'Peripheral', 'Phone',
    'Printer', 'Problem', 'Project', 'ProjectTask',
    'Reminder', 'Software', 'Line',
    'SoftwareLicense', 'Supplier', 'Ticket','User',
    'Certificate', 'Cluster', 'ITILFollowup', 'ITILSolution',
    'ChangeTask', 'ProblemTask', 'TicketTask', 'Appliance',
    'DatabaseInstance'
];

$CFG_GLPI["consumables_types"]            = ['Group', 'User'];

$CFG_GLPI["report_types"]                 = ['Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'Project',
    'Software', 'SoftwareLicense', 'Certificate'
];

// `peripheralhost_types` contains assets that can host peripherals
// `directconnect_types` contains the list of assets that are considered as peripherals
$CFG_GLPI["peripheralhost_types"]         = ['Computer'];
$CFG_GLPI["directconnect_types"]          = ['Monitor', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI["infocom_types"]                = ['Cartridge', 'CartridgeItem', 'Computer',
    'Consumable', 'ConsumableItem', 'Monitor',
    'NetworkEquipment', 'Peripheral', 'Phone',
    'Printer', 'Software', 'SoftwareLicense',
    'Line', 'Certificate', 'Domain', 'Appliance', 'Item_DeviceSimcard',
    'Rack', 'Enclosure', 'PDU', 'PassiveDCEquipment',
    'DatabaseInstance', 'Cable'
];

$CFG_GLPI["reservation_types"]            = ['Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'Software', 'Rack'
];

$CFG_GLPI["assignable_types"] = [
    'Appliance',
    'Cable',
    'CartridgeItem',
    'Certificate',
    'Cluster',
    'Computer',
    'ConsumableItem',
    'DatabaseInstance',
    'Domain',
    'DomainRecord',
    'Enclosure',
    'Item_DeviceSimcard',
    'Line',
    'Monitor',
    'NetworkEquipment',
    'PassiveDCEquipment',
    'PDU',
    'Peripheral',
    'Phone',
    'Printer',
    'Rack',
    'Software',
    'SoftwareLicense',
    'Unmanaged',
];
$CFG_GLPI["linkuser_types"]         = $CFG_GLPI["assignable_types"];
$CFG_GLPI["linkgroup_types"]        = $CFG_GLPI["assignable_types"];
$CFG_GLPI["linkuser_tech_types"]    = $CFG_GLPI["assignable_types"];
$CFG_GLPI["linkgroup_tech_types"]   = $CFG_GLPI["assignable_types"];

$CFG_GLPI["location_types"]               = ['Budget', 'CartridgeItem', 'ConsumableItem',
    'Computer', 'Monitor', "Glpi\\Socket",
    'NetworkEquipment', 'Peripheral', 'Phone',
    'Printer', 'Software', 'SoftwareLicense',
    'Ticket', 'User', 'Certificate', 'Item_DeviceSimcard',
    'Line', 'Appliance', 'PassiveDCEquipment', 'DataCenter',
    'DCRoom', 'Rack', 'Enclosure', 'PDU'
];

$CFG_GLPI["ticket_types"]                 = ['Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'Software',
    'SoftwareLicense', 'Certificate',
    'Line', 'DCRoom', 'Rack', 'Enclosure', 'Cluster', 'PDU',
    'Domain', 'DomainRecord', 'Appliance', 'Item_DeviceSimcard', 'PassiveDCEquipment',
    'DatabaseInstance', 'Database', 'Cable'
];

$CFG_GLPI["link_types"]                   = ['Budget', 'CartridgeItem', 'Computer',
    'ConsumableItem', 'Contact', 'Contract', 'Monitor',
    'NetworkEquipment', 'Peripheral', 'Phone',
    'Printer', 'Software', 'Supplier', 'User', 'Certificate', 'Cluster',
    'DCRoom', 'Domain', 'Appliance', 'DatabaseInstance'
];

$CFG_GLPI["dictionnary_types"]            = ['ComputerModel', 'ComputerType', 'Manufacturer',
    'MonitorModel', 'MonitorType',
    'NetworkEquipmentModel', 'NetworkEquipmentType',
    'OperatingSystem', 'OperatingSystemServicePack',
    'OperatingSystemVersion', 'PeripheralModel',
    'PeripheralType', 'PhoneModel', 'PhoneType',
    'Printer', 'PrinterModel', 'PrinterType',
    'Software', 'OperatingSystemArchitecture',
    'OperatingSystemKernel', 'OperatingSystemKernelVersion',
    'OperatingSystemEdition', 'ImageResolution', 'ImageFormat',
    'DatabaseInstanceType', SocketModel::class, 'CableType'
];

$CFG_GLPI["helpdesk_visible_types"]       = ['Software', 'Appliance', 'DatabaseInstance'];

$CFG_GLPI["networkport_types"]            = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
    'Phone', 'Printer', 'Enclosure', 'PDU', 'Cluster', 'Unmanaged'
];

// Warning : the order is used for displaying different NetworkPort types ! Keep it !
$CFG_GLPI['networkport_instantiations']   = ['NetworkPortEthernet', 'NetworkPortWifi' ,
    'NetworkPortAggregate', 'NetworkPortAlias',
    'NetworkPortDialup',   'NetworkPortLocal',
    'NetworkPortFiberchannel'
];

$CFG_GLPI["contract_types"]               = [
    'Computer', 'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer', 'Project', 'Line',
    'Software', 'SoftwareLicense', 'Certificate',
    'DCRoom', 'Rack', 'Enclosure', 'Cluster', 'PDU', 'Appliance', 'Domain',
    'DatabaseInstance',
];

$CFG_GLPI['device_types']                 = ['DeviceMotherboard', 'DeviceFirmware', 'DeviceProcessor',
    'DeviceMemory', 'DeviceHardDrive', 'DeviceNetworkCard',
    'DeviceDrive', 'DeviceBattery', 'DeviceGraphicCard',
    'DeviceSoundCard', 'DeviceControl', 'DevicePci',
    'DeviceCase', 'DevicePowerSupply', 'DeviceGeneric',
    'DeviceSimcard', 'DeviceSensor', 'DeviceCamera'
];


$CFG_GLPI["socket_types"]                  = ['Computer','NetworkEquipment',
    'Peripheral','Phone','Printer', 'PassiveDCEquipment'
];

foreach ($CFG_GLPI['device_types'] as $dtype) {
    $CFG_GLPI['location_types'][] = 'Item_' . $dtype;
    $CFG_GLPI['state_types'][] = 'Item_' . $dtype;
    $CFG_GLPI["contract_types"][] = 'Item_' . $dtype;
}

$CFG_GLPI["itemdevices_types"]            = ['Computer', 'NetworkEquipment', 'Peripheral',
    'Phone', 'Printer', 'Enclosure'
];

$CFG_GLPI["itemdevices_itemaffinity"]     = ['Computer'];

$CFG_GLPI["itemdevicememory_types"]       = ['Computer', 'NetworkEquipment', 'Peripheral', 'Printer', 'Phone'];

$CFG_GLPI["itemdevicepowersupply_types"]  = ['Computer', 'NetworkEquipment', 'Peripheral', 'Enclosure', 'Phone'];

$CFG_GLPI["itemdevicenetworkcard_types"]  = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['itemdeviceharddrive_types']    = ['Computer', 'Peripheral', 'NetworkEquipment', 'Printer', 'Phone'];

$CFG_GLPI['itemdevicebattery_types']      = ['Computer', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['itemdevicefirmware_types']     = ['Computer', 'Peripheral', 'Phone', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicesimcard_types']      = ['Computer', 'Peripheral', 'Phone', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicegeneric_types']      = ['*'];

$CFG_GLPI['itemdevicepci_types']          = ['*'];

$CFG_GLPI['itemdevicecontrol_types']      = ['Computer'];

$CFG_GLPI['itemdevicedrive_types']        = ['Computer'];

$CFG_GLPI['itemdevicesensor_types']       = ['Computer', 'Peripheral', 'Phone'];

$CFG_GLPI['itemdeviceprocessor_types']    = ['Computer', 'Phone'];

$CFG_GLPI['itemdevicesoundcard_types']    = ['Computer'];

$CFG_GLPI['itemdevicegraphiccard_types']  = ['Computer', 'Phone'];

$CFG_GLPI['itemdevicemotherboard_types']  = ['Computer', 'Phone'];

$CFG_GLPI['itemdevicecamera_types']  = ['Computer', 'Phone'];

$CFG_GLPI["notificationtemplates_types"]  = ['CartridgeItem', 'Change', 'ConsumableItem',
    'Contract', 'CronTask', 'DBConnection',
    'FieldUnicity', 'Infocom', 'MailCollector',
    'ObjectLock', 'PlanningRecall', 'Problem',
    'Project', 'ProjectTask', 'Reservation',
    'SoftwareLicense', 'Ticket', 'User',
    'SavedSearch_Alert', 'Certificate', 'Glpi\\Marketplace\\Controller',
    'Domain', 'KnowbaseItem'
];


$CFG_GLPI["union_search_type"]            = ['ReservationItem' => "reservation_types",
    AllAssets::class       => "asset_types"
];

$CFG_GLPI["systeminformations_types"]     = ['AuthLDAP', 'DBConnection', 'MailCollector',
    'Plugin'
];

$CFG_GLPI["rulecollections_types"]        = [
    'RuleImportAssetCollection',
    'RuleImportEntityCollection',
    'RuleLocationCollection',
    'RuleMailCollectorCollection',
    'RuleRightCollection',
    'RuleSoftwareCategoryCollection',
    'RuleTicketCollection',
    'RuleChangeCollection',
    'RuleProblemCollection',
    'RuleAssetCollection'
];

// Items which can planned something
$CFG_GLPI['planning_types']               = ['ChangeTask', 'ProblemTask', 'Reminder',
    'TicketTask', 'ProjectTask', 'PlanningExternalEvent'
];
$CFG_GLPI['planning_add_types']           = ['PlanningExternalEvent'];

// supported components send by caldav server
// - VTODO: All possible planning events of GLPI with a status of TODO/DONE,
//    You can generaly retrieve them in the todo tab of your caldav client
// - VJOURNAL: Glpi Reminders/Tasks with "Information" status and **not planned**, you can retrieve them in the notes tab
// - VEVENT: all **planned** events without todo/done status, displayed in the calendar of your client
// The two first entry fallback on VEVENT if they are disabled (and they are planned, other are not displayed)
$CFG_GLPI['caldav_supported_components']  = ['VEVENT', 'VJOURNAL'];

$CFG_GLPI["globalsearch_types"]           = ['Computer', 'Contact', 'Contract',
    'Document',  'Monitor',
    'NetworkEquipment', 'Peripheral', 'Phone',
    'Printer', 'Software', 'SoftwareLicense',
    'Ticket', 'Problem', 'Change',
    'User', 'Group', 'Project', 'Supplier',
    'Budget', 'Certificate', 'Line', 'Datacenter',
    'DCRoom', 'Enclosure', 'PDU', 'Rack', 'Cluster', 'PassiveDCEquipment',
    'Domain', 'Appliance'
];

// New config options which can be missing during migration
$CFG_GLPI["number_format"]  = 0;
$CFG_GLPI["decimal_number"] = 2;

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
    'show_jobs_at_login', 'task_private', 'task_state',
    'use_flat_dropdowntree', 'use_flat_dropdowntree_on_search_result', 'palette', 'page_layout',
    'highcontrast_css', 'default_dashboard_central', 'default_dashboard_assets',
    'default_dashboard_helpdesk', 'default_dashboard_mini_ticket', 'default_central_tab',
    'fold_menu', 'savedsearches_pinned', 'richtext_layout', 'timeline_order',
    'itil_layout', 'toast_location', 'timeline_action_btn_layout', 'timeline_date_format', 'is_notif_enable_default',
    'show_search_form', 'search_pagination_on_top'
];

$CFG_GLPI['lock_lockable_objects'] = ['Budget',  'Change', 'Contact', 'Contract', 'Document',
    'CartridgeItem', 'Computer', 'ConsumableItem', 'Entity',
    'Group', 'KnowbaseItem', 'Line', 'Link', 'Monitor',
    'NetworkEquipment', 'NetworkName', 'Peripheral', 'Phone',
    'Printer', 'Problem', 'Profile', 'Project', 'Reminder',
    'RSSFeed', 'Software', 'Supplier', 'Ticket', 'User',
    'SoftwareLicense', 'Certificate'
];

$CFG_GLPI['inventory_types'] = [
    'Computer',
    'Phone',
    'Printer',
    'NetworkEquipment'
];

$CFG_GLPI['inventory_lockable_objects'] = [Asset_PeripheralAsset::class,  'Item_SoftwareLicense',
    'Item_SoftwareVersion', 'Item_Disk', 'ItemVirtualMachine','ItemAntivirus',
    'NetworkPort', 'NetworkName', 'IPAddress', 'Item_OperatingSystem', 'Item_DeviceBattery', 'Item_DeviceCase',
    'Item_DeviceControl', 'Item_DeviceDrive', 'Item_DeviceFirmware', 'Item_DeviceGeneric', 'Item_DeviceGraphicCard',
    'Item_DeviceHardDrive', 'Item_DeviceMemory', 'Item_DeviceMotherboard', 'Item_DeviceNetworkCard', 'Item_DevicePci',
    'Item_DevicePowerSupply', 'Item_DeviceProcessor', 'Item_DeviceSensor', 'Item_DeviceSimcard', 'Item_DeviceSoundCard',
    'DatabaseInstance', 'Item_RemoteManagement','Monitor', 'Domain_Item'
];

$CFG_GLPI["kb_types"]              = ['Budget', 'Change', 'Computer',
    'Contract', 'Entity',
    'Monitor', 'NetworkEquipment',
    'Peripheral', 'Phone', 'Printer',
    'Problem', 'Project', 'Software',
    'SoftwareLicense', 'Supplier',
    'Ticket', 'Certificate', 'Appliance',
    'DatabaseInstance'
];
$CFG_GLPI["certificate_types"]     = ['Computer',
    'NetworkEquipment', 'Peripheral',
    'Phone', 'Printer',
    'SoftwareLicense', 'User', 'Domain', 'Appliance',
    'DatabaseInstance'
];

$CFG_GLPI["rackable_types"]        = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Enclosure', 'PDU', 'PassiveDCEquipment'];

$CFG_GLPI["cluster_types"]        = ['Computer', 'NetworkEquipment'];

$CFG_GLPI['operatingsystem_types'] = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['software_types']      = $CFG_GLPI['operatingsystem_types'];

$CFG_GLPI['disk_types'] = ['Computer', 'NetworkEquipment', 'Phone', 'Printer'];

$CFG_GLPI['kanban_types']        = ['Project'];

$CFG_GLPI['domain_types']        = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
    'Phone', 'Printer', 'Software', 'Appliance', 'Certificate', 'DatabaseInstance', 'Database', 'Unmanaged'
];

$CFG_GLPI['appliance_types']     = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
    'Printer', 'Software', 'Appliance', 'Cluster', 'DatabaseInstance', 'Database'
];

$CFG_GLPI['appliance_relation_types'] = ['Location', 'Network', 'Domain', 'ApplianceEnvironment'];

$CFG_GLPI['remote_management_types'] = ['Computer', 'Phone'];

$CFG_GLPI['databaseinstance_types'] = ['Computer'];

$CFG_GLPI['agent_types'] = ['Computer', 'Phone'];

$CFG_GLPI['line_types'] = $CFG_GLPI['itemdevicesimcard_types'];

$CFG_GLPI['itil_types'] = ['Ticket', 'Change', 'Problem'];

$reservations_libs = ['fullcalendar', 'reservations'];

$CFG_GLPI['javascript'] = [
    'central'   => [
        'central' => [
            'fullcalendar',
            'planning',
            'masonry',
            'tinymce',
            'dashboard',
        ]
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
        'planning'  => ['clipboard', 'fullcalendar', 'tinymce', 'planning'],
        'ticket'    => ['rateit', 'tinymce', 'dashboard'],
        'problem'   => ['tinymce', 'sortable'],
        'change'    => ['tinymce', 'sortable', 'rateit'],
        'stat'      => ['charts', 'rateit']
    ],
    'tools'     => [
        'project'                 => ['sortable', 'tinymce'],
        'knowbaseitem'            => ['tinymce', 'kb'],
        'knowbaseitemtranslation' => ['tinymce', 'kb'],
        'reminder'                => ['tinymce'],
        'remindertranslation'     => ['tinymce'],
        'report'                  => ['dashboard'],
        'reservationitem'         => $reservations_libs,
    ],
    'management' => [
        'datacenter' => [
            'DCRoom' => ['gridstack', 'rack']
        ],
    ],
    'config' => [
        'commondropdown'  => [
            'ITILFollowupTemplate'   => ['tinymce'],
            'ProjectTaskTemplate'    => ['tinymce'],
            'SolutionTemplate'       => ['tinymce'],
            'TaskTemplate'           => ['tinymce'],
            'ITILValidationTemplate' => ['tinymce'],
        ],
        'notification' => [
            'NotificationTemplate' => ['tinymce']
        ],
        'plugin' => [
            'marketplace' => ['marketplace']
        ],
        'config' => ['clipboard', 'tinymce'],
        'webhook' => ['monaco'],
        'link' => ['monaco']
    ],
    'admin'        => ['clipboard', 'monaco', 'tinymce'],
    'preference'   => ['clipboard'],
    'self-service' => array_merge(['tinymce'], $reservations_libs),
    'tickets'      => [
        'ticket' => ['tinymce']
    ],
    'create_ticket' => ['tinymce'],
    'reservation'   => array_merge(['tinymce'], $reservations_libs),
    'faq'           => ['tinymce'],
    'helpdesk-home' => ['home-scss-file']
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
$CFG_GLPI["default_impact_asset_types"] = [
    Appliance::class          => "pics/impact/appliance.png",
    Cluster::class            => "pics/impact/cluster.png",
    Computer::class           => "pics/impact/computer.png",
    Datacenter::class         => "pics/impact/datacenter.png",
    DCRoom::class             => "pics/impact/dcroom.png",
    Domain::class             => "pics/impact/domain.png",
    Enclosure::class          => "pics/impact/enclosure.png",
    Monitor::class            => "pics/impact/monitor.png",
    NetworkEquipment::class   => "pics/impact/networkequipment.png",
    PDU::class                => "pics/impact/pdu.png",
    Peripheral::class         => "pics/impact/peripheral.png",
    Phone::class              => "pics/impact/phone.png",
    Printer::class            => "pics/impact/printer.png",
    Rack::class               => "pics/impact/rack.png",
    Software::class           => "pics/impact/software.png",
    DatabaseInstance::class   => "pics/impact/databaseinstance.png",
];

/**
 * All possible impact itemtypes: default + extra itemtypes that can be
 * added in GLPI configuration
 */
$CFG_GLPI["impact_asset_types"] = $CFG_GLPI["default_impact_asset_types"] + [
    AuthLDAP::class           => "pics/impact/authldap.png",
    CartridgeItem::class      => "pics/impact/cartridgeitem.png",
    Contract::class           => "pics/impact/contract.png",
    CronTask::class           => "pics/impact/crontask.png",
    DeviceSimcard::class      => "pics/impact/devicesimcard.png",
    Entity::class             => "pics/impact/entity.png",
    Group::class              => "pics/impact/group.png",
    ITILCategory::class       => "pics/impact/itilcategory.png",
    Line::class               => "pics/impact/line.png",
    Location::class           => "pics/impact/location.png",
    MailCollector::class      => "pics/impact/mailcollector.png",
    Notification::class       => "pics/impact/notification.png",
    Profile::class            => "pics/impact/profile.png",
    Project::class            => "pics/impact/project.png",
    Rack::class               => "pics/impact/rack.png",
    SLM::class                => "pics/impact/slm.png",
    SoftwareLicense::class    => "pics/impact/softwarelicense.png",
    Supplier::class           => "pics/impact/supplier.png",
    User::class               => "pics/impact/user.png",
    Database::class           => "pics/impact/database.png",
];

$CFG_GLPI['itemantivirus_types'] = ['Computer', 'Phone'];
$CFG_GLPI['itemvirtualmachines_types'] = ['Computer'];
$CFG_GLPI['plug_types'] = ['PDU'];

$CFG_GLPI['management_types'] = [
    'Budget', 'Supplier', 'Contact', 'Contract', 'Document', 'Project', 'Certificate', 'Appliance', 'Database'
];

$CFG_GLPI['tools_types'] = [
    'Reminder', 'RSSFeed'
];

$CFG_GLPI['admin_types'] = [
    'User', 'Group', 'Entity', 'Profile'
];

$CFG_GLPI['process_types'] = ['Computer'];
$CFG_GLPI['environment_types'] = ['Computer'];
