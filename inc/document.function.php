<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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





function moveUploadedDocument($filename,$old_file=''){
	global $CFG_GLPI,$LANG;

	$_SESSION["MESSAGE_AFTER_REDIRECT"]="";

	if (is_dir(GLPI_DOC_DIR."/_uploads")){
		if (is_file(GLPI_DOC_DIR."/_uploads/".$filename)){
			$dir=isValidDoc($filename);
			$new_path=getUploadFileValidLocationName($dir,$filename,0);
			if (!empty($new_path)){

				// Delete old file
				if(!empty($old_file)&& is_file(GLPI_DOC_DIR."/".$old_file)&& !is_dir(GLPI_DOC_DIR."/".$old_file)) {
					if (unlink(GLPI_DOC_DIR."/".$old_file))
						$_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][24]." ".GLPI_DOC_DIR."/".$old_file."<br>";
					else 
						$_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][25]." ".GLPI_DOC_DIR."/".$old_file."<br>";
				}

				// D�lacement si droit
				if (is_writable (GLPI_DOC_DIR."/_uploads/".$filename)){
					if (rename(GLPI_DOC_DIR."/_uploads/".$filename,GLPI_DOC_DIR."/".$new_path)){
						$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][39]."<br>";
						return $new_path;
					}
					else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][40]."<br>";
				} else { // Copi sinon
					if (copy(GLPI_DOC_DIR."/_uploads/".$filename,GLPI_DOC_DIR."/".$new_path)){
						$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][41]."<br>";
						return $new_path;
					}
					else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][40]."<br>";
				}
			}

		} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][38].": ".GLPI_DOC_DIR."/_uploads/".$filename."<br>";

	} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][35]."<br>";

	return "";	
}

function uploadDocument($FILEDESC,$old_file=''){
	global $CFG_GLPI,$LANG;

	$_SESSION["MESSAGE_AFTER_REDIRECT"]="";
	// Is a file uploaded ?
	if (count($FILEDESC)>0&&!empty($FILEDESC['name'])){
		// Clean is name
		$filename=cleanFilenameFocument($FILEDESC['name']);
		$force=0;
		// Is it a valid file ?
		$dir=isValidDoc($filename);
		if (!empty($old_file)&&$dir."/".$filename==$old_file) $force=1;

		$new_path=getUploadFileValidLocationName($dir,$filename,$force);

		if (!empty($new_path)){
			// Delete old file
			if(!empty($old_file)&& is_file(GLPI_DOC_DIR."/".$old_file)&& !is_dir(GLPI_DOC_DIR."/".$old_file)) {
				if (unlink(GLPI_DOC_DIR."/".$old_file))
					$_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][24]." ".GLPI_DOC_DIR."/".$old_file."<br>";
				else 
					$_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][25]." ".GLPI_DOC_DIR."/".$old_file."<br>";
			}

			// Move uploaded file
			if (move_uploaded_file($FILEDESC['tmp_name'],GLPI_DOC_DIR."/".$new_path)) {
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][26]."<br>";
				return $new_path;
			} else {
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][27]."<br>";
			}
		}


	}	
	return "";	
}


function getUploadFileValidLocationName($dir,$filename,$force){

	global $CFG_GLPI,$LANG;

	if (!empty($dir)){
		// Test existance repertoire DOCS
		if (is_dir(GLPI_DOC_DIR)){
			// Test existance sous-repertoire type dans DOCS -> sinon cr�tion
			if (!is_dir(GLPI_DOC_DIR."/".$dir)){
				$_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][34]." ".GLPI_DOC_DIR."/".$dir."<br>";
				@mkdir(GLPI_DOC_DIR."/".$dir);
			}
			// Copy du fichier upload�si r�ertoire existe
			if (is_dir(GLPI_DOC_DIR."/".$dir)){
				if (!$force){
					// Rename file if exists
					$NB_CHAR_MORE=10;
					$i=0;
					$tmpfilename=$filename;
					while ($i<$NB_CHAR_MORE&&is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
						$filename="_".$filename;
						$i++;
					}

					if ($i==$NB_CHAR_MORE){
						$i=0;
						$filename=$tmpfilename;
						while ($i<$NB_CHAR_MORE&&is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
							$filename="-".$filename;
							$i++;
						}
						if ($i==$NB_CHAR_MORE){
							$i=0;
							$filename=$tmpfilename;
							while ($i<$NB_CHAR_MORE&&is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
								$filename="0".$filename;
								$i++;
							}
						}
					}
				}
				if ($force||!is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
					return $dir."/".$filename;
				} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][28]."<br>";

			} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["document"][29]." ".GLPI_DOC_DIR."/".$dir." ".$LANG["document"][30]."<br>";

		} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][31]." ".GLPI_DOC_DIR."<br>";

	} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $LANG["document"][32]."<br>";

	return "";
}


