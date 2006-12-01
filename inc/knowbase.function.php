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



// FUNCTIONS knowledgebase

/**
 * Print out an HTML "<form>" for Search knowbase item
 *
 * 
 * 
 *
 * @param $target 
 * @param $contains 
 * @return nothing (display the form)
 **/
function searchFormKnowbase($target,$contains){
	global $lang,$cfg_glpi;
	if ($cfg_glpi["public_faq"] == 0&&!haveRight("knowbase","r")&&!haveRight("faq","r")) return false;

	echo "<form method=post action=\"$target\">";
	echo "<div align='center'><table border='0' class='tab_cadre_fixe'>";

	echo "<tr ><th colspan='4'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_2' align='center'><td><input type='text' size='30' name=\"contains\" value=\"". stripslashes($contains) ."\" ></td><td><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit' ></td>";
	// From helpdesk or central
	if (ereg("\?",$target)) $separator="&amp;";
	else $separator="?";

	echo "<td><a href=\"".$target.$separator."toshow=all\">".$lang["knowbase"][21]."</a> </td>";
	echo "<td ><a href=\"".$target.$separator."tohide=all\">".$lang["knowbase"][22]."</a>";
	echo "</td></tr>";

	echo "</table></div></form>";


}

/**
 * Print out a title  for knowbase module
 *
 * 
 * 
 *
 * 
 * @return nothing (display the title)
 **/
function titleknowbase(){


	global  $lang,$HTMLRel;

	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/knowbase.png\" alt='".$lang["knowbase"][2]."' title='".$lang["knowbase"][2]."'></td>";
	if (haveRight("faq","w")||haveRight("knowbase","w")){
		echo "<td>";
		echo "<a  class='icon_consol' href=\"knowbase.form.php?ID=new\"><b>".$lang["knowbase"][2]."</b></a>";
		echo "</td>";
	} else echo "<td><span class='icon_sous_nav'>".$lang["title"][5]."</span></td>";
	echo "</tr>";
	echo "</table></div>";

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
function showKbItemForm($target,$ID){

	// show kb item form

	global  $lang,$HTMLRel,$cfg_glpi;
	if (!haveRight("knowbase","w")&&!haveRight("faq","w")) return false;
	$ki= new kbitem;	

	if (empty($ID)) {

		$ki->getEmpty();


	} else {
		$ki->getfromDB($ID);
		if ($ki->fields["faq"]=="yes"&&!haveRight("faq","w")) return false;
		if ($ki->fields["faq"]!="yes"&&!haveRight("knowbase","w")) return false;

	}	
	echo "<div align='center'>";
	echo "<div id='contenukb'>";
	//echo "<script type='text/javascript' language='javascript' src='".$HTMLRel."toolbar.js'></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/tiny_mce/tiny_mce_gzip.php\"></script>";
	echo "<script language=\"javascript\" type=\"text/javascript\">";
	echo "tinyMCE.init({	language : \"".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][5]."\",  mode : \"exact\",  elements: \"answer\", plugins : \"table\", theme : \"advanced\",  theme_advanced_toolbar_location : \"top\", theme_advanced_toolbar_align : \"left\",   theme_advanced_buttons1 : \"bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent\", theme_advanced_buttons2 : \"forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator\",  theme_advanced_buttons3 : \"\"});";
	echo "</script>";
	echo "<form method='post' id='form_kb' name='form_kb' action=\"$target\">";


	if (!empty($ID))
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";


	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][13]."</legend>";
	echo "<p style='text-align:center'>".$lang["knowbase"][6];
	dropdownValue("glpi_dropdown_kbcategories","categoryID",$ki->fields["categoryID"]);
	echo "</p>";
	echo "</fieldset>";

	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][14]."</legend>";
	echo "<div align='center'><textarea cols='80' rows='2'  name='question' >".$ki->fields["question"]."</textarea></div>"; 
	echo "</fieldset>";


	echo "<fieldset>";
	echo "<legend>".$lang["knowbase"][15]."</legend><div align='center'>";
	echo "<textarea cols='80' rows='30' id='answer'  name='answer' >".$ki->fields["answer"]."</textarea></div>"; 

	echo "</fieldset>";


	echo "<br>\n";

	if (!empty($ID)) {
		echo "<fieldset>";
		echo "<div style='position: relative; text-align:left;'><span style='font-size:10px; color:#aaaaaa;'>";
		if ($ki->fields["author"]){
			echo $lang["common"][37]." : ".getUserName($ki->fields["author"],"1")."      ";
		}
		
		echo "</span>";

		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px; color:#aaaaaa;  '>";
		if ($ki->fields["date_mod"]){
			echo $lang["common"][26]." : ".convDateTime($ki->fields["date_mod"])."     ";
		}
		echo "</span><br />";
		echo "<span style='font-size:10px; color:#aaaaaa;'>";
		if ($ki->fields["date"]){
			echo $lang["knowbase"][27]." : ". convDateTime($ki->fields["date"]);
		}
		echo "</span>";
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px; color:#aaaaaa;  '>";
		echo $lang["knowbase"][26]." : ".$ki->fields["view"]."</span></div>";
		

		echo "</fieldset>";
	}
	echo "<p align='center'>";

	if (haveRight("faq","w")&&haveRight("knowbase","w")){
		if ($ki->fields["faq"] == "yes") {
			echo "<input class='submit' type='checkbox' name='faq' value='yes' checked>";
		} else {
			echo "<input class='submit' type='checkbox' name='faq' value='yes'>";
		}
		echo $lang["knowbase"][5]."<br><br>\n";
	}

	if (empty($ID)) {
		echo "<input type='hidden' name='author' value=\"".$_SESSION['glpiID']."\">\n";
		echo "<input type='submit' class='submit' name='add' value=\"".$lang["buttons"][2]."\"> <input type='reset' class='submit' value=\"".$lang["buttons"][16]."\">";
	} else {
		echo "<input type='submit' class='submit' name='update' value=\"".$lang["buttons"][7]."\"> <input type='reset' class='submit' value=\"".$lang["buttons"][16]."\">";
	}

	echo "</p>";
	echo "</form>";

	echo "</div></div>";
} 

