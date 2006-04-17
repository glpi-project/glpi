<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
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

/**
* Make a "where" clause for a mysql query on user table
*
*
* Return a string witch contain the where clause, for a query 
* under the glpi_users table, witch return users that have the right $authtype.
* 
*
*@param $authtype auth type
*@returns : string (in order to construct a SQL where clause)
**/
/*function searchUserbyType($authtype) {
	switch ($authtype){
		case "post-only" :
			return " 1=1 ";
			break;
		case "normal" :
			return " type ='super-admin' OR type ='admin' OR type ='normal'";
			break;
		case "admin":
			return " type ='super-admin' OR type ='admin' ";
			break;
		case "super-admin":
			return " type ='super-admin' ";
			break;
		default :
			return "";
		}
}
*/
/**
* Count the number of elements in a table.
*
* @param $table table name
*
* return int nb of elements in table
*/
function countElementsInTable($table){
	global $db;
	$query="SELECT count(*) as cpt from $table";
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
	$query = "select * from $table where (ID = $ID)";
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
	$query = "select * from $table where (ID = $ID)";
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
* @return string name
*/
// DO NOT DELETE THIS FUNCTION : USED IN THE UPDATE
function getTreeValueName($table,$ID, $wholename="",$level=0)
{
	global $db,$lang;
	
	$query = "select * from $table where (ID = $ID)";
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

return " ( $table.completename LIKE '%$search%' ) ";

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
* @return string the query
*/
function getRealQueryForTreeItem($table,$IDf){

global $db;

if (empty($IDf)) return "";


// IDs to be present in the final query
$id_found=array();
// current ID found to be added
$found=array();

// First request init the  varriables
$query="SELECT ID from $table WHERE ID = '$IDf'";
if ( ($result=$db->query($query)) && ($db->numrows($result)>0) ){
	while ($row=$db->fetch_array($result)){
		array_push($id_found,$row['ID']);
		array_push($found,$row['ID']);
	}
} else return " ( $table.ID = '$IDf') ";

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

$query="select parentID from $table where ID='$ID'";
while (1)
{
	if (($result=$db->query($query))&&$db->numrows($result)==1){
		$parentID=$db->result($result,0,"parentID");
		if ($parentID==0) return $level;
		else {
			$level++;
			$query="select parentID from $table where ID='$parentID'";
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
	$query="SELECT ID from $table";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			list($name,$level)=getTreeValueName($table,$data['ID']);
			$query="UPDATE $table SET completename='".addslashes($name)."', level='$level' WHERE ID='".$data['ID']."'";
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

	$query="UPDATE $table SET completename='".addslashes($name)."', level='$level' WHERE ID='".$ID."'";
	$db->query($query);
	$query="SELECT ID FROM $table WHERE parentID='$ID'";
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
	$query="select ".$nextprev_item." FROM $table where ID=$ID";
	$result=$db->query($query);
	$search=addslashes($db->result($result,0,0));
}

$query = "select ID from $table where ".$nextprev_item." > '$search' ";

if (in_array($table,$cfg_glpi["deleted_tables"]))
	$query.="AND deleted='N'";
if (in_array($table,$cfg_glpi["template_tables"]))
	$query.="AND is_template='0'";	
		
$query.=" order by ".$nextprev_item." ASC";

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
	$query="select ".$nextprev_item." FROM $table where ID=$ID";
	$result=$db->query($query);
	$search=addslashes($db->result($result,0,0));
}

$query = "select ID from $table where ".$nextprev_item." < '$search' ";

if (in_array($table,$cfg_glpi["deleted_tables"]))
	$query.="AND deleted='N'";
if (in_array($table,$cfg_glpi["template_tables"]))
	$query.="AND is_template='0'";	
		
$query.=" order by ".$nextprev_item." DESC";

$result=$db->query($query);
if ($db->numrows($result)>0)
	return $db->result($result,0,"ID");
else return -1;

}

/**
* Get name of the user with ID=$ID (optional with link to users-info.php)
*
*@param $ID int : ID of the user.
*@param $link int : 1 = Show link to users-info.php 2 = return array with comments and link
*
*@return string : username string (realname if not empty and name if realname is empty).
*
**/
function getUserName($ID,$link=0){
	global $db,$cfg_glpi,$lang;

	$query="SELECT * from glpi_users WHERE ID=$ID";
	$result=$db->query($query);
	$user="";
	if ($link==2) $user=array("name"=>"","comments"=>"","link"=>"");
	if ($db->numrows($result)==1){
		$data=$db->fetch_assoc($result);
		$before="";
		$after="";
		if ($link==1){
			$before="<a href=\"".$cfg_glpi["root_doc"]."/users/users-info.php?ID=".$ID."\">";
			$after="</a>";
		}
		if (strlen($data["realname"])>0) $username=$before.$data["realname"].$after;
		else $username=$before.$data["name"].$after;

		if ($link==2){
			$user["name"]=$username;
			$user["link"]=$cfg_glpi["root_doc"]."/users/users-info.php?ID=".$ID;
			$user["comments"]=$lang["common"][16].": ".$username."<br>";
			$user["comments"].=$lang["setup"][14].": ".$data["email"]."<br>";
			$user["comments"].=$lang["setup"][15].": ".$data["phone"]."<br>";
			$user["comments"].=$lang["common"][15].": ".getDropdownName("glpi_dropdown_locations",$data["location"],0)."<br>";
		} else $user=$username;
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
   mysql_free_result($result);
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
	$result = $db->query("SELECT * FROM ". $table ."");
	$fields = $db->num_fields($result);
	$var1 = false;
	for ($i=0; $i < $fields; $i++) {
		$name  = $db->field_name($result, $i);
		if(strcmp($name,$field)==0) {
			$var1 = true;
		}
	}
	mysql_free_result($result);
	return $var1;
}

// return true if the field $field of the table $table is a mysql index
// else return false
function isIndex($table, $field) {
	
		global $db;
		$result = $db->query("SHOW INDEX from ". $table);
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

?>