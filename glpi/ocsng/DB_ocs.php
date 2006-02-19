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

// Original Author of file: Bazile Lebeau
// Purpose of file:
// ----------------------------------------------------------------------
require_once ("_relpos.php");
require_once ($phproot."/glpi/includes.php");

class DBocs extends DBmysql { 

 var $dbhost	= ""; 
 var $dbuser 	= ""; 
 var $dbpassword= ""; 
 var $dbdefault	= ""; 
 
	function DBocs() {
		$db = new DB;
		$query = "select * from glpi_ocs_config";
		$result = $db->query($query);
		$this->dbhost = $db->result($result,0,"ocs_db_host");
		$this->dbuser = $db->result($result,0,"ocs_db_user");
		$this->dbpassword = $db->result($result,0,"ocs_db_passwd");
		$this->dbdefault = $db->result($result,0,"ocs_db_name");
		$this->dbh = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword) or $this->error = 1;
		mysql_select_db($this->dbdefault) or $this->error = 1;
	}
}

?>