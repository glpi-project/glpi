<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
*/

// Current version of GLPI
define("GLPI_VERSION","0.80");
define("GLPI_DEMO_MODE","0");


// dictionnaries
// 0 Name - 1 lang file - 2 extjs - 3 tiny_mce - 4 english lang name
$CFG_GLPI['languages'] =  //| NAME in native lang    |LANG FILE  |extjs| tinymce|english names|
      array('bg_BG' => array('Български',            'bg_BG.php','bg',    'bg','bulgarian'),
            'ca_CA' => array('Català',               'ca_CA.php','ca',    'ca','catalan'),
            'cs_CZ' => array('Čeština',              'cs_CZ.php','cs',    'cs','czech'),
            'de_DE' => array('Deutsch',              'de_DE.php','de',    'de','german'),
            'dk_DK' => array('Dansk',                'dk_DK.php','da',    'da','danish'),
            'en_GB' => array('English',              'en_GB.php','en',    'en','english'),
            'es_AR' => array('Español (Argentina)',  'es_AR.php','es',    'es','spanish'),
            'es_ES' => array('Español (España)',     'es_ES.php','es',    'es','spanish'),
            'es_MX' => array('Español (Mexico)',     'es_MX.php','es',    'es','spanish'),
            'fr_FR' => array('Français',             'fr_FR.php','fr',    'fr','french'),
            'gl_ES' => array('Galego',               'gl_ES.php','es',    'gl','galician'),
            'el_EL' => array('Ελληνικά',             'el_EL.php','el_GR', 'el','greek'),
            'he_HE' => array('עברית',                  'he_HE.php','he',    'he','hebrew'),
            'hr_HR' => array('Hrvatski',             'hr_HR.php','hr',    'hr','croatian'),
            'hu_HU' => array('Magyar',               'hu_HU.php','hu',    'hu','hungarian'),
            'it_IT' => array('Italiano',             'it_IT.php','it',    'it','italian'),
            'lv_LV' => array('Latviešu',             'lv_LV.php','lv',    'lv','latvian'),
            'lt_LT' => array('Lietuvių',             'lt_LT.php','lt',    'lt','lithuanian'),
            'nl_NL' => array('Nederlands',           'nl_NL.php','nl',    'nl','dutch'),
            'no_NB' => array('Norsk (Bokmål)',       'no_NB.php','no_NB', 'nb','norwegian'),
            'no_NN' => array('Norsk (Nynorsk)',      'no_NN.php','no_NN', 'nn','norwegian'),
            'pl_PL' => array('Polski',               'pl_PL.php','pl',    'pl','polish'),
            'pt_PT' => array('Português',            'pt_PT.php','pt',    'pt','portuguese'),
            'pt_BR' => array('Português do Brasil',  'pt_BR.php','pt_BR', 'pt','brazilian portuguese'),
            'ro_RO' => array('Română',               'ro_RO.php','ro',    'en','romanian'),
            'ru_RU' => array('Pусский',              'ru_RU.php','ru',    'ru','russian'),
            'sk_SK' => array('Slovenčina',           'sk_SK.php','sk',    'sk','slovak'),
            'sl_SI' => array('Slovenščina',          'sl_SI.php','sl',    'sl','slovenian slovene'),
            'sv_SE' => array('Svenska',              'sv_SE.php','sv_SE', 'sv','swedish'),
            'tr_TR' => array('Türkçe',               'tr_TR.php','tr',    'tr','turkish'),
            'ua_UA' => array('Українська',           'ua_UA.php','ukr',   'en','ukrainian'),
            'ja_JP' => array('日本語',                'ja_JP.php','ja',    'ja','japanese'),
            'zh_CN' => array('简体中文',              'zh_CN.php','zh_CN', 'zh','chinese'),
            'zh_TW' => array('繁體中文',              'zh_TW.php','zh_TW', 'zh','chinese'),);

// Init to store glpi itemtype / tables link
$CFG_GLPI['glpitables']=array();

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

