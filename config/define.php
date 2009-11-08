<?php

// Current version of GLPI
define("GLPI_VERSION","0.80");
define("GLPI_DEMO_MODE","0");


// dictionnaries
// 0 Name - 1 lang file - 2 extjs dico - 3 tiny_mce dico
$CFG_GLPI["languages"] = array("bg_BG" => array("Български","bg_BG.php","bg","bg"),
                               "ca_CA" => array("Català","ca_CA.php","ca","ca"),
                               "cs_CZ" => array("Čeština","cs_CZ.php","cs","cs"),
                               "de_DE" => array("Deutsch","de_DE.php","de","de"),
                               "dk_DK" => array("Dansk","dk_DK.php","da","da"),
                               "nl_NL" => array("Nederlands","nl_NL.php","nl","nl"),
                               "nl_BE" => array("Nederlands (Belgium)","nl_BE.php","nl","nl"),
                               "en_GB" => array("English","en_GB.php","en","en"),
                               "es_AR" => array("Español (Argentina)","es_AR.php","es","es"),
                               "es_ES" => array("Español (España)","es_ES.php","es","es"),
                               "es_MX" => array("Español (Mexico)","es_MX.php","es","es"),
                               "fr_FR" => array("Français","fr_FR.php","fr","fr"),
                               "gl_ES" => array("Galego","gl_ES.php","es","gl"),
                               "el_EL" => array("Ελληνικά","el_EL.php","el_GR","el"),
                               "he_HE" => array("עברית","he_HE.php","he","he"),
                               "hr_HR" => array("Hrvatski","hr_HR.php","hr","hr"),
                               "hu_HU" => array("Magyar","hu_HU.php","hu","hu"),
                               "it_IT" => array("Italiano","it_IT.php","it","it"),
                               "lv_LV" => array("Latviešu","lv_LV.php","lv","lv"),
                               "lt_LT" => array("Lietuvių","lt_LT.php","lt","lt"),
                               "no_NB" => array("Norsk (Bokmål)","no_NB.php","no_NB","nb"),
                               "no_NN" => array("Norsk (Nynorsk)","no_NN.php","no_NN","nn"),
                               "pl_PL" => array("Polski","pl_PL.php","pl","pl"),
                               "pt_PT" => array("Português","pt_PT.php","pt","pt"),
                               "pt_BR" => array("Português do Brasil","pt_BR.php","pt_BR","pt"),
                               "ro_RO" => array("Română","ro_RO.php","ro","en"),
                               "ru_RU" => array("Pусский","ru_RU.php","ru","ru"),
                               "sl_SL" => array("Slovenščina","sl_SI.php","sl","sl"),
                               "sv_SE" => array("Svenska","sv_SE.php","sv_SE","sv"),
                               "tr_TR" => array("Türkçe","tr_TR.php","tr","tr"),
                               "ua_UA" => array("українська мова","ua_UA.php","ukr","en"),
                               "ja_JP" => array("日本語","ja_JP.php","ja","ja"),
                               "zh_CN" => array("中文","zh_CN.php","en","zh"));


// TIMES
define("MINUTE_TIMESTAMP",60);
define("HOUR_TIMESTAMP",3600);
define("DAY_TIMESTAMP",86400);
define("WEEK_TIMESTAMP",604800);
define("MONTH_TIMESTAMP",2592000);


// CACHE CONTROL
define("DEFAULT_CACHE_LIFETIME",WEEK_TIMESTAMP);
define("CACHE_FILELOCKINGCONTROL",true);
define("CACHE_READCONTROL",true);
define("CACHE_WRITECONTROL",true);


// MAILGATE CONTROL -> IN DB ?
define("MAX_MAILS_RETRIEVED",10);


//OCSNG IMPORT VARIABLES
define("OCS_FIELD_SEPARATOR",'$$$$$');
define("OCS_IMPORT_TAG_070","_version_070_");
define("OCS_IMPORT_TAG_072","_version_072_");
define("OCS_IMPORT_TAG_080","_version_080_");


