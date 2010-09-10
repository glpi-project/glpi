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


define('GLPI_ROOT', '..');

include_once (GLPI_ROOT . "/config/define.php");
include_once (GLPI_ROOT . "/config/based_config.php");
include_once (GLPI_ROOT . "/inc/timer.class.php");
include_once (GLPI_ROOT . "/inc/common.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/inc/dropdown.class.php");
include_once (GLPI_ROOT . "/inc/display.function.php");

//Print a correct  Html header for application
function header_html($etape)
{
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html>";
	echo "<head>";
	echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
	echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\"> ";
	echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\"> ";
	echo "<meta http-equiv=\"Content-Language\" content=\"fr\"> ";
	echo "<meta name=\"generator\" content=\"\">";
	echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\">";
	echo "<title>Setup GLPI</title>";
	// CSS
	echo "<link rel='stylesheet'  href='../css/style_install.css' type='text/css' media='screen' >";
	echo "</head>";
	echo "<body>";
	echo "<div id='principal'>";
	echo "<div id='bloc'>";
	echo "<div class='haut'></div>";
	echo "<h2>GLPI SETUP</h2>";
	echo "<br><h3>". $etape ."</h3>";
}

//Display a great footer.
function footer_html()
{
	echo "<div class='bas'></div></div></div></body></html>";
}

// choose language

function choose_language()
{

	echo "<form action=\"install.php\" method=\"post\">";
	echo "<p class='center'>";

	Dropdown::showLanguages("language", array('value'=>"en_GB"));
	echo "</p>";
	echo "";
	echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"lang_select\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"OK\"></p>";
	echo "</form>";
}

// load language

function loadLang($LANGuage) {
	if (isset($LANG)){
		unset($LANG);
	}
	global $LANG;
	$file = GLPI_ROOT ."/locales/".$LANGuage.".php";
	if (file_exists($file)){
		include($file);
	} else {
		include(GLPI_ROOT ."/locales/en_GB.php");
	}
}

function acceptLicence() {

	global $LANG;

	echo "<div align='center'>";
	echo "<textarea id='license' cols='85' rows='10' readonly='readonly'>";
	readfile("../COPYING.txt");
	echo "</textarea>";

   echo "<br><a target='_blank' href='http://www.gnu.org/licenses/old-licenses/gpl-2.0-translations.html'>".$LANG['install'][18]."</a>";

	echo "<form action=\"install.php\" method=\"post\">";
	echo "<p>";
	echo " <input type=\"radio\" name=\"install\" id=\"agree\" value=\"Licence\">";
	echo " <label for=\"agree\">";
	echo $LANG['install'][93];
	echo " </label></p>";


	echo "<br>";
	echo " <input type=\"radio\" name=\"install\" value=\"lang_select\" id=\"disagree\" checked=\"checked\">";
	echo " <label for=\"disagree\">";
	echo $LANG['install'][94];
	echo " </label>";
	echo "<p><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\" ></p>";
	echo "</form>";
	echo "</div>";
}



//confirm install form
function step0()
{

	global $LANG;
	echo "<h3>".$LANG['install'][0]."</h3>";
	echo "<p>".$LANG['install'][1]."</p>";
	echo "<p> ".$LANG['install'][2]."</p>";
	echo "<form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"update\" value=\"no\">";
	echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\">";
	echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][3]."\"></p>";
	echo "</form>";
	echo "<form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"update\" value=\"yes\">";
	echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\">";
	echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][4]."\"></p>";
	echo "</form>";
}

//Step 1 checking some compatibilty issue and some write tests.
function step1($update)
{
	global $LANG,$CFG_GLPI;

	$error = 0;
	echo "<h3>".$LANG['install'][5]."</h3>";
	echo "<table class='tab_check'>";

	$error=commonCheckForUseGLPI();

	echo "</table>";
	switch ($error) {
		case 0 :
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\">";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\">";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\"></p>";
			echo "</form>";
			break;
		case 1 :
			echo "<h3>".$LANG['install'][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\">";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\"></p>";
			echo "</form> &nbsp;&nbsp;";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\">";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\">";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][27]."\"></p>";
			echo "</form>";
			break;
		case 2 :
			echo "<h3>".$LANG['install'][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\">";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\">";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][27]."\"></p>";
			echo "</form>";
			break;
	}


}

