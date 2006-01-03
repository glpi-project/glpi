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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

	include ("_relpos.php");
	include ($phproot."/glpi/includes.php");
	checkAuthentication("normal");
	include ($phproot."/glpi/includes_search.php");


	header("Content-Type: text/html; charset=UTF-8");

if ($_POST["type"]>0){
	echo "<input type='text' size='15' name=\"contains2[".$_POST["num"]."]\" value=\"".$_POST["val"]."\" >";
	echo "&nbsp;";
	echo $lang["search"][10]."&nbsp;";

	echo "<select name=\"field2[".$_POST["num"]."]\" size='1'>";

	foreach ($SEARCH_OPTION[$_POST["type"]] as $key => $val) 
	if ($val["meta"])
	{
			echo "<option value=\"".$key."\""; 
			if($key == $_POST["field"]) echo "selected";
			echo ">". $val["name"] ."</option>\n";
	}
	echo "</select>&nbsp;";
}
?>
