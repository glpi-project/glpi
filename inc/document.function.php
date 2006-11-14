<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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







function moveUploadedDocument($filename,$old_file=''){
	global $cfg_glpi,$phproot,$lang;

	$_SESSION["MESSAGE_AFTER_REDIRECT"]="";

	if (is_dir($cfg_glpi["doc_dir"]."/_uploads")){
		if (is_file($cfg_glpi["doc_dir"]."/_uploads/".$filename)){
			$dir=isValidDoc($filename);
			$new_path=getUploadFileValidLocationName($dir,$filename,0);
			if (!empty($new_path)){

				// Delete old file
				if(!empty($old_file)&& is_file($cfg_glpi["doc_dir"]."/".$old_file)&& !is_dir($cfg_glpi["doc_dir"]."/".$old_file)) {
					if (unlink($cfg_glpi["doc_dir"]."/".$old_file))
						$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][24]." ".$cfg_glpi["doc_dir"]."/".$old_file."<br>";
					else 
						$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][25]." ".$cfg_glpi["doc_dir"]."/".$old_file."<br>";
				}

				// Déplacement si droit
				if (is_writable ($cfg_glpi["doc_dir"]."/_uploads/".$filename)){
					if (rename($cfg_glpi["doc_dir"]."/_uploads/".$filename,$cfg_glpi["doc_dir"]."/".$new_path)){
						$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][39]."<br>";
						return $new_path;
					}
					else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][40]."<br>";
				} else { // Copi sinon
					if (copy($cfg_glpi["doc_dir"]."/_uploads/".$filename,$cfg_glpi["doc_dir"]."/".$new_path)){
						$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][41]."<br>";
						return $new_path;
					}
					else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][40]."<br>";
				}
			}

		} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][38].": ".$cfg_glpi["doc_dir"]."/_uploads/".$filename."<br>";

	} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][35]."<br>";

	return "";	
}

function uploadDocument($FILEDESC,$old_file=''){
	global $cfg_glpi,$phproot,$lang;

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
			if(!empty($old_file)&& is_file($cfg_glpi["doc_dir"]."/".$old_file)&& !is_dir($cfg_glpi["doc_dir"]."/".$old_file)) {
				if (unlink($cfg_glpi["doc_dir"]."/".$old_file))
					$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][24]." ".$cfg_glpi["doc_dir"]."/".$old_file."<br>";
				else 
					$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][25]." ".$cfg_glpi["doc_dir"]."/".$old_file."<br>";
			}

			// Move uploaded file
			if (move_uploaded_file($FILEDESC['tmp_name'],$cfg_glpi["doc_dir"]."/".$new_path)) {
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][26]."<br>";
				return $new_path;
			} else {
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][27]."<br>";
			}
		}


	}	
	return "";	
}


function getUploadFileValidLocationName($dir,$filename,$force){

	global $cfg_glpi,$lang;

	if (!empty($dir)){
		// Test existance repertoire DOCS
		if (is_dir($cfg_glpi["doc_dir"])){
			// Test existance sous-repertoire type dans DOCS -> sinon création
			if (!is_dir($cfg_glpi["doc_dir"]."/".$dir)){
				$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][34]." ".$cfg_glpi["doc_dir"]."/".$dir."<br>";
				@mkdir($cfg_glpi["doc_dir"]."/".$dir);
			}
			// Copy du fichier uploadé si répertoire existe
			if (is_dir($cfg_glpi["doc_dir"]."/".$dir)){
				if (!$force){
					// Rename file if exists
					$NB_CHAR_MORE=10;
					$i=0;
					$tmpfilename=$filename;
					while ($i<$NB_CHAR_MORE&&is_file($cfg_glpi["doc_dir"]."/".$dir."/".$filename)){
						$filename="_".$filename;
						$i++;
					}

					if ($i==$NB_CHAR_MORE){
						$i=0;
						$filename=$tmpfilename;
						while ($i<$NB_CHAR_MORE&&is_file($cfg_glpi["doc_dir"]."/".$dir."/".$filename)){
							$filename="-".$filename;
							$i++;
						}
						if ($i==$NB_CHAR_MORE){
							$i=0;
							$filename=$tmpfilename;
							while ($i<$NB_CHAR_MORE&&is_file($cfg_glpi["doc_dir"]."/".$dir."/".$filename)){
								$filename="0".$filename;
								$i++;
							}
						}
					}
				}
				if ($force||!is_file($cfg_glpi["doc_dir"]."/".$dir."/".$filename)){
					return $dir."/".$filename;
				} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][28]."<br>";

			} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][29]." ".$cfg_glpi["doc_dir"]."/".$dir." ".$lang["document"][30]."<br>";

		} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][31]." ".$cfg_glpi["doc_dir"]."<br>";

	} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][32]."<br>";

	return "";
}


