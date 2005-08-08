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


function searchFormDocument($field="",$phrasetype= "",$contains="",$sort= "",$deleted="",$link="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["glpi_docs.ID"]				= $lang["document"][14];
	$option["glpi_docs.name"]			= $lang["document"][1];
	$option["glpi_docs.filename"]			= $lang["document"][2];
	$option["glpi_docs.link"]			= $lang["document"][33];
	$option["glpi_dropdown_rubdocs.name"]		= $lang["document"][3];
	$option["glpi_docs.mime"]			= $lang["document"][4];	
	$option["glpi_docs.comment"]			= $lang["document"][6];

	echo "<form method=get action=\"".$cfg_install["root"]."/documents/documents-search.php\">";
	echo "<div align='center'><table class='tab_cadre' width='800'>";
	echo "<tr><th colspan='4'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";

	echo "<table>";
	
	for ($i=0;$i<$_SESSION["glpisearchcount"];$i++){
		echo "<tr><td align='right'>";
		if ($i==0){
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?add_search_count=1'><img src=\"".$HTMLRel."pics/plus.png\" alt='+'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"]>1)
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?delete_search_count=1'><img src=\"".$HTMLRel."pics/moins.png\" alt='-'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if ($i>0) {
			echo "<select name='link[$i]'>";
			
			echo "<option value='AND' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "AND") echo "selected";
			echo ">AND</option>";
			
			echo "<option value='OR' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "OR") echo "selected";
			echo ">OR</option>";		

			echo "</select>";
		}
		
		echo "<input type='text' size='15' name=\"contains[$i]\" value=\"". (is_array($contains)&&isset($contains[$i])?stripslashes($contains[$i]):"" )."\" >";
		echo "&nbsp;";
		echo $lang["search"][10]."&nbsp;";
	
		echo "<select name=\"field[$i]\" size='1'>";
        	echo "<option value='all' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "all") echo "selected";
		echo ">".$lang["search"][7]."</option>";
        	reset($option);
		foreach ($option as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if(is_array($field)&&isset($field[$i]) && $key == $field[$i]) echo "selected";
			echo ">". $val ."</option>\n";
		}
		echo "</select>&nbsp;";

		
		echo "</td></tr>";
	}
	echo "</table>";
	echo "</td>";

	echo "<td>";

	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

function showDocumentList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted,$link) {

	// Lists Document

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	$where ="";
	
	foreach ($field as $k => $f)
	if ($k<$_SESSION["glpisearchcount"])
	if ($contains[$k]==""){
		if ($k>0) $where.=" ".$link[$k]." ";
		$where.=" ('1'='1') ";
		}
	else {
		if ($k>0) $where.=" ".$link[$k]." ";
		$where.="( ";
		// Build query
		if($f == "all") {
			$fields = $db->list_fields("glpi_docs");
			$columns = $db->num_fields($fields);
			
			for ($i = 0; $i < $columns; $i++) {
				if($i != 0) {
					$where .= " OR ";
				}
				$coco = $db->field_name($fields, $i);
				$where .= "glpi_docs.".$coco . " LIKE '%".$contains[$k]."%'";
			}
		}
		else {
			if ($phrasetype == "contains") {
				$where .= "($f LIKE '%".$contains[$k]."%')";
			}
			else {
				$where .= "($f LIKE '".$contains[$k]."')";
			}
		}
	$where.=" )";
	}

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	
	$query = "SELECT glpi_docs.ID as ID FROM glpi_docs LEFT JOIN glpi_dropdown_rubdocs ON glpi_docs.rubrique=glpi_dropdown_rubdocs.ID ";
	
	$query.= " where ";
	if (!empty($where)) $query .= " $where AND ";
	$query .= " glpi_docs.deleted='$deleted'  ORDER BY $sort $order";
	
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table class='tab_cadre' width='750'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_docs.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_docs.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["document"][1]."</a></th>";

			
			// filename
			echo "<th>";
			if ($sort=="glpi_docs.filename") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_docs.filename&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["document"][2]."</a></th>";

			// link
			echo "<th>";
			if ($sort=="glpi_docs.link") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_docs.link&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["document"][33]."</a></th>";
			
			// num
			echo "<th>";
			if ($sort=="glpi_dropdown_rubdocs.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_rubdocs.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["document"][3]."</a></th>";
	
			// mime
			echo "<th>";
			if ($sort=="glpi_docs.mime") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_docs.mime&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["document"][4]."</a></th>";

			// comment		
			echo "<th>";
			if ($sort=="glpi_docs.comment") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_docs.comment&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["document"][6]."</a></th>";


			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new Document;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td>";
				echo "<a href=\"".$cfg_install["root"]."/documents/documents-info-form.php?ID=$ID\"><b>";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</b></a></td>";

				echo "<td align='left'>";
				echo getDocumentLink($ct->fields["filename"]);
				echo "</td>";
				echo "<td>";
				if (!empty($ct->fields["link"]))
					echo "<a href=\"".$ct->fields["link"]."\">".$ct->fields["link"]."</a>";
				else echo "&nbsp;";
				echo "</td>";
				echo "<td>".getDropdownName("glpi_dropdown_rubdocs",$ct->fields["rubrique"])."</td>";
				echo "<td>".$ct->fields["mime"]."</td>";
				echo "<td>".$ct->fields["comment"]."</td>";				
			
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
//			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["document"][23]."</b></div>";
			
		}
	}
}


