<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

if ($add) {
	checkAuthentication("admin");
	addDropdown($HTTP_POST_VARS);
	logEvent(0, "dropdowns", 5, "setup", "$IRMName added a value to a dropdown.");
	header("Location: $HTTP_REFERER?done");
} else if ($delete) {
	checkAuthentication("admin");
	deleteDropdown($HTTP_POST_VARS);
	logEvent($HTTP_POST_VARS["ID"], "templates", 4, "inventory", "$IRMName deleted a dropdown value.");
	header("Location: $HTTP_REFERER?done");
} else {
	checkAuthentication("normal");
	commonHeader("Setup",$PHP_SELF);
	echo "<center><table cellpadding=4><tr><th>".$lang["setup"][0].":</th></tr></table></center>";
	showFormDropDown($PHP_SELF,"locations",$lang["setup"][3]);
	showFormTypeDown($PHP_SELF,"computers",$lang["setup"][4]);
	showFormTypeDown($PHP_SELF,"networking",$lang["setup"][42]);
	showFormTypeDown($PHP_SELF,"printers",$lang["setup"][43]);
	showFormTypeDown($PHP_SELF,"monitors",$lang["setup"][44]);
	showFormDropDown($PHP_SELF,"os",$lang["setup"][5]);
	showFormDropDown($PHP_SELF,"ram",$lang["setup"][6]);
	showFormDropDown($PHP_SELF,"processor",$lang["setup"][7]);
	showFormDropDown($PHP_SELF,"moboard",$lang["setup"][45]);
	showFormDropDown($PHP_SELF,"gfxcard",$lang["setup"][46]);
	showFormDropDown($PHP_SELF,"sndcard",$lang["setup"][47]);
	showFormDropDown($PHP_SELF,"hdtype",$lang["setup"][48]);
	showFormDropDown($PHP_SELF,"network",$lang["setup"][8]);
	showFormDropDown($PHP_SELF,"iface",$lang["setup"][9]);
	commonFooter();
}


?>
