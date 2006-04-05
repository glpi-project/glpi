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
// CLASSES PlanningTracking

class PlanningTracking extends CommonDBTM {

	function PlanningTracking () {
		$this->table="glpi_tracking_planning";
	}
	
	// SPECIFIC FUNCTIONS
	
	function is_alreadyplanned(){
		global $db;
		if (!isset($this->fields["id_assign"])||empty($this->fields["id_assign"]))
		return true;
		
		// When modify a planning do not itself take into account 
		$ID_where="";
		if(isset($this->fields["ID"]))
		$ID_where=" (ID <> '".$this->fields["ID"]."') AND ";
		
		$query = "SELECT * FROM glpi_tracking_planning".
		" WHERE $ID_where (id_assign = '".$this->fields["id_assign"]."') AND ".
		" ( ('".$this->fields["begin"]."' < begin AND '".$this->fields["end"]."' > begin) ".
		" OR ('".$this->fields["begin"]."' < end AND '".$this->fields["end"]."' >= end) ".
		" OR ('".$this->fields["begin"]."' >= begin AND '".$this->fields["end"]."' < end))";
//		echo $query."<br>";
		if ($result=$db->query($query)){
			return ($db->numrows($result)>0);
		}
		return true;
		}
	function test_valid_date(){
		return (strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
		}

	function displayError($type,$ID,$target){
		global $HTMLRel,$lang;
		
		//echo "<br><div align='center'>";
		switch ($type){
			case "date":
			 $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["planning"][1]."<br>";
			break;
			case "is_res":
			 $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["planning"][0]."<br>";
			break;
			default :
				$_SESSION["MESSAGE_AFTER_REDIRECT"].="Erreur Inconnue<br>";
			break;
		}
		//echo "<br><a href='".$target."?job=$ID'>".$lang["buttons"][13]."</a>";
		//echo "</div>";
		}

}


?>