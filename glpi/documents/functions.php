<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


function titleDocument(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/docs.png\" alt='".$lang["document"][13]."' title='".$lang["document"][13]."'></td><td><a  class='icon_consol' href=\"documents-info-form.php\"><b>".$lang["document"][13]."</b></a>";
         echo "</td></tr></table></div>";
}

function showDocumentOnglets($target,$withtemplate,$actif){
	global $lang, $HTMLRel;

	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}
	
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="10") {echo "class='actif'";} echo "><a href='$target&amp;onglet=10$template'>".$lang["title"][37]."</a></li>";
	
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_docs",$ID);
	$prev=getPreviousItem("glpi_docs",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}

	echo "</ul></div>";
	
}


function showDocumentForm ($target,$ID) {
	// Show Document or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$con = new Document;
	$con_spotted=false;
	if (!$ID) {
		
		if($con->getEmpty()) $con_spotted = true;
	} else {
		if($con->getfromDB($ID)) $con_spotted = true;
	}
	
	if ($con_spotted){
	echo "<form name='form' method='post' action=\"$target\" enctype=\"multipart/form-data\"><div align='center'>";
	echo "<table class='tab_cadre' width='800'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["document"][16].":";
	} else {
		echo $lang["document"][18]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][1].":		</td>";
	echo "<td colspan='2'>";
	autocompletionTextField("name","glpi_docs","name",$con->fields["name"],25);
	echo "</td></tr>";
	
	if (!empty($ID)){
	echo "<tr class='tab_bg_1'><td>".$lang["document"][22].":		</td>";
	echo "<td colspan='2'>".getDocumentLink($con->fields["filename"])."";
	echo "<input type='hidden' name='current_filename' value='".$con->fields["filename"]."'>";
	echo "</td></tr>";
	}
	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);
	
	echo "<tr class='tab_bg_1'><td>".$lang["document"][2]." (".$max_size." Mb max):	</td>";
	echo "<td colspan='2'><input type='file' name='filename' value=\"".$con->fields["filename"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][33].":		</td>";
	echo "<td colspan='2'>";
	autocompletionTextField("link","glpi_docs","link",$con->fields["link"],40);
	echo "</td></tr>";

	
	echo "<tr class='tab_bg_1'><td>".$lang["document"][3].":		</td>";
	echo "<td colspan='2'>";
		dropdownValue("glpi_dropdown_rubdocs","rubrique",$con->fields["rubrique"]);
	echo "</td></tr>";
	

		
	echo "<tr class='tab_bg_1'><td>".$lang["document"][4].":		</td>";
	echo "<td colspan='2'>";
	autocompletionTextField("mime","glpi_docs","mime",$con->fields["mime"],25);
	echo "</td></tr>";
	
	echo "<tr>";
	echo "<td class='tab_bg_1' valign='top'>";

	// table commentaires
	echo $lang["document"][6].":	</td>";
	echo "<td align='center' colspan='2'  class='tab_bg_1'><textarea cols='35' rows='4' name='comment' >".$con->fields["comment"]."</textarea>";

	echo "</td>";
	echo "</tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='3'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	} else {

		echo "<tr>";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>\n\n";
		
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		if ($con->fields["deleted"]=='N')
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		else {
		echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
		}
		
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		echo "</form>";
		
		
	}
	} else {
	echo "<div align='center'><b>".$lang["document"][23]."</b></div>";
	return false;
	
	}
	
	return true;

}

function updateDocument($input) {
	// Update Software in the database

	$con = new Document;
	$con->getFromDB($input["ID"]);
	
	if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type']))
		$input['mime']=$_FILES['filename']['type'];
		

	$input['filename']= uploadDocument($_FILES['filename'],$input['current_filename']);
	if (empty($input['filename'])) unset($input['filename']);
	unset($input['current_filename']);	


	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$con->fields) && $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$con->updateInDB($updates);
	}
}


