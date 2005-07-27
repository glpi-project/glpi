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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_users.php");

@session_start();

if (!isset($_SESSION["noCAS"])&&!empty($cfg_login['cas']['host'])) {
	include ($phproot . "/glpi/CAS/CAS.php");
	phpCAS::client(CAS_VERSION_2_0,$cfg_login['cas']['host'],intval($cfg_login['cas']['port']),$cfg_login['cas']['uri']);
	phpCAS::logout();
}

$noCAS="";
if (isset($_SESSION["noCAS"])) $noCAS="?noCAS=1";





$id = new Identification('bogus');
$id->eraseCookies();


// Redirect to the login-page

glpi_header($cfg_install["root"]."/".$noCAS);
?>
