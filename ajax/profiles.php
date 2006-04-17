<?php
/*
 * @version $Id: autocompletion.php 2768 2006-02-24 05:15:47Z moyo $
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
	$AJAX_INCLUDE=1;
	include ($phproot."/glpi/includes.php");
	include ($phproot."/glpi/includes_profiles.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkRight("profile","r");

	if ($_POST["interface"]=="helpdesk")
		showHelpdeskProfilesForm($_POST["ID"]);
	else if ($_POST["interface"]=="central")
		showCentralProfilesForm($_POST["ID"]);
?>