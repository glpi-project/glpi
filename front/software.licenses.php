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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

$NEEDED_ITEMS=array("computer","software","infocom","contract");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["lID"])) $tab["lID"] = "";
if(!isset($tab["sID"])) $tab["sID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = 0;

$lic=new License;

if (isset($_POST["add"]))
{
	checkRight("software","w");

	if ($_POST["serial"]=="free")$number=1;
	else $number=$_POST["number"];
	unset($tab["number"]);


	for ($i=1;$i<=$number;$i++){
		unset($lic->fields["ID"]);
		$lic->add($tab);
	}

	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");

	glpi_header($_SERVER['PHP_SELF']."?form=add&sID=".$tab["sID"]);
}
else if (isset($tab["update_stock_licenses"])||isset($tab["update_stock_licenses_x"])){
	checkRight("software","w");

	foreach ($tab as $key => $val)
		if (ereg("stock_licenses_([0-9]+)",$key,$regs))
			if ($val!=$tab["nb_licenses_".$regs[1]])
				updateNumberLicenses($regs[1],$tab["nb_licenses_".$regs[1]],$val);
	glpi_header($_SERVER['HTTP_REFERER']);

}	
else if (isset($tab["update_expire"])||isset($tab["update_expire_x"])){
	checkRight("software","w");

	$lic=new License;
	$input["expire"]=$tab["expire"];

	foreach ($tab as $key => $val)
		if (ereg("license_([0-9]+)",$key,$ereg)){
			$input["ID"]=$ereg[1];
			$lic->update($input);
		}

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["update_buy"])||isset($tab["update_buy_x"])){
	checkRight("software","w");

	$input["buy"]=$tab["buy"];	

	foreach ($tab as $key => $val)
		if (ereg("license_([0-9]+)",$key,$ereg)){
			$input["ID"]=$ereg[1];
			$lic->update($input);
		}

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["move"])||isset($tab["move"])){
	if ($tab["lID"]&&$lic->getFromDB($tab["lID"])){
		unset($lic->fields["ID"]);
		unset($lic->fields["comments"]);

		$lic2=new License();
		foreach ($tab as $key => $val)
			if (ereg("license_([0-9]+)",$key,$ereg)){
				$input=$lic->fields;
				$input["ID"]=$ereg[1];
				unset($lic2->fields);
				$lic2->update($input);
			}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["update"]))
{
	checkRight("software","w");

	unset($tab["search_software"]);

	$lic->update($tab);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." update a license.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["form"]))
{
	checkRight("software","w");

	commonHeader($lang["title"][12],$_SERVER['PHP_SELF']);
	showLicenseForm($_SERVER['PHP_SELF'],$tab['form'],$tab["sID"],$tab["lID"]);
	commonFooter();
}
else if (isset($tab["delete"]))
{
	checkRight("software","w");

	$lic->delete(array("ID"=>$tab["ID"]));
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." deleted a license.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["install"]))
{
	checkRight("software","w");

	installSoftware($tab["cID"],$tab["licenseID"],$tab["sID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["uninstall"]))
{
	checkRight("software","w");

	uninstallSoftware($tab["ID"]);
	logEvent($tab["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled software.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["unglobalize"])&&isset($tab["ID"])){
	unglobalizeLicense($tab["ID"]);
	logEvent($tab["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$lang["log"][60]);
	glpi_header($cfg_glpi["root_doc"]."/front/software.form.php?ID=".$tab["sID"]);
}
else if (isset($tab["back"]))
{
	glpi_header($tab["back"]." ");
}


?>
