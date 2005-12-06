<?php
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

include ("_relpos.php");

function showInfocomForm ($target,$device_type,$dev_ID,$show_immo=1,$withtemplate='') {
	// Show Infocom or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$date_fiscale=$cfg_install["date_fiscale"];
	
	$ic = new Infocom;

	$option="";
	if ($withtemplate==2)
		$option=" readonly ";

	if (!ereg("infocoms-show",$_SERVER["PHP_SELF"])&&($device_type==SOFTWARE_TYPE||$device_type==CARTRIDGE_TYPE||$device_type==CONSUMABLE_TYPE)){
	echo "<div align='center'>".$lang["financial"][84]."</div>";
	}
		
	echo "<br>";
	if (!$ic->getfromDB($device_type,$dev_ID)){
		if ($withtemplate!=2){
		echo "<div align='center'>";
		echo "<b><a href='$target?device_type=$device_type&amp;FK_device=$dev_ID&amp;add=add'>".$lang["financial"][68]."</a></b><br>";
		echo "</div>";
		}
	} else {
		if ($withtemplate!=2)
		echo "<form name='form_ic' method='post' action=\"$target\">";
		
		echo "<div align='center'>";
		echo "<table class='tab_cadre' width='700'>";

		echo "<tr><th colspan='4'><b>".$lang["financial"][3]."</b></th></tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][26].":		</td>";
		echo "<td align='center'>";
		if ($withtemplate==2) 
		echo getDropdownName("glpi_enterprises",$ic->fields["FK_enterprise"]);
		else dropdownValue("glpi_enterprises","FK_enterprise",$ic->fields["FK_enterprise"]);
		
		echo "</td>";
		echo "<td>".$lang["financial"][82].":		</td>";
		echo "<td >";
		autocompletionTextField("facture","glpi_infocoms","facture",$ic->fields["facture"],25);	
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][18].":		</td>";
		echo "<td >";
		autocompletionTextField("num_commande","glpi_infocoms","num_commande",$ic->fields["num_commande"],25);	
		echo "</td>";
		
		echo "<td>".$lang["financial"][19].":		</td><td>";
		autocompletionTextField("bon_livraison","glpi_infocoms","bon_livraison",$ic->fields["bon_livraison"],25);	
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][14].":	</td><td>";
		showCalendarForm("form_ic","buy_date",$ic->fields["buy_date"],$withtemplate);	
	    echo "</td>";
		

		echo "<td>".$lang["financial"][76].":	</td><td>";
		showCalendarForm("form_ic","use_date",$ic->fields["use_date"],$withtemplate);	
	    echo "</td>";
		echo "</tr>";

		if ($show_immo==1){
		
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][15].":	</td><td>";
		if ($withtemplate==2)
		echo $ic->fields["warranty_duration"];
		else dropdownContractTime("warranty_duration",$ic->fields["warranty_duration"]);
		echo " ".$lang["financial"][57];
		echo "</td>";
	
		echo "<td>".$lang["financial"][80].":	</td><td >";
	
		echo getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]);
				
		echo "</td></tr>";
		
		
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][78].":		</td>";
		echo "<td ><input type='text' $option name='warranty_value' value=\"".htmlentities(number_format($ic->fields["warranty_value"],2,'.',''))."\" size='10'></td>";
		

		echo "<td>".$lang["financial"][16].":		</td>";
		echo "<td >";
		autocompletionTextField("warranty_info","glpi_infocoms","warranty_info",$ic->fields["warranty_info"],25);	

		echo "</td></tr>";
		}
		
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][21].":		</td><td  ".($show_immo==1?"":" colspan='3'")."><input type='text' name='value' $option value=\"".htmlentities(number_format($ic->fields["value"],2,'.',''))."\" size='10'></td>";
		if ($show_immo==1){
		echo "<td>".$lang["financial"][81]." :</td><td>";
				
		echo  TableauAmort($ic->fields["amort_type"],$ic->fields["value"],$ic->fields["amort_time"],$ic->fields["amort_coeff"],$ic->fields["buy_date"],$ic->fields["use_date"],$date_fiscale,"n");
		
		echo "</td>";
		}
		echo "</tr>";
		
		if ($show_immo==1){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][20].":		</td>";
		echo "<td >";
		autocompletionTextField("num_immo","glpi_infocoms","num_immo",$ic->fields["num_immo"],25,$option);	

		echo "</td>";
		
					
		

		echo "<td>".$lang["financial"][22].":		</td><td >";
		if ($withtemplate==2)
		echo getAmortTypeName($ic->fields["amort_type"]);
		else dropdownAmortType("amort_type",$ic->fields["amort_type"]);
		
		echo "</td></tr>";
		
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][23].":		</td><td>";
		if ($withtemplate==2)
		echo $ic->fields["amort_time"];
		else dropdownDuration("amort_time",$ic->fields["amort_time"]);
		echo " ".$lang["financial"][9];
		echo "</td>";
		
		echo "<td>".$lang["financial"][77].":		</td>";
		echo "<td >";
		autocompletionTextField("amort_coeff","glpi_infocoms","amort_coeff",$ic->fields["amort_coeff"],10);	
		echo "</td></tr>";
		}

		echo "<tr class='tab_bg_1'><td valign='top'>";
		echo $lang["financial"][12].":	</td>";
		echo "<td align='center' colspan='3'><textarea cols='60' $option rows='2' name='comments' >".$ic->fields["comments"]."</textarea>";
		echo "</td></tr>";
		if ($withtemplate!=2){
			echo "<tr>";
                
		        echo "<td class='tab_bg_2' colspan='2' align='center'>";
			echo "<input type='hidden' name='ID' value=\"".$ic->fields['ID']."\">\n";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n\n";
//			echo "</form>";
//			echo "<form method='post' action=\"".$HTMLRel."infocoms/infocoms-info-form.php\"><div align='center'>";
//			echo "<input type='hidden' name='ID' value=\"".$ic->fields['ID']."\">\n";
			echo "<td class='tab_bg_2' colspan='2' align='center'>\n";
			echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
			
			echo "</td>";
			echo "</tr>";
		}

		echo "</table></div>";
		if ($withtemplate!=2) echo "</form>";
		
	}
}