function uploadDocument($FILEDESC,$old_file=''){
	global $cfg_install,$phproot,$lang;

	$_SESSION["MESSAGE_AFTER_REDIRECT"]="";
	// Is a file uploaded ?
	if (count($FILEDESC)>0&&!empty($FILEDESC['name'])){
		// Clean is name
		$filename=preg_replace("/[^a-zA-Z0-9\-_\.]/","_",$FILEDESC['name']);
		$force=0;
		// Is it a valid file ?
		$dir=isvalidDoc($filename);
		if (!empty($old_file)&&$dir."/".$filename==$old_file) $force=1;
		
		if (!empty($dir)){
			// Test existance repertoire DOCS
			if (is_dir($cfg_install["doc_dir"])){
			// Test existance sous-repertoire type dans DOCS -> sinon création
			if (!is_dir($cfg_install["doc_dir"]."/".$dir)){
				$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][34]." ".$cfg_install["doc_dir"]."/".$dir."<br>";
				@mkdir($cfg_install["doc_dir"]."/".$dir);
			}
			// Copy du fichier uploadé si répertoire existe
			if (is_dir($cfg_install["doc_dir"]."/".$dir)){
				// Rename file if exists
				$NB_CHAR_MORE=10;
				$i=0;
				$tmpfilename=$filename;
				while ($i<$NB_CHAR_MORE&&!$force&&is_file($cfg_install["doc_dir"]."/".$dir."/".$filename)){
					$filename="_".$filename;
					$i++;
				}
				
				if ($i==$NB_CHAR_MORE){
					$i=0;
					$filename=$tmpfilename;
					while ($i<$NB_CHAR_MORE&&!$force&&is_file($cfg_install["doc_dir"]."/".$dir."/".$filename)){
						$filename="-".$filename;
						$i++;
					}
					if ($i==$NB_CHAR_MORE){
						$i=0;
						$filename=$tmpfilename;
						while ($i<$NB_CHAR_MORE&&!$force&&is_file($cfg_install["doc_dir"]."/".$dir."/".$filename)){
							$filename="0".$filename;
							$i++;
						}
					}
				}
				if ($force||!is_file($cfg_install["doc_dir"]."/".$dir."/".$filename)){
					// Delete old file
					if(!empty($old_file)&& is_file($cfg_install["doc_dir"]."/".$old_file)&& !is_dir($cfg_install["doc_dir"]."/".$old_file)) {
						if (unlink($cfg_install["doc_dir"]."/".$old_file))
						$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][24].$cfg_install["doc_dir"]."/".$old_file."<br>";
						else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][25].$cfg_install["doc_dir"]."/".$old_file."<br>";
						}
					
					if (move_uploaded_file($FILEDESC['tmp_name'],$cfg_install["doc_dir"]."/".$dir."/".$filename )) {
   						$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][26]."<br>";
						return $dir."/".$filename;
					} else {
	   					$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][27]."<br>";
					}
				} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][28]."<br>";
			
			} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][29]." ".$cfg_install["doc_dir"]."/".$dir." ".$lang["document"][30]."<br>";
			
			} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][31]." ".$cfg_install["doc_dir"]."<br>";
		
		} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][32]."<br>";
	
	}	
return "";	
}

function addDocument($input) {

	$con = new Document;

	// dump status
	unset($input['add']);
	
	
	if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type']))
		$input['mime']=$_FILES['filename']['type'];
		
	$input['filename']= uploadDocument($_FILES['filename']);		

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($con->fields[$key]) || $con->fields[$key] != $input[$key])) {
			$con->fields[$key] = $input[$key];
		}
	}
	return $con->addToDB();
}


function deleteDocument($input,$force=0) {
	// Delete Document
	
	$con = new Document;
	$con->deleteFromDB($input["ID"],$force);
} 

function restoreDocument($input) {
	// Restore Document
	
	$con = new Document;
	$con->restoreInDB($input["ID"]);
} 


function showDeviceDocument($instID,$search='') {
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_doc = '$instID' AND glpi_doc_device.is_template='0' order by device_type, FK_device";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
	echo "<form method='post' action=\"".$cfg_install["root"]."/documents/documents-info-form.php\">";
	
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["document"][19].":</th></tr>";
	echo "<tr><th>".$lang['document'][20]."</th>";
	echo "<th>".$lang['document'][1]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$device_ID=$db->result($result, $i, "FK_device");
		$ID=$db->result($result, $i, "ID");
		$type=$db->result($result, $i, "device_type");
		$con=new CommonItem;
		$con->getFromDB($type,$device_ID);
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".$con->getType()."</td>";
	echo "<td align='center' ".(isset($con->obj->fields['deleted'])&&$con->obj->fields['deleted']=='Y'?"class='tab_bg_2_2'":"").">".$con->getLink()."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteitem=deleteitem&amp;ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	
	echo "<input type='hidden' name='conID' value='$instID'>";
		dropdownAllItems("item",0,0,1,1,1,1);
	echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "</td></tr>";
	
	echo "</table></div>"    ;
	echo "</form>";
	
}