// ITEMS TYPE
define("GENERAL_TYPE",0);
define("COMPUTER_TYPE",1);
define("NETWORKING_TYPE",2);
define("PRINTER_TYPE",3);
define("MONITOR_TYPE",4);
define("PERIPHERAL_TYPE",5);
define("SOFTWARE_TYPE",6);
define("CONTACT_TYPE",7);
define("ENTERPRISE_TYPE",8);
define("INFOCOM_TYPE",9);
define("CONTRACT_TYPE",10);
define("CARTRIDGEITEM_TYPE",11);
define("TYPEDOC_TYPE",12);
define("DOCUMENT_TYPE",13);
define("KNOWBASE_TYPE",14);
define("USER_TYPE",15);
define("TRACKING_TYPE",16);
define("CONSUMABLEITEM_TYPE",17);
define("CONSUMABLE_TYPE",18);
define("CARTRIDGE_TYPE",19);
define("SOFTWARELICENSE_TYPE",20);
define("LINK_TYPE",21);
define("STATE_TYPE",22);
define("PHONE_TYPE",23);
define("DEVICE_TYPE",24);
define("REMINDER_TYPE",25);
define("STAT_TYPE",26);
define("GROUP_TYPE",27);
define("ENTITY_TYPE",28);
define("RESERVATION_TYPE",29);
define("AUTH_MAIL_TYPE",30);
define("AUTH_LDAP_TYPE",31);
define("OCSNG_TYPE",32);
define("REGISTRY_TYPE",33);
define("PROFILE_TYPE",34);
define("MAILGATE_TYPE",35);
define("RULE_TYPE",36);
define("TRANSFER_TYPE",37);
define("BOOKMARK_TYPE",38);
define("SOFTWAREVERSION_TYPE",39);
define("PLUGIN_TYPE",40);
define("COMPUTERDISK_TYPE",41);
define("NETWORKING_PORT_TYPE",42);
define("FOLLOWUP_TYPE",43);
define("BUDGET_TYPE",44);
define("CONTRACTITEM_TYPE",45);
define("CONTACTSUPPLIER_TYPE",46);
define("CONTRACTSUPPLIER_TYPE",47);
define("DOCUMENTITEM_TYPE",48);
define("CRONTASK_TYPE",49);
define("CRONTASKLOG_TYPE",50);
define("TICKETCATEGORY_TYPE",51);
define("TASKCATEGORY_TYPE",52);
define("LOCATION_TYPE",53);
define("NETPOINT_TYPE",54);
define("ITEMSTATE_TYPE",55);
define("REQUESTTYPE_TYPE",56);
define("MANUFACTURER_TYPE",57);
define("COMPUTERTYPE_TYPE",58);
define("COMPUTERMODEL_TYPE",59);
define("NETWORKEQUIPMENTTYPE_TYPE",60);
define("NETWORKEQUIPMENTMODEL_TYPE",61);
define("PRINTERTYPE_TYPE",62);
define("PRINTERMODEL_TYPE",63);
define("MONITORTYPE_TYPE",64);
define("MONITORMODEL_TYPE",65);
define("PERIPHERALTYPE_TYPE",66);
define("PERIPHERALMODEL_TYPE",67);
define("PHONETYPE_TYPE",68);
define("PHONEMODEL_TYPE",69);
define("SOFTWARELICENSETYPE_TYPE",70);
define("CARTRIDGEITEMTYPE_TYPE",71);
define("CONSUMABLEITEMTYPE_TYPE",72);
define("CONTRACTTYPE_TYPE",73);
define("CONTACTTYPE_TYPE",74);
define("DEVICEMEMORYTYPE_TYPE",75);
define("SUPPLIERTYPE_TYPE",76);
define("INTERFACESTYPE_TYPE",77);
define("DEVICECASETYPE_TYPE",78);
define("PHONEPOWERSUPPLY_TYPE",79);
define("FILESYSTEM_TYPE",80);
define("DOCUMENTCATEGORY_TYPE",81);
define("KNOWBASEITEMCATEGORY_TYPE",82);
define("OPERATINGSYSTEM_TYPE",83);
define("OPERATINGSYSTEMVERSION_TYPE",84);
define("OPERATINGSYSTEMSERVICEPACK_TYPE",85);
define("AUTOUPDATESYSTEM_TYPE",86);
define("NETWORKINTERFACE_TYPE",87);
define("NETWORKEQUIPMENTFIRMWARE_TYPE",88);
define("DOMAIN_TYPE",89);
define("NETWORK_TYPE",90);
define("VLAN_TYPE",91);
define("SOFTWARECATEGORY_TYPE",92);
define("USERTITLE_TYPE",93);
define("USERCATEGORY_TYPE",94);

