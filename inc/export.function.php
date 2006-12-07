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

/**
 * Print generic Header Column
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *@param $value value to display
 *@param $num column number
 *@param $linkto link display element (HTML specific)
 *@param $issort is the sort column ?
 *@param $order  order type ASC or DESC
 *
 *@return string to display
 *
 **/
function displaySearchHeaderItem($type,$value,&$num,$linkto="",$issort=0,$order=""){
	global $HTMLRel;
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $pdf_header,$pdf_size;
			$pdf_header[$num]=html_clean(utf8_decode($value));
			$pdf_size[$num]=strlen($pdf_header[$num]);
			break;
		case SYLK_OUTPUT : //sylk
			$out="F;SDM4;FG0C;".($num == 1 ? "Y1;" : "")."X$num\n";
			$out.= "C;N;K\"".sylk_clean($value)."\"\n"; 
			break;
		default :

			$out="<th>";
			if ($issort) {
				if ($order=="DESC") $out.="<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else $out.="<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}

			if (!empty($linkto))
				$out.= "<a href=\"$linkto\">";

			$out.= $value;

			if (!empty($linkto))
				$out.="</a>";

			$out.="</th>\n";
			break;
	}
	$num++;
	return $out;

}


/**
 * Print generic normal Item Cell
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *@param $value value to display
 *@param $num column number
 *@param $row  row number
 *@param $deleted is it a deleted item ?
 *@param $extraparam extra parameters for display
 *
 *@return string to display
 *
 **/
function displaySearchItem($type,$value,&$num,$row,$deleted=0,$extraparam=''){
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $pdf_array,$pdf_header,$pdf_size;
			$pdf_array[$row][$num]=html_clean(utf8_decode($value));
			$pdf_size[$num]=max($pdf_size[$num],strlen($pdf_array[$row][$num]));
			break;
		case SYLK_OUTPUT : //sylk
			$out="F;P3;FG0L;".($num == 1 ? "Y".$row.";" : "")."X$num\n";
			$out.= "C;N;K\"".sylk_clean($value)."\"\n"; 
			break;
		default :
			$class="";
			if ($deleted) $class=" class='tab_bg_2_2' ";
			$out="<td $class $extraparam>".$value."</td>\n";
			break;
	}
	$num++;
	return $out;

}

/**
 * Print generic error
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *
 *@return string to display
 *
 **/
function displaySearchError($type){
	global $lang;
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			break;
		case SYLK_OUTPUT : //sylk
			break;
		default :
			$out= "<div align='center'><b>".$lang["search"][15]."</b></div>\n";
			break;
	}
	return $out;

}
/**
 * Print generic footer
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *@param $title title of file : used for PDF
 *
 *@return string to display
 *
 **/
function displaySearchFooter($type,$title=""){
	global $lang;
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $pdf_header,$pdf_array,$pdf_size,$phproot;
			$pdf= new Cezpdf('a4','landscape');
			$pdf->selectFont($phproot."/lib/ezpdf/fonts/Helvetica.afm");
			$pdf->ezStartPageNumbers(750,10,10,'left',"GLPI PDF export - ".convDate(date("Y-m-d"))." - ".count($pdf_array)." ".utf8_decode($lang["pager"][5])."- {PAGENUM}/{TOTALPAGENUM}");
			$options=array('fontSize'=>8,'colGap'=>2,'maxWidth'=>800,'titleFontSize'=>8,);
			//print_r($pdf_size);

			$pdf->ezTable($pdf_array,$pdf_header,utf8_decode($title),$options);
			$pdf->ezStream();

			break;
		case SYLK_OUTPUT : //sylk
			break;
		default :
			$out= "</table></div><br>\n";
			break;
	}
	return $out;

}
/**
 * Print generic footer
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *@param $cols number of columns
 *@param $rows  number of rows
 *@param $fixed  used tab_cadre_fixe table for HTML export ?  
 *
 *@return string to display
 *
 **/
