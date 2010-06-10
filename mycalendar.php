<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 
 // inspiré du script mycalendar de PASCAL, CLAUDE MANON
//pascal.manon@caramail.com



include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
checkauthentication("normal");

// Section de configuration

  $bgcolor="d6d6f5" ;        // Couleur de fond
  $daybgcolor="6F6FDB" ;     // Couleur des jours de la semaine
  $dombgcolor="339999" ;     // Couleur du jour sélectionné
  $dayholcolor="6F6FDB" ;     // Couleur des WE

  // Mois
  $month[0] = $lang["calendarM"][0] ;
  $month[1] = $lang["calendarM"][1] ;
  $month[2] = $lang["calendarM"][2] ;
  $month[3] = $lang["calendarM"][3] ;
  $month[4] = $lang["calendarM"][4];
  $month[5] = $lang["calendarM"][5] ;
  $month[6] = $lang["calendarM"][6] ;
  $month[7] = $lang["calendarM"][7] ;
  $month[8] = $lang["calendarM"][8] ;
  $month[9] = $lang["calendarM"][9] ;
  $month[10] = $lang["calendarM"][10] ;
  $month[11] = $lang["calendarM"][11] ;

  // Première lettre des jours de la semaine
  $day[0] =  $lang["calendarD"][0];
  $day[1] = $lang["calendarD"][1];
  $day[2] = $lang["calendarD"][2];
  $day[3] = $lang["calendarD"][3];
  $day[4] = $lang["calendarD"][4] ;
  $day[5] = $lang["calendarD"][4] ;
  $day[6] = $lang["calendarD"][5];

  $error01 = "Erreur : date invalide"

?>
<html>
<head>
<style>
 #general
 {
  font-family: Arial;
  font-size: 10pt;
 }

 a:link,a:active,a:visited
 {
        text-decoration:none;
        color:#000000;
 }

 a:hover
 {
        text-decoration:underline;
        color:#000000;
 }

</style>
<script language='JavaScript'>
 window.resizeTo(200,270) ;
 function modifier (jour)
 {
  window.location.href = "mycalendar.php?form=<?php

echo $form;?>&elem=<?php

 

echo $elem;?>&mois=" + document.forms["MyCalendar"].elements['month'].options[document.forms["MyCalendar"].elements['month'].selectedIndex].value + "&jour=" + jour +"&annee=" + document.forms["MyCalendar"].elements['year'].options[document.forms["MyCalendar"].elements['year'].selectedIndex].value

 }
<?php

 


if (!isset($jour))
       $jour = date("j") ;

  if (!isset($mois))
       $mois = date("m") ;

  if (!isset($annee))
       $annee = date("Y") ;

    // nombre de jours par mois
  $nbjmonth[0] = 31 ;
  $nbjmonth[1] = ($annee%4==0?($annee%100==0?($annee%400?29:28):29):28) ;
  $nbjmonth[2] = 31 ;
  $nbjmonth[3] = 30 ;
  $nbjmonth[4] = 31 ;
  $nbjmonth[5] = 30 ;
  $nbjmonth[6] = 31;
  $nbjmonth[7] = 31 ;
  $nbjmonth[8] = 30 ;
  $nbjmonth[9] = 31 ;
  $nbjmonth[10] = 30 ;
  $nbjmonth[11] = 31 ;

  if(!checkdate($mois,$jour,$annee))
  {
   echo "alert('$error01')\n" ;
   $jour = date("j") ;
   $mois = date("m") ;
   $annee = date("Y") ;
  }

  // Calcul du jour julien et du numéro du jour
  $HR = 0;
  $GGG = 1;
  if( $annee < 1582 ) $GGG = 0;
  if( $annee <= 1582 && $mois < 10 ) $GGG = 0;
  if( $annee <= 1582 && $mois == 10 && 1 < 5 ) $GGG = 0;
  $JD = -1 * floor(7 * (floor(($mois + 9) / 12) + $annee) / 4);
  $S = 1;
  if (($mois - 9)<0) $S=-1;
  $A = abs($mois - 9);
  $J1 = floor($mois + $S * floor($A / 7));
  $J1 = -1 * floor((floor($J1 / 100) + 1) * 3 / 4);
  $JD = $JD + floor(275 * $mois / 9) + 1 + ($GGG * $J1);
  $JD = $JD + 1721027 + 2 * $GGG + 367 * $annee - 0.5;



  /*$tmp = ((int)(($mois>2?$annee:$annee-1)/100)) ;
  $jj = (int)((((int)(365.25*($mois>2?$annee:$annee-1))) + ((int)(30.6001*($mois>2?$mois+1:$mois+13))) + $jour + 1720994.5 + ($annee > 1582 && $mois > 10 && $jour > 15?2-$tmp+((int)($tmp/4)):0))) ;
  $jj = (int)(($jj) % 7)*/
  $jj = (($JD+.5)%7) ;
