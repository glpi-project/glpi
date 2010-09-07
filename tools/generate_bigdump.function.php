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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


// BIG DUMP GENERATION FOR THE 0.6 VERSION

$IP=array(10,0,0,1);
$MAC=array(8,0,20,30,40,50);
$NETPOINT=array(0,0,0,0);

/** Generate bigdump : Get next netpoint name
*/
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

/** Generate bigdump : Get next IP address
*/
function getNextIP(){
	global $IP;

	$IP[3]=max(1,($IP[3]+1)%254);
	if ($IP[3]==1) {
		$IP[2]=max(1,($IP[2]+1)%255);
		if ($IP[2]==0) {
			$IP[1]=max(1,($IP[1]+1)%255);
			if ($IP[1]==0) {
				$IP[0]=max(1,($IP[0]+1)%255);
			}
		}
	}
	return array( "ip"=>$IP[0].".".$IP[1].".".$IP[2].".".$IP[3],
		"gateway"=>$IP[0].".".$IP[1].".".$IP[2].".254",
		"subnet"=>$IP[0].".".$IP[1].".".$IP[2].".0",
		"netwmask"=>"255.255.255.0");
}
/** Generate bigdump :  Get next MAC address
*/
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

/**  Generate bigdump : make an item reservable
* @param $type item type
* @param $ID item ID
* @param $ID_entity item entity ID
*/
function addReservation($type,$ID,$ID_entity){
	global $percent,$DB,$FIRST,$LAST;

	$current_year=date("Y");

	if (mt_rand(0,100)<$percent['reservationitems']){
		$query="INSERT INTO glpi_reservationitems VALUES (NULL,'$type','$ID_entity','0','$ID','comment $ID $type','1')";
		$DB->query($query) or die("PB REQUETE ".$query);
      $tID=$DB->insert_id();

		$date1=strtotime('-2 week'); // reservations since 2 weeks
		$date2=$date1;
      $i=0;

		while (mt_rand(0,100)<$percent['reservations']){
         $date1=$date2+3600*mt_rand(0,10); // 10 hours between each resa max
			$date2=$date1+3600*mt_rand(1,5); // A reservation from 1 to 5 hours

			$query="INSERT INTO glpi_reservations VALUES (NULL,'$tID','".date("Y-m-d H:i:s",$date1)."','".date("Y-m-d H:i:s",$date2)."','".mt_rand($FIRST['users_normal'],$LAST['users_postonly'])."','comments $i ".getRandomString(15)."');";
			$DB->query($query) or die("PB REQUETE ".$query);
			$DB->insert_id();
			$i++;
		}

	}

}

/**  Generate bigdump : add documents to an item
* @param $type item type
* @param $ID item ID
*/
function addDocuments($type,$ID){
	global $DOC_PER_ITEM,$DB,$MAX,$FIRST,$LAST;
	$nb=mt_rand(0,$DOC_PER_ITEM);
	$docs=array();
	for ($i=0;$i<$nb;$i++)
		$docs[]=mt_rand($FIRST["document"],$LAST["document"]);
	$docs=array_unique($docs);
	foreach ($docs as $val){
		$query="INSERT INTO glpi_documents_items VALUES (NULL,'$val','$ID','$type')";
		$DB->query($query);
	}
}
/**  Generate bigdump : add contracts to an item
* @param $type item type
* @param $ID item ID
*/
function addContracts($type,$ID){
	global $CONTRACT_PER_ITEM,$DB,$MAX,$FIRST,$LAST;
	$nb=mt_rand(0,$CONTRACT_PER_ITEM);
	$con=array();
	for ($i=0;$i<$nb;$i++)
		$con[]=mt_rand($FIRST["contract"],$LAST["contract"]);
	$con=array_unique($con);
	foreach ($con as $val){
		$query="INSERT INTO glpi_contracts_items VALUES (NULL,'$val','$ID','$type')";
		$DB->query($query) ;
	}
}
/**  Generate bigdump : add tickets to an item
* @param $type item type
* @param $ID item ID
* @param $ID_entity entity ID
*/
function addTracking($type,$ID,$ID_entity){
	global $percent,$DB,$MAX,$FIRST,$LAST;

	$current_year=date("Y");

	$tco=0;

	while (mt_rand(0,100)<$percent['tracking_on_item']){
		// ticket closed ?
		$status="closed";
      $closedate="";
      $solvedate="";
		if (mt_rand(0,100)<$percent['closed_tracking']){
			$opendate=strtotime(mt_rand(2005,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28)." ".mt_rand(0,23).":".mt_rand(0,59).":".mt_rand(0,59));
			$rtype=mt_rand(0,100);
			if ($rtype<20) {
   			$status="solved";
         } else {
            $status="closed";
         }
		} else {
			$opendate=strtotime("$current_year-".mt_rand(1,12)."-".mt_rand(1,28)." ".mt_rand(0,23).":".mt_rand(0,59).":".mt_rand(0,59));
			$rtype=mt_rand(0,100);
			if ($rtype<20)
				$status="new";
			else if ($rtype<40)
				$status="waiting";
			else if ($rtype<80){
				$status="plan";
				$date3=$opendate+mt_rand(10800,7776000); // + entre 3 heures et 3 mois
				$date4=$date3+10800; // + 3 heures
			} else {
            $status="assign";
         }
		}
		// Author
		$users[0]=mt_rand($FIRST['users_normal'],$LAST['users_postonly']);
		// Assign user
		$users[1]=0;
		if ($status!="new"){
			$users[1]=mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
		}
		$enterprise=0;
		if (mt_rand(0,100)<20)
			$enterprise=mt_rand($FIRST["enterprises"],$LAST['enterprises']);

		$actiontime=mt_rand(0,3)*HOUR_TIMESTAMP+mt_rand(0,60)*MINUTE_TIMESTAMP;
		$firstactiontime=mt_rand(0,10)*HOUR_TIMESTAMP+mt_rand(0,60)*MINUTE_TIMESTAMP;
      $solvetime=0;
      $closetime=0;
      if ($status=="closed" || $status=="solved") {
         $solvetime=$firstactiontime+mt_rand(0,10)*DAY_TIMESTAMP+mt_rand(0,10)*HOUR_TIMESTAMP+mt_rand(0,60)*MINUTE_TIMESTAMP;
         $solvedate=$opendate+$solvetime;
         $closedate=$opendate+$solvetime;
         if ($status=="closed") {
            $closetime=$solvetime+mt_rand(0,5)*DAY_TIMESTAMP+mt_rand(0,10)*HOUR_TIMESTAMP+mt_rand(0,60)*MINUTE_TIMESTAMP;
            $closedate=$opendate+$closetime;
         } 
      }
      $updatedate=$opendate+max($firstactiontime,$solvetime,$closetime);
		$hour_cost=100;
		$tco+=$actiontime*$hour_cost;

      $closedatetoadd='NULL';
      if (!empty($closedate)) {
         $closedatetoadd="'".date("Y-m-d H:i:s",intval($closedate))."'";
      }

      $solvedatetoadd='NULL';
      if (!empty($solvedate)) {
         $solvedatetoadd="'".date("Y-m-d H:i:s",intval($solvedate))."'";
      }

		$query="INSERT INTO glpi_tickets VALUES (NULL,'$ID_entity','Title ".getRandomString(20)."','".date("Y-m-d H:i:s",intval($opendate))."',$closedatetoadd,$solvedatetoadd,'".date("Y-m-d H:i:s",intval($updatedate))."','$status','".$users[0]."','".$users[0]."','".mt_rand($FIRST["groups"],$LAST['groups'])."','".mt_rand(0,6)."','".$users[1]."','$enterprise','".mt_rand($FIRST["groups"],$LAST['groups'])."','$type','$ID','tracking ".getRandomString(15)."','".mt_rand(1,5)."','".mt_rand(1,5)."','".mt_rand(1,5)."','','0','".mt_rand(0,$MAX['tracking_category'])."','$hour_cost','0','0','".mt_rand(0,$MAX['ticketsolutions'])."','Solution ".getRandomString(20)."','accepted',0,0,NULL,NULL,0,0,$closetime,$solvetime,$firstactiontime,'$actiontime')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$tID=$DB->insert_id();
		// Add followups
		$i=0;
		$fID=0;
      $first=true;
      $date=0;
		while (mt_rand(0,100)<$percent['followups']){
         if ($first) {
            $date=$opendate+$firstactiontime;
            $first=false;
            
         } else {
            $date+=mt_rand(3600,7776000);
         }
			$query="INSERT INTO glpi_ticketfollowups VALUES (NULL,'$tID','".date("Y-m-d H:i:s",$date)."','".$users[1]."','followup $i ".getRandomString(15)."','0','".mt_rand(0,3)."');";
			$DB->query($query) or die("PB REQUETE ".$query);
			$fID=$DB->insert_id();
			$i++;
		}
		while (mt_rand(0,100)<$percent['tasks']){
         if ($first) {
            $date=$opendate+$firstactiontime;
            $first=false;
         } else {
            $date+=mt_rand(3600,7776000);
         }
         $actiontime=(mt_rand(0,3)+mt_rand(0,100)/100);
			$query="INSERT INTO glpi_tickettasks VALUES (NULL,'$tID','".mt_rand($FIRST['taskcategory'],$LAST['taskcategory'])."','".date("Y-m-d H:i:s",$date)."','".$users[1]."','task $i ".getRandomString(15)."','".mt_rand(0,1)."','$actiontime');";
			$DB->query($query) or die("PB REQUETE ".$query);
			$fID=$DB->insert_id();
			$i++;
		}
      if ($status=="plan"&&$fID){
         $query="INSERT INTO glpi_ticketplannings VALUES (NULL,'$fID','".$users[1]."','".date("Y-m-d H:i:s",$date3)."','".date("Y-m-d H:i:s",$date4)."','1');";
         $DB->query($query) or die("PB REQUETE ".$query);
      }

	}
	$query="UPDATE ".getTableForItemType($type)." SET ticket_tco='$tco'	WHERE id='".$ID."';";
	$DB->query($query) or die("PB REQUETE ".$query);

}

