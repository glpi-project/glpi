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


class SetupDefaultDisplay extends CommonDBTM{

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_display_default";
		$this->type=-1;
	}

}

/// Bookmark class
class Bookmark extends CommonDBTM {

	/**
	 * Constructor
	 **/
	function __construct() {
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
		// Set new user if initial user have been deleted 
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

        function cleanDBonPurge($ID) {
		global $DB;
		$query="DELETE FROM glpi_display_default WHERE FK_bookmark=$ID";
		$DB->query($query);
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


		// Only an edit form : always check w right
		if ($ID > 0){
			$this->check($ID,'w');
		} else {
			// Create item : do getempty before check right to set default values
			$this->getEmpty();
			$this->check(-1,'w');
		} 

		
		echo '<br>';
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
		autocompletionTextField("name",$this->table,"name",$this->fields['name'],40,-1,$this->fields["FK_users"]);				
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
	* @param $opener boolean load bookmark in opener window ? false -> current window
	* @return nothing
	**/
	function load($ID,$opener=true){
		
		if ($this->getFromDB($ID)){
			$url = GLPI_ROOT."/".rawurldecode($this->fields["path"]);
			$query_tab=array();
			parse_str($this->fields["query"],$query_tab);
			$params=$this->prepareQueryToUse($this->fields["type"],$query_tab);
			$url.="?".append_params($params);
			if ($opener){
				echo "<script type='text/javascript' >\n";
					echo "window.opener.location.href='$url';";
					//echo "window.close();";
				echo "</script>";
			} else {
				glpi_header($url);
			}
		}
	}
	
	/**
	* Mark bookmark as default view for the currect user
	*
	* @param $ID ID of the bookmark
	* @return nothing
	**/	
	function mark_default($ID){
		global $DB;
		
		// Get bookmark / Only search bookmark
		if ($this->getFromDB($ID) && $this->fields['type']=BOOKMARK_SEARCH){
			$dd=new SetupDefaultDisplay();
			// Is default view for this device_type already exists ?
			$query="SELECT ID FROM glpi_display_default 
				WHERE FK_users='".$_SESSION['glpiID']."'
					AND device_type='".$this->fields['device_type']."'";
			if ($result=$DB->query($query)){
				if ($DB->numrows($result) > 0){
					// already exists update it
					$updateID=$DB->result($result,0,0);
					$dd->update(array('ID'=>$updateID,'FK_bookmark'=>$ID));
				} else {
					$dd->add(array('FK_bookmark'=>$ID,'FK_users'=>$_SESSION['glpiID'],'device_type'=>$this->fields['device_type']));
				}
			}
			
		}
	}

	/**
	* Mark bookmark as default view for the currect user
	*
	* @param $ID ID of the bookmark
	* @return nothing
	**/	
	function unmark_default($ID){
		global $DB;
		
		// Get bookmark / Only search bookmark
		if ($this->getFromDB($ID) && $this->fields['type']=BOOKMARK_SEARCH){
			$dd=new SetupDefaultDisplay();
			// Is default view for this device_type already exists ?
			$query="SELECT ID FROM glpi_display_default 
				WHERE FK_users='".$_SESSION['glpiID']."'
					AND FK_bookmark='$ID'
					AND device_type='".$this->fields['device_type']."'";
			if ($result=$DB->query($query)){
				if ($DB->numrows($result) > 0){
					// already exists delete it
					$deleteID=$DB->result($result,0,0);
					$dd->delete(array('ID'=>$deleteID));
				} 
			}
			
		}
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
	
		$query="SELECT ".$this->table.".*, glpi_display_default.ID AS IS_DEFAULT FROM ".$this->table." 
			LEFT JOIN glpi_display_default 
				ON (".$this->table.".device_type = glpi_display_default.device_type AND ".$this->table.".ID = glpi_display_default.FK_bookmark) 
			WHERE ";
			
		if ($private){
			$query.="(".$this->table.".private=1 AND ".$this->table.".FK_users='".$_SESSION['glpiID']."') ";
		} else {
			$query.="(".$this->table.".private=0  ".getEntitiesRestrictRequest("AND",$this->table,"","",true) . ")";
		}
			
		$query.=" ORDER BY device_type,name";

		if ($result = $DB->query($query)){
			echo "<br>";
	
			$rand=mt_rand();
			echo "<form method='post' id='form_load_bookmark$rand' action=\"$target\">";
			echo "<div class='center' id='tabsbody' >";
	
	
			echo "<table class='tab_cadrehov'>";
			echo "<tr><th align='center' colspan='3'>".$LANG["buttons"][52]." ".$LANG["bookmark"][1]."</th>";
			echo "<th width='20px'>&nbsp;</th>";
			echo "<th>".$LANG["bookmark"][6]."</th>";
			echo "</tr>";
			
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
					echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=load_bookmark&amp;ID=".$this->fields["ID"]."\">".$this->fields["name"]."</a>";
					echo "</td>";
					if ($canedit) {
						echo "<td><a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;ID=".$this->fields["ID"]."\"><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='".$LANG["buttons"][14]."'></a></td>";
					} else {
						echo "<td>&nbsp;</td>";					
					}
					echo "<td align='center'>";
					if ($this->fields['type']==BOOKMARK_SEARCH){
						if (is_null($this->fields['IS_DEFAULT'])){
							echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;mark_default=1&amp;ID=".$this->fields["ID"]."\">".$LANG["choice"][0]."</a>";;
						} else {
							echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;mark_default=0&amp;ID=".$this->fields["ID"]."\">".$LANG["choice"][1]."</a>";;
						}
					}
					echo "</td>";

					echo "</tr>";
				}
				echo "</table>";
				echo "</div>";
				
				echo "<div class='center'>";
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('form_load_bookmark$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all'>".$LANG["buttons"][18]."</a></td>";
				echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('form_load_bookmark$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none'>".$LANG["buttons"][19]."</a>";
				echo "</td><td align='left' width='80%'>";
				echo "<input type='submit' name='delete_several' value=\"".$LANG["buttons"][6]."\" class='submit'>";
				echo "</td></tr>";
				echo "</table>";
		
			}
			else {
				echo "<tr class='tab_bg_1'><td colspan='5'>".$LANG["bookmark"][3]."</td></tr></table>";
			}
			echo '</div>';
			echo "</form>";
		}
	}
}
?>
