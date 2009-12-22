<?php
/*
 * @version $Id: ruleactionvalue.php 9768 2009-12-16 15:44:16Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
if (strpos($_SERVER['PHP_SELF'],"searchoptionvalue.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();
//print_r($_REQUEST);
if (isset($_REQUEST['searchtype'])) {
   $searchopt=unserialize(stripslashes($_REQUEST['searchopt']));
   $inputname='contains['.$_REQUEST['num'].']';
   //print_r($searchopt);
   $display=false;
   switch ($_REQUEST['searchtype']) {
      case "equals" :
        if (isset($searchopt['field'])) {
            //echo $searchopt['field'];
            switch ($searchopt['field']) {
               case "name":
               case "completename":
                  if ($searchopt['table']=='glpi_users') {
                     User::dropdown(array('name'      => $inputname,
                                          'value'     => $_REQUEST['value'],
                                          'comments'  => false,
                                          'all'       => -1,
                                          'right'     => 'all'));
                  } else {
                     Dropdown::show(getItemTypeForTable($searchopt['table']),
                                    array('value'     => $_REQUEST['value'],
                                          'name'      => $inputname,
                                          'comments'  => 0));
                  }
                  $display=true;
                  break;
            }
        }
        break;
   }


//    static function dropdownValue($table,$myname,$value='',$display_comment=1,$entity_restrict=-1,
//                           $update_item="",$used=array(),$auto_submit=0) {


   // Default case : text field
   if (!$display) {
        echo "<input type='text' size='13' name=\"$inputname\" value=\"".$_REQUEST['value']."\" >";
   }
}

//ajaxFooter();


?>
