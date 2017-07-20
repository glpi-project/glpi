<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

/** @file
* @brief
*/

// Current version of GLPI
define("GLPI_VERSION","9.1.6");
define("GLPI_SCHEMA_VERSION","9.1.3");
define('GLPI_YEAR', '2017');
define("GLPI_DEMO_MODE","0");

define("GLPI_USE_CSRF_CHECK", "1");
define("GLPI_CSRF_EXPIRES","7200");
define("GLPI_CSRF_MAX_TOKENS","100");

// for compatibility with mysql 5.7
// TODO: this var need to be set to 0 after review of all sql queries)
define("GLPI_FORCE_EMPTY_SQL_MODE","1");

// rights
define("READ",        1);
define("UPDATE",      2);
define("CREATE",      4);
define("DELETE",      8);
define("PURGE",      16);
define("ALLSTANDARDRIGHT", 31);
define("READNOTE",   32);
define("UPDATENOTE", 64);
define("UNLOCK",     128);

// dictionnaries
// 0 Name - 1 lang file - 2 extjs - 3 tiny_mce - 4 english lang name
$CFG_GLPI['languages'] =  //| NAME in native lang    |LANG FILE  |jquery| tinymce|english names|standard plural number
      array('ar_SA' => array('العَرَبِيَّةُ',        'ar_SA.mo','ar',    'ar','arabic'     , 103),
            'bg_BG' => array('Български',            'bg_BG.mo','bg',    'bg','bulgarian'  , 2),
            'id_ID' => array('Bahasa Indonesia',     'id_ID.mo','id',    'id','indonesian' , 2),
            'ms_MY' => array('Bahasa Melayu',        'ms_MY.mo','ms',    'ms','malay'      , 2),
            'ca_ES' => array('Català',               'ca_ES.mo','ca',    'ca','catalan'    , 2), // ca_CA
            'cs_CZ' => array('Čeština',              'cs_CZ.mo','cs',    'cs','czech'      , 10),
            'de_DE' => array('Deutsch',              'de_DE.mo','de',    'de','german'     , 2),
            'da_DK' => array('Dansk',                'da_DK.mo','da',    'da','danish'     , 2)     , // dk_DK
            'et_EE' => array('Eesti',                'et_EE.mo','et',    'et','estonian'   , 2), // ee_ET
            'en_GB' => array('English',              'en_GB.mo','en-GB', 'en','english'    , 2),
            'en_US' => array('English (US)',         'en_US.mo','en-GB', 'en','english'    , 2),
            'es_AR' => array('Español (Argentina)',  'es_AR.mo','es',    'es','spanish'    , 2),
            'es_CO' => array('Español (Colombia)',   'es_CO.mo','es',    'es','spanish'    , 2),
            'es_ES' => array('Español (España)',     'es_ES.mo','es',    'es','spanish'    , 2),
            'es_419' => array('Español (América Latina)', 'es_419.mo','es',    'es','spanish' , 2),
            'es_MX' => array('Español (Mexico)',     'es_MX.mo','es',    'es','spanish'    , 2),
            'es_VE' => array('Español (Venezuela)',  'es_VE.mo','es',    'es','spanish'    , 2),
            'eu_ES' => array('Euskara',              'eu_ES.mo','eu',    'en','basque'     , 2),
            'fr_FR' => array('Français',             'fr_FR.mo','fr',    'fr','french'     , 2),
            'gl_ES' => array('Galego',               'gl_ES.mo','gl',    'gl','galician'   , 2),
            'el_GR' => array('Ελληνικά',             'el_GR.mo','el',    'el','greek'      , 2), // el_EL
            'he_IL' => array('עברית',                 'he_IL.mo','he',    'he','hebrew'     , 2), // he_HE
            'hr_HR' => array('Hrvatski',             'hr_HR.mo','hr',    'hr','croatian'   , 2),
            'hu_HU' => array('Magyar',               'hu_HU.mo','hu',    'hu','hungarian'  , 2),
            'it_IT' => array('Italiano',             'it_IT.mo','it',    'it','italian'    , 2),
            'lv_LV' => array('Latviešu',             'lv_LV.mo','lv',    'lv','latvian'    , 2),
            'lt_LT' => array('Lietuvių',             'lt_LT.mo','lt',    'lt','lithuanian' , 2),
            'nl_NL' => array('Nederlands',           'nl_NL.mo','nl',    'nl','dutch'      , 2),
            'nb_NO' => array('Norsk (Bokmål)',       'nb_NO.mo','no',    'nb','norwegian'  , 2), // no_NB
            'nn_NO' => array('Norsk (Nynorsk)',      'nn_NO.mo','no',    'nn','norwegian'  , 2), // no_NN
            'fa_IR' => array('فارسی',                'fa_IR.mo','fa',    'fa','persian'    , 2),
            'pl_PL' => array('Polski',               'pl_PL.mo','pl',    'pl','polish'     , 2),
            'pt_PT' => array('Português',            'pt_PT.mo','pt',    'pt','portuguese' , 2),
            'pt_BR' => array('Português do Brasil',  'pt_BR.mo','pt-BR', 'pt','brazilian portuguese'    , 2),
            'ro_RO' => array('Română',               'ro_RO.mo','ro',    'en','romanian'    , 2),
            'ru_RU' => array('Русский',              'ru_RU.mo','ru',    'ru','russian'    , 2),
            'sk_SK' => array('Slovenčina',           'sk_SK.mo','sk',    'sk','slovak'    , 10),
            'sl_SI' => array('Slovenščina',          'sl_SI.mo','sl',    'sl','slovenian slovene'    , 2),
            'sr_RS' => array('Srpski',               'sr_RS.mo','sr',    'sr','serbian'    , 2),
            'fi_FI' => array('Suomi',                'fi_FI.mo','fi',    'fi','finish'    , 2),
            'sv_SE' => array('Svenska',              'sv_SE.mo','sv',    'sv','swedish'    , 2),
            'vi_VN' => array('Tiếng Việt',           'vi_VN.mo','vi',    'vi','vietnamese'    , 2),
            'th_TH' => array('ภาษาไทย',              'th_TH.mo','th',    'th','thai'    , 2),
            'tr_TR' => array('Türkçe',               'tr_TR.mo','tr',    'tr','turkish'    , 2),
            'uk_UA' => array('Українська',           'uk_UA.mo','uk',    'en','ukrainian'    , 2), // ua_UA
            'ja_JP' => array('日本語',                'ja_JP.mo','ja',    'ja','japanese'    , 2),
            'zh_CN' => array('简体中文',              'zh_CN.mo','zh-CN', 'zh','chinese'    , 2),
            'zh_TW' => array('繁體中文',              'zh_TW.mo','zh-TW', 'zh','chinese'    , 2),);

