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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// FUNCTIONS State


function showStateSummary($target){
	global $DB,$LANG,$CFG_GLPI,$LINK_ID_TABLE;


	$state_type=$CFG_GLPI["state_types"];

	$states=array();
	foreach ($state_type as $key=>$itemtype){
		if (!haveTypeRight($itemtype,"r")) {
			unset($state_type[$key]);
		} else {
			$query= "SELECT states_id, COUNT(*) AS CPT FROM ".$LINK_ID_TABLE[$itemtype]." ".
				getEntitiesRestrictRequest("WHERE",$LINK_ID_TABLE[$itemtype])." AND is_deleted=0 AND is_template=0 GROUP BY states_id";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0){
					while ($data=$DB->fetch_array($result)){
						$states[$data["states_id"]][$itemtype]=$data["CPT"];
					}
				}
			}
		}
	}

	if (count($states)){
		// Produce headline
		echo "<div class='center'><table  class='tab_cadrehov'><tr>";
	
		// Type			
		echo "<th>";
		echo $LANG['state'][0]."</th>";
	
		$ci=new CommonItem;
		foreach ($state_type as $itemtype){
			$ci->setType($itemtype);
			echo "<th>".$ci->getType()."</th>";
			$total[$itemtype]=0;
		}
		echo "<th>".$LANG['common'][33]."</th>";
		echo "</tr>";
		$query="SELECT * FROM glpi_states ORDER BY name";
		$result = $DB->query($query);
		
		// No state 
		$tot=0; 
		echo "<tr class='tab_bg_2'><td class='center'>&nbsp;</td>"; 
		foreach ($state_type as $itemtype){ 
			echo "<td class='center'>"; 

			if (isset($states[0][$itemtype])) { 
				echo $states[0][$itemtype]; 
				$total[$itemtype]+=$states[0][$itemtype];
				$tot+=$states[0][$itemtype]; 
			} else {
				echo "&nbsp;"; 
			}
			echo "</td>"; 
		} 
		echo "<td class='center'><strong>$tot</strong></td></tr>"; 

		while ($data=$DB->fetch_array($result)){
			$tot=0;
			echo "<tr class='tab_bg_2'><td class='center'><strong><a href='".$CFG_GLPI['root_doc']."/front/state.php?reset_before=1&amp;contains[0]=$$$$".$data["id"]."&amp;field[0]=31&amp;sort=1&amp;start=0'>".$data["name"]."</a></strong></td>";
	
			foreach ($state_type as $itemtype){
				echo "<td class='center'>";
	
				if (isset($states[$data["id"]][$itemtype])) {
					echo $states[$data["id"]][$itemtype];
					$total[$itemtype]+=$states[$data["id"]][$itemtype];
					$tot+=$states[$data["id"]][$itemtype];
				}
				else echo "&nbsp;";
				echo "</td>";
			}
			echo "<td class='center'><strong>$tot</strong></td>";
			echo "</tr>";
		}
		echo "<tr class='tab_bg_2'><td class='center'><strong>".$LANG['common'][33]."</strong></td>";
		$tot=0;
		foreach ($state_type as $itemtype){
			echo "<td class='center'><strong>".$total[$itemtype]."</strong></td>";
			$tot+=$total[$itemtype];
		}
		echo "<td class='center'><strong>".$tot."</strong></td>";
		echo "</tr>";
		echo "</table></div>";

	}else {
		echo "<div class='center'><strong>".$LANG['state'][7]."</strong></div>";
	}


}

?>
