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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_financial.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["lID"])) $tab["lID"] = "";
if(!isset($tab["sID"])) $tab["sID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = 0;

/*if (isset($tab["Modif_Interne"])){
	checkAuthentication("admin");
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showLicenseForm($_SERVER["PHP_SELF"],$tab['form'],$tab["sID"],$tab["lID"],$tab['search_computer']);
	commonFooter();

}
else 
*/


if (isset($_POST["add"]))
{
	checkAuthentication("admin");
	//print_r($_POST);
	if ($_POST["serial"]=="free")$number=1;
	else $number=$_POST["number"];
	unset($tab["number"]);
	
	
	for ($i=1;$i<=$number;$i++){
	addLicense($tab);
	}
	
	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");
	
	glpi_header($_SERVER['PHP_SELF']."?form=add&sID=".$tab["sID"]);
}
else if (isset($tab["update_stock_licenses"])||isset($tab["update_stock_licenses_x"])){
	checkAuthentication("admin");
	foreach ($tab as $key => $val)
	if (ereg("stock_licenses_([0-9]+)",$key,$regs))
		if ($val!=$tab["nb_licenses_".$regs[1]])
			updateNumberLicenses($regs[1],$tab["nb_licenses_".$regs[1]],$val);
	glpi_header($_SERVER['HTTP_REFERER']);

}	
/*else if (isset($tab["duplicate"]))
{
	checkAuthentication("admin");
	$lic=new License();
	$lic->getFromDB($tab["lID"]);
	
	unset($lic->fields["ID"]);
	if (is_null($lic->fields["expire"]))
	unset($lic->fields["expire"]);
	$lic->addToDB();
		
	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
*/
else if (isset($tab["update"]))
{
	checkAuthentication("admin");
	unset($tab["search_software"]);

	updateLicense($tab);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." update a license.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["form"]))
{
	checkAuthentication("admin");
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showLicenseForm($_SERVER["PHP_SELF"],$tab['form'],$tab["sID"],$tab["lID"]);
	commonFooter();
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	deleteLicense($tab["ID"]);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." deleted a license.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
/*
else if (isset($tab["select"]))
{
	if ($tab["sID"]==-1)
		glpi_header($_SERVER['HTTP_REFERER']." ");
	
	checkAuthentication("admin");
	commonHeader($lang["title"][12],$_SERVER["PHP_SELF"]);
	showLicenseSelect($_SERVER['HTTP_REFERER']."&withtemplate=".$tab["withtemplate"],$_SERVER["PHP_SELF"],$tab["cID"],$tab["sID"],$tab["withtemplate"]);
	commonFooter();
}
*/
else if (isset($tab["install"]))
{
	checkAuthentication("admin");
	installSoftware($tab["cID"],$tab["licenseID"],$tab["sID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["uninstall"]))
{
	checkAuthentication("admin");
	uninstallSoftware($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled software.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["back"]))
{
	
	glpi_header($tab["back"]." ");
}


?>
