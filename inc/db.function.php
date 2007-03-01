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

/**
 * Count the number of elements in a table.
 *
 * @param $table table name
 *
 * return int nb of elements in table
 */
function countElementsInTable($table){
	global $db;
	$query="SELECT count(*) AS cpt 
		FROM $table";
	$result=$db->query($query);
	$ligne = $db->fetch_array($result);
	return $ligne['cpt'];
}

/**
 * Get the Name of the element of a Dropdown Tree table
 *
 * @param $table : Dropdown Tree table
 * @param $ID : ID of the element
 * @param $withcomments : 1 if you want to give the array with the comments
 * @return string : name of the element
 * @see getTreeValueCompleteName
 */
function getTreeLeafValueName($table,$ID,$withcomments=0)
{
	global $db;
	$query = "SELECT * 
		FROM $table 
		WHERE (ID = '$ID')";
	$name="";
	$comments="";
	if ($result=$db->query($query)){
		if ($db->numrows($result)==1){
			$name=$db->result($result,0,"name");
			$comments=$db->result($result,0,"comments");
		}

	}
	if ($withcomments)
		return array("name"=>$name,"comments"=>$comments);
	else return $name;
}

/**
 * Get completename of a Dropdown Tree table
 *
 * @param $table : Dropdown Tree table
 * @param $ID : ID of the element
 * @param $withcomments : 1 if you want to give the array with the comments
 * @return string : completename of the element
 * @see getTreeLeafValueName
 */
function getTreeValueCompleteName($table,$ID,$withcomments=0)
{
	global $db;
	$query = "SELECT * 
		FROM $table 
		WHERE (ID = '$ID')";
	$name="";
	$comments="";
	if ($result=$db->query($query)){
		if ($db->numrows($result)==1){
			$name=$db->result($result,0,"completename");
			$comments=$db->result($result,0,"comments");
		}

	}
	if (empty($name)) $name="&nbsp;";
	if ($withcomments) 
		return array("name"=>$name,"comments"=>$comments);
	else return $name;
}

/**
 * show name catégory
 *
 * @param $table
 * @param $ID
 * @param $wholename
 * @param $level
 * @return string name
 */
// DO NOT DELETE THIS FUNCTION : USED IN THE UPDATE
function getTreeValueName($table,$ID, $wholename="",$level=0)
{
	global $db,$lang;

	$query = "SELECT * 
		FROM $table 
		WHERE (ID = '$ID')";
	$name="";

	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){

			$row=$db->fetch_array($result);

			$parentID = $row["parentID"];
			if($wholename == "")
			{
				$name = $row["name"];
			} else
			{
				$name = $row["name"] . " > ";
			}
			$level++;
			list($tmpname,$level)=getTreeValueName($table,$parentID, $name,$level);
			$name =  $tmpname. $name;
		}

	}
	return array($name,$level);
}

/**
 * Get the equivalent search query using ID that the search of the string argument
 *
 * @param $table
 * @param $search the search string value
 * @return string the query
 */
function getRealSearchForTreeItem($table,$search){

	return " ( $table.completename ".makeTextSearch($search)." ) ";

	/*if (empty($search)) return " ( $table.name LIKE '%$search%' ) ";

	  global $db;

	// IDs to be present in the final query
	$id_found=array();
	// current ID found to be added
	$found=array();

	// First request init the  varriables
	$query="SELECT ID from $table WHERE name LIKE '%$search%'";
	if ( ($result=$db->query($query)) && ($db->numrows($result)>0) ){
	while ($row=$db->fetch_array($result)){
	array_push($id_found,$row['ID']);
	array_push($found,$row['ID']);
	}
	}else return " ( $table.name LIKE '%$search%') ";

	// Get the leafs of previous founded item
	while (count($found)>0){
	// Get next elements
	$query="SELECT ID from $table WHERE '0'='1' ";
	foreach ($found as $key => $val)
	$query.= " OR parentID = '$val' ";

	// CLear the found array
	unset($found);
	$found=array();

	$result=$db->query($query);
	if ($db->numrows($result)>0){
	while ($row=$db->fetch_array($result)){
	if (!in_array($row['ID'],$id_found)){
	array_push($id_found,$row['ID']);
	array_push($found,$row['ID']);
	}
	}		
	}

	}

	// Construct the final request
	if (count($id_found)>0){
	$ret=" ( '0' = '1' ";
	foreach ($id_found as $key => $val)
	$ret.=" OR $table.ID = '$val' ";
	$ret.=") ";

	return $ret;
	}else return " ( $table.name LIKE '%$search%') ";
	 */
}



