<?
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


// BIG DUMP GENERATION FOR THE 0.6 VERSION

include ("_relpos.php");
include ($phproot."/glpi/includes.php");
$db=new DB();


$multiplicator=1;

$max['locations']=50;
$max['kbcategories']=10;

// DROPDOWNS
$max['consumable_type']=1;
$max['cartridge_type']=1;
$max['contact_type']=1;
$max['contract_type']=1;
$max['domain']=20;
$max['enttype']=10;
$max['firmware']=10;
$max['hdd_type']=10;
$max['iface']=10;
$max['model']=10;
$max['network']=10;
$max['os']=10;
$max['ram_type']=10;
$max['rubdocs']=10;
$max['state']=10;
$max['tracking_category']=10;
$max['vlan']=10;
$max['type_computers']=10;
$max['type_printers']=10;
$max['type_monitors']=10;
$max['type_peripherals']=5;
$max['type_networking']=10;
$max['model_printers']=10;
$max['model_monitors']=10;
$max['model_peripherals']=5;
$max['model_networking']=10;
$max['netpoint']=1000;
$max['auto_update']=1;

// USERS
$max['users_sadmin']=1;
$max['users_admin']=10;
$max['users_normal']=10;
$max['users_postonly']=100;
$max['enterprises']=10;
$max['contacts']=10;
// INVENTORY ITEMS
$max['computers']=1000;
$max['printers']=100;
$max['networking']=$max['locations'];
$max['monitors']=$max['computers'];
$max['type_of_cartridges']=5;
$max['cartridges_by_printer']=4;
$max['cartridges_stock']=2;
$max['device']=10;
$max['software']=10;
$max['global_peripherals']=10;
// DIRECT PERIPHERALS CONNECTED
$percent['peripherals']=5;

// DIRECT CONNECTED PRINTERS
$percent['printer']=5;
// PERCENT ELEMENTIN SPECIAL STATE
$percent['state']=2;
// LICENSES
$percent['free_software']=20;
$percent['global_software']=20;
$percent['normal_software']=60;
$max['normal_licenses_per_software']=10;
$max['free_licenses_per_software']=10;
$max['global_licenses_per_software']=10;
$max['more_licenses']=1;
//PERIPHERALS
$max['connect_for_peripherals']=30;
// TRACKING :
$percent['tracking_on_item']=30;
$max['general_tracking']=100;
$percent['closed_tracking']=90;
$percent['followups']=50;
// RESERVATION
$percent['reservation']=1;

foreach ($max as $key => $val)
	$max[$key]=$multiplicator*$val;

// you could repeat the alphabet to get more randomness
$alphabet = "1234567890abcdefghijklmnopqrstuvwxyz";


function GetRandomString($length) {

       global $alphabet;
       $rndstring="";
       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($alphabet) - 1);
               $rndstring .= $alphabet[$b];
       }
       return $rndstring;
}

function add_reservation($type,$ID){
	global $percent,$db;
	if (mt_rand(0,100)<$percent['reservation']){
		$query="INSERT INTO glpi_reservation_item VALUES ('','$type','$ID','')";
		$db->query($query) or die("PB REQUETE ".$query);
		// TODO add elements in reservation planning
	}
	
}

function add_tracking($type,$ID){
	global $percent,$db,$max;
	while (mt_rand(0,100)<$percent['tracking_on_item']){
		// tracking closed ?
		$status="old";
		if (mt_rand(0,100)<$percent['closed_tracking']){
			$date1=strtotime(mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28)." ".mt_rand(0,23).":".mt_rand(0,59).":".mt_rand(0,59));
			$date2=$date1+mt_rand(10800,7776000); // + entre 3 heures et 3 mois
			$status="old_done";
		} else {
			$date1=strtotime("2005-".mt_rand(1,12)."-".mt_rand(1,28));	
			$date2="";
			$status="new";
		}
		// Author
		$users[0]=mt_rand(1,$max['users_sadmin']+$max['users_admin']+$max['users_normal']+$max['users_postonly']);
		// Assign user
		$users[1]=mt_rand(1,$max['users_sadmin']+$max['users_admin']+$max['users_normal']);
		$query="INSERT INTO glpi_tracking VALUES ('','".date("Y-m-d H:i:s",$date1)."','".date("Y-m-d H:i:s",$date2)."','$status','".$users[0]."','".$users[1]."','".USER_TYPE."','$type','$ID','tracking ".GetRandomString(15)."','".mt_rand(1,5)."','no','','no','".(mt_rand(0,3)+mt_rand(0,100)/100)."','".mt_rand(1,$max['tracking_category'])."')";
		$db->query($query) or die("PB REQUETE ".$query);
		$tID=$db->insert_id();
		// Add followups
		$i=0;
		while (mt_rand(0,100)<$percent['followups']){
			$query="INSERT INTO glpi_followups VALUES ('','$tID','".date("Y-m-d H:i:s",$date1+mt_rand(3600,7776000))."','".$users[mt_rand(0,1)]."','followup $i ".GetRandomString(15)."','0','".mt_rand(0,3)."');";
			$db->query($query) or die("PB REQUETE ".$query);
			$i++;
			}
	}
}

