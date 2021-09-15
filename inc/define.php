<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
*/

// Current version of GLPI
define('GLPI_VERSION', '9.5.6');
if (substr(GLPI_VERSION, -4) === '-dev') {
   //for dev version
   define('GLPI_PREVER', str_replace('-dev', '', GLPI_VERSION));
   define(
      'GLPI_SCHEMA_VERSION',
      GLPI_PREVER . '@' . sha1_file(GLPI_ROOT . '/install/mysql/glpi-empty.sql')
   );
} else {
   //for stable version
   define("GLPI_SCHEMA_VERSION", '9.5.6');
}
define('GLPI_MIN_PHP', '7.2.0'); // Must also be changed in top of index.php
define('GLPI_YEAR', '2021');

//Define a global recipient address for email notifications
//define('GLPI_FORCE_MAIL', 'me@localhost');

// rights
define("READ", 1);
define("UPDATE", 2);
define("CREATE", 4);
define("DELETE", 8);
define("PURGE", 16);
define("ALLSTANDARDRIGHT", 31);
define("READNOTE", 32);
define("UPDATENOTE", 64);
define("UNLOCK", 128);

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

$DEFAULT_PLURAL_NUMBER = 2;

// Init to store glpi itemtype / tables link
$CFG_GLPI['glpitables'] = [];

define("NOT_AVAILABLE", 'N/A');

// key used to crypt passwords in DB for external access : proxy / smtp / ldap /  mailcollectors
// This key is not used to crypt user's passwords
// If you hav to define passwords again
define("GLPIKEY", "GLPI£i'snarss'ç");

// TIMES
define("MINUTE_TIMESTAMP", 60);
define("HOUR_TIMESTAMP", 3600);
define("DAY_TIMESTAMP", 86400);
define("WEEK_TIMESTAMP", 604800);
define("MONTH_TIMESTAMP", 2592000);


//Management modes
define("MANAGEMENT_UNITARY", 0);
define("MANAGEMENT_GLOBAL", 1);


//Mail send methods
define("MAIL_MAIL", 0);
define("MAIL_SMTP", 1);
define("MAIL_SMTPSSL", 2);
define("MAIL_SMTPTLS", 3);

// MESSAGE TYPE
define("INFO", 0);
define("ERROR", 1);
define("WARNING", 2);

// ACTIONS_ERROR

define("ERROR_NOT_FOUND", 1);
define("ERROR_RIGHT", 2);
define("ERROR_COMPAT", 3);
define("ERROR_ON_ACTION", 4);
define("ERROR_ALREADY_DEFINED", 5);

// For plugins
$PLUGIN_HOOKS     = [];
$CFG_GLPI_PLUGINS = [];
$LANG             = [];

$CFG_GLPI["unicity_types"]                = ['Budget', 'Computer', 'Contact', 'Contract',
                                                  'Infocom', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Supplier','User', 'Certicate',
                                                  'Rack', 'Enclosure', 'PDU', 'Cluster', 'Item_DeviceSimcard'];

$CFG_GLPI["state_types"]                  = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
                                                  'Certificate', 'Enclosure', 'PDU', 'Line',
                                                  'Rack', 'SoftwareVersion', 'Cluster', 'Contract',
                                                  'Appliance'];

$CFG_GLPI["asset_types"]                  = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
                                                  'Certificate'];

$CFG_GLPI["project_asset_types"]          = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'DeviceMotherboard', 'DeviceProcessor', 'DeviceMemory',
                                                  'DeviceHardDrive', 'DeviceNetworkCard', 'DeviceDrive',
                                                  'DeviceControl', 'DeviceGraphicCard', 'DeviceSoundCard',
                                                  'DevicePci', 'DeviceCase', 'DevicePowerSupply', 'DeviceGeneric',
                                                  'DeviceBattery', 'DeviceFirmware',
                                                  'Certificate'];


