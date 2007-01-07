<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
include ("_relpos.php");

include_once ($phproot . "/inc/timer.class.php");

// Init Timer to compute time of display
$TIMER_DEBUG=new Script_Timer;
$TIMER_DEBUG->Start_Timer();

include_once ($phproot . "/inc/dbmysql.class.php");
include_once ($phproot . "/inc/commondbtm.class.php");
include_once ($phproot . "/inc/commonitem.class.php");
include_once ($phproot . "/inc/common.function.php");
include_once ($phproot . "/inc/auth.function.php");
include_once ($phproot . "/inc/display.function.php");
include_once ($phproot . "/inc/dropdown.function.php");
include_once ($phproot . "/inc/config.class.php");
include_once ($phproot . "/config/config.php");

session_save_path($cfg_glpi["doc_dir"]."/_sessions");
if(!session_id()){@session_start();}

// Override cfg_features by session value
if (isset($_SESSION['glpilist_limit'])) $cfg_glpi["list_limit"]=$_SESSION['glpilist_limit'];


// Load Language file
loadLanguage();

if ($cfg_glpi["debug"]){
	if ($cfg_glpi["debug_profile"]){		
		$SQL_TOTAL_TIMER=0;
		$SQL_TOTAL_REQUEST=0;
	}
	if ($cfg_glpi["debug_sql"]){		
		$DEBUG_SQL_STRING="";
	}
}

include_once ($phproot . "/inc/db.function.php");

if (!isset($AJAX_INCLUDE)){

	include_once ($phproot . "/inc/auth.class.php");
	include_once ($phproot . "/inc/connection.class.php");
	include_once ($phproot . "/inc/mailing.class.php");
	include_once ($phproot . "/inc/mailing.function.php");
	include_once ($phproot . "/inc/report.function.php");
	include_once ($phproot . "/inc/export.function.php");
	include_once ($phproot . "/inc/log.function.php");
	include_once ($phproot . "/inc/connection.function.php");
	include_once ($phproot . "/inc/plugin.function.php");
}

// Security system
	if (isset($_POST)){
		if (!get_magic_quotes_gpc())
			$_POST = array_map('addslashes_deep', $_POST);
		$_POST = array_map('clean_cross_side_scripting_deep', $_POST);
	}
	if (isset($_GET)){
		if (!get_magic_quotes_gpc())
			$_GET = array_map('addslashes_deep', $_GET);
		$_GET = array_map('clean_cross_side_scripting_deep', $_GET);
	}


/* On startup, register all plugins configured for use. */
if (!isset($AJAX_INCLUDE)){
	if (!isset($_SESSION["glpi_plugins"])) initPlugins();

	if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
		do_hook("config");

		if (count($_SESSION["glpi_plugins"]))
			foreach ($_SESSION["glpi_plugins"] as $name) {
				use_plugin($name);

				if (isset($_SESSION["glpilanguage"])&&file_exists($phproot . "/plugins/$name/locales/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][1]))
					include_once ($phproot . "/plugins/$name/locales/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][1]);
				else if (file_exists($phproot . "/plugins/$name/locales/".$cfg_glpi["languages"][$cfg_glpi["default_language"]][1]))
					include_once ($phproot . "/plugins/$name/locales/".$cfg_glpi["languages"][$cfg_glpi["default_language"]][1]);
				else if (file_exists($phproot . "/plugins/$name/locales/en_GB.php"))
					include_once ($phproot . "/plugins/$name/locales/en_GB.php");
				else if (file_exists($phproot . "/plugins/$name/locales/fr_FR.php"))
					include_once ($phproot . "/plugins/$name/locales/fr_FR.php");
			}
	}
}

// Mark if Header is loaded or not :
$HEADER_LOADED=false;
$FOOTER_LOADED=false;
if (isset($AJAX_INCLUDE))
	$HEADER_LOADED=true;;

	if (isset($NEEDED_ITEMS)&&is_array($NEEDED_ITEMS)){
		foreach ($NEEDED_ITEMS as $item){
			if (file_exists($phproot . "/inc/$item.class.php"))
				include_once ($phproot . "/inc/$item.class.php");
			if (file_exists($phproot . "/inc/$item.function.php"))
				include_once ($phproot . "/inc/$item.function.php");
			if ($item=="ocsng"&&$cfg_glpi["ocs_mode"]&&isset($USE_OCSNGDB))
				$dbocs=new DBocs;
		}
	}

if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"]="";

?>
