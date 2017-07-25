<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/


define('GLPI_ROOT', realpath('..'));

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");

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

    // LIBS
   echo Html::script("../lib/jquery/js/jquery-1.10.2.min.js");
   echo Html::script('lib/jquery/js/jquery-ui-1.10.4.custom.js');
   echo Html::script("../lib/jqueryplugins/select2/select2.min.js");
   echo Html::css('lib/jquery/css/smoothness/jquery-ui-1.10.4.custom.css');
   echo Html::css("../lib/jqueryplugins/select2/select2.css");

   // CSS
   echo "<link rel='stylesheet' href='../css/style_install.css' type='text/css' media='screen'>";
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

   echo "<form action='install.php' method='post'>";
   echo "<p class='center'>";

   // fix missing param for js drodpown
   $CFG_GLPI['ajax_limit_count'] = 15;

   Dropdown::showLanguages("language", ['value' => "en_GB"]);
   echo "</p>";
   echo "";
   echo "<p class='submit'><input type='hidden' name='install' value='lang_select'>";
   echo "<input type='submit' name='submit' class='submit' value='OK'></p>";
   Html::closeForm();
}


function acceptLicense() {

   echo "<div class='center'>";
   echo "<textarea id='license' cols='85' rows='10' readonly='readonly'>";
   readfile("../COPYING.txt");
   echo "</textarea>";

   echo "<br><a target='_blank' href='http://www.gnu.org/licenses/old-licenses/gpl-2.0-translations.html'>".
         __('Unofficial translations are also available')."</a>";

   echo "<form action='install.php' method='post'>";
   echo "<p id='license'>";

   echo "<label for='agree' class='radio'>";
   echo "<input type='radio' name='install' id='agree' value='License'>";
   echo "<span class='outer'><span class='inner'></span></span>";
   echo __('I have read and ACCEPT the terms of the license written above.');
   echo " </label>";

   echo "<label for='disagree' class='radio'>";
   echo "<input type='radio' name='install' value='lang_select' id='disagree' checked='checked'>";
   echo "<span class='outer'><span class='inner'></span></span>";
   echo __('I have read and DO NOT ACCEPT the terms of the license written above');
   echo " </label>";

   echo "<p><input type='submit' name='submit' class='submit' value=\"".__s('Continue')."\"></p>";
   Html::closeForm();
   echo "</div>";
}


//confirm install form
function step0() {

   echo "<h3>".__('Installation or update of GLPI')."</h3>";
   echo "<p>".__s("Choose 'Install' for a completely new installation of GLPI.")."</p>";
   echo "<p> ".__s("Select 'Upgrade' to update your version of GLPI from an earlier version")."</p>";
   echo "<form action='install.php' method='post'>";
   echo "<input type='hidden' name='update' value='no'>";
   echo "<p class='submit'><input type='hidden' name='install' value='Etape_0'>";
   echo "<input type='submit' name='submit' class='submit' value=\""._sx('button', 'Install')."\"></p>";
   Html::closeForm();

   echo "<form action='install.php' method='post'>";
   echo "<input type='hidden' name='update' value='yes'>";
   echo "<p class='submit'><input type='hidden' name='install' value='Etape_0'>";
   echo "<input type='submit' name='submit' class='submit' value=\""._sx('button', 'Upgrade')."\"></p>";
   Html::closeForm();
}


