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

/**
 * Count the number of elements in a table.
 *
 * @param $table table name
 * @param $condition condition to use
 *
 * return int nb of elements in table
 */
function countElementsInTable($table,$condition=""){
	global $DB;
	$query="SELECT count(*) AS cpt 
		FROM $table";
	if (!empty($condition)){
		$query.=" WHERE $condition ";
	}
	$result=$DB->query($query);
	$ligne = $DB->fetch_array($result);
	return $ligne['cpt'];
}

/**
 * Count the number of elements in a table for a specific entity
 *
 * @param $table table name
 *
 * return int nb of elements in table
 */
function countElementsInTableForMyEntities($table){
	global $CFG_GLPI;
	return countElementsInTable($table,getEntitiesRestrictRequest("",$table,'','',in_array($table,$CFG_GLPI["recursive_type"])));
}
/**
 * Count the number of elements in a table for a specific entity
 *
 * @param $table table name
 * @param $entity the entity ID
 *
 * return int nb of elements in table
 */
function countElementsInTableForEntity($table,$entity){
	global $CFG_GLPI;
	return countElementsInTable($table,getEntitiesRestrictRequest("",$table,'',$entity,in_array($table,$CFG_GLPI["recursive_type"])));
}
/**
 * Get datas from a table in an array : CAUTION TO USE ONLY FOR SMALL TABLES OR USING A STRICT CONDITION
 * 
 * @param $table table name
 * @param $condition condition to use
 *
 * return array containing all the datas
 */
function getAllDatasFromTable($table,$condition=""){
	global $DB;
	$datas=array();
	$query="SELECT * FROM $table ";

	if (!empty($condition)){
		$query.=" WHERE $condition ";
	}

	if ($result=$DB->query($query)){
		while ($data=$DB->fetch_assoc($result)){
			$datas[$data['ID']]=$data;
		}
	}
	return $datas;
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
	global $DB,$LANG;

	$name="";
	$comments="";
	if ($ID==0 && $table=="glpi_entities") {
		$name = $LANG["entity"][2];
		
	} else if ($ID==0 && $table=="glpi_dropdown_kbcategories") {
		$name = $LANG["knowbase"][12];
		
	} else {
		$query = "SELECT * 
			FROM $table 
			WHERE (ID = '$ID')";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)==1){
				$name=$DB->result($result,0,"name");
				$comments=$DB->result($result,0,"comments");
			}
	
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
	global $DB,$LANG;
	$name="";
	$comments="";

	if ($ID==0 && $table=="glpi_entities") {
		$name = $LANG["entity"][2];
		
	} else if ($ID==0 && $table=="glpi_dropdown_kbcategories") {
		$name = $LANG["knowbase"][12];
		
	} else {
		$query = "SELECT * 
			FROM $table 
			WHERE (ID = '$ID')";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)==1){
				$name=$DB->result($result,0,"completename");
				$comments=$name.":<br>";
				$comments.=$DB->result($result,0,"comments");
			}
	
		}
	}
	if (empty($name)) $name="&nbsp;";
	if ($withcomments) 
		return array("name"=>$name,"comments"=>$comments);
	else return $name;
}

/**
 * show name cat�ory
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
	global $DB,$LANG;

	$query = "SELECT * 
		FROM $table 
		WHERE (ID = '$ID')";
	$name="";

	if ($result=$DB->query($query)){
		if ($DB->numrows($result)>0){

			$row=$DB->fetch_array($result);

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

	  global $DB;

	// IDs to be present in the final query
	$id_found=array();
	// current ID found to be added
	$found=array();

	// First request init the  varriables
	$query="SELECT ID from $table WHERE name LIKE '%$search%'";
	if ( ($result=$DB->query($query)) && ($DB->numrows($result)>0) ){
	while ($row=$DB->fetch_array($result)){
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

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
	while ($row=$DB->fetch_array($result)){
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
 * Get the ancestors of an entity
 * result is cached in session
 *
 * @param $ID The ID of the entity
 * @return array of IDs of the ancestors
 */
function getEntityAncestors ($ID){
	if (!isset($_SESSION['glpi_entities_ancestors'][$ID])){
		$_SESSION['glpi_entities_ancestors'][$ID]=getAncestorsOfTreeItem("glpi_entities",$ID);
	}
	return $_SESSION['glpi_entities_ancestors'][$ID];
}

