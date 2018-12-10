<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

define ('NS_GLPI', 'Glpi\\');
define ('NS_PLUG', 'GlpiPlugin\\');

/**
 * Is the script launch in Command line?
 *
 * @return boolean
 */
function isCommandLine() {
   return (PHP_SAPI == 'cli');
}

/**
 * Is the script launched From API?
 *
 * @return boolean
 */
function isAPI() {
   if (strpos($_SERVER["SCRIPT_FILENAME"], 'apirest.php') !== false) {
      return true;
   }
   if (strpos($_SERVER["SCRIPT_FILENAME"], 'apixmlrpc.php') !== false) {
      return true;
   }
   return false;
}


/**
 * Determine if an object name is a plugin one
 *
 * @param string $classname class name to analyze
 *
 * @return boolean[object false or an object containing plugin name and class name
 */
function isPluginItemType($classname) {

   /** @var array $matches */
   if (preg_match("/Plugin([A-Z][a-z0-9]+)([A-Z]\w+)/", $classname, $matches)) {
      $plug           = [];
      $plug['plugin'] = $matches[1];
      $plug['class']  = $matches[2];
      return $plug;

   } else if (substr($classname, 0, \strlen(NS_PLUG)) === NS_PLUG) {
      $tab = explode('\\', $classname, 3);
      $plug           = [];
      $plug['plugin'] = $tab[1];
      $plug['class']  = $tab[2];
      return $plug;
   }
   // Standard case
   return false;
}


/**
 * Translate a string
 *
 * @since 0.84
 *
 * @param string $str    String to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string translated string
 */
function __($str, $domain = 'glpi') {
   global $TRANSLATE;

   if (is_null($TRANSLATE)) { // before login
      return $str;
   }
   $trans = $TRANSLATE->translate($str, $domain);
   // Wrong call when plural defined
   if (is_array($trans)) {
      return $trans[0];
   }
   return  $trans;
}


/**
 * Translate a string and escape HTML entities
 *
 * @since 0.84
 *
 * @param string $str    String to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function __s($str, $domain = 'glpi') {
   return htmlentities(__($str, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Translate a contextualized string and escape HTML entities
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string protected string (with htmlentities)
 */
function _sx($ctx, $str, $domain = 'glpi') {
   return htmlentities(_x($ctx, $str, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Pluralized translation
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plurial
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string translated string
 */
function _n($sing, $plural, $nb, $domain = 'glpi') {
   global $TRANSLATE;

   if (is_null($TRANSLATE)) { // before login
      if ($nb == 0 || $nb > 1) {
         return $plural;
      } else {
         return $sing;
      }
   }

   return $TRANSLATE->translatePlural($sing, $plural, $nb, $domain);
}


/**
 * Pluralized translation with HTML entities escaped
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plurial
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string protected string (with htmlentities)
 */
function _sn($sing, $plural, $nb, $domain = 'glpi') {
   return htmlentities(_n($sing, $plural, $nb, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Contextualized translation
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _x($ctx, $str, $domain = 'glpi') {

   // simulate pgettext
   $msg   = $ctx."\004".$str;
   $trans = __($msg, $domain);

   if ($trans == $msg) {
      // No translation
      return $str;
   }
   return $trans;
}


/**
 * Pluralized contextualized translation
 *
 * @since 0.84
 *
 * @param string  $ctx    context
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plurial
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _nx($ctx, $sing, $plural, $nb, $domain = 'glpi') {

   // simulate pgettext
   $singmsg    = $ctx."\004".$sing;
   $pluralmsg  = $ctx."\004".$plural;
   $trans      = _n($singmsg, $pluralmsg, $nb, $domain);

   if ($trans == $singmsg) {
      // No translation
      return $sing;
   }
   if ($trans == $pluralmsg) {
      // No translation
      return $plural;
   }
   return $trans;
}


/**
 * Classes loader
 *
 * @param string $classname : class to load
 *
 * @return void
 */
function glpi_autoload($classname) {
   global $DEBUG_AUTOLOAD;
   static $notfound = ['xStates'    => true,
                            'xAllAssets' => true, ];

   // empty classname or non concerted plugin or classname containing dot (leaving GLPI main treee)
   if (empty($classname) || is_numeric($classname) || (strpos($classname, '.') !== false)) {
      echo "Security die. trying to load a forbidden class name";
      die(1);
   }

   if ($classname === 'phpCAS'
       && file_exists(stream_resolve_include_path("CAS.php"))) {
      include_once('CAS.php');
      return true;
   }

   $dir = GLPI_ROOT . "/inc/";

   // Deprecation warn for TicketFollowup
   if ($classname === 'TicketFollowup') {
      Toolbox::deprecated('TicketFollowup has been replaced by ITILFollowup.');
   }

   if ($plug = isPluginItemType($classname)) {
      $plugname = strtolower($plug['plugin']);
      $dir      = GLPI_ROOT . "/plugins/$plugname/inc/";
      $item     = str_replace('\\', '/', strtolower($plug['class']));
      // Is the plugin active?
      // Command line usage of GLPI : need to do a real check plugin activation
      if (isCommandLine()) {
         $plugin = new Plugin();
         if (count($plugin->find(['directory' => $plugname, 'state' => Plugin::ACTIVATED])) == 0) {
            // Plugin does not exists or not activated
            return false;
         }
      } else {
         // Standard use of GLPI
         if (!Plugin::isPluginLoaded($plugname)) {
            // Plugin not activated
            return false;
         }
      }
   } else {
      $item = strtolower($classname);
      if (substr($classname, 0, \strlen(NS_GLPI)) === NS_GLPI) {
         $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
      }
   }

   if (file_exists("$dir$item.class.php")) {
      include_once("$dir$item.class.php");
      if (isset($_SESSION['glpi_use_mode'])
          && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)) {
         $DEBUG_AUTOLOAD[] = $classname;
      }

   } else if (!isset($notfound["x$classname"])) {
      // trigger an error to get a backtrace, but only once (use prefix 'x' to handle empty case)
      // trigger_error("GLPI autoload : file $dir$item.class.php not founded trying to load class '$classname'");
      $notfound["x$classname"] = true;
   }
}

// composer autoload
$autoload = GLPI_ROOT . '/vendor/autoload.php';
$needrun  = false;
if (!file_exists($autoload)) {
   $needrun = true;
} else if (file_exists(GLPI_ROOT . '/composer.lock')) {
   if (!file_exists(GLPI_ROOT . '/.composer.hash')) {
      /* First time */
      $needrun = true;
   } else if (sha1_file(GLPI_ROOT . '/composer.lock') != file_get_contents(GLPI_ROOT . '/.composer.hash')) {
      /* update */
      $needrun = true;
   }
}
if ($needrun) {
   $getComposerUrl = 'https://getcomposer.org/';
   if (isCommandLine()) {
      echo 'Run "composer install --no-dev" in the glpi tree.' . PHP_EOL
          . 'To install composer please refer to ' . $getComposerUrl . PHP_EOL;
   } else {
      echo 'Run "composer install --no-dev" in the glpi tree.<br>'
          . 'To install composer please refer to <a href="'.$getComposerUrl.'">'.$getComposerUrl.'</a>';
   }
   die(1);
}
require_once $autoload;

// Use spl autoload to allow stackable autoload.
spl_autoload_register('glpi_autoload', false, true);
