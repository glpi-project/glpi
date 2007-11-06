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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/**
 * Print generic Header Column
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
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
	global $CFG_GLPI;
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $PDF_HEADER;
			$PDF_HEADER[$num]=utf8_decode(html_clean($value));
			break;
		case SYLK_OUTPUT : //sylk
			global $SYLK_HEADER,$SYLK_SIZE;
			$SYLK_HEADER[$num]=sylk_clean($value);
			$SYLK_SIZE[$num]=strlen($SYLK_HEADER[$num]);

//			$out="F;SDM4;FG0C;".($num == 1 ? "Y1;" : "")."X$num\n";
//			$out.= "C;N;K\"".sylk_clean($value)."\"\n"; 
			break;
		case CSV_OUTPUT : //CSV
			$out="\"".csv_clean($value)."\";";
			break;
		default :

			$out="<th>";
			if ($issort) {
				if ($order=="DESC") $out.="<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
				else $out.="<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
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
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
 *@param $value value to display
 *@param $num column number
 *@param $row  row number
 *@param $extraparam extra parameters for display
 *
 *@return string to display
 *
 **/
function displaySearchItem($type,$value,&$num,$row,$extraparam=''){
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $PDF_ARRAY,$PDF_HEADER;
			$PDF_ARRAY[$row][$num]=utf8_decode(html_clean($value));
			break;
		case SYLK_OUTPUT : //sylk
			global $SYLK_ARRAY,$SYLK_HEADER,$SYLK_SIZE;
			$SYLK_ARRAY[$row][$num]=sylk_clean($value);
			$SYLK_SIZE[$num]=max($SYLK_SIZE[$num],strlen($SYLK_ARRAY[$row][$num]));

//			$out="F;P3;FG0L;".($num == 1 ? "Y".$row.";" : "")."X$num\n";
//			$out.= "C;N;K\"".sylk_clean($value)."\"\n"; 
			break;
      		case CSV_OUTPUT : //csv
            		$out="\"".csv_clean($value)."\";";
            		break;
		default :
			$out="<td $extraparam>".$value."</td>\n";
			break;
	}
	$num++;
	return $out;

}

/**
 * Print generic error
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
 *
 *@return string to display
 *
 **/
function displaySearchError($type){
	global $LANG;
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			break;
		case SYLK_OUTPUT : //sylk
			break;
        	case CSV_OUTPUT : //csv
            		break;
		default :
			$out= "<div class='center'><strong>".$LANG["search"][15]."</strong></div>\n";
			break;
	}
	return $out;

}
/**
 * Print generic footer
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
 *@param $title title of file : used for PDF
 *
 *@return string to display
 *
 **/
