<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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

 
// CLASSE knowledgebase

class kbitem extends CommonDBTM {

	function kbitem () {
		$this->table="glpi_kbitems";
		$this->type=KNOWBASE_TYPE;
	}

	function prepareInputForAdd($input) {
			
		global $lang;
		// set new date.
		$input["date"] = date("Y-m-d H:i:s");
		// set author
		
		// set title for question if empty
		if(empty($input["question"])) $input["question"]=$lang["common"][30];
		
		if (haveRight("faq","w")&&!haveRight("knowbase","w")) $input["faq"]="yes";
		if (!haveRight("faq","w")&&haveRight("knowbase","w")) $input["faq"]="no";

		return $input;
		}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
		// set title for question if empty
		if(empty($input["question"])) $input["question"]=$lang["common"][30];

		return $input;
	}

	




}

?>