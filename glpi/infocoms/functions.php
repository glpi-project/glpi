<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
*/
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

function showInfocomForm ($target,$device_type,$dev_ID,$show_immo=1) {
	// Show Infocom or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$date_fiscale="2005-12-31";
	
	$ic = new Infocom;

	if (!$ic->getfromDB($device_type,$dev_ID)){
		echo "<center><b><a href='$target?device_type=$device_type&FK_device=$dev_ID&add=add'>Activer les informations commerciales</a></b></center>";
	} else {

		echo "<form name='form_ic' method='post' action=\"$target\"><div align='center'>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='3'><b>".$lang["financial"][3]."</b></th></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][26].":		</td>";
		echo "<td colspan='2'>";
		dropdownValue("glpi_enterprises","FK_enterprise",$ic->fields["FK_enterprise"]);
		$ent=new Enterprise();
		if ($ent->getFromDB($ic->fields["FK_enterprise"])){
			if (!empty($ent->fields['website'])){
				if (!ereg("https*://",$ent->fields['website']))	$website="http://".$ent->fields['website'];
				else $website=$ent->fields['website'];
				echo "<a href='$website'>SITE WEB</a>";
			}
		echo "&nbsp;&nbsp;";
		echo "<a href='".$HTMLRel."enterprises/enterprises-info-form.php?ID=".$ent->fields['ID']."'>MODIF</a>";
		}
		
		echo "</td></tr>";


		echo "<tr class='tab_bg_1'><td>".$lang["financial"][18].":		</td>";
		echo "<td colspan='2'><input type='text' name='num_commande' value=\"".$ic->fields["num_commande"]."\" size='25'></td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][19].":		</td>";
		echo "<td colspan='2'><input type='text' name='bon_livraison' value=\"".$ic->fields["bon_livraison"]."\" size='25'></td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][14].":	</td>";
		echo "<td colspan='2'><input type='text' name='buy_date' readonly size='10' value=\"".$ic->fields["buy_date"]."\">";
		echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form_ic&amp;elem=buy_date&amp;value=".$ic->fields["buy_date"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form_ic'].buy_date.value='0000-00-00'\" value='reset'>";
	    echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][76].":	</td>";
		echo "<td colspan='2'><input type='text' name='use_date' readonly size='10' value=\"".$ic->fields["use_date"]."\">";
		echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form_ic&amp;elem=use_date&amp;value=".$ic->fields["use_date"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form_ic'].use_date.value='0000-00-00'\" value='reset'>";
	    echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][15].":	</td><td colspan='2'>";
		dropdownContractTime("warranty_duration",$ic->fields["warranty_duration"]);
		echo " ".$lang["financial"][57];
		echo "</td></tr>";
	

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][16].":		</td>";
		echo "<td colspan='2'><input type='text' name='warranty_info' value=\"".$ic->fields["warranty_info"]."\" size='25'></td>";
		echo "</tr>";

		if ($show_immo==1){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][20].":		</td>";
		echo "<td colspan='2'><input type='text' name='num_immo' value=\"".$ic->fields["num_immo"]."\" size='25'></td>";
		echo "</tr>";
		}

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][21].":		</td>";
		echo "<td ".($show_immo==1?"":" colspan='2'")."><input type='text' name='value' value=\"".$ic->fields["value"]."\" size='10'></td>";
		
		if ($show_immo==1){
		echo "<td>Valeur nette comptable :";
		echo  TableauAmort($ic->fields["amort_type"],$ic->fields["value"],$ic->fields["amort_time"],$ic->fields["amort_coeff"],$ic->fields["buy_date"],$ic->fields["use_date"],$date_fiscale,$view="n");
		
		echo "</td>";
		}	
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][78].":		</td>";
		echo "<td colspan='2'><input type='text' name='warranty_value' value=\"".$ic->fields["warranty_value"]."\" size='10'></td>";
		echo "</tr>";
		
		if ($show_immo==1){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][22].":		</td><td colspan='2'>";
		dropdownAmortType("amort_type",$ic->fields["amort_type"]);
		echo "</td></tr>";
		}
		
		if ($show_immo==1){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][23].":		</td><td colspan='2'>";
		dropdownDuration("amort_time",$ic->fields["amort_time"]);
		echo " ".$lang["financial"][9];
		echo "</td></tr>";
		}
		if ($show_immo==1){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][77].":		</td>";
		echo "<td colspan='2'><input type='text' name='amort_coeff' value=\"".$ic->fields["amort_coeff"]."\" size='10'></td>";
		echo "</tr>";
		}

		echo "<tr class='tab_bg_1'><td valign='top'>";
		echo $lang["financial"][12].":	</td>";
		echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ic->fields["comments"]."</textarea>";
		echo "</td></tr>";
	
		echo "<tr>";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"".$ic->fields['ID']."\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>\n\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		echo "</form>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		
	}

}

