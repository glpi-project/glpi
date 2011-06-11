<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

///update the database to the 0.31 version
function updateDbTo031(){
	global $DB,$LANG;

	//amSize ramSize
	$query = "Alter table users drop can_assign_job";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "Update users set can_assign_job = 'yes' where type = 'admin'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());

	echo "<p class='center'>Version 0.2 & < </p>";

	//Version 0.21 ajout du champ ramSize a la table printers si non existant.


	if(!FieldExists("printers", "ramSize")) {
		$query = "alter table printers add ramSize varchar(6) NOT NULL default ''";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
	}

	echo "<p class='center'>Version 0.21  </p>";

	//Version 0.3
	//Ajout de NOT NULL et des valeurs par defaut.

	$query = "ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


	$query = "ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

	$query = "ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


	$query = "ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

	$query = "ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00'";
	$DB->query($query) or die($LANG['update'][90].$DB->error());
	$query = "ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

	echo "<p class='center'>Version 0.3  </p>";
}
?>