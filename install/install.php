<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\System\Requirement\DbConfiguration;

define('GLPI_ROOT', realpath('..'));

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");

$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

Config::detectRootDoc();

//Print a correct  Html header for application
function header_html($etape) {
   // Send UTF8 Headers
   header("Content-Type: text/html; charset=UTF-8");

   echo "<!DOCTYPE html'>";
   echo "<html lang='fr'>";
   echo "<head>";
   echo "<meta charset='utf-8'>";
   echo "<meta http-equiv='Content-Script-Type' content='text/javascript'> ";
   echo "<meta http-equiv='Content-Style-Type' content='text/css'> ";
   echo "<title>Setup GLPI</title>";

   // CFG
   echo Html::getCoreVariablesForJavascript();

    // LIBS
   echo Html::script("public/lib/base.js");
   echo Html::script("public/lib/fuzzy.js");
   echo Html::script("js/common.js");

    // CSS
   echo Html::css('public/lib/base.css');
   echo Html::scss("css/style_install");
   echo "</head>";
   echo "<body>";
   echo "<div id='principal'>";
   echo "<div id='bloc'>";
   echo "<div id='logo_bloc'></div>";
   echo "<h2>GLPI SETUP</h2>";
   echo "<br><h3>". $etape ."</h3>";
}


//Display a great footer.
function footer_html() {
   echo "</div></div></body></html>";
}


// choose language
function choose_language() {
   global $CFG_GLPI;

   // fix missing param for js drodpown
   $CFG_GLPI['ajax_limit_count'] = 15;

   TemplateRenderer::getInstance()->display('install/choose_language.html.twig', [
      'languages_dropdown'  => Dropdown::showLanguages('language', [
         'display' => false,
         'value'   => $_SESSION['glpilanguage'],
         'width'   => '100%'
      ])
   ]);
}


function acceptLicense() {
   TemplateRenderer::getInstance()->display('install/accept_license.html.twig', [
      'copying' => file_get_contents("../COPYING.txt"),
   ]);
}


//confirm install form
function step0() {
   TemplateRenderer::getInstance()->display('install/step0.html.twig');
}


//Step 1 checking some compatibility issue and some write tests.
function step1($update) {
   ob_start();
   $error = Toolbox::commonCheckForUseGLPI(true);
   $checks = ob_get_contents();
   ob_end_clean();

   TemplateRenderer::getInstance()->display('install/step1.html.twig', [
      'update' => $update,
      'checks' => $checks,
      'error'  => $error,
   ]);
}


//step 2 import mysql settings.
function step2($update) {
   TemplateRenderer::getInstance()->display('install/step2.html.twig', [
      'update' => $update,
   ]);
}


//step 3 test mysql settings and select database.
function step3($host, $user, $password, $update) {

   error_reporting(16);

   //Check if the port is in url
   $hostport = explode(":", $host);
   if (count($hostport) < 2) {
      $link = new mysqli($hostport[0], $user, $password);
   } else {
      $link = new mysqli($hostport[0], $user, $password, '', $hostport[1]);
   }

   $dbversion_message = "";
   $config_message    = "";
   $dbversion         = "";
   $config_valid      = false;
   $dbversion_ok      = false;
   if (!$link->connect_error) {
      $_SESSION['db_access'] = [
         'host'     => $host,
         'user'     => $user,
         'password' => $password
      ];

      //get database raw version
      $DB_ver = $link->query("SELECT version()");
      $row = $DB_ver->fetch_array();
      $result = Config::checkDbEngine($row[0]);
      $dbversion = key($result);

      if (!$dbversion) {
         $dbversion_message = sprintf(__('Your database engine version seems too old: %s.'), $dbversion);
      } else {
         $dbversion_message = sprintf(__('Database version seems correct (%s) - Perfect!'), $dbversion);
         $dbversion_ok      = true;
      }

      // Check DB config
      $db = new class($link) extends DBmysql {
         public function __construct($dbh) {
            $this->dbh = $dbh;
         }
      };
      $config_requirement = new DbConfiguration($db);
      $config_valid       = $config_requirement->isValidated();
      $config_messages    = implode('<br />', $config_requirement->getValidationMessages());
   }

   // get databases
   $databases = [];
   if ($config_valid) {
      if ($DB_list = $link->query("SHOW DATABASES")) {
         while ($row = $DB_list->fetch_array()) {
            if (!in_array($row['Database'], [
               "information_schema",
               "mysql",
               "performance_schema"
            ])) {
               $databases[] = $row['Database'];
            }
         }
      }
   }

   // display html
   TemplateRenderer::getInstance()->display('install/step3.html.twig', [
      'update'            => $update,
      'link'              => $link,
      'host'              => $host,
      'user'              => $user,
      'dbversion'         => $dbversion,
      'dbversion_ok'      => $dbversion_ok,
      'dbversion_message' => $dbversion_message,
      'config_valid'      => $config_valid,
      'config_messages'   => $config_messages,
      'databases'         => $databases,
   ]);
}


