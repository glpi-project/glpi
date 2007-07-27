<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("ldap","user","profile","group","entity","rulesengine","rule.right");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("user","w");

commonHeader($LANG["setup"][3],$_SERVER['PHP_SELF'],"admin","ldap");

if (isset($_GET['next']))
{
	ldapChooseDirectory($_SERVER['PHP_SELF']);
}
else
{
if (isset($_SESSION["ldap_import"])){
	if ($count=count($_SESSION["ldap_import"])){
		$percent=min(100,round(100*($_SESSION["ldap_import_count"]-$count)/$_SESSION["ldap_import_count"],0));

		displayProgressBar(400,$percent);

		$key=array_pop($_SESSION["ldap_import"]);
		ldapImportUser($key,0);
		glpi_header($_SERVER['PHP_SELF']);

	} else {
		unset($_SESSION["ldap_import"]);
	   
		displayProgressBar(400,100);

		echo "<div align='center'><strong>".$LANG["ocsng"][8]."<br>";
		echo "<a href='".$_SERVER['PHP_SELF']."'>".$LANG["buttons"][13]."</a>";
		echo "</strong></div>";
	}
}
if (isset($_POST["change_ldap_filter"]))
{
	$_SESSION["ldap_filter"] = $_POST["ldap_filter"];
	glpi_header($_SERVER['PHP_SELF']);
}
elseif (!isset($_POST["import_ok"])){
	if (!isset($_GET['check'])) $_GET['check']='all';
	if (!isset($_GET['start'])) $_GET['start']=0;
	
	if (isset($_SESSION["ldap_import"])) unset($_SESSION["ldap_import"]);
	if (!isset($_SESSION["ldap_server"])) {
		if (isset($_POST["ldap_server"])){
			$_SESSION["ldap_server"]=$_POST["ldap_server"];
		} else {
			glpi_header($CFG_GLPI["root_doc"]."/front/ldap.php");
		}
	}
	
	if (!testLDAPConnection($_SESSION["ldap_server"]))
	{
		unset($_SESSION["ldap_server"]);
		echo "<div align='center'><strong>".$LANG["ldap"][6]."<br>";
		echo "<a href='".$_SERVER['PHP_SELF']."?next=listservers'>".$LANG["buttons"][13]."</a>";
		echo "</strong></div>";
	}
	else
	{
			if (!isset($_SESSION["ldap_filter"]))
					$_SESSION["ldap_filter"]='';
			showLdapUsers($_SERVER['PHP_SELF'],$_GET['check'],$_GET['start'],0,$_SESSION["ldap_filter"]);
	}
} else {

	if (count($_POST['toimport'])>0){
		$_SESSION["ldap_import_count"]=0;
		foreach ($_POST['toimport'] as $key => $val){
			if ($val=="on")	{
				$_SESSION["ldap_import"][]=$key;
				$_SESSION["ldap_import_count"]++;
			}
		}
	}

	glpi_header($_SERVER['PHP_SELF']);
}
}

commonFooter();

?>
