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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if ($argv) {
	for ($i=1;$i<count($argv);$i++)
	{
		//To be able to use = in search filters, enter \= instead in command line
		//Replace the \= by ° not to match the split function
		$arg=str_replace('\=','°',$argv[$i]);
		$it = explode("=",$arg);
		$it[0] = preg_replace('/^--/','',$it[0]);
		
		//Replace the ° by = the find the good filter 
		$it=str_replace('°','=',$it);
		$_GET[$it[0]] = $it[1];
	}
}
// MASS IMPORT for OCSNG

define('GLPI_ROOT', '..');

$NEEDED_ITEMS=array("ocsng","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","tracking","enterprise","reservation","setup","group","entity","rulesengine","rule.ocs","registry","rule.softwarecategories","rule.dictionnary.software");
include (GLPI_ROOT."/inc/includes.php");

//// PARAMETERS
// Just import these tags : separeted by $
$tag="";
if (isset($_GET["tag"])) $tag=$_GET["tag"];
// Just import these ocs computer
$ocsid=0;
$ocsservers_id=0;
if (isset($_GET["ocsid"])) $ocsid=$_GET["ocsid"];
if (isset($_GET["ocsservers_id"])) $ocsservers_id=$_GET["ocsservers_id"];

// Limit import
$limit=0;
if (isset($_GET["limit"])) $limit=$_GET["limit"];

$DBocs = new DBocs($ocsservers_id);
$cfg_ocs=getOcsConf($ocsservers_id);

// PREREQUISITE : activate trace_deleted (check done in ocsManageDeleted)
// Clean links
ocsManageDeleted($ocsservers_id);
ocsCleanLinks($ocsservers_id);


$WHERE="";
if (!empty($tag)){
	$splitter=explode("$",$tag);
	if (count($splitter)){
		$WHERE="WHERE TAG='".$splitter[0]."' ";
		for ($i=1;$i<count($splitter);$i++)
			$WHERE.=" OR TAG='".$splitter[$i]."' ";
	}
}

if ($ocsid){
	if (empty($WHERE)) $WHERE="WHERE";
	$WHERE.=" hardware.ID='$ocsid'";
}

$query_ocs = "SELECT hardware.*, accountinfo.TAG AS TAG 
	FROM hardware 
INNER JOIN accountinfo ON (hardware.ID = accountinfo.HARDWARE_ID)
	$WHERE 
	ORDER BY hardware.NAME";
	$result_ocs = $DBocs->query($query_ocs);

	// Existing OCS - GLPI link
	$query_glpi = "SELECT * 
	FROM glpi_ocslinks";
	if ($ocsid) $query_glpi.=" WHERE ocsid='$ocsid' and ocsservers_id=".$ocsservers_id;
	$result_glpi = $DB->query($query_glpi);

	if ($DBocs->numrows($result_ocs)>0){

		// Get all hardware from OCS DB
		$hardware=array();
		while($data=$DBocs->fetch_array($result_ocs)){
			$data=clean_cross_side_scripting_deep(addslashes_deep($data));
			$hardware[$data["ID"]]["id"]=$data["ID"];
		}

		// Get all links between glpi and OCS
		$already_linked=array();
		if ($DB->numrows($result_glpi)>0){
			while($data=$DBocs->fetch_array($result_glpi)){
				$already_linked[$data["ocsid"]]=$data["last_update"];
			}
		}

		// Clean $hardware from already linked element
		if (count($already_linked)>0){
			foreach ($already_linked as $ID => $date){
				if (isset($hardware[$ID])&&isset($already_linked[$ID]))
					unset($hardware[$ID]);
			}
		}

		if (count($hardware)){
			$i=0;
			foreach ($hardware as $ID => $tab){
				echo ".";
				if ($limit&&$i>=$limit) exit();
				ocsProcessComputer($ID,$ocsservers_id,0,-1,1);
				$i++;
			}
		}
	}

?>
