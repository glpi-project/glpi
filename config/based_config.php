<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

// If this file exists, it is load, allow to set configdir/dumpdir elsewhere
if(file_exists(GLPI_ROOT ."/config/config_path.php")) {
	include_once(GLPI_ROOT ."/config/config_path.php");
}

// Default location for database configuration : config_db.php
if (!defined("GLPI_CONFIG_DIR")){
	define("GLPI_CONFIG_DIR",GLPI_ROOT . "/config");
}

// Default location for backup dump
if (!defined("GLPI_DUMP_DIR")){
	define("GLPI_DUMP_DIR",GLPI_ROOT . "/files/_dumps");
}

// Path for documents storage
if (!defined("GLPI_DOC_DIR")){
	define("GLPI_DOC_DIR",GLPI_ROOT . "/files");
}

// Path for cache storage
if (!defined("GLPI_CACHE_DIR")){
	// Need / at the end for Cache Lite compatibility
	define("GLPI_CACHE_DIR",GLPI_ROOT . "/files/_cache/");
}

// Path for cron storage
if (!defined("GLPI_CRON_DIR")){
	define("GLPI_CRON_DIR",GLPI_ROOT . "/files/_cron");
}

// Path for sessions storage
if (!defined("GLPI_SESSION_DIR")){
	define("GLPI_SESSION_DIR",GLPI_ROOT . "/files/_sessions");
}

// Path for plugins documents storage
if (!defined("GLPI_PLUGIN_DOC_DIR")){
	define("GLPI_PLUGIN_DOC_DIR",GLPI_ROOT . "/files/_plugins");
}
// Path for cache storage
if (!defined("GLPI_LOCK_DIR")){
	define("GLPI_LOCK_DIR",GLPI_ROOT . "/files/_lock");
}

// Path for log storage
if (!defined("GLPI_LOG_DIR")){
	define("GLPI_LOG_DIR",GLPI_ROOT . "/files/_log");
}

// Default location scripts
if (!defined("GLPI_SCRIPT_DIR")){
	define("GLPI_SCRIPT_DIR",GLPI_ROOT . "/scripts");
}

// Default cache_lite installation dir
if (!defined("GLPI_CACHE_LITE_DIR")){
	define("GLPI_CACHE_LITE_DIR", GLPI_ROOT."/lib/cache_lite");

	# if PEAR + Cache_Lite installed, use (in config_path.php)
	# define("GLPI_CACHE_LITE_DIR", "Cache");
}

// Default PHPMailer installation dir
if (!defined("GLPI_PHPMAILER_DIR")){
	define("GLPI_PHPMAILER_DIR", GLPI_ROOT."/lib/phpmailer");

	# if PHPMailer installed, use (in config_path.php)
	# define("GLPI_PHPMAILER_DIR", "/usr/share/php/phpmailer");
}

// Default phpCAS installation dir
if (!defined("GLPI_PHPCAS")) {
   define("GLPI_PHPCAS", GLPI_ROOT . "/lib/phpcas/CAS.php");

   # if phpCAS installed as PEAR extension, use (in config_path.php)
   # define("GLPI_PHPCAS", "CAS.php");
}
?>