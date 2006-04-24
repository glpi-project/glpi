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
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

 


class Reminder extends CommonDBTM {

	function Reminder () {
		$this->table="glpi_reminder";
		$this->type=REMINDER_TYPE;
	}

	function prepareInputForAdd($input) {
		global $lang;

		if(empty($input["title"])) $input["title"]=$lang["reminder"][15];
	
		$input["begin"] = $input["end"] = "0000-00-00 00:00:00";

		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":".$input['_plan']["begin_min"].":00";
  			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":".$input['_plan']["end_min"].":00";
		}	

		
		// set new date.
   		$input["date"] = date("Y-m-d H:i:s");

		return $input;
	}

	function prepareInputForUpdate($input) {
		global $lang;

		if(empty($input["title"])) $input["title"]=$lang["reminder"][15];
	

		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":".$input['_plan']["begin_min"].":00";
  			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":".$input['_plan']["end_min"].":00";
		}	

		
		// set new date.
   		$input["date_mod"] = date("Y-m-d H:i:s");

		return $input;
	}

	
}

?>