//Step 4 Create and fill database.
function step4 ($databasename, $newdatabasename) {

   $host     = $_SESSION['db_access']['host'];
   $user     = $_SESSION['db_access']['user'];
   $password = $_SESSION['db_access']['password'];

   //display the form to return to the previous step.
   echo "<h3>".__('Initialization of the database')."</h3>";

   function prev_form($host, $user, $password) {

      echo "<br><form action='install.php' method='post'>";
      echo "<input type='hidden' name='db_host' value='". $host ."'>";
      echo "<input type='hidden' name='db_user' value='". $user ."'>";
      echo " <input type='hidden' name='db_pass' value='". rawurlencode($password) ."'>";
      echo "<input type='hidden' name='update' value='no'>";
      echo "<input type='hidden' name='install' value='Etape_2'>";
      echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
            __s('Back')."'></p>";
      Html::closeForm();
   }

   //Display the form to go to the next page
   function next_form() {

      echo "<br><form action='install.php' method='post'>";
      echo "<input type='hidden' name='install' value='Etape_4'>";
      echo "<button type='submit' name='submit' class='btn btn-primary'>
         ".__('Continue')."
         <i class='fas fa-chevron-right ms-1'></i>
      </button>";
      Html::closeForm();
   }

   //create security key
   $glpikey = new GLPIKey();
   $secured = $glpikey->keyExists();
   if (!$secured) {
      $secured = $glpikey->generate();
   }

   if (!$secured) {
      echo "<p><strong>".__('Security key cannot be generated!')."</strong></p>";
      prev_form($host, $user, $password);
      return;
   }

   //Check if the port is in url
   $hostport = explode(":", $host);
   if (count($hostport) < 2) {
      $link = new mysqli($hostport[0], $user, $password);
   } else {
      $link = new mysqli($hostport[0], $user, $password, '', $hostport[1]);
   }

   $databasename    = $link->real_escape_string($databasename);
   $newdatabasename = $link->real_escape_string($newdatabasename);

   if (!empty($databasename)) { // use db already created
      $DB_selected = $link->select_db($databasename);

      if (!$DB_selected) {
         echo __('Impossible to use the database:');
         echo "<br>".sprintf(__('The server answered: %s'), $link->error);
         prev_form($host, $user, $password);

      } else {
         if (DBConnection::createMainConfig($host, $user, $password, $databasename, true)) {
            Toolbox::createSchema($_SESSION["glpilanguage"]);
            echo "<p>".__('OK - database was initialized')."</p>";

            next_form();

         } else { // can't create config_db file
            echo "<p>".__('Impossible to write the database setup file')."</p>";
            prev_form($host, $user, $password);
         }
      }

   } else if (!empty($newdatabasename)) { // create new db
      // Try to connect
      if ($link->select_db($newdatabasename)) {
         echo "<p>".__('Database created')."</p>";

         if (DBConnection::createMainConfig($host, $user, $password, $newdatabasename, true)) {
            Toolbox::createSchema($_SESSION["glpilanguage"]);
            echo "<p>".__('OK - database was initialized')."</p>";
            next_form();

         } else { // can't create config_db file
            echo "<p>".__('Impossible to write the database setup file')."</p>";
            prev_form($host, $user, $password);
         }

      } else { // try to create the DB
         if ($link->query("CREATE DATABASE IF NOT EXISTS `".$newdatabasename."`")) {
            echo "<p>".__('Database created')."</p>";

            if ($link->select_db($newdatabasename)
                && DBConnection::createMainConfig($host, $user, $password, $newdatabasename, true)) {

               Toolbox::createSchema($_SESSION["glpilanguage"]);
               echo "<p>".__('OK - database was initialized')."</p>";
               next_form();

            } else { // can't create config_db file
               echo "<p>".__('Impossible to write the database setup file')."</p>";
               prev_form($host, $user, $password);
            }

         } else { // can't create database
            echo __('Error in creating database!');
            echo "<br>".sprintf(__('The server answered: %s'), $link->error);
            prev_form($host, $user, $password);
         }
      }

   } else { // no db selected
      echo "<p>".__("You didn't select a database!"). "</p>";
      //prev_form();
      prev_form($host, $user, $password);
   }

   $link->close();

}

