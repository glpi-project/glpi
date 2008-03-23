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


// CLASSES peripherals
require_once(GLPI_ROOT . "/lib/mail/receivemail.class.php");

class Mailgate  extends CommonDBTM {


	function Mailgate () {
		$this->table="glpi_mailgate";
		$this->type=MAILGATE_TYPE;
	}
	function prepareInputForUpdate($input) {

		if (isset($input['password'])&&empty($input['password'])){
			unset($input['password']);
		}
		if (isset ($input['mail_server']) && !empty ($input['mail_server']))
			$input["host"] = constructMailServerConfig($input);
		return $input;
	}

	function prepareInputForAdd($input) {

		if (isset ($input['mail_server']) && !empty ($input['mail_server']))
			$input["host"] = constructMailServerConfig($input);
		return $input;
	}

	function showForm ($target,$ID) {

		global $CFG_GLPI, $LANG;

		if (!haveRight("config","r")) return false;

		$spotted = false;

		if(empty($ID)) {
			if($this->getEmpty()) $spotted = true;
		} else {
			if($this->getFromDB($ID)) $spotted = true;
		}

		if ($spotted){

			echo "<div class='center'><form method='post' name=form action=\"$target\">";

			echo "<table class='tab_cadre' cellpadding='2'>";

			echo "<tr><th align='center' colspan='2'>";
			if (empty($ID)){
				echo $LANG["mailgate"][1];
			} else {
				echo $LANG["mailgate"][0].": ".$this->fields["ID"];
			}

			echo "</th></tr>";
			if (!function_exists('mb_list_encodings')||!function_exists('mb_convert_encoding')){
				echo "<tr class='tab_bg_1'><td align='center' colspan='2'>";
				echo $LANG["mailgate"][4];
				echo "</td></tr>";
			}
			echo "<tr class='tab_bg_2'><td>".$LANG["common"][16].":	</td><td>";
			autocompletionTextField("name","glpi_mailgate","name",$this->fields["name"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["entity"][0].":	</td><td>";
			dropdownValue("glpi_entities", "FK_entities",$this->fields["FK_entities"],1,$_SESSION['glpiactiveentities']);
			echo "</td></tr>";

			showMailServerConfig($this->fields["host"]);

			echo "<tr class='tab_bg_2'><td>".$LANG["login"][6].":	</td><td>";
			autocompletionTextField("login","glpi_mailgate","login",$this->fields["login"],20);
			echo "</td></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["login"][7].":	</td><td>";
			echo "<input type='password' name='password' value='' size='20'>";
			echo "</td></tr>";


			if (haveRight("config","w")) {

				echo "<tr class='tab_bg_1'>";
				if(empty($ID)){

					echo "<td valign='top' colspan='2'>";
					echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
					echo "</td>";
					echo "</tr>";
				} else {

					echo "<td valign='top' align='center'>";
					echo "<input type='hidden' name='ID' value=\"$ID\">\n";
					echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
					echo "</td>";
					echo "<td valign='top'>\n";
					echo "<div class='center'>";
					echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'>";
					echo "</div>";
					echo "</td>";
					echo "</tr>";
					echo "<tr class='tab_bg_1'><td colspan='2' align='center'><input type='submit' name='get_mails' value=\"".$LANG["mailgate"][2]."\" class='submit'>";
					echo "</td></tr>";
				}


			}

			echo "</table></form></div>";

			return true;	
		}
		else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;
		}

	}
	
}


class MailCollect  extends receiveMail {
	var $entity;
	var $charset="";
	/**
	* Constructor
	*/
	function collect($server,$username,$password,$entity,$display=0){
			global $LANG;
			$this->entity=$entity;
			$this->set($username,$password,$server);
			//example receiveMail('abc@example.com','XXX','abc@example.com','mail.example.com','pop3','110');
			
			//Connect to the Mail Box
			$this->connect();
			if ($this->marubox){
				// Get Total Number of Unread Email in mail box
				$tot=$this->getTotalMails(); //Total Mails in Inbox Return integer value
				$error=0;
//				if (isset ($tot))
//				{
						
					for($i=1;$i<=$tot;$i++)
					{
						$tkt= $this->buildTicket($i);
						
						// Is a mail responding of an already existgin ticket ?
						if (array_key_exists('tracking',$tkt) ) {
							$fup=new Followup();
							if ($fup->add($tkt)){
								$this->deleteMails($i); // Delete Mail from Mail box
							} else {
								$error++;
							}
						} else { // New ticket
							$track=new job;
							if ($track->add($tkt)){
								$this->deleteMails($i); // Delete Mail from Mail box
							} else {
								$error++;
							}
						}

					}
					imap_expunge($this->marubox);
//				}
				$this->close_mailbox();   //Close Mail Box

				if ($display){
					addMessageAfterRedirect($LANG["mailgate"][3].": $tot ".($error>0?"($error ".$LANG["common"][63].")":""));
				} else {
					return $LANG["mailgate"][3].": $tot ".($error>0?"($error ".$LANG["common"][63].")":"");
				}
				
			}
			else
			{
				if ($display){
					addMessageAfterRedirect($LANG["log"][41]);
				} else {
					return $LANG["log"][41];
				}
//				return 0;
			}
	} // end function MailCollect
	
	
	
