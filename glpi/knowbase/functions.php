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
 Original Author of file: Julien Dombre
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
	
	echo "Here is where you can add an article to the knowledge base.";
	echo "<hr noshade><BR>";

	echo "<form method='post' name='form' action=\"$target\">";
	
	if (empty($ID)) {
		
		$ki->getEmpty();
		echo "on vide";
	
	} else {
		$ki->getfromDB($ID);
		echo "on récup";
	echo "<input type='hidden' name='ID' value=\"$ID\">\n";
	}		

	
	echo "Select the category in which this article should be placed: ";
	
	
	
	kbcategoryList($ki->fields["categoryID"]);
	
	echo "<br><br> Enter the question here.  Please be as detailed as possible with the question, but don't repeat information that can be inferred by the category.<br>";
	
	echo "<textarea cols='80' rows='10' wrap='soft' name='question' value=\"".$ki->fields["question"]."\">".$ki->fields["question"]."</textarea><br>"; 
	
	echo "<textarea cols='80' rows='10' wrap='soft' name='answer' value=\"".$ki->fields["answer"]."\">".$ki->fields["answer"]."</textarea>"; 
	echo "<br>\n";

	//echo "<input type='checkbox' name='faq' value=\"yes\"> Place this Knowledge Base Article into the publicly viewable FAQ as well. <BR>\n";
	
	if ($ki->fields["faq"] == "yes") {
			echo "<input type='checkbox' name='faq' value='yes' checked>";
		} else {
			echo "<input type='checkbox' name='faq' value='yes''>";
		}
	echo "Place this Knowledge Base Article into the publicly viewable FAQ as well. <BR>\n";
	
	if (empty($ID)) {
	echo "<input type='submit' name='add' value=\"Valider\"> <input type='reset' value=\"Reset\"></form>";
	} else {
	echo "<input type='submit' name='update' value=\"Actualiser\"> <input type='reset' value=\"Reset\"></form>";
	}
} 


function kbItemMenu($ID)
{
	global $lang,$HTMLRel, $cfg_install, $cfg_layout, $layout;

	
	$ki= new kbitem;	
	
	$ki->getfromDB($ID);

	echo "<br><hr>";
	
	$isFAQ = $ki->fields["faq"];
	
	if($isFAQ == "yes")
	{
		echo "This Knowledge Base entry is part of the FAQ.";
	}
	else
	{
		echo "This Knowledge Base entry is not part of the FAQ.";
	}

	echo "<br><br>\n";
	echo "<table border=0 width=100%>\n";
	echo "<tr>\n";
	if($isFAQ == "yes")
	{
		echo "<td align=left width=\"33%\"><h4><a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&removefromfaq=yes\">Remove Article from the FAQ</a></h4></td>\n";
	}
	else
	{
		echo "<td align=left width=\"33%\"><h4><a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&addtofaq=yes\">Add Article to the FAQ</a></h4></td>\n";
	}
	echo "<td align=left width=\"34%\"><h4><a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&modify=yes\">Modify Article</a></h4></td>\n";
	echo "<td align=left width=\"33%\"><h4><a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID&delete=yes\">Delete Article</A>";
	echo "		</h4></td></tr>\n";
	echo "</table>\n";
	echo "<br>";

	

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

	

	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	
		
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




function showKbCategories($parentID=0)
{
	// show kb catégories
	// ok
	
	
	
	$query = "select * from glpi_kbcategories where (parentID = $parentID) order by name asc";

	$db=new DB;
		
	if ($result=$db->query($query)){
			if ($db->numrows($result)>0){
			echo "<ul>";	
			while ($row=$db->fetch_array($result)){
	
	
			$name = $row["name"];
			echo "<li><B>$name</B>\n";
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
	
	$query = "select * from glpi_kbitems where (ID=$ID)";

	$db=new DB;
	
	if ($result=$db->query($query)){
	$data = $db->fetch_array($result);
	
	$question = $data["question"];
	$categoryID = $data["categoryID"];
	$fullcategoryname = kbcategoryname($categoryID);
	echo "Catégorie : ".$fullcategoryname;
	echo "<H2>Question :</H2>$question\n";
	echo "<HR>\n";
	echo "<H2>Answer:</H2>\n";
	$answer = nl2br($data["answer"]);
	echo "$answer";
	}
	
}

function kbcategoryList($current=0)
{
	// show select category
	// ok ?
	
	echo "<select name='categoryID' size='1'>\n";
	//echo "<option value='0'>Main</option>\n";
	kbcategoryListSelect($current, 0, "Main\\");
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
			$name = "Main\\" . $name;
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