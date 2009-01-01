<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------


$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['TAG']['table']='accountinfo';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['TAG']['field']='TAG';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['TAG']['name']=$LANG["ocsconfig"][39];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['TAG']['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DOMAIN']['table']='hardware';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DOMAIN']['field']='WORKGROUP';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DOMAIN']['name']=$LANG["setup"][89];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DOMAIN']['linkfield']='';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['table']='glpi_ocs_config';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['field']='name';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['name']=$LANG["ocsng"][29];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['linkfield']='';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['type']='dropdown';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['virtual']='true';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['id']='ocs_server';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['table']='networks';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['field']='IPSUBNET';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['name']=$LANG["networking"][61];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['table']='networks';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['field']='IPADDRESS';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['name']=$LANG["financial"][44]." ".$LANG["networking"][14];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['table']='hardware';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['field']='NAME';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['name']=$LANG["rulesengine"][25];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['linkfield']='';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DESCRIPTION']['table']='hardware';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DESCRIPTION']['field']='DESCRIPTION';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DESCRIPTION']['name']=$LANG["joblist"][6];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['DESCRIPTION']['linkfield']='';

$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['table']='glpi_auth_ldap';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['field']='name';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['name']=$LANG["login"][2];
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['linkfield']='';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['type']='dropdown';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['virtual']='true';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['LDAP_SERVER']['id']='ldap_server';

$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['table']='glpi_auth_mail';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['field']='name';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['name']=$LANG["login"][3];
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['linkfield']='';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['type']='dropdown';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['virtual']='true';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_SERVER']['id']='mail_server';

$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_EMAIL']['table']='';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_EMAIL']['field']='';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_EMAIL']['name']=$LANG["login"][6]." ".$LANG["login"][3];
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_EMAIL']['linkfield']='';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_EMAIL']['virtual']='true';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['MAIL_EMAIL']['id']='mail_email';

$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['table']='glpi_groups';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['field']='name';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['name']=$LANG["Menu"][36]." ".$LANG["login"][2];
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['linkfield']='';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['type']='dropdown';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['virtual']='true';
$RULES_CRITERIAS[RULE_AFFECT_RIGHTS]['GROUPS']['id']='groups';

$RULES_ACTIONS[RULE_OCS_AFFECT_COMPUTER]['FK_entities']['name']=$LANG["entity"][0];
$RULES_ACTIONS[RULE_OCS_AFFECT_COMPUTER]['FK_entities']['type']='dropdown';
$RULES_ACTIONS[RULE_OCS_AFFECT_COMPUTER]['FK_entities']['table']='glpi_entities';

$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['FK_entities']['name']=$LANG["entity"][0];
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['FK_entities']['type']='dropdown';
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['FK_entities']['table']='glpi_entities';

$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['_affect_entity_by_dn']['name']=$LANG["rulesengine"][130];
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['_affect_entity_by_dn']['type']='text';

$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['_affect_entity_by_tag']['name']=$LANG["rulesengine"][131];
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['_affect_entity_by_tag']['type']='text';

$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['FK_profiles']['name']=$LANG["Menu"][35];
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['FK_profiles']['type']='dropdown';
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['FK_profiles']['table']='glpi_profiles';

$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['recursive']['name']=$LANG["profiles"][28];
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['recursive']['type']='yesno';
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['recursive']['table']='';

$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['active']['name']=$LANG["common"][60];
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['active']['type']='yesno';
$RULES_ACTIONS[RULE_AFFECT_RIGHTS]['active']['table']='';

// BUSINESS RULES FOR TRACKING

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['table']='glpi_tracking';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['name']=$LANG["common"][57];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['linkfield']='name';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['contents']['table']='glpi_tracking';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['contents']['field']='contents';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['contents']['name']=$LANG["joblist"][6];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['contents']['linkfield']='contents';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['table']='glpi_dropdown_tracking_category';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['name']=$LANG["common"][36];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['linkfield']='category';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['type']='dropdown';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author']['table']='glpi_users';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author']['name']=$LANG["job"][4]." - ".$LANG["common"][34];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author']['linkfield']='author';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author']['type']='dropdown_users';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author_location']['table']='glpi_dropdown_locations';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author_location']['field']='completename';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author_location']['name']=$LANG["job"][4]." - ".$LANG["common"][15];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author_location']['linkfield']='author_location';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['author_location']['type']='dropdown';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_group']['table']='glpi_groups';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_group']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_group']['name']=$LANG["job"][4]." - ".$LANG["common"][35];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_group']['linkfield']='FK_group';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_group']['type']='dropdown';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign']['table']='glpi_users';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign']['name']=$LANG["job"][5]." - ".$LANG["job"][6];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign']['linkfield']='assign';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign']['type']='dropdown_users';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign_group']['table']='glpi_groups';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign_group']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign_group']['name']=$LANG["job"][5]." - ".$LANG["common"][35];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign_group']['linkfield']='assign_group';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['assign_group']['type']='dropdown';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['request_type']['table']='glpi_tracking';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['request_type']['field']='request_type';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['request_type']['name']=$LANG["job"][44];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['request_type']['linkfield']='request_type';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['request_type']['type']='dropdown_request_type';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['device_type']['table']='glpi_tracking';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['device_type']['field']='device_type';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['device_type']['name']=$LANG["state"][6];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['device_type']['linkfield']='device_type';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['device_type']['type']='dropdown_tracking_device_type';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_entities']['table']='glpi_entities';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_entities']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_entities']['name']=$LANG["entity"][0];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_entities']['linkfield']='FK_entities';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['FK_entities']['type']='dropdown';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['priority']['name']=$LANG["joblist"][2];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['priority']['type']='dropdown_priority';


