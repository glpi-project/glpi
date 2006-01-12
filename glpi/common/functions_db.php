<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
*@param : $authtype auth type
*@returns : string (in order to construct a SQL where clause)
**/
function searchUserbyType($authtype) {
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

/**
* Count the number of elements in a table.
*
*
* @param $table table name
*
* return int nb of elements in table
*/
function countElementsInTable($table){
$db=new DB;
$query="SELECT count(*) as cpt from $table";
$result=$db->query($query);
$ligne = $db->fetch_array($result);
return $ligne['cpt'];
}

/**
* To be commented
*
* @param $table
* @param $current
* @param $parentID
* @param $categoryname
* @return nothing 
*/

/**
* To be commented
*
* @param $table
* @param $ID
* @return nothing 
*/
function getTreeLeafValueName($table,$ID)
{
	$query = "select * from $table where (ID = $ID)";
	$db=new DB;
	$name="";
	if ($result=$db->query($query)){
		if ($db->numrows($result)==1){
			$name=$db->result($result,0,"name");
		}
		
	}
return $name;
}

/**
* To be commented
*
* @param $table
* @param $ID
* @return nothing 
*/
function getTreeValueCompleteName($table,$ID)
{
	$query = "select * from $table where (ID = $ID)";
	$db=new DB;
	$name="";
	if ($result=$db->query($query)){
		if ($db->numrows($result)==1){
			$name=$db->result($result,0,"completename");
		}
		
	}
return $name;
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
function getTreeValueName($table,$ID, $wholename="")
{
	
	global $lang;
	
	$query = "select * from $table where (ID = $ID)";
	$db=new DB;
	
	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
		
		$row=$db->fetch_array($result);
	
		$parentID = $row["parentID"];
		if($wholename == "")
		{
			$name = $row["name"];
		} else
		{
			$name = $row["name"] . ">";
		}
		$name = getTreeValueName($table,$parentID, $name) . $name;
	}
	
	}
return (@$name);
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

$db=new DB();

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

if (empty($IDf)) return "";

$db=new DB();

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

$level=0;

$db=new DB();
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
* To be commented
*
* @param $table
* @return nothing
*/
function regenerateTreeCompleteName($table){
	$db=new DB;
	$query="SELECT ID from $table";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
		$query="UPDATE $table SET completename='".addslashes(getTreeValueName("$table",$data['ID']))."' WHERE ID='".$data['ID']."'";
		$db->query($query);
		}
	}
}

/**
* To be commented
*
* @param $table
* @param $ID
* @return nothing
*/
function regenerateTreeCompleteNameUnderID($table,$ID){
	$db=new DB;
	$query="UPDATE $table SET completename='".addslashes(getTreeValueName("$table",$ID))."' WHERE ID='".$ID."'";
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
global $deleted_tables,$template_tables,$cfg_layout;

$db=new DB;

$nextprev_item=$cfg_layout["nextprev_item"];
if ($table=="glpi_tracking") $nextprev_item="ID";

$search=$ID;

if ($nextprev_item!="ID"){
	$query="select ".$nextprev_item." FROM $table where ID=$ID";
	$result=$db->query($query);
	$search=addslashes($db->result($result,0,0));
}

$query = "select ID from $table where ".$nextprev_item." > '$search' ";

if (in_array($table,$deleted_tables))
	$query.="AND deleted='N'";
if (in_array($table,$template_tables))
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
global $deleted_tables,$template_tables,$cfg_layout;

$db=new DB;

$nextprev_item=$cfg_layout["nextprev_item"];
if ($table=="glpi_tracking") $nextprev_item="ID";

$search=$ID;
if ($nextprev_item!="ID"){
	$query="select ".$nextprev_item." FROM $table where ID=$ID";
	$result=$db->query($query);
	$search=addslashes($db->result($result,0,0));
}

$query = "select ID from $table where ".$nextprev_item." < '$search' ";

if (in_array($table,$deleted_tables))
	$query.="AND deleted='N'";
if (in_array($table,$template_tables))
	$query.="AND is_template='0'";	
		
$query.=" order by ".$nextprev_item." DESC";

$result=$db->query($query);
if ($db->numrows($result)>0)
	return $db->result($result,0,"ID");
else return -1;

}

function getUserName($ID,$link=0){
	global $cfg_install;
	$db=new DB;
	$query="SELECT * from glpi_users WHERE ID='$ID'";
	$result=$db->query($query);
	if ($db->numrows($result)==1){
		$before="";
		$after="";
		if ($link){
			$before="<a href=\"".$cfg_install["root"]."/users/users-info.php?ID=".$ID."\">";
			$after="</a>";
		}
		if (strlen($db->result($result,0,"realname"))>0) return $before.$db->result($result,0,"realname").$after;
		else return $before.$db->result($result,0,"name").$after;
	}
	else return "";		
}

?>
