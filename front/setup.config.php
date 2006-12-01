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

$NEEDED_ITEMS=array("setup","ocsng","mailing");
include ($phproot . "/inc/includes.php");

checkRight("config","w");
$config= new Config();
if ($cfg_glpi["ocs_mode"]) $ocsconfig=new ConfigOCS();

if (!isset($_SESSION['glpi_mailconfig'])) $_SESSION['glpi_mailconfig']=1;
if (isset($_GET['onglet'])) $_SESSION['glpi_mailconfig']=$_GET['onglet'];

if(!empty($_GET["next"])) {

	if($_GET["next"] == "extauth") {
		commonHeader($lang["title"][14],$_SERVER['PHP_SELF']);
		titleExtAuth();
		showFormExtAuth($_SERVER['PHP_SELF']);
	}
	elseif($_GET["next"] == "mailing") {
		commonHeader($lang["title"][15],$_SERVER['PHP_SELF']);
		titleMailing();
		showFormMailing($_SERVER['PHP_SELF']);
	}
	elseif($_GET["next"] == "confgen") {
		commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);
		titleConfigGen();
		showFormConfigGen($_SERVER['PHP_SELF']);
	}
	elseif($_GET["next"] == "confdisplay") {
		commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);
		titleConfigDisplay();
		showFormConfigDisplay($_SERVER['PHP_SELF']);
	}
	elseif($_GET["next"] == "ocsng") {
		$dbocs=new DBocs();
		commonHeader($lang["title"][39],$_SERVER['PHP_SELF']);
		ocsFormDBConfig($_SERVER['PHP_SELF'], $cfg_glpi["ID"]);
	}


}
elseif (!empty($_POST["test_smtp_send"])){
	testMail();
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=mailing");
}
elseif(!empty($_POST["update_mailing"])) {
	$config->update($_POST);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=mailing");
}
elseif(!empty($_POST["update_notifications"])) {

	updateMailNotifications($_POST);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=mailing");
}
elseif(!empty($_POST["update_ext"])) {
	$config->update($_POST);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=extauth");

}
elseif(!empty($_POST["update_confgen"])) {
	$config->update($_POST);
	if ($_POST["ocs_mode"]&&!$cfg_glpi["ocs_mode"])
		glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=ocsng");
	else 
		glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=confgen");
}
elseif(!empty($_POST["update_confdisplay"])) {
	$config->update($_POST);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=confdisplay");
} elseif(!empty($_POST["update_ocs_config"])) {
	$ocsconfig->update($_POST);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=ocsng");
} elseif(!empty($_POST["update_ocs_dbconfig"])) {
	$ocsconfig->update($_POST);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=ocsng");
}
commonFooter();


?>
