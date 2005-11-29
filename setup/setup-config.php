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
include ($phproot . "/glpi/includes_setup.php");

checkAuthentication("super-admin");

if(!empty($_GET["next"])) {

	if($_GET["next"] == "extsources") {
		commonHeader($lang["title"][14],$_SERVER["PHP_SELF"]);
		titleExtSources();
		showFormExtSources($_SERVER["PHP_SELF"]);
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
	
	
}
elseif(!empty($_POST["update_mailing"])) {

	updateMailing($_POST["mailing"],$_POST["admin_email"],$_POST["mailing_signature"],$_POST["mailing_new_admin"],$_POST["mailing_followup_admin"],$_POST["mailing_finish_admin"],$_POST["mailing_new_all_admin"],$_POST["mailing_followup_all_admin"],$_POST["mailing_finish_all_admin"],$_POST["mailing_new_all_normal"],$_POST["mailing_followup_all_normal"],$_POST["mailing_finish_all_normal"],$_POST["mailing_followup_attrib"],$_POST["mailing_finish_attrib"],$_POST["mailing_new_user"],$_POST["mailing_followup_user"],$_POST["mailing_finish_user"],$_POST["mailing_new_attrib"],$_POST["mailing_resa_admin"],$_POST["mailing_resa_all_admin"],$_POST["mailing_resa_user"],$_POST["url_base"],$_POST["url_in_mail"],$_POST["mailing_attrib_attrib"]);
	glpi_header($cfg_install["root"]."/setup/index.php");
}
elseif(!empty($_POST["update_ext"])) {

	if(empty($_POST["LDAP_Test"]) ) {
//todo test remote connection
	
		updateLDAP($_POST["ldap_host"],$_POST["ldap_basedn"],$_POST["ldap_rootdn"],$_POST["ldap_pass"],$_POST["ldap_condition"],$_POST["ldap_login"],$_POST["ldap_field_name"],$_POST["ldap_field_email"],$_POST["ldap_field_location"],$_POST["ldap_field_phone"],$_POST["ldap_field_realname"],$_POST["ldap_port"]);
	}
	if(empty($_POST["IMAP_Test"])) {
		$auth_server=constructIMAPAuthServer($_POST);
		updateIMAP($auth_server,$_POST["imap_host"]);
	}
	if(empty($_POST["CA_Test"])) {
		updateCAS($_POST["cas_host"],$_POST["cas_port"],$_POST["cas_uri"]);
	}
	glpi_header($cfg_install["root"]."/setup/setup-config.php?next=extsources");
}
elseif(!empty($_POST["update_confgen"])) {
	updateConfigGen($_POST["root_doc"], $_POST["event_loglevel"], $_POST["expire_events"],$_POST["permit_helpdesk"],$_POST["default_language"],$_POST["date_fiscale"],$_POST["cartridges_alarm"],
	$_POST["auto_assign"],$_POST["auto_update_check"]);
	glpi_header($cfg_install["root"]."/setup/setup-config.php?next=confgen");
}
elseif(!empty($_POST["update_confdisplay"])) {
	updateConfigDisplay($_POST["num_of_events"], $_POST["jobs_at_login"],$_POST["list_limit"], $_POST["cut"],$_POST["priority"],
	$_POST["planning_begin"],$_POST["planning_end"],$_POST["public_faq"],$_POST["text_login"],
	$_POST["use_ajax"],$_POST["ajax_wildcard"],$_POST["ajax_limit_count"],$_POST["dropdown_max"]);
	glpi_header($cfg_install["root"]."/setup/setup-config.php?next=confdisplay");
}

commonFooter();


?>