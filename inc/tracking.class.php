<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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


// Tracking Classes

class Job extends CommonDBTM{

	var $fields	= array();
	var $updates	= array();
	var $computername	= "";
	var $computerfound	= 0;

	function Job(){
		$this->table="glpi_tracking";
		$this->type=TRACKING_TYPE;
	}



	function getFromDBwithData ($ID,$purecontent) {

		global $db,$lang;

		if ($this->getFromDB($ID)){

			if (!$purecontent) {
				$this->fields["contents"] = nl2br(preg_replace("/\r\n\r\n/","\r\n",$this->fields["contents"]));
			}
			$m= new CommonItem;
			if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
				$this->computername=$m->getName();
			} else $this->computername='';

			if ($this->computername==""){
				if ($this->fields["device_type"]==0) $this->computername = $lang["help"][30];
				else $this->computername = "N/A";
				$this->computerfound=0;				
			} else 	$this->computerfound=1;	

			return true;
		} else {
			return false;
		}
	}


	function cleanDBonPurge($ID) {
		global $db;

		$query="SELECT ID FROM glpi_followups WHERE tracking = '$ID'";
		$result=$db->query($query);
		if ($db->numrows($result)>0)
			while ($data=$db->fetch_array($result)){
				$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '".$data['ID']."'";
				$db->query($querydel);				
			}
		$query1="delete from glpi_followups where tracking = '$ID'";
		$db->query($query1);

	}

	function prepareInputForUpdate($input) {
		global $lang,$cfg_glpi;
		// Security checks
		if (!haveRight("update_ticket","1")){
			if (haveRight("assign_ticket","1")){
				$ret["ID"]=$input["ID"];
				$ret["assign"]=$input["assign"];
				$ret["assign_ent"]=$input["assign_ent"];
				$input=$ret;
			} else if (haveRight("steal_ticket","1")&&$input["assign"]==$_SESSION["glpiID"]){
				$ret["ID"]=$input["ID"];
				$ret["assign"]=$input["assign"];
				$input=$ret;
			} else { // Default case can only update contents if no followups already added
				$ret["ID"]=$input["ID"];
				if (isset($input["contents"]))
					$ret["contents"]=$input["contents"];
				$input=$ret;
			}

		}

		if (isset($input["item"])&& $input["item"]!=0){
			$input["computer"]=$input["item"];
			$input["device_type"]=$input["type"];

			if ($this->fields['FK_group']){
				$ci=new CommonItem;
				$ci->getFromDB($input["device_type"],$input["computer"]);
				if (isset($ci->obj->fields['FK_groups'])&&$ci->obj->fields['FK_groups']!=0){
					$input["FK_group"] = $ci->obj->fields['FK_groups'];
				}
			}
		} else if (isset($input["type"])&&$input["type"]!=0)
			$input["device_type"]=0;

		// add Document if exists
		if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
			$input2=array();
			$input2["name"]=$lang["tracking"][24]." ".$input["ID"];
			$input2["FK_tracking"]=$input["ID"];
			$input2["_only_if_upload_succeed"]=1;
			$doc=new Document();
			if ($docID=$doc->add($input2)){
				addDeviceDocument($docID,TRACKING_TYPE,$input["ID"]);
			}
		}
		if (isset($input["document"])&&$input["document"]>0){
			addDeviceDocument($input["document"],TRACKING_TYPE,$input["ID"]);
			unset($input["document"]);
		}

		// Old values for add followup in change
		if ($cfg_glpi["followup_on_update_ticket"]){
			$this->getFromDB($input["ID"]);
			$input["_old_assign_name"]=getAssignName($this->fields["assign"],USER_TYPE);
			$this->fields["_old_assign"]=$this->fields["assign"];
			$input["_old_assign_ent_name"]=getAssignName($this->fields["assign_ent"],ENTERPRISE_TYPE);
			$input["_old_category"]=$this->fields["category"];
			$input["_old_item"]=$this->fields["computer"];
			$input["_old_item_type"]=$this->fields["device_type"];
			$input["_old_author"]=$this->fields["author"];
			$input["_old_group"]=$this->fields["FK_group"];
			$input["_old_priority"]=$this->fields["priority"];
			$input["_old_status"]=$this->fields["status"];
		}


		return $input;
	}

	function pre_updateInDB($input,$updates) {
		if (((in_array("assign",$updates)&&$input["assign"]>0)||(in_array("assign_ent",$updates)&&$input["assign_ent"]>0))&&$this->fields["status"]=="new"){
			$updates[]="status";
			$this->fields["status"]="assign";
		}
		if (isset($input["status"])){
			if ($input["assign_ent"]==0&&$input["assign"]==0&&$input["status"]=="assign"){
				$updates[]="status";
				$this->fields["status"]="new";
			}

			if (in_array("status",$updates)&&ereg("old_",$input["status"])){
				$updates[]="closedate";
				$this->fields["closedate"]=date("Y-m-d H:i:s");
			}
		}

		if (in_array("author",$updates)){
			$user=new User;
			$user->getfromDB($input["author"]);
			if (!empty($user->fields["email"])){
				$updates[]="uemail";
				$this->fields["uemail"]=$user->fields["email"];
			}
		}

		if (!haveRight("update_ticket","1")){
			if (haveRight("assign_ticket","1"))
				$updates=array_intersect($updates,array("assign","assign_ent"));
			else if (haveRight("steal_ticket","1")){
				if ($input["assign"]==$_SESSION["glpiID"])
					$updates=array_intersect($updates,array("assign"));
				else $updates=array();
			} else if ($this->fields["author"]==$_SESSION["glpiID"]&&$this->numberOfFollowups()==0){ // Helpdesk case
				$updates=array_intersect($updates,array("contents"));
			}
		}

		return array($input,$updates);
	}

	function post_updateItem($input,$updates,$history=1) {
		global $cfg_glpi,$lang;

		// New values for add followup in change
		$change_followup_content="";
		$global_mail_change_count=0;

		if ($cfg_glpi["followup_on_update_ticket"]){
			if (in_array("assign",$updates)){
				$new_assign_name=getAssignName($this->fields["assign"],USER_TYPE);
				if ($input["_old_assign_name"]=="[Nobody]")
					$input["_old_assign_name"]=$lang["mailing"][105];
				$change_followup_content.=$lang["mailing"][12].": ".$input["_old_assign_name"]." -> ".$new_assign_name."\n";
				$global_mail_change_count++;
			} else unset($this->fields["_old_assign"]);

			if (in_array("assign_ent",$updates)){
				$new_assign_ent_name=getAssignName($this->fields["assign_ent"],ENTERPRISE_TYPE);
				$change_followup_content.=$lang["mailing"][12].": ".$input["_old_assign_ent_name"]." -> ".$new_assign_ent_name."\n";
				$global_mail_change_count++;
			}
			if (in_array("category",$updates)){
				$new_category=$this->fields["category"];
				$old_category_name=ereg_replace("&nbsp;",$lang["mailing"][100],getDropdownName("glpi_dropdown_tracking_category",$input["_old_category"]));
				$new_category_name=ereg_replace("&nbsp;",$lang["mailing"][100],getDropdownName("glpi_dropdown_tracking_category",$new_category));
				$change_followup_content.=$lang["mailing"][14].": ".$old_category_name." -> ".$new_category_name."\n";
				$global_mail_change_count++;
			}
			if (in_array("computer",$updates)||in_array("device_type",$updates)){	
				$ci=new CommonItem;
				$ci->getfromDB($input["_old_item_type"],$input["_old_item"]);
				$old_item_name=$ci->getName();
				if ($old_item_name=="N/A"||empty($old_item_name))
					$old_item_name=$lang["mailing"][107];
				$ci->getfromDB($this->fields["device_type"],$this->fields["computer"]);
				$new_item_name=$ci->getName();
				if ($new_item_name=="N/A"||empty($new_item_name))
					$new_item_name=$lang["mailing"][107];

				$change_followup_content.=$lang["mailing"][17].": $old_item_name -> ".$new_item_name."\n";
				if (in_array("computer",$updates)) $global_mail_change_count++;
				if (in_array("device_type",$updates)) $global_mail_change_count++;
			}
			if (in_array("author",$updates)){
				$author=new User;
				$author->getFromDB($input["_old_author"]);
				$old_author_name=$author->getName();
				$author->getFromDB($this->fields["author"]);
				$new_author_name=$author->getName();
				$change_followup_content.=$lang["mailing"][18].": $old_author_name -> ".$new_author_name."\n";

				$global_mail_change_count++;
			}
			if (in_array("FK_group",$updates)){
				$new_group=$this->fields["FK_group"];
				$old_group_name=ereg_replace("&nbsp;",$lang["mailing"][109],getDropdownName("glpi_groups",$input["_old_group"]));
				$new_group_name=ereg_replace("&nbsp;",$lang["mailing"][109],getDropdownName("glpi_groups",$new_group));
				$change_followup_content.=$lang["mailing"][20].": ".$old_group_name." -> ".$new_group_name."\n";
				$global_mail_change_count++;
			}

			if (in_array("priority",$updates)){
				$new_priority=$this->fields["priority"];
				$change_followup_content.=$lang["mailing"][15].": ".getPriorityName($input["_old_priority"])." -> ".getPriorityName($new_priority)."\n";
				$global_mail_change_count++;		
			}
			if (in_array("status",$updates)){
				$new_status=$this->fields["status"];
				$change_followup_content.=$lang["mailing"][27].": ".getStatusName($input["_old_status"])." -> ".getStatusName($new_status)."\n";

				if (ereg("old_",$new_status))
					$newinput["add_close"]="add_close";
				if (in_array("closedate",$updates))	
					$global_mail_change_count++; // Manage closedate

				$global_mail_change_count++;
			}
			if (in_array("emailupdates",$updates)){
				if ($this->fields["emailupdates"]=="yes")
					$change_followup_content.=$lang["mailing"][101]."\n";
				else if ($this->fields["emailupdates"]=="no")
					$change_followup_content.=$lang["mailing"][102]."\n";
				$global_mail_change_count++;
			}
		}

		$mail_send=false;

		if (!empty($change_followup_content)){ // Add followup if not empty

			$newinput["contents"]=addslashes($change_followup_content);
			$newinput["author"]=$_SESSION['glpiID'];
			$newinput["private"]=$newinput["hour"]=$newinput["minute"]=0;
			$newinput["tracking"]=$this->fields["ID"];
			$newinput["type"]="update";
			// pass _old_assign if assig changed
			if (isset($this->fields["_old_assign"]))
				$newinput["_old_assign"]=$this->fields["_old_assign"];
			if (in_array("status",$updates)&&ereg("old_",$input["status"]))
				$newinput["type"]="finish";
			$fup=new Followup();
			$fup->add($newinput);
			$mail_send=true;
		}

		// Clean content to mail
		$this->fields["contents"]=stripslashes($this->fields["contents"]);

		if (!$mail_send&&count($updates)>$global_mail_change_count&&$cfg_glpi["mailing"]){
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mailtype="update";
			if (in_array("status",$updates)&&ereg("old_",$input["status"]))
				$mailtype="finish";
			else $mail_send++;

			$mail = new Mailing($mailtype,$this,$user);
			$mail->send();
		}

	}


	function prepareInputForAdd($input) {
		global $cfg_glpi;

		// Manage helpdesk.html submission type
		unset($input["type"]);

		if (!isset($input["request_type"])) $input["request_type"]=1;
		if (!isset($input["status"])) $input["status"]="new";
		if (!isset($input["assign"])) $input["assign"]=0;

		if (!isset($input["author"])){
			if (isset($_SESSION["glpiID"])&&$_SESSION["glpiID"]>0)
				$input["author"]=$_SESSION["glpiID"];
			else $input["author"]=1; // Helpdesk injector
		}
		if ($input["assign"]>0&&$input["status"]=="new")
			$input["status"] = "assign";

		if (isset($input["computer"])&&$input["computer"]==0)
			$input["device_type"]=0;	

		if ($input["device_type"]==0)
			$input["computer"]=0;

		if ($input["computer"]&&$input["device_type"]){
			$ci=new CommonItem;
			$ci->getFromDB($input["device_type"],$input["computer"]);
			if (isset($ci->obj->fields['FK_groups'])&&$ci->obj->fields['FK_groups']!=0){
				$input["FK_group"] = $ci->obj->fields['FK_groups'];
			}
		}

		if (isset($input["emailupdates"])&&$input["emailupdates"]=="yes"&&empty($input["uemail"])){
			$user=new User();
			$user->getFromDB($input["author"]);
			$input["uemail"]=$user->fields["email"];
		}

		if ($cfg_glpi["auto_assign"]&&$input["assign"]==0&&isset($input["computer"])&&$input["computer"]>0&&isset($input["device_type"])&&$input["device_type"]>0){
			$ci=new CommonItem;
			$ci->getFromDB($input["device_type"],$input["computer"]);
			if (isset($ci->obj->fields['tech_num'])&&$ci->obj->fields['tech_num']!=0){
				$input["assign"] = $ci->obj->fields['tech_num'];
				if ($input["assign"]>0)
					$input["status"] = "assign";
			}
		}

		if (isset($input["hour"])&&isset($input["minute"])){
			$input["realtime"]=$input["hour"]+$input["minute"]/60;
			$input["_hour"]=$input["hour"];
			$input["_minute"]=$input["minute"];
			unset($input["hour"]);
			unset($input["minute"]);
		}

		// Add and close for central helpdesk
		if (isset($input["add_close"])){
			$input["status"]="old_done";
			unset($input["add_close"]);
		}

		if (!isset($input["date"]))
			$input["date"] = date("Y-m-d H:i:s");

		if (strstr($input["status"],"old_"))
			$input["closedate"] = $input["date"];

		return $input;
	}

	function postAddItem($newID,$input) {
		global $lang,$cfg_glpi;

		// add Document if exists
		if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
			$input2=array();
			$input2["name"]=$lang["tracking"][24]." $newID";
			$input2["FK_tracking"]=$newID;
			$input2["_only_if_upload_succeed"]=1;
			$doc=new Document();
			if ($docID=$doc->add($input2))
				addDeviceDocument($docID,TRACKING_TYPE,$newID);
		}

		// Log this event
		logEvent($newID,"tracking",4,"tracking",getUserName($input["author"])." ".$lang["log"][20]);

		$already_mail=false;
		if ((isset($input["_followup"])&&strlen($input["_followup"]))||(isset($input["_hour"])&&isset($input["_minute"])&&isset($input["realtime"])&&$input["realtime"]>0)){

			$fup=new Followup();
			$type="new";
			if (isset($this->fields["status"])&&ereg("old_",$this->fields["status"])) $type="finish";
			$toadd=array("type"=>$type,"tracking"=>$newID);
			if (isset($input["_hour"])) $toadd["hour"]=$input["_hour"];
			if (isset($input["_minute"])) $toadd["minute"]=$input["_minute"];
			if (isset($input["_followup"])&&strlen($input["_followup"])) $toadd["contents"]=$input["_followup"];
			if ($input["assign"]>0)
				$toadd["author"]=$input["assign"];

			$fup->add($toadd);
			$already_mail=true;
		}

		// Processing Email
		if ($cfg_glpi["mailing"]&&!$already_mail)
		{
			$user=new User();
			$user->getFromDB($input["author"]);

			$this->fields=stripslashes_deep($this->fields);
			$type="new";
			if (isset($this->fields["status"])&&ereg("old_",$this->fields["status"])) $type="finish";
			$mail = new Mailing($type,$this,$user);
			$mail->send();
		}

	}

	// SPECIFIC FUNCTIONS

	function numberOfFollowups($with_private=1){
		global $db;
		$RESTRICT="";
		if ($with_private!=1) $RESTRICT = " AND private='0'";
		// Set number of followups
		$query = "SELECT count(*) FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."' $RESTRICT";
		$result = $db->query($query);
		return $db->result($result,0,0);

	}

	function updateRealTime() {
		// update Status of Job

		global $db;
		$query = "SELECT SUM(realtime) FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."'";
		if ($result = $db->query($query)) {
			$sum=$db->result($result,0,0);
			if (is_null($sum)) $sum=0;
			$query2="UPDATE glpi_tracking SET realtime='".$sum."' WHERE ID='".$this->fields["ID"]."'";
			$db->query($query2);
			return true;
		} else {
			return false;
		}
	}


	function textFollowups($format="text") {
		// get the last followup for this job and give its contents as
		global $db,$lang;

		if (isset($this->fields["ID"])){
			$query = "SELECT * FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."' AND private = '0' ORDER by date DESC";
			$result=$db->query($query);
			$nbfollow=$db->numrows($result);
			if($format=="html"){
				$message = "<div class='description'><strong>".$lang["mailing"][4]." : $nbfollow<br></strong></div><br>";

				if ($nbfollow>0){
					$fup=new Followup();
					while ($data=$db->fetch_array($result)){
						$fup->getfromDB($data['ID']);
						$message .= "<strong>[ ".convDateTime($fup->fields["date"])." ]</strong><br>";
						$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["common"][37].":</span> ".$fup->getAuthorName()."<br>";
						$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][3].":</span><br>".nl2br($fup->fields["contents"])."<br>";
						if ($fup->fields["realtime"]>0)
							$message .= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][104].":</span> ".getRealtime($fup->fields["realtime"])."<br>";

						$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][25].":</span> ";
						$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$db->query($query2);
						if ($db->numrows($result2)==0)
							$message.=$lang["job"][32]."<br>";
						else {
							$data2=$db->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."<br>";
						}

						$message.=$lang["mailing"][0]."<br>";	
					}	
				}
			}else{ // text format
				$message = $lang["mailing"][1]."\n".$lang["mailing"][4]." : $nbfollow\n".$lang["mailing"][1]."\n";

				if ($nbfollow>0){
					$fup=new Followup();
					while ($data=$db->fetch_array($result)){
						$fup->getfromDB($data['ID']);
						$message .= "[ ".convDateTime($fup->fields["date"])." ]\n";
						$message .= $lang["common"][37].": ".$fup->getAuthorName()."\n";
						$message .= $lang["mailing"][3]."\n".$fup->fields["contents"]."\n";
						if ($fup->fields["realtime"]>0)
							$message .= $lang["mailing"][104].": ".getRealtime($fup->fields["realtime"])."\n";

						$message.=$lang["mailing"][25]." ";
						$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$db->query($query2);
						if ($db->numrows($result2)==0)
							$message.=$lang["job"][32]."\n";
						else {
							$data2=$db->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
						}

						$message.=$lang["mailing"][0]."\n";	
					}	
				}


			}
			return $message;
		} else return "";
	}

	function textDescription($format="text"){
		global $db,$lang;


		$m= new CommonItem;
		$name=$lang["help"][30];
		$contact=0;
		$tech=0;
		if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
			$name=$m->getType()." ".$m->getName();
			if (isset($m->obj->fields["tech_num"])&&$m->obj->fields["tech_num"]>0){
					$tech=getUserName($m->obj->fields["tech_num"]);
			}
			if (isset($m->obj->fields["contact"]))
				$contact=$m->obj->fields["contact"];
			if (isset($m->obj->fields["FK_users"]))
				$contact=getUserName($m->obj->fields["FK_users"]);
			if (isset($m->obj->fields["FK_groups"])){
				if (!empty($contact)) $contact.=" / ";
					$contact.=getDropdownName("glpi_groups",$m->obj->fields["FK_groups"]);
			}
				
			
		}

		if($format=="html"){
			$message= "<html><head> <style type=\"text/css\">";
			$message.=".description{ color: inherit; background: #ebebeb; border-style: solid; border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }";
			$message.=" </style></head><body>";

			$message.="<div class='description'><strong>".$lang["mailing"][5]."</strong></div><br>";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$lang["mailing"][108];
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["common"][37].":</span> ".$author."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["search"][8].":</span> ".convDateTime($this->fields["date"])."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["job"][44].":</span> ".getRequestTypeName($this->fields["request_type"])."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["mailing"][7]."</span> ".$name."<br>";
			if ($tech)
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["common"][10].":</span> ".$tech."<br>";
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["joblist"][0].":</span> ".getStatusName($this->fields["status"])."<br>";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			if ($assign=="[Nobody]")
				$assign=$lang["mailing"][105];
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][8]."</span> ".$assign."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["joblist"][2].":</span> ".getPriorityName($this->fields["priority"])."<br>";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE&&$contact)
				$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][28]."</span> ".$contact."<br>";
			if ($this->fields["emailupdates"]=="yes"){
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][103]."</span> ".$lang["choice"][1]."<br>";
			} else {
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][103]."</span> ".$lang["choice"][0]."<br>";
			}

			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["common"][36].":</span> ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.=$lang["mailing"][100];
			$message.= "<br>";

			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["mailing"][3]."</span><br>".nl2br($this->fields["contents"])."<br><br>";	

		}else{ //text format
			$message = $lang["mailing"][1]."\n*".$lang["mailing"][5]."*\n".$lang["mailing"][1]."\n";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$lang["mailing"][108];
			$message.= $lang["common"][37].": ".$author."\n";
			$message.= $lang["search"][8].": ".convDateTime($this->fields["date"])."\n";
			$message.= $lang["job"][44].": ".getRequestTypeName($this->fields["request_type"])."\n";
			$message.= $lang["mailing"][7]." ".$name."\n";
			if ($tech)
				$message.= $lang["common"][10].": ".$tech."\n";
			$message.= $lang["joblist"][0].": ".getStatusName($this->fields["status"])."\n";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			if ($assign=="[Nobody]")
				$assign=$lang["mailing"][105];
			$message.= $lang["mailing"][8]." ".$assign."\n";
			$message.= $lang["joblist"][2].": ".getPriorityName($this->fields["priority"])."\n";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE&&$contact)
				$message.= $lang["mailing"][28]." ".$contact."\n";
			if ($this->fields["emailupdates"]=="yes"){
				$message.=$lang["mailing"][103]." ".$lang["choice"][1]."\n";
			} else {
				$message.=$lang["mailing"][103]." ".$lang["choice"][0]."\n";
			}

			$message.= $lang["common"][36].": ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.=$lang["mailing"][100];
			$message.= "\n";

			$message.= $lang["mailing"][3]."\n".$this->fields["contents"]."\n";	
			$message.="\n\n";

		}

		return $message;
	}


	function getAuthorName($link=0){

		return getUserName($this->fields["author"],$link);
	}

}