//send telemetry information
function step6() {
   global $DB;

   include_once(GLPI_ROOT . "/inc/dbmysql.class.php");
   include_once(GLPI_CONFIG_DIR . "/config_db.php");
   $DB = new DB();

   TemplateRenderer::getInstance()->display('install/step6.html.twig', [
      'telemetry_info' => Telemetry::showTelemetry(),
      'reference_info' => Telemetry::showReference(),
   ]);
}

function step7() {
   TemplateRenderer::getInstance()->display('install/step7.html.twig', [
      'glpinetwork' => GLPINetwork::showInstallMessage(),
   ]);
}

// finish installation
function step8() {
   include_once(GLPI_ROOT . "/inc/dbmysql.class.php");
   include_once(GLPI_CONFIG_DIR . "/config_db.php");
   $DB = new DB();

   if (isset($_POST['send_stats'])) {
      //user has accepted to send telemetry infos; activate cronjob
      $DB->update(
         'glpi_crontasks',
         ['state' => 1],
         ['name' => 'telemetry']
      );
   }

   $url_base = str_replace("/install/install.php", "", $_SERVER['HTTP_REFERER']);
   $DB->update(
      'glpi_configs',
      ['value' => $DB->escape($url_base)], [
         'context'   => 'core',
         'name'      => 'url_base'
      ]
   );

   $url_base_api = "$url_base/apirest.php/";
   $DB->update(
      'glpi_configs',
      ['value' => $DB->escape($url_base_api)], [
         'context'   => 'core',
         'name'      => 'url_base_api'
      ]
   );

   Session::destroy(); // Remove session data (debug mode for instance) set by web installation

   TemplateRenderer::getInstance()->display('install/step8.html.twig');
}


function update1($DBname) {

   $host     = $_SESSION['db_access']['host'];
   $user     = $_SESSION['db_access']['user'];
   $password = $_SESSION['db_access']['password'];

   if ($success = DBConnection::createMainConfig($host, $user, $password, $DBname) && !empty($DBname)) {
      include_once (GLPI_CONFIG_DIR . "/config_db.php");
      global $DB;
      $DB = new DB();
      if ($DB->listTables('glpi\_%', ['table_collation' => 'utf8mb4_unicode_ci'])->count() > 0) {
         // Use utf8mb4 charset for update process if at least one table already uses this charset.
         if ($success = DBConnection::updateConfigProperty('use_utf8mb4', true)) {
            $DB->use_utf8mb4 = true;
            $DB->setConnectionCharset();
         }
      }
   }
   if ($success) {
      $from_install = true;
      include_once(GLPI_ROOT ."/install/update.php");
   } else { // can't create config_db file
      echo __("Can't create the database connection file, please verify file permissions.");
      echo "<h3>".__('Do you want to continue?')."</h3>";
      echo "<form action='install.php' method='post'>";
      echo "<input type='hidden' name='update' value='yes'>";
      echo "<p class='submit'><input type='hidden' name='install' value='Etape_0'>";
      echo "<input type='submit' name='submit' class='submit' value=\"".__('Continue')."\">";
      echo "</p>";
      Html::closeForm();
   }
}



