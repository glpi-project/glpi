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
   define('GLPI_ROOT', dirname(__DIR__));
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");

// Init Timer to compute time of display
$TIMER_DEBUG = new Timer();
$TIMER_DEBUG->start();

foreach (array('glpi_table_of', 'glpi_foreign_key_field_of') as $session_array_fields) {
   if (!isset($_SESSION[$session_array_fields])) {
      $_SESSION[$session_array_fields] = array();
   }
}

/// TODO try to remove them if possible
include_once (GLPI_ROOT . "/inc/db.function.php");

// Standard includes
include_once (GLPI_ROOT . "/config/config.php");


// Security of PHP_SELF
$_SERVER['PHP_SELF'] = Html::cleanParametersURL($_SERVER['PHP_SELF']);




// Load Language file
Session::loadLanguage();

if (isset($_SESSION['glpi_use_mode'])
    && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)) {
   $SQL_TOTAL_REQUEST    = 0;
   $DEBUG_SQL["queries"] = array();
   $DEBUG_SQL["errors"]  = array();
   $DEBUG_SQL["times"]   = array();
   $DEBUG_AUTOLOAD       = array();
}

// Security system
if (isset($_POST)) {
   if (isset($_POST['_glpi_simple_form'])) {
      $_POST = array_map('urldecode', $_POST);
   }
   $_POST = array_map(array('Toolbox','addslashes_deep'), $_POST);
   $_POST = array_map(array('Toolbox', 'clean_cross_side_scripting_deep'), $_POST);
}
if (isset($_GET)) {
   $_GET = array_map(array('Toolbox','addslashes_deep'), $_GET);
   $_GET = array_map(array('Toolbox', 'clean_cross_side_scripting_deep'), $_GET);
}
if (isset($_REQUEST)) {
   $_REQUEST = array_map(array('Toolbox','addslashes_deep'), $_REQUEST);
   $_REQUEST = array_map(array('Toolbox', 'clean_cross_side_scripting_deep'), $_REQUEST);
}
if (isset($_FILES)) {
   $_FILES = array_map(array('Toolbox','addslashes_deep'), $_FILES);
   $_FILES = array_map(array('Toolbox', 'clean_cross_side_scripting_deep'), $_FILES);
}

// Mark if Header is loaded or not :
$HEADER_LOADED = false;
$FOOTER_LOADED = false;
if (isset($AJAX_INCLUDE)) {
   $HEADER_LOADED = true;
}

/* On startup, register all plugins configured for use. */
if (!isset($AJAX_INCLUDE) && !isset($PLUGINS_INCLUDED)) {
   // PLugin already included
   $PLUGINS_INCLUDED = 1;
   $LOADED_PLUGINS   = array();
   $plugin           = new Plugin();
   if (!isset($_SESSION["glpi_plugins"])) {
      $plugin->init();
   }
   if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
      //Plugin::doHook("config");
      if (count($_SESSION["glpi_plugins"])) {
         foreach ($_SESSION["glpi_plugins"] as $name) {
            Plugin::load($name);
         }
      }
      // For plugins which require action after all plugin init
      Plugin::doHook("post_init");
   }
}


if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
   $_SESSION["MESSAGE_AFTER_REDIRECT"]="";
}

// Manage force tab
if (isset($_REQUEST['forcetab'])) {
   if (preg_match('/\/plugins\/([a-zA-Z]+)\/front\/([a-zA-Z]+).form.php/',$_SERVER['PHP_SELF'],$matches)) {
      $itemtype = 'plugin'.$matches[1].$matches[2];
      Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
   } else if (preg_match('/([a-zA-Z]+).form.php/',$_SERVER['PHP_SELF'],$matches)) {
      $itemtype = $matches[1];
      Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
   } else if (preg_match('/\/plugins\/([a-zA-Z]+)\/front\/([a-zA-Z]+).php/',$_SERVER['PHP_SELF'],$matches)) {
      $itemtype = 'plugin'.$matches[1].$matches[2];
      Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
   } else if (preg_match('/([a-zA-Z]+).php/',$_SERVER['PHP_SELF'],$matches)) {
      $itemtype = $matches[1];
      Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
   }
}
// Manage tabs
if (isset($_REQUEST['glpi_tab']) && isset($_REQUEST['itemtype'])) {
   Session::setActiveTab($_REQUEST['itemtype'], $_REQUEST['glpi_tab']);
}
// Override list-limit if choosen
if (isset($_REQUEST['glpilist_limit'])) {
   $_SESSION['glpilist_limit'] = $_REQUEST['glpilist_limit'];
}

// Security : Check HTTP_REFERRER : need to be in GLPI.
if (!defined('DO_NOT_CHECK_HTTP_REFERER')
    && !isCommandLine()
    && isset($_POST) && is_array($_POST) && count($_POST)) {
   Toolbox::checkValidReferer();
}

// Security : check CSRF token
if (GLPI_USE_CSRF_CHECK
    && isset($_POST) && is_array($_POST) && count($_POST)
    // ALL plugins need to be CSRF compliant
    /*&& Plugin::isAllPluginsCSRFCompliant()*/) {
   // No ajax pages
   if (!preg_match(':'.$CFG_GLPI['root_doc'].'(/plugins/[^/]*|)/ajax/:', $_SERVER['REQUEST_URI'])) {
      Session::checkCSRF($_POST);
   }
}
// SET new global Token
$CURRENTCSRFTOKEN = '';
?>
