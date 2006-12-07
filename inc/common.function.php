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







//******************************************************************************************************
//******************************************************************************************************
//********************************  Fonctions diverses ************************************
//******************************************************************************************************
//******************************************************************************************************



/**
 * Give name of the device
 *
 *
 *
 * @param $ID ID of the device type
 * @return string name of the device type in the current lang
 *
 */
function getDeviceTypeName($ID){
	global $lang;
	switch ($ID){
		case COMPUTER_TYPE : return $lang["help"][25];break;
		case NETWORKING_TYPE : return $lang["help"][26];break;
		case PRINTER_TYPE : return $lang["help"][27];break;
		case MONITOR_TYPE : return $lang["help"][28];break;
		case PERIPHERAL_TYPE : return $lang["help"][29];break;
		case SOFTWARE_TYPE : return $lang["help"][31];break;
		case CARTRIDGE_TYPE : return $lang["Menu"][21];break;
		case CONTACT_TYPE : return $lang["Menu"][22];break;
		case ENTERPRISE_TYPE : return $lang["Menu"][23];break;
		case CONTRACT_TYPE : return $lang["Menu"][25];break;
		case CONSUMABLE_TYPE : return $lang["Menu"][32];break;


	}

}

/**
 * Strip slash  for variable & array
 *
 *
 *
 * @param $value item to stripslashes (array or string)
 * @return stripslashes item
 *
 */
function stripslashes_deep($value) {
	$value = is_array($value) ?
		array_map('stripslashes_deep', $value) :
			(is_null($value) ? NULL : stripslashes($value));

	return $value;
}

/**
 *  Add slash for variable & array 
 *
 *
 *
 * @param $value value to add slashes (array or string)
 * @return addslashes value
 *
 */
function addslashes_deep($value) {
	$value = is_array($value) ?
		array_map('addslashes_deep', $value) :
			(is_null($value) ? NULL : addslashes($value));
	return $value;
}

/**
 * Prevent from XSS
 * Clean code 
 *
 *
 * @param $value item to prevent (array or string)
 * @return clean item
 $
 * @see unclean_cross_side_scripting_deep*
 */
function clean_cross_side_scripting_deep($value) {
	$in=array("<",">");
	$out=array("&lt;","&gt;");
	$value = is_array($value) ?
		array_map('clean_cross_side_scripting_deep', $value) :
			(is_null($value) ? NULL : str_replace($in,$out,$value));
	return $value;
}
/**
 *  
 *  Invert fonction from clean_cross_side_scripting_deep
 *
 *
 * @param $value item to unclean from clean_cross_side_scripting_deep
 * @return unclean item
 * @see clean_cross_side_scripting_deep
 *
 */
function unclean_cross_side_scripting_deep($value) {
	$in=array("<",">");
	$out=array("&lt;","&gt;");
	$value = is_array($value) ?
		array_map('clean_cross_side_scripting_deep', $value) :
			(is_null($value) ? NULL : str_replace($out,$in,$value));
	return $value;
}
/**
 *  Utf8 decode for variable & array
 *
 *
 *
 * @param $value item to utf8_decode (array or string)
 * @return decoded item
 *
 */
function utf8_decode_deep($value) {
	$value = is_array($value) ?
		array_map('utf8_decode_deep', $value) :
			(is_null($value) ? NULL : utf8_decode($value));
	return $value;

}

/**
 *  Resume test for followup
 *
 * @param $string
 * @param $length
 * @return cut string
 *
 */
function resume_text($string,$length=255){

	if (strlen($string)>$length){
		$lastchar=substr($string,$length-1,1);
		// last char is not utf8 encoded
		if ($lastchar==utf8_decode($lastchar))
			$string=substr($string,0,$length)."&nbsp;(...)";
		else $string=substr($string,0,$length-1)."&nbsp;(...)";
	}

	return $string;
}






/**
 * Convert a date YY-MM-DD to DD-MM-YY for display in a html table
 *
 *
 *
 * @param $time
 * @return $time or $date
 *
 */
function convDateTime($time) { 
	global $cfg_glpi;
	if (is_null($time)) return $time;
	if ($cfg_glpi["dateformat"]!=0) {
		$date = substr($time,8,2)."-";        // jour 
		$date = $date.substr($time,5,2)."-";  // mois 
		$date = $date.substr($time,0,4). " "; // annÃ©e 
		$date = $date.substr($time,11,5);     // heures et minutes 
		return $date; 
	}else {
		return $time;
	}
}

