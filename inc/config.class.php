<?php
/*
 * @version $Id: setup.class.php 3313 2006-04-24 03:20:51Z silvermat $
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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// And Marco Gaiarin for ldap features 

 
// CLASSES Setup

class Config extends CommonDBTM {

	function Config () {
		$this->table="glpi_config";
		$this->type=-1;
	}

	function prepareInputForUpdate($input) {
		if (isset($input['mail_server'])&&!empty($input['mail_server']))
			$input["imap_auth_server"]=constructIMAPAuthServer($input);
		if (isset($input["planning_begin"]))
			$input["planning_begin"]=$input["planning_begin"].":00:00";
		if (isset($input["planning_end"]))
			$input["planning_end"]=$input["planning_end"].":00:00";
		return $input;
	}

}
?>
