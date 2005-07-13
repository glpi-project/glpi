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


// Based on
// ------------------------------------------------------------------------- //
// Génération d'un fichier SYLK à partir de données MySQL en vue d'une       //
// récupération sous Excel.                                                  //
// L'avantange du format SYLK par rapport au format CSV est qu'il permet de  //
// définir des attributs de mise en forme pour les données : alignement,     //
// gras, itallique, formats de données, ...                                  //
// ------------------------------------------------------------------------- //
// Auteur: J-Pierre DEZELUS                                                  //
// Email:  jpdezelus@phpinfo.net                                             //
// Web:    http://www.phpinfo.net/                                           //
// ------------------------------------------------------------------------- //

include ("_relpos.php");

include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_networking.php");

checkAuthentication("normal");

define("FORMAT_REEL",   1); // #,##0.00
define("FORMAT_ENTIER", 2); // #,##0
define("FORMAT_TEXTE",  3); // @

$cfg_formats[FORMAT_ENTIER] = "FF0";
$cfg_formats[FORMAT_REEL]   = "FF2";
$cfg_formats[FORMAT_TEXTE]  = "FG0";


// ----------------------------------------------------------------------------
$db = new DB;
// ----------------------------------------------------------------------------

    // construction de la requête
    // ------------------------------------------------------------------------
$table=$_GET["table"];//"printers"; // networking, monitors, printers, computers, peripherals

switch($table){
	case "printers" :
		$query_nb = "select distinct glpi_printers.ID from glpi_printers";
		
		$query = "select glpi_printers.*, glpi_users.name as techname, glpi_enterprises.name as entname, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= "from glpi_printers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = ".PRINTER_TYPE." AND glpi_networking_ports.on_device = glpi_printers.ID) ";
		$query.= "LEFT JOIN glpi_users ON glpi_users.ID = glpi_printers.tech_num ";
		$query.= "LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_printers.FK_glpi_enterprise ";
		$query.= " WHERE glpi_printers.is_template='0' ";
		$query.=" ORDER by glpi_printers.deleted DESC, glpi_printers.ID ASC";
