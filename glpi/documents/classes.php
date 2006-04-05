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

include ("_relpos.php");


class Document extends CommonDBTM {

	function Document () {
		$this->table="glpi_docs";
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
	
}

?>
