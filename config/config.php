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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

	if (!defined('GLPI_ROOT')){
		die("Sorry. You can't access directly to this file");
		}
	include_once (GLPI_ROOT."/config/based_config.php");
	include (GLPI_ROOT."/config/define.php");
	session_save_path(GLPI_SESSION_DIR);
	if(!session_id()){@session_start();}




	if(!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
		nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
		echo "<div align='center'>";
		echo "<p>Error : GLPI seems to not be installed properly.</p><p> config_db.php file is missing.</p><p>Please restart the install process.</p>";
		echo "</div>";
		nullFooter("Mysql Error",$_SERVER['PHP_SELF']);
	
		die();
	} else {
	
		require_once (GLPI_CONFIG_DIR . "/config_db.php");
		include_once (GLPI_ROOT."/lib/cache_lite/Lite/Output.php");
		include_once (GLPI_ROOT."/lib/cache_lite/Lite/File.php");

		$DB = new DB;


		// *************************** Statics config options **********************
		// ********************options d'installation statiques*********************
		// ***********************************************************************		

		//Options gerees dynamiquement, ne pas toucher cette partie.
		//Options from DB, do not touch this part.
		$CFG_GLPI["debug"]=$CFG_GLPI["debug_sql"]=$CFG_GLPI["debug_vars"]=$CFG_GLPI["debug_profile"]=$CFG_GLPI["debug_lang"]=0;
		$config_object=new Config();
	
		if($config_object->getFromDB(1)){
			$CFG_GLPI=array_merge($CFG_GLPI,$config_object->fields);

			if ( !isset($_SERVER['REQUEST_URI']) ) {
				$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			}
			$currentdir=getcwd();
			chdir(GLPI_ROOT);
			$glpidir=str_replace(str_replace('\\', '/',getcwd()),"",str_replace('\\', '/',$currentdir));
			chdir($currentdir);
			
			$globaldir=preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/","",$_SERVER['REQUEST_URI']);
			$globaldir=preg_replace("/\?.*/","",$globaldir);
			$CFG_GLPI["root_doc"]=str_replace($glpidir,"",$globaldir);
			$CFG_GLPI["root_doc"]=preg_replace("/\/$/","",$CFG_GLPI["root_doc"]);
	
	
			// Path for icon of document type
			$CFG_GLPI["typedoc_icon_dir"] = GLPI_ROOT."/pics/icones";


			// *************************** Mode NORMAL / TRALATION /DEBUG  **********************
			// *********************************************************************************
	
			// Mode debug ou traduction
			//$CFG_GLPI["debug"]=DEBUG_MODE;
			$CFG_GLPI["debug_sql"]=($CFG_GLPI["debug"]==DEBUG_MODE?1:0); // affiche les requetes
			$CFG_GLPI["debug_vars"]=($CFG_GLPI["debug"]==DEBUG_MODE?1:0); // affiche les variables
			$CFG_GLPI["debug_profile"]=($CFG_GLPI["debug"]==DEBUG_MODE?1:0); // Profile les requetes
			$CFG_GLPI["debug_lang"]=($CFG_GLPI["debug"]==TRANSLATION_MODE?1:0); // affiche les variables de trads
	
		} else {
			echo "Error accessing config table";
			exit();
		}


		$cache_options = array(
			'cacheDir' => GLPI_CACHE_DIR,
			'lifeTime' => DEFAULT_CACHE_LIFETIME,
			'automaticSerialization' => true,
			'caching' => $CFG_GLPI["use_cache"],
			'hashedDirectoryLevel' => 2,
			'fileLocking' => CACHE_FILELOCKINGCONTROL,
			'writeControl' => CACHE_WRITECONTROL,
			'readControl' => CACHE_READCONTROL,
		);

		$GLPI_CACHE = new Cache_Lite_Output($cache_options);
		$CFG_GLPI["cache"]=$GLPI_CACHE;

	
		// Mode debug activé on affiche un certains nombres d'informations
		if ($CFG_GLPI["debug"]==DEBUG_MODE){
			ini_set('display_errors','On'); 
			error_reporting(E_ALL); 
			ini_set('error_prepend_string','<div style="position:fload-left; background-color:red; z-index:10000">PHP ERROR : '); 
			ini_set('error_append_string','</div>'); 
			set_error_handler("userErrorHandler"); 
		}else{
			//Pas besoin des warnings de PHP en mode normal : on va eviter de faire peur ;)
			error_reporting(0); 
		}
	
		if (isset($_SESSION["glpiroot"])&&$CFG_GLPI["root_doc"]!=$_SESSION["glpiroot"]) {
			glpi_header($_SESSION["glpiroot"]);
		}
	
	
	
		// Override cfg_features by session value
		if (!isset($_SESSION['glpilist_limit'])||$_SESSION['glpilist_limit']<5) $_SESSION["glpilist_limit"]=$CFG_GLPI['list_limit'];

		if ((!isset($CFG_GLPI["version"])||trim($CFG_GLPI["version"])!=GLPI_VERSION)&&!isset($_GET["donotcheckversion"])){
			loadLanguage();
			nullHeader("UPDATE NEEDED",$_SERVER['PHP_SELF']);
			echo "<div align='center'>";
	
	
			echo "<table class='tab_cadre' style='width:600px'>";
			echo "<tr><th>".$LANG["install"][6]."</th><th >".$LANG["install"][7]."</th></tr>";
	
			$error=checkWriteAccessToDirs();
	
			echo "</table><br>";
	
			if (!$error){
				if (!isset($CFG_GLPI["version"])||trim($CFG_GLPI["version"])<GLPI_VERSION){
					echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/install/update.php'>";
					echo "<table class='tab_cadre' style='width:500px'><tr><th>";
					echo $LANG["update"][88];
					echo "</th></tr>";
					echo "<tr class='tab_bg_1'><td align='center'>";
					echo "<input type='submit' name='from_update' value='".$LANG["install"][4]."' class='submit'>";
					echo "</td></tr>";
					echo "</table></form>";
				} else if (trim($CFG_GLPI["version"])>GLPI_VERSION){
					echo "<table class='tab_cadre' style='width:500px'><tr><th>";
					echo $LANG["update"][89];
					echo "</th></tr>";
					echo "</table>";
				}
			} else {
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
				echo "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"".$LANG["install"][27]."\" />";
				echo "</form>";
			}
			echo "</div>";
			nullFooter();
			exit();
		} 
	}


?>
