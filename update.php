<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 Original Author of file: Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");


function test_connect() {
$db = new DB;
$query = "select MAX(ID) from computers";
if($db->query($query)) {
	return true;
}
else return false;
}


//update the database.
function update_db()
{
$db = new DB;


// Version 0.2 et inferieures Changement du champs can_assign_job
 $query = "Alter table users drop can_assign_job";
 $db->query($query) or die("erreur lors de la migration".$db->error());
 $query = "Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no'";
 $db->query($query) or die("erreur lors de la migration".$db->error());
 $query = "Update users set can_assign_job = 'yes' where type = 'admin'";
 $db->query($query) or die("erreur lors de la migration".$db->error());
 

//Version 0.21 ajout du champ ramSize a la table printers si non existant.
$db = new DB;
$result = $db->query("SELECT * FROM printers");
$fields = mysql_num_fields($result);
$var1 = true;
for ($i=0; $i < $fields; $i++) {
	//$type  = mysql_field_type($result, $i);
	$name  = mysql_field_name($result, $i);
	//$len  = mysql_field_len($result, $i);
	$flags = mysql_field_flags($result, $i);
	if($name == "ramSize") {
		$var1 = false;
	}
}
if($var1 == true) {
	$query = "alter table printers add ramSize varchar(6) NOT NULL default ''";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

//Version 0.3
//Ajout de NOT NULL et des valeurs par defaut.

$query = "ALTER TABLE computers MODIFY name VARCHAR(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY type VARCHAR(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY os VARCHAR(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY osver VARCHAR(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY processor VARCHAR(30) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY processor_speed VARCHAR(30) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY hdspace VARCHAR(6) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY contact VARCHAR(90) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY contact_num VARCHAR(90) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE networking MODIFY ram varchar(10) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY serial varchar(50) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY otherserial varchar(50) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE software MODIFY name varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY platform varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY version varchar(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY location varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY comments text NOT NULL";


$query = "ALTER TABLE templates MODIFY templname varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY name varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY os varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY osver varchar(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY processor varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY processor_speed varchar(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY location varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY serial varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY otherserial varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY ramtype varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY ram varchar(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY network varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY hdspace varchar(10) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY contact varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY contact_num varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY comments text NOT NULL";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE users MODIFY password varchar(80) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE users MODIFY email varchar(80) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE users MODIFY location varchar(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE users MODIFY phone varchar(100) NOT NULL default ''";



//Version Superieur a 0.31 ajout de la table glpi_config

$query = "CREATE TABLE `glpi_config` (
  `config_id` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(200) NOT NULL default '',
  `jobs_at_login` varchar(200) NOT NULL default '',
  `sendexpire` varchar(200) NOT NULL default '',
  `cut` varchar(200) NOT NULL default '',
  `expire_events` varchar(200) NOT NULL default '',
  `list_limit` varchar(200) NOT NULL default '',
  `version` varchar(200) NOT NULL default '',
  `logotxt` varchar(200) NOT NULL default '',
  `root_doc` varchar(200) NOT NULL default '',
  `event_loglevel` varchar(200) NOT NULL default '',
  `mailing` varchar(200) NOT NULL default '',
  `imap_auth_server` varchar(200) NOT NULL default '',
  `imap_host` varchar(200) NOT NULL default '',
  `ldap_host` varchar(200) NOT NULL default '',
  `ldap_basedn` varchar(200) NOT NULL default '',
  `ldap_rootdn` varchar(200) NOT NULL default '',
  `ldap_pass` varchar(200) NOT NULL default '',
  `admin_email` varchar(200) NOT NULL default '',
  `mailing_signature` varchar(200) NOT NULL default '',
  `mailing_new_admin` varchar(200) NOT NULL default '',
  `mailing_attrib_admin` varchar(200) NOT NULL default '',
  `mailing_followup_admin` varchar(200) NOT NULL default '',
  `mailing_finish_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_admin` varchar(200) NOT NULL default '',
  `mailing_attrib_all_admin` varchar(200) NOT NULL default '',
  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_normal` varchar(200) NOT NULL default '',
  `mailing_attrib_all_normal` varchar(200) NOT NULL default '',
  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
  `mailing_attrib_attrib` varchar(200) NOT NULL default '',
  `mailing_followup_attrib` varchar(200) NOT NULL default '',
  `mailing_finish_attrib` varchar(200) NOT NULL default '',
  `mailing_new_user` varchar(200) NOT NULL default '',
  `mailing_attrib_user` varchar(200) NOT NULL default '',
  `mailing_followup_user` varchar(200) NOT NULL default '',
  `mailing_finish_user` varchar(200) NOT NULL default '',
  `ldap_field_name` varchar(200) NOT NULL default '',
  `ldap_field_email` varchar(200) NOT NULL default '',
  `ldap_field_location` varchar(200) NOT NULL default '',
  `ldap_field_realname` varchar(200) NOT NULL default '',
  `ldap_field_phone` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`config_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ";
$db->query($query) or die("erreur lors de la migration".$db->error());

$query = "INSERT INTO `glpi_config` VALUES (1, '10', '10', '1', '80', '30', '15', ' 0.3', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', 'ldap://localhost/', 'dc=melnibone', '', '', 'admsys@sic.sp2mi.xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
$db->query($query) or die("erreur lors de la migration".$db->error());

}


//Debut du script
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
echo "<head><title>GLPI Update</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" />\n";
echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />\n";
// Include CSS
echo "<style type=\"text/css\">\n";
include ($phproot . "/glpi/config/styles.css");
echo "</style>\n";

echo "</head>";

// Body with configured stuff

echo "<body>";

// step 1    avec bouton de confirmation
if(empty($_POST["continuer"])) {
	echo "Attention ! Vous allez mettre à jour votre base de données GLPI<br />";
	echo "Be carreful ! Your are going to update your GLPI database<br/>";
	echo "<form action=\"update.php\" method=\"post\">";
	echo "<input type=\"submit\" name=\"continuer\" value=\"Mettre à jour / Continue \" />";
	echo "</form>";
}
// Step 2  avec message d'erreur en cas d'echec de connexion
else {
	if(test_connect()) {
		echo "Connexion &agrave; la base de données réussie <br />";
		echo "Connection to the database  sucessful";
		update_db();
		echo "<br/>La mise &agrave; jour &agrave; réussie, votre base de données est actualisée \n<br /> vous pouvez supprimer le fichier update.php de votre repertoire";
	        echo "<br/>The update with successful, your data base is update \n<br /> you can remove the file update.php from your directory";
        }
	else {
		echo "<br /> <br />";
		echo "La connexion à la base de données a échouée, verifiez les paramètres de connexion figurant dans le fichier config.php <br />";
	
        echo "Connection to the database failed, you should verify the parameters of connection  in the file config.php";
        }
}

// Step 3 Si tout va bien





echo "</body></html>";
?>
