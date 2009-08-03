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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/// Software class
class Software extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct() {
		$this->table = "glpi_softwares";
		$this->type = SOFTWARE_TYPE;
		$this->dohistory = true;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}

	function defineTabs($ID,$withtemplate) {
		global $LANG, $CFG_GLPI;
		$ong=array();
		if ($ID > 0 ){
			$ong[1] = $LANG['software'][5]."/".$LANG['software'][11];
			if (empty ($withtemplate)) {
				$ong[2] = $LANG['software'][19];
			}
			if (haveRight("contract","r") || haveRight("infocom","r")){
				$ong[4] = $LANG['Menu'][26];
			}
			if (haveRight("document","r")){
				$ong[5] = $LANG['Menu'][27];
			}
	
			if (empty ($withtemplate)) {
				if (haveRight("show_all_ticket","1")){
					$ong[6] = $LANG['title'][28];
				}
				if (haveRight("link","r")){
					$ong[7] = $LANG['title'][34];
				}
				if (haveRight("notes","r")){
					$ong[10] = $LANG['title'][37];
				}
				if (haveRight("reservation_central", "r")){
					$ong[11] = $LANG['Menu'][17];
				}
			
				$ong[12] = $LANG['title'][38];
				
				if ($this->isRecursive()) {
					$ong[21] = $LANG['software'][47];
				}
			}
		} else { // New item
			$ong[1]=$LANG['title'][26];
		}
		return $ong;
	}

	function prepareInputForUpdate($input) {

		if (isset ($input['is_update']) && ! $input['is_update'])
			$input['softwares_id'] = 0;

		return $input;
	}

	function prepareInputForAdd($input) {

		if (isset ($input['is_update']) && !$input['is_update'])
			$input['softwares_id'] = 0;

		if (isset($input["ID"])&&$input["ID"]>0){
			$input["_oldID"]=$input["ID"];
		}
		unset($input['ID']);
		unset($input['withtemplate']);

		//If category was not set by user (when manually adding a user)
		if (!isset($input["softwarescategories_id"])||!$input["softwarescategories_id"])
		{
			$softcatrule = new SoftwareCategoriesRuleCollection;
			$result = $softcatrule->processAllRules(null,null,$input);
			if (!empty($result) && isset($result["softwarescategories_id"])){
				$input["softwarescategories_id"]=$result["softwarescategories_id"];
			} else {
				$input["softwarescategories_id"]=0;
			}
		}
				
		return $input;
	}
	function post_addItem($newID, $input) {
		global $DB;


		// Manage add from template
		if (isset($input["_oldID"])){
			// ADD Infocoms
			$ic = new Infocom();
			if ($ic->getFromDBforDevice(SOFTWARE_TYPE, $input["_oldID"])) {
				$ic->fields["items_id"] = $newID;
				unset ($ic->fields["ID"]);
				if (isset($ic->fields["num_immo"])) {
					$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE,$input['entities_id']);
				}
				if (empty($ic->fields['use_date'])){
					unset($ic->fields['use_date']);
				}
				if (empty($ic->fields['buy_date'])){
					unset($ic->fields['buy_date']);
				}

				$ic->addToDB();
			}
	
			// ADD Contract				
			$query = "SELECT contracts_id FROM glpi_contracts_items WHERE items_id='" . $input["_oldID"] . "' AND itemtype='" . SOFTWARE_TYPE . "';";
			$result = $DB->query($query);
			if ($DB->numrows($result) > 0) {
	
				while ($data = $DB->fetch_array($result))
					addDeviceContract($data["contracts_id"], SOFTWARE_TYPE, $newID);
			}
	
			// ADD Documents			
			$query = "SELECT documents_id FROM glpi_documents_items WHERE items_id='" . $input["_oldID"] . "' AND itemtype='" . SOFTWARE_TYPE . "';";
			$result = $DB->query($query);
			if ($DB->numrows($result) > 0) {
	
				while ($data = $DB->fetch_array($result))
					addDeviceDocument($data["documents_id"], SOFTWARE_TYPE, $newID);
			}
		}

	}

	function cleanDBonPurge($ID) {

		global $DB, $CFG_GLPI;

		$job = new Job();
		$query = "SELECT * FROM glpi_tickets WHERE (items_id = '$ID'  AND itemtype='" . SOFTWARE_TYPE . "')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data = $DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tickets_on_delete"] == 1) {
					$query = "UPDATE glpi_tickets SET items_id = '0', itemtype='0' WHERE ID='" . $data["ID"] . "';";
					$DB->query($query);
				} else
					$job->delete(array (
						"ID" => $data["ID"]
					));
			}

		$query = "DELETE FROM glpi_infocoms WHERE (items_id = '$ID' AND itemtype='" . SOFTWARE_TYPE . "')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_contracts_items WHERE (items_id = '$ID' AND itemtype='" . SOFTWARE_TYPE . "')";
		$result = $DB->query($query);

		$query = "SELECT * FROM glpi_reservationsitems WHERE (itemtype='" . SOFTWARE_TYPE . "' AND items_id='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) > 0) {
				$rr = new ReservationItem();
				$rr->delete(array (
					"ID" => $DB->result($result,0,"ID")
				));
			}
		}

		// Delete all licenses
		$query2 = "SELECT ID FROM glpi_softwareslicenses WHERE (softwares_id = '$ID')";

		if ($result2 = $DB->query($query2)) {
			if ($DB->numrows($result2)) {
				$lic = new SoftwareLicense;

				while ($data = $DB->fetch_array($result2)) {
					$lic->delete(array (
						"ID" => $data["ID"]
					));
				}
			}
		}

		// Delete all versions
		$query2 = "SELECT ID FROM glpi_softwaresversions WHERE (softwares_id = '$ID')";

		if ($result2 = $DB->query($query2)) {
			if ($DB->numrows($result2)) {
				$vers = new SoftwareVersion;

				while ($data = $DB->fetch_array($result2)) {
					$vers->delete(array (
						"ID" => $data["ID"]
					));
				}
			}
		}

	}

	/**
	 * Print the Software form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $withtemplate integer template or basic item
	 *
	 *@return boolean item found
	 **/
	function showForm($target, $ID, $withtemplate = '') {
		// Show Software or blank form

		global $CFG_GLPI, $LANG;

		if (!haveRight("software", "r"))
			return false;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$this->getEmpty();
		} 
		$canedit=$this->can($ID,'w');

		if (!empty ($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $LANG['computers'][14] . ": ";
			$date = convDateTime($_SESSION["glpi_currenttime"]);
		}
		elseif (!empty ($withtemplate) && $withtemplate == 1) {
			$template = "newtemplate";
			$datestring = $LANG['computers'][14] . ": ";
			$date = convDateTime($_SESSION["glpi_currenttime"]);
		} else {
			$datestring = $LANG['common'][26] . " : ";
			$date = convDateTime($this->fields["date_mod"]);
			$template = false;
		}
	
      $this->showTabs($ID, $withtemplate, $_SESSION['glpi_tab']);
      $this->showFormHeader($target, $ID, $withtemplate, 2);

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . ":		</td>";
      echo "<td>";
      autocompletionTextField("name", "glpi_softwares", "name", $this->fields["name"], 40,$this->fields["entities_id"]);
      echo "</td>";
      
      
      echo "<td>" . $LANG['common'][36] . ":		</td><td>";
      dropdownValue("glpi_softwarescategories", "softwarescategories_id", $this->fields["softwarescategories_id"]);
      echo "</td>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['software'][3] . ": 	</td><td>";
      dropdownValue("glpi_operatingsystems", "operatingsystems_id", $this->fields["operatingsystems_id"]);
      echo "</td>";

      echo "<td>" . $LANG['common'][5] . ": 	</td><td>";
      dropdownValue("glpi_manufacturers", "manufacturers_id", $this->fields["manufacturers_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >" . $LANG['common'][34] . ": 	</td>";
      echo "<td >";
      dropdownAllUsers("users_id", $this->fields["users_id"], 1, $this->fields["entities_id"]);
      echo "</td>";

      echo "<td>" . $LANG['common'][35] . ":</td><td>";
      dropdownValue("glpi_groups", "groups_id", $this->fields["groups_id"], 1, $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][10] . ": 	</td><td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"], "interface", 1, $this->fields["entities_id"]);
      echo "</td>";

      echo "<td>" . $LANG['common'][15] . ": 	</td><td>";
      dropdownValue("glpi_locations", "locations_id", $this->fields["locations_id"], 1, $this->fields["entities_id"]);
      echo "</td></tr>";

      // UPDATE
      echo "<tr class='tab_bg_1'><td>" . $LANG['software'][29] . ":</td><td colspan='3'>";
      dropdownYesNo("is_update",$this->fields['is_update']);
      echo "&nbsp;" . $LANG['pager'][2] . "&nbsp;";
      dropdownValue("glpi_softwares", "softwares_id", $this->fields["softwares_id"]);
      echo "</td>";

      /*echo "<td>" . $LANG['state'][0] . ":</td><td>";
      dropdownValue("glpi_states", "states_id", $this->fields["states_id"]);
      echo "</td></tr>";*/

      echo "<tr class='tab_bg_1'><td>" . $LANG['software'][46] . ":</td><td>";
      dropdownYesNo('is_helpdesk_visible',$this->fields['is_helpdesk_visible']);
      echo "</td>";
      echo "<td>$datestring</td><td>$date";
      if (!$template && !empty ($this->fields['template_name']))
         echo "&nbsp;&nbsp;&nbsp;(" . $LANG['common'][13] . ": " . $this->fields['template_name'] . ")";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td valign='top'>";
      echo $LANG['common'][25] . ":	</td>";
      echo "<td align='center' colspan='3'><textarea cols='50' rows='4' name='comment' >" . $this->fields["comment"] . "</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";


		return true;
	}

	// SPECIFIC FUNCTIONS
	/**
	* Count Installations of a software 
	*
	* @return integer installation number
	*/
	function countInstallations() {
		global $DB;
		$query = "SELECT * FROM glpi_computers_softwaresversions WHERE (softwares_id = '".$this->fields["ID"]."')";
		if ($result = $DB->query($query)) {
			$number = $DB->numrows($result);
			return $number;
		} else {
			return false;
		}
	}
	
	function getEmpty()
	{
		global $CFG_GLPI;
		
		parent::getEmpty();
		$this->fields["is_helpdesk_visible"]= $CFG_GLPI["default_software_helpdesk_visible"];
	}
}

