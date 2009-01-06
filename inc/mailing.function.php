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

function testMail(){
	global $CFG_GLPI,$LANG;
	$mmail=new glpi_phpmailer();
	$mmail->From=$CFG_GLPI["admin_email"];
	$mmail->FromName=$CFG_GLPI["admin_email"];
	$mmail->AddAddress($CFG_GLPI["admin_email"], "GLPI");
	$mmail->Subject="[GLPI] ".$LANG["mailing"][32];  
	$mmail->Body=$LANG["mailing"][31]."\n-- \n".$CFG_GLPI["mailing_signature"];

	if(!$mmail->Send()){
		addMessageAfterRedirect($LANG["setup"][206]);
	} else addMessageAfterRedirect($LANG["setup"][205]);
}

	function showFormMailingType($type, $profiles) {
		global $LANG, $DB;
	
		$options="";
		// Get User mailing
		$query = "SELECT glpi_mailing.FK_item as item, glpi_mailing.ID as ID 
				FROM glpi_mailing 
				WHERE glpi_mailing.type='$type' AND glpi_mailing.item_type='" . USER_MAILING_TYPE . "' 
				ORDER BY glpi_mailing.FK_item;";
		$result = $DB->query($query);
		if ($DB->numrows($result))
			while ($data = $DB->fetch_assoc($result)) {
				if (isset($profiles[USER_MAILING_TYPE."_".$data["item"]])) {
					unset($profiles[USER_MAILING_TYPE."_".$data["item"]]);
				}
				switch ($data["item"]) {
					case ADMIN_MAILING :
						$name = $LANG["setup"][237];
						break;
					case ADMIN_ENTITY_MAILING :
						$name = $LANG["setup"][237]." ".$LANG["entity"][0];
						break;
					case ASSIGN_MAILING :
						$name = $LANG["setup"][239];
						break;
					case AUTHOR_MAILING :
						$name = $LANG["job"][4];
						break;
					case USER_MAILING :
						$name = $LANG["common"][34] . " " . $LANG["common"][1];
						break;
					case OLD_ASSIGN_MAILING :
						$name = $LANG["setup"][236];
						break;
					case TECH_MAILING :
						$name = $LANG["common"][10];
						break;
					case RECIPIENT_MAILING :
						$name = $LANG["job"][3];
						break;
					case ASSIGN_ENT_MAILING :
						$name = $LANG["financial"][26];
						break;
					case ASSIGN_GROUP_MAILING :
						$name = $LANG["setup"][248];
						break;
					case SUPERVISOR_ASSIGN_GROUP_MAILING :
						$name = $LANG["common"][64]." ".$LANG["setup"][248];
						break;
					case SUPERVISOR_AUTHOR_GROUP_MAILING :
						$name = $LANG["common"][64]." ".$LANG["setup"][249];
						break;
					default :
						$name="&nbsp;";
						break;
				}
				$options.= "<option value='" . $data["ID"] . "'>" . $name . "</option>\n";
			}
		// Get Profile mailing
		$query = "SELECT glpi_mailing.FK_item as item, glpi_mailing.ID as ID, glpi_profiles.name as prof 
			FROM glpi_mailing 
			LEFT JOIN glpi_profiles ON (glpi_mailing.FK_item = glpi_profiles.ID) 
			WHERE glpi_mailing.type='$type' AND glpi_mailing.item_type='" . PROFILE_MAILING_TYPE . "' 
			ORDER BY glpi_profiles.name;";
		$result = $DB->query($query);
		if ($DB->numrows($result))
			while ($data = $DB->fetch_assoc($result)) {
				$options.= "<option value='" . $data["ID"] . "'>" . $LANG["profiles"][22] . " " . $data["prof"] . "</option>\n";
				if (isset($profiles[PROFILE_MAILING_TYPE."_".$data["item"]])) {
					unset($profiles[PROFILE_MAILING_TYPE."_".$data["item"]]);
				}
			}
	
		// Get Group mailing
		$query = "SELECT glpi_mailing.FK_item as item, glpi_mailing.ID as ID, glpi_groups.name as name 
			FROM glpi_mailing 
			LEFT JOIN glpi_groups ON (glpi_mailing.FK_item = glpi_groups.ID) 
			WHERE glpi_mailing.type='$type' AND glpi_mailing.item_type='" . GROUP_MAILING_TYPE . "' 
			ORDER BY glpi_groups.name;";
		$result = $DB->query($query);
		if ($DB->numrows($result))
			while ($data = $DB->fetch_assoc($result)) {
				$options.= "<option value='" . $data["ID"] . "'>" . $LANG["common"][35] . " " . $data["name"] . "</option>\n";
				if (isset($profiles[GROUP_MAILING_TYPE."_".$data["item"]])) {
					unset($profiles[GROUP_MAILING_TYPE."_".$data["item"]]);
				}
			}

		echo "<td class='right'>";
		if (count($profiles)) {
			echo "<select name='mailing_to_add_" . $type . "[]' multiple size='5'>";
		
			foreach ($profiles as $key => $val) {
				list ($item_type, $item) = split("_", $key);
				echo "<option value='$key'>" . $val . "</option>\n";
			}
			echo "</select>";			
		}

		echo "</td><td class='center'>";
		if (count($profiles)) {
			echo "<input type='submit'  class=\"submit\" name='mailing_add_$type' value='" . $LANG["buttons"][8] . " >>'>";
		}
		echo "<br /><br />";
		if (!empty($options)){
			echo "<input type='submit'  class=\"submit\" name='mailing_delete_$type' value='<< " . $LANG["buttons"][6] . "'>";
		}

		echo "</td><td>";
		if (!empty($options)){
			echo "<select name='mailing_to_delete_" . $type . "[]' multiple size='5'>";
			echo $options;
			echo "</select>";
		} else {
			echo "&nbsp;";
		}
		echo "</td>";
	
	}
	
	function updateMailNotifications($input) {
		global $DB;
		$type = "";
		$action = "";
	
		foreach ($input as $key => $val) {
			if (!ereg("mailing_to_", $key) && ereg("mailing_", $key)) {
				if (preg_match("/mailing_([a-z]+)_([a-z]+)/", $key, $matches)) {
					$type = $matches[2];
					$action = $matches[1];
				}
			}
		}
	
		if (count($input["mailing_to_" . $action . "_" . $type]) > 0) {
			foreach ($input["mailing_to_" . $action . "_" . $type] as $val) {
				switch ($action) {
					case "add" :
						list ($item_type, $item) = split("_", $val);
						$query = "INSERT INTO glpi_mailing (type,FK_item,item_type) VALUES ('$type','$item','$item_type')";
						$DB->query($query);
						break;
					case "delete" :
						$query = "DELETE FROM glpi_mailing WHERE ID='$val'";
						$DB->query($query);
						break;
				}
			}
		}
	
	}


/**
 * Determine if email is valid
 * @param $email email to check
 * @return boolean 
 */
function isValidEmail($email="")
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

function isAuthorMailingActivatedForHelpdesk(){
	global $DB,$CFG_GLPI;

	if ($CFG_GLPI['mailing']){
		$query="SELECT COUNT(ID) FROM glpi_mailing WHERE type IN ('new','followup','update','finish') 
				AND item_type = '".USER_MAILING_TYPE."' AND FK_item = '".AUTHOR_MAILING."' ;";
		if ($result=$DB->query($query)){
			if ($DB->result($result,0,0)>0){
				return true;
			}
		}
	}
	return false;
}

?>
