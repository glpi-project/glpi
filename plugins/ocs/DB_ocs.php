<?php
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