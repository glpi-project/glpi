<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
* @since version 0.85
*/

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"getDropdownNumber.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

Session::checkLoginUser();

$used = array();

if (isset($_GET['used'])) {
   $used = $_GET['used'];
}

if (!isset($_GET['value'])) {
   $_GET['value'] = 0;
}

$one_item = -1;
if (isset($_GET['_one_id'])) {
   $one_item = $_GET['_one_id'];
}

if (!isset($_GET['page'])) {
   $_GET['page']       = 1;
   $_GET['page_limit'] = $CFG_GLPI['dropdown_max'];
}

if (isset($_GET['toadd'])) {
   $toadd = $_GET['toadd'];
} else {
   $toadd = array();
}

$datas = array();
// Count real items returned
$count = 0;

if ($_GET['page'] == 1) {
   if (count($toadd)) {
      foreach ($toadd as $key => $val) {
         if (($one_item < 0) || ($one_item == $key)) {
            array_push($datas, array('id'   => $key,
                                     'text' => strval(stripslashes($val))));
         }
      }
   }
}

$values = array();
if (!empty($_GET['searchText'])) {
   for ($i=$_GET['min'] ; $i<=$_GET['max'] ; $i+=$_GET['step']) {
      if (strstr($i, $_GET['searchText'])) {
         $values[$i] = $i;
      }
   }
} else {
   for ($i=$_GET['min'] ; $i<=$_GET['max'] ; $i+=$_GET['step']) {
      $values[$i] = $i;
   }
}

if ($one_item < 0 && count($values)) {
   $start  = ($_GET['page']-1)*$_GET['page_limit'];
   $tosend = array_splice($values,$start, $_GET['page_limit']);
   foreach ($tosend as $i) {
      $txt = $i;
      if (isset($_GET['unit'])) {
         $txt = Dropdown::getValueWithUnit($i,$_GET['unit']);
      }
      array_push($datas, array('id'   => $i,
                               'text' => strval($txt)));
      $count++;
   }

} else {
   if (!isset($toadd[$one_item])) {
      if (isset($_GET['unit'])) {
         $txt = Dropdown::getValueWithUnit($one_item,$_GET['unit']);
      }
      array_push($datas, array('id'   => $one_item,
                               'text' => strval(stripslashes($txt))));
      $count++;
   }
}

if (($one_item >= 0)
    && isset($datas[0])) {
   echo json_encode($datas[0]);
} else {
   $ret['results'] = $datas;
   $ret['count']   = $count;
   echo json_encode($ret);
}
?>