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

include ("_relpos.php");

// FUNCTIONS knowledgebase


function searchFormKnowbase($target,$contains){
global $lang;
	echo "<form method=post action=\"$target\">";
	echo "<div align='center'><table border='0' width='500px' class='tab_cadre'>";

	echo "<tr ><th colspan='4'><b>".$lang["search"][0].":</b></th></tr>";
    echo "<tr class='tab_bg_2' align='center'><td><input type='text' size='30' name=\"contains\" value=\"". $contains ."\" ></td><td><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit' ></td>";
	// From helpdesk or central
    if (ereg("\?",$target)) $separator="&";
    else $separator="?";
    
    echo "<td><a href=\"".$target.$separator."toshow=all\">".$lang["knowbase"][21]."</a> </td>";
    echo "<td ><a href=\"".$target.$separator."tohide=all\">".$lang["knowbase"][22]."</a>";
    echo "</td></tr>";
	
	echo "</table></div></form>";
	
	
}


function titleknowbase(){

	
	GLOBAL  $lang,$HTMLRel;

         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/knowbase.png\" alt='".$lang["knowbase"][2]."' title='".$lang["knowbase"][2]."'></td><td>";
         //if (countElementsInTable("glpi_dropdown_kbcategories"))
         echo "<a  class='icon_consol' href=\"knowbase-info-form.php?ID=new\"><b>".$lang["knowbase"][2]."</b></a>";
         //else echo "<span class='icon_consol'>".$lang["knowbase"][2]."</span>";
         echo "</td></tr>";
		echo "</table></div>";
	
}


function showKbItemForm($target,$ID){

	// show kb item form
	
	GLOBAL  $lang,$HTMLRel;

	$ki= new kbitem;	
	
	
	echo "<div id='contenukb'>";
	echo "<script type='text/javascript' language='javascript' src='".$HTMLRel."toolbar.js'></script>";
	echo "<form method='post' name='form_kb' action=\"$target\">";
	
	if (empty($ID)) {
		
		$ki->getEmpty();
		
	
	} else {
		$ki->getfromDB($ID);
	
		
	echo "<input type='hidden' name='ID' value=\"$ID\">\n";
	}		

	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][13]."</legend>";
	echo "<p style='text-align:center'>".$lang["knowbase"][6];
	kbcategoryList($ki->fields["categoryID"],"yes");
	echo "</p>";
	echo "</fieldset>";
		
	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][3]."</legend>";
	echo "<div align='center'><textarea cols='80' rows='2'  name='question' >".$ki->fields["question"]."</textarea></div>"; 
	echo "</fieldset>";
	
	
	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][4]."</legend><div align='center'>";
	/*echo "
		<script type='text/javascript' language='javascript'>
		drawToolbar('form_kb.answer');
		</script>
		";	
	*/
	
	echo "<p class='toolbar'>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[b]', '[/b]')\"><img src=\"".$HTMLRel."pics/gras.png\" alt='".$lang["toolbar"][1]."' title='".$lang["toolbar"][1]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[i]', '[/i]')\"><img src=\"".$HTMLRel."pics/italique.png\" alt='".$lang["toolbar"][2]."' title='".$lang["toolbar"][2]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[u]', '[/u]')\"><img src=\"".$HTMLRel."pics/souligne.png\" alt='".$lang["toolbar"][3]."' title='".$lang["toolbar"][3]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[s]', '[/s]')\"><img src=\"".$HTMLRel."pics/barre.png\" alt='".$lang["toolbar"][4]."' title='".$lang["toolbar"][4]."' style='vertical-align:middle;'</img></a>";		
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[g]', '[/g]')\"><img src=\"".$HTMLRel."pics/grand.png\" alt='".$lang["toolbar"][7]."' title='".$lang["toolbar"][7]."' style='vertical-align:middle;'</img></a>";	
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[c]', '[/c]')\"><img src=\"".$HTMLRel."pics/centre.png\" alt='".$lang["toolbar"][5]."' title='".$lang["toolbar"][5]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[code]', '[/code]')\"><img src=\"".$HTMLRel."pics/code.png\" alt='".$lang["toolbar"][6]."' title='".$lang["toolbar"][6]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[color=red]', '[/color]')\"><img src=\"".$HTMLRel."pics/rouge.png\" alt='".$lang["toolbar"][8]."' title='".$lang["toolbar"][8]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[color=blue]', '[/color]')\"><img src=\"".$HTMLRel."pics/bleu.png\" alt='".$lang["toolbar"][9]."' title='".$lang["toolbar"][9]."' style='vertical-align:middle;'</img></a>";
	echo "<a href=\"javascript:raccourciTypo(document.form_kb.answer , '[color=yellow]', '[/color]')\"><img src=\"".$HTMLRel."pics/jaune.png\" alt='".$lang["toolbar"][10]."' title='".$lang["toolbar"][10]."' style='vertical-align:middle;'</img></a>";
	echo "</p>";
	echo "<textarea cols='80' rows='15'  name='answer' >".$ki->fields["answer"]."</textarea></div>"; 
	echo "</fieldset>";
	
	
	echo "<br>\n";
	
	
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

	
	echo "<div align='center'><table border='0' class='tab_cadre' >";
	echo "<tr><th align='center' width='700px'>".$lang["knowbase"][0]."</th></tr><tr><td>";	
	
	showKbCategories();
	
	echo "</td></tr></table></div>";
}




