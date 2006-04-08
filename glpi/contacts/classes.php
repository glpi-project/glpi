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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

// CLASSES contact
class Contact extends CommonDBTM{

	function Contact () {
		$this->table="glpi_contacts";
		$this->type=CONTACT_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $db;
			
		$query = "DELETE from glpi_contact_enterprise WHERE FK_contact = '$ID'";
		$db->query($query);
	}

	function defineOnglets($withtemplate){
		global $lang;
		return array(	1 => $lang["title"][26],
				7 => $lang["title"][34],
				10 => $lang["title"][37],
		);
	}
}

?>