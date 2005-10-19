<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_state.php");

checkAuthentication("normal");

commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);

titleNetdevices();


include ($phproot . "/glpi/includes_search.php");
searchForm(NETWORKING_TYPE,$cfg_install["root"]."/networking/networking-search.php");


//listNetdevices();


if(!isset($_GET["start"])) $_GET["start"] = 0;
if (!isset($_GET["order"])) $_GET["order"] = "ASC";
if (!isset($_GET["field"])) $_GET["field"] = array(0 => "view");
if (!isset($_GET["phrasetype"])) $_GET["phrasetype"] = "contains";
if (!isset($_GET["contains"])) $_GET["contains"] = array(0=>"");
if (!isset($_GET["sort"])) $_GET["sort"] = 1;
if (!isset($_GET["deleted"])) $_GET["deleted"] = "N";
if (!isset($_GET["link"])) $_GET["link"] = "";


//searchFormNetworking();

//showNetworkingList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$_GET["field"],$_GET["phrasetype"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["deleted"],$_GET["link"]);

showList(NETWORKING_TYPE,$_SERVER["PHP_SELF"],$_GET["field"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["deleted"],$_GET["link"]);

commonFooter();
?>
