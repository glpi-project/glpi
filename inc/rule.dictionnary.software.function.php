<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

function showWarningPageBeforeProcessingDictionnary($target)
{
	global $LANG,$CFG_GLPI;
	echo "<form name='testrule_form' id='softdictionnary_confirmation' method='post' action=\"".$target."\">\n";
	echo "<div class='center'>"; 
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'><strong>" .$LANG["rulesengine"][92]. "</strong></th</tr>";
	echo "<tr><td align='center' class='tab_bg_2'>"; 
	echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning2.png\"></td>";
	echo "<td align='center' class='tab_bg_2'>".$LANG["rulesengine"][93]. "</td></tr>\n";
	echo "<tr><th colspan='2'><strong>" .$LANG["rulesengine"][95]. "</strong></th</tr>";
	echo "<tr><td align='center' class='tab_bg_2'>".$LANG["rulesengine"][96]."</td>"; 
	echo "<td align='center' class='tab_bg_2'>"; 
	dropdownValue("glpi_dropdown_manufacturer","manufacturer");
	echo"</td></tr>\n";

	echo "<tr><td align='center' class='tab_bg_2' colspan='2'><input type='submit' name='replay_rule_process' value=\"" . $LANG["buttons"][2] . "\" class='submit'></td></tr>";
	echo "</table>";
	echo "</div></form>";
}
?>
