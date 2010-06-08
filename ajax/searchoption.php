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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"searchoption.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();

// Non define case

if (isset($_POST["itemtype"]) && isset($_POST["field"]) ) {

   $addmeta="";
   if (isset($_POST['meta'])&&$_POST['meta']) {
      $addmeta='2';
   } else {
      $_POST['meta']=0;
   }

   $actions=Search::getActionsFor($_POST["itemtype"],$_POST["field"]);
   $randsearch=-1;
   $dropdownname="searchtype$addmeta".$_POST["itemtype"].$_POST["num"];
   $searchopt=array();

   if (count($actions)>0){
      // get already get search options
      if (isset($actions['searchopt'])) {
         $searchopt=$actions['searchopt'];
         // No name for clean array whith quotes
         unset($searchopt['name']);
         unset($actions['searchopt']);
      }
      $randsearch=Dropdown::showFromArray("searchtype".$addmeta."[".$_POST["num"]."]",
                           $actions,array('value' => $_POST["searchtype"]));
   }
   echo "&nbsp;<span id='span$dropdownname'>\n";

   $_REQUEST['searchtype']=$_POST["searchtype"];
   $_REQUEST['field']=$_POST["field"];
   $_REQUEST['itemtype']=$_POST["itemtype"];
   $_REQUEST['num']=$_POST["num"];
   $_REQUEST['value']=stripslashes($_POST['value']);
   $_REQUEST['meta']=$_POST['meta'];
   $_REQUEST['searchopt']=serialize($searchopt);
   include(GLPI_ROOT."/ajax/searchoptionvalue.php");
   echo "</span>\n";

   $paramsaction=array( 'searchtype'   => '__VALUE__',
                        'field'        => $_POST["field"],
                        'itemtype'     => $_POST["itemtype"],
                        'num'          => $_POST["num"],
                        'value'        => rawurlencode(stripslashes($_POST['value'])),
                        'searchopt'    => $searchopt,
                        'meta'         => $_POST['meta'],);

   ajaxUpdateItemOnSelectEvent("dropdown_searchtype".$addmeta."[".$_POST["num"]."]$randsearch","span$dropdownname",
                                 $CFG_GLPI["root_doc"]."/ajax/searchoptionvalue.php",$paramsaction,false);
//    ajaxUpdateItem("span$dropdownname",$CFG_GLPI["root_doc"]."/ajax/searchoptionvalue.php",
//                      $paramsaction, false,"dropdown_searchtype".$addmeta."[".$_POST["num"]."]$randsearch");

}

?>
