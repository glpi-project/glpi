<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
*/
 

//Toutes les options notifiées "non supportées par glpi v0.2 sont liées
// au fonctions LDAP et mail (Qmail) présentes dans IRMA
// GLPI  CONFIGURATION OPTIONS


// Basic MYSQL configuration change as you need/want.
// Configuration standard changez selon vos besoins.
class DB extends DBmysql {

	var $dbhost	= "localhost"; 
	var $dbuser 	= "root"; 
	var $dbpassword	= "";
	var $dbdefault	= "glpidb";
}


// Navigation Functions
// Fonctions du menu
class baseFunctions {
	// Could have inventory, maintain, admin and settings, 
	//changes these values on the dicts on header menu.

	
	var $inventory	= true;

	var $maintain	= true;

// set $admin to "false" to disable the LDAP-Browser
// Laisser $admin = false si vous n'utilisez pas le navigateur LDAP
// Le navigateur LDAP n'as pas été touché par les developpeurs de GLPI,
// il s'agit donc de la version basée sur IRMA.
// Il est conseillé de laisser false.
	var $admin	= false;

	var $settings	= true;

				
}

// Features configuration

// Log level :
// Niveau de log :

// 1 - Critical (login failures) |  (erreur de loging seulement)
// 2 - Severe - not used  | (non utilisé)
// 3 - Important - (sucessfull logins)  |  importants (loging réussis)
// 4 - Notice (updates, adds, deletes, tracking) | classique
// 5 - Junk (i.e., setup dropdown fields, update users or templates) | log tout (ou presque)
$cfg_features["event_loglevel"]	= 5;

// Address to send new posted jobs, status changes and assignments,
// e.g. an admin-mailinglist, leave empty to disable notification.
// Laissez ce champ vide (Non supporté par GLPI)
$cfg_features["job_email"]	= "";	

// Address to send new followups to, e. g. an admin mailinglist,
// leave empty to disable notification.
// laisser ce champs vide
$cfg_features["newfup_email"]	= "";

// Send mail if a new job gets assigned to someone, notify him that
// he has work to do. :)
// laisser ce champs à 0 (non supporté par GLPI v0.2)
$cfg_features["notify_assign"]	= 0;

// Also send mail to job_email, that the job has been assigned
// to someone.
// laisser ce champs a 0 (non supporté par GLPI v0.2)
$cfg_features["notify_ass_all"] = 0;	

// Send mail to whom the job belongs, notify him that it has been updated.
// laisser ce champs a 0 (non supporté par GLPI v0.2)
$cfg_features["notify_fups"]	= 0;	

// Send mail to the user who send the job to the tracking system through
// the help-desk.
// laisser ce champs a 0 (non supporté par GLPI v0.2)
$cfg_features["notify_users"]	= 0;	

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

// options d'installation et interface graphique
// Installation and graphical options

// version number
// numero de version
$cfg_install["version"]		=" 0.21 ";


//root document
//document root
$cfg_install["root"]		= "/glpi";

// dicts
//dictionnaires
$cfg_install["languages"]	= array("english","deutsch","french");

//logo
$cfg_layout["logogfx"]		= "/glpi/pics/logo-glpi.png";
//txt du logo
$cfg_layout["logotxt"]		= "GLPI powered by indepnet";


// Interface graphique...

$cfg_layout["body_bg"]		= "#FFFFFF";
$cfg_layout["body_text"]	= "#000000";
$cfg_layout["body_link"]	= "#009966";
$cfg_layout["body_vlink"]	= "#009966";
$cfg_layout["body_alink"]	= "#009966";

$cfg_layout["tab_bg_1"] 	= "#cccccc";
$cfg_layout["tab_bg_2"]		= "#dddddd";
$cfg_layout["tab_bg_3"]		= "#eeeeee";

// Signature for automatic generated E-Mails
// laisser ce champs a "" (non supporté par GLPI v0.2)
$cfg_layout["signature"]	= "";

// END OF CONFIGURATION
?>
