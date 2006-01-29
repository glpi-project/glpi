<?
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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

$max['locations']=100;
$max['kbcategories']=5;

// DROPDOWNS
$max['consumable_type']=1;
$max['cartridge_type']=1;
$max['contact_type']=1;
$max['contract_type']=1;
$max['domain']=5;
$max['enttype']=1;
$max['firmware']=5;
$max['hdd_type']=1;
$max['iface']=5;
$max['model']=5;
$max['network']=5;
$max['os']=5;
$max['ram_type']=5;
$max['rubdocs']=5;
$max['state']=5;
$max['tracking_category']=5;
$max['vlan']=5;
$max['type_computers']=3;
$max['type_printers']=3;
$max['type_monitors']=3;
$max['type_peripherals']=5;
$max['type_networking']=3;
$max['model_printers']=10;
$max['model_monitors']=10;
$max['model_peripherals']=10;
$max['model_networking']=10;
$max['netpoint']=1000;
$max['auto_update']=3;

// USERS
$max['users_sadmin']=1;
$max['users_admin']=5;
$max['users_normal']=5;
$max['users_postonly']=10;
$max['enterprises']=5;
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
$percent['state']=70;
// LICENSES
$percent['free_software']=20;
$percent['global_software']=20;
$percent['normal_software']=60;
$max['normal_licenses_per_software']=10;
$max['free_licenses_per_software']=10;
$max['global_licenses_per_software']=10;
$max['more_licenses']=1;
//PERIPHERALS
$max['connect_for_peripherals']=2;
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
$IP=array(10,0,0,1);
$MAC=array(8,0,20,30,40,50);
$NETPOINT=array(0,0,0,0);
$net_port=array();
$vlan_loc=array();

function getNextNETPOINT(){
global $NETPOINT;
$type=array("V","D","I");
$NETPOINT[3]=($NETPOINT[3]+1)%3;
if ($NETPOINT[3]==1) {
	$NETPOINT[2]=max(1,($NETPOINT[2]+1)%255);
	if ($NETPOINT[2]==0) {
		$NETPOINT[1]=max(1,($NETPOINT[1]+1)%255);
		if ($NETPOINT[1]==0) {
			$NETPOINT[0]=max(1,($NETPOINT[0]+1)%255);
		}
	}
}
return $type[$NETPOINT[3]]."/".$NETPOINT[0]."/".$NETPOINT[1]."/".$NETPOINT[2];
}


function getNextIP(){
global $IP;

$IP[3]=max(1,($IP[3]+1)%255);
if ($IP[3]==1) {
	$IP[2]=max(1,($IP[2]+1)%255);
	if ($IP[2]==0) {
		$IP[1]=max(1,($IP[1]+1)%255);
		if ($IP[1]==0) {
			$IP[0]=max(1,($IP[0]+1)%255);
		}
	}
}
return $IP[0].".".$IP[1].".".$IP[2].".".$IP[3];
}