function showKbCategories($parentID=0)
{
	// show kb catégories
	// ok
	
	global $lang,$HTMLRel;
	
	$query = "select * from glpi_dropdown_kbcategories where (parentID = $parentID) order by name asc";

	$db=new DB;
	
	if ($parentID==0) showKbItemAll($parentID);
	
	/// Show category
	if ($result=$db->query($query)){
					
		if ($db->numrows($result)>0){
			echo "<ul>";	
			while ($row=$db->fetch_array($result)){
	
	
			$name = $row["name"];
			$ID = $row["ID"];

			echo "<li><b>";
			if (!isset($_SESSION["kb_show"][$ID])) $_SESSION["kb_show"][$ID]='Y';
			if ($_SESSION["kb_show"][$ID]=='Y')
			echo "<a href=\"".$_SERVER["PHP_SELF"]."?tohide=$ID\"><img src='".$HTMLRel."pics/puce-down.gif' alt='down'></a>";
			else 
			echo "<a href=\"".$_SERVER["PHP_SELF"]."?toshow=$ID\"><img src='".$HTMLRel."pics/puce.gif' alt='up'></a>";
			
			echo " $name</b>\n";
			if ($_SESSION["kb_show"][$ID]=='Y'){
	  	  showKbItemAll($ID);
			showKbCategories($ID);
			}
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
	echo "<li><a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=$ID\">&nbsp;".$question."&nbsp;</a>\n";
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
	$fullcategoryname = getTreeValueName("glpi_dropdown_kbcategories",$categoryID);
	
	echo "<div align='center'><table class='tab_cadre' cellpadding='10' width='700px'><tr><th>";
	
	echo "Catégorie : ".$fullcategoryname."</th></tr>";
	echo "<tr class='tab_bg_3'><td><h2>".$lang["knowbase"][3]."</h2>";
	//$question = autop($ki->fields["question"]);
	$question = rembo($ki->fields["question"]);
	//echo clicurl($question);
	echo $question;
	echo "</td></tr>\n";
	echo "<tr  class='tab_bg_3'><td><h2>".$lang["knowbase"][4]."</h2>\n";
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
	echo "<option value='0'>--- ".$lang["knowbase"][12]." ---</option>\n";
	}
	showTreeListSelect("glpi_dropdown_kbcategories",$current, $parentID=0, $categoryname="");
	
//	kbcategoryListSelect($current, 0, "\\");
	echo "</select>\n";
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
	
	$catNumbers = array();

	if ($result=$db->query($query)){
			if ($db->numrows($result)>0){
	
	
				while ($row=$db->fetch_array($result)){
	
				getFAQParentCategories($row["categoryID"], $catNumbers);
				//	$catNumbers[] = $result["categoryID"];
				}
			}

	return($catNumbers);
}
}	

function getFAQParentCategories($ID, &$catNumbers)
{
	
		
	$query = "select * from glpi_dropdown_kbcategories where (ID = '$ID')";

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


function faqShowCategoriesall($target,$contains)
{

	global $lang;	

	searchFormKnowbase($target,$contains);
	
	echo "<div align='center'><table border='0' class='tab_cadre' >";
	echo "<tr><th align='center' width='700px'>".$lang["knowbase"][1]."</th></tr><tr><td>";	
	
	
	faqShowCategories();
	
	echo "</td></tr></table></div>";
}

function faqShowCategories($parentID=0)
{
	global $HTMLRel;
		
	$catNumbers = getFAQCategories();
	$query = "select * from glpi_dropdown_kbcategories where (parentID = $parentID) order by name asc";

	$db=new DB;

	if ($parentID==0) faqShowItems($parentID);

	if ($result=$db->query($query)){
			
	
			if ($db->numrows($result)>0){
		
			 
			 
			echo "<ul>\n";

			while ($row=$db->fetch_array($result)){
			
			
			
				$name = $row["name"];
				$ID = $row["ID"];
				if(in_array($ID, $catNumbers))
				{
				
				echo "<li><b>";
				if (!isset($_SESSION["kb_show"][$ID])) $_SESSION["kb_show"][$ID]='Y';
				if ($_SESSION["kb_show"][$ID]=='Y')
					echo "<a href=\"".$_SERVER["PHP_SELF"]."?show=faq&tohide=$ID\"><img src='".$HTMLRel."pics/puce-down.gif'></a>";
				else 
					echo "<a href=\"".$_SERVER["PHP_SELF"]."?show=faq&toshow=$ID\"><img src='".$HTMLRel."pics/puce.gif'></a>";

				echo "</a> $name</b>\n";
				if ($_SESSION["kb_show"][$ID]=='Y'){
	  				faqShowItems($ID);
					faqShowCategories($ID);
				}
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

function initExpandSessionVar(){
	if (!isset($_SESSION["kb_show"])){
	$query = "select ID from glpi_dropdown_kbcategories";

	$db=new DB;
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
		$_SESSION["kb_show"][$data["ID"]]='Y';
	}
	}	
}
function ExpandSessionVarHide($ID){
	$_SESSION["kb_show"][$ID]='N';
	
}
function ExpandSessionVarShow($ID,$recurse=0){
	$_SESSION["kb_show"][$ID]='Y';
	if ($recurse!=0){
		$db=new DB();
		$query="select parentID from glpi_dropdown_kbcategories where ID=$ID";
		$result=$db->query($query);
		$data=$db->fetch_array($result);
		if ($data["parentID"]!=0)
			ExpandSessionVarShow($data["parentID"],$recurse);
	}

}


function ExpandSessionVarHideAll(){
	$query = "select ID from glpi_dropdown_kbcategories";

	$db=new DB;
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
		$_SESSION["kb_show"][$data["ID"]]='N';
	}
}

function ExpandSessionVarShowAll(){
	$query = "select ID from glpi_dropdown_kbcategories";

	$db=new DB;
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
		$_SESSION["kb_show"][$data["ID"]]='Y';
	}
}

function searchLimitSessionVarKnowbase($contains){
	ExpandSessionVarHideAll();	
	$db=new DB;

// Recherche categories
	$query = "select ID from glpi_dropdown_kbcategories WHERE name LIKE '%$contains%'";
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
		ExpandSessionVarShow($data["ID"],1);
	}
// Recherche items
	$query = "select categoryID from glpi_kbitems WHERE question LIKE '%$contains%' OR answer LIKE '%$contains%'";
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
		ExpandSessionVarShow($data["categoryID"],1);
	}
	

	
}
?>