class Followup  extends CommonDBTM {

	function Followup () {
		$this->table="glpi_followups";
		$this->type=-1;
	}


	function post_addToDB(){

		if (isset($this->fields["realtime"])&&$this->fields["realtime"]>0) {
			$job=new Job();
			$job->getfromDB($this->fields["tracking"]);
			$job->updateRealTime();
		}
	}

	function post_updateInDB($updates)  {

		for ($i=0; $i < count($updates); $i++) {
			if ($updates[$i]=="realtime") {
				$job=new Job();
				$job->getFromDB($this->fields["tracking"]);
				$job->updateRealTime();
			}
		}
	}

	function cleanDBonPurge($ID) {
		global $db;
		$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '$ID'";
		$db->query($querydel);				
	}

	function post_deleteFromDB($ID){
		$job=new Job();
		$job->getFromDB($this->fields['tracking']);
		$job->updateRealtime();		
	}


	function prepareInputForUpdate($input) {
		$input["realtime"]=$input["hour"]+$input["minute"]/60;
		$input["author"]=$_SESSION["glpiID"];

		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $cfg_glpi;
		$job=new Job;
		$job->getFromDBwithData($input["tracking"],1);

		if (in_array("contents",$updates)&&$cfg_glpi["mailing"]){
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}
	}

