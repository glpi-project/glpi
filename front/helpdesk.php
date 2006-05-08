<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
 ------------------------------------------------------------------------
*/
 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("user","tracking","document","computer","printer","networking","peripheral","monitor","software","infocom","phone");
include ($phproot . "/inc/includes.php");

checkRight("create_ticket","1");

commonHeader("Helpdesk",$_SERVER["PHP_SELF"]);

if (!isset($_POST["user"])) $user=$_SESSION["glpiID"];
else $user=$_POST["user"];
if (!isset($_POST["assign"])) $assign=0;
else $assign=$_POST["assign"];

if(empty($_POST["status"])) $_POST["status"] = "new";
$error = "";

$track=new Job();

if (isset($_POST["priority"]) && empty($_POST["contents"]))
{
	$error=$lang["tracking"][8] ;
	addFormTracking(0,0,$user,$assign,$_SERVER["PHP_SELF"],$error);
}
elseif (isset($_POST["priority"]) && !empty($_POST["contents"]))
{

	if ($track->add($_POST))
	{
		$error=$lang["tracking"][9];
		displayMessageAfterRedirect();
		addFormTracking(0,0,$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
	else
	{
		$error=$lang["tracking"][10];
		displayMessageAfterRedirect();
		addFormTracking(0,0,$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
} 
else
{
	addFormTracking(0,0,$user,$assign,$_SERVER["PHP_SELF"],$error);
}

commonFooter();


?>