$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['category']['name']=$LANG["common"][36];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['category']['type']='dropdown';
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['category']['table']='glpi_dropdown_tracking_category';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['author']['name']=$LANG["job"][4]." - ".$LANG["common"][34];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['author']['type']='dropdown_users';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['FK_group']['name']=$LANG["job"][4]." - ".$LANG["common"][35];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['FK_group']['type']='dropdown';
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['FK_group']['table']='glpi_groups';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['assign']['name']=$LANG["job"][5]." - ".$LANG["job"][6];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['assign']['type']='dropdown_assign';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['assign_group']['table']='glpi_groups';
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['assign_group']['name']=$LANG["job"][5]." - ".$LANG["common"][35];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['assign_group']['type']='dropdown';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['priority']['name']=$LANG["joblist"][2];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['priority']['type']='dropdown_priority';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['status']['name']=$LANG["joblist"][0];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['status']['type']='dropdown_status';

$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['affectobject']['name']= $LANG["common"][1];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['affectobject']['type']='text';
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['affectobject']['force_actions']=array("affectbyip","affectbyfqdn","affectbymac");

//Software categories
$RULES_CRITERIAS[RULE_SOFTWARE_CATEGORY]['name']['field']='name';
$RULES_CRITERIAS[RULE_SOFTWARE_CATEGORY]['name']['name']=$LANG["help"][31];
$RULES_CRITERIAS[RULE_SOFTWARE_CATEGORY]['name']['table']='glpi_software';

$RULES_CRITERIAS[RULE_SOFTWARE_CATEGORY]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_SOFTWARE_CATEGORY]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_SOFTWARE_CATEGORY]['manufacturer']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_SOFTWARE_CATEGORY]['category']['name']=$LANG["common"][36];
$RULES_ACTIONS[RULE_SOFTWARE_CATEGORY]['category']['type']='dropdown';
$RULES_ACTIONS[RULE_SOFTWARE_CATEGORY]['category']['table']='glpi_dropdown_software_category';

//Dictionnary Software
$RULES_CRITERIAS[RULE_DICTIONNARY_SOFTWARE]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_SOFTWARE]['name']['name']=$LANG["help"][31];
$RULES_CRITERIAS[RULE_DICTIONNARY_SOFTWARE]['name']['table']='glpi_software';

$RULES_CRITERIAS[RULE_DICTIONNARY_SOFTWARE]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_SOFTWARE]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_SOFTWARE]['manufacturer']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['name']['name']=$LANG["help"][31];
$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['name']['force_actions']=array("assign","regex_result");

$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['_ignore_ocs_import']['name']=$LANG["ocsconfig"][6]; 
$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['_ignore_ocs_import']['type']="yesno";

$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['version']['name']=$LANG["rulesengine"][78];
$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['version']['force_actions']=array("assign","regex_result","append_regex_result");

$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['manufacturer']['name']=$LANG["common"][5];
$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['manufacturer']['table']="glpi_dropdown_manufacturer";
$RULES_ACTIONS[RULE_DICTIONNARY_SOFTWARE]['manufacturer']['type']='dropdown';

//Dictionnary Manufacturer
$RULES_CRITERIAS[RULE_DICTIONNARY_MANUFACTURER]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MANUFACTURER]['name']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_MANUFACTURER]['name']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_DICTIONNARY_MANUFACTURER]['name']['name']=$LANG["common"][5];
$RULES_ACTIONS[RULE_DICTIONNARY_MANUFACTURER]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Model Computer
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_COMPUTER]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_COMPUTER]['name']['name']=$LANG["common"][22];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_COMPUTER]['name']['table']='glpi_dropdown_model';

$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_COMPUTER]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_COMPUTER]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_COMPUTER]['manufacturer']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_COMPUTER]['name']['name']=$LANG["common"][22];
$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_COMPUTER]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Model Monitor
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_MONITOR]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_MONITOR]['name']['name']=$LANG["common"][22];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_MONITOR]['name']['table']='glpi_dropdown_model_monitors';

