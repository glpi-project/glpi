<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

include ("_relpos.php");
include ($phproot . "/config/based_config.php");
if(!file_exists($cfg_glpi["config_dir"] . "/config_db.php")) {
	include($phproot ."/install/install.php");
	die();
}
else
{
	include ($phproot . "/inc/includes.php");

	// Using CAS server
	if (!empty($cfg_glpi["cas_host"])&&!isset($_GET["noCAS"])) {
		glpi_header("login.php");
	}


	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Start the page


	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
	echo "<head><title>GLPI Login</title>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" />\n";
	echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />\n";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' />";

	// Appel CSS
	echo "<link rel='stylesheet'  href='".$HTMLRel."css/styles.css' type='text/css' media='screen' />";
	echo "<script type=\"text/javascript\"><!--document.getElementById('var_login_name').focus();--></script>";
	echo "</head>";

	// Body with configured stuff
	echo "<body>";
	// contenu



	echo "<div id='contenulogin'>";

	echo "<div id='logo-login'>";

	echo nl2br(unclean_cross_side_scripting_deep($cfg_glpi['text_login']));

	// Affichage autorisé FAQ
	if ($cfg_glpi["public_faq"]){
		echo "<ul><li><a href='front/faq.php'>".$lang["knowbase"][24]."</a></li></ul>";
	}
	echo "</div>";




	echo "<div id='boxlogin'>";

	echo "<form action='login.php' method='post'>";
	// authentification CAS 
	if (isset($_GET["noCAS"])) echo "<input type='hidden' name='noCAS' value='1' />";

	// redirect to tracking
	if (isset($_GET["redirect"])){
		if(!session_id()){@session_start();}

		list($type,$ID)=split("_",$_GET["redirect"]);
		// Déjà connecté
		if (isset($_SESSION["glpiprofile"]["interface"])&&!empty($_SESSION["glpiprofile"]["interface"])){
			switch ($_SESSION["glpiprofile"]["interface"]){
				case "helpdesk" :
					switch ($type){
						case "tracking":
							glpi_header($cfg_glpi["root_doc"]."/front/helpdesk.php?show=user&ID=$ID");
						break;
						default:
						glpi_header($cfg_glpi["root_doc"]."/front/helpdesk.php");
						break;
					}
				break;
				case "central" :
					switch ($type){
						case "tracking":
							glpi_header($cfg_glpi["root_doc"]."/front/tracking.form.php?ID=$ID");
						break;
						case "computers":
							glpi_header($cfg_glpi["root_doc"]."/front/computer.form.php?ID=$ID");
						break;
						default:
						glpi_header($cfg_glpi["root_doc"]."/front/central.php");
						break;
					}
				break;
			}
		}
		// Non connecté : connection puis redirection 
		else {
			echo "<input type='hidden' name='redirect' value='".$_GET['redirect']."'>";
		}
	}

	echo "<fieldset>";
	echo "<legend>".$lang["login"][10]."</legend>";


	echo "<div class='row'><span class='label'><label>".$lang["login"][6]." :  </label></span><span class='formw'> <input type='text' name='login_name' id='login_name' size='15' /></span></div>";


	echo "<div class='row'><span class='label'><label>".$lang["login"][7]." : </label></span><span class='formw'><input type='password' name='login_password' id='login_password' size='15' /> </span></div>";





	echo "</fieldset>";
	echo "<p ><span> <input type='submit' name='submit' value='".$lang["buttons"][2]."' class='submit' /></span></p>";
	echo "</form>";

	echo "<script type='text/javascript' >\n";
	echo "document.getElementById('login_name').focus();";
	echo "</script>";


	echo "</div>";  // fin box login



	echo "</div>"; // fin contenu login

	if ($cfg_glpi["debug"]==DEMO_MODE){
		echo "<div align='center'";

		$query="SELECT count(*) 
			FROM `glpi_event_log` 
			WHERE message LIKE '%logged in%'";

		$query2="SELECT date 
			FROM `glpi_event_log` 
			ORDER BY date ASC 
			LIMIT 1";

		$db=new DB;
		$result=$db->query($query);
		$result2=$db->query($query2);
		$nb_login=$db->result($result,0,0);
		$date=$db->result($result2,0,0);

		echo "<b>$nb_login</b> logins since $date" ;

		echo "</div>";
	}


	echo "<div id='footer-login'>";
	echo "<a href=\"http://glpi-project.org/\" title=\"Powered By Indepnet\"  >";
	echo "GLPI version ".$cfg_glpi["version"]." Copyright (C) 2003-".date("Y")." INDEPNET Development Team.";
	echo "</a>";
	echo "</div>";

}
// Appel de cron
if ($cfg_glpi["debug"]!=DEMO_MODE)
callCron();


echo "</body></html>";

// End


?>
