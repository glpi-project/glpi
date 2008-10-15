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
// Original Author of file: JMD
// Purpose of file:
// ----------------------------------------------------------------------

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

$NEEDED_ITEMS=array("cron","computer","device","printer","networking","peripheral","monitor","setup",
"software","infocom","phone","tracking","enterprise","ocsng","mailgate","rulesengine","rule.tracking","rule.softwarecategories","rule.ocs",
"user","reservation","reminder","admininfo","group","mailing","document","rule.dictionnary.software","rule.dictionnary.dropdown");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if (!is_writable(GLPI_LOCK_DIR)) {
	echo "\tERROR : " .GLPI_LOCK_DIR. " not writable\n";
	echo "\trun script as 'apache' user\n";
	exit (1);	
}

if (!isCommandLine()) {
	//The advantage of using background-image is that cron is called in a separate
	//request and thus does not slow down output of the main page as it would if called
	//from there.
	$image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
	header("Content-Type: image/gif");
	header("Content-Length: ".strlen($image));
	header("Cache-Control: no-cache,no-store");
	header("Pragma: no-cache");
	header("Connection: close");
	echo $image;
	flush();
}

//Definitions possibles des taches directement en passant un array (rappel les taches appellent des fonctions cron_matache() )
//$taches=array("test"=>30,"test2"=>10);
//$cron=new Cron($taches);

$cron=new Cron();
$cron->launch();

?>
