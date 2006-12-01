<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
// Original Author of file: Jean-Fran�is MOREAU
// Purpose of file: Display the knowledge base for anonymous users
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("user","knowbase","document");
include ($phproot . "/inc/includes.php");


checkAccessToPublicFaq();

//*********************
// Affichage Module FAQ
//*********************

nullHeader("Login",$_SERVER['PHP_SELF']);

if (isset($_GET["ID"])){

	if (ShowKbItemFull($_GET["ID"]))
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

	if (!empty($contains)) searchLimitSessionVarKnowbase($contains);


	faqShowCategoriesall($_SERVER['PHP_SELF'],$contains);
}
//**************************
//  fin Affichage Module FAQ
//**************************

helpFooter();

?>
