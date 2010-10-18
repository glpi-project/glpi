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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include_once (GLPI_ROOT . "/inc/timer.class.php");

include_once (GLPI_ROOT . "/inc/common.function.php");

// Security of PHP_SELF
$_SERVER['PHP_SELF']=cleanParametersURL($_SERVER['PHP_SELF']);

function __autoload($classname) {
   global $DEBUG_AUTOLOAD;
   static $notfound = array();

   // empty classname or non concerted plugin
   if (empty($classname) || is_numeric($classname)) {
      return false;
   }

   $dir=GLPI_ROOT . "/inc/";
   //$classname="PluginExampleProfile";
   if ($plug=isPluginItemType($classname)) {
      $plugname=strtolower($plug['plugin']);
      $dir=GLPI_ROOT . "/plugins/$plugname/inc/";
      $item=strtolower($plug['class']);
      // Is the plugin activate ?
      // Command line usage of GLPI : need to do a real check plugin activation
      if (isCommandLine()) {
         $plugin = new Plugin();
         if (count($plugin->find("directory='$plugname' AND state=".Plugin::ACTIVATED)) == 0) {
            // Plugin does not exists or not activated
            return false;
         }
      } else {
         // Standard use of GLPI
         if (!in_array($plugname,$_SESSION['glpi_plugins'])) {
            // Plugin not activated
            return false;
         }
      }
   } else {
      // Is ezComponent class ?
      if (preg_match('/^ezc([A-Z][a-z]+)/',$classname,$matches)) {
         include_once(GLPI_EZC_BASE);
         ezcBase::autoload($classname);
         return true;
      } else {
         $item=strtolower($classname);
      }
   }

   if (file_exists("$dir$item.class.php")) {
      include_once ("$dir$item.class.php");
      if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
         $DEBUG_AUTOLOAD[]=$classname;
      }

   } else if (!isset($notfound["x$classname"])) {
      // trigger an error to get a backtrace, but only once (use prefix 'x' to handle empty case)
      //logInFile('debug',"file $dir$item.class.php not founded trying to load class $classname\n");
      trigger_error("GLPI autoload : file $dir$item.class.php not founded trying to load class '$classname'");
      $notfound["x$classname"] = true;
   }
}

// Init Timer to compute time of display
$TIMER_DEBUG=new Timer;
$TIMER_DEBUG->start();

/// TODO try to remove them if possible
include_once (GLPI_ROOT . "/inc/plugin.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/inc/auth.function.php");
include_once (GLPI_ROOT . "/inc/display.function.php");
include_once (GLPI_ROOT . "/inc/ajax.function.php");
include_once (GLPI_ROOT . "/inc/setup.function.php");

// Standard includes
include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
include_once (GLPI_ROOT . "/inc/commonglpi.class.php");
include_once (GLPI_ROOT . "/inc/commondbtm.class.php");
include_once (GLPI_ROOT . "/inc/commondbrelation.class.php");
include_once (GLPI_ROOT . "/config/config.php");




// Load Language file
loadLanguage();

if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
   $SQL_TOTAL_REQUEST=0;
   $DEBUG_SQL["queries"]=array();
   $DEBUG_SQL["errors"]=array();
   $DEBUG_SQL["times"]=array();
   $DEBUG_AUTOLOAD=array();
}

// Security system
if (isset($_POST)) {
   if (get_magic_quotes_gpc()) {
      $_POST = array_map('stripslashes_deep', $_POST);
   }

   $_POST = array_map('addslashes_deep', $_POST);
   $_POST = array_map('clean_cross_side_scripting_deep', $_POST);
}
if (isset($_GET)) {
   if (get_magic_quotes_gpc()) {
      $_GET = array_map('stripslashes_deep', $_GET);
   }
   $_GET = array_map('addslashes_deep', $_GET);
   $_GET = array_map('clean_cross_side_scripting_deep', $_GET);
}

// Mark if Header is loaded or not :
$HEADER_LOADED=false;
$FOOTER_LOADED=false;
if (isset($AJAX_INCLUDE)) {
   $HEADER_LOADED=true;
}

/* On startup, register all plugins configured for use. */
if (!isset($AJAX_INCLUDE) && !isset($PLUGINS_INCLUDED)) {
   // PLugin already included
   $PLUGINS_INCLUDED=1;
   $LOADED_PLUGINS=array();
   $plugin = new Plugin();
   if (!isset($_SESSION["glpi_plugins"])) {
      $plugin->init();
   }
   if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
      //doHook("config");
      if (count($_SESSION["glpi_plugins"])) {
         foreach ($_SESSION["glpi_plugins"] as $name) {
            Plugin::load($name);
         }
      }
   }
}


if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
   $_SESSION["MESSAGE_AFTER_REDIRECT"]="";
}

// Manage force tab
if (isset($_REQUEST['forcetab'])) {
   if (preg_match('/([a-zA-Z]+).form.php/',$_SERVER['PHP_SELF'],$matches)) {
      $itemtype=$matches[1];
      setActiveTab($matches[1],$_REQUEST['forcetab']);
   }
}
// Manage tabs
if (isset($_REQUEST['glpi_tab']) && isset($_REQUEST['itemtype'])) {
   setActiveTab($_REQUEST['itemtype'],$_REQUEST['glpi_tab']);
}
// Override list-limit if choosen
if (isset($_REQUEST['glpilist_limit'])) {
   $_SESSION['glpilist_limit']=$_REQUEST['glpilist_limit'];
}
?>