$CFG_GLPI['dropdown_types']= array(TICKETCATEGORY_TYPE, TASKCATEGORY_TYPE, LOCATION_TYPE, NETPOINT_TYPE,
   ITEMSTATE_TYPE, REQUESTTYPE_TYPE, MANUFACTURER_TYPE, COMPUTERTYPE_TYPE, COMPUTERMODEL_TYPE,
   NETWORKEQUIPMENTTYPE_TYPE, NETWORKEQUIPMENTMODEL_TYPE, PRINTERTYPE_TYPE, PRINTERMODEL_TYPE,
   MONITORTYPE_TYPE, MONITORMODEL_TYPE, PERIPHERALTYPE_TYPE, PERIPHERALMODEL_TYPE, PHONETYPE_TYPE,
   PHONEMODEL_TYPE, SOFTWARELICENSETYPE_TYPE, CARTRIDGEITEMTYPE_TYPE, CONSUMABLEITEMTYPE_TYPE,
   CONTRACTTYPE_TYPE, CONTACTTYPE_TYPE, DEVICEMEMORYTYPE_TYPE, SUPPLIERTYPE_TYPE, INTERFACESTYPE_TYPE,
   DEVICECASETYPE_TYPE, PHONEPOWERSUPPLY_TYPE, FILESYSTEM_TYPE, DOCUMENTCATEGORY_TYPE,
   KNOWBASEITEMCATEGORY_TYPE, OPERATINGSYSTEM_TYPE, OPERATINGSYSTEMVERSION_TYPE,
   OPERATINGSYSTEMSERVICEPACK_TYPE, AUTOUPDATESYSTEM_TYPE, NETWORKINTERFACE_TYPE,
   NETWORKEQUIPMENTFIRMWARE_TYPE, DOMAIN_TYPE, NETWORK_TYPE, VLAN_TYPE, SOFTWARECATEGORY_TYPE,
   USERTITLE_TYPE, USERCATEGORY_TYPE);


// GLPI MODE
define("NORMAL_MODE",0);
define("TRANSLATION_MODE",1);
define("DEBUG_MODE",2);


// DEVICE TYPE
define("MOBOARD_DEVICE",1);
define("PROCESSOR_DEVICE",2);
define("RAM_DEVICE",3);
define("HDD_DEVICE",4);
define("NETWORK_DEVICE",5);
define("DRIVE_DEVICE",6);
define("CONTROL_DEVICE",7);
define("GFX_DEVICE",8);
define("SND_DEVICE",9);
define("PCI_DEVICE",10);
define("CASE_DEVICE",11);
define("POWER_DEVICE",12);


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


// OCSNG TYPES
define("HARDWARE_FL",0);
define("BIOS_FL",1);
define("MEMORIES_FL",2);
define("SLOTS_FL",3);
define("REGISTRY_FL",4);
define("CONTROLLERS_FL",5);
define("MONITORS_FL",6);
define("PORTS_FL",7);
define("STORAGES_FL",8);
define("DRIVES_FL",9);
define("INPUTS_FL",10);
define("MODEMS_FL",11);
define("NETWORKS_FL",12);
define("PRINTERS_FL",13);
define("SOUNDS_FL",14);
define("VIDEOS_FL",15);
define("SOFTWARES_FL",16);

define("MAX_OCS_CHECKSUM",131071);


// MAILING TYPE
define("USER_MAILING_TYPE",1);
define("PROFILE_MAILING_TYPE",2);
define("GROUP_MAILING_TYPE",3);
define("DB_NOTIFICATION_MAILING_TYPE",3);


// MAILING USERS TYPE
define("ADMIN_MAILING",1);
define("ASSIGN_MAILING",2);
define("AUTHOR_MAILING",3);
define("OLD_ASSIGN_MAILING",4);
define("TECH_MAILING",5);
define("USER_MAILING",6);
define("RECIPIENT_MAILING",7);
define("ASSIGN_ENT_MAILING",8);
define("ASSIGN_GROUP_MAILING",9);
define("SUPERVISOR_ASSIGN_GROUP_MAILING",10);
define("ADMIN_ENTITY_MAILING",11);
define("SUPERVISOR_AUTHOR_GROUP_MAILING",12);


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


// ALERTS TYPE
define("ALERT_THRESHOLD",1);
define("ALERT_END",2);
define("ALERT_NOTICE",3);