function getNextMAC(){
global $MAC;

$MAC[5]=($MAC[5]+1)%256;
if ($MAC[5]==0) {
	$MAC[4]=($MAC[4]+1)%256;
	if ($MAC[4]==0) {
		$MAC[3]=($MAC[3]+1)%256;
		if ($MAC[3]==0) {
			$MAC[2]=($MAC[2]+1)%256;
			if ($MAC[2]==0) {
				$MAC[1]=($MAC[1]+1)%256;
				if ($MAC[1]==0) {
					$MAC[0]=($MAC[0]+1)%256;
				}
			}
		}
	}
}

return dechex($MAC[0]).":".dechex($MAC[1]).":".dechex($MAC[2]).":".dechex($MAC[3]).":".dechex($MAC[4]).":".dechex($MAC[5]);
}

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
$items=array("CD","CD-RW","DVD-R","DVD+R","DVD-RW","DVD+RW","ramette papier","disquette","ZIP");
for ($i=0;$i<$max['consumable_type'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de consommable $i";
	$query="INSERT INTO glpi_dropdown_consumable_type VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Laser","Jet-Encre","Encre Solide");
for ($i=0;$i<$max['cartridge_type'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de cartouche $i";
	$query="INSERT INTO glpi_dropdown_cartridge_type VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Technicien","Commercial","Technico-Commercial","President","Secretaire");
for ($i=0;$i<$max['contact_type'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de contact $i";
	$query="INSERT INTO glpi_dropdown_contact_type VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

/*for ($i=0;$i<$max['contract_type'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de consommable $i";
	$query="INSERT INTO glpi_dropdown_contract_type VALUES ('','type de contract $i')";
	$db->query($query) or die("PB REQUETE ".$query);
}
*/

$items=array("SP2MI","CAMPUS","IUT86","PRESIDENCE","CEAT");
for ($i=0;$i<$max['domain'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="domain $i";
	$query="INSERT INTO glpi_dropdown_domain VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Fournisseur","Transporteur","SSII","Revendeur","Assembleur","SSLL","Financeur","Assureur");
for ($i=0;$i<$max['enttype'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type entreprise $i";
	$query="INSERT INTO glpi_dropdown_enttype VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("H.07.02","I.07.56","P51","P52","1.60","4.06","43-4071299","1.0.14","3.0.1","rev 1.0","rev 1.1","rev 1.2","rev 1.2.1","rev 2.0","rev 3.0");
for ($i=0;$i<$max['firmware'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="firmware $i";
	$query="INSERT INTO glpi_dropdown_firmware VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("USB","Firewire");
for ($i=0;$i<$max['hdd_type'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de disque dur $i";
	$query="INSERT INTO glpi_dropdown_hdd_type VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("100 Base TX","100 Base T4","10 base T","1000 Base SX","1000 Base LX","1000 Base T","ATM","802.3 10 Base 2","IEEE 803.3 10 Base 5");
for ($i=0;$i<$max['iface'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type carte reseau $i";
	$query="INSERT INTO glpi_dropdown_iface VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Non","Oui - generique","Oui - specifique entite");
for ($i=0;$i<$max['auto_update'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de mise à jour $i";
	$query="INSERT INTO glpi_dropdown_auto_update VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Assemble","Latitude C600","Latitude C700","VAIO FX601","VAIO FX905P","VAIO TR5MP","L5000C","A600K","PowerBook G4");
for ($i=0;$i<$max['model'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="Modele $i";
	$query="INSERT INTO glpi_dropdown_model VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("4200 DTN","4200 DN","4200 N","8400 ADP","7300 ADP","5550 DN","PIXMA iP8500","Stylus Color 3000","DeskJet 5950");
for ($i=0;$i<$max['model_printers'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="modele imprimante $i";
	$query="INSERT INTO glpi_dropdown_model_printers VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("LS902UTG","MA203DT","P97F+SB","G220F","10-30-75","PLE438S-B0S","PLE481S-W","L1740BQ","L1920P","SDM-X73H");
for ($i=0;$i<$max['model_monitors'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="modele moniteur $i";
	$query="INSERT INTO glpi_dropdown_model_monitors VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("HP 4108GL","HP 2524","HP 5308","7600","Catalyst 4500","Catalyst 2950","Catalyst 3750","Catalyst 6500");
for ($i=0;$i<$max['model_networking'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="modele materiel reseau $i";
	$query="INSERT INTO glpi_dropdown_model_networking VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("DCS-2100+","DCS-2100G","KD-P35B","Optical 5000","Cordless","ASR 600","ASR 375","CS21","MX5020","VS4121","T3030","T6060");
for ($i=0;$i<$max['model_peripherals'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="modele peripherique $i";
	$query="INSERT INTO glpi_dropdown_model_peripherals VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("SIC","LMS","LMP","LEA","SP2MI","STIC","MATH","ENS-MECA","POUBELLE","WIFI");
for ($i=0;$i<$max['network'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="reseau $i";
	$query="INSERT INTO glpi_dropdown_network VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Windows XP Pro SP2","Linux (Debian)","Mac OS X","Linux (Mandriva 2006)","Linux (Redhat)","Windows 98","Windows 2000","Windows XP Pro SP1","LINUX (Suse)","Linux (Mandriva 10.2)");
for ($i=0;$i<$max['os'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="os $i";
	$query="INSERT INTO glpi_dropdown_os VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("DDR2");
for ($i=0;$i<$max['ram_type'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de ram $i";
	$query="INSERT INTO glpi_dropdown_ram_type VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Documentation","Facture","Bon Livraison","Bon commande","Capture Ecran","Dossier Technique");
for ($i=0;$i<$max['rubdocs'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="rubrique $i";
	$query="INSERT INTO glpi_dropdown_rubdocs VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Reparation","En stock","En fonction","Retour SAV","En attente");
for ($i=0;$i<$max['state'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="Etat $i";
	$query="INSERT INTO glpi_dropdown_state VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Maintenance Materielle","Maintenance Logicielle","Accueil personne","Probleme Impression","Probleme Compte","Probleme Reseau","Probleme Mail","Commande Livre","Hebergement Web");
for ($i=0;$i<$max['tracking_category'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="Categorie $i";
	$query="INSERT INTO glpi_dropdown_tracking_category VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("SIC","LMS","LMP","LEA","SP2MI","STIC","MATH","ENS-MECA","POUBELLE","WIFI");
for ($i=0;$i<$max['vlan'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="VLAN $i";
	$query="INSERT INTO glpi_dropdown_vlan VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Portable","Desktop","Tour");
for ($i=0;$i<$max['type_computers'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type ordinateur $i";
	$query="INSERT INTO glpi_type_computers VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Laser A4","Jet-Encre","Laser A3","Encre Solide A4","Encre Solide A3");
for ($i=0;$i<$max['type_printers'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type imprimante $i";
	$query="INSERT INTO glpi_type_printers VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("TFT 17","TFT 19","TFT 21","CRT 17","CRT 19","CRT 21","CRT 15");
for ($i=0;$i<$max['type_monitors'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type ecran $i";
	$query="INSERT INTO glpi_type_monitors VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Switch","Routeur","Hub","Borne Wifi");
for ($i=0;$i<$max['type_networking'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de materiel reseau $i";
	$query="INSERT INTO glpi_type_networking VALUES ('','$val')";
	$db->query($query) or die("PB REQUETE ".$query);
}

$items=array("Clavier","Souris","Webcam","Enceintes","Scanner","Clef USB");
for ($i=0;$i<$max['type_peripherals'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="type de peripheriques $i";
	$query="INSERT INTO glpi_type_peripherals VALUES ('','$val')";
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
	$query="INSERT INTO glpi_users VALUES ('','sadmin$i','',MD5('sadmin$i'),'sadmin$i@tutu.com','tel $i','super-admin','','no','".mt_rand(1,$max['locations'])."','no','french','1')";
	$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['users_admin'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','admin$i','',MD5('admin$i'),'admin$i@tutu.com','tel $i','admin','','no','".mt_rand(1,$max['locations'])."','no','french','1')";
	$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['users_normal'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','normal$i','',MD5('normal$i'),'normal$i@tutu.com','tel $i','normal','','no','".mt_rand(1,$max['locations'])."','no','french','1')";
	$db->query($query) or die("PB REQUETE ".$query);
}
for ($i=0;$i<$max['users_postonly'];$i++){
	$query="INSERT INTO glpi_users VALUES ('','postonly$i','',MD5('postonly$i'),'postonly$i@tutu.com','tel $i','post-only','','no','".mt_rand(1,$max['locations'])."','no','french','1')";
	$db->query($query) or die("PB REQUETE ".$query);
}

// glpi_enterprises
$items=array("DELL","IBM","ACER","Microsoft","Epson","Xerox","Hewlett Packard","Nikon","Targus","LG","Samsung","Lexmark");
for ($i=0;$i<$max['enterprises'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="enterprise_$i";

	$query="INSERT INTO glpi_enterprises VALUES ('','$val','".mt_rand(1,$max['enttype'])."','address $i','http://$val.com/','phone $i','comment $i','N','fax $i','info@ent$i.com','notes enterprises $i')";
	$db->query($query) or die("PB REQUETE ".$query);
}

// Ajout contacts
$items=array("Jean Dupont","John Smith","Louis Durand","Pierre Martin","Auguste Dubois","Jean Dufour","Albert Dupin","Julien Duval","Guillaume Petit","Bruno Grange","Maurice Bernard","Francois Bonnet","Laurent Richard","Richard Leroy","Henri Dumont","Clement Fontaine");
for ($i=0;$i<$max['contacts'];$i++){
	if (isset($items[$i])) $val=$items[$i];
	else $val="contact $i";
	$query="INSERT INTO glpi_contacts VALUES ('','$val','phone $i','phone2 $i','fax $i','email $i','".mt_rand(1,$max['contact_type'])."','comment $i','N','notes contact $i')";
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
	$vlanID=mt_rand(1,$max["vlan"]);
	$vlan_loc[$data['ID']]=$vlanID;
	$netname="networking $i";
	$query="INSERT INTO glpi_networking VALUES ('','$netname','".mt_rand(32,256)."','serial $i','serial2 $i','contact $i','num $i','$techID',NOW(),'comment $i','".$data['ID']."','$domainID','$networkID','".mt_rand(1,$max['model_networking'])."','".mt_rand(1,$max['type_networking'])."','".mt_rand(1,$max['firmware'])."','".mt_rand(1,$max['enterprises'])."','N','0','','".getNextMAC()."','".getNextIP()."','notes networking $i')";
	$db->query($query) or die("PB REQUETE ".$query);
	$netwID=$db->insert_id();
	$net_loc[$data['ID']]=$netwID;
	$net_port[NETWORKING_TYPE][$netwID]=1;
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
		$query="INSERT INTO glpi_dropdown_netpoint VALUES ('','".$data['ID']."','".getNextNETPOINT()."')";
		$db->query($query) or die("PB REQUETE ".$query);
		$netpointID=$db->insert_id();
	
		$iface=mt_rand(1,$max['iface']);

		// Add networking ports 
		$newIP=getNextIP();
		$newMAC=getNextMAC();
		$query="INSERT INTO glpi_networking_ports VALUES ('','$netwID','".NETWORKING_TYPE."','".$net_port[NETWORKING_TYPE][$netwID]++."','link port to netw ".$net_loc[$data['parentID']]."','$newIP','$newMAC','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port1ID=$db->insert_id();
		$query="INSERT INTO glpi_networking_ports VALUES ('','".$net_loc[$data['parentID']]."','".NETWORKING_TYPE."','".$net_port[NETWORKING_TYPE][$net_loc[$data['parentID']]]++."','link port to netw $netwID','$newIP','$newMAC','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port2ID=$db->insert_id();
	
		$query="INSERT INTO glpi_networking_wire VALUES ('','$port1ID','$port2ID')";
		$db->query($query) or die("PB REQUETE ".$query);	
		// Add Vlan
		$query="INSERT INTO glpi_networking_vlan VALUES ('','$port1ID','$vlanID')";
		$db->query($query) or die("PB REQUETE ".$query);	
		$query="INSERT INTO glpi_networking_vlan VALUES ('','$port2ID','$vlanID')";
		$db->query($query) or die("PB REQUETE ".$query);	
	}
	
	// Ajout imprimantes reseaux : 1 par loc + connexion à un matos reseau + ajout de cartouches
	//insert netpoint
	$query="INSERT INTO glpi_dropdown_netpoint VALUES ('','".$data['ID']."','".getNextNETPOINT()."')";
	$db->query($query) or die("PB REQUETE ".$query);
	$netpointID=$db->insert_id();

	// Add trackings
	add_tracking(NETWORKING_TYPE,$netwID);

	
	$typeID=mt_rand(1,$max['type_printers']);
	$modelID=mt_rand(1,$max['model_printers']);
	$query="INSERT INTO glpi_printers VALUES ('','printer of loc ".$data['ID']."',NOW(),'contact ".$data['ID']."','num ".$data['ID']."','$techID','serial ".$data['ID']."','serial2 ".$data['ID']."','0','0','1','comments $i','".mt_rand(0,64)."','".$data['ID']."','$domainID','$networkID','$modelID','$typeID','".mt_rand(1,$max['enterprises'])."','N','0','','0','notes printers ".$data['ID']."')";
	$db->query($query) or die("PB REQUETE ".$query);
	$printID=$db->insert_id();
	$net_port[PRINTER_TYPE][$printID]=0;

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
	$newIP=getNextIP();
	$newMAC=getNextMAC();
	$query="INSERT INTO glpi_networking_ports VALUES ('','$netwID','".NETWORKING_TYPE."','".$net_port[NETWORKING_TYPE][$netwID]++."','link port to printer of loc ".$data["ID"]."','$newIP','$newMAC','$iface','$netpointID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$port1ID=$db->insert_id();
	$query="INSERT INTO glpi_networking_ports VALUES ('','$printID','".PRINTER_TYPE."','".$net_port[PRINTER_TYPE][$printID]++."','link port to netw $netwID','$newIP','$newMAC','$iface','$netpointID')";
	$db->query($query) or die("PB REQUETE ".$query);
	$port2ID=$db->insert_id();
	$query="INSERT INTO glpi_networking_wire VALUES ('','$port1ID','$port2ID')";
	$db->query($query) or die("PB REQUETE ".$query);	
	// Add Vlan
	$query="INSERT INTO glpi_networking_vlan VALUES ('','$port1ID','$vlanID')";
	$db->query($query) or die("PB REQUETE ".$query);	
	$query="INSERT INTO glpi_networking_vlan VALUES ('','$port2ID','$vlanID')";
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
	$net_port[COMPUTER_TYPE][$compID]=0;

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
	$query="INSERT INTO glpi_computer_device VALUES ('','".getNextMAC()."','".NETWORK_DEVICE."','".mt_rand(1,$max['device'])."','$compID')";
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
	$query="INSERT INTO glpi_dropdown_netpoint VALUES ('','$loc','".getNextNETPOINT()."')";
	$db->query($query) or die("PB REQUETE ".$query);
	$netpointID=$db->insert_id();

	// Get networking element
	$query="SELECT ID FROM glpi_networking WHERE location='$loc'";
	$result=$db->query($query) or die("PB REQUETE ".$query);
	if ($db->numrows($result)>0){
		$netwID=$db->result($result,0,0);

		$iface=mt_rand(1,$max['iface']);

		// Add networking ports 
		$newIP=getNextIP();
		$newMAC=getNextMAC();
		$query="INSERT INTO glpi_networking_ports VALUES ('','$compID','".COMPUTER_TYPE."','".$net_port[COMPUTER_TYPE][$compID]++."','link port to netw $netwID','$newIP','$newMAC','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port1ID=$db->insert_id();
		$query="INSERT INTO glpi_networking_ports VALUES ('','$netwID','".NETWORKING_TYPE."','".$net_port[NETWORKING_TYPE][$netwID]++."','link port to computer $i','$newIP','$newMAC','$iface','$netpointID')";
		$db->query($query) or die("PB REQUETE ".$query);
		$port2ID=$db->insert_id();
	
		$query="INSERT INTO glpi_networking_wire VALUES ('','$port1ID','$port2ID')";
		$db->query($query) or die("PB REQUETE ".$query);	
		// Add Vlan
		$query="INSERT INTO glpi_networking_vlan VALUES ('','$port1ID','".$vlan_loc[$loc]."')";
		$db->query($query) or die("PB REQUETE ".$query);	
		$query="INSERT INTO glpi_networking_vlan VALUES ('','$port2ID','".$vlan_loc[$loc]."')";
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
		$query="INSERT INTO glpi_peripherals VALUES ('','periph of comp $i',NOW(),'contact $i','num $i','$techID','comments $i','serial $i','serial2 $i','$loc','".mt_rand(1,$max['type_peripherals'])."','".mt_rand(1,$max['model_peripherals'])."','brand $i','".mt_rand(1,$max['enterprises'])."','0','N','0','','notes peripherals $i')";
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
	$query="INSERT INTO glpi_peripherals VALUES ('','periph $i',NOW(),'contact $i','num $i','$techID','comments $i','serial $i','serial2 $i','0','".mt_rand(1,$max['type_peripherals'])."','".mt_rand(1,$max['model_peripherals'])."','brand $i','".mt_rand(1,$max['enterprises'])."','1','N','0','','notes peripherals $i')";
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
