<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre & Bazile Lebeau
// Purpose of file:
// ----------------------------------------------------------------------

//#################### INCLUDE & SESSIONS ############################
include ("_relpos.php");
include ($phproot . "/glpi/common/classes.php");
include ($phproot . "/glpi/common/functions.php");
include ($phproot . "/glpi/config/based_config.php");
include($cfg_install["config_dir"] . "/config_db.php");

if(!session_id()){@session_start();}



//################################ Functions ################################

function FieldExists($table, $field) {
	$db = new DB;
	$result = $db->query("SELECT * FROM ". $table ."");
	$fields = $db->num_fields($result);
	$var1 = false;
	for ($i=0; $i < $fields; $i++) {
		$name  = $db->field_name($result, $i);
		if(strcmp($name,$field)==0) {
			$var1 = true;
		}
	}
	return $var1;
}


function loadLang() {
			unset($lang);
			global $lang;
			include ("_relpos.php");
			$file = $phproot ."/glpi/dicts/".$_SESSION["dict"].".php";
			include($file);
}

$max_time=min(get_cfg_var("max_execution_time"),get_cfg_var("max_input_time"));
if ($max_time>5) {$defaulttimeout=$max_time-2;$defaultrowlimit=1;}
else {$defaulttimeout=1;$defaultrowlimit=1;}

$db=new DB;

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
    flush();
    }

}

function test_content_ok(){
	$db = new DB;
	
	$query1="SELECT ID FROM glpi_computers WHERE  comments LIKE '%\\\\\\%';";
	$query2="SELECT ID FROM glpi_printers WHERE  comments LIKE '%\\\\\\%';";	
	$query3="SELECT ID FROM glpi_tracking WHERE  contents LIKE '%\\\\\\%';";	
	$query4="SELECT ID FROM glpi_followups WHERE  contents LIKE '%\\\\\\%';";	
	
	$result1=$db->query($query1);
	if ($db->numrows($result1)>0)
		return false;
	$result4=$db->query($query4);
	if ($db->numrows($result4)>0)
		return false;	
	$result3=$db->query($query3);
	if ($db->numrows($result3)>0)
		return false;
	$result2=$db->query($query2);
	if ($db->numrows($result2)>0)
		return false;
	return true;		
	}

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

function get_update_content($db, $table,$from,$limit,$conv_utf8)
{
     $content="";
     $result = $db->query("SELECT * FROM $table LIMIT $from,$limit");
     
     if($result)
     while($row = $db->fetch_assoc($result)) {
         if (isset($row["ID"])) {
     		if (get_magic_quotes_runtime()) $row=stripslashes_deep($row);
     		$row=stripslashes_deep($row);
         	$insert = "UPDATE $table SET ";
         	foreach ($row as $key => $val) {
	         	$insert.=" ".$key."=";
	         	
            	if(!isset($val)) $insert .= "NULL,";
            	else if($val != "") {
            		if ($conv_utf8) {
			// Gestion users AD qui sont déjà en UTF8
			if ($table!="glpi_users"||!seems_utf8($val))
				$val=utf8_encode($val);
			}
            		$insert .= "'".addslashes($val)."',";
            		}
            	else $insert .= "'',";
         	}
         	$insert = ereg_replace(",$","",$insert);
         	$insert.=" WHERE ID = '".$row["ID"]."' ";
         	$insert .= ";\n";
         	$content .= $insert;
		}
     }
     //if ($table=="glpi_dropdown_locations") echo $content;
     return $content;
}