	/* *** Primary Functions ***** *
	* Functions called directly from the Script Flow portion of the script. */
	
	
	/* function buildTicket - Builds,and returns, the major structure of the ticket to be entered . */
	function buildTicket($i)
	{
		global $DB,$LANG,$CFG_GLPI;
	
		$head=$this->getHeaders($i);  // Get Header Info Return Array Of Headers **Key Are (subject,to,toOth,toNameOth,from,fromName)
	
		$tkt= array ();

		// max size = 0 : no import attachments
		if ($CFG_GLPI['mailgate_filesize_max']>0){
			if (!is_writable(GLPI_DOC_DIR."/_tmp/")){
				logInFile('mailgate',GLPI_DOC_DIR."/_tmp/ is not writable");
			}
			$str=$this->GetAttech($i,GLPI_DOC_DIR."/_tmp/");
			$ar=explode(",",$str);
			$j=0;
			$addtobody="";
			foreach($ar as $key=>$value) {
				if (!empty($value)){
					$size=filesize(GLPI_DOC_DIR."/_tmp/".$value);
					if ($size>0){
						if (isValidDoc($value)){
							if ($size<$CFG_GLPI['mailgate_filesize_max'] ){
								$_FILES['multiple'] = true;
								$_FILES[$j]['filename']['size'] = $size;
								$_FILES[$j]['filename']['name'] = $value;
								$_FILES[$j]['filename']['tmp_name'] = GLPI_DOC_DIR."/_tmp/".$value;
								$_FILES[$j]['filename']['type'] = mime_content_type(GLPI_DOC_DIR."/_tmp/".$value);
								$j++;
							} else {
								unlink(GLPI_DOC_DIR."/_tmp/".$value);
								$addtobody.="<br>".$LANG["mailgate"][6].": ".$value;
							}
						} else {
							unlink(GLPI_DOC_DIR."/_tmp/".$value);
							$addtobody.="<br>".$LANG["mailgate"][5].": ".$value;
						}
					}
				}
			}
		}

		//  Who is the user ?
		$tkt['author']=0;
		$query="SELECT ID from glpi_users WHERE email='".$head['from']."'";
		$result=$DB->query($query);
		if ($result&&$DB->numrows($result)){
			$tkt['author']=$DB->result($result,0,"ID");
		}


		// AUto_import
		$tkt['_auto_import']=1;
		$body=$this->getBody($i);

		$body.=$addtobody;

		if (!empty($this->charset)&&function_exists('mb_convert_encoding')){
			$body=mb_convert_encoding($body, 'utf-8',$this->charset);
		}
		if (!seems_utf8($body)){
			$tkt['contents']= utf8_encode($body);	
		}else{
			$tkt['contents']= $body;
		}

		$exists = false;
		if ( preg_match('/\[GLPI #(\d+)\]/',$head['subject'],$match) ) {
			// it's a reply to a previous ticket
			$ID = (int)$match[1];
			$tkt['tracking'] = $ID;
			$job=new Job();
			$job->getfromDB($ID);
			// the job exists and the author is the same
			if ( $job->getFromDB($ID) && ($job->fields["author"] == $tkt['author']) ) {
		
				$exists = true;
				$content=explode("\n",$tkt['contents']);
				$tkt['contents']="";
				$first_comment=true;
				$to_keep=array();
				foreach($content as $ID => $val){
					if (isset($val[0])&&$val[0]=='>'){
						// Delete line at the top of the first comment
						if ($first_comment){
							$first_comment=false;
							if (isset($to_keep[$ID-1])){
								unset($to_keep[$ID-1]);
							}
						}
					} else {
						// Detect a signature if already keep lines
						if (isset($val[0])&&$val[0]=='-'
							&&isset($val[1])&&$val[1]=='-' 
							&&count($tokeep)){
							
							break;
						} else {
							$to_keep[$ID]=$ID;
						}
					}
				}
				foreach($to_keep as $ID ){
					$tkt['contents'].=$content[$ID]."\n";
				}
			}
		}

                if ( ! $exists ) {
			unset($tkt['tracking']);
			// Mail followup
			$tkt['uemail']=$head['from'];
			$tkt['emailupdates']=1;
			// Which entity ?
			$tkt['FK_entities']=$this->entity;
	
			//$tkt['Subject']= $head['subject'];   // not use for the moment
			$tkt['name']=$this->textCleaner($this->decodeMimeString($head['subject']));
			// Medium
			$tkt['priority']= "3";
			// No hardware associated
			$tkt['device_type']="0";
			// Mail request type
			$tkt['request_type']="2";
		}

		$tkt['contents']=clean_cross_side_scripting_deep(html_clean($tkt['contents']));

		$tkt=addslashes_deep($tkt);
		
		return $tkt;
	}


