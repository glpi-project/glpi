<?php
/*
 
  ----------------------------------------------------------------------
 GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

checkAuthentication("admin");

if(!empty($_GET["next"])) {

	if($_GET["next"] == "extsources") {
	
		commonHeader("External sources infos",$_SERVER["PHP_SELF"]);
		titleExtSources();
		showFormExtSources($_SERVER["PHP_SELF"]);
	}
	elseif($_GET["next"] == "mailing") {
	
		commonHeader("Mailing infos",$_SERVER["PHP_SELF"]);
		
		
		titleMailing();
		showFormMailing($_SERVER["PHP_SELF"]);
		
		
		
	}
}
elseif(!empty($_POST["update_mailing"])) {

	updateMailing($_POST["mailing"],$_POST["admin_email"],$_POST["mailing_signature"],$_POST["mailing_new_admin"],$_POST["mailing_attrib_admin"],$_POST["mailing_followup_admin"],$_POST["mailing_finish_admin"],$_POST["mailing_new_all_admin"],$_POST["mailing_attrib_all_admin"],$_POST["mailing_followup_all_admin"],$_POST["mailing_finish_all_admin"],$_POST["mailing_new_all_normal"],$_POST["mailing_attrib_all_normal"],$_POST["mailing_followup_all_normal"],$_POST["mailing_finish_all_normal"],$_POST["mailing_attrib_attrib"],$_POST["mailing_followup_attrib"],$_POST["mailing_finish_attrib"],$_POST["mailing_new_user"],$_POST["mailing_attrib_user"],$_POST["mailing_followup_user"],$_POST["mailing_finish_user"],$_POST["mailing_new_attrib"]);
	header("Location: ".$cfg_install["root"]."/setup/index.php");
}
elseif(!empty($_POST["update_ext"])) {

	if(!empty($_POST["LDAP_test"]) ) {
//todo test remote connection
		updateLDAP($_POST["ldap_host"],$_POST["ldap_basedn"],$_POST["ldap_rootdn"],$_POST["ldap_pass"]);
	}
	if(!empty($_POST["IMAP_test"])) {
		updateIMAP($_POST["imap_auth_server"],$_POST["imap_host"]);
	}
	header("Location: ".$cfg_install["root"]."/setup/index.php");
}


commonFooter();


?>