<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
define("GLPI_VERSION","0.84.4");
define("GLPI_DEMO_MODE","0");

define("GLPI_USE_CSRF_CHECK", "1");
define("GLPI_CSRF_EXPIRES","7200");
define("GLPI_CSRF_MAX_TOKENS","100");

// dictionnaries
// 0 Name - 1 lang file - 2 extjs - 3 tiny_mce - 4 english lang name
$CFG_GLPI['languages'] =  //| NAME in native lang    |LANG FILE  |extjs| tinymce|english names|
      array('ar_SA' => array('العَرَبِيَّةُ',        'ar_SA.mo','en',    'ar','arabic'),
            'bg_BG' => array('Български',            'bg_BG.mo','bg',    'bg','bulgarian'),
            'id_ID' => array('Bahasa Indonesia',     'id_ID.mo','id',    'id','indonesian'),
            'ms_MY' => array('Bahasa Melayu',        'ms_MY.mo','en',    'ms','malay'),
            'ca_ES' => array('Català',               'ca_ES.mo','ca',    'ca','catalan'), // ca_CA
            'cs_CZ' => array('Čeština',              'cs_CZ.mo','cs',    'cs','czech'),
            'de_DE' => array('Deutsch',              'de_DE.mo','de',    'de','german'),
            'da_DK' => array('Dansk',                'da_DK.mo','da',    'da','danish'), // dk_DK
            'et_EE' => array('Eesti',                'et_EE.mo','en',    'et','estonian'), // ee_ET
            'en_GB' => array('English',              'en_GB.mo','en',    'en','english'),
            'en_US' => array('English (US)',         'en_US.mo','en',    'en','english'),
            'es_AR' => array('Español (Argentina)',  'es_AR.mo','es',    'es','spanish'),
            'es_CO' => array('Español (Colombia)',   'es_CO.mo','es',    'es','spanish'),
            'es_ES' => array('Español (España)',     'es_ES.mo','es',    'es','spanish'),
            'es_419' => array('Español (América Latina)', 'es_419.mo','es',    'es','spanish'),
            'es_MX' => array('Español (Mexico)',     'es_MX.mo','es',    'es','spanish'),
            'es_VE' => array('Español (Venezuela)',  'es_VE.mo','es',    'es','spanish'),
            'eu_ES' => array('Euskara',              'eu_ES.mo','en',    'en','basque'),
            'fr_FR' => array('Français',             'fr_FR.mo','fr',    'fr','french'),
            'gl_ES' => array('Galego',               'gl_ES.mo','es',    'gl','galician'),
            'el_GR' => array('Ελληνικά',             'el_GR.mo','el_GR', 'el','greek'), // el_EL
            'he_IL' => array('עברית',                 'he_IL.mo','he',    'he','hebrew'), // he_HE
            'hr_HR' => array('Hrvatski',             'hr_HR.mo','hr',    'hr','croatian'),
            'hu_HU' => array('Magyar',               'hu_HU.mo','hu',    'hu','hungarian'),
            'it_IT' => array('Italiano',             'it_IT.mo','it',    'it','italian'),
            'lv_LV' => array('Latviešu',             'lv_LV.mo','lv',    'lv','latvian'),
            'lt_LT' => array('Lietuvių',             'lt_LT.mo','lt',    'lt','lithuanian'),
            'nl_NL' => array('Nederlands',           'nl_NL.mo','nl',    'nl','dutch'),
            'nb_NO' => array('Norsk (Bokmål)',       'nb_NO.mo','no_NB', 'nb','norwegian'), // no_NB
            'nn_NO' => array('Norsk (Nynorsk)',      'nn_NO.mo','no_NN', 'nn','norwegian'), // no_NN
            'fa_IR' => array('فارسی',                'fa_IR.mo','fa',    'fa','persian'),
            'pl_PL' => array('Polski',               'pl_PL.mo','pl',    'pl','polish'),
            'pt_PT' => array('Português',            'pt_PT.mo','pt',    'pt','portuguese'),
            'pt_BR' => array('Português do Brasil',  'pt_BR.mo','pt_BR', 'pt','brazilian portuguese'),
            'ro_RO' => array('Română',               'ro_RO.mo','ro',    'en','romanian'),
            'ru_RU' => array('Pусский',              'ru_RU.mo','ru',    'ru','russian'),
            'sk_SK' => array('Slovenčina',           'sk_SK.mo','sk',    'sk','slovak'),
            'sl_SI' => array('Slovenščina',          'sl_SI.mo','sl',    'sl','slovenian slovene'),
            'sr_RS' => array('Srpski',               'sr_RS.mo','sr',    'sr','serbian'),
            'fi_FI' => array('Suomi',                'fi_FI.mo','fi',    'fi','finish'),
            'sv_SE' => array('Svenska',              'sv_SE.mo','sv_SE', 'sv','swedish'),
            'vi_VN' => array('Tiếng Việt',           'vi_VN.mo','vn',    'vi','vietnamese'),
            'th_TH' => array('ภาษาไทย',              'th_TH.mo','th',    'th','thai'),
            'tr_TR' => array('Türkçe',               'tr_TR.mo','tr',    'tr','turkish'),
            'uk_UA' => array('Українська',           'uk_UA.mo','ukr',   'en','ukrainian'), // ua_UA
            'ja_JP' => array('日本語',                'ja_JP.mo','ja',    'ja','japanese'),
            'zh_CN' => array('简体中文',              'zh_CN.mo','zh_CN', 'zh','chinese'),
            'zh_TW' => array('繁體中文',              'zh_TW.mo','zh_TW', 'zh','chinese'),);

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