function showDocumentForm ($target,$ID,$search) {
	// Show Document or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$con = new Document;

	echo "<form name='form' method='post' action=\"$target\" enctype=\"multipart/form-data\"><div align='center'>";
	echo "<table class='tab_cadre' width='700'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["document"][16].":";
		$con->getEmpty();
	} else {
		$con->getfromDB($ID);
		echo $lang["document"][18]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][1].":		</td>";
	echo "<td colspan='2'><input type='text' name='name' value=\"".$con->fields["name"]."\" size='25'></td>";
	echo "</tr>";
	
	if (!empty($ID)){
	echo "<tr class='tab_bg_1'><td>".$lang["document"][22].":		</td>";
	echo "<td colspan='2'>".getDocumentLink($con->fields["filename"])."";
	echo "<input type='hidden' name='current_filename' value='".$con->fields["filename"]."'>";
	echo "</td></tr>";
	}
	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);
	
	echo "<tr class='tab_bg_1'><td>".$lang["document"][2]." (".$max_size."Mo max):	</td>";
	echo "<td colspan='2'><input type='file' name='filename' value=\"".$con->fields["filename"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][33].":		</td>";
	echo "<td colspan='2'><input type='text' name='link' value='".$con->fields["link"]."' size='40'></td>";
	echo "</tr>";

	
	echo "<tr class='tab_bg_1'><td>".$lang["document"][3].":		</td>";
	echo "<td colspan='2'>";
		dropdownValue("glpi_dropdown_rubdocs","rubrique",$con->fields["rubrique"]);
	echo "</td></tr>";
	

		
	echo "<tr class='tab_bg_1'><td>".$lang["document"][4].":		</td>";
	echo "<td colspan='2'><input type='text' name='mime' value='".$con->fields["mime"]."' size='25'></td>";
	echo "</tr>";
	
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
		
		showDeviceDocument($ID,$search);
	}

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
		$filename=preg_replace("/[^a-zA-Z0-9\-_\.]/","",$FILEDESC['name']);
		$force=0;
		// Is it a valid file ?
		$dir=isvalidDoc($filename);
		if (!empty($old_file)&&$dir."/".$filename==$old_file) $force=1;
		
		if (!empty($dir)){
			// Test existance repertoire DOCS
			if (is_dir($phproot.$cfg_install["doc_dir"])){
			// Test existance sous-repertoire type dans DOCS -> sinon création
			if (!is_dir($phproot.$cfg_install["doc_dir"]."/".$dir)){
			$_SESSION["MESSAGE_AFTER_REDIRECT"].= "Création du répertoire ".$phproot.$cfg_install["doc_dir"]."/".$dir."<br>";
			@mkdir($phproot.$cfg_install["doc_dir"]."/".$dir);
			}
			// Copy du fichier uploadé si répertoire existe
			if (is_dir($phproot.$cfg_install["doc_dir"]."/".$dir)){
				// Rename file if exists
				$NB_CHAR_MORE=10;
				$i=0;
				$tmpfilename=$filename;
				while ($i<$NB_CHAR_MORE&&!$force&&is_file($phproot.$cfg_install["doc_dir"]."/".$dir."/".$filename)){
					$filename="_".$filename;
					$i++;
				}
				
				if ($i==$NB_CHAR_MORE){
					$i=0;
					$filename=$tmpfilename;
					while ($i<$NB_CHAR_MORE&&!$force&&is_file($phproot.$cfg_install["doc_dir"]."/".$dir."/".$filename)){
						$filename="-".$filename;
						$i++;
					}
					if ($i==$NB_CHAR_MORE){
						$i=0;
						$filename=$tmpfilename;
						while ($i<$NB_CHAR_MORE&&!$force&&is_file($phproot.$cfg_install["doc_dir"]."/".$dir."/".$filename)){
							$filename="0".$filename;
							$i++;
						}
					}
				}
				if ($force||!is_file($phproot.$cfg_install["doc_dir"]."/".$dir."/".$filename)){
					// Delete old file
					if(!empty($old_file)&& is_file($phproot.$cfg_install["doc_dir"]."/".$old_file)&& !is_dir($phproot.$cfg_install["doc_dir"]."/".$old_file)) {
						if (unlink($phproot.$cfg_install["doc_dir"]."/".$old_file))
						$_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][24].$phproot.$cfg_install["doc_dir"]."/".$old_file."<br>";
						else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][25].$phproot.$cfg_install["doc_dir"]."/".$old_file."<br>";
						}
					
					if (move_uploaded_file($FILEDESC['tmp_name'],$phproot.$cfg_install["doc_dir"]."/".$dir."/".$filename )) {
   						$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][26]."<br>";
						return $dir."/".$filename;
					} else {
	   					$_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][27]."<br>";
					}
				} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][28]."<br>";
			
			} else $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["document"][29].$phproot.$cfg_install["doc_dir"]."/".$dir." ".$lang["document"][30]."<br>";
			
			} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][31].$phproot.$cfg_install["doc_dir"]."<br>";
		
		} else $_SESSION["MESSAGE_AFTER_REDIRECT"].= $lang["document"][32]."<br>";
	
	}	
