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
 
// ----------------------------------------------------------------------
// Original Author of file: Jean-François MOREAU
// Purpose of file: Display the knowledge base for anonymous users
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_knowbase.php");
include ($phproot . "/glpi/includes_documents.php");


checkAuthentication("anonymous");

//*********************
// Affichage Module FAQ
//*********************

nullHeader("Login",$_SERVER["PHP_SELF"]);

if (isset($_GET["ID"])){

ShowKbItemFull($_GET["ID"]);
showDocumentAssociated(KNOWBASE_TYPE,$_GET["ID"],3);

} else {
initExpandSessionVar();

if (isset($_GET["toshow"])) {
	if ($_GET["toshow"]=="all")
		ExpandSessionVarShowAll();
	else ExpandSessionVarShow($_GET["toshow"]);
}
if (isset($_GET["tohide"])) {
	if ($_GET["tohide"]=="all")
		ExpandSessionVarHideAll();
	else ExpandSessionVarHide($_GET["tohide"]);
}
if (isset($_POST["contains"])) $contains=$_POST["contains"];
else $contains="";

if (isset($_POST["contains"])) searchLimitSessionVarKnowbase($_POST["contains"]);


faqShowCategoriesall($_SERVER["PHP_SELF"]."?show=faq",$contains);
}
//**************************
//  fin Affichage Module FAQ
//**************************

helpFooter();

?>
