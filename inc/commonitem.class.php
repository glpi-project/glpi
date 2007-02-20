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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


/**
 *  Common Item of GLPI : Global simple interface to items - abstraction usage
 */
class CommonItem{
	//! Object Type depending of the device_type
	var $obj = NULL;	
	//! Device Type ID of the object
	var $device_type=0;
	//! Device ID of the object
	var $id_device=0;


	/**
	 * Get an Object / General Function
	 *
	 * Create a new Object depending of $device_type and Get the item with the ID $id_device
	 *
	 * @param $device_type Device Type ID of the object
	 * @param $id_device Device ID of the object
	 *
	 * @return boolean : object founded and loaded
	 */
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
			case PHONE_TYPE : 
				$this->obj= new Phone;	
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
			case CONTACT_TYPE : 
				$this->obj= new Contact;	
				break;	
			case KNOWBASE_TYPE : 
				$this->obj= new kbitem;	
				break;					
			case USER_TYPE : 
				$this->obj= new User;	
				break;					
			case TRACKING_TYPE : 
				$this->obj= new Job;	
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
			case GROUP_TYPE : 
				$this->obj= new Group;	
				break;			
			case AUTH_MAIL_TYPE:
				$this->obj = new AuthMail;
				break;
			case AUTH_LDAP_TYPE:
				$this->obj = new AuthLDAP;
				break;
			case OCSNG_TYPE:
				$this->obj = new Ocsng;
				break;					
		}

		if ($this->obj!=NULL){
			// Do not load devices
			return $this->obj->getfromDB($id_device);
		}
		else return false;

	}

	/**
	 * Set the device type
	 *
	 * @param $device_type Device Type ID of the object
	 *
	 */
	function setType ($device_type){
		$this->device_type=$device_type;
	}

	/**
	 * Get The Type Name of the Object
	 *
	 * @return String: name of the object type in the current language
	 */
	function getType (){
		global $LANG;

		switch ($this->device_type){
			case GENERAL_TYPE :
				return $LANG["help"][30];
				break;
			case COMPUTER_TYPE :
				return $LANG["computers"][44];
				break;
			case NETWORKING_TYPE :
				return $LANG["networking"][12];
				break;
			case PRINTER_TYPE :
				return $LANG["printers"][4];
				break;
			case MONITOR_TYPE : 
				return $LANG["monitors"][4];
				break;
			case PERIPHERAL_TYPE : 
				return $LANG["peripherals"][4];
				break;				
			case PHONE_TYPE : 
				return $LANG["phones"][4];
				break;				
			case SOFTWARE_TYPE : 
				return $LANG["software"][10];
				break;				
			case CONTRACT_TYPE : 
				return $LANG["financial"][1];
				break;				
			case ENTERPRISE_TYPE : 
				return $LANG["financial"][26];
				break;
			case CONTACT_TYPE : 
				return $LANG["common"][18];
				break;
			case KNOWBASE_TYPE : 
				return $LANG["knowbase"][0];
				break;	
			case USER_TYPE : 
				return $LANG["setup"][57];
				break;	
			case TRACKING_TYPE : 
				return $LANG["job"][38];
				break;	
			case CARTRIDGE_TYPE : 
				return $LANG["cartridges"][16];
				break;
			case CONSUMABLE_TYPE : 
				return $LANG["consumables"][16];
				break;					
			case LICENSE_TYPE : 
				return $LANG["software"][11];
				break;					
			case CARTRIDGE_ITEM_TYPE : 
				return $LANG["cartridges"][0];
				break;
			case CONSUMABLE_ITEM_TYPE : 
				return $LANG["consumables"][0];
				break;					
			case DOCUMENT_TYPE : 
				return $LANG["document"][0];
				break;					
			case GROUP_TYPE : 
				return $LANG["common"][35];
				break;					
		}

	}

	function getField($field){
		if ($this->obj==NULL) return false;
		if ($this->device_type==0) {
			return false;
		} else {
			if (isset($this->obj->fields[$field])) {
				return $this->obj->fields[$field];
			} else {
				return false;
			}
		}
	}
	/**
	 * Get The Name of the Object
	 *
	 * @return String: name of the object in the current language
	 */
	function getName($with_comments=0){
		global $LANG;


		if ($this->device_type==0) {
			return "";
		}

		$toadd="";
		if ($with_comments) $toadd="&nbsp;".$this->getComments();

		if ($this->device_type==KNOWBASE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["question"])&&$this->obj->fields["question"]!="")
			return $this->obj->fields["question"];
		else if ($this->device_type==LICENSE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["serial"])&&$this->obj->fields["serial"]!="")
			return $this->obj->fields["serial"];
		else if (($this->device_type==CARTRIDGE_TYPE||$this->device_type==CONSUMABLE_TYPE)&&$this->obj!=NULL&&$this->obj->fields["name"]!=""){
			$name=$this->obj->fields["name"];
			if (isset($this->obj->fields["ref"])&&!empty($this->obj->fields["ref"]))			
				$name.=" - ".$this->obj->fields["ref"];
			return $name.$toadd;
		}
		else if ($this->obj!=NULL&&isset($this->obj->fields["name"])&&$this->obj->fields["name"]!="")
			return $this->obj->fields["name"].$toadd;
		else 
			return "N/A";
	}
	/**
	 * Get The Name of the Object with the ID if the config is set
	 *
	 * @return String: name of the object in the current language
	 */
	function getNameID($with_comments=0){
		global $CFG_GLPI;
		$toadd="";
		if ($with_comments) $toadd="&nbsp;".$this->getComments();
		if ($CFG_GLPI["view_ID"]){
			if ($this->device_type==0)
				return $this->getName().$toadd;
			else return $this->getName()." (".$this->id_device.")".$toadd;
		} else return $this->getName().$toadd;
	}
	/**
	 * Get The link to the Object
	 * @param $with_comments Display comments
	 * @return String: link to the object type in the current language
	 */
	function getLink($with_comments=0){

		global $CFG_GLPI,$INFOFORM_PAGES;
		$ID="";
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $this->getName($with_comments);
				break;
			case COMPUTER_TYPE :
			case NETWORKING_TYPE :
			case PRINTER_TYPE :
			case MONITOR_TYPE : 
			case PERIPHERAL_TYPE : 
			case PHONE_TYPE : 
			case SOFTWARE_TYPE : 
			case CONTRACT_TYPE : 
			case ENTERPRISE_TYPE : 
			case CONTACT_TYPE : 
			case KNOWBASE_TYPE : 
			case USER_TYPE : 
			case TRACKING_TYPE : 			
			case CARTRIDGE_TYPE : 
			case CONSUMABLE_TYPE : 
			case DOCUMENT_TYPE : 
			case GROUP_TYPE : 
				return "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->device_type]."?ID=".$this->id_device."\">".$this->getNameID($with_comments)."</a>";
				break;
			case LICENSE_TYPE : 
				return $this->getName($with_comments);
				break;						
			case CARTRIDGE_ITEM_TYPE : 
				return $this->getName($with_comments);
				break;						
			case CONSUMABLE_ITEM_TYPE : 
				return $this->getName($with_comments);
				break;						
		}


	}
	function getComments(){
		global $LANG,$CFG_GLPI;
		$comment="";

		if (!($comment = $CFG_GLPI["cache"]->get("comments_".$this->id_device."_".$_SESSION["glpilanguage"],"GLPI_".$this->device_type))) {
			if ($tmp=$this->getField('serial')){
				$comment.=$LANG["common"][19].": ".$tmp."<br>";
			}
			
			if ($tmp=$this->getField('otherserial')){
				$comment.=$LANG["common"][20].": ".$tmp."<br>";
			}
			if ($tmp=$this->getField('location')){
				$tmp=getDropdownName("glpi_dropdown_locations",$tmp);
				if (!empty($tmp)&&$tmp!='&nbsp;'){
					$comment.=$LANG["common"][15].": ".$tmp."<br>";
				}
			}
			if ($tmp=$this->getField('FK_users')){
				$tmp=getDropdownName("glpi_users",$tmp);
				if (!empty($tmp)&&$tmp!='&nbsp;'){
					$comment.=$LANG["common"][34].": ".$tmp."<br>";
				}
			}
			if ($tmp=$this->getField('FK_groups')){
				$tmp=getDropdownName("glpi_groups",$tmp);
				if (!empty($tmp)&&$tmp!='&nbsp;'){
					$comment.=$LANG["common"][35].": ".$tmp."<br>";
				}
			}
			if ($tmp=$this->getField('tech_num')){
				$tmp=getDropdownName("glpi_users",$tmp);
				if (!empty($tmp)&&$tmp!='&nbsp;'){
					$comment.=$LANG["common"][10].": ".$tmp."<br>";
				}
			}
			if ($tmp=$this->getField('contact')){
				$comment.=$LANG["common"][18].": ".$tmp."<br>";
			}
			if ($tmp=$this->getField('contact_num')){
				$comment.=$LANG["common"][21].": ".$tmp."<br>";
			}
			$CFG_GLPI["cache"]->save($comment,"comments_".$this->id_device."_".$_SESSION["glpilanguage"],"GLPI_".$this->device_type);
		}

		if (!empty($comment)){
			$rand=mt_rand();
			$comments_display=" onmouseout=\"cleanhide('comments_commonitem$rand')\" onmouseover=\"cleandisplay('comments_commonitem$rand')\" ";
			$comments_display2="<span class='over_link' id='comments_commonitem$rand'>".nl2br($comment)."</span>";
	
			$comment="<img alt='".$LANG["common"][25]."' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' $comments_display> ";
			$comment.=$comments_display2;
		}

		
		return $comment;
	}
}




?>
