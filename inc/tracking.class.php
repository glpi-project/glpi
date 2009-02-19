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

/// Tracking class
class Job extends CommonDBTM{
	/// Hardware datas used by getFromDBwithData
	var $hardwaredatas	= array();
	/// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
	var $computerfound	= 0;

	/**
	 * Constructor
	**/
	function __construct(){
		$this->table="glpi_tracking";
		$this->type=TRACKING_TYPE;
		$this->entity_assign=true;

	}
	
	function defineTabs($ID,$withtemplate){ 
		global $LANG,$CFG_GLPI; 
		
		$job=new Job();
		$job->getFromDB($ID);
		
		$ong[1]=$LANG["job"][38]." ".$ID;
		if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
			if ($job->canAddFollowups()){
				$ong[2]=$LANG["job"][29];
			}
		}elseif (haveRight("comment_ticket","1")){
			$ong[1]=$LANG["job"][38]." ".$ID;
			if (!strstr($job->fields["status"],"old_")&&$job->fields["author"]==$_SESSION["glpiID"]){
				$ong[2]=$LANG["job"][29];
			}
		}

		$ong['no_all_tab']=true;

		return $ong; 
	}
	/**
	 * Retrieve an item from the database with datas associated (hardwares)
	 *
	 *@param $ID ID of the item to get
	 *@param $purecontent boolean : true : nothing change / false : convert to HTML display
	 *@return true if succeed else false
	**/
	function getFromDBwithData ($ID,$purecontent) {

		global $DB,$LANG;

		if ($this->getFromDB($ID)){

			if (!$purecontent) {
				$this->fields["contents"] = nl2br(preg_replace("/\r\n\r\n/","\r\n",$this->fields["contents"]));
			}

			$this->getHardwareData();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Retrieve data of the hardware linked to the ticket if exists
	 *
	 *@return nothing : set computerfound to 1 if founded
	**/
	function getHardwareData(){
		$m= new CommonItem;
		if ($m->getFromDB($this->fields["device_type"],$this->fields["computer"])){
			$this->hardwaredatas=$m;
			$this->computerfound=0;
		} else {
			$this->hardwaredatas=$m;
			$this->computerfound=1;
		}
	}
	function cleanDBonPurge($ID) {
		global $DB;

		$query="SELECT ID FROM glpi_followups WHERE tracking = '$ID'";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0)
			while ($data=$DB->fetch_array($result)){
				$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '".$data['ID']."'";
				$DB->query($querydel);				
			}
		$query1="DELETE FROM glpi_followups WHERE tracking = '$ID'";
		$DB->query($query1);

	}

	function prepareInputForUpdate($input) {
		global $LANG,$CFG_GLPI;

		if (isset($input["date"])&&empty($input["date"])){
			unset($input["date"]);
		}
		if (isset($input["closedate"])&&empty($input["closedate"])){
			unset($input["closedate"]);
		}

		// Security checks
		if (!haveRight("assign_ticket","1")){
			if (isset($input["assign"])){
				$this->getFromDB($input['ID']);
				// must own_ticket to grab a non assign ticket
				if ($this->fields['assign']==0){
					if ((!haveRight("steal_ticket","1") && !haveRight("own_ticket","1"))
						|| ($input["assign"]!=$_SESSION["glpiID"])){
						unset($input["assign"]);
					}
				} else {
					// Can not steal or can steal and not assign to me
					if (!haveRight("steal_ticket","1")||$input["assign"]!=$_SESSION["glpiID"]){
						unset($input["assign"]);
					} 

				}
			}
			if (isset($input["assign_ent"])){
				unset($input["assign_ent"]);
			}
			if (isset($input["assign_group"])){
				unset($input["assign_group"]);
			}

		}

		if (!haveRight("update_ticket","1")){
			// Manage assign and steal right
			if (isset($input["assign"])){
				$ret["assign"]=$input["assign"];
			}
			if (isset($input["assign_ent"])){
				$ret["assign_ent"]=$input["assign_ent"];
			}
			if (isset($input["assign_group"])){
				$ret["assign_group"]=$input["assign_group"];
			}
			// Can only update contents if no followups already added
			$ret["ID"]=$input["ID"];
			if (isset($input["contents"])){
				$ret["contents"]=$input["contents"];
			}
			if (isset($input["name"])){
				$ret["name"]=$input["name"];
			}
			$input=$ret;
		}

		// NEEDED ???? 
		if (isset($input["type"])&& $input["type"]==0&&!isset($input["item"])){
			$input["computer"]=0;
			$input["device_type"]=$input["type"];
		} else if (isset($input["item"])&& $input["item"]!=0&&isset($input["type"])&& $input["type"]!=0){
			$input["computer"]=$input["item"];
			$input["device_type"]=$input["type"];
		} 

		if (isset($input["computer"])&&$input["computer"]>=0&&isset($input["device_type"])&&$input["device_type"]>=0){
			if (isset($this->fields['FK_group'])&&$this->fields['FK_group']){
				$ci=new CommonItem;
				$ci->getFromDB($input["device_type"],$input["computer"]);
				if ($tmp=$ci->getField('FK_groups')){
					$input["FK_group"] = $tmp;
				}
			}
		} else if (isset($input["device_type"])&&$input["device_type"]==0){
			$input["computer"]=0;
		} else {
			unset($input["computer"]);
			unset($input["device_type"]);
		}	


		if ( isset($_FILES['multiple']) ) {
			unset($_FILES['multiple']);
			$TMPFILE = $_FILES;
		} else {
			$TMPFILE = array( $_FILES );
		}
		foreach ($TMPFILE as $_FILES) {
			// add Document if exists
			if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
				$input2=array();
				$input2["name"]=addslashes(resume_text($LANG["tracking"][24]." ".$input["ID"],200)); 
				$input2["FK_tracking"]=$input["ID"];
				$input2["rubrique"]=$CFG_GLPI["default_rubdoc_tracking"];
				$this->getFromDB($input["ID"]);
				$input2["FK_entities"]=$this->fields["FK_entities"];
				$input2["_only_if_upload_succeed"]=1;
				$doc=new Document();
				if ($docID=$doc->add($input2)){
					addDeviceDocument($docID,TRACKING_TYPE,$input["ID"]);
					// force update date_mod
					$input["date_mod"]=$_SESSION["glpi_currenttime"];
					if ($CFG_GLPI["followup_on_update_ticket"]){
						$input['_doc_added']=stripslashes($doc->fields["name"]); 
					}
				}
			} else if (!empty($_FILES['filename']['name'])&&isset($_FILES['filename']['error'])&&$_FILES['filename']['error']){
				addMessageAfterRedirect($LANG["document"][46]);
			}
		}

		if (isset($input["document"])&&$input["document"]>0){
			addDeviceDocument($input["document"],TRACKING_TYPE,$input["ID"]);
			$doc=new Document();
			$doc->getFromDB($input["document"]);
			unset($input["document"]);
			// Force date_mod of tracking
			$input["date_mod"]=$_SESSION["glpi_currenttime"];
			$input['_doc_added']=$doc->fields["name"];
		}

		// Old values for add followup in change
		if ($CFG_GLPI["followup_on_update_ticket"]){
			$this->getFromDB($input["ID"]);
			$input["_old_assign_name"]=getAssignName($this->fields["assign"],USER_TYPE);
			$input["_old_assign"]=$this->fields["assign"];
			$input["_old_assign_ent_name"]=getAssignName($this->fields["assign_ent"],ENTERPRISE_TYPE);
			$input["_old_assign_group_name"]=getAssignName($this->fields["assign_group"],GROUP_TYPE);
			$input["_old_category"]=$this->fields["category"];
			$input["_old_item"]=$this->fields["computer"];
			$input["_old_item_type"]=$this->fields["device_type"];
			$input["_old_author"]=$this->fields["author"];
			$input["_old_recipient"]=$this->fields["recipient"];
			$input["_old_group"]=$this->fields["FK_group"];
			$input["_old_priority"]=$this->fields["priority"];
			$input["_old_status"]=$this->fields["status"];
			$input["_old_request_type"]=$this->fields["request_type"];
			$input["_old_cost_time"]=$this->fields["cost_time"];
			$input["_old_cost_fixed"]=$this->fields["cost_fixed"];
			$input["_old_cost_material"]=$this->fields["cost_material"];
			$input["_old_date"]=$this->fields["date"];
			$input["_old_closedate"]=$this->fields["closedate"];
		}
		return $input;
	}

	function pre_updateInDB($input,$updates) {
		global $LANG;

		// Status close : check dates
		if (strstr($this->fields["status"],"old_")&&(in_array("date",$updates)||in_array("closedate",$updates))){
			// Invalid dates : no change
			if ($this->fields["closedate"]<$this->fields["date"]){
				addMessageAfterRedirect($LANG["tracking"][3]);
				if (($key=array_search('date',$updates))!==false){
					unset($updates[$key]);
				}
				if (($key=array_search('closedate',$updates))!==false){
					unset($updates[$key]);
				}
			}
		}



		if (((in_array("assign",$updates)&&$input["assign"]>0)||(in_array("assign_ent",$updates)&&$input["assign_ent"]>0)||(in_array("assign_group",$updates)&&$input["assign_group"]>0))&&$this->fields["status"]=="new"){
			$updates[]="status";
			$this->fields["status"]="assign";
		}
		if (isset($input["status"])){
			if (isset($input["assign_ent"])&&$input["assign_ent"]==0&&isset($input["assign_group"])&&$input["assign_group"]==0&&
			isset($input["assign"])&&$input["assign"]==0&&$input["status"]=="assign"){
				$updates[]="status";
				$this->fields["status"]="new";
			}

			if (in_array("status",$updates)&&strstr($input["status"],"old_")){
				$updates[]="closedate";
				$this->fields["closedate"]=$_SESSION["glpi_currenttime"];
				// If invalid date : set open date
				if ($this->fields["closedate"]<$this->fields["date"]){
					$this->fields["closedate"]=$this->fields["date"];
				}
			}
		}

		if (in_array("author",$updates)){
			$user=new User;
			$user->getFromDB($input["author"]);
			if (!empty($user->fields["email"])){
				$updates[]="uemail";
				$this->fields["uemail"]=$user->fields["email"];
			}
		}

		return array($input,$updates);
	}

	function post_updateItem($input,$updates,$history=1) {
		global $CFG_GLPI,$LANG;

		if (count($updates)){
			// New values for add followup in change
			$change_followup_content="";
			if (isset($input['_doc_added'])){
				$change_followup_content=$LANG["mailing"][26]." ".$input['_doc_added'];
			}
			$global_mail_change_count=0;
	
			// Update Ticket Tco
			if (in_array("realtime",$updates)||in_array("cost_time",$updates)|| in_array("cost_fixed",$updates)||in_array("cost_material",$updates)){
				$ci=new CommonItem;
				if ($ci->getFromDB($this->fields["device_type"],$this->fields["computer"])){
					$newinput=array();
					$newinput['ID']=$this->fields["computer"];
					$newinput['ticket_tco']=computeTicketTco($this->fields["device_type"],$this->fields["computer"]);
					$ci->obj->update($newinput);
				}
			}

			if ($CFG_GLPI["followup_on_update_ticket"]&&count($updates)){
	
	
				foreach ($updates as $key)
				switch ($key) {
					case "name":
						$change_followup_content.=$LANG["mailing"][45]."\n";
						$global_mail_change_count++;
						break;
					case "contents":
						$change_followup_content.=$LANG["mailing"][46]."\n";
						$global_mail_change_count++;
					break;
					case "date":
						$change_followup_content.=$LANG["mailing"][48].": ".$input["_old_date"]." -> ".$this->fields["date"]."\n";
		
						$global_mail_change_count++;
					break;
					case "closedate":
						// if update status from an not closed status : no mail for change closedate
						if (!in_array("status",$updates)||!strstr($input["status"],"old_")){
							$change_followup_content.=$LANG["mailing"][49].": ".$input["_old_closedate"]." -> ".$this->fields["closedate"]."\n";
			
							$global_mail_change_count++;
						}
					break;
					case "status":
						$new_status=$this->fields["status"];
						$change_followup_content.=$LANG["mailing"][27].": ".getStatusName($input["_old_status"])." -> ".getStatusName($new_status)."\n";
		
						if (strstr($new_status,"old_"))
							$newinput["add_close"]="add_close";

						if (in_array("closedate",$updates))	
							$global_mail_change_count++; // Manage closedate
		
						$global_mail_change_count++;
					break;
					case "author":
						$author=new User;
						$author->getFromDB($input["_old_author"]);
						$old_author_name=$author->getName();
						$author->getFromDB($this->fields["author"]);
						$new_author_name=$author->getName();
						$change_followup_content.=$LANG["mailing"][18].": $old_author_name -> ".$new_author_name."\n";
		
						$global_mail_change_count++;
					break;
					case "recipient":
						$recipient=new User;
						$recipient->getFromDB($input["_old_recipient"]);
						$old_recipient_name=$recipient->getName();
						$recipient->getFromDB($this->fields["recipient"]);
						$new_recipient_name=$recipient->getName();
						$change_followup_content.=$LANG["mailing"][50].": $old_recipient_name -> ".$new_recipient_name."\n";
		
						$global_mail_change_count++;
					break;
					case "FK_group" :
						$new_group=$this->fields["FK_group"];
						$old_group_name=str_replace("&nbsp;",$LANG["mailing"][109],getDropdownName("glpi_groups",$input["_old_group"]));
						$new_group_name=str_replace("&nbsp;",$LANG["mailing"][109],getDropdownName("glpi_groups",$new_group));
						$change_followup_content.=$LANG["mailing"][20].": ".$old_group_name." -> ".$new_group_name."\n";
						$global_mail_change_count++;
					break;
					case "priority" :
						$new_priority=$this->fields["priority"];
						$change_followup_content.=$LANG["mailing"][15].": ".getPriorityName($input["_old_priority"])." -> ".getPriorityName($new_priority)."\n";
						$global_mail_change_count++;		
					break;
					case "category":
						$new_category=$this->fields["category"];
						$old_category_name=str_replace("&nbsp;",$LANG["mailing"][100],getDropdownName("glpi_dropdown_tracking_category",$input["_old_category"]));
						$new_category_name=str_replace("&nbsp;",$LANG["mailing"][100],getDropdownName("glpi_dropdown_tracking_category",$new_category));
						$change_followup_content.=$LANG["mailing"][14].": ".$old_category_name." -> ".$new_category_name."\n";
						$global_mail_change_count++;
					break;
					case "request_type":
						$new_request_type=$this->fields["request_type"];
						$old_request_type_name=getRequestTypeName($input["_old_request_type"]);
						$new_request_type_name=getRequestTypeName($new_request_type);
						$change_followup_content.=$LANG["mailing"][21].": ".$old_request_type_name." -> ".$new_request_type_name."\n";
						$global_mail_change_count++;
					break;
					case "computer" :
					case "device_type":
						if (isset($already_done_computer_device_type_update)){
							break;
						} else {
							$already_done_computer_device_type_update=true;
						}

						$ci=new CommonItem;
						$ci->getFromDB($input["_old_item_type"],$input["_old_item"]);
						$old_item_name=$ci->getName();
						if ($old_item_name=="N/A"||empty($old_item_name))
							$old_item_name=$LANG["mailing"][107];
						$ci->getFromDB($this->fields["device_type"],$this->fields["computer"]);
						$new_item_name=$ci->getName();
						if ($new_item_name=="N/A"||empty($new_item_name))
							$new_item_name=$LANG["mailing"][107];
		
						$change_followup_content.=$LANG["mailing"][17].": $old_item_name -> ".$new_item_name."\n";
						if (in_array("computer",$updates)) $global_mail_change_count++;
						if (in_array("device_type",$updates)) $global_mail_change_count++;
					break;
					case "assign" :
						$new_assign_name=getAssignName($this->fields["assign"],USER_TYPE);
						if ($input["_old_assign"]==0){
							$input["_old_assign_name"]=$LANG["mailing"][105];
						}
						$change_followup_content.=$LANG["mailing"][12].": ".$input["_old_assign_name"]." -> ".$new_assign_name."\n";
						$global_mail_change_count++;
					break;
					case "assign_ent" :
						$new_assign_ent_name=getAssignName($this->fields["assign_ent"],ENTERPRISE_TYPE);
						$change_followup_content.=$LANG["mailing"][12].": ".$input["_old_assign_ent_name"]." -> ".$new_assign_ent_name."\n";
						$global_mail_change_count++;
					break;
					case "assign_group" :
						$new_assign_group_name=getAssignName($this->fields["assign_group"],GROUP_TYPE);
						$change_followup_content.=$LANG["mailing"][12].": ".$input["_old_assign_group_name"]." -> ".$new_assign_group_name."\n";
						$global_mail_change_count++;
					break;
					case "cost_time":
						$change_followup_content.=$LANG["mailing"][42].": ".formatNumber($input["_old_cost_time"])." -> ".formatNumber($this->fields["cost_time"])."\n";
						$global_mail_change_count++;
					break;
					case "cost_fixed" :
						$change_followup_content.=$LANG["mailing"][43].": ".formatNumber($input["_old_cost_fixed"])." -> ".formatNumber($this->fields["cost_fixed"])."\n";
						$global_mail_change_count++;
					break;
					case "cost_material" :
						$change_followup_content.=$LANG["mailing"][44].": ".formatNumber($input["_old_cost_material"])." -> ".formatNumber($this->fields["cost_material"])."\n";
						$global_mail_change_count++;
					break;
					case "emailupdates":
						if ($this->fields["emailupdates"]){
							$change_followup_content.=$LANG["mailing"][101]."\n";
						} else {
							$change_followup_content.=$LANG["mailing"][102]."\n";
						}
						$global_mail_change_count++;
					break;
				}
			}
			if (!in_array("assign",$updates)){
				unset($input["_old_assign"]);
			}
			$mail_send=false;
	
			if (!empty($change_followup_content)){ // Add followup if not empty
				$newinput=array();
				$newinput["contents"]=addslashes($change_followup_content);
				$newinput["author"]=$_SESSION['glpiID'];
				$newinput["private"]=0;
				$newinput["hour"]=$newinput["minute"]=0;
				$newinput["tracking"]=$this->fields["ID"];
				$newinput["type"]="update";
				$newinput["_do_not_check_author"]=true;
				// pass _old_assign if assig changed
				if (isset($input["_old_assign"])){
					$newinput["_old_assign"]=$input["_old_assign"];
				} 
				if (isset($input["status"])&&in_array("status",$updates)&&strstr($input["status"],"old_")){
					$newinput["type"]="finish";
				}
				$fup=new Followup();
				$fup->add($newinput);
				$mail_send=true;
			}
	
			
			// Clean content to mail
			$this->fields["contents"]=stripslashes($this->fields["contents"]);
	
			if (!$mail_send&&count($updates)>$global_mail_change_count&&$CFG_GLPI["mailing"]){
				$user=new User;
				$user->getFromDB($_SESSION["glpiID"]);
				$mailtype="update";
				if ($input["status"]&&in_array("status",$updates)&&strstr($input["status"],"old_")){
					$mailtype="finish";
				} 
				if (isset($input["_old_assign"])){
					$this->fields["_old_assign"]=$input["_old_assign"];
				} 
				$mail = new Mailing($mailtype,$this,$user);
				$mail->send();
			}
		}
	}


	function prepareInputForAdd($input) {
		global $CFG_GLPI,$LANG;
		
		// Check mandatory
		$mandatory_ok=true;

		// Do not check mandatory on auto import (mailgates)
		if (!isset($input['_auto_import'])){

			$_SESSION["helpdeskSaved"]=$input;
	
			if ($CFG_GLPI["ticket_content_mandatory"]&&(!isset($input['contents'])||empty($input['contents']))){
				addMessageAfterRedirect($LANG["tracking"][8]);
				$mandatory_ok=false;
			}
			if ($CFG_GLPI["ticket_title_mandatory"]&&(!isset($input['name'])||empty($input['name']))){
				addMessageAfterRedirect($LANG["help"][40]);
				$mandatory_ok=false;
			}
			if ($CFG_GLPI["ticket_category_mandatory"]&&(!isset($input['category'])||empty($input['category']))){
				addMessageAfterRedirect($LANG["help"][41]);
				$mandatory_ok=false;
			}
			if (isset($input['emailupdates'])&&$input['emailupdates']&&(!isset($input['uemail'])||empty($input['uemail']))){
				addMessageAfterRedirect($LANG["help"][16]);
				$mandatory_ok=false;
			}
	
			if (!$mandatory_ok){
				return false;
			}
		}
		unset($_SESSION["helpdeskSaved"]);

		// Manage helpdesk.html submission type
		unset($input["type"]);

		// No Auto set Import for external source
		if (!isset($input['_auto_import'])){
			if (!isset($input["author"])){
				if (isset($_SESSION["glpiID"])&&$_SESSION["glpiID"]>0)
					$input["author"]=$_SESSION["glpiID"];
			}
		}

		// No Auto set Import for external source
		if (isset($_SESSION["glpiID"])&&!isset($input['_auto_import'])) {
			$input["recipient"]=$_SESSION["glpiID"];
		} else if ($input["author"]) {
			$input["recipient"]=$input["author"];
		}

		if (!isset($input["request_type"])) $input["request_type"]=1;
		if (!isset($input["status"])) $input["status"]="new";
		if (!isset($input["assign"])) $input["assign"]=0;
		
		$user=new User();
		if ($user->getFromDB($input["author"])){
			$input['author_location']=$user->fields['location'];
		}

		if (!isset($input["date"])||empty($input["date"])){
			$input["date"] = $_SESSION["glpi_currenttime"];
		}

		if (isset($input["computer"])&&$input["computer"]==0){
			$input["device_type"]=0;	
		}

		if ($input["device_type"]==0){
			$input["computer"]=0;
		}

		// Auto group define
		if (isset($input["computer"])&&$input["computer"]&&$input["device_type"]){
			$ci=new CommonItem;
			$ci->getFromDB($input["device_type"],$input["computer"]);
			if ($tmp=$ci->getField('FK_groups')){
				$input["FK_group"] = $tmp;
			}
		}

		if ($CFG_GLPI["auto_assign"]&&$input["assign"]==0&&isset($input["computer"])&&$input["computer"]>0&&isset($input["device_type"])&&$input["device_type"]>0){
			$ci=new CommonItem;
			$ci->getFromDB($input["device_type"],$input["computer"]);
			if ($tmp=$ci->getField('tech_num')){
				$input["assign"] = $tmp;
				if ($input["assign"]>0){
					$input["status"] = "assign";
				}
			}
		}

		// Process Business Rules
		$rules=new TrackingBusinessRuleCollection();

		$input=$rules->processAllRules($input,$input);

		if (isset($input["emailupdates"])&&$input["emailupdates"]&&empty($input["uemail"])){
			$user=new User();
			$user->getFromDB($input["author"]);
			$input["uemail"]=$user->fields["email"];
		}

		if (((isset($input["assign"])&&$input["assign"]>0)
				||(isset($input["assign_group"])&&$input["assign_group"]>0)
				||(isset($input["assign_ent"])&&$input["assign_ent"]>0))
			&&$input["status"]=="new"){
			$input["status"] = "assign";
		}

		if (isset($input["hour"])&&isset($input["minute"])){
			$input["realtime"]=$input["hour"]+$input["minute"]/60;
			$input["_hour"]=$input["hour"];
			$input["_minute"]=$input["minute"];
			unset($input["hour"]);
			unset($input["minute"]);
		}

		if (isset($input["status"])&&strstr($input["status"],"old_")){
			if (isset($input["date"])){
				$input["closedate"]=$input["date"];
			} else {
				$input["closedate"]=$_SESSION["glpi_currenttime"];
			}
		} 

		if (empty($input["name"])) {
			$input["name"]=preg_replace('/\r\n/',' ',$input['contents']);
			$input["name"]=preg_replace('/\n/',' ',$input['name']);
			$input["name"]=substr($input['name'],0,70);
		}

		return $input;
	}

	function post_addItem($newID,$input) {
		global $LANG,$CFG_GLPI;

		// add Document if exists
		if (isset($_FILES['multiple']) ) {
			unset($_FILES['multiple']);
			$TMPFILE = $_FILES;
		} else {
			$TMPFILE = array( $_FILES );
		}
		foreach ($TMPFILE as $_FILES) {
			if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
				$input2=array();
				$input2["name"]=$LANG["tracking"][24]." $newID";
				$input2["FK_tracking"]=$newID;
				$input2["FK_entities"]=$input["FK_entities"];
				$input2["rubrique"]=$CFG_GLPI["default_rubdoc_tracking"];
				$input2["_only_if_upload_succeed"]=1;
				$doc=new Document();
				if ($docID=$doc->add($input2))
					addDeviceDocument($docID,TRACKING_TYPE,$newID);
			}
		}

		// Log this event
		logEvent($newID,"tracking",4,"tracking",getUserName($input["author"])." ".$LANG["log"][20]);

		$already_mail=false;
		if (((isset($input["_followup"])&&is_array($input["_followup"])&&strlen($input["_followup"]['contents']))||isset($input["plan"]))
			||(isset($input["_hour"])&&isset($input["_minute"])&&isset($input["realtime"])&&$input["realtime"]>0)){

			$fup=new Followup();
			$type="new";
			if (isset($this->fields["status"])&&strstr($this->fields["status"],"old_")) $type="finish";
			$toadd=array("type"=>$type,"tracking"=>$newID);
			if (isset($input["_hour"])) $toadd["hour"]=$input["_hour"];
			if (isset($input["_minute"])) $toadd["minute"]=$input["_minute"];
			if (isset($input["_followup"]['contents'])&&strlen($input["_followup"]['contents'])) $toadd["contents"]=$input["_followup"]['contents'];
			if (isset($input["_followup"]['private'])) $toadd["private"]=$input["_followup"]['private'];
			if (isset($input["plan"])) $toadd["plan"]=$input["plan"];
			$fup->add($toadd);
			$already_mail=true;
		}

		// Processing Email
		if ($CFG_GLPI["mailing"]&&!$already_mail)
		{
			$user=new User();
			$user->getFromDB($input["author"]);

			$this->fields=stripslashes_deep($this->fields);
			$type="new";
			if (isset($this->fields["status"])&&strstr($this->fields["status"],"old_")) $type="finish";
			$mail = new Mailing($type,$this,$user);
			$mail->send();
		}

	}

	// SPECIFIC FUNCTIONS
	/**
	 * Number of followups of the ticket
	 *
	 *@param $with_private boolean : true : all ticket / false : only public ones
	 *@return followup count
	**/
	function numberOfFollowups($with_private=1){
		global $DB;
		$RESTRICT="";
		if ($with_private!=1) $RESTRICT = " AND private='0'";
		// Set number of followups
		$query = "SELECT count(*) FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."' $RESTRICT";
		$result = $DB->query($query);
		return $DB->result($result,0,0);

	}

	/**
	 * Update realtime of the ticket based on realtim of the followups
	 *
	 *@param $ID ID of the ticket
	 *@return boolean : success
	**/
	function updateRealTime($ID) {
		// update Status of Job

		global $DB;
		$query = "SELECT SUM(realtime) FROM glpi_followups WHERE tracking = '$ID'";
		if ($result = $DB->query($query)) {
			$sum=$DB->result($result,0,0);
			if (is_null($sum)) $sum=0;
			$query2="UPDATE glpi_tracking SET realtime='".$sum."' WHERE ID='$ID'";
			$DB->query($query2);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update date mod of the ticket
	 *
	 *@param $ID ID of the ticket
	**/
	function updateDateMod($ID) {
		global $DB;
		$query="UPDATE glpi_tracking SET date_mod='".$_SESSION["glpi_currenttime"]."' WHERE ID='$ID'";
		$DB->query($query);
	}

	/**
	 * Get text describing Followups
	 * 
	* @param $format text or html
	* @param $sendprivate true if both public and private followups have to be printed in the email
	 */
	function textFollowups($format="text", $sendprivate=false) {
		// get the last followup for this job and give its contents as
		global $DB,$LANG;

		if (isset($this->fields["ID"])){
			$query = "SELECT * FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."' ".($sendprivate?"":" AND private = '0' ")." ORDER by date DESC";
			$result=$DB->query($query);
			$nbfollow=$DB->numrows($result);
			if($format=="html"){
				$message = "<div class='description'><strong>".$LANG["mailing"][4]." : $nbfollow<br></strong></div>\n";

				if ($nbfollow>0){
					$fup=new Followup();
					while ($data=$DB->fetch_array($result)){
						$fup->getFromDB($data['ID']);
						$message .= "<strong>[ ".convDateTime($fup->fields["date"])." ] ".($fup->fields["private"]?"<i>".$LANG["common"][77]."</i>":"")."</strong>\n";
						$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["job"][4].":</span> ".$fup->getAuthorName()."\n";
						$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][3]."</span>:<br>".str_replace("\n","<br>",$fup->fields["contents"])."\n";
						if ($fup->fields["realtime"]>0)
							$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][104].":</span> ".getRealtime($fup->fields["realtime"])."\n";

						$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][25]."</span> ";
						$query2="SELECT * FROM glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$DB->query($query2);
						if ($DB->numrows($result2)==0)
							$message.=$LANG["job"][32]."\n";
						else {
							$data2=$DB->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
						}

						$message.=$LANG["mailing"][0]."\n";	
					}	
				}
			}else{ // text format
				$message = $LANG["mailing"][1]."\n".$LANG["mailing"][4]." : $nbfollow\n".$LANG["mailing"][1]."\n";

				if ($nbfollow>0){
					$fup=new Followup();
					while ($data=$DB->fetch_array($result)){
						$fup->getFromDB($data['ID']);
						$message .= "[ ".convDateTime($fup->fields["date"])." ]".($fup->fields["private"]?"\t".$LANG["common"][77]:"")."\n";
						$message .= $LANG["job"][4].": ".$fup->getAuthorName()."\n";
						$message .= $LANG["mailing"][3].":\n".$fup->fields["contents"]."\n";
						if ($fup->fields["realtime"]>0)
							$message .= $LANG["mailing"][104].": ".getRealtime($fup->fields["realtime"])."\n";

						$message.=$LANG["mailing"][25]." ";
						$query2="SELECT * FROM glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$DB->query($query2);
						if ($DB->numrows($result2)==0)
							$message.=$LANG["job"][32]."\n";
						else {
							$data2=$DB->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
						}

						$message.=$LANG["mailing"][0]."\n";	
					}	
				}


			}
			return $message;
		} else return "";
	}

	/**
	 * Get text describing ticket
	 * 
	* @param $format text or html
	 */
	function textDescription($format="text"){
		global $DB,$LANG;


		$name=$LANG["help"][30];
		$contact='';
		$tech='';
		$name=$this->hardwaredatas->getType()." ".$this->hardwaredatas->getName();
		if ($this->hardwaredatas->obj!=NULL){
			if (isset($this->hardwaredatas->obj->fields["serial"])&&!empty($this->hardwaredatas->obj->fields["serial"])){
				$name.=" - #".$this->hardwaredatas->obj->fields["serial"];
			}
			if (isset($this->hardwaredatas->obj->fields["model"])&&$this->hardwaredatas->obj->fields["model"]>0){
				$add="";
				switch ($this->fields['device_type']){
					case MONITOR_TYPE:
						$add='_monitors';
						break;
					case NETWORKING_TYPE:
						$add='_networking';
						break;
					case PERIPHERAL_TYPE:
						$add='_peripherals';
						break;
					case PHONE_TYPE:
						$add='_phones';
						break;
					case PRINTER_TYPE:
						$add='_printers';
						break;
				}
				$name.=" - ".getDropdownName("glpi_dropdown_model".$add,$this->hardwaredatas->obj->fields["model"]);
			}
			if (isset($this->hardwaredatas->obj->fields["tech_num"])&&$this->hardwaredatas->obj->fields["tech_num"]>0){
					$tech=getUserName($this->hardwaredatas->obj->fields["tech_num"]);
			}
			if (isset($this->hardwaredatas->obj->fields["contact"])){
				$contact=$this->hardwaredatas->obj->fields["contact"];
			}
			if (isset($this->hardwaredatas->obj->fields["FK_users"])){
				$contact=getUserName($this->hardwaredatas->obj->fields["FK_users"]);
			}
			if (isset($this->hardwaredatas->obj->fields["FK_groups"])){
				if (!empty($contact)) $contact.=" / ";
					$contact.=getDropdownName("glpi_groups",$this->hardwaredatas->obj->fields["FK_groups"]);
			}
		}

		if($format=="html"){
			$message= "<html><head> <style type=\"text/css\">";
			$message.=".description{ color: inherit; background: #ebebeb; border-style: solid; border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }";
			$message.=" </style></head><body>";

			$message.="<div class='description'><strong>".$LANG["mailing"][5]."</strong></div>\n";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][57].":</span> ".$this->fields["name"]."\n";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$LANG["mailing"][108];
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["job"][4].":</span> ".$author."\n";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["search"][8].":</span> ".convDateTime($this->fields["date"])."\n";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["job"][44].":</span> ".getRequestTypeName($this->fields["request_type"])."\n";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["mailing"][7].":</span> ".$name."\n";
			if (!empty($tech))
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["common"][10].":</span> ".$tech."\n";
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["joblist"][0].":</span> ".getStatusName($this->fields["status"])."\n";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			$assign_group="";
			if (isset($this->fields["assign_group"])){
				$assign_group=getAssignName($this->fields["assign_group"],GROUP_TYPE);
			}
			if ($assign=="[Nobody]"){
				if (!empty($assign_group)){
					$assign=$assign_group;
				} else {
					$assign=$LANG["mailing"][105];
				}
			} else {
				if (!empty($assign_group)){
					$assign.=" / ".$assign_group;
				}
			}
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][8].":</span> ".$assign."\n";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["joblist"][2].":</span> ".getPriorityName($this->fields["priority"])."\n";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE&&!empty($contact))
				$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][18].":</span> ".$contact."\n";
			if (isset($this->fields["emailupdates"]) && $this->fields["emailupdates"]){
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][103].":</span> ".$LANG["choice"][1]."\n";
			} else {
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["mailing"][103].":</span> ".$LANG["choice"][0]."\n";
			}

			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$LANG["common"][36].":</span> ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.=$LANG["mailing"][100];
			$message.= "\n";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $LANG["mailing"][3].":</span><br>".str_replace("\n","<br>",$this->fields["contents"])."<br>\n";	

		}else{ //text format
			$message = $LANG["mailing"][1]."\n*".$LANG["mailing"][5]."*\n".$LANG["mailing"][1]."\n";
			
			$message.=mailRow($LANG["common"][57],$this->fields["name"]);
			$author=$this->getAuthorName();
			if (empty($author)) $author=$LANG["mailing"][108];
			$message.=mailRow($LANG["job"][4],$author);
			$message.=mailRow($LANG["search"][8],convDateTime($this->fields["date"]));
			$message.=mailRow($LANG["job"][44],getRequestTypeName($this->fields["request_type"]));
			$message.=mailRow($LANG["mailing"][7],$name);
			if (!empty($tech))
				$message.= mailRow($LANG["common"][10],$tech);
			$message.= mailRow($LANG["joblist"][0],getStatusName($this->fields["status"]));
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			$assign_group="";
			if (isset($this->fields["assign_group"])){
				$assign_group=getAssignName($this->fields["assign_group"],GROUP_TYPE);
			}
			if ($assign=="[Nobody]"){
                                if (!empty($assign_group)){
                                        $assign=$assign_group;
                                } else {
                                        $assign=$LANG["mailing"][105];
                                }
                        } else {
				if (!empty($assign_group)){
	                                $assign.=" / ".$assign_group;
				}
                        }

			$message.= mailRow($LANG["mailing"][8],$assign);
			$message.= mailRow($LANG["joblist"][2],getPriorityName($this->fields["priority"]));
			if ($this->fields["device_type"]!=SOFTWARE_TYPE&&!empty($contact))
				$message.= mailRow($LANG["common"][18],$contact);
			if (isset($this->fields["emailupdates"]) && $this->fields["emailupdates"]){
				$message.=mailRow($LANG["mailing"][103],$LANG["choice"][1]);
			} else {
				$message.=mailRow($LANG["mailing"][103],$LANG["choice"][0]);
			}

			
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= mailRow($LANG["common"][36],getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]));
			} else $message.=mailRow($LANG["common"][36],$LANG["mailing"][100]);
			$message.= "--\n";
			$message.= $LANG["mailing"][3]." : \n".$this->fields["contents"]."\n";	
			$message.="\n\n";

		}

		return $message;
	}


	/**
	 * Get author name
	 * 
	 * @param $link boolean with link ?
	 * @return string author name
	 */
	function getAuthorName($link=0){
		return getUserName($this->fields["author"],$link);
	}

	/**
	 * Is the current user have right to add followups to the current ticket ?
	 * 
	 * @return boolean
	 */
	function canAddFollowups(){
		return ((haveRight("comment_ticket","1")&&$this->fields["author"]==$_SESSION["glpiID"])
			||haveRight("comment_all_ticket","1")
			||(isset($_SESSION["glpiID"])&&$this->fields["assign"]==$_SESSION["glpiID"])
			||(isset($_SESSION["glpigroups"])&&in_array($this->fields["assign_group"],$_SESSION['glpigroups']))
			);
	}
	/**
	 * Is the current user have right to show the current ticket ?
	 * 
	 * @return boolean
	 */
	function canView(){
		return (
			haveRight("show_all_ticket","1")
			|| (isset($_SESSION["glpiID"])&&$this->fields["author"]==$_SESSION["glpiID"])
			|| (haveRight("show_group_ticket",'1')&&isset($_SESSION["glpigroups"])&&in_array($this->fields["FK_group"],$_SESSION["glpigroups"]))
			|| (haveRight("show_assign_ticket",'1')&&(
				(isset($_SESSION["glpiID"])&&$this->fields["assign"]==$_SESSION["glpiID"])
				||(isset($_SESSION["glpigroups"])&&in_array($this->fields["assign_group"],$_SESSION["glpigroups"]))
				)
			)
			);
	}

}

