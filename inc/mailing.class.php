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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

require_once(GLPI_ROOT . "/lib/phpmailer/class.phpmailer.php");

/**
 *  glpi_phpmailer class extends 
 */
class glpi_phpmailer extends phpmailer {

	/// Set default variables for all new objects
	var $WordWrap = 80;
	/// Defaut charset
	var $CharSet ="utf-8";


	/**
	 * Constructor
	**/
	function glpi_phpmailer(){
		global $CFG_GLPI;

		// Comes from config
		$this->SetLanguage("en", GLPI_ROOT . "/lib/phpmailer/language/");
		if($CFG_GLPI['smtp_mode'] == '1') {
			$this->Host = $CFG_GLPI['smtp_host'];
			$this->Port = $CFG_GLPI['smtp_port'];

			if($CFG_GLPI['smtp_username'] != '') {
				$this->SMTPAuth  = true;
				$this->Username  = $CFG_GLPI['smtp_username'];
				$this->Password  =  $CFG_GLPI['smtp_password'];
			}

			/*if($CFG_GLPI['debug']=="2"){
				$this->SMTPDebug    = TRUE;
			}*/

			$this->Mailer = "smtp";
		}

	} 




}


/**
 *  Mailing class for trackings
 */
class Mailing
{
	//! mailing type (new,attrib,followup,finish)
	var $type=NULL;
	/** Job class variable - job to be mailed
	 * @see Job
	 */
	var $job=NULL;
	/** User class variable - user who make changes
	 * @see User
	 */
	var $user=NULL;
	/// Is the followupadded private ? 
	var $followupisprivate=NULL;


	/**
	 * Constructor
	 * @param $type mailing type (new,attrib,followup,finish)
	 * @param $job Job to mail
	 * @param $user User who made change
	 * @param $followupisprivate true if the currently added/modified followup is private
	 * @return nothing 
	 */
	function Mailing ($type="",$job=NULL,$user=NULL,$followupisprivate=false)
	{
		$this->type=$type;
		if (!isset($job->hardwaredatas)||!count($job->hardwaredatas)){
			$job->getHardwareData();
		}
		$this->job=$job;
		$this->user=$user;
		$this->followupisprivate=$followupisprivate;
	}

