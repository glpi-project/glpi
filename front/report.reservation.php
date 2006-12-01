<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("reservation","computer","printer","monitor","peripheral","networking","software","phone");
include ($phproot . "/inc/includes.php");

checkRight("reports","r");

commonHeader($lang["title"][16],$_SERVER['PHP_SELF']);

if (!isset($_GET["ID"])) $_GET["ID"]=0;

echo "<div align='center'><form method=\"get\" name=\"form\" action=\"report.reservation.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td rowspan='2' align='center'>";
dropdownUsers("ID",$_GET["ID"],"reservation_helpdesk");
echo "</td>";
echo "<td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "</table></form></div>";

if ($_GET["ID"]>0)
showUserReservations($_SERVER['PHP_SELF'],$_GET["ID"]);

commonFooter();

?>