/**
 * Get the sons of an entity
 * result is cached in session
 *
 * @param $ID The ID of the entity
 * @return array of IDs of the sons (including ID of the searched entity)
 */
function getEntitySons ($ID){
	if (!isset($_SESSION['glpi_entities_sons'][$ID])){
		$_SESSION['glpi_entities_sons'][$ID]=getSonsOfTreeItem("glpi_entities",$ID);
	}
	return $_SESSION['glpi_entities_sons'][$ID];
}

/**
 * Get the ancestors of an item in a tree dropdown
 *
 * @param $table
 * @param $IDf The ID of the item
 * @return array of IDs of the ancestors
 */
function getAncestorsOfTreeItem($table,$IDf){
	global $DB;

	// IDs to be present in the final array
	$id_found=array();
	
	// Get the leafs of previous founded item
	while ($IDf>0){
		// Get next elements
		$query="SELECT parentID 
			FROM $table 
			WHERE ID = '$IDf'";

		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			$IDf=$DB->result($result,0,0);
		} else {
			$IDf=0;
		}
		if ($IDf>=0&&!in_array($IDf,$id_found)){
			$id_found[]=$IDf;
		} else {
			$IDf=0;
		}
	}
	return $id_found;

}

/**
 * Get the sons of an item in a tree dropdown
 *
 * @param $table
 * @param $IDf The ID of the father
 * @return array of IDs of the sons
 */
function getSonsOfTreeItem($table,$IDf){
	global $DB;

	// IDs to be present in the final array
	$id_found=array($IDf);
	// current ID found to be added
	$found=array();
	// First request init the  varriables
	$query="SELECT ID 
		FROM $table 
		WHERE parentID = '$IDf'
		ORDER BY name";

	if ( ($result=$DB->query($query)) && ($DB->numrows($result)>0) ){
		while ($row=$DB->fetch_array($result)){
			array_push($id_found,$row['ID']);
			array_push($found,$row['ID']);
		}
	} else return $id_found;

	// Get the leafs of previous founded item
	while (count($found)>0){
		$first=true;
		// Get next elements
		$query="SELECT ID 
			FROM $table 
			WHERE ";
		foreach ($found as $key => $val){
			if (!$first) $query.=" OR ";
			else $first=false;
			$query.= " parentID = '$val' ";
		}
			

		// CLear the found array
		unset($found);
		$found=array();

		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			while ($row=$DB->fetch_array($result)){
				if (!in_array($row['ID'],$id_found)){
					array_push($id_found,$row['ID']);
					array_push($found,$row['ID']);
				}
			}		
		}
	}
	return $id_found;

}

/**
 * Get the sons of an item in a tree dropdown
 *
 * @param $table
 * @param $IDf The ID of the father
 * @return array of IDs of the sons
 */
function getTreeForItem($table,$IDf){
	global $DB;

	// IDs to be present in the final array
	$id_found=array();

	// current ID found to be added
	$found=array();
	// First request init the  varriables
	$query="SELECT * 
		FROM $table 
		WHERE parentID = '$IDf' ORDER BY name";
	if ( ($result=$DB->query($query)) && ($DB->numrows($result)>0) ){
		while ($row=$DB->fetch_array($result)){
			$id_found[$row['ID']]['parent']=$IDf;
			$id_found[$row['ID']]['name']=$row['name'];
			array_push($found,$row['ID']);
		}
	} 

	// Get the leafs of previous founded item
	while (count($found)>0){
		$first=true;
		// Get next elements
		$query="SELECT * 
			FROM $table 
			WHERE ";
		foreach ($found as $key => $val){
			if (!$first) $query.=" OR ";
			else $first=false;
			$query.= " parentID = '$val' ";
		}
		$query.=" ORDER BY name";
		// CLear the found array
		unset($found);
		$found=array();

		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			while ($row=$DB->fetch_array($result)){
				if (!in_array($row['ID'],$id_found)){
					$id_found[$row['ID']]['parent']=$row['parentID'];
					$id_found[$row['ID']]['name']=$row['name'];
					array_push($found,$row['ID']);
				}
			}		
		}
	}
	$tree[$IDf]['name']=getDropdownName($table,$IDf);
	$tree[$IDf]['tree']=contructTreeFromList($id_found,$IDf);
	return $tree;

}