// ITEMS TYPE
/// Temporary definition for test
// TODO clean it.
if (!strstr($_SERVER['PHP_SELF'], "/install/")
    && !strstr($_SERVER['PHP_SELF'], 'cliupdate.php')) {
   define("GENERAL_TYPE",'');
   define("COMPUTER_TYPE",'Computer');
   define("NETWORKING_TYPE",'NetworkEquipment');
   define("PRINTER_TYPE",'Printer');
   define("MONITOR_TYPE",'Monitor');
   define("PERIPHERAL_TYPE",'Peripheral');
   define("SOFTWARE_TYPE",'Software');
   define("CONTACT_TYPE",'Contact');
   define("ENTERPRISE_TYPE",'Supplier');
   define("INFOCOM_TYPE",'Infocom');
   define("CONTRACT_TYPE",'Contract');
   define("CARTRIDGEITEM_TYPE",'CartridgeItem');
   define("TYPEDOC_TYPE",'DocumentType');
   define("DOCUMENT_TYPE",'Document');
   define("KNOWBASE_TYPE",'KnowbaseItem');
   define("USER_TYPE",'User');
   define("TRACKING_TYPE",'Ticket');
   define("CONSUMABLEITEM_TYPE",'ConsumableItem');
   define("CONSUMABLE_TYPE",'Consumable');
   define("CARTRIDGE_TYPE",'Cartridge');
   define("SOFTWARELICENSE_TYPE",'SoftwareLicense');
   define("LINK_TYPE",'Link');
   define("STATE_TYPE",'State');
   define("PHONE_TYPE",'Phone');
   define("DEVICE_TYPE",'Device');
   define("REMINDER_TYPE",'Reminder');
   define("STAT_TYPE",'Stat');
   define("GROUP_TYPE",'Group');
   define("ENTITY_TYPE",'Entity');
   define("RESERVATION_TYPE",'ReservationItem');
   define("AUTHMAIL_TYPE",'AuthMail');
   define("AUTHLDAP_TYPE",'AuthLDAP');
   define("OCSNG_TYPE",'OcsServer');
   define("REGISTRY_TYPE",'RegistryKey');
   define("PROFILE_TYPE",'Profile');
   define("MAILGATE_TYPE",'MailCollector');
   define("RULE_TYPE",'Rule');
   define("TRANSFER_TYPE",'Transfer');
   define("BOOKMARK_TYPE",'Bookmark');
   define("SOFTWAREVERSION_TYPE",'SoftwareVersion');
   define("PLUGIN_TYPE",'Plugin');
   define("COMPUTERDISK_TYPE",'ComputerDisk');
   define("NETWORKING_PORT_TYPE",'NetworkPort');
   define("FOLLOWUP_TYPE",'TicketFollowup');
   define("BUDGET_TYPE",'Budget');
}


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


// For plugins
$PLUGIN_HOOKS     = array();
$CFG_GLPI_PLUGINS = array();
$LANG             = array();