// NAME FIRSTNAME ORDER TYPE
define("REALNAME_BEFORE",0);
define("FIRSTNAME_BEFORE",1);


// Default number of items displayed in global search
define("GLOBAL_SEARCH_DISPLAY_COUNT",10);

// Database Table for each type (order by type number)
$LINK_ID_TABLE = array(COMPUTER_TYPE                     => 'glpi_computers',
                       NETWORKING_TYPE                   => 'glpi_networkequipments',
                       PRINTER_TYPE                      => 'glpi_printers',
                       MONITOR_TYPE                      => 'glpi_monitors',
                       PERIPHERAL_TYPE                   => 'glpi_peripherals',
                       SOFTWARE_TYPE                     => 'glpi_softwares',
                       CONTACT_TYPE                      => 'glpi_contacts',
                       ENTERPRISE_TYPE                   => 'glpi_suppliers',
                       INFOCOM_TYPE                      => 'glpi_infocoms',
                       CONTRACT_TYPE                     => 'glpi_contracts',
                       CARTRIDGEITEM_TYPE                => 'glpi_cartridgesitems',
                       TYPEDOC_TYPE                      => 'glpi_documentstypes',
                       DOCUMENT_TYPE                     => 'glpi_documents',
                       KNOWBASE_TYPE                     => 'glpi_knowbaseitems',
                       USER_TYPE                         => 'glpi_users',
                       TRACKING_TYPE                     => 'glpi_tickets',
                       CONSUMABLEITEM_TYPE               => 'glpi_consumablesitems',
                       CONSUMABLE_TYPE                   => 'glpi_consumables',
                       CARTRIDGE_TYPE                    => 'glpi_cartridges',
                       SOFTWARELICENSE_TYPE              => 'glpi_softwareslicenses',
                       LINK_TYPE                         => 'glpi_links',
                       STATE_TYPE                        => '',
                       PHONE_TYPE                        => 'glpi_phones',
// DEVICE_TYPE             => '???',
                       REMINDER_TYPE                     => 'glpi_reminders',
// STAT_TYPE             => '???',
                       GROUP_TYPE                        => 'glpi_groups',
                       ENTITY_TYPE                       => 'glpi_entities',
                       RESERVATION_TYPE                  => 'glpi_reservationsitems',
                       AUTH_MAIL_TYPE                    => 'glpi_authmails',
                       AUTH_LDAP_TYPE                    => 'glpi_authldaps',
                       OCSNG_TYPE                        => 'glpi_ocsservers',
                       REGISTRY_TYPE                     => 'glpi_registrykeys',
                       PROFILE_TYPE                      => 'glpi_profiles',
                       MAILGATE_TYPE                     => 'glpi_mailcollectors',
                       RULE_TYPE                         => 'glpi_rules',
                       TRANSFER_TYPE                     => 'glpi_transfers',
                       BOOKMARK_TYPE                     => 'glpi_bookmarks',
                       SOFTWAREVERSION_TYPE              => 'glpi_softwaresversions',
                       PLUGIN_TYPE                       => 'glpi_plugins',
                       COMPUTERDISK_TYPE                 => 'glpi_computersdisks',
                       NETWORKING_PORT_TYPE              => 'glpi_networkports',
                       FOLLOWUP_TYPE                     => 'glpi_ticketsfollowups',
                       BUDGET_TYPE                       => 'glpi_budgets',
                       CONTRACTITEM_TYPE                 => 'glpi_contracts_items',
                       CONTACTSUPPLIER_TYPE              => 'glpi_contacts_suppliers',
                       CONTRACTSUPPLIER_TYPE             => 'glpi_contracts_suppliers',
                       DOCUMENTITEM_TYPE                 => 'glpi_documents_items',
                       CRONTASK_TYPE                     => 'glpi_crontasks',
                       CRONTASKLOG_TYPE                  => 'glpi_crontaskslogs',
                       TICKETCATEGORY_TYPE               => 'glpi_ticketscategories',
                       TASKCATEGORY_TYPE                 => 'glpi_taskscategories',
                       LOCATION_TYPE                     => 'glpi_locations',
                       NETPOINT_TYPE                     => 'glpi_netpoints',
                       ITEMSTATE_TYPE                    => 'glpi_states',
                       REQUESTTYPE_TYPE                  => 'glpi_requesttypes',
                       MANUFACTURER_TYPE                 => 'glpi_manufacturers',
                       COMPUTERTYPE_TYPE                 => 'glpi_computerstypes',
                       COMPUTERMODEL_TYPE                => 'glpi_computersmodels',
                       NETWORKEQUIPMENTTYPE_TYPE         => 'glpi_networkequipmentstypes',
                       NETWORKEQUIPMENTMODEL_TYPE        => 'glpi_networkequipmentsmodels',
                       PRINTERTYPE_TYPE                  => 'glpi_printerstypes',
                       PRINTERMODEL_TYPE                 => 'glpi_printersmodels',
                       MONITORTYPE_TYPE                  => 'glpi_monitorstypes',
                       MONITORMODEL_TYPE                 => 'glpi_monitorsmodels',
                       PERIPHERALTYPE_TYPE               => 'glpi_peripheralstypes',
                       PERIPHERALMODEL_TYPE              => 'glpi_peripheralsmodels',
                       PHONETYPE_TYPE                    => 'glpi_phonestypes',
                       PHONEMODEL_TYPE                   => 'glpi_phonesmodels',
                       SOFTWARELICENSETYPE_TYPE          => 'glpi_softwareslicensestypes',
                       CARTRIDGEITEMTYPE_TYPE            => 'glpi_cartridgesitemstypes',
                       CONSUMABLEITEMTYPE_TYPE           => 'glpi_consumablesitemstypes',
                       CONTRACTTYPE_TYPE                 => 'glpi_contractstypes',
                       CONTACTTYPE_TYPE                  => 'glpi_contactstypes',
                       DEVICEMEMORYTYPE_TYPE             => 'glpi_devicesmemoriestypes',
                       SUPPLIERTYPE_TYPE                 => 'glpi_supplierstypes',
                       INTERFACESTYPE_TYPE               => 'glpi_interfacestypes',
                       DEVICECASETYPE_TYPE               => 'glpi_devicescasestypes',
                       PHONEPOWERSUPPLY_TYPE             => 'glpi_phonespowersupplies',
                       FILESYSTEM_TYPE                   => 'glpi_filesystems',
                       DOCUMENTCATEGORY_TYPE             => 'glpi_documentscategories',
                       KNOWBASEITEMCATEGORY_TYPE         => 'glpi_knowbaseitemscategories',
                       OPERATINGSYSTEM_TYPE              => 'glpi_operatingsystems',
                       OPERATINGSYSTEMVERSION_TYPE       => 'glpi_operatingsystemsversions',
                       OPERATINGSYSTEMSERVICEPACK_TYPE   => 'glpi_operatingsystemsservicepacks',
                       AUTOUPDATESYSTEM_TYPE             => 'glpi_autoupdatesystems',
                       NETWORKINTERFACE_TYPE             => 'glpi_networkinterfaces',
                       NETWORKEQUIPMENTFIRMWARE_TYPE     => 'glpi_networkequipmentsfirmwares',
                       DOMAIN_TYPE                       => 'glpi_domains',
                       NETWORK_TYPE                      => 'glpi_networks',
                       VLAN_TYPE                         => 'glpi_vlans',
                       SOFTWARECATEGORY_TYPE             => 'glpi_softwarescategories',
                       USERTITLE_TYPE                    => 'glpi_userstitles',
                       USERCATEGORY_TYPE                 => 'glpi_userscategories');

