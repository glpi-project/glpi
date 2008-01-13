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

define('GLPI_ROOT', '.');
$NEEDED_ITEMS=array("user");
include (GLPI_ROOT . "/inc/includes.php");

//@session_start();

if (!isset($_SESSION["noAUTO"])&&$_SESSION["glpiauth_method"]==AUTH_CAS) {
	include (GLPI_ROOT . "/lib/phpcas/CAS.php");
	$cas=new phpCAS();
	$cas->client(CAS_VERSION_2_0,$CFG_GLPI["cas_host"],intval($CFG_GLPI["cas_port"]),$CFG_GLPI["cas_uri"]);
	$cas->logout($CFG_GLPI["cas_logout"]);
}

$noAUTO="";
if (isset($_SESSION["noAUTO"])) {
	$noAUTO="?noAUTO=1";
}

$id = new Identification();
$id->destroySession();

// Redirect to the login-page

glpi_header($CFG_GLPI["root_doc"]."/".$noAUTO);
?>
