<?php
/*

  ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
 */

//Ce script génère ses propres messages d'erreur 
//Pas besoin des warnings de PHP
error_reporting(0);





//Print a correct  Html header for application
function header_html($etape)
{

        echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
        echo "<head>";
        echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />";
        echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" /> ";
        echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\" /> ";
        echo "<meta http-equiv=\"Content-Language\" content=\"fr\" /> ";
        echo "<meta name=\"generator\" content=\"\" />";
        echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\" />";
        echo "<title>Setup GLPI</title>";
       
        echo "<style type=\"text/css\">";
        echo "<!--

        /*  ... Definition des styles ... */

        body {
        background-color:#C5DAC8;
        color:#000000; }
        
       .principal {
        background-color: #ffffff;
        font-family: Verdana;font-size:12px;
        text-align: justify ; 
        -moz-border-radius: 4px;
	border: 1px solid #FFC65D;
         margin: 40px; 
         padding: 40px 40px 10px 40px;
       }

       table {
       text-align:center;
       border: 0;
       margin: 20px;
       margin-left: auto;
       margin-right: auto;
       width: 90%;}

       .red { color:red;}
       .green {color:green;}
       
       h2 {
        color:#FFC65D;
        text-align:center;}

       h3 {
        text-align:center;}

        input {border: 1px solid #ccc;}

        fieldset {
        padding: 20px;
          border: 1px dotted #ccc;
        font-size: 12px;
        font-weight:200;}

        .submit { text-align:center;}
       
        input.submit {
        border:1px solid #000000;
        background-color:#eeeeee;
        }
        
        input.submit:hover {
        border:1px solid #cccccc;
       background-color:#ffffff;
        }

	.button {
        font-weight:200;
	color:#000000;
	padding:5px;
	text-decoration:none;
	border:1px solid #009966;
        background-color:#eeeeee;
        }

        .button:hover{
          font-weight:200;
	  color:#000000;
	 padding:5px;
	text-decoration:none;
	border:1px solid #009966;
       background-color:#ffffff;
        }

	
	
        -->  ";
        echo "</style>";
         echo "</head>";
        echo "<body>";
	echo "<div class=\"principal\">";
        echo "<h2>GLPI SETUP</h2>";
	echo "<br/><h3>". $etape ."</h3>";
}

//Display a great footer.
function footer_html()
{
		echo "</div></body></html>";
}

// choose language

function choose_language()
{
echo "<form action=\"install.php\" method=\"post\">";
echo "<p align='center'><label>Choose your language <select name=\"language\"><label></p>";
	echo "<option value=\"french\">Français</option>";
	echo "<option value=\"english\">English</option>";
	echo "<option value=\"deutch\">Deutch</option>";
	echo "<option value=\"italian\">Italiano</option>";
	echo "</select>"; 
	echo "<input type=\"hidden\" name=\"install\" value=\"lang_select\" />";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"OK\" /></p>";
	echo "</form>";
}

// load language

function loadLang($language) {
		
		unset($lang);
		global $lang;
		include ("_relpos.php");
		$file = $phproot ."/glpi/dicts/".$language.".php";
		include($file);
}



//confirm install form
function step0()
{

global $lang;
echo "<h3>".$lang["install"][0]."</h3>";
echo "<p>".$lang["install"][1]."</p>";
echo "<p> ".$lang["install"][2]."</p>";
echo "<form action=\"install.php\" method=\"post\">";
echo "<input type=\"hidden\" name=\"update\" value=\"no\" />";
echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][3]."\" /></p>";
echo "</form>";
echo "<form action=\"install.php\" method=\"post\">";
echo "<input type=\"hidden\" name=\"update\" value=\"yes\" />";
echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][4]."\" /></p>";
echo "</form>";
}

