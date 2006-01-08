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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features

class DBmysql {

	var $dbhost	= ""; 
	var $dbuser = ""; 
	var $dbpassword	= "";
	var $dbdefault	= "";
	var $dbh;
	var $error = 0;

	function DBmysql()
	{  // Constructor
		$this->dbh = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword) or $this->error = 1;
		if ($this->dbh)
		mysql_select_db($this->dbdefault) or $this->error = 1;
		else {
			nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
			echo "<div align='center'><strong>Connection to the mysql server error. Check your configuration.</strong></div>";
			nullFooter("Mysql Error",$_SERVER['PHP_SELF']);
			die();
		}
	}
	function query($query) {
		global $cfg_debug,$DEBUG_SQL_STRING,$SQL_TOTAL_TIMER, $SQL_TOTAL_REQUEST;
		
		if ($cfg_debug["active"]) {
			if ($cfg_debug["sql"]){		
				$SQL_TOTAL_REQUEST++;
				$DEBUG_SQL_STRING.="N&#176; ".$SQL_TOTAL_REQUEST." : <br>".$query;
				
				if ($cfg_debug["profile"]){		
					$TIMER=new Script_Timer;
					$TIMER->Start_Timer();
				}
			}
		}

		$res=mysql_query($query);

		if ($cfg_debug["active"]) {
			if ($cfg_debug["profile"]&&$cfg_debug["sql"]){		
				$TIME=$TIMER->Get_Time();
				$DEBUG_SQL_STRING.="<br><b>Time: </b>".$TIME."s";
				$SQL_TOTAL_TIMER+=$TIME;
			}
			if ($cfg_debug["sql"]){
				$DEBUG_SQL_STRING.="<hr>";
			}
		}
		
		return $res;
	}
	function result($result, $i, $field) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_result($result, $i, $field)):mysql_result($result, $i, $field);
		return $value;
	}
	function numrows($result) {
		return mysql_num_rows($result);
	}
	function fetch_array($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_array($result)):mysql_fetch_array($result);
		return $value;
	}
	function fetch_row($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_row($result)):mysql_fetch_row($result);
		return $value;
	}
	function fetch_assoc($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_assoc($result)):mysql_fetch_assoc($result);
		return $value;
	}
	function data_seek($result,$num){
		return mysql_data_seek ($result,$num);
	}
	function insert_id() {
 		return mysql_insert_id();
 	}
	function num_fields($result) {
		return mysql_num_fields($result);
	}
	function field_name($result,$nb)
	{
		return mysql_field_name($result,$nb);
	}
	function field_flags($result,$field)
	{
		return mysql_field_flags($result,$field);
	}
	function list_tables() {
		return mysql_list_tables($this->dbdefault);
	}
	function table_name($result,$nb) {
		return mysql_tablename($result,$nb);
	}
	function list_fields($table) {
		return mysql_list_fields($this->dbdefault,$table);
	}
	function affected_rows() {
		return mysql_affected_rows($this->dbh);
	}
	function errno()
	{
		return mysql_errno();
	}

	function error()
	{
		return mysql_error();
	}
	function close()
	{
		return mysql_close($this->dbh);
	}
	
}





class CommonItem{
	var $obj = NULL;	
	var $device_type=0;
	var $id_type=0;
	
	function getfromDB ($device_type,$id_device) {
		$this->id_device=$id_device;
		$this->device_type=$device_type;
		// Make new database object and fill variables

			switch ($device_type){
			case COMPUTER_TYPE :
				$this->obj=new Computer;
				break;
			case NETWORKING_TYPE :
				$this->obj=new Netdevice;
				break;
			case PRINTER_TYPE :
				$this->obj=new Printer;
				break;
			case MONITOR_TYPE : 
				$this->obj= new Monitor;	
				break;
			case PERIPHERAL_TYPE : 
				$this->obj= new Peripheral;	
				break;				
			case SOFTWARE_TYPE : 
				$this->obj= new Software;	
				break;				
			case CONTRACT_TYPE : 
				$this->obj= new Contract;	
				break;				
			case ENTERPRISE_TYPE : 
				$this->obj= new Enterprise;	
				break;	
			case KNOWBASE_TYPE : 
				$this->obj= new kbitem;	
				break;					
			case CARTRIDGE_TYPE : 
				$this->obj= new CartridgeType;	
				break;					
			case CONSUMABLE_TYPE : 
				$this->obj= new ConsumableType;	
				break;					
			case CARTRIDGE_ITEM_TYPE : 
				$this->obj= new Cartridge;	
				break;					
			case CONSUMABLE_ITEM_TYPE : 
				$this->obj= new Consumable;	
				break;					
			case LICENSE_TYPE : 
				$this->obj= new License;	
				break;					
			case DOCUMENT_TYPE : 
				$this->obj= new Document;	
				break;					
			}

			if ($this->obj!=NULL){
				// Do not load devices
					return $this->obj->getfromDB($id_device);
			}
			else return false;
			
	}
	function setType ($device_type){
		$this->device_type=$device_type;
	}