function updateInfocom($input) {
	// Update Software in the database

	$ic = new Infocom;
	$ic->getFromDBbyID($input["ID"]);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$ic->fields) && $ic->fields[$key] != $input[$key]) {
			$ic->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$ic->updateInDB($updates);
	}
}

function addInfocom($input) {
	
	$ic = new Infocom;

	// dump status
	unset($input['add']);

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($ic->fields[$key]) || $ic->fields[$key] != $input[$key])) {
			$ic->fields[$key] = $input[$key];
		}
	}

	return $ic->addToDB();
}


function deleteInfocom($input,$force=0) {
	// Delete Infocom
	
	$ic = new Infocom;
	$ic->deleteFromDB($input["ID"],$force);
} 

function dropdownDuration($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	for ($i=0;$i<=10;$i+=1)
	echo "<option value='$i' ".($value==$i?" selected ":"").">$i</option>";	
	echo "</select>";	
}

function dropdownAmortType($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["financial"][47]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["financial"][48]."</option>";
	echo "</select>";	
}
function getAmortTypeName($value){
	global $lang;
	
	switch ($value){
	case 2 :
		return $lang["financial"][47];
		break;
	case 1 :
		return $lang["financial"][48];
		break;
	case 0 :
		return "";
		break;
	
	}	
}

function dropdownInfocoms($name){

	$db=new DB;
	$query="SELECT glpi_infocoms.buy_date as buy_date, glpi_infocoms.ID as ID, glpi_enterprises.name as name ";
	$query.= " from glpi_infocoms LEFT JOIN glpi_enterprises ON glpi_infocoms.FK_enterprise = glpi_enterprises.ID ";
	$query.= " WHERE glpi_infocoms.deleted = 'N' order by glpi_infocoms.buy_date DESC";
	$result=$db->query($query);
	echo "<select name='$name'>";
	while ($data=$db->fetch_array($result)){
		
	echo "<option value='".$data["ID"]."'>";
	echo $data["buy_date"]." - ".$data["name"];
	echo "</option>";
	}

	echo "</select>";	
	
	
	
}


function getWarrantyExpir($from,$addwarranty){
if ($from==NULL || $from=='0000-00-00')
return "";
else return date("Y-m-d", strtotime("$from + $addwarranty month "));

}