$DEFAULT_PLURAL_NUMBER = 2;

// Init to store glpi itemtype / tables link
$CFG_GLPI['glpitables'] = array();

define("NOT_AVAILABLE",'N/A');

// key used to crypt passwords in DB for external access : proxy / smtp / ldap /  mailcollectors
// This key is not used to crypt user's passwords
// If you hav to define passwords again
define("GLPIKEY","GLPI£i'snarss'ç");

// TIMES
define("MINUTE_TIMESTAMP",60);
define("HOUR_TIMESTAMP",3600);
define("DAY_TIMESTAMP",86400);
define("WEEK_TIMESTAMP",604800);
define("MONTH_TIMESTAMP",2592000);


//Management modes
define("MANAGEMENT_UNITARY",0);
define("MANAGEMENT_GLOBAL",1);


//Mail send methods
define("MAIL_MAIL",0);
define("MAIL_SMTP",1);
define("MAIL_SMTPSSL",2);
define("MAIL_SMTPTLS",3);


// MESSAGE TYPE
define("INFO",0);
define("ERROR",1);
define("WARNING",2);

// ACTIONS_ERROR

define("ERROR_NOT_FOUND",1);
define("ERROR_RIGHT",2);
define("ERROR_COMPAT",3);
define("ERROR_ON_ACTION",4);
define("ERROR_ALREADY_DEFINED",5);


// For plugins
$PLUGIN_HOOKS     = array();
$CFG_GLPI_PLUGINS = array();
$LANG             = array();

$CFG_GLPI["unicity_types"]                = array('Budget', 'Computer', 'Contact', 'Contract',
                                                  'Infocom', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Supplier','User');

$CFG_GLPI["state_types"]                  = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense');

$CFG_GLPI["asset_types"]                  = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense');

$CFG_GLPI["project_asset_types"]          = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');


$CFG_GLPI["document_types"]               = array('Budget', 'CartridgeItem', 'Change', 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract',
                                                  'Document', 'Entity', 'KnowbaseItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Problem', 'Project', 'ProjectTask',
                                                  'Reminder', 'Software',
                                                  'SoftwareLicense', 'Supplier', 'Ticket','User');

$CFG_GLPI["consumables_types"]            = array('Group', 'User');

$CFG_GLPI["contract_types"]               = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Project',
                                                  'Software', 'SoftwareLicense');

$CFG_GLPI["directconnect_types"]          = array('Monitor', 'Peripheral', 'Phone', 'Printer');

$CFG_GLPI["infocom_types"]                = array('Cartridge', 'CartridgeItem', 'Computer',
                                                  'Consumable', 'ConsumableItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense');

$CFG_GLPI["reservation_types"]            = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["linkuser_types"]               = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense');

