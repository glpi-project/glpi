<?php


/*
 * @version $Id: setup.auth.php 7326 2008-09-23 10:18:17Z tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

$NEEDED_ITEMS = array (
	"setup",
	"auth",
	"ldap",
	"user"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");
$config = new Config();
$config_ldap = new AuthLDAP();

//LDAP Server add/update/delete
if (isset ($_POST["update_ldap"])) {
	$config_ldap->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["add_ldap"])) {
	//If no name has been given to this configuration, then go back to the page without adding
	if ($_POST["name"] != ""){
		if ($newID=$config_ldap->add($_POST)){
			if (testLDAPConnection($newID)){
				addMessageAfterRedirect($LANG["login"][22]);
			} else{
				addMessageAfterRedirect($LANG["login"][23]);	
			}
			glpi_header($CFG_GLPI["root_doc"] . "/front/auth.ldap.php?next=extauth_ldap&ID=".$newID);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["delete_ldap"])) {
	$config_ldap->delete($_POST);
	$_SESSION['glpi_authconfig']=1;
	glpi_header($CFG_GLPI["root_doc"] . "/front/auth.ldap.php");
}
elseif (isset ($_POST["test_ldap"])) {
	$ldap = new AuthLDAP;
	$ldap->getFromDB($_POST["ID"]);
	
	if (testLDAPConnection($_POST["ID"])){
		$_SESSION["LDAP_TEST_MESSAGE"]=$LANG["login"][22]." (".$LANG["ldap"][21]." : ".$ldap->fields["name"].")";;
	} else{
		$_SESSION["LDAP_TEST_MESSAGE"]=$LANG["login"][23]." (".$LANG["ldap"][21]." : ".$ldap->fields["name"].")";;	
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset ($_POST["test_ldap_replicate"])) {
	foreach($_POST["test_ldap_replicate"] as $replicate_id => $value)
	{
		$replicate = new AuthLdapReplicate;
		$replicate->getFromDB($replicate_id);

		if (testLDAPConnection($_POST["ID"],$replicate_id)){
			$_SESSION["LDAP_TEST_MESSAGE"]=$LANG["login"][22]." (".$LANG["ldap"][19]." : ".$replicate->fields["name"].")";
		} else{
			$_SESSION["LDAP_TEST_MESSAGE"]=$LANG["login"][23]." (".$LANG["ldap"][19]." : ".$replicate->fields["name"].")";	
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (isset($_POST["delete_replicate"]))
{
	$replicate = new AuthLdapReplicate;
	foreach ($_POST["item"] as $index=>$val)
		$replicate->delete(array("ID"=>$index));
	glpi_header($_SERVER['HTTP_REFERER']);		
}
elseif (isset($_POST["add_replicate"]))
{
	$replicate = new AuthLdapReplicate;
	unset($_POST["next"]);
	unset($_POST["ID"]);
	$replicate->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);	
}

if (!isset ($_SESSION['glpi_authconfig'])){
	$_SESSION['glpi_authconfig'] = 1;
}
if (isset ($_GET['onglet'])){
	$_SESSION['glpi_authconfig'] = $_GET['onglet'];
}

if (!isset($_GET["ID"])){
	$_GET["ID"]="";	
}

if (!isset($_GET["next"])){
	$_GET["next"]="";	
}

if (!isset($_GET["preconfig"])){
	$_GET["preconfig"]="";	
}

commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"config","extauth","ldap");

$tabs[1]=array('title'=>$LANG["login"][2],
'url'=>$CFG_GLPI['root_doc']."/ajax/auth.tabs.php",
'params'=>"target=".$_SERVER['PHP_SELF']."&ID=".$_GET["ID"]."&next=".$_GET["next"]."&preconfig=".$_GET["preconfig"]."&auth_tab=1");
			
echo "<div id='tabspanel' class='center-h'></div>";
createAjaxTabs('tabspanel','tabcontent',$tabs,$_SESSION['glpi_authconfig']);
echo "<div id='tabcontent'></div>";
echo "<script type='text/javascript'>loadDefaultTab();</script>";

commonFooter();
?>