//------------Start of install script---------------------------


// Use default session dir if not writable
if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
}

Session::start();
error_reporting(0); // we want to check system before affraid the user.

if (isset($_POST["language"])) {
   $_SESSION["glpilanguage"] = $_POST["language"];
}

Session::loadLanguage('', false);

/**
 * @since 0.84.2
**/
function checkConfigFile() {

   if (file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
      Html::redirect($CFG_GLPI['root_doc'] ."/index.php");
      die();
   }
}

if (!isset($_SESSION['can_process_install']) || !isset($_POST["install"])) {
   $_SESSION = [];

   $_SESSION["glpilanguage"] = Session::getPreferredLanguage();

   checkConfigFile();

   // Add a flag that will be used to validate that installation can be processed.
   // This flag is put here just after checking that DB config file does not exist yet.
   // It is mandatory to validate that `Etape_4` to `Etape_6` are not used outside installation process
   // to change GLPI base URL without even being authenticated.
   $_SESSION['can_process_install'] = true;

   header_html(__("Select your language"));
   choose_language();

} else {
   // Check valid Referer :
   Toolbox::checkValidReferer();
   // Check CSRF: ensure nobody strap first page that checks if config file exists ...
   Session::checkCSRF($_POST);

   // DB clean
   if (isset($_POST["db_pass"])) {
      $_POST["db_pass"] = stripslashes($_POST["db_pass"]);
      $_POST["db_pass"] = rawurldecode($_POST["db_pass"]);
      $_POST["db_pass"] = stripslashes($_POST["db_pass"]);
   }

   switch ($_POST["install"]) {
      case "lang_select" : // lang ok, go accept licence
         checkConfigFile();
         header_html(SoftwareLicense::getTypeName(1));
         acceptLicense();
         break;

      case "License" : // licence  ok, go choose installation or Update
         checkConfigFile();
         header_html(__('Beginning of the installation'));
         step0();
         break;

      case "Etape_0" : // choice ok , go check system
         checkConfigFile();
         //TRANS %s is step number
         header_html(sprintf(__('Step %d'), 0));
         $_SESSION["Test_session_GLPI"] = 1;
         step1($_POST["update"]);
         break;

      case "Etape_1" : // check ok, go import mysql settings.
         checkConfigFile();
         // check system ok, we can use specific parameters for debug
         Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);

         header_html(sprintf(__('Step %d'), 1));
         step2($_POST["update"]);
         break;

      case "Etape_2" : // mysql settings ok, go test mysql settings and select database.
         checkConfigFile();
         header_html(sprintf(__('Step %d'), 2));
         step3($_POST["db_host"], $_POST["db_user"], $_POST["db_pass"], $_POST["update"]);
         break;

      case "Etape_3" : // Create and fill database
         checkConfigFile();
         header_html(sprintf(__('Step %d'), 3));
         if (empty($_POST["databasename"])) {
            $_POST["databasename"] = "";
         }
         if (empty($_POST["newdatabasename"])) {
            $_POST["newdatabasename"] = "";
         }
         step4($_POST["databasename"],
               $_POST["newdatabasename"]);
         break;

      case "Etape_4" : // send telemetry information
         header_html(sprintf(__('Step %d'), 4));
         step6();
         break;

      case "Etape_5" : // finish installation
         header_html(sprintf(__('Step %d'), 5));
         step7();
         break;

      case "Etape_6" : // finish installation
         header_html(sprintf(__('Step %d'), 6));
         step8();
         break;

      case "update_1" :
         checkConfigFile();
         if (empty($_POST["databasename"])) {
            $_POST["databasename"] = "";
         }
         update1($_POST["databasename"]);
         break;
   }
}
footer_html();
