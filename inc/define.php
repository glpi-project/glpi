<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
define('GLPI_VERSION', '9.3.3');
if (substr(GLPI_VERSION, -4) === '-dev') {
   //for dev version
   define('GLPI_PREVER', str_replace('-dev', '', GLPI_VERSION));
   define(
      'GLPI_SCHEMA_VERSION',
      GLPI_PREVER . '@' . sha1_file(GLPI_ROOT . '/install/mysql/glpi-empty.sql')
   );
} else {
   //for stable version
   define("GLPI_SCHEMA_VERSION", '9.3.2');
}
define('GLPI_MIN_PHP', '5.6.0'); // Must also be changed in top of index.php
define('GLPI_YEAR', '2018');
if (!defined('GLPI_DEMO_MODE')) {
   define('GLPI_DEMO_MODE', '0');
}
if (!defined('GLPI_USE_CSRF_CHECK')) {
   define('GLPI_USE_CSRF_CHECK', '1');
}
define("GLPI_CSRF_EXPIRES", "7200");
define("GLPI_CSRF_MAX_TOKENS", "100");

//Define a global recipient address for email notifications
//define('GLPI_FORCE_MAIL', 'me@localhost');

// for compatibility with mysql 5.7
// TODO: this var need to be set to 0 after review of all sql queries)
if (!defined('GLPI_FORCE_EMPTY_SQL_MODE')) {
   define("GLPI_FORCE_EMPTY_SQL_MODE", "1");
}

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
   'nl_NL'  => ['Nederlands',                'nl_NL.mo',    'nl',    'nl', 'dutch',                2],
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

//Telemetry
if (!defined('GLPI_TELEMETRY_URI')) {
   define('GLPI_TELEMETRY_URI', 'https://telemetry.glpi-project.org');
}

// GLPI Network
if (!defined('GLPI_NETWORK_SERVICES')) {
   define('GLPI_NETWORK_SERVICES', 'https://services.glpi-network.com');
}

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
                                                  'SoftwareLicense', 'Supplier','User', 'Certicate', 'Rack', 'Enclosure', 'Pdu'];

$CFG_GLPI["state_types"]                  = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
                                                  'Certificate', 'Enclosure', 'Pdu'];

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
                                                  'Certificate'];

$CFG_GLPI["consumables_types"]            = ['Group', 'User'];

$CFG_GLPI["itemdevices"]                  = ['Item_DevicePowerSupply', 'Item_DevicePci',
                                                  'Item_DeviceCase', 'Item_DeviceGraphicCard',
                                                  'Item_DeviceMotherBoard', 'Item_DeviceNetworkCard',
                                                  'Item_DeviceSoundCard', 'Item_DeviceControl',
                                                  'Item_DeviceHardDrive', 'Item_DeviceDrive', 'Item_DeviceMemory',
                                                  'Item_DeviceProcessor', 'Item_DeviceGeneric',
                                                  'Item_DeviceBattery', 'Item_DeviceFirmware', 'Item_DeviceSimcard',
                                                  'Item_DeviceSensor'];

$CFG_GLPI["contract_types"]               = array_merge(['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Project', 'Line',
                                                  'Software', 'SoftwareLicense', 'Certificate',
                                                  'DCRoom', 'Rack', 'Enclosure'],
                                                  $CFG_GLPI['itemdevices']);

$CFG_GLPI["report_types"]                 = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Project',
                                                  'Software', 'SoftwareLicense', 'Certificate'];


