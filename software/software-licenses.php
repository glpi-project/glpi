<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_computers.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["lID"])) $tab["lID"] = "";
if(!isset($tab["sID"])) $tab["sID"] = "";
if(!isset($tab["search_computer"])) $tab["search_computer"] = "";
if(!isset($tab["search_software"])) $tab["search_software"] = "";


if (isset($tab["Modif_Interne"])){
	checkAuthentication("admin");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showLicenseForm($_SERVER["PHP_SELF"],$tab['form'],$tab["sID"],$tab["lID"],$tab['search_computer'],$tab['search_software']);
	commonFooter();

}
else if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	//print_r($_POST);
	if ($_POST["serial"]=="free")$number=1;
	else $number=$_POST["number"];
	unset($tab["number"]);
	unset($tab["search_computer"]);
	unset($tab["search_software"]);
	
	
	for ($i=1;$i<=$number;$i++){
	addLicense($tab);
	}
	
	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");
	
	header("Location: ".$_SERVER['PHP_SELF']."?form=add&sID=".$tab["sID"]);
}
else if (isset($tab["duplicate"]))
{
	checkAuthentication("admin");
	$lic=new License();
	$lic->getFromDB($tab["lID"]);
	
	unset($lic->fields["ID"]);
	if (is_null($lic->fields["expire"]))
	unset($lic->fields["expire"]);
	$lic->addToDB();
		
	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");
	
	header("Location: ".$_SERVER['HTTP_REFERER']);
}
else if (isset($tab["update"]))
{
	checkAuthentication("admin");
	unset($tab["search_computer"]);
	unset($tab["search_software"]);

	updateLicense($tab);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." update a license.");
	header("Location: ".$_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["form"]))
{
	checkAuthentication("admin");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showLicenseForm($_SERVER["PHP_SELF"],$tab['form'],$tab["sID"],$tab["lID"],$tab['search_computer'],$tab['search_software']);
	commonFooter();
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	deleteLicense($tab["ID"]);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." deleted a license.");
	header("Location: ".$_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["select"]))
{
	checkAuthentication("admin");
	commonHeader("Software",$_SERVER["PHP_SELF"]);
	showLicenseSelect($_SERVER['HTTP_REFERER'],$_SERVER["PHP_SELF"],$tab["cID"],$tab["sID"]);
	commonFooter();
}
else if (isset($tab["install"]))
{
	checkAuthentication("admin");
	installSoftware($tab["cID"],$tab["lID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	//echo $tab["back"];
	header("Location: ".$tab['back']." ");
}
else if (isset($tab["uninstall"]))
{
	checkAuthentication("admin");
	uninstallSoftware($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled software.");
	header("Location: ".$_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["back"]))
{
	
	header("Location: ".$tab["back"]." ");
}


?>
