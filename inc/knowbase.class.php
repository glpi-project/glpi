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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// CLASSE knowledgebase

class kbitem extends CommonDBTM {

	function kbitem () {
		$this->table="glpi_kbitems";
		$this->type=KNOWBASE_TYPE;
	}

	function prepareInputForAdd($input) {

		global $LANG;
		// set new date.
		$input["date"] = $_SESSION["glpi_currenttime"];
		// set author

		// set title for question if empty
		if(empty($input["question"])) $input["question"]=$LANG["common"][30];

		if (haveRight("faq","w")&&!haveRight("knowbase","w")) $input["faq"]=1;
		if (!haveRight("faq","w")&&haveRight("knowbase","w")) $input["faq"]=0;

		return $input;
	}

	function pre_updateInDB($input,$updates) {
		$this->fields["date_mod"]=$_SESSION["glpi_currenttime"];
		$updates[]="date_mod";
		return array($input,$updates);
	}

	function prepareInputForUpdate($input) {
		global $LANG;
		// set title for question if empty
		if(empty($input["question"])) $input["question"]=$LANG["common"][30];

		return $input;
	}


	/**
	* Print out an HTML "<form>" for knowbase item
	*
	* 
	* 
	*
	* @param $target 
	* @param $ID
	* @return nothing (display the form)
	**/
	function showForm($target,$ID){
	
		// show kb item form
	
		global  $LANG,$CFG_GLPI;
		if (!haveRight("knowbase","w")&&!haveRight("faq","w")) return false;
	
		$spotted=false;
		if (empty($ID)) {
	
			if ($this->getEmpty()) $spotted=true;
	
	
		} else {
			if ($this->getFromDB($ID)) $spotted=true;
			if ($this->fields["faq"]&&!haveRight("faq","w")) $spotted=false;
			if (!$this->fields["faq"]&&!haveRight("knowbase","w")) $spotted=false;
	
		}	

		if($spotted) {
			
			echo "<div id='contenukb'>";

			echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/tiny_mce/tiny_mce.js\"></script>";
			echo "<script language=\"javascript\" type=\"text/javascript\">";
			echo "tinyMCE.init({	
				language : \"".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][3]."\",  
				mode : \"exact\",  
				elements: \"answer\", 
				plugins : \"table\", 
				theme : \"advanced\",  
				entity_encoding : \"numeric\",
				theme_advanced_toolbar_location : \"top\", 
				theme_advanced_toolbar_align : \"left\",   
				theme_advanced_buttons1 : \"bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent\", 
				theme_advanced_buttons2 : \"forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator\",  
				theme_advanced_buttons3 : \"\"});";
			echo "</script>";

			echo "<form method='post' id='form_kb' name='form_kb' action=\"$target\">";
		
		
			if (!empty($ID)) {
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			}
		
		
			echo "<fieldset>";
			echo "<legend>".$LANG["knowbase"][13]."</legend>";
			echo "<div class='center'>".$LANG["knowbase"][6];
			dropdownValue("glpi_dropdown_kbcategories","categoryID",$this->fields["categoryID"]);
			echo "</div></fieldset>";
		
			echo "<fieldset>";
			echo "<legend>".$LANG["knowbase"][14]."</legend>";
			echo "<div class='center'><textarea cols='80' rows='2'  name='question' >".$this->fields["question"]."</textarea></div>"; 
			echo "</fieldset>";
		
		
			echo "<fieldset>";
			echo "<legend>".$LANG["knowbase"][15]."</legend><div class='center'>";
			echo "<textarea cols='80' rows='30' id='answer'  name='answer' >".$this->fields["answer"]."</textarea></div>"; 
		
			echo "</fieldset>";
		
		
			echo "<br>\n";
		
			if (!empty($ID)) {
				echo "<fieldset>";
				echo "<div class='baskb'>";
				if ($this->fields["author"]){
					echo $LANG["common"][37]." : ".getUserName($this->fields["author"],"1")."      ";
				}
				
				
		
				echo "<span class='baskb_right'  >";
				if ($this->fields["date_mod"]){
					echo $LANG["common"][26]." : ".convDateTime($this->fields["date_mod"])."     ";
				}
				echo "</span><br />";
				
				if ($this->fields["date"]){
					echo $LANG["common"][27]." : ". convDateTime($this->fields["date"]);
				}
				
				echo "<span class='baskb_right'>";
				echo $LANG["knowbase"][26]." : ".$this->fields["view"]."</span></div>";
				
		
				echo "</fieldset>";
			}
			echo "<p class='center'>";
		
			if (haveRight("faq","w")&&haveRight("knowbase","w")){
				
				echo $LANG["knowbase"][5].": ";
				dropdownYesNo('faq',$this->fields["faq"]);
				echo "<br><br>\n";
			}
		
			if (empty($ID)) {
				echo "<input type='hidden' name='author' value=\"".$_SESSION['glpiID']."\">\n";
				echo "<input type='submit' class='submit' name='add' value=\"".$LANG["buttons"][2]."\"> <input type='reset' class='submit' value=\"".$LANG["buttons"][16]."\">";
			} else {
				echo "<input type='submit' class='submit' name='update' value=\"".$LANG["buttons"][7]."\"> <input type='reset' class='submit' value=\"".$LANG["buttons"][16]."\">";
			}
		
			echo "</p>";
			echo "</form>";
		
			echo "</div>";
			return true;
		} else return false;
	} 




}

?>
