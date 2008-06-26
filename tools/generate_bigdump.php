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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


// BIG DUMP GENERATION FOR THE 0.6 VERSION

define('GLPI_ROOT', '..');
include (GLPI_ROOT."/inc/includes.php");
include ("generate_bigdump.function.php");

$entity_number=5;

$multiplicator=0.2;

$MAX['locations']=100;
$MAX['kbcategories']=8;
$MAX['tracking_category']=5;
$MAX_KBITEMS_BY_CAT=10;

// DROPDOWNS
$MAX['budget']=1;
$MAX['consumable_type']=1;
$MAX['cartridge_type']=1;
$MAX['contact_type']=1;
$MAX['user_title']=3;
$MAX['user_type']=3;
$MAX['vlan']=5;
$MAX_CONTRACT_TYPE=7;
$MAX['domain']=5;
$MAX['enttype']=1;
$MAX['firmware']=5;
$MAX['interface']=1;
$MAX['case_type']=1;
$MAX['iface']=5;
$MAX['model']=5;
$MAX['network']=5;
$MAX['os']=5;
$MAX['os_version']=5;
$MAX['os_sp']=5;
$MAX['ram_type']=5;
$MAX['rubdocs']=5;
$MAX['softwarecategory']=5;
$MAX['licensetype']=5;
$MAX['state']=5;
$MAX['vlan']=5;
$MAX['type_computers']=3;
$MAX['type_printers']=3;
$MAX['type_monitors']=3;
$MAX['type_peripherals']=5;
$MAX['type_networking']=3;
$MAX['type_phones']=3;
$MAX['model_printers']=10;
$MAX['model_monitors']=10;
$MAX['model_peripherals']=10;
$MAX['model_phones']=10;
$MAX['model_networking']=10;
$MAX['netpoint']=1000;
$MAX['auto_update']=3;
$MAX['phone_power']=3;
$MAX['manufacturer']=5;

// USERS
$MAX['users_sadmin']=1;
$MAX['users_admin']=50;
$MAX['users_normal']=50;
$MAX['users_postonly']=1000;
$MAX['enterprises']=5;
$MAX['contacts']=10;
$MAX['groups']=3;

// INVENTORY ITEMS
$MAX['computers']=1000;
$MAX['printers']=100;
$MAX['networking']=$MAX['locations'];
$MAX['monitors']=$MAX['computers'];
$MAX['type_of_consumables']=5;
$MAX['consumables_stock']=2;
$MAX['consumables_given']=4;
$MAX['type_of_cartridges']=5;
$MAX['cartridges_by_printer']=4;
$MAX['cartridges_stock']=2;
$MAX['device']=10;
$MAX['software']=50;
$MAX['softwareversions']=5;
$MAX['softwareinstall']=$MAX['computers'];
$MAX['softwarelicenses']=2;
$MAX['global_peripherals']=10;
// DIRECT PERIPHERALS CONNECTED
$percent['peripherals']=5;

// DIRECT CONNECTED PRINTERS
$percent['printer']=5;
// PERCENT ELEMENTIN SPECIAL STATE
$percent['state']=70;
//PERIPHERALS
$MAX['connect_for_peripherals']=2;
// TRACKING :
$percent['tracking_on_item']=30;
$MAX['general_tracking']=100;
$percent['closed_tracking']=80;
$percent['followups']=50;
// RESERVATION
$percent['reservation']=1;
// DOCUMENT
$MAX['document']=10;
$DOC_PER_ITEM=2;
// CONTRACT
$MAX['contract']=10;
$CONTRACT_PER_ITEM=1;

foreach ($MAX as $key => $val){
	$MAX[$key]=$multiplicator*$val;
	$LAST[$key]=0;
}


$net_port=array();
$vlan_loc=array();


generateGlobalDropdowns();

optimize_tables ();

// Root entity
generate_entity(0);

// Entite
$added=0;
for ($i=0;$i<max(1,pow($entity_number,1/2))&&$added<$entity_number;$i++){
	$added++;
	$query="INSERT INTO glpi_entities VALUES (NULL,'entity $i','0','','comment entity $i','1')";
	$DB->query($query) or die("PB REQUETE ".$query);
	$newID=$DB->insert_id();
	generate_entity($newID);

	for ($j=0;$j<mt_rand(0,pow($entity_number,1/2))&&$added<$entity_number;$j++){
		$added++;
		$query="INSERT INTO glpi_entities VALUES (NULL,'s-entity $j','$newID','','comment s-entity $j','2')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$newID2=$DB->insert_id();
		generate_entity($newID2);
		for ($k=0;$k<mt_rand(0,pow($entity_number,1/2))&&$added<$entity_number;$k++){
			$added++;
			$query="INSERT INTO glpi_entities VALUES (NULL,'ss-entity $k','$newID2','','comment ss-entity $k','3')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$newID3=$DB->insert_id();
			generate_entity($newID3);
		}	
	}
}	

regenerateTreeCompleteName("glpi_entities");
regenerateTreeCompleteName("glpi_dropdown_locations");
regenerateTreeCompleteName("glpi_dropdown_kbcategories");

optimize_tables();	

?>