	/**
	 * Give mails to send the mail
	 * 
	 * Determine email to send mail using global config and Mailing type
	 * @param $sendprivate false : all users; true : only users who have the right to see private followups 
	 *
	 * @return array containing email
	 */
	function get_users_to_send_mail($sendprivate=false)
	{
		global $DB,$CFG_GLPI;

		$emails=array();

		$query="SELECT * FROM glpi_mailing WHERE type='".$this->type."'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){

			$select ="";
			$join="";
			$joinprofile="";
			// If send private is the user can see private followups ?
			if ($sendprivate){
				$join=" INNER JOIN glpi_users_profiles 
					ON (glpi_users_profiles.FK_users = glpi_users.ID 
						".getEntitiesRestrictRequest("AND","glpi_users_profiles","FK_entities",$this->job->fields['FK_entities'],true).")
					INNER JOIN glpi_profiles 
					ON (glpi_profiles.ID = glpi_users_profiles.FK_profiles AND glpi_profiles.interface='central' AND glpi_profiles.show_full_ticket = '1') ";
				$joinprofile=	"INNER JOIN glpi_profiles 
					ON (glpi_profiles.ID = glpi_users_profiles.FK_profiles AND glpi_profiles.interface='central' AND glpi_profiles.show_full_ticket = '1') ";

			}

			while ($data=$DB->fetch_assoc($result)){
				switch ($data["item_type"]){
					case USER_MAILING_TYPE :
						switch($data["FK_item"]){
							// ADMIN SEND
							case ADMIN_MAILING :
								if (isValidEmail($CFG_GLPI["admin_email"])&&!isset($emails[$CFG_GLPI["admin_email"]])){
									$emails[$CFG_GLPI["admin_email"]]=$CFG_GLPI["default_language"];
								}
								break;
							// ADMIN ENTITY SEND
							case ADMIN_ENTITY_MAILING :
								$query2 = "SELECT admin_email AS EMAIL 
									FROM glpi_entities_data 
									WHERE (FK_entities = '".$this->job->fields["FK_entities"]."')";
								if ($result2 = $DB->query($query2)) {
									if ($DB->numrows($result2)==1){
										$row = $DB->fetch_array($result2);
										if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
											$emails[$row['EMAIL']]=$CFG_GLPI["default_language"];
										}
									}
								}
								break;
							// ASSIGN SEND
							case ASSIGN_MAILING :
								if (isset($this->job->fields["assign"])&&$this->job->fields["assign"]>0){
									$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
										FROM glpi_users $join 
										WHERE (glpi_users.ID = '".$this->job->fields["assign"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
												$emails[$row['EMAIL']]=$row['LANG'];
											}
										}
									}
								}
								break;
							// ASSIGN SEND
							case ASSIGN_ENT_MAILING :
								
								if (!$sendprivate&&isset($this->job->fields["assign_ent"])&&$this->job->fields["assign_ent"]>0){
									$query2 = "SELECT DISTINCT glpi_enterprises.email AS EMAIL 
									FROM glpi_enterprises 
									WHERE (glpi_enterprises.ID = '".$this->job->fields["assign_ent"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
												$emails[$row['EMAIL']]=$CFG_GLPI["default_language"];
											}
										}
									}
								}
								break;
							// ASSIGN GROUP SEND
							case ASSIGN_GROUP_MAILING :
								if (isset($this->job->fields["assign_group"])&&$this->job->fields["assign_group"]>0){
									$query="SELECT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
									FROM glpi_users_groups 
									INNER JOIN glpi_users ON (glpi_users_groups.FK_users = glpi_users.ID) $join 
									WHERE glpi_users_groups.FK_groups='".$this->job->fields["assign_group"]."'";
				
									if ($result2= $DB->query($query)){
										if ($DB->numrows($result2)){
											while ($row=$DB->fetch_assoc($result2)){
												if (isValidEmail($row["EMAIL"])&&!isset($emails[$row['EMAIL']])){
													$emails[$row['EMAIL']]=$row['LANG'];
												}
											}
										}
									}
								}
								break;
							// SUPERVISOR ASSIGN GROUP SEND
							case SUPERVISOR_ASSIGN_GROUP_MAILING :
								if (isset($this->job->fields["assign_group"])&&$this->job->fields["assign_group"]>0){
									$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
									FROM glpi_groups 
									LEFT JOIN glpi_users ON (glpi_users.ID = glpi_groups.FK_users) $join 
									WHERE (glpi_groups.ID = '".$this->job->fields["assign_group"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
												$emails[$row['EMAIL']]=$row['LANG'];
											}
										}
									}
								}
								break;


							// RECIPIENT SEND
							case RECIPIENT_MAILING :
								if (isset($this->job->fields["recipient"])&&$this->job->fields["recipient"]>0){
									$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG  
										FROM glpi_users $join 
										WHERE (glpi_users.ID = '".$this->job->fields["recipient"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
												$emails[$row['EMAIL']]=$row['LANG'];
											}
										}
									}
								}
								break;

							// AUTHOR SEND
							case AUTHOR_MAILING :
								if ($this->job->fields["emailupdates"]&&isValidEmail($this->job->fields["uemail"])&&!isset($emails[$this->job->fields["uemail"]])){
									// Uemail = mail of the author ? -> use right of the author to see private followups
									// Else not see private
									$authorsend=false;
									$authorlang=$CFG_GLPI["default_language"];
									if (!$sendprivate){
										$authorsend=true;
									} 

									// Is the user have the same mail that uemail ?
									$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG   
									FROM glpi_users $join 
									WHERE (glpi_users.ID = '".$this->job->fields["author"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if ($row['EMAIL']==$this->job->fields["uemail"]){
												$authorsend=true;
												$authorlang=$row['LANG'];
											}
										}
									}

									if ($authorsend){
										$emails[$this->job->fields["uemail"]]=$authorlang;
									}
								}
								break;
							// SUPERVISOR ASSIGN GROUP SEND
							case SUPERVISOR_AUTHOR_GROUP_MAILING :
								if (isset($this->job->fields["FK_group"])&&$this->job->fields["FK_group"]>0){
									$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
										FROM glpi_groups 
										LEFT JOIN glpi_users ON (glpi_users.ID = glpi_groups.FK_users) $join 
										WHERE (glpi_groups.ID = '".$this->job->fields["FK_group"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
												$emails[$row['EMAIL']]=$row['LANG'];
											}
										}
									}
								}
								break;

							// OLD ASSIGN SEND
							case OLD_ASSIGN_MAILING :
								if (isset($this->job->fields["_old_assign"])&&$this->job->fields["_old_assign"]>0){
									$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG  
										FROM glpi_users $join 
										WHERE (glpi_users.ID = '".$this->job->fields["_old_assign"]."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
												$emails[$row['EMAIL']]=$row['LANG'];
											}
										}
									}
								}
								break;
							// TECH SEND
							case TECH_MAILING :
								if (isset($this->job->fields["computer"])&&$this->job->fields["computer"]>0&&isset($this->job->fields["device_type"])&&$this->job->fields["device_type"]>0){
									$ci= new CommonItem();
									$ci->getFromDB($this->job->fields["device_type"],$this->job->fields["computer"]);
									if ($tmp=$ci->getField('tech_num')){
										$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
											FROM glpi_users $join 
											WHERE (glpi_users.ID = '".$tmp."')";
										if ($result2 = $DB->query($query2)) {
											if ($DB->numrows($result2)==1){
												$row = $DB->fetch_array($result2);
												if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
													$emails[$row['EMAIL']]=$row['LANG'];
												}
											}
										}
									}
								}
								break;
							// USER SEND
							case USER_MAILING :
								if (isset($this->job->fields["computer"])&&$this->job->fields["computer"]>0&&isset($this->job->fields["device_type"])&&$this->job->fields["device_type"]>0){
									$ci= new CommonItem();
									$ci->getFromDB($this->job->fields["device_type"],$this->job->fields["computer"]);
									if ($tmp=$ci->getField('FK_users')){
										$query2 = "SELECT DISTINCT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
											FROM glpi_users $join 
											WHERE (glpi_users.ID = '".$tmp."')";
										if ($result2 = $DB->query($query2)) {
											if ($DB->numrows($result2)==1){
												$row = $DB->fetch_array($result2);
												if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
													$emails[$row['EMAIL']]=$row['LANG'];
												}
											}
										}
									}
								}
								break;
								
						}
						break;
					case PROFILE_MAILING_TYPE :
						$query="SELECT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
						FROM glpi_users_profiles 
						INNER JOIN glpi_users ON (glpi_users_profiles.FK_users = glpi_users.ID) $joinprofile 
						WHERE glpi_users_profiles.FK_profiles='".$data["FK_item"]."' ".
						getEntitiesRestrictRequest("AND","glpi_users_profiles","FK_entities",$this->job->fields['FK_entities'],true);

						if ($result2= $DB->query($query)){
							if ($DB->numrows($result2))
								while ($row=$DB->fetch_assoc($result2)){
									if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
										$emails[$row['EMAIL']]=$row['LANG'];
									}
								}
						}
						break;
					case GROUP_MAILING_TYPE :
						$query="SELECT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
							FROM glpi_users_groups 
							INNER JOIN glpi_users ON (glpi_users_groups.FK_users = glpi_users.ID) $join 
							WHERE glpi_users_groups.FK_groups='".$data["FK_item"]."'";

						if ($result2= $DB->query($query)){
							if ($DB->numrows($result2))
								while ($row=$DB->fetch_assoc($result2)){
									if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
										$emails[$row['EMAIL']]=$row['LANG'];
									}
								}
						}
						break;
				}
			}
		}

		return $emails;
	}

	/**
	 * Format the mail body to send
	* @param $format text or html
	 * @param $sendprivate true if the email contains private followups
	 * @return mail body string
	 */
	function get_mail_body($format="text", $sendprivate=false)
	{
		global $CFG_GLPI, $LANG;

		// Create message body from Job and type
		$body="";

		if($format=="html"){
			if ($CFG_GLPI["url_in_mail"]&&!empty($CFG_GLPI["url_base"])){
				$body.="URL :<a href=\"".$CFG_GLPI["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]."\">".$CFG_GLPI["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]." </a><br><br>";

			}

			$body.=$this->job->textDescription($format);
			$body.=$this->job->textFollowups($format, $sendprivate);

			$body.="<br>-- <br>".$CFG_GLPI["mailing_signature"];
			$body.="</body></html>";
			$body=ereg_replace("\n","<br>\n",$body);

		}else{ // text format

			if ($CFG_GLPI["url_in_mail"]&&!empty($CFG_GLPI["url_base"])){
				$body.=$LANG["mailing"][1]."\n"; $body.="URL : ".$CFG_GLPI["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]."\n";

			}

			$body.=$this->job->textDescription($format);
			$body.=$this->job->textFollowups($format, $sendprivate);

			$body.="\n-- \n".$CFG_GLPI["mailing_signature"];
			$body=ereg_replace("<br />","\n",$body);
			$body=ereg_replace("<br>","\n",$body);
		}

		return $body;
	}

	/**
	 * Format the mail sender to send
	 * @return mail sender email string
	 */
	function get_mail_sender(){
		global $CFG_GLPI,$DB;

		$query = "SELECT admin_email AS EMAIL FROM glpi_entities_data WHERE (FK_entities = '".$this->job->fields["FK_entities"]."')";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)){
				$data=$DB->fetch_assoc($result);
				if (isValidEmail($data["EMAIL"])){
					return $data["EMAIL"];
				}
			}
		}

		return $CFG_GLPI["admin_email"];
	}

	/**
	 * Format the mail subject to send
	 * @return mail subject string
	 */
	function get_mail_subject()
	{
		global $LANG;

		// Create the message subject 
		$subject=sprintf("%s%07d%s","[GLPI #",$this->job->fields["ID"],"] ");

		if (isMultiEntitiesMode()){
			$subject.=getDropdownName("glpi_entities",$this->job->fields['FK_entities'])." | ";
		}

		switch ($this->type){
			case "new":
				$subject.=$LANG["mailing"][9];
			break;
			case "attrib":
				$subject.=$LANG["mailing"][12];
			break;
			case "followup":
				$subject.=$LANG["mailing"][10];
			break;
			case "update":
				$subject.=$LANG["mailing"][30];
			break;
			case "finish":
				$subject.=$LANG["mailing"][11]." ".convDateTime($this->job->fields["closedate"]);			
			break;
			default :
			$subject.=$LANG["mailing"][13];
			break;
		}
		
		if (strlen($this->job->fields['name'])>150){
			$subject.=" - ".utf8_substr($this->job->fields['name'],0,150)." (...)";
		}else{
			$subject.=" - ".$this->job->fields['name'];
		}
	
		return $subject;
	}

	/**
	 * Get reply to address 
	 * @param $sender sender address
	 * @return return mail
	 */
	function get_reply_to_address ($sender){
		global $CFG_GLPI,$DB;

		$replyto=$CFG_GLPI["admin_email"];

		// Entity  conf
		$query = "SELECT admin_email AS EMAIL, admin_reply AS REPLY FROM glpi_entities_data WHERE (FK_entities = '".$this->job->fields["FK_entities"]."')";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)){
				$data=$DB->fetch_assoc($result);
				if (isValidEmail($data["REPLY"])){
					return $data["REPLY"];
				} else if (isValidEmail($data["EMAIL"])){
					$replyto=$data["EMAIL"];
				} 
			}
		}
		// Global conf
		if (isValidEmail($CFG_GLPI["admin_reply"])){
			return $CFG_GLPI["admin_reply"];
		}

		// No specific config
		switch ($this->type){
			case "new":
				if (isValidEmail($this->job->fields["uemail"])) {
					$replyto=$this->job->fields["uemail"];
				} else {
					$replyto=$sender;
				}
				break;
			case "followup":
			case "update":
				if (isset($this->user->fields["email"]) && isValidEmail($this->user->fields["email"])) {
					$replyto=$this->user->fields["email"];
				} else {
					$replyto=$sender;
				}
			break;
		}
		return $replyto;		
	}
	/**
	 * Send mail function
	 *
	 * Construct email and send it
	 *
	 * @return mail subject string
	 */
	function send()
	{
		global $CFG_GLPI,$LANG;
		if ($CFG_GLPI["mailing"])
		{	
			if (!is_null($this->job)&&in_array($this->type,array("new","update","followup","finish")))
			{
				$senderror=false;
				// get users to send mail
				$users=array();
				// All users
				$users[0]=$this->get_users_to_send_mail(0);
				// Users who could see private followups
				$users[1]=$this->get_users_to_send_mail(1);
				// Delete users who can see private followups to all users list
				foreach ($users[1] as $email => $lang){
					if (isset($users[0][$email])){
						unset($users[0][$email]);
					}
				}

				// New Followup is private : do not send to common users
				if ($this->followupisprivate){
					unset($users[0]);
				}

				$subjects=array();
				// get sender
				$sender= $this->get_mail_sender(); 
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ($sender);

				$messageerror=$LANG["mailing"][47];
				// Send all mails
				foreach ($users as $private=>$someusers) {
					if (count($someusers)){

						$mmail=new glpi_phpmailer();
						$mmail->From=$sender;
						$mmail->AddReplyTo("$replyto", ''); 
						$mmail->FromName=$sender;
						$mmail->isHTML(true);
						
						$bodys=array();
						$altbodys=array();
						foreach ($someusers as $email => $lang){
							if (!isset($subjects[$lang])||!isset($bodys[$lang])||!isset($altbodys[$lang])){
								loadLanguage($lang);
								if (!isset($subjects[$lang])){
									$subjects[$lang]=$this->get_mail_subject();
								}
								$bodys[$lang]=$this->get_mail_body("html",$private);
								$altbodys[$lang]=$this->get_mail_body("text",$private);
							}
							$mmail->Subject=$subjects[$lang];
							$mmail->Body=$bodys[$lang];
							$mmail->AltBody=$altbodys[$lang];

							$mmail->AddAddress($email, "");

							if(!$mmail->Send()){
								$senderror=true;
								addMessageAfterRedirect($messageerror."<br>".$mmail->ErrorInfo);
							}else{
								logInFile("mail",$LANG["tracking"][38]." ".$email.": ".$subjects[$lang]."\n");
							} 

							$mmail->ClearAddresses(); 
						}
					}
				}
				// Reinit language
				loadLanguage();
				if ($senderror){
					return false;
				}
			} else {
				addMessageAfterRedirect($LANG["mailing"][112]);
			}
		}
		return true;
	}
}

