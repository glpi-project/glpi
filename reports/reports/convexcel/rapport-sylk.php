<?php

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

		function write_line($enr){
            global $nbcol,$ligne,$num_format,$format;
            // parcours des champs
            for ($cpt = 0; $cpt < $nbcol; $cpt++)
            {
            	$enr[$cpt]=preg_replace('/\x0A/',' ',$enr[$cpt]);
            	$enr[$cpt]=preg_replace('/\x0D/','',$enr[$cpt]);
                // format
                echo "F;P".$num_format[$cpt].";".$format[$cpt];
                echo ($cpt == 0 ? ";Y".$ligne : "").";X".($cpt+1)."\n";
                // valeur
                if ($num_format[$cpt] == FORMAT_TEXTE)
                    echo "C;N;K\"".str_replace(';', ';;', $enr[$cpt])."\"\n"; // ajout des ""
                else
                    echo "C;N;K".$enr[$cpt]."\n";
            }
            echo "\n";
		}

// ----------------------------------------------------------------------------
$db = new DB;
// ----------------------------------------------------------------------------

    // construction de la requête
    // ------------------------------------------------------------------------
$query_nb = "select distinct glpi_networking.ID from glpi_networking";
$result = $db->query($query_nb);
$nbrows=$db->numrows($result)+1;

$query = "select glpi_networking.*, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac ";
$query.= "from glpi_networking LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 2 AND glpi_networking_ports.on_device = glpi_networking.ID) ORDER by glpi_networking.ID";
//echo $query;


/*    $sql  = "SELECT code, rubrique, titre, auteur ";
    $sql .= "FROM astuces ";
    $sql .= "WHERE theme=1 "; // PHP
    $sql .= "ORDER BY rubrique, titre";
*/
    $champs = Array(
      //     champ       en-tête     format         align  width
      Array( 'ID',     html_entity_decode($lang["networking"][42]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'name', html_entity_decode($lang["networking"][0]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ram',    html_entity_decode($lang["networking"][5]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'serial',   html_entity_decode($lang["networking"][6]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'otherserial',    html_entity_decode($lang["networking"][7]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contact',    html_entity_decode($lang["networking"][3]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'contactnum',    html_entity_decode($lang["networking"][4]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'date_mod',     html_entity_decode($lang["networking"][9]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'comments', html_entity_decode($lang["networking"][8]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'achat_date',    html_entity_decode($lang["networking"][39]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'date_fin_garantie',   html_entity_decode($lang["networking"][40]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'maintenance',    html_entity_decode($lang["networking"][41]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'location',    html_entity_decode($lang["networking"][1]), FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'type',    html_entity_decode($lang["networking"][2]), FORMAT_TEXTE, 'L',    20 ,'0'),      
      Array( 'firmware',     html_entity_decode($lang["networking"][49]),FORMAT_TEXTE, 'L',    20 ,'0'),
      Array( 'ifaddr', html_entity_decode($lang["networking"][14]), FORMAT_TEXTE, 'L',    20 ,'1'),
      Array( 'ifmac',    html_entity_decode($lang["networking"][15]), FORMAT_TEXTE, 'L',    20 ,'1')
    );
    // ------------------------------------------------------------------------




    if ($resultat = $db->query($query))
    {
        // en-tête HTTP
        // --------------------------------------------------------------------
        header('Content-disposition: filename=reseau.slk');
        header('Content-type: application/octetstream');
        header('Pragma: no-cache');
        header('Expires: 0');


        // en-tête du fichier
        // --------------------------------------------------------------------
        echo "ID;PASTUCES-phpInfo.net\n"; // ID;Pappli
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
        for ($cpt = 0; $cpt < $nbcol; $cpt++)
        {
            $num_format[$cpt] = $champs[$cpt][2];
            $format[$cpt]     = $cfg_formats[ $num_format[$cpt] ] . $champs[$cpt][3];
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
		

        $ligne = 2;
		$old_ID=-1;
		$ligne_content=array();
        while ($enr = mysql_fetch_array($resultat))
        {
//        	print_r($champs);
//        	echo $enr[0]."----";
        	// Same entry
        	
 			if ($enr[0]==$old_ID)      {
// 				echo "same";
				for($i=0;$i<$nbcol;$i++)
				if ($champs[$i][5]&&$enr[$i]!="") $ligne_content[$i].=" - ".$enr[$i];
 				}
 			else {
// 				echo "new";
 				$old_ID=$enr[0];
 				if ($ligne!=2) write_line($ligne_content);
	            $ligne++;

 				for($i=0;$i<$nbcol;$i++) {
 					//echo $i."+++";
				$name=$db->field_name($resultat,$i);
				//echo $name."--";
				if($name == "firmware") {
					$ligne_content[$i]=getDropdownName("glpi_dropdown_firmware",$enr[$i]);
				}
				elseif($name == "location") {
					$ligne_content[$i]=getDropdownName("glpi_dropdown_locations",$enr[$i]);
				}
				elseif($name == "type") {
					$ligne_content[$i]=getDropdownName("glpi_type_networking",$enr[$i]);
				}
				else
				 $ligne_content[$i]=$enr[$i];
 				
 				}
 				//for($i=0;$i<$nbcol;$i++) echo $i."--".$ligne_content[$i]."\n";//
	           
	           }
	           
        }
       // print_r($ligne_content);
		write_line($ligne_content);

        // fin du fichier
        // --------------------------------------------------------------------
        echo "E\n";
    }

?> 