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

// If config_db doesn't exist -> start installation
define('GLPI_ROOT', '.');
include (GLPI_ROOT . "/config/based_config.php");

if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   include (GLPI_ROOT . "/inc/common.function.php");
   glpi_header("install/install.php");
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
   echo '<head><title>GLPI - '.$LANG['login'][10].'</title>'."\n";
   echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'."\n";
   echo '<meta http-equiv="Content-Script-Type" content="text/javascript"/>'."\n";
   echo '<link rel="shortcut icon" type="images/x-icon" href="'.$CFG_GLPI["root_doc"].
          '/pics/favicon.ico" />';

   // Appel CSS
   echo '<link rel="stylesheet" href="'.$CFG_GLPI["root_doc"].'/css/styles.css" type="text/css" '.
         'media="screen" />';
   // surcharge CSS hack for IE
   echo "<!--[if lte IE 6]>" ;
   echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' ".
         "media='screen' >\n";
   echo "<![endif]-->";
   echo "<script type='text/javascript'><!--document.getElementById('var_login_name').focus();-->".
         "</script>";

   echo "</head>";

   echo "<body>";
   echo "<div id='contenulogin'>";
   echo "<div id='logo-login'>";
   echo nl2br(unclean_cross_side_scripting_deep($CFG_GLPI['text_login']));
   echo "</div>";

   echo "<div id='boxlogin'>";
   echo "<form action='".$CFG_GLPI["root_doc"]."/login.php' method='post'>";

   // Other CAS
   if (isset($_GET["noAUTO"])) {
      echo "<input type='hidden' name='noAUTO' value='1'/>";
   }

   // redirect to ticket
   if (isset($_GET["redirect"])) {
      manageRedirect($_GET["redirect"]);
      echo '<input type="hidden" name="redirect" value="'.$_GET['redirect'].'">';
   }
   echo "<fieldset>";
   echo '<legend>'.$LANG['login'][10].'</legend>';
   echo '<div class="row"><span class="label"><label>'.$LANG['login'][6].' :  </label></span>';
   echo '<span class="formw"><input type="text" name="login_name" id="login_name" size="15" />';
   echo '</span></div>';

   echo '<div class="row"><span class="label"><label>'.$LANG['login'][7].' : </label></span>';
   echo '<span class="formw">';
   echo '<input type="password" name="login_password" id="login_password" size="15" /></span></div>';

   echo "</fieldset>";
   echo '<p><span>';
   echo '<input type="submit" name="submit" value="'.$LANG['buttons'][2].'" class="submit"/>';
   echo '</span></p>';
   echo "</form>";

   echo "<script type='text/javascript' >\n";
   echo "document.getElementById('login_name').focus();";
   echo "</script>";

   echo "</div>";  // end login box


   echo "<div class='error'>";
   echo "<noscript><p>";
   echo $LANG['login'][26];
   echo "</p></noscript>";

   if (isset($_GET['error'])) {
      switch ($_GET['error']) {
         case 1 : // cookie error
            echo $LANG['login'][27];
            break;

         case 2 : // GLPI_SESSION_DIR not writable
            echo $LANG['install'][50]." : ".GLPI_SESSION_DIR;
            break;
      }
   }
   echo "</div>";


   // Display FAQ is enable
   if ($CFG_GLPI["use_public_faq"]) {
      echo '<div id="box-faq"><a href="front/helpdesk.faq.php">[ '.$LANG['knowbase'][24].' ]</a>';
      echo '</div>';
   }

   if ($CFG_GLPI["use_mailing"]) {
      echo '<div id="box-faq"><a href="front/lostpassword.php?lostpassword=1">[ '.$LANG['users'][3].' ]</a>';
      echo '</div>';
   }

   echo "</div>"; // end contenu login

   if (GLPI_DEMO_MODE) {
      echo "<div class='center'>";
      getCountLogin();
      echo "</div>";
   }

   echo "<div id='footer-login'>";
   echo "<a href='http://glpi-project.org/' title='Powered By Indepnet'>";
   echo 'GLPI version '.(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").
        ' Copyright (C) 2003-'.date("Y").' INDEPNET Development Team.';
   echo "</a></div>";

}
// call cron
if (! GLPI_DEMO_MODE) {
   callCronForce();
}

echo "</body></html>";

?>