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

// Based on cacti plugin system
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


class Plugin extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_plugins";
		$this->type=PLUGIN_TYPE;
	}
	
	/**
	 * Retrieve an item from the database using its name
	 *
	 *@param $name name of the plugin
	 *@return true if succeed else false
	 * 
	**/	
	function getFromDBbyName($name) {
		global $DB;
		$query = "SELECT * FROM ".$this->table." WHERE (name = '" . $name . "')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) != 1) {
				return false;
			}
			$this->fields = $DB->fetch_assoc($result);
			if (is_array($this->fields) && count($this->fields)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Check plugins states and detect new plugins
	 *
	**/	
	function checkStates(){
		global $LANG;
		//// Get all plugins 
		// Get all from DBs
		$pluglist=$this->find("","name, directory");
		$db_plugins=array();
		if (count($pluglist)){
			foreach ($pluglist as $plug){
				$db_plugins[$plug['directory']]=$plug['ID'];
			}
		}
		
		// Parse plugin dir 
		$file_plugins=array();
		$error_plugins=array();
		$dirplug=GLPI_ROOT."/plugins";
		$dh  = opendir($dirplug);
		while (false !== ($filename = readdir($dh))) {
			if ($filename!=".svn"&&$filename!="."&&$filename!=".."&&is_dir($dirplug."/".$filename)){
				// Find version
				if (file_exists($dirplug."/".$filename."/setup.php")){
					loadPluginLang($filename);

					include_once($dirplug."/".$filename."/setup.php");
					$function="plugin_version_$filename";
					if (function_exists($function)){
						$file_plugins[$filename]=$function();	
					}
				} 
			}
		}

		// check plugin state
		foreach ($db_plugins as $plug => $ID){
			$install_ok=true;
			// Check file
			if (!isset($file_plugins[$plug])){
				$this->update(array('ID'=>$ID,'state'=>PLUGIN_TOBECLEANED));
				$install_ok=false;
			} else {
				// Check version
				if ($file_plugins[$plug]['version']!=$pluglist[$ID]['version']){
					$input=$file_plugins[$plug];
					$input['ID']=$ID;
					$this->update($input);
					$install_ok=false;
				}
			}
			// Check install is ok for activated plugins
			if ($install_ok && ($pluglist[$ID]['state'] == PLUGIN_ACTIVATED) ){
				$usage_ok=true;
				$function="plugin_".$plug."_check_prerequisites";
				if (function_exists($function)){
					if (!$function()){
						$usage_ok=false;
					}
				}
				$function="plugin_".$plug."_check_config";
				if (function_exists($function)){
					if (!$function()){
						$usage_ok=false;
					}
				} else {
					$usage_ok=false;
				}
				if (!$usage_ok){
					$input=$file_plugins[$plug];
					$input['ID']=$ID;
					$this->update($input);					
				}
			}
			// Delete plugin for file list
			if (isset($file_plugins[$plug])){
				unset($file_plugins[$plug]);
			}
		}

		if (count($file_plugins)){
			foreach ($file_plugins as $plug => $data){
				$data['state']=PLUGIN_NOTINSTALLED;
				$data['directory']=$plug;
				$this->add($data);
			}
		}


	}


	/**
	 * List availabled plugins
	 *
	**/	
	function listPlugins(){
		global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;
		$this->checkStates();

		echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
		
		// ligne a modifier en fonction de la modification des fichiers de langues
		echo "<tr><th colspan='7'>".$LANG["plugins"][0]."</th></tr>";
		echo "<tr><th>".$LANG["common"][16]."</th><th>".$LANG["rulesengine"][78]."</th><th>".$LANG["state"][0]."</th><th>".$LANG["common"][37]."</th><th>".$LANG["financial"][45]."</th><th colspan='2'>&nbsp;</th></tr>";
		$pluglist=$this->find("","name, directory");
		$i=0;
		foreach ($pluglist as $ID => $plug){
			usePlugin($plug['directory']);
			$i++;
			$class='tab_bg_1';
			if ($i%2==0){
				$class='tab_bg_2';
			}
			echo "<tr class='$class'>";
			echo "<td>";

			if (isset($PLUGIN_HOOKS['config_page'][$plug['directory']])) {
				echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/".$plug['directory']."/".$PLUGIN_HOOKS['config_page'][$plug['directory']]."'><strong>".$plug['name']."</strong></a>";		
			} else {
				echo $plug['name'];
			}
			echo "</td>";
			echo "<td>".$plug['version']."</td>";
			echo "<td>";
			switch ($plug['state']){
				case PLUGIN_NEW :
					echo $LANG["joblist"][9];
					break;
				case PLUGIN_ACTIVATED :
					echo $LANG["setup"][192];
					break;
				case PLUGIN_NOTINSTALLED :
					echo $LANG["plugins"][1];
					break;
				case PLUGIN_TOBECONFIGURED :
					echo $LANG["plugins"][2];
					break;
				case PLUGIN_NOTACTIVATED :
					echo $LANG["plugins"][3];
					break;
				case PLUGIN_TOBECLEANED :
				default:
					echo $LANG["plugins"][4];
					break;
			}
			echo "</td>";
			echo "<td>".$plug['author']."</td>";
			$weblink=formatOutputWebLink(trim($plug['homepage']));
			echo "<td>";
			if (!empty($weblink)){
				echo "<a href='$weblink' target='_blank'><img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle' alt='".$LANG["common"][4]."' title='".$LANG["common"][4]."' ></a>";
			} else {
				echo "&nbsp;";
			}
			echo "</td>";

			switch ($plug['state']){
				case PLUGIN_ACTIVATED :
					echo "<td>";
					echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=unactivate'>".$LANG["buttons"][42]."<a>";
					echo "</td><td>";
					if (function_exists("plugin_".$plug['directory']."_uninstall")){
						echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=uninstall'>".$LANG["buttons"][5]."<a>";
					} else {
						echo $LANG["plugins"][5].": "."plugin_".$plug['directory']."_uninstall";
					}
					echo "</td>";
					break;
				case PLUGIN_NEW :
				case PLUGIN_NOTINSTALLED :
					echo "<td>";
					if (function_exists("plugin_".$plug['directory']."_install")){
						$function = 'plugin_' . $plug['directory'] . '_check_prerequisites';
						$do_install=true;
						if (function_exists($function)) {
							$do_install=$function();
						}
						if ($do_install){
							echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=install'>".$LANG["buttons"][4]."<a>";
						}
					} else {
						echo $LANG["plugins"][5].": "."plugin_".$plug['directory']."_install";
					}
					echo "</td><td>";
					if (function_exists("plugin_".$plug['directory']."_uninstall")){
						echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=uninstall'>".$LANG["buttons"][5]."<a>";
					} else {
						echo $LANG["plugins"][5].": "."plugin_".$plug['directory']."_uninstall";
					}
					echo "</td>";
					break;
				case PLUGIN_TOBECONFIGURED :
					echo "<td>";
						$function = 'plugin_' . $plug['directory'] . '_check_config';
						if (function_exists($function)){
							if ($function()){
								$this->update(array('ID'=>$ID,'state'=>PLUGIN_NOTACTIVATED));
								glpi_header($_SERVER['PHP_SELF']);
							}
						} else {
							echo $LANG["plugins"][5].": "."plugin_".$plug['directory']."_check_config";
						}

					echo "</td><td>";
					if (function_exists("plugin_".$plug['directory']."_uninstall")){
						echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=uninstall'>".$LANG["buttons"][5]."<a>";
					} else {
						echo $LANG["plugins"][5].": "."plugin_".$plug['directory']."_uninstall";
					}
					echo "</td>";
					break;
				case PLUGIN_NOTACTIVATED :
					echo "<td>";
						echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=activate'>".$LANG["buttons"][41]."<a>";
					echo "</td><td>";
					if (function_exists("plugin_".$plug['directory']."_uninstall")){
						echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=uninstall'>".$LANG["buttons"][5]."<a>";
					} else {
						echo $LANG["plugins"][5].": "."plugin_".$plug['directory']."_uninstall";
					}
					echo "</td>";
					break;
					break;
				case PLUGIN_TOBECLEANED :
				default:
					echo "<td colspan='2'>";
						echo "<a href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;action=clean'>".$LANG["buttons"][53]."<a>";
					echo "</td>";
					break;
			}

			echo "</tr>";
		}
		echo "</table></div>";
	}

	/**
	 * uninstall a plugin
	 *
	 *@param $ID ID of the plugin
	**/	
	function uninstall($ID){
		if ($this->getFromDB($ID)){
			include_once(GLPI_ROOT."/plugins/".$this->fields['directory']."/setup.php");
			usePlugin($this->fields['directory']);
			// Run the Plugin's Uninstall Function first
			$function = 'plugin_' . $this->fields['directory'] . '_uninstall';
			if (function_exists($function)) {
				$function();
			}
			$this->update(array('ID'=>$ID,'state'=>PLUGIN_NOTINSTALLED,'version'=>''));
			$this->removeFromSession($this->fields['directory']);
		}
	}

	/**
	 * install a plugin
	 *
	 *@param $ID ID of the plugin
	**/	
	function install($ID){
		if ($this->getFromDB($ID)){
			include_once(GLPI_ROOT."/plugins/".$this->fields['directory']."/setup.php");
			usePlugin($this->fields['directory']);
			$function = 'plugin_' . $this->fields['directory'] . '_install';
			$install_ok=false;
			if (function_exists($function)) {
				if ($function()){
					$function = 'plugin_' . $this->fields['directory'] . '_check_config';
					if (function_exists($function)) {
						if ($function()){
							$this->update(array('ID'=>$ID,'state'=>PLUGIN_NOTACTIVATED));
						} else {
							$this->update(array('ID'=>$ID,'state'=>PLUGIN_TOBECONFIGURED));
						}
					}
				}
			}
		}
	}
	
	/**
	 * activate a plugin
	 *
	 *@param $ID ID of the plugin
	**/	
	function activate($ID){
		if ($this->getFromDB($ID)){
			include_once(GLPI_ROOT."/plugins/".$this->fields['directory']."/setup.php");
			usePlugin($this->fields['directory']);
			$function = 'plugin_' . $this->fields['directory'] . 'check_prerequisites';
			if (function_exists($function)) {
				if (!$function()){
					return false;
				}
			}
			
			$function = 'plugin_' . $this->fields['directory'] . '_check_config';
			if (function_exists($function)) {
				if ($function()){
					$this->update(array('ID'=>$ID,'state'=>PLUGIN_ACTIVATED));
					$_SESSION['glpi_plugins'][]=$this->fields['directory'];
					if (isset($_SESSION["glpiID"])){
						cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
					}
				} 
			}
		}
	}
	/**
	 * unactivate a plugin
	 *
	 *@param $ID ID of the plugin
	**/	
	function unactivate($ID){
		if ($this->getFromDB($ID)){
			$this->update(array('ID'=>$ID,'state'=>PLUGIN_NOTACTIVATED));
			$this->removeFromSession($this->fields['directory']);
		}
	}

	/**
	 * unactivate all activated plugins for update process
	 *
	**/	
	function unactivateAll(){
		global$DB;
		$query="UPDATE glpi_plugins SET state=".PLUGIN_NOTACTIVATED." WHERE state=".PLUGIN_ACTIVATED.";";
		$DB->query($query);

		$_SESSION['glpi_plugins']=array();
		if (isset($_SESSION["glpiID"])){
			cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
		}

	}
	/**
	 * clean a plugin
	 *
	 *@param $ID ID of the plugin
	**/	
	function clean($ID){
		if ($this->getFromDB($ID)){
			$this->delete(array('ID'=>$ID));
			$this->removeFromSession($this->fields['directory']);
		}
	}
	/**
	 * remove plugin from session variable
	 *
	 *@param $plugin plugin directory
	**/	
	function removeFromSession($plugin){
		$key=array_search($plugin,$_SESSION['glpi_plugins']);
		if ($key!==false){
			unset($_SESSION['glpi_plugins'][$key]);
			if (isset($_SESSION["glpiID"])){
				cleanCache("GLPI_HEADER_".$_SESSION["glpiID"]);
			}
		}
	}

}

?>