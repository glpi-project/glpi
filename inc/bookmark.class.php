<?php

/*
 * @version $Id: cron.class.php 6235 2008-01-02 17:57:10Z moyo $
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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class Bookmark extends CommonDBTM {

	function Bookmark() {
		global $CFG_GLPI;
		$this->table = "glpi_bookmark";
		$this->entity_assign=true;
		$this->may_be_recursive=true;
		$this->may_be_private=true;
	}


	function prepareInputForAdd($input) {
		if (!isset($input['url'])||!isset($input['type'])){
			return false;
		}

		$taburl = parse_url(urldecode($input['url']));

		$index = strpos($taburl["path"],"plugins");
		if (!$index)
			$index = strpos($taburl["path"],"front");
		$input['path'] = substr($taburl["path"],$index,strlen($taburl["path"]) - $index);

		$query_tab=array();
		
		if (isset($taburl["query"])){
			parse_str($taburl["query"],$query_tab);
		}

		$input['query']=append_params($this->prepareQueryToStore($input['type'],$query_tab));

		return $input;
	}

	function post_getEmpty () {
		global $LANG;
		$this->fields["FK_users"]=$_SESSION['glpiID'];
		$this->fields["private"]=1;
		$this->fields["FK_entities"]=$_SESSION["glpiactive_entity"];
	}

	function showForm($target,$ID,$type=0,$url='',$device_type=0) {


		global $CFG_GLPI,$LANG;

		$spotted=false;
		if ($ID>0) {
			if($this->can($ID,'r')) {
				$spotted = true;	
			}
		} else {
			if ($this->can(-1,'w')){
				$spotted = true;
			}
		} 

		if ($spotted){
			$canedit=$this->can($ID,'w');

			if($canedit) {
				echo "<form method='post' name='form_save_query' action=\"$target\">";
			}
			echo "<div class='center'>";
			if ($device_type!=0){
				echo "<input type='hidden' name='device_type' value='$device_type'>";
			}
			if ($type!=0){
				echo "<input type='hidden' name='type' value='$type'>";
			}
			if (!empty($url)){
				echo "<input type='hidden' name='url' value='" . urlencode($url) . "'>";
			}

	
	//" . (isset($taburl["query"])?urlencode($taburl["query"]."&reset_before"):"reset_before") . "
	
			echo "<table class='tab_cadre' width='500'>";
			echo "<tr><th>&nbsp;</th><th>";
			if (!$ID) {
				echo $LANG["bookmark"][4];
			} else {
				echo $LANG["common"][2]." $ID";
			}		

			echo "</th></tr>";


			echo "<tr><td class='tab_bg_1'>".$LANG["common"][16]."</td>"; 

			echo "<td class='tab_bg_1'>";
			autocompletionTextField("name",$this->table,"name",$this->fields['name'],40);				
			echo "</td></tr>"; 

			echo "<tr class='tab_bg_2'><td>".$LANG["common"][17].":		</td>";
			echo "<td>";

			if($canedit) { 
				privatePublicSwitch($this->fields["private"],$this->fields["FK_entities"],$this->fields["recursive"]);
			}else{
				echo getYesNo($this->fields["private"]);				
			}


			if (!$ID) { // add
				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='FK_users' value=\"".$this->fields['FK_users']."\">\n";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";
			} elseif($canedit) { 
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
			if($canedit) {
				echo "</form>";
			}
		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";

		}
		
	}

	function prepareQueryToStore($type,$query_tab){
		switch ($type){
			case BOOKMARK_SEARCH :
				if (isset($query_tab['start'])){
					unset($query_tab['start']);
				}
			break;
		}
		
		return $query_tab;
	}

	function prepareQueryToUse($type,$query_tab){
		switch ($type){
			case BOOKMARK_SEARCH :
				$query_tab['reset_before']=1;
			break;
		}
		
		return $query_tab;
	}

	function load($ID){
		
		$this->getFromDB($ID);
		$url = GLPI_ROOT."/".urldecode($this->fields["path"]);
		$query_tab=array();
		parse_str($this->fields["query"],$query_tab);
		$params=$this->prepareQueryToUse($this->fields["type"],$query_tab);
		$url.="?".append_params($params);
		echo "<script type='text/javascript' >\n";
			echo "window.opener.location.href='$url';";
			//echo "window.close();";
		echo "</script>";
	}
	
	function showBookmarkList($target,$user_id) {
		global $DB,$LANG,$CFG_GLPI;

		$result = $DB->query("SELECT ID, name FROM ".$this->table." WHERE FK_users=$user_id ORDER BY name");

		echo "<br>";

		echo "<div class='center'>"; 
		echo "<form method='post' name='form_load_bookmark' action=\"$target\">";

		echo "<table class='tab_cadrehov'>";
		echo "<tr><th align='center' colspan='2'>".$LANG["buttons"][52]." ".$LANG["bookmark"][1]."</th><th width='20px'>&nbsp;</th>";

		if( $DB->numrows($result))
		{

			while ($data = $DB->fetch_array($result))
			{
				echo "<tr class='tab_bg_1'>";
				echo "<td width='10'>";
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
				echo "<input type='checkbox' name='bookmark[" . $data["ID"] . "]' " . $sel . ">";
				echo "</td>";
				echo "<td>";
				echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=load_bookmark&bookmark_id=".$data["ID"]."\">".$data["name"]."</a>";
				echo "</td>";
				echo "<td><a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&ID=".$data["ID"]."\"><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png'></a></td>";
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
	
			echo "</div>";

		}
		else
			echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG["bookmark"][3]."</td></tr></table>";
		
		echo "</form></div>";
	}
}
?>