function TableauAmort($type_amort,$va,$duree,$coef,$date_achat,$date_use,$date_fiscale,$view="n") {
	// By Jean-Mathieu Doléans qui s'est un peu pris le chou :p
	
	// $type_amort = "lineaire=2" ou "degressif=1"
	// $va = valeur d'acquisition
	// $duree = duree d'amortissement
	// $coef = coefficient d'amortissement
	// $date_achat= Date d'achat
	// $date_use = Date d'utilisation
	// $date_fiscale= date du début de l'année fiscale
	// $view = "n" pour l'année en cours ou "all" pour le tableau complet
	
	// Attention date mise en service/dateachat ->amort linéaire  et $prorata en jour !!
	// amort degressif au prorata du nombre de mois. Son point de départ est le 1er jour du mois d'acquisition et non date de mise en service
			
		if ($type_amort=="2"){
		
			if ($date_use!="0000-00-00") $date_achat=$date_use;
		
		}
	
		$prorata=0;
		$ecartfinmoiscourant=0;
		$ecartmoisexercice=0;
		
		sscanf($date_achat, "%4s-%2s-%2s %2s:%2s:%2s",
		$date_Y, $date_m, $date_d,
		$date_H, $date_i, $date_s); // un traitement sur la date mysql pour récupérer l'année
	
		// un traitement sur la date mysql pour les infos necessaires	
		sscanf($date_fiscale, "%4s-%2s-%2s %2s:%2s:%2s",
		$date_Y2, $date_m2, $date_d2,
		$date_H2, $date_i2, $date_s2); 
		$date_Y2=date("Y");
		
		//if ($type_amort=="2") { 
		
		switch ($type_amort) {
		
		case "2" :
		
		########################### Calcul amortissement linéaire ###########################
			
			if($va>0 &&$duree>0  &&$date_achat!="0000-00-00") {
				
				## calcul du prorata temporis en jour ##	
			
					
				$ecartfinmoiscourant=(30-$date_d); // calcul ecart entre jour  date acquis ou mise en service et fin du mois courant
			
				// en linéaire on calcule en jour
			
				if ($date_d2<30) {$ecartmoisexercice=(30-$date_d2); }	
				
			
				if ($date_m>$date_m2) {$date_m2=$date_m2+12;} // si l'année fiscale débute au delà de l'année courante
			
			
				$ecartmois=(($date_m2-$date_m)*30); // calcul ecart etre mois d'acquisition et debut année fiscale
			
			
				$prorata=$ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
				
			
				## calcul tableau d'amortissement ##
				
				$txlineaire = (100/$duree); // calcul du taux linéaire
			
				$annuite = ($va*$txlineaire)/100; // calcul de l'annuité 
			
				$mrt=$va; //
			
			
				// si prorata temporis la dernière annnuité cours sur la durée n+1
				if ($prorata>0){$duree=$duree+1;}
			
				for($i=1;$i<=$duree;$i++) {
			
				
					$tab['annee'][$i]=$date_Y+$i-1;
			
					$tab['annuite'][$i]=$annuite;
				
					$tab['vcnetdeb'][$i]=$mrt; // Pour chaque année on calcul la valeur comptable nette  de debut d'exercice
				
					$tab['vcnetfin'][$i]=abs(($mrt - $annuite)); // Pour chaque année on calcul la valeur comptable nette  de fin d'exercice
			
			
					// calcul de la première annuité si prorata temporis
					if ($prorata>0){
						$tab['annuite'][1]=$annuite*($prorata/360);
			
						$tab['vcnetfin'][1]=abs($va - $tab['annuite'][1]);
							}
			
					$mrt=$tab['vcnetfin'][$i];
			
				} // end for
				
				// calcul de la dernière annuité si prorata temporis
				if ($prorata>0){
					$tab['annuite'][$duree]=$tab['vcnetdeb'][$duree];
				
					$tab['vcnetfin'][$duree]=$tab['vcnetfin'][$duree-1]- $tab['annuite'][$duree];
						}
			
			}else{ return "-"; break; }	
		
			break;	
			
		//}else{	
		case "1" :
			########################### Calcul amortissement dégressif ###########################
		
			if($va>0 &&$duree>0  &&$coef>1 &&$date_achat!="0000-00-00") {
			
			## calcul du prorata temporis en mois ##
			
			// si l'année fiscale débute au delà de l'année courante
		
			if ($date_m>$date_m2) {	$date_m2=$date_m2+12;	}
		
			$ecartmois=($date_m2-$date_m)+1; // calcul ecart etre mois d'acquisition et debut année fiscale
		
			$prorata=$ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
			
			
			
			## calcul tableau d'amortissement ##
			
			
		
			$txlineaire = (100/$duree); // calcul du taux linéaire virtuel
			$txdegressif=$txlineaire*$coef; // calcul du taux degressif
			$dureelineaire= (int) (100/$txdegressif); // calcul de la durée de l'amortissement en mode linéaire
			$dureedegressif=$duree-$dureelineaire;// calcul de la durée de l'amortissement en mode degressif
			$mrt=$va; //
		
			// amortissement degressif pour les premières années
		
			for($i=1;$i<=$dureedegressif;$i++) {
			
				$tab['annee'][$i]=$date_Y+$i-1;
				
				$tab['vcnetdeb'][$i]=$mrt; // Pour chaque année on calcul la valeur comptable nette  de debut d'exercice
		
				$tab['annuite'][$i]=$tab['vcnetdeb'][$i]*$txdegressif/100;
		
				$tab['vcnetfin'][$i]=$mrt - $tab['annuite'][$i]; // Pour chaque année on calcul la valeur comptable nette  de fin d'exercice
		
				// calcul de la première annuité si prorata temporis
				if ($prorata>0){
		
					$tab['annuite'][1]=($va*$txdegressif/100)*($prorata/12);
		
					$tab['vcnetfin'][1]=$va - $tab['annuite'][1];
			
					}
		
				$mrt=$tab['vcnetfin'][$i];
		
			} // end for
		
				
			
			// amortissement en linéaire pour les dernières années 	 
		
			
			if ($dureelineaire!=0)
				$txlineaire = (100/$dureelineaire); // calcul du taux linéaire
			else $txlineaire = 100;
			$annuite = ($tab['vcnetfin'][$dureedegressif]*$txlineaire)/100; // calcul de l'annuité 
			$mrt=$tab['vcnetfin'][$dureedegressif];
				
			
			for($i=$dureedegressif+1;$i<=$dureedegressif+$dureelineaire;$i++) {
		
				$tab['annee'][$i]=$date_Y+$i-1;
				
				$tab['annuite'][$i]=$annuite;
				
				$tab['vcnetdeb'][$i]=$mrt; // Pour chaque année on calcul la valeur comptable nette  de debut d'exercice
			
				$tab['vcnetfin'][$i]=abs(($mrt - $annuite)); // Pour chaque année on calcul la valeur comptable nette  de fin d'exercice
		
				$mrt=$tab['vcnetfin'][$i];
		
			} // end for
			
			// calcul de la dernière annuité si prorata temporis
			if ($prorata>0){
			
			$tab['annuite'][$duree]=$tab['vcnetdeb'][$duree];
			
			if (isset($tab['vcnetfin'][$duree-1]))
			$tab['vcnetfin'][$duree]=$tab['vcnetfin'][$duree-1]- $tab['annuite'][$duree];
			else $tab['vcnetfin'][$duree]=0;
			
			}
		
			}else{ return "-"; break; }	
			
			break;
		
				
		default :
			return "-"; break;
			
					
		}
		
		
		
		// le return
		
	
		if ($view=="all") {
		
		// on retourne le tableau complet
		
		return $tab;
		
		}else{
		
		// on retourne juste la valeur résiduelle
		
		
			// si on ne trouve pas l'année en cours dans le tableau d'amortissement dans le tableau, le matériel est amorti
			if (!array_search(date("Y"),$tab["annee"]))
			{
			$vnc=0;
		
			}elseif (mktime(0 , 0 , 0, $date_m2, $date_d2, date("Y"))  - mktime(0 , 0 , 0 , date("m") , date("d") , date("Y")) < 0 ){
		
				// on a dépassé la fin d'exercice de l'année en cours
		
				//on prend la valeur résiduelle de l'année en cours
			
				$vnc= $tab["vcnetfin"][array_search(date("Y"),$tab["annee"])];
				
			}else {
		
				// on se situe avant la fin d'exercice
				// on prend la valeur résiduelle de l'année n-1
			
				
				
				$vnc=$tab["vcnetdeb"][array_search(date("Y"),$tab["annee"])];
				
				
				
						
			}
	
		return number_format($vnc,2,".","");
		
		}

	



}