function showDeviceDocument($instID,$search='') {
	global $db,$cfg_glpi, $lang,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("document","r"))	return false;
	$canedit=haveRight("document","w");

	$query = "SELECT DISTINCT device_type FROM glpi_doc_device WHERE glpi_doc_device.FK_doc = '$instID' AND glpi_doc_device.is_template='0' order by device_type, FK_device";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/document.form.php\">";

	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='3'>".$lang["document"][19].":</th></tr>";
	echo "<tr><th>".$lang["common"][17]."</th>";
	echo "<th>".$lang["common"][16]."</th>";
	echo "<th>&nbsp;</th></tr>";
	$ci=new CommonItem();
	while ($i < $number) {
		$type=$db->result($result, $i, "device_type");
		if (($type!=TRACKING_TYPE&&haveTypeRight($type,"r"))||($type==TRACKING_TYPE&&haveTypeRight($type,"1"))){
			$column="name";
			if ($type==TRACKING_TYPE) $column="ID";

			$query = "SELECT ".$LINK_ID_TABLE[$type].".*, glpi_doc_device.ID AS IDD  FROM glpi_doc_device INNER JOIN ".$LINK_ID_TABLE[$type]." ON (".$LINK_ID_TABLE[$type].".ID = glpi_doc_device.FK_device) WHERE glpi_doc_device.device_type='$type' AND glpi_doc_device.FK_doc = '$instID' AND glpi_doc_device.is_template='0' order by ".$LINK_ID_TABLE[$type].".$column";

			if ($result_linked=$db->query($query))
				if ($db->numrows($result_linked)){
					$ci->setType($type);
					while ($data=$db->fetch_assoc($result_linked)){
						$ID="";
						if ($type==TRACKING_TYPE) $data["name"]=$lang["job"][38]." ".$data["ID"];
						if($cfg_glpi["view_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
						$name= "<a href=\"".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";


						echo "<tr class='tab_bg_1'>";
						echo "<td align='center'>".$ci->getType()."</td>";

						echo "<td align='center' ".(isset($data['deleted'])&&$data['deleted']=='Y'?"class='tab_bg_2_2'":"").">".$name."</td>";
						echo "<td align='center' class='tab_bg_2'>";
						if ($canedit){
							echo "<a href='".$_SERVER["PHP_SELF"]."?deleteitem=deleteitem&amp;ID=".$data["IDD"]."'><b>".$lang["buttons"][6]."</b></a>";
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
		dropdownAllItems("item",0,0,1,1,1,1);
		echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
		echo "<td align='center' class='tab_bg_2'>";
		echo "</td></tr>";
	}

	echo "</table></div>"    ;
	echo "</form>";

}

function addDeviceDocument($conID,$type,$ID,$template=0){
	global $db;
	if ($conID>0&&$ID>0&&$type>0){

		$query="INSERT INTO glpi_doc_device (FK_doc,FK_device, device_type ,is_template) VALUES ('$conID','$ID','$type','$template');";
		$result = $db->query($query);
	}
}

function deleteDeviceDocument($ID){

	global $db;
	$query="DELETE FROM glpi_doc_device WHERE ID= '$ID';";
	$result = $db->query($query);
}


// $withtemplate==3 -> visu via le helpdesk -> plus aucun lien
function showDocumentAssociated($device_type,$ID,$withtemplate=''){

	global $db,$cfg_glpi, $lang,$HTMLRel;

	if ($device_type!=KNOWBASE_TYPE)
		if (!haveRight("document","r")||!haveTypeRight($device_type,"r"))	return false;

	if (empty($withtemplate)) $withtemplate=0;

	$canread=haveTypeRight($device_type,"r");
	$canedit=haveTypeRight($device_type,"w");

	$query = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '$ID' AND glpi_doc_device.device_type = '$device_type' ";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	if ($withtemplate!=2) echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/document.form.php\" enctype=\"multipart/form-data\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$lang["document"][21].":</th></tr>";
	echo "<tr><th>".$lang["common"][16]."</th>";
	echo "<th width='100px'>".$lang["document"][2]."</th>";
	echo "<th>".$lang["document"][33]."</th>";
	echo "<th>".$lang["document"][3]."</th>";
	echo "<th>".$lang["document"][4]."</th>";
	if ($withtemplate<2)echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_doc");
		$assocID=$db->result($result, $i, "ID");

		$con=new Document;
		$con->getFromDB($cID);
		echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
		if ($withtemplate!=3&&$canread){
			echo "<td align='center'><a href='".$HTMLRel."front/document.form.php?ID=$cID'><b>".$con->fields["name"];
			if ($cfg_glpi["view_ID"]) echo " (".$con->fields["ID"].")";
			echo "</b></a></td>";
		} else {
			echo "<td align='center'><b>".$con->fields["name"];
			if ($cfg_glpi["view_ID"]) echo " (".$con->fields["ID"].")";
			echo "</b></td>";
		}

		echo "<td align='center'  width='100px'>".getDocumentLink($con->fields["filename"])."</td>";

		echo "<td align='center'>";
		if (!empty($con->fields["link"]))
			echo "<a target=_blank href='".$con->fields["link"]."'>".$con->fields["link"]."</a>";
		else echo "&nbsp;";
		echo "</td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_rubdocs",$con->fields["rubrique"])."</td>";
		echo "<td align='center'>".$con->fields["mime"]."</td>";

		if ($withtemplate<2) {
			echo "<td align='center' class='tab_bg_2'>";
			if ($canedit)
				echo "<a href='".$HTMLRel."front/document.form.php?deleteitem=deleteitem&amp;ID=$assocID'><b>".$lang["buttons"][6]."</b></a>";
			else echo "&nbsp;";
			echo "</td>";
		}
		echo "</tr>";
		$i++;
	}

	if ($canedit){
		$q="SELECT * FROM glpi_docs WHERE deleted='N'";
		$result = $db->query($q);
		$nb = $db->numrows($result);

		if ($withtemplate<2&&$nb>0){

			echo "<tr class='tab_bg_1'>";
			echo "<td align='center' colspan='3'>";
			echo "<input type='hidden' name='is_template' value='$withtemplate'>";
			echo "<input type='file' name='filename' size='25'>&nbsp;&nbsp;";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>";
			echo "<td align='right' colspan='2'>";
			echo "<div class='software-instal'><input type='hidden' name='item' value='$ID'><input type='hidden' name='type' value='$device_type'>";
			dropdown("glpi_docs","conID");
			echo "</div></td><td align='center'>";
			echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>";

			echo "</tr>";
		}
	}
	if (!empty($withtemplate))
		echo "<input type='hidden' name='is_template' value='1'>";

	echo "</table></div>"    ;
	echo "</form>";

}

function getDocumentLink($filename,$params=""){
	global $db,$HTMLRel,$cfg_glpi;	
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

		if ($result=$db->query($query))
			if ($db->numrows($result)>0){
				$icon=$db->result($result,0,'icon');

				$out="<a href=\"".$HTMLRel."front/document.send.php?file=$filename$params\" target=\"_blank\">&nbsp;<img style=\"vertical-align:middle; margin-left:3px; margin-right:6px;\" alt='".$fileout."' title='".$fileout."' src=\"./".$HTMLRel.$cfg_glpi["typedoc_icon_dir"]."/$icon\" ></a>";				
			}

	}

	$out.="<a href=\"".$HTMLRel."front/document.send.php?file=$filename$params\" target=\"_blank\"><b>$fileout</b></a>";	


	return $out;
}

function cleanFilenameFocument($name){
	return preg_replace("/[^a-zA-Z0-9\-_\.]/","_",$name);
}

function showUploadedFilesDropdown($myname){
	global $cfg_glpi,$lang;

	if (is_dir($cfg_glpi["doc_dir"]."/_uploads")){
		$uploaded_files=array();
		if ($handle = opendir($cfg_glpi["doc_dir"]."/_uploads")) {
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
		} else echo $lang["document"][37];
	} else echo $lang["document"][35];
}

function isValidDoc($filename){
	global $db;
	$splitter=split("\.",$filename);
	$ext=end($splitter);

	$query="SELECT * from glpi_type_docs where ext LIKE '$ext' AND upload='Y'";
	if ($result = $db->query($query))
		if ($db->numrows($result)>0)
			return strtoupper($ext);

	return "";
}


?>