// Form for each type (order by type number)
$INFOFORM_PAGES = array(COMPUTER_TYPE        => "front/computer.form.php",
                        NETWORKING_TYPE      => "front/networking.form.php",
                        PRINTER_TYPE         => "front/printer.form.php",
                        MONITOR_TYPE         => "front/monitor.form.php",
                        PERIPHERAL_TYPE      => "front/peripheral.form.php",
                        SOFTWARE_TYPE        => "front/software.form.php",
                        CONTACT_TYPE         => "front/contact.form.php",
                        ENTERPRISE_TYPE      => "front/enterprise.form.php",
                        INFOCOM_TYPE         => "front/infocom.form.php",
                        CONTRACT_TYPE        => "front/contract.form.php",
                        CARTRIDGEITEM_TYPE   => "front/cartridge.form.php",
                        TYPEDOC_TYPE         => "front/typedoc.form.php",
                        DOCUMENT_TYPE        => "front/document.form.php",
                        KNOWBASE_TYPE        => "front/knowbase.form.php",
                        USER_TYPE            => "front/user.form.php",
                        TRACKING_TYPE        => "front/tracking.form.php",
                        CONSUMABLEITEM_TYPE  => "front/consumable.form.php",
// CONSUMABLE_TYPE => "??",
// CARTRIDGE_TYPE => "??",
                        SOFTWARELICENSE_TYPE => "front/softwarelicense.form.php",
                        LINK_TYPE            => "front/link.form.php",
// STATE_TYPE => "??",
                        PHONE_TYPE           => "front/phone.form.php",
                        DEVICE_TYPE          => "front/device.form.php",
                        REMINDER_TYPE        => "front/reminder.form.php",
// STAT_TYPE => "??",
                        GROUP_TYPE           => "front/group.form.php",
                        ENTITY_TYPE          => "front/entity.form.php",
// RESERVATION_TYPE => "???",
// AUTH_MAIL_TYPE => "???",
// AUTH_LDAP_TYPE => "???",
                        OCSNG_TYPE           => "front/ocsng.form.php",
// REGISTRY_TYPE => "???",
                        PROFILE_TYPE         => "front/profile.form.php",
                        MAILGATE_TYPE        => "front/mailgate.form.php",
// RULE_TYPE => "???",
                        TRANSFER_TYPE        => "front/transfer.form.php",
// BOOKMARK_TYPE => "???",
                        SOFTWAREVERSION_TYPE => "front/softwareversion.form.php",
// PLUGIN_TYPE => "???",
                        COMPUTERDISK_TYPE    => "front/computerdisk.form.php",
                        NETWORKING_PORT_TYPE => "front/networking.port.php",
// FOLLOWUP_TYPE => "???",
                        BUDGET_TYPE          => "front/budget.form.php",
// CONTRACTITEM_TYPE => "???",
// CONTACTSUPPLIER_TYPE => "???",
// CONTRACTSUPPLIER_TYPE => "???",
// DOCUMENTITEM_TYPE => "???",
                        CRONTASK_TYPE        => "front/crontask.form.php");