/// Version class
class SoftwareVersion extends CommonDBTM {
	/**
	 * Constructor
	**/
	function __construct() {
		$this->table = "glpi_softwaresversions";
		$this->type = SOFTWAREVERSION_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
		$this->dohistory = true;
	}
	function cleanDBonPurge($ID) {

		global $DB;

		// Delete Installations
		$query2 = "DELETE FROM glpi_computers_softwaresversions WHERE (softwaresversions_id = '$ID')";
		$DB->query($query2);
	}

	function prepareInputForAdd($input) {
		// Not attached to software -> not added
		if (!isset($input['softwares_id']) || $input['softwares_id'] <= 0){
			return false;
		}
		return $input;
	}

	function getEntityID () {
		$soft=new Software();
		$soft->getFromDB($this->fields["softwares_id"]);
		return $soft->getEntityID();
	}	

	function isRecursive () {
		$soft=new Software();
		$soft->getFromDB($this->fields["softwares_id"]);
		return $soft->isRecursive();
	}	

	function defineTabs($ID,$withtemplate) {
		global $LANG, $CFG_GLPI;

		$ong[1] = $LANG['title'][26];
		
		if ($ID) {
			$ong[2] = $LANG['software'][19];
			$ong[12] = $LANG['title'][38];
		}

		return $ong;
	}

