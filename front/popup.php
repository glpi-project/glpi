<?php
/*
 * @version $Id: HEADER 3794 2006-08-22 03:47:26Z moyo $
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
include ("_relpos.php");
$NEEDED_ITEMS=array("setup");
include ($phproot . "/inc/includes.php");

checkLoginUser();

if (isset($_GET["popup"])) $_SESSION["glpipopup"]=$_GET["popup"];

if (isset($_SESSION["glpipopup"])){
	switch ($_SESSION["glpipopup"]){
		case "dropdown":
			if (isset($_POST["add"])||isset($_POST["delete"])||isset($_POST["several_add"])||isset($_POST["move"])||isset($_POST["update"])){
				echo "<script type='text/javascript' >\n";
				echo "window.opener.location.reload();";
				echo "</script>";
			}

			include "setup.dropdowns.php";
			break;
	}
}

?>