// CRONTASKLOG_TYPE => "???",

// Form for each type (order by type number)
$SEARCH_PAGES = array(COMPUTER_TYPE        => "front/computer.php",
                      NETWORKING_TYPE      => "front/networking.php",
                      PRINTER_TYPE         => "front/printer.php",
                      MONITOR_TYPE         => "front/monitor.php",
                      PERIPHERAL_TYPE      => "front/peripheral.php",
                      SOFTWARE_TYPE        => "front/software.php",
                      CONTACT_TYPE         => "front/contact.php",
                      ENTERPRISE_TYPE      => "front/enterprise.php",
                      CONTRACT_TYPE        => "front/contract.php",
                      CARTRIDGEITEM_TYPE   => "front/cartridge.php",
                      DOCUMENT_TYPE        => "front/document.php",
                      CONSUMABLEITEM_TYPE  => "front/consumable.php",
                      OCSNG_TYPE           => "front/setup.ocsng.php",
                      BUDGET_TYPE          => "front/budget.php",
                      CRONTASK_TYPE        => "front/crontask.php");

foreach ($CFG_GLPI['dropdown_types'] as $type) {
   $INFOFORM_PAGES[$type] = "front/dropdown.form.php?itemtype=".$type;
   $SEARCH_PAGES[$type]   = "front/dropdown.php?itemtype=".$type;
   }

define("AUTH_DB_GLPI",1);
define("AUTH_MAIL",2);
define("AUTH_LDAP",3);
define("AUTH_EXTERNAL",4);
define("AUTH_CAS",5);
define("AUTH_X509",6);
define("NOT_YET_AUTHENTIFIED",0);


//Mail send methods
define("MAIL_MAIL",0);
define("MAIL_SMTP",1);
define("MAIL_SMTPSSL",2);
define("MAIL_SMTPTLS",3);


// MESSAGE TYPE
define("INFO",0);
define("ERROR",1);


//Generic rules engine
define("PATTERN_IS",0);
define("PATTERN_IS_NOT",1);
define("PATTERN_CONTAIN",2);
define("PATTERN_NOT_CONTAIN",3);
define("PATTERN_BEGIN",4);
define("PATTERN_END",5);
define("REGEX_MATCH",6);
define("REGEX_NOT_MATCH",7);

define("AND_MATCHING","AND");
define("OR_MATCHING","OR");