/** Generate bigdump : generate global dropdowns
*/
function generateGlobalDropdowns(){
	global $MAX,$DB;

//	$FIRST["kbcategories"]=getMaxItem("glpi_knowbaseitemcategories")+1;
	for ($i=0;$i<max(1,pow($MAX['kbcategories'],1/3));$i++){
		$query="INSERT INTO glpi_knowbaseitemcategories VALUES (NULL,'0','categorie $i','','comment categorie $i','1')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$newID=$DB->insert_id();
		for ($j=0;$j<mt_rand(0,pow($MAX['kbcategories'],1/2));$j++){
			$query="INSERT INTO glpi_knowbaseitemcategories VALUES (NULL,'$newID','s-categorie $j','','comment s-categorie $j','2')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$newID2=$DB->insert_id();
			for ($k=0;$k<mt_rand(0,pow($MAX['kbcategories'],1/2));$k++){
				$query="INSERT INTO glpi_knowbaseitemcategories VALUES (NULL,'$newID2','ss-categorie $k','','comment ss-categorie $k','3')";
				$DB->query($query) or die("PB REQUETE ".$query);
			}
		}
	}

	$query = "OPTIMIZE TABLE  glpi_knowbaseitemcategories;";
	$DB->query($query) or die("PB REQUETE ".$query);


	// glpi_knowbaseitems
	$MAX["kbcategories"]=getMaxItem("glpi_knowbaseitemcategories");

	$items=array("CD","CD-RW","DVD-R","DVD+R","DVD-RW","DVD+RW","ramette papier","disquette","ZIP");
	for ($i=0;$i<$MAX['consumable_type'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de consommable $i";
		$query="INSERT INTO glpi_consumableitemtypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array();
	for ($i=0;$i<$MAX['phone_power'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="power $i";
		$query="INSERT INTO glpi_phonepowersupplies VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Grand","Moyen","Micro","1U","5U");
	for ($i=0;$i<$MAX['case_type'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="power $i";
		$query="INSERT INTO glpi_devicecasetypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}


	$items=array("Laser","Jet-Encre","Encre Solide");
	for ($i=0;$i<$MAX['cartridge_type'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de cartouche $i";
		$query="INSERT INTO glpi_cartridgeitemtypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array();
	for ($i=0;$i<$MAX['ticketsolutions'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de solution $i";
		$query="INSERT INTO glpi_ticketsolutiontypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("Technicien","Commercial","Technico-Commercial","President","Secretaire");
	for ($i=0;$i<$MAX['contact_type'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de contact $i";
		$query="INSERT INTO glpi_contacttypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("SP2MI","CAMPUS","IUT86","PRESIDENCE","CEAT");
	for ($i=0;$i<$MAX['domain'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="domain $i";
		$query="INSERT INTO glpi_domains VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Fournisseur","Transporteur","SSII","Revendeur","Assembleur","SSLL","Financeur","Assureur");
	for ($i=0;$i<$MAX['enttype'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type entreprise $i";
		$query="INSERT INTO glpi_suppliertypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("H.07.02","I.07.56","P51","P52","1.60","4.06","43-4071299","1.0.14","3.0.1","rev 1.0","rev 1.1","rev 1.2","rev 1.2.1","rev 2.0","rev 3.0");
	for ($i=0;$i<$MAX['firmware'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="firmware $i";
		$query="INSERT INTO glpi_networkequipmentfirmwares VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Firewire");
	for ($i=0;$i<$MAX['interface'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de disque dur $i";
		$query="INSERT INTO glpi_interfacetypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("100 Base TX","100 Base T4","10 base T","1000 Base SX","1000 Base LX","1000 Base T","ATM","802.3 10 Base 2","IEEE 803.3 10 Base 5");
	for ($i=0;$i<$MAX['iface'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type carte reseau $i";
		$query="INSERT INTO glpi_networkinterfaces VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Non","Oui - generique","Oui - specifique entite");
	for ($i=0;$i<$MAX['auto_update'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de mise a jour $i";
		$query="INSERT INTO glpi_autoupdatesystems VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Assemble","Latitude C600","Latitude C700","VAIO FX601","VAIO FX905P","VAIO TR5MP","L5000C","A600K","PowerBook G4");
	for ($i=0;$i<$MAX['model'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="Modele $i";
		$query="INSERT INTO glpi_computermodels VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("4200 DTN","4200 DN","4200 N","8400 ADP","7300 ADP","5550 DN","PIXMA iP8500","Stylus Color 3000","DeskJet 5950");
	for ($i=0;$i<$MAX['model_printers'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="modele imprimante $i";
		$query="INSERT INTO glpi_printermodels VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("LS902UTG","MA203DT","P97F+SB","G220F","10-30-75","PLE438S-B0S","PLE481S-W","L1740BQ","L1920P","SDM-X73H");
	for ($i=0;$i<$MAX['model_monitors'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="modele moniteur $i";
		$query="INSERT INTO glpi_monitormodels VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("HP 4108GL","HP 2524","HP 5308","7600","Catalyst 4500","Catalyst 2950","Catalyst 3750","Catalyst 6500");
	for ($i=0;$i<$MAX['model_networking'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="modele materiel reseau $i";
		$query="INSERT INTO glpi_networkequipmentmodels VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("DCS-2100+","DCS-2100G","KD-P35B","Optical 5000","Cordless","ASR 600","ASR 375","CS21","MX5020","VS4121","T3030","T6060");
	for ($i=0;$i<$MAX['model_peripherals'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="modele peripherique $i";
		$query="INSERT INTO glpi_peripheralmodels VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array();
	for ($i=0;$i<$MAX['model_phones'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="modele phone $i";
		$query="INSERT INTO glpi_phonemodels VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("SIC","LMS","LMP","LEA","SP2MI","STIC","MATH","ENS-MECA","POUBELLE","WIFI");
	for ($i=0;$i<$MAX['network'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="reseau $i";
		$query="INSERT INTO glpi_networks VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Windows XP Pro SP2","Linux (Debian)","Mac OS X","Linux (Mandriva 2006)","Linux (Redhat)","Windows 98","Windows 2000","Windows XP Pro SP1","LINUX (Suse)","Linux (Mandriva 10.2)");
	for ($i=0;$i<$MAX['os'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="os $i";
		$query="INSERT INTO glpi_operatingsystems VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("XP Pro","XP Home","10.0","10.1","10.2","2006","Sarge");
	for ($i=0;$i<$MAX['os_version'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="osversion $i";
		$query="INSERT INTO glpi_operatingsystemversions VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Service Pack 1","Service Pack 2","Service Pack 3","Service Pack 4");
	for ($i=0;$i<$MAX['os_sp'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="ossp $i";
		$query="INSERT INTO glpi_operatingsystemservicepacks VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("DDR2");
	for ($i=0;$i<$MAX['ram_type'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de ram $i";
		$query="INSERT INTO glpi_devicememorytypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Documentation","Facture","Bon Livraison","Bon commande","Capture Ecran","Dossier Technique");
	for ($i=0;$i<$MAX['rubdocs'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="category $i";
		$query="INSERT INTO glpi_documentcategories VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	for ($i=0;$i<$MAX['softwarecategory'];$i++){
		$val="categorie $i";
		$query="INSERT INTO glpi_softwarecategories VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	for ($i=0;$i<$MAX['licensetype'];$i++){
		$val="type $i";
		$query="INSERT INTO glpi_softwarelicensetypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Reparation","En stock","En fonction","Retour SAV","En attente");
	for ($i=0;$i<$MAX['state'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="Etat $i";
		$query="INSERT INTO glpi_states VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("SIC","LMS","LMP","LEA","SP2MI","STIC","MATH","ENS-MECA","POUBELLE","WIFI");
	for ($i=0;$i<$MAX['vlan'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="VLAN $i";
		$query="INSERT INTO glpi_vlans VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Portable","Desktop","Tour");
	for ($i=0;$i<$MAX['type_computers'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type ordinateur $i";
		$query="INSERT INTO glpi_computertypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Laser A4","Jet-Encre","Laser A3","Encre Solide A4","Encre Solide A3");
	for ($i=0;$i<$MAX['type_printers'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type imprimante $i";
		$query="INSERT INTO glpi_printertypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("TFT 17","TFT 19","TFT 21","CRT 17","CRT 19","CRT 21","CRT 15");
	for ($i=0;$i<$MAX['type_monitors'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type ecran $i";
		$query="INSERT INTO glpi_monitortypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Switch","Routeur","Hub","Borne Wifi");
	for ($i=0;$i<$MAX['type_networking'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de materiel reseau $i";
		$query="INSERT INTO glpi_networkequipmenttypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Clavier","Souris","Webcam","Enceintes","Scanner","Clef USB");
	for ($i=0;$i<$MAX['type_peripherals'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de peripheriques $i";
		$query="INSERT INTO glpi_peripheraltypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array();
	for ($i=0;$i<$MAX['type_phones'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="type de phone $i";
		$query="INSERT INTO glpi_phonetypes VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("DELL","HP","IIYAMA","CANON","EPSON","LEXMARK","ASUS","MSI");
	for ($i=0;$i<$MAX['manufacturer'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="manufacturer $i";
		$query="INSERT INTO glpi_manufacturers VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Ingénieur","Stagiaire","Secrétaire");
	for ($i=0;$i<$MAX['user_type'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="user type $i";
		$query="INSERT INTO glpi_usercategories VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

	$items=array("Président","Agent Comptable","Directeur");
	for ($i=0;$i<$MAX['user_title'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="user type $i";
		$query="INSERT INTO glpi_usertitles VALUES (NULL,'$val','comment $val')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}

         // GLobal ticket categories : also specific ones by entity
	for ($i=0;$i<max(1,pow($MAX['tracking_category'],1/3));$i++){
		$query="INSERT INTO glpi_ticketcategories VALUES (NULL,'0','1','0','categorie $i','','comment categorie $i','1','0','0','0','','',1)";
		$DB->query($query) or die("PB REQUETE ".$query);
		$newID=$DB->insert_id();
		for ($j=0;$j<mt_rand(0,pow($MAX['tracking_category'],1/2));$j++){
			$query="INSERT INTO glpi_ticketcategories VALUES (NULL,'0','1','$newID','s-categorie $j','','comment s-categorie $j','2','0','0','0','','',1)";
			$DB->query($query) or die("PB REQUETE ".$query);
			$newID2=$DB->insert_id();
			for ($k=0;$k<mt_rand(0,pow($MAX['tracking_category'],1/2));$k++){
				$query="INSERT INTO glpi_ticketcategories VALUES (NULL,'0','1','$newID2','ss-categorie $k','','comment ss-categorie $k','3','0','0','0','','',1)";
				$DB->query($query) or die("PB REQUETE ".$query);
			}
		}
	}

	$query = "OPTIMIZE TABLE  glpi_ticketcategories;";
	$DB->query($query) or die("PB REQUETE ".$query);

	regenerateTreeCompleteName("glpi_ticketcategories");

	$MAX['tracking_category']=0;
	$query="SELECT MAX(id) FROM glpi_ticketcategories";
	$result=$DB->query($query) or die("PB REQUETE ".$query);
	$MAX['tracking_category']=$DB->result($result,0,0) or die (" PB RESULT ".$query);


	// DEVICE
	$items=array("Textorm 6A19","ARIA","SLK3000B-EU","Sonata II","TA-212","TA-551","TA-581","TAC-T01","CS-512","Li PC-60891","STT-TJ02S");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="case $i";
		$query="INSERT INTO glpi_devicecases VALUES (NULL,'$val','".mt_rand(0,$MAX["case_type"])."','comment $i','".mt_rand(1,$MAX['manufacturer'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("Escalade 8006-2LP","Escalade 8506-4LP","2810SA","1210SA","DuoConnect","DU-420","DUB-A2","FastTrak SX4100B","DC-395U","TFU-H33PI");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="control $i";
		$query="INSERT INTO glpi_devicecontrols VALUES (NULL,'$val','0','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".mt_rand(1,$MAX['interface'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("DUW1616","DRW-1608P","DW1625","GSA-4160B","GSA-4165B","GSA-4167RBB","SHW-16H5S","SOHW-1673SX","DVR-110D","PX-716AL","PX-755A");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="drive $i";
		$query="INSERT INTO glpi_devicedrives VALUES (NULL,'$val','1','".mt_rand(0,60)."','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".mt_rand(1,$MAX['interface'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("A9250/TD","AX550/TD","Extreme N5900","V9520-X/TD","All-In-Wonder X800 GT","GV-NX66256D","GV-RX80256DE","Excalibur 9600XT","X1300 IceQ","WinFast PX6200 TD","Millenium 750","NX6600GT");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="gfxcard $i";
		$query="INSERT INTO glpi_devicegraphiccards VALUES (NULL,'$val','".mt_rand(1,$MAX['interface'])."','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".(256*mt_rand(0,8))."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("Deskstar 7K500","Deskstar T7K250","Atlas 15K II","DiamondMax Plus","SpinPoint P - SP2514N","Barracuda 7200.9","WD2500JS","WD1600JB","WD1200JD");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="hdd  $i";
		$query="INSERT INTO glpi_deviceharddrives VALUES (NULL,'$val','".mt_rand(0,10500)."','".mt_rand(1,$MAX['interface'])."','".(51200*mt_rand(0,10))."','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".mt_rand(0,300)."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("DFE-530TX","DFE-538TX","PWLA8492MF","PWLA8492MT","USBVPN1","GA311","FA511","TEG-PCBUSR","3C996-SX","3C996B-T","3C905C-TX-M");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="iface  $i";
		$query="INSERT INTO glpi_devicenetworkcards VALUES (NULL,'$val','".mt_rand(0,1000)."','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".getNextMAC()."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("AW8-MAX","NV8","AK86-L","P4V88","A8N-SLI","A8N-VM","K8V-MX","K8N4-E","P5LD2","GA-K8NE","GA-8I945P Pro","D945PBLL","SE7525GP2","865PE Neo3-F","K8N Neo4-F","Thunder i7520 (S5360G2NR)","Thunder K8SR - S2881UG2NR","Tiger K8QS Pro - S4882UG2NR","Tomcat i875PF (S5105G2NR)");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="moboard $i";
		$query="INSERT INTO glpi_devicemotherboards VALUES (NULL,'$val','chipset ".mt_rand(0,1000)."','comment $i','".mt_rand(1,$MAX['manufacturer'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("Instant TV Cardbus","WinTV Express","WinTV-NOVA-S-Plus","WinTV-NOVA-T","WinTV-PVR-150");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="pci $i";
		$query="INSERT INTO glpi_devicepcis VALUES (NULL,'$val','comment $i','".mt_rand(1,$MAX['manufacturer'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("DB-Killer PW335","DB-Killer PW385","NeoHE 380","NeoHE 450","Phantom 500-PEC","TruePower 2.0 550","Master RS-380","EG375AX-VE-G-SFMA","EG495AX");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="power $i";
		$query="INSERT INTO glpi_devicepowersupplies VALUES (NULL,'$val','".mt_rand(0,500)."W','1','comment $i','".mt_rand(1,$MAX['manufacturer'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("Athlon 64 FX-57","Athlon 64 FX-55","Sempron 2400+","Sempron 2600+","Celeron D 325","Celeron D 330J","Pentium 4 530J","Pentium 4 631","Pentium D 830","Pentium D 920");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="processor $i";
		$query="INSERT INTO glpi_deviceprocessors VALUES (NULL,'$val','".mt_rand(1000,3000)."','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".(1000+200*mt_rand(0,10))."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("CM2X256A-5400C4","CMX1024-3200C2","CMXP512-3200XL","TWIN2X1024-4300C3PRO","KTD-DM8400/1G","KTH8348/1G","KTD4400/256","D6464D30A","KTA-G5400/512","KVR667D2N5/1G","KVR133X64C3/256");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="ram $i";
		$query="INSERT INTO glpi_devicememories VALUES (NULL,'$val','".(100*mt_rand(0,10))."','comment $i','".mt_rand(1,$MAX['manufacturer'])."','".(1024*mt_rand(0,6))."','".mt_rand(1,$MAX['ram_type'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$items=array("DDTS-100","Audigy 2 ZS Platinum","Audigy SE","DJ Console Mk2","Gamesurround Muse Pocket USB","Phase 22","X-Fi Platinum","Live! 24-bit","X-Fi Elite Pro");
	for ($i=0;$i<$MAX['device'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="sndcard $i";
		$query="INSERT INTO glpi_devicesoundcards VALUES (NULL,'$val','type ".mt_rand(0,100)."','comment $i','".mt_rand(1,$MAX['manufacturer'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}


} // Fin generation global dropdowns

/** Generate bigdump : get max ID of a table
*@param $table table name
*/
function getMaxItem($table){
	global $DB;
	$query="SELECT MAX(id) FROM $table";
	$result=$DB->query($query) or die("PB REQUETE ".$query);
	return $DB->result($result,0,0);
}

/** Generate bigdump : generate items for an entity
*@param $ID_entity entity ID
*/
function generate_entity($ID_entity){

	global $MAX,$DB,$MAX_CONTRACT_TYPE,$percent,$FIRST,$LAST,$MAX_KBITEMS_BY_CAT,$MAX_DISK;
	$current_year=date("Y");


	// glpi_groups
	$FIRST["groups"]=getMaxItem("glpi_groups")+1;
	for ($i=0;$i<$MAX['groups'];$i++){
		$query="INSERT INTO glpi_groups VALUES (NULL,'$ID_entity',0,'group $i','comment group $i','0','','','',NOW())";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["groups"]=$DB->insert_id();


	// glpi_users
	$FIRST["users_sadmin"]=getMaxItem("glpi_users")+1;
	for ($i=0;$i<$MAX['users_sadmin'];$i++){
		$query="INSERT INTO glpi_users (name, password,email,phone,phone2,mobile,realname,firstname,comment,usertitles_id,usercategories_id) VALUES ('sadmin$i-$ID_entity',MD5('sadmin$i'),'sadmin$i-$ID_entity@tutu.com','tel $i','tel2 $i','mobile $i','sadmin$i name','sadmin$i firstname','comment $i','".mt_rand(0,$MAX['user_title'])."','".mt_rand(0,$MAX['user_type'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$user_id=$DB->insert_id();
		$query="INSERT INTO glpi_profiles_users VALUES (NULL,'$user_id','4','$ID_entity','1','0');";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_groups_users VALUES (NULL,'$user_id','".mt_rand($FIRST['groups'],$LAST['groups'])."',0);";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["users_sadmin"]=getMaxItem("glpi_users");
	$FIRST["users_admin"]=getMaxItem("glpi_users")+1;
	for ($i=0;$i<$MAX['users_admin'];$i++){
		$query="INSERT INTO glpi_users (name, password,email,phone,phone2,mobile,realname,firstname,comment,usertitles_id,usercategories_id) VALUES ('admin$i-$ID_entity',MD5('admin$i'),'admin$i-$ID_entity@tutu.com','tel $i','tel2 $i','mobile $i','admin$i name','admin$i firstname','comment $i','".mt_rand(0,$MAX['user_title'])."','".mt_rand(0,$MAX['user_type'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$user_id=$DB->insert_id();
		$query="INSERT INTO glpi_profiles_users VALUES (NULL,'$user_id','3','$ID_entity','1','0');";
		$DB->query($query) or die("PB REQUETE ".$query);
		$group=mt_rand($FIRST['groups'],$LAST['groups']);
		$query="INSERT INTO glpi_groups_users VALUES (NULL,'$user_id','$group',0);";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="UPDATE glpi_groups SET users_id='$user_id' WHERE users_id='$group'";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["users_admin"]=getMaxItem("glpi_users");
	$FIRST["users_normal"]=getMaxItem("glpi_users")+1;
	for ($i=0;$i<$MAX['users_normal'];$i++){
		$query="INSERT INTO glpi_users (name, password,email,phone,phone2,mobile,realname,firstname,comment,usertitles_id,usercategories_id) VALUES ('normal$i-$ID_entity',MD5('normal$i'),'normal$i-$ID_entity@tutu.com','tel $i','tel2 $i','mobile $i','normal$i name','normal$i firstname','comment $i','".mt_rand(0,$MAX['user_title'])."','".mt_rand(0,$MAX['user_type'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$user_id=$DB->insert_id();
		$LAST["users_normal"]=$user_id;
		$query="INSERT INTO glpi_profiles_users VALUES (NULL,'$user_id','2','$ID_entity','1','0');";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_groups_users VALUES (NULL,'$user_id','".mt_rand($FIRST['groups'],$LAST['groups'])."',0);";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["users_normal"]=getMaxItem("glpi_users");
	$FIRST["users_postonly"]=getMaxItem("glpi_users")+1;
	for ($i=0;$i<$MAX['users_postonly'];$i++){
		$query="INSERT INTO glpi_users (name, password,email,phone,phone2,mobile,realname,firstname,comment,usertitles_id,usercategories_id) VALUES ('postonly$i-$ID_entity',MD5('postonly$i'),'postonly$i-$ID_entity@tutu.com','tel $i','tel2 $i','mobile $i','postonly$i name','postonly$i firstname','comment $i','".mt_rand(0,$MAX['user_title'])."','".mt_rand(0,$MAX['user_type'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$user_id=$DB->insert_id();
		$LAST["users_postonly"]=$user_id;
		$query="INSERT INTO glpi_profiles_users VALUES (NULL,'$user_id','1','$ID_entity','1','0');";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_groups_users VALUES (NULL,'$user_id','".mt_rand($FIRST['groups'],$LAST['groups'])."',0);";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["users_postonly"]=getMaxItem("glpi_users");


	// LOCATIONS
	$added=0;
	$FIRST["locations"]=getMaxItem("glpi_locations")+1;
	for ($i=0;$i<pow($MAX['locations'],1/5)&&$added<$MAX['locations'];$i++){
		$added++;
		$query="INSERT INTO glpi_locations VALUES (NULL,'$ID_entity','1','lieu $i','0','','comment lieu $i','1','','','building X','room $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$newID=$DB->insert_id();
		for ($j=0;$j<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'];$j++){
			$added++;
			$query="INSERT INTO glpi_locations VALUES (NULL,'$ID_entity','1','s-lieu $j','$newID','','comment s-lieu $j','2','','','building X','room $i-$j')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$newID2=$DB->insert_id();
			for ($k=0;$k<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'];$k++){
				$added++;
				$query="INSERT INTO glpi_locations VALUES (NULL,'$ID_entity','1','ss-lieu $k','$newID2','','comment ss-lieu $k','3','','','building X','room $i-$j-$k')";
				$DB->query($query) or die("PB REQUETE ".$query);
				$newID3=$DB->insert_id();
				for ($l=0;$l<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'];$l++){
					$added++;
					$query="INSERT INTO glpi_locations VALUES (NULL,'$ID_entity','1','sss-lieu $l','$newID3','','comment sss-lieu $l','4','','','building X','room $i-$j-$k-$l')";
					$DB->query($query) or die("PB REQUETE ".$query);
					$newID4=$DB->insert_id();
					for ($m=0;$m<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'];$m++){
						$added++;
						$query="INSERT INTO glpi_locations VALUES (NULL,'$ID_entity','1','ssss-lieu $m','$newID4','','comment ssss-lieu $m',5,'','','building X','room $i-$j-$k-$l-$m')";
						$DB->query($query) or die("PB REQUETE ".$query);
					}
				}
			}
		}
	}

	$query = "OPTIMIZE TABLE  glpi_locations;";
	$DB->query($query) or die("PB REQUETE ".$query);

	regenerateTreeCompleteName("glpi_locations");
	$LAST["locations"]=getMaxItem("glpi_locations");


	// Task categories
	$added=0;
	$FIRST["taskcategory"]=getMaxItem("glpi_taskcategories")+1;
	for ($i=0;$i<pow($MAX['taskcategory'],1/5)&&$added<$MAX['taskcategory'];$i++){
		$added++;
		$query="INSERT INTO glpi_taskcategories VALUES (NULL,'$ID_entity','1','0','ent$ID_entity taskcategory $i','','comment lieu $i','1','','','1')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$newID=$DB->insert_id();
		for ($j=0;$j<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'];$j++){
			$added++;
			$query="INSERT INTO glpi_taskcategories VALUES (NULL,'$ID_entity','1','$newID','ent$ID_entity s-taskcategory $j','','comment s-lieu $j','2','','','1')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$newID2=$DB->insert_id();
		}
	}

	$query = "OPTIMIZE TABLE  glpi_taskcategories;";
	$DB->query($query) or die("PB REQUETE ".$query);

	regenerateTreeCompleteName("glpi_taskcategories");
	$LAST["taskcategory"]=getMaxItem("glpi_taskcategories");


   // Specific ticket categories
   $query="INSERT INTO glpi_ticketcategories VALUES (NULL,'$ID_entity','1','0','category for entity $ID_entity','','comment category for entity $ID_entity','1','0','".mt_rand($FIRST['users_sadmin'],$LAST['users_admin'])."','".mt_rand($FIRST['groups'],$LAST['groups'])."','','',1)";
	$DB->query($query) or die("PB REQUETE ".$query);
   $newID=$DB->insert_id();
	for ($i=0;$i<max(1,pow($MAX['tracking_category'],1/3));$i++){
		$query="INSERT INTO glpi_ticketcategories VALUES (NULL,'$ID_entity','1','$newID','categorie $i','','comment categorie $i','1','0','".mt_rand($FIRST['users_sadmin'],$LAST['users_admin'])."','".mt_rand($FIRST['groups'],$LAST['groups'])."','','',1)";
		$DB->query($query) or die("PB REQUETE ".$query);
		$newID=$DB->insert_id();
	}

	regenerateTreeCompleteName("glpi_ticketcategories");

	// glpi_knowbaseitems
	$MAX["kbcategories"]=getMaxItem("glpi_knowbaseitemcategories");

	// Add Specific questions
	$k=0;
	$FIRST["kbitems"]=getMaxItem("glpi_knowbaseitems")+1;
	for ($i=1;$i<=$MAX['kbcategories'];$i++){
		$nb=mt_rand(0,$MAX_KBITEMS_BY_CAT);
		for ($j=0;$j<$nb;$j++){
			$k++;
			$query="INSERT INTO glpi_knowbaseitems VALUES (NULL,'$ID_entity','0','$i','Entity $ID_entity Question $k','Reponse $k','".mt_rand(0,1)."','10','".mt_rand(0,1000)."',NOW(),NOW())";
			$DB->query($query) or die("PB REQUETE ".$query);
		}
	}
	// Add global questions
	for ($i=1;$i<=$MAX['kbcategories']/2;$i++){
		$nb=mt_rand(0,$MAX_KBITEMS_BY_CAT);
		for ($j=0;$j<$nb;$j++){
			$k++;
			$query="INSERT INTO glpi_knowbaseitems VALUES (NULL,'$ID_entity','1','$i','Entity $ID_entity Recursive Question $k','Reponse $k','".mt_rand(0,1)."','10','".mt_rand(0,1000)."',NOW(),NOW())";
			$DB->query($query) or die("PB REQUETE ".$query);
		}
	}
	$LAST["kbitems"]=getMaxItem("glpi_knowbaseitems");



	// Ajout documents  specific
	$FIRST["document"]=getMaxItem("glpi_documents")+1;
	for ($i=0;$i<$MAX['document'];$i++){
		$link="";
		if (mt_rand(0,100)<50) $link="http://linktodoc/doc$i";
		$query="INSERT INTO glpi_documents VALUES (NULL,'$ID_entity','0','document $i-$ID_entity','','','".mt_rand(1,$MAX['rubdocs'])."','',NOW(),'comment $i','0','$link','notes document $i','0','0','')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	// GLobal ones
	for ($i=0;$i<$MAX['document']/2;$i++){
		$link="";
		if (mt_rand(0,100)<50) $link="http://linktodoc/doc$i";
		$query="INSERT INTO glpi_documents VALUES (NULL,'$ID_entity','1','Recursive document $i-$ID_entity','','','".mt_rand(1,$MAX['rubdocs'])."','',NOW(),'comment $i','0','$link','notes document $i','0','0','')";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["document"]=getMaxItem("glpi_documents");


	// Ajout budgets  specific
	$FIRST["budget"]=getMaxItem("glpi_budgets")+1;
	for ($i=0;$i<$MAX['budget'];$i++){
		$date1=strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
		$date2=$date1+MONTH_TIMESTAMP*12*mt_rand(1,4); // + entre 1 et 4 ans

		$query="INSERT INTO glpi_budgets VALUES (NULL,'budget $i-$ID_entity','$ID_entity','0','comment $i-$ID_entity','0','".date("Y-m-d",intval($date1))."','".date("Y-m-d",intval($date2))."','0.0000','0','',NULL,'');";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	// GLobal ones
	for ($i=0;$i<$MAX['document']/2;$i++){
		$date1=strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
		$date2=$date1+MONTH_TIMESTAMP*12*mt_rand(1,4); // + entre 1 et 4 ans

		$query="INSERT INTO glpi_budgets VALUES (NULL,'Recursive budget $i-$ID_entity','$ID_entity','1','comment $i-$ID_entity','0','".date("Y-m-d",intval($date1))."','".date("Y-m-d",intval($date2))."','0.0000','0','',NULL,'');";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["document"]=getMaxItem("glpi_documents");

	// glpi_suppliers
	$items=array("DELL","IBM","ACER","Microsoft","Epson","Xerox","Hewlett Packard","Nikon","Targus","LG","Samsung","Lexmark");
	$FIRST["enterprises"]=getMaxItem("glpi_suppliers")+1;

	// Global ones
	for ($i=0;$i<$MAX['enterprises']/2;$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="Global enterprise_".$i."_ID_entity";

		$query="INSERT INTO glpi_suppliers VALUES (NULL,'$ID_entity','1','Recursive $val-$ID_entity','".mt_rand(1,$MAX['enttype'])."','address $i', 'postcode $i','town $i','state $i','country $i','http://www.$val.com/','phone $i','comment enterprises $i','0','fax $i','info@ent$i.com','notes enterprises $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$entID=$DB->insert_id();
		addDocuments('Supplier',$entID);
	}

	// Specific ones
	for ($i=0;$i<$MAX['enterprises'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="enterprise_".$i."_ID_entity";

		$query="INSERT INTO glpi_suppliers VALUES (NULL,'$ID_entity','0','$val-$ID_entity','".mt_rand(1,$MAX['enttype'])."','address $i', 'postcode $i','town $i','state $i','country $i','http://www.$val.com/','phone $i','comment enterprises $i','0','fax $i','info@ent$i.com','notes suppliers $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$entID=$DB->insert_id();
		addDocuments('Supplier',$entID);
	}
	$LAST["enterprises"]=getMaxItem("glpi_suppliers");

	// Ajout contracts
	$FIRST["contract"]=getMaxItem("glpi_contracts")+1;
	// Specific
	for ($i=0;$i<$MAX['contract'];$i++){
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);;

		$query="INSERT INTO glpi_contracts VALUES (NULL,'$ID_entity','0','contract $i-$ID_entity','num $i','".mt_rand(100,10000)."','".mt_rand(1,$MAX_CONTRACT_TYPE)."','$date','".mt_rand(1,36)."','".mt_rand(1,3)."','".mt_rand(1,36)."','".mt_rand(1,36)."','comment $i','compta num $i','0','08:00:00','19:00:00','09:00:00','16:00:00','1','00:00:00','00:00:00','0','0','notes contract $i','0','1')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$conID=$DB->insert_id();
		addDocuments('Contract',$conID);
		// Add an enterprise
		$query="INSERT INTO glpi_contracts_suppliers VALUES(NULL,'".mt_rand($FIRST["enterprises"],$LAST["enterprises"])."','$conID');";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	for ($i=0;$i<$MAX['contract']/2;$i++){
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);;

		$query="INSERT INTO glpi_contracts VALUES (NULL,'$ID_entity','1','Recursive contract $i-$ID_entity','num $i','".mt_rand(100,10000)."','".mt_rand(1,$MAX_CONTRACT_TYPE)."','$date','".mt_rand(1,36)."','".mt_rand(1,3)."','".mt_rand(1,36)."','".mt_rand(1,36)."','comment $i','compta num $i','0','08:00:00','19:00:00','09:00:00','16:00:00','1','00:00:00','00:00:00','0','0','notes contract $i','0','1')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$conID=$DB->insert_id();
		addDocuments('Contract',$conID);
		// Add an enterprise
		$query="INSERT INTO glpi_contracts_suppliers VALUES(NULL,'".mt_rand($FIRST["enterprises"],$LAST["enterprises"])."','$conID');";
		$DB->query($query) or die("PB REQUETE ".$query);
	}
	$LAST["contract"]=getMaxItem("glpi_contracts");


	// Ajout contacts
	$items=array("Jean Dupont","John Smith","Louis Durand","Pierre Martin","Auguste Dubois","Jean Dufour","Albert Dupin","Julien Duval","Guillaume Petit","Bruno Grange","Maurice Bernard","Francois Bonnet","Laurent Richard","Richard Leroy","Henri Dumont","Clement Fontaine");
	$FIRST["contacts"]=getMaxItem("glpi_contacts")+1;
	for ($i=0;$i<$MAX['contacts'];$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="contact $i";
		$query="INSERT INTO glpi_contacts VALUES (NULL,'$ID_entity','0','$val-$ID_entity','','phone $i','phone2 $i','mobile $i','fax $i','email $i','".mt_rand(1,$MAX['contact_type'])."','comment $i','0','notes contact $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$conID=$DB->insert_id();

		// Link with enterprise
		$query="INSERT INTO glpi_contacts_suppliers VALUES (NULL,'".mt_rand($FIRST['enterprises'],$LAST['enterprises'])."','$conID')";
		//	echo $query."<br>";
		$DB->query($query) or die("PB REQUETE ".$query);

	}
	for ($i=0;$i<$MAX['contacts']/2;$i++){
		if (isset($items[$i])) $val=$items[$i];
		else $val="contact $i";
		$query="INSERT INTO glpi_contacts VALUES (NULL,'$ID_entity','1','Recursive $val-$ID_entity','','phone $i','phone2 $i','mobile $i','fax $i','email $i','".mt_rand(1,$MAX['contact_type'])."','comment $i','0','notes contact $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$conID=$DB->insert_id();

		// Link with enterprise
		$query="INSERT INTO glpi_contacts_suppliers VALUES (NULL,'".mt_rand($FIRST['enterprises'],$LAST['enterprises'])."','$conID')";
		//	echo $query."<br>";
		$DB->query($query) or die("PB REQUETE ".$query);

	}
	$LAST["contacts"]=getMaxItem("glpi_contacts");

	// TYPE DE CONSOMMABLES
	$FIRST["type_of_consumables"]=getMaxItem("glpi_consumableitems")+1;
	for ($i=0;$i<$MAX['type_of_consumables'];$i++){
		$query="INSERT INTO glpi_consumableitems VALUES (NULL,'$ID_entity','consumable type $i','ref $i','".mt_rand($FIRST["locations"],$LAST['locations'])."','".mt_rand(0,$MAX['consumable_type'])."','".mt_rand(1,$MAX['manufacturer'])."','".mt_rand($FIRST['users_sadmin'],$LAST['users_admin'])."','0','comment $i','".mt_rand(0,10)."','notes consumableitem  $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$consID=$DB->insert_id();
		addDocuments('ConsumableItem',$consID);

		// AJOUT INFOCOMS
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES (NULL,'$consID','ConsumableItem','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar constype $consID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande constype $consID','BL cartype $consID','immo constype $consID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment constype $consID','facture constype $consID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);


		// Ajout consommable en stock
		for ($j=0;$j<mt_rand(0,$MAX['consumables_stock']);$j++){
			$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
			$query="INSERT INTO glpi_consumables VALUES(NULL,'$ID_entity','$consID','$date',NULL,0)";
			$DB->query($query) or die("PB REQUETE ".$query);
			$ID=$DB->insert_id();

			// AJOUT INFOCOMS
			$query="INSERT INTO glpi_infocoms VALUES (NULL,'$ID','Consumable','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar cons $ID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande cons $ID','BL cart $ID','immo cons $ID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment cons $ID','facture cons $ID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}
		// Ajout consommable donn�
		for ($j=0;$j<mt_rand(0,$MAX['consumables_given']);$j++){
			$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
			$query="INSERT INTO glpi_consumables VALUES(NULL,'$ID_entity','$consID','$date',NOW(),'".mt_rand($FIRST['users_sadmin'],$LAST['users_postonly'])."')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$ID=$DB->insert_id();

			// AJOUT INFOCOMS
			$query="INSERT INTO glpi_infocoms VALUES (NULL,'$ID','Consumable','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar cons $ID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande cons $ID','BL cart $ID','immo cons $ID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment cons $ID','facture cons $ID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}
	}
	$LAST["type_of_consumables"]=getMaxItem("glpi_consumableitems");


	// TYPE DE CARTOUCHES
	$FIRST["type_of_cartridges"]=getMaxItem("glpi_cartridgeitems")+1;
	for ($i=0;$i<$MAX['type_of_cartridges'];$i++){
		$query="INSERT INTO glpi_cartridgeitems VALUES (NULL,'$ID_entity','cartridge type $i','ref $i','".mt_rand(1,$MAX['locations'])."','".mt_rand(1,$MAX['cartridge_type'])."','".mt_rand(1,$MAX['manufacturer'])."','".mt_rand($FIRST['users_sadmin'],$LAST['users_admin'])."','0','comment $i','".mt_rand(0,10)."','notes cartridgeitem $i')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$cartID=$DB->insert_id();
		addDocuments('CartridgeItem',$cartID);

		// AJOUT INFOCOMS
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES (NULL,'$cartID','CartridgeItem','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar cartype $cartID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande cartype $cartID','BL cartype $cartID','immo cartype $cartID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment cartype $cartID','facture cartype $cartID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);


		// Ajout cartouche en stock
		for ($j=0;$j<mt_rand(0,$MAX['cartridges_stock']);$j++){
			$query="INSERT INTO glpi_cartridges VALUES(NULL,'$ID_entity','$cartID',0,NOW(),NULL,NULL,'0')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$ID=$DB->insert_id();

			// AJOUT INFOCOMS
			$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
			$query="INSERT INTO glpi_infocoms VALUES (NULL,'$ID','Cartridge','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar cart $ID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande cart $ID','BL cart $ID','immo cart $ID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment cart $ID','facture cart $ID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
			$DB->query($query) or die("PB REQUETE ".$query);

		}
		// Assoc printer type to cartridge type
		$query="INSERT INTO glpi_cartridgeitems_printermodels VALUES (NULL,'$cartID','".mt_rand(1,$MAX['type_printers'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);

	}
	$LAST["type_of_cartridges"]=getMaxItem("glpi_cartridgeitems");


	// Networking
	$net_loc=array();
	$FIRST["networking"]=getMaxItem("glpi_networkequipments")+1;
	$FIRST["printers"]=getMaxItem("glpi_printers")+1;
	$query="SELECT * FROM glpi_locations WHERE entities_id='$ID_entity'";
	$result=$DB->query($query);
	while ($data=$DB->fetch_array($result)){
		// insert networking
		$techID=mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
		$domainID=mt_rand(1,$MAX['domain']);
		$networkID=mt_rand(1,$MAX['network']);
		$vlanID=mt_rand(1,$MAX["vlan"]);
		$i=$data["id"];
		$vlan_loc[$data['id']]=$vlanID;
		$netname="networking $i-$ID_entity";
		$infoIP=getNextIP();
		$query="INSERT INTO glpi_networkequipments VALUES (NULL,'$ID_entity','0','$netname','".mt_rand(32,256)."','".getRandomString(10)."','".getRandomString(10)."','contact $i','num $i','$techID',NOW(),'comment $i','".$data['id']."','$domainID','$networkID','".mt_rand(1,$MAX['type_networking'])."','".mt_rand(1,$MAX['model_networking'])."','".mt_rand(1,$MAX['firmware'])."','".mt_rand(1,$MAX['enterprises'])."','0','0','','".getNextMAC()."','".$infoIP["ip"]."','notes networking $i','".mt_rand($FIRST['users_sadmin'],$LAST['users_admin'])."','".mt_rand($FIRST["groups"],$LAST["groups"])."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$netwID=$DB->insert_id();
		addDocuments('NetworkEquipment',$netwID);
		addContracts('NetworkEquipment',$netwID);

		$net_loc[$data['id']]=$netwID;
		$net_port['NetworkEquipment'][$netwID]=1;

		// AJOUT INFOCOMS
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES (NULL,'$netwID','NetworkEquipment','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar netw $netwID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande netw $netwID','BL netw $netwID','immo netw $netwID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment netw $netwID','facture netw $netwID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);

		// Link with father
		if ($data['locations_id']>0){
			//insert netpoint
			$query="INSERT INTO glpi_netpoints VALUES (NULL,'$ID_entity','".$data['id']."','".getNextNETPOINT()."','comment netpoint')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$netpointID=$DB->insert_id();

			$iface=mt_rand(1,$MAX['iface']);

			// Add networking ports
			$newIP=getNextIP();
			$newMAC=getNextMAC();
			$query="INSERT INTO glpi_networkports VALUES (NULL,'$netwID','NetworkEquipment','$ID_entity','0','".$net_port['NetworkEquipment'][$netwID]++."','link port to netw ".$net_loc[$data['locations_id']]."','".$newIP['ip']."','$newMAC','$iface','$netpointID','".$newIP['netwmask']."','".$newIP['gateway']."','".$newIP['subnet']."')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$port1ID=$DB->insert_id();
			$query="INSERT INTO glpi_networkports VALUES (NULL,'".$net_loc[$data['locations_id']]."','NetworkEquipment','$ID_entity','0','".$net_port['NetworkEquipment'][$net_loc[$data['locations_id']]]++."','link port to netw $netwID','".$newIP['ip']."','$newMAC','$iface','$netpointID','".$newIP['netwmask']."','".$newIP['gateway']."','".$newIP['subnet']."')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$port2ID=$DB->insert_id();

			$query="INSERT INTO glpi_networkports_networkports VALUES (NULL,'$port1ID','$port2ID')";
			$DB->query($query) or die("PB REQUETE ".$query);
			// Add Vlan
			$query="INSERT INTO glpi_networkports_vlans VALUES (NULL,'$port1ID','$vlanID')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$query="INSERT INTO glpi_networkports_vlans VALUES (NULL,'$port2ID','$vlanID')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}

		// Ajout imprimantes reseaux : 1 par loc + connexion �un matos reseau + ajout de cartouches
		//insert netpoint
		$query="INSERT INTO glpi_netpoints VALUES (NULL,'$ID_entity','".$data['id']."','".getNextNETPOINT()."','comment netpoint')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$netpointID=$DB->insert_id();

		// Add trackings
		addTracking('NetworkEquipment',$netwID,$ID_entity);

		$typeID=mt_rand(1,$MAX['type_printers']);
		$modelID=mt_rand(1,$MAX['model_printers']);
      $recur=mt_rand(0,1);
		$query="INSERT INTO glpi_printers VALUES (NULL,'$ID_entity','$recur','printer of loc ".$data['id']."',NOW(),'contact ".$data['id']."','num ".$data['id']."','$techID','".getRandomString(10)."','".getRandomString(10)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','comment $i','".mt_rand(0,64)."','".$data['id']."','$domainID','$networkID','$modelID','$typeID','".mt_rand(1,$MAX['manufacturer'])."','1','0','0','','0','notes printers ".$data['id']."','".mt_rand($FIRST['users_sadmin'],$LAST['users_admin'])."','".mt_rand($FIRST["groups"],$LAST["groups"])."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$printID=$DB->insert_id();
		addDocuments('Printer',$printID);
		addContracts('Printer',$printID);
		$net_port['Printer'][$printID]=0;

		// Add trackings
		addTracking('Printer',$printID,$ID_entity);

		// AJOUT INFOCOMS
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES (NULL,'$printID','Printer','$ID_entity','$recur','$date','$date','".mt_rand(12,36)."','infowar print $printID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande print $printID','BL print $printID','immo print $printID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment print $printID','facture print $printID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);


		// Add Cartouches
		// Get compatible cartridge
		$query="SELECT cartridgeitems_id FROM glpi_cartridgeitems_printermodels WHERE printermodels_id='$typeID'";
		$result2=$DB->query($query) or die("PB REQUETE ".$query);
		if ($DB->numrows($result2)>0){
			$ctypeID=$DB->result($result2,0,0) or die (" PB RESULT ".$query);
			$printed=0;
			$oldnb=mt_rand(1,$MAX['cartridges_by_printer']);
			$date1=strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
			$date2=time();
			$inter=abs(round(($date2-$date1)/$oldnb));

			// Add old cartridges
			for ($j=0;$j<$oldnb;$j++){
				$printed+=mt_rand(0,5000);
				$query="INSERT INTO glpi_cartridges VALUES (NULL,'$ID_entity','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date1+$j*$inter)."','".date("Y-m-d",$date1+($j+1)*$inter)."','$printed')";
				$DB->query($query) or die("PB REQUETE ".$query);
			}
			// Add current cartridges
			$query="INSERT INTO glpi_cartridges VALUES (NULL,'$ID_entity','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date2)."',NULL,'0')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}

		$iface=mt_rand(1,$MAX['iface']);

		// Add networking ports
		$newIP=getNextIP();
		$newMAC=getNextMAC();
		$query="INSERT INTO glpi_networkports VALUES (NULL,'$netwID','NetworkEquipment','$ID_entity','0','".$net_port['NetworkEquipment'][$netwID]++."','link port to printer of loc ".$data["id"]."','".$newIP['ip']."','$newMAC','$iface','$netpointID','".$newIP['netwmask']."','".$newIP['gateway']."','".$newIP['subnet']."');";

		$DB->query($query) or die("PB REQUETE ".$query);
		$port1ID=$DB->insert_id();
		$query="INSERT INTO glpi_networkports VALUES (NULL,'$printID','Printer','$ID_entity','$recur','".$net_port['Printer'][$printID]++."','link port to netw $netwID','".$newIP['ip']."','$newMAC','$iface','$netpointID','".$newIP['netwmask']."','".$newIP['gateway']."','".$newIP['subnet']."');";
		$DB->query($query) or die("PB REQUETE ".$query);
		$port2ID=$DB->insert_id();
		$query="INSERT INTO glpi_networkports_networkports VALUES (NULL,'$port1ID','$port2ID')";
		$DB->query($query) or die("PB REQUETE ".$query);
		// Add Vlan
		$query="INSERT INTO glpi_networkports_vlans VALUES (NULL,'$port1ID','$vlanID')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_networkports_vlans VALUES (NULL,'$port2ID','$vlanID')";
		$DB->query($query) or die("PB REQUETE ".$query);

	}
	unset($net_loc);
	$LAST["networking"]=getMaxItem("glpi_networkequipments");


	//////////// INVENTORY

	// glpi_computers
	$FIRST["computers"]=getMaxItem("glpi_computers")+1;
	$FIRST["monitors"]=getMaxItem("glpi_monitors")+1;
	$FIRST["phones"]=getMaxItem("glpi_phones")+1;
	$FIRST["peripherals"]=getMaxItem("glpi_peripherals")+1;

	for ($i=0;$i<$MAX['computers'];$i++){
		$loc=mt_rand($FIRST["locations"],$LAST['locations']);
		$techID=mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
		$userID=mt_rand($FIRST['users_normal'],$LAST['users_postonly']);
		$groupID=mt_rand($FIRST["groups"],$LAST["groups"]);
		$domainID=mt_rand(1,$MAX['domain']);
		$networkID=mt_rand(1,$MAX['network']);
		$query="INSERT INTO glpi_computers VALUES (NULL,'$ID_entity','computers $i-$ID_entity','".getRandomString(10)."','".getRandomString(10)."','contact $i','num $i','$techID','',NOW(),'".mt_rand(1,$MAX['os'])."','".mt_rand(1,$MAX['os_version'])."','".mt_rand(1,$MAX['os_sp'])."','os sn $i','os id $i','".mt_rand(1,$MAX['auto_update'])."','".$loc."','$domainID','$networkID','".mt_rand(1,$MAX['model'])."','".mt_rand(1,$MAX['type_computers'])."','0','','".mt_rand(1,$MAX['manufacturer'])."','0','note computer $i','0','".$userID."','".$groupID."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$compID=$DB->insert_id();
		addDocuments('Computer',$compID);
		addContracts('Computer',$compID);

		$net_port['Computer'][$compID]=0;

		// Add trackings
		addTracking('Computer',$compID,$ID_entity);
		// Add reservation
		addReservation('Computer',$compID,$ID_entity);


		// AJOUT INFOCOMS
		$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES (NULL,'$compID','Computer','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar comp $compID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande comp $compID','BL comp $compID','immo comp $compID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment comp $compID','facture comp $compID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);

		// ADD DEVICE
		$query="INSERT INTO glpi_computers_devicemotherboards VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_deviceprocessors VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."','".(1000+200*mt_rand(0,10))."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicememories VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."','".(1024*mt_rand(0,6))."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_deviceharddrives VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."','".(51200*mt_rand(0,10))."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicenetworkcards VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."','".getNextMAC()."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicedrives VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicecontrols VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicegraphiccards VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."','".(256*mt_rand(0,8))."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicesoundcards VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		if (mt_rand(0,100)<50){
			$query="INSERT INTO glpi_computers_devicepcis VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}
		$query="INSERT INTO glpi_computers_devicecases VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$query="INSERT INTO glpi_computers_devicepowersupplies VALUES (NULL,'$compID','".mt_rand(1,$MAX['device'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);

		// insert disk
		$nb_disk=mt_rand(1,$MAX_DISK);
		for ($j=1;$j<=$nb_disk;$j++){
			$totalsize=mt_rand(10000,1000000);
			$freesize=mt_rand(0,$totalsize);
			$query="INSERT INTO glpi_computerdisks VALUES (NULL,'$ID_entity','$compID','disk $j','/dev/disk$j','/mnt/disk$j','".mt_rand(1,10)."','$totalsize','$freesize')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}

		//insert netpoint
		$query="INSERT INTO glpi_netpoints VALUES (NULL,'$ID_entity','$loc','".getNextNETPOINT()."','comment netpoint')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$netpointID=$DB->insert_id();

		// Get networking element
		$query="SELECT id FROM glpi_networkequipments WHERE locations_id='$loc' and entities_id='$ID_entity'";
		$result=$DB->query($query) or die("PB REQUETE ".$query);
		if ($DB->numrows($result)>0){
			$netwID=$DB->result($result,0,0) or die (" PB RESULT ".$query);

			$iface=mt_rand(1,$MAX['iface']);

			// Add networking ports
			$newIP=getNextIP();
			$newMAC=getNextMAC();
			$query="INSERT INTO glpi_networkports VALUES (NULL,'$compID','Computer','$ID_entity','0','".$net_port['Computer'][$compID]++."','link port to netw $netwID','".$newIP['ip']."','$newMAC','$iface','$netpointID','".$newIP['netwmask']."','".$newIP['gateway']."','".$newIP['subnet']."')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$port1ID=$DB->insert_id();
			$query="INSERT INTO glpi_networkports VALUES (NULL,'$netwID','NetworkEquipment','$ID_entity','0','".$net_port['NetworkEquipment'][$netwID]++."','link port to computer $i','".$newIP['ip']."','$newMAC','$iface','$netpointID','".$newIP['netwmask']."','".$newIP['gateway']."','".$newIP['subnet']."')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$port2ID=$DB->insert_id();

			$query="INSERT INTO glpi_networkports_networkports VALUES (NULL,'$port1ID','$port2ID')";
			$DB->query($query) or die("PB REQUETE ".$query);
			// Add Vlan
			$query="INSERT INTO glpi_networkports_vlans VALUES (NULL,'$port1ID','".$vlan_loc[$loc]."')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$query="INSERT INTO glpi_networkports_vlans VALUES (NULL,'$port2ID','".$vlan_loc[$loc]."')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}

		// Ajout d'un ecran sur l'ordi

		$query="INSERT INTO glpi_monitors VALUES (NULL,'$ID_entity','monitor $i-$ID_entity',NOW(),'contact $i','num $i','$techID','comment $i','".getRandomString(10)."','".getRandomString(10)."','".mt_rand(14,22)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','$loc','".mt_rand(1,$MAX['model_monitors'])."','".mt_rand(1,$MAX['type_monitors'])."','".mt_rand(1,$MAX['manufacturer'])."','0','0','0','','notes monitor $i','".$userID."','".$groupID."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$monID=$DB->insert_id();
		addDocuments('Monitor',$monID);
		addContracts('Monitor',$monID);

		// Add trackings
		addTracking('Monitor',$monID,$ID_entity);

		$query="INSERT INTO glpi_computers_items VALUES (NULL,'$monID','$compID','Monitor')";
		$DB->query($query) or die("PB REQUETE ".$query);

		// Ajout d'un t��hone avec l'ordi

		$query="INSERT INTO glpi_phones VALUES (NULL,'$ID_entity','phone $i-$ID_entity',NOW(),'contact $i','num $i','$techID','comment $i','".getRandomString(10)."','".getRandomString(10)."','".getRandomString(10)."','$loc','".mt_rand(1,$MAX['type_phones'])."','".mt_rand(1,$MAX['model_phones'])."','".getRandomString(10)."','".mt_rand(0,$MAX['phone_power'])."','".getRandomString(10)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(1,$MAX['manufacturer'])."','0','0','0','','notes phone $i','".$userID."','".$groupID."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$telID=$DB->insert_id();
		addDocuments('Phone',$monID);
		addContracts('Phone',$monID);

		// Add trackings
		addTracking('Phone',$monID,$ID_entity);

		$query="INSERT INTO glpi_computers_items VALUES (NULL,'$telID','$compID','Phone')";
		$DB->query($query) or die("PB REQUETE ".$query);

		// Ajout des periphs externes en connection directe
		while (mt_rand(0,100)<$percent['peripherals']){
			$query="INSERT INTO glpi_peripherals VALUES (NULL,'$ID_entity','periph of comp $i-$ID_entity',NOW(),'contact $i','num $i','$techID','comment $i','".getRandomString(10)."','".getRandomString(10)."','$loc','".mt_rand(1,$MAX['type_peripherals'])."','".mt_rand(1,$MAX['model_peripherals'])."','brand $i','".mt_rand(1,$MAX['manufacturer'])."','0','0','0','','notes peripherals $i','".$userID."','".$groupID."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$periphID=$DB->insert_id();
			addDocuments('Peripheral',$periphID);
			addContracts('Peripheral',$periphID);

			// Add trackings
			addTracking('Peripheral',$periphID,$ID_entity);

			// Add connection
			$query="INSERT INTO glpi_computers_items VALUES (NULL,'$periphID','$compID','Peripheral')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}

		// AJOUT INFOCOMS
		// Use date of the computer
		//	$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
		$query="INSERT INTO glpi_infocoms VALUES (NULL,'$monID','Monitor','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar mon $monID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande mon $monID','BL mon $monID','immo mon $monID','".mt_rand(0,800)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','v mon $monID','facture mon $monID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);


		// Ajout d'une imprimante connection directe pour X% des computers + ajout de cartouches
		if (mt_rand(0,100)<=$percent['printer']){
			// Add printer
			$typeID=mt_rand(1,$MAX['type_printers']);
			$modelID=mt_rand(1,$MAX['model_printers']);
			$query="INSERT INTO glpi_printers VALUES (NULL,'$ID_entity','0','printer of comp $i-$ID_entity',NOW(),'contact $i','num $i','$techID','".getRandomString(10)."','".getRandomString(10)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','".mt_rand(0,1)."','comment $i','".mt_rand(0,64)."','$loc','$domainID','$networkID','$modelID','$typeID','".mt_rand(1,$MAX['enterprises'])."','0','0','0','','0','notes printers $i','".mt_rand($FIRST['users_postonly'],$LAST['users_postonly'])."','".mt_rand(1,$MAX["groups"])."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$printID=$DB->insert_id();
			addDocuments('Printer',$printID);
			addContracts('Printer',$printID);

			// Add trackings
			addTracking('Printer',$printID,$ID_entity);

			// Add connection
			$query="INSERT INTO glpi_computers_items VALUES (NULL,'$printID','$compID','Printer')";
			$DB->query($query) or die("PB REQUETE ".$query);


			// AJOUT INFOCOMS
			// use computer date
			//$date=mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
			$query="INSERT INTO glpi_infocoms VALUES (NULL,'$printID','Printer','$ID_entity','0','$date','$date','".mt_rand(12,36)."','infowar print $printID','".mt_rand($FIRST["enterprises"],$LAST['enterprises'])."','commande print $printID','BL print $printID','immo print $printID','".mt_rand(0,5000)."','".mt_rand(0,500)."','".mt_rand(1,7)."','".mt_rand(1,2)."','".mt_rand(2,5)."','comment print $printID','facture print $printID','".mt_rand($FIRST['budget'],$LAST['budget'])."','0')";
			$DB->query($query) or die("PB REQUETE ".$query);

			// Add Cartouches
			// Get compatible cartridge
			$query="SELECT cartridgeitems_id FROM glpi_cartridgeitems_printermodels WHERE printermodels_id='$typeID'";
			$result=$DB->query($query) or die("PB REQUETE ".$query);
			if ($DB->numrows($result)>0){
				$ctypeID=$DB->result($result,0,0) or die (" PB RESULT ".$query);
				$printed=0;
				$oldnb=mt_rand(1,$MAX['cartridges_by_printer']);
				$date1=strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
				$date2=time();
				$inter=round(($date2-$date1)/$oldnb);
				// Add old cartridges
				for ($j=0;$j<$oldnb;$j++){
					$printed+=mt_rand(0,5000);

					$query="INSERT INTO glpi_cartridges VALUES (NULL,'$ID_entity','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date1+$j*$inter)."','".date("Y-m-d",$date1+($j+1)*$inter)."','$printed')";
					$DB->query($query) or die("PB REQUETE ".$query);
				}
				// Add current cartridges
				$query="INSERT INTO glpi_cartridges VALUES (NULL,'$entID','$ctypeID','$printID','".date("Y-m-d",$date1)."','".date("Y-m-d",$date2)."',NULL,'0')";
				$DB->query($query) or die("PB REQUETE ".$query);
			}
		}

	}
	$LAST["computers"]=getMaxItem("glpi_computers");
	$LAST["monitors"]=getMaxItem("glpi_monitors");
	$LAST["phones"]=getMaxItem("glpi_phones");



	// Add global peripherals
	for ($i=0;$i<$MAX['global_peripherals'];$i++){
		$techID=mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
		$query="INSERT INTO glpi_peripherals VALUES (NULL,'$ID_entity','periph $i-$ID_entity',NOW(),'contact $i','num $i','$techID','comment $i','".getRandomString(10)."','".getRandomString(10)."','0','".mt_rand(1,$MAX['type_peripherals'])."','".mt_rand(1,$MAX['model_peripherals'])."','brand $i','".mt_rand(1,$MAX['manufacturer'])."','1','0','0','','notes peripherals $i','".mt_rand($FIRST['users_normal'],$LAST['users_normal'])."','".mt_rand($FIRST["groups"],$LAST["groups"])."','".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','0')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$periphID=$DB->insert_id();
		addDocuments('Peripheral',$periphID);
		addContracts('Peripheral',$periphID);

		// Add trackings
		addTracking('Peripheral',$periphID,$ID_entity);
		// Add reservation
		addReservation('Peripheral',$periphID,$ID_entity);

		// Add connections
		$val=mt_rand(1,$MAX['connect_for_peripherals']);
		for ($j=1;$j<$val;$j++){
			$query="INSERT INTO glpi_computers_items VALUES (NULL,'$periphID','".mt_rand($FIRST["computers"],$LAST['computers'])."','Peripheral')";
			$DB->query($query) or die("PB REQUETE ".$query);
		}
	}
	$LAST["peripherals"]=getMaxItem("glpi_peripherals");


	$FIRST["software"]=getMaxItem("glpi_softwares")+1;
	// Ajout logiciels + licences associees a divers PCs
	$items=array(array("OpenOffice","1.1.4","2.0","2.0.1"),array("Microsoft Office","95","97","XP","2000","2003",2007),array("Acrobat Reader","6.0","7.0","7.04"),array("Gimp","2.0","2.2"),array("InkScape","0.4"));
	for ($i=0;$i<$MAX['software'];$i++){

		if (isset($items[$i])) $name=$items[$i][0];
		else {$name="software $i";}

		$loc=mt_rand(1,$MAX['locations']);
		$techID=mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
		$recursive=mt_rand(0,1);
		$query="INSERT INTO glpi_softwares VALUES (NULL,'$ID_entity','$recursive','$name','comment $i','$loc','$techID','0','-1','".mt_rand(1,$MAX['manufacturer'])."','0','0','',NOW(),'notes software $i','".mt_rand($FIRST['users_admin'],$LAST['users_admin'])."','".mt_rand($FIRST["groups"],$LAST["groups"])."','0','1','".mt_rand(1,$MAX['softwarecategory'])."')";
		$DB->query($query) or die("PB REQUETE ".$query);
		$softID=$DB->insert_id();
		addDocuments('Software',$softID);
		addContracts('Software',$softID);

		// Add trackings
		addTracking('Software',$softID,$ID_entity);

		// Add versions
		$FIRST["version"]=getMaxItem("glpi_softwareversions")+1;
		if (isset($items[$i])) {
			$val2=count($items[$i]);
		} else {
			$val2=mt_rand(1,$MAX['softwareversions']+1);
		}
		for ($j=1;$j<=$val2;$j++){
			if (isset($items[$i])) {
				$version=$items[$i][mt_rand(1,count($items[$i])-1)];
			} else $version="$j.0";
         		$os=mt_rand(1,$MAX['os']);
			$query="INSERT INTO glpi_softwareversions VALUES (NULL, '$ID_entity','$recursive', $softID, '".(mt_rand(0,100)<$percent['state']?mt_rand(1,$MAX['state']):0)."','$version','comment $version','$os')";
			$DB->query($query) or die("PB REQUETE ".$query);
			$versID=$DB->insert_id();
			$val3=mt_rand(1,$MAX['softwareinstall']);
			for ($k=0;$k<$val3;$k++){
				$query="INSERT INTO glpi_computers_softwareversions VALUES (NULL,'".mt_rand($FIRST["computers"],$LAST['computers'])."','$versID')";
				$DB->query($query); // no die because may be corrupt unicity constraint
			}
		}
		$LAST["version"]=getMaxItem("glpi_softwareversions");
		// Add licenses
		$val2=mt_rand(1,$MAX['softwarelicenses']);
		for ($j=0;$j<$val2;$j++){
			$softwareversions_id_buy=mt_rand($FIRST["version"],$LAST["version"]);
			$softwareversions_id_use=mt_rand($softwareversions_id_buy,$LAST["version"]);
                        $nbused=mt_rand(1,$MAX['softwareinstall']);
			$query="INSERT INTO glpi_softwarelicenses VALUES (NULL,$softID,'$ID_entity','$recursive','$nbused','".mt_rand(1,$MAX['licensetype'])."','license $j','serial $j','otherserial $j','$softwareversions_id_buy','$softwareversions_id_use',NULL,'comment license $j',NOW())";
			$DB->query($query) or die("PB REQUETE ".$query);
                        $licID=$DB->insert_id();

                        for ($k=0;$k<$nbused;$k++){
				$query="INSERT INTO glpi_computers_softwarelicenses VALUES (NULL,'".mt_rand($FIRST["computers"],$LAST['computers'])."','$licID')";
				$DB->query($query); // no die because may be corrupt unicity constraint
			}
		}

	}

	$LAST["software"]=getMaxItem("glpi_softwares");


	$query="UPDATE `glpi_ticketplannings` SET state='2' WHERE end < NOW()";
	$DB->query($query) or die("PB REQUETE ".$query);

}

?>
