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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


include ("_relpos.php");
$NEEDED_ITEMS=array("knowbase","document");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["modify"])) $tab["modify"] = "";
if(!isset($tab["delete"])) $tab["delete"] = "";
if(!isset($tab["addtofaq"])) $tab["addtofaq"] = "";
if(!isset($tab["removefromfaq"])) $tab["removefromfaq"] = "";


$kb=new kbItem;


if ($tab["ID"]=="new"){
	// on affiche le formulaire de saisie de l'item

	checkSeveralRightsOr(array("knowbase"=>"w","faq"=>"w"));

	commonHeader($lang["title"][5],$_SERVER['PHP_SELF']);

	showKbItemForm($_SERVER['PHP_SELF'],"");

	commonFooter();

}

else if (isset($_POST["add"])){
	// ajoute un item dans la base de connaisssances 	
	checkSeveralRightsOr(array("knowbase"=>"w","faq"=>"w"));


	$newID=$kb->add($_POST);
	logEvent($newID, "knowbase", 5, "tools", $_SESSION["glpiname"]." ".$lang["log"][20]);

	glpi_header($cfg_glpi["root_doc"]."/front/knowbase.php");
}

else if (isset($tab["ID"])  && strcmp($tab["modify"],"yes") == 0){


	// modifier un item dans la base de connaissance

	checkSeveralRightsOr(array("knowbase"=>"r","faq"=>"r"));
	commonHeader($lang["title"][5],$_SERVER['PHP_SELF']);

	showKbItemForm($_SERVER['PHP_SELF'],$tab["ID"]);


	commonFooter();

}

else if (isset($_POST["update"])){

	// actualiser  un item dans la base de connaissances

	checkSeveralRightsOr(array("knowbase"=>"w","faq"=>"w"));

	$kb->update($_POST);
	logEvent($tab["ID"], "knowbase", 5, "tools", $_SESSION["glpiname"]." ".$lang["log"][21]);	

	glpi_header($cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=".$tab["ID"]);
}

else if (isset($tab["ID"])  && strcmp($tab["delete"],"yes") == 0){


	// effacer un item dans la base de connaissances

	checkSeveralRightsOr(array("knowbase"=>"w","faq"=>"w"));

	$kb->delete($tab);
	logEvent(0, "knowbase", 5, "tools", $_SESSION["glpiname"]." ".$lang["log"][22]);	
	glpi_header($cfg_glpi["root_doc"]."/front/knowbase.php");
}


else if (isset($tab["ID"])  && strcmp($tab["addtofaq"],"yes") == 0){


	// ajouter  un item dans la faq

	checkRight("faq","w");

	KbItemaddtofaq($tab["ID"]);



	glpi_header($cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=".$tab["ID"]);
}


else if (isset($tab["ID"])  && strcmp($tab["removefromfaq"],"yes") == 0){


	// retirer  un item de la faq

	checkRight("faq","w");

	KbItemremovefromfaq($tab["ID"]);




	glpi_header($cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=".$tab["ID"]);
}

else if (empty($tab["ID"])) {
	glpi_header($cfg_glpi["root_doc"]."/front/knowbase.php");
}		

else  {
	// Affiche un item de la base de connaissances
	checkSeveralRightsOr(array("knowbase"=>"r","faq"=>"r"));
	commonHeader($lang["title"][5],$_SERVER['PHP_SELF']);


	if (ShowKbItemFull($tab["ID"])){
		kbItemMenu($tab["ID"]);
		showDocumentAssociated(KNOWBASE_TYPE,$tab["ID"]);
	}
	commonFooter();
}


?>