/**
 * Convert a date YY-MM-DD to DD-MM-YY for calendar
 *
 *
 *
 * @param $time
 * @return $time or $date
 *
 */
function convDate($time) { 
	global $cfg_glpi;
	if (is_null($time)) return $time;
	if ($cfg_glpi["dateformat"]!=0) {
		$date = substr($time,8,2)."-";        // jour 
		$date = $date.substr($time,5,2)."-";  // mois 
		$date = $date.substr($time,0,4); // annÃ©e 
		//$date = $date.substr($time,11,5);     // heures et minutes 
		return $date; 
	}else {
		return $time;
	}
}
/**
 *  Send a file to the navigator
 *
 * @param $file
 * @param $filename
 * @return nothing
 */
function sendFile($file,$filename){
	global $db;

	// Test sécurité
	if (ereg("\.\.",$file)){
		session_start();
		echo "Security attack !!!";
		logEvent($file, "sendFile", 1, "security", $_SESSION["glpiname"]." try to get a non standard file.");
		return;
	}
	if (!file_exists($file)){
		echo "Error file $file does not exist";
		return;
	} else {
		$splitter=split("/",$file);
		$filedb=$splitter[count($splitter)-2]."/".$splitter[count($splitter)-1];
		$query="SELECT mime from glpi_docs WHERE filename LIKE '$filedb'";
		$result=$db->query($query);
		$mime="application/octetstream";
		if ($result&&$db->numrows($result)==1){
			$mime=$db->result($result,0,0);

		} else {
			// fichiers DUMP SQL et XML
			if ($splitter[count($splitter)-2]=="dump"){
				$splitter2=split("\.",$file);
				switch ($splitter2[count($splitter2)-1]) {
					case "sql" : 
						$mime="text/x-sql";
					break;
					case "xml" :
						$mime="text/xml";
					break;
				}
			} else {
				// Cas particulier
				switch ($splitter[count($splitter)-2]) {
					case "SQL" : 
						$mime="text/x-sql";
					break;
					case "XML" :
						$mime="text/xml";
					break;
				}
			}

		}

		// Now send the file with header() magic
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
		header('Pragma: private'); /// IE BUG + SSL
		//header('Pragma: no-cache'); 
		header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
		header("Content-disposition: filename=\"$filename\"");
		header("Content-type: ".$mime);





		$f=fopen($file,"r");

		if (!$f){
			echo "Error opening file $file";
		} else {
			// Pour que les \x00 ne devienne pas \0
			$mc=get_magic_quotes_runtime();
			if ($mc) @set_magic_quotes_runtime(0); 

			echo fread($f, filesize($file));

			if ($mc) @set_magic_quotes_runtime($mc); 
		}

	}
}

/**
 * Convert a value in byte, kbyte, megabyte etc...
 *
 *
 *
 * @param $val
 * @return $val
 *
 */
