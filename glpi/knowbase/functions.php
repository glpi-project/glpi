<?php
/*
 
  ----------------------------------------------------------------------
  GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
 ----------------------------------------------------------------------
 Original Author of file: 
 Purpose of file:
 ----------------------------------------------------------------------
*/


include ("_relpos.php");

// FUNCTIONS knowledgebase



function titleknowbase(){

	
	GLOBAL  $lang,$HTMLRel;

         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/knowbase.png\" alt='".$lang["knowbase"][2]."' title='".$lang["knowbase"][2]."'></td><td><a  class='icon_consol' href=\"knowbase-info-form.php?ID=new\"><b>".$lang["knowbase"][2]."</b></a>";
         echo "</td></tr></table></div>";
	
	
	
		   
}


function showKbItemForm($target,$ID){

	// show kb item form
	
	GLOBAL  $lang,$HTMLRel;

	$ki= new kbitem;	
	
	
	echo "<div id='contenukb'>";

	echo "<form method='post' name='form' action=\"$target\">";
	
	if (empty($ID)) {
		
		$ki->getEmpty();
		
	
	} else {
		$ki->getfromDB($ID);
		
	echo "<input type='hidden' name='ID' value=\"$ID\">\n";
	}		

	
	echo "<p >".$lang["knowbase"][6];
	kbcategoryList($ki->fields["categoryID"],"no");
	echo "</p>";
		
	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][3]."</legend>";
	echo "<span><textarea cols='80' rows='10'  name='question' >".$ki->fields["question"]."</textarea></span>"; 
	echo "</fieldset>";
	
	
	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][4]."</legend>";
	echo "<span><textarea cols='80' rows='10'  name='answer' >".$ki->fields["answer"]."</textarea></span>"; 
	echo "</fieldset>";
	
	
	echo "<br>\n";
	
	//echo "<input type='checkbox' name='faq' value=\"yes\"> Place this Knowledge Base Article into the publicly viewable FAQ as well. <BR>\n";
	echo "<p align='center'>";
	if ($ki->fields["faq"] == "yes") {
			echo "<input class='submit' type='checkbox' name='faq' value='yes' checked>";
		} else {
			echo "<input class='submit' type='checkbox' name='faq' value='yes'>";
		}
	echo $lang["knowbase"][5]."<br><br>\n";
	
	if (empty($ID)) {
	echo "<input type='submit' class='submit' name='add' value=\"".$lang["buttons"][2]."\"> <input type='reset' class='submit' value=\"".$lang["buttons"][16]."\">";
	} else {
	echo "<input type='submit' class='submit' name='update' value=\"".$lang["buttons"][7]."\"> <input type='reset' class='submit' value=\"".$lang["buttons"][16]."\">";
	}
	
	echo "</p>";
	echo "</form></div>";
} 


function kbItemMenu($ID)
{
	global $lang,$HTMLRel, $cfg_install, $cfg_layout, $layout;

	
	$ki= new kbitem;	
	
	$ki->getfromDB($ID);

	
	$isFAQ = $ki->fields["faq"];
	
	echo "<div align='center'><table class='tab_cadre' cellpadding='10' width='500px'><tr><th colspan='3'>";
	
	if($isFAQ == "yes")
	{
		echo $lang["knowbase"][10]."</th></tr>";
	}
	else
	{
		echo $lang["knowbase"][11]."</th></tr>";
	}

	
	echo "<tr>\n";
	if($isFAQ == "yes")
	{
		echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&removefromfaq=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqremove.png\" alt='".$lang["knowbase"][7]."' title='".$lang["knowbase"][7]."'></a></td>\n";
	}
	else
	{
		echo "<td align='center' width=\"33%\"><a  class='icon_nav_move' href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&addtofaq=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqadd.png\" alt='".$lang["knowbase"][5]."' title='".$lang["knowbase"][5]."'></a></td>\n";
	}
	echo "<td align='center' width=\"34%\"><a class='icon_nav_move' href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&modify=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqedit.png\" alt='".$lang["knowbase"][8]."' title='".$lang["knowbase"][8]."'></a></td>\n";
	echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&delete=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqdelete.png\" alt='".$lang["knowbase"][9]."' title='".$lang["knowbase"][9]."'></a>";
	echo "		</td></tr>\n";
	echo "</table></div>\n";
	

	

}


function addKbItem($input){
// Add kb Item, nasty hack until we get PHP4-array-functions
// ok
	$ki = new kbitem;

	// dump status
	$null = array_pop($input);
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if (!isset($ki->fields[$key]) || $ki->fields[$key] != $input[$key]) {
			$ki->fields[$key] = $input[$key];
		}
	}

	$ki->addToDB();



}

