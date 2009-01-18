<?php

	// Current version of GLPI
	define("GLPI_VERSION","0.72");
	define("GLPI_DEMO_MODE","0");

	
	// dictionnaries
	// 0 Name - 1 lang file -  2 calendar dico - 3 toolbar dico
	$CFG_GLPI["languages"]=array(   
		"pt_BR"=>array("Brazilian","pt_BR.php","pt_BR","pt"),
		"bg_BG"=>array("Bulgarian","bg_BG.php","bg","bg"),
		"ca_CA"=>array("Catalan","ca_CA.php","ca","ca"),
		"cs_CZ"=>array("Czech","cs_CZ.php","cs","cs"),
		"de_DE"=>array("Deutsch","de_DE.php","de","de"),
		"dk_DK"=>array("Danish","dk_DK.php","da","da"),
		"nl_NL"=>array("Dutch","nl_NL.php","nl","nl"),
		"nl_BE"=>array("Dutch (Belgium)","nl.php","nl","nl"),
		"en_GB"=>array("English","en_GB.php","en","en"),
		"es_AR"=>array("Español (Argentina)","es.php","es","es"),
		"es_ES"=>array("Español (España)","es.php","es","es"),
		"fr_FR"=>array("Français","fr_FR.php","fr","fr"),
		"gl_ES"=>array("Galician","gl_ES.php","es","es"),
		"el_EL"=>array("Greek","el_EL.php","el_GR","el"),
		"hu_HU"=>array("Hungarian","hu_HU.php","hu","hu"),
		"it_IT"=>array("Italiano","it_IT.php","it","it"),
		"ja_JP"=>array("Japanese","ja_JP.php","ja","ja"),
		"lv_LV"=>array("Latvian","lv_LV.php","lv","lv"),
		"lt_LT"=>array("Lithuanian","lt_LT.php","lt","lt"),
		"pl_PL"=>array("Polish","pl_PL.php","pl","pl"),
		"pt_PT"=>array("Português","pt_PT.php","pt","pt"),
		"ro_RO"=>array("Romanian","ro_RO.php","ro","en"),
		"ru_RU"=>array("Russian","ru_RU.php","ru","ru"),
		"zh_CN"=>array("Simplified Chinese","zh_CN.php","en","zh"),
		"sl_SL"=>array("Slovenian","sl_SI.php","sl","sl"),
		"sv_SE"=>array("Swedish","sv_SE.php","sv_SE","sv"),
		"tr_TR"=>array("Turkish","tr_TR.php","tr","en"),
		);

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


	// ITEMS TYPE
	define("GENERAL_TYPE",0);//
	define("COMPUTER_TYPE",1);//
	define("NETWORKING_TYPE",2);//
	define("PRINTER_TYPE",3);//
	define("MONITOR_TYPE",4);//
	define("PERIPHERAL_TYPE",5);//
	define("SOFTWARE_TYPE",6);//
	define("CONTACT_TYPE",7);//
	define("ENTERPRISE_TYPE",8);//
	define("INFOCOM_TYPE",9);//
	define("CONTRACT_TYPE",10);//
	define("CARTRIDGE_TYPE",11);//
	define("TYPEDOC_TYPE",12);
	define("DOCUMENT_TYPE",13);//
	define("KNOWBASE_TYPE",14);//
	define("USER_TYPE",15);//
	define("TRACKING_TYPE",16);//
	define("CONSUMABLE_TYPE",17);//
	define("CONSUMABLE_ITEM_TYPE",18);
	define("CARTRIDGE_ITEM_TYPE",19);
	define("SOFTWARELICENSE_TYPE",20);
	define("LINK_TYPE",21);
	define("STATE_TYPE",22);
	define("PHONE_TYPE",23);//
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

	// Default number of items displayed in global search
	define("GLOBAL_SEARCH_DISPLAY_COUNT",10);
	
	$LINK_ID_TABLE=array(
			COMPUTER_TYPE=> "glpi_computers",
			NETWORKING_TYPE => "glpi_networking",
			PRINTER_TYPE => "glpi_printers",
			MONITOR_TYPE => "glpi_monitors",
			PERIPHERAL_TYPE => "glpi_peripherals",
			SOFTWARE_TYPE => "glpi_software",
			CONTACT_TYPE => "glpi_contacts",
			ENTERPRISE_TYPE => "glpi_enterprises",
			INFOCOM_TYPE => "glpi_infocoms",
			CONTRACT_TYPE => "glpi_contracts",
			CARTRIDGE_TYPE => "glpi_cartridges_type",
			TYPEDOC_TYPE => "glpi_type_docs",
			DOCUMENT_TYPE => "glpi_docs",
			KNOWBASE_TYPE => "glpi_kbitems",
			USER_TYPE => "glpi_users",
			TRACKING_TYPE => "glpi_tracking",
			CONSUMABLE_TYPE => "glpi_consumables_type",
			CONSUMABLE_ITEM_TYPE => "glpi_consumables",
			CARTRIDGE_ITEM_TYPE => "glpi_cartridges",
			SOFTWARELICENSE_TYPE => "glpi_softwarelicenses",
			LINK_TYPE => "glpi_links",
			STATE_TYPE => "",
			PHONE_TYPE => "glpi_phones",
			DEVICE_TYPE => "???",
			REMINDER_TYPE => "glpi_reminder",
			STAT_TYPE => "???",
			GROUP_TYPE => "glpi_groups",
			ENTITY_TYPE => "glpi_entities",
			RESERVATION_TYPE => "glpi_reservation_item",
			OCSNG_TYPE => "glpi_ocs_config",
			REGISTRY_TYPE => "glpi_registry",
			PROFILE_TYPE => "glpi_profiles",
			MAILGATE_TYPE => "glpi_mailgate",
			RULE_TYPE => "glpi_rules_descriptions",
			TRANSFER_TYPE => "glpi_transfers",
			SOFTWAREVERSION_TYPE => "glpi_softwareversions",
			COMPUTERDISK_TYPE => "glpi_computerdisks",
			NETWORKING_PORT_TYPE => "glpi_networking_ports"
			);

	$INFOFORM_PAGES=array( 
			COMPUTER_TYPE=> "front/computer.form.php",
			NETWORKING_TYPE => "front/networking.form.php",
			PRINTER_TYPE => "front/printer.form.php",
			MONITOR_TYPE => "front/monitor.form.php",
			PERIPHERAL_TYPE => "front/peripheral.form.php",
			SOFTWARE_TYPE => "front/software.form.php",
			CONTACT_TYPE => "front/contact.form.php",
			ENTERPRISE_TYPE => "front/enterprise.form.php",
			INFOCOM_TYPE => "front/infocom.form.php",
			CONTRACT_TYPE => "front/contract.form.php",
			CARTRIDGE_TYPE => "front/cartridge.form.php",
			TYPEDOC_TYPE => "front/typedoc.form.php",
			DOCUMENT_TYPE => "front/document.form.php",
			KNOWBASE_TYPE => "front/knowbase.form.php",
			USER_TYPE => "front/user.form.php",
			TRACKING_TYPE => "front/tracking.form.php",
			CONSUMABLE_TYPE => "front/consumable.form.php",
//			CONSUMABLE_ITEM_TYPE => "??",
//			CARTRIDGE_ITEM_TYPE => "??",
			SOFTWARELICENSE_TYPE => "front/softwarelicense.form.php",
			LINK_TYPE => "front/link.form.php",
//			STATE_TYPE => "??",
			PHONE_TYPE => "front/phone.form.php",
//			DEVICE_TYPE => "???",
			REMINDER_TYPE => "front/reminder.form.php",
//			STAT_TYPE => "???",
			GROUP_TYPE => "front/group.form.php",
			ENTITY_TYPE => "front/entity.form.php",
//			RESERVATION_TYPE => "???",
			OCSNG_TYPE => "front/ocsng.form.php",
			REGISTRY_TYPE => "???",
			PROFILE_TYPE => "front/profile.form.php",
			MAILGATE_TYPE => "front/mailgate.form.php",
//			RULE_TYPE => "???",
			TRANSFER_TYPE => "front/transfer.form.php",
			SOFTWAREVERSION_TYPE => "front/softwareversion.form.php",
			COMPUTERDISK_TYPE => "front/computerdisk.form.php",
			NETWORKING_PORT_TYPE => "/front/networking.port.php"
			);
	$SEARCH_PAGES=array( 
			COMPUTER_TYPE=> "front/computer.php",
			NETWORKING_TYPE => "front/networking.php",
			PRINTER_TYPE => "front/printer.php",
			MONITOR_TYPE => "front/monitor.php",
			SOFTWARE_TYPE => "front/software.php",
			PERIPHERAL_TYPE => "front/peripheral.php",
			CONTACT_TYPE => "front/contact.php",
			ENTERPRISE_TYPE => "front/enterprise.php",
			CONTRACT_TYPE => "front/contract.php",
			DOCUMENT_TYPE => "front/document.php",
		);

	define("AUTH_DB_GLPI",1);
	define("AUTH_MAIL",2);
	define("AUTH_LDAP",3);
	define("AUTH_EXTERNAL",4);
	define("AUTH_CAS",5);
	define("AUTH_X509",6);
	define("NOT_YET_AUTHENTIFIED",-1);

	//Mail send methods
	define("MAIL_MAIL",0);
	define("MAIL_SMTP",1);
	define("MAIL_SMTPSSL",2);
	define("MAIL_SMTPTLS",3);
	


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

	//Type of process to import datas in GLPI
	define("IMPORT_TYPE_OCS",0); //Import from OCS
	define("IMPORT_TYPE_DICTIONNARY",1); //Import or modified from dictionnaries

	//Bookmark types
	define("BOOKMARK_SEARCH",1); //SEARCH SYSTEM bookmark


	// PLUGIN states
	define("PLUGIN_NEW",0);
	define("PLUGIN_ACTIVATED",1);
	define("PLUGIN_NOTINSTALLED",2);
	define("PLUGIN_TOBECONFIGURED",3);
	define("PLUGIN_NOTACTIVATED",4);
	define("PLUGIN_TOBECLEANED",5);

	//DEVICE ARRAY.
	$CFG_GLPI["devices_tables"] =array("moboard","processor","ram","hdd","iface","drive","control","gfxcard","sndcard","pci","case","power");
	$CFG_GLPI["deleted_tables"]=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals",
		"glpi_software","glpi_cartridges_type","glpi_contracts","glpi_contacts","glpi_enterprises","glpi_docs","glpi_phones",
		"glpi_consumables_type","glpi_users","state_types","reservation_types");
	
	$CFG_GLPI["template_tables"]=array("glpi_computers","glpi_networking","glpi_printers","glpi_monitors","glpi_peripherals","glpi_software","glpi_phones","state_types","reservation_types","glpi_ocs_config");
	
	$CFG_GLPI["dropdowntree_tables"]=array("glpi_entities","glpi_dropdown_locations","glpi_dropdown_kbcategories","glpi_dropdown_tracking_category");
	$CFG_GLPI["state_types"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE);
	$CFG_GLPI["infocom_types"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,SOFTWARE_TYPE,CONSUMABLE_TYPE,CARTRIDGE_TYPE,CONSUMABLE_ITEM_TYPE,CARTRIDGE_ITEM_TYPE);
	$CFG_GLPI["reservation_types"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,SOFTWARE_TYPE);
	$CFG_GLPI["linkuser_types"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,SOFTWARE_TYPE);
	$CFG_GLPI["helpdesk_types"]=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,SOFTWARE_TYPE);
 	$CFG_GLPI["netport_types"]=array(COMPUTER_TYPE,PRINTER_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE);

	$CFG_GLPI["specif_entities_tables"]=array("glpi_cartridges_type","glpi_computers","glpi_consumables_type",
		"glpi_contacts","glpi_contracts","glpi_docs",
		"glpi_dropdown_locations","glpi_dropdown_netpoint","glpi_enterprises","glpi_groups",
		"glpi_mailgate","glpi_monitors","glpi_networking","glpi_peripherals","glpi_phones","glpi_printers","glpi_software",
		"glpi_softwarelicenses", "glpi_tracking","state_types","reservation_types","glpi_links");

	$CFG_GLPI["union_search_type"]=array(RESERVATION_TYPE=>"reservation_types",STATE_TYPE=>"state_types");

	$CFG_GLPI["recursive_type"]=array(CONTACT_TYPE=>"glpi_contacts", ENTERPRISE_TYPE=>"glpi_enterprises", CONTRACT_TYPE=>"glpi_contracts", 
		DOCUMENT_TYPE=>"glpi_docs", KNOWBASE_TYPE=>"glpi_kbitems", NETWORKING_TYPE => "glpi_networking", GROUP_TYPE => "glpi_groups",
		SOFTWARE_TYPE => "glpi_software", SOFTWARELICENSE_TYPE => "glpi_softwarelicenses", PRINTER_TYPE => "glpi_printers",LINK_TYPE=>"glpi_links");
		
	// New config options which can be missing during migration
	$_SESSION["glpinumberformat"]=0;
	$CFG_GLPI["decimal_number"]=2;

	// Default debug options : may be locally overriden
	$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=$CFG_GLPI["debug_lang"]=1; 

	
	// User Prefs fields which override $CFG_GLPI config
	$CFG_GLPI['user_pref_field']=array("language","list_limit","dateformat","numberformat","view_ID","dropdown_limit","flat_dropdowntree","num_of_events","nextprev_item","jobs_at_login","priority_1","priority_2","priority_3","priority_4","priority_5","expand_soft_categorized","expand_soft_not_categorized","tracking_order","followup_private");


?>