// DROPDOWNS
for ($i=0;$i<$max['consumable_type'];$i++){
$query="INSERT INTO glpi_dropdown_consumable_type VALUES ('','type de consommable $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['consumable_type'];$i++){
$query="INSERT INTO glpi_dropdown_cartridge_type VALUES ('','type de cartouche $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['contact_type'];$i++){
$query="INSERT INTO glpi_dropdown_contact_type VALUES ('','type de contact $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['contact_type'];$i++){
$query="INSERT INTO glpi_dropdown_contract_type VALUES ('','type de contract $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['domain'];$i++){
$query="INSERT INTO glpi_dropdown_domain VALUES ('','domain $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['enttype'];$i++){
$query="INSERT INTO glpi_dropdown_enttype VALUES ('','type d\'entreprise $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['firmware'];$i++){
$query="INSERT INTO glpi_dropdown_firmware VALUES ('','firmware $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['hdd_type'];$i++){
$query="INSERT INTO glpi_dropdown_hdd_type VALUES ('','type de disque dur $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['iface'];$i++){
$query="INSERT INTO glpi_dropdown_iface VALUES ('','type d\'interface $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['auto_update'];$i++){
$query="INSERT INTO glpi_dropdown_auto_update VALUES ('','mise a jour type $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['model'];$i++){
$query="INSERT INTO glpi_dropdown_model VALUES ('','Modele $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['model_printers'];$i++){
$query="INSERT INTO glpi_dropdown_model_printers VALUES ('','model imprimante $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['model_monitors'];$i++){
$query="INSERT INTO glpi_dropdown_model_monitors VALUES ('','model ecran $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['model_networking'];$i++){
$query="INSERT INTO glpi_dropdown_model_networking VALUES ('','model matos reseau $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['model_peripherals'];$i++){
$query="INSERT INTO glpi_dropdown_model_peripherals VALUES ('','model peripheriques $i')";
$db->query($query) or die("PB REQUETE ".$query);
}

for ($i=0;$i<$max['network'];$i++){
$query="INSERT INTO glpi_dropdown_network VALUES ('','network $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['os'];$i++){
$query="INSERT INTO glpi_dropdown_os VALUES ('','os $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['ram_type'];$i++){
$query="INSERT INTO glpi_dropdown_ram_type VALUES ('','type de RAM $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['rubdocs'];$i++){
$query="INSERT INTO glpi_dropdown_rubdocs VALUES ('','rubdocs $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['state'];$i++){
$query="INSERT INTO glpi_dropdown_state VALUES ('','state $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['tracking_category'];$i++){
$query="INSERT INTO glpi_dropdown_tracking_category VALUES ('','categorie $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['vlan'];$i++){
$query="INSERT INTO glpi_dropdown_vlan VALUES ('','VLAN $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['type_computers'];$i++){
$query="INSERT INTO glpi_type_computers VALUES ('','type ordinateur $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['type_printers'];$i++){
$query="INSERT INTO glpi_type_printers VALUES ('','type imprimante $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['type_monitors'];$i++){
$query="INSERT INTO glpi_type_monitors VALUES ('','type ecran $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['type_networking'];$i++){
$query="INSERT INTO glpi_type_networking VALUES ('','type matos reseau $i')";
$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['type_peripherals'];$i++){
$query="INSERT INTO glpi_type_peripherals VALUES ('','type peripheriques $i')";
$db->query($query) or die("PB REQUETE ".$query);
}

optimize_tables ();

for ($i=0;$i<max(1,pow($max['kbcategories'],1/3));$i++){
	$query="INSERT INTO glpi_dropdown_kbcategories VALUES ('','0','categorie $i','')";
	$db->query($query) or die("PB REQUETE ".$query);
	$newID=$db->insert_id();
	for ($j=0;$j<mt_rand(0,pow($max['kbcategories'],1/2));$j++){
		$query="INSERT INTO glpi_dropdown_kbcategories VALUES ('','$newID','s-categorie $j','')";
		$db->query($query) or die("PB REQUETE ".$query);
		$newID2=$db->insert_id();
		for ($k=0;$k<mt_rand(0,pow($max['kbcategories'],1/2));$k++){
			$query="INSERT INTO glpi_dropdown_kbcategories VALUES ('','$newID2','ss-categorie $k','')";
			$db->query($query) or die("PB REQUETE ".$query);
		}	
	}
}	
$query = "OPTIMIZE TABLE  glpi_dropdown_kbcategories;";
$db->query($query) or die("PB REQUETE ".$query);

