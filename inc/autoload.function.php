<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include_once (GLPI_ROOT."/config/based_config.php");
include_once (GLPI_ROOT."/config/define.php");


/// TODO delete when PHP comptibility set > 5.3
// PHP_VERSION_ID is available as of PHP 5.2.7, if our
// version is lower than that, then emulate it
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}


/**
 * Is the script launch in Command line ?
 *
 * @return boolean
**/
function isCommandLine() {
   return (!isset($_SERVER["SERVER_NAME"]));
}


/**
 * Determine if an object name is a plugin one
 *
 * @param $classname class name to analyze
 *
 * @return false or an object containing plugin name and class name
**/
function isPluginItemType($classname) {

   if (preg_match("/Plugin([A-Z][a-z0-9]+)([A-Z]\w+)/",$classname,$matches)) {
      $plug = array();
      $plug['plugin'] = $matches[1];
      $plug['class']  = $matches[2];
      return $plug;
   }
   // Standard case
   return false;
}


function glpi_autoload($classname) {
   global $DEBUG_AUTOLOAD, $CFG_GLPI;
   static $notfound = array('xStates' => true);

   // empty classname or non concerted plugin or classname containing dot (leaving GLPI main treee)
   if (empty($classname) || is_numeric($classname) || (strpos($classname, '.') !== false)) {
      return false;
   }
   $dir = GLPI_ROOT . "/inc/";
   if ($plug=isPluginItemType($classname)) {
      $plugname = strtolower($plug['plugin']);
      $dir      = GLPI_ROOT . "/plugins/$plugname/inc/";
      $item     = strtolower($plug['class']);
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
//    echo $classname;
      // Is ezComponent class ?
      if (preg_match('/^ezc([A-Z][a-z]+)/',$classname,$matches)) {
         include_once(GLPI_EZC_BASE);
         return ezcBase::autoload($classname);
      }
      // Do not try to load phpcas using GLPi autoload
      if (preg_match('/^CAS_.*/', $classname)) {
         return false;
      }

      $item = strtolower($classname);
   }

   // No errors for missing classes due to implementation
   if (!in_array($item,$CFG_GLPI['missingclasses'])){
      if (file_exists("$dir$item.class.php")) {
         include_once ("$dir$item.class.php");
         if (isset($_SESSION['glpi_use_mode'])
             && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $DEBUG_AUTOLOAD[] = $classname;
         }

      } else if (!isset($notfound["x$classname"])) {
         // trigger an error to get a backtrace, but only once (use prefix 'x' to handle empty case)
         //Toolbox::logInFile('debug',"file $dir$item.class.php not founded trying to load class $classname\n");
         trigger_error("GLPI autoload : file $dir$item.class.php not founded trying to load class '$classname'");
         $notfound["x$classname"] = true;
      }
   }
}

if (function_exists('spl_autoload_register')) {
   // Requires PHP >= 5.1.2
   spl_autoload_register('glpi_autoload');
} else {
   // For old PHP < 5.1.2 (tips, to check, in php.ini disable_functions = spl_autoload_register)
   function __autoload($class) {
      // Is phpCAS class ?
      if (preg_match('/^CAS_.*/', $class)) {
         include_once(GLPI_PHPCAS);
         return CAS_autoload($class);
      }
      // GLPI class
      return glpi_autoload($class);
   }
}

?>