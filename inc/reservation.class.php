<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


/// Reservation item class
class ReservationItem extends CommonDBTM {
	/**
	 * Constructor
	**/
	function ReservationItem () {
		$this->table="glpi_reservation_item";
		$this->type=-1;
	}

	/**
	 * Retrieve an item from the database for a specific item
	 *
	 *@param $ID ID of the item
	 *@param $type type of the item
	 *@return true if succeed else false
	**/	
	function getFromDBbyItem($type,$ID){
		global $DB;

		$query = "SELECT * FROM glpi_reservation_item WHERE (device_type = '$type' AND id_device = '$ID')";
		$result = $DB->query($query);
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)==1){
				$this->fields = $DB->fetch_assoc($result);
				return true;
			}
		}
		return false;

	}
	
	function cleanDBonPurge($ID) {

		global $DB;

		$query2 = "DELETE FROM glpi_reservation_resa WHERE (id_item = '$ID')";
		$result2 = $DB->query($query2);
	}
	function prepareInputForAdd($input) {
		if (!$this->getFromDBbyItem($input['device_type'],$input['id_device'])){ 
			if (!isset($input['active'])){
				$input['active']=1;
			}
			return $input;
		}
		return false; 
	}
}

/// Reservation class
class ReservationResa extends CommonDBTM {

	/**
	 * Constructor
	**/
	function ReservationResa () {
		$this->table="glpi_reservation_resa";
		$this->type=-1;
	}

	function pre_deleteItem($ID) {
		global $CFG_GLPI;
		if ($this->getFromDB($ID))
			if (isset($this->fields["id_user"])&&($this->fields["id_user"]==$_SESSION["glpiID"]||haveRight("reservation_central","w"))){
				// Processing Email
				if ($CFG_GLPI["mailing"]){
					$mail = new MailingResa($this,"delete");
					$mail->send();
				}

		}
		return true;
	}


	function update($input,$history=1){
		global $LANG,$CFG_GLPI;
		// Update a printer in the database

		$target="";
		if (isset($input['_target'])){
			$target=$input['_target'];
		}
		$item=0;
		if (isset($input['_item'])){
			$item=$_POST['_item'];
		}

		$this->getFromDB($input["ID"]);

		list($begin_year,$begin_month,$begin_day)=split("-",$input["begin_date"]);
		list($end_year,$end_month,$end_day)=split("-",$input["end_date"]);

		list($begin_hour,$begin_min)=split(":",$input["begin_hour"]);
		list($end_hour,$end_min)=split(":",$input["end_hour"]);
		$input["begin"]=date("Y-m-d H:i:00",mktime($begin_hour,$begin_min,0,$begin_month,$begin_day,$begin_year));
		$input["end"]=date("Y-m-d H:i:00",mktime($end_hour,$end_min,0,$end_month,$end_day,$end_year));


		// Fill the update-array with changes
		$x=0;
		foreach ($input as $key => $val) {
			if (array_key_exists($key,$this->fields) && $this->fields[$key] != $input[$key]) {
				$this->fields[$key] = $input[$key];
				$updates[$x] = $key;
				$x++;
			}
		}

		if (!$this->test_valid_date()){
			$this->displayError("date",$item,$target);
			return false;
		}

		if ($this->is_reserved()){
			$this->displayError("is_res",$item,$target);
			return false;
		}


		if (isset($updates)){
			$this->updateInDB($updates);
			// Processing Email
			if ($CFG_GLPI["mailing"]){
				$mail = new MailingResa($this,"update");
				$mail->send();
			}
		}
		return true;
	}

	function add($input){
		global $CFG_GLPI;
	       	
		// Add a Reservation
		if (!isset($input['_ok'])||$input['_ok']){
			$target="";
			if (isset($input['_target'])){
				$target=$input['_target'];
			}
			// set new date.
			$this->fields["id_item"] = $input["id_item"];
			$this->fields["comment"] = $input["comment"];
			$this->fields["id_user"] = $input["id_user"];
			$this->fields["begin"] = $input["begin_date"]." ".$input["begin_hour"].":00";
			$this->fields["end"] = $input["end_date"]." ".$input["end_hour"].":00";

			if (!$this->test_valid_date()){
				$this->displayError("date",$input["id_item"],$target);
				return false;
			}

			if ($this->is_reserved()){
				$this->displayError("is_res",$input["id_item"],$target);
				return false;
			}

			if ($input["id_user"]>0)
				if ($this->addToDB()){
					// Processing Email
					if ($CFG_GLPI["mailing"]){
						$mail = new MailingResa($this,"new");
						$mail->send();
					}
					return true;
				} else {
					return false;
				}
		}
	}


