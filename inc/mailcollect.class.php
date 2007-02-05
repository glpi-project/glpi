<?php
/*
 * @version $Id: display.function.php 4323 2007-01-18 23:12:38Z jmd $
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
// Adapted from mailGate.php  
//Original Author of file: Author: Chris Rothbauer
// Copyright 2006 OneOrZero
// http://www.oneorzero.com
 // Developers: OneOrZero Team / Contributors: OneOrZero Community
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


require_once(GLPI_ROOT . "/lib/mail/mimeDecode.php");


class MailCollect
{

//Requires: mimeDecode.php, Mail_Mime PEAR extensions

/***************************************************************************
 *  There are several configurable options.
 *  Features and Options:
 *
 *    1. Collect email from an input stream (ie. Sendmail) or a pop3 server.
 *       To collect email via a sendmail, alter the aliases file to look like the following...
 *           helpdesk: "|php /path/to/mailGate.php"
 *       To collect email via a pop3 server, setup a cron job to collect emails.
 *
 *    2. Text only ticket descriptions, even from html multipart messages. However, if the
 *       email client is configured to send HTML only email, no suitable description will
 *       be gathered and the script will indicate such.
 *
 *    3. Parsing of attached files and embedded images.
 *
 /***************************************************************************/

var $input="pop3";    // Can be either "stream" or "pop3"
var $popHost= "";    // Mail server hostname
var $popPort= "110" ;    // Mail server port
var $popUser= "" ;   // Mail server username
var $popPass= "" ;  // Mail server password
var $popConn= 0;
var $gotMail= false;


/**
	 * Constructor
*/
function MailCollect(){
	
	if ($this->input == "stream")
	{
		$email= $this->getStream();
		if (isset ($email))
		{
			$msg= $this->mimeDecode($email);
		}
		if (isset ($msg))
		{
			$tkt= $this->buildTicket($msg);
		}
		if (isset ($tkt))
		{
			//getTicketContent($msg);
			//$tid= writeTicket();
		}
		
		print_r($tkt);
				$track=new job;
				$track->add($tkt);
	}
	elseif ($this->input == "pop3")
	{
		if ($this->popConnect())
		{
			$emailArray=$this->getMessages();
		}
		if (isset ($emailArray))
		{
				
			foreach ($emailArray as $email)
			{
				$msg= $this->mimeDecode($email);
				if (isset ($msg))
				{
					$tkt= $this->buildTicket($msg);
				}
				
				if (isset ($tkt))
				{
					//$this->getTicketContent($msg,$tkt);
					//$tid= writeTicket();
				}
					
				print_r($tkt);
				$track=new job;
				$track->add($tkt);
				}
					
			}
			$this->popDisconnect();
		
	}
	else
	{
		print "Input method not set. \n";
	}

} // end function MailCollect



/* *** Primary Functions ***** *
 * Functions called directly from the Script Flow portion of the script. */

/* function getStream - Collects email input, as a data stream from STDIN. Returns a single email. */
function getStream()
{
	$in= fopen("php://stdin", "r");
	$email= "";
	while (!feof($in))
	{
		$email .= fgets($in);
	}
	fclose($in);
	return $email;
}

/* function popConnect - Connects to the designated pop3 mail server, using the supplied credentials. */
function popConnect()
{
	

	$this->popConn= fsockopen($this->popHost, $this->popPort, $errno, $errstr, 30);
	if (!$this->popConn)
	{
		print "Connect Failed: $errstr($errno)\n";
		return 0;
	}
	else
	{
		$output= fgets($this->popConn, 128);
		print "$output\n<br>";
		fputs($this->popConn, "USER $this->popUser\r\n");
		$output= fgets($this->popConn, 128);
		print "$output\n<br>";
		fputs($this->popConn, "PASS $this->popPass\r\n");
		$output= fgets($this->popConn, 128);
		print "$output\n<br>";
		return 1;
	}
}


/* function getMessages - Collects email messages from a pop3 server, and returns them as an array of messages.
 *                        Calls function getEmail to download each individual message. Returns an array of 
 *                        of email messages. */
function getMessages()
{
	
	

	$numMessages=$this-> checkForMail();

	if ($this->gotMail)
	{
		$mailArray= array ();
		for ($i= 0; $i < $numMessages; $i ++)
		{
			$mailArray[$i]= $this->getEmail($i +1);
			$this->deleteMessage($i +1);
		}
		return $mailArray;
	}
}

