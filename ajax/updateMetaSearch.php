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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

	define('GLPI_ROOT','..');

	$AJAX_INCLUDE=1;
	$NEEDED_ITEMS=array("search");
	include (GLPI_ROOT."/inc/includes.php");
	
	
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
	
	if ($_POST["type"]>0){
		checkTypeRight($_POST["type"],"r");
		echo "<input type='text' size='10' name=\"contains2[".$_POST["num"]."]\" value=\"".stripslashes($_POST["val"])."\" >";
		echo "&nbsp;";
		echo $LANG["search"][10]."&nbsp;";
	
		echo "<select name=\"field2[".$_POST["num"]."]\" size='1'>";
		$first_group=true;
		$newgroup="";
		$items_in_group=0;
		$searchopt=cleanSearchOption($_POST["type"]);
		foreach ($searchopt as $key => $val) {
			// print groups
			if (!is_array($val)){
				if (!empty($newgroup)&&$items_in_group>0) {
					echo $newgroup;
					$first_group=false;
				}
				$items_in_group=0;
				$newgroup="";
				if (!$first_group) $newgroup.="</optgroup>";
				$newgroup.="<optgroup label=\"$val\">";
			} else {
				if (!isset($val["nometa"])){
					$newgroup.= "<option value=\"".$key."\" title=\"".cleanInputText($val["name"])."\""; 
					if($key == $_POST["field"]) $newgroup.= "selected";
					$newgroup.= ">". substr($val["name"],0,20) ."</option>\n";
					$items_in_group++;
				}
			}
		}
		if (!empty($newgroup)&&$items_in_group>0) echo $newgroup;
		if (!$first_group)
			echo "</optgroup>";
	
		echo "</select>&nbsp;";
	}
?>
