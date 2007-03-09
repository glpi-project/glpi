<?php
/*
 * @version $Id: rules.constant.php 4415 2007-02-16 13:46:55Z moyo $
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


$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][1]['ID']='TAG';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][1]['table']='accountinfo';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][1]['field']='TAG';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][1]['name']=$LANG["ocsconfig"][39];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][1]['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][2]['ID']='DOMAIN';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][2]['table']='hardware';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][2]['field']='WORKGROUP';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][2]['name']=$LANG["setup"][89];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][2]['linkfield']='';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][3]['ID']='OCS_SERVER';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][3]['table']='';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][3]['field']='ocs_server';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][3]['name']=$LANG["ocsng"][29];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][3]['linkfield']='';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][4]['ID']='IPSUBNET';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][4]['table']='networks';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][4]['field']='IPSUBNET';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][4]['name']=$LANG["ocsconfig"][42];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][4]['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][5]['ID']='IPADDRESS';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][5]['table']='networks';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][5]['field']='IPADDRESS';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][5]['name']=$LANG["financial"][44]." ".$LANG["networking"][14];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][5]['linkfield']='HARDWARE_ID';

$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][6]['ID']='MACHINE_NAME';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][6]['table']='hardware';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][6]['field']='NAME';
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][6]['name']=$LANG["rulesengine"][25];
$RULES_CRITERIAS[RULE_OCS_AFFECT_COMPUTER][6]['linkfield']='';

?>
