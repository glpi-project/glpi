<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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

// Original Author of file: Bazile Lebeau :wq
// Purpose of file:
// ----------------------------------------------------------------------
include ("_relpos.php");
include ($phproot."/glpi/includes.php");
include ($phproot."/plugins/ocs/functions/functions.php");
checkAuthentication("admin");
include($phproot."/plugins/ocs/dicts/".$_SESSION["glpilanguage"]."Ocs.php");
commonHeader($langOcs["title"][0],$_SERVER["PHP_SELF"]);
	echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
	echo "<tr><th colspan=\"1\">".$lanOcs["import"][0]."</th></tr>";
	echo "<tr class='tab_bg_1'><td align='center'><a href=\"importall.php\">".$lanOcs["import"][1]."</a></td></tr>";
	#echo "<tr class='tab_bg_1'><td align='center'><a href=\"importslow.php\">".$lanOcs["import"][2]."</a></td></tr>";
	echo "</table></div>";
commonFooter();
?>