/**
 *  Mailing class for reservations
 */
class MailingResa{
	/** ReservationResa class variable
	 * @see ReservationResa
	 */
	var $resa;	
	//! type of mailing (new, update, delete)
	var $type;

	/**
	 * Constructor
	 * @param $type mailing type (new,attrib,followup,finish)
	 * @param $resa ReservationResa to mail
	 * @return nothing 
	 */
	function MailingResa ($resa,$type="new")
	{
		$this->resa=$resa;
		$this->type=$type;

	}

	/**
	 * Give mails to send the mail
	 * 
	 * Determine email to send mail using global config and Mailing type
	 *
	 * @return array containing email
	 */
	function get_users_to_send_mail()
	{
		global $DB,$CFG_GLPI;

		$emails=array();

		$query="SELECT * FROM glpi_mailing WHERE type='resa'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_assoc($result)){
				switch ($data["item_type"]){
					case USER_MAILING_TYPE :
						switch ($data["FK_item"]){
							// ADMIN SEND
							case ADMIN_MAILING :
								if (isValidEmail($CFG_GLPI["admin_email"])&&!isset($emails[$CFG_GLPI["admin_email"]]))
									$emails[$CFG_GLPI["admin_email"]]=$CFG_GLPI["default_language"];
								break;
							// ADMIN ENTITY SEND
							case ADMIN_ENTITY_MAILING :

								$ri=new ReservationItem();
								$ci=new CommonItem();
								$entity=-1;
								if ($ri->getFromDB($this->resa->fields["id_item"])){
									if ($ci->getFromDB($ri->fields['device_type'],$ri->fields['id_device'])	){
										$entity=$ci->getField('FK_entities');
									}
								}
								
								if ($entity>=0){
									$query2 = "SELECT admin_email AS EMAIL FROM glpi_entities_data WHERE (FK_entities = '".$entity."')";
									if ($result2 = $DB->query($query2)) {
										if ($DB->numrows($result2)==1){
											$row = $DB->fetch_array($result2);
											if (isValidEmail($CFG_GLPI["admin_email"])&&!isset($emails[$CFG_GLPI["admin_email"]])){
												$emails[$row['EMAIL']]=$CFG_GLPI["default_language"];
											}
										}
									}
								}
								break;
							// AUTHOR SEND
							case AUTHOR_MAILING :
								$user = new User;
								if ($user->getFromDB($this->resa->fields["id_user"]))
									if (isValidEmail($user->fields["email"])&&!isset($emails[$user->fields["email"]])){
										$emails[$user->fields["email"]]=$user->fields['language'];
									}
								break;
							// TECH SEND
							case TECH_MAILING :
								$ri=new ReservationItem();
								if ($ri->getFromDB($this->resa->fields["id_item"])){
									$ci=new CommonItem();
									$ci->getFromDB($ri->fields["device_type"],$ri->fields["id_device"]);

									if ($tmp=$ci->getField('tech_num')){
										$query2 = "SELECT glpi_users.email as EMAIL, glpi_users.language as LANG 
										FROM glpi_users WHERE (glpi_users.ID = '".$tmp."')";
										if ($result2 = $DB->query($query2)) {
											if ($DB->numrows($result2)==1){
												$row = $DB->fetch_row($result2);
												if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
													$emails[$row['EMAIL']]=$row['LANG'];
												}
											}
										}
									}
								}
								break;
							// USER SEND
							case USER_MAILING :
								$ri=new ReservationItem();
								if ($ri->getFromDB($this->resa->fields["id_item"])){
									$ci=new CommonItem();
									$ci->getFromDB($ri->fields["device_type"],$ri->fields["id_device"]);

									if ($tmp=$ci->getField('FK_users')){
										$query2 = "SELECT glpi_users.email AS EMAIL, glpi_users.language as LANG 
										FROM glpi_users WHERE (glpi_users.ID = '".$tmp."')";
										if ($result2 = $DB->query($query2)) {
											if ($DB->numrows($result2)==1){
												$row = $DB->fetch_row($result2);
												if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
													$emails[$row['EMAIL']]=$row['LANG'];
												}
											}
										}
									}
								}
								break;								

						}
						break;
					case PROFILE_MAILING_TYPE :
						// Get entity
						$ri=new ReservationItem();
						$ri->getFromDB($this->resa->fields['id_item']);
						$ci = new CommonItem();
						$ci->getFromDB($ri->fields['device_type'],$ri->fields['id_device']);
						$FK_entities=$ci->getField('FK_entities');
						$query="SELECT glpi_users.email AS EMAIL, glpi_users.language as LANG 
							FROM glpi_users_profiles 
							INNER JOIN glpi_users ON (glpi_users_profiles.FK_users = glpi_users.ID) 
							WHERE glpi_users_profiles.FK_profiles='".$data["FK_item"]."' 
							".getEntitiesRestrictRequest("AND","glpi_users_profiles","FK_entities",$FK_entities,true);
						if ($result2= $DB->query($query)){
							if ($DB->numrows($result2))
								while ($row=$DB->fetch_assoc($result2)){
									if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
										$emails[$row['EMAIL']]=$row['LANG'];
									}
							}
						}
						break;
					case GROUP_MAILING_TYPE :
						$query="SELECT glpi_users.email AS EMAIL, glpi_users.language as LANG  
						FROM glpi_users_groups 
						INNER JOIN glpi_users ON (glpi_users_groups.FK_users = glpi_users.ID) 
						WHERE glpi_users_groups.FK_groups='".$data["FK_item"]."'";
						if ($result2= $DB->query($query)){
							if ($DB->numrows($result2))
								while ($row=$DB->fetch_assoc($result2)){
									if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
										$emails[$row['EMAIL']]=$row['LANG'];
									}
								}
						}
						break;
				}
			}
		}

		return $emails;
	}


	/**
	 * Format the mail sender to send
	 * @return mail sender email string
	 */
	function get_mail_sender(){
		global $CFG_GLPI,$DB;


		$ri=new ReservationItem();
		$ci=new CommonItem();
		$entity=-1;
		if ($ri->getFromDB($this->resa->fields["id_item"])){
			if ($ci->getFromDB($ri->fields['device_type'],$ri->fields['id_device'])	){
				$entity=$ci->getField('FK_entities');
			}
		}
		if ($entity>=0){
			$query = "SELECT admin_email AS EMAIL FROM glpi_entities_data WHERE (FK_entities = '$entity')";
			if ($result=$DB->query($query)){
				if ($DB->numrows($result)){
					$data=$DB->fetch_assoc($result);
					if (isValidEmail($data["EMAIL"])){
						return $data["EMAIL"];
					}
				}
			}
		}

		return $CFG_GLPI["admin_email"];
	}


	/**
	 * Format the mail body to send
	* @param $format text or html
	 * @return mail body string
	 */
	function get_mail_body($format="text"){
		global $CFG_GLPI;

		// Create message body from Job and type
		$body="";

		if($format=="html"){

			$body.=$this->resa->textDescription("html");
			$body.="<br>-- <br>".$CFG_GLPI["mailing_signature"];
			$body.="</body></html>";
			$body=ereg_replace("\n","<br>",$body);
		}else{ // text format

			$body.=$this->resa->textDescription();
			$body.="\n-- \n".$CFG_GLPI["mailing_signature"];
			$body=ereg_replace("<br />","\n",$body);
			$body=ereg_replace("<br>","\n",$body);
		}
		return $body;
	}
	/**
	 * Format the mail subject to send
	 * @return mail subject string
	 */
	function get_mail_subject()
	{
		global $LANG;

		// Create the message subject 
		if ($this->type=="new")
			$subject="[GLPI] ".$LANG["mailing"][19];
		else if ($this->type=="update") $subject="[GLPI] ".$LANG["mailing"][23];
		else if ($this->type=="delete") $subject="[GLPI] ".$LANG["mailing"][29];

		return $subject;
	}

	/**
	 * Get reply to address 
	 * @param $sender sender address
	 * @return return mail
	 */
	function get_reply_to_address ($sender){
		global $CFG_GLPI;
		$replyto="";

		$user = new User;
		if ($user->getFromDB($this->resa->fields["id_user"])){
			if (isValidEmail($user->fields["email"])) $replyto=$user->fields["email"];		
			else $replyto=$sender;
		}
		else $replyto=$sender;		

		return $replyto;		
	}
	/**
	 * Send mail function
	 *
	 * Construct email and send it
	 *
	 * @return mail subject string
	 */
	function send()
	{
		global $CFG_GLPI,$LANG;
		if ($CFG_GLPI["mailing"]&&isValidEmail($CFG_GLPI["admin_email"]))
		{
			// get users to send mail
			$users=$this->get_users_to_send_mail();

			// get sender
			$sender= $this->get_mail_sender(); 
			// get reply-to address : user->email ou job_email if not set OK
			$replyto=$this->get_reply_to_address ($sender);


			$mmail=new glpi_phpmailer();
			$mmail->From=$sender;
			$mmail->AddReplyTo("$replyto", ''); 
			$mmail->FromName=$sender;
			
			$mmail->isHTML(true);

			// get subject
			$bodys=array();
			$altbodys=array();
			$subjects=array();
			// Send all mails
			if (count($users)){
				foreach ($users as $email => $lang){
					if (!isset($subjects[$lang])||!isset($bodys[$lang])||!isset($altbodys[$lang])){
						loadLanguage($lang);
						$subjects[$lang]=$this->get_mail_subject();
						$bodys[$lang]=$this->get_mail_body("html");
						$altbodys[$lang]=$this->get_mail_body("text");
					}

					$mmail->Subject=$subjects[$lang];

					$mmail->Body=$bodys[$lang];
					$mmail->AltBody=$altbodys[$lang];

					$mmail->AddAddress($email, "");
	
					if(!$mmail->Send()){
						echo "<div class='center'>".$LANG["mailing"][47]."</div>";
						return false;
					}else{
						logInFile("mail",$LANG["reservation"][40]." ".$email.": ".$subjects[$lang]."\n");
					}
	
					$mmail->ClearAddresses(); 
				}
			} else {
				return false;
			}
		}
		return true;
	}

}


