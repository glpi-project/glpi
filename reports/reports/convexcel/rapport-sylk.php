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
      Array( 'ID',     html_entity_decode($lang["printers"][19]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', html_entity_decode($lang["printers"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    html_entity_decode($lang["printers"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    html_entity_decode($lang["printers"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ramSize',    html_entity_decode($lang["printers"][23]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    html_entity_decode($lang["printers"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    html_entity_decode($lang["printers"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'serial',    html_entity_decode($lang["printers"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    html_entity_decode($lang["printers"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'flags_serial',     html_entity_decode($lang["printers"][14]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_par', html_entity_decode($lang["printers"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_usb',    html_entity_decode($lang["printers"][27]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifaddr', html_entity_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    html_entity_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    html_entity_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'maintenance',    html_entity_decode($lang["printers"][22]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'achat_date',    html_entity_decode($lang["printers"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   html_entity_decode($lang["printers"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments',    html_entity_decode($lang["printers"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',    html_entity_decode($lang["printers"][16]), FORMAT_TEXTE, 'L',    20 ,'0'),
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
      Array( 'ID',     html_entity_decode($lang["networking"][50]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', html_entity_decode($lang["networking"][0]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    html_entity_decode($lang["networking"][2]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    html_entity_decode($lang["networking"][1]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'firmware',     html_entity_decode($lang["networking"][49]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ram',    html_entity_decode($lang["networking"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   html_entity_decode($lang["networking"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    html_entity_decode($lang["networking"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    html_entity_decode($lang["networking"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    html_entity_decode($lang["networking"][4]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ifaddr', html_entity_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    html_entity_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1'),
	  Array( 'netpoint',    html_entity_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'achat_date',    html_entity_decode($lang["networking"][39]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   html_entity_decode($lang["networking"][40]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    html_entity_decode($lang["networking"][41]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', html_entity_decode($lang["networking"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     html_entity_decode($lang["networking"][9]),FORMAT_TEXTE, 'L',    20 ,'0'),
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
      Array( 'ID',     html_entity_decode($lang["monitors"][23]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', html_entity_decode($lang["monitors"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    html_entity_decode($lang["monitors"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'size',    html_entity_decode($lang["monitors"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    html_entity_decode($lang["monitors"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   html_entity_decode($lang["monitors"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    html_entity_decode($lang["monitors"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    html_entity_decode($lang["monitors"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    html_entity_decode($lang["monitors"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'flags_micro',    html_entity_decode($lang["monitors"][14]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_speaker',    html_entity_decode($lang["monitors"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_subd',    html_entity_decode($lang["monitors"][19]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_bnc',    html_entity_decode($lang["monitors"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'achat_date',    html_entity_decode($lang["monitors"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   html_entity_decode($lang["monitors"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    html_entity_decode($lang["monitors"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', html_entity_decode($lang["monitors"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     html_entity_decode($lang["monitors"][16]),FORMAT_TEXTE, 'L',    20 ,'0'),
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
      Array( 'ID',     html_entity_decode($lang["peripherals"][23]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', html_entity_decode($lang["peripherals"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    html_entity_decode($lang["peripherals"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'brand',    html_entity_decode($lang["peripherals"][18]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'location',    html_entity_decode($lang["peripherals"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   html_entity_decode($lang["peripherals"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    html_entity_decode($lang["peripherals"][11]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    html_entity_decode($lang["peripherals"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    html_entity_decode($lang["peripherals"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'achat_date',    html_entity_decode($lang["peripherals"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   html_entity_decode($lang["peripherals"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    html_entity_decode($lang["peripherals"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', html_entity_decode($lang["peripherals"][12]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     html_entity_decode($lang["peripherals"][16]),FORMAT_TEXTE, 'L',    20 ,'0'),
    );
 	break;
    // ------------------------------------------------------------------------
	case "computers" :
		$query_nb = "select distinct glpi_computers.ID from glpi_computers";
		
		$query = "select glpi_computers.*, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac, glpi_networking_ports.netpoint ";
		$query.= "from glpi_computers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 1 AND glpi_networking_ports.on_device = glpi_computers.ID)";
		$query.= " ORDER by glpi_computers.ID";

	    $champs = Array(
      //     champ       en-tête     format         align  width multiple_zone
      Array( 'ID',     html_entity_decode($lang["computers"][31]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', html_entity_decode($lang["computers"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    html_entity_decode($lang["computers"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'flags_server', html_entity_decode($lang["computers"][28]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ramtype',    html_entity_decode($lang["computers"][23]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ram',   html_entity_decode($lang["computers"][24]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'processor',    html_entity_decode($lang["computers"][21]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'processor_speed', html_entity_decode($lang["computers"][22]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'os',    html_entity_decode($lang["computers"][9]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'osver', html_entity_decode($lang["computers"][20]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'hdtype',    html_entity_decode($lang["computers"][36]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'hdspace',    html_entity_decode($lang["computers"][25]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'sndcard',    html_entity_decode($lang["computers"][33]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'moboard',    html_entity_decode($lang["computers"][35]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'gfxcard',    html_entity_decode($lang["computers"][34]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'network',    html_entity_decode($lang["computers"][26]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    html_entity_decode($lang["computers"][10]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   html_entity_decode($lang["computers"][17]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    html_entity_decode($lang["computers"][18]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    html_entity_decode($lang["computers"][16]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact_num',    html_entity_decode($lang["computers"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'ifaddr',    html_entity_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifmac',    html_entity_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'0'),      
	  Array( 'netpoint',    html_entity_decode($lang["networking"][51]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'achat_date',    html_entity_decode($lang["computers"][41]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   html_entity_decode($lang["computers"][42]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    html_entity_decode($lang["computers"][43]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', html_entity_decode($lang["computers"][19]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_mod',     html_entity_decode($lang["computers"][11]),FORMAT_TEXTE, 'L',    20 ,'0'),
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

            for ($cpt = 0; $cpt < $nbcol; $cpt++)
            {
            	$enr[$champs[$cpt][0]]=preg_replace('/\x0A/',' ',$enr[$champs[$cpt][0]]);
            	$enr[$champs[$cpt][0]]=preg_replace('/\x0D/','',$enr[$champs[$cpt][0]]);
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
				if ($champs[$i][5]&&$enr[$champs[$i][0]]!="") $ligne_content[$champs[$i][0]].=" - ".$enr[$champs[$i][0]];
 				}
 			else {
 				$old_ID=$enr[$champs[0][0]];
 				if ($ligne!=2) write_line($ligne_content);
	            $ligne++;

 				for($i=0;$i<$nbcol;$i++) {
				$name=$champs[$i][0];
				if($name == "firmware") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_firmware",$enr[$champs[$i][0]]);
				}
				elseif($name == "location") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_locations",$enr[$champs[$i][0]]);
				}
				elseif($name == "type") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_type_".$table,$enr[$champs[$i][0]]);
				}
				elseif($name == "ramtype") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_ram",$enr[$champs[$i][0]]);
				}
				elseif($name == "netpoint") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_netpoint",$enr[$champs[$i][0]]);
				}
				elseif($name == "os") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_os",$enr[$champs[$i][0]]);
				}
				elseif($name == "hdtype") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_hdtype",$enr[$champs[$i][0]]);
				}
				elseif($name == "sndcard") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_sndcard",$enr[$champs[$i][0]]);
				}
				elseif($name == "moboard") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_moboard",$enr[$champs[$i][0]]);
				}
				elseif($name == "gfxcard") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_gfxcard",$enr[$champs[$i][0]]);
				}
				elseif($name == "network") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_network",$enr[$champs[$i][0]]);
				}
				elseif($name == "processor") {
					$ligne_content[$champs[$i][0]]=getDropdownName("glpi_dropdown_processor",$enr[$champs[$i][0]]);
				}
				else
				 $ligne_content[$champs[$i][0]]=$enr[$champs[$i][0]];
 				
 				}
           
	           }
	           
        }
		write_line($ligne_content);

        // fin du fichier
        // --------------------------------------------------------------------
        echo "E\n";
    }

?> 