function contructTreeFromList($list,$root){
	$tree=array();
	

	foreach ($list as $ID => $data){
		if ($data['parent']==$root){
			unset($list[$ID]);
			$tree[$ID]['name']=$data['name'];
			$tree[$ID]['tree']=contructTreeFromList($list,$ID);
		}
	}
	return $tree;

}

function contructListFromTree($tree,$parent=0){
	$list=array();
	foreach ($tree as $root => $data){
		$list[$root]=$parent;
		if (is_array($data['tree'])&&count($data['tree'])){
			foreach ($data['tree'] as $ID => $underdata){
				$list[$ID]=$root;
				if (is_array($underdata['tree'])&&count($underdata['tree'])){
					$list+=contructListFromTree($underdata['tree'],$ID);
				}
			}
		}
	}
	return $list;

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

	global $DB;

	if (empty($IDf)) return "";

	if (empty($reallink)) $reallink=$table.".ID";

	$id_found=getSonsOfTreeItem($table,$IDf);


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
	} else return " ( $reallink = '$IDf') ";
}


/**
 * Get the level for an item in a tree structure
 *
 * @param $table
 * @param $ID
 * @return int level
 */
function getTreeItemLevel($table,$ID){
	global $DB;
	$level=0;

	$query="SELECT parentID 
		FROM $table 
		WHERE ID='$ID'";
	while (1)
	{
		if (($result=$DB->query($query))&&$DB->numrows($result)==1){
			$parentID=$DB->result($result,0,"parentID");
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
	global $DB;
	$query="SELECT ID 
		FROM $table";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			list($name,$level)=getTreeValueName($table,$data['ID']);
			$query="UPDATE $table 
				SET completename='".addslashes($name)."', level='$level' 
				WHERE ID='".$data['ID']."'";
			$DB->query($query);
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
	global $DB;

	list($name,$level)=getTreeValueName($table,$ID);

	$query="UPDATE $table 
		SET completename='".addslashes($name)."', level='$level' 
		WHERE ID='".$ID."'";
	$DB->query($query);
	$query="SELECT ID 
		FROM $table 
		WHERE parentID='$ID'";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			regenerateTreeCompleteNameUnderID($table,$data["ID"]);
		}
	}

}

/**
 * Get the ID of the next Item
 *
 * @param $table table to search next item
 * @param $ID current ID
 * @param $condition condition to add to the search
 * @param $nextprev_item field used to sort
 * @return the next ID, -1 if not exist
 */
