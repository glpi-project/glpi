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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/// Mailgate class
class Mailgate  extends CommonDBTM {


	/**
	 * Constructor
	**/
	function __construct () {
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

	/**
	 * Print the mailgate form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the item to print
	 *
	 *@return boolean item found
	 **/
	function showForm ($target,$ID) {

		global $CFG_GLPI, $LANG;

		if (!haveRight("config","r")) return false;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 


		echo "<div class='center'><form method='post' name=form action=\"$target\">";

		echo "<table class='tab_cadre' cellpadding='2'>";

		echo "<tr><th align='center' colspan='2'>";
		if (empty($ID)){
			echo $LANG['mailgate'][1];
		} else {
			echo $LANG['mailgate'][0].": ".$this->fields["ID"];
		}

		echo "</th></tr>";
		if (!function_exists('mb_list_encodings')||!function_exists('mb_convert_encoding')){
			echo "<tr class='tab_bg_1'><td align='center' colspan='2'>";
			echo $LANG['mailgate'][4];
			echo "</td></tr>";
		}
		echo "<tr class='tab_bg_2'><td>".$LANG['common'][16].":	</td><td>";
		autocompletionTextField("name","glpi_mailgate","name",$this->fields["name"],40);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td>".$LANG['entity'][0].":	</td><td>";
		dropdownValue("glpi_entities", "FK_entities",$this->fields["FK_entities"],1,$_SESSION['glpiactiveentities']);
		echo "</td></tr>";

		showMailServerConfig($this->fields["host"]);

		echo "<tr class='tab_bg_2'><td>".$LANG['login'][6].":	</td><td>";
		autocompletionTextField("login","glpi_mailgate","login",$this->fields["login"],40);
		echo "</td></tr>";

		echo "<tr class='tab_bg_2'><td>".$LANG['login'][7].":	</td><td>";
		echo "<input type='password' name='password' value='' size='20'>";
		echo "</td></tr>";


		if (haveRight("config","w")) {

			echo "<tr class='tab_bg_1'>";
			if(empty($ID)){

				echo "<td valign='top' colspan='2'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";
			} else {

				echo "<td valign='top' align='center'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
				echo "</td>";
				echo "<td valign='top'>\n";
				echo "<div class='center'>";
				echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
				echo "</div>";
				echo "</td>";
				echo "</tr>";
				echo "<tr class='tab_bg_1'><td colspan='2' align='center'><input type='submit' name='get_mails' value=\"".$LANG['mailgate'][2]."\" class='submit'>";
				echo "</td></tr>";
			}


		}

		echo "</table></form></div>";

		return true;	

	}
	
}


// modif and debug by  INDEPNET Development Team.
// Merge with collect GLPI system after big modification in it
/* Original class ReceiveMail 1.0 by Mitul Koradia Created: 01-03-2006
 * Description: Reciving mail With Attechment
 * Email: mitulkoradia@gmail.com
 */
/// Mailcollect class
class MailCollect {
	/// working entity
	var $entity;
	/// working charset of the mail
	var $charset="";

	/// IMAP / POP server
	var $server='';
	/// IMAP / POP login
	var $username='';
	/// IMAP / POP password
	var $password='';
	
	/// IMAP / POP connection
	var $marubox='';					
	
	/// ID of the current message
	var $mid = -1;
	/// structure used to store the mail structure
	var $structure = false;
	/// structure used to store files attached to a mail
	var $files; 	
	/// Message to add to body to build ticket
	var $addtobody; 
	/// Number of ferchs emails
	var $fetch_emails=0;
        /// Body converted 
        var $body_converted=false;

