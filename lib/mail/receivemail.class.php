<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

// modif and debug by  INDEPNET Development Team.


// Main ReciveMail Class File - Version 1.0 (01-03-2006)
/*
 * File: recivemail.class.php
 * Description: Reciving mail With Attechment
 * Version: 1.0
 * Created: 01-03-2006
 * Author: Mitul Koradia
 * Email: mitulkoradia@gmail.com
 * Cell : +91 9879697592
 */
class receiveMail
{
	var $server='';
	var $username='';
	var $password='';
	
	var $marubox='';					
	
	var $email='';			
	
/*
* constructor
* Arguments are
 * $username                = User name off the mail box
 * $password                = Password of mailbox
 * $emailAddress            = Email address of that mailbox some time the uname and email address are identical
 * $mailserver              = Ip or name of the POP or IMAP mail server
 * $servertype              = if this server is imap or pop default is pop
 * $port                    = Server port for pop or imap Default is 110 for pop and 143 for imap
*/
/*	function receiveMail($username,$password,$EmailAddress,$mailserver='localhost',$servertype='pop',$port='110') //Constructure
	{
		if($servertype=='imap')
		{
			if($port=='') $port='143'; 
			$strConnect='{'.$mailserver.':'.$port. '/novalidate-cert}INBOX'; 
		}
		else
		{
			$strConnect='{'.$mailserver.':'.$port. '/pop3/novalidate-cert}INBOX'; 
		}
		$this->server			=	$strConnect;
		$this->username			=	$username;
		$this->password			=	$password;
		$this->email			=	$EmailAddress;
	}
*/
	// Special adding for GLPI
	function set($username,$password,$server) //Constructure
	{
		$this->server			=	$server;
		$this->username			=	$username;
		$this->password			=	$password;
	}

	function connect() //Connect To the Mail Box
	{
		$this->marubox=imap_open($this->server,$this->username,$this->password);
	}
	
	
/*	
*This function is use full to Get Header info from particular mail
*
*Arguments : 
*$mid               = Mail Id of a Mailbox
*
*Return :
*Return Associative array with following keys
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
		$sender_replyto=$mail_header->reply_to[0];
		if(strtolower($sender->mailbox)!='mailer-daemon' && strtolower($sender->mailbox)!='postmaster')
		{
			$mail_details=array(
					'from'=>strtolower($sender->mailbox).'@'.$sender->host,
					//'fromName'=>$sender->personal,
					//'toOth'=>strtolower($sender_replyto->mailbox).'@'.$sender_replyto->host,
					//'toNameOth'=>$sender_replyto->personal,
					'subject'=>$mail_header->subject,
					//'to'=>strtolower($mail_header->toaddress)
				);
		}
		return $mail_details;
	}
	

	function get_mime_type(&$structure) //Get Mime type Internal Private Use
	{ 
		$primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"); 
		
		if($structure->subtype) { 
			return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype; 
		} 
		return "TEXT/PLAIN"; 
	}
	
	

	function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) //Get Part Of Message Internal Private Use
	{ 
		if(!$structure) { 
			$structure = imap_fetchstructure($stream, $msg_number); 
		} 
		if($structure) { 
			if($mime_type == $this->get_mime_type($structure))
			{ 
				if(!$part_number) 
				{ 
					$part_number = "1"; 
				} 
				$text = imap_fetchbody($stream, $msg_number, $part_number); 
				if($structure->encoding == 3) 
				{ 
					return imap_base64($text); 
				} 
				else if($structure->encoding == 4) 
				{ 
					return imap_qprint($text); 
				} 
				else
				{ 
					return $text; 
				} 
			} 
			if($structure->type == 1) /* multipart */ 
			{ 
				$prefix="";
				while(list($index, $sub_structure) = each($structure->parts))
				{ 
					if($part_number)
					{ 
						$prefix = $part_number . '.'; 
					} 
					$data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1)); 
					if($data)
					{ 
						return $data; 
					} 
				} 
			} 
		} 
		return false; 
	} 
	
/*
*used to get total unread mail from That mailbox
*
*Return : 
*Int Total Mail
*/	
	function getTotalMails() //Get Total Number off Unread Email In Mailbox
	{
		$headers=imap_headers($this->marubox);
		return count($headers);
	}
	
/*
*GetAttech($mid,$path)
*Save attached file from mail to given path of a particular location
*
*Arguments :
* $mid         = mail id
* $path        = path where to save
*
*Return  :
* String of filename with coma separated
 *like a.gif,pio.jpg etc
*/	
	function GetAttech($mid,$path) // Get Atteced File from Mail
	{
		
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
	
/*	
* Get The actual mail content from this mail
* Arguments 
* $mid          = Mail id
* Return String
*/
	function getBody($mid) // Get Message Body
	{
		$body = $this->get_part($this->marubox, $mid, "TEXT/HTML");
		if ($body == "")
			$body = $this->get_part($this->marubox, $mid, "TEXT/PLAIN");
		if ($body == "") { 
			return "";
		}
		return $body;
	}
	
/*
* Delete mail from that mail box
*
* Arguments :
*	$mid         = mail Id
*/
	function deleteMails($mid) // Delete That Mail
	{
		imap_delete($this->marubox,$mid);
	}
	
/*
* Close The Mail Box
*/	
	function close_mailbox() //Close Mail Box
	{
		imap_close($this->marubox,CL_EXPUNGE);
	}
}
?>