$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_MONITOR]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_MONITOR]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_MONITOR]['manufacturer']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_MONITOR]['name']['name']=$LANG["common"][22];
$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_MONITOR]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Model Printer
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PRINTER]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PRINTER]['name']['name']=$LANG["common"][22];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PRINTER]['name']['table']='glpi_dropdown_model_printers';

$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PRINTER]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PRINTER]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PRINTER]['manufacturer']['table']='glpi_dropdown_manufacturer';


$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_PRINTER]['name']['name']=$LANG["common"][22];
$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_PRINTER]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Model Peripheral
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['name']['name']=$LANG["common"][22];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['name']['table']='glpi_dropdown_model_peripherals';

$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['manufacturer']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['name']['name']=$LANG["common"][22];
$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_PERIPHERAL]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Model Networking
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_NETWORKING]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_NETWORKING]['name']['name']=$LANG["common"][22];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_NETWORKING]['name']['table']='glpi_dropdown_model_networking';

$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_NETWORKING]['manufacturer']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_NETWORKING]['manufacturer']['name']=$LANG["common"][5];
$RULES_CRITERIAS[RULE_DICTIONNARY_MODEL_NETWORKING]['manufacturer']['table']='glpi_dropdown_manufacturer';

$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_NETWORKING]['name']['name']=$LANG["common"][22];
$RULES_ACTIONS[RULE_DICTIONNARY_MODEL_NETWORKING]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Type Computer
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_COMPUTER]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_COMPUTER]['name']['name']=$LANG["common"][17];
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_COMPUTER]['name']['table']='glpi_type_computers';

$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_COMPUTER]['name']['name']=$LANG["common"][17];
$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_COMPUTER]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Type Monitor
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_MONITOR]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_MONITOR]['name']['name']=$LANG["common"][17];
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_MONITOR]['name']['table']='glpi_type_monitors';

$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_MONITOR]['name']['name']=$LANG["common"][17];
$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_MONITOR]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Type Printer
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_PRINTER]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_PRINTER]['name']['name']=$LANG["common"][17];
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_PRINTER]['name']['table']='glpi_type_printers';

$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_PRINTER]['name']['name']=$LANG["common"][17];
$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_PRINTER]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Type Peripheral
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_PERIPHERAL]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_PERIPHERAL]['name']['name']=$LANG["common"][17];
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_PERIPHERAL]['name']['table']='glpi_type_peripherals';

$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_PERIPHERAL]['name']['name']=$LANG["common"][17];
$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_PERIPHERAL]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary Type Networking
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_NETWORKING]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_NETWORKING]['name']['name']=$LANG["common"][17];
$RULES_CRITERIAS[RULE_DICTIONNARY_TYPE_NETWORKING]['name']['table']='glpi_type_networking';

$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_NETWORKING]['name']['name']=$LANG["common"][17];
$RULES_ACTIONS[RULE_DICTIONNARY_TYPE_NETWORKING]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary OS
$RULES_CRITERIAS[RULE_DICTIONNARY_OS]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_OS]['name']['name']=$LANG["computers"][9];
$RULES_CRITERIAS[RULE_DICTIONNARY_OS]['name']['table']='glpi_dropdown_os';

$RULES_ACTIONS[RULE_DICTIONNARY_OS]['name']['name']=$LANG["computers"][9];
$RULES_ACTIONS[RULE_DICTIONNARY_OS]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary OS SP
$RULES_CRITERIAS[RULE_DICTIONNARY_OS_SP]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_OS_SP]['name']['name']=$LANG["computers"][53];
$RULES_CRITERIAS[RULE_DICTIONNARY_OS_SP]['name']['table']='glpi_dropdown_os_sp';

$RULES_ACTIONS[RULE_DICTIONNARY_OS_SP]['name']['name']=$LANG["computers"][53];
$RULES_ACTIONS[RULE_DICTIONNARY_OS_SP]['name']['force_actions']=array("assign","regex_result","append_regex_result");

//Dictionnary OS Version
$RULES_CRITERIAS[RULE_DICTIONNARY_OS_VERSION]['name']['field']='name';
$RULES_CRITERIAS[RULE_DICTIONNARY_OS_VERSION]['name']['name']=$LANG["rulesengine"][78];
$RULES_CRITERIAS[RULE_DICTIONNARY_OS_VERSION]['name']['table']='glpi_dropdown_os_version';

$RULES_ACTIONS[RULE_DICTIONNARY_OS_VERSION]['name']['name']=$LANG["rulesengine"][78];
$RULES_ACTIONS[RULE_DICTIONNARY_OS_VERSION]['name']['force_actions']=array("assign","regex_result","append_regex_result");
?>