/**
 * Get the equivalent search query using ID of soons that the search of the father's ID argument
 *
 * @param $table
 * @param $IDf The ID of the father
 * @param $reallink real field to link ($table.ID if not set)
 * @return string the query
 */
function getRealQueryForTreeItem($table,$IDf,$reallink=""){

	global $db;

	if (empty($IDf)) return "";

	if (empty($reallink)) $reallink=$table.".ID";


	// IDs to be present in the final query
	$id_found=array();
	// current ID found to be added
	$found=array();

	// First request init the  varriables
	$query="SELECT ID 
		FROM $table 
		WHERE ID = '$IDf'";
	if ( ($result=$db->query($query)) && ($db->numrows($result)>0) ){
		while ($row=$db->fetch_array($result)){
			array_push($id_found,$row['ID']);
			array_push($found,$row['ID']);
		}
	} else return " ( $table.ID = '$IDf') ";

	// Get the leafs of previous founded item
	while (count($found)>0){
		// Get next elements
		$query="SELECT ID 
			FROM $table 
			WHERE '0'='1' ";
		foreach ($found as $key => $val)
			$query.= " OR parentID = '$val' ";

		// CLear the found array
		unset($found);
		$found=array();

		$result=$db->query($query);
		if ($db->numrows($result)>0){
			while ($row=$db->fetch_array($result)){
				if (!in_array($row['ID'],$id_found)){
					array_push($id_found,$row['ID']);
					array_push($found,$row['ID']);
				}
			}		
		}
	}

	// Construct the final request
	if (count($id_found)>0){
		$ret=" ( ";
		$i=0;
		foreach ($id_found as $key => $val){
			if ($i>0) $ret.=" OR ";
			$ret.="$reallink = '$val' ";
			$i++;
		}
		$ret.=") ";

		return $ret;
	}else return " ( $table.ID = '$IDf') ";
}


/**
 * Get the level for an item in a tree structure
 *
 * @param $table
 * @param $ID
 * @return int level
 */
function getTreeItemLevel($table,$ID){
	global $db;
	$level=0;

	$query="SELECT parentID 
		FROM $table 
		WHERE ID='$ID'";
	while (1)
	{
		if (($result=$db->query($query))&&$db->numrows($result)==1){
			$parentID=$db->result($result,0,"parentID");
			if ($parentID==0) return $level;
			else {
				$level++;
				$query="SELECT parentID 
					FROM $table 
					WHERE ID='$parentID'";
			}
		}
	}


	return -1;

}

/**
 * Compute all completenames of Dropdown Tree table
 *
 * @param $table : dropdown tree table to compute
 * @return nothing
 */
function regenerateTreeCompleteName($table){
	global $db;
	$query="SELECT ID 
		FROM $table";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			list($name,$level)=getTreeValueName($table,$data['ID']);
			$query="UPDATE $table 
				SET completename='".addslashes($name)."', level='$level' 
				WHERE ID='".$data['ID']."'";
			$db->query($query);
		}
	}
}

/**
 * Compute completename of Dropdown Tree table under the element of ID $ID
 *
 * @param $table : dropdown tree table to compute
 * @param $ID : root ID to used : regenerate all under this element
 * @return nothing
 */
function regenerateTreeCompleteNameUnderID($table,$ID){
	global $db;

	list($name,$level)=getTreeValueName($table,$ID);

	$query="UPDATE $table 
		SET completename='".addslashes($name)."', level='$level' 
		WHERE ID='".$ID."'";
	$db->query($query);
	$query="SELECT ID 
		FROM $table 
		WHERE parentID='$ID'";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			regenerateTreeCompleteNameUnderID($table,$data["ID"]);
		}
	}

}

/**
 * Get the ID of the next Item
 *
 * @param $table table to search next item
 * @param $ID current ID
 * @return the next ID, -1 if not exist
 */
