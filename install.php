<?php
/*

  ----------------------------------------------------------------------
 GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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

// Ce script génère ses propres messages d'erreur 
// Pas besoin des warnings de PHP
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


//confirm install form
function step0()
{
echo "<h3>Vous allez installer GLPI, voulez vous continuer ?</h3>";
echo "<form action=\"install.php\" method=\"post\">";
echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
echo "</form>";
}

//Step 1 checking some compatibilty issue and some write tests.
function step1()
{
	$error = 0;
	echo "Nous allons procéder à des tests afin de vérifier que votre environnement est bien compatible avec l'éxecution de GLPI<br /><br />";
	echo "<table>";
	echo "<tr><th>Test effectué</th><th >Résultats</th></tr>";
// Parser test
	echo "<tr><td><h4>Test du Parser PHP</h4></td>";
// PHP Version  - exclude PHP3
	if (substr(phpversion(),0,1) == "3") {
		$error = 2;
		echo "<td>Vous devez installer  PHP4<br /><br /> Vous pouvez le télécharger ici : <a href='http://www.php.net'>www.php.net</a>.\n</td>";
	}
	elseif (substr(phpversion(),0,3) == "4.0" and ereg("0|1",substr(phpversion(),4,1))) {
		echo "<td><span class='wrong'>&nbsp;<td>Vous utilisez une des versions 4.0.0 ou 4.0.1 de PHP - Nous vous conseillons de mettre à jour votre PHP<td>";
		if($error != 2) $error = 1;
	}
	else {
		echo "<td>La version de PHP est 4.x - Parfait !</td></tr>";
	}
// end parser test
//	echo "<h2>GLPI environment test</h2>";
//	echo "<table>";
//	echo "<tr><th>Test effectué</th><th colspan='2'>Résultats</th></tr>";
// session test
	echo "<tr><td><h4>Test des Sessions</h4></td>";



  // check whether session are enabled at all!!
	if (!extension_loaded('session')) {
		$error = 2;
		echo "<td><h2>Votre parser PHP n'as pas été compilé avec le support des sessions ! </h2></td></tr>";
	} 
	if ($_SESSION["Test_session_GLPI"] == 1) {
		echo "<td><i>Le support des sessions est opérationnel - Parfait</i></td></tr>";
	}
	else {
		if($error != 2) $error = 1;
		echo "<td>Verifiez que le support des sessions est bien activé dans votre php.ini</td></tr>";
	}


// *********
// file test

// il faut un test dans /dump et un dans /tmp pour phpexcel et /glpi/config/

	echo "<tr><td><h4>Tests d'écriture de fichiers dump</h4></td>";
	
	$fp = fopen("backups/dump/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>Le fichier n'a pas pu être créé.</p> Vérifiez que PHP a un droit d'écriture pour le répertoire 'backups/dump/' Si vous êtes sous un environnement de Microsoft Windows, regardez si c'est en lecture seule.</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink("backups/dump/test_glpi.txt");
		if (!$delete) {
			echo "<td>Le fichier a été créé mais n'a pas pu être supprimé.</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>Le fichier a été créé et supprimé - Parfait !</td></tr>";

		}
	}
	echo "<tr><td><h4>Test d'écriture de fichiers temporaires</h4></td>";
		$fp = fopen("reports/reports/convexcel/tmp/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>Le fichier n'a pas pu être créé.</p> Vérifiez que PHP a un droit d'écriture pour le répertoire : 'reports/reports/convexcel/tmp/' Si vous êtes sous un environnement de Microsoft Windows, regardez si c'est en lecture seule.</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink("reports/reports/convexcel/tmp/test_glpi.txt");
		if (!$delete) {
			echo "<td>Le fichier a été créé mais n'a pas pu être supprimé.</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>Le fichier a été créé et supprimé - Parfait !</td></tr>";
		}
	}
	echo "<tr><td><h4>Test d'écriture de fichier configuration</h4></td>";
	$fp = fopen("glpi/config/test_glpi.txt",'w');
	if (empty($fp)) {
		echo "<td><p class='red'>Le fichier n'a pas pu être créé.</p> Vérifiez que PHP a un droit d'écriture pour le répertoire : 'glpi/config/' Si vous êtes sous un environnement de Microsoft Windows, regardez si c'est en lecture seule.</td></tr>";
		$error = 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink("glpi/config/test_glpi.txt");
		if (!$delete) {
			echo "<td>Le fichier a été créé mais n'a pas pu être supprimé.</td></tr>";
			if($error != 2) $error = 1;
		}
		else {
			echo "<td>Le fichier a été créé et supprimé - Parfait !</td></tr>";
		}
	}
	echo "</table>";
        switch ($error) {
		case 0 :       
        	echo "<h3>Continuer ?</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		echo "</form>";
		break;
		case 1 :       
        	echo "<h3>Continuer ?</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		echo "</form> &nbsp;&nbsp;";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Re-essayer\" /></p>";
		echo "</form>";
		break;
		case 2 :       
        	echo "<h3>Continuer ?</h3>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<p class=\"submit\"><input type=\"hidden\" name=\"install\" value=\"Etape_0\" />";
		echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Re-essayer\" /></p>";
		echo "</form>";
		break;
	}
	

}

//step 2 import mysql settings.
function step2()
{

		echo "<p>Nous allons maintenant configurer votre connection à la base de données</p>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<fieldset><legend>Paramètres de connection à la base de données</legend>";
                echo "<p><label>Mysql server : <input type=\"text\" name=\"db_host\" /></label></p>";
		echo "<p ><label>Mysql user : <input type=\"text\" name=\"db_user\" /></label></p>";
		echo "<p ><label>Mysql pass : <input type=\"password\" name=\"db_pass\" /></label></p></fieldset>";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
	
}

//step 3 test mysql settings and select database.
function step3($host,$user,$password)
{
	error_reporting(16);
	echo "<h3>Test de la connection à la base de données</h3>";
	$link = mysql_connect($host,$user,$password);
	if (!$link || empty($host) || empty($user)) {
		echo "Impossible de se connecter à la base de données : \n
		<br /> Le serveur à répondu : ".mysql_error();
		if(empty($host) || empty($user)) {
			echo "Le champs serveur ou/et le champ user est vide";
		}
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_1\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\"  value=\"Retour\" /></p>";
		echo "</form>";
                //     BUG
                // IL y a un pb ici la validation du bouton devrait nous faire revenir sur l'étape 2 hors on reste bloqué... pas trouvé pourquoi.
                // END BUG
	}
	else {
		echo "Connection réussie !! <br />";
		echo " Veuillez selectionner une base de données : ";
		echo "<form action=\"install.php\" method=\"post\">";
		$db_list = mysql_list_dbs($link);
		while ($row = mysql_fetch_object($db_list)) {
			echo "<p><input type=\"radio\" name=\"databasename\" value=\"". $row->Database ."\" />$row->Database.</p>";
		}
		echo "<p><input type=\"radio\" name=\"databasename\" value=\"0\" />Créer une nouvelle base : ";
		echo "<input type=\"text\" name=\"newdatabasename\"/></p>";
		echo "<input type=\"hidden\" name=\"db_host\" value=\"". $host ."\" />";
		echo "<input type=\"hidden\" name=\"db_user\" value=\"". $user ."\" />";
		echo "<input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_3\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		mysql_close($link);
	        echo "</form>";
        }
}

//Step 4 Create and fill database.
function step4 ($host,$user,$password,$databasename,$newdatabasename)
{
	//display the form to return to the previous step.
	
	function prev_form() {
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo "Mysql server : <input type=\"hidden\" name=\"db_host\" value=\"". $host ."\"/><br />";
		echo "Mysql user : <input type=\"hidden\" name=\"db_user\" value=\"". $user ."\"/>";
		echo "Mysql pass : <input type=\"hidden\" name=\"db_pass\" value=\"". $password ."\" />";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_2\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Retour\" /></p>";
		echo "</form>";
	}
	//Display the form to go to the next page
	function next_form()
	{
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_4\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		echo "</form>";
	}
	
	//Fill the database
	function fill_db()
	{
		include ("_relpos.php");
		include ($phproot . "/glpi/includes.php");
		$db = new DB;
		$db_file = $phproot ."/mysql/glpidb-0.4-default.sql";
		$dbf_handle = fopen($db_file, "rt");
		$sql_query = fread($dbf_handle, filesize($db_file));
		fclose($dbf_handle);
		foreach ( explode(";\n", "$sql_query") as $sql_line) {
			$db->query($sql_line);
		}
	
	}
	
	//Create the file glpi/config/config_db.php
	// an fill it with user connections info.
	function create_conn_file($host,$user,$password,$dbname)
	{

		$db_str = "<?php \n class DB extends DBmysql { \n var \$dbhost	= \"". $host ."\"; \n var \$dbuser 	= \"". $user ."\"; \n var \$dbpassword= \"". $password ."\"; \n var \$dbdefault	= \"". $dbname ."\"; \n } \n ?>";
		$fp = fopen("glpi/config/config_db.php",'wt');
		if($fp) {
			$fw = fwrite($fp,$db_str);
			fclose($fp);
			return true;
		}
		else return false;
	}
	
	
	
	$link = mysql_connect($host,$user,$password);
	if(!empty($databasename)) {
		$db_selected = mysql_select_db($databasename, $link);
		if (!$db_selected) {
			echo "Impossible d\'utiliser la base : ";
			echo "<br />Le serveur à répondu " . mysql_error();
			prev_form();
		}
		else {
			if (create_conn_file($host,$user,$password,$databasename)) {
				fill_db();
				echo "<p>OK - La base a bien été initialisée</p>";
				echo "<p>Des valeurs par défaut ont été entrées, n'hésitez pas à supprimer ces dernières</p>";
				echo "<p>Ne supprimez pas l'utilisateur \"helpdesk\"</p>";
				echo "<p>A la première connection vous pouvez utiliser le login \"glpi\" et le mot de passe \"glpi\" pour accéder à l'application avec des droits administrateur</p>";
				next_form();
			}
			else {
				echo "<p>Impossible d'écrire le fichier de configuration de votre base de données</p>";
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
				echo "<p>OK - La base a bien été initialisée</p>";
				echo "<p>Des valeurs par defaut on été entrées, n'hésitez pas à supprimer ces dernières</p>";
				echo "<p>Ne supprimez pas l'utilisateur \"helpdesk\"</p>";
				echo "<p>A la première connection vous pouvez utiliser le login \"glpi\" et le mot de passe \"glpi\" pour accéder à l'application avec des droits administrateur</p>";
				next_form();
			}
			else {
				echo "<p>Impossible d'écrire le fichiers de configuration de votre base de données</p>";
				prev_form();
			}
		}
		else {
			echo "Erreur lors de la création de la base !";
			echo "<br />Le serveur a répondu : " . mysql_error();
			prev_form();
		}
		mysql_close($link);
	}
	else {
		echo "<p>Vous n'avez pas séléctionné de base de données !</p>";
		prev_form();
		mysql_close($link);
	}
	
}

// Step 5 Start the glpi configuration
//
function step5()
{
		include ("_relpos.php");
		include ($phproot . "/glpi/includes.php");
		$db = new DB;
		$query = "select * from glpi_config where config_id = 1";
		$result = $db->query($query);
		echo "Configuration de GLPI : ";
		echo "<p>Les valeurs présélectionnées sont les valeurs par defaut, il est recommandé de laisser ces valeurs</p>";
		echo "<form action=\"install.php\" method=\"post\">";
		echo "<p><label>Document root : <input type=\"text\" name=\"root_doc\" value=\"". $db->result($result,0,"root_doc") ."\"></label></p>";
		echo "<p><label>Niveau de log : <select name=\"event_loglevel\"><label></p>";
		echo "<option value=\"1\">1- Critique (erreur de login seulement) </option>";
		echo "<option value=\"2\">2- Sévère (Non utilisée) </option>";
		echo "<option value=\"3\">3- Important (logins réussis) </option>";
		echo "<option value=\"4\" selected>4- Notices (Ajout, suppression, tracking) </option>";
		echo "<option value=\"5\">5- Complet (Log quasiment tout) </option>";
		echo "</select>";
		echo "<p><label>Nombre d'évenements de log a afficher : <input type=\"text\" name=\"num_of_events\" value=\"". $db->result($result,0,"num_of_events") ."\"><label></p>";
		echo "<p><label>Temps en jours durant lequel on conserve les logs (0 pour infini) : <input type=\"text\" name=\"expire_events\" value=\"". $db->result($result,0,"expire_events") ."\"><label></p>";
		echo "<p> Montrer les interventions au login :  <input type=\"radio\" name=\"jobs_at_login\" value=\"1\" checked /><label>Oui</label>";
		echo " <input type=\"radio\" name=\"jobs_at_login\" value=\"0\" /><label> Non </label></p>";
		echo "<p><label>Nombre d'élements à afficher par page  : <input type=\"text\" name=\"list_limit\" value=\"". $db->result($result,0,"list_limit") ."\"><label></p>";
		echo "<p><label>Nombre de caractères maximum pour chaque éléments de la liste : <input type=\"text\" name=\"cut\" value=\"". $db->result($result,0,"cut") ."\"><label></p>";
		echo "<p>Voulez vous utiliser les fonctionnalitées de mailing ? (Notifications par mail) ";
		echo " <input type=\"radio\" name=\"mailing\" value=\"1\" /><label>Oui</label>";
		echo "<input type=\"radio\" name=\"mailing\" value=\"0\" checked /><label>Non<label></p>";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_5\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		echo "</form>";
}

// STEP 6 Get the config and fill database
// Config the mailing features if enabled by user
function step6($root_doc, $event_loglevel, $num_of_events, $expire_events, $list_limit, $cut, $mailing)
{
	
	//Display a great mailing config form
	function mailing_form()
	{
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"mailing\" value=\"1\">";
		echo "<br />Mail de l'administrateur systeme : <input type=\"text\" name=\"admin_email\" />";
		echo "<br />Signature automatique  : <input type=\"text\" name=\"mailing_signature\" />";
		echo "<br /> Options de configuration : <br />";
		
		echo "<table><tr>L'administrateur Système doit recevoir une notification:<td>&nbsp;</td>&nbsp;<td></td>&nbsp;<td></td></tr>";
		echo "<tr><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_new_admin\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_admin\" value=\"0\"></td></tr>";
		echo "<tr><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_followup_admin\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_finish_admin\" value=\"0\"></td></tr>";
		
		echo "<table><tr>Les utilisateurs ayant un accés Admin doivent recevoir une notification :<td></td><td></td><td></td></tr>";
		echo "<tr><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_all_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_all_admin\" value=\"0\"></td></tr>";
		echo "<tr><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_followup_all_admin\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_finish_all_admin\" value=\"0\"></td></tr>";
		
		
		echo "<table><tr>Les utilisateurs ayant un accés Normal doivent recevoir une notification :<td></td><td></td><td></td></tr>";
		echo "<tr><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_all_admin\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_new_all_normal\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_all_normal\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_all_normal\" value=\"0\"></td></tr>";
		echo "<tr><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_followup_all_normal\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_finish_all_normal\" value=\"0\"></td></tr>";
		
		
		echo "<table><tr>La personne responsable de la tache doit recevoir un notification :<td></td><td></td><td></td></tr>";
		echo "<tr><td>A chaque nouvelle intervention</td><td>Oui : <input type=\"radio\" name=\"mailing_new_attrib\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_new_attrib\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque changement de responsable</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_attrib\" value=\"0\"></td></tr>";
		echo "<tr><td>Pour chaque nouveau suivi</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_followup_attrib\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque fois qu'une intervention est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_finish_attrib\" value=\"0\"><td></td></tr>";
		
		echo "<table><tr>L'utilisateur demandeur doit recevoir une notification:<td></td><td></td><td></td></tr>";
		echo "<tr><td>A chaque nouvelle intervention le concernant</td><td>Oui : <input type=\"radio\" name=\"mailing_new_user\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_new_user\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque changement de responsable d'une intervention le concernant</td><td>Oui : <input type=\"radio\" name=\"mailing_attrib_user\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_attrib_user\" value=\"0\"></td></tr>";
		echo "<tr><td>Pour chaque nouveau suivi sur une intervention le concernant</td><td>Oui : <input type=\"radio\" name=\"mailing_followup_user\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_followup_user\" value=\"0\"></td></tr>";
		echo "<tr><td>A chaque fois qu'une intervention le concernant est marquée comme terminée</td><td>Oui : <input type=\"radio\" name=\"mailing_finish_user\" value=\"1\"></td><td>Non : <input type=\"radio\" name=\"mailing_finish_user\" value=\"0\"></td></tr>";
		
				echo "</table>";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_6\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		echo "</form>";
	}
	
	include ("_relpos.php");
	require_once ($phproot . "/glpi/includes.php");
	$db = new DB;
	$query = "update glpi_config set root_doc = '". $root_doc ."', event_loglevel = '". $event_loglevel ."', num_of_events = '". $num_of_events ."', list_limit = '". $list_limit ."', cut = '". $cut ."'"; 
	$db->query($query);
	echo "Votre configuration a bien été enregistrée";
	if($mailing == 1) {
		if (function_exists('mail')) {
			echo "<br />La fonction mail() existe bien sur votre système : Veuillez configurer les envois de mails.";
			$query = "update glpi_config set mailing = '1'";
			$db->query($query);
			mailing_form();
		}
		else {
			echo " La fonction mail n'existe pas sur ce système : impossible d'utiliser les notifications par mail";
			echo "<br /><form action=\"install.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"install\" value=\"Etape_4\" />";
			echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Retour\" />";
			echo "</form>";
		}
	}
	else {
		echo "<br />Vous avez choisi de ne pas utiliser les notification par mail, vous pouvez passer à l'étape suivante";
		echo "<br /><form action=\"install.php\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"install\" value=\"Etape_6\" />";
		echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
		echo "</form>";
		
	} 
	

	
}


// STEP 7 Display a great form for LDAP and IMAP options
function step7()
{	
	include ("_relpos.php");
	require_once ($phproot . "/glpi/includes.php");
	$db = new DB;
	$query = "select * from glpi_config where config_id = 1";
	$result = $db->query($query);
	echo "Configuration des paramètres de connection externe";
	echo "<br /> Si vous ne souhaitez pas utiliser LDAP ou/et IMAP comme source(s) de connection laissez les champs vides";
	echo "<br /><form action=\"install.php\" method=\"post\">";
	echo "<table>";
	echo "<tr><td>LDAP configuration</td><td></td></tr>";
	echo "<tr><td>LDAP Host</td><td><input type=\"text\" name=\"ldap_host\" value=\"". $db->result($result,0,"ldap_host") ."\"/></td></tr>";
	echo "<tr><td>Basedn</td><td><input type=\"text\" name=\"ldap_basedn\" value=\"". $db->result($result,0,"ldap_basedn") ."\" /></td></tr>";
	echo "<tr><td>rootdn (for non anonymous binds)</td><td><input type=\"text\" name=\"ldap_rootdn\" value=\"". $db->result($result,0,"ldap_rootdn") ."\" /></td></tr>";
	echo "<tr><td>Pass (for non-anonymous binds)</td><td><input type=\"text\" name=\"ldap_pass\" value=\"". $db->result($result,0,"ldap_pass") ."\" /></td></tr>";
	echo "<tr><td>IMAP configuration</td><td></td></tr>";
	echo "<tr><td>IMAP Auth Server</td><td><input type=\"text\" name=\"imap_auth_server\" value=\"". $db->result($result,0,"imap_auth_server") ."\" /></td></tr>";
	echo "<tr><td>IMAP Host Name (users email will be login@thishost)</td><td><input type=\"text\" name=\"imap_host\" value=\"". $db->result($result,0,"imap_host") ."\" /></td></tr>";
	echo "</table>";
	echo "<input type=\"hidden\" name=\"install\" value=\"Etape_7\" />";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"submit\" class=\"submit\" value=\"Continuer\" /></p>";
	echo "</form>";
}

//Step 8 : Get and test LDAP and IMAP settings and fill the database And finish the install
function step8($ldap_host,$ldap_basedn,$ldap_rootdn,$ldap_pass,$imap_auth_server,$imap_host)
{
	include ("_relpos.php");
	require_once ($phproot . "/glpi/includes.php");
	$db = new DB;
	if(!empty($ldap_host)) {
		if (extension_loaded('ldap')) {
			//TODO : test the remote LDAP connection
			$query = "update glpi_config set ldap_host = '". $ldap_host ."', ";
			$query.= "ldap_basedn = '". $ldap_basedn ."', ldap_rootdn = '". $ldap_rootdn ."', ";
			$query .= "ldap_pass = '". $ldap_pass ."' where config_id = '1' ";
			$db->query($query);
			echo "Vos paramètres de connection LDAP ont bien été enregistrés";
		}
		else {
			echo " La librairie LDAP pour PHP ne semble pas être installée sur votre système, impossible d'utiliser les identifications LDAP pour l'instant";
		}
	}
	if(!empty($imap_host)) {
		if(function_exists('imap_open')) {
			//TODO : test the remote IMAP connection
			$query = "update glpi_config set imap_auth_server = '". $imap_auth_server ."', ";
			$query.= "imap_host = '". $imap_host ."' where config_id = '1'";
			$db->query($query);
			echo "Vos paramètres de connection IMAP ont bien été enregistrés";
		}
		else {
			echo " La librairie IMAP pour PHP ne semble pas installée sur votre système, impossible d'utiliser les identification IMAP pour l'instant";
		}
	}
	echo "<h2>L'installation s'est bien terminée </h2>";
	echo "<p>Il est recommandé maintenant d'appliquer un chmod+0 sur le fichier install.php</p>";
	echo "<p>Vous pouvez utiliser l'application en cliquant <a href=\"index.php\">sur ce lien </a>.</p>";
	echo "<p>Les logins mots de passes par defauts sont :</p>";
	echo "<p>&nbsp;<li> glpi/glpi pour le compte administrateur</li>";
	echo "&nbsp;<li>tech/tech pour le compte technicien</li>";
	echo "&nbsp;<li>normal pour le compte normal</li>";
	echo "&nbsp;<li>post-only/post-only pour le compte postonly</li></p>";
	echo "<p>Vous pouvez supprimer ces comptes ainsi que les premières entrées dans la base de données.</p>";
	echo "<p>Attention tout de même NE SUPPRIMEZ PAS l'utilisateur HELPDESK.</p>";
}

//fill database with mailing configuration
function mailing_config_to_db($admin_email, $mailing_signature,$mailing_new_admin,$mailing_attrib_admin,$mailing_followup_admin,$mailing_finish_admin,$mailing_new_all_admin,$mailing_attrib_all_admin,$mailing_followup_all_admin,$mailing_finish_all_admin,$mailing_new_all_normal,$mailing_attrib_all_normal,$mailing_followup_all_normal,$mailing_finish_all_normal,$mailing_attrib_attrib,$mailing_followup_attrib,$mailing_finish_attrib,$mailing_new_user,$mailing_attrib_user,$mailing_followup_user,$mailing_finish_user,$mailing_new_attrib)
{
	include ("_relpos.php");
	require_once ($phproot . "/glpi/includes.php");
	$db = new DB;
	$query = "update glpi_config set admin_email = '$admin_email', ";
	$query .= "mailing_signature = '". $mailing_signature ."', ";
	$query .= "mailing_new_admin = '". $mailing_new_admin ."', ";
	$query .= "mailing_attrib_admin = '". $mailing_attrib_admin ."', ";
	$query .= "mailing_followup_admin = '". $mailing_followup_admin ."', ";
	$query .= "mailing_finish_admin = '". $mailing_finish_admin ."', ";
	$query .= "mailing_new_all_admin = '". $mailing_new_all_admin ."', ";
	$query .= "mailing_attrib_all_admin = '". $mailing_attrib_all_admin ."', ";
	$query .= "mailing_followup_all_admin = '". $mailing_followup_all_admin ."', ";
	$query .= "mailing_finish_all_admin = '". $mailing_finish_all_admin ."', ";
	$query .= "mailing_new_all_normal = '". $mailing_new_all_normal ."', ";
	$query .= "mailing_attrib_all_normal = '". $mailing_attrib_all_normal ."', ";
	$query .= "mailing_followup_all_normal = '". $mailing_followup_all_normal ."', ";
	$query .= "mailing_finish_all_normal = '". $mailing_finish_all_normal ."', ";
	$query .= "mailing_attrib_attrib = '". $mailing_attrib_attrib ."', ";
	$query .= "mailing_followup_attrib = '". $mailing_followup_attrib ."', ";
	$query .= "mailing_finish_attrib = '". $mailing_finish_attrib ."', ";
	$query .= "mailing_new_user = '". $mailing_new_user ."', ";
	$query .= "mailing_attrib_user = '". $mailing_attrib_user ."', ";
	$query .= "mailing_followup_user = '". $mailing_followup_user ."', ";
	$query .= "mailing_finish_user = '". $mailing_finish_user ."', ";
	$query .= "mailing_new_attrib = '". $mailing_new_attrib ."' ";
	$query .= "where config_id = 1";
	$db->query($query);
}



//------------Start of install script---------------------------
include ("_relpos.php");
	if(!isset($_POST["install"])) {
		if(file_exists($phproot ."/glpi/config/config_db.php")) {
			include($phproot ."/index.php");
			die();
		}
		else {
			header_html("Début de l'installation");
			step0();
		}
	}
	else {
		switch ($_POST["install"]) {
			case "Etape_0" :
				session_start();
				header_html("Etape 0");
				$_SESSION["Test_session_GLPI"] = 1;
				session_destroy();
				step1();
				break;
			case "Etape_1" :
				header_html("Etape 1");
				step2();
				break;
			case "Etape_2" :
				header_html("Etape 2");
				step3($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"]);
				break;
			case "Etape_3" :
				header_html("Etape 3");
				if(empty($_POST["databasename"])) $_POST["databasename"] ="";
				if(empty($_POST["newdatabasename"])) $_POST["newdatabasename"] ="";
				step4($_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["databasename"],$_POST["newdatabasename"]);
				break;
			case "Etape_4" :
				header_html("Etape 4");
				step5();
				break;
			case "Etape_5" :
				header_html("Etape 5");
				step6($_POST["root_doc"], $_POST["event_loglevel"], $_POST["num_of_events"], $_POST["expire_events"], $_POST["list_limit"], $_POST["cut"], $_POST["mailing"]);
				break;
			case "Etape_6" :
				header_html("Etape 6");
				if(!empty($_POST["mailing"])) {
				
				mailing_config_to_db($_POST["admin_email"],$_POST["mailing_signature"],$_POST["mailing_new_admin"],$_POST["mailing_attrib_admin"],$_POST["mailing_followup_admin"],$_POST["mailing_finish_admin"],$_POST["mailing_new_all_admin"],$_POST["mailing_attrib_all_admin"],$_POST["mailing_followup_all_admin"],$_POST["mailing_finish_all_admin"],$_POST["mailing_new_all_normal"],$_POST["mailing_attrib_all_normal"],$_POST["mailing_followup_all_normal"],$_POST["mailing_finish_all_normal"],$_POST["mailing_attrib_attrib"],$_POST["mailing_followup_attrib"],$_POST["mailing_finish_attrib"],$_POST["mailing_new_user"],$_POST["mailing_attrib_user"],$_POST["mailing_followup_user"],$_POST["mailing_finish_user"],$_POST["mailing_new_attrib"]);
				}
				step7();
				break;
			case "Etape_7" :
				header_html("Etape 7");
				step8($_POST["ldap_host"],$_POST["ldap_basedn"],$_POST["ldap_rootdn"],$_POST["ldap_pass"],$_POST["imap_auth_server"],$_POST["imap_host"]);
				break;
		}
	}
	footer_html();
//FIn du script
?>