$CFG_GLPI["document_types"]               = ['Budget', 'CartridgeItem', 'Change', 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract',
                                                  'Document', 'Entity', 'KnowbaseItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Problem', 'Project', 'ProjectTask',
                                                  'Reminder', 'Software', 'Line',
                                                  'SoftwareLicense', 'Supplier', 'Ticket','User',
                                                  'Certificate', 'Cluster', 'ITILFollowup', 'ITILSolution',
                                                   'ChangeTask', 'ProblemTask', 'TicketTask', 'Appliance'];

$CFG_GLPI["consumables_types"]            = ['Group', 'User'];

$CFG_GLPI["report_types"]                 = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Project',
                                                  'Software', 'SoftwareLicense', 'Certificate'];


$CFG_GLPI["directconnect_types"]          = ['Monitor', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI["infocom_types"]                = ['Cartridge', 'CartridgeItem', 'Computer',
                                                  'Consumable', 'ConsumableItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense',
                                                  'Line', 'Certificate', 'Domain', 'Appliance', 'Item_DeviceSimcard',
                                                  'Rack', 'Enclosure', 'PDU', 'PassiveDCEquipment'];

$CFG_GLPI["reservation_types"]            = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software'];

$CFG_GLPI["linkuser_types"]               = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate', 'Appliance', 'Item_DeviceSimcard'];

$CFG_GLPI["linkgroup_types"]              = ['Computer', 'Consumable', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate', 'Appliance', 'Item_DeviceSimcard'];

$CFG_GLPI["linkuser_tech_types"]          = ['Computer', 'ConsumableItem', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate', 'Appliance'];

$CFG_GLPI["linkgroup_tech_types"]         = ['Computer', 'ConsumableItem', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate', 'Appliance'];

$CFG_GLPI["location_types"]               = ['Budget', 'CartridgeItem', 'ConsumableItem',
                                                  'Computer', 'Monitor', 'Netpoint',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense',
                                                  'Ticket', 'User', 'Certificate', 'Item_DeviceSimcard'];

$CFG_GLPI["ticket_types"]                 = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate',
                                                  'Line', 'DCRoom', 'Rack', 'Enclosure', 'Cluster', 'PDU',
                                                  'Domain', 'DomainRecord', 'Appliance', 'Item_DeviceSimcard', 'PassiveDCEquipment'];

$CFG_GLPI["link_types"]                   = ['Budget', 'CartridgeItem', 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'Supplier', 'User', 'Certificate', 'Cluster',
                                                  'DCRoom', 'Domain', 'Appliance'];

$CFG_GLPI["dictionnary_types"]            = ['ComputerModel', 'ComputerType', 'Manufacturer',
                                                  'MonitorModel', 'MonitorType',
                                                  'NetworkEquipmentModel', 'NetworkEquipmentType',
                                                  'OperatingSystem', 'OperatingSystemServicePack',
                                                  'OperatingSystemVersion', 'PeripheralModel',
                                                  'PeripheralType', 'PhoneModel', 'PhoneType',
                                                  'Printer', 'PrinterModel', 'PrinterType',
                                                  'Software', 'OperatingSystemArchitecture',
                                                  'OperatingSystemKernel', 'OperatingSystemKernelVersion',
                                                  'OperatingSystemEdition'];

$CFG_GLPI["helpdesk_visible_types"]       = ['Software', 'Appliance'];

$CFG_GLPI["networkport_types"]            = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer', 'Enclosure', 'PDU', 'Cluster'];

// Warning : the order is used for displaying different NetworkPort types ! Keep it !
$CFG_GLPI['networkport_instantiations']   = ['NetworkPortEthernet', 'NetworkPortWifi' ,
                                                  'NetworkPortAggregate', 'NetworkPortAlias',
                                                  'NetworkPortDialup',   'NetworkPortLocal',
                                                  'NetworkPortFiberchannel'];

$CFG_GLPI['device_types']                 = ['DeviceMotherboard', 'DeviceFirmware', 'DeviceProcessor',
                                                  'DeviceMemory', 'DeviceHardDrive', 'DeviceNetworkCard',
                                                  'DeviceDrive', 'DeviceBattery', 'DeviceGraphicCard',
                                                  'DeviceSoundCard', 'DeviceControl', 'DevicePci',
                                                  'DeviceCase', 'DevicePowerSupply', 'DeviceGeneric',
                                                  'DeviceSimcard', 'DeviceSensor'];

$CFG_GLPI['itemdevices'] = [];
foreach ($CFG_GLPI['device_types'] as $dtype) {
   $CFG_GLPI['location_types'][] = 'Item_' . $dtype;
   $CFG_GLPI["itemdevices"][] = 'Item_' . $dtype;
}

$CFG_GLPI["itemdevices_types"]            = ['Computer', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer', 'Enclosure'];

$CFG_GLPI["itemdevices_itemaffinity"]     = ['Computer'];

$CFG_GLPI["itemdevicememory_types"]       = ['Computer', 'NetworkEquipment', 'Peripheral', 'Printer'];

$CFG_GLPI["itemdevicepowersupply_types"]  = ['Computer', 'NetworkEquipment', 'Enclosure'];

$CFG_GLPI["itemdevicenetworkcard_types"]  = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['itemdeviceharddrive_types']    = ['Computer', 'Peripheral', 'NetworkEquipment', 'Printer', 'Phone'];

$CFG_GLPI['itemdevicebattery_types']      = ['Computer', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['itemdevicefirmware_types']     = ['Computer', 'Peripheral', 'Phone', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicesimcard_types']      = ['Computer', 'Peripheral', 'Phone', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicegeneric_types']      = ['*'];

$CFG_GLPI['itemdevicepci_types']          = ['*'];

$CFG_GLPI['itemdevicesensor_types']       = ['Computer', 'Peripheral'];

$CFG_GLPI['itemdeviceprocessor_types']    = ['Computer'];

$CFG_GLPI['itemdevicesoundcard_types']    = ['Computer'];

$CFG_GLPI['itemdevicegraphiccard_types']  = ['Computer'];

$CFG_GLPI['itemdevicemotherboard_types']  = ['Computer'];

$CFG_GLPI["notificationtemplates_types"]  = ['CartridgeItem', 'Change', 'ConsumableItem',
                                             'Contract', 'CronTask', 'DBConnection',
                                             'FieldUnicity', 'Infocom', 'MailCollector',
                                             'ObjectLock', 'PlanningRecall', 'Problem',
                                             'Project', 'ProjectTask', 'Reservation',
                                             'SoftwareLicense', 'Ticket', 'User',
                                             'SavedSearch_Alert', 'Certificate', 'Glpi\\Marketplace\\Controller',
                                             'Domain'];

$CFG_GLPI["contract_types"]               = array_merge(['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Project', 'Line',
                                                  'Software', 'SoftwareLicense', 'Certificate',
                                                  'DCRoom', 'Rack', 'Enclosure', 'Cluster', 'PDU', 'Appliance', 'Domain'],
                                                  $CFG_GLPI['itemdevices']);


$CFG_GLPI["union_search_type"]            = ['ReservationItem' => "reservation_types",
                                                  'AllAssets'       => "asset_types"];

$CFG_GLPI["systeminformations_types"]     = ['AuthLDAP', 'DBConnection', 'MailCollector',
                                                  'Plugin'];

$CFG_GLPI["rulecollections_types"]        = ['RuleImportEntityCollection',
                                                  'RuleImportComputerCollection',
                                                  'RuleMailCollectorCollection',
                                                  'RuleRightCollection',
                                                  'RuleSoftwareCategoryCollection',
                                                  'RuleTicketCollection',
                                                  'RuleAssetCollection'];

// Items which can planned something
$CFG_GLPI['planning_types']               = ['ChangeTask', 'ProblemTask', 'Reminder',
                                             'TicketTask', 'ProjectTask', 'PlanningExternalEvent'];
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
                                             'DCRoom', 'Enclosure', 'PDU', 'Rack', 'Cluster',
                                             'Domain'];

// New config options which can be missing during migration
$CFG_GLPI["number_format"]  = 0;
$CFG_GLPI["decimal_number"] = 2;

// Default debug options : may be locally overriden
$CFG_GLPI["debug_sql"] = $CFG_GLPI["debug_vars"] = $CFG_GLPI["debug_lang"] = 1;


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
                                     'set_default_requester', 'show_count_on_tabs',
                                     'show_jobs_at_login', 'task_private', 'task_state',
                                     'use_flat_dropdowntree', 'layout', 'palette',
                                     'highcontrast_css', 'default_dashboard_central', 'default_dashboard_assets',
                                     'default_dashboard_helpdesk', 'default_dashboard_mini_ticket'];

$CFG_GLPI['layout_excluded_pages'] = ["profile.form.php",
                                           "knowbaseitem.php",
                                           "knowbaseitem.form.php",
                                           "bookmark.php",
                                           "displaypreference.form.php",
                                           "central.php",
                                           "preference.php",
                                           "config.form.php",
                                           "common.tabs.php",
                                           "transfer.form.php",
                                           "entity.form.php",
                                           "queuednotification.form.php"];

$CFG_GLPI['lock_lockable_objects'] = ['Budget',  'Change', 'Contact', 'Contract', 'Document',
                                           'CartridgeItem', 'Computer', 'ConsumableItem', 'Entity',
                                           'Group', 'KnowbaseItem', 'Line', 'Link', 'Monitor',
                                           'NetworkEquipment', 'NetworkName', 'Peripheral', 'Phone',
                                           'Printer', 'Problem', 'Profile', 'Project', 'Reminder',
                                           'RSSFeed', 'Software', 'Supplier', 'Ticket', 'User',
                                           'SoftwareLicense', 'Certificate'];

$CFG_GLPI['inventory_lockable_objects'] = ['Computer_Item',  'Item_SoftwareLicense',
                                           'Item_SoftwareVersion', 'Item_Disk', 'ComputerVirtualMachine',
                                           'NetworkPort', 'NetworkName', 'IPAddress'];

$CFG_GLPI["kb_types"]              = ['Budget', 'Change', 'Computer',
                                           'Contract', 'Entity',
                                           'Monitor', 'NetworkEquipment',
                                           'Peripheral', 'Phone', 'Printer',
                                           'Problem', 'Project', 'Software',
                                           'SoftwareLicense', 'Supplier',
                                           'Ticket', 'Certificate', 'Appliance'];
$CFG_GLPI["certificate_types"]     = ['Computer',
                                      'NetworkEquipment', 'Peripheral',
                                      'Phone', 'Printer',
                                      'SoftwareLicense', 'User', 'Domain', 'Appliance'];

$CFG_GLPI["rackable_types"]        = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Enclosure', 'PDU', 'PassiveDCEquipment'];

$CFG_GLPI["cluster_types"]        = ['Computer', 'NetworkEquipment'];

$CFG_GLPI['operatingsystem_types'] = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['software_types']      = $CFG_GLPI['operatingsystem_types'];

$CFG_GLPI['kanban_types']        = ['Project'];

$CFG_GLPI['domain_types']        = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                       'Phone', 'Printer', 'Software', 'Appliance', 'Certificate'];

$CFG_GLPI['appliance_types']     = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                                       'Printer', 'Software', 'Cluster'];

$CFG_GLPI['appliance_relation_types'] = ['Location', 'Network', 'Domain'];

$dashboard_libs = [
   'dashboard', 'gridstack',
   'charts', 'clipboard', 'sortable'
];

$CFG_GLPI['javascript'] = [
   'central'   => [
      'central' => array_merge([
         'fullcalendar',
         'planning',
         'tinymce',
      ], $dashboard_libs)
   ],
   'assets'    => [
      'dashboard' => $dashboard_libs,
      'rack'      => ['gridstack', 'rack']
   ],
   'helpdesk'  => [
      'dashboard' => $dashboard_libs,
      'planning'  => ['clipboard', 'fullcalendar', 'tinymce', 'planning'],
      'ticket'    => array_merge(['rateit', 'tinymce', 'photoswipe'], $dashboard_libs),
      'problem'   => ['tinymce', 'photoswipe'],
      'change'    => ['tinymce', 'photoswipe'],
      'stat'      => ['charts']
   ],
   'tools'     => [
      'project'                 => ['gantt', 'kanban', 'tinymce'],
      'knowbaseitem'            => ['tinymce', 'jstree'],
      'knowbaseitemtranslation' => ['tinymce'],
      'reminder'                => ['tinymce'],
      'remindertranslation'     => ['tinymce'],
   ],
   'management' => [
      'datacenter' => [
         'dcroom' => ['gridstack', 'rack']
      ]
   ],
   'config' => [
      'commondropdown'  => [
         'ITILFollowupTemplate'  => ['tinymce'],
         'ProjectTaskTemplate'   => ['tinymce'],
         'SolutionTemplate'      => ['tinymce'],
         'TaskTemplate'          => ['tinymce'],
      ],
      'notification' => [
         'notificationtemplate' => ['tinymce']
      ],
      'plugin'=> [
         'marketplace' => ['marketplace']
      ]
   ],
   'admin'        => ['clipboard'],
   'preference'   => ['clipboard'],
   'self-service' => ['tinymce', 'photoswipe']
];

//Maximum time, in miliseconds a saved search should not exeed
//so we count it on display (using automatic mode).
$CFG_GLPI['max_time_for_count'] = 200;

/**
 * Impact itemtypes enabled by default
 */
$CFG_GLPI["default_impact_asset_types"] = [
   Appliance::getType()          => "pics/impact/appliance.png",
   Cluster::getType()            => "pics/impact/cluster.png",
   Computer::getType()           => "pics/impact/computer.png",
   Datacenter::getType()         => "pics/impact/datacenter.png",
   DCRoom::getType()             => "pics/impact/dcroom.png",
   Domain::getType()             => "pics/impact/domain.png",
   Enclosure::getType()          => "pics/impact/enclosure.png",
   Monitor::getType()            => "pics/impact/monitor.png",
   NetworkEquipment::getType()   => "pics/impact/networkequipment.png",
   PDU::getType()                => "pics/impact/pdu.png",
   Peripheral::getType()         => "pics/impact/peripheral.png",
   Phone::getType()              => "pics/impact/phone.png",
   Printer::getType()            => "pics/impact/printer.png",
   Rack::getType()               => "pics/impact/rack.png",
   Software::getType()           => "pics/impact/software.png",
];

/**
 * All possible impact itemtypes: default + extra itemtypes that can be
 * added in GLPI configuration
 */
$CFG_GLPI["impact_asset_types"] = $CFG_GLPI["default_impact_asset_types"] + [
   AuthLDAP::getType()           => "pics/impact/authldap.png",
   CartridgeItem::getType()      => "pics/impact/cartridgeitem.png",
   Contract::getType()           => "pics/impact/contract.png",
   CronTask::getType()           => "pics/impact/crontask.png",
   DeviceSimcard::getType()      => "pics/impact/devicesimcard.png",
   Entity::getType()             => "pics/impact/entity.png",
   Group::getType()              => "pics/impact/group.png",
   ITILCategory::getType()       => "pics/impact/itilcategory.png",
   Line::getType()               => "pics/impact/line.png",
   Location::getType()           => "pics/impact/location.png",
   MailCollector::getType()      => "pics/impact/mailcollector.png",
   Notification::getType()       => "pics/impact/notification.png",
   Profile::getType()            => "pics/impact/profile.png",
   Project::getType()            => "pics/impact/project.png",
   Rack::getType()               => "pics/impact/rack.png",
   SLM::getType()                => "pics/impact/slm.png",
   SoftwareLicense::getType()    => "pics/impact/softwarelicense.png",
   Supplier::getType()           => "pics/impact/supplier.png",
   User::getType()               => "pics/impact/user.png",
];