function return_bytes_from_ini_vars($val) {
	$val = trim($val);
	$last = strtolower($val{strlen($val)-1});
	switch($last) {
		// Le modifieur 'G' est disponible depuis PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

/**
 * Header redirection hack
 *
 *
 *
 * @param $dest Redirection destination 
 * @return nothing
 *
 */
function glpi_header($dest){
	echo "<script language=javascript>window.location=\"".$dest."\"</script>";
	exit();
}

/**
 * Call cron
 *
 *
 *
 * 
 * @return nothing
 *
 */
function callCron(){
	if (ereg("front",$_SERVER['PHP_SELF'])){
		echo "<div style=\"background-image: url('cron.php');\"></div>";
	} else {
		echo "<div style=\"background-image: url('front/cron.php');\"></div>";
	}
}




/**
 * Get hour from sql
 *
 *
 *
 * @param $time
 * @return  array
 *
 */
function get_hour_from_sql($time){
	$t=explode(" ",$time);
	$p=explode(":",$t[1]);
	return $p[0].":".$p[1];
}

/**
 *  Optimize sql table
 *
 *
 *
 * @return nothing
 *
 */
function optimize_tables (){

	global $db;
	$result=$db->list_tables();
	while ($line = $db->fetch_array($result))
	{
		if (ereg("glpi_",$line[0])){
			$table = $line[0];
			$query = "OPTIMIZE TABLE ".$table." ;";
			$db->query($query);
		}
	}
	$db->free_result($result);
}







//*************************************************************************************************************
// De jolies fonctions pour améliorer l'affichage du texte de la FAQ/knowledgbase
// obsolète since 0.68 but DONT DELETE  THIS SECTION !!
// USED IN THE UPDATE SCRIPT
//************************************************************************************************************

/**
 *Met en "ordre" une chaine avant affichage
 * Remplace trés AVANTAGEUSEMENT nl2br 
 * 
 * @param $pee
 * @param $br
 * 
 * @return $string
 */
function autop($pee, $br=1) {

	// Thanks  to Matthew Mullenweg

	$pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee); // cross-platform newlines
	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
	$pee = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
	if ($br) $pee = preg_replace('|(?<!</p>)\s*\n|', "<br>\n", $pee); // optionally make line breaks
	return $pee;
}


/**
 * Rend une url cliquable htp/https/ftp meme avec une variable Get
 *
 * @param $chaine
 * 
 * 
 * 
 * @return $string
 */
function clicurl($chaine){

	$text=preg_replace("`((?:https?|ftp)://\S+)(\s|\z)`", '<a href="$1">$1</a>$2', $chaine); 

		return $text;
}

/**
 * Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
 *
 * @param $text
 * @param $start
 * @param $end
 * 
 * @return array 
 */
function split_text($text, $start, $end)
{

	// Adapté de PunBB 
	//Copyright (C)  Rickard Andersson (rickard@punbb.org)

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; ++$i)
	{
		$temp = explode($end, $tokens[$i]);
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}



	return array($inside, $outside);
}


/**
 * Replace bbcode in text by html tag
 *
 * @param $string
 * 
 * 
 * 
 * @return $string 
 */
function rembo($string){

	// Adapté de PunBB 
	//Copyright (C)  Rickard Andersson (rickard@punbb.org)

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($string, '[code]') !== false && strpos($string, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($string, '[code]', '[/code]');
		$outside = array_map('trim', $outside);
		$string = implode('<">', $outside);
	}




	$pattern = array('#\[b\](.*?)\[/b\]#s',
			'#\[i\](.*?)\[/i\]#s',
			'#\[u\](.*?)\[/u\]#s',
			'#\[s\](.*?)\[/s\]#s',
			'#\[c\](.*?)\[/c\]#s',
			'#\[g\](.*?)\[/g\]#s',
			//'#\[url\](.*?)\[/url\]#e',
			//'#\[url=(.*?)\](.*?)\[/url\]#e',
			'#\[email\](.*?)\[/email\]#',
			'#\[email=(.*?)\](.*?)\[/email\]#',
			'#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s');


	$replace = array('<strong>$1</strong>',
			'<em>$1</em>',
			'<span class="souligne">$1</span>',
			'<span class="barre">$1</span>',
			'<div align="center">$1</div>',
			'<big>$1</big>',
			// 'truncate_url(\'$1\')',
			//'truncate_url(\'$1\', \'$2\')',
		'<a href="mailto:$1">$1</a>',
		'<a href="mailto:$1">$2</a>',
		'<span style="color: $1">$2</span>');

	// This thing takes a while! :)
			$string = preg_replace($pattern, $replace, $string);



			$string=clicurl($string);

			$string=autop($string);


			// If we split up the message before we have to concatenate it together again (code tags)
			if (isset($inside))
			{
				$outside = explode('<">', $string);
				$string = '';

				$num_tokens = count($outside);

				for ($i = 0; $i < $num_tokens; ++$i)
				{
					$string .= $outside[$i];
					if (isset($inside[$i]))
						$string .= '<br><br><table  class="code" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><b>Code:</b><br><br><pre>'.trim($inside[$i]).'</pre></td></tr></table><br>';
				}
			}






			return $string;
}

