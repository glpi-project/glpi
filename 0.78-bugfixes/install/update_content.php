<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre & Bazile Lebeau
// Purpose of file:
// ----------------------------------------------------------------------

//#################### INCLUDE & SESSIONS ############################
define('GLPI_ROOT', '..');

// Do not include config.php so set root_doc
$CFG_GLPI['root_doc']='..';

include_once (GLPI_ROOT . "/config/define.php");

include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
include_once (GLPI_ROOT . "/inc/common.function.php");
include_once (GLPI_ROOT . "/inc/display.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/config/based_config.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

setGlpiSessionPath();
startGlpiSession();

// Init debug variable
$_SESSION['glpi_use_mode']=DEBUG_MODE;
$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=0; 
$CFG_GLPI["use_errorlog"]=1;
ini_set('display_errors','On'); 
error_reporting(E_ALL | E_STRICT); 
set_error_handler("userErrorHandlerDebug");           

//################################ Functions ################################

function loadLang() {
	if (isset($LANG)){
		unset($LANG);
	}
	global $LANG;
	if (isset($_SESSION["glpilanguage"]))
		$dict=$_SESSION["glpilanguage"];
	else $dict="en_GB";

	$file = GLPI_ROOT ."/locales/$dict.php";
	if (!is_file($file))
		$file = GLPI_ROOT ."/locales/en_GB.php";
	include($file);
}

$max_time=min(get_cfg_var("max_execution_time"),get_cfg_var("max_input_time"));
if ($max_time>5) {$defaulttimeout=$max_time-2;$defaultrowlimit=1;}
else {$defaulttimeout=1;$defaultrowlimit=1;}

$DB=new DB;

function init_time() 
{
	global $TPSDEB,$TPSCOUR;


	list ($usec,$sec)=explode(" ",microtime());
	$TPSDEB=$sec;
	$TPSCOUR=0;

}

function current_time() 
{
	global $TPSDEB,$TPSCOUR;
	list ($usec,$sec)=explode(" ",microtime());
	$TPSFIN=$sec;
	if (round($TPSFIN-$TPSDEB,1)>=$TPSCOUR+1) //une seconde de plus
	{
		$TPSCOUR=round($TPSFIN-$TPSDEB,1);
	}

}


function get_update_content($DB, $table,$from,$limit,$conv_utf8)
{
	$content="";
	$DB->query("SET NAMES latin1");

	$result = $DB->query("SELECT * FROM $table LIMIT $from,$limit");

	if($result){
		while($row = $DB->fetch_assoc($result)) {
			if (isset($row["id"])) {
				$insert = "UPDATE $table SET ";
				foreach ($row as $key => $val) {
					$insert.=" `".$key."`=";

					if(!isset($val)) $insert .= "NULL,";
					else if($val != "") {
						if ($conv_utf8) {
							// Gestion users AD qui sont d��en UTF8
							if ($table!="glpi_users"||!seems_utf8($val))
								$val=encodeInUtf8($val);
						}
						$insert .= "'".addslashes($val)."',";
					}
					else $insert .= "'',";
				}
				$insert = preg_replace("/,$/","",$insert);
				$insert.=" WHERE id = '".$row["id"]."' ";
				$insert .= ";\n";
				$content .= $insert;
			}
		}
	}
	//if ($table=="glpi_dropdown_locations") echo $content;
	return $content;
}


function UpdateContent($DB, $duree,$rowlimit,$conv_utf8,$complete_utf8)
{
	// $dumpFile, fichier source
	// $database, nom de la base de données cible
	// $mysqlUser, login pouyr la connexion au serveur MySql
	// $mysqlPassword, mot de passe
	// $histMySql, nom de la machine serveur MySQl
	// $duree=timeout pour changement de page (-1 = aucun)


	global $TPSCOUR,$offsettable,$offsetrow,$cpt,$LANG;

	$result=$DB->list_tables();
	$numtab=0;
	while ($t=$DB->fetch_array($result)){
		if (strstr($t[0],"glpi_")){
			$tables[$numtab]=$t[0];
			$numtab++;
		}
	}


	for (;$offsettable<$numtab;$offsettable++){
	//	echo $tables[$offsettable]."<br>\n";
		// Dump de la structyre table
		if ($offsetrow==-1){
			if ($complete_utf8){
				$DB->query("ALTER TABLE `".$tables[$offsettable]."`  DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
				$data=$DB->list_fields($tables[$offsettable]);
				
				foreach ($data as $key =>$val){
//					echo "<br>".$key."<br>";
//					print_r($val);

					if (preg_match("/^char/i",$val["Type"])){
						$default="NULL";
						if (!empty($val["Default"])&&!is_null($val["Default"])){
							$default="'".$val["Default"]."'";
						}

						$DB->query("ALTER TABLE `".$tables[$offsettable]."` CHANGE `".$val["Field"]."` `".$val["Field"]."` ".$val["Type"]." CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT $default");
					} else if (preg_match("/^varchar/i",$val["Type"])){
						$default="NULL";
						if (!empty($val["Default"])&&!is_null($val["Default"])){
							$default="'".$val["Default"]."'";
						}
						$DB->query("ALTER TABLE `".$tables[$offsettable]."` CHANGE `".$val["Field"]."` `".$val["Field"]."` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT $default");
					} else if (preg_match("/^longtext/i",$val["Type"])){
						$DB->query("ALTER TABLE `".$tables[$offsettable]."` CHANGE `".$val["Field"]."` `".$val["Field"]."` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
					} else if (preg_match("/^text/i",$val["Type"])){
						$DB->query("ALTER TABLE `".$tables[$offsettable]."` CHANGE `".$val["Field"]."` `".$val["Field"]."` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
					}
	
				}
			}
			$offsetrow++;
			$cpt++;
		}

		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;

		$fin=0;
		while (!$fin){
			$todump=get_update_content($DB,$tables[$offsettable],$offsetrow,$rowlimit,$conv_utf8);
			//	echo $todump."<br>";
			$rowtodump=substr_count($todump, "UPDATE ");
			if ($rowtodump>0){
				$DB->query("SET NAMES utf8");
				$result = $DB->query($todump);
	//			if (!$result) echo "ECHEC ".$todump;

				$cpt+=$rowtodump;
				$offsetrow+=$rowlimit;
				if ($rowtodump<$rowlimit) $fin=1;
				current_time();
				if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
					return TRUE;

			}
			else {$fin=1;$offsetrow=-1;}
		}
		if ($fin) $offsetrow=-1;
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;

	}
	if ($DB->error()){
		echo "<hr>".$LANG['backup'][23]." [$formattedQuery]<br>".$DB->error()."<hr>";
	}
	$offsettable=-1;
	return TRUE;
}

//########################### Script start ################################

loadLang();


// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
echo "<html>";
echo "<head>";
echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\"> ";
echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\"> ";
echo "<meta http-equiv=\"Content-Language\" content=\"fr\"> ";
echo "<meta name=\"generator\" content=\"\">";
echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\">";
echo "<title>Setup GLPI</title>";
// CSS
echo "<link rel='stylesheet'  href='../css/style_install.css' type='text/css' media='screen' >";

echo "</head>";
echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div class='haut'></div>";
echo "<h2>GLPI SETUP</h2>";
//end style and co

// #################" UPDATE CONTENT #################################

$time_file=date("Y-m-d-h-i");
$cur_time=date("Y-m-d H:i");

init_time(); //initialise le temps
//debut de fichier
if (!isset($_GET["offsettable"])) $offsettable=0; 
else $offsettable=$_GET["offsettable"]; 
//debut de fichier
if (!isset($_GET["offsetrow"])) $offsetrow=-1; 
else $offsetrow=$_GET["offsetrow"];
//timeout de 5 secondes par d�aut, -1 pour utiliser sans timeout
if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
else $duree=$_GET["duree"];
//Limite de lignes �dumper �chaque fois
if (!isset($_GET["rowlimit"])) $rowlimit=$defaultrowlimit; 
else  $rowlimit=$_GET["rowlimit"];

$tab=$DB->list_tables();
$tot=$DB->numrows($tab);
	if(isset($offsettable)){
		if ($offsettable>=0)
			$percent=min(100,round(100*$offsettable/$tot,0));
		else $percent=100;
	}
else $percent=0;

$conv_utf8=false;
$complete_utf8=true;

if(!FieldExists("glpi_configs","utf8_conv")) {
	$conv_utf8=true;
} else {
	$query="SELECT utf8_conv FROM glpi_configs WHERE id='1'";
	$result=$DB->query($query);
	$data=$DB->fetch_assoc($result);
	if ($data["utf8_conv"]){
		$complete_utf8=false;
	}
}

if ($offsettable>=0&&$complete_utf8){
		if ($percent >= 0) {
		
			displayProgressBar(400,$percent);
			echo "<div class='bas'></div></div></div></body></html>";
			glpi_flush();    
		
		}
		if (UpdateContent($DB,$duree,$rowlimit,$conv_utf8,$complete_utf8))
		{
			echo "<br><a href=\"update_content.php?dump=1&amp;duree=$duree&amp;rowlimit=$rowlimit&amp;offsetrow=$offsetrow&amp;offsettable=$offsettable&amp;cpt=$cpt\">".$LANG['backup'][24]."</a>";
			echo "<script language=\"javascript\" type=\"text/javascript\">window.location=\"update_content.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt\";</script>";
//			echo "<div class='bas'></div></div></div></body></html>";

			glpi_flush();    
			exit;
		}
	}
else  { 
	echo "<p class='submit'> <a href=\"../index.php\"><span class='button'>".$LANG['install'][64]."</span></a></p>";
	echo "<div class='bas'></div></div></div></body></html>";

}

if ($conv_utf8){
	$query = "ALTER TABLE `glpi_configs` ADD `utf8_conv` INT( 11 ) DEFAULT '0' NOT NULL";
	$DB->query($query) or die(" 0.6 add utf8_conv to glpi_configs".$LANG['update'][90].$DB->error());
}

if ($complete_utf8){
	$DB->query("ALTER DATABASE `".$DB->dbdefault."` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
	$DB->query("UPDATE glpi_configs SET utf8_conv='1' WHERE id='1'");
}

?>