/**
 *  Mailing class for alerts
 */
class MailingAlert
{
	/// mailing type (contract,infocom,cartridge,consumable)
	var $type=NULL;
	/// message to send
	var $message="";
	/// working entity
	var $entity="";

	/**
	 * Constructor
	 * @param $type mailing type (new,attrib,followup,finish)
	 * @param $message Message to send
	 * @param $entity Restrict to a defined entity
	 * @return nothing 
	 */
	function MailingAlert ($type,$message,$entity=-1)
	{
		$this->type=$type;
		$this->message=$message;
		$this->entity=$entity;
	}


	/**
	 * Give mails to send the mail
	 * 
	 * Determine email to send mail using global config and Mailing type
	 *
	 * @return array containing email
	 */
	function get_users_to_send_mail()
	{
		global $DB,$CFG_GLPI;

		$emails=array();

		$query="SELECT * FROM glpi_mailing WHERE type='".$this->type."'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_assoc($result)){
				switch ($data["item_type"]){
					case USER_MAILING_TYPE :
						switch($data["FK_item"]){
							// ADMIN SEND
							case ADMIN_MAILING :
								if (isValidEmail($CFG_GLPI["admin_email"])&&!isset($emails[$CFG_GLPI["admin_email"]]))
									$emails[$CFG_GLPI["admin_email"]]=$CFG_GLPI["default_language"];
								break;
							// ADMIN ENTITY SEND
							case ADMIN_ENTITY_MAILING :
								$query2 = "SELECT admin_email AS EMAIL 
									FROM glpi_entities_data 
									WHERE (FK_entities = '".$this->entity."')";
								if ($result2 = $DB->query($query2)) {
									if ($DB->numrows($result2)==1){
										$row = $DB->fetch_array($result2);
										if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
											$emails[$row['EMAIL']]=$CFG_GLPI["default_language"];
										}
									}
								}
								break;

						}
						break;
					case PROFILE_MAILING_TYPE :
						$query="SELECT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
							FROM glpi_users_profiles 
							INNER JOIN glpi_users ON (glpi_users_profiles.FK_users = glpi_users.ID) 
							WHERE glpi_users_profiles.FK_profiles='".$data["FK_item"]."'
							".getEntitiesRestrictRequest("AND","glpi_users_profiles","FK_entities",$this->entity,true);

						if ($result2= $DB->query($query)){
							if ($DB->numrows($result2)){
								while ($row=$DB->fetch_assoc($result2)){
									if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
										$emails[$row['EMAIL']]=$row['LANG'];
									}
								}
							}
						}
						break;
					case GROUP_MAILING_TYPE :
						$query="SELECT glpi_users.email AS EMAIL, glpi_users.language AS LANG 
							FROM glpi_users_groups 
							INNER JOIN glpi_users ON (glpi_users_groups.FK_users = glpi_users.ID) 
							WHERE glpi_users_groups.FK_groups='".$data["FK_item"]."'";

