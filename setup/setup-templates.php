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
	addTemplate($HTTP_POST_VARS);
	logEvent(0,"Templates", 5, "setup", "$IRMName added template ".$HTTP_POST_VARS["templname"].".");
	header("Location: $HTTP_REFERER?done");
} else if ($delete) {
	checkAuthentication("admin");
	deleteTemplate($HTTP_GET_VARS);
	logEvent(0,"Templates", 5, "setup", "$IRMName deleted template ".$HTTP_POST_VARS["ID"].".");
	header("Location: $HTTP_REFERER?done");
} else if ($update) {
	checkAuthentication("admin");
	updateTemplate($HTTP_POST_VARS);
	logEvent(0,"Templates", 5, "setup", "$IRMName updated template ".$HTTP_POST_VARS["ID"].".");
	header("Location: $HTTP_REFERER?done");
} else if ($showform) {
	checkAuthentication("admin");
	commonHeader("glpi Setup",$PHP_SELF);
	showTemplateForm($PHP_SELF,$ID);
	commonFooter();
} else {
	checkAuthentication("normal");
	commonHeader("glpi Setup",$PHP_SELF);
	listTemplates($PHP_SELF);
	commonFooter();
}

?>