/**
 * Print out an HTML Menu for knowbase item
 *
 * 
 * 
 *
 * @param $ID
 * @return nothing (display the form)
 **/
function kbItemMenu($ID)
{
	global $lang,$HTMLRel, $cfg_glpi;

	if (!haveRight("knowbase","w")&&!haveRight("faq","w")) return false;

	$ki= new kbitem;	

	$ki->getfromDB($ID);
	$isFAQ = $ki->fields["faq"];
	$editFAQ=haveRight("faq","w");
	$edit=true;
	if ($isFAQ=="yes"&&!haveRight("faq","w")) $edit=false;
	if ($isFAQ!="yes"&&!haveRight("knowbase","w")) $edit=false;

	echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='10' ><tr><th colspan='3'>";

	if($isFAQ == "yes")
	{
		echo $lang["knowbase"][10]."</th></tr>";
	}
	else
	{
		echo $lang["knowbase"][11]."</th></tr>";
	}


	echo "<tr>\n";
	if ($editFAQ)
		if($isFAQ == "yes")
		{
			echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;removefromfaq=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqremove.png\" alt='".$lang["knowbase"][7]."' title='".$lang["knowbase"][7]."'></a></td>\n";
		}
		else
		{
			echo "<td align='center' width=\"33%\"><a  class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;addtofaq=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqadd.png\" alt='".$lang["knowbase"][5]."' title='".$lang["knowbase"][5]."'></a></td>\n";
		}

	if ($edit){
		echo "<td align='center' width=\"34%\"><a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;modify=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqedit.png\" alt='".$lang["knowbase"][8]."' title='".$lang["knowbase"][8]."'></a></td>\n";
		echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;delete=yes\"><img class='icon_nav' src=\"".$HTMLRel."pics/faqdelete.png\" alt='".$lang["knowbase"][9]."' title='".$lang["knowbase"][9]."'></a></td>";
	}
	echo "</tr>\n";
	echo "</table></div>\n";

}


/**
 * Print out all kb cat�ories
 *
 * 
 * 
 *
 * 
 * @return nothing (display all kb cat�ories)
 **/
function showKbCategoriesall($target,$contains='')
{

	global $lang;	
	if (!haveRight("knowbase","r")) return false;

	searchFormKnowbase($target,$contains);

	echo "<div align='center'><table border='0' class='tab_cadre_fixe' >";
	echo "<tr><th align='center' >".$lang["knowbase"][0]."</th></tr><tr><td align='left' class='tab_bg_3'>";	

	showKbCategories(0,$contains);

	echo "</td></tr></table></div>";
}


/**
 * Print out kb cat�ories
 *
 * @param $parentID integer
 * @param $contains
 * 
 *
 * 
 * @return nothing (display kb cat�ories in a list)
 **/

