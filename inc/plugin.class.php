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

class Plugin extends CommonDBTM {

   // Class constant : Plugin state
   const ANEW           = 0;
   const ACTIVATED      = 1;
   const NOTINSTALLED   = 2;
   const TOBECONFIGURED = 3;
   const NOTACTIVATED   = 4;
   const TOBECLEANED    = 5;
   const NOTUPDATED     = 6;


   /**
    * Retrieve an item from the database using its directory
    *
    *@param $dir directory of the plugin
    *@return true if succeed else false
    *
   **/
   function getFromDBbyDir($dir) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE (`directory` = '" . $dir . "')";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         } else {
            return false;
         }
      }
      return false;
   }

   /**
   * Init plugins list reading plugins directory
   * @return nothing
   */
   function init() {

      $this->checkStates();
      $plugins=$this->find('state='.self::ACTIVATED);

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
   static function load($name, $withhook=false) {
      global $CFG_GLPI, $PLUGIN_HOOKS,$LANG,$LOADED_PLUGINS;

      if (file_exists(GLPI_ROOT . "/plugins/$name/setup.php")) {
         include_once(GLPI_ROOT . "/plugins/$name/setup.php");
         if (!isset($LOADED_PLUGINS[$name])) {
            Plugin::loadLang($name);
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
   * Load lang file for a plugin
   *
   * @param $name Name of hook to use
   * @param $forcelang force a specific lang
   *
   * @return nothing
   */
   static function loadLang($name,$forcelang='') {
      global $CFG_GLPI,$LANG;

      $trytoload='en_GB';
      if (isset($_SESSION['glpilanguage'])) {
         $trytoload=$_SESSION["glpilanguage"];
      }
      // Force to load a specific lang
      if (!empty($forcelang)) {
         $trytoload=$forcelang;
      }

      // If not set try default lang file
      if (empty($trytoload)) {
         $trytoload=$CFG_GLPI["language"];
      }
      $dir=GLPI_ROOT . "/plugins/$name/locales/";

      if (file_exists($dir.$CFG_GLPI["languages"][$trytoload][1])) {
         include ($dir.$CFG_GLPI["languages"][$trytoload][1]);

      } else if (file_exists($dir.$CFG_GLPI["languages"][$CFG_GLPI["language"]][1])) {

         include ($dir.$CFG_GLPI["languages"][$CFG_GLPI["language"]][1]);

      } else if (file_exists($dir . "en_GB.php")) {
         include ($dir . "en_GB.php");
      } else if (file_exists($dir . "fr_FR.php")) {
         include ($dir . "fr_FR.php");
      }
   }

   /**
    * Check plugins states and detect new plugins
    *
   **/
   function checkStates() {
      global $LANG;

      //// Get all plugins
      // Get all from DBs
      $pluglist=$this->find("","name, directory");
      $db_plugins=array();
      if (count($pluglist)) {
         foreach ($pluglist as $plug) {
            $db_plugins[$plug['directory']]=$plug['id'];
         }
      }
      // Parse plugin dir
      $file_plugins=array();
      $error_plugins=array();
      $dirplug=GLPI_ROOT."/plugins";
      $dh  = opendir($dirplug);
      while (false !== ($filename = readdir($dh))) {
         if ($filename!=".svn" && $filename!="." && $filename!=".."
             && is_dir($dirplug."/".$filename)) {
            // Find version
            if (file_exists($dirplug."/".$filename."/setup.php")) {
               Plugin::loadLang($filename);
               include_once($dirplug."/".$filename."/setup.php");
               $function="plugin_version_$filename";
               if (function_exists($function)) {
                  $file_plugins[$filename]=$function();
                  $file_plugins[$filename]=addslashes_deep($file_plugins[$filename]);
               }
            }
         }
      }
      // check plugin state
      foreach ($db_plugins as $plug => $ID) {
         $install_ok=true;
         // Check file
         if (!isset($file_plugins[$plug])) {
            $this->update(array('id'=>$ID,'state'=>self::TOBECLEANED));
            $install_ok=false;
         } else {
            // Check version
            if ($file_plugins[$plug]['version']!=$pluglist[$ID]['version']) {
               $input=$file_plugins[$plug];
               $input['id']=$ID;
               if ($pluglist[$ID]['version']) {
                  $input['state']=self::NOTUPDATED;
               }
               $this->update($input);
               $install_ok=false;
            }
         }
         // Check install is ok for activated plugins
         if ($install_ok && ($pluglist[$ID]['state'] == self::ACTIVATED)) {
            $usage_ok=true;
            $function="plugin_".$plug."_check_prerequisites";
            if (function_exists($function)) {
               if (!$function()) {
                  $usage_ok=false;
               }
            }
            $function="plugin_".$plug."_check_config";
            if (function_exists($function)){
               if (!$function()) {
                  $usage_ok=false;
               }
            } else {
               $usage_ok=false;
            }
            if (!$usage_ok) {
               $input=$file_plugins[$plug];
               $input['id']=$ID;
               $this->update($input);
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
                  if (isset($check['directory']) && $check['directory'] == $data['oldname']) {
                     $data['state']=self::NOTUPDATED;
                     $this->delete(array('id'=>$check['id']));
                  }
               }
            } else {
               $data['state']=self::NOTINSTALLED;
            }
            $data['directory']=$plug;
            $this->add($data);
         }
      }
   }

   /**
    * List availabled plugins
    *
   **/
   function listPlugins() {
      global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;

      $this->checkStates();
      echo "<div class='center'><table class='tab_cadrehov'>";

      // ligne a modifier en fonction de la modification des fichiers de langues
      echo "<tr><th colspan='7'>".$LANG['plugins'][0]."</th></tr>\n";
      echo "<tr><th>".$LANG['common'][16]."</th><th>".$LANG['rulesengine'][78]."</th>";
      echo "<th>".$LANG['state'][0]."</th><th>".$LANG['common'][37]."</th>";
      echo "<th>".$LANG['financial'][45]."</th><th colspan='2'>&nbsp;</th></tr>\n";
      $pluglist=$this->find("","name, directory");
      $i=0;
      $PLUGIN_HOOKS_SAVE=$PLUGIN_HOOKS;
      foreach ($pluglist as $ID => $plug) {
         if (function_exists("plugin_".$plug['directory']."_check_config")) {
            // init must not be called for incompatible plugins
            self::load($plug['directory'],true);
         }
         $i++;
         $class='tab_bg_1';
         if ($i%2==0){
            $class='tab_bg_2';
         }
         echo "<tr class='$class'>";
         echo "<td>";
         $name=trim($plug['name']);
         if (empty($name)) {
            $plug['name']=$plug['directory'];
         }

         // Only config for install plugins
         if (in_array($plug['state'],array(self::ACTIVATED,
                                           self::TOBECONFIGURED,
                                           self::NOTACTIVATED))
             && isset($PLUGIN_HOOKS['config_page'][$plug['directory']])) {

            echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/".$plug['directory']."/".
                   $PLUGIN_HOOKS['config_page'][$plug['directory']]."'><strong>".$plug['name'].
                  "</strong></a>";
         } else {
            echo $plug['name'];
         }
         echo "</td>";
         echo "<td>".$plug['version']."</td>";
         echo "<td>";
         switch ($plug['state']) {
            case self::ANEW :
               echo $LANG['joblist'][9];
               break;

            case self::ACTIVATED :
               echo $LANG['setup'][192];
               break;

            case self::NOTINSTALLED :
               echo $LANG['common'][89];
               break;

            case self::NOTUPDATED :
               echo $LANG['plugins'][6];
               break;

            case self::TOBECONFIGURED :
               echo $LANG['plugins'][2];
               break;

            case self::NOTACTIVATED :
               echo $LANG['plugins'][3];
               break;

            case self::TOBECLEANED :
            default:
               echo $LANG['plugins'][4];
               break;
         }
         echo "</td>";
         echo "<td>".$plug['author']."</td>";
         $weblink=trim($plug['homepage']);
         echo "<td>";
         if (!empty($weblink)) {
            echo "<a href='".formatOutputWebLink($weblink)."' target='_blank'>";
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle' alt='".
                   $LANG['common'][4]."' title='".$LANG['common'][4]."' ></a>";
         } else {
            echo "&nbsp;";
         }
         echo "</td>";

         switch ($plug['state']) {
            case self::ACTIVATED :
               echo "<td>";
               echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=unactivate'>".
                      $LANG['buttons'][42]."</a>";
               echo "</td><td>";
               if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                  echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=uninstall'>".
                         $LANG['buttons'][5]."</a>";
               } else {
                  echo $LANG['plugins'][5]."&nbsp;: "."plugin_".$plug['directory']."_uninstall";
               }
               echo "</td>";
               break;

            case self::ANEW :
            case self::NOTINSTALLED :
            case self::NOTUPDATED :
               echo "<td>";
               if (function_exists("plugin_".$plug['directory']."_install")
                   && function_exists("plugin_".$plug['directory']."_check_config")) {

                  $function = 'plugin_' . $plug['directory'] . '_check_prerequisites';
                  $do_install=true;
                  if (function_exists($function)) {
                     $do_install=$function();
                  }
                  if ($plug['state']==self::NOTUPDATED) {
                     $msg = $LANG['buttons'][58];
                  } else {
                     $msg = $LANG['buttons'][4];
                  }
                  if ($do_install) {
                     echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=install'>".
                            $msg."</a>";
                  }
               } else {
                  echo $LANG['plugins'][5]."&nbsp;:";
                  if (!function_exists("plugin_".$plug['directory']."_install")) {
                     echo " plugin_".$plug['directory']."_install";
                  }
                  if (!function_exists("plugin_".$plug['directory']."_check_config")) {
                     echo " plugin_".$plug['directory']."_check_config";
                  }
               }
               echo "</td><td>";
               if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                  if (function_exists("plugin_".$plug['directory']."_check_config")) {
                     echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=uninstall'>".
                            $LANG['buttons'][5]."</a>";
                  } else {
                     // This is an incompatible plugin (0.71), uninstall fonction could crash
                     echo "&nbsp;";
                  }
               } else {
                  echo $LANG['plugins'][5]."&nbsp;: "."plugin_".$plug['directory']."_uninstall";
               }
               echo "</td>";
               break;

            case self::TOBECONFIGURED :
               echo "<td>";
               $function = 'plugin_' . $plug['directory'] . '_check_config';
               if (function_exists($function)) {
                  if ($function(true)) {
                     $this->update(array('id'=>$ID,
                                         'state'=>self::NOTACTIVATED));
                     glpi_header($this->getSearchURL());
                  }
               } else {
                  echo $LANG['plugins'][5]."&nbsp;: "."plugin_".$plug['directory']."_check_config";
               }
               echo "</td><td>";
               if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                  echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=uninstall'>".
                         $LANG['buttons'][5]."</a>";
               } else {
                  echo $LANG['plugins'][5]."&nbsp;: "."plugin_".$plug['directory']."_uninstall";
               }
               echo "</td>";
               break;

            case self::NOTACTIVATED :
               echo "<td>";
               echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=activate'>".
                      $LANG['buttons'][41]."</a>";
               echo "</td><td>";
               if (function_exists("plugin_".$plug['directory']."_uninstall")) {
                  echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=uninstall'>".
                         $LANG['buttons'][5]."</a>";
               } else {
                  echo $LANG['plugins'][5]."&nbsp;: "."plugin_".$plug['directory']."_uninstall";
               }
               echo "</td>";
               break;

            case self::TOBECLEANED :
            default :
               echo "<td colspan='2'>";
               echo "<a href='".$this->getSearchURL()."?id=$ID&amp;action=clean'>".
                      $LANG['buttons'][53]."</a>";
               echo "</td>";
         }
         echo "</tr>\n";
      }
      echo "</table></div>";
      $PLUGIN_HOOKS=$PLUGIN_HOOKS_SAVE;
   }

   /**
    * uninstall a plugin
    *
    *@param $ID ID of the plugin
   **/
   function uninstall($ID) {

      if ($this->getFromDB($ID)) {

         CronTask::Unregister($this->fields['directory']);

         self::load($this->fields['directory'],true);
         // Run the Plugin's Uninstall Function first
         $function = 'plugin_' . $this->fields['directory'] . '_uninstall';
         if (function_exists($function)) {
            $function();
         }

         $this->update(array('id'=>$ID,
                             'state'=>self::NOTINSTALLED,
                             'version'=>''));
         $this->removeFromSession($this->fields['directory']);


      }
   }

   /**
    * install a plugin
    *
    *@param $ID ID of the plugin
   **/
   function install($ID) {

      if ($this->getFromDB($ID)) {
         self::load($this->fields['directory'],true);
         $function = 'plugin_' . $this->fields['directory'] . '_install';
         $install_ok=false;
         if (function_exists($function)) {
            if ($function()) {
               $function = 'plugin_' . $this->fields['directory'] . '_check_config';
               if (function_exists($function)) {
                  if ($function()) {
                     $this->update(array('id'=>$ID,
                                         'state'=>self::NOTACTIVATED));
                  } else {
                     $this->update(array('id'=>$ID,
                                         'state'=>self::TOBECONFIGURED));
                  }
               }
            }
         }
      }
   }

   /**
    * activate a plugin
    *
    *@param $ID ID of the plugin
   **/
   function activate($ID) {
      global $PLUGIN_HOOKS;

      if ($this->getFromDB($ID)) {
         self::load($this->fields['directory'],true);
         $function = 'plugin_' . $this->fields['directory'] . 'check_prerequisites';
         if (function_exists($function)) {
            if (!$function()) {
               return false;
            }
         }
         $function = 'plugin_' . $this->fields['directory'] . '_check_config';
         if (function_exists($function)) {
            if ($function()) {
               $this->update(array('id'=>$ID,
                                   'state'=>self::ACTIVATED));
               $_SESSION['glpi_plugins'][]=$this->fields['directory'];

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
            }
         }  // exists _check_config
      } // getFromDB
   }

   /**
    * unactivate a plugin
    *
    *@param $ID ID of the plugin
   **/
   function unactivate($ID) {

      if ($this->getFromDB($ID)) {
         $this->update(array('id'=>$ID,
                             'state'=>self::NOTACTIVATED));
         $this->removeFromSession($this->fields['directory']);
      }
   }

   /**
    * unactivate all activated plugins for update process
    *
   **/
   function unactivateAll() {
      global$DB;

      $query = "UPDATE ".
                $this->getTable()."
                SET `state` = ".self::NOTACTIVATED."
                WHERE `state` = ".self::ACTIVATED;
      $DB->query($query);
      $_SESSION['glpi_plugins']=array();
   }

   /**
    * clean a plugin
    *
    *@param $ID ID of the plugin
   **/
   function clean($ID) {

      if ($this->getFromDB($ID)) {
         // Clean crontask after "hard" remove
         CronTask::Unregister($this->fields['directory']);

         $this->delete(array('id'=>$ID));
         $this->removeFromSession($this->fields['directory']);
      }
   }

   /**
    * is a plugin activated
    *
    *@param $plugin plugin directory
   **/
   function isActivated($plugin) {

      if ($this->getFromDBbyDir($plugin)) {
         return ($this->fields['state']==self::ACTIVATED);
      }
   }

   /**
    * is a plugin installed
    *
    *@param $plugin plugin directory
   **/
   function isInstalled($plugin) {

      if ($this->getFromDBbyDir($plugin)) {
         return ($this->fields['state']==self::ACTIVATED
                 || $this->fields['state']==self::TOBECONFIGURED
                 || $this->fields['state']==self::NOTACTIVATED);
      }
   }

   /**
    * remove plugin from session variable
    *
    *@param $plugin plugin directory
   **/
   function removeFromSession($plugin) {

      $key=array_search($plugin,$_SESSION['glpi_plugins']);
      if ($key!==false) {
         unset($_SESSION['glpi_plugins'][$key]);
      }
   }

   /*
    * Migrate itemtype from interger (0.72) to string (0.80)
    *
    * @param $types array of (num=>name) of type manage by the plugin
    * @param $glpitables array of GLPI table name used by the plugin
    * @param $plugtables array of Plugin table name which have an itemtype
    *
    * @return nothing
    */
   static function migrateItemType ($types=array(), $glpitables=array(), $plugtables=array()) {
      global $DB, $LANG;

      $typetoname=array(
          0 => "",// For tickets
          1 => "Computer",
          2 => "NetworkEquipment",
          3 => "Printer",
          4 => "Monitor",
          5 => "Peripheral",
          6 => "Software",
          7 => "Contact",
          8 => "Supplier",
          9 => "Infocom",
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
         38 => "Bookmark",
         39 => "SoftwareVersion",
         40 => "Plugin",
         41 => "ComputerDisk",
         42 => "NetworkPort",
         43 => "TicketFollowup",
         44 => "Budget");

      //Add plugins types
      $typetoname = doHookFunction("migratetypes",$typetoname);

      foreach ($types as $num => $name) {
         $typetoname[$num]=$name;
         foreach ($glpitables as $table) {
            $query = "UPDATE `$table` SET `itemtype` = '$name' WHERE `itemtype` = '$num'";
            $DB->query($query) or die("update itemtype of table $table for $name : ". $DB->error());
         }
      }

      if (in_array('glpi_infocoms', $glpitables)) {
         $entities=getAllDatasFromTable('glpi_entities');
         $entities[0]="Root";

         foreach ($types as $num => $name) {
            $itemtable=getTableForItemType($name);
            if (!TableExists($itemtable)) {
               // Just for security, shouldn't append
               continue;
            }
            $do_recursive=false;
            if (FieldExists($itemtable,'is_recursive')) {
               $do_recursive=true;
            }
            foreach ($entities as $entID => $val) {
               if ($do_recursive) {
                  // Non recursive ones
                  $query3="UPDATE `glpi_infocoms`
                           SET `entities_id`=$entID, `is_recursive`=0
                           WHERE `itemtype`='$name'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID AND `is_recursive`=0)";
                  $DB->query($query3) or die("0.80 update entities_id and is_recursive=0
                        in glpi_infocoms for $name ". $LANG['update'][90] . $DB->error());

                  // Recursive ones
                  $query3="UPDATE `glpi_infocoms`
                           SET `entities_id`=$entID, `is_recursive`=1
                           WHERE `itemtype`='$name'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID AND `is_recursive`=1)";
                  $DB->query($query3) or die("0.80 update entities_id and is_recursive=1
                        in glpi_infocoms for $name ". $LANG['update'][90] . $DB->error());
               } else {
                  $query3="UPDATE `glpi_infocoms`
                           SET `entities_id`=$entID
                           WHERE `itemtype`='$name'
                              AND `items_id` IN (SELECT `id` FROM `$itemtable`
                              WHERE `entities_id`=$entID)";
                  $DB->query($query3) or die("0.80 update entities_id in glpi_infocoms
                        for $name ". $LANG['update'][90] . $DB->error());
               }
            } // each entity
         } // each plugin type
      }

      foreach ($typetoname as $num => $name) {
         foreach ($plugtables as $table) {
            $query = "UPDATE `$table` SET `itemtype` = '$name' WHERE `itemtype` = '$num'";
            $DB->query($query) or die("update itemtype of table $table for $name : ". $DB->error());
         }
      }
   }

   function showSystemInformations($width) {
      global $LANG;
      echo "\n</pre></td></tr><tr class='tab_bg_2'><th>" . $LANG['plugins'][0] . "</th></tr>";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      $plug = new Plugin();
      $pluglist=$plug->find("","name, directory");
      foreach ($pluglist as $plugin) {
         $msg = substr(str_pad($plugin['directory'],30),0,16)." ".$LANG['common'][16].":".
                utf8_substr(str_pad($plugin['name'],40),0,30)." ";
         $msg .= $LANG['rulesengine'][78]."&nbsp;:".str_pad($plugin['version'],10)." ";
         $msg .= $LANG['joblist'][0]."&nbsp;:";
         switch ($plugin['state']) {
            case self::ANEW :
               $msg .=  $LANG['joblist'][9];
               break;

            case self::ACTIVATED :
               $msg .=  $LANG['setup'][192];
               break;

            case self::NOTINSTALLED :
               $msg .=  $LANG['common'][89];
               break;

            case self::TOBECONFIGURED :
               $msg .=  $LANG['plugins'][2];
               break;

            case self::NOTACTIVATED :
               $msg .=  $LANG['plugins'][3];
               break;

            case self::TOBECLEANED :
            default :
               $msg .=  $LANG['plugins'][4];
               break;
         }
         echo wordwrap("\t".$msg."\n", $width, "\n\t\t");
      }
      echo "\n</pre></td></tr>";
   }

   /**
    * Define a new class managed by a plugin
    *
    * @param $itemtype class name
    * @param $attrib Array of attributes, a hashtable with index in
    *    (classname, typename, reservation_types)
    *
    * @return bool
    */
   static function registerClass($itemtype, $attrib=array()) {
      global $PLUGIN_HOOKS,$CFG_GLPI;

      $plug = isPluginItemType($itemtype);
      if (!$plug) {
         return false;
      }
      $plugin = strtolower($plug['plugin']);

      foreach (array('contract_types','doc_types','helpdesk_types','helpdesk_visible_types',
                     'infocom_types','linkgroup_types','linkuser_types',
                     'massiveaction_noupdate_types','massiveaction_nodelete_types',
                     'netport_types','reservation_types','notificationtemplates_types') as $att) {
         if (isset($attrib[$att]) && $attrib[$att]) {
            array_push($CFG_GLPI[$att], $itemtype);
            unset($attrib[$att]);
         }
      }

      /// TODO : clean warning when plug
      if (count($attrib)) {
         foreach ($attrib as $key => $val) {
            logInFile('debug',"Attribut $key used by $itemtype no more used for plugins\n");
         }
      }
      return true;
   }
   /**
    * Display plugin actions for a device type
    * @param $item object
    * @param $onglet Heading corresponding of the datas to display
    * @param $withtemplate is the item display like a template ?
    * @return true if display have been done
    */
   static function displayAction(CommonGLPI $item, $onglet=1, $withtemplate=0) {
      global $PLUGIN_HOOKS;

      // Show all Case
      if ($onglet==-1) {
         if (isset($PLUGIN_HOOKS["headings_action"]) &&
             is_array($PLUGIN_HOOKS["headings_action"])
             && count($PLUGIN_HOOKS["headings_action"])) {

            foreach ($PLUGIN_HOOKS["headings_action"] as $plug => $function) {
               if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                  include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
               }
               if (is_callable($function)) {
                  $actions=call_user_func($function, $item);
                  if (is_array($actions) && count($actions)) {
                     foreach ($actions as $key => $action) {
                        if (is_callable($action)) {
                           echo "<br>";
                           call_user_func($action, $item, $withtemplate);
                        }
                     }
                  }
               }
            }
         }
         return true;

      } else {
         if (preg_match("/^(.*)_([0-9]*)$/",$onglet,$split)) {
            $plug = $split[1];
            $ID_onglet = $split[2];
            if (isset($PLUGIN_HOOKS["headings_action"][$plug])) {
               if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                  include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
               }
               $function=$PLUGIN_HOOKS["headings_action"][$plug];
               if (is_callable($function)) {
                  $actions=call_user_func($function, $item);
                  if (isset($actions[$ID_onglet])
                     && is_callable($actions[$ID_onglet])) {
                     $function=$actions[$ID_onglet];
                     call_user_func($actions[$ID_onglet], $item, $withtemplate);
                     return true;
                  }
               }
            }
         }
      }
      return false;
   }

   /**
    * Display plugin headgsin for a device type
    * @param $target page to link
    * @param $item object
    * @param $withtemplate is the item display like a template ?
    * @return Array of tabs (sorted)
    */
   static function getTabs($target, CommonGLPI $item, $withtemplate) {
      global $PLUGIN_HOOKS,$LANG,$CFG_GLPI;

      $template="";
      if (!empty($withtemplate)) {
         $template="&withtemplate=$withtemplate";
      }
      $display_onglets=array();

      $tabpage = $item->getTabsURL();

      $active=false;
      $tabid=0;
      $tabs=array();
      $order=array();
      if (isset($PLUGIN_HOOKS["headings"]) && is_array($PLUGIN_HOOKS["headings"])) {
         foreach ($PLUGIN_HOOKS["headings"] as $plug => $function) {
            if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
               include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
            }
            if (is_callable($function)) {
               $onglet=call_user_func($function, $item, $withtemplate);
               if (is_array($onglet) && count($onglet)) {
                  foreach ($onglet as $key => $val) {
                     $key=$plug."_".$key;
                     $params = "target=$target&itemtype=".get_class($item)."&glpi_tab=$key";
                     if ($item instanceof CommonDBTM) {
                        $params .= "&id=".$item->getField('id')."$template";
                     }
                     $tabs[$key]=array('title'  => $val,
                                       'url'    => $tabpage,
                                       'params' => $params);
                     $order[$key]=$val;
                  }
               }
            }
         }
         // Order plugin tab
         if (count($tabs)) {
            asort($order);
            foreach ($order as $key => $val) {
               $order[$key]=$tabs[$key];
            }
         }
      }
      return $order;
   }


}

?>