<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
 * Init plugins list reading plugins directory
 * @return nothing
 */
function initPlugins() {
   //return;
   $plugin=new Plugin();

   $plugin->checkStates();
   $plugins=$plugin->find('state='.PLUGIN_ACTIVATED);

   $_SESSION["glpi_plugins"]=array();

   if (count($plugins)) {
      foreach ($plugins as $ID => $plug) {
         $_SESSION["glpi_plugins"][$ID]=$plug['directory'];
      }
   }
}

/**
 * Init a plugin including setup.php file
 * launching plugin_init_NAME function  after checking compatibility
 *
 * @param $name Name of hook to use
 * @param $withhook boolean to load hook functions
 *
 * @return nothing
 */
function usePlugin ($name, $withhook=false) {
   global $CFG_GLPI, $PLUGIN_HOOKS,$LANG,$LOADED_PLUGINS;

   if (file_exists(GLPI_ROOT . "/plugins/$name/setup.php")) {
      include_once(GLPI_ROOT . "/plugins/$name/setup.php");
      if (!isset($LOADED_PLUGINS[$name])) {
         loadPluginLang($name);
         $function = "plugin_init_$name";
         if (function_exists($function)) {
            $function();
            $LOADED_PLUGINS[$name]=$name;
         }
      }
   }
   if ($withhook && file_exists(GLPI_ROOT . "/plugins/$name/hook.php")) {
      include_once(GLPI_ROOT . "/plugins/$name/hook.php");
   }
}

/**
 * This function executes a hook.
 * @param $name Name of hook to fire
 * @param $param Parameters if needed
 * @return mixed $data
 */
function doHook ($name,$param=NULL) {
   global $PLUGIN_HOOKS;

   if ($param==NULL) {
      $data = func_get_args();
   } else {
      $data=$param;
   }

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
 * @param $suffixe_of_function to be called
 * @param $args params passed to the function
 *
 * @return mixed $data
 */
function doOneHook() {

   $args=func_get_args();
   $plugname = array_shift($args);
   $hook = array_shift($args);
   if (!is_array($hook)) {
      $hook = "plugin_" . $plugname . "_" . $hook;
   }
   // TODO move this in the above test when autoload ready
   if (file_exists(GLPI_ROOT . "/plugins/$plugname/hook.php")) {
      include_once(GLPI_ROOT . "/plugins/$plugname/hook.php");
   }
   if (is_callable($hook)) {
      return call_user_func_array($hook, $args);
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
   if (isset($PLUGIN_HOOKS['plugin_types']) && count($PLUGIN_HOOKS['plugin_types'])) {
      $tab=array_unique($PLUGIN_HOOKS['plugin_types']);
      foreach ($tab as $plug) {
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

/**
 * Deprecated function
 *
 *
 * @param $plugin plugin of the device type
 * @param $name name of the itemtype to define the constant
 * @param $itemtype number used as constant
 * @param $attrib Array of attributes, a hashtable with index in
 * 	(classname, tablename, typename, formpage, searchpage, reservation_types,
 *   deleted_tables, specif_entities_tables, recursive_type, template_tables)
 *
 * @return nothing
 */
 // TODO Remove this on 2009-12-14
function registerPluginType($plugin,$name,$itemtype,$attrib) {
   global $PLUGIN_HOOKS,$LINK_ID_TABLE,$INFOFORM_PAGES,$SEARCH_PAGES,$CFG_GLPI;

   if (is_numeric($itemtype)) {
      die("itemtype MUST be a class name ($plugin/$name/$itemtype)");
   }
   $tmp = isPluginItem($itemtype);
   if (strcasecmp($tmp['plugin'],$plugin)) {
      die("itemtype not standard : $plugin/$name/$itemtype/".$tmp['plugin']);
   }
   if (!defined($name)) {
      define($name,$itemtype);

      Plugin::registerClass($itemtype, $attrib);
   } // not already defined
}

function loadPluginLang($name) {
   global $CFG_GLPI,$LANG;

   if (isset($_SESSION['glpilanguage'])
       && file_exists(GLPI_ROOT . "/plugins/$name/locales/".
                      $CFG_GLPI["languages"][$_SESSION['glpilanguage']][1])) {

      include_once (GLPI_ROOT . "/plugins/$name/locales/".
                    $CFG_GLPI["languages"][$_SESSION['glpilanguage']][1]);

   } else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/".
                          $CFG_GLPI["languages"][$CFG_GLPI["language"]][1])) {

      include_once (GLPI_ROOT . "/plugins/$name/locales/".
                    $CFG_GLPI["languages"][$CFG_GLPI["language"]][1]);

   } else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/en_GB.php")) {
      include_once (GLPI_ROOT . "/plugins/$name/locales/en_GB.php");
   } else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/fr_FR.php")) {
      include_once (GLPI_ROOT . "/plugins/$name/locales/fr_FR.php");
   }
}

/**
 * Determine if an object name is a plugin one
 *
 * @param $classname class name to analyze
 *
 * @return false or an object containing plugin name and class name
 */
function isPluginItem($classname) {

   if (preg_match("/Plugin([A-Z][a-z0-9]+)([A-Z]\w+)/",$classname,$matches)) {
      $plug=array();
      $plug['plugin']=$matches[1];
      $plug['class']=$matches[2];
      return $plug;
   } else { // Standard case
      return false;
   }

}

?>