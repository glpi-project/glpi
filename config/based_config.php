<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Notice problem  for date function :
$tz = ini_get('date.timezone');
if (!empty($tz)) {
   date_default_timezone_set($tz);
} else {
   date_default_timezone_set(@date_default_timezone_get());
}

// If this file exists, it is load, allow to set configdir/dumpdir elsewhere
if (file_exists(GLPI_ROOT ."/config/config_path.php")) {
   include_once(GLPI_ROOT ."/config/config_path.php");
}

// Default location for database configuration : config_db.php
if (!defined("GLPI_CONFIG_DIR")) {
   define("GLPI_CONFIG_DIR",GLPI_ROOT . "/config");
}

// Default location for backup dump
if (!defined("GLPI_DUMP_DIR")) {
   define("GLPI_DUMP_DIR",GLPI_ROOT . "/files/_dumps");
}

// Path for documents storage
if (!defined("GLPI_DOC_DIR")) {
   define("GLPI_DOC_DIR",GLPI_ROOT . "/files");
}

// Path for cron storage
if (!defined("GLPI_CRON_DIR")) {
   define("GLPI_CRON_DIR",GLPI_ROOT . "/files/_cron");
}

// Path for sessions storage
if (!defined("GLPI_SESSION_DIR")) {
   define("GLPI_SESSION_DIR",GLPI_ROOT . "/files/_sessions");
}

// Path for plugins documents storage
if (!defined("GLPI_PLUGIN_DOC_DIR")) {
   define("GLPI_PLUGIN_DOC_DIR",GLPI_ROOT . "/files/_plugins");
}
// Path for cache storage
if (!defined("GLPI_LOCK_DIR")) {
   define("GLPI_LOCK_DIR",GLPI_ROOT . "/files/_lock");
}

// Path for log storage
if (!defined("GLPI_LOG_DIR")) {
   define("GLPI_LOG_DIR",GLPI_ROOT . "/files/_log");
}

// Path for graph storage
if (!defined("GLPI_GRAPH_DIR")) {
   define("GLPI_GRAPH_DIR",GLPI_ROOT . "/files/_graphs");
}

// Path for picture storage
if (!defined("GLPI_PICTURE_DIR")) {
   define("GLPI_PICTURE_DIR",GLPI_ROOT . "/files/_pictures");
}

// Path for temp storage
if (!defined("GLPI_TMP_DIR")) {
   define("GLPI_TMP_DIR",GLPI_ROOT . "/files/_tmp");
}

// Path for rss storage
if (!defined("GLPI_RSS_DIR")) {
   define("GLPI_RSS_DIR",GLPI_ROOT . "/files/_rss");
}

// Path for upload storage
if (!defined("GLPI_UPLOAD_DIR")) {
   define("GLPI_UPLOAD_DIR",GLPI_ROOT . "/files/_uploads");
}

// Default location scripts
if (!defined("GLPI_SCRIPT_DIR")) {
   define("GLPI_SCRIPT_DIR",GLPI_ROOT . "/scripts");
}

// Default PHPMailer installation dir
if (!defined("GLPI_PHPMAILER_DIR")) {
   define("GLPI_PHPMAILER_DIR", GLPI_ROOT."/lib/phpmailer");

   # if PHPMailer installed, use (in config_path.php)
   # define("GLPI_PHPMAILER_DIR", "/usr/share/php/phpmailer");
}

// Default tcpdf installation dir
if (!defined("GLPI_TCPDF_DIR")) {
   define("GLPI_TCPDF_DIR", GLPI_ROOT."/lib/tcpdf");

   # if PHPMailer installed, use (in config_path.php)
   # define("GLPI_TCPDF_DIR", "/usr/share/php/tcpdf");
}

// Default EZ Components path to base.php
if (!defined("GLPI_EZC_BASE")) {
   //define("GLPI_EZC_BASE", GLPI_ROOT."/lib/ezcomponents/Base/src/base.php");
   define("GLPI_EZC_BASE", GLPI_ROOT."/lib/zeta/Base/src/base.php");

   # if EZ components installed as PEAR extension, use (in config_path.php)
   # define("GLPI_EZC_BASE", "ezc/Base/base.php");
}

if (!defined("GLPI_ZEND_PATH")) {
   define("GLPI_ZEND_PATH", GLPI_ROOT."/lib/Zend");

   # if Zend Framework 2 available in system, use (in config_path.php)
   # define('GLPI_ZEND_PATH', '/usr/share/php/Zend');
}

// Default SimplePie path
if (!defined("GLPI_SIMPLEPIE_PATH")) {
   define("GLPI_SIMPLEPIE_PATH", GLPI_ROOT."/lib/simplepie");

   # if SimplePie installed, use (in config_path.php)
   # define("GLPI_SIMPLEPIE_PATH", "/usr/share/php/simplepie");  // if not in standard include_path
}

// Default phpCAS installation dir
if (!defined("GLPI_PHPCAS")) {
   define("GLPI_PHPCAS", GLPI_ROOT . "/lib/phpcas/CAS.php");

   # if phpCAS installed as PEAR extension, use (in config_path.php)
   # define("GLPI_PHPCAS", "CAS.php");
}

// Default path to FreeSans.ttf
if (!defined("GLPI_FONT_FREESANS")) {
   define("GLPI_FONT_FREESANS", GLPI_ROOT . '/lib/FreeSans.ttf');

   # if FreeSans.ttf available in system, use (in config_path.php)
   # define("GLPI_FONT_FREESANS", '/usr/share/fonts/gnu-free/FreeSans.ttf');
}

// Default patch to htmLawed
if (!defined('GLPI_HTMLAWED')) {
   define('GLPI_HTMLAWED', GLPI_ROOT.'/lib/htmlawed/htmLawed.php');

   # if htmLawed available in system, use (in config_path.php)
   # define('GLPI_HTMLAWED', '/usr/share/htmlawed/htmLawed.php');
}

// Default path to password_compat
if (!defined('GLPI_PASSWORD_COMPAT')) {
   define('GLPI_PASSWORD_COMPAT', GLPI_ROOT.'/lib/password_compat/password.php');

   # if password_compat available in system, use (in config_path.php)
   # define('GLPI_PASSWORD_COMPAT', '/usr/share/php/password_compat/password.php');
}
?>
