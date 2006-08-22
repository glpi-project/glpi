<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

//update the database to the 0.31 version
function updateDbTo031()
{

global $db,$lang;


//amSize ramSize
 $query = "Alter table users drop can_assign_job";
 $db->query($query) or die($lang["update"][90].$db->error());
 $query = "Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no'";
 $db->query($query) or die($lang["update"][90].$db->error());
 $query = "Update users set can_assign_job = 'yes' where type = 'admin'";
 $db->query($query) or die($lang["update"][90].$db->error());
 
 echo "<p class='center'>Version 0.2 & < </p>";

//Version 0.21 ajout du champ ramSize a la table printers si non existant.


if(!FieldExists("printers", "ramSize")) {
	$query = "alter table printers add ramSize varchar(6) NOT NULL default ''";
	$db->query($query) or die($lang["update"][90].$db->error());
}

 echo "<p class='center'>Version 0.21  </p>";

//Version 0.3
//Ajout de NOT NULL et des valeurs par defaut.

$query = "ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

 echo "<p class='center'>Version 0.3  </p>";

 
}


?>