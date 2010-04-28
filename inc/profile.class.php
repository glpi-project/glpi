<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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


/// Profile class
class Profile extends CommonDBTM{

	/// Helpdesk fields of helpdesk profiles
	var $helpdesk_rights=array("faq","reservation_helpdesk","create_ticket","comment_ticket","observe_ticket","password_update","helpdesk_hardware","helpdesk_hardware_type","show_group_ticket","show_group_hardware");

	/// Common fields used for all profiles type
	var $common_fields=array("ID","name","interface","is_default");
	/// Fields not related to a basic right
	var $noright_fields=array("helpdesk_hardware","helpdesk_hardware_type","show_group_ticket","show_group_hardware","own_ticket");

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_profiles";
		$this->type=PROFILE_TYPE;
	}

	function defineTabs($ID,$withtemplate){ 
		global $LANG,$CFG_GLPI; 
			
		$ong[1]=$LANG['common'][12]; 
		if ($ID && haveRight("user","r")){ 
			$ong[2]=$LANG['Menu'][14]; 
		} 
		
		return $ong; 
	} 	
	
	function post_updateItem($input,$updates,$history=1) {
		global $DB;

		if (isset($input["is_default"])&&$input["is_default"]==1){
			$query="UPDATE glpi_profiles SET `is_default`='0' WHERE ID <> '".$input['ID']."'";
			$DB->query($query);
		}
	}
	function cleanDBonPurge($ID) {

		global $DB,$CFG_GLPI,$LINK_ID_TABLE;

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_profiles = '$ID')";
		$DB->query($query);

	}

	function prepareInputForUpdate($input){

      // Check for faq 
      if (isset($input["interface"])&&$input["interface"]=='helpdesk'){
         if (isset($input["faq"])&&$input["faq"]=='w'){
               $input["faq"]=='r';
         }
      }
		if (isset($input["helpdesk_hardware_type"])){
			$types=$input["helpdesk_hardware_type"];
			unset($input["helpdesk_hardware_type"]);
			$input["helpdesk_hardware_type"]=0;
			foreach ($types as $val)
				$input["helpdesk_hardware_type"]+=pow(2,$val);
		} else if (isset($input["_helpdesk_hardware_type_present"])) {
                     $input["helpdesk_hardware_type"]=0;
                }
		return $input;
	}

	function prepareInputForAdd($input){
		if (isset($input["helpdesk_hardware_type"])){
			$types=$input["helpdesk_hardware_type"];
			unset($input["helpdesk_hardware_type"]);
			$input["helpdesk_hardware_type"]=0;
			foreach ($types as $val)
				$input["helpdesk_hardware_type"]+=pow(2,$val);
		}
		return $input;
	}

	/**
	 * Unset unused rights for helpdesk
	 **/
	function cleanProfile(){
		if ($this->fields["interface"]=="helpdesk"){
			foreach($this->fields as $key=>$val){
				if (!in_array($key,$this->common_fields)&&!in_array($key,$this->helpdesk_rights)){
					unset($this->fields[$key]);
				}
			}
		}
	}
	/**
	 * Get SQL restrict request to determine profiles with less rights than the active one
	 * @param $separator Separator used at the beginning of the request
	 * @return SQL restrict string
	 **/
	function getUnderProfileRetrictRequest($separator = "AND"){
		$query = $separator ." ";

		// Not logged -> no profile to see
		if (!isset($_SESSION['glpiactiveprofile'])){
			return $query." 0 ";
		}

		// Profile right : may modify profile so can attach all profile
		if (haveRight("profile","w")){
			return $query." 1 ";
		}
		
		if ($_SESSION['glpiactiveprofile']['interface']=='central'){
			$query.=" (glpi_profiles.interface='helpdesk') " ;
		}
		
		$query.=" OR ( glpi_profiles.interface='".$_SESSION['glpiactiveprofile']['interface']."' ";
		foreach ($_SESSION['glpiactiveprofile'] as $key => $val){
			if (
			!is_array($val) // Do not include entities field added by login
			&&!in_array($key,$this->common_fields) // 
			&&!in_array($key,$this->noright_fields)
			&&($_SESSION['glpiactiveprofile']['interface']=='central'||in_array($key,$this->helpdesk_rights))){
				switch ($key){
				
					default:
						switch ($val){
							case '0':
								$query.=" AND (glpi_profiles.$key = '0' OR glpi_profiles.$key IS NULL OR glpi_profiles.$key = '') ";
								break;	
							case '1':
								$query.=" AND (glpi_profiles.$key = '1' OR glpi_profiles.$key = '0' OR glpi_profiles.$key IS NULL OR glpi_profiles.$key = '' ) ";
								break;	
							case 'r':
								$query.=" AND (glpi_profiles.$key = 'r' OR glpi_profiles.$key IS NULL OR glpi_profiles.$key = '' ) ";
								break;	
							case 'w':
								$query.=" AND (glpi_profiles.$key = 'w' OR glpi_profiles.$key = 'r' OR glpi_profiles.$key IS NULL OR glpi_profiles.$key = '' ) ";
								break;	
							default:
								$query.=" AND glpi_profiles.$key IS NULL OR glpi_profiles.$key = '' ";
								break;
						}
					break;
				}
			}
		}	
		$query.=")";
		return $query;
	}

	/**
	 * Is the current user have more right than all profiles in parameters
	 *
	 *@param $IDs array of profile ID to test
	 *@return boolean true if have more right
	 **/	
	function currentUserHaveMoreRightThan($IDs=array()){
		global $DB;

      if (count($IDs)==0) {
         // Check all profiles (means more right than all possible profiles) 
         return (countElementsInTable($this->table)
              == countElementsInTable($this->table, $this->getUnderProfileRetrictRequest('')));
      }

		$under_profiles=array();
		$query="SELECT * FROM glpi_profiles ".$this->getUnderProfileRetrictRequest("WHERE");
		$result=$DB->query($query);
		while ($data=$DB->fetch_assoc($result)){
			$under_profiles[$data['ID']]=$data['ID'];
		}
		foreach ($IDs as $ID){
			if (!isset($under_profiles[$ID])){
				return false;
			}
		}
		return true;

	}

	/**
	 * Print the profile form configuration
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $rand integer : rand in order form is unique
	 *@return boolean item found
	 **/
	function showProfileConfig($target,$ID,$rand){
		global $LANG,$CFG_GLPI;


	}
	function showLegend(){
		global $LANG;
		
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_2'><td width='70' style='text-decoration:underline'><strong>".$LANG['profiles'][34]." : </strong></td><td class='tab_bg_4' width='15' style='border:1px solid black'></td><td><strong>".$LANG['profiles'][0]."</strong></td></tr>";
		echo "<tr class='tab_bg_2'><td></td><td class='tab_bg_2' width='15' style='border:1px solid black'></td><td><strong>".$LANG['profiles'][1]."</strong></td></tr>";
		echo "</table>";
	}

	/**
	 * Print the profile form headers
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *@param $withtemplate integer template or basic item
	 *
	 *@return boolean item found
	 **/
	function showForm($target,$ID, $withtemplate=''){
		global $LANG,$CFG_GLPI;

		if (!haveRight("profile","r")) return false;

		$onfocus="";
		$new=false;
		if (!empty($ID)&&$ID){
			$this->getFromDB($ID);
		} else {
			$this->getEmpty();
			$onfocus="onfocus=\"this.value=''\"";
			$new=true;
		}

		$rand=mt_rand();

		if (empty($this->fields["interface"])) $this->fields["interface"]="helpdesk";
		if (empty($this->fields["name"])) $this->fields["name"]=$LANG['common'][0];

		echo "<form name='form' method='post' action=\"$target\">";
		echo "<div class='center' id='tabsbody' >";
		echo "<table class='tab_cadre_fixe'><tr>";
		echo "<th>".$LANG['common'][16]." :&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input type='text' name='name' value=\"".$this->fields["name"]."\" $onfocus></th>";
		echo "<th>".$LANG['profiles'][2]." :&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<select name='interface' ".($new?"":"onchange='submit()'").">";
		echo "<option value='helpdesk' ".($this->fields["interface"]=="helpdesk"?"selected":"").">".$LANG['Menu'][31]."</option>";
		echo "<option value='central' ".($this->fields["interface"]=="central"?"selected":"").">".$LANG['title'][0]."</option>";
		echo "</select></th>";
		echo "</tr></table></div>";

		echo "<div align='center' id='profile_form$rand'>";

		if (!empty($ID)&&$ID){

			//$this->showProfileConfig($target,$ID,$rand);
	
			if ($this->fields["interface"]=="helpdesk"){
				$this->showHelpdeskForm($CFG_GLPI["root_doc"]."/front/profile.form.php",$ID);
			} else {
				$this->showCentralForm($CFG_GLPI["root_doc"]."/front/profile.form.php",$ID);
			}
			$this->showLegend();
		} else {
			echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
		}
		echo "</div>";
		
		return true;
	}


	/**
	 * Print the helpdesk form for a profile
	 *
	 *@param $ID Integer : Id of the item to print
	 *
	 *@return boolean item found
	 **/	
	function showHelpdeskForm($target,$ID){
		global $LANG,$CFG_GLPI;

		if (!haveRight("profile","r")) return false;
		$canedit=haveRight("profile","w");
		if ($ID){
			$this->getFromDB($ID);
		} else {
			return false;
		}
		echo "<table class='tab_cadre_fixe'><tr>";
		echo "<th colspan='4'>".$LANG['profiles'][3].":&nbsp;&nbsp;".$LANG['profiles'][13].":&nbsp;&nbsp;";
		dropdownYesNo("is_default",$this->fields["is_default"]);
		echo "</th></tr>";

		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$LANG['profiles'][25]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][24].":</td><td>";
		dropdownYesNo("password_update",$this->fields["password_update"]);
		echo "</td>";
		echo "<td colspan='2'>&nbsp;";
		echo "</td>";
		echo "</tr>";


		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$LANG['title'][24]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][5].":</td><td>";
		dropdownYesNo("create_ticket",$this->fields["create_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][6].":</td><td>";
		dropdownYesNo("comment_ticket",$this->fields["comment_ticket"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][9].":</td><td>";
		dropdownYesNo("observe_ticket",$this->fields["observe_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][26].":</td><td>";
		dropdownYesNo("show_group_ticket",$this->fields["show_group_ticket"]);
		echo "</td>";
		echo "</tr>";
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][27].":</td><td>";
		dropdownYesNo("show_group_hardware",$this->fields["show_group_hardware"]);
		echo "</td>";
		echo "<td>&nbsp;</td><td>&nbsp;";
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['setup'][350]."</td><td>";
		echo "<select name=\"helpdesk_hardware\">";
		echo "<option value=\"0\" ".($this->fields["helpdesk_hardware"]==0?"selected":"")." >------</option>";
		echo "<option value=\"".pow(2,HELPDESK_MY_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_MY_HARDWARE)?"selected":"")." >".$LANG['tracking'][1]."</option>";
		echo "<option value=\"".pow(2,HELPDESK_ALL_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_ALL_HARDWARE)?"selected":"")." >".$LANG['setup'][351]."</option>";
		echo "<option value=\"".(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))."\" ".($this->fields["helpdesk_hardware"]==(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))?"selected":"")." >".$LANG['tracking'][1]." + ".$LANG['setup'][351]."</option>";
		echo "</select>";
		echo "</td><td>".$LANG['setup'][352]."</td>";
		echo "<td>";

                echo "<input type='hidden' name='_helpdesk_hardware_type_present' value='1'>";
		echo "<select name='helpdesk_hardware_type[]' multiple size='3'>";
		$ci = new CommonItem();
		foreach($CFG_GLPI["helpdesk_types"] as $type){
			$ci->setType($type);
			echo "<option value='".$type."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,$type))?" selected":"").">".$ci->getType()."</option>\n";
		}
		echo "</select>";

		echo "</td>";

		echo "</tr>";

		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><strong>".$LANG['Menu'][18]."</strong></td>";
		echo "</tr>";


		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['knowbase'][1].":</td><td>";
      if ($this->fields["interface"]=="helpdesk" && $this->fields["faq"]=='w'){
         $this->fields["faq"]='r';
      }
		dropdownNoneReadWrite("faq",$this->fields["faq"],1,1,0);
		echo "</td>";
		echo "<td>".$LANG['Menu'][17].":</td><td>";
		dropdownYesNo("reservation_helpdesk",$this->fields["reservation_helpdesk"]);
		echo "</td></tr>";

		if ($canedit){
			echo "<tr class='tab_bg_1'>";
			echo "<td colspan='2' align='center'>";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";

			echo "</td><td colspan='2' align='center'>";
			echo "<input type='submit' name='delete' onclick=\"return confirm('".$LANG['common'][50]."')\"  value=\"".$LANG['buttons'][6]."\" class='submit'>";

			echo "</td></tr>";
		}
		echo "</table>";
	}


	/**
	 * Print the central form for a profile
	 *
	 *@param $ID Integer : Id of the item to print
	 *
	 *@return boolean item found
	 **/	
	function showCentralForm($target,$ID){
		global $LANG,$CFG_GLPI;

		if (!haveRight("profile","r")) return false;
		$canedit=haveRight("profile","w");

		if ($ID){
			$this->getFromDB($ID);
		} else {
			return false;
		}
		
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr>";
		echo "<th colspan='6'>".$LANG['profiles'][4].":&nbsp;&nbsp;".$LANG['profiles'][13].":&nbsp;&nbsp;";
		dropdownYesNo("is_default",$this->fields["is_default"]);
		echo "</th></tr>";
		// Inventory
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['Menu'][38]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['Menu'][0].":</td><td>";
		dropdownNoneReadWrite("computer",$this->fields["computer"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][3].":</td><td>";
		dropdownNoneReadWrite("monitor",$this->fields["monitor"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][4].":</td><td>";
		dropdownNoneReadWrite("software",$this->fields["software"],1,1,1);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['Menu'][1].":</td><td>";
		dropdownNoneReadWrite("networking",$this->fields["networking"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][2].":</td><td>";
		dropdownNoneReadWrite("printer",$this->fields["printer"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][21].":</td><td>";
		dropdownNoneReadWrite("cartridge",$this->fields["cartridge"],1,1,1);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['Menu'][32].":</td><td>";
		dropdownNoneReadWrite("consumable",$this->fields["consumable"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][34].":</td><td>";
		dropdownNoneReadWrite("phone",$this->fields["phone"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][16].":</td><td>";
		dropdownNoneReadWrite("peripheral",$this->fields["peripheral"],1,1,1);
		echo "</td>";
		echo "</tr>";
		// General
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['profiles'][25]."</strong></td></tr>";


		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['title'][37].":</td><td>";
		dropdownNoneReadWrite("notes",$this->fields["notes"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['profiles'][24].":</td><td>";
		dropdownYesNo("password_update",$this->fields["password_update"]);
		echo "</td>";
		echo "<td>".$LANG['reminder'][1].":</td><td>";
		dropdownNoneReadWrite("reminder_public",$this->fields["reminder_public"],1,1,1);
		echo "</td></tr>";
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['bookmark'][5].":</td><td>";
		dropdownNoneReadWrite("bookmark_public",$this->fields["bookmark_public"],1,1,1);
		echo "</td>";
		echo "<td colspan='4'>";
		echo "</td></tr>";
		// Gestion / Management
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['Menu'][26]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['Menu'][22]." / ".$LANG['Menu'][23].":</td><td>";
		dropdownNoneReadWrite("contact_enterprise",$this->fields["contact_enterprise"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][27].":</td><td>";
		dropdownNoneReadWrite("document",$this->fields["document"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][25].":</td><td>";
		dropdownNoneReadWrite("contract",$this->fields["contract"],1,1,1);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['Menu'][24].":</td><td>";
		dropdownNoneReadWrite("infocom",$this->fields["infocom"],1,1,1);
		echo "</td>";
		echo "<td colspan='4'>&nbsp;</td>";
		echo "</tr>";

		// Assistance / Tracking-helpdesk
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['title'][24]."</strong></td></tr>";

		echo "<tr><td class='tab_bg_5' colspan='6' ><strong>".$LANG['profiles'][41]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][5].":</td><td>";
		dropdownYesNo("create_ticket",$this->fields["create_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][6].":</td><td>";
		dropdownYesNo("comment_ticket",$this->fields["comment_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][15].":</td><td>";
		dropdownYesNo("comment_all_ticket",$this->fields["comment_all_ticket"]);
		echo "</td>";
		echo "</tr>";

		echo "<tr><td class='tab_bg_5' colspan='6' ><strong>".$LANG['profiles'][40]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][18].":</td><td>";
		dropdownYesNo("update_ticket",$this->fields["update_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][14].":</td><td>";
		dropdownYesNo("delete_ticket",$this->fields["delete_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][35].":</td><td>";
		dropdownYesNo("update_followups",$this->fields["update_followups"]);
		echo "</td></tr>";

		echo "<tr><td class='tab_bg_5' colspan='6' ><strong>".$LANG['profiles'][39]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		

		echo "<td>".$LANG['profiles'][16].":</td><td>";
		dropdownYesNo("own_ticket",$this->fields["own_ticket"]);
		echo "<td>".$LANG['profiles'][17].":</td><td>";
		dropdownYesNo("steal_ticket",$this->fields["steal_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][19].":</td><td>";
		dropdownYesNo("assign_ticket",$this->fields["assign_ticket"]);
		echo "</td></tr>";

		echo "<tr><td class='tab_bg_5' colspan='6' ><strong>".$LANG['profiles'][42]."</strong></td></tr>";
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][27]."</td><td>";
		dropdownYesNo("show_group_hardware",$this->fields["show_group_hardware"]);
		echo "</td>";

		echo "<td>".$LANG['setup'][350].":</td><td>";
		echo "<select name=\"helpdesk_hardware\">";
		echo "<option value=\"0\" ".($this->fields["helpdesk_hardware"]==0?"selected":"")." >------</option>";
		echo "<option value=\"".pow(2,HELPDESK_MY_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_MY_HARDWARE)?"selected":"")." >".$LANG['tracking'][1]."</option>";
		echo "<option value=\"".pow(2,HELPDESK_ALL_HARDWARE)."\" ".($this->fields["helpdesk_hardware"]==pow(2,HELPDESK_ALL_HARDWARE)?"selected":"")." >".$LANG['setup'][351]."</option>";
		echo "<option value=\"".(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))."\" ".($this->fields["helpdesk_hardware"]==(pow(2,HELPDESK_MY_HARDWARE)+pow(2,HELPDESK_ALL_HARDWARE))?"selected":"")." >".$LANG['tracking'][1]." + ".$LANG['setup'][351]."</option>";
		echo "</select>";
		echo "</td>";

		echo "<td>".$LANG['setup'][352].":</td>";
		echo "<td>";

                echo "<input type='hidden' name='_helpdesk_hardware_type_present' value='1'>";
		echo "<select name='helpdesk_hardware_type[]' multiple size='3'>";
		$ci = new CommonItem();
		foreach($CFG_GLPI["helpdesk_types"] as $type){
			$ci->setType($type);
			echo "<option value='".$type."' ".(($this->fields["helpdesk_hardware_type"]&pow(2,$type))?" selected":"").">".$ci->getType()."</option>\n";
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";


		
		echo "<tr><td class='tab_bg_5' colspan='6' ><strong>".$LANG['profiles'][38]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		
		
		echo "<td>".$LANG['profiles'][32].":</td><td>";
		dropdownYesNo("show_assign_ticket",$this->fields["show_assign_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][26]."</td><td>";
		dropdownYesNo("show_group_ticket",$this->fields["show_group_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][7].":</td><td>";
		dropdownYesNo("show_all_ticket",$this->fields["show_all_ticket"]);
		echo "</td>";
		echo "</tr>";
		echo "<tr class='tab_bg_2'>";
		echo "<td>".$LANG['profiles'][9].":</td><td>";
		dropdownYesNo("observe_ticket",$this->fields["observe_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][8].":</td><td>";
		dropdownYesNo("show_full_ticket",$this->fields["show_full_ticket"]);
		echo "</td>";
		echo "<td>".$LANG['Menu'][13].":</td><td>";
		dropdownYesNo("statistic",$this->fields["statistic"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		
		echo "<td>".$LANG['profiles'][20].":</td><td>";
		dropdownYesNo("show_planning",$this->fields["show_planning"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][36].":</td><td>";
		dropdownYesNo("show_group_planning",$this->fields["show_group_planning"]);
		echo "</td>";
		echo "<td>".$LANG['profiles'][21].":</td><td>";
		dropdownYesNo("show_all_planning",$this->fields["show_all_planning"]);
		echo "</td></tr>";

		

		// Outils / Tools
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['Menu'][18]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td class='tab_bg_4'>".$LANG['knowbase'][1].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("faq",$this->fields["faq"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][6].":</td><td>";
		dropdownNoneReadWrite("reports",$this->fields["reports"],1,1,0);
		echo "</td>";
		echo "<td>".$LANG['Menu'][17].":</td><td>";
		dropdownYesNo("reservation_helpdesk",$this->fields["reservation_helpdesk"]);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td class='tab_bg_4'>".$LANG['title'][5].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("knowbase",$this->fields["knowbase"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['profiles'][23].":</td><td>";
		dropdownNoneReadWrite("reservation_central",$this->fields["reservation_central"],1,1,1);
		echo "</td>";
		echo "<td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td  class='tab_bg_4'>".$LANG['Menu'][33].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("ocsng",$this->fields["ocsng"],1,0,1);
		echo "</td>";
		echo "<td>".$LANG['profiles'][31].":</td><td>";
		dropdownNoneReadWrite("sync_ocsng",$this->fields["sync_ocsng"],1,0,1);
		echo "</td>";
		echo "<td>".$LANG['profiles'][30].":</td><td>";
		dropdownNoneReadWrite("view_ocsng",$this->fields["view_ocsng"],1,1,0);
		echo "</td>";
		echo "</tr>";

		// Administration
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['Menu'][15]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";

		echo "<td>".$LANG['Menu'][14].":</td><td>";
		dropdownNoneReadWrite("user",$this->fields["user"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['Menu'][36].":</td><td>";
		dropdownNoneReadWrite("group",$this->fields["group"],1,1,1);
		echo "</td>";
		echo "<td>".$LANG['profiles'][43].":</td><td>";
		dropdownNoneReadWrite("user_auth_method",$this->fields["user_auth_method"],1,1,1);
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_4'>";	
		echo "<td class='tab_bg_4'>".$LANG['Menu'][37].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("entity",$this->fields["entity"],1,1,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['transfer'][1].":</td><td>";
		dropdownNoneReadWrite("transfer",$this->fields["transfer"],1,1,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['Menu'][35].":</td><td>";
		dropdownNoneReadWrite("profile",$this->fields["profile"],1,1,1);
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_4'>";
		echo "<td class='tab_bg_4'>".$LANG['Menu'][12].":</td><td>";
		dropdownNoneReadWrite("backup",$this->fields["backup"],1,0,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['Menu'][30].":</td><td>";
		dropdownNoneReadWrite("logs",$this->fields["logs"],1,1,0);
		echo "</td>";
		echo "<td colspan='2'></td></tr>";


		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['rulesengine'][17].' / '.$LANG['rulesengine'][77]."</strong></td></tr>";

		echo "<tr class='tab_bg_4'>";
		echo "<td class='tab_bg_4'>".$LANG['rulesengine'][19].":</td><td>";
		dropdownNoneReadWrite("rule_ldap",$this->fields["rule_ldap"],1,1,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['rulesengine'][18].":</td><td>";
		dropdownNoneReadWrite("rule_ocs",$this->fields["rule_ocs"],1,1,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['rulesengine'][28].":</td><td>";
		dropdownNoneReadWrite("rule_tracking",$this->fields["rule_tracking"],1,1,1);
		echo "</td>";
		echo "</tr>";
		echo "<tr class='tab_bg_4'>";
		echo "<td class='tab_bg_4'>".$LANG['rulesengine'][37].":</td><td>";
		dropdownNoneReadWrite("rule_softwarecategories",$this->fields["rule_softwarecategories"],1,1,1);
		echo "</td>";

		echo "<td class='tab_bg_4'>".$LANG['rulesengine'][33].":</td><td>";
		dropdownNoneReadWrite("rule_dictionnary_dropdown",$this->fields["rule_dictionnary_dropdown"],1,1,1);
		echo"</td>";

		echo "<td class='tab_bg_4'>".$LANG['rulesengine'][35].":</td><td>";
		dropdownNoneReadWrite("rule_dictionnary_software",$this->fields["rule_dictionnary_software"],1,1,1);
		echo"</td>";
		echo "</tr>";

		// Configuration 
		echo "<tr><td class='tab_bg_1' colspan='6' align='center'><strong>".$LANG['common'][12]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td  class='tab_bg_4'>".$LANG['common'][12].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("config",$this->fields["config"],1,0,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['setup'][250].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("search_config_global",$this->fields["search_config_global"],1,0,1);
		echo "</td>";
		echo "<td>".$LANG['setup'][250]." (".$LANG['common'][34]."):</td><td>";
		dropdownNoneReadWrite("search_config",$this->fields["search_config"],1,0,1);
		echo "</td>";
		echo "</tr>";


		echo "<tr class='tab_bg_2'>";
		echo "<td class='tab_bg_4'>".$LANG['title'][30].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("device",$this->fields["device"],1,0,1);
		echo "</td>";
		echo "<td  class='tab_bg_4'>".$LANG['setup'][0].":</td><td class='tab_bg_4'>";
		dropdownNoneReadWrite("dropdown",$this->fields["dropdown"],1,0,1);
		echo "</td>";
		echo "<td>".$LANG['setup'][0]." (".$LANG['entity'][0]."):</td><td>";
		dropdownNoneReadWrite("entity_dropdown",$this->fields["entity_dropdown"],1,0,1);
		echo "</td>";
		echo "</tr>";



		echo "<tr class='tab_bg_4'>";
		echo "<td class='tab_bg_4'>".$LANG['document'][7].":</td><td>";
		dropdownNoneReadWrite("typedoc",$this->fields["typedoc"],1,1,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['setup'][87].":</td><td>";
		dropdownNoneReadWrite("link",$this->fields["link"],1,1,1);
		echo "</td>";
		echo "<td class='tab_bg_4'>".$LANG['setup'][306].":</td><td>";
		dropdownNoneReadWrite("check_update",$this->fields["check_update"],1,1,0);
		echo "</td>";
		echo "</tr>";

		if ($canedit){
			echo "<tr class='tab_bg_2'>";
			echo "<td colspan='3' align='center'>";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
			echo "</td><td colspan='3' align='center'>";
			echo "<input type='submit' name='delete'  onclick=\"return confirm('".$LANG['common'][50]."')\"  value=\"".$LANG['buttons'][6]."\" class='submit'>";
			echo "</td></tr>";
		}
		echo "</table>";
	}


}	
?>