	/**
	 * Print the Software / version form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the version or the template to print
	 *@param $softwares_id ID of the software for add process
	 *
	 *@return true if displayed  false if item not found or not right to display
	 **/	
	function showForm($target,$ID,$softwares_id=-1){
		global $CFG_GLPI,$LANG;

		if (!haveRight("software","r"))	return false;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$this->getEmpty();
		} 
		$canedit=$this->can($ID,'w');


		$this->showTabs($ID, false, $_SESSION['glpi_tab'],array(),"softwares_id=".$this->fields['softwares_id']);
		echo "<form name='form' method='post' action=\"$target\" enctype=\"multipart/form-data\">";

		echo "<div class='center' id='tabsbody'><table class='tab_cadre_fixe'>";
		if ($ID>0){
			echo "<tr><th colspan='2'>".$LANG['common'][2]." $ID";
			$softwares_id=$this->fields["softwares_id"];
		} else {
			echo "<tr><th colspan='2'>".$LANG['software'][7];
			echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
		}
		echo "</th></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['help'][31].":		</td>";
		echo "<td>";
		echo "<a href='software.form.php?ID=".$softwares_id."'>".getDropdownName("glpi_softwares",$softwares_id)."</a>";
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].":		</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_softwaresversions","name",$this->fields["name"],80);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>" . $LANG['state'][0] . ":</td><td>";
		dropdownValue("glpi_states", "states_id", $this->fields["states_id"]);
		echo "</td></tr>";

		echo "<tr  class='tab_bg_1'><td valign='top'>";

		// table commentaires
		echo $LANG['common'][25].":	</td>";
		echo "<td class='tab_bg_1'>";
		echo "<textarea cols='70' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";

		echo "</td>";
		echo "</tr>";

		if ($canedit) {			
			echo "<tr  class='tab_bg_2'>";

			if ($ID>0) {
	
				if (countLicensesForVersion($ID)>0    // Only count softwaresversions_id_buy (don't care of softwaresversions_id_use if no installation) 
					|| countInstallationsForVersion($ID)>0){
					echo "<td  colspan='2'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div class='center'><input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'></div>";
					echo "</td>\n\n";
				} else {
					echo "<td>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div class='center'><input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'></div>";
					echo "</td>\n\n";
					echo "<td>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'></div>";
					echo "</td>\n\n";
	
				}
		
	
			} else {
	
				echo "<td colspan='2'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></div>";
				echo "</td></tr>";
	
			}
		}
		echo "</table></div></form>";
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";
				
		return true;

	}

}
/// License class
class SoftwareLicense extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct() {
		$this->table = "glpi_softwareslicenses";
		$this->type = SOFTWARELICENSE_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
		$this->dohistory = true;
	}