//Step 1 checking some compatibilty issue and some write tests.
function step1($update)
{
	global $lang;
	$error = 0;
	echo "<h3>".$lang["install"][5]."</h3>";
	echo "<table>";
	echo "<tr><th>".$lang["install"][6]."</th><th >".$lang["install"][7]."</th></tr>";
// Parser test
	echo "<tr><td><h4>".$lang["install"][8]."</h4></td>";
// PHP Version  - exclude PHP3
	if (substr(phpversion(),0,1) == "3") {
		$error = 2;
		echo "<td>".$lang["install"][9]."</a>.\n</td>";
	}
	elseif (substr(phpversion(),0,3) == "4.0" and ereg("0|1",substr(phpversion(),4,1))) {
		echo "<td><span class='wrong'>&nbsp;<td>".$lang["install"][10]."<td>";
		if($error != 2) $error = 1;
	}
	else {
		echo "<td>".$lang["install"][11]."</td></tr>";
	}
// end parser test
//	echo "<h2>GLPI environment test</h2>";
//	echo "<table>";
//	echo "<tr><th>Test effectué</th><th colspan='2'>Résultats</th></tr>";
// session test
	echo "<tr><td><h4>".$lang["install"][12]."</h4></td>";



  // check whether session are enabled at all!!
	if (!extension_loaded('session')) {
		$error = 2;
		echo "<td><h2>".$lang["install"][13]."</h2></td></tr>";
	} 
	if ($_SESSION["Test_session_GLPI"] == 1) {
		echo "<td><i>".$lang["install"][14]."</i></td></tr>";
	}
	else {
		if($error != 2) $error = 1;
		echo "<td>".$lang["install"][15]."</td></tr>";
	}
	//Test for sybase extension loaded or not.
	echo "<tr><td><h4>".$lang["install"][65]."</h4></td>";
	if(ini_get('magic_quotes_sybase')) {
		echo "<td class='red'>".$lang["install"][66]."</td></tr>";
		$error = 2;
	}
	else {
		echo "<td>".$lang["install"][67]."</td></tr>";
		
	}
// *********
// file test

// il faut un test dans /dump et un dans /tmp pour phpexcel et /glpi/config/

	echo "<tr><td><h4>".$lang["install"][16]."</h4></td>";
	
	$fp = fopen("backups/dump/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]."</p> ".$lang["install"][18]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink("backups/dump/test_glpi.txt");
		if (!$delete) {
			echo "<td>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";

		}
	}
	/* A supprimer Obsolète 
	
	echo "<tr><td><h4>".$lang["install"][21]."</h4></td>";
		$fp = fopen("reports/reports/convexcel/tmp/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]."</p>". $lang["install"][22]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink("reports/reports/convexcel/tmp/test_glpi.txt");
		if (!$delete) {
			echo "<td>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";
		}
	}
	*/
	
	echo "<tr><td><h4>".$lang["install"][23]."</h4></td>";
	$fp = fopen("glpi/config/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>".$lang["install"][17]."</p>". $lang["install"][24]."</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink("glpi/config/test_glpi.txt");
		if (!$delete) {
			echo "<td>".$lang["install"][19]."</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>".$lang["install"][20]."</td></tr>";
		}
	}
	echo "</table>";
        switch ($error) {
		case 0 :       
        	echo "<h3>".$lang["install"][25]."</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\" />";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
		echo "</form>";
		break;
		case 1 :       
        	echo "<h3>".$lang["install"][25]."</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
		echo "</form> &nbsp;&nbsp;";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"". $update."\" />";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][27]."\" /></p>";
		echo "</form>";
		break;
		case 2 :       
        	echo "<h3>".$lang["install"][25]."</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\" />";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][27]."\" /></p>";
		echo "</form>";
		break;
	}
	

}

//step 2 import mysql settings.
function step2($update)
{
		global $lang;
		echo "<p>".$lang["install"][28]."</p>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\" />";
		echo "<fieldset><legend>".$lang["install"][29]."</legend>";
                echo "<p><label>".$lang["install"][30] .": <input type=\"text\" name=\"db_host\" /></label></p>";
		echo "<p ><label>".$lang["install"][31] .": <input type=\"text\" name=\"db_user\" /></label></p>";
		echo "<p ><label>".$lang["install"][32]." : <input type=\"password\" name=\"db_pass\" /></label></p></fieldset>";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
	
}