/* function popDisconnect - Disconnects the pop3 session. */
function popDisconnect()
{
	

	
	fputs($this->popConn, "QUIT\r\n");
	$output= fgets($this->popConn, 128);
	print "$output\n";
	fclose($this->popConn);
	$this->popConn= 0;
}

/* function mimeDecode - decodes the MIME message of the supplied email. Returns an array of all
 *                       components of the message. */
function mimeDecode($email)
{
	$p['include_bodies']= true;
	$p['include_headers']= true;
	$p['decode_headers']= true;
	$p['crlf']= "\r\n";
	$p['input']= $email;

	$msg= Mail_mimeDecode :: decode($p);
	return $msg;
}


/* function buildTicket - Builds,and returns, the major structure of the ticket to be entered . */
function buildTicket($msg)
{
		global $DB;

	$head= $msg->headers;
	$from= $this->getFrom($head);
	$uname= explode("@", $from);
	$subject= $this->textCleaner($head['subject']);

	$tkt= array ();
	
	// $tkt['UserName']= $uname[0]; Pas utilisé mais ça peut ;)
	
	//  Who is the user ?
	$query="SELECT ID from glpi_users WHERE email='$from'";
	$result=$DB->query($query);
	$glpiID="";
		if ($result&&$DB->numrows($result))
			$glpiID=$DB->result($result,0,"ID");
	$tkt['author']=$glpiID;
	
	// Which entity ?
	// Que fait-on si l'utilisateur est inconnu et s'il est rattaché à plusieurs entités ?
	$query="SELECT FK_entities from glpi_users_profiles WHERE FK_users='$glpiID'";
	$result=$DB->query($query);
	$FK_entities="";
		if ($result&&$DB->numrows($result))
			$FK_entities=$DB->result($result,0,"FK_entities");
	$tkt['FK_entities']=$FK_entities;

	//$tkt['Subject']= $subject;   // PAS ENCORE UTILISE pour le sujet mais  CA VA VENIR en attendant on le met dans le content
	$tkt['contents']=$subject." : ";
	$tkt['priority']= "3";
	$tkt['device_type']="0";
	$tkt['request_type']="2";
	if ($msg->ctype_primary == "text")
	{
		$tkt['contents'].= $msg->body;
	}

	
	return $tkt;
}



//============== Fonction non utilisée pour le moment ======================
/* function getTicketContent - Reads all the parts of the decoded message and assigns attachments, 
 *                             description, and subject of the created/updated ticket. Saves updates
 *                             to the  $tkt array.
 */
function getTicketContent($msg,$tkt)
{
	//global $tkt, $time, $attach, $path, $noThreads;
	
	
	foreach ($msg->parts as $part)
	{
		if (($part->ctype_primary == 'text') and ($part->ctype_secondary == 'plain'))
		{
			$tkt['contents']= $part->body;
		}
		if ($part->ctype_primary == 'image')
		{
			$enc= $part->headers['content-transfer-encoding'];
			$fileName= $this->fileNameCleaner("image.".$part->ctype_secondary);
			$fileDest= GLPI_DOC_DIR."/_uploads/".$fileName;
			$fileSize= $this->writeFile($enc, $fileDest, $part->body);

			$tkt[]= array ("filename" => $fileName, "filetype" => $part->ctype_primary."/".$part->ctype_secondary, "filesize" => $fileSize);
		}
		elseif (isset ($part->disposition) and ($part->disposition == 'attachment'))
		{
			if (isset ($part->ctype_parameters['filename']))
			{
				$fileName= $part->ctype_parameters['filename'];
			}
			elseif (isset ($part->ctype_parameters['name']))
			{
				$fileName= $part->ctype_parameters['name'];
			}
			else
			{
				$fileName= "not_named";
			}
			$enc= $part->headers['content-transfer-encoding'];
			$fileName= $this->fileNameCleaner($fileName);
			$fileDest= GLPI_DOC_DIR."/_uploads/".$fileName;
			$fileSize=$this->writeFile($enc, $fileDest, $part->body);
			$tkt[]= array ("filename" => $fileName, "filetype" => $part->ctype_primary."/".$part->ctype_secondary, "filesize" => $fileSize);
		}
		elseif (isset ($part->disposition) and ($part->disposition == 'inline'))
		{
			if (isset ($part->ctype_parameters['filename']))
			{
				$fileName= $part->ctype_parameters['filename'];
			}
			elseif (isset ($part->ctype_parameters['name']))
			{
				$fileName= $part->ctype_parameters['name'];
			}
			else
			{
				$fileName= "not_named";
			}
			$enc= $part->headers['content-transfer-encoding'];
			$fileName= $this->fileNameCleaner($fileName);
			$fileDest= GLPI_DOC_DIR."/_uploads/".$fileName;
			$fileSize= $this->writeFile($enc, $fileDest, $part->body);
			$tkt[]= array ("filename" => $fileName, "filetype" => $part->ctype_primary."/".$part->ctype_secondary, "filesize" => $fileSize);
		}
		if (isset ($part->parts) and (is_array($part->parts)))
		{
			$this->getTicketContent($part,$tkt);
		}
	}
	if ($tkt['contents'] == "")
	{
		$tkt['contents']= "No suitable message found";
	}
	$tkt['contents']=$this->textCleaner($tkt['contents']);
	
	return $tkt;
}

