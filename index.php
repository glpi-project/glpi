<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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


/** @file
* @brief
*/

// Check PHP version not to have trouble
if (version_compare(PHP_VERSION, "5.4.0") < 0) {
   die("PHP >= 5.4.0 required");
}

define('DO_NOT_CHECK_HTTP_REFERER', 1);
// If config_db doesn't exist -> start installation
define('GLPI_ROOT', dirname(__FILE__));
include (GLPI_ROOT . "/config/based_config.php");

if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   include_once (GLPI_ROOT . "/inc/autoload.function.php");
   Html::redirect("install/install.php");
   die();

} else {
   $TRY_OLD_CONFIG_FIRST = true;
   include (GLPI_ROOT . "/inc/includes.php");
   $_SESSION["glpicookietest"] = 'testcookie';

   // For compatibility reason
   if (isset($_GET["noCAS"])) {
      $_GET["noAUTO"] = $_GET["noCAS"];
   }

   Auth::checkAlternateAuthSystems(true, isset($_GET["redirect"])?$_GET["redirect"]:"");

   // Send UTF8 Headers
   header("Content-Type: text/html; charset=UTF-8");

   // Start the page
   echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
         '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
   echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">';
   echo '<head><title>'.__('GLPI - Authentication').'</title>'."\n";
   echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'."\n";
   echo '<meta http-equiv="Content-Script-Type" content="text/javascript"/>'."\n";
   echo '<link rel="shortcut icon" type="images/x-icon" href="'.$CFG_GLPI["root_doc"].
          '/pics/favicon.ico" />';

   // auto desktop / mobile viewport
   echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";

   // Appel CSS
   echo '<link rel="stylesheet" href="'.$CFG_GLPI["root_doc"].'/css/styles.css" type="text/css" '.
         'media="screen" />';
   // CSS theme link
      echo Html::css($CFG_GLPI["root_doc"]."/css/palettes/".$CFG_GLPI["palette"].".css");
   // surcharge CSS hack for IE
   echo "<!--[if lte IE 6]>" ;
   echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' ".
         "media='screen' >\n";
   echo "<![endif]-->";
//    echo "<script type='text/javascript'><!--document.getElementById('var_login_name').focus();-->".
//          "</script>";

   echo "</head>";

   echo "<body>";
   echo "<div id='firstboxlogin'>";
   echo "<div id='logo_login'></div>";
   echo "<div id='text-login'>";
   echo nl2br(Toolbox::unclean_html_cross_side_scripting_deep($CFG_GLPI['text_login']));
   echo "</div>";

   echo "<div id='boxlogin'>";
   echo "<form action='".$CFG_GLPI["root_doc"]."/front/login.php' method='post'>";


   // Other CAS
   if (isset($_GET["noAUTO"])) {
      echo "<input type='hidden' name='noAUTO' value='1' />";
   }
   // redirect to ticket
   if (isset($_GET["redirect"])) {
      Toolbox::manageRedirect($_GET["redirect"]);
      echo '<input type="hidden" name="redirect" value="'.$_GET['redirect'].'"/>';
   }
   echo '<p class="login_input">
         <input type="text" name="login_name" id="login_name" required="required" 
                placeholder="'.__('Login').'" />
         <span class="login_img"></span>
         </p>';
   echo '<p class="login_input">
         <input type="password" name="login_password" id="login_password" required="required" 
                placeholder="'.__('Password').'"  />
         <span class="login_img"></span>
         </p>';
   echo '<p class="login_input">
         <input type="submit" name="submit" value="'._sx('button','Post').'" class="submit" />
         </p>';

   if ($CFG_GLPI["use_mailing"]
       && countElementsInTable('glpi_notifications',
                               "`itemtype`='User'
                                AND `event`='passwordforget'
                                AND `is_active`=1")) {
      echo '<a id="forget" href="front/lostpassword.php?lostpassword=1">'.
             __('Forgotten password?').'</a>';
   }
   Html::closeForm();

   echo "<script type='text/javascript' >\n";
   echo "document.getElementById('login_name').focus();";
   echo "</script>";

   echo "</div>";  // end login box


   echo "<div class='error'>";
   echo "<noscript><p>";
   _e('You must activate the JavaScript function of your browser');
   echo "</p></noscript>";

   if (isset($_GET['error'])) {
      switch ($_GET['error']) {
         case 1 : // cookie error
            _e('You must accept cookies to reach this application');
            break;

         case 2 : // GLPI_SESSION_DIR not writable
            _e('Checking write permissions for session files');
            echo "<br>".GLPI_SESSION_DIR;
            break;

         case 3 :
            _e('Invalid use of session ID');
            break;
      }
   }
   echo "</div>";

   // Display FAQ is enable
   if ($CFG_GLPI["use_public_faq"]) {
      echo '<div id="box-faq">'.
            '<a href="front/helpdesk.faq.php">[ '.__('Access to the Frequently Asked Questions').' ]';
      echo '</a></div>';
   }

   echo "<div id='display-login'>";
   Plugin::doHook('display_login');
   echo "</div>";
   

   echo "</div>"; // end contenu login

   if (GLPI_DEMO_MODE) {
      echo "<div class='center'>";
      Event::getCountLogin();
      echo "</div>";
   }
   echo "<div id='footer-login'>";
   echo "<a href='http://glpi-project.org/' title='Powered By Teclib'>";
   echo "GLPI version ".(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"")." Copyright (C) ".
        "2015".
        //"-".date("Y"). // TODO, decomment this in 2016
        " By Teclib'. <br />".
        "Copyright (C) 2003-2015 INDEPNET Development Team";
   echo "</a></div>";

}
// call cron
if (!GLPI_DEMO_MODE) {
   CronTask::callCronForce();
}

echo "</body></html>";
?>