define("RULE_NOT_IN_CACHE",-1);
define("RULE_OCS_AFFECT_COMPUTER",0);
define("RULE_AFFECT_RIGHTS",1);
define("RULE_TRACKING_AUTO_ACTION",2);
define("RULE_SOFTWARE_CATEGORY",3);
define("RULE_DICTIONNARY_SOFTWARE",4);
define("RULE_DICTIONNARY_MANUFACTURER",5);
define("RULE_DICTIONNARY_MODEL_COMPUTER",6);
define("RULE_DICTIONNARY_TYPE_COMPUTER",7);
define("RULE_DICTIONNARY_MODEL_MONITOR",8);
define("RULE_DICTIONNARY_TYPE_MONITOR",9);
define("RULE_DICTIONNARY_MODEL_PRINTER",10);
define("RULE_DICTIONNARY_TYPE_PRINTER",11);
define("RULE_DICTIONNARY_MODEL_PHONE",12);
define("RULE_DICTIONNARY_TYPE_PHONE",13);
define("RULE_DICTIONNARY_MODEL_PERIPHERAL",14);
define("RULE_DICTIONNARY_TYPE_PERIPHERAL",15);
define("RULE_DICTIONNARY_MODEL_NETWORKING",16);
define("RULE_DICTIONNARY_TYPE_NETWORKING",17);
define("RULE_DICTIONNARY_OS",18);
define("RULE_DICTIONNARY_OS_SP",19);
define("RULE_DICTIONNARY_OS_VERSION",20);


//Bookmark types
define("BOOKMARK_SEARCH",1); //SEARCH SYSTEM bookmark


//OCS constants
define("OCS_COMPUTER_IMPORTED", 0);
define("OCS_COMPUTER_SYNCHRONIZED", 1);
define("OCS_COMPUTER_LINKED", 2);
define("OCS_COMPUTER_FAILED_IMPORT", 3);
define("OCS_COMPUTER_NOTUPDATED", 4);


// PLUGIN states
define("PLUGIN_NEW",0);
define("PLUGIN_ACTIVATED",1);
define("PLUGIN_NOTINSTALLED",2);
define("PLUGIN_TOBECONFIGURED",3);
define("PLUGIN_NOTACTIVATED",4);
define("PLUGIN_TOBECLEANED",5);


// Cron Task
define("CRONTASK_STATE_DISABLE",0);
define("CRONTASK_STATE_WAITING",1);
define("CRONTASK_STATE_RUNNING",2);

define("CRONTASK_MODE_INTERNAL",1);
define("CRONTASK_MODE_EXTERNAL",2);

define("CRONTASKLOG_STATE_START",0);
define("CRONTASKLOG_STATE_RUN",1);
define("CRONTASKLOG_STATE_STOP",2);


//DEVICE ARRAY.
// tables in alphabetic order
// type in numeric order
// *** Please respect ***
$CFG_GLPI["deleted_tables"] = array('glpi_budgets','glpi_cartridgesitems','glpi_computers',
                                    'glpi_consumablesitems','glpi_contacts','glpi_contracts',
                                    'glpi_documents','glpi_monitors','glpi_networkequipments',
                                    'glpi_peripherals','glpi_phones','glpi_printers','glpi_softwares',
                                    'glpi_suppliers','glpi_users',
                                    'reservation_types','state_types');

$CFG_GLPI["template_tables"] = array('glpi_budgets','glpi_computers','glpi_monitors',
                                     'glpi_networkequipments','glpi_peripherals','glpi_phones',
                                     'glpi_printers','glpi_softwares',
                                     'reservation_types','state_types');

$CFG_GLPI["dropdowntree_tables"] = array('glpi_entities', 'glpi_knowbaseitemscategories',
                                         'glpi_locations', 'glpi_taskscategories',
                                         'glpi_ticketscategories');

$CFG_GLPI["state_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                 PERIPHERAL_TYPE, PHONE_TYPE);

$CFG_GLPI["doc_types"]= array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                              PERIPHERAL_TYPE, SOFTWARE_TYPE, ENTERPRISE_TYPE, CONTRACT_TYPE,
                              CARTRIDGEITEM_TYPE, CONSUMABLEITEM_TYPE, PHONE_TYPE, ENTITY_TYPE,
                              SOFTWARELICENSE_TYPE, BUDGET_TYPE);