	function prepareInputForAdd($input) {

		$input["_isadmin"]=haveRight("comment_all_ticket","1");

		$input["_job"]=new Job;
		$input["_job"]->getFromDB($input["tracking"]);

		// Security to add unauthorized followups
		if (!$input["_isadmin"]&&$input["_job"]->fields["author"]!=$_SESSION["glpiID"]) return false;

		// Pass old assign From Job in case of assign change
		if (isset($input["_old_assign"]))
			$input["_job"]->fields["_old_assign"]=$input["_old_assign"];


		if (!isset($input["type"])) $input["type"]="followup";
		$input["_type"]=$input["type"];
		unset($input["type"]);

		$input['_close']=0;
		unset($input["add"]);

		if (!isset($input["author"]))
			$input["author"]=$_SESSION["glpiID"];

		if ($input["_isadmin"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
			if (isset($input['plan'])){
				$input['_plan']=$input['plan'];
				unset($input['plan']);
			}	
			if (isset($input["add_close"])) $input['_close']=1;
			unset($input["add_close"]);

			if ($input["hour"]>0||$input["minute"]>0)
				$input["realtime"]=$input["hour"]+$input["minute"]/60;
		}

		unset($input["minute"]);
		unset($input["hour"]);

		$input["date"] = date("Y-m-d H:i:s");

		return $input;
	}

	function postAddItem($newID,$input) {
		global $cfg_glpi;

		if ($input["_isadmin"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
			if (isset($input["_plan"])){
				$input["_plan"]['id_followup']=$newID;
				$input["_plan"]['id_tracking']=$input['tracking'];
				$pt=new PlanningTracking();

				if (!$pt->add($input["_plan"],"",1)){
					return false;
				}
			}


			if ($input["_close"]&&$input["_type"]!="update"&&$input["_type"]!="finish"){
				$updates[]="status";
				$updates[]="closedate";
				$input["_job"]->fields["status"]="old_done";
				$input["_job"]->fields["closedate"] = date("Y-m-d H:i:s");
				$input["_job"]->updateInDB($updates);
			}

		}

		if ($cfg_glpi["mailing"]){
			if ($input["_close"]) $input["_type"]="finish";
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing($input["_type"],$input["_job"],$user);
			$mail->send();
		}
	}


	// SPECIFIC FUNCTIONS

	function getAuthorName($link=0){
		return getUserName($this->fields["author"],$link);
	}	


}



?>