//step 3 test mysql settings and select database.
function step3($host,$user,$password,$update)
{

	global $lang;
	
	error_reporting(16);
	echo "<h3>".$lang["install"][34]."</h3>";
	$link = mysql_connect($host,$user,$password);
	if (!$link || empty($host) || empty($user)) {
		echo "".$lang["install"][35]." : \n
		<br />".$lang["install"][36]." : ".mysql_error();
		if(empty($host) || empty($user)) {
			echo $lang["install"][37];
		}
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"".$update."\" />";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\"  value=\"".$lang["install"][33]."\" /></p>";
		echo "</form>";
                
	}
	else {
		echo $lang["update"][93]."<br />";
		if($update == "no") {
			echo $lang["install"][38];
			echo "<form action=\"install.php\" method=\"post\">";
			$db_list = mysql_list_dbs($link);
			while ($row = mysql_fetch_object($db_list)) {
				echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\" />$row->Database.</p>";
			}
			echo "<p><input type=\"radio\" name=\"databasename\" value=\"0\" />".$lang["install"][39];
			echo "<input type=\"text\" name=\"newdatabasename\"/></p>";
			echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\" />";
			echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\" />";
			echo "<input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
			echo "<input type=\"hidden\" name=\"install\" value=\"Etape_3\" />";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
			mysql_close($link);
			echo "</form>";
		}
		elseif($update == "yes") {
			echo $lang["install"][40];
			echo "<form action=\"install.php\" method=\"post\">";
			$db_list = mysql_list_dbs($link);
			while ($row = mysql_fetch_object($db_list)) {
				echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\" />$row->Database.</p>";
			}
			echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\" />";
			echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\" />";
			echo "<input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
			echo "<input type=\"hidden\" name=\"install\" value=\"update_1\" />";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
			mysql_close($link);
			echo "</form>";
			
		}
        }
}



//Step 4 Create and fill database.
function step4 ($host,$user,$password,$databasename,$newdatabasename)
{
	global $lang;
	//display the form to return to the previous step.
	
	function prev_form($host,$user,$password) {
		global $lang;
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo $lang["install"][30] .": <input type=\"hidden\" name=\"db_host\" value=\"". $host ."\"/><br />";
		echo $lang["install"][31] ." : <input type=\"hidden\" name=\"db_user\" value=\"". $user ."\"/>";
		echo $lang["install"][32] .": <input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][33]."\" /></p>";
		echo "</form>";
	}
	//Display the form to go to the next page
	function next_form()
	{
		global $lang;
		
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_4\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
		echo "</form>";
	}
	
	//Fill the database
	function fill_db()
	{
		global $lang;
		
		include ("_relpos.php");
		include ($phproot . "/glpi/common/classes.php");
		include ($phproot . "/glpi/common/functions.php");
		include ($phproot . "/glpi/config/config_db.php");
		$db = new DB;
		$db_file = $phproot ."/mysql/glpi-0.42-default.sql";
		$dbf_handle = fopen($db_file, "rt");
		$sql_query = fread($dbf_handle, filesize($db_file));
		fclose($dbf_handle);
		foreach ( explode(";\n", "$sql_query") as $sql_line) {
			if (get_magic_quotes_runtime()) $sql_line=stripslashes_deep($sql_line);
			$db->query($sql_line);
		}
		// Mise a jour de la langue par defaut
		$query = "UPDATE `glpi_config` SET default_language='".$_SESSION["dict"]."' ;";
		$db->query($query) or die("4203 ".$lang["update"][90].$db->error());

		// Mise a jour des prefs par defaut
		$query = "UPDATE `glpi_prefs` SET language='".$_SESSION["dict"]."' ;";
		$db->query($query) or die("4203 ".$lang["update"][90].$db->error());
	}
	$link = mysql_connect($host,$user,$password);
	if(!empty($databasename)) {
		$db_selected = mysql_select_db($databasename, $link);
		if (!$db_selected) {
			echo $lang["install"][41];
			echo "<br />";
			echo $lang["install"][36]." ". mysql_error();
			prev_form($host,$user,$password);
		}
		else {
			if (create_conn_file($host,$user,$password,$databasename)) {
				fill_db();
				echo "<p>".$lang["install"][43]."</p>";
				echo "<p>".$lang["install"][44]."</p>";
				echo "<p>".$lang["install"][45]."</p>";
				echo "<p>".$lang["install"][46]."</p>";
				next_form();
			}
			else {
				echo "<p>".$lang["install"][47]."</p>";
				prev_form();
			}
		}
		mysql_close($link);
	}
	elseif(!empty($newdatabasename)) {
		// BUG cette fonction est obsolète je l'ai remplacé par la nouvelle
                //if (mysql_create_db($newdatabasename)) {
		// END BUG
		if (mysql_query("CREATE DATABASE ".$newdatabasename)){

			echo "<p>Base de données créée </p>";
			mysql_select_db($newdatabasename, $link);
			if (create_conn_file($host,$user,$password,$newdatabasename)) {
				fill_db();
				echo "<p>".$lang["install"][43]."</p>";
				echo "<p>".$lang["install"][44]."</p>";
				echo "<p>".$lang["install"][45]."</p>";
				echo "<p>".$lang["install"][46]."</p>";
				next_form();
			}
			else {
					echo "<p>".$lang["install"][47]."</p>";
				prev_form();
			}
		}
		else {
			echo $lang["install"][48];
			echo "<br />".$lang["install"][42] . mysql_error();
			prev_form();
		}
		mysql_close($link);
	}
	else {
		echo "<p>".$lang["install"][49]. "</p>";
		prev_form();
		mysql_close($link);
	}
	
}