function updateInfocom($input) {
	// Update Software in the database

	$ic = new Infocom;
	$ic->getFromDBbyID($input["ID"]);
 	// Pop off the last attribute, no longer needed
	$null=array_pop($input);
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($ic->fields[$key]) || $ic->fields[$key] != $input[$key]) {
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
	$null = array_pop($input);

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($ic->fields[$key]) || $ic->fields[$key] != $input[$key]) {
			$ic->fields[$key] = $input[$key];
		}
	}

	if ($ic->addToDB()) {
		return true;
	} else {
		return false;
	}
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
	
	
	
	if($va>0 &&$duree>0 &&$coef>1.01 &&$date_achat!="0000-00-00") {
		
		if ($type_amort=="2"){
		
			if ($date_use!="0000-00-00") $date_achat=$date_use;
		
		}
	
		$prorata=0;
		$ecartfinmoiscourant=0;
		$ecartmoisexercice=0;
		
		sscanf($date_achat, "%4s-%2s-%2s %2s:%2s:%2s",
		&$date_Y, &$date_m, &$date_d,
		&$date_H, &$date_i, &$date_s); // un traitement sur la date mysql pour récupérer l'année
	
		// un traitement sur la date mysql pour les infos necessaires	
		sscanf($date_fiscale, "%4s-%2s-%2s %2s:%2s:%2s",
		&$date_Y2, &$date_m2, &$date_d2,
		&$date_H2, &$date_i2, &$date_s2); 
		$date_Y2=date("Y");
		
		if ($type_amort=="2") { 
		
		########################### Calcul amortissement linéaire ###########################
		
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
			
		
			
		}else{	
		
			########################### Calcul amortissement dégressif ###########################
		
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
		
			
			$txlineaire = (100/$dureelineaire); // calcul du taux linéaire
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
			
			$tab['vcnetfin'][$duree]=$tab['vcnetfin'][$duree-1]- $tab['annuite'][$duree];
			
			}
			
		}
		
	
		if ($view=="all") {
		
		// on retourne le tableau complet
		
		return $tab;
		
		}else{
		
		// on retourne juste la valeur résiduelle
		
		
			if (mktime(0 , 0 , 0, $date_m2, $date_d2, date("Y"))  - mktime(0 , 0 , 0 , date("m") , date("d") , date("Y")) < 0 ){
		
				// on a dépassé la fin d'exercice de l'année en cours
		
				//on prend la valeur résiduelle de l'année en cours
			
				$vnc= $tab["vcnetfin"][array_search(date("Y"),$tab["annee"])];
				
			}else {
		
				// on se situe avant la fin d'exercice
				// on prend la valeur résiduelle de l'année n-1
			
				/*
				$nmoinsun=array_search(date("Y"),$tab["annee"])-1;
					
				if ($nmoinsun<=0) { $nmoinsun=array_search(date("Y"),$tab["annee"]);}
					
				$vnc=$tab["vcnetfin"][$nmoinsun];
				*/
				
				$vnc=$tab["vcnetdeb"][array_search(date("Y"),$tab["annee"])];
				
				
				
						
			}
	
		return number_format($vnc,2,"."," ");
		
		}

	}else{
	
	return "-";
	
	}



}





?>