function getNextItem($table,$ID,$condition="",$nextprev_item=""){
	global $DB,$CFG_GLPI;

	if (empty($nextprev_item)){
		$nextprev_item=$CFG_GLPI["nextprev_item"];
	}

	$search=$ID;

	if ($nextprev_item!="ID"){
		$query="SELECT ".$nextprev_item." 
			FROM $table 
			WHERE ID='$ID'";
		if ($result=$DB->query($query)){
			if ($DB->numrows($result)>0){
				$search=addslashes($DB->result($result,0,0));
			} else {
				$nextprev_item="ID";
			}
		}
	}

	$LEFTJOIN='';
	if ($table=="glpi_users"){
		$LEFTJOIN=' LEFT JOIN glpi_users_profiles ON (glpi_users.ID = glpi_users_profiles.FK_users)';
	}	

	$query = "SELECT $table.ID 
		FROM $table $LEFTJOIN
		WHERE ( $table.".$nextprev_item." > '$search' ";

	// Same name case
	if ($nextprev_item!="ID"){
		$query .= " OR ($table.".$nextprev_item." = '$search' AND $table.ID > '$ID') ";
	}

	$query.=" ) ";

	if (!empty($condition)){
		$query.=" AND $condition";
	}
	if (in_array($table,$CFG_GLPI["deleted_tables"]))
		$query.=" AND $table.deleted='0' ";
	if (in_array($table,$CFG_GLPI["template_tables"]))
		$query.=" AND $table.is_template='0' ";	

	// Restrict to active entities
	if (in_array($table,$CFG_GLPI["specif_entities_tables"])){
		$query.=getEntitiesRestrictRequest("AND",$table,'','',in_array($table,$CFG_GLPI["recursive_type"]));
	} else if ($table=="glpi_users"){
		$query.=getEntitiesRestrictRequest("AND","glpi_users_profiles");
	}

	//$query.=" ORDER BY ".$nextprev_item." ASC, ID ASC";
	$query.=" ORDER BY $table.$nextprev_item ASC, $table.ID ASC";

	$result=$DB->query($query);
	if ($result&&$DB->numrows($result)>0)
		return $DB->result($result,0,"ID");
	else return -1;

}

/**
 * Get the ID of the previous Item
 *
 * @param $table table to search next item
 * @param $ID current ID
 * @param $condition condition to add to the search
 * @param $nextprev_item field used to sort
 * @return the previous ID, -1 if not exist
 */
function getPreviousItem($table,$ID,$condition="",$nextprev_item=""){
	global $DB,$CFG_GLPI;

	if (empty($nextprev_item)){
		$nextprev_item=$CFG_GLPI["nextprev_item"];
	}

	$search=$ID;
	if ($nextprev_item!="ID"){
		$query="SELECT ".$nextprev_item." 
			FROM $table 
			WHERE ID=$ID";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			$search=addslashes($DB->result($result,0,0));
		} else {
			$nextprev_item="ID";
		}
	}

	$LEFTJOIN='';
	if ($table=="glpi_users"){
		$LEFTJOIN=' LEFT JOIN glpi_users_profiles ON (glpi_users.ID = glpi_users_profiles.FK_users)';
	}	

	$query = "SELECT $table.ID 
		FROM $table $LEFTJOIN
		WHERE  ($table.".$nextprev_item." < '$search' ";

	// Same name case
	if ($nextprev_item!="ID"){
		$query .= " OR ($table.".$nextprev_item." = '$search' AND $table.ID < '$ID') ";
	}

	$query.=" ) ";


	if (!empty($condition)){
		$query.=" AND $condition";
	}

	if (in_array($table,$CFG_GLPI["deleted_tables"]))
		$query.="AND $table.deleted='0'";
	if (in_array($table,$CFG_GLPI["template_tables"]))
		$query.="AND $table.is_template='0'";	

	// Restrict to active entities
	if (in_array($table,$CFG_GLPI["specif_entities_tables"])){
		$query.=getEntitiesRestrictRequest("AND",$table,'','',in_array($table,$CFG_GLPI["recursive_type"]));
	} else if ($table=="glpi_users"){
		$query.=getEntitiesRestrictRequest("AND","glpi_users_profiles");
	}

	$query.=" ORDER BY $table.".$nextprev_item." DESC, $table.ID DESC";

	$result=$DB->query($query);
	if ($result&&$DB->numrows($result)>0)
		return $DB->result($result,0,"ID");
	else return -1;

}

function formatUserName($ID,$login,$realname,$firstname,$link=0,$cut=0){
	global $CFG_GLPI;
	$before="";
	$after="";
	$viewID="";
	if (strlen($realname)>0) {
		$temp=$realname;
		
		if (strlen($firstname)>0)$temp.=" ".$firstname;

		if($cut>0&&strlen($temp) > $cut){
			$temp=utf8_substr($temp,0,$cut);
			$temp.=" ..."; 
		}
	}
	else {
		$temp=$login;
	}

	if ($ID>0&&(strlen($temp)==0||$CFG_GLPI["view_ID"])){
		$viewID="&nbsp;($ID)";
	}

	if ($link==1&&$ID>0){
		$before="<a href=\"".$CFG_GLPI["root_doc"]."/front/user.form.php?ID=".$ID."\" title=\"".$temp."\">";
		$after="</a>";
	}

	$username=$before.$temp.$viewID.$after;

	return $username;


}

/**
 * Get name of the user with ID=$ID (optional with link to user.form.php)
 *
 *@param $ID int : ID of the user.
 *@param $link int : 1 = Show link to user.form.php 2 = return array with comments and link
 *
 *@return string : username string (realname if not empty and name if realname is empty).
 *
 **/
function getUserName($ID,$link=0){
	global $DB,$CFG_GLPI,$LANG;

	$user="";
	if ($link==2){
		$user=array("name"=>"","link"=>"","comments"=>"");
	}
	if ($ID){
		$query="SELECT * 
			FROM glpi_users 
			WHERE ID='$ID'";
		$result=$DB->query($query);
		
		if ($link==2) $user=array("name"=>"","comments"=>"","link"=>"");
		if ($DB->numrows($result)==1){
			$data=$DB->fetch_assoc($result);
			
			$username=formatUserName($data["ID"],$data["name"],$data["realname"],$data["firstname"],$link);
			if ($link==2){
				$user["name"]=$username;
				$user["link"]=$CFG_GLPI["root_doc"]."/front/user.form.php?ID=".$ID;
				$user["comments"]=$LANG["common"][16].": ".$username."<br>";
				$user["comments"].=$LANG["setup"][18].": ".$data["name"]."<br>";
				if (!empty($data["email"]))
					$user["comments"].=$LANG["setup"][14].": ".$data["email"]."<br>";
				if (!empty($data["phone"]))
					$user["comments"].=$LANG["help"][35].": ".$data["phone"]."<br>";
			} else {
				$user=$username;
			}
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

	global $DB;
	// Get a list of tables contained within the database.
	$result = $DB->list_tables("%".$tablename."%");
	if ($rcount = $DB->numrows($result)){
		while ($data=$DB->fetch_row($result)){
			if ($data[0]===$tablename){
				return true;
			}
		}
	}
	$DB->free_result($result);
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
	global $DB;

	if ($fields = $DB->list_fields($table)){

		if (isset($fields[$field]))
			return true;
		else return false;
	} else return false;
}

// return true if the field $field of the table $table is a mysql index
// else return false
function isIndex($table, $field) {

	global $DB;
	$result = $DB->query("SHOW INDEX FROM ". $table);
	if ($result&&$DB->numrows($result)){
		while ($data=$DB->fetch_assoc($result))
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
		$EXPORT .= urlencode($KEY)."=>".(is_array($VALUE)?" ".exportArrayToDB($VALUE):urlencode($VALUE))." ";
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
// Cr�tion automatique d'un nouveau code �partir du gabarit
// @object     : objet concern�// @field      : nom de champ du gabarit contenant le format du code
// @isTemplate : true si template new
// @type       : type d'objet
function autoName($objectName, $field, $isTemplate, $type,$FK_entities=-1){
	global $LINK_ID_TABLE,$DB,$CFG_GLPI;

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
							AND deleted = '0' 
							AND is_template = '0'";
							if ($CFG_GLPI["autoname_entity"]&&$FK_entities>=0){
								$query.=" AND FK_entities = '$FK_entities' ";
							}
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
				if ($type != INFOCOM_TYPE){
					$query .= " AND deleted = '0' AND is_template = '0'";
					if ($CFG_GLPI["autoname_entity"]&&$FK_entities>=0){
						$query.=" AND FK_entities = '$FK_entities' ";
					}
				}

			}

			$query = "SELECT MAX(Num.no) AS lastNo 
				FROM (".$query.") AS Num";
			$resultNo = $DB->query($query);

			if ($DB->numrows($resultNo)>0) {
				$data = $DB->fetch_array($resultNo);
				$newNo = $data['lastNo'] + 1;
			} else	$newNo = 0;
			$objectName = str_replace(array($mask,'\\_','\\%'), array(str_pad($newNo, $len, '0', STR_PAD_LEFT),'_','%'), $autoNum);
		}
	}
	return $objectName;
}

function closeDBConnections(){
	global $DB, $DBocs;

	// Case of not init $DB object
	if (method_exists($DB,"close")){
		$DB->close();
		if (isset($DBocs)&&method_exists($DBocs,"close")){
			$DBocs->close();
		}
	}
}

// Check if the user have an email 
function checkEmailForUser($ID){
	global $DB;
	$query="SELECT email FROM glpi_users WHERE ID='$ID'";
	$result=$DB->query($query);
	if ($DB->numrows($result)==1){
		return isValidEmail($DB->result($result,0,0));
	}
	return false;
}

function formatOutputWebLink($link){
	if (!ereg("^https?",$link)){
		return "http://".$link;
	} 
	return $link;
}

?>