function deleteKbItem($input){

	// Delete Reservation Item 
	//ok
	
	$ki = new kbitem;
	$ki->deleteFromDB($input);
}


function updateKbItem($input) {
	
	// Update a kbitem in the database
	//ok 
	
	$ki = new kbitem;
	$ki->getFromDB($input["ID"]);

	

	// Pop off the last  attribute, no longer needed
	$null=array_pop($input);
	
	
	// Get faq and fill with no if unchecked in form
	foreach ($ki->fields as $key => $val) {
		if (eregi("\.*faq\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]="no";
			}
		}
	}
		
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if ($ki->fields[$key] != $input[$key]) {
			$ki->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if (isset($updates))
		$ki->updateInDB($updates);

}



function showKbCategoriesall()
{

	global $lang;	

	echo "<div align='center'>";
	
	echo "<div align='center'><table border='0' class='tab_cadre' >";
	echo "<tr><th align='center' width='700px'>".$lang["knowbase"][0]."</th></tr><tr><td>";	
	
	
	showKbCategories();
	
	echo "</td></tr></table></div>";
}




function showKbCategories($parentID=0)
{
	// show kb catégories
	// ok
	
	global $lang;
	
	$query = "select * from glpi_kbcategories where (parentID = $parentID) order by name asc";

	$db=new DB;
	
	
	
	if ($result=$db->query($query)){
					
		if ($db->numrows($result)>0){
			echo "<ul>";	
			while ($row=$db->fetch_array($result)){
	
	
			$name = $row["name"];
			echo "<li><b>$name</b>\n";
			$ID = $row["ID"];
	  		showKbItemAll($ID);
			showKbCategories($ID);
			}
		echo "</ul>\n";
		}
	
	
	} 
	
}

function showKbItemAll($parentID)
{
	// show kb item in each categories
	//ok 
	
	$query = "select * from glpi_kbitems where (categoryID = $parentID) order by question asc";

	$db=new DB;
	
	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
		echo "<ul>\n";

			while ($row=$db->fetch_array($result)){
			
			
			$ID = $row["ID"];
			showKbItem($ID);
			}
		echo "</ul>\n";
		}
	}
}


function showKbItem($ID)
{
	// show each kb items
	//ok 
	
	global $cfg_install, $cfg_layout, $layout, $lang;

	$query = "select * from glpi_kbitems where (ID=$ID)";

	$db=new DB;
	if ($result=$db->query($query)){
	$data = $db->fetch_array($result);
	$question = $data["question"];
	echo "<li><a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID\">$question</a>\n";
	}
}




function ShowKbItemFull($ID)
{
	
	// show item : question and answer
	// ok
	global $lang;
	
	$ki= new kbitem;	
	
	$ki->getfromDB($ID);
		
	
	$categoryID = $ki->fields["categoryID"];
	$fullcategoryname = kbcategoryname($categoryID);
	
	echo "<div align='center'><table class='tab_cadre' cellpadding='10' width='700px'><tr><th>";
	
	echo "Catégorie : ".$fullcategoryname."</th></tr>";
	echo "<tr class='tab_bg_2'><td><h2>".$lang["knowbase"][3]."</h2>";
	//$question = autop($ki->fields["question"]);
	$question = rembo($ki->fields["question"]);
	//echo clicurl($question);
	echo $question;
	echo "</td></tr>\n";
	echo "<tr  class='tab_bg_2'><td><h2>".$lang["knowbase"][4]."</h2>\n";
	//$answer = autop($ki->fields["answer"]);
	$answer = rembo($ki->fields["answer"]);
	//echo clicurl(bbcode($answer));
	echo $answer;
	echo "</td></tr></table></div><br>";
	
	
}

function kbcategoryList($current=0,$nullroot="yes")
{
	// show select category
	// ok ?
	
	global $lang;
	
	
	echo "<select name='categoryID' size='1'>\n";
	
	if ($nullroot=="yes"){
	echo "<option value='0'>".$lang["knowbase"][12]."</option>\n";
	}
	
	kbcategoryListSelect($current, 0, "\\");
	echo "</select>\n";
}