$CFG_GLPI["directconnect_types"]          = ['Monitor', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI["infocom_types"]                = ['Cartridge', 'CartridgeItem', 'Computer',
                                                  'Consumable', 'ConsumableItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense',
                                                  'Line', 'Certificate'];

$CFG_GLPI["reservation_types"]            = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software'];

$CFG_GLPI["linkuser_types"]               = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate'];

$CFG_GLPI["linkgroup_types"]              = ['Computer', 'Consumable', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate'];

$CFG_GLPI["linkuser_tech_types"]          = ['Computer', 'ConsumableItem', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate'];

$CFG_GLPI["linkgroup_tech_types"]         = ['Computer', 'ConsumableItem', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate'];

$CFG_GLPI["location_types"]               = ['Budget', 'CartridgeItem', 'ConsumableItem',
                                                  'Computer', 'Monitor', 'Netpoint',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense',
                                                  'Ticket', 'User', 'Certificate'];

$CFG_GLPI["ticket_types"]                 = ['Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Certificate',
                                                  'Line', 'DCRoom', 'Rack', 'Enclosure'];

$CFG_GLPI["link_types"]                   = ['Budget', 'CartridgeItem', 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'Supplier', 'User', 'Certificate'];

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

$CFG_GLPI["helpdesk_visible_types"]       = ['Software'];

$CFG_GLPI["networkport_types"]            = ['Computer', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer', 'Enclosure', 'PDU'];

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

$CFG_GLPI["itemdevices_types"]            = ['Computer', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer', 'Enclosure'];

$CFG_GLPI["itemdevices_itemaffinity"]     = ['Computer'];

$CFG_GLPI["itemdevicememory_types"]       = ['Computer', 'NetworkEquipment', 'Peripheral', 'Printer'];

$CFG_GLPI["itemdevicepowersupply_types"]  = ['Computer', 'NetworkEquipment', 'Enclosure'];

$CFG_GLPI["itemdevicenetworkcard_types"]  = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['itemdeviceharddrive_types']    = ['Computer', 'Peripheral', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicebattery_types']      = ['Computer', 'Peripheral', 'Phone', 'Printer'];

$CFG_GLPI['itemdevicefirmware_types']     = ['Computer', 'Peripheral', 'Phone', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicesimcard_types']      = ['Computer', 'Peripheral', 'Phone', 'NetworkEquipment', 'Printer'];

$CFG_GLPI['itemdevicegeneric_types']      = ['*'];

$CFG_GLPI['itemdevicepci_types']          = ['*'];

$CFG_GLPI['itemdevicesensor_types']       = ['Computer', 'Peripheral'];


$CFG_GLPI["notificationtemplates_types"]  = ['CartridgeItem', 'Change', 'ConsumableItem',
                                             'Contract', 'Crontask', 'DBConnection',
                                             'FieldUnicity', 'Infocom', 'MailCollector',
                                             'ObjectLock', 'PlanningRecall', 'Problem',
                                             'Project', 'ProjectTask', 'Reservation',
                                             'SoftwareLicense', 'Ticket', 'User',
                                             'SavedSearch_Alert', 'Certificate'];

$CFG_GLPI["union_search_type"]            = ['ReservationItem' => "reservation_types",
                                                  'AllAssets'       => "asset_types"];

$CFG_GLPI["systeminformations_types"]     = ['AuthLDAP', 'DBConnection', 'MailCollector',
                                                  'Plugin'];

$CFG_GLPI["rulecollections_types"]        = ['RuleImportEntityCollection',
                                                  'RuleImportComputerCollection',
                                                  'RuleMailCollectorCollection',
                                                  'RuleRightCollection',
                                                  'RuleSoftwareCategoryCollection',
                                                  'RuleTicketCollection'];

// Items which can planned something
$CFG_GLPI['planning_types']               = ['ChangeTask', 'ProblemTask', 'Reminder',
                                                  'TicketTask', 'ProjectTask'];
$CFG_GLPI['planning_add_types']           = ['Reminder'];

$CFG_GLPI["globalsearch_types"]           = ['Computer', 'Contact', 'Contract',
                                             'Document',  'Monitor',
                                             'NetworkEquipment', 'Peripheral', 'Phone',
                                             'Printer', 'Software', 'SoftwareLicense',
                                             'Ticket', 'Problem', 'Change',
                                             'User', 'Group', 'Project', 'Supplier',
                                             'Budget', 'Certificate', 'Line'];

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
                                     'priority_6', 'refresh_ticket_list', 'set_default_tech',
                                     'set_default_requester', 'show_count_on_tabs',
                                     'show_jobs_at_login', 'task_private', 'task_state',
                                     'use_flat_dropdowntree', 'layout', 'ticket_timeline',
                                     'ticket_timeline_keep_replaced_tabs', 'palette',
                                     'highcontrast_css'];

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

$CFG_GLPI['inventory_lockable_objects'] = ['Computer_Item',  'Computer_SoftwareLicense',
                                           'Computer_SoftwareVersion', 'Item_Disk', 'ComputerVirtualMachine',
                                           'NetworkPort', 'NetworkName', 'IPAddress'];

$CFG_GLPI["kb_types"]              = ['Budget', 'Change', 'Computer',
                                           'Contract', 'Entity',
                                           'Monitor', 'NetworkEquipment',
                                           'Peripheral', 'Phone', 'Printer',
                                           'Problem', 'Project', 'Software',
                                           'SoftwareLicense', 'Supplier',
                                           'Ticket', 'Certificate'];
$CFG_GLPI["certificate_types"]     = ['Computer',
                                      'NetworkEquipment', 'Peripheral',
                                      'Phone', 'Printer',
                                      'SoftwareLicense', 'User'];

$CFG_GLPI["rackable_types"]        = ['Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Enclosure', 'PDU'];

$CFG_GLPI['javascript'] = [
   'central'   => [
      'central'   => ['fullcalendar', 'tinymce']
   ],
   'assets'    => [
      'rack'         => ['colorpicker', 'gridstack']
   ],
   'helpdesk'  => [
      'planning'  => ['fullcalendar', 'colorpicker', 'tinymce'],
      'ticket'    => ['rateit', 'tinymce'],
      'problem'   => ['tinymce'],
      'change'    => ['tinymce'],
      'stat'      => ['charts']
   ],
   'tools'     => [
      'project'      => ['gantt'],
      'knowbaseitem' => ['tinymce'],
      'reminder'     => ['tinymce']
   ],
   'management' => [
      'datacenter'       => [
         'dcroom' => ['colorpicker', 'gridstack']
      ]
   ],
   'config'    => [
      'config'    => ['colorpicker'],
      'commondropdown'  => [
         'ProjectState'       => ['colorpicker'],
         'SolutionTemplate'   => ['tinymce']
      ],
      'notification'    => [
         'notificationtemplate' => ['tinymce']
      ]
   ],
   'admin'     => ['colorpicker', 'clipboard'],
   'preference'=> ['colorpicker', 'clipboard'],
   'self-service' => ['colorpicker', 'tinymce']
];

//Maximum time, in miliseconds a saved search should not exeed
//so we count it on display (using automatic mode).
$CFG_GLPI['max_time_for_count'] = 200;
