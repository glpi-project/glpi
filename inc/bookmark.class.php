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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/// Bookmark class
class Bookmark extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function Bookmark() {
		global $CFG_GLPI;
		$this->table = "glpi_bookmark";
		$this->entity_assign=true;
		$this->may_be_recursive=true;
		$this->may_be_private=true;
		// To allow "can" method (canView & canCreate)
		$this->type = BOOKMARK_TYPE;
	}


	function prepareInputForAdd($input) {
		if (!isset($input['url'])||!isset($input['type'])){
			return false;
		}

		$taburl = parse_url(rawurldecode($input['url']));

		$index = strpos($taburl["path"],"plugins");
		if (!$index)
			$index = strpos($taburl["path"],"front");
		$input['path'] = substr($taburl["path"],$index,strlen($taburl["path"]) - $index);

		$query_tab=array();
		
		if (isset($taburl["query"])){
			parse_str($taburl["query"],$query_tab);
		}

		$input['query']=append_params($this->prepareQueryToStore($input['type'],$query_tab,$input['device_type']));

		return $input;
	}

	function pre_updateInDB($input,$updates) {
		if ($this->fields['FK_users']==0){
			$input['FK_users']=$_SESSION["glpiID"];
			$this->fields['FK_users']=$_SESSION["glpiID"];
			$updates[]="FK_users";
		}
		return array($input,$updates);
	}

	function post_getEmpty () {
		global $LANG;
		$this->fields["FK_users"]=$_SESSION['glpiID'];
		$this->fields["private"]=1;
		$this->fields["recursive"]=0;
		$this->fields["FK_entities"]=$_SESSION["glpiactive_entity"];
	}

	/**
	* Print the bookmark form
	*
	* @param $target target for the form
	* @param $ID ID of the item
	* @param $type bookmark type when adding a new bookmark
	* @param $url url when adding a new bookmark
	* @param $device_type device_type when adding a new bookmark
	**/
	function showForm($target,$ID,$type=0,$url='',$device_type=0) {


		global $CFG_GLPI,$LANG;

		$canedit=false;
		
		if ($ID>0) {
			if($this->can($ID,'w')) {
				$canedit = true;	
			}
		} else {
			if ($this->can(-1,'w')){
				$canedit = true;
			}
		} 
		echo '<br>';
		if ( $canedit){
			echo "<form method='post' name='form_save_query' action=\"$target\">";
			echo "<div class='center'>";
			if ($device_type!=0){
				echo "<input type='hidden' name='device_type' value='$device_type'>";
			}
			if ($type!=0){
				echo "<input type='hidden' name='type' value='$type'>";
			}

			if (!empty($url)) {
				echo "<input type='hidden' name='url' value='" . rawurlencode($url) . "'>";
			}

			echo "<table class='tab_cadre' width='500'>";
			echo "<tr><th>&nbsp;</th><th>";
			if ($ID>0) {
				echo $LANG["common"][2]." $ID";
			} else {
				echo $LANG["bookmark"][4];
			}		

			echo "</th></tr>";


			echo "<tr><td class='tab_bg_1'>".$LANG["common"][16]."</td>"; 

			echo "<td class='tab_bg_1'>";
			autocompletionTextField("name",$this->table,"name",$this->fields['name'],40);				
			echo "</td></tr>"; 

			echo "<tr class='tab_bg_2'><td>".$LANG["common"][17].":		</td>";
			echo "<td>";

			if(haveRight("bookmark_public","w")) { 
				privatePublicSwitch($this->fields["private"],$this->fields["FK_entities"],$this->fields["recursive"]);
			}else{
				if ($this->fields["private"]){
					echo $LANG["common"][77];
				} else {
					echo $LANG["common"][76];
				}
			}

			echo "</td></tr>";

			if ($ID<=0) { // add
				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='FK_users' value=\"".$this->fields['FK_users']."\">\n";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";
			} else { 
				echo "<tr>";

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";

				echo "<input type='hidden' name='ID' value=\"$ID\">\n";

				echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";

				echo "</td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "</div>";
			echo "</form>";
		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";

		}
		
	}

	/**
	* Prepare query to store depending of the type
	*
	* @param $type bookmark type
	* @param $query_tab parameters array
	* @param $device_type device type
	* @return clean query array
	**/
	function prepareQueryToStore($type,$query_tab,$device_type=0){
		switch ($type){
			case BOOKMARK_SEARCH :
				if (isset($query_tab['start'])){
					unset($query_tab['start']);
				}
				// Manage glpisearchcount / dclean if needed + store
				if (isset($query_tab['glpisearchcount'])){
					unset($query_tab['glpisearchcount']);
				}
				if (isset($_SESSION["glpisearchcount"][$device_type])){
					$query_tab['glpisearchcount']=$_SESSION["glpisearchcount"][$device_type];
				} else {
					$query_tab['glpisearchcount']=1;
				}

				// Manage glpisearchcount2 / dclean if needed + store
				if (isset($query_tab['glpisearchcount2'])){
					unset($query_tab['glpisearchcount2']);
				}
				if (isset($_SESSION["glpisearchcount2"][$device_type])){
					$query_tab['glpisearchcount2']=$_SESSION["glpisearchcount2"][$device_type];
				}else {
					$query_tab['glpisearchcount2']=0;
				}
			break;
		}
		
		return $query_tab;
	}
	/**
	* Prepare query to use depending of the type
	*
	* @param $type bookmark type
	* @param $query_tab parameters array
	* @return prepared query array
	**/
	function prepareQueryToUse($type,$query_tab){
		switch ($type){
			case BOOKMARK_SEARCH :
				$query_tab['reset_before']=1;
			break;
		}
		
		return $query_tab;
	}

	/**
	* load a bookmark
	*
	* @param $ID ID of the bookmark
	* @return nothing
	**/
	function load($ID){
		
		$this->getFromDB($ID);
		$url = GLPI_ROOT."/".rawurldecode($this->fields["path"]);
		$query_tab=array();
		parse_str($this->fields["query"],$query_tab);
		$params=$this->prepareQueryToUse($this->fields["type"],$query_tab);
		$url.="?".append_params($params);
		echo "<script type='text/javascript' >\n";
			echo "window.opener.location.href='$url';";
			//echo "window.close();";
		echo "</script>";
	}
	
	/**
	* Show bookmarks list
	*
	* @param $target target to use for links
	* @param $private show private of public bookmarks ?
	* @return nothing
	**/
	function showBookmarkList($target,$private=1) {
		global $DB,$LANG,$CFG_GLPI;

		if (!$private && !haveRight('bookmark_public','r')){ 
			return false;
		}
	
		$query="SELECT * FROM `".$this->table."` WHERE ";
			
		if ($private){
			$query.="(private=1 AND FK_users='".$_SESSION['glpiID']."') ";
		} else {
			$query.="(private=0  ".getEntitiesRestrictRequest("AND",$this->table,"","",true) . ")";
		}
			
		$query.=" ORDER BY device_type,name";

		if ($result = $DB->query($query)){
			echo "<br>";
	
	
			echo "<form method='post' id='form_load_bookmark' action=\"$target\">";
			echo "<div class='center'>";
	
			echo "<div id='barre_onglets_percent'><ul id='onglet'>";
			echo "<li ".($private?"class='actif'":"")."><a href='$target?onglet=1&amp;popup=load_bookmark'>".$LANG["common"][77]."</a></li>";
			if (haveRight('bookmark_public','r')){
				echo "<li ".(!$private?"class='actif'":"")."><a href='$target?onglet=0&amp;popup=load_bookmark'>".$LANG["common"][76]."</a></li>";
			}
			echo "</ul></div>";
	
	
			echo "<table class='tab_cadrehov'>";
			echo "<tr><th align='center' colspan='3'>".$LANG["buttons"][52]." ".$LANG["bookmark"][1]."</th><th width='20px'>&nbsp;</th>";
	
			
			if( $DB->numrows($result)){
				$ci=new CommonItem();
				$current_type=-1;
				$current_type_name="&nbsp;";
				while ($this->fields = $DB->fetch_assoc($result)){
					if ($current_type!=$this->fields['device_type']){
						$current_type=$this->fields['device_type'];
						$ci->setType($current_type);
						$current_type_name=$ci->getType();
					}
					$canedit=$this->can($this->fields["ID"],"w");

					echo "<tr class='tab_bg_1'>";
					echo "<td width='10px'>";
					if ($canedit) {
						$sel="";
						if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
						echo "<input type='checkbox' name='bookmark[" . $this->fields["ID"] . "]' " . $sel . ">";
					} else {
						echo "&nbsp;";
					}
					echo "</td>";
					echo "<td>$current_type_name</td>";
					echo "<td>";
					echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=load_bookmark&amp;bookmark_id=".$this->fields["ID"]."\">".$this->fields["name"]."</a>";
					echo "</td>";
					if ($canedit) {
						echo "<td><a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;ID=".$this->fields["ID"]."\"><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='".$LANG["buttons"][14]."'></a></td>";
					} else {
						echo "<td>&nbsp;</td>";					
					}
					echo "</tr>";
				}
				echo "</table>";
				echo "</div>";
				
				echo "<div class='center'>";
				echo "<table width='80%'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('form_load_bookmark') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG["buttons"][18]."</a></td>";
				echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('form_load_bookmark') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG["buttons"][19]."</a>";
				echo "</td><td align='left' width='80%'>";
				echo "<input type='submit' name='delete_several' value=\"".$LANG["buttons"][6]."\" class='submit'>";
				echo "</td></tr>";
				echo "</table>";
		
			}
			else {
				echo "<tr class='tab_bg_1'><td colspan='4'>".$LANG["bookmark"][3]."</td></tr></table>";
			}
			echo '</div>';
			echo "</form>";
		}
	}
}
?>
