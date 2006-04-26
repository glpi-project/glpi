<?php
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

$NEEDED_ITEMS=array("setup","ocsng");
include ($phproot . "/inc/includes.php");

checkRight("config","w");
$config= new Config();
if(!empty($_GET["next"])) {

	if($_GET["next"] == "extauth") {
		commonHeader($lang["title"][14],$_SERVER["PHP_SELF"]);
		titleExtAuth();
		showFormExtAuth($_SERVER["PHP_SELF"]);
	}
	elseif($_GET["next"] == "mailing") {
		commonHeader($lang["title"][15],$_SERVER["PHP_SELF"]);
		titleMailing();
		showFormMailing($_SERVER["PHP_SELF"]);
	}
	elseif($_GET["next"] == "confgen") {
		commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);
		titleConfigGen();
		showFormConfigGen($_SERVER["PHP_SELF"]);
	}
	elseif($_GET["next"] == "confdisplay") {
		commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);
		titleConfigDisplay();
		showFormConfigDisplay($_SERVER["PHP_SELF"]);
	}
	elseif($_GET["next"] == "ocsng") {
		commonHeader($lang["title"][39],$_SERVER["PHP_SELF"]);
		ocsFormDBConfig($_SERVER["PHP_SELF"], 1);
	}
	
	
}
elseif(!empty($_POST["update_mailing"])) {

	updateMailing($_POST["mailing"],$_POST["admin_email"],$_POST["mailing_signature"],$_POST["mailing_new_admin"],$_POST["mailing_followup_admin"],$_POST["mailing_finish_admin"],$_POST["mailing_new_all_admin"],$_POST["mailing_followup_all_admin"],$_POST["mailing_finish_all_admin"],$_POST["mailing_new_all_normal"],$_POST["mailing_followup_all_normal"],$_POST["mailing_finish_all_normal"],$_POST["mailing_followup_attrib"],$_POST["mailing_finish_attrib"],$_POST["mailing_new_user"],$_POST["mailing_followup_user"],$_POST["mailing_finish_user"],$_POST["mailing_new_attrib"],$_POST["mailing_resa_admin"],$_POST["mailing_resa_all_admin"],$_POST["mailing_resa_user"],$_POST["url_base"],$_POST["url_in_mail"],$_POST["mailing_attrib_attrib"],$_POST["mailing_update_admin"],$_POST["mailing_update_all_admin"],$_POST["mailing_update_all_normal"],$_POST["mailing_update_attrib"],$_POST["mailing_update_user"],$_POST["smtp_mode"],$_POST["smtp_host"],$_POST["smtp_port"],$_POST["smtp_username"], $_POST["smtp_password"]);
	glpi_header($cfg_glpi["root_doc"]."/font/setup.php");
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
	ocsUpdateConfig($_POST, 1);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=ocsng");
} elseif(!empty($_POST["update_ocs_dbconfig"])) {
	ocsUpdateDBConfig($_POST, 1);
	glpi_header($cfg_glpi["root_doc"]."/front/setup.config.php?next=ocsng");
}

commonFooter();


?>