	function getType (){
		global $lang;
		
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $lang["help"][30];
				break;
			case COMPUTER_TYPE :
				return $lang["computers"][44];
				break;
			case NETWORKING_TYPE :
				return $lang["networking"][12];
				break;
			case PRINTER_TYPE :
				return $lang["printers"][4];
				break;
			case MONITOR_TYPE : 
				return $lang["monitors"][4];
				break;
			case PERIPHERAL_TYPE : 
				return $lang["peripherals"][4];
				break;				
			case SOFTWARE_TYPE : 
				return $lang["software"][10];
				break;				
			case CONTRACT_TYPE : 
				return $lang["financial"][1];
				break;				
			case ENTERPRISE_TYPE : 
				return $lang["financial"][26];
				break;
			case KNOWBASE_TYPE : 
				return $lang["knowbase"][0];
				break;	
			case CARTRIDGE_TYPE : 
				return $lang["cartridges"][16];
				break;
			case CONSUMABLE_TYPE : 
				return $lang["consumables"][16];
				break;					
			case LICENSE_TYPE : 
				return $lang["software"][11];
				break;					
			case CARTRIDGE_ITEM_TYPE : 
				return $lang["cartridges"][0];
				break;
			case CONSUMABLE_ITEM_TYPE : 
				return $lang["consumables"][0];
				break;					
			case DOCUMENT_TYPE : 
				return $lang["document"][0];
				break;					
			}
	
	}
	function getName(){
		global $lang;
		
		if ($this->device_type==0) return "";
		
		if ($this->device_type==KNOWBASE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["question"])&&$this->obj->fields["question"]!="")
			return $this->obj->fields["question"];
		else if ($this->device_type==LICENSE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["serial"])&&$this->obj->fields["serial"]!="")
			return $this->obj->fields["serial"];
		else if (($this->device_type==CARTRIDGE_TYPE||$this->device_type==CONSUMABLE_TYPE)&&$this->obj!=NULL&&$this->obj->fields["name"]!=""){
			$name=$this->obj->fields["name"];
			if (isset($this->obj->fields["ref"])&&!empty($this->obj->fields["ref"]))			
				$name.=" - ".$this->obj->fields["ref"];
			return $name;
			}
		else if ($this->obj!=NULL&&isset($this->obj->fields["name"])&&$this->obj->fields["name"]!="")
			return $this->obj->fields["name"];
		else 
			return "N/A";
	}
	function getNameID(){
		if ($this->device_type==0)
		return $this->getName();
		else return $this->getName()." (".$this->id_device.")";
	}
	
	function getLink(){
	
		global $cfg_install;
	
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $this->getName();
				break;
			case COMPUTER_TYPE :
				return "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case NETWORKING_TYPE :
				return "<a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case PRINTER_TYPE :
				return "<a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case MONITOR_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case PERIPHERAL_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/peripherals/peripherals-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;				
			case SOFTWARE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;				
			case CONTRACT_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;				
			case ENTERPRISE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case KNOWBASE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;						
			case CARTRIDGE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/cartridges/cartridges-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;						
			case CONSUMABLE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/consumables/consumables-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;						
			case LICENSE_TYPE : 
				return $this->getName();
				break;						
			case CARTRIDGE_ITEM_TYPE : 
				return $this->getName();
				break;						
			case CONSUMABLE_ITEM_TYPE : 
				return $this->getName();
				break;						
			case DOCUMENT_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/documents/documents-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;						
			
			}

	
	}
	
}




?>
