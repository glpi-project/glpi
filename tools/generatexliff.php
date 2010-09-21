<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Remi Collet
// Purpose of file: Purge history with some criterias
// ----------------------------------------------------------------------
ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");

if ($argv) {
   for ($i=1;$i<$_SERVER['argc'];$i++) {
      $it = explode("=",$_SERVER['argv'][$i]);
      $it[0] = preg_replace('/^--/','',$it[0]);
      $_GET[$it[0]] = $it[1];
   }
}

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$CFG_GLPI["debug"]=0;


if (!isset($_GET['file'])) {
   print "


Usage : php generatexliff.php --en=#   --lang=#   --file=#

   With en english locale file
   With lang lang file to process
   With file lang file to process.\n\n";
   die();
}

// TODO complete it : http://translate.sourceforge.net/wiki/l10n/pluralforms
 $plurals=array('bg' => array(2,'(n != 1)'),
            'ca' => array(2,'(n != 1)'),
            'cs' => array(3,'(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'),
            'de' => array(2,'(n != 1)'),
            'da' => array(2,'(n != 1)'), // old dk_DK
            'nl' => array(2,'(n != 1)'),
            'en' => array(2,'(n != 1)'),
            'es_AR' => array(2,'(n != 1)'),
            'es' => array(2,'(n != 1)'),
            'es_MX' => array(2,'(n != 1)'),
            'fr' => array(2,'(n > 1)'),
            'gl_ES' => array(),
            'el_EL' => array(),
            'he_HE' => array(),
            'hr_HR' => array(),
            'hu_HU' => array(),
            'it_IT' => array(),
            'lv_LV' => array(),
            'lt_LT' => array(),
            'no_NB' => array(),
            'no_NN' => array(),
            'pl_PL' => array(),
            'pt_PT' => array(),
            'pt_BR' => array(),
            'ro_RO' => array(),
            'ru_RU' => array(),
            'sl_SL' => array(),
            'sv_SE' => array(),
            'tr_TR' => array(),
            'ua_UA' => array(),
            'ja_JP' => array(),
            'zh_CN' => array(1,0),
            'zh_TW' => array(1,0));


require($_GET['en']);
$LANG_EN=$LANG;
require($_GET['file']);

$plural='TODO';
if (isset($plurals[$_GET['lang']][0])) {
   $plural=$plurals[$_GET['lang']][0];
}
$nplural='TODO';
if (isset($plurals[$_GET['lang']][1])) {
   $nplural=$plurals[$_GET['lang']][1];
}

$fp = fopen($_GET['lang'].'.xlf', 'w+');

fwrite($fp,"<?xml version='1.0' encoding='utf-8'?>\n");
fwrite($fp,"<xliff xmlns=\"urn:oasis:names:tc:xliff:document:1.2\" version=\"1.2\">");
fwrite($fp,"<file source-language=\"en\" target-language=\"".$_GET['lang']."\" datatype=\"plaintext\" nplural=\"$plural\" plural=\"$nplural\">\n");
fwrite($fp,"<body>\n");


$already_pushed=array();

foreach ($LANG_EN as $module => $tab) {
   foreach ($tab as $ID => $string) {

      $approved ="";
      $translated="";
      if (isset($LANG[$module][$ID])) {
         if (strcmp($LANG[$module][$ID],$string) !=0 || $_GET['lang']=='en') {
            $approved=" approved=\"yes\" ";
            $translated=$LANG[$module][$ID];
         }
      }
      if (!isset($already_pushed[$string])) {
         $string_id=str_replace('<br>','BR',$string);
         fwrite($fp,"<trans-unit id=\"".cleanforID($string)."\"$approved>\n");
         fwrite($fp,"<source>".cleanforData($string)."</source>\n");
         fwrite($fp,"<target>".cleanforData($translated)."</target>\n");
         fwrite($fp,"</trans-unit>\n");
         $already_pushed[$string]=1;
      } else {
         echo "Already add : $string\n";
      }
   }
}

fwrite($fp,"</body>\n");
fwrite($fp,"</file>\n");
fwrite($fp,"</xliff>\n");
fclose($fp);

function cleanforID($string){
   $string= str_replace('<br>','BR',$string);
   $string = str_replace('&','&amp;',$string);
   return $string;
}
function cleanforData($string){
   $string = str_replace('<br>','<x id="x1" ctype="x-html-br" equiv-text=" "/>',$string);
   $string = str_replace('&','&amp;',$string);
   return $string;
}
?>