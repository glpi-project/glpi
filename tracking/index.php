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


*/

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");

checkAuthentication("normal");

commonHeader("Tracking",$_SERVER["PHP_SELF"]);

 // titre
        echo "<div align='center'><table border='0'><tr><td><b>";
        echo "<img src=\"".$HTMLRel."pics/suivi-intervention.png\" ></td><td><span class='icon_nav'>".$lang["tracking"][0]."</span>";
        echo "</b></td></tr></table></div>";


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;

if(empty($tab["start"])) $tab["start"] = 0;

if (isAdmin($_SESSION["glpitype"])&&isset($tab["delete"])&&!empty($tab["todel"])){
	$j=new Job;
	foreach ($tab["todel"] as $key => $val){
		if ($val==1) $j->deleteInDB($key);
		}
	}


if (isset($tab["show"]))
{
	if(isset($tab["contains"]))
	{
		searchFormTracking($tab["show"],$tab["contains"]);
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$tab["show"],$tab["contains"],"",$tab["start"]);
		
	}
	else
	{
		searchFormTracking($tab["show"],"");
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$tab["show"],"","",$tab["start"]);
		
	}
}
else
{
	if(isset($tab["contains"]))
	{
		searchFormTracking("",$tab["contains"]);
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],"",$tab["contains"],"",$tab["start"]);
		
	}
	else
	{
		searchFormTracking("","");
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],"","","",$tab["start"]);
		
	}
}
commonFooter();
?>