function UpdateContent($db, $duree,$rowlimit,$conv_utf8)
{
// $dumpFile, fichier source
// $database, nom de la base de données cible
// $mysqlUser, login pouyr la connexion au serveur MySql
// $mysqlPassword, mot de passe
// $histMySql, nom de la machine serveur MySQl
// $duree=timeout pour changement de page (-1 = aucun)


global $TPSCOUR,$offsettable,$offsetrow,$cpt;
if ($db->error)
{
     echo "Connexion impossible à $hostMySql pour $mysqlUser";
     return FALSE;
}

$result=$db->list_tables();
$numtab=0;
while ($t=$db->fetch_array($result)){
	if (ereg("glpi_",$t[0])){
	$tables[$numtab]=$t[0];
$numtab++;
}
}


for (;$offsettable<$numtab;$offsettable++){
// Dump de la strucutre table
if ($offsetrow==-1){
	$offsetrow++;
	$cpt++;
	}
    current_time();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;

	$fin=0;
	while (!$fin){
	$todump=get_update_content($db,$tables[$offsettable],$offsetrow,$rowlimit,$conv_utf8);
//	echo $todump."<br>";
	$rowtodump=substr_count($todump, "UPDATE ");
	if ($rowtodump>0){
//	echo $todump;
	$result = $db->query($todump);
//	if (!$result) echo "ECHEC ".$todump;
	
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
if ($db->error())
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".$db->error()."<hr>";
$offsettable=-1;
return TRUE;
}

//########################### Script start ################################

loadLang();

		// Send UTF8 Headers
		header("Content-Type: text/html; charset=UTF-8");

//style and co
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
       
        echo "<style type=\"text/css\">";
        echo "<!--

        /*  ... Definition des styles ... */

        body {
        background-color:#C5DAC8;
        color:#000000; }
        
       .principal {
        background-color: #ffffff;
        font-family: Verdana;font-size:12px;
        text-align: justify ; 
        -moz-border-radius: 4px;
	border: 1px solid #FFC65D;
         margin: 40px; 
         padding: 40px 40px 10px 40px;
       }

       table {
       text-align:center;
       border: 0;
       margin: 20px;
       margin-left: auto;
       margin-right: auto;
       width: 90%;}

       .red { color:red;}
       .green {color:green;}
       
       h2 {
        color:#FFC65D;
        text-align:center;}

       h3 {
        text-align:center;}

        input {border: 1px solid #ccc;}

        fieldset {
        padding: 20px;
          border: 1px dotted #ccc;
        font-size: 12px;
        font-weight:200;}

        .submit { text-align:center;}
       
        input.submit {
        border:1px solid #000000;
        background-color:#eeeeee;
        }
        
        input.submit:hover {
        border:1px solid #cccccc;
       background-color:#ffffff;
        }

	.button {
        font-weight:200;
	color:#000000;
	padding:5px;
	text-decoration:none;
	border:1px solid #009966;
        background-color:#eeeeee;
        }

        .button:hover{
          font-weight:200;
	  color:#000000;
	 padding:5px;
	text-decoration:none;
	border:1px solid #009966;
       background-color:#ffffff;
        }
	
        -->  ";
        echo "</style>";
         echo "</head>";
        echo "<body>";
	echo "<div class=\"principal\">";
//end style and co
/*if (!isset($_POST["oui"])&&!isset($_POST["non"])&&!isset($_GET["dump"]))
if (test_content_ok()) {
	echo "<div align=\"center\">";
	echo $lang["update"]["108"];
	echo $lang["update"]["109"];
	echo "<form action=\"update_content.php\" method=\"post\">";
	echo "<input type=\"submit\" class='submit' name=\"oui\" value=\"Oui\" />&nbsp;&nbsp;";
	echo "<input type=\"submit\" class='submit' name=\"non\" value=\"Non\" />";
	echo "</form></div>";
}
else {
	echo "<div align=\"center\">";
	echo $lang["update"]["110"];
	echo $lang["update"]["109"];
	echo "<form action=\"update_content.php\" method=\"post\">";
	echo "<input type=\"submit\" class='submit' name=\"oui\" value=\"Oui\" />&nbsp;&nbsp;";
	echo "<input type=\"submit\" class='submit' name=\"non\" value=\"Non\" />";
	echo "</form></div>";

}
*/
// #################" UPDATE CONTENT #################################

    $time_file=date("Y-m-d-h-i");
	$cur_time=date("Y-m-d H:i");

init_time(); //initialise le temps
//début de fichier
if (!isset($_GET["offsettable"])) $offsettable=0; 
else $offsettable=$_GET["offsettable"]; 
//début de fichier
if (!isset($_GET["offsetrow"])) $offsetrow=-1; 
else $offsetrow=$_GET["offsetrow"];
//timeout de 5 secondes par défaut, -1 pour utiliser sans timeout
if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
else $duree=$_GET["duree"];
//Limite de lignes à dumper à chaque fois
if (!isset($_GET["rowlimit"])) $rowlimit=$defaultrowlimit; 
else  $rowlimit=$_GET["rowlimit"];

$tab=$db->list_tables();
$tot=$db->numrows($tab);
if(isset($offsettable)){
if ($offsettable>=0)
$percent=min(100,round(100*$offsettable/$tot,0));
else $percent=100;
}
else $percent=0;
if ($percent >= 0) {
 
 $percentwitdh=$percent*4;

	echo "<div align='center'><table class='tab_cadre' width='400'><tr><td width='400' align='center'> Progression ".$percent."%</td></tr><tr><td><table><tr><td bgcolor='red'  width='$percentwitdh' height='20'>&nbsp;</td></tr></table></td></tr></table></div>";

}
$conv_utf8=false;
if(!FieldExists("glpi_config","utf8_conv")) {
$conv_utf8=true;
}

flush();
if ($offsettable>=0){
	if (UpdateContent($db,$duree,$rowlimit,$conv_utf8))
	{
    echo "<br>Redirection automatique sinon cliquez <a href=\"update_content.php?dump=1&amp;duree=$duree&amp;rowlimit=$rowlimit&amp;offsetrow=$offsetrow&amp;offsettable=$offsettable&amp;cpt=$cpt\">ici</a>";
    echo "<script language=\"javascript\" type=\"text/javascript\">window.location=\"update_content.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt\";</script>";
echo "</div>";

	flush();    
	exit;
	}
}
else  { 
//echo "<div align='center'><p>Terminé. Nombre de requêtes totales traitées : $cpt</p></div>";
	echo "<p class='submit'> <a href=\"index.php\"><span class='button'>".$lang["install"][64]."</span></a></p>";
echo "</div>";

}

if ($conv_utf8){
$db=new DB;
$query = "ALTER TABLE `glpi_config` ADD `utf8_conv` INT( 11 ) DEFAULT '0' NOT NULL";
$db->query($query) or die(" 0.6 add utf8_conv to glpi_config".$lang["update"][90].$db->error());
}
//}
	
/*if (isset($_POST["non"])){
	// Redirection à faire
	
	echo "<p class='submit'> <a href=\"index.php\"><span class='button'>".$lang["install"][64]."</span></a></p>";
}
*/
?>
