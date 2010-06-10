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
	addUser($HTTP_POST_VARS);
	logEvent(0, "users", 4, "setup", "$IRMName added user ".$HTTP_POST_VARS["name"].".");
	header("Location: $HTTP_REFERER?done");
} else if ($delete) {
	checkAuthentication("admin");
	deleteUser($HTTP_POST_VARS);
	logEvent(0,"users", 4, "setup", "$IRMName deleted user ".$HTTP_POST_VARS["name"].".");
	header("Location: $HTTP_REFERER?done");
} else if ($update) {
	checkAuthentication("admin");
	updateUser($HTTP_POST_VARS);
	logEvent(0,"users", 5, "setup", "$IRMName updated user ".$HTTP_POST_VARS["name"].".");
	header("Location: $HTTP_REFERER?done");
} else {
	checkAuthentication("normal");
	commonHeader("Setup",$PHP_SELF);
	echo "<center><table cellpadding=4><tr><th>".$lang["setup"][2].":</th></tr></table></center>";
	listUsersForm($PHP_SELF);
	if (can_assign_job($IRMName)) {
	 echo "<center><strong><a href='setup-assign-job.php'>".$lang["setup"][59]."</a></strong></center>";
	}
	commonFooter();
}

?>
