<?php
/*
 * @version $Id: contract.function.php 8498 2009-07-25 19:11:33Z moyo $
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


/**
 * Print the HTML array for contract on devices
 *
 * Print the HTML array for contract on devices $budgetID
 *
 *@param $budgetID array : Contract identifier.
 *
 *@return Nothing (display)
 *
 **/

function showDeviceBudget($budgetID) {

	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE,$SEARCH_PAGES;

	if (!haveRight("budget","r")) return false;

	$query = "SELECT DISTINCT itemtype
		FROM glpi_infocoms
		WHERE budget = '$budgetID'
		ORDER BY itemtype";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<br><br><div class='center'><table class='tab_cadrehov'>";
	echo "<tr><th colspan='2'>";
	printPagerForm();
	echo "</th><th colspan='3'>".$LANG['document'][19].":</th></tr>";
	echo "<tr><th>".$LANG['common'][17]."</th>";
	echo "<th>".$LANG['entity'][0]."</th>";
	echo "<th>".$LANG['common'][16]."</th>";
	echo "<th>".$LANG['common'][19]."</th>";
	echo "<th>".$LANG['common'][20]."</th>";
	echo "</tr>";
	$ci=new CommonItem;
	$num=0;
	while ($i < $number) {
		$itemtype=$DB->result($result, $i, "itemtype");
		if (haveTypeRight($itemtype,"r")&&$itemtype!=CONSUMABLE_ITEM_TYPE&&$itemtype!=CARTRIDGE_ITEM_TYPE&&$itemtype!=SOFTWARELICENSE_TYPE){
			$query = "SELECT ".$LINK_ID_TABLE[$itemtype].".* "
				." FROM glpi_infocoms "
				." INNER JOIN ".$LINK_ID_TABLE[$itemtype]." ON (".$LINK_ID_TABLE[$itemtype].".ID = glpi_infocoms.items_id) "
				." WHERE glpi_infocoms.itemtype='$itemtype' AND glpi_infocoms.budget = '$budgetID' "
				. getEntitiesRestrictRequest(" AND",$LINK_ID_TABLE[$itemtype])
				." ORDER BY entities_id, ".$LINK_ID_TABLE[$itemtype].".name";

			$result_linked=$DB->query($query);
			$nb=$DB->numrows($result_linked);
			$ci->setType($itemtype);
			if ($nb>$_SESSION['glpilist_limit'] && isset($SEARCH_PAGES[$itemtype)) {

				echo "<tr class='tab_bg_1'>";
				echo "<td class='center'>".$ci->getType()."<br />$nb</td>";
				echo "<td class='center' colspan='2'><a href='"
					. $CFG_GLPI["root_doc"]."/".$SEARCH_PAGES[$itemtype] . "?" . rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$budgetID) . "&" . rawurlencode("field[0]") . "=53&sort=80&order=ASC&deleted=0&start=0"
					. "'>" . $LANG['reports'][57]."</a></td>";

				echo "<td class='center'>-</td><td class='center'>-</td></tr>";
			} else if ($nb){
				for ($prem=true;$data=$DB->fetch_assoc($result_linked);$prem=false){
					$ID="";
					if($_SESSION["glpiview_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
					$name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$itemtype]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";

					echo "<tr class='tab_bg_1'>";
					if ($prem) {
						echo "<td class='center' rowspan='$nb' valign='top'>".$ci->getType()
							.($nb>1?"<br />$nb</td>":"</td>");
					}
					echo "<td class='center'>".getDropdownName("glpi_entities",$data["entities_id"])."</td>";

					echo "<td class='center' ".(isset($data['deleted'])&&$data['deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
					echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
					echo "<td class='center'>".(isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
					echo "</tr>";
				}
			}
			$num+=$nb;
		}
		$i++;
	}
	echo "<tr class='tab_bg_2'><td class='center'>$num</td><td colspan='4'>&nbsp;</td></tr> ";
	echo "</table></div>"    ;


}

function showDeviceBudgetValue($budgetID) {
  	global $DB,$LANG;

	if (!haveRight("budget","r")) return false;

	$query = "SELECT DISTINCT itemtype, SUM(value) as sumvalue
		FROM glpi_infocoms
		WHERE budget = '$budgetID'
		GROUP BY itemtype
      ORDER BY itemtype";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;
   $total = 0;
   
	$ci=new CommonItem;
   $budget =  new Budget();
   $budget->getFromDB($budgetID);

   echo "<br><br><div class='center'><table class='tab_cadre'>";
	echo "<tr>";
	echo "<th colspan='2'>".$LANG['financial'][108]." ".$budget->fields['name']."</th></tr>";
	echo "<tr><th>".$LANG['common'][17]."</th>";
	echo "<th>".$LANG['financial'][21]."</th>";
	echo "</tr>";
	while ($i < $number) {
		$itemtype=$DB->result($result, $i, "itemtype");
      $value = $DB->result($result, $i, "sumvalue");
      $ci->setType($itemtype);
		echo "<tr class='tab_bg_1'>";
		echo "<td class='center'>".$ci->getType()."</td>";
      echo "<td class='center'>".formatNumber($value)."</td>";
      echo "</tr>";
      $total +=$value;
      $i++;
   }

   echo "<tr class='tab_bg_1'><th colspan='2'><br></th></tr>";
   echo "<tr class='tab_bg_1'>";
   echo "<td align='right'>".$LANG['financial'][108]."</td>";
   echo "<td><strong>".formatNumber($total)."</strong></td></tr>";
   echo "<tr class='tab_bg_1'>";
   echo "<td align='right'>".$LANG['financial'][109]."</td>";
   echo "<td><strong>".formatNumber($budget->fields['value'] - $total)."</strong></td></tr>";
   echo "</table></div>";
}
?>
