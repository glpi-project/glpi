<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['table']='';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['field']='ocs_server';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['name']=$LANG["ocsng"][29];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['OCS_SERVER']['linkfield']='';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['table']='networks';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['field']='IPSUBNET';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['name']=$LANG["ocsconfig"][42];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPSUBNET']['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['table']='networks';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['field']='IPADDRESS';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['name']=$LANG["financial"][44]." ".$LANG["networking"][14];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['IPADDRESS']['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['table']='hardware';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['field']='NAME';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['name']=$LANG["rulesengine"][25];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER]['MACHINE_NAME']['linkfield']='';

$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['table']='glpi_auth_ldap';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['field']='name';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['name']=$LANG["login"][2];
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['linkfield']='';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['type']='dropdown';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['virtual']='true';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['LDAP_SERVER']['id']='ldap_server';

$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['table']='glpi_groups';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['field']='name';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['name']=$LANG["Menu"][36];
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['linkfield']='';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['type']='dropdown';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['virtual']='true';
$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY]['GROUPS']['id']='groups';


$RULES_ACTIONS[RULE_OCS_AFFECT_COMPUTER]['FK_entities']['name']=$LANG["entity"][0];
$RULES_ACTIONS[RULE_OCS_AFFECT_COMPUTER]['FK_entities']['type']='dropdown';
$RULES_ACTIONS[RULE_OCS_AFFECT_COMPUTER]['FK_entities']['table']='glpi_entities';

$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['FK_entities']['name']=$LANG["entity"][0];
$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['FK_entities']['type']='dropdown';
$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['FK_entities']['table']='glpi_entities';

$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['FK_profiles']['name']=$LANG["Menu"][35];
$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['FK_profiles']['type']='dropdown';
$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['FK_profiles']['table']='glpi_profiles';

$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['recursive']['name']=$LANG["profiles"][28];
$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['recursive']['type']='yesno';
$RULES_ACTIONS[RULE_LDAP_AFFECT_ENTITY]['recursive']['table']='';

// BUSINESS RULES FOR TRACKING

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['table']='glpi_tracking';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['name']=$LANG["common"][57];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['linkfield']='name';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['table']='glpi_tracking';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['field']='contents';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['name']=$LANG["joblist"][6];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['name']['linkfield']='contents';

$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['table']='glpi_dropdown_tracking_category';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['field']='name';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['name']=$LANG["common"][36];
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['linkfield']='category';
$RULES_CRITERIAS[RULE_TRACKING_AUTO_ACTION]['category']['type']='dropdown';


$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['category']['name']=$LANG["common"][36];
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['category']['type']='dropdown';
$RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]['category']['table']='glpi_dropdown_tracking_category';

?>