	/**
	* Constructor
	* @param $server IMAP/POP server address
	* @param $username IMAP/POP user name
	* @param $password IMAP/POP password
	* @param $entity entity ID used
	* @param $display display messages in MessageAfterRedirect or just return error
	* @return if $display = false return messages result string
	*/
	function collect($server,$username,$password,$entity,$display=0,$mailgateID=0){
		global $LANG;
		$this->entity=$entity;

		$this->server	=	$server;
		$this->username	=	$username;
		$this->password	=	$password;
		$this->mid	= -1;

		$this->fetch_emails = 0;
		//Connect to the Mail Box
		$this->connect();

		if ($this->marubox){
			// Get Total Number of Unread Email in mail box
			$tot=$this->getTotalMails(); //Total Mails in Inbox Return integer value
			$error=0;

			for($i=1;$i<=$tot && $i<= MAX_MAILS_RETRIEVED;$i++){
				$tkt= $this->buildTicket($i);
				$tkt['_mailgate']=$mailgateID;

				$this->deleteMails($i); // Delete Mail from Mail box
				$result=imap_fetchheader($this->marubox,$i);

				// Is a mail responding of an already existgin ticket ?
				if (isset($tkt['tracking']) ) {
					// Deletion of message with sucess
					if (false === is_array($result)){
						$fup=new Followup();
						$fup->add($tkt);
					} else {
						$error++;
					}
				} else { // New ticket
					// Deletion of message with sucess
					if (false === is_array($result)){
						$track=new Job();
						$track->add($tkt);
					} else {
						$error++;
					}
				}
				$this->fetch_emails++;
			}
			imap_expunge($this->marubox);
			$this->close_mailbox();   //Close Mail Box

			if ($display){
				if ($error==0){
					addMessageAfterRedirect($LANG['mailgate'][3].": ".$this->fetch_emails);
				} else {
					addMessageAfterRedirect($LANG['mailgate'][3].": ".$this->fetch_emails." ($error ".$LANG['common'][63].")",false,ERROR);
				}
			} else {
				return "Number of messages available and collected : ".$this->fetch_emails." ".($error>0?"($error error(s))":"");
			}
			
		}else{
			if ($display){
				addMessageAfterRedirect($LANG['log'][41],false,ERROR);
			} else {
				return "Could not connect to mailgate server";
			}
//			return 0;
		}
	} // end function MailCollect
	
	
	
	/** function buildTicket - Builds,and returns, the major structure of the ticket to be entered . 
	* @param $i mail ID
	* @return ticket fields array
	*/
	function buildTicket($i){
		global $DB,$LANG,$CFG_GLPI;
	
		$head=$this->getHeaders($i);  // Get Header Info Return Array Of Headers **Key Are (subject,to,toOth,toNameOth,from,fromName)

		$tkt= array ();

		// max size = 0 : no import attachments
		if ($CFG_GLPI['mailgate_filesize_max']>0){
			if (is_writable(GLPI_DOC_DIR."/_tmp/")){
				$_FILES=$this->getAttached($i,GLPI_DOC_DIR."/_tmp/",$CFG_GLPI['mailgate_filesize_max']);
			} else {
				logInFile('mailgate',GLPI_DOC_DIR."/_tmp/ is not writable");
			}
		}

		//  Who is the user ?
		$tkt['author']=0;
		$query="SELECT ID 
			FROM glpi_users 
			WHERE email='".$head['from']."'";
		$result=$DB->query($query);
		if ($result&&$DB->numrows($result)){
			$tkt['author']=$DB->result($result,0,"ID");
		}

		// AUto_import
		$tkt['_auto_import']=1;
		// For followup : do not check author = login user
		$tkt['_do_not_check_author']=1;

		$body=$this->getBody($i);


		// Do it before using charset variable
		$head['subject']=$this->decodeMimeString($head['subject']);

		if (!$this->body_converted&&!empty($this->charset)&&function_exists('mb_convert_encoding')){
			$body=mb_convert_encoding($body, 'utf-8',$this->charset);
			$this->body_converted=true;
		}
		if (!seems_utf8($body)){
			$tkt['contents']= utf8_encode($body);	
		}else{
			$tkt['contents']= $body;
		}
		// Add message from getAttached
		if ($this->addtobody) {
			$tkt['contents'] .= $this->addtobody;
		}
		
		//// Detect if it is a mail reply
		$glpi_message_match="/GLPI-([0-9]+)\.[0-9]+\.[0-9]+@\w*/";
		// See In-Reply-To field
		if (isset($head['in_reply_to'])){
			if (preg_match($glpi_message_match,$head['in_reply_to'],$match)){
				$tkt['tracking'] = (int)$match[1];
			}
		}
		// See in References
		if (!isset($tkt['tracking']) && isset($head['references'])){
			if (preg_match($glpi_message_match,$head['references'],$match)){
				$tkt['tracking'] = (int)$match[1];
			}
		}

		// See in title
		if (!isset($tkt['tracking']) && preg_match('/\[GLPI #(\d+)\]/',$head['subject'],$match)){
				$tkt['tracking']=(int)$match[1];
		}
		// Found ticket link
		if ( isset($tkt['tracking']) ) {
			// it's a reply to a previous ticket
			$job=new Job();

			// Check if tracking exists and author exists in GLPI
			/// TODO check if author have right to add a followup to the ticket
			if ( $job->getFromDB($tkt['tracking'])
               &&  ($tkt['author'] > 0 || !strcasecmp($job->fields['uemail'],$head['from'])) ) {
		
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
/*						if (isset($val[0])&&$val[0]=='-'
							&&isset($val[1])&&$val[1]=='-' 
							&&count($tokeep)){
							
							break;
						} else {
*/
							$to_keep[$ID]=$ID;
//						}
					}
				}
				foreach($to_keep as $ID ){
					$tkt['contents'].=$content[$ID]."\n";
				}
			} else {
				unset($tkt['tracking']);
			}
		}

