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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}
// Notice problem  for date function :
if (function_exists('date_default_timezone_set')){
	$tz=ini_get('date.timezone');
	if (!empty($tz)){
		date_default_timezone_set($tz);
	} else {
		date_default_timezone_set(@date_default_timezone_get());
	}
}
include_once (GLPI_ROOT . "/inc/timer.class.php");

// Init Timer to compute time of display
$TIMER_DEBUG=new Script_Timer;
$TIMER_DEBUG->Start_Timer();

include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
include_once (GLPI_ROOT . "/inc/commondbtm.class.php");
include_once (GLPI_ROOT . "/inc/commonitem.class.php");
include_once (GLPI_ROOT . "/inc/common.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/inc/auth.function.php");
include_once (GLPI_ROOT . "/inc/display.function.php");
include_once (GLPI_ROOT . "/inc/ajax.function.php");
include_once (GLPI_ROOT . "/inc/dropdown.function.php");
include_once (GLPI_ROOT . "/inc/config.class.php");
include_once (GLPI_ROOT . "/config/config.php");
include_once (GLPI_ROOT . "/inc/plugin.function.php");

// Load Language file
loadLanguage();

if ($CFG_GLPI["debug"]){
	if ($CFG_GLPI["debug_profile"]){		
		$SQL_TOTAL_REQUEST=0;
	}
	if ($CFG_GLPI["debug_sql"]){		
		$DEBUG_SQL["queries"]=array();
		$DEBUG_SQL["errors"]=array();
		$DEBUG_SQL["times"]=array();
	}
}



if (!isset($AJAX_INCLUDE)){
	include_once (GLPI_ROOT . "/inc/auth.class.php");
	include_once (GLPI_ROOT . "/inc/connection.class.php");
	include_once (GLPI_ROOT . "/inc/connection.function.php");
	include_once (GLPI_ROOT . "/inc/mailing.class.php");
	include_once (GLPI_ROOT . "/inc/mailing.function.php");
	include_once (GLPI_ROOT . "/inc/export.function.php");
	include_once (GLPI_ROOT . "/inc/log.function.php");
	include_once (GLPI_ROOT . "/inc/bookmark.function.php");
	include_once (GLPI_ROOT . "/inc/alert.class.php");
}

// Security system
if (isset($_POST)){
	if (!get_magic_quotes_gpc()){
		$_POST = array_map('addslashes_deep', $_POST);
	}
	$_POST = array_map('clean_cross_side_scripting_deep', $_POST);
}
if (isset($_GET)){
	if (!get_magic_quotes_gpc()){
		$_GET = array_map('addslashes_deep', $_GET);
	}
	$_GET = array_map('clean_cross_side_scripting_deep', $_GET);
}



// Mark if Header is loaded or not :
$HEADER_LOADED=false;
$FOOTER_LOADED=false;
if (isset($AJAX_INCLUDE)){
	$HEADER_LOADED=true;
}

if (isset($NEEDED_ITEMS)&&is_array($NEEDED_ITEMS)){
	foreach ($NEEDED_ITEMS as $item){
		if (file_exists(GLPI_ROOT . "/inc/$item.class.php")){
			include_once (GLPI_ROOT . "/inc/$item.class.php");
		}
		if (file_exists(GLPI_ROOT . "/inc/$item.function.php")){
			include_once (GLPI_ROOT . "/inc/$item.function.php");
		}
	}
}

/* On startup, register all plugins configured for use. */
if (!isset($AJAX_INCLUDE)&&!isset($PLUGINS_INCLUDED)){
	// PLugin already included
	$PLUGINS_INCLUDED=1;

	if (!isset($_SESSION["glpi_plugins"])) {
		initPlugins();
	}

	if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
		//doHook("config");

		if (count($_SESSION["glpi_plugins"])){
			foreach ($_SESSION["glpi_plugins"] as $name) {

				if (isset($_SESSION["glpilanguage"])&&file_exists(GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][1]))
					include_once (GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][1]);
				else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$CFG_GLPI["default_language"]][1]))
					include_once (GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$CFG_GLPI["default_language"]][1]);
				else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/en_GB.php"))
					include_once (GLPI_ROOT . "/plugins/$name/locales/en_GB.php");
				else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/fr_FR.php"))
					include_once (GLPI_ROOT . "/plugins/$name/locales/fr_FR.php");

				usePlugin($name);

			}
		}
	}
}

// Get search_option array / need to be included after plugin definition
if (isset($NEEDED_ITEMS)&&in_array('search', $NEEDED_ITEMS)){
	$SEARCH_OPTION=getSearchOptions();
}

if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) $_SESSION["MESSAGE_AFTER_REDIRECT"]="";

// Manage tabs
if (!isset($_SESSION['glpi_tab'])) $_SESSION['glpi_tab']=1;
if (isset($_GET['glpi_tab'])) {
	$_SESSION['glpi_tab']=$_GET['glpi_tab'];
}

?>