?>
</script>
</head>
<?php

echo "<body bgcolor='#$bgcolor' onUnLoad=''>\n" ;

  echo "<center><form name='MyCalendar'>\n" ;
  echo "<table width='170' cellspacing='0' cellspading='0' border='0'><tr>\n" ;

  // Affichage de la sélection du mois et de l'année
  echo "<td><select name='month' onChange=\"modifier($jour)\">\n" ;

  for ($i=0;$i<12;$i++)
  {
   echo "<option value='".($i+1)."'".($mois==($i+1)?" selected":"").">".$month[$i]."</option>\n" ;
  }

  echo "</select></td>\n" ;

  echo "<td align='right'><select name='year' onChange=\"modifier($jour)\">\n" ;

  $y = date("Y") ;
  for ($i=$y-10;$i<$y+10;$i++)
  {
   echo "<option value='$i'".($annee==($i)?" selected":"").">$i</option>\n" ;
  }

  echo "</select></td></tr><tr><td colspan='2'>&nbsp;</td></tr>\n" ;

  echo "<tr><td colspan='2'><table width='100%' cellspacing='0' cellspading='0' border='0'>\n" ;
  echo "<tr>\n" ;

  // Affichage des jours
  for($i=0;$i<7;$i++)
  {
   echo "<td width='14%' bgcolor='#$daybgcolor'><font id='general'>".$day[$i]."</font></td>" ;
  }

  echo "</tr>\n<tr><td colspan='7'> </td></tr>\n<tr>\n" ;

  // Première ligne des jours
  $j = $jj ;//date ("w", mktime (0,0,0,$mois,1,$annee)) ;
  $dom = 1 ;
  for ($i=0;$i<7;$i++)
  {
   if ($j<=$i)
   {
        echo "<td".($dom==$jour?" bgcolor='#$dombgcolor'":"")."><a href='javascript:modifier($dom)'><font id='general'>".$dom++."</font></a></td>\n" ;
   }
   else
       echo "<td>&nbsp;</td>\n" ;
  }

  echo "</tr>\n" ;
  // Le reste
  for ($i=0;$i<5;$i++)
  {
   echo "<tr>\n" ;
   for ($j=0;$j<7;$j++)   
   {    
	$j_inac = ($j==0 || $j==6) ;
	
	if($dom < $nbjmonth[($mois-1)])
         echo "<td".($dom==$jour?" bgcolor='#$dombgcolor'":($j_inac ?" bgcolor='#$dayholcolor'":""))."><a href='javascript:modifier($dom)'><font id='general'>".$dom++."</font></a></td>\n" ;
    else if (checkdate($mois,$dom,$annee))
         echo "<td".($dom==$jour?" bgcolor='#$dombgcolor'":($j_inac ?" bgcolor='#$dayholcolor'":""))."><a href='javascript:modifier($dom)'><font id='general'>".$dom++."</font></a></td>\n" ;
    else
         echo "<td>&nbsp;</td>\n" ;

   }
   echo "</tr>\n" ;
  }

  echo "\n<tr><td colspan='10' align='center'><input type='button' onclick='window.opener.document.forms[\"$form\"].elements[\"$elem\"].value=\"$annee-$mois-$jour\";window.close()' value='Valider'>&nbsp;&nbsp;<input onclick='window.close()' type='button' value='Annuler'></td></tr></table>\n" ;

  echo "\n</tr></table>\n" ;

  echo "</td></tr></table>" ;
  echo "</form></center>" ;

  echo "</body>\n" ;
?>
</html>
