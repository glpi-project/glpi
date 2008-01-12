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
// Original Author of file: 
// Purpose of file:
// ----------------------------------------------------------------------



$NEEDED_ITEMS=array("stat","tracking");


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG["Menu"][13],$_SERVER['PHP_SELF'],"maintain","stat");

checkRight("statistic","1");

//Affichage du tableau de pr�entation des stats
echo "<table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$LANG["stats"][0].":</th></tr>";


echo  "<tr class='tab_bg_1'><td align='center'><a href=\"stat.global.php\"><b>".$LANG["stats"][1]."</b></a></td></tr>";
echo  "<tr class='tab_bg_1'><td align='center'><a href=\"stat.tracking.php\"><b>".$LANG["stats"][47]."</b></a></td></tr>";
echo  "<tr class='tab_bg_1'><td align='center'><a href=\"stat.location.php\"><b>".$LANG["stats"][3]."</b></a><br> (".$LANG["common"][15]
.", ".$LANG["common"][17]	= "Type".", ".$LANG["computers"][9]	= "OS".", ".$LANG["computers"][21].", ".$LANG["computers"][36]
.", ".$LANG["devices"][2].", ".$LANG["devices"][5].")</td></tr>";
echo  "<tr class='tab_bg_1'><td align='center'><a href=\"stat.item.php\"><b>".$LANG["stats"][45]."</b></a></td></tr>";


echo "</table>";

commonFooter();
?>
