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

function cron_mailgate(){
	global $DB,$CFG_GLPI;
	
	$already_retrieve=0;
	$query="SELECT * FROM glpi_mailgate";
	if ($result=$DB->query($query)){
		if ($DB->numrows($result)>0){
			$mc=new MailCollect();
			while ($data=$DB->fetch_assoc($result)){
				
				logInFile("cron","Collect mails from ".$data["host"]." for  ".getDropdownName("glpi_entities",$data["FK_entities"])."\n");
				$message=$mc->collect($data["host"],$data["login"],$data["password"],$data["FK_entities"]); 
 				logInFile("cron","$message\n");

				$already_retrieve+=$mc->fetch_emails;
				// Finish mailgate process but mark it to be redone
				if ($already_retrieve >= MAX_MAILS_RETRIEVED){
					return -1;
				}
			}
		}
	}
	return 1;
}
?>
