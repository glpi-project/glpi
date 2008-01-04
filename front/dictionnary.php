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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("rulesengine","affectentity");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG["title"][2],$_SERVER['PHP_SELF'],"admin","dictionnary",-1);

	echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
	echo "<tr class='tab_bg_1'><th colspan='4'>" . $LANG["rulesengine"][77] . "</th></tr>";

	echo "<tr class='tab_bg_1'><td valign='top'><table class='tab_cadre' cellpadding='5'>";
	echo "<tr class='tab_bg_1'><th>".$LANG["rulesengine"][80]."</th></tr>";	
	
	if (haveRight("rule_dictionnary_software","r")){
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.software.php\"><strong>" . $LANG["rulesengine"][35] . "</strong></a></td></tr>";
	}
	if (haveRight("rule_dictionnary_manufacturer","r")){
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.manufacturer.php\"><strong>" . $LANG["rulesengine"][36] . "</strong></a></td></tr>";
	}

	echo "</table></td>";

	echo "<td valign='top'><table class='tab_cadre' cellpadding='5'>";
	if (haveRight("rule_dictionnary_model","r")){
		echo "<tr class='tab_bg_1'><th>".$LANG["rulesengine"][56]."</th></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.model.computer.php\"><strong>" . $LANG["rulesengine"][50] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.model.monitor.php\"><strong>" . $LANG["rulesengine"][51] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.model.printer.php\"><strong>" . $LANG["rulesengine"][54] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.model.peripheral.php\"><strong>" . $LANG["rulesengine"][53] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.model.networking.php\"><strong>" . $LANG["rulesengine"][55] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.model.phone.php\"><strong>" . $LANG["rulesengine"][52] . "</strong></a></td></tr>";
	}
	echo "</table></td>";

	echo "<td valign='top'><table class='tab_cadre' cellpadding='5'>";
	if (haveRight("rule_dictionnary_type","r")){
		echo "<tr class='tab_bg_1'><th>".$LANG["rulesengine"][66]."</th></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.type.computer.php\"><strong>" . $LANG["rulesengine"][60] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.type.monitor.php\"><strong>" . $LANG["rulesengine"][61] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.type.printer.php\"><strong>" . $LANG["rulesengine"][64] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.type.peripheral.php\"><strong>" . $LANG["rulesengine"][63] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.type.networking.php\"><strong>" . $LANG["rulesengine"][65] . "</strong></a></td></tr>";		
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.type.phone.php\"><strong>" . $LANG["rulesengine"][62] . "</strong></a></td></tr>";		
	}
	echo "</table></td>";

	echo "<td valign='top'><table class='tab_cadre' cellpadding='5'>";
	if (haveRight("rule_dictionnary_os","r")){
		echo "<tr class='tab_bg_1'><th>".$LANG["computers"][9]."</th></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.os.php\"><strong>" . $LANG["rulesengine"][67] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.os_sp.php\"><strong>" . $LANG["rulesengine"][68] . "</strong></a></td></tr>";
		echo "<tr class='tab_bg_1'><td  align='center'><a href=\"rule.dictionnary.os_version.php\"><strong>" . $LANG["rulesengine"][69] . "</strong></a></td></tr>";
	}
	echo "</table></td></tr>";

	echo "</table></div>";
commonFooter();
?>