// Create SQL search condition
function makeTextSearch($val,$not=0){
	$NOT="";
	if ($not) $NOT= " NOT ";
	
	if ($val=="NULL"||$val=="null") $SEARCH=" IS $NOT NULL ";
	else {
		// Unclean to permit < and > search
		$val=unclean_cross_side_scripting_deep($val);
		$begin=0;
		$end=0;
		if (($length=strlen($val))>0){
			if (($val[0]=='^'))
				$begin=1;
			if ($val[$length-1]=='$')
				$end=1;
		}
		if ($begin||$end) 
			$val=substr($val,$begin,$length-$end-$begin);

		$SEARCH=" $NOT LIKE '".(!$begin?"%":"").$val.(!$end?"%":"")."' ";

	}

	return $SEARCH;
}

function checkNewVersionAvailable($auto=1){
	global $db,$lang,$cfg_glpi;

	if (!haveRight("check_update","r")) return false;	

	if (!$auto) echo "<br>";
	$latest_version = '';

	// Connection directe
	if (empty($cfg_glpi["proxy_name"])){
		if ($fp=@fsockopen("glpi-project.org", 80, $errno, $errstr, 1)){

			$request  = "GET /latest_version HTTP/1.1\r\n";
			$request .= "Host: glpi-project.org\r\n";
			$request .= 'User-Agent: GLPICheckUpdate/'.trim($cfg_glpi["version"])."\r\n";
			$request .= "Connection: Close\r\n\r\n";

			fwrite($fp, $request);
			while (!feof($fp)) {
				$ret=fgets($fp, 128);
				if (!empty($ret))
					$latest_version=$ret;
			}
			fclose($fp);
		}
	} else { // Connection using proxy
		$proxy_cont = ''; //laissez vide

		$proxy_fp = fsockopen($cfg_glpi["proxy_name"], $cfg_glpi["proxy_port"], $errno, $errstr, 1);
		if (!$proxy_fp)    {
			if (!$auto) echo "<div align='center'>".$lang["setup"][311]." ($errstr)</div>";
		} 

		fputs($proxy_fp, "GET http://glpi-project.org/latest_version HTTP/1.0\r\nHost: ".$cfg_glpi["proxy_name"]."\r\n");
		if (!empty($cfg_glpi["proxy_user"]))
			fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode ($cfg_glpi["proxy_user"].":".$cfg_glpi["proxy_password"]) . "\r\n");    // added
		fputs($proxy_fp,"\r\n");
		while(!feof($proxy_fp)) {
			$ret = fread($proxy_fp,128);
			if (!empty($ret))
				$latest_version=$ret;
		}
		fclose($proxy_fp);
	}

	if (strlen(trim($latest_version)) == 0){
		if (!$auto) echo "<div align='center'>".$lang["setup"][304]." ($errstr)</div>";
	} else {			
		$splitted=split("\.",trim($cfg_glpi["version"]));

		if ($splitted[0]<10) $splitted[0].="0";
		if ($splitted[1]<10) $splitted[1].="0";
		$cur_version = $splitted[0]*10000+$splitted[1]*100;
		if (isset($splitted[2])) {
			if ($splitted[2]<10) $splitted[2].="0";
			$cur_version+=$splitted[2];
		}

		$splitted=split("\.",trim($latest_version));

		if ($splitted[0]<10) $splitted[0].="0";
		if ($splitted[1]<10) $splitted[1].="0";

		$lat_version = $splitted[0]*10000+$splitted[1]*100;
		if (isset($splitted[2])) {
			if ($splitted[2]<10) $splitted[2].="0";
			$lat_version+=$splitted[2];
		}

		if ($cur_version < $lat_version){
			if (!$auto) {
				echo "<div align='center'>".$lang["setup"][301]." ".$latest_version."</div>";
				echo "<div align='center'>".$lang["setup"][302]."</div>";
			}

			$query="UPDATE glpi_config SET founded_new_version='".$latest_version."' WHERE ID='1'";
			$db->query($query);

		}  else echo "<div align='center'>".$lang["setup"][303]."</div>";
	} 
	return 1;
}

function cron_check_update(){
	checkNewVersionAvailable(1);
}


/** 
 * Garbage collector for expired file session 
 * 
 **/ 
function cron_session() { 

	// max time to keep the file session 
	$maxlifetime = session_cache_expire(); 

	foreach (glob(GLPI_DOC_DIR."/_sessions/sess_*") as $filename) { 
		if (filemtime($filename) + $maxlifetime < time()) { 
			// Delete session file if not delete before 
			@unlink($filename); 
		} 
	} 
	return true; 
} 


?>
