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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
 //And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features
*/

 

//Toutes les options notifiées "non supportées par glpi v0.2 sont liées
// au fonctions LDAP et mail (Qmail) présentes dans IRMA
// GLPI  CONFIGURATION OPTIONS


// *************************** Eléments à renseigner impérativement ********
// *************************************************************************
// *************************************************************************

// Basic MYSQL configuration change as you need/want.
// Configuration standard changez selon vos besoins.
class DB extends DBmysql {

	var $dbhost	= "localhost";
	var $dbuser 	= "glpiuser";
	var $dbpassword	= "";
	var $dbdefault	= "glpidb";
}


//root document
//document root
$cfg_install["root"]		= "/glpi";


// *************************** Eléments optionnels  **********************
// ***********************************************************************
// ***********************************************************************

// Navigation Functions
// Fonctions du menu
class baseFunctions {
	// Could have inventory, maintain, admin and settings, 
	//changes these values on the dicts on header menu.

	
	var $inventory	= true;

	var $maintain	= true;

	var $settings	= true;

				
}

// Gestion de source d'information alternatives pour le login
// telles que des serveurs de mail en imap pop...
// ports standards : pop 110 , imap 993
// Dans tous les cas le dernier type de login testé est la base de données
// Dans le cas où le login est incorrect dans la base mais est correct
// sur la source alternative, l'utilisateur est ajouté ou son mot de passe
// est modifié
// Si plusieurs sources alternatives sont définies, seule la première
// fournissant un login correct est utilisé

$cfg_login['imap']['auth_server'] = "";
$cfg_login['imap']['host'] = "";

// LDAP setup.
// We can use LDAP both for authentication and for user information
$cfg_login['ldap']['host'] = "";
$cfg_login['ldap']['basedn'] = "";
$cfg_login['ldap']['rootdn'] ="";
$cfg_login['ldap']['pass'] ="";
// some lDAP server (eg, M$ Active Directory) does not like anonymous
// bind
//$cfg_login['ldap']['rootdn'] = "cn=admin,ou=People,dc=sv,dc=lnf,dc=it";
//$cfg_login['ldap']['pass'] = "secret";
// relation between the GLPI users table field and the LDAP field
$cfg_login['ldap']['fields'] = "";
// $cfg_login['ldap']['fields'] = array( "name" => "uid", "email" => "mail", "location" => "physicaldeliveryofficename", "phone" => "telephonenumber", "realname" => "cn");


//other sources
//$cfg_login['other_source']...

// Features configuration

// Log level :
// Niveau de log :

// 1 - Critical (login failures) |  (erreur de loging seulement)
// 2 - Severe - not used  | (non utilisé)
// 3 - Important - (sucessfull logins)  |  importants (loging réussis)
// 4 - Notice (updates, adds, deletes, tracking) | classique
// 5 - Junk (i.e., setup dropdown fields, update users or templates) | log tout (ou presque)
$cfg_features["event_loglevel"]	= 5;

// Utilisation des fonctions mailing ou non, mettez 1 si vous voulez utiliser les 
//notifications par mail.
//Necessite que votre fonction mail() fonctionne.
$cfg_features["mailing"]	= 0;	
// Addresse de l'administrateur (obligatoire si mailing activé)
$cfg_mailing["admin_email"]	= " ";
// Signature for automatic generated E-Mails
$cfg_mailing["signature"]	= "SIGNATURE";


// A utiliser  uniquement si $cfg_features["mailing"] = 1;
// Définition des envois des mails d'informations
// admin : vers le mail $cfg_features["admin_email"]
// all_admin : tous les utilisateurs en mode admin
// all_normal : toutes les utilisateurs en mode normal
// attrib : personne responsable de la tache
// user : utilisateur demandeur
// 1 pour l'envoi et 0 dans le cas contraire 

$cfg_mailing["new"]["admin"]=1;
$cfg_mailing["attrib"]["admin"]=1;
$cfg_mailing["followup"]["admin"]=1;
$cfg_mailing["finish"]["admin"]=1;

$cfg_mailing["new"]["all_admin"]=0;
$cfg_mailing["attrib"]["all_admin"]=0;
$cfg_mailing["followup"]["all_admin"]=0;
$cfg_mailing["finish"]["all_admin"]=0;


$cfg_mailing["new"]["all_normal"]=0;
$cfg_mailing["attrib"]["all_normal"]=0;
$cfg_mailing["followup"]["all_normal"]=0;
$cfg_mailing["finish"]["all_normal"]=0;

$cfg_mailing["attrib"]["attrib"]=1;
$cfg_mailing["followup"]["attrib"]=1;
$cfg_mailing["finish"]["attrib"]=1;

$cfg_mailing["new"]["user"]=1;
$cfg_mailing["attrib"]["user"]=1;
$cfg_mailing["followup"]["user"]=1;
$cfg_mailing["finish"]["user"]=1;



// Show jobs at login.
// Montrer les interventions au loging (1 = oui | 0 = non)
$cfg_features["jobs_at_login"]	= 1;

// Show last num_of_events on login.
// Nombre des derniers evenements presents dans le tableau au loging
$cfg_features["num_of_events"]	= 10;

// Send Expire Headers and set Meta-Tags for proper content expiration.
$cfg_features["sendexpire"]		= 1;

// In listings, cut long text fields after cut characters.
$cfg_features["cut"]			= 80;	

// Expire events older than this days at every login
// (only admin-level login, set to 0 to disable expiration).
// Temps durant lequel on log les actions ayant eu lieu
// mettez cette variable a 0 pour conserver tous les logs (prend beaucoup de place dans la bdd)
$cfg_features["expire_events"]	= 30;

// Threshold for long listings, activates pager.
//Nombre d'occurence (ordinateurs, imprimantes etc etc...) qui apparaitrons dans
//la liste de recherche par page.
$cfg_features["list_limit"]		= 15;	
									

// Report generation
// Default Report included
$report_list["default"]["name"] = "Rapport par défaut";
$report_list["default"]["file"] = "reports/default.php";

// Vous pouvez faire vos propres rapports :
// My Own Report:
// $report_list["my_own"]["name"] = "My Own Report";
// $report_list["my_own"]["file"] = "reports/my_own.php";


// Rapport ajoutés par GLPI V0.2
$report_list["Maintenance"]["name"] = "Maintenance";
$report_list["Maintenance"]["file"] = "reports/maintenance.php";
$report_list["Par_annee"]["name"] = "Par date";
$report_list["Par_annee"]["file"] = "reports/parAnnee.php";
$report_list["excel"]["name"] = "excel";
$report_list["excel"]["file"] = "reports/geneExcel.php";

// options d'installation
// Installation  option

 // dicts
//dictionnaires
$cfg_install["languages"]	= array("english","deutsch","french");

// END OF CONFIGURATION


// version number
// numero de version
$cfg_install["version"]		=" 0.31";
$cfg_layout["logotxt"]		= "GLPI powered by indepnet";


?>
