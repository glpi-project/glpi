<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if ($argv) {
   for ($i=1 ; $i<count($argv) ; $i++) {
      //To be able to use = in search filters, enter \= instead in command line
      //Replace the \= by ° not to match the split function
      $arg   = str_replace('\=', '°', $argv[$i]);
      $it    = explode("=",$arg);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      //Replace the ° by = the find the good filter
      $it           = str_replace('°', '=', $it);
      $_GET[$it[0]] = $it[1];
   }
}

if (!isset($_GET['lang'])) {
   echo "Usage move_to_po.php lang=xx_YY\n Will take the pot file and try to complete it to create initial po for the lang\n";
}

define('GLPI_ROOT', realpath('../..'));
//include (GLPI_ROOT . "/inc/includes.php");

if (!is_readable(GLPI_ROOT . "/locales/".$_GET['lang'].".php")) {
   print "Unable to read dictionary file\n";
   exit();
}
include (GLPI_ROOT . "/locales/en_GB.php");
$REFLANG = $LANG;

$lf     = fopen(GLPI_ROOT . "/locales/".$_GET['lang'].".php", "r");
$lf_new = fopen(GLPI_ROOT . "/locales/temp.php", "w+");

while (($content = fgets($lf, 4096)) !== false) {
   if (!preg_match('/string to be translated/',$content,$reg)) {
      if (fwrite($lf_new, $content) === FALSE) {
         echo "unable to write in clean lang file";
         exit;
      }
   }

}
fclose($lf);
fclose($lf_new);


include (GLPI_ROOT . "/locales/temp.php");

if (!is_readable(GLPI_ROOT . "/locales/glpi.pot")) {
   print "Unable to read glpi.pot file\n";
   exit();
}
$current_string_plural = '';

$pot = fopen(GLPI_ROOT . "/locales/glpi.pot", "r");
$po  = fopen(GLPI_ROOT . "/locales/".$_GET['lang'].".po", "w+");

$in_plural = false;

if ($pot && $po) {
   $context = '';

   while (($content = fgets($pot, 4096)) !== false) {
      if (preg_match('/^msgctxt "(.*)"$/',$content,$reg)) {
         $context = $reg[1];
      }
      if (preg_match('/^msgid "(.*)"$/',$content,$reg)) {
         $current_string = $reg[1];
      }

      if (preg_match('/^msgid_plural "(.*)"$/',$content,$reg)) {
         $current_string_plural = $reg[1];
         $sing_trans = '';
         $plural_trans = '';
      }

      // String on several lines
      if (preg_match('/^"(.*)"$/',$content,$reg)) {
         if ($in_plural) {
            $current_string_plural .= $reg[1];
         } else {
            $current_string        .= $reg[1];
         }
//          echo '-'.$current_string."-\n";
      }


      if (preg_match('/^msgstr[\[]*([0-9]*)[\]]* "(.*)"$/',$content,$reg)) {
         if (strlen($reg[1]) == 0) { //Singular
            $in_plural = false;
            if ($_GET['lang']=='en_GB') {
               $content     = "msgstr \"$current_string\"\n";
            } else {
               $translation = search_in_dict($current_string, $context);
               $content     = "msgstr \"$translation\"\n";
//              echo '+'.$current_string."+\n";
//                echo "$translation\n";
            }
         } else {

            switch ($reg[1]) {
               case "0" : // Singular
                  $in_plural = false;

//                   echo '+'.$current_string."+\n";
                  $sing_trans = search_in_dict($current_string, $context);
//                   echo "$translation\n";
                  break;

               case "1" : // Plural
                  $in_plural = true;

//                   echo '++'.$current_string."++\n";
                  $plural_trans = search_in_dict($current_string_plural, $context);
//                   echo "$translation\n";
                  break;
            }

            if ($reg[1] == "1") {
               if ($_GET['lang']=='en_GB') {
                  $content = "msgstr[0] \"$current_string\"\n";
                  $content .= "msgstr[1] \"$current_string_plural\"\n";
               } else {
//                   echo $current_string.'->'.$sing_trans.' '.$current_string_plural.'->'.$plural_trans."\n";
                  if (!strlen($sing_trans) || !strlen($plural_trans)) {
//                      echo "clean\n";
                     $sing_trans = '';
                     $plural_trans = '';
                  }
                  $content = "msgstr[0] \"$sing_trans\"\n";
                  $content .= "msgstr[1] \"$plural_trans\"\n";
               }
            } else {
                  $content='';
            }
         }
         $context = '';
      }
     // Standard replacement
     $content = preg_replace('/charset=CHARSET/','charset=UTF-8',$content);

     if (preg_match('/Plural-Forms/',$content)) {
         $content = "\"Plural-Forms: nplurals=2; plural=(n != 1)\\n\"\n";
     }

      if (fwrite($po, $content) === FALSE) {
         echo "unable to write in po file";
         exit;
      }

   }
}
fclose($pot);
fclose($po);


function search_in_dict($string, $context) {
   global $REFLANG, $LANG;

   if ($context) {
      $string = "$context/$string";
   }

   $ponctmatch = "([\.: \(\)]*)";
   $varmatch = "(%s)*";

   if (preg_match("/$varmatch$ponctmatch(.*)$ponctmatch$varmatch$/U",$string,$reg)) {
//       print_r($reg);
      $left   = $reg[1];
      $left   .= $reg[2];
      $string = $reg[3];
      $right  = $reg[4];
      if (isset($reg[5])) {
         $right  .= $reg[5];
      }
   }
//    echo $left.' <- '.$string.' -> '.$right."\n";
   foreach ($REFLANG as $mod => $data) {
      foreach ($data as $key => $val) {
         if (!isset($LANG[$mod][$key])) {
            continue;
         }
         // Search same case with punc
         if (strcmp($val,$left.$string.$right) === 0) {
            return $LANG[$mod][$key];
         }
         // Search same case with punc
         if (strcasecmp($val,$left.$string.$right) === 0) {
            return $LANG[$mod][$key];
         }

         // Search same case with left punc
         if (strcmp($val,$left.$string) === 0) {
            return $LANG[$mod][$key].$right;
         }
         // Search same case with left punc
         if (strcasecmp($val,$left.$string) === 0) {
            return $LANG[$mod][$key].$right;
         }

         // Search same case with right punc
         if (strcmp($val,$string.$right) === 0) {
            return $left.$LANG[$mod][$key];
         }
         // Search same case with right punc
         if (strcasecmp($val,$string.$right) === 0) {
            return $left.$LANG[$mod][$key];
         }

         // Search same case without punc
         if (strcmp($val,$string) === 0) {
            return $left.$LANG[$mod][$key].$right;
         }
         // Search non case sensitive
         if (strcasecmp($val,$string) === 0) {
            return $left.$LANG[$mod][$key].$right;
         }

      }
   }

   return "";
}
?>