$CFG_GLPI["linkgroup_types"]              = array('Computer', 'Consumable', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense');

$CFG_GLPI["linkuser_tech_types"]          = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense');

$CFG_GLPI["linkgroup_tech_types"]         = array('Computer', 'Consumable', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense');

$CFG_GLPI["location_types"]               = array('Budget', 'CartridgeItem', 'ConsumableItem',
                                                  'Computer', 'Monitor', 'Netpoint',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense',
                                                  'Ticket', 'User');

$CFG_GLPI["ticket_types"]                 = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense');

$CFG_GLPI["link_types"]                   = array('Budget', 'CartridgeItem', 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'Supplier', 'User');

$CFG_GLPI["dictionnary_types"]            = array('ComputerModel', 'ComputerType', 'Manufacturer',
                                                  'MonitorModel', 'MonitorType',
                                                  'NetworkEquipmentModel', 'NetworkEquipmentType',
                                                  'OperatingSystem', 'OperatingSystemServicePack',
                                                  'OperatingSystemVersion', 'PeripheralModel',
                                                  'PeripheralType', 'PhoneModel', 'PhoneType',
                                                  'Printer', 'PrinterModel', 'PrinterType',
                                                  'Software', 'OperatingSystemArchitecture');

$CFG_GLPI["helpdesk_visible_types"]       = array('Software');

$CFG_GLPI["networkport_types"]            = array('Computer', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer');

// Warning : the order is used for displaying different NetworkPort types ! Keep it !
$CFG_GLPI['networkport_instantiations']   = array('NetworkPortEthernet', 'NetworkPortWifi' ,
                                                  'NetworkPortAggregate', 'NetworkPortAlias',
                                                  'NetworkPortDialup',   'NetworkPortLocal',
                                                  'NetworkPortFiberchannel');

$CFG_GLPI['device_types'] = array('DeviceMotherboard', 'DeviceProcessor', 'DeviceMemory',
                                  'DeviceHardDrive', 'DeviceNetworkCard', 'DeviceDrive',
                                  'DeviceControl', 'DeviceGraphicCard', 'DeviceSoundCard',
                                  'DevicePci', 'DeviceCase', 'DevicePowerSupply');

$CFG_GLPI["itemdevices_types"]            = array('Computer', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer');

$CFG_GLPI["itemdevices_itemaffinity"]     = array('Computer');

$CFG_GLPI["itemdevicesmemory_types"]      = array('Computer', 'NetworkEquipment', 'Peripheral', 'Printer');

$CFG_GLPI["itemdevicepowersupply_types"]  = array('Computer', 'NetworkEquipment');

$CFG_GLPI["itemdevicenetworkcard_types"]  = array('Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer');


$CFG_GLPI["notificationtemplates_types"]  = array('CartridgeItem', 'Change', 'ConsumableItem',
                                                  'Contract', 'Crontask', 'DBConnection',
                                                  'FieldUnicity', 'Infocom', 'MailCollector',
                                                  'ObjectLock', 'PlanningRecall', 'Problem',
                                                  'Project', 'ProjectTask', 'Reservation',
                                                  'SoftwareLicense', 'Ticket', 'User');

$CFG_GLPI["notificationmethods_types"]    = array('NotificationMail');

$CFG_GLPI["union_search_type"]            = array('ReservationItem' => "reservation_types",
                                                  'AllAssets'       => "asset_types");

$CFG_GLPI["systeminformations_types"]     = array('AuthLDAP', 'DBConnection', 'MailCollector',
                                                  'Plugin');

$CFG_GLPI["rulecollections_types"]        = array('RuleImportEntityCollection',
                                                  'RuleImportComputerCollection',
                                                  'RuleMailCollectorCollection',
                                                  'RuleRightCollection',
                                                  'RuleSoftwareCategoryCollection',
                                                  'RuleTicketCollection');

// Items which can planned something
$CFG_GLPI['planning_types']               = array('ChangeTask', 'ProblemTask', 'Reminder',
                                                  'TicketTask', 'ProjectTask');
$CFG_GLPI['planning_add_types']           = array('Reminder');

$CFG_GLPI["globalsearch_types"]           = array('Computer', 'Contact', 'Document',  'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'Supplier', 'Ticket',
                                                  'SoftwareLicense', 'User');

// New config options which can be missing during migration
$CFG_GLPI["number_format"]  = 0;
$CFG_GLPI["decimal_number"] = 2;

// Default debug options : may be locally overriden
$CFG_GLPI["debug_sql"] = $CFG_GLPI["debug_vars"] = $CFG_GLPI["debug_lang"] = 1;


// User Prefs fields which override $CFG_GLPI config
$CFG_GLPI['user_pref_field'] = array('backcreated', 'csv_delimiter', 'date_format',
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
                                     'highcontrast_css');

$CFG_GLPI['layout_excluded_pages'] = array("profile.form.php",
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
                                           "queuedmail.form.php");

$CFG_GLPI['lock_lockable_objects'] = array('Budget',  'Change', 'Contact', 'Contract', 'Document',
                                           'CartridgeItem', 'Computer', 'ConsumableItem', 'Entity',
                                           'Group', 'KnowbaseItem', 'Link', 'Monitor',
                                           'NetworkEquipment', 'NetworkName', 'Peripheral', 'Phone',
                                           'Printer', 'Problem', 'Profile', 'Project', 'Reminder',
                                           'RSSFeed', 'Software', 'Supplier', 'Ticket', 'User',
                                           'SoftwareLicense') ;
?>
