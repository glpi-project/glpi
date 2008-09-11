<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

// Test si config_db n'existe pas on lance l'installation
define('GLPI_ROOT', '.');

include (GLPI_ROOT . "/config/based_config.php");
if(!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
	include (GLPI_ROOT . "/inc/common.function.php");
	glpi_header("install/install.php");
	die();
}
else
{
	include (GLPI_ROOT . "/inc/includes.php");
	$_SESSION["glpitest"]='testcookie';

	// For compatibility reason
	if (isset($_GET["noCAS"])) {
		$_GET["noAUTO"]=$_GET["noCAS"];
	}
	
	checkAlternateAuthSystems(true,isset($_GET["redirect"])?$_GET["redirect"]:"");
	
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Start the page


	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
	echo "<head><title>GLPI Login</title>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" />\n";
	echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />\n";
	echo '<link rel="shortcut icon" type="images/x-icon" href="'.$CFG_GLPI["root_doc"].'/pics/favicon.ico" />';

	// Appel CSS
	echo '<link rel="stylesheet"  href="'.$CFG_GLPI["root_doc"].'/css/styles.css" type="text/css" media="screen" />';
	// surcharge CSS hack for IE 
	echo "<!--[if lte IE 6]>" ; 
	echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' media='screen' >\n"; 
	echo "<![endif]-->";
	echo "<script type=\"text/javascript\"><!--document.getElementById('var_login_name').focus();--></script>";

	echo "</head>";

	// Body with configured stuff
	echo "<body>";
	// contenu

	echo "<div id='contenulogin'>";
	
	echo "<div id='logo-login'>";

	echo nl2br(unclean_cross_side_scripting_deep($CFG_GLPI['text_login']));

	
	echo "</div>";

	echo "<div id='boxlogin'>";

	echo "<form action='login.php' method='post'>";

	// Other CAS 
	if (isset($_GET["noAUTO"])) {
		echo "<input type='hidden' name='noAUTO' value='1' />";
	}

	// redirect to tracking
	if (isset($_GET["redirect"])){
		manageRedirect($_GET["redirect"]);
		echo '<input type="hidden" name="redirect" value="'.$_GET['redirect'].'">';
	}

	echo "<fieldset>";
	echo '<legend>'.$LANG["login"][10].'</legend>';


	echo '<div class="row"><span class="label"><label>'.$LANG["login"][6].' :  </label></span><span class="formw"> <input type="text" name="login_name" id="login_name" size="15" /></span></div>';


	echo '<div class="row"><span class="label"><label>'.$LANG["login"][7].' : </label></span><span class="formw"><input type="password" name="login_password" id="login_password" size="15" /> </span></div>';





	echo "</fieldset>";
	echo '<p ><span> <input type="submit" name="submit" value="'.$LANG["buttons"][2].'" class="submit" /></span></p>';
	echo "</form>";

	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('login_name').focus();";
	echo "</script>";


	echo "</div>";  // fin box login



	echo "<div class='error'>";
	echo "<noscript><p>";
	echo $LANG["login"][26];
	echo "</p></noscript>";
	if (isset($_GET['cookie_error'])){
		echo $LANG["login"][27];
	}
	echo "</div>";


	// Affichage autorisee FAQ
	if ($CFG_GLPI["public_faq"]){
		echo '<div id="box-faq"><a href="front/helpdesk.faq.php">[ '.$LANG["knowbase"][24].' ]</a></div>';
	}
	

	echo "</div>"; // fin contenu login

	if (GLPI_DEMO_MODE){
		echo "<div align='center'";

		$query="SELECT count(*) 
			FROM `glpi_event_log` 
			WHERE message LIKE '%logged in%'";

		$query2="SELECT date 
			FROM `glpi_event_log` 
			ORDER BY date ASC 
			LIMIT 1";

		$DB=new DB;
		$result=$DB->query($query);
		$result2=$DB->query($query2);
		$nb_login=$DB->result($result,0,0);
		$date=$DB->result($result2,0,0);

		echo '<b>'.$nb_login.'</b> logins since '.$date ;

		echo "</div>";
	}


	echo "<div id='footer-login'>";
	echo "<a href=\"http://glpi-project.org/\" title=\"Powered By Indepnet\"  >";
	echo 'GLPI version '.(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").' Copyright (C) 2003-'.date("Y").' INDEPNET Development Team.';
	echo "</a>";
	echo "</div>";

}
// Appel de cron
if (! GLPI_DEMO_MODE){
	callCronForce();
}


echo "</body></html>";

// End


?>