regenerateTreeCompleteName("glpi_dropdown_kbcategories");

$max['kbcategories']=0;
$query="SELECT MAX(ID) FROM glpi_dropdown_kbcategories";
$result=$db->query($query) or die("PB REQUETE ".$query);
$max['kbcategories']=$db->result($result,0,0);

// LOCATIONS

for ($i=0;$i<pow($max['locations'],1/5);$i++){
	$query="INSERT INTO glpi_dropdown_locations VALUES ('','lieu $i','0','')";
	$db->query($query) or die("PB REQUETE ".$query);
	$newID=$db->insert_id();
	for ($j=0;$j<mt_rand(0,pow($max['locations'],1/4));$j++){
		$query="INSERT INTO glpi_dropdown_locations VALUES ('','s-lieu $j','$newID','')";
		$db->query($query) or die("PB REQUETE ".$query);
		$newID2=$db->insert_id();
		for ($k=0;$k<mt_rand(0,pow($max['locations'],1/4));$k++){
			$query="INSERT INTO glpi_dropdown_locations VALUES ('','ss-lieu $k','$newID2','')";
			$db->query($query) or die("PB REQUETE ".$query);
			$newID3=$db->insert_id();
			for ($l=0;$l<mt_rand(0,pow($max['locations'],1/4));$l++){
				$query="INSERT INTO glpi_dropdown_locations VALUES ('','sss-lieu $l','$newID3','')";
				$db->query($query) or die("PB REQUETE ".$query);
				$newID4=$db->insert_id();
				for ($m=0;$m<mt_rand(0,pow($max['locations'],1/4));$m++){
					$query="INSERT INTO glpi_dropdown_locations VALUES ('','ssss-lieu $m','$newID4','')";
					$db->query($query) or die("PB REQUETE ".$query);
				}	
			}	
		}	
	}
}	

$query = "OPTIMIZE TABLE  glpi_dropdown_locations;";
$db->query($query) or die("PB REQUETE ".$query);

regenerateTreeCompleteName("glpi_dropdown_locations");

$max['locations']=0;
$query="SELECT MAX(ID) FROM glpi_dropdown_locations";
$result=$db->query($query) or die("PB REQUETE ".$query);
$max['locations']=$db->result($result,0,0);