function addInfocomOptionFieldsToResearch($option){
global $lang;
$option["glpi_infocoms.num_immo"]=$lang["financial"][20];
$option["glpi_infocoms.num_commande"]=$lang["financial"][18];
$option["glpi_infocoms.bon_livraison"]=$lang["financial"][19];
$option["glpi_infocoms.facture"]=$lang["financial"][82];
return $option;

}

function getInfocomSearchToRequest($table,$type){
return " LEFT JOIN glpi_infocoms ON ($table.ID = glpi_infocoms.FK_device AND glpi_infocoms.device_type='".$type."') ";

}

function getInfocomSearchToViewAllRequest($contains){
return " OR glpi_infocoms.num_immo LIKE '%".$contains."%' OR glpi_infocoms.num_commande LIKE '%".$contains."%' OR glpi_infocoms.bon_livraison LIKE '%".$contains."%' OR glpi_infocoms.facture LIKE '%".$contains."%' ";
}

function showDisplayInfocomLink($device_type,$device_id,$update=0){
global $HTMLRel,$lang;
$db=new DB;
$query="SELECT COUNT(ID) FROM glpi_infocoms WHERE FK_device='$device_id' AND device_type='$device_type'";
$add="add";
$text=$lang["buttons"][8];
$result=$db->query($query);
if ($db->result($result,0,0)>0) {
	$add="";
	$text=$lang["buttons"][23];
}

echo "<a href='#' onClick=\"window.open('".$HTMLRel."infocoms/infocoms-show.php?device_type=$device_type&amp;device_id=$device_id&amp;update=$update','infocoms','location=infocoms,width=750,height=600,scrollbars=no')\"><img src=\"".$HTMLRel."/pics/dollar$add.png\" alt=\"$text\" title=\"$text\"></a>";
}

?>