function showDeviceDocument($instID,$search='') {
	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("document","r"))	return false;
	$canedit=haveRight("document","w");

	$doc=new Document();
	if ($doc->getFromDB($instID)){

		$query = "SELECT DISTINCT device_type FROM glpi_doc_device WHERE glpi_doc_device.FK_doc = '$instID' order by device_type";
		
		$result = $DB->query($query);
		$number = $DB->numrows($result);
		$i = 0;
	
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/document.form.php\">";
	
		echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='3'>".$LANG["document"][19].":</th></tr>";
		echo "<tr><th>".$LANG["common"][17]."</th>";
		echo "<th>".$LANG["common"][16]."</th>";
		echo "<th>&nbsp;</th></tr>";
		$ci=new CommonItem();
		while ($i < $number) {
			$type=$DB->result($result, $i, "device_type");
			if (($type!=TRACKING_TYPE&&haveTypeRight($type,"r"))||($type==TRACKING_TYPE&&haveTypeRight($type,"1"))){
				$column="name";
				if ($type==TRACKING_TYPE) $column="ID";
	
				$query = "SELECT ".$LINK_ID_TABLE[$type].".*, glpi_doc_device.ID AS IDD  FROM glpi_doc_device INNER JOIN ".$LINK_ID_TABLE[$type]." ON (".$LINK_ID_TABLE[$type].".ID = glpi_doc_device.FK_device) WHERE glpi_doc_device.device_type='$type' AND glpi_doc_device.FK_doc = '$instID' ";
				if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
					$query.=" AND ".$LINK_ID_TABLE[$type].".is_template='0'";
				}
				$query.=" order by ".$LINK_ID_TABLE[$type].".$column";

				if ($result_linked=$DB->query($query))
					if ($DB->numrows($result_linked)){
						$ci->setType($type);
						while ($data=$DB->fetch_assoc($result_linked)){
							$ID="";
							if ($type==TRACKING_TYPE) $data["name"]=$LANG["job"][38]." ".$data["ID"];
							if($CFG_GLPI["view_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
							$name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";
	
	
							echo "<tr class='tab_bg_1'>";
							echo "<td align='center'>".$ci->getType()."</td>";
	
							echo "<td align='center' ".(isset($data['deleted'])&&$data['deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
							echo "<td align='center' class='tab_bg_2'>";
							if ($canedit){
								echo "<a href='".$_SERVER['PHP_SELF']."?deleteitem=deleteitem&amp;ID=".$data["IDD"]."'><strong>".$LANG["buttons"][6]."</strong></a>";
							} else echo "&nbsp;";
							echo "</td></tr>";
						}
					}
			}
			$i++;
		}
	
		if (haveRight("document","w"))	{
			echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	
			echo "<input type='hidden' name='conID' value='$instID'>";
			$types=$CFG_GLPI["state_types"];
			$types[]=ENTERPRISE_TYPE;
			$types[]=CARTRIDGE_TYPE;
			$types[]=CONSUMABLE_TYPE;
			$types[]=CONTRACT_TYPE;
			dropdownAllItems("item",0,0,$doc->fields['FK_entities'],$types);
			echo "<input type='submit' name='additem' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td>";
			echo "<td align='center' class='tab_bg_2'>";
			echo "</td></tr>";
		}
	
		echo "</table></div>"    ;
		echo "</form>";
	}

}

function addDeviceDocument($conID,$type,$ID){
	global $DB;
	if ($conID>0&&$ID>0&&$type>0){

		$query="INSERT INTO glpi_doc_device (FK_doc,FK_device, device_type) VALUES ('$conID','$ID','$type');";
		$result = $DB->query($query);
	}
}

function deleteDeviceDocument($ID){

	global $DB;
	$query="DELETE FROM glpi_doc_device WHERE ID= '$ID';";
	$result = $DB->query($query);
}


// $withtemplate==3 -> visu via le helpdesk -> plus aucun lien
function showDocumentAssociated($device_type,$ID,$withtemplate=''){

	global $DB,$CFG_GLPI, $LANG;

	if ($device_type!=KNOWBASE_TYPE)
		if (!haveRight("document","r")||!haveTypeRight($device_type,"r"))	return false;

	if (empty($withtemplate)) $withtemplate=0;

	$canread=haveTypeRight($device_type,"r");
	$canedit=haveTypeRight($device_type,"w");

	$query = "SELECT glpi_doc_device.ID as assocID, glpi_docs.* FROM glpi_doc_device 
		LEFT JOIN glpi_docs ON (glpi_doc_device.FK_doc=glpi_docs.ID) 
		WHERE glpi_doc_device.FK_device = '$ID' AND glpi_doc_device.device_type = '$device_type' ";
	//echo $query;
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	if ($withtemplate!=2) echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/document.form.php\" enctype=\"multipart/form-data\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='6'>".$LANG["document"][21].":</th></tr>";
	echo "<tr><th>".$LANG["common"][16]."</th>";
	echo "<th width='100px'>".$LANG["document"][2]."</th>";
	echo "<th>".$LANG["document"][33]."</th>";
	echo "<th>".$LANG["document"][3]."</th>";
	echo "<th>".$LANG["document"][4]."</th>";
	if ($withtemplate<2)echo "<th>&nbsp;</th>";
	echo "</tr>";
	if ($number)
	while ($data=$DB->fetch_assoc($result)) {
		$docID=$data["ID"];
		$assocID=$data["assocID"];

		echo "<tr class='tab_bg_1".($data["deleted"]?"_2":"")."'>";
		if ($withtemplate!=3&&$canread){
			echo "<td align='center'><a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?ID=$docID'><strong>".$data["name"];
			if ($CFG_GLPI["view_ID"]) echo " (".$docID.")";
			echo "</strong></a></td>";
		} else {
			echo "<td align='center'><strong>".$data["name"];
			if ($CFG_GLPI["view_ID"]) echo " (".$docID.")";
			echo "</strong></td>";
		}

		echo "<td align='center'  width='100px'>".getDocumentLink($data["filename"])."</td>";

		echo "<td align='center'>";
		if (!empty($data["link"]))
			echo "<a target=_blank href='".$data["link"]."'>".$data["link"]."</a>";
		else echo "&nbsp;";
		echo "</td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_rubdocs",$data["rubrique"])."</td>";
		echo "<td align='center'>".$data["mime"]."</td>";

		if ($withtemplate<2) {
			echo "<td align='center' class='tab_bg_2'>";
			if ($canedit)
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?deleteitem=deleteitem&amp;ID=$assocID'><strong>".$LANG["buttons"][6]."</strong></a>";
			else echo "&nbsp;";
			echo "</td>";
		}
		echo "</tr>";
		$i++;
	}

	if ($canedit){
		// Restrict entity for knowbase
		$ci=new CommonItem();
		$ci->getFromDB($device_type,$ID);
		$entity=-1;
		$limit="";
		if ($ci->getField('FK_entities')>=0){
			$entity=$ci->getField('FK_entities');
			$limit=" AND FK_entities='$entity' ";
		}

		$q="SELECT count(*) FROM glpi_docs WHERE deleted='0' $limit";
			
		$result = $DB->query($q);
		$nb = $DB->result($result,0,0);
	
		if ($withtemplate<2){
	
			echo "<tr class='tab_bg_1'>";
			echo "<td align='center' colspan='3'>";
			echo "<input type='file' name='filename' size='25'>&nbsp;&nbsp;";
			if ($entity){
				echo "<input type='hidden' name='FK_entities' value='$entity'>";
			}
			echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td>";
			echo "<td align='left' colspan='2'>";
			echo "<div class='software-instal'><input type='hidden' name='item' value='$ID'><input type='hidden' name='type' value='$device_type'>";
			dropdownDocument("conID",$entity);
			//dropdown("glpi_docs","conID",1,$entity);
			echo "</div></td><td align='center'>";
			echo "<input type='submit' name='additem' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td>";
	
			echo "</tr>";
		}
	}

	echo "</table></div>"    ;
	echo "</form>";

}

function getDocumentLink($filename,$params=""){
	global $DB,$CFG_GLPI;	
	if (empty($filename))
		return "&nbsp;";
	$out="";
	$splitter=split("/",$filename);
	if (count($splitter)==2)
		$fileout=$splitter[1];
	else $fileout=$filename;

	if (strlen($fileout)>20) $fileout=substr($fileout,0,20)."...";

	if (count($splitter)==2){

		$query="SELECT * from glpi_type_docs WHERE ext LIKE '".$splitter[0]."' AND icon <> ''";

		if ($result=$DB->query($query))
			if ($DB->numrows($result)>0){
				$icon=$DB->result($result,0,'icon');

				$out="<a href=\"".$CFG_GLPI["root_doc"]."/front/document.send.php?file=$filename$params\" target=\"_blank\">&nbsp;<img style=\"vertical-align:middle; margin-left:3px; margin-right:6px;\" alt='".$fileout."' title='".$fileout."' src=\"".$CFG_GLPI["typedoc_icon_dir"]."/$icon\" ></a>";				
			}

	}

	$out.="<a href=\"".$CFG_GLPI["root_doc"]."/front/document.send.php?file=$filename$params\" target=\"_blank\"><strong>$fileout</strong></a>";	


	return $out;
}

function cleanFilenameFocument($name){
	return preg_replace("/[^a-zA-Z0-9\-_\.]/","_",$name);
}

function showUploadedFilesDropdown($myname){
	global $CFG_GLPI,$LANG;

	if (is_dir(GLPI_DOC_DIR."/_uploads")){
		$uploaded_files=array();
		if ($handle = opendir(GLPI_DOC_DIR."/_uploads")) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$dir=isValidDoc($file);
					if (!empty($dir))
						$uploaded_files[]=$file;
				}
			}
			closedir($handle);
		}

		if (count($uploaded_files)){
			echo "<select name='$myname'>";
			echo "<option value=''>-----</option>";
			foreach ($uploaded_files as $key => $val)
				echo "<option value=\"$val\">$val</option>";
			echo "</select>";
		} else echo $LANG["document"][37];
	} else echo $LANG["document"][35];
}

function isValidDoc($filename){
	global $DB;
	$splitter=split("\.",$filename);
	$ext=end($splitter);

	$query="SELECT * from glpi_type_docs where ext LIKE '$ext' AND upload='1'";
	if ($result = $DB->query($query))
		if ($DB->numrows($result)>0)
			return strtoupper($ext);

	return "";
}


?>