                if ( ! isset($tkt['tracking']) ) {
			// Mail followup
			$tkt['uemail']=$head['from'];
			$tkt['emailupdates']=1;
			// Which entity ?
			$tkt['FK_entities']=$this->entity;
	
			//$tkt['Subject']= $head['subject'];   // not use for the moment
			$tkt['name']=$this->textCleaner($head['subject']);
			// Medium
			$tkt['priority']= "3";
			// No hardware associated
			$tkt['device_type']="0";
			// Mail request type
			$tkt['request_type']="2";
		} else {
			// Reopen if needed
			$tkt['add_reopen']=1;
		}

		$tkt['contents']=clean_cross_side_scripting_deep(html_clean($tkt['contents']));

		$tkt=addslashes_deep($tkt);

		return $tkt;
	}


	/** function textCleaner - Strip out unwanted/unprintable characters from the subject. 
	* @param $text text to clean
	* @return clean text
	*/
	function textCleaner($text)
	{
		//$text= str_replace("'", "", $text);
		$text= str_replace("=20", "\n", $text);
		return $text;
	}


	///return supported encodings in lowercase.
	function mb_list_lowerencodings() { 
		$r=mb_list_encodings();
		for ($n=sizeOf($r); $n--; ) { 
			$r[$n]=strtolower($r[$n]); 
		} 
		return $r;
	}
	
	/**  Receive a string with a mail header and returns it
	// decoded to a specified charset.
	// If the charset specified into a piece of text from header
	// isn't supported by "mb", the "fallbackCharset" will be
	// used to try to decode it.
	* @param $mimeStr mime header string
	* @param $inputCharset input charset
	* @param $targetCharset target charset
	* @param $fallbackCharset charset used if input charset not supported by mb
	* @return decoded string
	*/
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

	 ///Connect To the Mail Box
	function connect()
	{
		$this->marubox=@imap_open($this->server,$this->username,$this->password, 1);
	}

	/**
	 * get the message structure if not already retrieved
	 * 
	 * @param $mid : Message ID.
	 * 
	 */
	 function getStructure ($mid)
	 {
	 	if ($mid != $this->mid || !$this->structure) {
			$this->structure = imap_fetchstructure($this->marubox,$mid);
			if ($this->structure) {
				$this->mid = $mid;
			}
	 	}
	 }
	/**
	*This function is use full to Get Header info from particular mail
	*
	* @param $mid               = Mail Id of a Mailbox
	*
	* @return Return Associative array with following keys
	*	subject   => Subject of Mail
	*	to        => To Address of that mail
	*	toOth     => Other To address of mail
	*	toNameOth => To Name of Mail
	*	from      => From address of mail
	*	fromName  => Form Name of Mail
	*/
	function getHeaders($mid) // Get Header info
	{	
		$mail_header=imap_header($this->marubox,$mid);
		
		$sender=$mail_header->from[0];
		//$sender_replyto=$mail_header->reply_to[0];
		if(strtolower($sender->mailbox)!='mailer-daemon' && strtolower($sender->mailbox)!='postmaster')
		{
			$mail_details=array(
					'from'=>strtolower($sender->mailbox).'@'.$sender->host,
					'subject'=>$mail_header->subject,
					//'fromName'=>$sender->personal,
					//'toOth'=>strtolower($sender_replyto->mailbox).'@'.$sender_replyto->host,
					//'toNameOth'=>$sender_replyto->personal,
					//'to'=>strtolower($mail_header->toaddress)
				);
			if (isset($mail_header->references)){
					$mail_details['references'] = $mail_header->references;
			}
			if (isset($mail_header->in_reply_to)){
					$mail_details['in_reply_to'] = $mail_header->in_reply_to;
			}
		}
		return $mail_details;
	}

	/**Get Mime type Internal Private Use
	* @param $structure mail structure
	* @return mime type
	*/
	function get_mime_type(&$structure) { 
		$primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"); 
		
		if($structure->subtype) { 
			return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype; 
		} 
		return "TEXT/PLAIN"; 
	}
	
	
	/**Get Part Of Message Internal Private Use
	* @param $stream An IMAP stream returned by imap_open
	* @param $msg_number The message number
	* @param $mime_type mime type of the mail
	* @param $structure struture of the mail
	* @param $part_number The part number.
	* @return data of false if error
	*/
	function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) { 
		if($structure) { 		
			if($mime_type == $this->get_mime_type($structure)){ 
				if(!$part_number) { 
					$part_number = "1"; 
				} 
				$text = imap_fetchbody($stream, $msg_number, $part_number); 
				if($structure->encoding == 3) { 
					$text =  imap_base64($text); 
				} else if($structure->encoding == 4) { 
					$text =  imap_qprint($text); 
				} 
                                 //else { return $text; } 
                                 if( $structure->subtype && $structure->subtype=="HTML"){ 
					$text = str_replace("\r","",$text);  
					$text = str_replace("\n","",$text);  
				}

				if (count($structure->parameters)>0){ 
   				  foreach ($structure->parameters as $param){ 
				    if ((strtoupper($param->attribute)=='CHARSET') && function_exists('mb_convert_encoding') && strtoupper($param->value) != 'UTF-8'){ 
                                       $text = mb_convert_encoding($text, 'utf-8',$param->value);
				       $this->body_converted=true; 
                                       } 
                                   } 
                                } 
                            return $text;
			} 
			if($structure->type == 1){ /* multipart */ 
				$prefix="";
				reset($structure->parts);
				while(list($index, $sub_structure) = each($structure->parts)){ 
					if($part_number){ 
						$prefix = $part_number . '.'; 
					} 
					$data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1)); 
					if($data){ 
						return $data; 
					} 
				} 
			} 
		} 
		return false; 
	} 
	
	/**
	 * used to get total unread mail from That mailbox
	 *
	 * Return : 
	 * Int Total Mail
	 */	
	function getTotalMails() //Get Total Number off Unread Email In Mailbox
	{
		$headers=imap_headers($this->marubox);
		return count($headers);
	}

	/**
	*GetAttech($mid,$path) / Prefer use getAttached
	*Save attached file from mail to given path of a particular location
	*
	* @param $mid mail id
	* @param $path path where to save
	*
	* @return  String of filename with coma separated
	*like a.gif,pio.jpg etc
	*/	
	function GetAttech($mid,$path) {
		$struckture = imap_fetchstructure($this->marubox,$mid);
		$ar="";
		if (isset($struckture->parts)&&count($struckture->parts)>0){
			foreach($struckture->parts as $key => $value)
			{
				$enc=$struckture->parts[$key]->encoding;
				if($struckture->parts[$key]->ifdparameters)
				{
					$name=$struckture->parts[$key]->dparameters[0]->value;
					$message = imap_fetchbody($this->marubox,$mid,$key+1);
					if ($enc == 0)
						$message = imap_8bit($message);
					if ($enc == 1)
						$message = imap_8bit ($message);
					if ($enc == 2)
						$message = imap_binary ($message);
					if ($enc == 3)
						$message = imap_base64 ($message); 
					if ($enc == 4)
						$message = quoted_printable_decode($message);
					if ($enc == 5)
						$message = $message;
					$fp=fopen($path.$name,"w");
					fwrite($fp,$message);
					fclose($fp);
					$ar=$ar.$name.",";
				}
			}
		}
		$ar=substr($ar,0,(strlen($ar)-1));
		return $ar;
	}
	
	/**
	 * Private function : Recursivly get attached documents
	 * 
	 * @param $mid : message id
	 * @param $path : temporary path
	 * @param $maxsize : of document to be retrieved
	 * @param $structure : of the message or part
	 * @param $part : part for recursive
	 * 
	 * Result is stored in $this->files
	 *  
	 */
	function getRecursiveAttached ($mid, $path, $maxsize, $structure, $part="")
	{
		global $LANG;
		
		if ($structure->type == 1) { // multipart
			reset($structure->parts);
			while(list($index, $sub) = each($structure->parts)) {
				$this->getRecursiveAttached($mid, $path, $maxsize, $sub, ($part ? $part.".".($index+1) : ($index+1)));
			}
		} else if ($structure->ifdparameters) {

			//get filename of attachment if present
			$filename='';
			// if there are any dparameters present in this part
			if (count($structure->dparameters)>0){
			foreach ($structure->dparameters as $dparam){
				if ((strtoupper($dparam->attribute)=='NAME') ||(strtoupper($dparam->attribute)=='FILENAME')) $filename=$dparam->value;
				}
			}
			//if no filename found
			if ($filename==''){
				// if there are any parameters present in this part
				if (count($structure->parameters)>0){
					foreach ($structure->parameters as $param){
					if ((strtoupper($param->attribute)=='NAME') ||(strtoupper($param->attribute)=='FILENAME')) $filename=$param->value;
					}
				}
			}
			$filename=$this->decodeMimeString($filename);

			if ($structure->bytes > $maxsize) {
				$this->addtobody .= "<br>".$LANG['mailgate'][6]." (" . getSize($structure->bytes) . "): ".$filename;
				return false;
			}
			if (!isValidDoc($filename)){
				$this->addtobody .= "<br>".$LANG['mailgate'][5]." (" . $this->get_mime_type($structure) . "): ".$filename;
				return false;
			}
			if ($message=imap_fetchbody($this->marubox, $mid, $part)) {
				switch ($structure->encoding)
				{
					// case 0:	$message = imap_7bit($message); break;
					case 1:	$message = imap_8bit($message); break;
					case 2: $message = imap_binary($message); break;
					case 3: $message = imap_base64($message); break;
					case 4: $message = quoted_printable_decode($message); break;
				}	
				$fp=fopen($path.$filename,"w");
				if ($fp) {
					fwrite($fp,$message);
					fclose($fp);	
					
					$this->files['multiple'] = true;
					$j = count($this->files)-1;
					$this->files[$j]['filename']['size'] = $structure->bytes;
					$this->files[$j]['filename']['name'] = $filename;
					$this->files[$j]['filename']['tmp_name'] = $path.$filename;
					$this->files[$j]['filename']['type'] = $this->get_mime_type($structure);	
				}
			} // fetchbody
		} // ifdparameters 
	}

	/**
	 * Public function : get attached documents in a mail
	 * 
	 * @param $mid : message id
	 * @param $path : temporary path
	 * @param $maxsize : of document to be retrieved
	 * 
	 * @return array like $_FILES
	 *  
	 */
	function getAttached ($mid, $path, $maxsize)
	{
		$this->getStructure($mid);
		$this->files = array();
		$this->addtobody="";
		$this->getRecursiveAttached($mid, $path, $maxsize, $this->structure);
		
		return ($this->files);
	}
	/**
	 * Get The actual mail content from this mail
	 *
	 * @param $mid : mail Id
	 */
	function getBody($mid) // Get Message Body
	{
		$this->getStructure($mid);

		$body = $this->get_part($this->marubox, $mid, "TEXT/HTML", $this->structure);
		if ($body == "") {
			$body = $this->get_part($this->marubox, $mid, "TEXT/PLAIN", $this->structure);			
		}
		if ($body == "") { 
			return "";
		}
		return $body;
	}
	
	/**
	 * Delete mail from that mail box
	 *
	 * @param $mid : mail Id
	 */
	function deleteMails($mid) {
		imap_delete($this->marubox,$mid);
	}
	
	/**
	 * Close The Mail Box
	 *  
 	 */	
	function close_mailbox() {
		imap_close($this->marubox,CL_EXPUNGE);
	}

}

?>