//step 2 import mysql settings.
function step2($update)
{
	global $LANG;
	echo "<h3>".$LANG['install'][28]."</h3>";
	echo "<form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\">";
	echo "<fieldset><legend>".$LANG['install'][29]."</legend>";
	echo "<p><label class='block'>".$LANG['install'][30] ." : </label><input type=\"text\" name=\"db_host\"><p>";
	echo "<p ><label class='block'>".$LANG['install'][31] ." : </label><input type=\"text\" name=\"db_user\"></p>";
	echo "<p ><label class='block'>".$LANG['install'][32]." : </label><input type=\"password\" name=\"db_pass\"></p></fieldset>";
	echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\">";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\"></p>";
	echo "</form>";

}

//step 3 test mysql settings and select database.
function step3($host,$user,$password,$update)
{

	global $LANG;
	error_reporting(16);
	echo "<h3>".$LANG['install'][34]."</h3>";
	$link = mysql_connect($host,$user,$password);
	if (!$link || empty($host) || empty($user)) {
		echo "<p>".$LANG['install'][35]." : \n
			<br>".$LANG['install'][36]." : ".mysql_error()."</p>";
		if(empty($host) || empty($user)) {
			echo "<p>".$LANG['install'][37]."</p>";
		}
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_1\">";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\"  value=\"".$LANG['buttons'][13]."\"></p>";
		echo "</form>";

	}
	else {
		echo  "<h3>".$LANG['update'][93]."</h3>";

		if($update == "no") {

			echo "<p>".$LANG['install'][38]."</p>";

			echo "<form action=\"install.php\" method=\"post\">";

			$DB_list = mysql_list_dbs($link);
			while ($row = mysql_fetch_object($DB_list)) {
				echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\">$row->Database.</p>";
			}
			echo "<p><input type=\"radio\" name=\"databasename\" value=\"0\">".$LANG['install'][39];
			echo "&nbsp;<input type=\"text\" name=\"newdatabasename\"></p>";
			echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\">";
			echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\">";
			echo "<input type=\"hidden\" name=\"db_pass\" value=\"". rawurlencode($password) ."\">";
			echo "<input type=\"hidden\" name=\"install\" value=\"Etape_3\">";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\"></p>";
			mysql_close($link);
			echo "</form>";
		}
		elseif($update == "yes") {
			echo "<p>".$LANG['install'][40]."</p>";
			echo "<form action=\"install.php\" method=\"post\">";

			$DB_list = mysql_list_dbs($link);
			while ($row = mysql_fetch_object($DB_list)) {
				echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\">$row->Database.</p>";
			}
			echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\">";
			echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\">";
			echo "<input type=\"hidden\" name=\"db_pass\" value=\"". rawurlencode($password) ."\">";
			echo "<input type=\"hidden\" name=\"install\" value=\"update_1\">";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\"></p>";
			mysql_close($link);
			echo "</form>";

		}
	}
}