	// SPECIFIC FUNCTIONS
	/**
	 * Is the item already reserved ?
	 *
	 *@return boolean
	 **/
	function is_reserved(){
		global $DB;
		if (!isset($this->fields["id_item"])||empty($this->fields["id_item"]))
			return true;

		// When modify a reservation do not itself take into account 
		$ID_where="";
		if(isset($this->fields["ID"]))
			$ID_where=" (ID <> '".$this->fields["ID"]."') AND ";

		$query = "SELECT * FROM glpi_reservation_resa".
			" WHERE $ID_where (id_item = '".$this->fields["id_item"]."') AND ( ('".$this->fields["begin"]."' < begin AND '".$this->fields["end"]."' > begin) OR ('".$this->fields["begin"]."' < end AND '".$this->fields["end"]."' >= end) OR ('".$this->fields["begin"]."' >= begin AND '".$this->fields["end"]."' < end))";
		//		echo $query."<br>";
		if ($result=$DB->query($query)){
			return ($DB->numrows($result)>0);
		}
		return true;
	}
	/**
	 * Current dates are valid ? begin before end
	 *
	 *@return boolean
	 **/
	function test_valid_date(){
		return (strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
	}

	/**
	 * display error message 
	 * @param $type error type : date / is_res / other
	 * @param $ID ID of the item
	 * @param $target where to go on error
	 *@return nothing
	 **/
	function displayError($type,$ID,$target){
		global $LANG;

		echo "<br><div class='center'>";
		switch ($type){
			case "date":
				echo $LANG["reservation"][19];
			break;
			case "is_res":
				echo $LANG["reservation"][18];
			break;
			default :
			echo "Unknown error";
			break;
		}
		echo "<br><a href='".$target."?show=resa&amp;ID=$ID'>".$LANG["reservation"][20]."</a>";
		echo "</div>";
	}
	/**
	 * Get text describing reservation
	 * 
	* @param $format text or html
	 */
	function textDescription($format="text"){
		global $LANG;

		$ri=new ReservationItem();
		$ci=new CommonItem();
		$name="";
		$tech="";
		if ($ri->getFromDB($this->fields["id_item"])){
			if ($ci->getFromDB($ri->fields['device_type'],$ri->fields['id_device'])	){
				$name=$ci->getType()." ".$ci->getName();
				if ($ci->getField('tech_num')){
					$tech=getUserName($ci->getField('tech_num'));
				}
			}
		}
		
		$u=new User();
		$u->getFromDB($this->fields["id_user"]);
		$content="";

		if($format=="html"){
			$content= "<html><head> <style type=\"text/css\">";
			$content.=".description{ color: inherit; background: #ebebeb; border-style: solid; border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }";
			$content.=" </style></head><body>";
			$content.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][37].":</span> ".$u->getName()."<br>";
			$content.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][7]."</span> ".$name."<br>";
			if (!empty($tech)){
				$content.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["common"][10].":</span> ".$tech."<br>";
			}
			$content.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["search"][8].":</span> ".convDateTime($this->fields["begin"])."<br>";
			$content.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["search"][9].":</span> ".convDateTime($this->fields["end"])."<br>";
			$content.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][25].":</span> ".nl2br($this->fields["comment"])."<br>";
		} else { // text format
			$content.=$LANG["mailing"][1]."\n";
			$content.=$LANG["common"][37].": ".$u->getName()."\n";
			$content.=$LANG["mailing"][7]." ".$name."\n";
			if (!empty($tech)){
				$content.= $LANG["common"][10].": ".$tech."\n";
			}

			$content.=$LANG["search"][8].": ".convDateTime($this->fields["begin"])."\n";
			$content.=$LANG["search"][9].": ".convDateTime($this->fields["end"])."\n";
			$content.=$LANG["common"][25].": ".$this->fields["comment"]."\n";
			$content.=$LANG["mailing"][1]."\n";
		}
		return $content;

	}

}


?>