$CFG_GLPI["unicity_types"]                = array('Budget', 'Computer', 'Contact', 'Contract',
                                                  'Infocom', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense', 'Supplier','User');

$CFG_GLPI["state_types"]                  = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer');

$CFG_GLPI["asset_types"]                  = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer');

$CFG_GLPI["document_types"]               = array('Budget', 'CartridgeItem', /*'Change',*/ 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract',
                                                  'Document', 'Entity', 'KnowbaseItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Problem', 'Reminder', 'Software',
                                                  'SoftwareLicense', 'Supplier', 'Ticket','User');

$CFG_GLPI["consumables_types"]            = array('Group', 'User');

$CFG_GLPI["contract_types"]               = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software',
                                                  'SoftwareLicense');

$CFG_GLPI["directconnect_types"]          = array('Monitor', 'Peripheral', 'Phone', 'Printer');

$CFG_GLPI["infocom_types"]                = array('Cartridge', 'CartridgeItem', 'Computer',
                                                  'Consumable', 'ConsumableItem', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'SoftwareLicense');

$CFG_GLPI["reservation_types"]            = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["linkuser_types"]               = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["linkgroup_types"]              = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["linkuser_tech_types"]          = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["linkgroup_tech_types"]         = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["ticket_types"]                 = array('Computer', 'Monitor', 'NetworkEquipment',
                                                  'Peripheral', 'Phone', 'Printer', 'Software');

$CFG_GLPI["link_types"]                   = array('Budget', 'CartridgeItem', 'Computer',
                                                  'ConsumableItem', 'Contact', 'Contract', 'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'Supplier');

$CFG_GLPI["dictionnary_types"]            = array('ComputerModel', 'ComputerType', 'Manufacturer',
                                                  'MonitorModel', 'MonitorType',
                                                  'NetworkEquipmentModel', 'NetworkEquipmentType',
                                                  'OperatingSystem', 'OperatingSystemServicePack',
                                                  'OperatingSystemVersion', 'PeripheralModel',
                                                  'PeripheralType', 'PhoneModel', 'PhoneType',
                                                  'Printer', 'PrinterModel', 'PrinterType',
                                                  'Software');

$CFG_GLPI["helpdesk_visible_types"]       = array('Software');

$CFG_GLPI["networkport_types"]            = array('Computer', 'NetworkEquipment', 'Peripheral',
                                                  'Phone', 'Printer');

// Warning : the order is used for displaying different NetworkPort types ! Keep it !
$CFG_GLPI['networkport_instantiations']   = array('NetworkPortEthernet', 'NetworkPortWifi' ,
                                                  'NetworkPortAggregate', 'NetworkPortAlias',
                                                  'NetworkPortDialup',   'NetworkPortLocal' );

$CFG_GLPI["notificationtemplates_types"]  = array('CartridgeItem', 'ConsumableItem', 'Contract', 'Crontask',
                                                  'DBConnection', 'FieldUnicity', 'Infocom',
                                                  'PlanningRecall',
                                                  'Problem', 'Reservation', 'SoftwareLicense',
                                                  'Ticket', 'User');

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
$CFG_GLPI['planning_types']               = array(/*'ChangeTask', */'ProblemTask', 'Reminder',
                                                  'TicketTask');

$CFG_GLPI["globalsearch_types"]           = array('Computer', 'Contact', 'Document',  'Monitor',
                                                  'NetworkEquipment', 'Peripheral', 'Phone',
                                                  'Printer', 'Software', 'Supplier', 'Ticket');

// New config options which can be missing during migration
$CFG_GLPI["number_format"]  = 0;
$CFG_GLPI["decimal_number"] = 2;

// Default debug options : may be locally overriden
$CFG_GLPI["debug_sql"] = $CFG_GLPI["debug_vars"] = $CFG_GLPI["debug_lang"] = 1;



// User Prefs fields which override $CFG_GLPI config
$CFG_GLPI['user_pref_field'] = array('csv_delimiter', 'date_format', 'default_requesttypes_id',
                                     'display_count_on_home', 'dropdown_chars_limit',
                                     'duedatecritical_color', 'duedatecritical_less',
                                     'duedatecritical_unit', 'duedateok_color',
                                     'duedatewarning_color','duedatewarning_less',
                                     'duedatewarning_unit',
                                     'followup_private', 'is_categorized_soft_expanded',
                                     'is_ids_visible', 'is_not_categorized_soft_expanded',
                                     'language', 'list_limit', 'names_format',
                                     'notification_to_myself', 'number_format', 'priority_1',
                                     'priority_2', 'priority_3', 'priority_4', 'priority_5',
                                     'priority_6', 'refresh_ticket_list', 'set_default_tech',
                                     'show_count_on_tabs', 'show_jobs_at_login', 'task_private',
                                     'use_flat_dropdowntree',);
?>