function showKbCategories($parentID=0,$contains='')
{
	// show kb cat�ories
	// ok

	global $db,$lang,$HTMLRel;

	if (!haveRight("knowbase","r")) return false;

	$query = "select * from glpi_dropdown_kbcategories where (parentID = $parentID) order by name asc";


	if ($parentID==0) showKbItemAll($parentID,$contains);

	/// Show category
	if ($result=$db->query($query)){

		if ($db->numrows($result)>0){
			echo "<ul>";	
			while ($row=$db->fetch_array($result)){


				$ID = $row["ID"];
				echo "<li><b>";
				if (!isset($_SESSION["kb_show"][$ID])) $_SESSION["kb_show"][$ID]='Y';
				if ($_SESSION["kb_show"][$ID]=='Y')
					echo "<a href=\"".$_SERVER['PHP_SELF']."?tohide=$ID\"><img src='".$HTMLRel."pics/puce-down.gif' alt='down'></a>";
				else 
					echo "<a href=\"".$_SERVER['PHP_SELF']."?toshow=$ID\"><img src='".$HTMLRel."pics/puce.gif' alt='up'></a>";

				echo " ".$row["name"]."</b>\n";
				if (!empty($row["comments"])){
					echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$ID')\" onmouseover=\"cleandisplay('comments_$ID')\">";
					echo "<span class='over_link' id='comments_$ID'>".nl2br($row['comments'])."</span>";
				}
				if ($_SESSION["kb_show"][$ID]=='Y'){
					showKbItemAll($ID,$contains);
					showKbCategories($ID,$contains);
				}
			}
			echo "</ul>\n";
		}


	} 

}

/**
 * Print out kb item in each categories
 *
 * @param $parentID integer
 * @param $contains
 * 
 *
 * 
 * @return nothing (display kb items in a list)
 **/
function showKbItemAll($parentID,$contains='')
{
	// show kb item in each categories

	global $db;	

	if (!haveRight("knowbase","r")) return false;

	$WHERE="";

	if (strlen($contains)) {
		$SEARCH=makeTextSearch($contains);
		$WHERE=" AND (question $SEARCH OR answer $SEARCH) ";
	}

	$query = "select * from glpi_kbitems where (categoryID = $parentID) $WHERE order by question asc";


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

/**
 * Print out each kb items
 *
 * @param $ID integer
 * 
 *
 * 
 * @return nothing (display kb items in a list)
 **/
function showKbItem($ID)
{
	// show each kb items

	global $db,$cfg_glpi,  $lang;

	$query = "select * from glpi_kbitems where (ID=$ID)";


	if ($result=$db->query($query)){
		$data = $db->fetch_array($result);
		$question = $data["question"];
		$class="";
		if ($data["faq"]=="no") $class=" class='pubfaq' ";
		echo "<li><a $class href=\"".$cfg_glpi["root_doc"]."/front/knowbase.form.php?ID=$ID\">&nbsp;".$question."&nbsp;</a>\n";
	}
}



/**
 * Print out (html) show item : question and answer
 *
 * @param $ID integer
 * @param $linkauthor
 * 
 *
 * 
 * @return nothing (display item : question and answer)
 **/
function ShowKbItemFull($ID,$linkauthor="yes")
{
	// show item : question and answer

	global $db,$lang,$cfg_glpi;

	if (!haveRight("user","r")) $linkauthor="no";

	$ki= new kbitem;	

	$ki->getfromDB($ID);
	if ($ki->fields["faq"]=="yes"){
		if ($cfg_glpi["public_faq"] == 0&&!haveRight("faq","r")) return false;	
	}
	else 
		if (!haveRight("knowbase","r")) return false;	

	//update counter view
	$query="UPDATE glpi_kbitems SET view=view+1 WHERE ID = '$ID'";
	$db->query($query);



	$categoryID = $ki->fields["categoryID"];
	$fullcategoryname = getTreeValueCompleteName("glpi_dropdown_kbcategories",$categoryID);

	echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='10' ><tr><th colspan='2'>";

	echo "<strong>".$lang["common"][36].": ".$fullcategoryname."</strong></th></tr>";

	echo "<tr class='tab_bg_3'><td style='text-align:left' colspan='2'><h2>";
	echo ($ki->fields["faq"]=="yes") ? "".$lang["knowbase"][3]."" : "".$lang["knowbase"][14]."";
	echo "</h2>";

	$question = $ki->fields["question"];

	echo $question;
	echo "</td></tr>\n";
	echo "<tr  class='tab_bg_3'><td style='text-align:left' colspan='2'><h2>";
	echo ($ki->fields["faq"]=="yes") ? "".$lang["knowbase"][4]."" : "".$lang["knowbase"][15]."";
	echo "</h2>\n";

	$answer = unclean_cross_side_scripting_deep($ki->fields["answer"]);

	echo $answer;
	echo "</td></tr>";

	echo "<tr><th style='text-align:left;font-size:10px; color:#aaaaaa;'>";
	if($ki->fields["author"]){
		echo $lang["common"][37]." : ";
		echo ($linkauthor=="yes") ? "".getUserName($ki->fields["author"],"1")."" : "".getUserName($ki->fields["author"])."";
		echo " | ";
	}
	if($ki->fields["date"]){
		echo $lang["knowbase"][27]." : ". convDateTime($ki->fields["date"]);
	}	

	echo "</th><th style='text-align:right;font-size:10px; color:#aaaaaa;'>";
	if($ki->fields["date_mod"]){
		echo  $lang["common"][26]." : ".convDateTime($ki->fields["date_mod"])." | ";
	}
	echo $lang["knowbase"][26]." : ".$ki->fields["view"]."</th></tr>";

	echo "</table></div><br>";

	return true;	
}


//*******************
// Gestion de la  FAQ
//******************



/**
 * Add kb item to the public FAQ
 *
 * 
 * @param $ID integer
 *
 * 
 * @return nothing 
 **/
function KbItemaddtofaq($ID)
{
	global $db;
	$db->query("UPDATE glpi_kbitems SET faq='yes' WHERE ID='$ID'");
}

/**
 * Remove kb item from the public FAQ
 *
 * 
 * @param $ID integer
 *
 * 
 * @return nothing 
 **/
function KbItemremovefromfaq($ID)
{
	global $db;
	$db->query("UPDATE glpi_kbitems SET faq='no' WHERE ID='$ID'");
}


/**
 * 
 * get FAQ Categories
 * 
 * 
 *
 * 
 * @return $catNumbers
 **/
function getFAQCategories()
{

	global $db;	

	$query = "select * from glpi_kbitems where (faq = 'yes')";

	$catNumbers = array();

	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){


			while ($row=$db->fetch_array($result)){
				$catNumbers=getFAQParentCategories($row["categoryID"], $catNumbers);
				array_push($catNumbers,$result["categoryID"]);
			}
		}
		return($catNumbers);
	}
}	