function displaySearchHeader($type,$rows,$cols,$fixed=0){
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $pdf_array,$pdf_header;
			$pdf_array=array();
			$pdf_header=array();
			$pdf_size=array();
			break;
		case SYLK_OUTPUT : // Sylk
			define("FORMAT_REEL",   1); // #,##0.00
			define("FORMAT_ENTIER", 2); // #,##0
			define("FORMAT_TEXTE",  3); // @

			$cfg_formats[FORMAT_ENTIER] = "FF0";
			$cfg_formats[FORMAT_REEL]   = "FF2";
			$cfg_formats[FORMAT_TEXTE]  = "FG0";

			// en-tête HTTP
			// --------------------------------------------------------------------
			header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
			header('Pragma: private'); /// IE BUG + SSL
			//header('Pragma: no-cache'); 
			header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
			header("Content-disposition: filename=glpi.slk");
			header('Content-type: application/octetstream');


			// en-tête du fichier
			// --------------------------------------------------------------------
			echo "ID;PGLPI_EXPORT\n"; // ID;Pappli
			echo "\n";
			// formats
			//        		echo "P;PGeneral\n";      
			//        		echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
			//        		echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
			//        		echo "P;P@\n";              // P;Pformat_3 (textes)
			//        		echo "\n";
			// polices
			/*        		echo "P;EArial;M200\n";
						echo "P;EArial;M200\n";
						echo "P;EArial;M200\n";
						echo "P;FArial;M200;SB\n";
						echo "\n";
			 */        		// nb lignes * nb colonnes
			echo "B;Y".$rows;
			echo ";X".$cols."\n"; // B;Yligmax;Xcolmax
			echo "\n";

			// largeurs des colonnes
			//			for ($i=1;$i<=$cols;$i++)
			//				echo "F;W".$i." ".$i." 20\n";

			break;

		default :
			if ($fixed)
				$out="<div align='center'><table border='0' class='tab_cadre_fixehov'>\n";
			else $out="<div align='center'><table border='0' class='tab_cadrehov'>\n";
			break;
	}
	return $out;

}

/**
 * Print generic new line
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *
 *@return string to display
 *
 **/

function displaySearchNewLine($type){
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			break;
		case SYLK_OUTPUT : //sylk
			$out="\n";
			break;

		default :
			$out="<tr class='tab_bg_2'>";
			break;
	}
	return $out;
}
/**
 * Print generic end line
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF)
 *
 *@return string to display
 *
 **/
function displaySearchEndLine($type){
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			break;
		case SYLK_OUTPUT : //sylk
			break;

		default :
			$out="</tr>";
			break;
	}
	return $out;
}

/**
 * Clean display value for sylk export
 *
 *
 *@param $value string value
 *
 *@return clean value
 *
 **/
function sylk_clean($value){

	$value=utf8_decode($value);
	if (get_magic_quotes_runtime()) $value=stripslashes($value);
	$value=preg_replace('/\x0A/',' ',$value);
	$value=preg_replace('/\x0D/',NULL,$value);
	$value=ereg_replace("\"","''",$value);
	$value=str_replace(';', ';;', $value);
	$value=html_clean($value);
	return $value;
}

/**
 * Clean display value deleting html tags
 *
 *
 *@param $value string value
 *
 *@return clean value
 *
 **/
function html_clean($value){

	$search=array(
			"/<a[^>]+>/",
			"/<img[^>]+>/",
			"/<span[^>]+>/",
			"/<\/span>/",
			"/<\/a>/",
			"/<strong>/",
			"/<\/strong>/",
			"/<small>/",
			"/<\/small>/",
			"/<i>/",
			"/<\/i>/",
			"/<br>/",
			"/<br \/>/",
			"/&nbsp;;/",
			"/&nbsp;/",
		     );
	$replace=array(
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			"",
			", ",
			"\n",
			" ",
			" ",
		      );
	$value=preg_replace($search,$replace,$value);
	return trim($value);
}
/*
   function pdf_wrap(&$data,$num,$size){
   foreach ($data as $a => $b){
   pdf_wrap_item($b,$num,$size);
   $data[$a]=$b;
   }
   }

   function pdf_wrap_item(&$data,$num,$size){

   foreach ($data as $key => $val)
   if ($key==$num&&strlen($val)>$size)
   if (strpos($val,">")){
   $data[$key]=wordwrapLine($val,$size,">");
   }
   else {
   $data[$key]=wordwrapLine($val,$size," ");
   }
   }

   function wordwrapLine($s, $l,$t) {

   $split=split("\n",$s);
   $out="";
   $maxlength=0;
   foreach ($split as $key=>$s){
   $line="";
   $formatted="";
   $tok = strtok($s, $t);

   while (strlen($tok) != 0) {
   if (strlen($line) + strlen($tok) < ($l + 2) ) {
   if (!empty($line)) $line.=$t;
   $line .= $tok;
   }
   else {
   $formatted .= "$line$t\n";
   $line = $tok;
   }
   $tok = strtok($t);
   }

   $formatted .= $line;
   if (substr($s,-1,1)==$t) $formatted .=$t;
   $out .= trim($formatted);

   if (($key+1)!=count($split)) $out.="\n";
   }

   return $out;
   }
 */

?>
