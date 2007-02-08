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



if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


require_once(GLPI_ROOT . "/lib/mail/receivemail.class.php");


class MailCollect  extends receivemail {


/**
	 * Constructor
*/
function MailCollect(){
	
		$this->receiveMail('glpi@indepnet.org','glpiindepnet','glpi@indepnet.org','oberon.indepnet.net','pop3','110');
		//Connect to the Mail Box
		$this->connect();
		echo "toto";
		// Get Total Number of Unread Email in mail box
		$tot=$this->getTotalMails(); //Total Mails in Inbox Return integer value
		echo "Total Mails:: $tot<br>";
		if (isset ($tot))
		{
				
			for($i=1;$i<=$tot;$i++)
			{

				
				$tkt= $this->buildTicket($i);
				
				//$this->deleteMails($i); // Delete Mail from Mail box
				
				print_r($tkt);
				$track=new job;
				$track->add($tkt);
			}
					
		}
		$this->close_mailbox();   //Close Mail Box
	
} // end function MailCollect



/* *** Primary Functions ***** *
 * Functions called directly from the Script Flow portion of the script. */


/* function buildTicket - Builds,and returns, the major structure of the ticket to be entered . */
function buildTicket($i)
{
	global $DB;

	$head=$this->getHeaders($i);  // Get Header Info Return Array Of Headers **Key Are (subject,to,toOth,toNameOth,from,fromName)
	echo "<br>----------------------------------------- Header  -------------------------------------------------<BR>";
	echo "Subjects :: ".$head['subject']."<br>";
	echo "TO :: ".$head['to']."<br>";
	echo "To Other :: ".$head['toOth']."<br>";
	echo "ToName Other :: ".$head['toNameOth']."<br>";
	echo "From :: ".$head['from']."<br>";
	echo "FromName :: ".$head['fromName']."<br>";
	
	echo "<br>----------------------------------------- BODY -------------------------------------------------<BR>";
	echo $this->getBody($i);  // Get Body Of Mail number Return String Get Mail id in interger
	/*$str=$obj->GetAttech($i,"./"); // Get attached File from Mail Return name of file in comma separated string  args. (mailid, Path to store file)
	$ar=explode(",",$str);
	foreach($ar as $key=>$value)
		echo ($value=="")?"":"Atteched File :: ".$value."<br>";
	*/
	echo "<br><BR>";
	echo "<br>******************************************************************************************<BR>";	
	


	$tkt= array ();
	
	//  Who is the user ?
	$query="SELECT ID from glpi_users WHERE email='".$head['from']."'";
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

	//$tkt['Subject']= $head['subject'];   // PAS ENCORE UTILISE pour le sujet mais  CA VA VENIR en attendant on le met dans le content
	$tkt['contents']=textCleaner($head['subject'])." : ";
	$tkt['priority']= "3";
	$tkt['device_type']="0";
	$tkt['request_type']="2";
	$tkt['contents'].= $this->getBody($i);
	
	// TODO récupérer le charset et encoder en utf8 si nécessaire
	
	return addslashes_deep($tkt);
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
