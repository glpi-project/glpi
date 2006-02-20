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
include ($phproot."/glpi/includes.php");
include ($phproot."/glpi/includes_ocsng.php");
include ($phproot."/glpi/includes_computers.php");
include ($phproot."/glpi/includes_financial.php");
include ($phproot."/glpi/includes_devices.php");
include ($phproot."/glpi/includes_networking.php");
include ($phproot."/glpi/includes_monitors.php");
include ($phproot."/glpi/includes_peripherals.php");
include ($phproot."/glpi/includes_printers.php");
include ($phproot."/glpi/includes_software.php");
include ($phproot."/glpi/includes_tracking.php");

checkAuthentication("admin");

commonHeader($lang["title"][39],$_SERVER["PHP_SELF"]);

if (!isset($_POST["update_ok"])){
if (!isset($_GET['check'])) $_GET['check']='all';
if (!isset($_GET['start'])) $_GET['start']=0;

ocsCleanLinks();
ocsShowUpdateComputer($_GET['check'],$_GET['start']);

} else {
	if (count($_POST['toupdate'])>0){
		foreach ($_POST['toupdate'] as $key => $val){
			if ($val=="on")	ocsUpdateComputer($key,2);
		}
	}

echo "<div align='center'><strong>".$lang["ocsng"][8]."<br>";
echo "<a href='".$_SERVER['PHP_SELF']."'>".$lang["buttons"][13]."</a>";
echo "</strong></div>";
	
//glpi_header($_SERVER['HTTP_REFERER']);
}


commonFooter();

?>
