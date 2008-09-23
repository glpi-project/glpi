<?php
/*
 * @version $Id: computer.tabs.php 7152 2008-07-29 12:27:18Z jmd $
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

if(!isset($_POST["ID"])) {
	$_POST["ID"]="";	
}

if (!isset($_POST["next"])){
	$_POST["next"] = '';
}

if (!isset($_POST["preconfig"])){
	$_POST["preconfig"] = '';
}

	checkRight("config", "r");
	
	$config = new Config();
	$config_mail = new AuthMail();
	$config_ldap = new AuthLDAP();
	
	switch($_POST['auth_tab']){
			
		case 1 :
				switch($_POST['next']){
					case "extauth_ldap" :
						$_SESSION['glpi_authconfig']=1;
						$config_ldap->showForm($_POST['target'], $_POST["ID"]);
						break;
					default :
						$_SESSION['glpi_authconfig']=1;
						showFormExtAuthList($_POST['target']);
						break;
				}
			break;
		case 2 :
				switch($_POST['next']){
					case "extauth_mail" :
						$_SESSION['glpi_authconfig']=2;
						$config_mail->showForm($_POST['target'], $_POST["ID"]);
						break;
					default :
						$_SESSION['glpi_authconfig']=2;
						showFormExtAuthList($_POST['target']);
						break;
				}
			break;
		case 3 :
				switch($_POST['next']){
					case "others" :
						$_SESSION['glpi_authconfig']=3;
						showFormExtAuthList($_POST['target']);
						break;
					default :
						$_SESSION['glpi_authconfig']=3;
						showFormExtAuthList($_POST['target']);
					break;
				}
			break;
		default :
			break;
	}


?>