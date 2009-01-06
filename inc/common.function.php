<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

//******************************************************************************************************
//******************************************************************************************************
//********************************  Fonctions diverses ************************************
//******************************************************************************************************
//******************************************************************************************************

	/**
	* Set the directory where are store the session file
	*
	**/
	function setGlpiSessionPath(){
		if (ini_get("session.save_handler")=="files") {
			session_save_path(GLPI_SESSION_DIR);
	       }
	}
	/**
	* Start the GLPI php session 
	*
	**/
	function startGlpiSession(){
		if(!session_id()){@session_start();}
		// Define current time for sync of action timing
		$_SESSION["glpi_currenttime"]=date("Y-m-d H:i:s");	
	}

	/**
	* Is GLPI used in mutli-entities mode ?
	*@return boolean
	* 
	**/
	function isMultiEntitiesMode(){
		if (!isset($_SESSION['glpi_multientitiesmode'])){
			if (countElementsInTable("glpi_entities")>0){
				$_SESSION['glpi_multientitiesmode']=1;
			} else {
				$_SESSION['glpi_multientitiesmode']=0;
			}
		}
		return $_SESSION['glpi_multientitiesmode'];
	}
	/**
	* Is the user have right to see all entities ?
	*@return boolean
	* 
	**/
	function isViewAllEntities(){
		return ((countElementsInTable("glpi_entities")+1)==count($_SESSION["glpiactiveentities"]));
	}
	/**
	* Log a message in log file
	*@param $name string: name of the log file
	*@param $text string: text to log 
	*@param $force boolean: force log in file not seeing use_errorlog config
	* 
	**/
	function logInFile($name,$text,$force=false){
		global $CFG_GLPI;
		if ($CFG_GLPI["use_errorlog"]||$force){ 
			error_log(convDateTime(date("Y-m-d H:i:s"))."\n".$text,3,GLPI_LOG_DIR."/".$name.".log");
		}
	}
	
	/**
	* Specific error handler
	*@param $errno integer: level of the error raised.
	*@param $errmsg string: error message.
	*@param $filename string: filename that the error was raised in.
	*@param $linenum integer: line number the error was raised at. 
	*@param $vars array: that points to the active symbol table at the point the error occurred.
	*
	**/
	function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars){
		global $CFG_GLPI;
		// Date et heure de l'erreur
		//$dt = date("Y-m-d H:i:s (T)");
		$errortype = array (
			E_ERROR              => 'Error',
			E_WARNING            => 'Warning',
			E_PARSE              => 'Parsing Error',
			E_NOTICE             => 'Notice',
			E_CORE_ERROR         => 'Core Error',
			E_CORE_WARNING       => 'Core Warning',
			E_COMPILE_ERROR      => 'Compile Error',
			E_COMPILE_WARNING    => 'Compile Warning',
			E_USER_ERROR         => 'User Error',
			E_USER_WARNING       => 'User Warning',
			E_USER_NOTICE        => 'User Notice',
			E_STRICT             => 'Runtime Notice',
			// Need php 5.2.0
			4096 /*E_RECOVERABLE_ERROR*/  => 'Catchable Fatal Error',
			// Need php 5.3.0
			8192 /* E_DEPRECATED */ => 'Deprecated function',
			16384 /* E_USER_DEPRECATED */ => 'User deprecated function'
			);			
		// Les niveaux qui seront enregistrés
		$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
			
		$err = $errortype[$errno] . "($errno): $errmsg\n";
		if (in_array($errno, $user_errors)) {
			$err .= "Variables:".wddx_serialize_value($vars,"Variables")."\n";
		}
		if (function_exists("debug_backtrace")) {
			$err .= "Backtrace :\n";
			$traces=debug_backtrace();
			foreach ($traces as $trace) {
				if (isset($trace["file"]) && isset($trace["line"])) {
					$err .= $trace["file"] . ":" . $trace["line"] . "\t\t"
						. (isset($trace["class"]) ? $trace["class"] : "")
						. (isset($trace["type"]) ? $trace["type"] : "")
						. (isset($trace["function"]) ? $trace["function"]."()" : "")
						. "\n";
				}
			}
		} else {
			$error .= "Script: $filename, Line: $linenum\n" ; 
		}
		
		// sauvegarde de l'erreur, et mail si c'est critique
		logInFile("php-errors",$err."\n");
		
		if (!isCommandLine()){
			echo '<div style="position:fload-left; background-color:red; z-index:10000"><strong>PHP ERROR: </strong>';
			echo $errmsg." in ".$filename." at line ".$linenum;
			echo '</div>';
		} else {
			echo "PHP ERROR: ".$errmsg." in ".$filename." at line ".$linenum."\n";
		}
	}
	/**
	* Is the script launch in Command line ?
	*@return boolean
	*
	**/
	function isCommandLine(){
		return (!isset($_SERVER["SERVER_NAME"]));
	}

	/**
	* substr function for utf8 string
	*@param $str string: string
	*@param $start integer: start of the result substring
	*
	*@return substring
	**/
	function utf8_substr($str,$start){
   		preg_match_all("/./su", $str, $ar);

   		if(func_num_args() >= 3) {
       		$end = func_get_arg(2);
       		return join("",array_slice($ar[0],$start,$end));
   		} else {
       		return join("",array_slice($ar[0],$start));
   		}
	}
	/**
	* strlen function for utf8 string
	* @param $str string: string 
	* @return length of the string
	**/
	function utf8_strlen($str){
    	$i = 0;
    	$count = 0;
    	$len = strlen ($str);
    	while ($i < $len){
    		$chr = ord ($str[$i]);
    		$count++;
    		$i++;
    		if ($i >= $len){
        		break;
    		}
    		if ($chr & 0x80){
        		$chr <<= 1;
        		while ($chr & 0x80){
        			$i++;
        			$chr <<= 1;
        		}
    		}
    	}
    	return $count;
	}
	/**
	* html_entity_decode function for utf8 string
	* @param $string string: string
	*
	* @return converted string
	**/
	function utf8_html_entity_decode($string){
		static $trans_tbl;
	
		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'code2utf(\\1)', $string);
	
		// replace literal entities
		if (!isset($trans_tbl)){
			$trans_tbl = array();
	
			foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key){
			$trans_tbl[$key] = utf8_encode($val);
			}
		}   
		return strtr($string, $trans_tbl);
	}

	/** Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
	* @param $num integer: character code
	*/
	function code2utf($num){
	    if ($num < 128) return chr($num);
	    if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
	    if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	    if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	    return '';
	}

	/**
 	* Clean log cron function
 	*
 	**/
	function cron_logs(){
		global $CFG_GLPI,$DB;

		// Expire Event Log
		if ($CFG_GLPI["expire_events"] > 0) {
			$secs = $CFG_GLPI["expire_events"] * DAY_TIMESTAMP;
			$query_exp = "DELETE FROM glpi_event_log WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";
			$DB->query($query_exp);
			logInFile("cron","Cleaning log events passed from more than ".$CFG_GLPI["expire_events"]." days\n");
		}
	}

	/**
 	* Clean log cron function
 	*
 	**/
	function cron_optimize(){
		global $CFG_GLPI,$DB;

		logInFile("cron","Start optimize tables\n");
		optimize_tables();
		logInFile("cron","Optimize tables done\n");
	}

	/**
 	* Clean cache cron function
 	*
 	**/
	function cron_cache(){
		global $CFG_GLPI;
		$max_recursion=5;
		$lifetime=DEFAULT_CACHE_LIFETIME;
		$max_size=$CFG_GLPI['cache_max_size']*1024*1024;
		while ($max_recursion>0&&(($size=filesizeDirectory(GLPI_CACHE_DIR))>$max_size)){
			$cache_options = array(
				'cacheDir' => GLPI_CACHE_DIR,
				'lifeTime' => $lifetime,
				'automaticSerialization' => true,
				'caching' => $CFG_GLPI["use_cache"],
				'hashedDirectoryLevel' => 2,
				'fileLocking' => CACHE_FILELOCKINGCONTROL,
				'writeControl' => CACHE_WRITECONTROL,
				'readControl' => CACHE_READCONTROL,
			);
			$cache = new Cache_Lite($cache_options);
			$cache->clean(false,"old");

			logInFile("cron","Clean cache created since more than $lifetime seconds\n");
			$lifetime/=2;
			$max_recursion--;
		}
		if ($max_recursion>0){
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * Garbage collector for expired file session
	 *
	 **/
	function cron_session(){
		global $CFG_GLPI;
		// max time to keep the file session
		$maxlifetime = session_cache_expire();
		$do=false;			
		foreach (glob(GLPI_SESSION_DIR."/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				// Delete session file if not delete before
				@unlink($filename);
				$do=true;
			}
		}
		if ($do){
			logInFile("cron","Clean session files created since more than $maxlifetime seconds\n");
		}
		return true;
	}

/**
 * Get the filesize of a complete directory (from php.net)
 *
 * @param $path string: directory or file to get size
 * @return size of the $path
 **/
function filesizeDirectory($path){
	if(!is_dir($path)){
		return filesize($path);
	}
   	if ($handle = opendir($path)) {
       	$size = 0;
       	while (false !== ($file = readdir($handle))) {
	    	if($file!='.' && $file!='..'){
	        	$size += filesize($path.'/'.$file);
	            $size += filesizeDirectory($path.'/'.$file);
	        }
	    }
	    closedir($handle);
       	return $size;
   	}
}


/**
 * Clean cache function
 *
 * @param $group string: group to clean (if not set clean all the cache)
 * @return nothing
 **/
function cleanCache($group=""){

	include_once (GLPI_CACHE_LITE_DIR."/Lite.php");

	$cache_options = array(
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => 0,
		'hashedDirectoryLevel' => 2,
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
		);
	$CACHE = new Cache_Lite($cache_options);
	if (empty($group)){
		$CACHE->clean();
	} else {
		$CACHE->clean($group,"ingroup");
	}

}

/**
 * Clean cache function for relations using a specific table
 *
 * @param $table string: table used. Need to clean all cache using this table
 * @return nothing
 **/
function cleanRelationCache($table){
	global $LINK_ID_TABLE,$CFG_GLPI;
	if ($CFG_GLPI["use_cache"]){
		$RELATION=getDbRelations();
		if (isset($RELATION[$table])){
			foreach ($RELATION[$table] as $tablename => $field){
				if ($key=array_search($tablename,$LINK_ID_TABLE)){
					cleanCache("GLPI_$key");
				}
			}
		}
	}
}

/**
 * Clean all dict cache
 *
 * @param $group string: cache group
 * @param $item  string: remove cache for item
 * @return nothing
 **/
function cleanAllItemCache($item,$group){
	global $CFG_GLPI;
	if ($CFG_GLPI["use_cache"]){
		foreach ($CFG_GLPI["languages"] as $key => $val){
			// clean main sheet
			$CFG_GLPI["cache"]->remove($item."_".$key,$group,true);
		}
	}
}


/**
 * Get the SEARCH_OPTION array using cache
 *
 * @return the SEARCH_OPTION array
 **/
function getSearchOptions(){
	global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;
	$options = array(
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => DEFAULT_CACHE_LIFETIME,
		'automaticSerialization' => true,
		'caching' => $CFG_GLPI["use_cache"],
		'hashedDirectoryLevel' => 2,
		'masterFile' => GLPI_ROOT . "/inc/search.constant.php",
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
	);
	$cache = new Cache_Lite_File($options);

	// Set a id for this cache : $file
	if (!($SEARCH_OPTION = $cache->get("OPTIONS","GLPI_SEARCH_".$_SESSION['glpilanguage']))) {
		// Cache miss !
		// Put in $SEARCH_OPTION datas to put in cache
		include (GLPI_ROOT . "/inc/search.constant.php");
		$cache->save($SEARCH_OPTION,"OPTIONS","GLPI_SEARCH_".$_SESSION['glpilanguage']);
	}
	
	$plugsearch=getPluginSearchOption();
	if (count($plugsearch)){
		foreach ($plugsearch as $key => $val){
			if (!isset($SEARCH_OPTION[$key])){
				$SEARCH_OPTION[$key]=array();
			} else {
				$SEARCH_OPTION[$key]+=array('plugins'=>$LANG["common"][29]);
			}

			$SEARCH_OPTION[$key]+=$val;
		}
	}

	return $SEARCH_OPTION;
}

/**
 * Get the $RELATION array using cache. It's defined all relations between tables in the DB.
 *
 * @return the $RELATION array
 **/
function getDbRelations(){
	global $CFG_GLPI;
	$options = array(
		'cacheDir' => GLPI_CACHE_DIR,
		'lifeTime' => DEFAULT_CACHE_LIFETIME,
		'automaticSerialization' => true,
		'caching' => $CFG_GLPI["use_cache"],
		'hashedDirectoryLevel' => 2,
		'masterFile' => GLPI_ROOT . "/inc/relation.constant.php",
		'fileLocking' => CACHE_FILELOCKINGCONTROL,
		'writeControl' => CACHE_WRITECONTROL,
		'readControl' => CACHE_READCONTROL,
	);
	$cache = new Cache_Lite_File($options);

	// Set a id for this cache : $file
	if (!($RELATION = $cache->get("OPTIONS","GLPI_RELATION"))) {
		// Cache miss !
		// Put in $SEARCH_OPTION datas to put in cache
		include (GLPI_ROOT . "/inc/relation.constant.php");
		$cache->save($RELATION,"OPTIONS","GLPI_RELATION");
	}

	// Add plugins relations
	$plug_rel=getPluginsDatabaseRelations();
	if (count($plug_rel)>0){
		$RELATION=array_merge_recursive($RELATION,$plug_rel);
	}
	
	return $RELATION;
}

/**
 * Check Write Access to a directory
 *
 * @param $dir string: directory to check
 * @return 2 : creation error 1 : delete error 0: OK
 **/
function testWriteAccessToDirectory($dir){

	$rand=rand();
	// Check directory creation which can be denied by SElinux
	$sdir = sprintf("%s/test_glpi_%08x", $dir, $rand);
	if (!mkdir($sdir)) {
		return 4;
	}
	else if (!rmdir($sdir)) {
		return 3;
	}

	// Check file creation
	$path = sprintf("%s/test_glpi_%08x.txt", $dir, $rand);
	$fp = fopen($path,'w');

	if (empty($fp)) {
		return 2;
	}
	else {
		$fw = fwrite($fp,"This file was created for testing reasons. ");
		fclose($fp);
		$delete = unlink($path);
		if (!$delete) {
			return 1;
		}
	}
	return 0;
}

/**
* Common Checks needed to use GLPI
*
* @return 2 : creation error 1 : delete error 0: OK
**/
function commonCheckForUseGLPI(){
	global $LANG;

	// memory test
	echo "<tr class='tab_bg_1'><td><b>".$LANG["install"][86]."</b></td>";

	$mem=ini_get("memory_limit");

	// Cette bidouille me plait pas
	//if(empty($mem)) {$mem=get_cfg_var("memory_limit");}  // Sous Win l'ini_get ne retourne rien.....

	preg_match("/([0-9]+)([KMG]*)/",$mem,$matches);

	$mem="";
	// no K M or G
	if (isset($matches[1])){ 
		if (!isset($matches[2])){
			$mem=$matches[1];
		} else {
			$mem=$matches[1];
			switch ($matches[2]){
				case "G" : $mem*=1024;
				case "M" : $mem*=1024;
				case "K" : $mem*=1024;
				break;
			}
		}
	}

	if( $mem == "" ){          // memory_limit non compilé -> no memory limit
		echo "<td>".$LANG["install"][95]." - ".$LANG["install"][89]."</td></tr>";
	}
	else if( $mem == "-1" ){   // memory_limit compilé mais illimité
		echo "<td>".$LANG["install"][96]." - ".$LANG["install"][89]."</td></tr>";
	}
	else{	
		if ($mem<64*1024*1024){ // memoire insuffisante
			echo "<td  class='red'><b>".$LANG["install"][87]." $mem octets</b><br>".$LANG["install"][88]."<br>".$LANG["install"][90]."</td></tr>";
		}
		else{ // on a sufisament de mémoire on passe à la suite
			echo "<td>".$LANG["install"][91]." - ".$LANG["install"][89]."</td></tr>";
		}
	}
	return checkWriteAccessToDirs();
}

/**
* Check Write Access to needed directories
*
* @return 2 : creation error 1 : delete error 0: OK
**/
function checkWriteAccessToDirs(){
	global $LANG;
	$dir_to_check=array(
		GLPI_DUMP_DIR => $LANG["install"][16],
		GLPI_DOC_DIR => $LANG["install"][21],
		GLPI_CONFIG_DIR => $LANG["install"][23],
		GLPI_SESSION_DIR => $LANG["install"][50],
		GLPI_CRON_DIR => $LANG["install"][52],
		GLPI_CACHE_DIR => $LANG["install"][99]
	);
	$error=0;	
	foreach ($dir_to_check as $dir => $message){
		echo "<tr class='tab_bg_1'><td><strong>".$message."</strong></td>";
		$tmperror=testWriteAccessToDirectory($dir);
	
		switch($tmperror){
			// Error on creation
			case 4 :
				echo "<td><p class='red'>".$LANG["install"][100]."</p> ".$LANG["install"][97]."'".$dir."'. ".$LANG["install"][98]."</td></tr>";
				$error=2;
				break;
			case 3 :
				echo "<td><p class='red'>".$LANG["install"][101]."</p> ".$LANG["install"][97]."'".$dir."'. ".$LANG["install"][98]."</td></tr>";
				$error=1;
				break;
			// Error on creation
			case 2 :
				echo "<td><p class='red'>".$LANG["install"][17]."</p> ".$LANG["install"][97]."'".$dir."'. ".$LANG["install"][98]."</td></tr>";
				$error=2;
				break;
			case 1 :
				echo "<td><p class='red'>".$LANG["install"][19]."</p> ".$LANG["install"][97]."'".$dir."'. ".$LANG["install"][98]."</td></tr>";
				$error=1;
				break;
			default :
				echo "<td>".$LANG["install"][20]."</td></tr>";
				break;
		}
	}
	
	// Only write test for GLPI_LOG as SElinux prevent removing log file.
	echo "<tr class='tab_bg_1'><td><strong>".$LANG["install"][53]."</strong></td>";
	if (error_log("Test\n", 3, GLPI_LOG_DIR."/php-errors.log")) {
		echo "<td>".$LANG["install"][22]."</td></tr>";
	} else {
		echo "<td><p class='red'>".$LANG["install"][19]."</p> ".$LANG["install"][97]."'".GLPI_LOG_DIR."'. ".$LANG["install"][98]."</td></tr>";
		$error=1;
	}
	return $error;
}

/**
 * Strip slash  for variable & array
 *
 * @param $value array or string: item to stripslashes (array or string)
 * @return stripslashes item
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
 * @param $value array or string: value to add slashes (array or string)
 * @return addslashes value
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
 * @param $value array or string: item to prevent (array or string)
 * @return clean item
 *
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
 *  Invert fonction from clean_cross_side_scripting_deep
 *
 * @param $value array or string: item to unclean from clean_cross_side_scripting_deep
 * @return unclean item
 * @see clean_cross_side_scripting_deep
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
 * @param $value array or string: item to utf8_decode (array or string)
 * @return decoded item
 */
function utf8_decode_deep($value) {
	$value = is_array($value) ?
		array_map('utf8_decode_deep', $value) :
			(is_null($value) ? NULL : utf8_decode($value));
	return $value;
}

/**
 *  Resume text for followup
 *
 * @param $string string: string to resume
 * @param $length integer: resume length
 * @return cut string
 */
function resume_text($string,$length=255){
	
	if (strlen($string)>$length){
		$string=utf8_substr($string,0,$length)."&nbsp;(...)";
	}
	return $string;
}

/**
 *  Format mail row
 *
 * @param $string string: label string
 * @param $value string: value string
 * @return string
 */
function mailRow($string,$value){
	$row=utf8_str_pad( $string . ': ',25,' ', STR_PAD_RIGHT).$value."\n";

	return $row;
}

/**
 *  Replace str_pad()
 *  who bug with utf8
 *
 * @param $ps_input string: input string
 * @param $pn_pad_length integer: padding length
 * @param $ps_pad_string string: padding string
 * @param $pn_pad_type: integer: padding type
 * @return string
 */
function utf8_str_pad($ps_input, $pn_pad_length, $ps_pad_string = " ", $pn_pad_type = STR_PAD_RIGHT) {
	$ret = "";
	
	$hn_length_of_padding = $pn_pad_length - utf8_strlen($ps_input);
	$hn_psLength = utf8_strlen($ps_pad_string); // pad string length
	
	if ($hn_psLength <= 0 || $hn_length_of_padding <= 0) {
		// Padding string equal to 0:
		//
		$ret = $ps_input;
	} else {
		$hn_repeatCount = floor($hn_length_of_padding / $hn_psLength); // how many times repeat
	
		if ($pn_pad_type == STR_PAD_BOTH) {
			$hs_lastStrLeft = "";
			$hs_lastStrRight = "";
			$hn_repeatCountLeft = $hn_repeatCountRight = ($hn_repeatCount - $hn_repeatCount % 2) / 2;
			
			$hs_lastStrLength = $hn_length_of_padding - 2 * $hn_repeatCountLeft * $hn_psLength; // the rest length to pad
			$hs_lastStrLeftLength = $hs_lastStrRightLength = floor($hs_lastStrLength / 2);      // the rest length divide to 2 parts
			$hs_lastStrRightLength += $hs_lastStrLength % 2; // the last char add to right side
			
			$hs_lastStrLeft = utf8_substr($ps_pad_string, 0, $hs_lastStrLeftLength);
			$hs_lastStrRight = utf8_substr($ps_pad_string, 0, $hs_lastStrRightLength);
			
			$ret = str_repeat($ps_pad_string, $hn_repeatCountLeft) . $hs_lastStrLeft;
			$ret .= $ps_input;
			$ret .= str_repeat($ps_pad_string, $hn_repeatCountRight) . $hs_lastStrRight;
		} else {
			$hs_lastStr = utf8_substr($ps_pad_string, 0, $hn_length_of_padding % $hn_psLength); // last part of pad string
		
			if ($pn_pad_type == STR_PAD_LEFT){
				$ret = str_repeat($ps_pad_string, $hn_repeatCount) . $hs_lastStr . $ps_input;
			} else {
				$ret = $ps_input . str_repeat($ps_pad_string, $hn_repeatCount) . $hs_lastStr;
			}
		}
	}
	
	return $ret;
}



//  Inspired From SPIP
// Suppression basique et brutale de tous les <...>
/*function removeTags($string, $rempl = "") {
	$string = preg_replace(",<[^>]*>,U", $rempl, $string);
	// ne pas oublier un < final non ferme
	// mais qui peut aussi etre un simple signe plus petit que
	$string = str_replace('<', ' ', $string);
	return $string;
}

//  Inspired from SPIP
/*function textBrut($string) {

	$string = preg_replace("/\s+/u", " ", $string);
	$string = preg_replace("/<(p|br)( [^>]*)?".">/i", "\n\n", $string);
	$string = preg_replace("/^\n+/", "", $string);
	$string = preg_replace("/\n+$/", "", $string);
	$string = preg_replace("/\n +/", "\n", $string);
	$string = removeTags($string);
	$string = preg_replace("/(&nbsp;| )+/", " ", $string);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$string = str_replace("&#8217;","'",$string);
	return $string;
}

*/

/**
 * Clean display value deleting html tags
 *
 *@param $value string: string value
 *
 *@return clean value
 **/
function html_clean($value){
//  Inspired from SPIP and http://fr3.php.net/strip-tags
/*
	$search=array(
			"/<a[^>]+>/i",
			"/<img[^>]+>/i",
			"/<span[^>]+>/i",
			"/<\/span>/i",
			"/<\/a>/i",
			"/<strong>/i",
			"/<\/strong>/i",
			"/<small>/i",
			"/<\/small>/i",
			"/<i>/i",
			"/<\/i>/i",
			"/<br>/i",
			"/<br \/>/i",
			"/&nbsp;;/",
			"/&nbsp;/",
			"/<p>/i",
			"/<\/p>/i",
			"/<div[^>]+>/i",
			"/<\/div>/i",
			"/<ul>/i",
			"/<\/ul>/i",
			"/<li>/i",
			"/<\/li>/i",
			"/<ol>/i",
			"/<\/ol>/i",
			"/<h[0-9]>/i",
			"/<\/h[0-9]>/i",
			"/<font[^>]+>/i",
			"/<\/font>/i",

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
			"",
			" ",
			" ",
			"",
			"\n",
			"",
			"\n",
			"",
			"\n",
			"",
			"\n",
			"",
			"\n",
			"",
			"\n",
			"",
			"",
		      );
	
	$value=preg_replace($search,$replace,$value);
	return trim($value);
*/



	$search = array('@<script[^>]*?>.*?</script[^>]*?>@si',  // Strip out javascript
               '@<style[^>]*?>.*?</style[^>]*?>@siU',    // Strip style tags properly
               '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
               '@<![\s\S]*?--[ \t\n\r]*>@'        // Strip multi-line comments including CDATA
	);
	$value = preg_replace($search, ' ', $value);

	$value = preg_replace("/(&nbsp;| )+/", " ", $value);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$value = str_replace("&#8217;","'",$value);

	$value = preg_replace("/<(p|br)( [^>]*)?".">/i", "\n", $value);

	$value = preg_replace("/ +/u", " ", $value);
//	$value = preg_replace("/^\n+/", " ", $value);
//	$value = preg_replace("/\n+$/", " ", $value);

	$value = preg_replace("/\n{2,}/", "\n\n", $value,-1);

	return trim($value);
}


/**
 * Convert a date YY-MM-DD HH:MM to DD-MM-YY HH:MM for display in a html table
 *
 * @param $time datetime: datetime to convert 
 * @return $time or $date
 */
function convDateTime($time) { 
	if (is_null($time)) return $time;

	if (!isset($_SESSION["glpidateformat"])){
		$_SESSION["glpidateformat"]=0;
	}

	
	switch ($_SESSION['glpidateformat']){
		case 1 : // DD-MM-YYYY
			$date = substr($time,8,2)."-";        // day 
			$date .= substr($time,5,2)."-";  // month
			$date .= substr($time,0,4). " "; // year
			$date .= substr($time,11,5);     // hours and minutes
			return $date; 
			break;
		case 2 : // MM-DD-YYYY
			$date = substr($time,5,2)."-";  // month
			$date .= substr($time,8,2)."-";        // day 
			$date .= substr($time,0,4). " "; // year
			$date .= substr($time,11,5);     // hours and minutes
			return $date; 
			break;
		default : // YYYY-MM-DD
			if (strlen($time)>16){
				return substr($time,0,16);
			}
			return $time;
			break;
	}
}

/**
 * Convert a date YY-MM-DD to DD-MM-YY for calendar
 *
 * @param $time date: date to convert 
 * @return $time or $date
 */
function convDate($time) { 
	global $CFG_GLPI;
	if (is_null($time)) return $time;

	if (!isset($_SESSION["glpidateformat"])){
		$_SESSION["glpidateformat"]=0;
	}

	switch ($_SESSION['glpidateformat']){
		case 1 : // DD-MM-YYYY
			$date = substr($time,8,2)."-";        // day 
			$date .= substr($time,5,2)."-";  // month
			$date .= substr($time,0,4); // year
			return $date; 
			break;
		case 2 : // MM-DD-YYYY
			$date = substr($time,5,2)."-";  // month
			$date .= substr($time,8,2)."-";        // day 
			$date .= substr($time,0,4); // year
			return $date; 
			break;
		default : // YYYY-MM-DD
			if (strlen($time)>10){
				return substr($time,0,10);
			}
			return $time;
			break;
	}
}

/**
 * Convert a number to correct display
 *
 * @param $number float: Number to display
 * @param $edit boolean: display number for edition ? (id edit use . in all case)
 * @param $forcedecimal integer: Force decimal number (do not use default value)
 *
 * @return formatted number
 */
function formatNumber($number,$edit=false,$forcedecimal=-1) { 
	global $CFG_GLPI;

	// Php 5.3 : number_format() expects parameter 1 to be double,
	if ($number=="") {
		$number=0;	
	}
	
	$decimal=$CFG_GLPI["decimal_number"];
	if ($forcedecimal>=0){
		$decimal=$forcedecimal;
	}

	// Edit : clean display for mysql
	if ($edit){
		return number_format($number,$decimal,'.','');
	}

	// Display : clean display
	switch ($_SESSION['glpinumberformat']){
		case 2: // Other French
			return number_format($number,$decimal,',',' ');
			break;
		case 0: // French
			return number_format($number,$decimal,'.',' ');
			break;
		default: // English
			return number_format($number,$decimal,'.',',');
			break;
	}	
}

/**
 *  Send a file to the navigator
 *
 * @param $file string: storage filename
 * @param $filename string: file title
 * @return nothing
 */
function sendFile($file,$filename){
	global $DB;

	// Test securite : document in DOC_DIR
	$tmpfile=str_replace(GLPI_DOC_DIR,"",$file);
	if (strstr($tmpfile,"..")){
		echo "Security attack !!!";
		logEvent($file, "sendFile", 1, "security", $_SESSION["glpiname"]." try to get a non standard file.");
		return;
	}

	if (!file_exists($file)){
		echo "Error file $file does not exist";
		return;
	} else {
		$splitter=explode("/",$file);
		$filedb=$splitter[count($splitter)-2]."/".$splitter[count($splitter)-1];
		$query="SELECT mime from glpi_docs WHERE filename LIKE '$filedb'";
		$result=$DB->query($query);
		$mime="application/octetstream";
		if ($result&&$DB->numrows($result)==1){
			$mime=$DB->result($result,0,0);
		} else {
			// fichiers DUMP SQL et XML
			if ($splitter[count($splitter)-2]=="dump"){
				$splitter2=explode(".",$file);
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
 * @param $val string: config value (like 10k, 5M)
 * @return $val
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
 * @param $dest string: Redirection destination 
 * @return nothing
 */
function glpi_header($dest){
	$toadd='';
	if (!strpos($dest,"?")){
		$toadd='?tokonq='.getRandomString(5);
	} 	
	echo "<script language=javascript>
		NomNav = navigator.appName;
		if (NomNav=='Konqueror'){
			window.location=\"".$dest.$toadd."\";
		} else {
			window.location=\"".$dest."\";
		}
	</script>";
	exit();
}

/**
 * Call cron without time check
 *
 * @return boolean : true if launched
 */
function callCronForce(){

	global $CFG_GLPI;
	
	$path=$CFG_GLPI['root_doc']."/front/cron.php";

	echo "<div style=\"background-image: url('$path');\"></div>";
	return true;		
}

/**
 * Call cron if time since last launch elapsed
 *
 * @return nothing
 */
function callCron(){
	
	if (isset($_SESSION["glpicrontimer"])){

		// call function callcron() every 5min
		if ((time()-$_SESSION["glpicrontimer"])>300){
			if (callCronForce()) {
				// Restart timer
				$_SESSION["glpicrontimer"]=time();			
			}
		}
	} else {
		// Start timer
		$_SESSION["glpicrontimer"]=time();	
	}
}


/**
 * Get hour from sql
 *
 * @param $time datetime: time
 * @return  array
 */
function get_hour_from_sql($time){
	$t=explode(" ",$time);
	$p=explode(":",$t[1]);
	return $p[0].":".$p[1];
}

/**
 *  Optimize sql table
 *
 * @param $progress_fct function to call to display progress message
 *  
 * @return nothing
 */
function optimize_tables ($progress_fct=NULL){

	global $DB;
	
	if (function_exists($progress_fct)) $progress_fct("optimize"); // Start
	$result=$DB->list_tables();
	while ($line = $DB->fetch_array($result))
	{
		if (strstr($line[0],"glpi_")){
			$table = $line[0];
			if (function_exists($progress_fct)) $progress_fct("optimize", $table);
			
			$query = "OPTIMIZE TABLE ".$table." ;";
			$DB->query($query);
		}
	}
	$DB->free_result($result);

	if (function_exists($progress_fct)) $progress_fct("optimize"); // End
}

/**
* Is a string seems to be UTF-8 one ?
* 
* @param $Str string: string to analyze
* @return  boolean 
**/
function seems_utf8($Str) {
	for ($i=0; $i<strlen($Str); $i++) {
		if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
				elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
				elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
				elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
				elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
		else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
					return false;
			}
	}
	return true;
}



/**
 * NOT USED IN CORE - Used for update process - Clean string for knowbase display
 * replace nl2br
 * 
 * @param $pee string: initial string
 * @param $br boolean: make line breaks ?
 * 
 * @return $string
 */
function autop($pee, $br=true) {
	// Thanks  to Matthew Mullenweg

	$pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee); // cross-platform newlines
	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
	$pee = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
	if ($br) {
		$pee = preg_replace('|(?<!</p>)\s*\n|', "<br>\n", $pee); // optionally make line breaks
	}
	return $pee;
}


/**
 * NOT USED IN CORE - Used for update process - make url clickable
 *
 * @param $chaine string: initial string
 * 
 * @return $string
 */
function clicurl($chaine){
	$text=preg_replace("`((?:https?|ftp)://\S+)(\s|\z)`", '<a href="$1">$1</a>$2', $chaine); 

	return $text;
}

/**
 * NOT USED IN CORE - Used for update process - Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
 *
 * @param $text string: initial text
 * @param $start integer: where to start
 * @param $end integer: where to stop
 * 
 * @return array 
 */
function split_text($text, $start, $end){

	// Adaptï¿½de PunBB 
	//Copyright (C)  Rickard Andersson (rickard@punbb.org)

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; ++$i){
		$temp = explode($end, $tokens[$i]);
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}

	return array($inside, $outside);
}


/**
 * NOT USED IN CORE - Used for update process - Replace bbcode in text by html tag
 *
 * @param $string string: initial string
 * 
 * @return formatted string 
 */
function rembo($string){

	// Adaptï¿½de PunBB 
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
	if (isset($inside)){
			$outside = explode('<">', $string);
			$string = '';
			$num_tokens = count($outside);

			for ($i = 0; $i < $num_tokens; ++$i){
					$string .= $outside[$i];
					if (isset($inside[$i]))
						$string .= '<br><br><table  class="code" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><strong>Code:</strong><br><br><pre>'.trim($inside[$i]).'</pre></td></tr></table><br>';
			}
	}

	return $string;
}

/**
* Create SQL search condition
* 
* @param $val string: value to search
* @param $not boolean: is a negative search ?
* 
* @return search string 
**/ 
function makeTextSearch($val,$not=false){
	$NOT="";
	if ($not) {
		$NOT= " NOT ";
	}
	// Unclean to permit < and > search
	$val=unclean_cross_side_scripting_deep($val);
	if ($val=="NULL"||$val=="null") {
		$SEARCH=" IS $NOT NULL ";
	} else {
		$begin=0;
		$end=0;
		if (($length=strlen($val))>0){
			if (($val[0]=='^')){
				$begin=1;
			}
			if ($val[$length-1]=='$'){
				$end=1;
			}
		}
		if ($begin||$end) {
			$val=substr($val,$begin,$length-$end-$begin);
		}

		$SEARCH=" $NOT LIKE '".(!$begin?"%":"").$val.(!$end?"%":"")."' ";

	}

	return $SEARCH;
}

/**
 * Get a web page. Use proxy if configured
 * 
 * @param $url string: to retrieve
 * @param $msgerr string: set if problem encountered
 * @param $rec integer: internal use only Must be 0
 * 
 * @return content of the page (or empty)
 */
function getURLContent ($url, &$msgerr=NULL, $rec=0) {

	global $LANG,$CFG_GLPI;
	
	$content="";

	// Connection directe
	if (empty($CFG_GLPI["proxy_name"])){
		$taburl=parse_url($url);
		if ($fp=@fsockopen($taburl["host"], (isset($taburl["port"]) ? $taburl["port"] : 80), $errno, $errstr, 1)){

			if (isset($taburl["path"]) && $taburl["path"]!='/') {
				// retrieve path + args
				$request  = "GET ".strstr($url, $taburl["path"])." HTTP/1.1\r\n";
			} else {
				$request  = "GET / HTTP/1.1\r\n";
			}
			$request .= "Host: ".$taburl["host"]."\r\n";

		} else {
			if (isset($msgerr)) {
				$msgerr=$LANG["setup"][304] . " ($errstr)"; // failed direct connexion - try proxy
			}
			return "";
		}
		
	} else { // Connection using proxy

		$fp = fsockopen($CFG_GLPI["proxy_name"], $CFG_GLPI["proxy_port"], $errno, $errstr, 1);
		if ($fp) {
	
			$request  = "GET $url HTTP/1.1\r\n";
			$request .= "Host: ".$CFG_GLPI["proxy_name"]."\r\n";
			if (!empty($CFG_GLPI["proxy_user"])) {
				$request .= "Proxy-Authorization: Basic " . base64_encode ($CFG_GLPI["proxy_user"].":".$CFG_GLPI["proxy_password"]) . "\r\n";				
			}
			
		} else {
			if (isset($msgerr)) {
				$msgerr=$LANG["setup"][311] . " ($errstr)"; // failed proxy connexion
			}
			return "";
		}
	}
		 
	$request .= "User-Agent: GLPI/".trim($CFG_GLPI["version"])."\r\n";
	$request .= "Connection: Close\r\n\r\n";
	
	fwrite($fp, $request);
	
	$header=true ;
	$redir=false;
	$errstr="";
	while(!feof($fp)) {
		if ($buf=fgets($fp, 1024)) {
			if ($header) {
				if (strlen(trim($buf))==0) {
					// Empty line = end of header
					$header=false;
				} else if ($redir && preg_match("/^Location: (.*)$/", $buf, $rep)) {
					if ($rec<9) {
						return (getURLContent(trim($rep[1]),$errstr,$rec+1));						
					} else {
						$errstr="Too deep";
						break;
					}
				} else if (preg_match("/^HTTP.*200.*OK/", $buf)) {
					// HTTP 200 = OK
				} else if (preg_match("/^HTTP.*302/", $buf)) {
					// HTTP 302 = Moved Temporarily
					$redir=true;
				} else if (preg_match("/^HTTP/", $buf)) {
					// Other HTTP status = error
					$errstr=trim($buf);
					break;
				}	
			}
			else { 
				// Body
				$content .= $buf;	
			}
		}
	} // eof
	
	fclose($fp);

 	if (empty($content) && isset($msgerr)) {
 		if (empty($errstr)) {
 			$msgerr=$LANG["setup"][312]; // no data
 		}
		else {
			$msgerr=$LANG["setup"][310] . " ($errstr)"; // HTTP error	
		}
	}

	return $content;	
}

/**
* Check if new version is available 
* 
* @param $auto boolean: check done autically ? (if not display result)
* 
* @return string explaining the result 
**/
function checkNewVersionAvailable($auto=true){
	global $DB,$LANG,$CFG_GLPI;

	if (!haveRight("check_update","r")) return false;	

	if (!$auto) echo "<br>";

	$error="";
	$latest_version = getURLContent("http://glpi-project.org/latest_version", $error);

	if (strlen(trim($latest_version)) == 0){
		if (!$auto) {
			echo "<div class='center'> $error </div>";
		} else {
			return $error;
		}
	} else {			
		$splitted=explode(".",trim($CFG_GLPI["version"]));

		if ($splitted[0]<10) $splitted[0].="0";
		if ($splitted[1]<10) $splitted[1].="0";
		$cur_version = $splitted[0]*10000+$splitted[1]*100;
		if (isset($splitted[2])) {
			if ($splitted[2]<10) $splitted[2].="0";
			$cur_version+=$splitted[2];
		}

		$splitted=explode(".",trim($latest_version));

		if ($splitted[0]<10) $splitted[0].="0";
		if ($splitted[1]<10) $splitted[1].="0";

		$lat_version = $splitted[0]*10000+$splitted[1]*100;
		if (isset($splitted[2])) {
			if ($splitted[2]<10) $splitted[2].="0";
			$lat_version+=$splitted[2];
		}

		if ($cur_version < $lat_version){
			$config_object=new Config();
			$input["ID"]=1;
			$input["founded_new_version"]=$latest_version;
			$config_object->update($input);
			if (!$auto) {
				echo "<div class='center'>".$LANG["setup"][301]." ".$latest_version."</div>";
				echo "<div class='center'>".$LANG["setup"][302]."</div>";
			} else {
				return $LANG["setup"][301]." ".$latest_version;
			}
		}  else {
			if (!$auto){
				echo "<div class='center'>".$LANG["setup"][303]."</div>";
			} else {
				return $LANG["setup"][303];
			}
		}
	} 
	return 1;
}
/**
* Cron job to check if a new version is available
* 
**/
function cron_check_update(){
	global $CFG_GLPI;
	$result=checkNewVersionAvailable(1);
	logInFile("cron",$result."\n");
}

/**
* Get date using a begin date and a period in month
* 
* @param $from date: begin date
* @param $addwarranty integer: period in months
* @param $deletenotice integer: period in months of notice
* 
* @return expiration date string
**/
function getWarrantyExpir($from,$addwarranty,$deletenotice=0){
	global $LANG;
	// Life warranty
	if ($addwarranty==-1 && $deletenotice==0){
		return $LANG["setup"][307];
	}
	if ($from==NULL || empty($from))
		return "";
	else {
		return convDate(date("Y-m-d", strtotime("$from+$addwarranty month -$deletenotice month")));
	}

}
/**
* Get date using a begin date and a period in month and a notice one
* 
* @param $begin date: begin date
* @param $duration integer: period in months
* @param $notice integer: notice in months 
* 
* @return expiration string 
**/
function getExpir($begin,$duration,$notice="0"){
	global $LANG;
	if ($begin==NULL || empty($begin)){
		return "";
	} else {
		$diff=strtotime("$begin+$duration month -$notice month")-time();
		$diff_days=floor($diff/60/60/24);
		if($diff_days>0){
			return $diff_days." ".$LANG["stats"][31];
		}else{
			return "<span class='red'>".$diff_days." ".$LANG["stats"][31]."</span>";
		}
	}
}

/**
* Manage login redirection 
* 
* @param $where string: where to redirect ? 
**/
function manageRedirect($where){
	global $CFG_GLPI,$PLUGIN_HOOKS;
	if (!empty($where)){
		$data=explode("_",$where);
		if (count($data)>=2&&isset($_SESSION["glpiactiveprofile"]["interface"])&&!empty($_SESSION["glpiactiveprofile"]["interface"])){
			switch ($_SESSION["glpiactiveprofile"]["interface"]){
				case "helpdesk" :
					switch ($data[0]){
						case "plugin":
							if (isset($data[2])&&$data[2]>0&&isset($PLUGIN_HOOKS['redirect_page'][$data[1]])&&!empty($PLUGIN_HOOKS['redirect_page'][$data[1]])){
								glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".$PLUGIN_HOOKS['redirect_page'][$data[1]]."?ID=".$data[2]);
							} else {
								glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
							} 
						break;
						case "tracking":
							glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&ID=".$data[1]);
						break;
						case "prefs":
							glpi_header($CFG_GLPI["root_doc"]."/front/user.form.my.php");
						break;
						default:
							glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
						break;


					}
				break;
				case "central" :
					switch ($data[0]){
						case "plugin":
							if (isset($data[2])&&$data[2]>0&&isset($PLUGIN_HOOKS['redirect_page'][$data[1]])&&!empty($PLUGIN_HOOKS['redirect_page'][$data[1]])){
								glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".$PLUGIN_HOOKS['redirect_page'][$data[1]]."?ID=".$data[2]);
							} else {
								glpi_header($CFG_GLPI["root_doc"]."/front/central.php");
							} 
						break;
						case "prefs":
							glpi_header($CFG_GLPI["root_doc"]."/front/user.form.my.php");
						break;
						default : 
							if (!empty($data[0])&&$data[1]>0){
								glpi_header($CFG_GLPI["root_doc"]."/front/".$data[0].".form.php?ID=".$data[1]);
							} else {
								glpi_header($CFG_GLPI["root_doc"]."/front/central.php");
							}
						break;
					}
				break;
			}
		}
	}
}
/**
* Clean string for input text field 
* 
* @param $string string: input text
* 
* @return clean string
**/
function cleanInputText($string){
	return preg_replace('/\"/','&quot;',$string);
} 

/**
* Get a random string 
* 
* @param $length integer: length of the random string 
* 
* @return random string
**/
function getRandomString($length) {

	$alphabet = "1234567890abcdefghijklmnopqrstuvwxyz";
	$rndstring="";
	for ($a = 0; $a <= $length; $a++) {
		$b = rand(0, strlen($alphabet) - 1);
		$rndstring .= $alphabet[$b];
	}
	return $rndstring;
}

/**
* Make a good string from the unix timestamp $sec
* 
* @param $sec integer: timestamp
* 
* @param $display_sec boolean: display seconds ? 
* 
* @return string 
**/
function timestampToString($sec,$display_sec=true){
	global $LANG;
	$sec=floor($sec);
	if ($sec<0) $sec=0;

	if($sec < MINUTE_TIMESTAMP) {
		return $sec." ".$LANG["stats"][34];

	} else if($sec < HOUR_TIMESTAMP) {
		$min = floor($sec/MINUTE_TIMESTAMP);
		$sec = $sec%MINUTE_TIMESTAMP;

		$out=$min." ".$LANG["stats"][33];
		if ($display_sec) $out.=" ".$sec." ".$LANG["stats"][34];
		return $out;

	} else if($sec <  DAY_TIMESTAMP) {
		$heure = floor($sec/HOUR_TIMESTAMP);
		$min = floor(($sec%HOUR_TIMESTAMP)/(MINUTE_TIMESTAMP));
		$sec = $sec%MINUTE_TIMESTAMP;
		$out=$heure." ".$LANG["job"][21]." ".$min." ".$LANG["stats"][33];
		if ($display_sec) $out.=" ".$sec." ".$LANG["stats"][34];
		return $out;

	} else {
		$jour = floor($sec/DAY_TIMESTAMP);
		$heure = floor(($sec%DAY_TIMESTAMP)/(HOUR_TIMESTAMP));
		$min = floor(($sec%HOUR_TIMESTAMP)/(MINUTE_TIMESTAMP));
		$sec = $sec%MINUTE_TIMESTAMP;
		$out=$jour." ".$LANG["stats"][31]." ".$heure." ".$LANG["job"][21]." ".$min." ".$LANG["stats"][33];
		if ($display_sec) $out.=" ".$sec." ".$LANG["stats"][34];
		return $out;

	}
}
/**
* Delete a directory and file contains in it
* 
* @param $dir string: directory to delete
**/
function deleteDir($dir) {
	if (file_exists($dir)){
		chmod($dir,0777);
		if (is_dir($dir)){
			$id_dir = opendir($dir);
			while($element = readdir($id_dir)){
				if ($element != "." && $element != ".."){
					if (is_dir($element)){
						deleteDir($dir."/".$element);
					} else {
						unlink($dir."/".$element);
					}
	
				}
			}
			closedir($id_dir);
			rmdir($dir);
		} else { // Delete file
			unlink($dir);
		}
	}
}

/**
 * Determine if a login is valid
 * @param $login string: login to check
 * @return boolean 
 */
function isValidLogin($login=""){
	return preg_match( "/^[[:alnum:]@.\-_]+$/i", $login);
}


/**
 * Determine if Ldap is usable checking ldap extension existence
 * @return boolean 
 */
function canUseLdap(){
	return extension_loaded('ldap');
}

/**
 * Determine if Imap/Pop is usable checking extension existence
 * @return boolean 
 */
function canUseImapPop(){
	return extension_loaded('imap');
}


/** Converts an array of parameters into a query string to be appended to a URL.
 *
 * @return  string  : Query string to append to a URL.
 * @param   $array  array: parameters to append to the query string.
 * @param   $parent This should be left blank (it is used internally by the function).
 */
function append_params($array, $parent=''){
	$params = array();
	foreach ($array as $k => $v){
		if (is_array($v))
		$params[] = append_params($v, (empty($parent) ? rawurlencode($k) : $parent . '[' . rawurlencode($k) . ']'));
		else
		$params[] = (!empty($parent) ? $parent . '[' . rawurlencode($k) . ']' : rawurlencode($k)) . '=' . rawurlencode($v);
	}
	
	return implode('&', $params);
}

/** Format a size passing a size in octet
 *
 * @param   $size integer: Size in octet
 * @return  formatted size
 */
function getSize($size) {
	$bytes = array('B','KB','MB','GB','TB');
	foreach($bytes as $val) {
		if($size > 1024){
			$size = $size / 1024;
		}else{
			break;
		}
	}
	return round($size, 2)." ".$val;
}
?>