/**
 * 
 * get parent FAQ Categories
 * 
 * @param $ID
 * @param $catNumbers
 * 
 * @return $catNumbers
 **/
function getFAQParentCategories($ID, $catNumbers)
{
	global $db;

	$query = "select * from glpi_dropdown_kbcategories where (ID = '$ID')";

	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){

			$data = $db->fetch_array($result);

			$parentID = $data["parentID"];
			if(!in_array($parentID, $catNumbers))
			{
				$catNumbers=getFAQParentCategories($parentID, $catNumbers);
			}
			if(!in_array($ID, $catNumbers))
			{
				array_push($catNumbers,$ID);
			}

		}
	}
	return($catNumbers);
}

/**
 * 
 * Print out all FAQ cat�ories 
 * 
 * @param $target
 * @param $contains
 * 
 * @return nothing (display faq cat�ories)
 **/
function faqShowCategoriesall($target,$contains)
{

	global $lang,$cfg_glpi;	

	if ($cfg_glpi["public_faq"] == 0 && !haveRight("faq","r")) return false;	

	searchFormKnowbase($target,$contains);

	echo "<div align='center'><table border='0' class='tab_cadre_fixe' >";
	echo "<tr ><th align='center' >".$lang["knowbase"][1]."</th></tr><tr class='tab_bg_2'><td  align='left'>";	



	faqShowCategories($target,0,$contains);

	echo "</td></tr></table></div>";
}

/**
 * 
 * To be commented
 * 
 * @param $parentID
 * @param $contains
 * 
 * 
 * @return 
 **/
function faqShowCategories($target,$parentID=0,$contains='')
{
	global $db,$HTMLRel,$lang;

	$catNumbers = getFAQCategories();

	$query = "select * from glpi_dropdown_kbcategories where (parentID = $parentID) order by name asc";


	if ($parentID==0) faqShowItems($target,$parentID,$contains);

	if ($result=$db->query($query)){


		if ($db->numrows($result)>0){


			while ($row=$db->fetch_array($result)){

				$ID = $row["ID"];

				if(in_array($ID, $catNumbers))
				{
					echo "<ul>\n";

					echo "<li><b>";
					if (!isset($_SESSION["kb_show"][$ID])) $_SESSION["kb_show"][$ID]='Y';
					if ($_SESSION["kb_show"][$ID]=='Y')
						echo "<a href=\"".$_SERVER['PHP_SELF']."?show=faq&amp;tohide=$ID\"><img src='".$HTMLRel."pics/puce-down.gif'></a>";
					else 
						echo "<a href=\"".$_SERVER['PHP_SELF']."?show=faq&amp;toshow=$ID\"><img src='".$HTMLRel."pics/puce.gif'></a>";

					echo " ".$row["name"]."</b>\n";

					if (!empty($row["comments"])){
						echo "<img alt='".$lang["common"][25]."' src='".$HTMLRel."pics/aide.png' onmouseout=\"cleanhide('comments_$ID')\" onmouseover=\"cleandisplay('comments_$ID')\">";
						echo "<span class='over_link' id='comments_$ID'>".nl2br($row['comments'])."</span>";
					}

					if ($_SESSION["kb_show"][$ID]=='Y'){
						faqShowItems($target,$ID,$contains);
						faqShowCategories($target,$ID,$contains);
					}
					echo "</ul>\n";
				}

			}
		}
	} 
}


