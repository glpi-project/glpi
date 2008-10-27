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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



class Document extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_docs";
		$this->type=DOCUMENT_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}
	/**
	 * Retrieve an item from the database using the filename
	 *
	 *@param $filename filename of the document
	 *@return true if succeed else false
	**/	
	function getFromDBbyFilename($filename){
		global $DB;
		$query="SELECT ID FROM glpi_docs WHERE filename='$filename'";
		$result=$DB->query($query);
		if ($DB->numrows($result)==1){
			return $this->getFromDB($DB->result($result,0,0));
		} 
		return false;
	}	

	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI,$LANG;

		$query3 = "DELETE FROM glpi_doc_device WHERE (FK_doc = '$ID')";
		$result3 = $DB->query($query3);

		// UNLINK DU FICHIER
		if (!empty($this->fields["filename"]))
			if(is_file(GLPI_DOC_DIR."/".$this->fields["filename"])&& !is_dir(GLPI_DOC_DIR."/".$this->fields["filename"])) {
				if (unlink(GLPI_DOC_DIR."/".$this->fields["filename"])){
					addMessageAfterRedirect($LANG["document"][24].GLPI_DOC_DIR."/".$this->fields["filename"]);
				 } else {
					addMessageAfterRedirect($LANG["document"][25].GLPI_DOC_DIR."/".$this->fields["filename"]);
				}
			}
	}

	function defineTabs($withtemplate){
		global $LANG;
		$ong[1]=$LANG["title"][26];
		$ong[5]=$LANG["Menu"][27];
		if (haveRight("notes","r"))
			$ong[10]=$LANG["title"][37];
		return $ong;
	}

	function prepareInputForAdd($input) {
		global $LANG;

		$input["FK_users"] = $_SESSION["glpiID"];

		if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type'])){
			$input['mime']=$_FILES['filename']['type'];
		}

		if (isset($input["item"])&&isset($input["type"])&&$input["type"]>0&&$input["item"]>0){
			$ci=new CommonItem();
			$ci->getFromDB($input["type"],$input["item"]);
			$input["name"]=addslashes(resume_text($LANG["document"][18]." ".$ci->getType()." - ".$ci->getNameID(),200));
		}

		if (isset($input["upload_file"])&&!empty($input["upload_file"])){
			$input['filename']=moveUploadedDocument($input["upload_file"]);
		} else if (isset($_FILES)&&isset($_FILES['filename']))	{
			$input['filename']= uploadDocument($_FILES['filename']);
		} 

		if (!isset($input['name'])&&isset($input['filename'])){
			$input['name']=$input['filename'];
		}

		unset($input["upload_file"]);
		if (!isset($input["_only_if_upload_succeed"])||!$input["_only_if_upload_succeed"]||!empty($input['filename'])) {
			return $input;
		}
		else {
			return false;
		}
	}

	function post_addItem($newID,$input) {
		global $LANG;
		if (isset($input["item"])&&isset($input["type"])&&$input["item"]>0&&$input["type"]>0){

			addDeviceDocument($newID,$input['type'],$input['item']);
			logEvent($newID, "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG["log"][32]);
		}


	}

	function prepareInputForUpdate($input) {
		if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type']))
			$input['mime']=$_FILES['filename']['type'];

		if (isset($input['current_filename']))
		if (isset($input["upload_file"])&&!empty($input["upload_file"])){
			$input['filename']=moveUploadedDocument($input["upload_file"],$input['current_filename']);
		} else 	$input['filename']= uploadDocument($_FILES['filename'],$input['current_filename']);

		if (empty($input['filename'])) unset($input['filename']);
		unset($input['current_filename']);	

		return $input;
	}


	/**
	 * Print the document form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the computer or the template to print
	 *@param $withtemplate='' boolean : template or basic computer
	 *
	 *@return Nothing (display)
	 **/
	function showForm ($target,$ID,$withtemplate='') {
		global $CFG_GLPI,$LANG;

		if (!haveRight("document","r"))	return false;

		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		$canedit=$this->can($ID,'w');
		$canrecu=$this->can($ID,'recursive');

		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);

		if ($canedit) {
			echo "<form name='form' method='post' action=\"$target\" enctype=\"multipart/form-data\">";
			if (empty($ID)||$ID<0){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}
		}

		echo "<div class='center' id='tabsbody'><table class='tab_cadre_fixe'>";
		
		$this->showFormHeader($ID);
		if ($ID>0) {
			echo "<tr><th>";
			if ($this->fields["FK_users"]>0){
				echo $LANG["document"][42]." ".getUserName($this->fields["FK_users"],1);
			} else {
				echo "&nbsp;";
			}
			echo "</th>";
			echo "<th>".$LANG["common"][26].": ".convDateTime($this->fields["date_mod"])."</th>";
			echo "</tr>\n";
		}

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$this->type))) {
			echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":		</td>";
			echo "<td>";
			autocompletionTextField("name","glpi_docs","name",$this->fields["name"],80,$this->fields["FK_entities"]);
			echo "</td></tr>";
	
			if (!empty($ID)){
				echo "<tr class='tab_bg_1'><td>".$LANG["document"][22].":		</td>";
				echo "<td>".getDocumentLink($this->fields["filename"])."";
				echo "<input type='hidden' name='current_filename' value='".$this->fields["filename"]."'>";
				echo "</td></tr>";
			}
			$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
			$max_size/=1024*1024;
			$max_size=round($max_size,1);
	
			echo "<tr class='tab_bg_1'><td>".$LANG["document"][2]." (".$max_size." Mb max):	</td>";
			echo "<td><input type='file' name='filename' value=\"".$this->fields["filename"]."\" size='40'></td>";
			echo "</tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["document"][36].":		</td>";
			echo "<td>";
			showUploadedFilesDropdown("upload_file");
			echo "</td></tr>";
	
	
			echo "<tr class='tab_bg_1'><td>".$LANG["document"][33].":		</td>";
			echo "<td>";
			autocompletionTextField("link","glpi_docs","link",$this->fields["link"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["document"][3].":		</td>";
			echo "<td>";
			dropdownValue("glpi_dropdown_rubdocs","rubrique",$this->fields["rubrique"]);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["document"][4].":		</td>";
			echo "<td>";
			autocompletionTextField("mime","glpi_docs","mime",$this->fields["mime"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";
	
			echo "<tr>";
			echo "<td class='tab_bg_1' valign='top'>";
	
			// table commentaires
			echo $LANG["common"][25].":	</td>";
			echo "<td class='tab_bg_1'><textarea cols='70' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
	
			echo "</td>";
			echo "</tr>";
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}

		if ($canedit){
			echo "<tr>";

			if ($ID>0) {

				echo "<td class='tab_bg_2' valign='top'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'></div>";
				echo "</td>\n\n";
		
				echo "<td class='tab_bg_2' valign='top'>\n";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				if (!$this->fields["deleted"])
					echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";
				else {
					echo "<div class='center'><input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";
		
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'></div>";
				}
		
				echo "</td></tr>";
			} else {
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td></tr>";
		
			}
			echo "</table></div></form>";
				
		} else { //  can't edit
			echo "</table></div>";			
		} 
			
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";
			
	return true;

	}

}

?>
