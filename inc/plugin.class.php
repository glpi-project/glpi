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

/**
 * Based on cacti plugin system
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class Plugin extends CommonDBTM {

   // Class constant : Plugin state
   const ANEW           = 0;
   const ACTIVATED      = 1;
   const NOTINSTALLED   = 2;
   const TOBECONFIGURED = 3;
   const NOTACTIVATED   = 4;
   const TOBECLEANED    = 5;
   const NOTUPDATED     = 6;

   static $rightname = 'config';



   /**
    * @since 0.85
    *
    * @param $nb
   **/
   static function getTypeName($nb = 0) {
      return _n('Plugin', 'Plugins', $nb);
   }


   /**
    * @see CommonGLPI::getMenuName()
    *
    * @since 0.85
   **/
   static function getMenuName() {
      return static::getTypeName(Session::getPluralNumber());
   }

   /**
    * @see CommonGLPI::getMenuContent()
    *
    * @since 0.85
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = [];
      if (static::canView()) {
         $menu['title']   = self::getMenuName();
         $menu['page']    = '/front/plugin.php';
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }

   /**
    * Retrieve an item from the database using its directory
    *
    * @param $dir directory of the plugin
    *
    * @return true if succeed else false
   **/
   function getFromDBbyDir($dir) {
      return $this->getFromDBByCrit([$this->getTable() . '.directory' => $dir]);
   }


   /**
    * Init plugins list reading plugins directory
    *
    * @return nothing
   **/
   function init() {

      $this->checkStates();
      $plugins                  = $this->find('state='.self::ACTIVATED);

      $_SESSION["glpi_plugins"] = [];

      if (count($plugins)) {
         foreach ($plugins as $ID => $plug) {
            $_SESSION["glpi_plugins"][$ID] = $plug['directory'];
         }
      }
   }


   /**
    * Init a plugin including setup.php file
    * launching plugin_init_NAME function  after checking compatibility
    *
    * @param $name             Name of hook to use
    * @param $withhook boolean to load hook functions (false by default)
    *
    * @return nothing
   **/
   static function load($name, $withhook = false) {
      global $LOADED_PLUGINS;

      if (file_exists(GLPI_ROOT . "/plugins/$name/setup.php")) {
         include_once(GLPI_ROOT . "/plugins/$name/setup.php");
         if (!isset($LOADED_PLUGINS[$name])) {
            self::loadLang($name);
            $function = "plugin_init_$name";
            if (function_exists($function)) {
               $function();
               $LOADED_PLUGINS[$name] = $name;
            }
         }
      }
      if ($withhook
          && file_exists(GLPI_ROOT . "/plugins/$name/hook.php")) {
         include_once(GLPI_ROOT . "/plugins/$name/hook.php");
      }
   }


   /**
    * Load lang file for a plugin
    *
    * @param $name            Name of hook to use
    * @param $forcelang       force a specific lang (default '')
    * @param $coretrytoload lang trying to be load from core (default '')
    *
    * @return nothing
   **/
   static function loadLang($name, $forcelang = '', $coretrytoload = '') {
      // $LANG needed : used when include lang file
      global $CFG_GLPI, $LANG, $TRANSLATE;

      // For compatibility for plugins using $LANG
      $trytoload = 'en_GB';
      if (isset($_SESSION['glpilanguage'])) {
         $trytoload = $_SESSION["glpilanguage"];
      }
      // Force to load a specific lang
      if (!empty($forcelang)) {
         $trytoload = $forcelang;
      }

      // If not set try default lang file
      if (empty($trytoload)) {
         $trytoload = $CFG_GLPI["language"];
      }

      if (empty($coretrytoload)) {
            $coretrytoload = $trytoload;
      }

      $dir = GLPI_ROOT . "/plugins/$name/locales/";

      $translation_included = false;
      // New localisation system
      if (file_exists($dir.$CFG_GLPI["languages"][$trytoload][1])) {
         $TRANSLATE->addTranslationFile('gettext',
                                        $dir.$CFG_GLPI["languages"][$trytoload][1],
                                        $name, $coretrytoload);

         $translation_included = true;

      } else if (!empty($CFG_GLPI["language"])
                 && file_exists($dir.$CFG_GLPI["languages"][$CFG_GLPI["language"]][1])) {
         $TRANSLATE->addTranslationFile('gettext',
                                        $dir.$CFG_GLPI["languages"][$CFG_GLPI["language"]][1],
                                        $name, $coretrytoload);
         $translation_included = true;
      } else if (file_exists($dir."en_GB.mo")) {
         $TRANSLATE->addTranslationFile('gettext',
                                        $dir."en_GB.mo",
                                        $name, $coretrytoload);
         $translation_included = true;

      }

      if (!$translation_included) {
         if (file_exists($dir.$trytoload.'.php')) {
            include ($dir.$trytoload.'.php');
         } else if (isset($CFG_GLPI["language"])
                    && file_exists($dir.$CFG_GLPI["language"].'.php')) {
            include ($dir.$CFG_GLPI["language"].'.php');
         } else if (file_exists($dir . "en_GB.php")) {
            include ($dir . "en_GB.php");
         } else if (file_exists($dir . "fr_FR.php")) {
            include ($dir . "fr_FR.php");
         }
      }
   }


   /**
    * Check plugins states and detect new plugins
   **/
   function checkStates() {

      //// Get all plugins
      // Get all from DBs
      $pluglist   = $this->find("", "name, directory");
      $db_plugins = [];
      if (count($pluglist)) {
         foreach ($pluglist as $plug) {
            $db_plugins[$plug['directory']] = $plug['id'];
         }
      }
      // Parse plugin dir
      $file_plugins  = [];
      $error_plugins = [];
      $dirplug       = GLPI_ROOT."/plugins";
      $dh            = opendir($dirplug);

      while (false !== ($filename = readdir($dh))) {
         if (($filename != ".svn")
             && ($filename != ".")
             && ($filename != "..")
             && is_dir($dirplug."/".$filename)) {

            // Find version
            if (file_exists($dirplug."/".$filename."/setup.php")) {
               self::loadLang($filename);
               include_once($dirplug."/".$filename."/setup.php");
               $info = self::getInfo($filename);
               if (count($info)) {
                  $file_plugins[$filename] = Toolbox::addslashes_deep($info);
               }
            }
         }
      }

      // check plugin state
      foreach ($db_plugins as $plug => $ID) {
         $install_ok = true;
         // Check file
         if (!isset($file_plugins[$plug])) {
            $this->update(['id'    => $ID,
                                'state' => self::TOBECLEANED]);
            $install_ok = false;
         } else {
            // Check version
            if ($file_plugins[$plug]['version'] != $pluglist[$ID]['version']) {
               $input          = $file_plugins[$plug];
               $input['id']    = $ID;
               if ($pluglist[$ID]['version']) {
                  $input['state'] = self::NOTUPDATED;
               }

               $this->removeFromSession($plug);
               // reset menu
               if (isset($_SESSION['glpimenu'])) {
                  unset($_SESSION['glpimenu']);
               }

               $this->update($input);
               $install_ok = false;
            }
         }
         // Check install is ok for activated plugins
         if ($install_ok
             && ($pluglist[$ID]['state'] == self::ACTIVATED)) {
            $usage_ok = $this->checkVersions($plug);

            $function = "plugin_".$plug."_check_prerequisites";
            if (function_exists($function)) {
               if (!$function()) {
                  $usage_ok = false;
               }
            }
            $function = "plugin_".$plug."_check_config";
            if (function_exists($function)) {
               if (!$function()) {
                  $usage_ok = false;
               }
            } else {
               $usage_ok = false;
            }
            if (!$usage_ok) {
               $this->unactivate($ID);
            }
         }
         // Delete plugin for file list
         if (isset($file_plugins[$plug])) {
            unset($file_plugins[$plug]);
         }
      }

      if (count($file_plugins)) {
         foreach ($file_plugins as $plug => $data) {
            if (isset($data['oldname'])) {
               $checking = $pluglist;
               foreach ($checking as $check) {
                  if (isset($check['directory']) && ($check['directory'] == $data['oldname'])) {
                     $data['state'] = self::NOTUPDATED;
                     $this->delete(['id' => $check['id']]);
                  }
               }
            } else {
               $data['state'] = self::NOTINSTALLED;
            }
            $data['directory'] = $plug;
            $this->add($data);
         }
      }
   }


   /**
    * Check if all plugins are CSRF compliant
    *
    * @since 0.83.3
   **/
   static function isAllPluginsCSRFCompliant() {
      global $PLUGIN_HOOKS;

      if (isset($_SESSION['glpi_plugins'])
          && is_array($_SESSION['glpi_plugins'])
          && count($_SESSION['glpi_plugins'])) {
         foreach ($_SESSION['glpi_plugins'] as $plug) {
            if (!isset($PLUGIN_HOOKS['csrf_compliant'][$plug])
                || !$PLUGIN_HOOKS['csrf_compliant'][$plug]) {
               return false;
            }
         }
      }

      return true;
   }

   /**
    * Get list of all plugins
    *
    * @param array $fields Fields to retrieve
    * @param array $order  Query ORDER clause
    *
    * @return array
    */
   public function getList(array $fields = [], array $order = ['name', 'directory']) {
      global $DB;

      $query = [
         'FROM'   => $this->getTable()
      ];

      if (count($fields) > 0) {
         $query['FIELDS'] = $fields;
      }

      if (count($order) > 0) {
         $query['ORDER'] = $order;
      }

      $iterator = $DB->request($query);
      return iterator_to_array($iterator, false);
   }


   /**
    * List available plugins
   **/
   function listPlugins() {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      $this->checkStates();
      echo "<div class='center'><table class='tab_cadrehov'>";

      $pluglist          = $this->getList();
      $i                 = 0;
      $PLUGIN_HOOKS_SAVE = $PLUGIN_HOOKS;
      echo "<tr><th colspan='9'>".__('Plugins list')."</th></tr>\n";

      if (!empty($pluglist)) {
         echo "<tr><th>".__('Name')."</th><th>"._n('Version', 'Versions', 1)."</th>";
         echo "<th>".__('License')."</th>";
         echo "<th>".__('Status')."</th><th>"._n('Author', 'Authors', Session::getPluralNumber())."</th>";
         echo "<th>".__('Website')."</th>";
         echo "<th>".__('CSRF compliant')."</th>";
         echo "<th colspan='2'>&nbsp;</th></tr>\n";

         foreach ($pluglist as $plug) {
            $ID = $plug['id'];
            if (function_exists("plugin_".$plug['directory']."_check_config")) {
               // init must not be called for incompatible plugins
               self::load($plug['directory'], true);
            }
            $i++;
            $class = 'tab_bg_1';
            if (($i%2) == 0) {
               $class = 'tab_bg_2';
            }
            echo "<tr class='$class'>";
            echo "<td>";
            $name = trim($plug['name']);
            if (empty($name)) {
               $plug['name'] = $plug['directory'];
            }

            // Only config for install plugins
            if (in_array($plug['state'], [self::ACTIVATED,
                                               self::TOBECONFIGURED,
                                               self::NOTACTIVATED])
                && isset($PLUGIN_HOOKS['config_page'][$plug['directory']])) {

               echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/".$plug['directory']."/".
                      $PLUGIN_HOOKS['config_page'][$plug['directory']]."'>
                      <span class='b'>".$plug['name']."</span></a>";
            } else {
               echo $plug['name'];
            }
            echo "</td>";
            echo "<td>".$plug['version']."</td><td>";
            if ($plug['license']) {
               $link = '';
               if (file_exists(GLPI_ROOT.'/plugins/'.$plug['directory'].'/LICENSE')) {
                  $link = $CFG_GLPI['root_doc'].'/plugins/'.$plug['directory'].'/LICENSE';
               } else if (file_exists(GLPI_ROOT.'/plugins/'.$plug['directory'].'/COPYING.txt')) {
                  $link = $CFG_GLPI['root_doc'].'/plugins/'.$plug['directory'].'/COPYING.txt';
               }
               if ($link) {
                  echo "<a href='$link'>".$plug['license']."</a>";
               } else {
                  echo $plug['license'];
               }
            } else {
               echo "&nbsp;";
            }
            echo "</td><td>";
            echo self::getState($plug['state']);
            echo "</td>";
            echo "<td>".$plug['author']."</td>";
            $weblink = trim($plug['homepage']);
            echo "<td>";
            if (!empty($weblink)) {
               echo "<a href='".Toolbox::formatOutputWebLink($weblink)."' target='_blank'>";
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle' alt=\"".
                      __s('Web')."\" title=\"".__s('Web')."\" ></a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            // CSRF
            echo "<td>";
            if (isset($PLUGIN_HOOKS['csrf_compliant'][$plug['directory']])
                && $PLUGIN_HOOKS['csrf_compliant'][$plug['directory']]) {
               echo __('Yes');
            } else {
               echo "<span class='red b'>";
               echo __('No');
               echo "</span>";
            }
            echo "</td>";

            switch ($plug['state']) {
               case self::ACTIVATED :
                  echo "<td>";
                  Html::showSimpleForm(static::getFormURL(), ['action' => 'unactivate'],
                                       _x('button', 'Disable'), ['id' => $ID]);
                  echo "</td>";
                  echo "<td>";
                  if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                     Html::showSimpleForm(static::getFormURL(), ['action' => 'uninstall'],
                                          _x('button', 'Uninstall'), ['id' => $ID]);
                  } else {
                     //TRANS: %s is the list of missing functions
                     echo sprintf(__('%1$s: %2$s'), __('Non-existent function'),
                                  "plugin_".$plug['directory']."_uninstall");
                  }
                  echo "</td>";
                  break;

               case self::ANEW :
               case self::NOTINSTALLED :
               case self::NOTUPDATED :
                  echo "<td>";
                  if (function_exists("plugin_".$plug['directory']."_install")
                      && function_exists("plugin_".$plug['directory']."_check_config")) {

                     $function   = 'plugin_' . $plug['directory'] . '_check_prerequisites';
                     $do_install = $this->checkVersions($plug['directory']);

                     if ($do_install && function_exists($function)) {
                        ob_start();
                        $do_install = $function();
                        $msg = '';
                        if (!$do_install) {
                           $msg = '<span class="error">' . ob_get_contents() . '</span>';
                        }
                        ob_end_clean();
                        echo $msg;
                     }
                     if ($plug['state'] == self::NOTUPDATED) {
                        $msg = _x('button', 'Upgrade');
                     } else {
                        $msg = _x('button', 'Install');
                     }
                     if ($do_install) {
                        Html::showSimpleForm(static::getFormURL(), ['action' => 'install'],
                                             $msg, ['id' => $ID]);
                     }
                  } else {

                     $missing = '';
                     if (!function_exists("plugin_".$plug['directory']."_install")) {
                        $missing .= "plugin_".$plug['directory']."_install";
                     }
                     if (!function_exists("plugin_".$plug['directory']."_check_config")) {
                        $missing .= " plugin_".$plug['directory']."_check_config";
                     }
                     //TRANS: %s is the list of missing functions
                     printf(__('%1$s: %2$s'), __('Non-existent function'),
                            $missing);
                  }
                  echo "</td><td>";
                  if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                     if (function_exists("plugin_".$plug['directory']."_check_config")) {
                        Html::showSimpleForm(static::getFormURL(), ['action' => 'uninstall'],
                                             _x('button', 'Uninstall'), ['id' => $ID]);
                     } else {
                        // This is an incompatible plugin (0.71), uninstall fonction could crash
                        echo "&nbsp;";
                     }
                  } else {
                     printf(__('%1$s: %2$s'), __('Non-existent function'),
                            "plugin_".$plug['directory']."_uninstall");
                  }
                  echo "</td>";
                  break;

               case self::TOBECONFIGURED :
                  echo "<td>";
                  $function = 'plugin_' . $plug['directory'] . '_check_config';
                  if (function_exists($function)) {
                     if ($function(true)) {
                        $this->update(['id'    => $ID,
                                            'state' => self::NOTACTIVATED]);
                        Html::redirect($this->getSearchURL());
                     }
                  } else {
                     printf(__('%1$s: %2$s'), __('Non-existent function'),
                            "plugin_".$plug['directory']."_check_config");
                  }
                  echo "</td><td>";
                  if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                     Html::showSimpleForm(static::getFormURL(), ['action' => 'uninstall'],
                                          _x('button', 'Uninstall'), ['id' => $ID]);
                  } else {
                     printf(__('%1$s: %2$s'), __('Non-existent function'),
                            "plugin_".$plug['directory']."_uninstall");
                  }
                  echo "</td>";
                  break;

               case self::NOTACTIVATED :
                  echo "<td>";
                  $process = $this->checkVersions($plug['directory']);
                  $function = 'plugin_' . $plug['directory'] . '_check_prerequisites';
                  if (!isset($PLUGIN_HOOKS['csrf_compliant'][$plug['directory']])
                      || !$PLUGIN_HOOKS['csrf_compliant'][$plug['directory']]) {
                     echo __('Not CSRF compliant');
                  } else if (function_exists($function) && $function() && $process) {
                     Html::showSimpleForm(static::getFormURL(), ['action' => 'activate'],
                                          _x('button', 'Enable'), ['id' => $ID]);
                  }
                  // Else : reason displayed by the plugin
                  echo "</td><td>";
                  if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                     Html::showSimpleForm(static::getFormURL(), ['action' => 'uninstall'],
                                          _x('button', 'Uninstall'), ['id' => $ID]);
                  } else {
                     printf(__('%1$s: %2$s'), __('Non-existent function'),
                            "plugin_".$plug['directory']."_uninstall");
                  }
                  echo "</td>";
                  break;

               case self::TOBECLEANED :
               default :
                  echo "<td colspan='2'>";
                  Html::showSimpleForm(static::getFormURL(), ['action' => 'clean'],
                                       _x('button', 'Clean'), ['id' => $ID]);
                  echo "</td>";
                  break;
            }
            echo "</tr>\n";
         }

      } else {
         echo "<tr class='tab_bg_1'><td class='center' colspan='7'>".__('No plugin installed').
              "</td></tr>";
      }
      echo "</table></div>";
      echo "<br>";
      echo "<div class='center'><p>";
      echo "<a href='http://plugins.glpi-project.org'  class='vsubmit' target='_blank'>".
            __('See the catalog of plugins')."</a></p>";
      echo "</div>";

      $PLUGIN_HOOKS = $PLUGIN_HOOKS_SAVE;
   }


   /**
    * uninstall a plugin
    *
    * @param $ID ID of the plugin
   **/
   function uninstall($ID) {
      $message = '';
      $type = ERROR;

      if ($this->getFromDB($ID)) {
         CronTask::Unregister($this->fields['directory']);
         self::load($this->fields['directory'], true);
         FieldUnicity::deleteForItemtype($this->fields['directory']);
         Link_Itemtype::deleteForItemtype($this->fields['directory']);

         // Run the Plugin's Uninstall Function first
         $function = 'plugin_' . $this->fields['directory'] . '_uninstall';
         if (function_exists($function)) {
            $_SESSION['glpi_plugins']['temp'] = $this->fields['directory']; // For autoloader
            $function();
            unset($_SESSION['glpi_plugins']['temp']);
         } else {
            Session::addMessageAfterRedirect(
               sprintf(__('Plugin %1$s has no uninstall function!'), $this->fields['name']),
               true,
               WARNING
            );
         }

         $this->update(['id'      => $ID,
                             'state'   => self::NOTINSTALLED,
                             'version' => '']);
         $this->removeFromSession($this->fields['directory']);

         $type = INFO;
         $message = sprintf(__('Plugin %1$s has been uninstalled!'), $this->fields['name']);
      } else {
         $message = sprintf(__('Plugin %1$s not found!'), $ID);
      }

      Session::addMessageAfterRedirect(
         $message,
         true,
         $type
      );
   }


   /**
    * Install a plugin
    *
    * @param int $ID ID of the plugin
    *
    * @return void
   **/
   function install($ID) {

      $message = '';
      $type = ERROR;

      if ($this->getFromDB($ID)) {
         self::load($this->fields['directory'], true);
         $function   = 'plugin_' . $this->fields['directory'] . '_install';
         if (function_exists($function)) {
            $_SESSION['glpi_plugins']['temp'] = $this->fields['directory'];  // For autoloader
            if ($function()) {
               $type = INFO;
               $function = 'plugin_' . $this->fields['directory'] . '_check_config';
               if (function_exists($function)) {
                  if ($function()) {
                     $this->update(['id'    => $ID,
                                         'state' => self::NOTACTIVATED]);
                     $message = sprintf(__('Plugin %1$s has been installed!'), $this->fields['name']);
                     $message .= '<br/><br/>' . str_replace(
                        '%activate_link',
                        Html::getSimpleForm(static::getFormURL(), ['action' => 'activate'],
                                          mb_strtolower(_x('button', 'Enable')), ['id' => $ID], '', 'class="pointer"'),
                        __('Do you want to %activate_link it?')
                     );
                  } else {
                     $this->update(['id'    => $ID,
                                         'state' => self::TOBECONFIGURED]);
                     $message = sprintf(__('Plugin %1$s has been installed and must be configured!'), $this->fields['name']);
                  }
               }
            }
            unset($_SESSION['glpi_plugins']['temp']);
         } else {
            $type = WARNING;
            $message = sprintf(__('Plugin %1$s has no install function!'), $this->fields['name']);
         }
      } else {
         $message = sprintf(__('Plugin %1$s not found!'), $ID);
      }

      Session::addMessageAfterRedirect(
         $message,
         true,
         $type
      );
   }


   /**
    * activate a plugin
    *
    * @param $ID ID of the plugin
    *
    * @return boolean about success
   **/
   function activate($ID) {
      global $PLUGIN_HOOKS;

      if ($this->getFromDB($ID)) {

         self::load($this->fields['directory'], true);
         // No activation if not CSRF compliant
         if (!isset($PLUGIN_HOOKS['csrf_compliant'][$this->fields['directory']])
             || !$PLUGIN_HOOKS['csrf_compliant'][$this->fields['directory']]) {
            Session::addMessageAfterRedirect(
               sprintf(__('Plugin %1$s is not CSRF compliant!'), $this->fields['name']),
               true,
               ERROR
            );
            return false;
         }
         // Enable autoloader early, during activation process
         $_SESSION['glpi_plugins'][$ID] = $this->fields['directory'];

         $function = 'plugin_' . $this->fields['directory'] . '_check_prerequisites';
         if (function_exists($function)) {
            if (!$function()) {
               unset($_SESSION['glpi_plugins'][$ID]);
               Session::addMessageAfterRedirect(
                  sprintf(__('Plugin %1$s has no check function!'), $this->fields['name']),
                  true,
                  ERROR
               );
               return false;
            }
         }
         $function = 'plugin_' . $this->fields['directory'] . '_check_config';
         if (function_exists($function)) {
            if ($function()) {
               $this->update(['id'    => $ID,
                                   'state' => self::ACTIVATED]);

               // Initialize session for the plugin
               if (isset($PLUGIN_HOOKS['init_session'][$this->fields['directory']])
                   && is_callable($PLUGIN_HOOKS['init_session'][$this->fields['directory']])) {

                  call_user_func($PLUGIN_HOOKS['init_session'][$this->fields['directory']]);
               }

               // Initialize profile for the plugin
               if (isset($PLUGIN_HOOKS['change_profile'][$this->fields['directory']])
                   && is_callable($PLUGIN_HOOKS['change_profile'][$this->fields['directory']])) {

                  call_user_func($PLUGIN_HOOKS['change_profile'][$this->fields['directory']]);
               }
               // reset menu
               if (isset($_SESSION['glpimenu'])) {
                  unset($_SESSION['glpimenu']);
               }

               Session::addMessageAfterRedirect(
                  sprintf(__('Plugin %1$s has been activated!'), $this->fields['name']),
                  true,
                  INFO
               );

               return true;
            }
         }  // exists _check_config
         // Failure so remove it
         unset($_SESSION['glpi_plugins'][$ID]);
      } // getFromDB

      Session::addMessageAfterRedirect(
         sprintf(__('Plugin %1$s not found!'), $ID),
         true,
         ERROR
      );
      return false;
   }


   /**
    * unactivate a plugin
    *
    * @param $ID ID of the plugin
    *
    * @return boolean
   **/
   function unactivate($ID) {

      if ($this->getFromDB($ID)) {
         $this->update(['id'    => $ID,
                             'state' => self::NOTACTIVATED]);
         $this->removeFromSession($this->fields['directory']);
         // reset menu
         if (isset($_SESSION['glpimenu'])) {
            unset($_SESSION['glpimenu']);
         }

         Session::addMessageAfterRedirect(
            sprintf(__('Plugin %1$s has been deactivated!'), $this->fields['name']),
            true,
            INFO
         );

         return true;
      }

      Session::addMessageAfterRedirect(
         sprintf(__('Plugin %1$s not found!'), $ID),
         true,
         ERROR
      );

      return false;
   }


   /**
    * unactivate all activated plugins for update process
   **/
   function unactivateAll() {
      global $DB;

      $DB->update(
         $this->getTable(), [
            'state' => self::NOTACTIVATED
         ], [
            'state' => self::ACTIVATED
         ]
      );
      $_SESSION['glpi_plugins'] = [];
      // reset menu
      if (isset($_SESSION['glpimenu'])) {
         unset($_SESSION['glpimenu']);
      }
   }


   /**
    * clean a plugin
    *
    * @param $ID ID of the plugin
   **/
   function clean($ID) {

      if ($this->getFromDB($ID)) {
         // Clean crontask after "hard" remove
         CronTask::Unregister($this->fields['directory']);

         $this->delete(['id' => $ID]);
         $this->removeFromSession($this->fields['directory']);
      }
   }


   /**
    * is a plugin activated
    *
    * @param $plugin plugin directory
   **/
   function isActivated($plugin) {

      if ($this->getFromDBbyDir($plugin)) {
         return ($this->fields['state'] == self::ACTIVATED);
      }
   }


   /**
    * is a plugin installed
    *
    * @param $plugin plugin directory
   **/
   function isInstalled($plugin) {

      if ($this->getFromDBbyDir($plugin)) {
         return (($this->fields['state']    == self::ACTIVATED)
                 || ($this->fields['state'] == self::TOBECONFIGURED)
                 || ($this->fields['state'] == self::NOTACTIVATED));
      }
   }


   /**
    * remove plugin from session variable
    *
    * @param $plugin plugin directory
   **/
   function removeFromSession($plugin) {
      if (!isset($_SESSION['glpi_plugins'])) {
         return;
      }
      $key = array_search($plugin, $_SESSION['glpi_plugins']);
      if ($key !== false) {
         unset($_SESSION['glpi_plugins'][$key]);
      }
   }


   /**
    * Migrate itemtype from integer (0.72) to string (0.80)
    *
    * @param $types        array of (num=>name) of type manage by the plugin
    * @param $glpitables   array of GLPI table name used by the plugin
    * @param $plugtables   array of Plugin table name which have an itemtype
    *
    * @return nothing
   **/
   static function migrateItemType($types = [], $glpitables = [], $plugtables = []) {
      global $DB;

      $typetoname = [0  => "",// For tickets
                          1  => "Computer",
                          2  => "NetworkEquipment",
                          3  => "Printer",
                          4  => "Monitor",
                          5  => "Peripheral",
                          6  => "Software",
                          7  => "Contact",
                          8  => "Supplier",
                          9  => "Infocom",
                          10 => "Contract",
                          11 => "CartridgeItem",
                          12 => "DocumentType",
                          13 => "Document",
                          14 => "KnowbaseItem",
                          15 => "User",
                          16 => "Ticket",
                          17 => "ConsumableItem",
                          18 => "Consumable",
                          19 => "Cartridge",
                          20 => "SoftwareLicense",
                          21 => "Link",
                          22 => "State",
                          23 => "Phone",
                          24 => "Device",
                          25 => "Reminder",
                          26 => "Stat",
                          27 => "Group",
                          28 => "Entity",
                          29 => "ReservationItem",
                          30 => "AuthMail",
                          31 => "AuthLDAP",
                          32 => "OcsServer",
                          33 => "RegistryKey",
                          34 => "Profile",
                          35 => "MailCollector",
                          36 => "Rule",
                          37 => "Transfer",
                          38 => "SavedSearch",
                          39 => "SoftwareVersion",
                          40 => "Plugin",
                          41 => "Item_Disk",
                          42 => "NetworkPort",
                          43 => "TicketFollowup",
                          44 => "Budget"];

      //Add plugins types
      $typetoname = self::doHookFunction("migratetypes", $typetoname);

      foreach ($types as $num => $name) {
         $typetoname[$num] = $name;
         foreach ($glpitables as $table) {
            $DB->updateOrDie(
               $table, [
                  'itemtype'  => $name,
               ], [
                  'itemtype'  => $num
               ],
               "update itemtype of table $table for $name"
            );
         }
      }

      if (in_array('glpi_infocoms', $glpitables)) {
         $entities    = getAllDatasFromTable('glpi_entities');
         $entities[0] = "Root";

         foreach ($types as $num => $name) {
            $itemtable = getTableForItemType($name);
            if (!$DB->tableExists($itemtable)) {
               // Just for security, shouldn't append
               continue;
            }
            $do_recursive = false;
            if ($DB->fieldExists($itemtable, 'is_recursive')) {
               $do_recursive = true;
            }
            foreach ($entities as $entID => $val) {
               if ($do_recursive) {
                  // Non recursive ones
                  // needs DB::update to support subqueries to be migrated
                  $query3 = "UPDATE `glpi_infocoms`
                             SET `entities_id` = '$entID',
                                 `is_recursive` = 0
                             WHERE `itemtype` = '$name'
                                   AND `items_id` IN (SELECT `id`
                                                      FROM `$itemtable`
                                                      WHERE `entities_id` = '$entID'
                                                            AND `is_recursive` = 0)";
                  $DB->queryOrDie($query3, "update entities_id and is_recursive=0
                                  in glpi_infocoms for $name");

                  // Recursive ones
                  // needs DB::update to support subqueries to be migrated
                  $query3 = "UPDATE `glpi_infocoms`
                             SET `entities_id` = '$entID',
                                 `is_recursive` = 1
                             WHERE `itemtype` = '$name'
                                   AND `items_id` IN (SELECT `id`
                                                      FROM `$itemtable`
                                                      WHERE `entities_id` = '$entID'
                                                            AND `is_recursive` = 1)";
                  $DB->queryOrDie($query3, "update entities_id and is_recursive=1
                                  in glpi_infocoms for $name");
               } else {
                  // needs DB::update to support subqueries to be migrated
                  $query3 = "UPDATE `glpi_infocoms`
                             SET `entities_id` = '$entID'
                             WHERE `itemtype` = '$name'
                                   AND `items_id` IN (SELECT `id`
                                                      FROM `$itemtable`
                                                      WHERE `entities_id` = '$entID')";
                  $DB->queryOrDie($query3, "update entities_id in glpi_infocoms
                                  for $name");
               }
            } // each entity
         } // each plugin type
      }

      foreach ($typetoname as $num => $name) {
         foreach ($plugtables as $table) {
            $DB->updateOrDie(
               $table, [
                  'itemtype' => $name
               ], [
                  'itemtype' => $num
               ],
               "update itemtype of table $table for $name"
            );
         }
      }
   }


   /**
    * @param $width
   **/
   function showSystemInformations($width) {

      // No need to translate, this part always display in english (for copy/paste to forum)

      echo "\n<tr class='tab_bg_2'><th>Plugins list</th></tr>";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      $plug     = new Plugin();
      $pluglist = $plug->find("", "name, directory");
      foreach ($pluglist as $plugin) {
         $msg  = substr(str_pad($plugin['directory'], 30), 0, 20).
                 " Name: ".Toolbox::substr(str_pad($plugin['name'], 40), 0, 30).
                 " Version: ".str_pad($plugin['version'], 10).
                 " State: ";

         switch ($plugin['state']) {
            case self::ANEW :
               $msg .=  'New';
               break;

            case self::ACTIVATED :
               $msg .=  'Enabled';
               break;

            case self::NOTINSTALLED :
               $msg .=  'Not installed';
               break;

            case self::TOBECONFIGURED :
               $msg .=  'To be configured';
               break;

            case self::NOTACTIVATED :
               $msg .=  'Not activated';
               break;

            case self::TOBECLEANED :
            default :
               $msg .=  'To be cleaned';
               break;
         }
         echo wordwrap("\t".$msg."\n", $width, "\n\t\t");
      }
      echo "\n</pre></td></tr>";
   }


   /**
    * Define a new class managed by a plugin
    *
    * @param $itemtype        class name
    * @param $attrib    array of attributes, a hashtable with index in
    *                         (classname, typename, reservation_types)
    *
    * @return bool
   **/
   static function registerClass($itemtype, $attrib = []) {
      global $CFG_GLPI;

      $plug = isPluginItemType($itemtype);
      if (!$plug) {
         return false;
      }
      $plugin = strtolower($plug['plugin']);

      if (isset($attrib['doc_types'])) {
         $attrib['document_types'] = $attrib['doc_types'];
         unset($attrib['doc_types']);
      }
      if (isset($attrib['helpdesk_types'])) {
         $attrib['ticket_types'] = $attrib['helpdesk_types'];
         unset($attrib['helpdesk_types']);
      }
      if (isset($attrib['netport_types'])) {
         $attrib['networkport_types'] = $attrib['netport_types'];
         unset($attrib['netport_types']);
      }

      foreach (['contract_types', 'directconnect_types', 'document_types',
                     'helpdesk_visible_types', 'infocom_types', 'linkgroup_tech_types',
                     'linkgroup_types', 'linkuser_tech_types', 'linkuser_types', 'location_types',
                     'networkport_instantiations', 'networkport_types',
                     'notificationtemplates_types', 'planning_types', 'reservation_types',
                     'rulecollections_types', 'systeminformations_types', 'ticket_types',
                     'unicity_types', 'link_types', 'kb_types'] as $att) {

         if (isset($attrib[$att]) && $attrib[$att]) {
            array_push($CFG_GLPI[$att], $itemtype);
            unset($attrib[$att]);
         }
      }

      if (isset($attrib['device_types']) && $attrib['device_types']
          && method_exists($itemtype, 'getItem_DeviceType')) {

         if (class_exists($itemtype::getItem_DeviceType())) {
            array_push($CFG_GLPI['device_types'], $itemtype);
         }
         unset($attrib[$att]);
      }

      if (isset($attrib['addtabon'])) {
         if (!is_array($attrib['addtabon'])) {
            $attrib['addtabon'] = [$attrib['addtabon']];
         }
         foreach ($attrib['addtabon'] as $form) {
            CommonGLPI::registerStandardTab($form, $itemtype);
         }
      }

      //Manage entity forward from a source itemtype to this itemtype
      if (isset($attrib['forwardentityfrom'])) {
         CommonDBTM::addForwardEntity($attrib['forwardentityfrom'], $itemtype);
      }

      // Use it for plugin debug
      // if (count($attrib)) {
      //    foreach ($attrib as $key => $val) {
      //       Toolbox::logInFile('debug',"Attribut $key used by $itemtype no more used for plugins\n");
      //    }
      // }
      return true;
   }


   /**
    * This function executes a hook.
    *
    * @param $name   Name of hook to fire
    * @param $param  Parameters if needed : if object limit to the itemtype (default NULL)
    *
    * @return mixed $data
   **/
   static function doHook ($name, $param = null) {
      global $PLUGIN_HOOKS;

      if ($param == null) {
         $data = func_get_args();
      } else {
         $data = $param;
      }

      // Apply hook only for the item
      if (($param != null) && is_object($param)) {
         $itemtype = get_class($param);
         if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
            foreach ($PLUGIN_HOOKS[$name] as $plug => $tab) {
               if (isset($tab[$itemtype])) {
                  if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                     include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
                  }
                  if (is_callable($tab[$itemtype])) {
                     call_user_func($tab[$itemtype], $data);
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
                  call_user_func($function, $data);
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
    *
    * @param $name   Name of hook to fire
    * @param $parm   Parameters (default NULL)
    *
    * @return mixed $data
   **/
   static function doHookFunction($name, $parm = null) {
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
    *
    * @param $plugname        Name of the plugin
    * @param $hook            function to be called (may be an array for call a class method)
    * @param $options   array of params passed to the function
    *
    * @return mixed $data
   **/
   static function doOneHook($plugname, $hook, $options = []) {

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
   **/
   static function getDropdowns() {

      $dps = [];
      if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
         foreach ($_SESSION["glpi_plugins"] as  $plug) {
            $tab = self::doOneHook($plug, 'getDropdown');
            if (is_array($tab)) {
               $dps      = array_merge($dps, [self::getInfo($plug, 'name') => $tab]);
            }
         }
      }
      return $dps;
   }


   /**
    * get information from a plugin
    *
    * @param $plugin String name of the plugin
    * @param $info   String wanted info (name, version, ...), NULL for all
    *
    * @since 0.84
    *
    * @return String or Array (when $info is NULL)
   **/
   static function getInfo($plugin, $info = null) {

      $fct = 'plugin_version_'.strtolower($plugin);
      if (function_exists($fct)) {
         $res = $fct();
         if (!isset($res['requirements']) && isset($res['minGlpiVersion'])) {
            $res['requirements'] = ['glpi' => ['min' => $res['minGlpiVersion']]];
         }
      } else {
         Toolbox::logError("$fct method must be defined!");
         $res = [];
      }
      if (isset($info)) {
         return (isset($res[$info]) ? $res[$info] : '');
      }
      return $res;
   }


   /**
    * Get database relations for plugins
    *
    * @return Array containing plugin database relations
   **/
   static function getDatabaseRelations() {

      $dps = [];
      if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
         foreach ($_SESSION["glpi_plugins"] as $plug) {
            if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
               include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
            }
            $function2 = "plugin_".$plug."_getDatabaseRelations";
            if (function_exists($function2)) {
               $dps = array_merge_recursive($dps, $function2());
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
   **/
   static function getAddSearchOptions($itemtype) {

      $sopt = [];
      if (isset($_SESSION['glpi_plugins']) && count($_SESSION['glpi_plugins'])) {
         foreach ($_SESSION['glpi_plugins'] as $plug) {
            if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
               include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
            }
            $function = "plugin_".$plug."_getAddSearchOptions";
            if (function_exists($function)) {
               $tmp = $function($itemtype);
               if (is_array($tmp) && count($tmp)) {
                  $sopt += $tmp;
               }
            }
         }
      }
      return $sopt;
   }


   /**
    * Get additional search options managed by plugins
    *
    * @since 9.2
    *
    * @param string $itemtype Item type
    *
    * @return array an *indexed* array of search options
    *
    * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
   **/
   static function getAddSearchOptionsNew($itemtype) {
      $options = [];

      if (isset($_SESSION['glpi_plugins']) && count($_SESSION['glpi_plugins'])) {
         foreach ($_SESSION['glpi_plugins'] as $plug) {
            if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
               include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
            }
            $function = "plugin_".$plug."_getAddSearchOptionsNew";
            if (function_exists($function)) {
               $tmp = $function($itemtype);
               foreach ($tmp as $opt) {
                  if (!isset($opt['id'])) {
                     throw new \Exception($itemtype . ': invalid search option! ' . print_r($opt, true));
                  }
                  $optid = $opt['id'];
                  unset($opt['id']);

                  if (isset($options[$optid])) {
                     $message = "Duplicate key $optid ({$options[$optid]['name']}/{$opt['name']}) in ".
                        $itemtype . " searchOptions!";
                     Toolbox::logError($message);
                  }

                  foreach ($opt as $k => $v) {
                     $options[$optid][$k] = $v;
                  }
               }
            }
         }
      }

      return $options;
   }

   /**
    * test is a import plugin is enable
    *
    * @return boolean
    *
    * @since 0.84
   **/
   static function haveImport() {
      global $PLUGIN_HOOKS;

      return (isset($PLUGIN_HOOKS['import_item']) && count($PLUGIN_HOOKS['import_item']));
   }

   /**
    * Get an internationnalized message for incomatible plugins (either core or php version)
    *
    * @param string $type Either 'php' or 'core', defaults to 'core'
    * @param string $min  Minimal required version
    * @param string $max  Maximal required version
    *
    * @since 9.2
    *
    * @return string
    */
   static public function messageIncompatible($type = 'core', $min = null, $max = null) {
      $type = ($type === 'core' ? __('GLPI') : __('PHP'));
      if ($min === null && $max !== null) {
         return sprintf(
            __('This plugin requires %1$s < %2$s.'),
            $type,
            $max
         );
      } else if ($min !== null && $max === null) {
         return sprintf(
            __('This plugin requires %1$s > %2$s.'),
            $type,
            $min
         );

      } else {
         return sprintf(
            __('This plugin requires %1$s > %2$s and < %3$s'),
            $type,
            $min,
            $max
         );
      }
   }

   /**
    * Get an internationnalized message for missing requirement (extension, other plugin, ...)
    *
    * @param string $type Type of what is missing, one of:
    *                     - ext (PHP module)
    *                     - plugin (other plugin)
    *                     - compil (compilation option)
    *                     - param (GLPI configuration parameter)
    * @param string $name Missing name
    *
    * @since 9.2
    *
    * @return string
    */
   static public function messageMissingRequirement($type, $name) {
      switch ($type) {
         case 'ext':
            return sprintf(
               __('This plugin requires PHP extension %1$s'),
               $name
            );
            break;
         case 'plugin':
            return sprintf(
               __('This plugin requires %1$s plugin'),
               $name
            );
            break;
         case 'compil':
            return sprintf(
               __('This plugin requires PHP compiled along with "%1$s"'),
               $name
            );
            break;
         case 'param':
            return sprintf(
               __('This plugin requires PHP parameter %1$s'),
               $name
            );
            break;
         case 'glpiparam':
            return sprintf(
               __('This plugin requires GLPI parameter %1$s'),
               $name
            );
            break;
         default:
            throw new \RuntimeException("messageMissing type $type is unknwown!");
      }
   }

   /**
    * Check declared versions (GLPI, PHP, ...)
    *
    * @since 9.2
    *
    * @param integer $plugid Plugin id
    *
    * @return boolean
    */
   public function checkVersions($name) {
      $infos = self::getInfo($name);
      $ret = true;
      if (isset($infos['requirements'])) {
         if (isset($infos['requirements']['glpi'])) {
            $glpi = $infos['requirements']['glpi'];
            if (isset($glpi['min']) || isset($glpi['max'])) {
               $ret = $ret && $this->checkGlpiVersion($infos['requirements']['glpi']);
            }
            if (isset($glpi['params'])) {
               $ret = $ret && $this->checkGlpiParameters($glpi['params']);
            }
            if (isset($glpi['plugins'])) {
               $ret = $ret && $this->checkGlpiPlugins($glpi['plugins']);
            }
         }
         if (isset($infos['requirements']['php'])) {
            $php = $infos['requirements']['php'];
            if (isset($php['min']) || isset($php['max'])) {
               $ret = $ret && $this->checkPhpVersion($php);
            }
            if (isset($php['exts'])) {
               $ret = $ret && $this->checkPhpExtensions($php['exts']);
            }
            if (isset($php['params'])) {
               $ret = $ret && $this->checkPhpParameters($php['params']);
            }
         }
      }
      return $ret;
   }

   /**
    * Check for GLPI version
    *
    * @since 9.2
    * @since 9.3 Removed the 'dev' key of $info parameter.
    *
    * @param array $infos Requirements infos:
    *                     - min: minimal supported version,
    *                     - max: maximal supported version
    *                     One of min or max is required.
    *
    * @return boolean
    */
   public function checkGlpiVersion($infos) {
      if (!isset($infos['min']) && !isset($infos['max'])) {
         throw new LogicException('Either "min" or "max" is required for GLPI requirements!');
      }

      $glpiVersion = $this->isGlpiPrever() ? $this->getGlpiPrever() : $this->getGlpiVersion();

      $compat = true;
      if (isset($infos['min']) && !version_compare($glpiVersion, $infos['min'], '>=')) {
         $compat = false;
      }
      if (isset($infos['max']) && !version_compare($glpiVersion, $infos['max'], '<')) {
         $compat = false;
      }

      if (!$compat) {
         echo Plugin::messageIncompatible(
            'core',
            (isset($infos['min']) ? $infos['min'] : null),
            (isset($infos['max']) ? $infos['max'] : null)
         );
      }

      return $compat;
   }

   /**
    * Check for PHP version
    *
    * @since 9.2
    *
    * @param array $infos Requirements infos:
    *                     - min: minimal supported version,
    *                     - max: maximal supported version.
    *                     One of min or max is required.
    *
    * @return boolean
    */
   public function checkPhpVersion($infos) {
      $compat = true;

      if (isset($infos['min']) && isset($infos['max'])) {
         $compat = !(version_compare($this->getPhpVersion(), $infos['min'], 'lt') || version_compare($this->getPhpVersion(), $infos['max'], 'ge'));
      } else if (isset($infos['min'])) {
         $compat = !(version_compare($this->getPhpVersion(), $infos['min'], 'lt'));
      } else if (isset($infos['max'])) {
         $compat = !(version_compare($this->getPhpVersion(), $infos['max'], 'ge'));
      } else {
         throw new LogicException('Either "min" or "max" is required for PHP requirements!');
      }

      if (!$compat) {
         echo Plugin::messageIncompatible(
            'php',
            (isset($infos['min']) ? $infos['min'] : null),
            (isset($infos['max']) ? $infos['max'] : null)
         );
      }

      return $compat;
   }


   /**
    * Check fo required PHP extensions
    *
    * @since 9.2
    *
    * @param array $exts Extensions lists/config @see Config::checkExtensions()
    *
    * @return boolean
    */
   public function checkPhpExtensions($exts) {
      $report = Config::checkExtensions($exts);
      if (count($report['missing'])) {
         foreach (array_keys($report['missing']) as $ext) {
            echo self::messageMissingRequirement('ext', $ext) . '<br/>';
         }
         return false;
      }
      return true;
   }


   /**
    * Check expected GLPI parameters
    *
    * @since 9.2
    *
    * @param array $params Expected parameters to be setup
    *
    * @return boolean
    */
   public function checkGlpiParameters($params) {
      global $CFG_GLPI;

      $compat = true;
      foreach ($params as $param) {
         if (!isset($CFG_GLPI[$param]) || trim($CFG_GLPI[$param]) == '' || !$CFG_GLPI[$param]) {
            echo self::messageMissingRequirement('glpiparam', $param) . '<br/>';
            $compat = false;
         }
      }

      return $compat;
   }


   /**
    * Check expected PHP parameters
    *
    * @since 9.2
    *
    * @param array $params Expected parameters to be setup
    *
    * @return boolean
    */
   public function checkPhpParameters($params) {
      $compat = true;
      foreach ($params as $param) {
         if (!ini_get($param) || trim(ini_get($param)) == '') {
            echo self::messageMissingRequirement('param', $param) . '<br/>';
            $compat = false;
         }
      }

      return $compat;
   }


   /**
    * Check expected GLPI plugins
    *
    * @since 9.2
    *
    * @param array $plugins Expected plugins
    *
    * @return boolean
    */
   public function checkGlpiPlugins($plugins) {
      $compat = true;
      foreach ($plugins as $plugin) {
         if (!$this->isInstalled($plugin) || !$this->isActivated($plugin)) {
            echo self::messageMissingRequirement('plugin', $plugin) . '<br/>';
            $compat = false;
         }
      }

      return $compat;
   }


   /**
    * Get GLPI version
    * Used from unit tests to mock.
    *
    * @since 9.2
    *
    * @return string
    */
   public function getGlpiVersion() {
      return GLPI_VERSION;
   }

   /**
    * Get GLPI pre version
    * Used from unit tests to mock.
    *
    * @since 9.2
    *
    * @return string
    */
   public function getGlpiPrever() {
      return GLPI_PREVER;
   }

   /**
    * Check if GLPI version is a pre version
    *
    * @since 9.3
    *
    * @return string
    */
   public function isGlpiPrever() {
      return defined('GLPI_PREVER');
   }

   /**
    * Get PHP version
    * Used from unit tests to mock.
    *
    * @since 9.2
    *
    * @return string
    */
   public function getPhpVersion() {
      return PHP_VERSION;
   }

   /**
    * Return label for an integer plugin state
    *
    * @since 9.3
    *
    * @param  integer $state see this class constants (ex self::ANEW, self::ACTIVATED)
    * @return string  the label
    */
   static function getState($state = 0) {
      switch ($state) {
         case self::ANEW :
            return _x('status', 'New');
            break;

         case self::ACTIVATED :
            return _x('plugin', 'Enabled');
            break;

         case self::NOTINSTALLED :
            return _x('plugin', 'Not installed');
            break;

         case self::NOTUPDATED :
            return __('To update');
            break;

         case self::TOBECONFIGURED :
            return _x('plugin', 'Installed / not configured');
            break;

         case self::NOTACTIVATED :
            return _x('plugin', 'Installed / not activated');
            break;
      }

      return __('Error / to clean');
   }
}
