<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

	define('GLPI_ROOT','..');

	$AJAX_INCLUDE=1;
	$NEEDED_ITEMS=array("software");
	include (GLPI_ROOT."/inc/includes.php");
	
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
	
	checkRight("software","w");
	
	switch ($_POST["type"]){
		case "update_buy"	:
			dropdownYesNo("buy");
			echo "&nbsp;&nbsp;<input type='submit' name='update_buy' value='".$LANG["buttons"][14]."' class='submit'>";
		break;
		case "update_expire" :
			echo "<table><tr><td>";
			showDateFormItem("expire");
			echo "</td></td>";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='update_expire' value='".$LANG["buttons"][14]."' class='submit'>";
			echo "</td></tr></table>";
		break;
		case "move":
			// TODO : check this ? obsoleted function call
			dropdownLicenseOfSoftware("lID",$_POST["sID"]);
			echo "&nbsp;&nbsp;<input type='submit' name='move' value='".$LANG["buttons"][14]."' class='submit'>";
		break;
		case "delete_similar_license":
			echo "&nbsp;&nbsp;<input type='submit' name='delete_similar_license' value='".$LANG["buttons"][6]."' class='submit'>"; 
		break;
		case "delete_license": 
			echo "&nbsp;&nbsp;<input type='submit' name='delete_license' value='".$LANG["buttons"][2]."' class='submit'>";
		break;
		case "uninstall_license": 
			echo "<input type='submit' name='uninstall_license' value='".$LANG["buttons"][2]."' class='submit'>";
		break;
		case "move_to_software":
			$soft=new Software();
			$soft->getFromDB($_POST["sID"]);
			dropdownValue("glpi_software","sID",0,1,$soft->fields['FK_entities']);
			echo "&nbsp;&nbsp;<input type='submit' name='move_to_software' value='".$LANG["buttons"][14]."' class='submit'>";
		break;
		
	}

?>
