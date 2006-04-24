<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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

 


class Document extends CommonDBTM {

	function Document () {
		$this->table="glpi_docs";
		$this->type=DOCUMENT_TYPE;
	}
	
	
	function cleanDBonPurge($ID) {
		global $db,$cfg_glpi,$phproot,$lang;
	
		$query3 = "DELETE FROM glpi_doc_device WHERE (FK_doc = '$ID')";
		$result3 = $db->query($query3);
				
		// UNLINK DU FICHIER
		if (!empty($this->fields["filename"]))
		if(is_file($cfg_glpi["doc_dir"]."/".$this->fields["filename"])&& !is_dir($cfg_glpi["doc_dir"]."/".$this->fields["filename"])) {
			if (unlink($cfg_glpi["doc_dir"]."/".$this->fields["filename"]))
				$_SESSION["MESSAGE_AFTER_REDIRECT"]= $lang["document"][24].$cfg_glpi["doc_dir"]."/".$this->fields["filename"]."<br>";
			else $_SESSION["MESSAGE_AFTER_REDIRECT"]= $lang["document"][25].$cfg_glpi["doc_dir"]."/".$this->fields["filename"]."<br>";
		}
	}

	function defineOnglets($withtemplate){
		global $lang;
		$ong[5]=$lang["title"][26];
		if (haveRight("notes","r"))
			$ong[10]=$lang["title"][37];
		return $ong;
	}


	function add($input,$only_if_upload_succeed=0) {
		// dump status
		unset($input['add']);
	
		if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type']))
			$input['mime']=$_FILES['filename']['type'];

		if (isset($input["upload_file"])&&!empty($input["upload_file"])){
			$input['filename']=moveUploadedDocument($input["upload_file"]);
		} else 	$input['filename']= uploadDocument($_FILES['filename']);
	
		unset($input["upload_file"]);

		// fill array for update
		foreach ($input as $key => $val) {
			if ($key[0]!='_'&&(empty($this->fields[$key]) || $this->fields[$key] != $input[$key])) {
				$this->fields[$key] = $input[$key];
			}
		}
		if (!$only_if_upload_succeed||!empty($input['filename'])){
			$newID= $this->addToDB();
			do_hook_function("item_add",array("type"=>DOCUMENT_TYPE, "ID" => $newID));
			return $newID;
		}
		else return false;
	}

	function update($input) {
		$this->getFromDB($input["ID"]);
	
		if (isset($_FILES['filename']['type'])&&!empty($_FILES['filename']['type']))
			$input['mime']=$_FILES['filename']['type'];
		
		if (isset($input["upload_file"])&&!empty($input["upload_file"])){
			$input['filename']=moveUploadedDocument($input["upload_file"],$input['current_filename']);
		} else 	$input['filename']= uploadDocument($_FILES['filename'],$input['current_filename']);
	
		if (empty($input['filename'])) unset($input['filename']);
		unset($input['current_filename']);	


		// Fill the update-array with changes
		$x=0;
		foreach ($input as $key => $val) {
			if (array_key_exists($key,$this->fields) && $this->fields[$key] != $input[$key]) {
				$this->fields[$key] = $input[$key];
				$updates[$x] = $key;
				$x++;
			}
		}
		if(!empty($updates)) {
			$this->updateInDB($updates);
		}
		do_hook_function("item_update",array("type"=>DOCUMENT_TYPE, "ID" => $input["ID"]));
	}
}

?>