$CFG_GLPI["contract_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                    PERIPHERAL_TYPE, SOFTWARE_TYPE, PHONE_TYPE);

$CFG_GLPI["infocom_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                   PERIPHERAL_TYPE, SOFTWARE_TYPE, CARTRIDGEITEM_TYPE,
                                   CONSUMABLEITEM_TYPE, CONSUMABLE_TYPE, CARTRIDGE_TYPE, PHONE_TYPE,
                                   SOFTWARELICENSE_TYPE);

$CFG_GLPI["reservation_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                       PERIPHERAL_TYPE, SOFTWARE_TYPE,PHONE_TYPE);

$CFG_GLPI["linkuser_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                    PERIPHERAL_TYPE, SOFTWARE_TYPE, PHONE_TYPE);

$CFG_GLPI["linkgroup_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                     PERIPHERAL_TYPE, SOFTWARE_TYPE, PHONE_TYPE);

// TODO Cannot add a type >= 32 in this array (because of pow(2,type) used for helpdesk rights)
$CFG_GLPI["helpdesk_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                    PERIPHERAL_TYPE, SOFTWARE_TYPE, PHONE_TYPE);

$CFG_GLPI["link_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                PERIPHERAL_TYPE, SOFTWARE_TYPE, CONTACT_TYPE, ENTERPRISE_TYPE,
                                CONTRACT_TYPE, CARTRIDGEITEM_TYPE, CONSUMABLEITEM_TYPE, PHONE_TYPE,
                                BUDGET_TYPE);

$CFG_GLPI["helpdesk_visible_types"] = array(SOFTWARE_TYPE);

$CFG_GLPI["netport_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, PERIPHERAL_TYPE,
                                   PHONE_TYPE);

$CFG_GLPI["massiveaction_noupdate_types"] = array(ENTITY_TYPE, OCSNG_TYPE, PROFILE_TYPE);

$CFG_GLPI["massiveaction_nodelete_types"] = array(ENTITY_TYPE, CRONTASK_TYPE);

$CFG_GLPI["specif_entities_tables"] = array('glpi_budgets','glpi_cartridgesitems','glpi_computers',
      'glpi_consumablesitems','glpi_contacts','glpi_contracts','glpi_documents','glpi_groups',
      'glpi_links','glpi_locations','glpi_mailcollectors','glpi_monitors','glpi_netpoints',
      'glpi_networkequipments','glpi_peripherals','glpi_phones','glpi_printers','glpi_softwares',
      'glpi_softwareslicenses','glpi_suppliers','glpi_tickets','glpi_taskscategories',
      'glpi_ticketscategories','reservation_types','state_types');

$CFG_GLPI["union_search_type"] = array(RESERVATION_TYPE=>"reservation_types",
                                       STATE_TYPE=>"state_types");

$CFG_GLPI["recursive_type"] = array(NETWORKING_TYPE      => "glpi_networkequipments",
                                    PRINTER_TYPE         => "glpi_printers",
                                    SOFTWARE_TYPE        => "glpi_softwares",
                                    CONTACT_TYPE         => "glpi_contacts",
                                    ENTERPRISE_TYPE      => "glpi_suppliers",
                                    CONTRACT_TYPE        => "glpi_contracts",
                                    DOCUMENT_TYPE        => "glpi_documents",
                                    KNOWBASE_TYPE        => "glpi_knowbaseitems",
                                    SOFTWARELICENSE_TYPE => "glpi_softwareslicenses",
                                    LINK_TYPE            => "glpi_links",
                                    GROUP_TYPE           => "glpi_groups",
                                    BUDGET_TYPE          => "glpi_budgets",
                                    TICKETCATEGORY_TYPE  => "glpi_ticketscategories",
                                    TASKCATEGORY_TYPE    => "glpi_taskscategories",
                                    LOCATION_TYPE        => "glpi_locations");


// New config options which can be missing during migration
$CFG_GLPI["number_format"]=0;
$CFG_GLPI["decimal_number"]=2;


// Default debug options : may be locally overriden
$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=$CFG_GLPI["debug_lang"]=1;


// User Prefs fields which override $CFG_GLPI config
$CFG_GLPI['user_pref_field'] = array('date_format','default_requesttypes_id','dropdown_chars_limit',
      'followup_private','is_categorized_soft_expanded','is_ids_visible',
      'is_not_categorized_soft_expanded','language','list_limit','number_format','priority_1',
      'priority_2','priority_3','priority_4','priority_5','show_jobs_at_login','use_flat_dropdowntree');

?>