function kbcategoryListSelect($current, $parentID=0, $categoryname="")
{
	//
	//ok
	$query = "select * from glpi_kbcategories where (parentID = $parentID) order by name desc";

	$db=new DB;
	
	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
	
			
		while ($row=$db->fetch_array($result)){
		
			$ID = $row["ID"];
			$name = $categoryname . $row["name"];
			echo "<option value='$ID'";
			if($current == $ID)
			{
				echo " selected";
			}
			echo ">$name</option>\n";
			$name = $name . "\\";
			kbcategoryListSelect($current, $ID, $name);
		}
	}	}


}

function kbcategoryname($ID, $wholename="")
{
	// show name catégory
	// ok ??
	
	global $lang;
	
	$query = "select * from glpi_kbcategories where (ID = $ID)";
	$db=new DB;
	
	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
		
		$row=$db->fetch_array($result);
		
		$parentID = $row["parentID"];
		if($wholename == "")
		{
			$name = $row["name"];
		} else
		{
			$name = $row["name"] . "\\";
		}
		$name = kbcategoryname($parentID, $name) . $name;
		if($parentID == 0)
		{
			$name = "\\" . $name;
		}
	}
	
	}
return (@$name);
}


//*******************
// Gestion de la  FAQ
//******************




function KbItemaddtofaq($ID)
{
	
	
	
	$db=new DB;
	$db->query("UPDATE glpi_kbitems SET faq='yes' WHERE ID=$ID");
}

function KbItemremovefromfaq($ID)
{
	
	
	$db=new DB;
	
	$db->query("UPDATE glpi_kbitems SET faq='no' WHERE ID=$ID");
	
}
 


function getFAQCategories()
{
	
	
	
	$query = "select * from glpi_kbitems where (faq = 'yes')";

	$db=new DB;
	
	if ($result=$db->query($query)){
			if ($db->numrows($result)>0){
	
	
			$catNumbers = array();
				while ($row=$db->fetch_array($result)){
	
				getFAQParentCategories($row["categoryID"], $catNumbers);
				#	$catNumbers[] = $result["categoryID"];
				}
			}

	return($catNumbers);
}
}	

function getFAQParentCategories($ID, &$catNumbers)
{
	
		
	$query = "select * from glpi_kbcategories where (ID = '$ID')";

	$db=new DB;
	if ($result=$db->query($query)){
			if ($db->numrows($result)>0){
	
			$data = $db->fetch_array($result);
	
		$parentID = $data["parentID"];
		if(!in_array($parentID, $catNumbers))
		{
			getFAQParentCategories($parentID, $catNumbers);
		}
		if(!in_array($ID, $catNumbers))
		{
			$szecatNumbers = sizeof($catNumbers);
			$catNumbers[$szecatNumbers] = $ID;
		}
	}
	}
}


function faqShowCategoriesall()
{

	global $lang;	

	echo "<div align='center'>";
	
	echo "<div align='center'><table border='0' class='tab_cadre' >";
	echo "<tr><th align='center' width='700px'>".$lang["knowbase"][1]."</th></tr><tr><td>";	
	
	
	faqShowCategories();
	
	echo "</td></tr></table></div>";
}

function faqShowCategories($parentID=0)
{
	
		
	$catNumbers = getFAQCategories();
	$query = "select * from glpi_kbcategories where (parentID = $parentID) order by name asc";

	$db=new DB;
	if ($result=$db->query($query)){
			
	
			if ($db->numrows($result)>0){
		
			 
			 
			echo "<ul>\n";

			while ($row=$db->fetch_array($result)){
			
			
			
				$name = $row["name"];
				$ID = $row["ID"];
				if(in_array($ID, $catNumbers))
				{
				
				echo "<li><b>$name</b>\n";
	  				faqShowItems($ID);
					faqShowCategories($ID);
				}
			}
			echo "</ul>\n";
	}
	} 
}

function faqShowItems($parentID)
{
	
	// ok	

	$query = "select * from glpi_kbitems where (categoryID = $parentID) and (faq = 'yes') order by question asc";

	$db=new DB;
	if ($result=$db->query($query)){
	echo "<ul>\n";
	while ($row=$db->fetch_array($result)){
	
		$ID = $row["ID"];
		faqShowItem($ID);
	}
	echo "</ul>\n";
}
}

function faqShowItem($ID)
{
	// ok
	
	global $cfg_install, $cfg_layout, $layout;
	
	$query = "select * from glpi_kbitems where (ID=$ID)";

	$db=new DB;
	if ($result=$db->query($query)){
	$data = $db->fetch_array($result);
	$question = $data["question"];
	echo "<li><a href=\"".$cfg_install["root"]."/helpdesk.php?show=faq&ID=$ID\">$question</a>\n";
	}

}


?>