	/* function textCleaner - Strip out unwanted/unprintable characters from the subject. */
	function textCleaner($text)
	{
		//$text= str_replace("'", "", $text);
		$text= str_replace("=20", "\n", $text);
		return $text;
	}


	//return supported encodings in lowercase.
	function mb_list_lowerencodings() { 
		$r=mb_list_encodings();
		for ($n=sizeOf($r); $n--; ) { 
			$r[$n]=strtolower($r[$n]); 
		} 
		return $r;
	}
	
	//  Receive a string with a mail header and returns it
	// decoded to a specified charset.
	// If the charset specified into a piece of text from header
	// isn't supported by "mb", the "fallbackCharset" will be
	// used to try to decode it.
	function decodeMimeString($mimeStr, $inputCharset='utf-8', $targetCharset='utf-8', $fallbackCharset='iso-8859-1') {
		if (function_exists('mb_list_encodings')&&function_exists('mb_convert_encoding')){
			$encodings=$this->mb_list_lowerencodings();
			$inputCharset=strtolower($inputCharset);
			$targetCharset=strtolower($targetCharset);
			$fallbackCharset=strtolower($fallbackCharset);
			
			$decodedStr='';
			$mimeStrs=imap_mime_header_decode($mimeStr);
			for ($n=sizeOf($mimeStrs), $i=0; $i<$n; $i++) {
				$mimeStr=$mimeStrs[$i];
				$mimeStr->charset=strtolower($mimeStr->charset);
				if (($mimeStr == 'default' && $inputCharset == $targetCharset)
				|| $mimeStr->charset == $targetCharset) {
					$decodedStr.=$mimeStr->text;
				} else {
					if (in_array($mimeStr->charset, $encodings)){
						$this->charset=	$mimeStr->charset;
					}
				
					$decodedStr.=mb_convert_encoding(
						$mimeStr->text, $targetCharset,
						(in_array($mimeStr->charset, $encodings) ?
						$mimeStr->charset : $fallbackCharset)
						);
				}
			} return $decodedStr;
		} else {
			return $mimeStr;
		}
		
	}





}

?>