//Step 1 checking some compatibility issue and some write tests.
function step1($update) {
   global $CFG_GLPI;

   $error = 0;
   echo "<h3>".__s('Checking of the compatibility of your environment with the execution of GLPI').
        "</h3>";
   echo "<table class='tab_check'>";

   $error = Toolbox::commonCheckForUseGLPI();

   echo "</table>";
   switch ($error) {
      case 0 :
         echo "<form action='install.php' method='post'>";
         echo "<input type='hidden' name='update' value='". $update."'>";
         echo "<input type='hidden' name='language' value='". $_SESSION['glpilanguage']."'>";
         echo "<p class='submit'><input type='hidden' name='install' value='Etape_1'>";
         echo "<input type='submit' name='submit' class='submit' value=\"".__('Continue')."\">";
         echo "</p>";
         Html::closeForm();
         break;

      case 1 :
         echo "<h3>".__('Do you want to continue?')."</h3>";
         echo "<div class='submit'><form action='install.php' method='post' class='inline'>";
         echo "<input type='hidden' name='install' value='Etape_1'>";
         echo "<input type='hidden' name='update' value='". $update."'>";
         echo "<input type='hidden' name='language' value='". $_SESSION['glpilanguage']."'>";
         echo "<input type='submit' name='submit' class='submit' value=\"".__('Continue')."\">";
         Html::closeForm();
         echo "&nbsp;&nbsp;";

         echo "<form action='install.php' method='post' class='inline'>";
         echo "<input type='hidden' name='update' value='". $update."'>";
         echo "<input type='hidden' name='language' value='". $_SESSION['glpilanguage']."'>";
         echo "<input type='hidden' name='install' value='Etape_0'>";
         echo "<input type='submit' name='submit' class='submit' value=\"".__('Try again')."\">";
         Html::closeForm();
         echo "</div>";
         break;

      case 2 :
         echo "<h3>".__('Do you want to continue?')."</h3>";
         echo "<form action='install.php' method='post'>";
         echo "<input type='hidden' name='update' value='".$update."'>";
         echo "<p class='submit'><input type='hidden' name='install' value='Etape_0'>";
         echo "<input type='submit' name='submit' class='submit' value=\"".__('Try again')."\">";
         echo "</p>";
         Html::closeForm();
         break;
   }

}


//step 2 import mysql settings.
function step2($update) {

   echo "<h3>".__('Database connection setup')."</h3>";
   echo "<form action='install.php' method='post'>";
   echo "<input type='hidden' name='update' value='".$update."'>";
   echo "<fieldset><legend>".__('Database connection parameters')."</legend>";
   echo "<p><label class='block'>".__('SQL server (MariaDB or MySQL)') ." </label>";
   echo "<input type='text' name='db_host'><p>";
   echo "<p><label class='block'>".__('SQL user') ." </label>";
   echo "<input type='text' name='db_user'></p>";
   echo "<p><label class='block'>".__('SQL password')." </label>";
   echo "<input type='password' name='db_pass'></p></fieldset>";
   echo "<input type='hidden' name='install' value='Etape_2'>";
   echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
         __('Continue')."'></p>";
   Html::closeForm();
}