						if ($result2= $DB->query($query)){
							if ($DB->numrows($result2))
								while ($row=$DB->fetch_assoc($result2)){
									if (isValidEmail($row['EMAIL'])&&!isset($emails[$row['EMAIL']])){
										$emails[$row['EMAIL']]=$row['LANG'];
									}
								}
						}
						break;
				}
			}
		}

		return $emails;
	}

	/**
	 * Format the mail body to send
	 * @param $format text or html
	 * @return mail body string
	 */
	function get_mail_body($format="text"){
		global $CFG_GLPI, $LANG;

		// Create message body from Job and type
		$body="";

		if($format=="html"){

			$body.=$this->message;
			$body.="<br>-- <br>".$CFG_GLPI["mailing_signature"];
			$body.="</body></html>";
			$body=ereg_replace("\n","<br>",$body);
		}else{ // text format

			$body.=$this->message;
			$body.="\n-- \n".$CFG_GLPI["mailing_signature"];
			$body=ereg_replace("<br />","\n",$body);
			$body=ereg_replace("<br>","\n",$body);
		}
		return $body;
	}

	/**
	 * Format the mail subject to send
	 * @return mail subject string
	 */
	function get_mail_subject()
	{
		global $LANG;

		// Create the message subject 
		$subject="[GLPI]";

		switch ($this->type){
			case "alertcartridge" :
				$subject.=" ".$LANG["mailing"][33]. " - ".getDropdownName("glpi_entities",$this->entity);
			break;
			case "alertconsumable":
				$subject.=" ".$LANG["mailing"][36]. " - ".getDropdownName("glpi_entities",$this->entity);
			break;
			case "alertcontract":
				$subject.=" ".$LANG["mailing"][39]. " - ".getDropdownName("glpi_entities",$this->entity);
			break;
			case "alertinfocom":
				$subject.=" ".$LANG["mailing"][41]. " - ".getDropdownName("glpi_entities",$this->entity);
			break;
			case "alertlicense":
				$subject.=" ".$LANG["mailing"][52]. " - ".getDropdownName("glpi_entities",$this->entity);
			break;
		}
		return $subject;
	}

	/**
	 * Format the mail sender to send
	 * @return mail sender email string
	 */
	function get_mail_sender(){
		global $CFG_GLPI,$DB;

		$query = "SELECT admin_email AS EMAIL FROM glpi_entities_data WHERE (FK_entities = '".$this->entity."')";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)){
				$data=$DB->fetch_assoc($result);
				if (isValidEmail($data["EMAIL"])){
					return $data["EMAIL"];
				}
			}
		}

		return $CFG_GLPI["admin_email"];
	}


	/**
	 * Send mail function
	 *
	 * Construct email and send it
	 *
	 * @return mail subject string
	 */
	function send()
	{
		global $CFG_GLPI,$LANG;
		if ($CFG_GLPI["mailing"])
		{
			// get users to send mail
			$users=$this->get_users_to_send_mail();
			// get subject OK
			$subject=$this->get_mail_subject();
			// get sender :  OK
			$sender= $this->get_mail_sender();
			// get reply-to address : user->email ou job_email if not set OK
			$replyto=$sender;


			$mmail=new glpi_phpmailer();
			$mmail->From=$sender;
			$mmail->AddReplyTo("$replyto", ''); 
			$mmail->FromName=$sender;

			$mmail->isHTML(true);

			// Send all mails

			// get subject
			$bodys=array();
			$altbodys=array();
			$subjects=array();
			// Send all mails
			if (count($users)){
				foreach ($users as $email => $lang){
					if (!isset($subjects[$lang])||!isset($bodys[$lang])||!isset($altbodys[$lang])){
						loadLanguage($lang);
						$subjects[$lang]=$this->get_mail_subject();
						$bodys[$lang]=$this->get_mail_body("html");
						$altbodys[$lang]=$this->get_mail_body("text");
					}

					$mmail->Subject=$subjects[$lang];

					$mmail->Body=$bodys[$lang];
					$mmail->AltBody=$altbodys[$lang];

					$mmail->AddAddress($email, "");

					if(!$mmail->Send()){
						addMessageAfterRedirect($LANG["mailing"][47]);
						return false;
					}else{
						logInFile("mail",$LANG["mailing"][111]." ".$email.": ".$subjects[$lang]."\n");
					}
					$mmail->ClearAddresses(); 
				}
			} else {
				return false;
			}
		}
		return true;
	}
}

?>
