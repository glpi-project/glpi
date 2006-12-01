<?php
/*
 * @version $Id$
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
$USE_OCSNGDB=1;
$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","state","tracking","enterprise");
include ($phproot."/inc/includes.php");

checkRight("ocsng","w");

commonHeader($lang["title"][39],$_SERVER['PHP_SELF']);
if (isset($_SESSION["ocs_import"])) unset($_SESSION["ocs_import"]);
if (isset($_SESSION["ocs_link"])) unset($_SESSION["ocs_link"]);
if (isset($_SESSION["ocs_update"])) unset($_SESSION["ocs_update"]);
echo "<div align='center'><table border='0'><tr><td>";
echo "<img src=\"".$HTMLRel."/pics/logoOcs.png\" alt='".$lang["ocsng"][0]."' title='".$lang["ocsng"][0]."' ></td>";
echo "</tr></table></div>";

echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$lang["ocsng"][0]."</th></tr>";

echo "<tr class='tab_bg_1'><td  align='center'><a href=\"ocsng.sync.php\"><b>".$lang["ocsng"][1]."</b></a></td></tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"ocsng.import.php\"><b>".$lang["ocsng"][2]."</b></a></td> </tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"ocsng.link.php\"><b>".$lang["ocsng"][4]."</b></a></td> </tr>";

echo "</table></div>";

ocsManageDeleted();
ocsCleanLinks();

commonFooter();

?>