/// Followup class
class Followup  extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_followups";
		$this->type=FOLLOWUP_TYPE;
	}

	function cleanDBonPurge($ID) {
		global $DB;
		$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '$ID'";
		$DB->query($querydel);				
	}

	function post_deleteFromDB($ID){
		$job=new Job();
		$job->updateRealtime($this->fields['tracking']);
		$job->updateDateMod($this->fields["tracking"]);		
	}


	function prepareInputForUpdate($input) {

		$input["realtime"]=$input["hour"]+$input["minute"]/60;
		if (isset($_SESSION["glpiID"])){
			$input["author"]=$_SESSION["glpiID"];
		}

		if (isset($input["plan"])){
			$input["_plan"]=$input["plan"];
			unset($input["plan"]);
		}
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $CFG_GLPI;

		$job=new Job;
		$mailsend=false;

		if ($job->getFromDB($input["tracking"])){
			$job->updateDateMod($input["tracking"]);
			
			if (count($updates)){
		
				if ($CFG_GLPI["mailing"]&&
				(in_array("contents",$updates)||isset($input['_need_send_mail']))){
					$user=new User;
					$user->getFromDB($_SESSION["glpiID"]);
					$mail = new Mailing("followup",$job,$user,(isset($input["private"]) && $input["private"]));
					$mail->send();
					$mailsend=true;
				}
		
				if (in_array("realtime",$updates)) {
					$job->updateRealTime($input["tracking"]);
				}
			}
		}
		
		if (isset($input["_plan"])){

			$pt=new PlanningTracking();
			// Update case
			if (isset($input["_plan"]["ID"])){
				$input["_plan"]['id_followup']=$input["ID"];
				$input["_plan"]['id_tracking']=$input['tracking'];
				$input["_plan"]['_nomail']=$mailsend;

				if (!$pt->update($input["_plan"])){
					return false;
				}
				unset($input["_plan"]);
			// Add case
			} else {
				$input["_plan"]['id_followup']=$input["ID"];
				$input["_plan"]['id_tracking']=$input['tracking'];
				$input["_plan"]['_nomail']=1;

				if (!$pt->add($input["_plan"])){
					return false;
				}
				unset($input["_plan"]);
				$input['_need_send_mail']=true;
			}
		}
	}

	function prepareInputForAdd($input) {

		$input["_isadmin"]=haveRight("comment_all_ticket","1");

		$input["_job"]=new Job;
		if ($input["_job"]->getFromDB($input["tracking"])){
			// Security to add unauthorized followups
			if (!isset($input['_do_not_check_author'])
			&&$input["_job"]->fields["author"]!=$_SESSION["glpiID"]
			&&!$input["_job"]->canAddFollowups()) {
				return false;
			}
		} else {
			return false;
		}

		// Pass old assign From Job in case of assign change
		if (isset($input["_old_assign"])){
			$input["_job"]->fields["_old_assign"]=$input["_old_assign"];
		}


		if (!isset($input["type"])) $input["type"]="followup";
		$input["_type"]=$input["type"];
		unset($input["type"]);

		$input['_close']=0;
		unset($input["add"]);

		if (!isset($input["author"]))
			$input["author"]=$_SESSION["glpiID"];

		if ($input["_isadmin"]&&$input["_type"]!="update"){
			if (isset($input['plan'])){
				$input['_plan']=$input['plan'];
				unset($input['plan']);
			}	
			if (isset($input["add_close"])) $input['_close']=1;
			unset($input["add_close"]);

			if (isset($input["add_reopen"])) $input['_reopen']=1;
			unset($input["add_reopen"]);

			if (!isset($input["hour"])){
				$input["hour"]=0;
			}
			if (!isset($input["minute"])){
				$input["minute"]=0;
			}
			if ($input["hour"]>0||$input["minute"]>0)
				$input["realtime"]=$input["hour"]+$input["minute"]/60;
		}

		unset($input["minute"]);
		unset($input["hour"]);

		$input["date"] = $_SESSION["glpi_currenttime"];

		return $input;
	}

	function post_addItem($newID,$input) {
		global $CFG_GLPI;

		$input["_job"]->updateDateMod($input["tracking"]);

		if (isset($input["realtime"])&&$input["realtime"]>0) {
			$input["_job"]->updateRealTime($input["tracking"]);
		}


		if ($input["_isadmin"]&&$input["_type"]!="update"){

			if (isset($input["_plan"])){
				$input["_plan"]['id_followup']=$newID;
				$input["_plan"]['id_tracking']=$input['tracking'];
				$input["_plan"]['_nomail']=1;
				$pt=new PlanningTracking();

				if (!$pt->add($input["_plan"])){
					return false;
				}
			}


			if ($input["_close"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
				$updates[]="status";
				$updates[]="closedate";
				$input["_job"]->fields["status"]="old_done";
				$input["_job"]->fields["closedate"] = $_SESSION["glpi_currenttime"];
				$input["_job"]->updateInDB($updates);
			}

			if (isset($input["_reopen"])&&$input["_reopen"]){
				$updates[]="status";
				if ($input["_job"]->fields["assign"]>0 || $input["_job"]->fields["assign_group"]>0 
					|| $input["_job"]->fields["assign_ent"]>0){
					$input["_job"]->fields["status"]="assign";
				} else {
					$input["_job"]->fields["status"]="new";
				}
				$input["_job"]->updateInDB($updates);
			}

		}

		if ($CFG_GLPI["mailing"]){
			if ($input["_close"]) $input["_type"]="finish";
			$user=new User;
			if (!isset($input['_auto_import'])&&isset($_SESSION["glpiID"])){ 
				$user->getFromDB($_SESSION["glpiID"]);
			}
			$mail = new Mailing($input["_type"],$input["_job"],$user,
						(isset($input["private"])&&$input["private"]));
			$mail->send();
		}
	}


	// SPECIFIC FUNCTIONS

	/**
	 * Get the author name of the followup
	 * @param $link insert link ?
	 *
	 *@return string of the author name
	**/
	function getAuthorName($link=0){
		return getUserName($this->fields["author"],$link);
	}	

}



?>