//Empty value displayed in a dropdown
define("DROPDOWN_EMPTY_VALUE","-----");

// ITEMS TYPE
/// Temporary definition for test
// TODO clean it.
if (!strstr($_SERVER['PHP_SELF'],"/install/")) {
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


// GLPI MODE
define("NORMAL_MODE",0);
define("TRANSLATION_MODE",1);
define("DEBUG_MODE",2);

//Management modes
define("MANAGEMENT_UNITARY",0);
define("MANAGEMENT_GLOBAL",1);

// DEVICE INTERNAL ACTION
define("HISTORY_ADD_DEVICE",1);
define("HISTORY_UPDATE_DEVICE",2);
define("HISTORY_DELETE_DEVICE",3);
define("HISTORY_INSTALL_SOFTWARE",4);
define("HISTORY_UNINSTALL_SOFTWARE",5);
define("HISTORY_DISCONNECT_DEVICE",6);
define("HISTORY_CONNECT_DEVICE",7);
define("HISTORY_OCS_IMPORT",8);
define("HISTORY_OCS_DELETE",9);
define("HISTORY_OCS_IDCHANGED",10);
define("HISTORY_OCS_LINK",11);
define("HISTORY_LOG_SIMPLE_MESSAGE",12);
define("HISTORY_DELETE_ITEM",13);
define("HISTORY_RESTORE_ITEM",14);
define("HISTORY_ADD_RELATION",15);
define("HISTORY_DEL_RELATION",16);
define("HISTORY_ADD_SUBITEM",17);
define("HISTORY_UPDATE_SUBITEM",18);
define("HISTORY_DELETE_SUBITEM",19);

// EXPORT TYPE
define("GLOBAL_SEARCH",-1);
define("HTML_OUTPUT",0);
define("SYLK_OUTPUT",1);
define("PDF_OUTPUT_LANDSCAPE",2);
define("CSV_OUTPUT",3);
define("PDF_OUTPUT_PORTRAIT",4);


// HELPDESK LINK HARDWARE DEFINITION : CHECKSUM SYSTEM : BOTH=1*2^0+1*2^1=3
define("HELPDESK_MY_HARDWARE",0);
define("HELPDESK_ALL_HARDWARE",1);

// NAME FIRSTNAME ORDER TYPE
define("REALNAME_BEFORE",0);
define("FIRSTNAME_BEFORE",1);


// Default number of items displayed in global search
define("GLOBAL_SEARCH_DISPLAY_COUNT",10);


//Mail send methods
define("MAIL_MAIL",0);
define("MAIL_SMTP",1);
define("MAIL_SMTPSSL",2);
define("MAIL_SMTPTLS",3);

// Auto assign mode for tickets
define("NO_AUTO_ASSIGN",0);
define("AUTO_ASSIGN_HARDWARE_CATEGORY",1);
define("AUTO_ASSIGN_CATEGORY_HARDWARE",2);


// MESSAGE TYPE
define("INFO",0);
define("ERROR",1);

//Bookmark types
define("BOOKMARK_SEARCH",1); //SEARCH SYSTEM bookmark


$CFG_GLPI["unicity_types"] = array('Budget', 'Cartridge', 'CartridgeItem', 'Computer', 'Consumable',
                                   'ConsumableItem', 'Contact', 'Contract', 'Infocom', 'Monitor',
                                   'NetworkEquipment', 'Peripheral', 'Phone', 'Printer', 'Software',
                                   'SoftwareLicense', 'Supplier');

$CFG_GLPI["state_types"] = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                                 'Printer');

$CFG_GLPI["doc_types"]= array('Budget', 'CartridgeItem', 'ConsumableItem', 'Contact', 'Contract',
                              'Computer', 'Entity', 'Monitor', 'NetworkEquipment', 'Peripheral',
                              'Phone', 'Printer', 'Software', 'SoftwareLicense', 'Supplier',
                              'Ticket','User');

