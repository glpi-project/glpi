<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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
 ----------------------------------------------------------------------
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

*/

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
		
		$query = "select glpi_printers.*, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= "from glpi_printers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 3 AND glpi_networking_ports.on_device = glpi_printers.ID) ";
		$query.=" ORDER by glpi_printers.ID";
//		echo $query;
//	exit;
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     unhtmlentities($lang["printers"][19]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', unhtmlentities($lang["printers"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    unhtmlentities($lang["printers"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    unhtmlentities($lang["printers"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ramSize',    unhtmlentities($lang["printers"][23]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    unhtmlentities($lang["printers"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    unhtmlentities($lang["printers"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'serial',    unhtmlentities($lang["printers"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    unhtmlentities($lang["printers"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'flags_serial',     unhtmlentities($lang["printers"][14]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_par', unhtmlentities($lang["printers"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_usb',    unhtmlentities($lang["printers"][27]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifaddr', unhtmlentities($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    unhtmlentities($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    unhtmlentities($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'maintenance',    unhtmlentities($lang["printers"][22]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'achat_date',    unhtmlentities($lang["printers"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   unhtmlentities($lang["printers"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments',    unhtmlentities($lang["printers"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',    unhtmlentities($lang["printers"][16]), FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "networking" :
		$query_nb = "select distinct glpi_networking.ID from glpi_networking";
		
		$query = "select glpi_networking.*, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= "from glpi_networking LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 2 AND glpi_networking_ports.on_device = glpi_networking.ID)";
		$query.=" ORDER by glpi_networking.ID";

//		echo $query;
//		exit;	
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     unhtmlentities($lang["networking"][50]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', unhtmlentities($lang["networking"][0]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    unhtmlentities($lang["networking"][2]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    unhtmlentities($lang["networking"][1]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'firmware',     unhtmlentities($lang["networking"][49]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ram',    unhtmlentities($lang["networking"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   unhtmlentities($lang["networking"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    unhtmlentities($lang["networking"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    unhtmlentities($lang["networking"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    unhtmlentities($lang["networking"][4]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ifaddr', unhtmlentities($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    unhtmlentities($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    unhtmlentities($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'achat_date',    unhtmlentities($lang["networking"][39]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   unhtmlentities($lang["networking"][40]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    unhtmlentities($lang["networking"][41]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', unhtmlentities($lang["networking"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     unhtmlentities($lang["networking"][9]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "monitors" :
		$query_nb = "select distinct glpi_monitors.ID from glpi_monitors";
		
		$query = "select * from glpi_monitors ORDER BY glpi_monitors.ID";

//		echo $query;
//		exit;	
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     unhtmlentities($lang["monitors"][23]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', unhtmlentities($lang["monitors"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    unhtmlentities($lang["monitors"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'size',    unhtmlentities($lang["monitors"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    unhtmlentities($lang["monitors"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   unhtmlentities($lang["monitors"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    unhtmlentities($lang["monitors"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    unhtmlentities($lang["monitors"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    unhtmlentities($lang["monitors"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'flags_micro',    unhtmlentities($lang["monitors"][14]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_speaker',    unhtmlentities($lang["monitors"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_subd',    unhtmlentities($lang["monitors"][19]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_bnc',    unhtmlentities($lang["monitors"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'achat_date',    unhtmlentities($lang["monitors"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   unhtmlentities($lang["monitors"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    unhtmlentities($lang["monitors"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', unhtmlentities($lang["monitors"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     unhtmlentities($lang["monitors"][16]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "peripherals" :
		$query_nb = "select distinct glpi_peripherals.ID from glpi_peripherals";
		
		$query = "select * from glpi_peripherals ORDER BY glpi_peripherals.ID";

//		echo $query;
//		exit;	
	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     unhtmlentities($lang["peripherals"][23]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', unhtmlentities($lang["peripherals"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    unhtmlentities($lang["peripherals"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'brand',    unhtmlentities($lang["peripherals"][18]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    unhtmlentities($lang["peripherals"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   unhtmlentities($lang["peripherals"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    unhtmlentities($lang["peripherals"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    unhtmlentities($lang["peripherals"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    unhtmlentities($lang["peripherals"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'achat_date',    unhtmlentities($lang["peripherals"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   unhtmlentities($lang["peripherals"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    unhtmlentities($lang["peripherals"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', unhtmlentities($lang["peripherals"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     unhtmlentities($lang["peripherals"][16]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "computers" :
		$query_nb = "select distinct glpi_computers.ID from glpi_computers";
		$query="";
		
/*		$query = "(select glpi_computers.*, CONCAT(glpi_software.name, glpi_software.version) AS soft, glpi_licenses.serial as softserial, ";
		$query.= " glpi_monitors.name as monname, glpi_monitors.type as montype, glpi_monitors.serial as monserial, ";
		$query.= " glpi_printers.name as printname, glpi_printers.type as printtype, glpi_printers.serial as printserial, ";
		$query.= " glpi_peripherals.name as periphname, glpi_peripherals.type as periphtype, glpi_peripherals.serial as periphserial, ";
		$query.= " glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= " from glpi_computers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 1 AND glpi_networking_ports.on_device = glpi_computers.ID)";
		$query.= " LEFT JOIN glpi_inst_software ON (glpi_inst_software.cID = glpi_computers.ID) ";
		$query.= " LEFT JOIN glpi_licenses ON (glpi_inst_software.license = glpi_licenses.ID) ";
		$query.= " LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) ";
		$query.= " LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.end1 = glpi_computers.ID) ";
		$query.= " LEFT JOIN glpi_monitors ON (glpi_connect_wire.end2 = glpi_monitors.ID AND glpi_connect_wire.type = '4') ";
		$query.= " LEFT JOIN glpi_peripherals ON (glpi_connect_wire.end2 = glpi_peripherals.ID AND glpi_connect_wire.type = '5') ";
		$query.= " LEFT JOIN glpi_printers ON (glpi_connect_wire.end2 = glpi_printers.ID AND glpi_connect_wire.type = '3') ";
		$query.=")";
		$query.= " ORDER by glpi_computers.ID";
*/		
		$query = "(select glpi_computers.*, CONCAT(glpi_software.name, glpi_software.version) AS soft, glpi_licenses.serial as softserial, ";
		$query.= " glpi_monitors.name as monname, glpi_monitors.type as montype, glpi_monitors.serial as monserial, ";
		$query.= " glpi_printers.name as printname, glpi_printers.type as printtype, glpi_printers.serial as printserial, ";
		$query.= " glpi_peripherals.name as periphname, glpi_peripherals.type as periphtype, glpi_peripherals.serial as periphserial, ";
		$query.= " glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= " from glpi_computers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 1 AND glpi_networking_ports.on_device = glpi_computers.ID)";
		$query.= " LEFT JOIN glpi_inst_software ON (glpi_inst_software.cID = glpi_computers.ID) ";
		$query.= " LEFT JOIN glpi_licenses ON (glpi_inst_software.license = glpi_licenses.ID) ";
		$query.= " LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) ";
		$query.= " LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.end1 = glpi_computers.ID) ";
		$query.= " LEFT JOIN glpi_monitors ON (glpi_connect_wire.end2 = glpi_monitors.ID AND glpi_connect_wire.type = '4') ";
		$query.= " LEFT JOIN glpi_peripherals ON (glpi_connect_wire.end2 = glpi_peripherals.ID AND glpi_connect_wire.type = '5') ";
		$query.= " LEFT JOIN glpi_printers ON (glpi_connect_wire.end2 = glpi_printers.ID AND glpi_connect_wire.type = '3') ";
		$query.=")";
		$query.= " ORDER by glpi_computers.ID";
		
		//echo $query;
		//exit;

	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     unhtmlentities($lang["computers"][31]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', unhtmlentities($lang["computers"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    unhtmlentities($lang["computers"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_server', unhtmlentities($lang["computers"][28]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ramtype',    unhtmlentities($lang["computers"][23]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ram',   unhtmlentities($lang["computers"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'processor',    unhtmlentities($lang["computers"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'processor_speed', unhtmlentities($lang["computers"][22]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'os',    unhtmlentities($lang["computers"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'osver', unhtmlentities($lang["computers"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'hdtype',    unhtmlentities($lang["computers"][36]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'hdspace',    unhtmlentities($lang["computers"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'sndcard',    unhtmlentities($lang["computers"][33]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'moboard',    unhtmlentities($lang["computers"][35]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'gfxcard',    unhtmlentities($lang["computers"][34]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'network',    unhtmlentities($lang["computers"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    unhtmlentities($lang["computers"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   unhtmlentities($lang["computers"][17]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    unhtmlentities($lang["computers"][18]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    unhtmlentities($lang["computers"][16]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    unhtmlentities($lang["computers"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'achat_date',    unhtmlentities($lang["computers"][41]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   unhtmlentities($lang["computers"][42]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    unhtmlentities($lang["computers"][43]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', unhtmlentities($lang["computers"][19]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     unhtmlentities($lang["computers"][11]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifaddr',    unhtmlentities($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifmac',    unhtmlentities($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'netpoint',    unhtmlentities($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'soft',    unhtmlentities($lang["software"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'softserial',    unhtmlentities($lang["software"][11]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'printname',    unhtmlentities($lang["printers"][4]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'printtype',    unhtmlentities($lang["printers"][9]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'printserial',    unhtmlentities($lang["printers"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'monname',    unhtmlentities($lang["monitors"][4]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'montype',    unhtmlentities($lang["monitors"][9]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'monserial',    unhtmlentities($lang["monitors"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'periphname',    unhtmlentities($lang["peripherals"][4]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'periphtype',    unhtmlentities($lang["peripherals"][9]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'periphserial',    unhtmlentities($lang["peripherals"][10]), FORMAT_TEXTE, 'L',    20 ,'1'),

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
        echo ";X".($nbcol = $db->num_fields($resultat))."\n"; // B;Yligmax;Xcolmax
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
			$enr=unhtmlentities_deep($enr);
        	if (get_magic_quotes_runtime()) $enr=stripslashes_deep($enr);

            for ($cpt = 0; $cpt < $nbcol; $cpt++)
            {
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
        while ($enr = mysql_fetch_assoc($resultat))
        {
        	// Same entry
			if ($enr[$champs[0][0]]==$old_ID)      {
				for($i=0;$i<$nbcol;$i++)
				if ($champs[$i][5]){
					$name=$champs[$i][0];
					if ($enr[$champs[$i][0]]!=""&&!is_null($enr[$champs[$i][0]])){
					$value="";
						if($name == "montype") {
							$value=getDropdownName("glpi_type_monitors",$enr[$champs[$i][0]]);
						}
						elseif($name == "printtype") {
							$value=getDropdownName("glpi_type_printers",$enr[$champs[$i][0]]);
						}
						elseif($name == "periphtype") {
							$value=getDropdownName("glpi_type_peripherals",$enr[$champs[$i][0]]);
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
					$$value=getDropdownName("glpi_dropdown_processor",$enr[$champs[$i][0]]);
				}
				else {
				$value=$enr[$champs[$i][0]];
				 }
 				if ($champs[$i][5]&&$enr[$champs[$i][0]]!=""&&!is_null($enr[$champs[$i][0]])) $ligne_content[$champs[$i][0]]=" - ".$value;
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