/*
// Step 5 Start the glpi configuration
//
function step5()
{
	global $lang;
	
	include ("_relpos.php");
	include ($phproot . "/glpi/common/classes.php");
	include ($phproot . "/glpi/common/functions.php");
	include ($phproot . "/glpi/config/config_db.php");
	$db = new DB;
	$query = "select * from glpi_config where ID = 1";
	$result = $db->query($query);
	echo "<p>".$lang["install"][51]. "</p>";
	echo "<p>".$lang["install"][52]. "</p>";
	echo "<form action=\"install.php\" method=\"post\">";
	$root_doc = ereg_replace("/install.php","",$_SERVER['REQUEST_URI']);
	echo "<p><label>".$lang["setup"][101]." : <input type=\"text\" name=\"root_doc\" value=\"". $root_doc ."\"></label></p>";
	echo "<p><label>".$lang["setup"][102]." <select name=\"event_loglevel\"><label></p>";
	$level=$db->result($result,0,"event_loglevel");
	echo "<option value=\"1\"";  if($level==1){ echo "selected";} echo ">".$lang["setup"][103]." </option>";
	echo "<option value=\"2\"";  if($level==2){ echo "selected";} echo ">".$lang["setup"][104]."</option>";
	echo "<option value=\"3\"";  if($level==3){ echo "selected";} echo ">".$lang["setup"][105]."</option>";
	echo "<option value=\"4\"";  if($level==4){ echo "selected";} echo ">".$lang["setup"][106]." </option>";
	echo "<option value=\"5\">".$lang["setup"][107]."</option>";
	echo "</select>";
	echo "<p><label>".$lang["setup"][108]." : <input type=\"text\" name=\"num_of_events\" value=\"". $db->result($result,0,"num_of_events") ."\"><label></p>";
	echo "<p><label>".$lang["setup"][109]." : <input type=\"text\" name=\"expire_events\" value=\"". $db->result($result,0,"expire_events") ."\"><label></p>";
	echo "<p> ".$lang["setup"][110]." :  <input type=\"radio\" name=\"jobs_at_login\" value=\"1\" checked=\"checked\" /><label>".$lang["choice"][0]."</label>";
	echo " <input type=\"radio\" name=\"jobs_at_login\" value=\"0\" /><label>".$lang["choice"][1] ."</label></p>";
	echo "<p><label>".$lang["setup"][111]."  : <input type=\"text\" name=\"list_limit\" value=\"". $db->result($result,0,"list_limit") ."\"><label></p>";
	echo "<p><label>".$lang["setup"][112]." : <input type=\"text\" name=\"cut\" value=\"". $db->result($result,0,"cut") ."\"><label></p>";
	echo "<p> ".$lang["setup"][219]." :  <input type=\"radio\" name=\"permit_helpdesk\" value=\"1\" /><label>".$lang["choice"][0]."</label>";
	echo "<input type=\"radio\" name=\"permit_helpdesk\" value=\"0\" checked=\"checked\" /><label>".$lang["choice"][1] ."</label></p>";
	echo "<input type=\"hidden\" name=\"install\" value=\"Etape_5\" />";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
	echo "</form>";
}
*/
// STEP 6 Get the config and fill database
/*
function step6($root_doc, $event_loglevel, $num_of_events, $expire_events,$jobs_at_login, $list_limit, $cut, $permit_helpdesk)
{
	global $lang;
	
	include ("_relpos.php");
	require_once ($phproot . "/glpi/common/classes.php");
	require_once ($phproot . "/glpi/common/functions.php");
	require_once ($phproot . "/glpi/config/config_db.php");
	$db = new DB;
	$query = "update glpi_config set root_doc = '". $root_doc ."', expire_events = '". $expire_events ."', event_loglevel = '". $event_loglevel ."', num_of_events = '". $num_of_events ."', jobs_at_login = '". $jobs_at_login ."', list_limit = '". $list_limit ."', cut = '". $cut ."', permit_helpdesk = '".$permit_helpdesk."'"; 
	$db->query($query);
	echo "<p>".$lang["install"][53]. "</p>";
	echo "<p>".$lang["install"][54]. "</p>";
	echo "<br /><form action=\"install.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"install\" value=\"Etape_6\" />";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][26]."\" /></p>";
	echo "</form>";
}
*/


