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
* @since version 0.85
*/

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"getDropdownNumber.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

Session::checkLoginUser();

$used = array();

if (isset($_POST['used'])) {
   $used = $_POST['used'];
}

if (!isset($_POST['value'])) {
   $_POST['value'] = 0;
}

$one_item = -1;
if (isset($_POST['_one_id'])) {
   $one_item = $_POST['_one_id'];
}

if (!isset($_POST['page'])) {
   $_POST['page']       = 1;
   $_POST['page_limit'] = $CFG_GLPI['dropdown_max'];
}

if (isset($_POST['toadd'])) {
   $toadd = $_POST['toadd'];
} else {
   $toadd = array();
}

$data = array();
// Count real items returned
$count = 0;

if ($_POST['page'] == 1) {
   if (count($toadd)) {
      foreach ($toadd as $key => $val) {
         if (($one_item < 0) || ($one_item == $key)) {
            array_push($data, array('id'   => $key,
                                     'text' => strval(stripslashes($val))));
         }
      }
   }
}

$values = array();
if (!empty($_POST['searchText'])) {
   for ($i=$_POST['min'] ; $i<=$_POST['max'] ; $i+=$_POST['step']) {
      if (strstr($i, $_POST['searchText'])) {
         $values[$i] = $i;
      }
   }
} else {
   for ($i=$_POST['min'] ; $i<=$_POST['max'] ; $i+=$_POST['step']) {
      $values[$i] = $i;
   }
}

if ($one_item < 0 && count($values)) {
   $start  = ($_POST['page']-1)*$_POST['page_limit'];
   $tosend = array_splice($values,$start, $_POST['page_limit']);
   foreach ($tosend as $i) {
      $txt = $i;
      if (isset($_POST['unit'])) {
         $txt = Dropdown::getValueWithUnit($i,$_POST['unit']);
      }
      array_push($data, array('id'   => $i,
                               'text' => strval($txt)));
      $count++;
   }

} else {
   if (!isset($toadd[$one_item])) {
      $value = $one_item;
      if (isset($_POST['min']) && $value < $_POST['min']) {
         $value = $_POST['min'];
      } else if (isset($_POST['max']) && $value > $_POST['max']) {
         $value = $_POST['max'];
      }

      if (isset($_POST['unit'])) {
         $txt = Dropdown::getValueWithUnit($value, $_POST['unit']);
      }
      array_push($data, array('id'   => $value,
                               'text' => strval(stripslashes($txt))));
      $count++;
   }
}

if (($one_item >= 0)
    && isset($data[0])) {
   echo json_encode($data[0]);
} else {
   $ret['results'] = $data;
   $ret['count']   = $count;
   echo json_encode($ret);
}
?>