/*	function prepareInputForUpdate($input) {
		if (isset($input['expire'])&&empty ($input['expire'])){
			$input['expire'] = "NULL";
		}

		if (isset ($input['oem']) && !$input['oem'])
			$input['oem_computer'] = -1;

		return $input;
	}
*/

	function pre_updateInDB($input,$updates,$oldvalues=array()) {
		// Clean end alert if expire is after old one
		if ((isset($oldvalues['expire'])
			&& ($oldvalues['expire'] < $this->fields['expire'] ))
		){
			$alert=new Alert();
			$alert->clear($this->type,$this->fields['ID'],ALERT_END);
		}
		return array($input,$updates);
	}

	function prepareInputForAdd($input) {
		// Unset to set to default using mysql default value
		if (empty ($input['expire']))
			unset ($input['expire']);

		/*if (isset($input['oem']) && !$input['oem']){
			$input['oem_computer'] = -1;
		}*/
		if (!isset($input['computers_id']) || $input['computers_id'] <= 0){
			$input['computers_id'] = -1;
		} else {
			// Number is 1 for affected license
			$input['number']=1;
		}

//		unset ($input["form"]);
//		unset ($input["withtemplate"]);
//		unset ($input["lID"]);
		return $input;
	}

	function prepareInputForUpdate($input) {

		if (isset($input['computers_id']) && $input['computers_id'] == 0){
			$input['computers_id'] = -1;
		}
		if ((isset($input['computers_id']) && $input['computers_id'] > 0) 
		 	|| (!isset($input['computers_id']) && isset($this->fields['computers_id']) && $this->fields['computers_id']>0)){
			// Number is 1 for affected license
			$input['number']=1;
		}
		
		return $input;
	}
	
	function post_addItem($newID, $input) {


		$itemtype = SOFTWARE_TYPE;
		$dupid = $this->fields["softwares_id"];
		if (isset ($input["_duplicate_license"])) {
			$itemtype = LICENSE_TYPE;
			$dupid = $input["_duplicate_license"];
		}
		// Add infocoms if exists for the licence
		$ic = new Infocom();
		if ($ic->getFromDBforDevice($itemtype, $dupid)) {
			unset ($ic->fields["ID"]);
			$ic->fields["items_id"] = $newID;
			if (isset($ic->fields["num_immo"])) {
				$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE,$input['entities_id']);
			}
			if (empty($ic->fields['use_date'])){
				unset($ic->fields['use_date']);
			}
			if (empty($ic->fields['buy_date'])){
				unset($ic->fields['buy_date']);
			}
			$ic->fields["itemtype"] = SOFTWARELICENSE_TYPE;
			$ic->addToDB();
		}
	}

	function cleanDBonPurge($ID) {

		global $DB;

		$query = "DELETE FROM glpi_infocoms WHERE (items_id = '$ID' AND itemtype='" . SOFTWARELICENSE_TYPE . "')";
		$result = $DB->query($query);

	}

