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
include ($phproot . "/glpi/includes_networking.php");

if ($add) {
	checkAuthentication("admin");
	addNetport($HTTP_POST_VARS);
	logEvent(0, "networking", 5, "inventory", "$IRMName added networking port.");
//	header("Location: ".$cfg_install["root"]."/networking/");
	header("Location: $HTTP_REFERER");
} else if ($delete) {
	checkAuthentication("admin");
	deleteNetport($HTTP_POST_VARS);
	logEvent(0, "networking", 5, "inventory", "$IRMName deleted networking port.");
	header("Location: ".$cfg_install["root"]."/networking/");
} else if ($update) {
	checkAuthentication("admin");
	updateNetport($HTTP_POST_VARS);
	commonHeader("Networking",$PHP_SELF);
	showNetportForm($PHP_SELF,$ID,$ondevice,$devtype);
	commonFooter();
} else {
	checkAuthentication("normal");
	commonHeader("Networking",$PHP_SELF);
	showNetportForm($PHP_SELF,$ID,$ondevice,$devtype);
	commonFooter();
}

?>