// glpi_users
for ($i=0;$i<$max['users_sadmin'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','sadmin$i','',MD5('sadmin$i'),'sadmin$i@tutu.com','tel $i','super-admin','','no','".mt_rand(1,$max['locations'])."','no','french')";
	$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['users_admin'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','admin$i','',MD5('admin$i'),'admin$i@tutu.com','tel $i','admin','','no','".mt_rand(1,$max['locations'])."','no','french')";
	$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['users_normal'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','normal$i','',MD5('normal$i'),'normal$i@tutu.com','tel $i','normal','','no','".mt_rand(1,$max['locations'])."','no','french')";
	$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['users_postonly'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','postonly$i','',MD5('postonly$i'),'postonly$i@tutu.com','tel $i','post-only','','no','".mt_rand(1,$max['locations'])."','no','french')";
	$db->query($query) or die("PB REQUETE ".$query);
}

// glpi_enterprises
for ($i=0;$i<$max['enterprises'];$i++){
	$query="INSERT INTO glpi_enterprises VALUES ('','enterprise $i','".mt_rand(1,$max['enttype'])."','address $i','http://ent$i.com/','phone $i','comment $i','N','fax $i','info@ent$i.com','notes enterprises $i')";
	$db->query($query) or die("PB REQUETE ".$query);
}

// Ajout contacts

for ($i=0;$i<$max['contacts'];$i++){
	$query="INSERT INTO glpi_contacts VALUES ('','contact $i','phone $i','phone2 $i','fax $i','email $i','".mt_rand(1,$max['contact_type'])."','comment $i','N','notes contacts $i')";
//	echo $query."<br>";
	$db->query($query) or die("PB REQUETE ".$query);
	$conID=$db->insert_id();
	
	// Link with enterprise
	$query="INSERT INTO glpi_contact_enterprise VALUES ('','".mt_rand(1,$max['enterprises'])."','$conID')";
//	echo $query."<br>";
	$db->query($query) or die("PB REQUETE ".$query);

}


// TYPE DE CARTOUCHES
for ($i=0;$i<$max['type_of_cartridges'];$i++){
	$query="INSERT INTO glpi_cartridges_type VALUES ('','cartridge type $i','ref $i','".mt_rand(1,$max['locations'])."','".mt_rand(1,$max['cartridge_type'])."','".mt_rand(1,$max['enterprises'])."','".mt_rand(1,$max['users_sadmin']+$max['users_admin'])."','N','comments $i','".mt_rand(0,10)."','notes cartridges type $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$cartID=$db->insert_id();

	// AJOUT INFOCOMS
	$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
	$query="INSERT INTO glpi_infocoms VALUES ('','$cartID','".CARTRIDGE_TYPE."','$date','$date','".mt_rand(12,36)."','infowar cartype $cartID','".mt_rand(1,$max['enterprises'])."','commande cartype $cartID','BL cartype $cartID','immo cartype $cartID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments cartype $cartID','facture cartype $cartID')";
	$db->query($query) or die("PB REQUETE ".$query);


	// Ajout cartouche en stock
	for ($j=0;$j<mt_rand(0,$max['cartridges_stock']);$j++){
	$query="INSERT INTO glpi_cartridges VALUES('','$cartID','0',NOW(),NULL,NULL,'0')";
	$db->query($query) or die("PB REQUETE ".$query);
	$ID=$db->insert_id();
	
	// AJOUT INFOCOMS
	$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
	$query="INSERT INTO glpi_infocoms VALUES ('','$ID','".CARTRIDGE_ITEM_TYPE."','$date','$date','".mt_rand(12,36)."','infowar cart $ID','".mt_rand(1,$max['enterprises'])."','commande cart $ID','BL cart $ID','immo cart $ID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments cart $ID','facture cart $ID')";
	$db->query($query) or die("PB REQUETE ".$query);

	}
}

// Assoc printer type to cartridge type
for ($i=0;$i<$max['type_printers'];$i++){
	$query="INSERT INTO glpi_cartridges_assoc VALUES ('','".mt_rand(1,$max['type_of_cartridges'])."','$i')";
	$db->query($query) or die("PB REQUETE ".$query);
}


// Networking
$query="SELECT * from glpi_dropdown_locations order by completename;";
$result=$db->query($query) or die("PB REQUETE ".$query);
$i=0;
$net_loc=array();	

while ($data=$db->fetch_array($result)){
	// insert networking
	$techID=mt_rand(1,$max['users_sadmin']+$max['users_admin']);
	$domainID=mt_rand(1,$max['domain']);
	$networkID=mt_rand(1,$max['network']);
	$query="INSERT INTO glpi_networking VALUES ('','networking $i','ram $i','serial $i','serial2 $i','contact $i','num $i','$techID',NOW(),'comment $i','".$data['ID']."','$domainID','$networkID','".mt_rand(1,$max['model_networking'])."','".mt_rand(1,$max['type_networking'])."','".mt_rand(1,$max['firmware'])."','".mt_rand(1,$max['enterprises'])."','N','0','','MAC networking $i','IP networking $i','notes networking $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$netwID=$db->insert_id();
	$net_loc[$data['ID']]=$netwID;

	// ITEMS IN SPECIAL STATES
	if (mt_rand(0,100)<$percent['state']){
		$query="INSERT INTO glpi_state_item VALUES ('','".NETWORKING_TYPE."','$netwID','".mt_rand(1,$max['state'])."','0')";
		$db->query($query) or die("PB REQUETE ".$query);
	}
	
	// AJOUT INFOCOMS
	$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
	$query="INSERT INTO glpi_infocoms VALUES ('','$netwID','".NETWORKING_TYPE."','$date','$date','".mt_rand(12,36)."','infowar netw $netwID','".mt_rand(1,$max['enterprises'])."','commande netw $netwID','BL netw $netwID','immo netw $netwID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments netw $netwID','facture netw $netwID')";
	$db->query($query) or die("PB REQUETE ".$query);
	
	// Link with father 
	if ($data['parentID']>0){
		//insert netpoint
		$query="INSERT INTO glpi_dropdown_netpoint VALUES ('','".$data['ID']."','netpoint networking $i')";
		$db->query($query) or die("PB REQUETE ".$query);
		$netpointID=$db->insert_id();
	
		$iface=mt_rand(1,$max['iface']);

		// Add networking ports 
		$query="INSERT INTO glpi_networking_ports VALUES ('','$netwID','".NETWORKING_TYPE."','".mt_rand(0,100)."','link port to netw ".$net_loc[$data['parentID']]."','IP networking $netwID','MAC networking $netwID','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port1ID=$db->insert_id();
		$query="INSERT INTO glpi_networking_ports VALUES ('','".$net_loc[$data['parentID']]."','".NETWORKING_TYPE."','".mt_rand(0,100)."','link port to netw $netwID','IP networking ".$net_loc[$data['parentID']]."','MAC networking ".$net_loc[$data['parentID']]."','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port2ID=$db->insert_id();
	
		$query="INSERT INTO glpi_networking_wire VALUES ('','$port1ID','$port2ID')";
		$db->query($query) or die("PB REQUETE ".$query);	
	}
	
	// Ajout imprimantes reseaux : 1 par loc + connexion à un matos reseau + ajout de cartouches
	//insert netpoint
	$query="INSERT INTO glpi_dropdown_netpoint VALUES ('','".$data['ID']."','netpoint networking $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$netpointID=$db->insert_id();

	// Add trackings
	add_tracking(NETWORKING_TYPE,$netwID);

	
	$typeID=mt_rand(1,$max['type_printers']);
	$modelID=mt_rand(1,$max['model_printers']);
	$query="INSERT INTO glpi_printers VALUES ('','printer of loc ".$data['ID']."',NOW(),'contact ".$data['ID']."','num ".$data['ID']."','$techID','serial ".$data['ID']."','serial2 ".$data['ID']."','0','0','1','comments $i','".mt_rand(0,64)."','".$data['ID']."','$domainID','$networkID','$modelID','$typeID','".mt_rand(1,$max['enterprises'])."','N','0','','0','notes printers ".$data['ID']."')";
	$db->query($query) or die("PB REQUETE ".$query);
	$printID=$db->insert_id();

	// Add trackings
	add_tracking(PRINTER_TYPE,$printID);

	// AJOUT INFOCOMS
	$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
	$query="INSERT INTO glpi_infocoms VALUES ('','$printID','".PRINTER_TYPE."','$date','$date','".mt_rand(12,36)."','infowar print $printID','".mt_rand(1,$max['enterprises'])."','commande print $printID','BL print $printID','immo print $printID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments print $printID','facture print $printID')";
	$db->query($query) or die("PB REQUETE ".$query);


	// ITEMS IN SPECIAL STATES
	if (mt_rand(0,100)<$percent['state']){
		$query="INSERT INTO glpi_state_item VALUES ('','".PRINTER_TYPE."','$printID','".mt_rand(1,$max['state'])."','0')";
		$db->query($query) or die("PB REQUETE ".$query);
	}
	
		
	$iface=mt_rand(1,$max['iface']);

	// Add networking ports 
	$query="INSERT INTO glpi_networking_ports VALUES ('','$netwID','".NETWORKING_TYPE."','".mt_rand(0,100)."','link port to printer $printID','IP printer $printID','MAC printer $printID','$iface','$netpointID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$port1ID=$db->insert_id();
	$query="INSERT INTO glpi_networking_ports VALUES ('','$printID','".PRINTER_TYPE."','".mt_rand(0,100)."','link port to netw $netwID','IP networking $netwID','MAC networking $netwID','$iface','$netpointID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$port2ID=$db->insert_id();
	$query="INSERT INTO glpi_networking_wire VALUES ('','$port1ID','$port2ID')";
	$db->query($query) or die("PB REQUETE ".$query);	

	// Add Cartouches 
	// Get compatible cartridge
	$query="SELECT FK_glpi_cartridges_type FROM glpi_cartridges_assoc WHERE FK_glpi_dropdown_model_printers='$typeID'";
	$result2=$db->query($query) or die("PB REQUETE ".$query);
	if ($db->numrows($result)>0){
		$ctypeID=$db->result($result2,0,0);
		$printed=0;
		$oldnb=mt_rand(1,$max['cartridges_by_printer']);
		$date1=strtotime(mt_rand(1995,2004)."-".mt_rand(1,12)."-".mt_rand(1,28));
		$date2=mktime();
		$inter=round(($date2-$date1)/$oldnb);
	
		// Add old cartridges
		for ($j=0;$j<$oldnb;$j++){
			$printed+=mt_rand(0,5000);
			$query="INSERT INTO glpi_cartridges VALUES ('','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date1+$j*$inter)."','".date("Y-m-d",$date1+($j+1)*$inter)."','$printed')";
			$db->query($query) or die("PB REQUETE ".$query);	
		}
		// Add current cartridges
		$query="INSERT INTO glpi_cartridges VALUES ('','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date2)."',NULL,'0')";	
		$db->query($query) or die("PB REQUETE ".$query);	
	}

$i++;
}	
unset($net_loc);


//////////// INVENTORY

// DEVICE

for ($i=0;$i<$max['device'];$i++){

$query="INSERT INTO glpi_device_case VALUES ('','case $i','".mt_rand(0,2)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,10)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_control VALUES ('','control $i','".mt_rand(0,3)."','N','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,1000)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_drive VALUES ('','drive $i','N','".mt_rand(0,60)."','".mt_rand(0,2)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,100)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_gfxcard VALUES ('','gfxcard $i','".mt_rand(0,128)."','".mt_rand(0,3)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,128)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_hdd VALUES ('','hdd $i','".mt_rand(0,10500)."','".mt_rand(1,$max['hdd_type'])."','".mt_rand(0,8000)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,128)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_iface VALUES ('','iface $i','".mt_rand(0,1000)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,10)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_moboard VALUES ('','moboard $i','chipset ".mt_rand(0,1000)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,10)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_pci VALUES ('','pci $i','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,10)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_power VALUES ('','power $i','".mt_rand(0,500)."W','Y','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,10)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_processor VALUES ('','processor $i','".mt_rand(1000,3000)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(1000,3000)."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_ram VALUES ('','ram $i','".mt_rand(0,400)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,10)."','".mt_rand(1,$max['ram_type'])."')";
$db->query($query) or die("PB REQUETE ".$query);
$query="INSERT INTO glpi_device_sndcard VALUES ('','sndcard $i','type ".mt_rand(0,100)."','comment $i','".mt_rand(1,$max['enterprises'])."','".mt_rand(0,100)."')";
$db->query($query) or die("PB REQUETE ".$query);
}



// glpi_computers
for ($i=0;$i<$max['computers'];$i++){
	$loc=mt_rand(1,$max['locations']);
	$techID=mt_rand(1,$max['users_sadmin']+$max['users_admin']);
	$domainID=mt_rand(1,$max['domain']);
	$networkID=mt_rand(1,$max['network']);
	$query="INSERT INTO glpi_computers VALUES ('','computers $i','serial $i','serial2 $i','contact $i','num $i','$techID','',NOW(),'".mt_rand(1,$max['os'])."','".mt_rand(1,$max['auto_update'])."','".$loc."','$domainID','$networkID','".mt_rand(1,$max['model'])."','".mt_rand(1,$max['type_computers'])."','0','','".mt_rand(1,$max['enterprises'])."','N','note computer $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$compID=$db->insert_id();

	// Add trackings
	add_tracking(COMPUTER_TYPE,$compID);
	// Add reservation
	add_reservation(COMPUTER_TYPE,$compID);

	// AJOUT INFOCOMS
	$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
	$query="INSERT INTO glpi_infocoms VALUES ('','$compID','".COMPUTER_TYPE."','$date','$date','".mt_rand(12,36)."','infowar comp $compID','".mt_rand(1,$max['enterprises'])."','commande comp $compID','BL comp $compID','immo comp $compID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments comp $compID','facture comp $compID')";
	$db->query($query) or die("PB REQUETE ".$query);

	// ADD DEVICE
	$query="INSERT INTO glpi_computer_device VALUES ('','','".MOBOARD_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','".mt_rand(0,3000)."','".PROCESSOR_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','".mt_rand(0,1024)."','".RAM_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','".mt_rand(0,100000)."','".HDD_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','MAC $compID','".NETWORK_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".DRIVE_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".CONTROL_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".GFX_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".SND_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".PCI_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".CASE_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$query="INSERT INTO glpi_computer_device VALUES ('','','".POWER_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
	$db->query($query) or die("PB REQUETE ".$query);
	
	
	// ITEMS IN SPECIAL STATES
	if (mt_rand(0,100)<$percent['state']){
		$query="INSERT INTO glpi_state_item VALUES ('','".COMPUTER_TYPE."','$compID','".mt_rand(1,$max['state'])."','0')";
		$db->query($query) or die("PB REQUETE ".$query);
	}
	
		
	//insert netpoint
	$query="INSERT INTO glpi_dropdown_netpoint VALUES ('','$loc','netpoint computer $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$netpointID=$db->insert_id();

	// Get networking element
	$query="SELECT ID FROM glpi_networking WHERE location='$loc'";
	$result=$db->query($query) or die("PB REQUETE ".$query);
	if ($db->numrows($result)>0){
		$netwID=$db->result($result,0,0);

		$iface=mt_rand(1,$max['iface']);

		// Add networking ports 
		$query="INSERT INTO glpi_networking_ports VALUES ('','$compID','".COMPUTER_TYPE."','".mt_rand(0,100)."','link port to netw $netwID','IP networking $compID','MAC networking $compID','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port1ID=$db->insert_id();
		$query="INSERT INTO glpi_networking_ports VALUES ('','$netwID','".NETWORKING_TYPE."','".mt_rand(0,100)."','link port to computer $compID','IP networking $netwID','MAC networking $netwID','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port2ID=$db->insert_id();
	
		$query="INSERT INTO glpi_networking_wire VALUES ('','$port1ID','$port2ID')";
		$db->query($query) or die("PB REQUETE ".$query);	
	}

	// Ajout d'un ecran sur l'ordi
	
	$query="INSERT INTO glpi_monitors VALUES ('','monitor $i',NOW(),'contact $i','num $i','$techID','comment $i','serial $i','serial2 $i','".mt_rand(14,22)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','$loc','".mt_rand(1,$max['model_monitors'])."','".mt_rand(1,$max['type_monitors'])."','".mt_rand(1,$max['enterprises'])."','0','N','0','','notes monitor $i')";
	$db->query($query) or die("PB REQUETE ".$query);	
	$monID=$db->insert_id();
	
	// Add trackings
	add_tracking(MONITOR_TYPE,$monID);

	$query="INSERT INTO glpi_connect_wire VALUES ('','$monID','$compID','".MONITOR_TYPE."')";
	$db->query($query) or die("PB REQUETE ".$query);	
	
	// Ajout des periphs externes en connection directe
	while (mt_rand(0,100)<$percent['peripherals']){
		$query="INSERT INTO glpi_peripherals VALUES ('','periph of comp $i',NOW(),'contact $i','num $i','$techID','comments $i','serial $i','serial2 $i','$loc','".mt_rand(1,$max['model_peripherals'])."','".mt_rand(1,$max['type_peripherals'])."','brand $i','".mt_rand(1,$max['enterprises'])."','0','N','0','','notes peripherals $i')";
		$db->query($query) or die("PB REQUETE ".$query);
		$periphID=$db->insert_id();
	
		// Add trackings
		add_tracking(PERIPHERAL_TYPE,$periphID);

		// Add connection
		$query="INSERT INTO glpi_connect_wire VALUES ('','$periphID','$compID','".PERIPHERAL_TYPE."')";
		$db->query($query) or die("PB REQUETE ".$query);	
	}

	// AJOUT INFOCOMS
	// Use date of the computer
	//	$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
	$query="INSERT INTO glpi_infocoms VALUES ('','$monID','".MONITOR_TYPE."','$date','$date','".mt_rand(12,36)."','infowar mon $monID','".mt_rand(1,$max['enterprises'])."','commande mon $monID','BL mon $monID','immo mon $monID','".mt_rand(0,800)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments mon $monID','facture mon $monID')";
	$db->query($query) or die("PB REQUETE ".$query);


	// ITEMS IN SPECIAL STATES
	if (mt_rand(0,100)<$percent['state']){
		$query="INSERT INTO glpi_state_item VALUES ('','".MONITOR_TYPE."','$monID','".mt_rand(1,$max['state'])."','0')";
		$db->query($query) or die("PB REQUETE ".$query);
	}
	
	// Ajout d'une imprimante connection directe pour X% des computers + ajout de cartouches
	if (mt_rand(0,100)<=$percent['printer']){
		// Add printer 
		$typeID=mt_rand(1,$max['type_printers']);
		$modelID=mt_rand(1,$max['model_printers']);
		$query="INSERT INTO glpi_printers VALUES ('','printer of comp $i',NOW(),'contact $i','num $i','$techID','serial $i','serial2 $i','0','0','1','comments $i','".mt_rand(0,64)."','$loc','$domainID','$networkID','$modelID','$typeID','".mt_rand(1,$max['enterprises'])."','N','0','','0','notes printers $i')";
		$db->query($query) or die("PB REQUETE ".$query);
		$printID=$db->insert_id();

		// Add trackings
		add_tracking(PRINTER_TYPE,$printID);

		// Add connection
		$query="INSERT INTO glpi_connect_wire VALUES ('','$printID','$compID','".PRINTER_TYPE."')";
		$db->query($query) or die("PB REQUETE ".$query);	


		// AJOUT INFOCOMS
		// use computer date
		//$date=mt_rand(1995,2005)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES ('','$printID','".PRINTER_TYPE."','$date','$date','".mt_rand(12,36)."','infowar print $printID','".mt_rand(1,$max['enterprises'])."','commande print $printID','BL print $printID','immo print $printID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comments print $printID','facture print $printID')";
		$db->query($query) or die("PB REQUETE ".$query);

		// ITEMS IN SPECIAL STATES
		if (mt_rand(0,100)<$percent['state']){
			$query="INSERT INTO glpi_state_item VALUES ('','".PRINTER_TYPE."','$printID','".mt_rand(1,$max['state'])."','0')";
			$db->query($query) or die("PB REQUETE ".$query);
		}
		
			
	
		// Add Cartouches 
		// Get compatible cartridge
		$query="SELECT FK_glpi_cartridges_type FROM glpi_cartridges_assoc WHERE FK_glpi_dropdown_model_printers='$typeID'";
		$result=$db->query($query) or die("PB REQUETE ".$query);
		if ($db->numrows($result)>0){
			$ctypeID=$db->result($result,0,0);
			$printed=0;
			$oldnb=mt_rand(1,$max['cartridges_by_printer']);
			$date1=strtotime(mt_rand(1995,2004)."-".mt_rand(1,12)."-".mt_rand(1,28));
			$date2=mktime();
			$inter=round(($date2-$date1)/$oldnb);
			// Add old cartridges
			for ($j=0;$j<$oldnb;$j++){
				$printed+=mt_rand(0,5000);
			
				$query="INSERT INTO glpi_cartridges VALUES ('','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date1+$j*$inter)."','".date("Y-m-d",$date1+($j+1)*$inter)."','$printed')";
				$db->query($query) or die("PB REQUETE ".$query);	
			}
			// Add current cartridges
			$query="INSERT INTO glpi_cartridges VALUES ('','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date2)."',NULL,'0')";	
			$db->query($query) or die("PB REQUETE ".$query);	
		}
	}

}

// Ajout logiciels + licences associés a divers PCs
for ($i=0;$i<$max['software'];$i++){
	$loc=mt_rand(1,$max['locations']);
	$techID=mt_rand(1,$max['users_sadmin']+$max['users_admin']);
	$os=mt_rand(1,$max['os']);
	$query="INSERT INTO glpi_software VALUES ('','software $i','version $i','comments $i','$loc','$techID','$os','N','-1','".mt_rand(1,$max['enterprises'])."','N','0','',NOW(),'notes software $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$softID=$db->insert_id();

	// Add trackings
	add_tracking(SOFTWARE_TYPE,$softID);

	// Add licenses depending of license type
	$val=mt_rand(0,100);
	// Free software
	if ($val<$percent['free_software']){
		$query="INSERT INTO glpi_licenses VALUES ('','$softID','free',NULL,'N','0','Y');";
		$db->query($query) or die("PB REQUETE ".$query);
		$licID=$db->insert_id();
		$val2=mt_rand(0,$max['free_licenses_per_software']);
		for ($j=0;$j<$val2;$j++){
			$query="INSERT INTO glpi_inst_software VALUES ('','".mt_rand(1,$max['computers'])."','$licID')";
			$db->query($query) or die("PB REQUETE ".$query);
		}
	} // Global software
	else if ($val<$percent['global_software']+$percent['free_software']){
		$query="INSERT INTO glpi_licenses VALUES ('','$softID','global',NULL,'N','0','Y');";
		$db->query($query) or die("PB REQUETE ".$query);
		$licID=$db->insert_id();
		$val2=mt_rand(0,$max['global_licenses_per_software']);
		for ($j=0;$j<$val2;$j++){
			$query="INSERT INTO glpi_inst_software VALUES ('','".mt_rand(1,$max['computers'])."','$licID')";
			$db->query($query) or die("PB REQUETE ".$query);
		}
	} // Normal software
	else {
		$val2=mt_rand(0,$max['normal_licenses_per_software']);
		for ($j=0;$j<$val2;$j++){
			$query="INSERT INTO glpi_licenses VALUES ('','$softID','serial $j',NULL,'N','0','Y');";
			$db->query($query) or die("PB REQUETE ".$query);
			$licID=$db->insert_id();
			$query="INSERT INTO glpi_inst_software VALUES ('','".mt_rand(1,$max['computers'])."','$licID')";
			$db->query($query) or die("PB REQUETE ".$query);
		}
		// Add more licenses
		$val2=mt_rand(0,$max['more_licenses']);
		for ($j=0;$j<$val2;$j++){
			$query="INSERT INTO glpi_licenses VALUES ('','$softID','more serial $j',NULL,'N','0','Y');";
			$db->query($query) or die("PB REQUETE ".$query);
		}
	}
}


// Add global peripherals
for ($i=0;$i<$max['global_peripherals'];$i++){
	$techID=mt_rand(1,$max['users_sadmin']+$max['users_admin']);
	$query="INSERT INTO glpi_peripherals VALUES ('','periph $i',NOW(),'contact $i','num $i','$techID','comments $i','serial $i','serial2 $i','0','".mt_rand(1,$max['model_peripherals'])."','".mt_rand(1,$max['type_peripherals'])."','brand $i','".mt_rand(1,$max['enterprises'])."','1','N','0','','notes peripherals $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$periphID=$db->insert_id();


	// Add trackings
	add_tracking(PERIPHERAL_TYPE,$periphID);
	// Add reservation
	add_reservation(PERIPHERAL_TYPE,$periphID);

	// Add connections
	$val=mt_rand(1,$max['connect_for_peripherals']);
	for ($j=1;$j<$val;$j++){
		$query="INSERT INTO glpi_connect_wire VALUES ('','$periphID','".($j)."','".PERIPHERAL_TYPE."')";
		$db->query($query) or die("PB REQUETE ".$query);	
	}
}

	
	// Ajout element dans la FAQ
	
	// Ajout d'entrées dans le planning
	
	// Ajout consommables en stock + utilisé

	// Ajout de documents + link aux elements
	
	// Ajout contrats 

	// Assoc des VLAN par regroupement de lieux

optimize_tables();	
	
?>