//============== Fin de la Fonction non utilisée pour le moment ======================






/* *** Secondary Functions *** *
 * Functions called from within other Functions */

/* function checkForMail - Check for the existence of email on the pop3 server. */
function checkForMail()
{
	

	fputs($this->popConn, "STAT\r\n");
	$output= fgets($this->popConn, 128);
	$ack= strtok($output, " "); // Bleed off +OK
	$numMessages= strtok(" "); // Get what we wanted

	print "Ack: $ack, Num Messages: $numMessages \n<br>";

	if ($numMessages > 0)
	{
		print "***New mail***\n<br>";
		$this->gotMail= true;
	}
	else
	{
		print "***No mail***\n";
		$this->gotMail= false;
	}
	return $numMessages;
}

/* function getEmail - Collect each individual message and return to the getMessages function. */
function getEmail($num)
{
	
	
	$message= "";
	fputs($this->popConn, "RETR $num\r\n");
	$output= fgets($this->popConn, 512);

	if (strtok($output, "+OK"))
	{
		while (!ereg("^\.\r\n", $output))
		{
			$output= fgets($this->popConn, 512);
			$message .= $output;
		}
		return $message;
	}
}

/* function deleteMessage - Delete messages from pop3 server after downloading them. */
function deleteMessage($message)
{
	
	//fputs($this->popConn, "DELE $message\r\n");
}

/* function getFrom - Quickly pull out the email address of the sender. */
function getFrom($head)
{
	$regex= '\<*[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,6})\>*';
	if (array_key_exists('reply-to', $head) and ereg($regex, $head['reply-to']))
	{
		$from= $head['reply-to'];
	}
	elseif (array_key_exists('return-path', $head) and ereg($regex, $head['return-path']))
	{
		$from= $head['return-path'];
	}
	elseif (array_key_exists('from', $head) and ereg($regex, $head['from']))
	{
		$from= $head['from'];
	}

	$from= ereg_replace("^(.*)<", "", $from);
	$from= str_replace(array ("<", ">", " "), "", $from);

	return $from;
}

/* function writeFile - Write attached file, to the filesystem, using the proper encoding mechanism. */
function writeFile($enc, $fileDest, $body)
{
	$fp= fopen($fileDest, 'w');
	if ($enc == 'base64')
	{
		$fileSize= fwrite($fp, base64_decode($body));
	}
	else
	{
		$fileSize= fwrite($fp, $body);
	}
	fclose($fp);
	return $fileSize;
}

/* function fileNameCleaner - Strip out unwanted/unprintable, characters from filenames. */
function fileNameCleaner($fileName)
{
	$mtime= explode(" ", microtime());
	$mtime= $mtime[1].substr($mtime[0], 5, 3);

	$fileName= preg_replace('/[^a-zA-Z0-9\.\$\%\'\`\-\@\{\}\~\!\#\(\)\&\_\^]/', '', str_replace(array (' ', '%20'), array ('_', '_'), $fileName));
	$fileName= str_replace("'", "", $fileName);
	$fileName= $mtime."_".$fileName;

	return $fileName;

}

/* function textCleaner - Strip out unwanted/unprintable characters from the subject. */
function textCleaner($text)
{
	$text= str_replace("'", "", $text);
	$text= str_replace("=20", "\n", $text);
	return $text;
}







}

?>