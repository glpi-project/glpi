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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_knowbase.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["modify"])) $tab["modify"] = "";
if(!isset($tab["delete"])) $tab["delete"] = "";
if(!isset($tab["addtofaq"])) $tab["addtofaq"] = "";
if(!isset($tab["removefromfaq"])) $tab["removefromfaq"] = "";
	


if (empty($tab["ID"])) {
	header("Location: ".$cfg_install["root"]."/knowbase/");
	}

	
	if ($tab["ID"]=="new"){
// on affiche le formulaire de saisie de l'item

	checkAuthentication("admin");
	commonHeader($lang["title"][5],$_SERVER["PHP_SELF"]);
	
	showKbItemForm($_SERVER["PHP_SELF"],"");
	
	commonFooter();

	}
			
	else if (isset($_POST["add"])){
// ajoute un item dans la base de connaisssances 	
	checkAuthentication("admin");
	
	
	addKbItem($_POST);
	logEvent(0, "knowledge", 5, "tools", $_SESSION["glpiname"]." add an item");	
	
	header("Location: ".$cfg_install["root"]."/knowbase/");
	
	}
	
	else if (isset($tab["ID"])  && strcmp($tab["modify"],"yes") == 0){
	
		
	// modifier un item dans la base de connaissance
	
	checkAuthentication("admin");
	commonHeader($lang["title"][5],$_SERVER["PHP_SELF"]);

	showKbItemForm($_SERVER["PHP_SELF"],$tab["ID"]);
	
	commonFooter();

	}
	
	else if (isset($_POST["update"])){
	
	// actualiser  un item dans la base de connaissances
	
	checkAuthentication("admin");
	
	updateKbItem($_POST);
	
		
	header("Location: ".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=".$tab["ID"]);
	
	}
	
	else if (isset($tab["ID"])  && strcmp($tab["delete"],"yes") == 0){
	
	
	
	// effacer un item dans la base de connaissances
	
	checkAuthentication("admin");
	
	deleteKbItem($tab["ID"]);
	header("Location: ".$cfg_install["root"]."/knowbase/");

	}
	
	
	else if (isset($tab["ID"])  && strcmp($tab["addtofaq"],"yes") == 0){
	
	
	// ajouter  un item dans la faq
	
	checkAuthentication("admin");
	
	KbItemaddtofaq($tab["ID"]);
	
	
		
	header("Location: ".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=".$tab["ID"]);
	
	}
	
	
	else if (isset($tab["ID"])  && strcmp($tab["removefromfaq"],"yes") == 0){
	
	
	// retirer  un item de la faq
	
	checkAuthentication("admin");
	
	KbItemremovefromfaq($tab["ID"]);
	
	
	
		
	header("Location: ".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=".$tab["ID"]);

	}
	
		

	else  {
// Affiche un item de la base de connaissances

	checkAuthentication("normal");
	commonHeader($lang["title"][5],$_SERVER["PHP_SELF"]);
	ShowKbItemFull($tab["ID"]);
	
		if ($_SESSION["glpitype"]=="admin"||$_SESSION["glpitype"]=="super-admin"){
		kbItemMenu($tab["ID"]);
		}
	commonFooter();
	}

	
?>