/**
 * 
 * To be commented
 * 
 * @param $parentID
 * @param $contains
 * 
 * 
 * @return 
 **/
function faqShowItems($target,$parentID,$contains)
{
	global $db;
	// ok	

	$WHERE="";
	if (strlen($contains)) {
		$SEARCH=makeTextSearch($contains);
		$WHERE=" AND (question $SEARCH OR answer $SEARCH) ";
	}


	$query = "select * from glpi_kbitems where (categoryID = $parentID) and (faq = 'yes') $WHERE order by question asc";


	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
			echo "<ul>\n";
			while ($row=$db->fetch_array($result)){
				$ID = $row["ID"];
				faqShowItem($target,$ID);
			}
			echo "</ul>\n";
		}
	}
}


/**
 * 
 * To be commented
 * 
 * @param $ID
 * 
 * 
 * @return 
 **/
function faqShowItem($target,$ID)
{
	// ok

	global $db,$cfg_glpi;

	$query = "select * from glpi_kbitems where (ID=$ID)";


	if ($result=$db->query($query)){
		$data = $db->fetch_array($result);
		$question = $data["question"];
		if (ereg("\?",$target)) $target.="&amp;";
		else $target.="?";

		echo "<li><a href=\"".$target."ID=$ID\">$question</a>\n";
	}

}

/**
 * 
 * To be commented
 * 
 * 
 * 
 * 
 * @return 
 **/
function initExpandSessionVar(){
	global $db;
	if (!isset($_SESSION["kb_show"])){
		$query = "select ID from glpi_dropdown_kbcategories";


		if ($result=$db->query($query)){
			while ($data=$db->fetch_array($result))
				$_SESSION["kb_show"][$data["ID"]]='Y';
		}
	}	
}

/**
 * 
 * To be commented
 * 
 * @param $ID
 * 
 * 
 * @return 
 **/
function ExpandSessionVarHide($ID){
	$_SESSION["kb_show"][$ID]='N';

}

/**
 * 
 * To be commented
 * 
 * @param $ID
 * @param $recurse
 * 
 * @return 
 **/
function ExpandSessionVarShow($ID,$recurse=0){
	global $db;
	$_SESSION["kb_show"][$ID]='Y';
	if ($recurse!=0){

		$query="select parentID from glpi_dropdown_kbcategories where ID='$ID'";
		$result=$db->query($query);
		$data=$db->fetch_array($result);
		if ($data["parentID"]!=0)
			ExpandSessionVarShow($data["parentID"],$recurse);
	}

}

/**
 * 
 * To be commented
 * 
 * 
 * 
 * 
 * @return 
 **/
function ExpandSessionVarHideAll(){
	global $db;
	$query = "select ID from glpi_dropdown_kbcategories";

	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
			$_SESSION["kb_show"][$data["ID"]]='N';
	}
}

/**
 * 
 * To be commented
 * 
 * 
 * 
 * 
 * @return 
 **/
function ExpandSessionVarShowAll(){
	global $db;
	$query = "select ID from glpi_dropdown_kbcategories";

	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
			$_SESSION["kb_show"][$data["ID"]]='Y';
	}
}

/**
 * 
 * To be commented
 * 
 * @param $contains
 * 
 * 
 * @return 
 **/
function searchLimitSessionVarKnowbase($contains){
	global $db;
	ExpandSessionVarHideAll();	

	$SEARCH=makeTextSearch($contains);

	// Recherche categories
	$query = "select ID from glpi_dropdown_kbcategories WHERE name $SEARCH";
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
			ExpandSessionVarShow($data["ID"],1);
	}
	// Recherche items
	$query = "select categoryID from glpi_kbitems WHERE question $SEARCH OR answer $SEARCH";
	if ($result=$db->query($query)){
		while ($data=$db->fetch_array($result))
			ExpandSessionVarShow($data["categoryID"],1);
	}



}
?>
