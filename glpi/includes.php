<?php
/*
 * @version $Id$
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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

if(!session_id()){@session_start();}

include ($phproot . "/glpi/common/Timer.php");
include ($phproot . "/glpi/common/classes.php");
include ($phproot . "/glpi/common/functions.php");
include ($phproot . "/glpi/common/functions_auth.php");
include ($phproot . "/glpi/common/functions_display.php");
include ($phproot . "/glpi/common/functions_dropdown.php");
include ($phproot . "/glpi/config/config.php");

// Load Language file
loadLanguage();

$TIMER_DEBUG=new Script_Timer;
$TIMER_DEBUG->Start_Timer();

if ($cfg_glpi["debug"]){
	if ($cfg_glpi["debug_profile"]){		
		$SQL_TOTAL_TIMER=0;
		$SQL_TOTAL_REQUEST=0;
	}
	if ($cfg_glpi["debug_sql"]){		
		$DEBUG_SQL_STRING="";
	}
}

include ($phproot . "/glpi/common/functions_db.php");

if (!isset($AJAX_INCLUDE)){

	include ($phproot . "/glpi/common/classes_auth.php");
	include ($phproot . "/glpi/common/classes_connection.php");
	include ($phproot . "/glpi/common/classes_mailing.php");
	include ($phproot . "/glpi/common/functions_reports.php");
	include ($phproot . "/glpi/common/functions_search.php");
	include ($phproot . "/glpi/common/functions_export.php");
	include ($phproot . "/glpi/common/functions_logs.php");
	include ($phproot . "/glpi/common/functions_connection.php");
	include ($phproot . "/glpi/common/functions_plugins.php");
}

$db=new DB();


	// Security system
	if (get_magic_quotes_gpc()) {
		if (isset($_POST)){
			$_POST = array_map('stripslashes_deep', $_POST);
		}
		if (isset($_GET)){
			$_GET = array_map('stripslashes_deep', $_GET);
		}
	}    
	if (isset($_POST)){
		$_POST = array_map('addslashes_deep', $_POST);
		$_POST = array_map('clean_cross_side_scripting_deep', $_POST);
	}
	if (isset($_GET)){
		$_GET = array_map('addslashes_deep', $_GET);
		$_GET = array_map('clean_cross_side_scripting_deep', $_GET);
	}


/* On startup, register all plugins configured for use. */
if (!isset($AJAX_INCLUDE))
if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
	do_hook("config");

	foreach ($_SESSION["glpi_plugins"] as $name) {
		use_plugin($name);
	
		if (file_exists($phproot . "/plugins/$name/dicts/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][1]))
			include ($phproot . "/plugins/$name/dicts/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][1]);
		else if (file_exists($phproot . "/plugins/$name/dicts/".$cfg_glpi["languages"][$cfg_glpi["default_language"]][1]))
			include ($phproot . "/plugins/$name/dicts/".$cfg_glpi["languages"][$cfg_glpi["default_language"]][1]);
		else if (file_exists($phproot . "/plugins/$name/dicts/en_GB.php"))
			include ($phproot . "/plugins/$name/dicts/en_GB.php");
		else if (file_exists($phproot . "/plugins/$name/dicts/fr_FR.php"))
			include ($phproot . "/plugins/$name/dicts/fr_FR.php");
	}
}

// Mark if Header is loaded or not :
$HEADER_LOADED=false;
if (isset($AJAX_INCLUDE))
	$HEADER_LOADED=true;;


?>
