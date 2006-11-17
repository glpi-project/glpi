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


// FUNCTIONS knowledgebase


/**
 * Print out a title  for knowbase module
 *
 * @return nothing (display the title)
 **/
function titleknowbase(){
	global  $LANG,$CFG_GLPI;

	$buttons=array();
	$title=$LANG["title"][5];
	
	if (haveRight("faq","w")||haveRight("knowbase","w")){
		$buttons["knowbase.php"]=$LANG["knowbase"][0];
		$buttons["knowbase.form.php?ID=new"]=$LANG["knowbase"][2];
		$title="";
	}
	displayTitle($CFG_GLPI["root_doc"]."/pics/knowbase.png",$LANG["title"][5],$title,$buttons);
}

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
function searchFormKnowbase($target,$contains,$parentID=0,$faq=0){
	global $LANG,$CFG_GLPI;
	
	if ($CFG_GLPI["public_faq"] == 0&&!haveRight("knowbase","r")&&!haveRight("faq","r")) return false;
	
	echo "<div align='center'>";
	echo "<table border='0'><tr><td>";
	
	
	echo "<form method=get action=\"".$target."\">";
	echo "<table border='0' class='tab_cadre'>";

	echo "<tr ><th colspan='2'><b>".$LANG["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_2' align='center'><td><input type='text' size='30' name=\"contains\" value=\"". stripslashes($contains) ."\" ></td>";
	
	//autocompletionTextField("contains","glpi_kbitems","question",$contains,20);
	
	echo "<td><input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit' ></td></tr>";

	echo "</table></form>";
	
	echo "</td>";
	
	// Category select not for anonymous FAQ
	if (isset($_SESSION["glpiID"])&&!$faq){
		echo "<td><form method=get action=\"".$target."\">";
		echo "<table border='0' class='tab_cadre'>";
		echo "<tr ><th colspan='2'><b>Naviguer</b></th></tr>";
		echo "<tr><td align='center'>";
		echo $LANG["common"][36]." : &nbsp; &nbsp;";
		dropdownValue("glpi_dropdown_kbcategories","parentID",$parentID);
		// ----***** TODO Dropdown qui affiche uniquement les categories contenant une FAQ
		
		echo "</td><td><input type='submit' value=\"".$LANG["buttons"][2]."\" class='submit' ></td></tr>";
	
		echo "</table></form></td>";
	} 
	
	echo "</tr></table></div>";


}




function showKbCategoriesFirstLevel($target,$parentID=0,$faq=0)
{
	// show kb categories
	// ok

	global $DB,$LANG,$CFG_GLPI;
	
	
	if($faq==1){
		if ($CFG_GLPI["public_faq"] == 0 && !haveRight("faq","r")) return false;	
		
		$query = "SELECT DISTINCT glpi_dropdown_kbcategories.* FROM glpi_kbitems LEFT JOIN glpi_dropdown_kbcategories ON (glpi_kbitems.categoryID = glpi_dropdown_kbcategories.ID) WHERE (glpi_kbitems.faq = 'yes') AND  (parentID = $parentID) ORDER  BY name ASC";
	}else{
		if (!haveRight("knowbase","r")) return false;
		$query = "SELECT * FROM glpi_dropdown_kbcategories WHERE  (parentID = $parentID) ORDER  BY name ASC";
	}
	

	/// Show category
	if ($result=$DB->query($query)){
		echo "<div align='center'><table cellspacing=\"0\" border=\"0\" width=\"750px\" class='tab_cadre'>";
		echo "<tr><td colspan='3'><a  href=\"".$target."\"<img alt='".$LANG["common"][25]."' src='".$CFG_GLPI["root_doc"]."/pics/folder-open.png' hspace=\"5\" ></a>";

		// Display Category
		if ($parentID!=0){
			$tmpID=$parentID;
			$todisplay="";
			while ($tmpID!=0){
				$query2="SELECT * FROM glpi_dropdown_kbcategories where ID='$tmpID'";
				$result2=$DB->query($query2);
				if ($DB->numrows($result2)==1){	
					$data=$DB->fetch_assoc($result2);
					$tmpID=$data["parentID"];
					$todisplay="<a href='$target?parentID=".$data["ID"]."'>".$data["name"]."</a>".(empty($todisplay)?"":" > ").$todisplay;
				} else $tmpID=0;
//				echo getDropdownName("glpi_dropdown_kbcategories",$parentID,"")."</td></tr>";
			}
			echo $todisplay;
		}
		
		if ($DB->numrows($result)>0){
			
			
				$i=0;
			while ($row=$DB->fetch_array($result)){
					// on affiche les résultats sur trois colonnes
					if ($i%3==0) { echo "<tr>";}
					$ID = $row["ID"];
					echo "<td valign=\"top\" align='left' style='width: 33%; padding: 3px 20px 3px 25px;'>";
				
					echo "<img alt='".$LANG["common"][25]."' src='".$CFG_GLPI["root_doc"]."/pics/folder.png'  hspace=\"5\" > <b><a  href=\"".$target."?parentID=".$row["ID"]."\">".$row["name"]."</a></b>\n";
					echo "<div style='font-size: 9px;	line-height: 10px; 	clear: both;	padding: 5px 0 0 25px;'>".resume_text($row['comments'],60)."</div>";
			
				if($i%3==2) { echo "</tr>\n"; }
				
				$i++;
			}
			
		}
	echo "<tr><td colspan='3'>&nbsp;</td></tr></table></div><br>";

	} 

}



/**
*Print out list kb item
*
*
*
**/
function showKbItemList($target,$field,$phrasetype,$contains,$sort,$order,$start,$parentID,$faq=0){
	// Lists kb  Items

	global $DB,$CFG_GLPI, $LANG;
	
	
	
	$where="";

	// Build query
	if ($faq==1){ // helpdesk
		
		$where="(glpi_kbitems.faq = 'yes') AND";
	
	}
	
	
	
	if (strlen($contains)) { // il s'agit d'une recherche 
		
		if($field=="all") {
			$search=makeTextSearch($contains);
			$where.=" (glpi_kbitems.question $search OR glpi_kbitems.answer $search) ";
			
		} else {
			$where = "($field ".makeTextSearch($contains).")";
			
		}
	}else { // Il ne s'agit pas d'une rechercher, on browse by category
	
		$where=" (glpi_kbitems.categoryID = $parentID) ";
	
	}
	
	
	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}

	$query = "SELECT  *  FROM glpi_kbitems";
  // $query.= " LEFT JOIN glpi_users  ON (glpi_users.ID = glpi_kbitems.author) ";
	$query.=" WHERE $where ORDER BY $sort $order";
	//echo $query;
	

	// Get it from database	
	if ($result = $DB->query($query)) {
		$numrows =  $DB->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows > $CFG_GLPI["list_limit"]&&!isset($_GET['export_all'])) {
			$query_limit = $query ." LIMIT $start,".$CFG_GLPI["list_limit"]." ";
			$result_limit = $DB->query($query_limit);
			$numrows_limit = $DB->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {


			// Set display type for export if define
			$output_type=HTML_OUTPUT;
			if (isset($_GET["display_type"]))
				$output_type=$_GET["display_type"];

			// Pager
			$parameters="start=$start&amp;parentID=$parentID&amp;field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=$sort&amp;order=$order&amp;faq=$faq";
			if ($output_type==HTML_OUTPUT)
				printPager($start,$numrows,$target,$parameters,KNOWBASE_TYPE);

			$nbcols=1;
			// Display List Header
			echo displaySearchHeader($output_type,$numrows_limit+1,$nbcols);

			if ($output_type!=HTML_OUTPUT){
				$header_num=1;
				echo displaySearchHeaderItem($output_type,$LANG["knowbase"][3],$header_num);
				echo displaySearchHeaderItem($output_type,$LANG["knowbase"][4],$header_num);
			}

			// Num of the row (1=header_line)
			$row_num=1;
			for ($i=0; $i < $numrows_limit; $i++) {
				$data=$DB->fetch_array($result_limit);


				// Column num
				$item_num=1;
				$row_num++;

				echo displaySearchNewLine($output_type);

				if ($output_type==HTML_OUTPUT){
					echo displaySearchItem($output_type,"<a  href=\"".$target."?ID=".$data["ID"]."\">".resume_text($data["question"],80)."</a><div style='font-size: 9px;	line-height: 10px; 	clear: both;	padding: 5px 0 0 45px;'>".resume_text(textBrut(unclean_cross_side_scripting_deep($data["answer"])),600)."</div>",$item_num,$row_num);
				} else {
					echo displaySearchItem($output_type,$data["question"],$item_num,$row_num);
					echo displaySearchItem($output_type,$data["answer"],$item_num,$row_num);
				}
				// le cumul de fonction me plait pas TODO à optimiser.
				
						

				// End Line
				echo displaySearchEndLine($output_type);
			}

			// Display footer
			if ($output_type==PDF_OUTPUT){
				echo displaySearchFooter($output_type,getDropdownName("glpi_dropdown_kbcategories",$parentID));
			} else {
				echo displaySearchFooter($output_type);
			}

			if ($output_type==HTML_OUTPUT) // In case of HTML display
				printPager($start,$numrows,$target,$parameters);

		} else {
			if ($parentID!=0) {echo "<div align='center'><b>".$LANG["search"][15]."</b></div>";}
		}
	}

}