return "";	
}

function addDocument($input) {

	$con = new Document;

	// dump status
	$null = array_pop($input);
	
	
	if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type']))
		$input['mime']=$_FILES['filename']['type'];
		
	$input['filename']= uploadDocument($_FILES['filename']);		

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($con->fields[$key]) || $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
		}
	}
//	print_r($con);
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
//echo $query;	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
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
	echo "<form method='post' action=\"".$cfg_install["root"]."/documents/documents-info-form.php\">";
	
	echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdownAllItems("item",1,$search,'');
	echo "</div><input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</form>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<form method='get' action=\"".$cfg_install["root"]."/documents/documents-info-form.php?ID=$instID\">";	
	echo "<input type='text' name='search' value=\"".$search."\" size='15'>";
	echo "<input type='hidden' name='ID' value='$instID'>";
	echo "<input type='submit' name='bsearch' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</form>";
	echo "</td></tr>";
	
	echo "</table></div>"    ;
	
}

function addDeviceDocument($conID,$type,$ID,$template=0){

if ($conID>0&&$ID>0){
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
	$q="SELECT * FROM glpi_docs WHERE deleted='N'";
	$result = $db->query($q);
	$nb = $db->numrows($result);
	
	if ($withtemplate<2&&$nb>0){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='ID' value='$ID'><input type='hidden' name='type' value='$device_type'>";
		dropdownDocuments("conID");
		echo "</div></td><td align='center'>";
		echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
		
		echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
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
			$out="<a href=\"".$HTMLRel."documents/send-document.php?file=$filename\" target=\"_blank\">&nbsp;<img style=\"vertical-align:middle; margin-left:3px; margin-right:6px;\" alt='".$fileout."' title='".$fileout."' src=\"".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/$icon\" ></a>";				
			}
	
	}
	
	$out.="<a href=\"".$HTMLRel."documents/send-document.php?file=$filename\" target=\"_blank\"><b>$fileout</b></a>";	
	
	
	return $out;
}

?>