//Step 4 Create and fill database.
function step4 ($host,$user,$password,$databasename,$newdatabasename)
{
	global $LANG;
	//display the form to return to the previous step.

   echo "<h3>".$LANG['install'][24]."</h3>";

	function prev_form($host,$user,$password) {
		global $LANG;

		echo "<br><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\">";
		echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\">";
		echo " <input type=\"hidden\" name=\"db_pass\" value=\"". rawurlencode($password) ."\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"no\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\">";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['buttons'][13]."\"></p>";
		echo "</form>";
	}
	//Display the form to go to the next page
	function next_form()
	{
		global $LANG;

		echo "<br><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_4\">";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][26]."\"></p>";
		echo "</form>";
	}

	//Fill the database
	function fill_db()
	{
		global $LANG, $CFG_GLPI;

		include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
		include_once (GLPI_CONFIG_DIR . "/config_db.php");

      $DB = new DB;
      if (!$DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.80-empty.sql")) {
         echo "Errors occurred inserting default database";
      }

		// update default language
		$query = "UPDATE `glpi_configs` SET language='".$_SESSION["glpilanguage"]."' ;";
      $DB->query($query) or die("4203 ".$LANG['update'][90].$DB->error());
		$query = "UPDATE `glpi_users` SET language=NULL ;";
		$DB->query($query) or die("4203 ".$LANG['update'][90].$DB->error());
	}

	$link = mysql_connect($host,$user,$password);

	if(!empty($databasename)) { // use db already created
		$DB_selected = mysql_select_db($databasename, $link);

		if (!$DB_selected) {

			echo $LANG['install'][41];
			echo "<br>";
			echo $LANG['install'][36]." ". mysql_error();
			prev_form($host,$user,$password);
		}
		else {
			if (create_conn_file($host,$user,$password,$databasename)) {
				fill_db();
				echo "<p>".$LANG['install'][43]."</p>";
				next_form();
			}
			else { // can't create config_db file
				echo "<p>".$LANG['install'][47]."</p>";
				prev_form($host,$user,$password);
			}
		}
	} elseif(!empty($newdatabasename)) { // create new db
		// Try to connect
		if (mysql_select_db($newdatabasename, $link)){

			echo "<p>".$LANG['install'][82]."</p>";
			if (create_conn_file($host,$user,$password,$newdatabasename)){
				fill_db();
				echo "<p>".$LANG['install'][43]."</p>";
				next_form();
			} else { // can't create config_db file
				echo "<p>".$LANG['install'][47]."</p>";
				prev_form($host,$user,$password);
			}
		} else { // try to create the DB
			if (mysql_query("CREATE DATABASE IF NOT EXISTS `".$newdatabasename."`")){

				echo "<p>".$LANG['install'][82]."</p>";

				if (mysql_select_db($newdatabasename, $link)&&create_conn_file($host,$user,$password,$newdatabasename)) {
					fill_db();
					echo "<p>".$LANG['install'][43]."</p>";
					next_form();

				}
				else { // can't create config_db file
					echo "<p>".$LANG['install'][47]."</p>";
					prev_form($host,$user,$password);
				}
			} else { // can't create database
				echo $LANG['install'][48];
				echo "<br>".$LANG['install'][42] . mysql_error();
				prev_form($host,$user,$password);
			}
		}
	} else { // no db selected
		echo "<p>".$LANG['install'][49]. "</p>";
		//prev_form();
		prev_form($host,$user,$password);
	}
	mysql_close($link);

	}


	// finish installation
	function step7() {

		global $LANG,$CFG_GLPI;
		require_once (GLPI_ROOT . "/inc/dbmysql.class.php");
		require_once (GLPI_ROOT . "/inc/common.function.php");
		require_once (GLPI_CONFIG_DIR . "/config_db.php");
		$DB = new DB;

		$query="UPDATE glpi_configs SET url_base='".str_replace("/install/install.php","",$_SERVER['HTTP_REFERER'])."' WHERE id='1'";
		$DB->query($query);


		echo "<h2>".$LANG['install'][55]."</h2>";
		echo "<p>".$LANG['install'][57]."</p>";
		echo "<p><ul><li> ".$LANG['install'][58]."</li>";
		echo "<li>".$LANG['install'][59]."</li>";
		echo "<li>".$LANG['install'][60]."</li>";
		echo "<li>".$LANG['install'][61]."</li></ul></p>";
		echo "<p>".$LANG['install'][62]."</p>";
		echo "<p class='submit'> <a href=\"../index.php\"><span class='button'>".$LANG['install'][64]."</span></a></p>";
	}

	//Create the file config_db.php
	// an fill it with user connections info.
	function create_conn_file($host,$user,$password,$DBname)
	{
		global $CFG_GLPI;
		$DB_str = "<?php\n class DB extends DBmysql { \n var \$dbhost	= '". $host ."'; \n var \$dbuser 	= '". $user ."'; \n var \$dbpassword= '". rawurlencode($password) ."'; \n var \$dbdefault	= '". $DBname ."'; \n } \n?>";
		$fp = fopen(GLPI_CONFIG_DIR . "/config_db.php",'wt');
		if($fp) {
			$fw = fwrite($fp,$DB_str);
			fclose($fp);
			return true;
		}
		else return false;
	}

	function update1($host,$user,$password,$DBname) {

		global $LANG;
		if(create_conn_file($host,$user,$password,$DBname) && !empty($DBname)) {

			$from_install = true;
			include(GLPI_ROOT ."/install/update.php");
		}
		else { // can't create config_db file
			echo $LANG['install'][70];
			echo "<h3>".$LANG['install'][25]."</h3>";
			echo "<form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"update\" value=\"yes\">";
			echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\">";
			echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG['install'][25]."\"></p>";
			echo "</form>";
		}


	}




	//------------Start of install script---------------------------


	// Use default session dir if not writable
	if (is_writable(GLPI_SESSION_DIR)){
		setGlpiSessionPath();
	}
	startGlpiSession();
	error_reporting(0); // we want to check system before affraid the user.


	if(!isset($_SESSION["glpilanguage"])||empty($_SESSION["glpilanguage"])) $_SESSION["glpilanguage"] = "en_GB";
	if(isset($_POST["language"])) $_SESSION["glpilanguage"] = $_POST["language"];


	loadLang($_SESSION["glpilanguage"]);
	if(!isset($_POST["install"])) {
		$_SESSION = array();
		if(file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
			glpi_header(GLPI_ROOT ."/index.php");
			die();
		}
		else {
			header_html("Select your language");
			choose_language();
		}
	}
	else {
		// DB clean
		if (isset($_POST["db_pass"])){
			$_POST["db_pass"]=stripslashes($_POST["db_pass"]);
			$_POST["db_pass"]=rawurldecode($_POST["db_pass"]);
			$_POST["db_pass"]=stripslashes($_POST["db_pass"]);
		}



		switch ($_POST["install"]) {

			case "lang_select" : // lang ok, go accept licence
				header_html("".$LANG['install'][92]."");
				acceptLicence();
			break;
			case "Licence" : // licence  ok, go choose installation or Update
				header_html("".$LANG['install'][81]."");
				step0();
			break;
			case "Etape_0" : // choice ok , go check system
				header_html($LANG['install'][77]." 0");
				$_SESSION["Test_session_GLPI"] = 1;
				step1($_POST["update"]);
			break;
			case "Etape_1" : // check ok, go import mysql settings.

				$_SESSION['glpi_use_mode']=DEBUG_MODE; // check system ok, we can use specific parameters for debug
				$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=0;
				$CFG_GLPI["use_errorlog"]=1;
				ini_set('display_errors','On');
				error_reporting(E_ALL | E_STRICT);
				set_error_handler("userErrorHandlerDebug");

				header_html($LANG['install'][77]." 1");
				step2($_POST["update"]);
			break;
			case "Etape_2" : // mysql settings ok, go test mysql settings and select database.
				header_html($LANG['install'][77]." 2");
				step3($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["update"]);
			break;
			case "Etape_3" : // Create and fill database

				header_html($LANG['install'][77]." 3");
				if(empty($_POST["databasename"])) $_POST["databasename"] ="";
				if(empty($_POST["newdatabasename"])) $_POST["newdatabasename"] ="";

				step4($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"],$_POST["newdatabasename"]);
			break;
			case "Etape_4" : // finish installation
				header_html($LANG['install'][77]." 4");
				step7();
			break;

			case "update_1" :
				if(empty($_POST["databasename"])) $_POST["databasename"] ="";
				update1($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"]);
			break;
		}
	}
	footer_html();

?>