/**
 * Print out list recent popular kb/faq
 *
 * 
 * 
 *
 * @param $target 
 * @param $faq
 * @return nothing (display table)
 **/
function showKbViewGlobal($target,$faq=0){
	
	echo "<div align='center'>";
	echo "<table width='950px'><tr><td align='center' valign='middle'>";
			
	showKbRecentPopular($target,"recent",$faq);
		
	echo "</td><td align='center' valign='middle'>";
		
	showKbRecentPopular($target,"popular",$faq);
		
	echo "</td></tr>";
				
	echo "</table>";
	echo "</div>";
}

function showKbRecentPopular($target,$order,$faq=0){
	
	global $DB,$CFG_GLPI, $LANG;
	
	
	if ($order=="recent"){
		$orderby="ORDER BY view DESC";
		$title=$LANG["knowbase"][30];
	}else {
		$orderby="ORDER BY date DESC";
		$title=$LANG["knowbase"][29];
	}
		

	$faq_limit="";		
	if($faq==1){ // FAQ
		$faq_limit="WHERE (glpi_kbitems.faq = 'yes')";
	}

	$query = "SELECT  *  FROM glpi_kbitems $faq_limit $orderby LIMIT 10";

	$result = $DB->query($query);
	$number = $DB->numrows($result);

	if ($number > 0) {
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th><b>".$title."</b></th></tr>";
	
		while ($data=$DB->fetch_array($result)) {
			echo "<tr><td><a  href=\"".$target."?ID=".$data["ID"]."\">".resume_text($data["question"],80)."</a></td></tr>";
		}
		echo "</table>";
	}
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

	global  $LANG,$CFG_GLPI;
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
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/tiny_mce/tiny_mce_gzip.php\"></script>";
	echo "<script language=\"javascript\" type=\"text/javascript\">";
	echo "tinyMCE.init({	language : \"".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][5]."\",  mode : \"exact\",  elements: \"answer\", plugins : \"table\", theme : \"advanced\",  theme_advanced_toolbar_location : \"top\", theme_advanced_toolbar_align : \"left\",   theme_advanced_buttons1 : \"bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent\", theme_advanced_buttons2 : \"forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator\",  theme_advanced_buttons3 : \"\"});";
	echo "</script>";
	echo "<form method='post' id='form_kb' name='form_kb' action=\"$target\">";


	if (!empty($ID))
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";


	echo "<fieldset>";
	echo "<legend>".$LANG["knowbase"][13]."</legend>";
	echo "<p style='text-align:center'>".$LANG["knowbase"][6];
	dropdownValue("glpi_dropdown_kbcategories","categoryID",$ki->fields["categoryID"]);
	echo "</p>";
	echo "</fieldset>";

	echo "<fieldset>";
	echo "<legend>".$LANG["knowbase"][14]."</legend>";
	echo "<div align='center'><textarea cols='80' rows='2'  name='question' >".$ki->fields["question"]."</textarea></div>"; 
	echo "</fieldset>";


	echo "<fieldset>";
	echo "<legend>".$LANG["knowbase"][15]."</legend><div align='center'>";
	echo "<textarea cols='80' rows='30' id='answer'  name='answer' >".$ki->fields["answer"]."</textarea></div>"; 

	echo "</fieldset>";


	echo "<br>\n";

	if (!empty($ID)) {
		echo "<fieldset>";
		echo "<div style='position: relative; text-align:left;'><span style='font-size:10px; color:#aaaaaa;'>";
		if ($ki->fields["author"]){
			echo $LANG["common"][37]." : ".getUserName($ki->fields["author"],"1")."      ";
		}
		
		echo "</span>";

		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px; color:#aaaaaa;  '>";
		if ($ki->fields["date_mod"]){
			echo $LANG["common"][26]." : ".convDateTime($ki->fields["date_mod"])."     ";
		}
		echo "</span><br />";
		echo "<span style='font-size:10px; color:#aaaaaa;'>";
		if ($ki->fields["date"]){
			echo $LANG["common"][27]." : ". convDateTime($ki->fields["date"]);
		}
		echo "</span>";
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px; color:#aaaaaa;  '>";
		echo $LANG["knowbase"][26]." : ".$ki->fields["view"]."</span></div>";
		

		echo "</fieldset>";
	}
	echo "<p align='center'>";

	if (haveRight("faq","w")&&haveRight("knowbase","w")){
		if ($ki->fields["faq"] == "yes") {
			echo "<input class='submit' type='checkbox' name='faq' value='yes' checked>";
		} else {
			echo "<input class='submit' type='checkbox' name='faq' value='yes'>";
		}
		echo $LANG["knowbase"][5]."<br><br>\n";
	}

	if (empty($ID)) {
		echo "<input type='hidden' name='author' value=\"".$_SESSION['glpiID']."\">\n";
		echo "<input type='submit' class='submit' name='add' value=\"".$LANG["buttons"][2]."\"> <input type='reset' class='submit' value=\"".$LANG["buttons"][16]."\">";
	} else {
		echo "<input type='submit' class='submit' name='update' value=\"".$LANG["buttons"][7]."\"> <input type='reset' class='submit' value=\"".$LANG["buttons"][16]."\">";
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
	global $LANG, $CFG_GLPI;

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
		echo $LANG["knowbase"][10]."</th></tr>";
	}
	else
	{
		echo $LANG["knowbase"][11]."</th></tr>";
	}


	echo "<tr>\n";
	if ($editFAQ)
		if($isFAQ == "yes")
		{
			echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;removefromfaq=yes\"><img class='icon_nav' src=\"".$CFG_GLPI["root_doc"]."/pics/faqremove.png\" alt='".$LANG["knowbase"][7]."' title='".$LANG["knowbase"][7]."'></a></td>\n";
		}
		else
		{
			echo "<td align='center' width=\"33%\"><a  class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;addtofaq=yes\"><img class='icon_nav' src=\"".$CFG_GLPI["root_doc"]."/pics/faqadd.png\" alt='".$LANG["knowbase"][5]."' title='".$LANG["knowbase"][5]."'></a></td>\n";
		}

	if ($edit){
		echo "<td align='center' width=\"34%\"><a class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;modify=yes\"><img class='icon_nav' src=\"".$CFG_GLPI["root_doc"]."/pics/faqedit.png\" alt='".$LANG["knowbase"][8]."' title='".$LANG["knowbase"][8]."'></a></td>\n";
		echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;delete=yes\"><img class='icon_nav' src=\"".$CFG_GLPI["root_doc"]."/pics/faqdelete.png\" alt='".$LANG["knowbase"][9]."' title='".$LANG["knowbase"][9]."'></a></td>";
	}
	echo "</tr>\n";
	echo "</table></div>\n";

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

	global $DB,$LANG,$CFG_GLPI;

	if (!haveRight("user","r")) $linkauthor="no";

	$ki= new kbitem;	

	$ki->getfromDB($ID);
	if ($ki->fields["faq"]=="yes"){
		if ($CFG_GLPI["public_faq"] == 0&&!haveRight("faq","r")) return false;	
	}
	else 
		if (!haveRight("knowbase","r")) return false;	

	//update counter view
	$query="UPDATE glpi_kbitems SET view=view+1 WHERE ID = '$ID'";
	$DB->query($query);



	$categoryID = $ki->fields["categoryID"];
	$fullcategoryname = getTreeValueCompleteName("glpi_dropdown_kbcategories",$categoryID);


	if (!($CFG_GLPI["cache"]->start($ID."_".$_SESSION["glpilanguage"],"GLPI_".$ki->type))) {
		echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='10' ><tr><th colspan='2'>";
	
		echo "<strong>".$LANG["common"][36].": ".$fullcategoryname."</strong></th></tr>";
	
		echo "<tr class='tab_bg_3'><td style='text-align:left' colspan='2'><h2>";
		echo ($ki->fields["faq"]=="yes") ? "".$LANG["knowbase"][3]."" : "".$LANG["knowbase"][14]."";
		echo "</h2>";
	
		$question = $ki->fields["question"];
	
		echo $question;
		echo "</td></tr>\n";
		echo "<tr  class='tab_bg_3'><td style='text-align:left' colspan='2'><h2>";
		echo ($ki->fields["faq"]=="yes") ? "".$LANG["knowbase"][4]."" : "".$LANG["knowbase"][15]."";
		echo "</h2>\n";
	
		$answer = unclean_cross_side_scripting_deep($ki->fields["answer"]);
	
		echo $answer;
		echo "</td></tr>";
	
		echo "<tr><th style='text-align:left;font-size:10px; color:#aaaaaa;'>";
		if($ki->fields["author"]){
			echo $LANG["common"][37]." : ";
			echo ($linkauthor=="yes") ? "".getUserName($ki->fields["author"],"1")."" : "".getUserName($ki->fields["author"])."";
			echo " | ";
		}
		if($ki->fields["date"]){
			echo $LANG["knowbase"][27]." : ". convDateTime($ki->fields["date"]);
		}	
	
		echo "</th><th style='text-align:right;font-size:10px; color:#aaaaaa;'>";
		if($ki->fields["date_mod"]){
			echo  $LANG["common"][26]." : ".convDateTime($ki->fields["date_mod"])." | ";
		}
		echo $LANG["knowbase"][26]." : ".$ki->fields["view"]."</th></tr>";
	
		echo "</table></div><br>";
		
		$CFG_GLPI["cache"]->end();
	}
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
	global $DB;
	$DB->query("UPDATE glpi_kbitems SET faq='yes' WHERE ID='$ID'");
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
	global $DB;
	$DB->query("UPDATE glpi_kbitems SET faq='no' WHERE ID='$ID'");
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

	global $DB;	

	$query = "SELECT DISTINCT glpi_dropdown_kbcategories.* FROM glpi_kbitems LEFT JOIN glpi_dropdown_kbcategories ON (glpi_kbitems.categoryID = glpi_dropdown_kbcategories.ID) WHERE (glpi_kbitems.faq = 'yes')";
	$toprocess=array();
	$catNumbers = array();

	if ($result=$DB->query($query)){
		if ($DB->numrows($result)>0){
			while ($row=$DB->fetch_array($result)){
				$catNumbers[]=$row["ID"];
			}
			$DB->data_seek($result,0);
			while ($row=$DB->fetch_array($result)){
				if($row["parentID"]&&!in_array($row["parentID"], $toprocess)){
					$toprocess[]=$row["parentID"];
				}
			}

		}
	}
	while (count($toprocess)){
		$query2="SELECT DISTINCT * FROM glpi_dropdown_kbcategories WHERE '0'='1' ";
		foreach ($toprocess as $key)
			$query2.=  " OR ID = '$key' ";
	
		$toprocess=array();

		if ($result=$DB->query($query2)){
			if ($DB->numrows($result)>0){
				while ($row=$DB->fetch_array($result)){
					if(!in_array($row["ID"], $catNumbers)){
						$catNumbers[]=$row["ID"];
						if($row["parentID"]&&!in_array($row["parentID"], $toprocess)){
							$toprocess[]=$row["parentID"];
						}
					}
				}
			}
		}
	}

	

	return($catNumbers);

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
	global $DB;

	$query = "select * from glpi_dropdown_kbcategories where (ID = '$ID')";

	if ($result=$DB->query($query)){
		if ($DB->numrows($result)>0){

			$data = $DB->fetch_array($result);

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

?>