//		echo $query;
//	exit;
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     utf8_decode($lang["printers"][19]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', utf8_decode($lang["printers"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    utf8_decode($lang["printers"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'entname',    utf8_decode($lang["common"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),            
	  Array( 'techname',    utf8_decode($lang["common"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),            
      Array( 'location',    utf8_decode($lang["printers"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ramSize',    utf8_decode($lang["printers"][23]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    utf8_decode($lang["printers"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    utf8_decode($lang["printers"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'serial',    utf8_decode($lang["printers"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    utf8_decode($lang["printers"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'flags_serial',     utf8_decode($lang["printers"][14]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_par', utf8_decode($lang["printers"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_usb',    utf8_decode($lang["printers"][27]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifaddr', utf8_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    utf8_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    utf8_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'deleted',    utf8_decode($lang["common"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'comments',    utf8_decode($lang["printers"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',    utf8_decode($lang["printers"][16]), FORMAT_TEXTE, 'L',    20 ,'0'),

    );
 	break;
    // ------------------------------------------------------------------------
	case "networking" :
		$query_nb = "select distinct glpi_networking.ID from glpi_networking";
		
		$query = "select glpi_networking.*, glpi_users.name as techname, glpi_enterprises.name as entname, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= "from glpi_networking LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 2 AND glpi_networking_ports.on_device = glpi_networking.ID)";
		$query.= "LEFT JOIN glpi_users ON glpi_users.ID = glpi_networking.tech_num ";
		$query.= "LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_networking.FK_glpi_enterprise ";
		$query.= " WHERE glpi_networking.is_template='0' ";
		$query.=" ORDER by glpi_networking.deleted DESC, glpi_networking.ID ASC";

//		echo $query;
//		exit;	
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     utf8_decode($lang["networking"][50]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', utf8_decode($lang["networking"][0]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    utf8_decode($lang["networking"][2]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'entname',    utf8_decode($lang["common"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),            
	  Array( 'techname',    utf8_decode($lang["common"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),            
      Array( 'location',    utf8_decode($lang["networking"][1]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'firmware',     utf8_decode($lang["networking"][49]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ram',    utf8_decode($lang["networking"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   utf8_decode($lang["networking"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    utf8_decode($lang["networking"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    utf8_decode($lang["networking"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    utf8_decode($lang["networking"][4]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ifaddr', utf8_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    utf8_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    utf8_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'deleted',    utf8_decode($lang["common"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'comments', utf8_decode($lang["networking"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     utf8_decode($lang["networking"][9]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "monitors" :
		$query_nb = "select distinct glpi_monitors.ID from glpi_monitors";
		
		$query = "select glpi_monitors.*, glpi_users.name as techname, glpi_enterprises.name as entname ";
		$query.= "from glpi_monitors ";
		$query.= "LEFT JOIN glpi_users ON glpi_users.ID = glpi_monitors.tech_num ";
		$query.= "LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_monitors.FK_glpi_enterprise ";
		$query.= " WHERE glpi_monitors.is_template='0' ";
		$query.=" ORDER by glpi_monitors.deleted DESC, glpi_monitors.ID ASC";


//		echo $query;
//		exit;	
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     utf8_decode($lang["monitors"][23]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', utf8_decode($lang["monitors"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    utf8_decode($lang["monitors"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'entname',    utf8_decode($lang["common"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),            
	  Array( 'techname',    utf8_decode($lang["common"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),            
      Array( 'size',    utf8_decode($lang["monitors"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    utf8_decode($lang["monitors"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   utf8_decode($lang["monitors"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    utf8_decode($lang["monitors"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    utf8_decode($lang["monitors"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    utf8_decode($lang["monitors"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'flags_micro',    utf8_decode($lang["monitors"][14]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_speaker',    utf8_decode($lang["monitors"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_subd',    utf8_decode($lang["monitors"][19]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_bnc',    utf8_decode($lang["monitors"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
	  Array( 'deleted',    utf8_decode($lang["common"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'comments', utf8_decode($lang["monitors"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     utf8_decode($lang["monitors"][16]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "peripherals" :
		$query_nb = "select distinct glpi_peripherals.ID from glpi_peripherals";
		
		$query = "select glpi_peripherals.*, glpi_users.name as techname, glpi_enterprises.name as entname, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac , glpi_networking_ports.netpoint";
		$query.= " from glpi_peripherals LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = ".PERIPHERAL_TYPE." AND glpi_networking_ports.on_device = glpi_peripherals.ID) ";
		$query.= "LEFT JOIN glpi_users ON glpi_users.ID = glpi_peripherals.tech_num ";
		$query.= "LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_peripherals.FK_glpi_enterprise ";
		$query.= " WHERE glpi_peripherals.is_template='0' ";
		$query.=" ORDER by glpi_peripherals.deleted DESC, glpi_peripherals.ID ASC";

	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     utf8_decode($lang["peripherals"][23]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', utf8_decode($lang["peripherals"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    utf8_decode($lang["peripherals"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'entname',    utf8_decode($lang["common"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),            
	  Array( 'techname',    utf8_decode($lang["common"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),            
      Array( 'brand',    utf8_decode($lang["peripherals"][18]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    utf8_decode($lang["peripherals"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   utf8_decode($lang["peripherals"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    utf8_decode($lang["peripherals"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    utf8_decode($lang["peripherals"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    utf8_decode($lang["peripherals"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ifaddr', utf8_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    utf8_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    utf8_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'deleted',    utf8_decode($lang["common"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'comments', utf8_decode($lang["peripherals"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     utf8_decode($lang["peripherals"][16]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "licenses" :
		$query_nb = "select distinct glpi_licenses.ID from glpi_licenses";
		
		$query = "select glpi_software.name as soft, glpi_software.version as vers, glpi_software.deleted as deleted,";
		$query.= " glpi_software.comments as comments, glpi_dropdown_os.name as platform, ";
		$query.= " glpi_software.is_update as is_update, update_soft.name as update_soft, update_soft.version as update_vers, ";
		$query.= " glpi_licenses.serial as serial, glpi_licenses.expire as expire, ";
		$query.= " glpi_licenses.buy as buy, glpi_licenses.ID as ID,";
		$query.= " glpi_computers.name as install_on, ";
		$query.= " oem_comp.name as oem_comp, glpi_licenses.oem as oem,";
		$query.= " glpi_users.name as techname, glpi_enterprises.name as entname ";
		$query.= " from glpi_licenses ";
		$query.= " LEFT JOIN glpi_software ON glpi_software.ID = glpi_licenses.sID ";
		$query.= " LEFT JOIN glpi_software as update_soft ON glpi_software.update_software = update_soft.ID ";
		$query.= " LEFT JOIN glpi_dropdown_os ON glpi_software.platform = glpi_dropdown_os.ID ";
		$query.= " LEFT JOIN glpi_inst_software ON glpi_inst_software.license = glpi_licenses.ID ";
		$query.= " LEFT JOIN glpi_computers ON glpi_inst_software.cID = glpi_computers.ID ";
		$query.= " LEFT JOIN glpi_computers AS oem_comp ON glpi_licenses.oem_computer = oem_comp.ID ";
		$query.= "LEFT JOIN glpi_users ON glpi_users.ID = glpi_software.tech_num ";
		$query.= "LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_software.FK_glpi_enterprise ";
		$query.= " WHERE glpi_software.is_template='0' ";
		$query.=" ORDER by glpi_software.deleted DESC, glpi_software.name ASC";

// ADD OEM

//		echo $query;
//		exit;	
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID', utf8_decode($lang["software"][1]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'soft', utf8_decode($lang["software"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'vers',    utf8_decode($lang["software"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'entname',    utf8_decode($lang["common"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),            
	  Array( 'techname',    utf8_decode($lang["common"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),            
      Array( 'platform',    utf8_decode($lang["software"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   utf8_decode($lang["software"][31]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'buy',    utf8_decode($lang["software"][35]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'install_on',    utf8_decode($lang["software"][19]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'expire',    utf8_decode($lang["software"][32]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'oem',    utf8_decode($lang["software"][28]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'oem_comp',    utf8_decode($lang["software"][28]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'is_update',    utf8_decode($lang["software"][29]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'update_soft',    utf8_decode($lang["software"][30]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'update_vers',   utf8_decode($lang["software"][30]), FORMAT_TEXTE, 'L',    20 ,'0'),
	  Array( 'deleted',    utf8_decode($lang["common"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'comments',    utf8_decode($lang["software"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),      
    );
 	break;

    // ------------------------------------------------------------------------
	case "computers" :
		$query_nb = "select distinct glpi_computers.ID from glpi_computers";
		$query="";
		
		$query = "select glpi_computers.*, CONCAT(glpi_software.name, glpi_software.version) AS soft, glpi_licenses.serial as softserial, ";
		$query.= " glpi_monitors.name as monname, glpi_monitors.type as montype, glpi_monitors.serial as monserial, ";
		$query.= " glpi_printers.name as printname, glpi_printers.type as printtype, glpi_printers.serial as printserial, ";
		$query.= " glpi_peripherals.name as periphname, glpi_peripherals.type as periphtype, glpi_peripherals.serial as periphserial, ";
		$query.= " glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint, ";
		$query.= " glpi_users.name as techname, glpi_enterprises.name as entname ";
		$query.= " from glpi_computers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 1 AND glpi_networking_ports.on_device = glpi_computers.ID)";
		$query.= " LEFT JOIN glpi_inst_software ON (glpi_inst_software.cID = glpi_computers.ID) ";
		$query.= " LEFT JOIN glpi_licenses ON (glpi_inst_software.license = glpi_licenses.ID) ";
		$query.= " LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) ";
		$query.= " LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.end2 = glpi_computers.ID) ";
		$query.= " LEFT JOIN glpi_monitors ON (glpi_connect_wire.end1 = glpi_monitors.ID AND glpi_connect_wire.type = '4') ";
		$query.= " LEFT JOIN glpi_peripherals ON (glpi_connect_wire.end1 = glpi_peripherals.ID AND glpi_connect_wire.type = '5') ";
		$query.= " LEFT JOIN glpi_printers ON (glpi_connect_wire.end1 = glpi_printers.ID AND glpi_connect_wire.type = '3') ";
		$query.= "LEFT JOIN glpi_users ON glpi_users.ID = glpi_computers.tech_num ";
		$query.= "LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_computers.FK_glpi_enterprise ";
		$query.= " WHERE glpi_computers.is_template='0' ";
		$query.=" ORDER by glpi_computers.deleted DESC, glpi_computers.ID ASC";
		
//		echo $query;
		//exit;

	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     utf8_decode($lang["computers"][31]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', utf8_decode($lang["computers"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    utf8_decode($lang["computers"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
	  Array( 'entname',    utf8_decode($lang["common"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),            
	  Array( 'techname',    utf8_decode($lang["common"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),            
      Array( 'flags_server', utf8_decode($lang["computers"][28]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'ramtype',    utf8_decode($lang["computers"][23]), FORMAT_TEXTE, 'L',    20 ,'0'),      
//      Array( 'ram',   utf8_decode($lang["computers"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'processor',    utf8_decode($lang["computers"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'processor_speed', utf8_decode($lang["computers"][22]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'os',    utf8_decode($lang["computers"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'osver', utf8_decode($lang["computers"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'hdtype',    utf8_decode($lang["computers"][36]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'hdspace',    utf8_decode($lang["computers"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'sndcard',    utf8_decode($lang["computers"][33]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'moboard',    utf8_decode($lang["computers"][35]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'gfxcard',    utf8_decode($lang["computers"][34]), FORMAT_TEXTE, 'L',    20 ,'0'),
//      Array( 'network',    utf8_decode($lang["computers"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    utf8_decode($lang["computers"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   utf8_decode($lang["computers"][17]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    utf8_decode($lang["computers"][18]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    utf8_decode($lang["computers"][16]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    utf8_decode($lang["computers"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'deleted',    utf8_decode($lang["common"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'comments', utf8_decode($lang["computers"][19]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     utf8_decode($lang["computers"][11]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifaddr',    utf8_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifmac',    utf8_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'netpoint',    utf8_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'soft',    utf8_decode($lang["software"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'softserial',    utf8_decode($lang["software"][11]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'printname',    utf8_decode($lang["printers"][4]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'printtype',    utf8_decode($lang["printers"][9]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'printserial',    utf8_decode($lang["printers"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'monname',    utf8_decode($lang["monitors"][4]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'montype',    utf8_decode($lang["monitors"][9]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'monserial',    utf8_decode($lang["monitors"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'periphname',    utf8_decode($lang["peripherals"][4]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'periphtype',    utf8_decode($lang["peripherals"][9]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'periphserial',    utf8_decode($lang["peripherals"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),

    );

    
	break;
    // ------------------------------------------------------------------------

	default : 
	echo "Unknown selected table";
	exit();
}	
		$result = $db->query($query_nb);
		$nbrows=$db->numrows($result)+1;
    
    if ($resultat = $db->query($query))
    {
        
	// en-tête HTTP
        // --------------------------------------------------------------------
        header("Content-disposition: filename=".$table.".slk");
        header('Content-type: application/octetstream');
        header('Pragma: no-cache');
        header('Expires: 0');


        // en-tête du fichier
        // --------------------------------------------------------------------
        echo "ID;PGLPI REPORT\n"; // ID;Pappli
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
        echo "B;Y".$nbrows;
        echo ";X".($nbcol = count($champs))."\n"; // B;Yligmax;Xcolmax
        echo "\n";

        // récupération des infos de formatage
        // --------------------------------------------------------------------
        $format=array();
        $num_format=array();
        for ($cpt = 0; $cpt < $nbcol; $cpt++)
        {
            $num_format[$champs[$cpt][0]] = $champs[$cpt][2];
            $format[$champs[$cpt][0]]     = $cfg_formats[ $num_format[$champs[$cpt][0]] ] . $champs[$cpt][3];
        }

        // largeurs des colonnes
        // --------------------------------------------------------------------
        for ($cpt = 1; $cpt <= $nbcol; $cpt++)
        {
            // F;Wcoldeb colfin largeur
            echo "F;W".$cpt." ".$cpt." ".$champs[$cpt-1][4]."\n";
        }
        echo "F;W".$cpt." 256 8\n"; // F;Wcoldeb colfin largeur
        echo "\n";

        // en-tête des colonnes (en gras --> SDM4)
        // --------------------------------------------------------------------
        for ($cpt = 1; $cpt <= $nbcol; $cpt++)
        {
            echo "F;SDM4;FG0C;".($cpt == 1 ? "Y1;" : "")."X".$cpt."\n";
            echo "C;N;K\"".$champs[$cpt-1][1]."\"\n";
        }
        echo "\n";

        // données
        // --------------------------------------------------------------------
		function write_line($enr){
            global $nbcol,$ligne,$num_format,$format,$champs;
            // parcours des champs
			$enr=utf8_decode_deep($enr);
        	if (get_magic_quotes_runtime()) $enr=stripslashes_deep($enr);

            for ($cpt = 0; $cpt < $nbcol; $cpt++)
            {
            	if (isset($_GET["limited"])&&$_GET["limited"]=="yes") $enr[$champs[$cpt][0]]=substr($enr[$champs[$cpt][0]],0,200);
            	
            	$enr[$champs[$cpt][0]]=preg_replace('/\x0A/',' ',$enr[$champs[$cpt][0]]);
            	$enr[$champs[$cpt][0]]=preg_replace('/\x0D/',NULL,$enr[$champs[$cpt][0]]);
            	$enr[$champs[$cpt][0]]=ereg_replace("\"","''",$enr[$champs[$cpt][0]]);
                // format
                echo "F;P".$num_format[$champs[$cpt][0]].";".$format[$champs[$cpt][0]];
                echo ($cpt == 0 ? ";Y".$ligne : "").";X".($cpt+1)."\n";
                // valeur

                if ($num_format[$champs[$cpt][0]] == FORMAT_TEXTE)
                    echo "C;N;K\"".str_replace(';', ';;', $enr[$champs[$cpt][0]])."\"\n"; // ajout des ""
                else
                    echo "C;N;K".$enr[$champs[$cpt][0]]."\n";
            }
            echo "\n";
		}
		

        $ligne = 2;
		$old_ID=-1;
		$ligne_content=array();
        while ($enr = $db->fetch_assoc($resultat))
        {
        	// Same entry
			if ($enr[$champs[0][0]]==$old_ID)      {
				for($i=0;$i<$nbcol;$i++)
				if ($champs[$i][5]){
					$name=$champs[$i][0];
					if ($enr[$champs[$i][0]]!=""&&!is_null($enr[$champs[$i][0]])){
					$value="";
				if($name == "firmware") {
					$value=getDropdownName("glpi_dropdown_firmware",$enr[$champs[$i][0]]);
				}
				elseif($name == "location") {
					$value=getDropdownName("glpi_dropdown_locations",$enr[$champs[$i][0]]);
				}
				elseif($name == "type") {
					$value=getDropdownName("glpi_type_".$table,$enr[$champs[$i][0]]);
				}
				elseif($name == "montype") {
					$value=getDropdownName("glpi_type_monitors",$enr[$champs[$i][0]]);
				}
				elseif($name == "printtype") {
					$value=getDropdownName("glpi_type_printers",$enr[$champs[$i][0]]);
				}
				elseif($name == "periphtype") {
					$value=getDropdownName("glpi_type_peripherals",$enr[$champs[$i][0]]);
				}
				elseif($name == "ramtype") {
					$value=getDropdownName("glpi_dropdown_ram",$enr[$champs[$i][0]]);
				}
				elseif($name == "netpoint") {
					$value=getDropdownName("glpi_dropdown_netpoint",$enr[$champs[$i][0]]);
				}
				elseif($name == "os") {
					$value=getDropdownName("glpi_dropdown_os",$enr[$champs[$i][0]]);
				}
				elseif($name == "hdtype") {
					$value=getDropdownName("glpi_dropdown_hdtype",$enr[$champs[$i][0]]);
				}
				elseif($name == "sndcard") {
					$value=getDropdownName("glpi_dropdown_sndcard",$enr[$champs[$i][0]]);
				}
				elseif($name == "moboard") {
					$value=getDropdownName("glpi_dropdown_moboard",$enr[$champs[$i][0]]);
				}
				elseif($name == "gfxcard") {
					$value=getDropdownName("glpi_dropdown_gfxcard",$enr[$champs[$i][0]]);
				}
				elseif($name == "network") {
					$value=getDropdownName("glpi_dropdown_network",$enr[$champs[$i][0]]);
				}
				elseif($name == "processor") {
					$value=getDropdownName("glpi_dropdown_processor",$enr[$champs[$i][0]]);
				}
						else $value=$enr[$champs[$i][0]];

					if (!ereg($value,$ligne_content[$champs[$i][0]])) $ligne_content[$champs[$i][0]].=" - ".$value;
					}
					
 				}
				}
 			else {
 				$old_ID=$enr[$champs[0][0]];
 				if ($ligne!=2) write_line($ligne_content);
	            		$ligne++;

 				for($i=0;$i<$nbcol;$i++) {
				$name=$champs[$i][0];
				$value="";
				if($name == "firmware") {
					$value=getDropdownName("glpi_dropdown_firmware",$enr[$champs[$i][0]]);
				}
				elseif($name == "location") {
					$value=getDropdownName("glpi_dropdown_locations",$enr[$champs[$i][0]]);
				}
				elseif($name == "type") {
					$value=getDropdownName("glpi_type_".$table,$enr[$champs[$i][0]]);
				}
				elseif($name == "montype") {
					$value=getDropdownName("glpi_type_monitors",$enr[$champs[$i][0]]);
				}
				elseif($name == "printtype") {
					$value=getDropdownName("glpi_type_printers",$enr[$champs[$i][0]]);
				}
				elseif($name == "periphtype") {
					$value=getDropdownName("glpi_type_peripherals",$enr[$champs[$i][0]]);
				}
				elseif($name == "ramtype") {
					$value=getDropdownName("glpi_dropdown_ram",$enr[$champs[$i][0]]);
				}
				elseif($name == "netpoint") {
					$value=getDropdownName("glpi_dropdown_netpoint",$enr[$champs[$i][0]]);
				}
				elseif($name == "os") {
					$value=getDropdownName("glpi_dropdown_os",$enr[$champs[$i][0]]);
				}
				elseif($name == "hdtype") {
					$value=getDropdownName("glpi_dropdown_hdtype",$enr[$champs[$i][0]]);
				}
				elseif($name == "sndcard") {
					$value=getDropdownName("glpi_dropdown_sndcard",$enr[$champs[$i][0]]);
				}
				elseif($name == "moboard") {
					$value=getDropdownName("glpi_dropdown_moboard",$enr[$champs[$i][0]]);
				}
				elseif($name == "gfxcard") {
					$value=getDropdownName("glpi_dropdown_gfxcard",$enr[$champs[$i][0]]);
				}
				elseif($name == "network") {
					$value=getDropdownName("glpi_dropdown_network",$enr[$champs[$i][0]]);
				}
				elseif($name == "processor") {
					$value=getDropdownName("glpi_dropdown_processor",$enr[$champs[$i][0]]);
				}
				else {
				$value=$enr[$champs[$i][0]];
				 }
 				if ($champs[$i][5]&&!empty($value)&&!is_null($value)) $ligne_content[$champs[$i][0]]=" - ".$value;
				else $ligne_content[$champs[$i][0]]=$value;
 				}
           
	           }
	           
        }
		write_line($ligne_content);

        // fin du fichier
        // --------------------------------------------------------------------
        echo "E\n";
    }

?> 