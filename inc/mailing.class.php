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

require_once($phproot . "/lib/phpmailer/class.phpmailer.php");

/**
 *  glpi_phpmailer class extends 
 */
class glpi_phpmailer extends phpmailer {
	
	 // Set default variables for all new objects
	 var $WordWrap = 50;
	 var $CharSet ="utf-8";
    	

	function glpi_phpmailer(){
	global $cfg_glpi;
	
	// Comes from config
	
	if($cfg_glpi['smtp_mode'] == '1') {
			$this->Host = $cfg_glpi['smtp_host'];
			$this->Port = $cfg_glpi['smtp_port'];

			if($cfg_glpi['smtp_username'] != '') {
					$this->SMTPAuth  = true;
					$this->Username  = $cfg_glpi['smtp_username'];
					$this->Password  =  $cfg_glpi['smtp_password'];
				}

			if($cfg_glpi['debug']=="2"){
				$this->SMTPDebug    = TRUE;
			}

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
 
	/**
	* Constructor
	* @param $type mailing type (new,attrib,followup,finish)
	* @param $job Job to mail
	* @param $user User who made change
	* @return nothing 
	*/
	function Mailing ($type="",$job=NULL,$user=NULL)
	{
		$this->type=$type;
		$this->job=$job;
		$this->user=$user;
	}
	/**
	* Determine if email is valid
	* @param $email email to check
	* @return boolean 
	*/
	function is_valid_email($email="")
	{
		if( !eregi( "^" .
			"[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
            "@" .
            "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
            "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld 
            "$", $email)
                        )
        {
        //echo "Erreur: '$email' n'est pas une adresse mail valide!<br>";
        return false;
        }
		else return true;
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
		global $db,$cfg_glpi;
		
		$emails=array();
			
		$query="SELECT FK_profiles as PROFILE FROM glpi_mailing_profiles WHERE type='".$this->type."'";
		$result=$db->query($query);
		if ($db->numrows($result)){
			while ($data=$db->fetch_assoc($result)){
				switch ($data["PROFILE"]){
					// ADMIN SEND
					case ADMIN_MAILING :
						if ($this->is_valid_email($cfg_glpi["admin_email"])&&!in_array($cfg_glpi["admin_email"],$emails))
							$emails[]=$cfg_glpi["admin_email"];
						break;
					// ASSIGN SEND
					case ASSIGN_MAILING :
						if (isset($this->job->fields["assign"])&&$this->job->fields["assign"]>0){
							$query2 = "SELECT email FROM glpi_users WHERE (ID = '".$this->job->fields["assign"]."')";
							if ($result2 = $db->query($query2)) {
								if ($db->numrows($result2)==1){
									$row = $db->fetch_row($result2);
									if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails)){
										$emails[]=$row[0];
									}
								}
							}
						}
						break;
					// USER SEND
					case USER_MAILING :
						if ($this->job->fields["emailupdates"]=="yes"&&$this->is_valid_email($this->job->fields["uemail"])&&!in_array($this->job->fields["uemail"],$emails)){
							$emails[]=$this->job->fields["uemail"];
					
						}
						break;
					// OLD ASSIGN SEND
					case OLD_ASSIGN_MAILING :
						if (isset($this->job->fields["_old_assign"])&&$this->job->fields["_old_assign"]>0){
							$query2 = "SELECT email FROM glpi_users WHERE (ID = '".$this->job->fields["_old_assign"]."')";
							if ($result2 = $db->query($query2)) {
								if ($db->numrows($result2)==1){
									$row = $db->fetch_row($result2);
									if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails)){
										$emails[]=$row[0];
									}
								}
							}
						}
						break;
					// ALL PROFILE USER SEND
					default :
						if ($data["PROFILE"]>0){
							$query="SELECT glpi_users.email as EMAIL FROM glpi_users_profiles INNER JOIN glpi_users ON (glpi_users_profiles.FK_users = glpi_users.ID) WHERE glpi_users_profiles.FK_profiles='".$data["PROFILE"]."'";
							if ($result2= $db->query($query)){
								while ($data=$db->fetch_assoc($result2)){
								if ($this->is_valid_email($data["EMAIL"])&&!in_array($data["EMAIL"],$emails)){
										$emails[]=$data["EMAIL"];
									}
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
	* @return mail body string
	*/
	function get_mail_body($format="text")
	{
		global $cfg_glpi, $lang;
		
		// Create message body from Job and type
		$body="";
		
		if($format=="html"){
			if ($cfg_glpi["url_in_mail"]&&!empty($cfg_glpi["url_base"])){
				$body.=$lang["mailing"][1]."<br>"; $body.="URL :<a href=\" ".$cfg_glpi["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]."\">".$cfg_glpi["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]." </a><br>";
				
				}
			
			$body.=$this->job->textDescription("html");
			if ($this->type!="new") $body.=$this->job->textFollowups("html");
			
			$body.="<br>-- <br>".$cfg_glpi["mailing_signature"];
		}else{ // text format
			
				if ($cfg_glpi["url_in_mail"]&&!empty($cfg_glpi["url_base"])){
				$body.=$lang["mailing"][1]."\n"; $body.="URL : ".$cfg_glpi["url_base"]."/index.php?redirect=tracking_".$this->job->fields["ID"]."\n";
				
				}
			
			$body.=$this->job->textDescription();
			if ($this->type!="new") $body.=$this->job->textFollowups();
			
			$body.="\n-- \n".$cfg_glpi["mailing_signature"];
			$body=ereg_replace("<br />","",$body);
			$body=ereg_replace("<br>","",$body);
		}

		return $body;
	}

	/**
	* Format the mail subject to send
	* @return mail subject string
	*/
	function get_mail_subject()
	{
		global $lang;
		
		// Create the message subject 
		$subject=sprintf("%s%07d%s","[GLPI #",$this->job->fields["ID"],"] ");
		
		switch ($this->type){
			case "new":
			$subject.=$lang["mailing"][9];
				break;
			case "attrib":
			$subject.=$lang["mailing"][12];
				break;
			case "followup":
			$subject.=$lang["mailing"][10];
				break;
			case "update":
			$subject.=$lang["mailing"][30];
				break;
			case "finish":
			$subject.=$lang["mailing"][11]." ".convDateTime($this->job->fields["closedate"]);			
				break;
			default :
			$subject.=$lang["mailing"][13];
				break;
		}
		
		
		
		return $subject;
	}
	
	/**
	* Get reply to address 
	* @return return mail
	*/
	function get_reply_to_address ()
	{
		global $cfg_glpi;
	$replyto="";

	switch ($this->type){
			case "new":
				if ($this->is_valid_email($this->job->fields["uemail"])) $replyto=$this->job->fields["uemail"];
				else $replyto=$cfg_glpi["admin_email"];
				break;
			case "followup":
			case "update":
				if ($this->is_valid_email($this->user->fields["email"])) $replyto=$this->user->fields["email"];
				else $replyto=$cfg_glpi["admin_email"];
				break;
			default :
				$replyto=$cfg_glpi["admin_email"];
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
		global $cfg_glpi,$phproot;
		if ($cfg_glpi["mailing"])
		{
			if (!is_null($this->job)&&!is_null($this->user)&&(strcmp($this->type,"new")||strcmp($this->type,"attrib")||strcmp($this->type,"followup")||strcmp($this->type,"finish")))
			{
				// get users to send mail
				$users=$this->get_users_to_send_mail();
				// get subject OK
				$subject=$this->get_mail_subject();
				// get sender :  OK
				$sender= $cfg_glpi["admin_email"];
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ();
				// Send all mails
				for ($i=0;$i<count($users);$i++)
				{
					$mmail=new glpi_phpmailer();
					$mmail->From=$sender;
					$mmail->AddReplyTo("$replyto", ''); 
					$mmail->FromName=$sender;
					
					$mmail->AddAddress($users[$i], "");
					$mmail->Subject=$subject	;  
					$mmail->Body=$this->get_mail_body("html");
					$mmail->isHTML(true);
					$mmail->AltBody=$this->get_mail_body("text");
					
					if(!$mmail->Send()){
						$_SESSION["MESSAGE_AFTER_REDIRECT"].="There was a problem sending this mail !";
					}
					$mmail->ClearAddresses(); 
		  		}
			} else {
				echo "Invalid mail type";
			}
		}
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
	* Determine if email is valid
	* @param $email email to check
	* @return boolean 
	*/
	function is_valid_email($email="")
	{
		if( !eregi( "^" .
			"[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
            "@" .
            "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
            "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld 
            "$", $email)
                        )
        {
        return false;
        }
		else return true;
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
		global $db,$cfg_glpi;
		
		$emails=array();

		$query="SELECT FK_profiles as PROFILE FROM glpi_mailing_profiles WHERE type='resa'";
		$result=$db->query($query);
		if ($db->numrows($result)){
			while ($data=$db->fetch_assoc($result)){
				switch ($data["PROFILE"]){
					// ADMIN SEND
					case ADMIN_MAILING :
						if ($this->is_valid_email($cfg_glpi["admin_email"])&&!in_array($cfg_glpi["admin_email"],$emails))
							$emails[]=$cfg_glpi["admin_email"];
						break;
					// USER SEND
					case USER_MAILING :
						$user = new User;
						if ($user->getFromDB($this->resa->fields["id_user"]))
						if ($this->is_valid_email($user->fields["email"])&&!in_array($user->fields["email"],$emails)){
							$emails[]=$user->fields["email"];
						}
						break;
					// ALL PROFILE USER SEND
					default :
						if ($data["PROFILE"]>0){
							$query="SELECT glpi_users.email as EMAIL FROM glpi_users_profiles INNER JOIN glpi_users ON (glpi_users_profiles.FK_users = glpi_users.ID) WHERE glpi_users_profiles.FK_profiles='".$data["PROFILE"]."'";
							if ($result2= $db->query($query)){
								while ($data=$db->fetch_assoc($result2)){
								if ($this->is_valid_email($data["EMAIL"])&&!in_array($data["EMAIL"],$emails)){
										$emails[]=$data["EMAIL"];
									}
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
	* @return mail body string
	*/
	function get_mail_body($format="text")
	{
		// Create message body from Job and type
		$body="";
		
		if($format=="html"){

			$body.=$this->resa->textDescription();
		}else{ // text format

			$body.=$this->resa->textDescription();
			$body=ereg_replace("<br />","",$body);
			$body=ereg_replace("<br>","",$body);
		}
		return $body;
	}
	/**
	* Format the mail subject to send
	* @return mail subject string
	*/
	function get_mail_subject()
	{
		global $lang;
		
		// Create the message subject 
		if ($this->type=="new")
		$subject="[GLPI] ".$lang["mailing"][19];
		else if ($this->type=="update") $subject="[GLPI] ".$lang["mailing"][23];
		else if ($this->type=="delete") $subject="[GLPI] ".$lang["mailing"][29];
		
		return $subject;
	}
	
	/**
	* Get reply to address 
	* @return return mail
	*/
	function get_reply_to_address ()
	{
		global $cfg_glpi;
	$replyto="";

	$user = new User;
	if ($user->getFromDB($this->resa->fields["id_user"])){
		if ($this->is_valid_email($user->fields["email"])) $replyto=$user->fields["email"];		
		else $replyto=$cfg_glpi["admin_email"];
	}
	else $replyto=$cfg_glpi["admin_email"];		
		
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
		global $cfg_glpi,$phproot;
		if ($cfg_glpi["mailing"]&&$this->is_valid_email($cfg_glpi["admin_email"]))
		{
				// get users to send mail
				$users=$this->get_users_to_send_mail();
				
				// get subject OK
				$subject=$this->get_mail_subject();
				// get sender :  OK
				$sender= $cfg_glpi["admin_email"];
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ();

				// Send all mails
				for ($i=0;$i<count($users);$i++)
				{
						
					$mmail=new glpi_phpmailer();
					$mmail->From=$sender;
					$mmail->AddReplyTo("$replyto", ''); 
					$mmail->FromName=$sender;
					$mmail->AddAddress($users[$i], "");
					$mmail->Subject=$subject	;  
					$mmail->Body=$this->get_mail_body("html");
					$mmail->isHTML(true);
					$mmail->AltBody=$this->get_mail_body("text");

					
					if(!$mmail->Send()){
						echo "<div align='center'>There was a problem sending this mail !</div>";
					}
					$mmail->ClearAddresses(); 
				
				}
		}
	}
	
}


?>