/*
	function getEntityID () {
		$soft=new Software();
		$soft->getFromDB($this->fields["softwares_id"]);
		return $soft->getEntityID();

	}	
*/
	function defineTabs($ID,$withtemplate) {
		global $LANG, $CFG_GLPI;

		$ong[1] = $LANG['title'][26];

		if ($ID){
			if (haveRight("infocom","r")) {
				$ong[4] = $LANG['Menu'][26];
			}
      if (haveRight("document","r")){
				$ong[5]=$LANG['Menu'][27];
			}
			$ong[12] = $LANG['title'][38];
		}

		return $ong;
	}

	/**
	 * Print the Software / license form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the version or the template to print
	 *@param $softwares_id ID of the software for add process
	 *
	 *@return true if displayed  false if item not found or not right to display
	 **/	
	function showForm($target,$ID,$softwares_id=-1){
		global $CFG_GLPI,$LANG;

		if (!haveRight("software","w"))	return false;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$this->getEmpty();
			
			$this->fields['softwares_id']=$softwares_id;
			$this->fields['number']=1;
		} 

		//$soft=new Software();
		//$soft->getFromDB($this->fields['softwares_id']);

		$this->showTabs($ID, false, $_SESSION['glpi_tab'],array(),"softwares_id=".$this->fields['softwares_id']);
		$this->showFormHeader($target,$ID);

		if ($ID>0){
			$softwares_id=$this->fields["softwares_id"];
		} else {
			echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
		}

		echo "<tr class='tab_bg_1'><td>".$LANG['help'][31].":		</td>";
		echo "<td>";
		echo "<a href='software.form.php?ID=".$softwares_id."'>".getDropdownName("glpi_softwares",$softwares_id)."</a>";
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].":		</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_softwareslicenses","name",$this->fields["name"],80);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['common'][19].":		</td>";
		echo "<td>";
		autocompletionTextField("serial","glpi_softwareslicenses","serial",$this->fields["serial"],80);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['common'][20].":		</td>";
		echo "<td>";
		autocompletionTextField("otherserial","glpi_softwareslicenses","otherserial",$this->fields["otherserial"],80);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['tracking'][29].":		</td>";
		echo "<td>";
		if ($this->fields["computers_id"]>0) {
			echo "1  (".$LANG['software'][50].")";		
		} else {
			dropdownInteger("number",$this->fields["number"],1,1000,1,array(-1=>$LANG['software'][4]));			
		}
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['common'][17].":		</td>";
		echo "<td>";
		dropdownValue("glpi_softwareslicensestypes", "softwareslicensestypes_id", $this->fields["softwareslicensestypes_id"]);
		echo "</td></tr>";



		echo "<tr class='tab_bg_1'><td>".$LANG['software'][1].":		</td>";
		echo "<td>";
		dropdownSoftwareVersions("softwaresversions_id_buy",$this->fields["softwares_id"],$this->fields["softwaresversions_id_buy"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['software'][2].":		</td>";
		echo "<td>";
		dropdownSoftwareVersions("softwaresversions_id_use",$this->fields["softwares_id"],$this->fields["softwaresversions_id_use"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['software'][32].":		</td>";
		echo "<td>";
		showDateFormItem('expire',$this->fields["expire"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['software'][50].":		</td>";
		echo "<td>";
		if ($this->fields["number"]==1) {
			dropdownValue('glpi_computers','computers_id',$this->fields["computers_id"],1,$this->fields['entities_id']);
		} else {
			echo $LANG['software'][51];
		}
		echo "</td></tr>";
	
		echo "<tr  class='tab_bg_1'><td valign='top'>";

		// table commentaires
		echo $LANG['common'][25].":	</td>";
		echo "<td class='tab_bg_1'>";
		echo "<textarea cols='70' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";

		echo "</td>";
		echo "</tr>";

		echo "<tr  class='tab_bg_2'>";

      $this->showFormButtons($ID);

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";
				
		return true;
	}

	/**
	 * Is the license may be recursive
	 * 
	 * @return boolean
	**/
	function maybeRecursive () {
		$soft=new Software();
		
		if (isset($this->fields["softwares_id"]) && $soft->getFromDB($this->fields["softwares_id"]))
			return $soft->isRecursive();
		
		return false;
	}
}
?>