$CFG_GLPI["contract_types"] = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                    'Phone', 'Printer', 'Software');

$CFG_GLPI["infocom_types"] = array('Cartridge', 'CartridgeItem', 'Computer', 'Consumable',
                                   'ConsumableItem', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                   'Phone', 'Printer', 'Software', 'SoftwareLicense');

$CFG_GLPI["reservation_types"] = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                       'Phone', 'Printer', 'Software');

$CFG_GLPI["linkuser_types"] = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                    'Phone', 'Printer', 'Software');

$CFG_GLPI["linkgroup_types"] = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                     'Phone', 'Printer', 'Software');

$CFG_GLPI["ticket_types"] = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                                  'Printer', 'Software');

$CFG_GLPI["link_types"] = array('Budget', 'CartridgeItem', 'Computer', 'ConsumableItem', 'Contact',
                                'Contract', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                                'Printer', 'Software', 'Supplier');

$CFG_GLPI["dictionnary_types"] = array('ComputerModel', 'ComputerType', 'Manufacturer',
                                       'MonitorModel', 'MonitorType', 'NetworkEquipmentModel',
                                       'NetworkEquipmentType', 'OperatingSystem',
                                       'OperatingSystemServicePack', 'OperatingSystemVersion',
                                       'PeripheralModel', 'PeripheralType', 'PhoneModel',
                                       'PhoneType', 'Printer', 'PrinterModel', 'PrinterType',
                                       'Software');

$CFG_GLPI["helpdesk_visible_types"] = array('Software');

$CFG_GLPI["netport_types"] = array('Computer', 'NetworkEquipment', 'Peripheral', 'Phone',
                                   'Printer');

$CFG_GLPI["massiveaction_noupdate_types"] = array('Entity', 'OcsServer', 'Profile',
                                                  'TicketValidation');

$CFG_GLPI["massiveaction_nodelete_types"] = array('CronTask', 'Entity', 'NotImportedEmail');

$CFG_GLPI["notificationtemplates_types"] = array('Cartridge', 'Consumable', 'Contract', 'Crontask',
                                                 'DBConnection', 'Infocom', 'Reservation',
                                                  'SoftwareLicense', 'Ticket', 'User');

$CFG_GLPI["notificationmethods_types"] = array('NotificationMail');

$CFG_GLPI["union_search_type"] = array('ReservationItem' => "reservation_types",
                                       'States'          => "state_types");

$CFG_GLPI["systeminformations_types"] = array ('AuthLDAP', 'DBConnection', 'MailCollector',
                                              'OcsServer', 'Plugin');

$CFG_GLPI["rulecollections_types"] = array('RuleImportComputerCollection',
                                           'RuleMailCollectorCollection', 'RuleOcsCollection',
                                           'RuleRightCollection', 'RuleSoftwareCategoryCollection',
                                           'RuleTicketCollection');

// New config options which can be missing during migration
$CFG_GLPI["number_format"]  = 0;
$CFG_GLPI["decimal_number"] = 2;

// Default debug options : may be locally overriden
$CFG_GLPI["debug_sql"] = $CFG_GLPI["debug_vars"] = $CFG_GLPI["debug_lang"] = 1;


// Items which can planned something
$CFG_GLPI['planning_itemtype'] = array('Reminder','TicketPlanning');

// User Prefs fields which override $CFG_GLPI config
$CFG_GLPI['user_pref_field'] = array('csv_delimiter', 'date_format', 'default_requesttypes_id',
                                     'dropdown_chars_limit', 'followup_private',
                                     'is_categorized_soft_expanded', 'is_ids_visible',
                                     'is_not_categorized_soft_expanded', 'language', 'list_limit',
                                     'names_format', 'number_format', 'priority_1', 'priority_2',
                                     'priority_3', 'priority_4', 'priority_5', 'priority_6',
                                     'show_jobs_at_login', 'task_private','use_flat_dropdowntree');

?>