function displaySearchFooter($type,$title=""){
	global $LANG;
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			global $PDF_HEADER,$PDF_ARRAY;
			$pdf= new Cezpdf('a4','landscape');
			$pdf->selectFont(GLPI_ROOT."/lib/ezpdf/fonts/Helvetica.afm");
			$pdf->ezStartPageNumbers(750,10,10,'left',"GLPI PDF export - ".convDate(date("Y-m-d"))." - ".count($PDF_ARRAY)." ".utf8_decode($LANG["pager"][5])."- {PAGENUM}/{TOTALPAGENUM}");
			$options=array('fontSize'=>8,'colGap'=>2,'maxWidth'=>800,'titleFontSize'=>8,);
			$pdf->ezTable($PDF_ARRAY,$PDF_HEADER,utf8_decode($title),$options);
			$pdf->ezStream();

			break;
		case SYLK_OUTPUT : //sylk

			global $SYLK_HEADER,$SYLK_ARRAY,$SYLK_SIZE;
			// largeurs des colonnes
			foreach ($SYLK_SIZE as $num => $val) {
				$out.= "F;W".$num." ".$num." ".min(50,$val)."\n";
			}
			$out.="\n";
			// Header
			foreach ($SYLK_HEADER as $num => $val){
				$out.="F;SDM4;FG0C;".($num == 1 ? "Y1;" : "")."X$num\n";
				$out.= "C;N;K\"".sylk_clean($val)."\"\n"; 
				$out.="\n";
			}
			// Datas
			foreach ($SYLK_ARRAY as $row => $tab){
				foreach ($tab as $num => $val){
					$out.="F;P3;FG0L;".($num == 1 ? "Y".$row.";" : "")."X$num\n";
					$out.= "C;N;K\"".sylk_clean($val)."\"\n"; 
				}
			}

			$out.= "E\n";
			break;
        	case CSV_OUTPUT : //csv
            		break;
		default :
			$out= "</table></div>\n";
			break;
	}
	return $out;

}
/**
 * Print generic footer
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
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
			global $PDF_ARRAY,$PDF_HEADER;
			$PDF_ARRAY=array();
			$PDF_HEADER=array();
			break;
		case SYLK_OUTPUT : // Sylk
			global $SYLK_ARRAY,$SYLK_HEADER,$SYLK_SIZE;
			$SYLK_ARRAY=array();
			$SYLK_HEADER=array();
			$SYLK_SIZE=array();

//			define("FORMAT_REEL",   1); // #,##0.00
//			define("FORMAT_ENTIER", 2); // #,##0
//			define("FORMAT_TEXTE",  3); // @

//			$cfg_formats[FORMAT_ENTIER] = "FF0";
//			$cfg_formats[FORMAT_REEL]   = "FF2";
//			$cfg_formats[FORMAT_TEXTE]  = "FG0";

			// entetes HTTP
			// --------------------------------------------------------------------
			header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
			header('Pragma: private'); /// IE BUG + SSL
			//header('Pragma: no-cache'); 
			header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
			header("Content-disposition: filename=glpi.slk");
			header('Content-type: application/octetstream');


			// entete du fichier
			// --------------------------------------------------------------------
			echo "ID;PGLPI_EXPORT\n"; // ID;Pappli
			echo "\n";
			// formats
        		echo "P;PGeneral\n";      
        		echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
			echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
			echo "P;P@\n";              // P;Pformat_3 (textes)
			echo "\n";
			// polices
        		echo "P;EArial;M200\n";
			echo "P;EArial;M200\n";
			echo "P;EArial;M200\n";
			echo "P;FArial;M200;SB\n";
			echo "\n";
        		// nb lignes * nb colonnes
			echo "B;Y".$rows;
			echo ";X".$cols."\n"; // B;Yligmax;Xcolmax
			echo "\n";

			break;
		case CSV_OUTPUT : // csv
			header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
			header('Pragma: private'); /// IE BUG + SSL
			header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
			header("Content-disposition: filename=glpi.csv");
			header('Content-type: application/octetstream');
			break;
		default :
			if ($fixed){
				$out="<div class='center'><table border='0' class='tab_cadre_fixehov'>\n";
			} else {
				$out="<div class='center'><table border='0' class='tab_cadrehov'>\n";
			}
			break;
	}
	return $out;

}

/**
 * Print generic new line
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
 *@param $odd is it a new odd line ?
 *
 *@return string to display
 *
 **/

function displaySearchNewLine($type,$odd=false){
	$out="";
	switch ($type){
		case PDF_OUTPUT : //pdf
			break;
		case SYLK_OUTPUT : //sylk
//			$out="\n";
			break;
        	case CSV_OUTPUT : //csv
            		break;
		default :
			$class=" class='tab_bg_2' ";
			if ($odd){
				$class=" class='tab_bg_1' ";
			}
			$out="<tr $class>";
			break;
	}
	return $out;
}
/**
 * Print generic end line
 *
 *
 *@param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
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
        	case CSV_OUTPUT : //csv
            		$out="\n";
            		break;
		default :
			$out="</tr>";
			break;
	}
	return $out;
}

/**
 * Clean display value for csv export
 *
 *
 *@param $value string value
 *
 *@return clean value
 *
 **/
function csv_clean($value){

//	$value=utf8_decode($value);
	if (get_magic_quotes_runtime()) $value=stripslashes($value);
//	$value=preg_replace('/\x0A/',' ',$value);
//	$value=preg_replace('/\x0D/',NULL,$value);
	$value=ereg_replace("\"","''",$value);
//	$value=str_replace(';', ',', $value);
	$value=html_clean($value);

	return $value;
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

//	$value=utf8_decode($value);
	if (get_magic_quotes_runtime()) $value=stripslashes($value);
	$value=preg_replace('/\x0A/',' ',$value);
	$value=preg_replace('/\x0D/',NULL,$value);
	$value=ereg_replace("\"","''",$value);
	$value=str_replace(';', ';;', $value);
	$value=html_clean($value);

	return $value;
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