//step 3 test mysql settings and select database.
function step3($host, $user, $password, $update) {

   error_reporting(16);
   echo "<h3>".__('Test of the connection at the database')."</h3>";

   //Check if the port is in url
   $hostport = explode(":", $host);
   if (count($hostport) < 2) {
      $link = new mysqli($hostport[0], $user, $password);
   } else {
      $link = new mysqli($hostport[0], $user, $password, '', $hostport[1]);
   }

   if ($link->connect_error
       || empty($host)
       || empty($user)) {
      echo "<p>".__("Can't connect to the database")."\n <br>".
           sprintf(__('The server answered: %s'), $link->connect_error)."</p>";

      if (empty($host)
          || empty($user)) {
         echo "<p>".__('The server or/and user field is empty')."</p>";
      }

      echo "<form action='install.php' method='post'>";
      echo "<input type='hidden' name='update' value='".$update."'>";
      echo "<input type='hidden' name='install' value='Etape_1'>";
      echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
            __s('Back')."'></p>";
      Html::closeForm();

   } else {
      $_SESSION['db_access'] = ['host'     => $host,
                                     'user'     => $user,
                                     'password' => $password];
      echo  "<h3>".__('Database connection successful')."</h3>";

      if ($update == "no") {
         echo "<p>".__('Please select a database:')."</p>";
         echo "<form action='install.php' method='post'>";

         if ($DB_list = $link->query("SHOW DATABASES")) {
            while ($row = $DB_list->fetch_array()) {
               if (!in_array($row['Database'], ["information_schema",
                                                     "mysql",
                                                     "performance_schema"] )) {
                  echo "<p>";
                  echo "<label class='radio'>";
                  echo "<input type='radio' name='databasename' value='". $row['Database']."'>";

                  echo "<span class='outer'><span class='inner'></span></span>";
                  echo $row['Database'];
                  echo " </label>";
                  echo " </p>";
               }
            }
         }

         echo "<p>";
         echo "<label class='radio'>";
         echo "<input type='radio' name='databasename' value='0'>";
         echo __('Create a new database or use an existing one:');
         echo "<span class='outer'><span class='inner'></span></span>";
         echo "&nbsp;<input type='text' name='newdatabasename'>";
         echo " </label>";
         echo "</p>";
         echo "<input type='hidden' name='install' value='Etape_3'>";
         echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
               __('Continue')."'></p>";
         $link->close();
         Html::closeForm();

      } else if ($update == "yes") {
         echo "<p>".__('Please select the database to update:')."</p>";
         echo "<form action='install.php' method='post'>";

         $DB_list = $link->query("SHOW DATABASES");
         while ($row = $DB_list->fetch_array()) {
            echo "<p>";
            echo "<label class='radio'>";
            echo "<input type='radio' name='databasename' value='". $row['Database']."'>";
            echo "<span class='outer'><span class='inner'></span></span>";
            echo $row['Database'];
            echo " </label>";
            echo "</p>";
         }

         echo "<input type='hidden' name='install' value='update_1'>";
         echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
                __('Continue')."'></p>";
         $link->close();
         Html::closeForm();
      }

   }
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
      echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
             __('Continue')."'></p>";
      Html::closeForm();
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
         if (DBConnection::createMainConfig($host, $user, $password, $databasename)) {
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

         if (DBConnection::createMainConfig($host, $user, $password, $newdatabasename)) {
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
                && DBConnection::createMainConfig($host, $user, $password, $newdatabasename)) {

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

//send telemetry informations
function step6() {
   global $DB;
   echo "<h3>".__('Collect data')."</h3>";

   include_once(GLPI_ROOT . "/inc/dbmysql.class.php");
   include_once(GLPI_CONFIG_DIR . "/config_db.php");
   $DB = new DB();

   echo "<form action='install.php' method='post'>";
   echo "<input type='hidden' name='install' value='Etape_5'>";

   echo Telemetry::showTelemetry();
   echo Telemetry::showReference();

   echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
            __('Continue')."'></p>";
   Html::closeForm();
}

// finish installation
function step7() {
   global $CFG_GLPI;

   include_once(GLPI_ROOT . "/inc/dbmysql.class.php");
   include_once(GLPI_CONFIG_DIR . "/config_db.php");
   $DB = new DB();

   if (isset($_POST['send_stats'])) {
      //user has accepted to send telemetry infos; activate cronjob
      $query = 'UPDATE glpi_crontasks SET state = 1 WHERE name=\'telemetry\'';
      $DB->query($query);
   }

   $url_base = str_replace("/install/install.php", "", $_SERVER['HTTP_REFERER']);
   $query = "UPDATE `glpi_configs`
             SET `value`     = '".$DB->escape($url_base)."'
             WHERE `context` = 'core'
                   AND `name`    = 'url_base'";
   $DB->query($query);

   $url_base_api = "$url_base/apirest.php/";
   $query = "UPDATE `glpi_configs`
             SET `value`     = '".$DB->escape($url_base_api)."'
             WHERE `context` = 'core'
                   AND `name`    = 'url_base_api'";
   $DB->query($query);

   echo "<h2>".__('The installation is finished')."</h2>";
   echo "<p>".__('Default logins / passwords are:')."</p>";
   echo "<p><ul><li> ".__('glpi/glpi for the administrator account')."</li>";
   echo "<li>".__('tech/tech for the technician account')."</li>";
   echo "<li>".__('normal/normal for the normal account')."</li>";
   echo "<li>".__('post-only/postonly for the postonly account')."</li></ul></p>";
   echo "<p>".__('You can delete or modify these accounts as well as the initial data.')."</p>";
   echo "<p class='center'><a class='vsubmit' href='../index.php'>".__('Use GLPI');
   echo "</a></p>";
}


function update1($DBname) {

   $host     = $_SESSION['db_access']['host'];
   $user     = $_SESSION['db_access']['user'];
   $password = $_SESSION['db_access']['password'];

   if (DBConnection::createMainConfig($host, $user, $password, $DBname) && !empty($DBname)) {
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

Session::loadLanguage();

/**
 * @since version 0.84.2
**/
function checkConfigFile() {

   if (file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
      Html::redirect($CFG_GLPI['root_doc'] ."/index.php");
      die();
   }
}

if (!isset($_POST["install"])) {
   $_SESSION = [];

   checkConfigFile();
   header_html("Select your language");
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
         header_html(__('License'));
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

      case "Etape_4" : // send telemetry informations
         header_html(sprintf(__('Step %d'), 4));
         step6();
         break;

      case "Etape_5" : // finish installation
         header_html(sprintf(__('Step %d'), 4));
         step7();
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
