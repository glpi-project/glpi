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
 Original Author of file: Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
include ("_relpos.php");
include ($phproot . "/glpi/common/classes.php");
include ($phproot . "/glpi/common/functions.php");
include ($phproot . "/glpi/config/config_db.php");

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
	
	$query1="SELECT ID FROM glpi_computers WHERE  comments LIKE '%\'%';";
	$query2="SELECT ID FROM glpi_printers WHERE  comments LIKE '%\'%';";	
	$query3="SELECT ID FROM glpi_tracking WHERE  contents LIKE '%\'%';";	
	$query4="SELECT ID FROM glpi_followups WHERE  contents LIKE '%\'%';";	
	
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



function get_update_content($db, $table,$from,$limit)
{
     $content="";
     $result = $db->query("SELECT * FROM $table LIMIT $from,$limit");
     
     if($result)
     while($row = mysql_fetch_assoc($result)) {
         if (!isset($row["ID"])) {echo "ERROR";exit();}
     	if (get_magic_quotes_runtime()) $row=stripslashes_deep($row);
     	$row=stripslashes_deep($row);
         $insert = "UPDATE $table SET ";
         foreach ($row as $key => $val) {
         	$insert.=" ".$key."=";
            if(!isset($val)) $insert .= "NULL,";
            else if($val != "") $insert .= "'".addslashes($val)."',";
            else $insert .= "'',";
         }
         $insert = ereg_replace(",$","",$insert);
         $insert.=" WHERE ID = '".$row["ID"]."' ";
         $insert .= ";\n";
         $content .= $insert;
     }
     return $content;
}


function UpdateContent($db, $duree,$rowlimit)
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
	$tables[$numtab]=$t[0];
$numtab++;
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
	$todump=get_update_content($db,$tables[$offsettable],$offsetrow,$rowlimit);
//	echo $todump."<br>";
	$rowtodump=substr_count($todump, "UPDATE ");
	if ($rowtodump>0){
//	echo $todump;
	$result = mysql_query($todump);
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
if (mysql_error())
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".mysql_error()."<hr>";
$offsettable=-1;
fclose($fileHandle);
return TRUE;
}

if (!isset($_POST["oui"])&&!isset($_POST["non"])&&!isset($_GET["dump"]))
if (test_content_ok()) {
	echo "La mise à jour du contenu ne semble pas nécessaire. ";
	echo "Voulez vous tout de même la faire ?";
	echo "<form action=\"update_content.php\" method=\"post\">";
	echo "<input type=\"submit\" class='submit' name=\"oui\" value=\"Oui\" />";
	echo "<input type=\"submit\" class='submit' name=\"non\" value=\"Non\" />";
	echo "</div></form>";
	}
else {
	echo "la mise à jour semble nécessaire. ";
	echo "Voulez vous la faire ?";
	echo "<form action=\"update_content.php\" method=\"post\">";
	echo "<input type=\"submit\" class='submit' name=\"oui\" value=\"Oui\" />";
	echo "<input type=\"submit\" class='submit' name=\"non\" value=\"Non\" />";
	echo "</div></form>";

}

// #################" UPDATE CONTENT #################################

if (isset($_POST["oui"])||isset($_GET["dump"])){

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

if ($offsettable>=0){
	if (UpdateContent($db,$duree,$rowlimit))
	{
    echo "<br>Redirection automatique sinon cliquez <a href=\"update_content.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\">ici</a>";
    echo "<script>window.location=\"update_content.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt\";</script>";
	flush();    
	exit;
	}
}
else  { 
//echo "<div align='center'><p>Terminé. Nombre de requêtes totales traitées : $cpt</p></div>";
}

}
	
if (isset($_POST["non"])){
	// Redirection à faire
	}
?>