function getNextItem($table,$ID){
	global $db,$cfg_glpi;

	$nextprev_item=$cfg_glpi["nextprev_item"];
	if ($table=="glpi_tracking"||ereg("glpi_device",$table)) $nextprev_item="ID";

	$search=$ID;

	if ($nextprev_item!="ID"){
		$query="SELECT ".$nextprev_item." 
			FROM $table 
			WHERE ID='$ID'";
		$result=$db->query($query);
		$search=addslashes($db->result($result,0,0));
	}

	$query = "SELECT ID 
		FROM $table 
		WHERE ( ".$nextprev_item." > '$search' ";

	// Same name case
	if ($nextprev_item!="ID"){
		$query .= " OR (".$nextprev_item." = '$search' AND ID > '$ID') ";
	}

	$query.=" ) ";

	if (in_array($table,$cfg_glpi["deleted_tables"]))
		$query.=" AND deleted='N' ";
	if (in_array($table,$cfg_glpi["template_tables"]))
		$query.=" AND is_template='0' ";	

	//$query.=" ORDER BY ".$nextprev_item." ASC, ID ASC";
	$query.=" ORDER BY $nextprev_item ASC, ID ASC";


	$result=$db->query($query);
	if ($db->numrows($result)>0)
		return $db->result($result,0,"ID");
	else return -1;

}

/**
 * Get the ID of the previous Item
 *
 * @param $table table to search next item
 * @param $ID current ID
 * @return the previous ID, -1 if not exist
 */
function getPreviousItem($table,$ID){
	global $db,$cfg_glpi;

	$nextprev_item=$cfg_glpi["nextprev_item"];
	if ($table=="glpi_tracking"||ereg("glpi_device",$table)) $nextprev_item="ID";

	$search=$ID;
	if ($nextprev_item!="ID"){
		$query="SELECT ".$nextprev_item." 
			FROM $table 
			WHERE ID=$ID";
		$result=$db->query($query);
		$search=addslashes($db->result($result,0,0));
	}

	$query = "SELECT ID 
		FROM $table 
		WHERE ( ".$nextprev_item." < '$search' ";

	// Same name case
	if ($nextprev_item!="ID"){
		$query .= " OR (".$nextprev_item." = '$search' AND ID < '$ID') ";
	}

	$query.=" ) ";

	if (in_array($table,$cfg_glpi["deleted_tables"]))
		$query.="AND deleted='N'";
	if (in_array($table,$cfg_glpi["template_tables"]))
		$query.="AND is_template='0'";	

	$query.=" ORDER BY ".$nextprev_item." DESC, ID DESC";

	$result=$db->query($query);
	if ($db->numrows($result)>0)
		return $db->result($result,0,"ID");
	else return -1;

}

/**
 * Get name of the user with ID=$ID (optional with link to user.info.php)
 *
 *@param $ID int : ID of the user.
 *@param $link int : 1 = Show link to user.info.php 2 = return array with comments and link
 *
 *@return string : username string (realname if not empty and name if realname is empty).
 *
 **/
function getUserName($ID,$link=0){
	global $db,$cfg_glpi,$lang;

	$user="";
	if ($link==2){
		$user=array("name"=>"","link"=>"","comments"=>"");
	}
	if ($ID){
		$query="SELECT * 
			FROM glpi_users 
			WHERE ID='$ID'";
		$result=$db->query($query);
		
		if ($link==2) $user=array("name"=>"","comments"=>"","link"=>"");
		if ($db->numrows($result)==1){
			$data=$db->fetch_assoc($result);
			$before="";
			$after="";
			if ($link==1){
				$before="<a href=\"".$cfg_glpi["root_doc"]."/front/user.info.php?ID=".$ID."\">";
				$after="</a>";
			}
			if (strlen($data["realname"])>0) {
				$temp=$data["realname"];
				if (strlen($data["firstname"])>0)$temp.=" ".$data["firstname"];
				$username=$before.$temp.$after;
			}
			else $username=$before.$data["name"].$after;
	
			if ($link==2){
				$user["name"]=$username;
				$user["link"]=$cfg_glpi["root_doc"]."/front/user.info.php?ID=".$ID;
				$user["comments"]=$lang["common"][16].": ".$username."<br>";
				if (!empty($data["email"]))
					$user["comments"].=$lang["setup"][14].": ".$data["email"]."<br>";
				if (!empty($data["phone"]))
					$user["comments"].=$lang["financial"][29].": ".$data["phone"]."<br>";
				if ($data["location"])
					$user["comments"].=$lang["common"][15].": ".getDropdownName("glpi_dropdown_locations",$data["location"],0)."<br>";
			} else $user=$username;
		}
	}
	return $user;		
}

/**
 * Verify if a DB table exists
 *
 *@param $tablename string : Name of the table we want to verify.
 *
 *@return bool : true if exists, false elseway.
 *
 **/
function TableExists($tablename) {

	global $db;
	// Get a list of tables contained within the database.
	$result = $db->list_tables($db);
	$rcount = $db->numrows($result);

	// Check each in list for a match.
	for ($i=0;$i<$rcount;$i++) {
		if ($db->table_name($result, $i)==$tablename) return true;
	}
	$db->free_result($result);
	return false;
}

/**
 * Verify if a DB field exists
 *
 *@param $table string : Name of the table we want to verify.
 *@param $field string : Name of the field we want to verify.
 *
 *@return bool : true if exists, false elseway.
 *
 **/
function FieldExists($table, $field) {
	global $db;

	if ($fields = $db->list_fields($table)){

		if (isset($fields[$field]))
			return true;
		else return false;
	} else return false;
}

// return true if the field $field of the table $table is a mysql index
// else return false
function isIndex($table, $field) {

	global $db;
	$result = $db->query("SHOW INDEX FROM ". $table);
	if ($result&&$db->numrows($result)){
		while ($data=$db->fetch_assoc($result))
			if ($data["Key_name"]==$field){
				//			echo $table.".".$field."-> INDEX<br>";
				return true;
			}
	}
	//echo $table.".".$field."-> NOT INDEX<br>";
	return false;		
}


function exportArrayToDB($TAB) {
	$EXPORT = "";
	while (list($KEY,$VALUE) = each($TAB)) {
		$EXPORT .= urlencode($KEY)."=>".urlencode($VALUE)." ";
	}
	return $EXPORT;
}

function importArrayFromDB($DATA) {
	$TAB = array();

	foreach(explode(" ", $DATA) as $ITEM) {
		$A = explode("=>", $ITEM);
		if (strlen($A[0])&&isset($A[1]))
			$TAB[urldecode($A[0])] = urldecode($A[1]);
	}
	return $TAB;
}


//***************************************************************
// Création automatique d'un nouveau code à partir du gabarit
// @object     : objet concerné
// @field      : nom de champ du gabarit contenant le format du code
// @isTemplate : true si template new
// @type       : type d'objet
function autoName($objectName, $field, $isTemplate, $type){
	global $LINK_ID_TABLE,$db;

	//$objectName = isset($object->fields[$field]) ? $object->fields[$field] : '';

	$len = strlen($objectName);
	if($isTemplate && $len > 8 && substr($objectName,0,4) === '&lt;' && substr($objectName,$len - 4,4) === '&gt;') {
		$autoNum = substr($objectName, 4, $len - 8);
		$mask = '';
		if(preg_match( "/\\#{1,10}/", $autoNum, $mask)){
			$global = strpos($autoNum, '\\g') !== false && $type != INFOCOM_TYPE ? 1 : 0;
			$autoNum = str_replace(array('\\y','\\Y','\\m','\\d','_','%','\\g'), array(date('y'),date('Y'),date('m'),date('d'),'\\_','\\%',''), $autoNum);
			$mask = $mask[0];
			$pos = strpos($autoNum, $mask) + 1;
			$len = strlen($mask);
			$like = str_replace('#', '_', $autoNum);
			if ($global == 1){
				$query = "";
				$first = 1;
				foreach($LINK_ID_TABLE as $t=>$table){
					if ($t == COMPUTER_TYPE || $t == MONITOR_TYPE  || $t == NETWORKING_TYPE || $t == PERIPHERAL_TYPE || $t == PRINTER_TYPE || $t == PHONE_TYPE){
						$query .= ($first ? "SELECT " : " UNION SELECT  ")." $field AS code 
							FROM $table 
							WHERE $field LIKE '$like' 
							AND deleted = 'N' 
							AND is_template = '0'";
						$first = 0;
					}
				}
				$query = "SELECT CAST(SUBSTRING(code, $pos, $len) AS unsigned) AS no 
					FROM ($query) AS codes";
			} else	{
				$table = $LINK_ID_TABLE[$type];
				$query = "SELECT CAST(SUBSTRING($field, $pos, $len) AS unsigned) AS no 
					FROM $table 
					WHERE $field LIKE '$like' ";
				if ($type != INFOCOM_TYPE)
					$query .= " AND deleted = 'N' AND is_template = '0'";
			}
			$query = "SELECT MAX(Num.no) AS lastNo 
				FROM (".$query.") AS Num";
			$resultNo = $db->query($query);

			if ($db->numrows($resultNo)>0) {
				$data = $db->fetch_array($resultNo);
				$newNo = $data['lastNo'] + 1;
			} else	$newNo = 0;
			$objectName = str_replace(array($mask,'\\_','\\%'), array(str_pad($newNo, $len, '0', STR_PAD_LEFT),'_','%'), $autoNum);
		}
	}
	return $objectName;
}

?>