function addDeviceDocument($conID,$type,$ID,$template=0){

if ($conID>0&&$ID>0&&$type>0){
	$db = new DB;
	$query="INSERT INTO glpi_doc_device (FK_doc,FK_device, device_type ,is_template) VALUES ('$conID','$ID','$type','$template');";
	$result = $db->query($query);
}
}

function deleteDeviceDocument($ID){

$db = new DB;
$query="DELETE FROM glpi_doc_device WHERE ID= '$ID';";
$result = $db->query($query);
}


function dropdownDocuments($name){

	$db=new DB;
	$query="SELECT * from glpi_docs WHERE deleted = 'N' order by name";
	$result=$db->query($query);
	echo "<select name='$name'>";
	echo "<option value='-1'>-----</option>";
	while ($data=$db->fetch_array($result)){
		
	echo "<option value='".$data["ID"]."'>";
	echo $data["name"];
	echo "</option>";
	}

	echo "</select>";	
	
	
	
}
// $withtemplate==3 -> visu via le helpdesk -> plus aucun lien
function showDocumentAssociated($device_type,$ID,$withtemplate=''){

	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '$ID' AND glpi_doc_device.device_type = '$device_type' ";
	

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    if ($withtemplate!=2) echo "<form method='post' action=\"".$cfg_install["root"]."/documents/documents-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='7'>".$lang["document"][21].":</th></tr>";
	echo "<tr><th>".$lang['document'][1]."</th>";
	echo "<th>".$lang['document'][2]."</th>";
	echo "<th>".$lang['document'][33]."</th>";
	echo "<th>".$lang['document'][3]."</th>";
	echo "<th>".$lang['document'][4]."</th>";
	if ($withtemplate<2)echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_doc");
		$assocID=$db->result($result, $i, "ID");
		
		$con=new Document;
		$con->getFromDB($cID);
	echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
	if ($withtemplate!=3)
		echo "<td align='center'><a href='".$HTMLRel."documents/documents-info-form.php?ID=$cID'><b>".$con->fields["name"]." (".$con->fields["ID"].")</b></a></td>";
	else echo "<td align='center'><b>".$con->fields["name"]." (".$con->fields["ID"].")</b></td>";
	
	echo "<td align='center'>".getDocumentLink($con->fields["filename"])."</td>";
	
	echo "<td align='center'>";
	if (!empty($con->fields["link"]))
		echo "<a target=_blank href='".$con->fields["link"]."'>".$con->fields["link"]."</a>";
	else echo "&nbsp;";
	echo "</td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_rubdocs",$con->fields["rubrique"])."</td>";
	echo "<td align='center'>".$con->fields["mime"]."</td>";

	if ($withtemplate<2)echo "<td align='center' class='tab_bg_2'><a href='".$HTMLRel."documents/documents-info-form.php?deleteitem=deleteitem&amp;ID=$assocID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	
	if (isAdmin($_SESSION["glpitype"])){
		$q="SELECT * FROM glpi_docs WHERE deleted='N'";
		$result = $db->query($q);
		$nb = $db->numrows($result);
	
		if ($withtemplate<2&&$nb>0){
			echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
			echo "<div class='software-instal'><input type='hidden' name='item' value='$ID'><input type='hidden' name='type' value='$device_type'>";
			dropdown("glpi_docs","conID");
			echo "</div></td><td align='center'>";
			echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>";
		
			echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		}
	}
	if (!empty($withtemplate))
	echo "<input type='hidden' name='is_template' value='1'>";

	echo "</table></div>"    ;
	echo "</form>";
	
}

function getDocumentLink($filename){
global $HTMLRel,$cfg_install;	
	if (empty($filename))
		return "&nbsp;";
	$out="";
	$splitter=split("/",$filename);
	if (count($splitter)==2)
	$fileout=$splitter[1];
	else $fileout=$filename;
	
	if (count($splitter)==2){
		$db=new DB;
		$query="SELECT * from glpi_type_docs WHERE ext LIKE '".$splitter[0]."' AND icon <> ''";
		$result=$db->query($query);
		if ($db->numrows($result)>0){
			$icon=$db->result($result,0,'icon');
			$out="<a href=\"".$HTMLRel."documents/send-document.php?file=$filename\" target=\"_blank\">&nbsp;<img style=\"vertical-align:middle; margin-left:3px; margin-right:6px;\" alt='".$fileout."' title='".$fileout."' src=\"./".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/$icon\" ></a>";				
			}
	
	}
	
	$out.="<a href=\"".$HTMLRel."documents/send-document.php?file=$filename\" target=\"_blank\"><b>$fileout</b></a>";	
	
	
	return $out;
}

?>
