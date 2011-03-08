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

include_once (GLPI_ROOT."/config/based_config.php");
include_once (GLPI_ROOT."/inc/dbconnection.class.php");
include (GLPI_ROOT."/config/define.php");

setGlpiSessionPath();
startGlpiSession();

if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   nullHeader("DB Error",$CFG_GLPI["root_doc"]);
   if (!isCommandLine()) {
      echo "<div class='center'>";
      echo "<p>Error : GLPI seems to not be installed properly.</p>";
      echo "<p> config_db.php file is missing.</p>";
      echo "<p>Please restart the install process.</p>";
      echo "<p><a class='red' href='".GLPI_ROOT."'>Click here to proceed</a></p>";
      echo "</div>";
   } else {
      echo "Error : GLPI seems to not be installed properly.\n";
      echo "config_db.php file is missing.\n";
      echo "Please restart the install process.\n";
   }
   nullFooter();

   die();
} else {
   require_once (GLPI_CONFIG_DIR . "/config_db.php");
   include_once (GLPI_CACHE_LITE_DIR."/Lite/Output.php");
   include_once (GLPI_CACHE_LITE_DIR."/Lite/File.php");

   //Database connection
   DBConnection::establishDBConnection((isset($USEDBREPLICATE) ? $USEDBREPLICATE : 0),
                         (isset($DBCONNECTION_REQUIRED) ? $DBCONNECTION_REQUIRED : 0));


   // *************************** Statics config options **********************
   // ********************options d'installation statiques*********************
   // *************************************************************************

   //Options from DB, do not touch this part.

   // Default Use mode
   if (!isset($_SESSION['glpi_use_mode'])) {
      $_SESSION['glpi_use_mode']=NORMAL_MODE;
   }

   $config_object=new Config();
   $config_ok=false;

   if (!isset($_GET['donotcheckversion']) && // use normal config table on restore process
         (isset($TRY_OLD_CONFIG_FIRST) // index case
         || (isset($_SESSION['TRY_OLD_CONFIG_FIRST']) && $_SESSION['TRY_OLD_CONFIG_FIRST']))) { // backup case

      if (isset($_SESSION['TRY_OLD_CONFIG_FIRST'])) {
         unset($_SESSION['TRY_OLD_CONFIG_FIRST']);
      }
      // First try old config table : for update proces management from < 0.80 to >= 0.80
      $config_object->forceTable('glpi_config');
      if ($config_object->getFromDB(1)) {
         $config_ok=true;
      } else {
         $config_object->forceTable('glpi_configs');
         if ($config_object->getFromDB(1)) {
            $config_ok=true;
         }
      }
   } else { // Normal load process : use normal config table. If problem try old one
      if($config_object->getFromDB(1)) {
         $config_ok=true;
      } else {
         // Manage glpi_config table before 0.80
         $config_object->forceTable('glpi_config');
         if ($config_object->getFromDB(1)) {
            $config_ok=true;
         }
      }
   }

   if ($config_ok) {
      $CFG_GLPI=array_merge($CFG_GLPI,$config_object->fields);
      $CFG_GLPI['priority_matrix'] = importArrayFromDB($config_object->fields['priority_matrix'],true);

      Config::detectRootDoc();

      // Path for icon of document type
      $CFG_GLPI["typedoc_icon_dir"] = $CFG_GLPI["root_doc"]."/pics/icones";
   } else {
      echo "Error accessing config table";
      exit();
   }

   // If debug mode activated : display some informations
   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
      // display_errors only need for for E_ERROR, E_PARSE, ... which cannot be catched
      ini_set('display_errors','On');
      // Recommended development settings
      error_reporting(E_ALL | E_STRICT);
      set_error_handler('userErrorHandlerDebug');
   } else {
      // Recommended production settings
      ini_set('display_errors','Off');
      error_reporting(E_ALL);
      set_error_handler('userErrorHandlerNormal');
   }

   if (isset($_SESSION["glpiroot"]) && $CFG_GLPI["root_doc"]!=$_SESSION["glpiroot"]) {
      glpi_header($_SESSION["glpiroot"]);
   }

   // Override cfg_features by session value
   foreach ($CFG_GLPI['user_pref_field'] as $field) {
      if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
         $_SESSION["glpi$field"]=$CFG_GLPI[$field];
      }
   }

   if ((!isset($CFG_GLPI["version"]) || trim($CFG_GLPI["version"])!=GLPI_VERSION)
       && !isset($_GET["donotcheckversion"])) {

      loadLanguage();
      if (isCommandLine()) {
         echo $LANG['update'][88] . "\n";
      } else {
         nullHeader("UPDATE NEEDED",$CFG_GLPI["root_doc"]);
         echo "<div class='center'>";
         echo "<table class='tab_check'>";
         $error=commonCheckForUseGLPI();
         echo "</table><br>";

         if (!$error) {
            if (!isset($CFG_GLPI["version"]) || trim($CFG_GLPI["version"])<GLPI_VERSION) {
               echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/install/update.php'>";
               echo "<p class='red'>".$LANG['update'][88]."</p>";
               echo "<input type='submit' name='from_update' value='".
                      $LANG['install'][4]."' class='submit'>";
               echo "</form>";
            } else if (trim($CFG_GLPI["version"])>GLPI_VERSION) {
               echo "<p class='red'>".$LANG['update'][89]."</p>";
            }
         } else {
            echo "<form action=\"".$CFG_GLPI["root_doc"]."/index.php\" method='post'>";
            echo "<input type='submit' name='submit' class='submit' value=\"".
                   $LANG['install'][27]."\" />";
            echo "</form>";
         }
         echo "</div>";
         nullFooter();
      }
      exit();
   }
}

?>
