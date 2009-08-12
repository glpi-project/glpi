<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// CLASSES peripherals


class Phone extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_phones";
		$this->type=PHONE_TYPE;
		$this->dohistory=true;
		$this->entity_assign=true;
	}

	function defineTabs($ID,$withtemplate){
		global $LANG,$CFG_GLPI;
		$ong=array();

		if ($ID > 0){
			$ong[1]=$LANG['title'][27];
			if (haveRight("contract","r") || haveRight("infocom","r")){
				$ong[4]=$LANG['Menu'][26];
			}
			if (haveRight("document","r")){
				$ong[5]=$LANG['Menu'][27];
			}
	
			if(empty($withtemplate)){
				if (haveRight("show_all_ticket","1")){
					$ong[6]=$LANG['title'][28];
				}
				if (haveRight("link","r")){
					$ong[7]=$LANG['title'][34];
				}
				if (haveRight("notes","r")){
					$ong[10]=$LANG['title'][37];
				}
				if (haveRight("reservation_central","r")){
					$ong[11]=$LANG['Menu'][17];
				}
					
				$ong[12]=$LANG['title'][38];
			}	
		} else { // New item
			$ong[1]=$LANG['title'][26];
		}

		return $ong;
	}

	function prepareInputForAdd($input) {

		if (isset($input["id"])&&$input["id"]>0){
			$input["_oldID"]=$input["id"];
		}
		unset($input['id']);
		unset($input['withtemplate']);

		return $input;
	}

	function post_addItem($newID,$input) {
		global $DB;

		// Manage add from template
		if (isset($input["_oldID"])){
			// ADD Infocoms
			$ic= new Infocom();
			if ($ic->getFromDBforDevice(PHONE_TYPE,$input["_oldID"])){
				$ic->fields["items_id"]=$newID;
				unset ($ic->fields["id"]);
				if (isset($ic->fields["immo_number"])) {
					$ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1, INFOCOM_TYPE,$input['entities_id']);
				}
				if (empty($ic->fields['use_date'])){
					unset($ic->fields['use_date']);
				}
				if (empty($ic->fields['buy_date'])){
					unset($ic->fields['buy_date']);
				}
	
				$ic->addToDB();
			}
	
			// ADD Ports
			$query="SELECT id 
				FROM glpi_networkports 
				WHERE items_id='".$input["_oldID"]."' AND itemtype='".PHONE_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result)){
					$np= new Netport();
					$np->getFromDB($data["id"]);
					unset($np->fields["id"]);
					unset($np->fields["ip"]);
					unset($np->fields["mac"]);
					unset($np->fields["netpoints_id"]);
					$np->fields["items_id"]=$newID;
					$np->addToDB();
				}
			}
	
			// ADD Contract				
			$query="SELECT contracts_id 
				FROM glpi_contracts_items 
				WHERE items_id='".$input["_oldID"]."' AND itemtype='".PHONE_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceContract($data["contracts_id"],PHONE_TYPE,$newID);
			}
	
			// ADD Documents			
			$query="SELECT documents_id 
				FROM glpi_documents_items 
				WHERE items_id='".$input["_oldID"]."' AND itemtype='".PHONE_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceDocument($data["documents_id"],PHONE_TYPE,$newID);
			}
		}

	}



	function cleanDBonPurge($ID) {

		global $DB,$CFG_GLPI;


		$job =new Job();
		$query = "SELECT * 
			FROM glpi_tickets 
			WHERE (items_id = '$ID'  AND itemtype='".PHONE_TYPE."')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tickets_on_delete"]==1){
					$query = "UPDATE glpi_tickets SET items_id = '0', itemtype='0' WHERE id='".$data["id"]."';";
					$DB->query($query);
				} else $job->delete(array("id"=>$data["id"]));
			}

		$query="SELECT * 
			FROM glpi_reservationsitems 
			WHERE (itemtype='".PHONE_TYPE."' AND items_id='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0){
				$rr=new ReservationItem();
				$rr->delete(array("id"=>$DB->result($result,0,"id")));
			}
		}

		$query = "DELETE FROM glpi_infocoms
                  WHERE (items_id = '$ID' AND itemtype='".PHONE_TYPE."')";
		$result = $DB->query($query);


		$query="SELECT * FROM glpi_computers_items
               WHERE (itemtype='".PHONE_TYPE."' AND items_id='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				while ($data = $DB->fetch_array($result)){
					// Disconnect without auto actions
					Disconnect($data["id"],1,false);
				}
			}
		}

		$query = "DELETE FROM glpi_contracts_items
               WHERE (items_id = '$ID' AND itemtype='".PHONE_TYPE."')";
		$result = $DB->query($query);
	}

	/**
	 * Print the phone form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $withtemplate integer template or basic item
	 *
	 *@return boolean item found
	 **/
   function showForm ($target,$ID,$withtemplate='') {
      
      global $CFG_GLPI, $LANG;
      
      if (!haveRight("phone","r")) return false;
      
      if ($ID > 0){
         $this->check($ID,'r');
      } else {
         // Create item 
         $this->check(-1,'w');
         $this->getEmpty();
      } 
      
      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate,2);
      
      if(!empty($withtemplate) && $withtemplate == 2) {
         $template = "newcomp";
         $datestring = $LANG['computers'][14].": ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } elseif(!empty($withtemplate) && $withtemplate == 1) { 
         $template = "newtemplate";
         $datestring = $LANG['computers'][14].": ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26].": ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].($template?"*":"").":	</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", 
         ($template === "newcomp"), PHONE_TYPE,$this->fields["entities_id"]);
      autocompletionTextField("name","glpi_phones","name",
         $objectName,40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['peripherals'][33].":</td><td>";
      globalManagementDropdown($target,$withtemplate,$this->fields["id"],
         $this->fields["is_global"],$CFG_GLPI["phones_management_restrict"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][15].": 	</td><td>";
      dropdownValue("glpi_locations", "locations_id", 
         $this->fields["locations_id"],1,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['phones'][36].":</td><td>";
      dropdownValue("glpi_phonespowersupplies", "phonespowersupplies_id", 
         $this->fields["phonespowersupplies_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][10].": 	</td><td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],
         "interface",1,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][19].": </td><td>";
      autocompletionTextField("serial","glpi_phones","serial",
         $this->fields["serial"],40,$this->fields["entities_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][21].":	</td><td>";
      autocompletionTextField("contact_num","glpi_phones","contact_num",
         $this->fields["contact_num"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][20].($template?"*":"").":</td><td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial", 
         ($template === "newcomp"), PHONE_TYPE,$this->fields["entities_id"]);
      autocompletionTextField("otherserial","glpi_phones","otherserial",
         $objectName,40,$this->fields["entities_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][18].":	</td><td>";
      autocompletionTextField("contact","glpi_phones","contact",
         $this->fields["contact"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['setup'][71].":  </td><td>";
      autocompletionTextField("firmware","glpi_phones","firmware",
         $this->fields["firmware"],40,$this->fields["entities_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][34].": 	</td><td>";
      dropdownAllUsers("users_id", $this->fields["users_id"],1,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['state'][0].":</td><td>";
      dropdownValue("glpi_states", "states_id",$this->fields["states_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][35].": 	</td><td>";
      dropdownValue("glpi_groups", "groups_id", 
         $this->fields["groups_id"],1,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['phones'][40].": </td><td>";
      autocompletionTextField("number_line","glpi_phones","number_line",
         $this->fields["number_line"],40,$this->fields["entities_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][17].": 	</td><td>";
      dropdownValue("glpi_phonestypes", "phonestypes_id", $this->fields["phonestypes_id"]);
      echo "</td>";
      echo "<td rowspan='2'>".$LANG['monitors'][18].": </td><td rowspan='2'>";
      // micro?
      echo "<table>";
      echo "<tr><td>".$LANG['phones'][38]."</td>";
      echo "<td>";
      dropdownYesNo("have_headset",$this->fields["have_headset"]);
      echo "</td></tr>";
      // hp?
      echo "<tr><td>".$LANG['phones'][39]."</td>";
      echo "<td>";
      dropdownYesNo("have_hp",$this->fields["have_hp"]);
      echo "</td></tr></table></td>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][22].": 	</td><td>";
      dropdownValue("glpi_phonesmodels", "phonesmodels_id", $this->fields["phonesmodels_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['common'][5].":   </td><td>";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td>";
      echo "<td rowspan='3' class='middle'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='45' rows='4' name='comment' >";
      echo $this->fields["comment"]."</textarea></td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$LANG['phones'][18].":</td><td>";
      autocompletionTextField("brand","glpi_phones","brand",
         $this->fields["brand"],40,$this->fields["entities_id"]);
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_1'><td>".$datestring."</td><td>".$date;
      if (!$template&&!empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13].": ".$this->fields['template_name'].")";
      }
      echo "</td></tr>";
      
      $this->showFormButtons($ID,$withtemplate,2);
      
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
      
      return true;	
   }

   /*
    * Return the SQL command to retrieve linked object
    * 
    * @return a SQL command which return a set of (itemtype, items_id)
    */
   function getSelectLinkedItem () {
      return "SELECT '".COMPUTER_TYPE."', `computers_id` 
         FROM glpi_computers_items 
         WHERE `itemtype`='".$this->type."' AND `items_id`='" . $this->fields['id']."'";
   }
}

?>
