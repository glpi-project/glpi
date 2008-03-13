<?php
/*
 * @version $Id$
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


if(ereg("private_public.php",$_SERVER['PHP_SELF'])){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};
if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
	}
	
	if (isset($_POST['private'])){
		checkLoginUser();
		switch ($_POST['private']){
			case true :
				echo "<input type='hidden' name='private' value='1'>\n";
				echo "<input type='hidden' name='FK_entities' value='-1'>\n";
				echo "<input type='hidden' name='recursive' value='0'>\n";
				echo $LANG["common"][77]. " - ";

				echo "<a onClick='setPublic".$_POST['rand']."()'>".$LANG["common"][78]."</a>";
				break;
			case false :
				echo "<input type='hidden' name='private' value='0'>\n";
				echo $LANG["common"][76].":&nbsp;";
				dropdownValue('glpi_entities',"FK_entities",$_POST["FK_entities"]);
				echo "&nbsp;+&nbsp;".$LANG["entity"][9].":&nbsp;";
				dropdownYesNo('recursive',$_POST["recursive"]);

				echo " - ";

				echo "<a onClick='setPrivate".$_POST['rand']."()'>".$LANG["common"][79]."</a>";
				
				break;
		}
	}
?>