function step7() {

	global $lang;
	include ("_relpos.php");
	require_once ($phproot . "/glpi/common/classes.php");
	require_once ($phproot . "/glpi/common/functions.php");
	require_once ($phproot . "/glpi/config/config_db.php");
	$db = new DB;
	$root_doc = ereg_replace("/install.php","",$_SERVER['REQUEST_URI']);
	$query = "update glpi_config set root_doc = '".$root_doc."'";
	$db->query($query);
	echo "<h2>".$lang["install"][55]."</h2>";
	echo "<p>".$lang["install"][57]."</p>";
	echo "<p><ul><li> ".$lang["install"][58]."</li>";
	echo "<li>".$lang["install"][59]."</li>";
	echo "<li>".$lang["install"][60]."</li>";
	echo "<li>".$lang["install"][61]."</li></ul></p>";
	echo "<p>".$lang["install"][62]."</p>";
	echo "<p>".$lang["install"][63]."</p>";
	echo "<p class='submit'> <a href=\"index.php\"><span class='button'>".$lang["install"][64]."</span></a></p>";
	}

//Create the file glpi/config/config_db.php
// an fill it with user connections info.
function create_conn_file($host,$user,$password,$dbname)
{
	$db_str = "<?php \n class DB extends DBmysql { \n var \$dbhost	= \"". $host ."\"; \n var \$dbuser 	= \"". $user ."\"; \n var \$dbpassword= \"". $password ."\"; \n var \$dbdefault	= \"". $dbname ."\"; \n } \n ?>";
	include ("_relpos.php");
	$fp = fopen($phproot ."/glpi/config/config_db.php",'wt');
	if($fp) {
		$fw = fwrite($fp,$db_str);
		fclose($fp);
		return true;
	}
	else return false;
}

function update1($host,$user,$password,$dbname) {
	
	global $lang;	
	include ("_relpos.php");
	if(create_conn_file($host,$user,$password,$dbname) && !empty($dbname)) {
		
		$from_install = true;
		include($phproot ."/update.php");
	}
	else {
		echo $lang["install"][70];
		echo "<h3>".$lang["install"][25]."</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"update\" value=\"yes\" />";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$lang["install"][25]."\" /></p>";
		echo "</form>";
	}
	
	
}




//------------Start of install script---------------------------
session_start();
include ("_relpos.php");
if(empty($_SESSION["dict"])) $_SESSION["dict"] = "french";
if(isset($_POST["language"])) $_SESSION["dict"] = $_POST["language"];
loadLang($_SESSION["dict"]);
	if(!isset($_POST["install"])) {
		$_SESSION = array();
		if(file_exists($phproot ."/glpi/config/config_db.php")) {
			include($phproot ."/index.php");
			die();
		}
		else {
			header_html("Language");
			choose_language();
			}
	}
	else {
		switch ($_POST["install"]) {
			
			case "lang_select" :
			header_html("Début de l'installation");
			step0();
			break;
			case "Etape_0" :
			header_html("Etape 0");
			$_SESSION["Test_session_GLPI"] = 1;
			step1($_POST["update"]);
			break;
			case "Etape_1" :
				header_html("Etape 1");
				step2($_POST["update"]);
				break;
			case "Etape_2" :
				header_html("Etape 2");
				step3($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["update"]);
				break;
			case "Etape_3" :
				header_html("Etape 3");
				if(empty($_POST["databasename"])) $_POST["databasename"] ="";
				if(empty($_POST["newdatabasename"])) $_POST["newdatabasename"] ="";
				step4($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"],$_POST["newdatabasename"]);
				break;
			case "Etape_4" :
				header_html("Etape 4");
				step7();
				break;
			/*	
			case "Etape_5" :
				header_html("Etape 5");
				step6($_POST["root_doc"], $_POST["event_loglevel"], $_POST["num_of_events"], $_POST["expire_events"], $_POST["jobs_at_login"],$_POST["list_limit"], $_POST["cut"],$_POST["permit_helpdesk"]);
				break;
			case "Etape_6" :
				header_html("Etape 6");
				step7();
				break;
			*/
			case "update_1" : 
				if(empty($_POST["databasename"])) $_POST["databasename"] ="";
				update1($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"]);
				break;
		}
	}
	footer_html();
//FIn du script
?>
