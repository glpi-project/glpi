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

// Based on cacti plugin system
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

global $PLUGIN_HOOKS;
$PLUGIN_HOOKS = array();

global $CFG_GLPI_PLUGINS;
$CFG_GLPI_PLUGINS = array();


/**
 * This function executes a hook.
 * @param $name Name of hook to fire
 * @param $param Parameters if needed : if object limit to the itemtype 
 * @return mixed $data
 */
function doHook ($name,$param=NULL) {
   global $PLUGIN_HOOKS;

   if ($param==NULL) {
      $data = func_get_args();
   } else {
      $data=$param;
   }

   // Apply hook only for the item
   if ($param != NULL && is_object($param)) {
      $itemtype=get_class($param);
      if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
         foreach ($PLUGIN_HOOKS[$name] as $plug => $tab) {
            if (isset($tab[$itemtype])) {
               if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                  include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
               }
               if (is_callable($tab[$itemtype])) {
                  call_user_func($tab[$itemtype],$data);
               }
            }
         }
      }
   } else { // Standard hook call
      if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
         foreach ($PLUGIN_HOOKS[$name] as $plug => $function) {
            if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
               include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
            }
            if (is_callable($function)) {
               call_user_func($function,$data);
            }
         }
      }
   }
   /* Variable-length argument lists have a slight problem when */
   /* passing values by reference. Pity. This is a workaround.  */
   return $data;
}

/**
 * This function executes a hook.
 * @param $name Name of hook to fire
 * @param $parm Parameters
 * @return mixed $data
 */
function doHookFunction($name,$parm=NULL) {
   global $PLUGIN_HOOKS;

   $ret = $parm;
   if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
      foreach ($PLUGIN_HOOKS[$name] as $plug => $function) {
         if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
            include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
         }
         if (is_callable($function)) {
            $ret = call_user_func($function, $ret);
         }
      }
   }
   /* Variable-length argument lists have a slight problem when */
   /* passing values by reference. Pity. This is a workaround.  */
   return $ret;
}

/**
 * This function executes a hook for 1 plugin.
 * @param $plugname Name of the plugin
 * @param $hook function to be called (may be an array for call a class method)
 * @param $options params passed to the function
 *
 * @return mixed $data
 */
function doOneHook($plugname,$hook,$options=array()) {
   $plugname=strtolower($plugname);
   if (!is_array($hook)) {
      $hook = "plugin_" . $plugname . "_" . $hook;
      if (file_exists(GLPI_ROOT . "/plugins/$plugname/hook.php")) {
         include_once(GLPI_ROOT . "/plugins/$plugname/hook.php");
      }
   }
   if (is_callable($hook)) {
      return call_user_func($hook, $options);
   }
}


/**
 * Get dropdowns for plugins
 *
 * @return Array containing plugin dropdowns
 */
function getPluginsDropdowns() {

   $dps=array();
   if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
      foreach ($_SESSION["glpi_plugins"] as  $plug) {
         $tab = doOneHook($plug,'getDropdown');
         if (is_array($tab)) {
            $function="plugin_version_$plug";
            $name=$function();
            $dps=array_merge($dps,array($name['name']=>$tab));
         }
      }
   }
   return $dps;
}

/**
 * Get database relations for plugins
 *
 * @return Array containing plugin database relations
 */
function getPluginsDatabaseRelations() {

   $dps=array();
   if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
      foreach ($_SESSION["glpi_plugins"] as $plug) {
         if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
            include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
         }
         $function2="plugin_".$plug."_getDatabaseRelations";
         if (function_exists($function2)) {
            $dps=array_merge($dps,$function2());
         }
      }
   }
   return $dps;
}

/**
 * Get additional search options managed by plugins
 *
 * @param $itemtype
 *
 * @return Array containing plugin search options for given type
 */
function getPluginSearchOptions($itemtype) {
   global $PLUGIN_HOOKS;

   $sopt=array();
   if (isset($_SESSION['glpi_plugins']) && count($_SESSION['glpi_plugins'])) {
      foreach ($_SESSION['glpi_plugins'] as $plug) {
         if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
            include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
         }
         $function="plugin_".$plug."_getAddSearchOptions";
         if (function_exists($function)) {
            $tmp=$function($itemtype);
            if (count($tmp)) {
               $sopt += $tmp;
            }
         }
      }
   }
   return $sopt;
}


?>
